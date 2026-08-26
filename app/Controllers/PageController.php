<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, CSRF};
use App\Models\PageModel;
use App\Models\PageStatisticsModel;
use App\Services\PageRenderer;
use App\Services\PagePublicService;
use App\Services\SeoService;
use App\Services\CacheService;

/**
 * Controlador principal de páginas
 * 
 * @package App\Controllers
 */
class PageController
{
    private PageModel $pageModel;
    private PageStatisticsModel $statsModel;
    private PageRenderer $renderer;
    private PagePublicService $publicService;

    public function __construct(
        PageModel $pageModel,
        PageStatisticsModel $statsModel,
        PageRenderer $renderer,
        PagePublicService $publicService
    ) {
        $this->pageModel = $pageModel;
        $this->statsModel = $statsModel;
        $this->renderer = $renderer;
        $this->publicService = $publicService;
    }

    /**
     * Muestra la página de inicio
     */
    public function home(Request $request): void
    {
        $this->publicService->renderHome();
    }

    /**
     * Muestra una página por slug
     */
    public function show(Request $request, array $params): void
    {
        $slug = $params['slug'] ?? '';
        
        if (empty($slug) || $slug === 'home' || $slug === 'index') {
            $this->home($request);
            return;
        }

        $this->publicService->renderBySlug($slug);
    }

