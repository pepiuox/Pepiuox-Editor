<?php
namespace App\Models;
use App\Core\Database;

class Role
{
    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM users_roles ORDER BY idRol ASC");
    }
    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM users_roles WHERE idRol = ?", [$id]);
    }
    public static function create(array $data): int
    {
        if (!empty($data['default_role'])) {
            Database::execute("UPDATE users_roles SET default_role = 0");
        }
        return Database::insert(
            "INSERT INTO users_roles (name, description, required, default_role) VALUES (?,?,?,?)",
            [$data['name'], $data['description'] ?? '', $data['required'] ?? 0, $data['default_role'] ?? 0]
        );
    }
    public static function update(int $id, array $data): int
    {
        if (!empty($data['default_role'])) {
            Database::execute("UPDATE users_roles SET default_role = 0");
        }
        $fields = []; $params = [];
        foreach (['name','description','required','default_role'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "`$f` = ?"; $params[] = $data[$f]; }
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE users_roles SET " . implode(',', $fields) . " WHERE idRol = ?", $params);
    }
    public static function delete(int $id): int
    {
        $role = self::find($id);
        if (!$role || $role['required']) return 0;
        // Reasignar usuarios a rol por defecto
        $default = Database::fetch("SELECT idRol FROM users_roles WHERE default_role = 1 LIMIT 1");
        if ($default) {
            Database::execute("UPDATE users SET role_id = ? WHERE role_id = ?", [$default['idRol'], $id]);
        }
        Database::execute("DELETE FROM users_role_permissions WHERE role_id = ?", [$id]);
        return Database::execute("DELETE FROM users_roles WHERE idRol = ? AND required = 0", [$id]);
    }
    /** Usuarios con este rol */
    public static function usersCount(int $id): int
    {
        return (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE role_id = ?", [$id])['c'];
    }
}