<?php
namespace App\Models;

use App\Core\Database;

class MediaModel
{
    private Database $db;
    private string $table = 'media';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO media (
                user_id, filename, original_name, file_path,
                file_type, file_size, mime_type, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['user_id'],
                $data['filename'],
                $data['original_name'],
                $data['file_path'],
                $data['file_type'],
                $data['file_size'],
                $data['mime_type'] ?? null
            ]
        );
    }

    public function getUserMedia(int $userId, ?string $type = null): array
    {
        $query = "SELECT * FROM media WHERE user_id = ?";
        $params = [$userId];

        if ($type) {
            $query .= " AND file_type LIKE ?";
            $params[] = $type . '%';
        }

        $query .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($query, $params);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->execute(
            "DELETE FROM media WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
}