<?php
namespace App\Models;
use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public static function all(): array
    {
        return Database::fetchAll("SELECT id, username, email, full_name, role, role_id, is_active, created_at, last_login FROM users ORDER BY id ASC");
    }

    public static function create(array $data): int
    {
        $cfg = require __DIR__ . '/../../config/config.php';
        $hash = password_hash($data['password'], $cfg['password']['algo'], $cfg['password']['options']);
        return Database::insert(
            "INSERT INTO users (username, email, password_hash, full_name, role, role_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['username'], $data['email'], $hash,
                $data['full_name'] ?? null,
                $data['role'] ?? 'Guest',
                $data['role_id'] ?? null,
                $data['is_active'] ?? 1,
            ]
        );
    }

    public static function update(int $id, array $data): bool
{
    $fields = [];
    $params = [];

    foreach (['username', 'email', 'full_name', 'role', 'is_active'] as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    // Manejar contraseña si se proporciona
    if (!empty($data['password'])) {
        $fields[] = "password_hash = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    if (empty($fields)) {
        return false;
    }

    $params[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

    try {
        return Database::execute($sql, $params) > 0;
    } catch (\Throwable $e) {
        error_log('[User] Error updating user: ' . $e->getMessage());
        return false;
    }
}

    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    public static function updateLastLogin(int $id): void
    {
        Database::execute(
            "UPDATE users SET last_login = NOW(), login_count = login_count + 1, last_ip = ? WHERE id = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $id]
        );
        Database::insert(
            "INSERT INTO user_logins (user_id, login_date, login_time, ip_address, user_agent, login_success)
             VALUES (?, CURDATE(), CURTIME(), ?, ?, 1)",
            [$id, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    }

    public static function setRememberToken(int $id, string $token, int $days): void
    {
        Database::execute(
            "UPDATE users SET remember_token = ?, remember_expires = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?",
            [$token, $days, $id]
        );
    }

    public static function clearRememberToken(int $id): void
    {
        Database::execute("UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?", [$id]);
    }

    /** Devuelve true si el usuario tiene el permiso indicado (por nombre). */
    public static function hasPermission(int $userId, string $permissionName): bool
    {
        $row = Database::fetch(
            "SELECT 1
               FROM users u
               JOIN users_role_permissions rp ON rp.role_id = u.role_id
               JOIN users_permissions p ON p.idPer = rp.permission_id
              WHERE u.id = ? AND p.name = ?
              LIMIT 1",
            [$userId, $permissionName]
        );
        return (bool)$row;
    }

    /** Permisos del usuario (array de nombres). */
    public static function permissions(int $userId): array
    {
        $user = self::find($userId);    
        if (!$user) return [];
        if ($user['role'] === 'Super admin') {
            
            $all = Database::fetchAll("SELECT name FROM users_permissions");
            return array_column($all, 'name');
            
           /* return Database::fetchAll("SELECT name FROM users_permissions") |>
                fn($r) => array_column($r, 'name');*/
        }
        $rows = Database::fetchAll(
            "SELECT p.name
               FROM users_role_permissions rp
               JOIN users_permissions p ON p.idPer = rp.permission_id
              WHERE rp.role_id = ?",
            [$user['role_id']]
        );
        return array_column($rows, 'name');
    }
}