<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, PageModel, PageVersion, MetaTag, Template};
use App\Models\PageStatisticsModel;
use App\Services\{AuditService, CacheService, SitemapService, PageRenderer, PagePublicService};

/**
 * Controlador unificado de páginas
 * Administra: frontend público + admin CRUD + builder visual + autosave + versiones
 */
class PagesController
{
    private Database $db;
    private PageModel $pageModel;
    private PageStatisticsModel $statsModel;
    private PageRenderer $renderer;
    private PagePublicService $publicService;

    public function __construct(
        Database $db,
        PageModel $pageModel,
        PageStatisticsModel $statsModel,
        PageRenderer $renderer,
        PagePublicService $publicService
    ) {
        $this->db = $db;
        $this->pageModel = $pageModel;
        $this->statsModel = $statsModel;
        $this->renderer = $renderer;
        $this->publicService = $publicService;
    }

    // ═══════════════════════════════════════════════════════════════
    // 🌐 FRONTEND PÚBLICO
    // ═══════════════════════════════════════════════════════════════

    public function home(Request $req): void
    {
        $this->publicService->renderHome();
    }

    public function show(Request $req, array $params): void
    {
        $slug = $params['slug'] ?? '';
        if (empty($slug) || $slug === 'home' || $slug === 'index') {
            $this->home($req);
            return;
        }
        $this->publicService->renderBySlug($slug);
    }

