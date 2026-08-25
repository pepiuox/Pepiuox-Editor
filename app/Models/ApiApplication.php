<?php
namespace App\Models;
use App\Core\Database;

class ApiApplication
{
    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM api_applications ORDER BY created_at DESC");
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM api_applications WHERE id = ?", [$id]);
    }

    public static function findByApiKey(string $key): ?array
    {
        return Database::fetch(
            "SELECT * FROM api_applications WHERE api_key = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())",
            [$key]
        );
    }

    public static function create(array $data): array
    {
        $appKey = 'app_' . bin2hex(random_bytes(16));
        $appSecret = bin2hex(random_bytes(32));
        $apiKey = 'pk_' . bin2hex(random_bytes(24));
        $apiSecret = bin2hex(random_bytes(32));

        $id = Database::insert(
            "INSERT INTO api_applications (app_name, app_key, app_secret, api_key, api_secret, description, allowed_ips, rate_limit, permissions, status, expires_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $data['app_name'],
                $appKey,
                hash('sha256', $appSecret),
                $apiKey,
                hash('sha256', $apiSecret),
                $data['description'] ?? null,
                !empty($data['allowed_ips']) ? json_encode(array_filter(array_map('trim', explode(',', $data['allowed_ips'])))) : null,
                $data['rate_limit'] ?? 100,
                !empty($data['permissions']) ? json_encode($data['permissions']) : null,
                $data['status'] ?? 'active',
                $data['expires_at'] ?? null,
            ]
        );
        return [
            'id' => $id,
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ];
    }

    public static function update(int $id, array $data): int
    {
        $fields = []; $params = [];
        foreach (['app_name','description','rate_limit','status','expires_at'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "`$f` = ?"; $params[] = $data[$f]; }
        }
        if (isset($data['allowed_ips'])) {
            $fields[] = "allowed_ips = ?";
            $params[] = !empty($data['allowed_ips']) ? json_encode(array_filter(array_map('trim', explode(',', $data['allowed_ips'])))) : null;
        }
        if (isset($data['permissions'])) {
            $fields[] = "permissions = ?";
            $params[] = json_encode($data['permissions']);
        }
        if (!$fields) return 0;
        $params[] = $id;
        return Database::execute("UPDATE api_applications SET " . implode(',', $fields) . " WHERE id = ?", $params);
    }

    public static function delete(int $id): int
    {
        return Database::execute("DELETE FROM api_applications WHERE id = ?", [$id]);
    }

    public static function regenerateKeys(int $id): array
    {
        $apiKey = 'pk_' . bin2hex(random_bytes(24));
        $apiSecret = bin2hex(random_bytes(32));
        Database::execute(
            "UPDATE api_applications SET api_key = ?, api_secret = ? WHERE id = ?",
            [$apiKey, hash('sha256', $apiSecret), $id]
        );
        return ['api_key' => $apiKey, 'api_secret' => $apiSecret];
    }
}