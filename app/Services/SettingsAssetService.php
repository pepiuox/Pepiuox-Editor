<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

/**
 * Subida y borrado de los archivos de identidad visual pública.
 *
 * Sólo toca dos carpetas, y sólo con nombres generados por el servidor:
 *
 * ```
 * uploads/settings/logos/<slot>-<12 hex>.<ext>
 * uploads/settings/favicons/<slot>-<12 hex>.<ext>
 * ```
 *
 * El nombre que envía el usuario **nunca** se usa para construir la ruta: sólo
 * se lee su extensión, y esa extensión ya pasó la lista blanca. Así no hay
 * forma de escribir fuera del directorio ni de colar `../` o `.php`.
 *
 * Orden de la sustitución, elegido para no quedarse sin imagen nunca:
 *  1. Validar (extensión, tamaño, MIME real, contenido).
 *  2. Escribir el archivo nuevo con nombre nuevo.
 *  3. Actualizar la fila de `settings`.
 *  4. Sólo entonces borrar el archivo anterior.
 * Si el paso 3 falla, se borra el archivo nuevo y la configuración anterior
 * sigue apuntando a un archivo que existe.
 *
 * @package App\Services
 * @since   2026-10-26
 */
final class SettingsAssetService
{
    /** Extensiones admitidas para logotipos. */
    private const LOGO_EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

    /** Extensiones admitidas para favicons. */
    private const FAVICON_EXTENSIONS = ['ico', 'png', 'svg'];

    /** 2 MB: un logotipo no necesita más. */
    private const LOGO_MAX_BYTES = 2097152;

    /** 512 KB: un favicon debe ser pequeño por definición. */
    private const FAVICON_MAX_BYTES = 524288;

    /**
     * @param string $uploadsPath Raíz de subidas (por defecto `UPLOADS_PATH`).
     */
    public function __construct(
        private readonly string $uploadsPath = UPLOADS_PATH
    ) {
    }

    /**
     * Guarda el archivo recibido en el hueco indicado.
     *
     * @param string               $slot Slot de `PublicThemeService::LOGO_SLOTS` o `FAVICON_SLOTS`.
     * @param array<string, mixed>|null $file Entrada de `$_FILES` (un solo archivo).
     * @return array{ok: bool, message: string, warnings: array<int, string>, url: ?string}
     */
    public function store(string $slot, ?array $file): array
    {
        $kind = $this->kindOf($slot);
        if ($kind === null) {
            return $this->fail('El hueco solicitado no existe.');
        }

        $isFavicon = $kind === 'favicon';

        $validator = new ImageUploadValidator(
            $isFavicon ? self::FAVICON_EXTENSIONS : self::LOGO_EXTENSIONS,
            $isFavicon ? self::FAVICON_MAX_BYTES : self::LOGO_MAX_BYTES,
            $isFavicon ? 1024 : 4096
        );

        $checked = $validator->validate($file);
        if (!$checked['ok']) {
            return $this->fail($checked['error'] ?? 'El archivo no es válido.');
        }

        $warnings = [];
        $extension = $checked['extension'];
        $directory = $this->absoluteDirectory($kind);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return $this->fail('No se pudo crear el directorio ' . $this->relativeDirectory($kind) . '.');
        }

        $filename = $slot . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target   = $directory . DIRECTORY_SEPARATOR . $filename;

        if ($extension === 'svg') {
            $raw = (string) @file_get_contents($checked['tmp_name']);
            [$clean, $svgErrors, $removed] = $this->sanitizeSvg($raw);
            if ($clean === null) {
                return $this->fail($svgErrors[0] ?? 'El SVG no superó la validación.');
            }
            if ($removed !== []) {
                $warnings[] = 'Se eliminó del SVG: ' . implode(', ', $removed) . '.';
            }
            if (@file_put_contents($target, $clean, LOCK_EX) === false) {
                return $this->fail('No se pudo escribir el archivo en el servidor.');
            }
        } elseif (!@move_uploaded_file($checked['tmp_name'], $target)) {
            return $this->fail('No se pudo mover el archivo al directorio de destino.');
        }

        @chmod($target, 0644);

        // Comprobación final: el archivo quedó donde se esperaba.
        if (!is_file($target)) {
            return $this->fail('El archivo no quedó guardado correctamente.');
        }

