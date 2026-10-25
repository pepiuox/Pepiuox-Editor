<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, PageModel, Template, MetaTag};
use App\Services\{PageService, AuditService};

class PagesController
{
    private Database $db;
    private PageService $pageService;

    public function __construct(?Database $db = null, ?PageService $pageService = null)
    {
        $this->db = $db ?? new Database();
        // PageService::save() es un método de instancia: llamarlo de forma
        // estática lanzaba "Non-static method cannot be called statically".
        $this->pageService = $pageService ?? new PageService($this->db, new PageModel($this->db));
    }

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

        $id = $this->pageService->create($data);
        if (!$id) {
            Session::flash('flash.error', 'Error al crear la página.');
            Response::redirect(url('admin/pages/create'));
        }

        $this->saveSeo($id, $req);
        // AuditService::log() recibe primero el usuario: con los argumentos
        // desordenados PHP lanzaba un TypeError y la petición acababa en 500.
        AuditService::log(Auth::id(), 'page_created', "Página creada: {$data['title']} (ID $id)", 'pages');

        Session::flash('flash.success', 'Página creada correctamente.');
        Response::redirect(url("admin/pages/$id/builder"));
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

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        $page = Page::find($id);
        if (!$page) Response::abort(404);

        $mode = $req->post('mode', 'code');

        $title       = (string) $req->post('title', $page['title']);
        $description = (string) $req->post('description', $page['description']);

        // `title` y `description` son varchar(255). Sin esta comprobación el
        // guardado fallaba con "Data too long" y un mensaje genérico.
        $tooLong = null;
        if (mb_strlen($title) > 255) {
            $tooLong = 'El título no puede superar los 255 caracteres.';
        } elseif (mb_strlen($description) > 255) {
            $tooLong = 'La descripción no puede superar los 255 caracteres.';
        }

