<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
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
        // Sin esto, una lectura anterior con «no hay ningún diseño activo»
        // dejaba la caché en vacío y activar después no surtía efecto.
        self::$activeDesign = null;
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

        // 1) Diseño activo del catálogo (`footer_designs`). Manda sobre todo.
        $activo = self::activeDesign();
        if ($activo !== null) {
            self::$builder = [
                'html'    => (string) ($activo['content_html'] ?? ''),
                'css'     => (string) ($activo['content_css'] ?? ''),
                'js'      => (string) ($activo['content_js'] ?? ''),
                'data'    => (string) ($activo['grapesjs_data'] ?? ''),
                'enabled' => trim((string) ($activo['content_html'] ?? '')) !== '',
                'design'  => $activo,
            ];

            return self::$builder;
        }

        // 2) Compatibilidad: lo que se guardó antes del catálogo, en `settings`.
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
            'design'  => null,
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
            }
            $valores[$clave] = $valor;
        }

        // Si algo no cabe, no se escribe nada: escribir el campo que falla vacío
        // borraría el contenido anterior.
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores, 'guardadas' => 0];
        }

        foreach ($valores as $clave => $valor) {
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

    // ═══════════════════════════════════════════════════════════════════
    // CATÁLOGO DE DISEÑOS (`footer_designs`)
    //
    // Antes solo se guardaba un diseño, en claves del grupo `footer`. Con esta
    // tabla se guardan varios, con nombre, y se elige cuál está activo. El pie por
    // campos (grupo `footer`) sigue siendo la alternativa cuando no hay ninguno.
    //
    // Solo un diseño activo a la vez: `activateDesign()` apaga los demás dentro de
    // una transacción, para que nunca queden dos encendidos a medias.
    // ═══════════════════════════════════════════════════════════════════

    /** Tope de diseños guardados: un catálogo, no un vertedero. */
    public const MAX_DESIGNS = 50;

    /** Tope del nombre de un diseño. */
    public const MAX_NAME = 120;

    /** @var array<string, mixed>|null Caché de la petición para el diseño activo. */
    private static ?array $activeDesign = null;

    /**
     * Todos los diseños, con el activo primero.
     *
     * @return list<array<string, mixed>>
     */
    public static function designs(): array
    {
        try {
            $filas = Database::fetchAll(
                'SELECT id, name, is_active, created_by, created_at, updated_at,
                        CHAR_LENGTH(COALESCE(content_html, \'\')) AS html_bytes
                   FROM footer_designs
               ORDER BY is_active DESC, updated_at DESC, id DESC'
            );
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo listar los diseños: ' . $e->getMessage());
            return [];
        }

        return array_map(static fn (array $f): array => [
            'id'         => (int) $f['id'],
            'name'       => (string) $f['name'],
            'is_active'  => (bool) $f['is_active'],
            'created_by' => $f['created_by'] === null ? null : (int) $f['created_by'],
            'created_at' => (string) $f['created_at'],
            'updated_at' => (string) $f['updated_at'],
            'html_bytes' => (int) $f['html_bytes'],
        ], $filas);
    }

    /**
     * Diseño por id, con todo su contenido.
     *
     * @return array<string, mixed>|null
     */
    public static function findDesign(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $fila = Database::fetch('SELECT * FROM footer_designs WHERE id = ?', [$id]);
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo leer el diseño ' . $id . ': ' . $e->getMessage());
            return null;
        }

        return $fila ?: null;
    }

    /**
     * El diseño activo, o `null` si no hay ninguno.
     *
     * @return array<string, mixed>|null
     */
    public static function activeDesign(): ?array
    {
        if (self::$activeDesign !== null) {
            return self::$activeDesign === [] ? null : self::$activeDesign;
        }

        try {
            $fila = Database::fetch('SELECT * FROM footer_designs WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        } catch (Throwable $e) {
            // La tabla puede no existir todavía (instalación anterior al catálogo):
            // no es un error, simplemente no hay diseños.
            error_log('[FooterContent] Sin catálogo de diseños: ' . $e->getMessage());
            self::$activeDesign = [];
            return null;
        }

        self::$activeDesign = $fila ?: [];

        return $fila ?: null;
    }

    /**
     * Crea un diseño vacío.
     *
     * @return array{ok:bool, id:?int, error:string}
     */
    public static function createDesign(string $name, ?int $userId = null): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['ok' => false, 'id' => null, 'error' => 'El diseño necesita un nombre.'];
        }
        if (mb_strlen($name) > self::MAX_NAME) {
            return ['ok' => false, 'id' => null, 'error' => 'El nombre no puede pasar de ' . self::MAX_NAME . ' caracteres.'];
        }
        if (count(self::designs()) >= self::MAX_DESIGNS) {
            return ['ok' => false, 'id' => null, 'error' => 'Has llegado al máximo de ' . self::MAX_DESIGNS . ' diseños: borra alguno.'];
        }

        $existente = Database::fetch('SELECT id FROM footer_designs WHERE name = ?', [$name]);
        if ($existente) {
            return ['ok' => false, 'id' => null, 'error' => 'Ya existe un diseño con ese nombre.'];
        }

        try {
            $id = Database::insert(
                'INSERT INTO footer_designs (name, content_html, content_css, content_js, grapesjs_data, is_active, created_by)
                 VALUES (?, \'\', \'\', \'\', \'\', 0, ?)',
                [$name, $userId]
            );
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo crear el diseño: ' . $e->getMessage());
            return ['ok' => false, 'id' => null, 'error' => 'No se pudo crear el diseño.'];
        }

        self::flush();
        return ['ok' => true, 'id' => $id, 'error' => ''];
    }

    /**
     * Guarda el contenido de un diseño concreto.
     *
     * @param array<string, mixed> $input
     * @return array{ok:bool, errores:list<string>}
     */
    public static function saveDesign(int $id, array $input): array
    {
        if (self::findDesign($id) === null) {
            return ['ok' => false, 'errores' => ['Ese diseño no existe.']];
        }

        $limites = [
            'content_html'  => self::MAX_HTML,
            'content_css'   => self::MAX_CSS,
            'content_js'    => self::MAX_JS,
            'grapesjs_data' => self::MAX_HTML,
        ];

        $errores = [];
        $valores = [];
        foreach ($limites as $campo => $max) {
            $valor = isset($input[$campo]) && is_string($input[$campo]) ? $input[$campo] : '';
            if (mb_strlen($valor) > $max) {
                $errores[] = $campo . ' supera el máximo de ' . number_format($max) . ' caracteres: no se guardó.';
            }
            $valores[$campo] = $valor;
        }

        // Si algo no cabe, NO se escribe nada. Antes se guardaban los campos
        // válidos y el que fallaba se escribía vacío, así que un guardado
        // rechazado borraba el contenido del diseño.
        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        try {
            Database::execute(
                'UPDATE footer_designs
                    SET content_html = ?, content_css = ?, content_js = ?, grapesjs_data = ?
                  WHERE id = ?',
                [$valores['content_html'], $valores['content_css'], $valores['content_js'], $valores['grapesjs_data'], $id]
            );
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo guardar el diseño ' . $id . ': ' . $e->getMessage());
            return ['ok' => false, 'errores' => ['No se pudo guardar el diseño.']];
        }

        self::flush();
        return ['ok' => $errores === [], 'errores' => $errores];
    }

    /**
     * Activa un diseño y apaga el resto, en una transacción.
     */
    public static function activateDesign(int $id): bool
    {
        if (self::findDesign($id) === null) {
            return false;
        }

        try {
            Database::begin();
            Database::execute('UPDATE footer_designs SET is_active = 0 WHERE is_active = 1');
            Database::execute('UPDATE footer_designs SET is_active = 1 WHERE id = ?', [$id]);
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            error_log('[FooterContent] No se pudo activar el diseño ' . $id . ': ' . $e->getMessage());
            return false;
        }

        self::flush();
        return true;
    }

    /**
     * Apaga el diseño activo: el sitio vuelve al pie por campos.
     */
    public static function deactivate(): bool
    {
        try {
            Database::execute('UPDATE footer_designs SET is_active = 0 WHERE is_active = 1');
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo desactivar: ' . $e->getMessage());
            return false;
        }

        self::flush();
        return true;
    }

    /**
     * Copia un diseño con otro nombre.
     *
     * @return array{ok:bool, id:?int, error:string}
     */
    public static function duplicateDesign(int $id, ?int $userId = null): array
    {
        $origen = self::findDesign($id);
        if ($origen === null) {
            return ['ok' => false, 'id' => null, 'error' => 'Ese diseño no existe.'];
        }

        $base = trim((string) $origen['name']) . ' (copia)';
        $nombre = $base;
        $n = 2;
        while (Database::fetch('SELECT id FROM footer_designs WHERE name = ?', [$nombre])) {
            $nombre = $base . ' ' . $n;
            $n++;
            if ($n > 99) {
                return ['ok' => false, 'id' => null, 'error' => 'No se pudo encontrar un nombre libre.'];
            }
        }

        try {
            $nuevo = Database::insert(
                'INSERT INTO footer_designs (name, content_html, content_css, content_js, grapesjs_data, is_active, created_by)
                 VALUES (?, ?, ?, ?, ?, 0, ?)',
                [
                    $nombre,
                    (string) $origen['content_html'],
                    (string) $origen['content_css'],
                    (string) $origen['content_js'],
                    (string) $origen['grapesjs_data'],
                    $userId,
                ]
            );
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo duplicar el diseño ' . $id . ': ' . $e->getMessage());
            return ['ok' => false, 'id' => null, 'error' => 'No se pudo duplicar el diseño.'];
        }

        self::flush();
        return ['ok' => true, 'id' => $nuevo, 'error' => ''];
    }

    /**
     * Borra un diseño. Se niega si está activo: apágalo antes.
     *
     * @return array{ok:bool, error:string}
     */
    public static function deleteDesign(int $id): array
    {
        $diseno = self::findDesign($id);
        if ($diseno === null) {
            return ['ok' => false, 'error' => 'Ese diseño no existe.'];
        }
        if ((int) $diseno['is_active'] === 1) {
            return ['ok' => false, 'error' => 'No se puede borrar el diseño activo. Activa otro o desactívalo antes.'];
        }

        try {
            Database::execute('DELETE FROM footer_designs WHERE id = ?', [$id]);
        } catch (Throwable $e) {
            error_log('[FooterContent] No se pudo borrar el diseño ' . $id . ': ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo borrar el diseño.'];
        }

        self::flush();
        return ['ok' => true, 'error' => ''];
    }
}
