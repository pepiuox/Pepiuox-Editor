<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\Component;
use App\Services\AuditService;

class ComponentsController
{
    public function index(Request $req): void
    {
        $cat = $req->get('category');
        $components = Component::all($cat);
        $categories = Component::categories();
        Response::render(View::render('components.index', compact('components','categories','cat')));
    }

    public function create(Request $req): void
    {
        Response::render(View::render('components.edit', ['component' => null]));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        $id = Component::create([
            'name' => $req->post('name'),
            'category' => $req->post('category', 'general'),
            'html' => $req->post('html', ''),
            'css' => $req->post('css', ''),
            'is_active' => (int)$req->post('is_active', 1),
            'created_by' => Auth::id(),
        ]);
        AuditService::log('component_created', "Component: ".$req->post('name'), 'components', $id);
        Session::flash('flash.success', 'Component created.');
        Response::redirect(url('admin/components'));
    }

    public function edit(Request $req, array $params): void
    {
        $component = Component::find((int)$params['id']);
        if (!$component) Response::abort(404);
        Response::render(View::render('components.edit', compact('component')));
    }

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        Component::update((int)$params['id'], [
            'name' => $req->post('name'),
            'category' => $req->post('category', 'general'),
            'html' => $req->post('html', ''),
            'css' => $req->post('css', ''),
            'is_active' => (int)$req->post('is_active', 1),
        ]);
        Session::flash('flash.success', 'Component updated.');
        Response::redirect(url('admin/components'));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        Component::delete((int)$params['id']);
        Session::flash('flash.success', 'Component deleted.');
        Response::redirect(url('admin/components'));
    }

    /** API AJAX para GrapesJS: lista componentes disponibles */
    public function apiList(Request $req): void
    {
        $components = Component::all();
        $out = [];
        foreach ($components as $c) {
            $out[] = [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'category' => $c['category'],
                'html' => $c['html'],
                'css' => $c['css'],
                'label' => $c['name'],
            ];
        }
        Response::json(['success'=>true,'data'=>$out]);
    }
}