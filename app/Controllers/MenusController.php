<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Database};
use App\Services\{MenuService, CacheService, AuditService};

class MenusController
{
    public function index(Request $req): void
    {
        $group = $req->get('group', 'main');
        $items = MenuService::allFlat($group);
        $groups = Database::fetchAll("SELECT * FROM menus ORDER BY name");
        Response::render(View::render('menus.index', compact('items','group','groups')));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        MenuService::create([
            'title' => $req->post('title'),
            'slug' => $req->post('slug') ?: \App\Models\Page::slugify($req->post('title')),
            'url' => $req->post('url'),
            'page_id' => $req->post('page_id') ?: null,
            'target' => $req->post('target', '_self'),
            'menu_group' => $req->post('menu_group', 'main'),
            'parent_id' => $req->post('parent_id') ?: null,
        ]);
        CacheService::invalidateAllPages();
        AuditService::log('menu_item_created', "Item menú: ".$req->post('title'), 'menus');
        if ($req->isAjax()) Response::json(['success'=>true]);
        Session::flash('flash.success', 'Item añadido.');
        Response::redirect(url('admin/menus?group='.$req->post('menu_group','main')));
    }

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        MenuService::update($id, [
            'title' => $req->post('title'),
            'slug' => $req->post('slug'),
            'url' => $req->post('url'),
            'page_id' => $req->post('page_id') ?: null,
            'target' => $req->post('target', '_self'),
            'is_published' => (int)$req->post('is_published', 1),
        ]);
        CacheService::invalidateAllPages();
        if ($req->isAjax()) Response::json(['success'=>true]);
        Session::flash('flash.success', 'Item actualizado.');
        Response::redirect(url('admin/menus?group='.$req->post('menu_group','main')));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        MenuService::delete((int)$params['id']);
        CacheService::invalidateAllPages();
        if ($req->isAjax()) Response::json(['success'=>true]);
        Session::flash('flash.success', 'Item eliminado.');
        Response::redirect(url('admin/menus'));
    }

    /** Reordenar vía AJAX (drag & drop) */
    public function reorder(Request $req): void
    {
        CSRF::guard();
        $items = json_decode($req->post('items') ?: '[]', true);
        if (!is_array($items)) Response::json(['success'=>false,'message'=>'Datos inválidos'], 400);
        MenuService::reorder($items);
        CacheService::invalidateAllPages();
        AuditService::log('menu_reordered', 'Menú reordenado', 'menus');
        Response::json(['success'=>true]);
    }
}