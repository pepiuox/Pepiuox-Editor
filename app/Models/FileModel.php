<?php
namespace App\Models;

use App\Core\Database;

class FileModel
{
    private Database $db;
    private string $table = 'files';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO files (
                user_id, filename, original_name, file_path, file_size,
                file_type, mime_type, category, description, is_public,
                metadata, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['user_id'],
                $data['filename'],
                $data['original_name'],
                $data['file_path'],
                $data['file_size'],
                $data['file_type'],
                $data['mime_type'],
                $data['category'],
                $data['description'] ?? null,
                $data['is_public'] ?? 0,
                json_encode($data['metadata'] ?? [])
            ]
        );
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $params[] = $userId;

        return $this->db->execute(
            "UPDATE files SET " . implode(', ', $fields) . ", updated_at = NOW()
             WHERE id = ? AND user_id = ?",
            $params
        );
    }

    public function softDelete(int $id, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE files SET deleted_at = NOW() WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public function hardDelete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM files WHERE id = ?",
            [$id]
        );
    }

    public function incrementDownloads(int $id): bool
    {
        return $this->db->execute(
            "UPDATE files SET download_count = download_count + 1 WHERE id = ?",
            [$id]
        );
    }

/**
 * Actualiza metadata específica
 */
public function updateMetadata(int $id, int $userId, array $metadata): bool
{
    return $this->db->execute(
        "UPDATE files SET metadata = JSON_MERGE_PATCH(COALESCE(metadata, '{}'), ?), updated_at = NOW()
         WHERE id = ? AND user_id = ?",
        [json_encode($metadata), $id, $userId]
    );
}

/**
 * Obtiene archivos con filtro por metadata
 */
public function getByMetadata(string $key, $value, ?int $userId = null): array
{
    $query = "SELECT * FROM files 
              WHERE JSON_EXTRACT(metadata, '$." . $key . "') = ?
              AND deleted_at IS NULL";
    $params = [$value];

    if ($userId !== null) {
        $query .= " AND user_id = ?";
        $params[] = $userId;
    }

    return $this->db->fetchAll($query, $params);
}
}