        $relative = $this->relativeDirectory($kind) . '/' . $filename;
        $previous = PublicThemeService::assetRelativePath($slot);

        try {
            Setting::updateBatch([
                PublicThemeService::GROUP => [
                    $slot => ['value' => $relative, 'type' => 'string'],
                ],
            ]);
            PublicThemeService::flush();
        } catch (\Throwable $e) {
            // La configuración no se actualizó: el archivo nuevo sobra.
            @unlink($target);
            error_log('[SettingsAsset] No se pudo guardar la configuración: ' . $e->getMessage());
            return $this->fail('No se pudo guardar la configuración del archivo.');
        }

        if ($previous !== null && $previous !== $relative) {
            $this->unlinkRelative($previous);
        }

        if ($isFavicon && $checked['width'] > 512) {
            $warnings[] = 'El favicon mide ' . $checked['width'] . ' px de lado; los navegadores lo reducirán.';
        }

        return [
            'ok'       => true,
            'message'  => 'Imagen actualizada.',
            'warnings' => $warnings,
            'url'      => url('uploads/' . $relative),
        ];
    }

    /**
     * Elimina el archivo del hueco y limpia la configuración.
     *
     * @return array{ok: bool, message: string, warnings: array<int, string>, url: null}
     */
    public function delete(string $slot): array
    {
        $kind = $this->kindOf($slot);
        if ($kind === null) {
            return $this->fail('El hueco solicitado no existe.');
        }

        $previous = PublicThemeService::get($slot);
        if ($previous === '') {
            return ['ok' => true, 'message' => 'No había ninguna imagen configurada.', 'warnings' => [], 'url' => null];
        }

        try {
            Setting::updateBatch([
                PublicThemeService::GROUP => [
                    $slot => ['value' => '', 'type' => 'string'],
                ],
            ]);
            PublicThemeService::flush();
        } catch (\Throwable $e) {
            error_log('[SettingsAsset] No se pudo borrar la configuración: ' . $e->getMessage());
            return $this->fail('No se pudo actualizar la configuración.');
        }

        $this->unlinkRelative($previous);

        return ['ok' => true, 'message' => 'Imagen eliminada.', 'warnings' => [], 'url' => null];
    }

    /**
     * Sanitiza un SVG y devuelve el resultado en el formato que espera `store()`.
     *
     * @return array{0: ?string, 1: array<int, string>, 2: array<int, string>}
     */
    private function sanitizeSvg(string $raw): array
    {
        $sanitizer = new SvgSanitizer();
        $result    = $sanitizer->sanitize($raw);
        if (!$result['ok']) {
            return [null, [$result['error'] ?? 'SVG no válido.'], []];
        }
        return [$result['svg'], [], $result['removed']];
    }

    /**
     * Tipo de hueco (`logo` o `favicon`), o null si no existe.
     */
    private function kindOf(string $slot): ?string
    {
        if (array_key_exists($slot, PublicThemeService::LOGO_SLOTS)) {
            return 'logo';
        }
        if (array_key_exists($slot, PublicThemeService::FAVICON_SLOTS)) {
            return 'favicon';
        }
        return null;
    }

    /**
     * Ruta relativa (dentro de `uploads/`) del hueco.
     */
    private function relativeDirectory(string $kind): string
    {
        return $kind === 'favicon' ? 'settings/favicons' : 'settings/logos';
    }

    /**
     * Ruta absoluta del hueco, verificada dentro de `uploads/`.
     */
    private function absoluteDirectory(string $kind): string
    {
        return rtrim(str_replace('\\', '/', $this->uploadsPath), '/') . '/' . $this->relativeDirectory($kind);
    }

    /**
     * Borra un archivo por su ruta relativa, sólo si está dentro de
     * `uploads/settings/`. Ignora cualquier ruta que no cumpla el patrón.
     */
    private function unlinkRelative(string $relative): void
    {
        if (preg_match('#^settings/(logos|favicons)/[A-Za-z0-9][A-Za-z0-9._-]{0,120}$#', $relative) !== 1) {
            return;
        }
        if (str_contains($relative, '..')) {
            return;
        }

        $absolute = rtrim(str_replace('\\', '/', $this->uploadsPath), '/') . '/' . $relative;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Respuesta de error homogénea.
     *
     * @return array{ok: false, message: string, warnings: array<int, string>, url: null}
     */
    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'warnings' => [], 'url' => null];
    }
}
