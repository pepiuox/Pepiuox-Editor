<?php
namespace App\Services;

use App\Core\{Database, Auth, Response, View};
use App\Models\{PageModel, PageStatisticsModel};

/**
 * Servicio para renderización de páginas públicas
 */
class PagePublicService
{
    private Database $db;
    private PageModel $pageModel;
    private PageStatisticsModel $statsModel;
    private PageRenderer $renderer;

    public function __construct(
        Database $db,
        PageModel $pageModel,
        PageStatisticsModel $statsModel,
        PageRenderer $renderer
    ) {
        $this->db = $db;
        $this->pageModel = $pageModel;
        $this->statsModel = $statsModel;
        $this->renderer = $renderer;
    }

    /**
     * Renderiza una página pública
     */
    public function renderPage(array $page, array $menu = [], array $seo = []): void
    {
        $this->statsModel->registerVisit(
            (int)$page['id'],
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        );

        $content = $this->renderer->buildPageHtml($page);
        $stats = $this->statsModel->getPageStatistics((int)$page['id']);

        View::render('layouts.public', [
            'page'    => $page,
            'menu'    => $menu,
            'content' => $content,
            'seo'     => $seo,
            'stats'   => $stats,
        ]);
    }

    /**
     * Renderiza una página por slug
     */
    public function renderBySlug(string $slug): void
    {
        $page = $this->pageModel->getPublicPageBySlug($slug);
        if (!$page) {
            $this->render404();
            return;
        }

        $menu = $this->renderer->getMenu();
        $seo = $this->getPageSEO($page);
        $this->renderPage($page, $menu, $seo);
    }

    /**
     * Renderiza la página de inicio
     */
    public function renderHome(): void
    {
        $page = $this->pageModel->getStartPage();
        if (!$page) {
            $this->renderDefaultHome();
            return;
        }

        $menu = $this->renderer->getMenu();
        $seo = $this->getPageSEO($page);
        $this->renderPage($page, $menu, $seo);
    }

    /**
     * Renderiza página por defecto si no hay inicio
     */
    private function renderDefaultHome(): void
    {
        $page = [
            'id'           => 0,
            'title'        => 'Bienvenido a PePiuoX Editor',
            'description'  => 'Plataforma de creación y gestión de contenido',
            'slug'         => 'home',
            'content_html' => '<div class="container py-5"><h1>Bienvenido a PePiuoX Editor</h1><p>Comienza a crear tu contenido.</p></div>',
            'content_css'  => '',
            'content_js'   => '',
            'is_published' => 1,
            'status'       => 'published'
        ];

        $menu = $this->renderer->getMenu();
        View::render('layouts.public', [
            'page'    => $page,
            'menu'    => $menu,
            'content' => $this->renderer->buildPageHtml($page),
        ]);
    }

    /**
     * Renderiza página 404
     */
    public function render404(): void
    {
        http_response_code(404);
        $page = [
            'id'           => 0,
            'title'        => 'Página no encontrada',
            'description'  => 'La página solicitada no existe',
            'slug'         => '404',
            'content_html' => '<div class="container py-5 text-center"><h1 class="display-1">404</h1><p class="lead">Página no encontrada</p></div>',
            'content_css'  => '',
            'content_js'   => '',
            'is_published' => 1,
            'status'       => 'published'
        ];

        $menu = $this->renderer->getMenu();
        View::render('layouts.public', [
            'page'    => $page,
            'menu'    => $menu,
            'content' => $this->renderer->buildPageHtml($page),
        ]);
    }

    /**
     * Obtiene SEO de la página
     */
    private function getPageSEO(array $page): array
    {
        try {
            $seo = $this->db->fetch(
                "SELECT * FROM meta_tags WHERE page_id = ?",
                [$page['id']]
            );

            if ($seo) {
                return [
                    'basic' => [
                        'title'       => $seo['meta_title'] ?? $page['title'],
                        'description' => $seo['meta_description'] ?? $page['description'],
                        'robots'      => $seo['meta_robots'] ?? 'index, follow',
                        'canonical'   => $seo['meta_canonical'] ?? '',
                    ],
                    'og' => [
                        'title'       => $seo['og_title'] ?? $page['title'],
                        'description' => $seo['og_description'] ?? $page['description'],
                        'image'       => $seo['og_image'] ?? '',
                        'type'        => $seo['og_type'] ?? 'website',
                        'url'         => $seo['og_url'] ?? '',
                    ],
                    'twitter' => [
                        'card'        => $seo['twitter_card'] ?? 'summary_large_image',
                        'title'       => $seo['twitter_title'] ?? $page['title'],
                        'description' => $seo['twitter_description'] ?? $page['description'],
                        'image'       => $seo['twitter_image'] ?? '',
                    ],
                ];
            }
        } catch (\Throwable $e) {
            error_log('[PagePublicService] Error al obtener SEO: ' . $e->getMessage());
        }

        return [
            'basic' => [
                'title'       => $page['title'],
                'description' => $page['description'] ?? '',
                'robots'      => 'index, follow',
                'canonical'   => '',
            ],
            'og' => [
                'title'       => $page['title'],
                'description' => $page['description'] ?? '',
                'image'       => '',
                'type'        => 'website',
                'url'         => '',
            ],
            'twitter' => [
                'card'        => 'summary_large_image',
                'title'       => $page['title'],
                'description' => $page['description'] ?? '',
                'image'       => '',
            ],
        ];
    }

    /**
     * Genera el sitemap.xml
     */
    public function generateSitemap(): string
    {
        $pages = $this->pageModel->getPublishedPages();
        $baseUrl = rtrim(BASE_URL ?? '', '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $xml .= '<url>';
        $xml .= '<loc>' . $baseUrl . '/</loc>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        foreach ($pages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/' . $page['slug'] . '</loc>';
            $xml .= '<lastmod>' . date('Y-m-d', strtotime($page['updated_at'] ?? 'now')) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Genera robots.txt
     */
    public function generateRobots(): string
    {
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        
        $txt = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /login\n";
        $txt .= "Disallow: /forgot\n";
        $txt .= "Disallow: /reset\n";
        $txt .= "Disallow: /api/\n";
        $txt .= "Disallow: /cron/\n";
        $txt .= "Sitemap: $baseUrl/sitemap.xml\n";
        
        return $txt;
    }
}