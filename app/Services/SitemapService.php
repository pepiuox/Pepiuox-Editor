<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;
use Throwable;

/**
 * Generación del `sitemap.xml` público.
 *
 * ── Qué cambia respecto a la versión anterior ───────────────────────
 * Antes el sitemap listaba **todas** las páginas publicadas, sin importar si
 * el sitio las ofrece. El dueño pidió lo contrario: que se genere «todo lo que
 * se pone en uso público», es decir, lo que el menú en uso expone de verdad.
 *
 * ── La regla, y por qué es esta ─────────────────────────────────────
 * No se inventa un criterio nuevo: se usa el mismo que el sitio público para
 * decidir qué menú pinta en cada ubicación. `MenuService` lo tiene documentado
 * y lo aplica así:
 *
 *   menú asignado a la página  →  menú `is_primary` de la ubicación  →  menú `is_default` de la ubicación
 *
 * Es decir, un menú con `is_primary = 1` **sustituye** al predeterminado en su
 * ubicación. Si el sitemap usara otro criterio, anunciaría a los buscadores
 * URLs que el visitante no puede alcanzar navegando.
 *
 * ── Dos fuentes, elegibles desde el panel ───────────────────────────
 * `seo.sitemap_source`:
 *   - `menu` (por defecto): solo lo que exponen los menús en uso.
 *   - `published`: todas las páginas publicadas, más lo que expongan los menús.
 *
 * Existe la segunda porque excluir una página publicada puede ser una decisión
 * legítima o un accidente, y esa diferencia la juzga el dueño, no el código.
 *
 * ── Qué queda fuera siempre ─────────────────────────────────────────
 * Destinos externos (`https://…`), enlaces `mailto:`/`tel:`, anclas y rutas
 * del panel (`/admin/…`), `/login` y el propio `sitemap.xml`. Un sitemap es
 * para contenido público indexable.
 *
 * @package App\Services
 * @since   2026-10-31
 */
final class SitemapService
{
    /** Ruta del archivo público, relativa a la raíz del proyecto. */
    private const PUBLIC_FILE = '/public/sitemap.xml';

    /** Rutas que nunca entran en el sitemap. */
    private const RUTAS_EXCLUIDAS = ['/login', '/logout', '/register', '/forgot', '/reset', '/sitemap.xml', '/admin'];

    /**
     * Valores por defecto de la configuración del sitemap.
     *
     * `scope = default_menus` es la decisión del dueño: por defecto el sitemap
     * sigue los **menús predeterminados** de cada ubicación, que es el «Menú
     * principal». El menú que el header esté mostrando queda como opción, no
     * como comportamiento implícito: son cosas distintas y la diferencia se ve.
     *
     * @return array{scope:string, menu_id:int, base_url:string, include_home:bool}
     */
    public static function defaults(): array
    {
        return [
            'scope'        => 'default_menus',
            'menu_id'      => 0,
            'base_url'     => '',
            'include_home' => true,
        ];
    }

    /**
     * Catálogo de ámbitos posibles, para el selector del panel.
     *
     * @return array<string, array{label:string, descripcion:string}>
     */
    public static function scopes(): array
    {
        return [
            'default_menus' => [
                'label'       => 'Menús predeterminados de cada ubicación',
                'descripcion' => 'Es el «Menú principal»: el marcado como predeterminado. Recomendado.',
            ],
            'header_menu'   => [
                'label'       => 'El menú que el header está mostrando',
                'descripcion' => 'Si hay un menú marcado como principal en la ubicación header, manda ese; si no, el predeterminado.',
            ],
            'specific'      => [
                'label'       => 'Un menú concreto',
                'descripcion' => 'Elige abajo el menú cuyos ítems se publican.',
            ],
            'all_published' => [
                'label'       => 'Todas las páginas publicadas, además de los menús',
                'descripcion' => 'Anuncia también las páginas publicadas que no están en ningún menú.',
            ],
        ];
    }

