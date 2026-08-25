<?php
namespace App\Controllers;
use App\Core\{Request, Response, Database};
use App\Models\Page;
use App\Services\PageService;

class PublicController
{
    public function home(Request $req): void
    {
        $page = Page::home();
        if (!$page) {
            // Si no hay home, mostrar primera publicada
            $page = Database::fetch("SELECT * FROM pages WHERE status='published' ORDER BY id ASC LIMIT 1");
        }
        if (!$page) Response::abort(404, 'No hay páginas publicadas');
        $this->render($page);
    }

    public function show(Request $req, array $params): void
    {
        $slug = $params['slug'] ?? '';

        // 1. Redirecciones
        $redir = Database::fetch(
            "SELECT * FROM redirects WHERE from_path = ? AND is_active = 1",
            ['/' . ltrim($slug, '/')]
        );
        if ($redir) {
            Database::execute("UPDATE redirects SET hits = hits + 1 WHERE id = ?", [$redir['id']]);
            Response::redirect($redir['to_path'], (int)$redir['status_code']);
        }

        // 2. Página
        $page = Page::findBySlug($slug);
        if (!$page) Response::abort(404, 'Página no encontrada');
        if ($page['status'] !== 'published' || !$page['is_published']) {
            Response::abort(404, 'Página no disponible');
        }

        // 3. Programada
        if (!empty($page['publish_at']) && strtotime($page['publish_at']) > time()) {
            Response::abort(404, 'Página aún no publicada');
        }

        $this->render($page);
    }

    private function render(array $page): void
    {
        $html = PageService::render($page);
        Response::render($html);
    }

    /** Sitemap.xml */
    public function sitemap(Request $req): void
    {
        $xml = \App\Services\SitemapService::getXml();
        http_response_code(200);
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
        exit;
    }
}