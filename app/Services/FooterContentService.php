<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\LinkGuard;
use Throwable;

/**
 * Contenido editable del pie del sitio público.
 *
 * ── Qué resuelve ────────────────────────────────────────────────────
 * El pie de `views/public/layout.php` tenía todo su texto escrito a mano:
 * el eslogan, dos columnas de enlaces y un correo. Se pintaba en **todas** las
 * páginas públicas, pero no había forma de cambiarlo sin editar el archivo.
 *
 * Aquí se aplica la misma receta que en `LandingContentService`, para que el
 * panel se comporte igual en los dos sitios:
 *
 *   valor guardado en `settings` (grupo `footer`)  →  valor por defecto en código
 *
 * Los valores por defecto viven en el código a propósito: el pie debe verse
 * completo antes de que nadie guarde nada, y una instalación nueva no puede
 * arrancar con un pie vacío. La migración siembra las mismas claves para que
 * sean editables.
 *
 * ── Estructura ──────────────────────────────────────────────────────
 * Las columnas de enlaces son una **lista JSON** de columnas, y cada columna
 * lleva su lista de enlaces. Así se pueden añadir, quitar y reordenar sin tocar
 * el esquema, que es lo que ya se hace con las tarjetas de la portada.
 *
 * ── Marcadores del copyright ────────────────────────────────────────
 * El texto del copyright admite dos marcadores que se sustituyen al pintar:
 * `{year}` por el año en curso y `{site}` por el nombre del sitio. Se sustituyen
 * en la vista, no aquí, para que el texto guardado siga siendo legible.
 *
 * @package App\Services
 * @since   2026-10-31
 */
final class FooterContentService
{
    /** Permiso del panel que gobierna la edición del pie. */
    public const PERMISSION = 'settings.manage';

    /** Topes: una lista no puede crecer sin límite. */
    public const MAX_COLUMNS = 4;
    public const MAX_LINKS   = 6;

    private const MAX_SHORT = 60;
    private const MAX_TEXT  = 400;

    /** @var array<string, mixed>|null Caché de petición. */
    private static ?array $resolved = null;

    /**
     * Contenido por defecto del pie.
     *
     * Los enlaces apuntan a páginas que **existen**: los antiguos
     * (`about`, `privacy`, `terms`) no estaban creados y llevaban a un 404.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'about'          => 'Editor y gestor de contenidos en PHP y MariaDB. Instalado en tu propio servidor, sin dependencias de terceros.',
            'copyright'      => '© {year} {site}. Todos los derechos reservados.',
            'show_contact'   => true,
            'contact_title'  => 'Contacto',
            'contact_email'  => 'info@pepiuox.com',
            'contact_note'   => '',
            'columns'        => [
                [
                    'title' => 'Navegación',
                    'links' => [
                        ['label' => 'Inicio',        'href' => '/'],
                        ['label' => 'Funciones',     'href' => '/funciones'],
                        ['label' => 'Cómo funciona', 'href' => '/como-funciona'],
                    ],
                ],
                [
                    'title' => 'Plataforma',
                    'links' => [
                        ['label' => 'Seguridad',     'href' => '/seguridad'],
                        ['label' => 'Quiénes somos', 'href' => '/quienes-somos'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Contenido resuelto del pie.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = self::defaults();
        $stored   = self::stored();

        $out = $defaults;

        foreach (['about' => self::MAX_TEXT, 'copyright' => self::MAX_SHORT * 3,
                  'contact_title' => self::MAX_SHORT, 'contact_note' => self::MAX_TEXT] as $clave => $max) {
            if (isset($stored[$clave]) && is_string($stored[$clave])) {
                $valor = trim($stored[$clave]);
                // El copyright y el «acerca de» pueden quedar vacíos a propósito;
                // el título de contacto no: sin título la columna no se pinta.
                if ($clave !== 'contact_title' || $valor !== '') {
                    $out[$clave] = mb_substr($valor, 0, $max);
                }
            }
        }

        if (array_key_exists('show_contact', $stored)) {
            $out['show_contact'] = filter_var($stored['show_contact'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($stored['contact_email']) && is_string($stored['contact_email'])) {
            $correo = trim($stored['contact_email']);
            $out['contact_email'] = filter_var($correo, FILTER_VALIDATE_EMAIL) !== false ? $correo : '';
        }

        $columnas = self::sanitizeColumns($stored['columns'] ?? null);
        if ($columnas !== []) {
            $out['columns'] = $columnas;
        }

        self::$resolved = $out;

        return $out;
    }

    /**
     * Invalida la caché de la petición (tras guardar).
     */
    public static function flush(): void
    {
        self::$resolved = null;
        self::$builder = null;
    }