    /**
     * Configuración efectiva, leída del grupo `seo` con respaldo en código.
     *
     * Admite la clave antigua `sitemap_source` para no romper una instalación
     * que ya la tenga guardada.
     *
     * @return array{scope:string, menu_id:int, base_url:string, include_home:bool}
     */
    public static function settings(): array
    {
        $defaults = self::defaults();

        try {
            $all = SettingService::all();
        } catch (Throwable $e) {
            error_log('[Sitemap] No se pudo leer la configuración: ' . $e->getMessage());
            return $defaults;
        }

        $seo = is_array($all['seo'] ?? null) ? $all['seo'] : [];

        $scope = (string) ($seo['sitemap_scope'] ?? '');

        // Compatibilidad con la clave anterior.
        if ($scope === '' && isset($seo['sitemap_source'])) {
            $scope = (string) $seo['sitemap_source'] === 'published' ? 'all_published' : 'default_menus';
        }

        if (!array_key_exists($scope, self::scopes())) {
            $scope = $defaults['scope'];
        }

        return [
            'scope'        => $scope,
            'menu_id'      => (int) ($seo['sitemap_menu_id'] ?? 0),
            'base_url'     => trim((string) ($seo['sitemap_base_url'] ?? '')),
            'include_home' => array_key_exists('sitemap_include_home', $seo)
                ? (bool) $seo['sitemap_include_home']
                : $defaults['include_home'],
        ];
    }

