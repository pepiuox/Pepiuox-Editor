<?php
namespace App\Models;
use App\Core\Database;

class Permission
{
    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM users_permissions ORDER BY category, name");
    }
    public static function byRole(int $roleId): array
    {
        $rows = Database::fetchAll(
            "SELECT p.idPer FROM users_role_permissions rp JOIN users_permissions p ON p.idPer = rp.permission_id WHERE rp.role_id = ?",
            [$roleId]
        );
        return array_column($rows, 'idPer');
    }
    public static function syncRole(int $roleId, array $permissionIds): void
    {
        Database::execute("DELETE FROM users_role_permissions WHERE role_id = ?", [$roleId]);
        foreach ($permissionIds as $pid) {
            Database::insert(
                "INSERT INTO users_role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, (int)$pid]
            );
        }
    }
}