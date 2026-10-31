<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, Template, MetaTag, PageVersion};
use App\Services\{PageService, AuditService, CacheService, SitemapService};

class PagesController
{
    private Database $db;
    private PageService $pageService;
    
    public function __construct(Database $db, PageService $pageService)
    {
        $this->db = $db;
        $this->pageService = $pageService;
    }

    /**
     * Lista de páginas
     */
    public function index(Request $req): void
    {
        if (!Auth::can('pages.view')) {
            Response::abort(403, 'No tienes permisos para ver páginas');
        }

        $status = $req->get('status');
        $pages = Page::all($status);
        
        Response::render(View::render('pages.index', [
            'pages' => $pages,
            'status' => $status
        ]));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $req): void
    {
        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        $templates = Template::allActive();
        $pages = Page::all();
        
        Response::render(View::render('pages.create', [
            'templates' => $templates,
            'pages' => $pages
        ]));
    }

    /**
     * Guardar nueva página
     */
    public function store(Request $req): void
    {
        CSRF::guard();
        
        if (!Auth::can('pages.create')) {
            Response::abort(403, 'No tienes permisos para crear páginas');
        }

        $data = [
            'user_id'        => Auth::id(),
            'template_id'    => $req->post('template_id') ?: null,
            'parent_id'      => $req->post('parent_id') ?: null,
            'title'          => trim($req->post('title', '')),
            'slug'           => trim($req->post('slug', '')),
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

        // Validar título
        if (empty($data['title'])) {
            Session::flash('flash.error', 'El título es obligatorio.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        // Generar slug si está vacío
        if (empty($data['slug'])) {
            $data['slug'] = Page::slugify($data['title']);
        }

        // Verificar slug único
        $data['slug'] = Page::uniqueSlug($data['slug']);

        // Crear página
        $id = PageService::create($data);
        
        if (!$id) {
            Session::flash('flash.error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
            return;
        }

        // Guardar SEO
        $this->saveSeo($id, $req);
        
        // Si es página de inicio, desactivar otras
        if ($data['startpage'] == 1) {
            Page::update($id, ['startpage' => 1]);
            $this->db->execute(
                "UPDATE pages SET startpage = 0 WHERE id != ? AND user_id = ?",
                [$id, Auth::id()]
            );
        }

        // Registrar auditoría
        AuditService::log(Auth::id(), 'page_created', "Página creada: {$data['title']}", 'pages');

        Session::flash('flash.success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/{$id}/builder"));
    }

    /**
     * Formulario de edición (metadata y SEO)
     */
    public function edit(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $id = (int)$params['id'];
        $page = Page::find($id);
        
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        // Verificar que el usuario sea propietario o admin
        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            Response::abort(403, 'No tienes permisos para editar esta página');
        }

        // ✅ Obtener datos necesarios para la vista
        $templates = Template::allActive();
        $pages = Page::all();
        $seo = MetaTag::forPage($id);
        $versions = PageVersion::forPage($id);

        Response::render(View::render('pages.edit', [
            'page' => $page,
            'templates' => $templates,
            'pages' => $pages,
            'seo' => $seo,
            'versions' => $versions
        ]));
    }

    /**
     * Actualizar página (metadata y SEO)
     */
    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $id = (int)$params['id'];
        $page = Page::find($id);
        
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            Response::abort(403, 'No tienes permisos para editar esta página');
        }

        $mode = $req->post('mode', 'metadata');
        $data = [];

        // ✅ Si es modo metadata (desde edit.php)
        if ($mode === 'metadata') {
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

            // Verificar slug único
            $data['slug'] = Page::uniqueSlug($data['slug'], $id);

            // Guardar cambios
            $updated = Page::update($id, $data);

            if ($updated) {
                // Si es página de inicio, desactivar otras
                if ($data['startpage'] == 1) {
                    $this->db->execute(
                        "UPDATE pages SET startpage = 0 WHERE id != ? AND user_id = ?",
                        [$id, Auth::id()]
                    );
                }

                // Guardar SEO
                $this->saveSeo($id, $req);

                // Crear versión
                PageVersion::create([
                    'page_id' => $id,
                    'title' => $data['title'],
                    'content_html' => $page['content_html'],
                    'content_css' => $page['content_css'],
                    'content_js' => $page['content_js'],
                    'content_php' => $page['content_php'],
                    'note' => $req->post('version_note', 'Actualización de metadatos')
                ]);

                // Invalidar caché
                CacheService::invalidatePage($id);
                SitemapService::generate();

                AuditService::log(Auth::id(), 'page_updated', "Página actualizada: {$data['title']}", 'pages');
                
                Session::flash('flash.success', 'Página actualizada correctamente.');
            } else {
                Session::flash('flash.error', 'Error al actualizar la página.');
            }
        }

        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    /**
     * Eliminar página
     */
    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!Auth::can('pages.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar páginas');
        }

        $id = (int)$params['id'];
        $page = Page::find($id);
        
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.delete_all')) {
            Response::abort(403, 'No tienes permisos para eliminar esta página');
        }

        $title = $page['title'];
        $deleted = Page::delete($id);

        if ($deleted) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_deleted', "Página eliminada: {$title}", 'pages');
            Session::flash('flash.success', 'Página eliminada correctamente.');
        } else {
            Session::flash('flash.error', 'Error al eliminar la página.');
        }

