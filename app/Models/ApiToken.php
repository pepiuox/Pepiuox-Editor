<?php
namespace App\Models;
use App\Core\Database;

class ApiToken
{
    public static function create(int $applicationId, array $scopes = [], int $expiresIn = 3600): array
    {
        $token = 'tok_' . bin2hex(random_bytes(32));
        $refresh = 'ref_' . bin2hex(random_bytes(32));
        Database::insert(
            "INSERT INTO api_tokens (application_id, token, refresh_token, token_type, scopes, expires_at, refresh_expires_at)
             VALUES (?,?,?,?,?,?,?)",
            [
                $applicationId,
                hash('sha256', $token),
                hash('sha256', $refresh),
                'bearer',
                json_encode($scopes),
                date('Y-m-d H:i:s', time() + $expiresIn),
                date('Y-m-d H:i:s', time() + $expiresIn * 30),
            ]
        );
        return ['token' => $token, 'refresh_token' => $refresh, 'expires_in' => $expiresIn];
    }

    public static function validate(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $row = Database::fetch(
            "SELECT t.*, a.app_name, a.permissions AS app_permissions, a.rate_limit, a.allowed_ips
               FROM api_tokens t
               JOIN api_applications a ON a.id = t.application_id
              WHERE t.token = ? AND t.revoked = 0 AND t.expires_at > NOW() AND a.status = 'active'",
            [$hash]
        );
        return $row ?: null;
    }

    public static function revoke(int $id): int
    {
        return Database::execute("UPDATE api_tokens SET revoked = 1 WHERE id = ?", [$id]);
    }

    public static function revokeByToken(string $token): int
    {
        return Database::execute("UPDATE api_tokens SET revoked = 1 WHERE token = ?", [hash('sha256', $token)]);
    }

    public static function forApplication(int $appId): array
    {
        return Database::fetchAll(
            "SELECT * FROM api_tokens WHERE application_id = ? ORDER BY created_at DESC",
            [$appId]
        );
    }
}