    public function sitemap(Request $req): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo $this->publicService->generateSitemap();
        exit;
    }

    public function robots(Request $req): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->publicService->generateRobots();
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋 ADMIN: LISTADO
    // ═══════════════════════════════════════════════════════════════

    public function index(Request $req): void
    {
        if (!Auth::can('pages.view')) {
            Response::abort(403, 'No tienes permisos para ver páginas');
        }

        $status = $req->get('status');
        $pages = Page::all($status ?: null);

        Response::render(View::render('admin.pages.index', [
            'pages'  => $pages,
            'status' => $status,
        ]));
    }

    // ═══════════════════════════════════════════════════════════════
    // ➕ ADMIN: CREAR
    // ═══════════════════════════════════════════════════════════════

    public function create(Request $req): void
    {
        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        $templates = Template::allActive();
        $pages = Page::all();

        Response::render(View::render('admin.pages.create', [
            'templates' => $templates,
            'pages'     => $pages,
        ]));
    }

    public function store(Request $req): void
    {
        CSRF::guard();

        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        $title = trim($req->post('title', ''));
        if (empty($title)) {
            Session::flash('flash.error', 'El título es obligatorio.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        $slug = trim($req->post('slug', ''));
        if (empty($slug)) {
            $slug = Page::slugify($title);
        }
        $slug = Page::uniqueSlug($slug);

        $data = [
            'user_id'        => Auth::id(),
            'template_id'    => $req->post('template_id') ?: null,
            'parent_id'      => $req->post('parent_id') ?: null,
            'title'          => $title,
            'slug'           => $slug,
            'description'    => trim($req->post('description', '')),
            'h1'             => trim($req->post('h1', '')),
            'content_html'   => $req->post('content_html', ''),
            'content_css'    => $req->post('content_css', ''),
            'content_js'     => $req->post('content_js', ''),
            'content_php'    => Auth::can('pages.edit') ? $req->post('content_php', '') : '',
            'grapesjs_data'  => $req->post('grapesjs_data'),
            'startpage'      => (int)$req->post('startpage', 0),
            'is_published'   => (int)$req->post('is_published', 0),
            'status'         => $req->post('status', 'draft'),
            'publish_at'     => $req->post('publish_at') ?: null,
        ];

        $id = Page::create($data);

        if (!$id) {
            Session::flash('flash.error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        if ($data['startpage'] === 1) {
            $this->pageModel->setStartpage($id, Auth::id());
        }

        $this->saveSeo($id, $req);

        AuditService::log(Auth::id(), 'page_created', "Página creada: {$title}", 'pages', $id);
        SitemapService::generate();

        Session::flash('flash.success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/{$id}/builder"));
    }

    // ═══════════════════════════════════════════════════════════════
    // ✏️ ADMIN: EDITAR (metadata + SEO)
    // ═══════════════════════════════════════════════════════════════

    public function edit(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $id = (int)$params['id'];
        $page = $this->resolvePage($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $templates = Template::allActive();
        $pages = Page::all();
        $seo = $this->normalizeSeo(MetaTag::forPage($id));
        $versions = PageVersion::forPage($id);

        Response::render(View::render('admin.pages.edit', [
            'page'      => $page,
            'templates' => $templates,
            'pages'     => $pages,
            'seo'       => $seo,
            'versions'  => $versions,
        ]));
    }

    // ═══════════════════════════════════════════════════════════════
    // 💾 ADMIN: ACTUALIZAR (unificado: metadata + visual)
    // ═══════════════════════════════════════════════════════════════

    public function update(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.edit')) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
                return;
            }
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $id = (int)$params['id'];
        $page = $this->resolvePage($id);

        if (!$page) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Página no encontrada']);
                return;
            }
            Response::abort(404, 'Página no encontrada');
        }

        $mode = $req->post('mode', 'metadata');
        $data = $this->buildUpdateData($mode, $req, $page);

        // Slug único
        $data['slug'] = Page::uniqueSlug($data['slug'], $id);

        $updated = Page::update($id, $data);

        if (!$updated) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error al guardar']);
                return;
            }
            Session::flash('flash.error', 'Error al actualizar la página.');
            Response::redirect(url("admin/pages/{$id}/edit"));
            return;
        }

        // Página de inicio
        if (($data['startpage'] ?? 0) === 1) {
            $this->pageModel->setStartpage($id, (int)$page['user_id']);
        }

        // SEO
        $this->saveSeo($id, $req);

        // Versión
        PageVersion::create([
            'page_id'      => $id,
            'title'        => $data['title'],
            'content_html' => $data['content_html'] ?? $page['content_html'],
            'content_css'  => $data['content_css'] ?? $page['content_css'],
            'content_js'   => $data['content_js'] ?? $page['content_js'],
            'content_php'  => $data['content_php'] ?? $page['content_php'],
            'note'         => $req->post('version_note', $mode === 'visual' ? 'Edición desde builder' : 'Actualización de metadatos'),
        ]);

        // Caché y sitemap
        CacheService::invalidatePage($id);
        SitemapService::generate();

        AuditService::log(Auth::id(), 'page_updated', "Página actualizada: {$data['title']}", 'pages', $id);

        // Respuesta
        if ($req->isAjax()) {
            Response::json(['success' => true, 'message' => 'Guardado correctamente', 'id' => $id]);
            return;
        }

        Session::flash('flash.success', 'Página guardada correctamente.');
        $redirect = $mode === 'visual'
            ? url("admin/pages/{$id}/builder")
            : url("admin/pages/{$id}/edit");
        Response::redirect($redirect);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🗑️ ADMIN: ELIMINAR
    // ═══════════════════════════════════════════════════════════════

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar páginas');
        }

        $id = (int)$params['id'];
        $page = $this->resolvePage($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $title = $page['title'];
        $deleted = Page::delete($id);

        if ($deleted) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_deleted', "Página eliminada: {$title}", 'pages', $id);
            Session::flash('flash.success', 'Página eliminada correctamente.');
        } else {
            Session::flash('flash.error', 'Error al eliminar la página.');
        }

        Response::redirect(url('admin/pages'));
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋 ADMIN: DUPLICAR
    // ═══════════════════════════════════════════════════════════════

    public function duplicate(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para duplicar páginas');
        }

        $id = (int)$params['id'];
        $page = $this->resolvePage($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $newSlug = Page::uniqueSlug($page['slug'] . '-copia');

        $newId = Page::create([
            'user_id'        => Auth::id(),
            'template_id'    => $page['template_id'],
            'parent_id'      => $page['parent_id'],
            'title'          => $page['title'] . ' (copia)',
            'slug'           => $newSlug,
            'description'    => $page['description'],
            'h1'             => $page['h1'],
            'content_html'   => $page['content_html'],
            'content_css'    => $page['content_css'],
            'content_js'     => $page['content_js'],
            'content_php'    => $page['content_php'],
            'grapesjs_data'  => $page['grapesjs_data'],
            'startpage'      => 0,
            'is_published'   => 0,
            'status'         => 'draft',
            'publish_at'     => null,
        ]);

        if ($newId) {
            AuditService::log(Auth::id(), 'page_duplicated', "Página duplicada desde ID {$id}", 'pages', $newId);
            Session::flash('flash.success', 'Página duplicada correctamente.');
            Response::redirect(url("admin/pages/{$newId}/edit"));
        } else {
            Session::flash('flash.error', 'Error al duplicar la página.');
            Response::redirect(url('admin/pages'));
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔄 ADMIN: CAMBIAR ESTADO
    // ═══════════════════════════════════════════════════════════════

    public function toggleStatus(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos');
        }

        $id = (int)$params['id'];
        $newStatus = $req->post('status', 'draft');
        $isPub = ($newStatus === 'published') ? 1 : 0;

        $updated = Page::update($id, [
            'status'       => $newStatus,
            'is_published' => $isPub,
        ]);

        if ($updated) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_status_changed', "Estado cambiado a {$newStatus}", 'pages', $id);

            if ($req->isAjax()) {
                Response::json(['success' => true, 'message' => 'Estado actualizado']);
                return;
            }
            Session::flash('flash.success', 'Estado actualizado correctamente.');
        } else {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error']);
                return;
            }
            Session::flash('flash.error', 'Error al cambiar el estado.');
        }

        Response::redirect(url('admin/pages'));
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎨 BUILDER VISUAL (GrapesJS)
    // ═══════════════════════════════════════════════════════════════

    public function builder(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para usar el builder');
        }

        $id = (int)$params['id'];
        $page = $this->resolvePage($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $templates = Template::allActive();
        $existingAssets = $this->getExistingAssets();
        $seo = $this->normalizeSeo(MetaTag::forPage($id));

        Response::render(View::render('admin.pages.builder', [
            'page'           => $page,
            'templates'      => $templates,
            'existingAssets' => $existingAssets,
            'seo'            => $seo,
        ]));
    }

    // ═══════════════════════════════════════════════════════════════
    // 💾 AUTOSAVE (AJAX)
    // ═══════════════════════════════════════════════════════════════

    public function autosave(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }

        $id = (int)$params['id'];
        $userId = Auth::id();

        $this->db->execute(
            "DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?",
            [$id, $userId]
        );

        $inserted = $this->db->insert(
            "INSERT INTO page_autosave 
            (page_id, user_id, content_html, content_css, content_js, content_php, grapesjs_data)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $id,
                $userId,
                $req->post('content_html', ''),
                $req->post('content_css', ''),
                $req->post('content_js', ''),
                $req->post('content_php', ''),
                $req->post('grapesjs_data', ''),
            ]
        );

        Response::json([
            'success' => (bool)$inserted,
            'message' => 'Autoguardado correctamente',
        ]);
    }

    public function getAutosave(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }

        $id = (int)$params['id'];
        $userId = Auth::id();

        $autosave = $this->db->fetch(
            "SELECT * FROM page_autosave 
             WHERE page_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$id, $userId]
        );

        if (!$autosave) {
            Response::json(['success' => true, 'data' => null]);
            return;
        }

        Response::json([
            'success' => true,
            'data' => [
                'content_html'  => $autosave['content_html'],
                'content_css'   => $autosave['content_css'],
                'content_js'    => $autosave['content_js'],
                'content_php'   => $autosave['content_php'],
                'grapesjs_data' => $autosave['grapesjs_data'],
                'created_at'    => $autosave['created_at'],
                'age_seconds'   => time() - strtotime($autosave['created_at']),
            ],
        ]);
    }

    public function clearAutosave(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }

        $id = (int)$params['id'];
        $userId = Auth::id();

        $deleted = $this->db->execute(
            "DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?",
            [$id, $userId]
        );

        Response::json(['success' => (bool)$deleted]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🖼️ ASSETS (AJAX)
    // ═══════════════════════════════════════════════════════════════

    public function uploadAsset(Request $req): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'errors' => ['Sin permisos']], 403);
            return;
        }

        $files = $_FILES['files'] ?? null;
        if (!$files) {
            Response::json(['success' => false, 'errors' => ['No se recibieron archivos']]);
            return;
        }

        $uploaded = [];
        $errors = [];
        $names = (array)$files['name'];

        for ($i = 0; $i < count($names); $i++) {
            $single = [
                'name'     => $names[$i],
                'type'     => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error'    => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size'     => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            ];

            if ($single['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $single['name'];
                continue;
            }

            $ext = strtolower(pathinfo($single['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Tipo no permitido: ' . $single['name'];
                continue;
            }

            if ($single['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Archivo demasiado grande (máx 5MB): ' . $single['name'];
                continue;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($single['tmp_name']);
            if (!str_starts_with($mime, 'image/')) {
                $errors[] = 'MIME inválido: ' . $single['name'];
                continue;
            }

            $yearMonth = date('Y/m');
            $dir = UPLOADS_PATH . '/' . $yearMonth;
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $dir . '/' . $filename;

            if (!move_uploaded_file($single['tmp_name'], $dest)) {
                $errors[] = 'No se pudo mover: ' . $single['name'];
                continue;
            }

            [$width, $height] = $this->getImageDimensions($dest, $ext);

            $relPath = $yearMonth . '/' . $filename;
            $uploaded[] = [
                'src'         => url('uploads/' . $relPath),
                'name'        => $single['name'],
                'type'        => 'image',
                'width'       => $width,
                'height'      => $height,
                'size'        => $single['size'],
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];
        }

        Response::json([
            'success' => true,
            'data'    => $uploaded,
            'errors'  => $errors,
        ]);
    }

    public function getAssets(Request $req): void
    {
        Response::json(['success' => true, 'data' => $this->getExistingAssets()]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🕒 VERSIONES
    // ═══════════════════════════════════════════════════════════════

    public function versions(Request $req, array $params): void
    {
        if (!Auth::can('pages.view')) {
            Response::abort(403, 'No tienes permisos para ver versiones');
        }

        $id = (int)$params['id'];
        $page = Page::find($id);

        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $versions = PageVersion::forPage($id);

        Response::render(View::render('admin.pages.versions', [
            'page'     => $page,
            'versions' => $versions,
        ]));
    }

    public function restoreVersion(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para restaurar versiones');
        }

        $id = (int)$params['id'];
        $vid = (int)$params['vid'];

        $page = Page::find($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $restored = PageVersion::restore($vid);

        if ($restored) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_restored', "Página restaurada desde versión {$vid}", 'pages', $id);
            Session::flash('flash.success', 'Versión restaurada correctamente.');
        } else {
            Session::flash('flash.error', 'Error al restaurar la versión.');
        }

        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔧 MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Resuelve una página con verificación de permisos
     */
    private function resolvePage(int $id): ?array
    {
        $page = Page::find($id);
        if (!$page) {
            return null;
        }

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            return null;
        }

        return $page;
    }

    /**
     * Construye el array de datos para actualizar según el modo
     */
    private function buildUpdateData(string $mode, Request $req, array $page): array
    {
        // Campos comunes (metadata)
        $data = [
            'title'        => trim($req->post('title', $page['title'])),
            'slug'         => trim($req->post('slug', $page['slug'])),
            'description'  => trim($req->post('description', $page['description'] ?? '')),
            'h1'           => trim($req->post('h1', $page['h1'] ?? '')),
            'template_id'  => $req->post('template_id') ?: null,
            'parent_id'    => $req->post('parent_id') ?: null,
            'status'       => $req->post('status', $page['status']),
            'is_published' => (int)$req->post('is_published', $page['is_published'] ?? 0),
            'startpage'    => (int)$req->post('startpage', $page['startpage'] ?? 0),
            'publish_at'   => $req->post('publish_at') ?: null,
        ];

        // Contenido según modo
        if ($mode === 'visual') {
            $data['content_html']  = $req->post('content_html', $page['content_html']);
            $data['content_css']   = $req->post('content_css', $page['content_css']);
            $data['content_js']    = $req->post('content_js', $page['content_js']);
            $data['content_php']   = Auth::can('pages.edit') ? $req->post('content_php', $page['content_php']) : $page['content_php'];
            $data['grapesjs_data'] = $req->post('grapesjs_data', $page['grapesjs_data']);
        } else {
            // metadata: no toca el contenido
            $data['content_html'] = $page['content_html'];
            $data['content_css']  = $page['content_css'];
            $data['content_js']   = $page['content_js'];
            $data['content_php']  = $page['content_php'];
            $data['grapesjs_data'] = $page['grapesjs_data'];
        }

        return $data;
    }

    /**
     * Normaliza el SEO a estructura anidada (basic/og/twitter)
     */
    private function normalizeSeo(?array $seo): array
    {
        if (empty($seo)) {
            return ['basic' => [], 'og' => [], 'twitter' => []];
        }

        // Si ya viene anidado
        if (isset($seo['basic'])) {
            return $seo;
        }

        // Si viene plano (de MetaTag::forPage)
        return [
            'basic' => [
                'title'       => $seo['meta_title'] ?? '',
                'description' => $seo['meta_description'] ?? '',
                'keywords'    => $seo['meta_keywords'] ?? '',
                'robots'      => $seo['meta_robots'] ?? 'index,follow',
                'canonical'   => $seo['meta_canonical'] ?? '',
                'author'      => $seo['meta_author'] ?? '',
            ],
            'og' => [
                'title'       => $seo['og_title'] ?? '',
                'description' => $seo['og_description'] ?? '',
                'image'       => $seo['og_image'] ?? '',
                'type'        => $seo['og_type'] ?? 'website',
                'url'         => $seo['og_url'] ?? '',
            ],
            'twitter' => [
                'card'        => $seo['twitter_card'] ?? 'summary_large_image',
                'title'       => $seo['twitter_title'] ?? '',
                'description' => $seo['twitter_description'] ?? '',
                'image'       => $seo['twitter_image'] ?? '',
            ],
        ];
    }

    /**
     * Guarda SEO de la página
     */
    private function saveSeo(int $pageId, Request $req): void
    {
        if (!Auth::can('seo.manage')) {
            return;
        }

        try {
            $seoData = [
                'page_id'             => $pageId,
                'meta_title'          => $req->post('seo_title'),
                'meta_description'    => $req->post('seo_description'),
                'meta_keywords'       => $req->post('seo_keywords'),
                'meta_robots'         => $req->post('seo_robots', 'index, follow'),
                'meta_canonical'      => $req->post('seo_canonical'),
                'meta_author'         => $req->post('seo_author'),
                'og_title'            => $req->post('og_title'),
                'og_description'      => $req->post('og_description'),
                'og_image'            => $req->post('og_image'),
                'og_type'             => $req->post('og_type', 'website'),
                'og_url'              => $req->post('og_url'),
                'twitter_card'        => $req->post('twitter_card', 'summary_large_image'),
                'twitter_title'       => $req->post('twitter_title'),
                'twitter_description' => $req->post('twitter_description'),
                'twitter_image'       => $req->post('twitter_image'),
            ];

            $existing = $this->db->fetch(
                "SELECT id FROM meta_tags WHERE page_id = ?",
                [$pageId]
            );

            if ($existing) {
                $this->db->execute(
                    "UPDATE meta_tags SET 
                        meta_title = ?, meta_description = ?, meta_keywords = ?,
                        meta_robots = ?, meta_canonical = ?, meta_author = ?,
                        og_title = ?, og_description = ?, og_image = ?, og_type = ?, og_url = ?,
                        twitter_card = ?, twitter_title = ?, twitter_description = ?, twitter_image = ?
                    WHERE page_id = ?",
                    array_merge(array_values($seoData), [$pageId])
                );
            } else {
                $this->db->insert(
                    "INSERT INTO meta_tags (
                        page_id, meta_title, meta_description, meta_keywords,
                        meta_robots, meta_canonical, meta_author,
                        og_title, og_description, og_image, og_type, og_url,
                        twitter_card, twitter_title, twitter_description, twitter_image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array_values($seoData)
                );
            }
        } catch (\Throwable $e) {
            error_log('[PagesController] Error al guardar SEO: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene dimensiones de imagen
     */
    private function getImageDimensions(string $path, string $ext): array
    {
        if ($ext === 'svg') {
            $svgContent = @file_get_contents($path);
            if ($svgContent && preg_match('/viewBox=["\']([^"\']+)["\']/', $svgContent, $matches)) {
                $viewBox = explode(' ', $matches[1]);
                if (count($viewBox) === 4) {
                    return [(int)$viewBox[2], (int)$viewBox[3]];
                }
            }
            return [0, 0];
        }

        $imgInfo = @getimagesize($path);
        return $imgInfo ? [$imgInfo[0], $imgInfo[1]] : [0, 0];
    }

    /**
     * Obtiene assets existentes del sistema de archivos
     */
    private function getExistingAssets(): array
    {
        $uploadDir = UPLOADS_PATH;
        $assets = [];

        if (!is_dir($uploadDir)) {
            return $assets;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $allowedExt, true)) continue;

            $relativePath = str_replace($uploadDir . '/', '', $file->getPathname());
            [$width, $height] = $this->getImageDimensions($file->getPathname(), $ext);

            $assets[] = [
                'src'         => url('uploads/' . $relativePath),
                'name'        => $file->getFilename(),
                'type'        => 'image',
                'width'       => $width,
                'height'      => $height,
                'size'        => $file->getSize(),
                'uploaded_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        usort($assets, fn($a, $b) => strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']));

        return $assets;
    }
}