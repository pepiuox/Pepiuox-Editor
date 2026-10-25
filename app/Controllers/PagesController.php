<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, PageModel, Template, MetaTag};
use App\Services\{PageService, AuditService, AssetUploadService, MenuService, ActivityLoggerService};

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

    /**
     * Servicio de menús: se usa para mantener el menú automático sincronizado
     * con las páginas publicadas (ver MenuService::syncDefaultMenu()).
     */
    private function menuService(): MenuService
    {
        return new MenuService($this->db, new ActivityLoggerService($this->db));
    }

    /** Sincroniza el menú automático sin romper la petición si algo falla. */
    private function syncMenus(): void
    {
        try {
            $this->menuService()->syncDefaultMenu();
        } catch (\Throwable $e) {
            error_log('[PagesController] No se pudo sincronizar el menú: ' . $e->getMessage());
        }
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
        // Si la página nace publicada, entra en el menú automático.
        $this->syncMenus();
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
        // Menús disponibles para asignar uno concreto a esta página.
        $menus = $this->menuService()->listMenus();

        Response::render(View::render('pages.edit', compact('page', 'templates', 'pages', 'seo', 'menus')));
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
            // Menú asignado a esta página. El builder no lo envía: se conserva.
            'menu_id'      => $req->post('menu_id', $page['menu_id'] ?? null) ?: null,
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
        // Publicar, cambiar de padre, despublicar o editar el título afecta al
        // menú automático: se sincroniza tras guardar.
        $this->syncMenus();
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
        // El ítem del menú automático apunta a una página que ya no existe: el
        // sync lo retira (los menús personalizados no se tocan).
        $this->syncMenus();
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

    /**
     * Sube imágenes para el gestor de assets del builder.
     *
     * La validación y el guardado viven en AssetUploadService (compartido con el
     * editor de plantillas); aquí solo quedan el permiso y la forma de la
     * respuesta que espera el cliente.
     */
    public function uploadAsset(Request $req): void
    {
        if (!Auth::can('pages.edit')) {
            Response::json(['success' => false, 'errors' => ['Sin permisos']], 403);
            return;
        }

        $result = (new AssetUploadService())->store($_FILES['files'] ?? null);

        if (!$result['received']) {
            Response::json(['success' => false, 'errors' => $result['errors']]);
            return;
        }

        Response::json([
            'success' => true,
            'data' => $result['uploaded'],
            'errors' => $result['errors'],
        ]);
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

        $assetUploader = new AssetUploadService();

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

            // Las dimensiones las calcula el servicio de subida (misma lógica para
            // lo que se sube y para lo que ya está en disco).
            [$width, $height] = $assetUploader->dimensionsOf($file->getPathname(), $ext);

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