    /** Tope del HTML del constructor: un pie no necesita más. */
    public const MAX_HTML = 200_000;
    public const MAX_CSS  = 100_000;
    public const MAX_JS   = 50_000;

    /** @var array<string, mixed>|null Caché de la petición para el constructor. */
    private static ?array $builder = null;

    /**
     * Contenido del pie hecho con el constructor visual.
     *
     * ── Por qué vive aparte del contenido estructurado ───────────────
     * El pie tiene dos formas de editarse y deben convivir:
     *
     *  - **Estructurado** (`columns`, `about`, `contact_*`): campos con validación,
     *    rápido de retocar y sin riesgo de romper la maqueta.
     *  - **Constructor** (GrapesJS): el mismo editor que las páginas, para quien
     *    quiera componer el pie a mano, con los mismos bloques.
     *
     * Si hay HTML del constructor y está activo, manda él. Si no, se pinta la
     * versión estructurada. Así nada se pierde: apagar el constructor devuelve el
     * pie anterior intacto.
     *
     * @return array{html:string, css:string, js:string, data:string, enabled:bool}
     */
    public static function builder(): array
    {
        if (self::$builder !== null) {
            return self::$builder;
        }

        $leer = static function (string $clave): string {
            try {
                return (string) (SettingService::get('footer', $clave, '') ?? '');
            } catch (Throwable $e) {
                error_log('[FooterContent] No se pudo leer ' . $clave . ': ' . $e->getMessage());
                return '';
            }
        };

        $html = $leer('builder_html');

        self::$builder = [
            'html'    => $html,
            'css'     => $leer('builder_css'),
            'js'      => $leer('builder_js'),
            'data'    => $leer('builder_data'),
            'enabled' => (bool) (SettingService::get('footer', 'builder_enabled', false) ?: false) && trim($html) !== '',
        ];

        return self::$builder;
    }

    /**
     * ¿Manda el pie del constructor?
     */
    public static function usesBuilder(): bool
    {
        return self::builder()['enabled'];
    }

    /**
     * Guarda lo que envía el constructor visual.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, errores:list<string>, guardadas:int}
     */
    public static function saveBuilder(array $input): array
    {
        $errores = [];
        $cuenta  = 0;

        $limites = [
            'builder_html' => self::MAX_HTML,
            'builder_css'  => self::MAX_CSS,
            'builder_js'   => self::MAX_JS,
            'builder_data' => self::MAX_HTML,
        ];

        foreach ($limites as $clave => $max) {
            $valor = isset($input[$clave]) && is_string($input[$clave]) ? $input[$clave] : '';
            if (mb_strlen($valor) > $max) {
                $errores[] = "«{$clave}» supera el máximo de " . number_format($max) . ' caracteres: no se guardó.';
                $valor = '';
            }
            SettingService::set('footer', $clave, $valor, 'string');
            $cuenta++;
        }

        SettingService::set('footer', 'builder_enabled', !empty($input['builder_enabled']) ? '1' : '0', 'bool');
        $cuenta++;

        self::flush();
        SettingService::clearCache();

        return ['ok' => $errores === [], 'errores' => $errores, 'guardadas' => $cuenta];
    }