        if ($tooLong !== null) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => $tooLong], 422);
            }
            Session::flash('flash.error', $tooLong);
            Response::redirect(url("admin/pages/$id/edit"));
        }

        $data = [
            'template_id'  => $req->post('template_id') ?: null,
            // El builder no envía parent_id: sin fallback se perdía la jerarquía.
            'parent_id'    => $req->post('parent_id', $page['parent_id'] ?? null) ?: null,
            'title'        => $title,
            'slug'         => $req->post('slug', $page['slug']),
            'description'  => $description,
            // El builder no envía h1: sin este fallback se borraría al guardar.
            'h1'           => $req->post('h1', $page['h1'] ?? null),
            'startpage'    => (int)$req->post('startpage', 0),
            // El builder tampoco envía is_published: sin fallback, guardar
            // despublicaba la página en cada guardado.
            'is_published' => (int)$req->post('is_published', $page['is_published'] ?? 0),
            'status'       => $req->post('status', $page['status']),
            'publish_at'   => $req->post('publish_at') ?: null,
        ];

        // El contenido se persiste igual en ambos modos: el builder visual
        // también edita JS y PHP, y antes se descartaban al guardar.
        $data['content_html'] = $req->post('content_html', $page['content_html']);
        $data['content_css']  = $req->post('content_css', $page['content_css']);
        $data['content_js']   = $req->post('content_js', $page['content_js']);
        $data['content_php']  = Auth::can('pages.edit')
            ? $req->post('content_php', $page['content_php'])
            : $page['content_php'];

        if ($mode === 'visual') {
            $data['grapesjs_data'] = $req->post('grapesjs_data', $page['grapesjs_data'] ?? null);
        }

        if (!$this->pageService->save($id, $data, $req->post('version_note'))) {
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error al guardar']);
            }
            Session::flash('flash.error', 'Error al guardar.');
            Response::redirect(url("admin/pages/$id/edit"));
        }

        $this->saveSeo($id, $req);
        // Firma real: log(?int $userId, string $action, ?string $details, ?string $context).
        // Antes se pasaba la acción como primer argumento y el TypeError que
        // lanzaba se comía la respuesta: el guardado sí estaba en la base de
        // datos, pero el builder recibía un 500 y lo mostraba como error.
        AuditService::log(Auth::id(), 'page_updated', "Página actualizada: {$data['title']} (ID $id)", 'pages');

        if ($req->isAjax()) {
            Response::json(['success' => true, 'message' => 'Guardado correctamente']);
        }

        Session::flash('flash.success', 'Página guardada.');
        Response::redirect(url("admin/pages/$id/edit"));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        Page::delete($id);
        \App\Services\CacheService::invalidatePage($id);
        \App\Services\SitemapService::generate();
        AuditService::log(Auth::id(), 'page_deleted', "Página eliminada ID $id", 'pages');
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
        // La tabla page_autosave solo tiene content_html/css/js/php. El INSERT
        // mencionaba además grapesjs_data, una columna que no existe: la consulta
        // fallaba siempre con "Unknown column" y el autoguardado nunca guardó nada.
        Database::insert(
            "INSERT INTO page_autosave 
                (page_id, user_id, content_html, content_css, content_js, content_php)
             VALUES (?,?,?,?,?,?)",
            [
                $id, Auth::id(),
                $req->post('content_html'),
                $req->post('content_css'),
                $req->post('content_js'),
                $req->post('content_php'),
            ]
        );
        Response::json(['success' => true, 'message' => 'Autoguardado']);
    }

    public function getAutosave(Request $req, array $params): void
    {
        $id = (int)$params['id'];
        $autosave = Database::fetch(
            "SELECT * FROM page_autosave 
             WHERE page_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$id, Auth::id()]
        );

        if (!$autosave) {
            Response::json(['success' => true, 'data' => null]);
            return;
        }

        Response::json([
            'success' => true,
            'data' => [
                'id' => (int)$autosave['id'],
                'content_html' => $autosave['content_html'],
                'content_css' => $autosave['content_css'],
                'content_js' => $autosave['content_js'],
                'content_php' => $autosave['content_php'],
                'grapesjs_data' => $autosave['grapesjs_data'] ?? null,
                'created_at' => $autosave['created_at'],
                'age_seconds' => time() - strtotime($autosave['created_at']),
            ]
        ]);
    }

    public function clearAutosave(Request $req, array $params): void
    {
        $id = (int)$params['id'];
        Database::execute(
            "DELETE FROM page_autosave WHERE page_id = ? AND user_id = ?",
            [$id, Auth::id()]
        );
        Response::json(['success' => true]);
    }

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

        // El builder es un documento HTML completo (lleva su propio <!DOCTYPE>),
        // así que se renderiza sin layout. Envolverlo en layouts.admin anidaba un
        // segundo <body>: el navegador lo descarta y el lienzo queda sin altura.
        Response::render(View::renderStandalone('pages.builder', compact(
            'page', 'templates', 'existingAssets', 'seo'
        )));
    }

    public function uploadAsset(Request $req): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'errors' => ['Sin permisos']], 403);
            return;
        }

        $files = $_FILES['files'] ?? null;
        if (!$files || !isset($files['name'])) {
            Response::json(['success' => false, 'errors' => ['No se recibieron archivos']]);
            return;
        }

        $uploaded = [];
        $errors   = [];
        $names    = (array) $files['name'];
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $maxBytes = 5 * 1024 * 1024;

        for ($i = 0, $count = count($names); $i < $count; $i++) {
            $originalName = (string) $names[$i];
            $error    = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            $tmpName  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size     = (int) (is_array($files['size']) ? $files['size'][$i] : $files['size']);
            $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $originalName;
                continue;
            }
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Tipo no permitido: ' . $originalName;
                continue;
            }
            if ($size > $maxBytes) {
                $errors[] = 'Archivo demasiado grande (máx 5MB): ' . $originalName;
                continue;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmpName);
            if (!str_starts_with($mime, 'image/')) {
                $errors[] = 'MIME inválido: ' . $originalName;
                continue;
            }

            $yearMonth = date('Y/m');
            $dir = rtrim(UPLOADS_PATH, '/\\') . '/' . $yearMonth;
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                $errors[] = 'No se pudo crear el directorio de subida';
                break;
            }

            // Se conserva el nombre original; si ya existe se añade -1, -2...
            $filename = $this->uniqueAssetName($dir, $originalName);
            $dest = $dir . '/' . $filename;

            if (!move_uploaded_file($tmpName, $dest)) {
                $errors[] = 'No se pudo guardar: ' . $originalName;
                continue;
            }
            @chmod($dest, 0644);

            [$width, $height] = $this->assetDimensions($dest, $ext);
            $relPath = $yearMonth . '/' . $filename;

            $uploaded[] = [
                'src'           => url('uploads/' . $relPath),
                'name'          => $filename,
                'original_name' => $originalName,
                'type'          => 'image',
                'width'         => $width,
                'height'        => $height,
                'size'          => $size,
                'uploaded_at'   => date('Y-m-d H:i:s'),
            ];
        }

        Response::json([
            'success' => true,
            'data' => $uploaded,
            'errors' => $errors,
        ]);
    }

    /**
     * Nombre de archivo seguro para URL que conserva el original.
     * Si ya existe en el directorio, añade un sufijo numérico.
     */
    private function uniqueAssetName(string $dir, string $originalName): string
    {
        $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        // Sin acentos, sin separadores de ruta y con espacios convertidos en guiones.
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
            if ($ascii !== false) {
                $base = $ascii;
            }
        }
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? '';
        $base = trim(str_replace(['..', '/', '\\'], '', $base), '-._');

        if ($base === '') {
            $base = 'imagen';
        }
        $base = substr($base, 0, 80);

        $candidate = $base . '.' . $ext;
        $suffix = 1;
        while (is_file($dir . '/' . $candidate)) {
            $candidate = $base . '-' . $suffix . '.' . $ext;
            $suffix++;
            if ($suffix > 999) {
                $candidate = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                break;
            }
        }

        return $candidate;
    }

    /**
     * Dimensiones de una imagen o de un SVG (por viewBox).
     *
     * @return array{0:int,1:int}
     */
    private function assetDimensions(string $path, string $ext): array
    {
        if ($ext === 'svg') {
            $svg = @file_get_contents($path);
            if ($svg && preg_match('/viewBox=["\']([^"\']+)["\']/', $svg, $matches)) {
                $viewBox = preg_split('/[\s,]+/', trim($matches[1])) ?: [];
                if (count($viewBox) === 4) {
                    return [(int) $viewBox[2], (int) $viewBox[3]];
                }
            }
            return [0, 0];
        }

        $info = @getimagesize($path);
        return $info ? [(int) $info[0], (int) $info[1]] : [0, 0];
    }

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

            // getPathname() devuelve separadores nativos (barra invertida en
            // Windows). Al quitar el prefijo con str_replace() y '/' la ruta no
            // se recortaba y la URL acababa siendo "uploads/C:\...": el visor de
            // assets mostraba imágenes rotas para todo lo que estaba en subcarpetas.
            $absolute = str_replace('\\', '/', $file->getPathname());
            $relativePath = ltrim(substr($absolute, strlen(str_replace('\\', '/', rtrim($uploadDir, '/\\')))), '/');
            $publicUrl = url('uploads/' . $relativePath);

            [$width, $height] = $this->assetDimensions($file->getPathname(), $ext);

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