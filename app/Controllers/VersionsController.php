<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\{PageVersion, Page};
use App\Services\{CacheService, AuditService};

class VersionsController
{
    public function index(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $page = Page::find($pageId);
        if (!$page) Response::abort(404);
        $versions = PageVersion::forPage($pageId);
        Response::render(View::render('pages.versions', compact('page','versions')));
    }

    public function preview(Request $req, array $params): void
    {
        $v = PageVersion::find((int)$params['vid']);
        if (!$v) Response::abort(404);
        echo "<!doctype html><html><head><meta charset='utf-8'><style>" . $v['content_css'] . "</style></head><body>" . $v['content_html'] . "<script>" . $v['content_js'] . "</script></body></html>";
        exit;
    }

    public function restore(Request $req, array $params): void
    {
        CSRF::guard();
        $vid = (int)$params['vid'];
        $v = PageVersion::find($vid);
        if (!$v) Response::abort(404);
        PageVersion::restore($vid);
        CacheService::invalidatePage((int)$v['page_id']);
        \App\Services\SitemapService::generate();
        AuditService::log(Auth::id(), 'page_restored', "Versión #$vid restaurada", 'pages', (int)$v['page_id']);
        Session::flash('flash.success', 'Versión restaurada.');
        Response::redirect(url("admin/pages/{$v['page_id']}/edit"));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $v = PageVersion::find((int)$params['vid']);
        if ($v) PageVersion::delete((int)$params['vid']);
        Session::flash('flash.success', 'Versión eliminada.');
        Response::redirect(url("admin/pages/{$v['page_id']}/versions"));
    }
}