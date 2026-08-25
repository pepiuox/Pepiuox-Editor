<?php
namespace App\Services;

use App\Core\{Database, Auth};

class FileService
{
    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM files WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public static function all(?string $category = null, ?int $userId = null): array
    {
        $sql = "SELECT * FROM files WHERE deleted_at IS NULL";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function delete(int $id): bool
    {
        $f = self::find($id);
        if (!$f) return false;
        
        // CORREGIDO: Ruta directa a uploads/ raíz
        $path = UPLOADS_PATH . '/' . $f['file_path'];
        if (is_file($path)) {
            @unlink($path);
        }
        
        return Database::execute("UPDATE files SET deleted_at = NOW() WHERE id = ?", [$id]) > 0;
    }

    public static function share(int $fileId, int $sharedWith, string $level = 'view', ?int $days = null): int
    {
        $token = bin2hex(random_bytes(16));
        return Database::insert(
            "INSERT INTO file_shares (file_id, shared_by, shared_with, share_token, access_level, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $fileId,
                Auth::id(),
                $sharedWith,
                $token,
                $level,
                $days ? date('Y-m-d H:i:s', time() + $days * 86400) : null,
            ]
        );
    }

    public static function sharesOf(int $fileId): array
    {
        return Database::fetchAll(
            "SELECT s.*, u.username, u.email 
               FROM file_shares s 
               LEFT JOIN users u ON u.id = s.shared_with 
              WHERE s.file_id = ? 
              ORDER BY s.created_at DESC",
            [$fileId]
        );
    }
}