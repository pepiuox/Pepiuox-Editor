<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Services\{SeoService, SettingService, SitemapService, AuditService};
use App\Models\{Page, MetaTag};

class SeoController
{
    /** Panel principal SEO */
    public function index(Request $req): void
    {
        $pages = Database::fetchAll(
            "SELECT p.id, p.title, p.slug, p.status, p.updated_at,
                    (SELECT tag_value FROM meta_tags WHERE page_id = p.id AND tag_type='basic' AND tag_name='title' LIMIT 1) AS meta_title,
                    (SELECT tag_value FROM meta_tags WHERE page_id = p.id AND tag_type='basic' AND tag_name='description' LIMIT 1) AS meta_desc
               FROM pages p
              ORDER BY p.updated_at DESC LIMIT 50"
        );

        // Calcular score de cada página
        foreach ($pages as &$p) {
            $seo = MetaTag::forPage((int)$p['id']);
            $result = SeoService::score($p, $seo);
            $p['seo_score'] = $result['score'];
        }
        unset($p);

        $stats = [
            'total_pages' => (int)Database::fetch("SELECT COUNT(*) AS c FROM pages")['c'],
            'with_title' => (int)Database::fetch("SELECT COUNT(DISTINCT page_id) AS c FROM meta_tags WHERE tag_type='basic' AND tag_name='title'")['c'],
            'with_desc' => (int)Database::fetch("SELECT COUNT(DISTINCT page_id) AS c FROM meta_tags WHERE tag_type='basic' AND tag_name='description'")['c'],
            'redirects' => (int)Database::fetch("SELECT COUNT(*) AS c FROM redirects WHERE is_active=1")['c'],
        ];

        Response::render(View::render('seo.index', compact('pages','stats')));
    }

    /** Configuración SEO global del sitio */
    public function global(Request $req): void
    {
        $settings = [
            'site_name' => SettingService::get('general', 'site_name', ''),
            'site_description' => SettingService::get('general', 'site_description', ''),
            'default_meta_title' => SettingService::get('seo', 'default_meta_title', ''),
            'default_meta_description' => SettingService::get('seo', 'default_meta_description', ''),
            'default_robots' => SettingService::get('seo', 'default_robots', 'index, follow'),
            'default_og_image' => SettingService::get('seo', 'default_og_image', ''),
            'twitter_site' => SettingService::get('seo', 'twitter_site', ''),
        ];

        Response::render(View::render('seo.global', compact('settings')));
    }

    public function globalSave(Request $req): void
    {
        CSRF::guard();
        $fields = [
            'default_meta_title','default_meta_description','default_robots',
            'default_og_image','twitter_site'
        ];
        foreach ($fields as $f) {
            SettingService::set('seo', $f, $req->post($f, ''), 'string');
        }

        AuditService::log(Auth::id(), 'seo_global_updated', 'SEO global actualizado', 'settings');
        Session::flash('flash.success', 'Configuración SEO global guardada.');
        Response::redirect(url('admin/seo/global'));
    }

    /** Analyzer de una página específica */
    public function analyze(Request $req, array $params): void
    {
        $page = Page::find((int)$params['id']);
        if (!$page) Response::abort(404);
        $seo = MetaTag::forPage((int)$page['id']);
        $result = SeoService::score($page, $seo);
        Response::render(View::render('seo.analyze', compact('page','seo','result')));
    }

    /** Gestión de redirecciones */
    public function redirects(Request $req): void
    {
        $redirects = Database::fetchAll("SELECT * FROM redirects ORDER BY created_at DESC");
        Response::render(View::render('seo.redirects', compact('redirects')));
    }

    public function redirectStore(Request $req): void
    {
        CSRF::guard();
        Database::insert(
            "INSERT INTO redirects (from_path, to_path, status_code, is_active, created_by) VALUES (?,?,?,?,?)",
            [
                '/' . ltrim($req->post('from_path'), '/'),
                $req->post('to_path'),
                (int)$req->post('status_code', 301),
                (int)$req->post('is_active', 1),
                Auth::id(),
            ]
        );
        AuditService::log(Auth::id(), 'redirect_created', "Redirect: ".$req->post('from_path'), 'redirects');
        Session::flash('flash.success', 'Redirección creada.');
        Response::redirect(url('admin/seo/redirects'));
    }

    public function redirectDelete(Request $req, array $params): void
    {
        CSRF::guard();
        Database::execute("DELETE FROM redirects WHERE id = ?", [(int)$params['id']]);
        Session::flash('flash.success', 'Redirección eliminada.');
        Response::redirect(url('admin/seo/redirects'));
    }

    /** Regenerar sitemap */
    public function regenerateSitemap(Request $req): void
    {
        CSRF::guard();
        $this->guardSitemap();

        $informe = SitemapService::generate();

        AuditService::log(
            Auth::id(),
            'sitemap_regenerated',
            'Sitemap regenerado: ' . $informe['total'] . ' URL(s) desde ' . $informe['source'],
            'seo'
        );

        Session::flash(
            $informe['ok'] ? 'flash.success' : 'flash.error',
            $informe['mensaje']
        );

        Response::redirect(url('admin/seo/sitemap'));
    }

