<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Auth};
use App\Models\{Page, PageModel, PageVersion, MetaTag};

/**
 * Servicio de lógica de negocio para páginas (PHP 8.2+)
 * 
 * Encapsula operaciones complejas que involucran múltiples modelos:
 * - Transacciones DB
 * - Creación de versiones automáticas
 * - Invalidación de caché
 * - Regeneración de sitemap
 * 
 * @package App\Services
 */
final class PageService
{
    public function __construct(
        private readonly Database $db,
        private readonly PageModel $pageModel
    ) {}

    /**
     * Guarda una página con transacción, versión y caché
     * 
     * @param int         $id   ID de la página
     * @param array       $data Datos a actualizar
     * @param string|null $note Nota de la versión
     * @return bool True si se guardó correctamente
     */
    public function save(int $id, array $data, ?string $note = null): bool
    {
        $this->db->begin();
        
        try {
            // Generar slug único si se proporciona
            if (!empty($data['slug'])) {
                $data['slug'] = $this->pageModel->uniqueSlug($data['slug'], $id);
            }

            // Actualizar página
            $this->pageModel->updateById($id, $data);

            // Obtener página actualizada
            $page = Page::find($id);
            
            if ($page) {
                // Crear versión
                PageVersion::create([
                    'page_id'      => $id,
                    'title'        => $page['title'],
                    'content_html' => $page['content_html'],
                    'content_css'  => $page['content_css'],
                    'content_js'   => $page['content_js'],
                    'content_php'  => $page['content_php'],
                    'note'         => $note ?? 'Actualización manual',
                ]);

                // Invalidar caché
                CacheService::invalidatePage($id);
                
                if (!empty($page['template_id'])) {
                    CacheService::invalidateTemplatePages((int) $page['template_id']);
                }
            }

            // Regenerar sitemap
            SitemapService::generate();

            $this->db->commit();
            return true;
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            \error_log('[PageService] Error al guardar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea una nueva página con versión inicial
     * 
     * @param array $data Datos de la página
     * @return int|null ID de la página creada o null si falla
     */
    public function create(array $data): ?int
    {
        $this->db->begin();
        
        try {
            // Generar slug único
            $data['slug'] = $this->pageModel->uniqueSlug($data['slug'] ?? $data['title']);

            // Crear página
            $id = $this->pageModel->create($data);

            // Crear versión inicial
            PageVersion::create([
                'page_id'      => $id,
                'title'        => $data['title'],
                'content_html' => $data['content_html'] ?? '',
                'content_css'  => $data['content_css'] ?? '',
                'content_js'   => $data['content_js'] ?? '',
                'content_php'  => $data['content_php'] ?? '',
                'note'         => 'Creación inicial',
            ]);

            // Regenerar sitemap
            SitemapService::generate();

            $this->db->commit();
            return $id;
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            \error_log('[PageService] Error al crear: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Elimina una página con invalidación de caché
     */
    public function delete(int $id, int $userId): bool
    {
        $this->db->begin();
        
        try {
            $deleted = $this->pageModel->delete($id, $userId);
            
            if ($deleted) {
                CacheService::invalidatePage($id);
                SitemapService::generate();
            }

            $this->db->commit();
            return $deleted;
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            \error_log('[PageService] Error al eliminar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Renderiza una página pública (con template + SEO + cache)
     */
    public function render(array $page): string
    {
        // 1. Intentar cache
        $cached = CacheService::getPage((int) $page['id']);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Obtener template
        $template = null;
        if (!empty($page['template_id'])) {
            $template = $this->db->fetch(
                "SELECT * FROM templates WHERE id = ?",
                [$page['template_id']]
            );
        }

        // 3. Obtener SEO
        $seo = MetaTag::forPage((int) $page['id']);

        // 4. Menús dinámicos
        $menus = MenuService::allActive();

        // 5. Construir HTML completo
        $html = $this->buildHtml($page, $template, $seo, $menus);

        // 6. Guardar en cache
        CacheService::putPage((int) $page['id'], $html);

        // 7. Contar visita (silencioso)
        $this->trackVisit((int) $page['id']);

        return $html;
    }

    /**
     * Construye el HTML completo de la página
     */
    private function buildHtml(array $page, ?array $template, array $seo, array $menus): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');

        // Head con SEO completo
        $head = $this->renderSeoHead($page, $seo, $siteName);

        // CSS: template + página
        $css = ($template['content_css'] ?? '') . "\n" . ($page['content_css'] ?? '');

        // Contenido: si hay template, insertar en {{content}}
        $body = $page['content_html'] ?? '';
        if ($template && !empty($template['content_html'])) {
            $layout = $template['content_html'];
            if (\str_contains($layout, '{{content}}')) {
                $body = \str_replace('{{content}}', $body, $layout);
            } else {
                $body = $layout . "\n" . $body;
            }
        }

        // JS: template + página
        $js = ($template['content_js'] ?? '') . "\n" . ($page['content_js'] ?? '');

        // PHP seguro (solo si tiene permiso)
        $phpOutput = '';
        if (!empty($page['content_php']) && Auth::can('pages.edit')) {
            \ob_start();
            try {
                (static function() use ($page) {
                    $PAGE = $page;
                    eval('?>' . $page['content_php']);
                })();
            } catch (\Throwable $e) {
                $phpOutput = "<!-- PHP error: " . \htmlspecialchars($e->getMessage()) . " -->";
            }
            $phpOutput .= \ob_get_clean();
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
     * Renderiza el <head> SEO completo
     */
    private function renderSeoHead(array $page, array $seo, string $siteName): string
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $baseUrl = \rtrim($cfg['app_url'] ?? \BASE_URL, '/');
        $pageUrl = $baseUrl . '/' . \ltrim($page['slug'] ?? '', '/');

        // Valores con fallback a configuración global
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

        $out = '';

        // Meta básicos
        $out .= '<title>' . \htmlspecialchars($title, \ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        
        if ($description) {
            $out .= '<meta name="description" content="' . \htmlspecialchars($description, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        
        $out .= '<meta name="robots" content="' . \htmlspecialchars($robots, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        
        if ($canonical) {
            $out .= '<link rel="canonical" href="' . \htmlspecialchars($canonical, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        if (!empty($seo['basic']['keywords'])) {
            $out .= '<meta name="keywords" content="' . \htmlspecialchars($seo['basic']['keywords'], \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        if (!empty($seo['basic']['author'])) {
            $out .= '<meta name="author" content="' . \htmlspecialchars($seo['basic']['author'], \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // Open Graph
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
                $out .= '<meta property="' . $property . '" content="' . \htmlspecialchars($value, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        // Twitter Cards
        $twitterData = [
            'twitter:card'        => $seo['twitter']['card'] ?? 'summary_large_image',
            'twitter:title'       => $seo['twitter']['title'] ?? $title,
            'twitter:description' => $seo['twitter']['description'] ?? $description,
            'twitter:image'       => $seo['twitter']['image'] ?? $seo['og']['image'] ?? $defaultOgImage,
            'twitter:site'        => SettingService::get('seo', 'twitter_site', ''),
        ];

        foreach ($twitterData as $name => $value) {
            if (!empty($value)) {
                $out .= '<meta name="' . $name . '" content="' . \htmlspecialchars($value, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        // Favicon
        $out .= '<link rel="icon" type="image/svg+xml" href="' . \htmlspecialchars($baseUrl . '/assets/favicon.svg', \ENT_QUOTES, 'UTF-8') . '">' . "\n";

        // JSON-LD Schema.org
        $out .= SeoService::jsonLd($page, $seo) . "\n";

        // Google Analytics
        $ga = SettingService::get('seo', 'ga_tracking');
        if (!empty($ga)) {
            $gaEscaped = \htmlspecialchars($ga, \ENT_QUOTES, 'UTF-8');
            $out .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaEscaped}\"></script>\n";
            $out .= "<script>\n";
            $out .= "  window.dataLayer = window.dataLayer || [];\n";
            $out .= "  function gtag(){dataLayer.push(arguments);}\n";
            $out .= "  gtag('js', new Date());\n";
            $out .= "  gtag('config', '{$gaEscaped}');\n";
            $out .= "</script>\n";
        }

        // Google Search Console Verification
        $gsc = SettingService::get('seo', 'gsc_verification');
        if (!empty($gsc)) {
            $out .= '<meta name="google-site-verification" content="' . \htmlspecialchars($gsc, \ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // Meta adicionales
        $out .= '<meta name="generator" content="PePiuoX Editor">' . "\n";
        $out .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";

        return $out;
    }

    /**
     * Registra una visita a la página (silencioso)
     */
    private function trackVisit(int $pageId): void
    {
        try {
            $this->db->execute(
                "INSERT INTO visits (page_id, visit_date, ip_address, user_agent) VALUES (?,?,?,?)",
                [
                    $pageId,
                    \date('Y-m-d H:i:s'),
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );

            $this->db->execute(
                "INSERT INTO page_statistics (page_id, visits) VALUES (?,1)
                 ON DUPLICATE KEY UPDATE visits = visits + 1, last_visit = NOW()",
                [$pageId]
            );
        } catch (\Throwable $e) {
            // Silencioso: no debe romper la carga de la página
        }
    }
}