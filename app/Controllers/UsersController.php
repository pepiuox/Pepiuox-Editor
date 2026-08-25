<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\User;
use App\Services\AuditService;

class UsersController
{
    /**
     * Lista de usuarios
     */
    public function index(Request $req): void
    {
        if (!Auth::can('users.view')) {
            Response::abort(403, 'No tienes permisos para ver usuarios');
        }

        $users = User::all();
        Response::render(View::render('users.index', compact('users')));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $req): void
    {
        if (!Auth::can('users.create')) {
            Response::abort(403, 'No tienes permisos para crear usuarios');
        }

        $roles = \App\Models\Role::all();
        Response::render(View::render('users.create', compact('roles')));
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $req): void
    {
        CSRF::guard();

        if (!Auth::can('users.create')) {
            Session::flash('flash.error', 'Sin permisos para crear usuarios.');
            Response::redirect(url('admin/users'));
        }

        $username = trim($req->post('username', ''));
        $email = trim($req->post('email', ''));
        $password = $req->post('password', '');
        $passwordConfirm = $req->post('password_confirmation', '');
        $fullName = trim($req->post('full_name', ''));
        $role = $req->post('role', 'Guest');
        $isActive = (int)$req->post('is_active', 1);

        // Validaciones
        if (empty($username) || empty($email) || empty($password)) {
            Session::flash('flash.error', 'Todos los campos obligatorios deben estar completos.');
            Response::redirect(url('admin/users/create'));
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            Session::flash('flash.error', 'El nombre de usuario debe tener entre 3 y 50 caracteres.');
            Response::redirect(url('admin/users/create'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('flash.error', 'Email inválido.');
            Response::redirect(url('admin/users/create'));
        }

        if (strlen($password) < 8) {
            Session::flash('flash.error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirect(url('admin/users/create'));
        }

        if ($password !== $passwordConfirm) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url('admin/users/create'));
        }

        // Verificar usuario único
        if (User::findByUsername($username)) {
            Session::flash('flash.error', 'El nombre de usuario ya existe.');
            Response::redirect(url('admin/users/create'));
        }

        // Verificar email único
        if (User::findByEmail($email)) {
            Session::flash('flash.error', 'El email ya está registrado.');
            Response::redirect(url('admin/users/create'));
        }

        try {
            $userId = User::create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'full_name' => $fullName,
                'role' => $role,
                'is_active' => $isActive,
            ]);

            AuditService::log('user_created', "Usuario creado: $username", 'users', $userId);
            Session::flash('flash.success', 'Usuario creado correctamente.');
            Response::redirect(url('admin/users'));
        } catch (\Throwable $e) {
            error_log('[UsersController] Error creando usuario: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al crear usuario: ' . $e->getMessage());
            Response::redirect(url('admin/users/create'));
        }
    }

    /**
     * Formulario de edición
     */
    public function edit(Request $req, array $params): void
    {
        if (!Auth::can('users.edit')) {
            Response::abort(403, 'No tienes permisos para editar usuarios');
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::abort(404, 'ID de usuario inválido');
        }

        $user = User::find($id);
        if (!$user) {
            Response::abort(404, 'Usuario no encontrado');
        }

        $roles = \App\Models\Role::all();
        Response::render(View::render('users.edit', compact('user', 'roles')));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('users.edit')) {
            Session::flash('flash.error', 'Sin permisos para editar usuarios.');
            Response::redirect(url('admin/users'));
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('flash.error', 'ID de usuario inválido.');
            Response::redirect(url('admin/users'));
        }

        $user = User::find($id);
        if (!$user) {
            Session::flash('flash.error', 'Usuario no encontrado.');
            Response::redirect(url('admin/users'));
        }

        $username = trim($req->post('username', ''));
        $email = trim($req->post('email', ''));
        $fullName = trim($req->post('full_name', ''));
        $role = $req->post('role', 'Guest');
        $isActive = (int)$req->post('is_active', 1);

        // Validaciones
        if (empty($username) || empty($email)) {
            Session::flash('flash.error', 'Username y email son obligatorios.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            Session::flash('flash.error', 'El nombre de usuario debe tener entre 3 y 50 caracteres.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('flash.error', 'Email inválido.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        // Verificar usuario único (excluyendo el actual)
        $existingUser = User::findByUsername($username);
        if ($existingUser && (int)$existingUser['id'] !== $id) {
            Session::flash('flash.error', 'El nombre de usuario ya existe.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        // Verificar email único (excluyendo el actual)
        $existingEmail = User::findByEmail($email);
        if ($existingEmail && (int)$existingEmail['id'] !== $id) {
            Session::flash('flash.error', 'El email ya está registrado.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        try {
            User::update($id, [
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role,
                'is_active' => $isActive,
            ]);

            AuditService::log('user_updated', "Usuario actualizado: $username", 'users', $id);
            Session::flash('flash.success', 'Usuario actualizado correctamente.');
            Response::redirect(url("admin/users/$id/edit"));
        } catch (\Throwable $e) {
            error_log('[UsersController] Error actualizando usuario: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al actualizar usuario: ' . $e->getMessage());
            Response::redirect(url("admin/users/$id/edit"));
        }
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function changePassword(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('users.edit')) {
            Session::flash('flash.error', 'Sin permisos para cambiar contraseñas.');
            Response::redirect(url('admin/users'));
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('flash.error', 'ID de usuario inválido.');
            Response::redirect(url('admin/users'));
        }

        $user = User::find($id);
        if (!$user) {
            Session::flash('flash.error', 'Usuario no encontrado.');
            Response::redirect(url('admin/users'));
        }

        $password = $req->post('password', '');
        $passwordConfirm = $req->post('password_confirmation', '');

        if (empty($password)) {
            Session::flash('flash.error', 'La contraseña no puede estar vacía.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        if (strlen($password) < 8) {
            Session::flash('flash.error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        if ($password !== $passwordConfirm) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url("admin/users/$id/edit"));
        }

        try {
            User::update($id, ['password' => $password]);

            // Invalidar todas las sesiones del usuario
            try {
                Database::execute("DELETE FROM user_sessions WHERE user_id = ?", [$id]);
                User::clearRememberToken($id);
            } catch (\Throwable $e) {
                error_log('[UsersController] Error invalidando sesiones: ' . $e->getMessage());
            }

            AuditService::log('user_password_changed', "Contraseña cambiada para: {$user['username']}", 'users', $id);
            Session::flash('flash.success', 'Contraseña cambiada correctamente. Todas las sesiones del usuario han sido cerradas.');
            Response::redirect(url("admin/users/$id/edit"));
        } catch (\Throwable $e) {
            error_log('[UsersController] Error cambiando contraseña: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al cambiar contraseña: ' . $e->getMessage());
            Response::redirect(url("admin/users/$id/edit"));
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('users.delete')) {
            Session::flash('flash.error', 'Sin permisos para eliminar usuarios.');
            Response::redirect(url('admin/users'));
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('flash.error', 'ID de usuario inválido.');
            Response::redirect(url('admin/users'));
        }

        // No permitir eliminarse a sí mismo
        if ($id === Auth::id()) {
            Session::flash('flash.error', 'No puedes eliminar tu propio usuario.');
            Response::redirect(url('admin/users'));
        }

        $user = User::find($id);
        if (!$user) {
            Session::flash('flash.error', 'Usuario no encontrado.');
            Response::redirect(url('admin/users'));
        }

        try {
            User::delete($id);
            AuditService::log('user_deleted', "Usuario eliminado: {$user['username']}", 'users', $id);
            Session::flash('flash.success', 'Usuario eliminado correctamente.');
            Response::redirect(url('admin/users'));
        } catch (\Throwable $e) {
            error_log('[UsersController] Error eliminando usuario: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al eliminar usuario: ' . $e->getMessage());
            Response::redirect(url('admin/users'));
        }
    }
    /**
 * Busca usuarios para menciones @usuario (AJAX).
 */
public function search(Request $req): void
{
    $q = trim($req->get('q', ''));
    
    if (strlen($q) < 1) {
        Response::json(['success' => true, 'data' => []]);
        return;
    }
    
    try {
        $users = Database::fetchAll(
            "SELECT id, username, full_name, role 
             FROM users 
             WHERE is_active = 1 
               AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)
             ORDER BY username 
             LIMIT 10",
            ["%$q%", "%$q%", "%$q%"]
        );
        
        Response::json(['success' => true, 'data' => $users]);
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'data' => []]);
    }
}
}