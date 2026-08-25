<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\Template;
use App\Services\{CacheService, AuditService};

class TemplatesController
{
    public function index(Request $req): void
    {
        $templates = Template::all();
        Response::render(View::render('templates.index', compact('templates')));
    }

    public function create(Request $req): void
    {
        Response::render(View::render('templates.edit', ['template' => null]));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        $id = Template::create([
            'name' => $req->post('name'),
            'description' => $req->post('description'),
            'content_html' => $req->post('content_html',''),
            'content_css' => $req->post('content_css',''),
            'content_js' => $req->post('content_js',''),
            'content_php' => Auth::can('templates.edit') ? $req->post('content_php','') : '',
            'grapesjs_data' => $req->post('grapesjs_data'),
            'is_active' => (int)$req->post('is_active',1),
            'is_default' => (int)$req->post('is_default',0),
            'created_by' => Auth::id(),
        ]);
        AuditService::log('template_created', "Template creado: ".$req->post('name'), 'templates', $id);
        Session::flash('flash.success', 'Template creado.');
        Response::redirect(url("admin/templates/$id/edit"));
    }

    public function edit(Request $req, array $params): void
    {
        $template = Template::find((int)$params['id']);
        if (!$template) Response::abort(404);
        Response::render(View::render('templates.edit', compact('template')));
    }

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        $old = Template::find($id);
        Template::update($id, [
            'name' => $req->post('name'),
            'description' => $req->post('description'),
            'content_html' => $req->post('content_html',''),
            'content_css' => $req->post('content_css',''),
            'content_js' => $req->post('content_js',''),
            'content_php' => Auth::can('templates.edit') ? $req->post('content_php','') : ($old['content_php'] ?? ''),
            'grapesjs_data' => $req->post('grapesjs_data'),
            'is_active' => (int)$req->post('is_active',1),
            'is_default' => (int)$req->post('is_default',0),
        ]);
        // Invalidar cache de páginas que usan este template
        CacheService::invalidateTemplatePages($id);
        AuditService::log('template_updated', "Template actualizado: ".$req->post('name'), 'templates', $id);
        Session::flash('flash.success', 'Template actualizado.');
        Response::redirect(url("admin/templates/$id/edit"));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        Template::delete((int)$params['id']);
        Session::flash('flash.success', 'Template eliminado.');
        Response::redirect(url('admin/templates'));
    }

    public function duplicate(Request $req, array $params): void
    {
        CSRF::guard();
        Template::duplicate((int)$params['id']);
        Session::flash('flash.success', 'Template duplicado.');
        Response::redirect(url('admin/templates'));
    }
}