    /**
     * URL base del sitio, sin barra final.
     *
     * Prioridad: lo guardado en el panel → `config.app_url` → el origen de la
     * petición. Nunca se inventa un dominio.
     *
     * @return array{url:string, origen:string, aviso:?string}
     */
    public static function baseUrl(): array
    {
        $cfg = require CONFIG_PATH . '/config.php';

        $guardada = self::settings()['base_url'];
        $config   = rtrim((string) ($cfg['app_url'] ?? ''), '/');
        $base     = $guardada !== '' ? rtrim($guardada, '/') : $config;
        $origen   = $guardada !== '' ? 'panel' : 'config/app_url';
        $aviso    = null;

        if ($base === '') {
            $base   = rtrim(BASE_URL, '/');
            $origen = 'petición';
        }

        // Un sitemap con localhost no lo usa ningún buscador: se avisa en vez de
        // generar uno que parezca correcto y no lo sea.
        if (preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])(:|/|$)#i', $base) === 1) {
            $aviso = 'La URL base apunta a localhost. Configúrala en «Sitemap» antes de publicar: '
                   . 'un sitemap con direcciones locales no lo indexa ningún buscador.';
        }

        if (!preg_match('#^https?://#i', $base)) {
            $aviso = 'La URL base no empieza por http:// o https://.';
        }

        return ['url' => $base, 'origen' => $origen, 'aviso' => $aviso];
    }

    /**
     * Menús que el sitemap va a leer, según el ámbito elegido.
     *
     * @param array{scope:string, menu_id:int, base_url:string, include_home:bool} $settings
     * @return array<int, array{id:int, location:string, name:string, motivo:string}>
     */
    public static function menusForSitemap(array $settings): array
    {
        return match ($settings['scope']) {
            'default_menus' => self::defaultMenus(),
            'header_menu'   => self::renderedMenus('header'),
            'specific'      => self::menuById($settings['menu_id']),
            default         => self::renderedMenus(),   // all_published
        };
    }

    /**
     * Un menú por ubicación tomando el `is_default`, que es el «Menú principal».
     *
     * @return array<int, array{id:int, location:string, name:string, motivo:string}>
     */
    private static function defaultMenus(): array
    {
        $filas = Database::fetchAll(
            "SELECT id, name, location
               FROM menus
              WHERE is_active = 1 AND is_default = 1
              ORDER BY location ASC, id ASC"
        );

        $out = [];
        foreach ($filas as $fila) {
            $ubicacion = (string) ($fila['location'] ?? 'header');
            if (isset($out[$ubicacion])) {
                continue; // Un menú predeterminado por ubicación.
            }
            $out[$ubicacion] = [
                'id'       => (int) $fila['id'],
                'location' => $ubicacion,
                'name'     => (string) $fila['name'],
                'motivo'   => 'default',
            ];
        }

        return array_values($out);
    }

    /**
     * Menús tal como los resuelve el sitio público: el primario gana al predeterminado.
     *
     * @return array<int, array{id:int, location:string, name:string, motivo:string}>
     */
    private static function renderedMenus(?string $soloUbicacion = null): array
    {
        $sql = "SELECT id, name, location, is_default, is_primary
                  FROM menus
                 WHERE is_active = 1";
        $params = [];

        if ($soloUbicacion !== null) {
            $sql .= " AND location = ?";
            $params[] = $soloUbicacion;
        }

        $sql .= " ORDER BY location ASC, is_primary DESC, is_default DESC, id ASC";

        $elegidos = [];
        foreach (Database::fetchAll($sql, $params) as $fila) {
            $ubicacion  = (string) ($fila['location'] ?? 'header');
            $esPrimario = (int) ($fila['is_primary'] ?? 0) === 1 && (int) ($fila['is_default'] ?? 0) === 0;
            $esDefault  = (int) ($fila['is_default'] ?? 0) === 1;

            if (!$esPrimario && !$esDefault) {
                continue; // Un menú suelto no lo pinta nadie.
            }

            if (isset($elegidos[$ubicacion])) {
                if ($elegidos[$ubicacion]['motivo'] === 'primary' || !$esPrimario) {
                    continue;
                }
                unset($elegidos[$ubicacion]);
            }

            $elegidos[$ubicacion] = [
                'id'       => (int) $fila['id'],
                'location' => $ubicacion,
                'name'     => (string) $fila['name'],
                'motivo'   => $esPrimario ? 'primary' : 'default',
            ];
        }

        return array_values($elegidos);
    }

    /**
     * Un menú concreto por id, si existe y está activo.
     *
     * @return array<int, array{id:int, location:string, name:string, motivo:string}>
     */
    private static function menuById(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $fila = Database::fetch(
            "SELECT id, name, location FROM menus WHERE id = ? AND is_active = 1",
            [$id]
        );

        if (!$fila) {
            return [];
        }

        return [[
            'id'       => (int) $fila['id'],
            'location' => (string) ($fila['location'] ?? 'header'),
            'name'     => (string) $fila['name'],
            'motivo'   => 'elegido',
        ]];
    }

    /**
     * Catálogo de menús activos, para el selector del panel.
     *
     * @return array<int, array{id:int, name:string, location:string, is_default:int, is_primary:int, items:int}>
     */
    public static function menuCatalog(): array
    {
        return Database::fetchAll(
            "SELECT m.id, m.name, m.location, m.is_default, m.is_primary,
                    (SELECT COUNT(*) FROM page_menu pm WHERE pm.menu_id = m.id) AS items
               FROM menus m
              WHERE m.is_active = 1
              ORDER BY m.location ASC, m.name ASC"
        );
    }

    /**
     * Regenera el archivo y devuelve el informe de lo que escribió.
     *
     * La escritura es atómica (archivo temporal + `rename`): un sitemap a medio
     * escribir es peor que uno viejo, porque los buscadores lo leen tal cual.
     *
     * @return array{
     *   ok:bool, mensaje:string, total:int, source:string, base_url:string,
     *   warnings:list<string>, urls:list<array{loc:string,lastmod:string,priority:string,origen:string}>,
     *   menus:array<int, array{id:int,location:string,name:string,motivo:string}>
     * }
     */
    /**
     * Genera el informe del sitemap y, opcionalmente, escribe el archivo.
     *
     * @param bool $escribir `false` calcula el informe sin tocar el disco. Lo usa
     *                       la pantalla del panel para mostrar qué se publicaría
     *                       sin reescribir el archivo cada vez que se abre.
     * @return array{
     *   ok:bool, escrito:bool, mensaje:string, total:int, source:string, base_url:string,
     *   warnings:list<string>, urls:list<array{loc:string,lastmod:string,priority:string,origen:string}>,
     *   menus:array<int, array{id:int,location:string,name:string,motivo:string}>
     * }
     */
    public static function generate(bool $escribir = true): array
    {
        try {
            return self::buildReport($escribir);
        } catch (Throwable $e) {
            // Nunca se propaga: hay llamadas dentro del guardado de páginas y de
            // versiones, y un sitemap roto no puede impedir guardar contenido.
            error_log('[Sitemap] Error al generar: ' . $e->getMessage());

            return [
                'ok'       => false,
                'escrito'  => false,
                'mensaje'  => 'Error al generar el sitemap: ' . $e->getMessage(),
                'total'    => 0,
                'source'   => self::settings()['scope'],
                'base_url' => '',
                'warnings' => ['La generación falló. Revisa el log de la aplicación.'],
                'urls'     => [],
                'menus'    => [],
            ];
        }
    }

    /**
     * Cuerpo real de la generación, separado para poder envolverlo.
     *
     * @return array{
     *   ok:bool, escrito:bool, mensaje:string, total:int, source:string, base_url:string,
     *   warnings:list<string>, urls:list<array{loc:string,lastmod:string,priority:string,origen:string}>,
     *   menus:array<int, array{id:int,location:string,name:string,motivo:string}>
     * }
     */
    private static function buildReport(bool $escribir): array
    {
        $settings = self::settings();
        $base     = self::baseUrl();
        $menus    = self::menusForSitemap($settings);
        $avisos   = [];

        if ($base['aviso'] !== null) {
            $avisos[] = $base['aviso'];
        }

        if ($menus === []) {
            $avisos[] = 'El ámbito elegido no produjo ningún menú: no se pudo recolectar ninguna ruta desde los menús.';
        }

        $urls = [];

        // ── Inicio ────────────────────────────────────────────────────
        if ($settings['include_home']) {
            $home      = \App\Models\Page::home();
            $lastmod   = isset($home['updated_at']) ? date('Y-m-d', strtotime((string) $home['updated_at'])) : date('Y-m-d');
            $urls['/'] = [
                'loc'      => $base['url'] . '/',
                'lastmod'  => $lastmod,
                'priority' => '1.0',
                'origen'   => 'inicio',
            ];
        }

        // ── Desde los menús en uso ───────────────────────────────────
        foreach ($menus as $menu) {
            foreach (self::urlsFromMenu($menu['id']) as $ruta => $datos) {
                if (isset($urls[$ruta])) {
                    continue;
                }
                $urls[$ruta] = [
                    'loc'      => $base['url'] . $ruta,
                    'lastmod'  => $datos['lastmod'],
                    'priority' => $datos['priority'],
                    'origen'   => 'menu:' . $menu['name'],
                ];
            }
        }

        // ── Todas las publicadas, si se eligió ese ámbito ────────────
        if ($settings['scope'] === 'all_published') {
            foreach (\App\Models\Page::publishedForSitemap() as $pagina) {
                $ruta = '/' . ltrim((string) $pagina['slug'], '/');
                if (isset($urls[$ruta]) || self::isExcluded($ruta)) {
                    continue;
                }
                $urls[$ruta] = [
                    'loc'      => $base['url'] . $ruta,
                    'lastmod'  => isset($pagina['updated_at']) ? date('Y-m-d', strtotime((string) $pagina['updated_at'])) : date('Y-m-d'),
                    'priority' => '0.6',
                    'origen'   => 'publicada',
                ];
            }
        }

        ksort($urls);

        $xml = self::buildXml(array_values($urls));

        $destino = dirname(__DIR__, 2) . self::PUBLIC_FILE;
        $ok      = $escribir ? self::writeAtomically($destino, $xml) : true;

        return [
            'ok'       => $ok,
            'escrito'  => $escribir && $ok,
            'mensaje'  => $escribir
                ? ($ok ? 'Sitemap generado con ' . count($urls) . ' URL(s).' : 'No se pudo escribir el sitemap.')
                : 'Previsualización: ' . count($urls) . ' URL(s). El archivo no se ha tocado.',
            'total'    => count($urls),
            'scope'    => $settings['scope'],
            'source'   => $settings['scope'],
            'menu_id'  => $settings['menu_id'],
            'base_url' => $base['url'],
            'warnings' => $avisos,
            'urls'     => array_values($urls),
            'menus'    => $menus,
        ];
    }

    /**
     * Regenera el archivo sin interrumpir al llamante.
     *
     * Se invoca cuando se guarda una página y cuando se cambia el menú en uso.
     * Nunca lanza: si el sitemap falla, el guardado de la página no puede
     * fallar por eso. Deja el motivo en el log.
     */
    public static function refresh(): void
    {
        try {
            $informe = self::generate();
            if (!$informe['ok']) {
                error_log('[Sitemap] ' . $informe['mensaje']);
            }
        } catch (Throwable $e) {
            error_log('[Sitemap] No se pudo regenerar: ' . $e->getMessage());
        }
    }

    /**
     * Informe del estado actual sin regenerar nada.
     *
     * @return array{exists:bool, size:int, generated_at:?string, modified_at:?string, urls:int}
     */
    public static function status(): array
    {
        $path = dirname(__DIR__, 2) . self::PUBLIC_FILE;

        if (!is_file($path)) {
            return ['exists' => false, 'size' => 0, 'generated_at' => null, 'modified_at' => null, 'urls' => 0];
        }

        $contenido = (string) file_get_contents($path);

        return [
            'exists'       => true,
            'size'         => (int) filesize($path),
            'generated_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
            'modified_at'  => date('Y-m-d H:i:s', (int) filemtime($path)),
            'urls'         => substr_count($contenido, '<loc>'),
        ];
    }

    /**
     * Contenido actual del archivo (para previsualizarlo en el panel).
     */
    public static function currentXml(): string
    {
        $path = dirname(__DIR__, 2) . self::PUBLIC_FILE;

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /**
     * Rutas que expone un menú concreto, solo ítems publicados e internos.
     *
     * @return array<string, array{lastmod:string, priority:string}>
     */
    private static function urlsFromMenu(int $menuId): array
    {
        $filas = Database::fetchAll(
            "SELECT pm.slug AS menu_slug, pm.url AS menu_url, pm.is_published, pm.status,
                    p.slug AS page_slug, p.updated_at, p.startpage,
                    p.is_published AS page_published, p.status AS page_status
               FROM page_menu pm
          LEFT JOIN pages p ON p.id = pm.page_id
              WHERE pm.menu_id = ?",
            [$menuId]
        );

        $out = [];

        foreach ($filas as $fila) {
            if ((int) ($fila['is_published'] ?? 0) !== 1) {
                continue;
            }
            if ((string) ($fila['status'] ?? 'published') !== 'published') {
                continue;
            }

            // Si el ítem apunta a una página, manda el estado de la página.
            $slug = null;
            if (!empty($fila['page_slug'])) {
                if ((int) ($fila['page_published'] ?? 0) !== 1) {
                    continue;
                }
                if ((string) ($fila['page_status'] ?? 'published') !== 'published') {
                    continue;
                }
                $slug = (string) $fila['page_slug'];
            } elseif (!empty($fila['menu_slug'])) {
                $slug = (string) $fila['menu_slug'];
            }

            if ($slug === null) {
                $url = (string) ($fila['menu_url'] ?? '');
                // Solo rutas internas absolutas; fuera los enlaces externos.
                if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
                    continue;
                }
                $ruta = $url;
            } else {
                $ruta = '/' . ltrim($slug, '/');
            }

            // La página de inicio se publica como `/`, no como `/su-slug`. Sin
            // esto, un menú que enlaza la portada anunciaba la misma página dos
            // veces (`/` y `/inicio`), que es contenido duplicado a ojos del
            // buscador. La raíz la añade `buildReport` según `include_home`.
            if ((int) ($fila['startpage'] ?? 0) === 1) {
                $ruta = '/';
            }

            if ($ruta === '/' || self::isExcluded($ruta)) {
                continue;
            }

            $out[$ruta] = [
                'lastmod'  => isset($fila['updated_at']) ? date('Y-m-d', strtotime((string) $fila['updated_at'])) : date('Y-m-d'),
                'priority' => '0.8',
            ];
        }

        return $out;
    }

    /** ¿Es una ruta que nunca debe publicarse en el sitemap? */
    private static function isExcluded(string $ruta): bool
    {
        foreach (self::RUTAS_EXCLUIDAS as $excluida) {
            if ($ruta === $excluida || str_starts_with($ruta, $excluida . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compone el XML.
     *
     * @param list<array{loc:string,lastmod:string,priority:string,origen:string}> $urls
     */
    private static function buildXml(array $urls): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<!-- Generado por PePiuoX Editor. No editar a mano: se reescribe desde el panel. -->' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Escritura atómica: escribe a un temporal en el mismo directorio y renombra.
     *
     * @throws RuntimeException Si no se puede escribir o mover el archivo.
     */
    private static function writeAtomically(string $destino, string $contenido): bool
    {
        $directorio = dirname($destino);

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            error_log('[Sitemap] No existe el directorio ' . $directorio);

            return false;
        }

        $temporal = $destino . '.tmp-' . bin2hex(random_bytes(4));

        if (file_put_contents($temporal, $contenido, LOCK_EX) === false) {
            error_log('[Sitemap] No se pudo escribir el temporal ' . $temporal);

            return false;
        }

        if (!@rename($temporal, $destino)) {
            @unlink($temporal);
            error_log('[Sitemap] No se pudo mover el temporal a ' . $destino);

            return false;
        }

        @chmod($destino, 0644);

        return true;
    }
}
