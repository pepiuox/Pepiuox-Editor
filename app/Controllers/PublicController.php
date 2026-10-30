<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Auth;
use App\Core\View;
use App\Services\PageRenderer;
use App\Services\SiteSettings;
use App\Core\Response;

class PublicController
{
    private PageRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new PageRenderer();
    }

    /**
     * Muestra la página de inicio
     */
    public function home(Request $request, array $params = []): void
    {
        $page = $this->renderer->getHomePage();
        
        if (!$page) {
            // Si no hay página de inicio, mostrar un mensaje
            $this->showFallbackPage('Bienvenido', 'Contenido de la página de inicio');
            return;
        }

        $this->renderPage($page);
    }

    /**
     * Muestra una página por slug
     */
    public function show(Request $request, array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        
        // Si el slug está vacío o es 'home', mostrar inicio
        if (empty($slug) || $slug === 'home') {
            $this->home($request, $params);
            return;
        }

        $page = $this->renderer->getPageBySlug($slug);
        
        if (!$page) {
            // Página no encontrada
            $this->show404($slug);
            return;
        }

        $this->renderPage($page);
    }

    /**
     * Renderiza una página
     */
    private function renderPage(array $page): void
    {
        // Construir HTML de la página
        $content = $this->renderer->buildPageHtml($page);

        // Obtener menú: se resuelve para ESTA página (menú asignado → principal
        // de la ubicación → menú automático).
        $menu = $this->renderer->menuForPage($page);

        // Renderizar la vista completa
        $this->renderLayout($page, $menu, $content);
    }

    /**
     * Renderiza el layout público: navbar + contenido + footer.
     *
     * La navbar decide sus acciones con Auth::check():
     *  - Visitante: «Iniciar sesión» y «Crear cuenta».
     *  - Con sesión: acceso al panel de administración, sin ingreso ni registro.
     */
    private function renderLayout(array $page, array $menu, string $content): void
    {
        $isLoggedIn = Auth::check();

        View::partial('public.layout', [
            'page' => [
                // Sin escapar: el layout compone el título con el nombre del
                // sitio y escapa una sola vez. Antes se escapaba aquí y otra
                // vez en la vista, y un «&» salía como «&amp;amp;».
                'title'       => (string) ($page['title'] ?? ''),
                'description' => (string) ($page['description'] ?? ''),
                'slug'        => (string) ($page['slug'] ?? ''),
            ],
            'menu'     => $menu,
            'content'  => $content,
            // El nombre sale de Configuración → General → «Nombre del Sitio».
            'appName'  => SiteSettings::name(),
            'authUser' => $isLoggedIn ? Auth::user() : null,
        ]);
    }

    /**
     * Muestra página de error 404
     */
    private function show404(string $slug): void
    {
        http_response_code(404);
        $content = '<div class="text-center py-5"><h1 class="display-1">404</h1><p class="lead">La página solicitada no existe.</p></div>';
        $menu = $this->renderer->getMenu();

        $this->renderLayout([
            'title'       => 'Página no encontrada',
            'description' => 'Página no encontrada',
            'slug'        => '404',
        ], $menu, $content);
    }

    /**
     * Muestra página fallback
     */
    private function showFallbackPage(string $title, string $description): void
    {
        $content = '<div class="text-center py-5"><h1>' . htmlspecialchars($title) . '</h1><p>' . htmlspecialchars($description) . '</p></div>';
        $menu = $this->renderer->getMenu();

        $this->renderLayout([
            'title'       => $title,
            'description' => $description,
            'slug'        => 'home',
        ], $menu, $content);
    }

    /**
     * Genera el sitemap.xml
     */
    public function sitemap(): void
    {
        // Obtener todas las páginas publicadas
        $pages = Database::fetchAll(
            "SELECT slug, updated_at FROM pages WHERE is_published = 1 AND status = 'published'"
        );
        
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Página de inicio
        echo '<url><loc>' . BASE_URL . '/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';
        
        foreach ($pages as $page) {
            echo '<url>';
            echo '<loc>' . BASE_URL . '/' . $page['slug'] . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($page['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>0.8</priority>';
            echo '</url>';
        }
        
        echo '</urlset>';
    }
}
