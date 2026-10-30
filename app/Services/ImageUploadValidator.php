<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Validación de una imagen subida por HTTP.
 *
 * Reglas, en este orden (defensa en profundidad, de lo barato a lo caro):
 *  1. El código de error de PHP debe ser `UPLOAD_ERR_OK`.
 *  2. La extensión debe estar en la lista blanca **del hueco** que la recibe.
 *  3. El tamaño debe ser mayor que 0 y no superar el tope del hueco.
 *  4. El archivo debe venir de una subida real (`is_uploaded_file`).
 *  5. El MIME **real** (finfo) debe ser uno de los admitidos para esa extensión.
 *  6. Si es una imagen raster, `getimagesize` debe reconocerla y devolver
 *     dimensiones dentro de los límites.
 *
 * No mueve ni renombra nada: eso es responsabilidad de `SettingsAssetService`.
 * Se mantiene aparte de `AssetUploadService` a propósito: aquel sirve al editor
 * visual y funciona; reescribirlo para compartir código pondría en riesgo la
 * ruta de subida del editor. La duplicación es de 40 líneas y está documentada
 * como trade-off en `docs/PUBLIC_THEME.md`.
 *
 * @package App\Services
 * @since   2026-10-26
 */
final class ImageUploadValidator
{
    /**
     * MIME admitidos por extensión.
     *
     * `finfo` es del sistema y no es consistente con los SVG e ICO: un SVG
     * legítimo puede llegar como `text/plain` o `text/xml`, y un ICO como
     * `application/octet-stream`. Se aceptan esas variantes y se compensa con
     * la comprobación de contenido posterior.
     *
     * @var array<string, array<int, string>>
     */
    private const MIME_BY_EXTENSION = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'svg'  => ['image/svg+xml', 'image/svg', 'text/plain', 'text/xml', 'application/xml'],
        'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'application/octet-stream'],
    ];

    /**
     * @param array<int, string> $allowedExtensions Extensiones permitidas para el hueco.
     * @param int                $maxBytes          Tope en bytes.
     * @param int                $maxDimension      Lado máximo aceptado en píxeles (0 = sin límite).
     */
    public function __construct(
        private readonly array $allowedExtensions,
        private readonly int $maxBytes,
        private readonly int $maxDimension = 4096
    ) {
    }

    /**
     * Valida una entrada de `$_FILES`.
     *
     * @param array<string, mixed>|null $file Entrada de un solo archivo.
     * @return array{ok: bool, error: ?string, extension: string, mime: string, size: int, width: int, height: int, tmp_name: string}
     */
    public function validate(?array $file): array
    {
        $result = [
            'ok'        => false,
            'error'     => null,
            'extension' => '',
            'mime'      => '',
            'size'      => 0,
            'width'     => 0,
            'height'    => 0,
            'tmp_name'  => '',
        ];

        if (!$file || !isset($file['name'], $file['tmp_name'], $file['error'])) {
            $result['error'] = 'No se recibió ningún archivo.';
            return $result;
        }
        if (is_array($file['name'])) {
            $result['error'] = 'Sólo se admite un archivo por vez.';
            return $result;
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->describeUploadError((int) $file['error']);
            return $result;
        }

        $tmp  = (string) $file['tmp_name'];
        $name = (string) $file['name'];
        $size = (int) ($file['size'] ?? 0);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $result['extension'] = $extension;
        $result['size']      = $size;
        $result['tmp_name']  = $tmp;

        if (!in_array($extension, $this->allowedExtensions, true)) {
            $result['error'] = 'Formato no permitido. Admitidos: ' . implode(', ', $this->allowedExtensions) . '.';
            return $result;
        }
        if ($size <= 0) {
            $result['error'] = 'El archivo está vacío.';
            return $result;
        }
        if ($size > $this->maxBytes) {
            $result['error'] = 'El archivo supera el máximo de ' . round($this->maxBytes / 1024) . ' KB.';
            return $result;
        }
        if (!is_uploaded_file($tmp)) {
            $result['error'] = 'El archivo no procede de una subida válida.';
            return $result;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        $result['mime'] = $mime;

        $allowed = self::MIME_BY_EXTENSION[$extension] ?? [];
        if (!in_array($mime, $allowed, true)) {
            $result['error'] = sprintf('El contenido real (%s) no corresponde a un .%s.', $mime, $extension);
            return $result;
        }

        if ($extension === 'svg') {
            $raw = (string) @file_get_contents($tmp, false, null, 0, 4096);
            if (!str_contains(strtolower($raw), '<svg')) {
                $result['error'] = 'El archivo no contiene un SVG válido.';
                return $result;
            }
            $result['ok'] = true;
            return $result;
        }

        if ($extension === 'ico') {
            // Cabecera ICO: 0x00 0x00 0x01 0x00
            $magic = (string) @file_get_contents($tmp, false, null, 0, 4);
            if (strlen($magic) < 4 || substr($magic, 0, 4) !== "\x00\x00\x01\x00") {
                $result['error'] = 'El archivo no tiene la cabecera de un icono ICO.';
                return $result;
            }
            $result['ok'] = true;
            return $result;
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            $result['error'] = 'El archivo no es una imagen reconocible.';
            return $result;
        }

        $result['width']  = (int) $info[0];
        $result['height'] = (int) $info[1];

        if ($this->maxDimension > 0
            && ($result['width'] > $this->maxDimension || $result['height'] > $this->maxDimension)) {
            $result['error'] = 'La imagen supera los ' . $this->maxDimension . ' px de lado.';
            return $result;
        }

        $result['ok'] = true;
        return $result;
    }

    /**
     * Traduce el código de error de PHP a un mensaje accionable.
     */
    private function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite configurado en el servidor.',
            UPLOAD_ERR_PARTIAL                        => 'La subida se interrumpió a medias. Inténtalo de nuevo.',
            UPLOAD_ERR_NO_FILE                        => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'El servidor no tiene directorio temporal.',
            UPLOAD_ERR_CANT_WRITE                     => 'El servidor no pudo escribir el archivo temporal.',
            UPLOAD_ERR_EXTENSION                      => 'Una extensión de PHP detuvo la subida.',
            default                                   => 'Error desconocido al subir el archivo.',
        };
    }
}
