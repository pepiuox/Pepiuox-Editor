<?php
namespace App\Controllers;

use App\Core\{Request, Response, Database};
use App\Services\PageRenderer;

class PublicController
{
    /**
     * Muestra la página de inicio
     */
    public function home(Request $req): void
    {
        $renderer = new PageRenderer();
        $page = $renderer->getHomePage();
        
        if (!$page) {
            Response::abort(404, 'No hay página de inicio configurada');
        }
        
        $this->renderPage($page, $renderer);
    }

    /**
     * Muestra una página pública por slug
     */
    public function show(Request $req, array $params): void
    {
        $slug = $params['slug'] ?? '';
        $renderer = new PageRenderer();
        $page = $renderer->getPageBySlug($slug);
        
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }
        
        $this->renderPage($page, $renderer);
    }

    /**
     * Renderiza una página con el layout público
     */
    private function renderPage(array $page, PageRenderer $renderer): void
    {
        // Obtener menú
        $menu = $renderer->getMenu();
        
        // Renderizar contenido
        $content = $renderer->buildPageHtml($page);
        
        // Renderizar layout completo
        Response::render(View::render('public.layout', compact('page', 'menu', 'content')));
    }

    /**
     * Genera sitemap.xml
     */
    public function sitemap(Request $req): void
    {
        $xml = \App\Services\SitemapService::getXml();
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
        exit;
    }
}