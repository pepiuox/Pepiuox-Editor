<?php
namespace App\Services;

use App\Core\{Database, Auth};
use App\Models\{ComponentComment, User};

class CommentService
{
    /**
     * Crea un comentario y procesa menciones.
     */
    public static function createComment(int $pageId, array $data): int
    {
        $userId = (int)Auth::id();
        $data['user_id'] = $userId;
        $data['page_id'] = $pageId;

        $commentId = ComponentComment::create($data);

        // Procesar menciones @usuario
        self::processMentions($data['message'], $commentId, null);

        // Notificar al autor de la página si no es el comentarista
        $page = Database::fetch("SELECT user_id FROM pages WHERE id = ?", [$pageId]);
        if ($page && (int)$page['user_id'] !== $userId) {
            NotificationService::notify(
                (int)$page['user_id'],
                'info',
                '💬 Nuevo comentario',
                self::truncate($data['message'], 80),
                url("admin/pages/{$pageId}/builder#comment-{$commentId}"),
                'chat-dots'
            );
        }

        AuditService::log(
            'comment_created',
            "Comentario creado en página ID $pageId",
            'comments',
            $commentId
        );

        return $commentId;
    }

    /**
     * Añade una respuesta y procesa menciones.
     */
    public static function addReply(int $commentId, string $message): int
    {
        $userId = (int)Auth::id();
        $replyId = ComponentComment::addReply($commentId, $userId, $message);

        // Procesar menciones
        self::processMentions($message, $commentId, $replyId);

        // Notificar al autor del comentario original
        $comment = Database::fetch(
            "SELECT user_id, page_id FROM component_comments WHERE id = ?",
            [$commentId]
        );
        if ($comment && (int)$comment['user_id'] !== $userId) {
            NotificationService::notify(
                (int)$comment['user_id'],
                'info',
                '💬 Nueva respuesta a tu comentario',
                self::truncate($message, 80),
                url("admin/pages/{$comment['page_id']}/builder#comment-{$commentId}"),
                'chat-left-text'
            );
        }

        return $replyId;
    }

    /**
     * Resuelve un comentario.
     */
    public static function resolve(int $commentId): bool
    {
        $userId = (int)Auth::id();
        $success = ComponentComment::resolve($commentId, $userId);

        if ($success) {
            AuditService::log('comment_resolved', "Comentario #$commentId resuelto", 'comments', $commentId);
        }

        return $success;
    }

    /**
     * Reabre un comentario.
     */
    public static function reopen(int $commentId): bool
    {
        $success = ComponentComment::reopen($commentId);
        if ($success) {
            AuditService::log('comment_reopened', "Comentario #$commentId reabierto", 'comments', $commentId);
        }
        return $success;
    }

    /**
     * Elimina un comentario.
     */
    public static function delete(int $commentId): bool
    {
        $comment = ComponentComment::find($commentId);
        if (!$comment) return false;

        // Solo el autor o admin puede eliminar
        $userId = (int)Auth::id();
        if ((int)$comment['user_id'] !== $userId && !Auth::can('comments.delete')) {
            return false;
        }

        $success = ComponentComment::delete($commentId);
        if ($success) {
            AuditService::log('comment_deleted', "Comentario #$commentId eliminado", 'comments', $commentId);
        }
        return $success;
    }

    /**
     * Obtiene comentarios pendientes del usuario actual (menciones).
     */
    public static function getMentionsForUser(int $userId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT c.*, p.title AS page_title, p.slug AS page_slug,
                    u.username AS author_username,
                    (SELECT COUNT(*) FROM comment_replies WHERE comment_id = c.id) AS replies_count
             FROM comment_mentions m
             JOIN component_comments c ON c.id = m.comment_id
             JOIN pages p ON p.id = c.page_id
             JOIN users u ON u.id = c.user_id
             WHERE m.mentioned_user_id = ? AND c.status = 'open'
             ORDER BY m.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Procesa menciones @usuario en un mensaje.
     */
    private static function processMentions(string $message, int $commentId, ?int $replyId): void
    {
        // Buscar @username en el mensaje
        preg_match_all('/@([a-zA-Z0-9_]+)/', $message, $matches);
        
        if (empty($matches[1])) return;

        $currentUserId = (int)Auth::id();

        foreach (array_unique($matches[1]) as $username) {
            $user = User::findByUsername($username);
            if (!$user || (int)$user['id'] === $currentUserId) continue;

            try {
                Database::insert(
                    "INSERT INTO comment_mentions (comment_id, reply_id, mentioned_user_id) VALUES (?, ?, ?)",
                    [$commentId, $replyId, (int)$user['id']]
                );

                // Notificar al usuario mencionado
                $author = Auth::user();
                NotificationService::notify(
                    (int)$user['id'],
                    'info',
                    '👋 Te mencionaron en un comentario',
                    '@' . ($author['username'] ?? 'alguien') . ': ' . self::truncate($message, 80),
                    url("admin/profile/mentions"),
                    'at'
                );
            } catch (\Throwable $e) {
                error_log('[CommentService] Error procesando mención: ' . $e->getMessage());
            }
        }
    }

    /**
     * Trunca un mensaje para notificaciones.
     */
    private static function truncate(string $text, int $length): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length - 3) . '...';
    }
}