    /**
     * Valida y guarda el contenido editado desde el panel.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, errores:list<string>, avisos:list<string>, guardadas:int}
     */
    public static function save(array $input): array
    {
        $errores = [];
        $avisos  = [];
        $cuenta  = 0;

        foreach (['about' => self::MAX_TEXT, 'copyright' => self::MAX_SHORT * 3,
                  'contact_title' => self::MAX_SHORT, 'contact_note' => self::MAX_TEXT] as $clave => $max) {
            $valor = isset($input[$clave]) && is_string($input[$clave]) ? trim($input[$clave]) : '';

            if (mb_strlen($valor) > $max) {
                $errores[] = "«{$clave}» supera los {$max} caracteres: se recortó.";
                $valor = mb_substr($valor, 0, $max);
            }
            if ($clave === 'contact_title' && $valor === '') {
                $errores[] = 'La columna de contacto necesita un título.';
                $valor = 'Contacto';
            }

            SettingService::set('footer', $clave, $valor, 'string');
            $cuenta++;
        }

        if (array_key_exists('show_contact', $input)) {
            SettingService::set('footer', 'show_contact', !empty($input['show_contact']) ? '1' : '0', 'bool');
            $cuenta++;
        }

        $correo = isset($input['contact_email']) && is_string($input['contact_email'])
            ? trim($input['contact_email'])
            : '';

        if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores[] = 'El correo de contacto no es válido: se guardó vacío.';
            $correo = '';
        }
        SettingService::set('footer', 'contact_email', $correo, 'string');
        $cuenta++;

        $enviadas = is_array($input['columns'] ?? null) ? count($input['columns']) : 0;
        $columnas = self::sanitizeColumns($input['columns'] ?? null);

        if ($enviadas > count($columnas)) {
            $avisos[] = 'Se descartaron ' . ($enviadas - count($columnas)) . ' columna(s) sin título o sin enlaces válidos.';
        }
        if ($enviadas > self::MAX_COLUMNS) {
            $avisos[] = 'El pie admite un máximo de ' . self::MAX_COLUMNS . ' columnas de enlaces.';
        }

        SettingService::set('footer', 'columns', $columnas, 'json');
        $cuenta++;

        self::flush();
        SettingService::clearCache();

        return [
            'ok'        => $errores === [],
            'errores'   => $errores,
            'avisos'    => array_values(array_unique($avisos)),
            'guardadas' => $cuenta,
        ];
    }

    /**
     * Lee el grupo `footer`, degradando si la base falla.
     *
     * @return array<string, mixed>
     */
    private static function stored(): array
    {
        try {
            $all = SettingService::all();
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo leer el contenido: ' . $e->getMessage());
            return [];
        }

        $grupo = $all['footer'] ?? [];

        return is_array($grupo) ? $grupo : [];
    }

    /**
     * Valida la lista de columnas y sus enlaces.
     *
     * @param mixed $valor
     * @return list<array{title:string, links:list<array{label:string, href:string}>}>
     */
    private static function sanitizeColumns(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        $out = [];
        foreach (array_slice($valor, 0, self::MAX_COLUMNS) as $columna) {
            if (!is_array($columna)) {
                continue;
            }

            $titulo = isset($columna['title']) && is_string($columna['title']) ? trim($columna['title']) : '';
            if ($titulo === '' || mb_strlen($titulo) > self::MAX_SHORT) {
                continue;
            }

            $enlaces = [];
            $crudos  = is_array($columna['links'] ?? null) ? $columna['links'] : [];
            foreach (array_slice($crudos, 0, self::MAX_LINKS) as $enlace) {
                if (!is_array($enlace)) {
                    continue;
                }
                $etiqueta = isset($enlace['label']) && is_string($enlace['label']) ? trim($enlace['label']) : '';
                $destino  = isset($enlace['href']) && is_string($enlace['href']) ? trim($enlace['href']) : '';

                if ($etiqueta === '' || mb_strlen($etiqueta) > self::MAX_SHORT) {
                    continue;
                }
                if (!LinkGuard::isSafeLink($destino)) {
                    continue;
                }

                $enlaces[] = ['label' => $etiqueta, 'href' => $destino];
            }

            if ($enlaces === []) {
                continue; // Una columna sin enlaces válidos no se pinta.
            }

            $out[] = ['title' => $titulo, 'links' => $enlaces];
        }

        return $out;
    }
}