    /**
     * Genera sitemap.xml
     */
    public function sitemap(Request $request): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo $this->publicService->generateSitemap();
        exit;
    }

    /**
     * Genera robots.txt
     */
    public function robots(Request $request): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->publicService->generateRobots();
        exit;
    }

    /**
     * Admin: Lista de páginas
     */
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        $pages = $this->pageModel->getPages($userId, $search, $status);

        Response::render(View::render('admin.pages.index', [
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
        ]));
    }

    /**
     * Admin: Crear página
     */
    public function create(Request $request): void
    {
        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        Response::render(View::render('admin.pages.create'));
    }

    /**
     * Admin: Guardar página
     */
    public function store(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        $data = [
            'user_id' => Auth::id(),
            'title' => $request->post('title'),
            'slug' => $request->post('slug') ?: $request->post('title'),
            'description' => $request->post('description', ''),
            'content_html' => $request->post('content_html', ''),
            'content_css' => $request->post('content_css', ''),
            'content_js' => $request->post('content_js', ''),
            'content_php' => Auth::can('pages.edit') ? $request->post('content_php', '') : '',
            'status' => $request->post('status', 'draft'),
            'is_published' => (int)$request->post('is_published', 0),
            'startpage' => (int)$request->post('startpage', 0),
        ];

        if (empty($data['title'])) {
            flash('error', 'El título es obligatorio.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        // Verificar slug único
        $existing = $this->pageModel->getBySlug($data['slug'], $data['user_id']);
        if ($existing) {
            $data['slug'] .= '-' . time();
        }

        $id = $this->pageModel->create($data);

        if (!$id) {
            flash('error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        // Si es página de inicio, desactivar otras
        if ($data['startpage'] === 1) {
            $this->pageModel->setStartpage($id, $data['user_id']);
        }

        // Guardar SEO
        $this->saveSEO($id, $request);

        // Invalidar caché
        CacheService::invalidatePage($id);

        flash('success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    /**
     * Admin: Editar página
     */
    public function edit(Request $request, array $params): void
    {
        $id = (int)$params['id'];
        $userId = Auth::id();

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $page = $this->pageModel->getPage($id, $userId);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        Response::render(View::render('admin.pages.edit', [
            'page' => $page,
        ]));
    }

    /**
     * Admin: Actualizar página
     */
    public function update(Request $request, array $params): void
    {
        CSRF::guard();

        $id = (int)$params['id'];
        $userId = Auth::id();

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $page = $this->pageModel->getPage($id, $userId);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $data = [
            'title' => $request->post('title', $page['title']),
            'slug' => $request->post('slug', $page['slug']),
            'description' => $request->post('description', $page['description']),
            'content_html' => $request->post('content_html', $page['content_html']),
            'content_css' => $request->post('content_css', $page['content_css']),
            'content_js' => $request->post('content_js', $page['content_js']),
            'content_php' => Auth::can('pages.edit') ? $request->post('content_php', $page['content_php']) : $page['content_php'],
            'status' => $request->post('status', $page['status']),
            'is_published' => (int)$request->post('is_published', $page['is_published']),
            'startpage' => (int)$request->post('startpage', $page['startpage']),
        ];

        // Verificar slug único
        $existing = $this->pageModel->getBySlug($data['slug'], $userId);
        if ($existing && $existing['id'] !== $id) {
            $data['slug'] .= '-' . time();
        }

        $updated = $this->pageModel->update($id, $data, $userId);

        if (!$updated) {
            flash('error', 'Error al actualizar la página.');
            Response::redirect(url("admin/pages/{$id}/edit"));
            return;
        }

        // Si es página de inicio, desactivar otras
        if ($data['startpage'] === 1) {
            $this->pageModel->setStartpage($id, $userId);
        }

        // Guardar SEO
        $this->saveSEO($id, $request);

        // Invalidar caché
        CacheService::invalidatePage($id);

        flash('success', 'Página actualizada correctamente.');
        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    /**
     * Admin: Eliminar página
     */
    public function delete(Request $request, array $params): void
    {
        CSRF::guard();

        $id = (int)$params['id'];
        $userId = Auth::id();

        if (!Auth::can('pages.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar páginas');
        }

        $deleted = $this->pageModel->delete($id, $userId);

        if ($deleted) {
            CacheService::invalidatePage($id);
            flash('success', 'Página eliminada correctamente.');
        } else {
            flash('error', 'Error al eliminar la página.');
        }

        Response::redirect(url('admin/pages'));
    }

    /**
     * Admin: Duplicar página
     */
    public function duplicate(Request $request, array $params): void
    {
        CSRF::guard();

        $id = (int)$params['id'];
        $userId = Auth::id();

        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para duplicar páginas');
        }

        $newId = $this->pageModel->duplicate($id, $userId);

        if ($newId) {
            flash('success', 'Página duplicada correctamente.');
            Response::redirect(url("admin/pages/{$newId}/edit"));
        } else {
            flash('error', 'Error al duplicar la página.');
            Response::redirect(url('admin/pages'));
        }
    }

    /**
     * Admin: Cambiar estado
     */
    public function toggleStatus(Request $request, array $params): void
    {
        CSRF::guard();

        $id = (int)$params['id'];
        $userId = Auth::id();
        $newStatus = $request->post('status', 'draft');

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para cambiar el estado');
        }

        $updated = $this->pageModel->toggleStatus($id, $newStatus, $userId);

        if ($updated) {
            CacheService::invalidatePage($id);
            flash('success', 'Estado actualizado correctamente.');
        } else {
            flash('error', 'Error al cambiar el estado.');
        }

        Response::redirect(url('admin/pages'));
    }

    /**
     * Guarda SEO de la página
     */
    private function saveSEO(int $pageId, Request $request): void
    {
        try {
            $seoData = [
                'page_id' => $pageId,
                'meta_title' => $request->post('seo_title'),
                'meta_description' => $request->post('seo_description'),
                'meta_keywords' => $request->post('seo_keywords'),
                'meta_robots' => $request->post('seo_robots', 'index, follow'),
                'meta_canonical' => $request->post('seo_canonical'),
                'og_title' => $request->post('og_title'),
                'og_description' => $request->post('og_description'),
                'og_image' => $request->post('og_image'),
                'og_type' => $request->post('og_type', 'website'),
                'og_url' => $request->post('og_url'),
                'twitter_card' => $request->post('twitter_card', 'summary_large_image'),
                'twitter_title' => $request->post('twitter_title'),
                'twitter_description' => $request->post('twitter_description'),
                'twitter_image' => $request->post('twitter_image'),
            ];

            // Verificar si ya existe SEO
            $existing = $this->db->fetch(
                "SELECT id FROM meta_tags WHERE page_id = ?",
                [$pageId]
            );

            if ($existing) {
                $this->db->execute(
                    "UPDATE meta_tags SET 
                        meta_title = ?, meta_description = ?, meta_keywords = ?,
                        meta_robots = ?, meta_canonical = ?,
                        og_title = ?, og_description = ?, og_image = ?, og_type = ?, og_url = ?,
                        twitter_card = ?, twitter_title = ?, twitter_description = ?, twitter_image = ?
                     WHERE page_id = ?",
                    array_merge(array_values($seoData), [$pageId])
                );
            } else {
                $this->db->insert(
                    "INSERT INTO meta_tags (
                        page_id, meta_title, meta_description, meta_keywords,
                        meta_robots, meta_canonical,
                        og_title, og_description, og_image, og_type, og_url,
                        twitter_card, twitter_title, twitter_description, twitter_image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array_values($seoData)
                );
            }
        } catch (\Throwable $e) {
            error_log('[PageController] Error al guardar SEO: ' . $e->getMessage());
        }
    }
}