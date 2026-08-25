<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, Template, MetaTag};
use App\Services\{PageService, AuditService, CacheService, SitemapService};

class PagesController
{
    public function index(Request $req): void
    {
        $status = $req->get('status');
        $pages = Page::all($status);
        Response::render(View::render('pages.index', compact('pages', 'status')));
    }

    public function create(Request $req): void
    {
        $templates = Template::allActive();
        $pages = Page::all();
        Response::render(View::render('pages.create', compact('templates', 'pages')));
    }

    public function store(Request $req): void
    {
        CSRF::guard();

        $data = [
            'user_id'        => Auth::id(),
            'template_id'    => $req->post('template_id') ?: null,
            'parent_id'      => $req->post('parent_id') ?: null,
            'title'          => $req->post('title'),
            'slug'           => $req->post('slug') ?: $req->post('title'),
            'description'    => $req->post('description', ''),
            'h1'             => $req->post('h1'),
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

        if (empty($data['title'])) {
            Session::flash('flash.error', 'El título es obligatorio.');
            Response::redirect(url('admin/pages/create'));
        }

        $id = PageService::create($data);
        if (!$id) {
            Session::flash('flash.error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
        }

        $this->saveSeo($id, $req);
        AuditService::log('page_created', "Página creada: {$data['title']}", 'pages', $id);

        Session::flash('flash.success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/$id/edit"));
    }

    public function edit(Request $req, array $params): void
    {
        $page = Page::find((int)$params['id']);
        if (!$page) Response::abort(404);

        $templates = Template::allActive();
        $pages = Page::all();
        $seo = MetaTag::forPage((int)$page['id']);

        Response::render(View::render('pages.edit', compact('page', 'templates', 'pages', 'seo')));
    }

    /**
     * Builder visual avanzado (GrapesJS + CodeMirror)
     */
    public function builder(Request $req, array $params): void
    {
        $id = (int)$params['id'];
        $page = Page::find($id);
        if (!$page) Response::abort(404, 'Página no encontrada');

        if (!Auth::can('pages.edit')) {
            Session::flash('flash.error', 'No tienes permisos para editar páginas.');
            Response::redirect(url('admin/pages'));
        }

        $templates = Template::allActive();
        $existingAssets = $this->getExistingAssets();
        $seo = MetaTag::forPage($id);

        Response::render(View::render('pages.builder', compact(
            'page', 'templates', 'existingAssets', 'seo'
        )));
    }

    public function update(Request $req, array $params): void
{
    CSRF::guard();
    
    $id = (int)$params['id'];
    $page = Page::find($id);
    if (!$page) Response::abort(404);
    
    $mode = $req->post('mode', 'metadata');
    
    // ═══════════════════════════════════════════════════════════════
    // MODO: METADATA (información básica de la página)
    // ═══════════════════════════════════════════════════════════════
    if ($mode === 'metadata') {
        $data = [
            'template_id'  => $req->post('template_id') ?: null,
            'parent_id'    => $req->post('parent_id') ?: null,
            'title'        => $req->post('title', $page['title']),
            'slug'         => $req->post('slug', $page['slug']),
            'description'  => $req->post('description', $page['description']),
            'h1'           => $req->post('h1'),
            'startpage'    => (int)$req->post('startpage', 0),
            'is_published' => (int)$req->post('is_published', 0),
            'status'       => $req->post('status', $page['status']),
            'publish_at'   => $req->post('publish_at') ?: null,
        ];
        
        if (!PageService::save($id, $data, $req->post('version_note'))) {
            Session::flash('flash.error', 'Error al guardar.');
            Response::redirect(url("admin/pages/$id/edit"));
        }
        
        AuditService::log('page_updated', "Página actualizada: {$data['title']}", 'pages', $id);
        Session::flash('flash.success', 'Página guardada correctamente.');
    }
    
    // ═══════════════════════════════════════════════════════════════
    // MODO: SEO (solo metadatos SEO)
    // ═══════════════════════════════════════════════════════════════
    elseif ($mode === 'seo') {
        $this->saveSeo($id, $req);
        AuditService::log('seo_updated', "SEO actualizado para página ID $id", 'pages', $id);
        Session::flash('flash.success', 'SEO guardado correctamente.');
    }
    
    // ═══════════════════════════════════════════════════════════════
    // MODO: VISUAL (desde builder.php)
    // ═══════════════════════════════════════════════════════════════
    elseif ($mode === 'visual') {
        $data = [
            'grapesjs_data' => $req->post('grapesjs_data'),
            'content_html'  => $req->post('content_html', $page['content_html']),
            'content_css'   => $req->post('content_css', $page['content_css']),
            'content_js'    => $req->post('content_js', $page['content_js']),
            'content_php'   => Auth::can('pages.edit') 
                ? $req->post('content_php', $page['content_php']) 
                : $page['content_php'],
        ];
        
        if (!PageService::save($id, $data, $req->post('version_note'))) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error al guardar']);
            }
            Session::flash('flash.error', 'Error al guardar.');
            Response::redirect(url("admin/pages/$id/builder"));
        }
        
        AuditService::log('page_content_updated', "Contenido actualizado para página ID $id", 'pages', $id);
        
        if ($req->isAjax()) {
            Response::json([
                'success' => true,
                'message' => 'Guardado correctamente',
                'data' => [
                    'id' => $id,
                    'slug' => $page['slug'],
                ]
            ]);
        }
        
        Session::flash('flash.success', 'Contenido guardado correctamente.');
    }
    
    // Redirección según el modo
    if ($mode === 'visual' && $req->isAjax()) {
        return; // Ya respondimos con JSON
    }
    
    $redirectUrl = $mode === 'visual' 
        ? url("admin/pages/$id/builder") 
        : url("admin/pages/$id/edit");
    
    Response::redirect($redirectUrl);
}

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        Page::delete($id);
        CacheService::invalidatePage($id);
        SitemapService::generate();
        AuditService::log('page_deleted', "Página eliminada ID $id", 'pages', $id);
        Session::flash('flash.success', 'Página eliminada.');
        Response::redirect(url('admin/pages'));
    }

