<?php
namespace App\Models;

use App\Core\Database;

class ComponentComment
{
    /**
     * Obtiene todos los comentarios de una página.
     */
    public static function forPage(int $pageId, string $status = 'all'): array
    {
        $sql = "SELECT c.*, 
                       u.username, u.full_name,
                       r.username AS resolved_by_username,
                       (SELECT COUNT(*) FROM comment_replies WHERE comment_id = c.id) AS replies_count,
                       (SELECT MAX(created_at) FROM comment_replies WHERE comment_id = c.id) AS last_reply_at
                FROM component_comments c
                JOIN users u ON u.id = c.user_id
                LEFT JOIN users r ON r.id = c.resolved_by
                WHERE c.page_id = ?";
        $params = [$pageId];

        if ($status !== 'all') {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Obtiene un comentario con sus respuestas.
     */
    public static function find(int $id): ?array
    {
        $comment = Database::fetch(
            "SELECT c.*, u.username, u.full_name
             FROM component_comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ?",
            [$id]
        );

        if (!$comment) return null;

        // Cargar respuestas
        $comment['replies'] = Database::fetchAll(
            "SELECT r.*, u.username, u.full_name
             FROM comment_replies r
             JOIN users u ON u.id = r.user_id
             WHERE r.comment_id = ?
             ORDER BY r.created_at ASC",
            [$id]
        );

        return $comment;
    }

    /**
     * Cuenta comentarios por estado en una página.
     */
    public static function countByStatus(int $pageId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS count 
             FROM component_comments 
             WHERE page_id = ? 
             GROUP BY status",
            [$pageId]
        );

        $counts = ['open' => 0, 'resolved' => 0, 'closed' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['count'];
            $counts['total'] += (int)$r['count'];
        }
        return $counts;
    }

    /**
     * Crea un comentario nuevo.
     */
    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO component_comments 
                (page_id, user_id, component_selector, component_id, 
                 position_x, position_y, message, status, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['page_id'],
                $data['user_id'],
                $data['component_selector'] ?? null,
                $data['component_id'] ?? null,
                $data['position_x'] ?? 0,
                $data['position_y'] ?? 0,
                $data['message'],
                $data['status'] ?? 'open',
                $data['priority'] ?? 'medium',
            ]
        );
    }

    /**
     * Añade una respuesta a un comentario.
     */
    public static function addReply(int $commentId, int $userId, string $message): int
    {
        return Database::insert(
            "INSERT INTO comment_replies (comment_id, user_id, message) VALUES (?, ?, ?)",
            [$commentId, $userId, $message]
        );
    }

    /**
     * Resuelve un comentario.
     */
    public static function resolve(int $commentId, int $userId): bool
    {
        return Database::execute(
            "UPDATE component_comments 
             SET status = 'resolved', resolved_at = NOW(), resolved_by = ?
             WHERE id = ?",
            [$userId, $commentId]
        ) > 0;
    }

    /**
     * Reabre un comentario resuelto.
     */
    public static function reopen(int $commentId): bool
    {
        return Database::execute(
            "UPDATE component_comments 
             SET status = 'open', resolved_at = NULL, resolved_by = NULL
             WHERE id = ?",
            [$commentId]
        ) > 0;
    }

    /**
     * Elimina un comentario.
     */
    public static function delete(int $commentId): bool
    {
        return Database::execute(
            "DELETE FROM component_comments WHERE id = ?",
            [$commentId]
        ) > 0;
    }

    /**
     * Actualiza la posición de un comentario (al mover el pin).
     */
    public static function updatePosition(int $commentId, float $x, float $y): bool
    {
        return Database::execute(
            "UPDATE component_comments SET position_x = ?, position_y = ? WHERE id = ?",
            [$x, $y, $commentId]
        ) > 0;
    }
}