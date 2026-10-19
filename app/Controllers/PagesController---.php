<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, PageModel, PageVersion, MetaTag, Template};
use App\Models\PageStatisticsModel;
use App\Services\{AuditService, CacheService, SitemapService, PageRenderer, PagePublicService};

/**
 * Controlador unificado de páginas (PHP 8.2+)
 *
 * Integra:
 *  - Frontend público (home, show, sitemap, robots)
 *  - Admin CRUD + duplicate + toggleStatus
 *  - Builder visual (GrapesJS) con guardado AJAX
 *  - Autosave (save / get / clear)
 *  - Gestión de assets (upload / list)
 *  - Sistema de versiones (list / restore)
 *  - SEO completo (basic + OpenGraph + Twitter)
 *
 * @package App\Controllers
 */
final class PagesController
{
    public function __construct(
        private readonly Database           $db,
        private readonly PageModel          $pageModel,
        private readonly PageStatisticsModel $statsModel,
        private readonly PageRenderer       $renderer,
        private readonly PagePublicService  $publicService,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // 🌐  FRONTEND PÚBLICO
    // ═══════════════════════════════════════════════════════════════

    public function home(Request $req): void
    {
        $this->publicService->renderHome();
    }

    public function show(Request $req, array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        if ($slug === '' || in_array($slug, ['home', 'index'], true)) {
            $this->home($req);
            return;
        }
        $this->publicService->renderBySlug($slug);
    }

    public function sitemap(Request $req): void
    {
        \header('Content-Type: application/xml; charset=utf-8');
        echo $this->publicService->generateSitemap();
        exit;
    }

    public function robots(Request $req): void
    {
        \header('Content-Type: text/plain; charset=utf-8');
        echo $this->publicService->generateRobots();
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // 📋  ADMIN: LISTADO
    // ═══════════════════════════════════════════════════════════════

    public function index(Request $req): void
    {
        $this->requirePermission('pages.view');

        $status = (string) $req->get('status', '');
        $pages  = Page::all($status !== '' ? $status : null);

        Response::render(View::render('admin.pages.index', [
            'pages'  => $pages,
            'status' => $status,
        ]));
    }

    // ═══════════════════════════════════════════════════════════════
    // ➕  ADMIN: CREAR
    // ═══════════════════════════════════════════════════════════════

    public function create(Request $req): void
    {
        $this->requirePermission('pages.create');

        Response::render(View::render('admin.pages.create', [
            'templates' => Template::allActive(),
            'pages'     => Page::all(),
        ]));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        $this->requirePermission('pages.create');

        $title = \trim((string) $req->post('title', ''));
        if ($title === '') {
            Session::flash('flash.error', 'El título es obligatorio.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        $slug = \trim((string) $req->post('slug', ''));
        $slug = Page::uniqueSlug($slug !== '' ? $slug : Page::slugify($title));

        $data = $this->buildPageData($req, [
            'title'       => $title,
            'slug'        => $slug,
            'user_id'     => Auth::id(),
            'content_html'=> (string) $req->post('content_html', ''),
            'content_css' => (string) $req->post('content_css', ''),
            'content_js'  => (string) $req->post('content_js', ''),
            'content_php' => Auth::can('pages.edit') ? (string) $req->post('content_php', '') : '',
        ]);

        $id = Page::create($data);
        if (!$id) {
            Session::flash('flash.error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        $this->handleStartpage($id, $data['startpage'], Auth::id());
        $this->saveSeo($id, $req);

        // Primera versión al crear
        PageVersion::create([
            'page_id'      => $id,
            'title'        => $data['title'],
            'content_html' => $data['content_html'],
            'content_css'  => $data['content_css'],
            'content_js'   => $data['content_js'],
            'content_php'  => $data['content_php'],
            'note'         => 'Página creada',
        ]);

        CacheService::invalidatePage($id);
        SitemapService::generate();
        AuditService::log(Auth::id(), 'page_created', "Página creada: {$data['title']}", 'pages', $id);

        Session::flash('flash.success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/{$id}/builder"));
    }

    // ═══════════════════════════════════════════════════════════════
    // ✏️  ADMIN: EDITAR (metadata + SEO)
    // ═══════════════════════════════════════════════════════════════

    public function edit(Request $req, array $params): void
    {
        $this->requirePermission('pages.edit');

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        Response::render(View::render('admin.pages.edit', [
            'page'      => $page,
            'templates' => Template::allActive(),
            'pages'     => Page::all(),
            'seo'       => $this->normalizeSeo(MetaTag::forPage($id)),
            'versions'  => PageVersion::forPage($id),
        ]));
    }

    // ═══════════════════════════════════════════════════════════════
    // 💾  ADMIN: ACTUALIZAR (metadata + visual unificados)
    //
    //  ✅ CORRECCIÓN: ahora procesa ambos modos (metadata y visual)
    //     y SIEMPRE crea una versión al guardar.
    // ═══════════════════════════════════════════════════════════════

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.edit', ajax: $req->isAjax());

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            $this->respondError('Página no encontrada', $req->isAjax(), 404);
            return;
        }

        $mode = (string) $req->post('mode', 'metadata');
        $data = $this->buildUpdateData($mode, $req, $page);

        // Slug único (respetando el ID actual)
        $data['slug'] = Page::uniqueSlug($data['slug'], $id);

        $updated = Page::update($id, $data);
        if (!$updated) {
            $this->respondError('Error al actualizar la página.', $req->isAjax());
            return;
        }

        $this->handleStartpage($id, (int) ($data['startpage'] ?? 0), (int) $page['user_id']);
        $this->saveSeo($id, $req);

        // ✅ Versión SIEMPRE se crea (metadata o visual)
        PageVersion::create([
            'page_id'      => $id,
            'title'        => $data['title'],
            'content_html' => $data['content_html'],
            'content_css'  => $data['content_css'],
            'content_js'   => $data['content_js'],
            'content_php'  => $data['content_php'],
            'note'         => (string) $req->post(
                'version_note',
                $mode === 'visual' ? 'Edición desde builder visual' : 'Actualización de metadatos'
            ),
        ]);

        CacheService::invalidatePage($id);
        SitemapService::generate();
        AuditService::log(Auth::id(), 'page_updated', "Página actualizada: {$data['title']}", 'pages', $id);

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
    // 🗑️  ADMIN: ELIMINAR
    // ═══════════════════════════════════════════════════════════════

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.delete');

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $title = (string) $page['title'];
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
    // 📋  ADMIN: DUPLICAR
    // ═══════════════════════════════════════════════════════════════

    public function duplicate(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.create');

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        $newSlug = Page::uniqueSlug($page['slug'] . '-copia');

        $newId = Page::create([
            'user_id'        => Auth::id(),
            'template_id'    => $page['template_id'] ?? null,
            'parent_id'      => $page['parent_id'] ?? null,
            'title'          => $page['title'] . ' (copia)',
            'slug'           => $newSlug,
            'description'    => (string) ($page['description'] ?? ''),
            'h1'             => $page['h1'] ?? null,
            'content_html'   => (string) ($page['content_html'] ?? ''),
            'content_css'    => (string) ($page['content_css'] ?? ''),
            'content_js'     => (string) ($page['content_js'] ?? ''),
            'content_php'    => (string) ($page['content_php'] ?? ''),
            'grapesjs_data'  => $page['grapesjs_data'] ?? null,
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
    // 🔄  ADMIN: CAMBIAR ESTADO (AJAX o POST)
    // ═══════════════════════════════════════════════════════════════

    public function toggleStatus(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.edit', ajax: $req->isAjax());

        $id        = (int) $params['id'];
        $newStatus = (string) $req->post('status', 'draft');
        $allowed   = ['draft', 'published', 'archived'];

        if (!\in_array($newStatus, $allowed, true)) {
            $this->respondError('Estado inválido.', $req->isAjax());
            return;
        }

        $isPub = $newStatus === 'published' ? 1 : 0;
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
            $this->respondError('Error al cambiar el estado.', $req->isAjax());
            return;
        }

        Response::redirect(url('admin/pages'));
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎨  BUILDER VISUAL (GrapesJS)
    // ═══════════════════════════════════════════════════════════════

    public function builder(Request $req, array $params): void
    {
        $this->requirePermission('pages.edit');

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        Response::render(View::render('admin.pages.builder', [
            'page'           => $page,
            'templates'      => Template::allActive(),
            'existingAssets' => $this->getExistingAssets(),
            'seo'            => $this->normalizeSeo(MetaTag::forPage($id)),
        ]));
    }

    /**
     * ✅ Guardado exclusivo desde el builder (AJAX)
     *    Antes no existía: el builder hacía POST a update() sin modo correcto.
     */
    public function builderSave(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.edit', ajax: true);

        $id   = (int) $params['id'];
        $page = $this->resolvePage($id);
        if (!$page) {
            Response::json(['success' => false, 'message' => 'Página no encontrada'], 404);
            return;
        }

        $data = $this->buildUpdateData('visual', $req, $page);
        $data['slug'] = Page::uniqueSlug($data['slug'], $id);

        $updated = Page::update($id, $data);
        if (!$updated) {
            Response::json(['success' => false, 'message' => 'Error al guardar']);
            return;
        }

        $this->handleStartpage($id, (int) ($data['startpage'] ?? 0), (int) $page['user_id']);
        $this->saveSeo($id, $req);

        PageVersion::create([
            'page_id'      => $id,
            'title'        => $data['title'],
            'content_html' => $data['content_html'],
            'content_css'  => $data['content_css'],
            'content_js'   => $data['content_js'],
            'content_php'  => $data['content_php'],
            'note'         => (string) $req->post('version_note', 'Edición desde builder visual'),
        ]);

        CacheService::invalidatePage($id);
        SitemapService::generate();
        AuditService::log(Auth::id(), 'page_builder_saved', "Builder: {$data['title']}", 'pages', $id);

        Response::json([
            'success' => true,
            'message' => 'Página guardada correctamente',
            'id'      => $id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 💾  AUTOSAVE (AJAX)
    // ═══════════════════════════════════════════════════════════════

    public function autosave(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.edit', ajax: true);

        $id     = (int) $params['id'];
        $userId = Auth::id();

        $this->db->execute(
            'DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?',
            [$id, $userId]
        );

        $inserted = $this->db->insert(
            'INSERT INTO page_autosave
                (page_id, user_id, content_html, content_css, content_js, content_php, grapesjs_data)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                $userId,
                (string) $req->post('content_html', ''),
                (string) $req->post('content_css', ''),
                (string) $req->post('content_js', ''),
                (string) $req->post('content_php', ''),
                (string) $req->post('grapesjs_data', ''),
            ]
        );

        Response::json([
            'success'   => (bool) $inserted,
            'message'   => 'Autoguardado correctamente',
            'saved_at'  => \date('Y-m-d H:i:s'),
        ]);
    }

    public function getAutosave(Request $req, array $params): void
    {
        $this->requirePermission('pages.edit', ajax: true);

        $id     = (int) $params['id'];
        $userId = Auth::id();

        $autosave = $this->db->fetch(
            'SELECT * FROM page_autosave
             WHERE page_id = ? AND user_id = ?
             ORDER BY created_at DESC LIMIT 1',
            [$id, $userId]
        );

        if (!$autosave) {
            Response::json(['success' => true, 'data' => null]);
            return;
        }

        Response::json([
            'success' => true,
            'data'    => [
                'content_html'  => (string) $autosave['content_html'],
                'content_css'   => (string) $autosave['content_css'],
                'content_js'    => (string) $autosave['content_js'],
                'content_php'   => (string) $autosave['content_php'],
                'grapesjs_data' => (string) $autosave['grapesjs_data'],
                'created_at'    => (string) $autosave['created_at'],
                'age_seconds'   => \time() - \strtotime((string) $autosave['created_at']),
            ],
        ]);
    }

    public function clearAutosave(Request $req, array $params): void
    {
        $this->requirePermission('pages.edit', ajax: true);

        $id     = (int) $params['id'];
        $userId = Auth::id();

        $deleted = $this->db->execute(
            'DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?',
            [$id, $userId]
        );

        Response::json(['success' => (bool) $deleted]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🖼️  ASSETS (AJAX)
    // ═══════════════════════════════════════════════════════════════

    public function uploadAsset(Request $req): void
    {
        $this->requirePermission('pages.edit', ajax: true);

        $files = $_FILES['files'] ?? null;
        if (!$files) {
            Response::json(['success' => false, 'errors' => ['No se recibieron archivos']]);
            return;
        }

        $uploaded = [];
        $errors   = [];
        $names    = (array) $files['name'];

        for ($i = 0, $c = \count($names); $i < $c; $i++) {
            $single = [
                'name'     => $names[$i],
                'type'     => \is_array($files['type'])     ? $files['type'][$i]     : $files['type'],
                'tmp_name' => \is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error'    => \is_array($files['error'])    ? $files['error'][$i]    : $files['error'],
                'size'     => \is_array($files['size'])     ? $files['size'][$i]     : $files['size'],
            ];

            if ($single['error'] !== \UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $single['name'];
                continue;
            }

            $ext = \strtolower(\pathinfo($single['name'], \PATHINFO_EXTENSION));
            if (!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                $errors[] = 'Tipo no permitido: ' . $single['name'];
                continue;
            }

            if ($single['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Archivo demasiado grande (máx 5MB): ' . $single['name'];
                continue;
            }

            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $mime  = (string) $finfo->file($single['tmp_name']);
            if (!\str_starts_with($mime, 'image/')) {
                $errors[] = 'MIME inválido: ' . $single['name'];
                continue;
            }

            $yearMonth = \date('Y/m');
            $dir       = \UPLOADS_PATH . '/' . $yearMonth;
            if (!\is_dir($dir)) {
                @\mkdir($dir, 0775, true);
            }

            $filename = \bin2hex(\random_bytes(16)) . '.' . $ext;
            $dest     = $dir . '/' . $filename;

            if (!\move_uploaded_file($single['tmp_name'], $dest)) {
                $errors[] = 'No se pudo mover: ' . $single['name'];
                continue;
            }

            [$width, $height] = $this->getImageDimensions($dest, $ext);

            $uploaded[] = [
                'src'         => url('uploads/' . $yearMonth . '/' . $filename),
                'name'        => $single['name'],
                'type'        => 'image',
                'width'       => $width,
                'height'      => $height,
                'size'        => (int) $single['size'],
                'uploaded_at' => \date('Y-m-d H:i:s'),
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
    // 🕒  VERSIONES
    // ═══════════════════════════════════════════════════════════════

    public function versions(Request $req, array $params): void
    {
        $this->requirePermission('pages.view');

        $id   = (int) $params['id'];
        $page = Page::find($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        Response::render(View::render('admin.pages.versions', [
            'page'     => $page,
            'versions' => PageVersion::forPage($id),
        ]));
    }

    public function restoreVersion(Request $req, array $params): void
    {
        CSRF::guard();
        $this->requirePermission('pages.edit');

        $id  = (int) $params['id'];
        $vid = (int) $params['vid'];

        $page = Page::find($id);
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        if (PageVersion::restore($vid)) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_restored', "Restaurada desde versión {$vid}", 'pages', $id);
            Session::flash('flash.success', 'Versión restaurada correctamente.');
        } else {
            Session::flash('flash.error', 'Error al restaurar la versión.');
        }

        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔧  MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Verifica permiso y aborta (HTML o JSON según corresponda).
     */
    private function requirePermission(string $permission, bool $ajax = false): void
    {
        if (Auth::can($permission)) {
            return;
        }
        if ($ajax) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            exit;
        }
        Response::abort(403, 'No tienes permisos para realizar esta acción');
    }

    /**
     * Resuelve la página verificando propiedad o permiso global.
     */
    private function resolvePage(int $id): ?array
    {
        $page = Page::find($id);
        if (!$page) {
            return null;
        }
        if ((int) $page['user_id'] === Auth::id() || Auth::can('pages.edit_all')) {
            return $page;
        }
        return null;
    }

    /**
     * Construye el array de datos para CREAR una página.
     */
    private function buildPageData(Request $req, array $base): array
    {
        return [
            'user_id'        => (int) $base['user_id'],
            'template_id'    => $req->post('template_id') ?: null,
            'parent_id'      => $req->post('parent_id') ?: null,
            'title'          => (string) $base['title'],
            'slug'           => (string) $base['slug'],
            'description'    => \trim((string) $req->post('description', '')),
            'h1'             => \trim((string) $req->post('h1', '')) ?: null,
            'content_html'   => (string) $base['content_html'],
            'content_css'    => (string) $base['content_css'],
            'content_js'     => (string) $base['content_js'],
            'content_php'    => (string) $base['content_php'],
            'grapesjs_data'  => $req->post('grapesjs_data'),
            'startpage'      => (int) $req->post('startpage', 0),
            'is_published'   => (int) $req->post('is_published', 0),
            'status'         => (string) $req->post('status', 'draft'),
            'publish_at'     => $req->post('publish_at') ?: null,
        ];
    }

    /**
     * ✅ Construye datos para ACTUALIZAR según el modo.
     *    - metadata: solo metadatos, el contenido NO se toca.
     *    - visual:   metadatos + contenido del builder.
     */
    private function buildUpdateData(string $mode, Request $req, array $page): array
    {
        $data = [
            'title'        => \trim((string) $req->post('title', $page['title'])),
            'slug'         => \trim((string) $req->post('slug', $page['slug'])),
            'description'  => \trim((string) $req->post('description', $page['description'] ?? '')),
            'h1'           => \trim((string) $req->post('h1', $page['h1'] ?? '')) ?: null,
            'template_id'  => $req->post('template_id') ?: null,
            'parent_id'    => $req->post('parent_id') ?: null,
            'status'       => (string) $req->post('status', $page['status']),
            'is_published' => (int) $req->post('is_published', $page['is_published'] ?? 0),
            'startpage'    => (int) $req->post('startpage', $page['startpage'] ?? 0),
            'publish_at'   => $req->post('publish_at') ?: null,
        ];

        if ($mode === 'visual') {
            $data['content_html']  = (string) $req->post('content_html', $page['content_html']);
            $data['content_css']   = (string) $req->post('content_css', $page['content_css']);
            $data['content_js']    = (string) $req->post('content_js', $page['content_js']);
            $data['content_php']   = Auth::can('pages.edit')
                ? (string) $req->post('content_php', $page['content_php'])
                : (string) ($page['content_php'] ?? '');
            $data['grapesjs_data'] = $req->post('grapesjs_data', $page['grapesjs_data']);
        } else {
            // metadata: preserva el contenido actual
            $data['content_html']  = (string) ($page['content_html'] ?? '');
            $data['content_css']   = (string) ($page['content_css'] ?? '');
            $data['content_js']    = (string) ($page['content_js'] ?? '');
            $data['content_php']   = (string) ($page['content_php'] ?? '');
            $data['grapesjs_data'] = $page['grapesjs_data'] ?? null;
        }

        return $data;
    }

    /**
     * Maneja la lógica de "página de inicio": si se marca, desmarca las demás del usuario.
     */
    private function handleStartpage(int $id, int $startpage, int $userId): void
    {
        if ($startpage !== 1) {
            return;
        }
        $this->db->execute(
            'UPDATE pages SET startpage = 0 WHERE user_id = ? AND id <> ?',
            [$userId, $id]
        );
    }

    /**
     * Normaliza el SEO a estructura anidada (basic / og / twitter).
     */
    private function normalizeSeo(?array $seo): array
    {
        if (empty($seo)) {
            return ['basic' => [], 'og' => [], 'twitter' => []];
        }
        if (isset($seo['basic'])) {
            return $seo;
        }

        return [
            'basic' => [
                'title'       => (string) ($seo['meta_title'] ?? ''),
                'description' => (string) ($seo['meta_description'] ?? ''),
                'keywords'    => (string) ($seo['meta_keywords'] ?? ''),
                'robots'      => (string) ($seo['meta_robots'] ?? 'index,follow'),
                'canonical'   => (string) ($seo['meta_canonical'] ?? ''),
                'author'      => (string) ($seo['meta_author'] ?? ''),
            ],
            'og' => [
                'title'       => (string) ($seo['og_title'] ?? ''),
                'description' => (string) ($seo['og_description'] ?? ''),
                'image'       => (string) ($seo['og_image'] ?? ''),
                'type'        => (string) ($seo['og_type'] ?? 'website'),
                'url'         => (string) ($seo['og_url'] ?? ''),
            ],
            'twitter' => [
                'card'        => (string) ($seo['twitter_card'] ?? 'summary_large_image'),
                'title'       => (string) ($seo['twitter_title'] ?? ''),
                'description' => (string) ($seo['twitter_description'] ?? ''),
                'image'       => (string) ($seo['twitter_image'] ?? ''),
            ],
        ];
    }

    /**
     * Guarda SEO (basic + OG + Twitter) en meta_tags.
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
                'SELECT id FROM meta_tags WHERE page_id = ?',
                [$pageId]
            );

            if ($existing) {
                $this->db->execute(
                    'UPDATE meta_tags SET
                        meta_title = ?, meta_description = ?, meta_keywords = ?,
                        meta_robots = ?, meta_canonical = ?, meta_author = ?,
                        og_title = ?, og_description = ?, og_image = ?, og_type = ?, og_url = ?,
                        twitter_card = ?, twitter_title = ?, twitter_description = ?, twitter_image = ?
                     WHERE page_id = ?',
                    \array_merge(\array_values($seoData), [$pageId])
                );
            } else {
                $this->db->insert(
                    'INSERT INTO meta_tags (
                        page_id, meta_title, meta_description, meta_keywords,
                        meta_robots, meta_canonical, meta_author,
                        og_title, og_description, og_image, og_type, og_url,
                        twitter_card, twitter_title, twitter_description, twitter_image
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    \array_values($seoData)
                );
            }
        } catch (\Throwable $e) {
            \error_log('[PagesController] Error al guardar SEO: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene dimensiones de imagen (soporta SVG vía viewBox).
     */
    private function getImageDimensions(string $path, string $ext): array
    {
        if ($ext === 'svg') {
            $svg = @\file_get_contents($path);
            if ($svg && \preg_match('/viewBox=["\']([^"\']+)["\']/', $svg, $m)) {
                $parts = \explode(' ', $m[1]);
                if (\count($parts) === 4) {
                    return [(int) $parts[2], (int) $parts[3]];
                }
            }
            return [0, 0];
        }

        $info = @\getimagesize($path);
        return $info ? [$info[0], $info[1]] : [0, 0];
    }

    /**
     * Lista assets existentes en UPLOADS_PATH.
     */
    private function getExistingAssets(): array
    {
        $uploadDir = \UPLOADS_PATH;
        if (!\is_dir($uploadDir)) {
            return [];
        }

        $assets = [];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $ext = \strtolower($file->getExtension());
            if (!\in_array($ext, $allowed, true)) {
                continue;
            }

            $relative = \str_replace($uploadDir . '/', '', $file->getPathname());
            [$width, $height] = $this->getImageDimensions($file->getPathname(), $ext);

            $assets[] = [
                'src'         => url('uploads/' . $relative),
                'name'        => $file->getFilename(),
                'type'        => 'image',
                'width'       => $width,
                'height'      => $height,
                'size'        => (int) $file->getSize(),
                'uploaded_at' => \date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        \usort($assets, static fn($a, $b) => \strtotime($b['uploaded_at']) - \strtotime($a['uploaded_at']));
        return $assets;
    }

    /**
     * Responde con error según el contexto (AJAX → JSON, normal → flash + redirect).
     */
    private function respondError(string $message, bool $ajax, int $httpCode = 400): void
    {
        if ($ajax) {
            Response::json(['success' => false, 'message' => $message], $httpCode);
            return;
        }
        Session::flash('flash.error', $message);
    }
}