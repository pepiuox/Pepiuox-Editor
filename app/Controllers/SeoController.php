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
            'ga_tracking' => SettingService::get('seo', 'ga_tracking', ''),
            'gsc_verification' => SettingService::get('seo', 'gsc_verification', ''),
            'twitter_site' => SettingService::get('seo', 'twitter_site', ''),
        ];
        Response::render(View::render('seo.global', compact('settings')));
    }

    public function globalSave(Request $req): void
    {
        CSRF::guard();
        $fields = [
            'default_meta_title','default_meta_description','default_robots',
            'default_og_image','ga_tracking','gsc_verification','twitter_site'
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
        SitemapService::generate();
        AuditService::log(Auth::id(), 'sitemap_regenerated', 'Sitemap regenerado', 'seo');
        Session::flash('flash.success', 'Sitemap regenerado correctamente.');
        Response::redirect(url('admin/seo'));
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