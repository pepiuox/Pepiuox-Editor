<?php
namespace App\Services;

use App\Core\{Database, Auth};

class AuditService
{
    public static function log(
        string $action, 
        string $description, 
        ?string $entity = null, 
        ?int $entityId = null, 
        ?array $newData = null, 
        ?array $oldData = null
    ): void {
        try {
            Database::insert(
                "INSERT INTO activity_log 
                    (user_id, action, description, ip_address, user_agent, 
                     request_method, request_url, old_data, new_data)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    Auth::id(),
                    $action,
                    $description,
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['REQUEST_METHOD'] ?? '',
                    $_SERVER['REQUEST_URI'] ?? '',
                    $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                    $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                ]
            );
        } catch (\Throwable $e) {
            error_log('[AuditService] ' . $e->getMessage());
        }
    }
}