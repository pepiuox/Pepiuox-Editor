<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF};
use App\Services\{CacheService, AuditService};

class CacheController
{
    public function index(Request $req): void
    {
        $stats = CacheService::stats();
        Response::render(View::render('cache.index', compact('stats')));
    }

    public function clear(Request $req): void
    {
        CSRF::guard();
        CacheService::invalidateAllPages();
        AuditService::log('cache_cleared', 'Cache limpiado', 'cache');
        Session::flash('flash.success', 'Cache limpiado.');
        Response::redirect(url('admin/cache'));
    }

    public function regenerate(Request $req): void
    {
        CSRF::guard();
        $n = CacheService::regenerateAll();
        AuditService::log('cache_regenerated', "Cache regenerado: $n páginas", 'cache');
        Session::flash('flash.success', "Cache regenerado ($n páginas).");
        Response::redirect(url('admin/cache'));
    }
}