    public function autosave(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        Database::execute(
            "DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?",
            [$id, Auth::id()]
        );
        Database::insert(
            "INSERT INTO page_autosave (page_id, user_id, content_html, content_css, content_js, content_php, grapesjs_data)
             VALUES (?,?,?,?,?,?,?)",
            [
                $id, Auth::id(),
                $req->post('content_html'),
                $req->post('content_css'),
                $req->post('content_js'),
                $req->post('content_php'),
                $req->post('grapesjs_data'),
            ]
        );
        Response::json(['success' => true, 'message' => 'Autoguardado']);
    }

    /**
     * Endpoint AJAX para subir imágenes desde el Asset Manager
     */
    public function uploadAsset(Request $req): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'errors' => ['Sin permisos']], 403);
        }

        $files = $_FILES['files'] ?? null;
        if (!$files) {
            Response::json(['success' => false, 'errors' => ['No se recibieron archivos']]);
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
     * Endpoint AJAX para obtener assets existentes
     */
    public function getAssets(Request $req): void
    {
        Response::json(['success' => true, 'data' => $this->getExistingAssets()]);
    }

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

    private function saveSeo(int $pageId, Request $req): void
    {
        if (!Auth::can('seo.manage')) return;

        MetaTag::syncForPage($pageId, [
            'basic' => [
                'title'       => $req->post('seo_title'),
                'description' => $req->post('seo_description'),
                'keywords'    => $req->post('seo_keywords'),
                'robots'      => $req->post('seo_robots', 'index,follow'),
                'canonical'   => $req->post('seo_canonical'),
                'author'      => $req->post('seo_author'),
            ],
            'og' => [
                'title'       => $req->post('og_title'),
                'description' => $req->post('og_description'),
                'image'       => $req->post('og_image'),
                'type'        => $req->post('og_type', 'website'),
                'url'         => $req->post('og_url'),
            ],
            'twitter' => [
                'card'        => $req->post('twitter_card', 'summary_large_image'),
                'title'       => $req->post('twitter_title'),
                'description' => $req->post('twitter_description'),
                'image'       => $req->post('twitter_image'),
            ],
        ]);
    }
}