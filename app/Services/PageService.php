<?php
namespace App\Services;

use App\Core\{Database, Auth};
use App\Models\{Page, MetaTag};

class PageService
{
    /**
     * Guarda una página, crea versión y regenera cache/sitemap.
     */
    public static function save(int $id, array $data, ?string $note = null): bool
    {
        Database::begin();
        try {
            if (!empty($data['slug'])) {
                $data['slug'] = Page::uniqueSlug($data['slug'], $id);
            }
            Page::update($id, $data);

            $page = Page::find($id);
            if ($page) {
                \App\Models\PageVersion::create([
                    'page_id'      => $id,
                    'title'        => $page['title'],
                    'content_html' => $page['content_html'],
                    'content_css'  => $page['content_css'],
                    'content_js'   => $page['content_js'],
                    'content_php'  => $page['content_php'],
                    'note'         => $note ?? 'Actualización manual',
                ]);
            }

            CacheService::invalidatePage($id);
            if (!empty($page['template_id'])) {
                CacheService::invalidateTemplatePages((int)$page['template_id']);
            }

            SitemapService::generate();
            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[PageService] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea una nueva página con versión inicial.
     */
    public static function create(array $data): ?int
    {
        Database::begin();
        try {
            $data['slug'] = Page::uniqueSlug($data['slug'] ?? $data['title']);
            $id = Page::create($data);

            \App\Models\PageVersion::create([
                'page_id'      => $id,
                'title'        => $data['title'],
                'content_html' => $data['content_html'] ?? '',
                'content_css'  => $data['content_css'] ?? '',
                'content_js'   => $data['content_js'] ?? '',
                'content_php'  => $data['content_php'] ?? '',
                'note'         => 'Creación inicial',
            ]);

            SitemapService::generate();
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[PageService] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Renderiza una página pública (con template + SEO + cache).
     */
    public static function render(array $page): string
    {
        // 1. Intentar cache
        $cached = CacheService::getPage((int)$page['id']);
        if ($cached !== null) return $cached;

        // 2. Obtener template
        $template = null;
        if (!empty($page['template_id'])) {
            $template = Database::fetch("SELECT * FROM templates WHERE id = ?", [$page['template_id']]);
        }

        // 3. Obtener SEO
        $seo = MetaTag::forPage((int)$page['id']);

        // 4. Menús dinámicos
        $menus = MenuService::allActive();

        // 5. Construir HTML completo
        $html = self::buildHtml($page, $template, $seo, $menus);

        // 6. Guardar en cache
        CacheService::putPage((int)$page['id'], $html);

        // 7. Contar visita (silencioso)
        self::trackVisit((int)$page['id']);

        return $html;
    }

    /**
     * Construye el HTML completo de la página.
     */
    private static function buildHtml(array $page, ?array $template, array $seo, array $menus): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');

        // Head con SEO completo
        $head = self::renderSeoHead($page, $seo, $siteName);

        // CSS: template + página
        $css = ($template['content_css'] ?? '') . "\n" . ($page['content_css'] ?? '');

        // Contenido: si hay template, insertar en {{content}}
        $body = $page['content_html'] ?? '';
        if ($template && !empty($template['content_html'])) {
            $layout = $template['content_html'];
            if (str_contains($layout, '{{content}}')) {
                $body = str_replace('{{content}}', $body, $layout);
            } else {
                $body = $layout . "\n" . $body;
            }
        }

        // JS: template + página
        $js = ($template['content_js'] ?? '') . "\n" . ($page['content_js'] ?? '');

        // PHP seguro (solo si tiene permiso)
        $phpOutput = '';
        if (!empty($page['content_php']) && Auth::can('pages.edit')) {
            ob_start();
            try {
                (static function() use ($page) {
                    $PAGE = $page;
                    eval('?>' . $page['content_php']);
                })();
            } catch (\Throwable $e) {
                $phpOutput = "<!-- PHP error: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            $phpOutput .= ob_get_clean();
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
$head
<style>
$css
</style>
</head>
<body>
$phpOutput
$body
<script>
$js
</script>
</body>
</html>
HTML;
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * RENDERIZA EL <head> SEO COMPLETO
     * ═══════════════════════════════════════════════════════════════
     * 
     * Genera todas las etiquetas meta necesarias para:
     * - SEO básico (title, description, robots, canonical)
     * - Open Graph (Facebook, LinkedIn, WhatsApp)
     * - Twitter Cards
     * - JSON-LD Schema.org
     * - Google Analytics
     * - Google Search Console verification
     * - Fallback a valores por defecto del sitio
     */
    
    public static function renderSeoHead(array $page, array $seo, string $siteName): string
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $baseUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/');
        $pageUrl = $baseUrl . '/' . ltrim($page['slug'] ?? '', '/');

        // ═══ 1. VALORES CON FALLBACK A CONFIGURACIÓN GLOBAL ═══
        $title = $seo['basic']['title'] 
                 ?? SettingService::get('seo', 'default_meta_title', '')
                 ?? $page['title'] . ' · ' . $siteName;
        
        $description = $seo['basic']['description'] 
                       ?? SettingService::get('seo', 'default_meta_description', '')
                       ?? $page['description'] ?? '';
        
        $robots = $seo['basic']['robots'] 
                  ?? SettingService::get('seo', 'default_robots', 'index, follow');
        
        $canonical = $seo['basic']['canonical'] ?? $pageUrl;

        $defaultOgImage = SettingService::get('seo', 'default_og_image', '');

        // ═══ 2. INICIAR SALIDA ═══
        $out = '';

        // ═══ 3. META BÁSICOS ═══
        $out .= '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        
        if ($description) {
            $out .= '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        
        $out .= '<meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        
        if ($canonical) {
            $out .= '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // Keywords y autor (opcionales)
        if (!empty($seo['basic']['keywords'])) {
            $out .= '<meta name="keywords" content="' . htmlspecialchars($seo['basic']['keywords'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        if (!empty($seo['basic']['author'])) {
            $out .= '<meta name="author" content="' . htmlspecialchars($seo['basic']['author'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // ═══ 4. OPEN GRAPH (Facebook, LinkedIn, WhatsApp) ═══
        $ogData = [
            'og:title'       => $seo['og']['title'] ?? $title,
            'og:description' => $seo['og']['description'] ?? $description,
            'og:image'       => $seo['og']['image'] ?? $defaultOgImage,
            'og:type'        => $seo['og']['type'] ?? 'website',
            'og:url'         => $seo['og']['url'] ?? $pageUrl,
            'og:site_name'   => $siteName,
            'og:locale'      => SettingService::get('general', 'site_locale', 'es_ES'),
        ];

        foreach ($ogData as $property => $value) {
            if (!empty($value)) {
                $out .= '<meta property="' . $property . '" content="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        // ═══ 5. TWITTER CARDS ═══
        $twitterData = [
            'twitter:card'        => $seo['twitter']['card'] ?? 'summary_large_image',
            'twitter:title'       => $seo['twitter']['title'] ?? $title,
            'twitter:description' => $seo['twitter']['description'] ?? $description,
            'twitter:image'       => $seo['twitter']['image'] ?? $seo['og']['image'] ?? $defaultOgImage,
            'twitter:site'        => SettingService::get('seo', 'twitter_site', ''),
        ];

        foreach ($twitterData as $name => $value) {
            if (!empty($value)) {
                $out .= '<meta name="' . $name . '" content="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        // ═══ 6. FAVICON ═══
        $out .= '<link rel="icon" type="image/svg+xml" href="' . htmlspecialchars($baseUrl . '/assets/favicon.svg', ENT_QUOTES, 'UTF-8') . '">' . "\n";

        // ═══ 7. JSON-LD Schema.org ═══
        $out .= SeoService::jsonLd($page, $seo) . "\n";

        // ═══ 8. GOOGLE ANALYTICS ═══
        $ga = SettingService::get('seo', 'ga_tracking');
        if (!empty($ga)) {
            $gaEscaped = htmlspecialchars($ga, ENT_QUOTES, 'UTF-8');
            $out .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaEscaped}\"></script>\n";
            $out .= "<script>\n";
            $out .= "  window.dataLayer = window.dataLayer || [];\n";
            $out .= "  function gtag(){dataLayer.push(arguments);}\n";
            $out .= "  gtag('js', new Date());\n";
            $out .= "  gtag('config', '{$gaEscaped}');\n";
            $out .= "</script>\n";
        }

        // ═══ 9. GOOGLE SEARCH CONSOLE VERIFICATION ═══
        $gsc = SettingService::get('seo', 'gsc_verification');
        if (!empty($gsc)) {
            $out .= '<meta name="google-site-verification" content="' . htmlspecialchars($gsc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // ═══ 10. META ADICIONALES DE SEGURIDAD Y RENDIMIENTO ═══
        $out .= '<meta name="generator" content="PePiuoX Editor">' . "\n";
        $out .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";

        return $out;
    }

    /**
     * Registra una visita a la página (silencioso, no falla).
     */
    private static function trackVisit(int $pageId): void
    {
        try {
            Database::execute(
                "INSERT INTO visits (page_id, visit_date, ip_address, user_agent) VALUES (?,?,?,?)",
                [
                    $pageId, 
                    date('Y-m-d H:i:s'), 
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
            Database::execute(
                "INSERT INTO page_statistics (page_id, visits) VALUES (?,1)
                 ON DUPLICATE KEY UPDATE visits = visits + 1, last_visit = NOW()",
                [$pageId]
            );
        } catch (\Throwable $e) {
            // Silencioso: no debe romper la carga de la página
        }
    }
}