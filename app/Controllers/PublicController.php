<?php
namespace App\Controllers;

use App\Core\Request;
use App\Services\PageRenderer;
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
        $title = $this->renderer->getPageTitle($page);
        $description = $this->renderer->getPageDescription($page);
        $slug = $this->renderer->getPageSlug($page);
        
        // Construir HTML de la página
        $content = $this->renderer->buildPageHtml($page);
        
        // Obtener menú
        $menu = $this->renderer->getMenu();
        $menuHtml = $this->renderer->renderMenuHtml($menu);
        
        // Renderizar la vista completa
        $this->renderLayout($title, $description, $slug, $content, $menuHtml);
    }

    /**
     * Renderiza el layout completo de la página
     */
    private function renderLayout(string $title, string $description, string $slug, string $content, string $menuHtml): void
    {
        // Aquí puedes incluir tu layout principal
        // Este es un ejemplo básico
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?></title>
            <meta name="description" content="<?= htmlspecialchars($description) ?>">
            <link rel="canonical" href="<?= BASE_URL . '/' . $slug ?>">
            
            <!-- Bootstrap CSS -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            
            <!-- Estilos personalizados -->
            <style>
                /* Estilos básicos del sitio */
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    line-height: 1.6;
                    color: #212529;
                }
                .navbar {
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .page-content {
                    min-height: 60vh;
                }
                .page-body {
                    padding: 2rem 0;
                }
                footer {
                    background: #f8f9fa;
                    padding: 2rem 0;
                    margin-top: 3rem;
                }
            </style>
        </head>
        <body>
            <!-- Header con menú -->
            <header>
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container">
                        <a class="navbar-brand" href="<?= BASE_URL ?>">Mi Sitio Web</a>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarMain">
                            <?= $menuHtml ?>
                        </div>
                    </div>
                </nav>
            </header>

            <!-- Contenido principal -->
            <main>
                <div class="container">
                    <?= $content ?>
                </div>
            </main>

            <!-- Footer -->
            <footer>
                <div class="container">
                    <p class="text-center text-muted">&copy; <?= date('Y') ?> Mi Sitio Web. Todos los derechos reservados.</p>
                </div>
            </footer>

            <!-- Scripts -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            
            <!-- Scripts para el menú -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Manejador para dropdowns en móvil
                    document.querySelectorAll('.dropdown-toggle, .dropdown-caret, .submenu-caret').forEach(function(btn) {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const parent = this.closest('.has-submenu');
                            if (parent) {
                                const dropdown = parent.querySelector('.dropdown-menu');
                                if (dropdown) {
                                    dropdown.classList.toggle('show');
                                }
                            }
                        });
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Muestra página de error 404
     */
    private function show404(string $slug): void
    {
        http_response_code(404);
        $title = 'Página no encontrada';
        $content = '<div class="text-center py-5"><h1 class="display-1">404</h1><p class="lead">La página solicitada no existe.</p></div>';
        $menu = $this->renderer->getMenu();
        $menuHtml = $this->renderer->renderMenuHtml($menu);
        
        $this->renderLayout($title, 'Página no encontrada', '404', $content, $menuHtml);
    }

    /**
     * Muestra página fallback
     */
    private function showFallbackPage(string $title, string $description): void
    {
        $content = '<div class="text-center py-5"><h1>' . htmlspecialchars($title) . '</h1><p>' . htmlspecialchars($description) . '</p></div>';
        $menu = $this->renderer->getMenu();
        $menuHtml = $this->renderer->renderMenuHtml($menu);
        
        $this->renderLayout($title, $description, 'home', $content, $menuHtml);
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