        Response::redirect(url('admin/pages'));
    }

    /**
     * Builder visual (GrapesJS)
     */
    public function builder(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $id = (int)$params['id'];
        $page = Page::find($id);
        
        if (!$page) {
            Response::abort(404, 'Página no encontrada');
        }

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            Response::abort(403, 'No tienes permisos para editar esta página');
        }

        $templates = Template::allActive();
        $existingAssets = $this->getExistingAssets();
        $seo = MetaTag::forPage($id);

        Response::render(View::render('pages.builder', [
            'page' => $page,
            'templates' => $templates,
            'existingAssets' => $existingAssets,
            'seo' => $seo
        ]));
    }

    /**
     * Guardar desde el builder (AJAX)
     */
    public function builderSave(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }

        $id = (int)$params['id'];
        $page = Page::find($id);
        
        if (!$page) {
            Response::json(['success' => false, 'message' => 'Página no encontrada']);
            return;
        }

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            Response::json(['success' => false, 'message' => 'Sin permisos']);
            return;
        }

        $data = [
            'title'          => trim($req->post('title', $page['title'])),
            'slug'           => trim($req->post('slug', $page['slug'])),
            'description'    => trim($req->post('description', $page['description'] ?? '')),
            'template_id'    => $req->post('template_id') ?: null,
            'status'         => $req->post('status', $page['status']),
            'is_published'   => (int)$req->post('is_published', $page['is_published'] ?? 0),
            'startpage'      => (int)$req->post('startpage', $page['startpage'] ?? 0),
            'content_html'   => $req->post('content_html', $page['content_html']),
            'content_css'    => $req->post('content_css', $page['content_css']),
            'content_js'     => $req->post('content_js', $page['content_js']),
            'content_php'    => Auth::can('pages.edit') ? $req->post('content_php', $page['content_php']) : $page['content_php'],
            'grapesjs_data'  => $req->post('grapesjs_data'),
        ];

        // Verificar slug único
        $data['slug'] = Page::uniqueSlug($data['slug'], $id);

        $updated = Page::update($id, $data);

        if ($updated) {
            // Si es página de inicio, desactivar otras
            if ($data['startpage'] == 1) {
                $this->db->execute(
                    "UPDATE pages SET startpage = 0 WHERE id != ? AND user_id = ?",
                    [$id, Auth::id()]
                );
            }

            // Guardar SEO
            $this->saveSeo($id, $req);

            // Crear versión
            PageVersion::create([
                'page_id' => $id,
                'title' => $data['title'],
                'content_html' => $data['content_html'],
                'content_css' => $data['content_css'],
                'content_js' => $data['content_js'],
                'content_php' => $data['content_php'],
                'note' => $req->post('version_note', 'Edición desde builder visual')
            ]);

            // Invalidar caché
            CacheService::invalidatePage($id);
            SitemapService::generate();

            AuditService::log(Auth::id(), 'page_builder_saved', "Página guardada desde builder: {$data['title']}", 'pages');
            
            Response::json(['success' => true, 'message' => 'Página guardada correctamente']);
        } else {
            Response::json(['success' => false, 'message' => 'Error al guardar la página']);
        }
    }

    /**
     * Autosave (AJAX)
     */
    public function autosave(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }

        $id = (int)$params['id'];
        $userId = Auth::id();

        // Eliminar autosave anterior
        $this->db->execute(
            "DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?",
            [$id, $userId]
        );

        // Guardar nuevo autosave
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
                $req->post('grapesjs_data', '')
            ]
        );

        Response::json(['success' => (bool)$inserted, 'message' => 'Autoguardado correctamente']);
    }

    /**
     * Obtener autosave (AJAX)
     */
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

        if ($autosave) {
            Response::json([
                'success' => true,
                'data' => [
                    'content_html' => $autosave['content_html'],
                    'content_css' => $autosave['content_css'],
                    'content_js' => $autosave['content_js'],
                    'content_php' => $autosave['content_php'],
                    'grapesjs_data' => $autosave['grapesjs_data'],
                    'created_at' => $autosave['created_at']
                ]
            ]);
        } else {
            Response::json(['success' => true, 'data' => null]);
        }
    }

    /**
     * Limpiar autosave (AJAX)
     */
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

    /**
     * Subir assets (AJAX)
     */
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
                'name' => $names[$i],
                'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
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
            if (!is_dir($dir)) @mkdir($dir, 0775, true);

            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $dir . '/' . $filename;

            if (!move_uploaded_file($single['tmp_name'], $dest)) {
                $errors[] = 'No se pudo mover: ' . $single['name'];
                continue;
            }

            $width = 0;
            $height = 0;
            if ($ext === 'svg') {
                $svgContent = @file_get_contents($dest);
                if ($svgContent && preg_match('/viewBox=["\']([^"\']+)["\']/', $svgContent, $matches)) {
                    $viewBox = explode(' ', $matches[1]);
                    if (count($viewBox) === 4) {
                        $width = (int)$viewBox[2];
                        $height = (int)$viewBox[3];
                    }
                }
            } else {
                $imgInfo = @getimagesize($dest);
                if ($imgInfo) {
                    $width = $imgInfo[0];
                    $height = $imgInfo[1];
                }
            }

            $relPath = $yearMonth . '/' . $filename;
            $publicUrl = url('uploads/' . $relPath);

            $uploaded[] = [
                'src' => $publicUrl,
                'name' => $single['name'],
                'type' => 'image',
                'width' => $width,
                'height' => $height,
                'size' => $single['size'],
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];
        }

        Response::json([
            'success' => true,
            'data' => $uploaded,
            'errors' => $errors,
        ]);
    }

    /**
     * Obtener assets existentes (AJAX)
     */
    public function getAssets(Request $req): void
    {
        Response::json(['success' => true, 'data' => $this->getExistingAssets()]);
    }

    /**
     * Versiones de una página
     */
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

        Response::render(View::render('pages.versions', [
            'page' => $page,
            'versions' => $versions
        ]));
    }

    /**
     * Restaurar versión
     */
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

        if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit_all')) {
            Response::abort(403, 'No tienes permisos para restaurar esta página');
        }

        $restored = PageVersion::restore($vid);

        if ($restored) {
            CacheService::invalidatePage($id);
            SitemapService::generate();
            AuditService::log(Auth::id(), 'page_restored', "Página restaurada desde versión {$vid}", 'pages');
            Session::flash('flash.success', 'Versión restaurada correctamente.');
        } else {
            Session::flash('flash.error', 'Error al restaurar la versión.');
        }

        Response::redirect(url("admin/pages/{$id}/edit"));
    }

    /**
     * Guardar SEO
     */
    private function saveSeo(int $pageId, Request $req): void
    {
        if (!Auth::can('seo.manage')) return;

        try {
            $seoData = [
                'page_id' => $pageId,
                'meta_title' => $req->post('seo_title'),
                'meta_description' => $req->post('seo_description'),
                'meta_keywords' => $req->post('seo_keywords'),
                'meta_robots' => $req->post('seo_robots', 'index, follow'),
                'meta_canonical' => $req->post('seo_canonical'),
                'meta_author' => $req->post('seo_author'),
                'og_title' => $req->post('og_title'),
                'og_description' => $req->post('og_description'),
                'og_image' => $req->post('og_image'),
                'og_type' => $req->post('og_type', 'website'),
                'og_url' => $req->post('og_url'),
                'twitter_card' => $req->post('twitter_card', 'summary_large_image'),
                'twitter_title' => $req->post('twitter_title'),
                'twitter_description' => $req->post('twitter_description'),
                'twitter_image' => $req->post('twitter_image'),
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
     * Obtener assets existentes
     */
    private function getExistingAssets(): array
    {
        $uploadDir = UPLOADS_PATH;
        $assets = [];
        
        if (!is_dir($uploadDir)) return $assets;

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
            $publicUrl = url('uploads/' . $relativePath);

            $width = 0;
            $height = 0;
            if ($ext === 'svg') {
                $svgContent = @file_get_contents($file->getPathname());
                if ($svgContent && preg_match('/viewBox=["\']([^"\']+)["\']/', $svgContent, $matches)) {
                    $viewBox = explode(' ', $matches[1]);
                    if (count($viewBox) === 4) {
                        $width = (int)$viewBox[2];
                        $height = (int)$viewBox[3];
                    }
                }
            } else {
                $imgInfo = @getimagesize($file->getPathname());
                if ($imgInfo) {
                    $width = $imgInfo[0];
                    $height = $imgInfo[1];
                }
            }

            $assets[] = [
                'src' => $publicUrl,
                'name' => $file->getFilename(),
                'type' => 'image',
                'width' => $width,
                'height' => $height,
                'size' => $file->getSize(),
                'uploaded_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        usort($assets, fn($a, $b) => strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']));
        return $assets;
    }
}