    /**
     * `GET /admin/seo/sitemap` — pantalla del sitemap.
     *
     * Muestra qué se está publicando de verdad y por qué: los menús en uso, la
     * URL base con su origen, el aviso si esa base es `localhost`, y la lista de
     * URLs que acabarían en el archivo con el motivo de cada una.
     */
    public function sitemap(Request $req): void
    {
        $this->guardSitemap();

        // Previsualización: abrir la pantalla no reescribe el archivo. Lo
        // escribe el botón «Regenerar» o el guardado de la configuración.
        $informe = SitemapService::generate(false);
        $estado  = SitemapService::status();
        $base    = SitemapService::baseUrl();

        Response::render(View::render('seo.sitemap', [
            'informe' => $informe,
            'estado'  => $estado,
            'base'    => $base,
            'ajustes' => SitemapService::settings(),
            'scopes'  => SitemapService::scopes(),
            'menus'   => SitemapService::menuCatalog(),
            'xml'     => SitemapService::currentXml(),
        ]));
    }

    /**
     * `POST /admin/seo/sitemap/settings` — guarda de dónde sale el sitemap.
     *
     * El ámbito por defecto son los **menús predeterminados** («Menú
     * principal»), por decisión del dueño. El menú que el header muestra queda
     * como opción explícita, porque no es lo mismo: puede haber un menú marcado
     * como principal que solo muestre una parte del sitio.
     */
    public function saveSitemapSettings(Request $req): void
    {
        CSRF::guard();
        $this->guardSitemap();

        $scope = (string) $req->post('scope');
        if (!array_key_exists($scope, SitemapService::scopes())) {
            $scope = 'default_menus';
        }

        $menuId = (int) $req->post('menu_id');
        if ($scope === 'specific' && $menuId <= 0) {
            Session::flash('flash.error', 'Elegiste «un menú concreto» pero no indicaste cuál.');
            Response::redirect(url('admin/seo/sitemap'));
        }

        $base = trim((string) $req->post('base_url'));
        if ($base !== '' && !preg_match('#^https?://[^\s]+$#i', $base)) {
            Session::flash('flash.error', 'La URL base debe empezar por http:// o https://');
            Response::redirect(url('admin/seo/sitemap'));
        }

        $incluirInicio = $req->post('include_home') === '1';

        SettingService::set('seo', 'sitemap_scope', $scope, 'string');
        SettingService::set('seo', 'sitemap_menu_id', (string) $menuId, 'int');
        SettingService::set('seo', 'sitemap_base_url', rtrim($base, '/'), 'string');
        SettingService::set('seo', 'sitemap_include_home', $incluirInicio ? '1' : '0', 'bool');
        SettingService::clearCache();

        $informe = SitemapService::generate();

        AuditService::log(Auth::id(), 'sitemap_settings_saved', 'Ámbito del sitemap: ' . $scope, 'seo');
        Session::flash(
            $informe['ok'] ? 'flash.success' : 'flash.error',
            'Configuración guardada. ' . $informe['mensaje']
        );

        Response::redirect(url('admin/seo/sitemap'));
    }

    /**
     * Permiso del módulo SEO para las acciones del sitemap.
     *
     * Se comprueba aquí y no solo con el middleware de sesión porque
     * `PermissionMiddleware` no está aplicado en ninguna ruta (deuda 14 de la
     * constitución). Una acción que escribe en `public/` no puede depender de un
     * middleware que no se ejecuta.
     */
    private function guardSitemap(): void
    {
        if (!Auth::can('seo.manage')) {
            Response::abort(403, 'No tienes permiso para gestionar el SEO.');
        }
    }

    /** robots.txt dinámico */
    public function robots(Request $req): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo SeoService::robotsTxt();
        exit;
    }
    /** Análisis de competencia */
public function competitor(Request $req, array $params): void
{
    $page = \App\Models\Page::find((int)$params['id']);
    if (!$page) Response::abort(404);
    $seo = \App\Models\MetaTag::forPage((int)$page['id']);
    $analysis = \App\Services\SeoCompetitorService::analyze($page, $seo);
    Response::render(View::render('seo.competitor', compact('page','seo','analysis')));
}

/** Vista del generador de meta descriptions */
public function metaGenerator(Request $req, array $params): void
{
    $page = \App\Models\Page::find((int)$params['id']);
    if (!$page) Response::abort(404);
    Response::render(View::render('seo.generator', compact('page')));
}

/** API AJAX: Generar meta descriptions */
public function generateMeta(Request $req, array $params): void
{
    $page = \App\Models\Page::find((int)$params['id']);
    if (!$page) Response::json(['success'=>false,'message'=>'Página no encontrada'], 404);
    
    $title = $req->post('title', $page['title']);
    $keyword = $req->post('keyword', '');
    
    $variants = \App\Services\MetaDescriptionGenerator::generate(
        $page['content_html'] ?? '',
        $title,
        $keyword
    );
    
    Response::json(['success' => true, 'data' => $variants]);
}

/** API AJAX: Aplicar meta description a la página */
public function applyMeta(Request $req, array $params): void
{
    csrf_guard();
    $pageId = (int)$params['id'];
    $desc = trim($req->post('seo_description', ''));
    
    if (mb_strlen($desc) > 160) {
        Response::json(['success'=>false,'message'=>'Descripción demasiado larga'], 400);
    }
    
    // Actualizar o crear meta tag
    \App\Models\MetaTag::syncForPage($pageId, [
        'basic' => ['description' => $desc]
    ]);
    
    // Invalidar cache
    \App\Services\CacheService::invalidatePage($pageId);
    \App\Services\SitemapService::generate();
    
    \App\Services\AuditService::log(Auth::id(), 'meta_generated', "Meta description generada automáticamente", 'pages', $pageId);
    
    Response::json(['success' => true, 'message' => 'Meta description aplicada']);
}
}