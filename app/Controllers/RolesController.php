<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\{Role, Permission};
use App\Services\AuditService;

class RolesController
{
    public function index(Request $req): void
    {
        $roles = Role::all();
        foreach ($roles as &$r) $r['users_count'] = Role::usersCount((int)$r['idRol']);
        Response::render(View::render('roles.index', compact('roles')));
    }

    public function create(Request $req): void
    {
        $permissions = Permission::all();
        $grouped = [];
        foreach ($permissions as $p) $grouped[$p['category']][] = $p;
        Response::render(View::render('roles.create', compact('grouped')));
    }

    public function store(Request $req): void
    {
        CSRF::guard();
        $id = Role::create([
            'name' => $req->post('name'),
            'description' => $req->post('description'),
            'required' => 0,
            'default_role' => (int)$req->post('default_role', 0),
        ]);
        $perms = $req->post('permissions') ?? [];
        Permission::syncRole($id, is_array($perms) ? $perms : []);
        AuditService::log('role_created', "Rol creado: ".$req->post('name'), 'roles', $id);
        Session::flash('flash.success', 'Rol creado.');
        Response::redirect(url('admin/roles'));
    }

    public function edit(Request $req, array $params): void
    {
        $role = Role::find((int)$params['id']);
        if (!$role) Response::abort(404);
        $permissions = Permission::all();
        $grouped = [];
        foreach ($permissions as $p) $grouped[$p['category']][] = $p;
        $rolePerms = Permission::byRole((int)$role['idRol']);
        Response::render(View::render('roles.edit', compact('role','grouped','rolePerms')));
    }

    public function update(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        $role = Role::find($id);
        if (!$role) Response::abort(404);
        Role::update($id, [
            'name' => $req->post('name'),
            'description' => $req->post('description'),
            'default_role' => (int)$req->post('default_role', 0),
        ]);
        $perms = $req->post('permissions') ?? [];
        Permission::syncRole($id, is_array($perms) ? $perms : []);
        AuditService::log('role_updated', "Rol actualizado: ".$req->post('name'), 'roles', $id);
        Session::flash('flash.success', 'Rol actualizado.');
        Response::redirect(url('admin/roles'));
    }

    public function delete(Request $req, array $params): void
    {
        CSRF::guard();
        $id = (int)$params['id'];
        $role = Role::find($id);
        if (!$role) Response::abort(404);
        if ($role['required']) {
            Session::flash('flash.error', 'No se puede eliminar un rol del sistema.');
            Response::redirect(url('admin/roles'));
        }
        Role::delete($id);
        AuditService::log('role_deleted', "Rol eliminado: {$role['name']}", 'roles', $id);
        Session::flash('flash.success', 'Rol eliminado.');
        Response::redirect(url('admin/roles'));
    }
}