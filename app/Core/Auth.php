<?php
namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return \App\Models\User::find((int)Session::get('user_id'));
    }

    public static function id(): ?int
    {
        return Session::has('user_id') ? (int)Session::get('user_id') : null;
    }

    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    /**
     * Verifica si el usuario tiene un permiso.
     * 
     * REGLAS:
     * 1. Super Admin tiene TODOS los permisos automáticamente
     * 2. Si no hay sesión → false
     * 3. Busca en users_role_permissions + users_permissions
     * 4. Si el permiso no existe en la BD → false (no falla)
     */
    public static function can(string $permission): bool
    {
        if (!self::check()) return false;

        $user = self::user();
        if (!$user) return false;

        // ═══ SUPER ADMIN BYPASS ═══
        // Los Super Admin tienen todos los permisos sin consultar la BD
        if (($user['role'] ?? '') === 'Super admin') {
            return true;
        }

        // ═══ BUSCAR PERMISO EN LA BD ═══
        try {
            // Obtener el role_id del usuario desde users_roles
            $roleRow = Database::fetch(
                "SELECT idRol FROM users_roles WHERE name = ?",
                [$user['role']]
            );

            if (!$roleRow) return false;

            // Buscar si el rol tiene el permiso
            $result = Database::fetch(
                "SELECT 1 
                   FROM users_role_permissions rp
                   JOIN users_permissions p ON p.idPer = rp.permission_id
                  WHERE rp.role_id = ? AND p.name = ?
                  LIMIT 1",
                [(int)$roleRow['idRol'], $permission]
            );

            return (bool)$result;
        } catch (\Throwable $e) {
            error_log('[Auth::can] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si el usuario tiene alguno de los permisos listados.
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (self::can($p)) return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $uid = self::id();
        if ($uid) {
            \App\Models\User::clearRememberToken($uid);
        }
        Session::destroy();
    }
}