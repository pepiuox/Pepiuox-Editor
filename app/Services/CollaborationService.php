<?php
namespace App\Services;

use App\Core\{Database, Auth};

/**
 * Servicio de colaboración en tiempo real.
 * 
 * Arquitectura: Polling + BroadcastChannel
 * - Polling HTTP cada 2s para sincronizar entre usuarios
 * - BroadcastChannel para sincronizar entre pestañas del mismo navegador
 */
class CollaborationService
{
    public const HEARTBEAT_INTERVAL = 10;     // segundos
    public const SESSION_TIMEOUT = 30;        // segundos sin heartbeat = inactivo
    public const LOCK_DURATION = 60;          // segundos de duración del lock
    public const POLL_INTERVAL = 2000;        // ms (cliente)
    public const MAX_EVENTS_PER_POLL = 100;   // eventos máximos por poll

    /**
     * Unirse a una sesión de colaboración.
     */
    public static function join(int $pageId): array
    {
        $userId = (int)Auth::id();
        $token = bin2hex(random_bytes(16));

        // Upsert de sesión
        Database::execute(
            "INSERT INTO collaboration_sessions 
                (page_id, user_id, session_token, is_active, last_heartbeat) 
             VALUES (?, ?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE 
                session_token = VALUES(session_token),
                is_active = 1,
                last_heartbeat = NOW()",
            [$pageId, $userId, $token]
        );

        // Evento de join
        $sequence = self::nextSequence($pageId);
        $user = Auth::user();
        self::insertEvent($pageId, $userId, 'join', [
            'username' => $user['username'] ?? 'Usuario',
            'full_name' => $user['full_name'] ?? $user['username'],
            'avatar' => mb_substr($user['full_name'] ?? $user['username'], 0, 1),
        ], $sequence);

        // Limpiar sesiones expiradas
        self::cleanupInactiveSessions($pageId);

        return [
            'token' => $token,
            'collaborators' => self::getActiveCollaborators($pageId),
            'sequence' => $sequence,
        ];
    }

    /**
     * Salir de la sesión.
     */
    public static function leave(int $pageId): void
    {
        $userId = (int)Auth::id();

        // Evento de leave
        $sequence = self::nextSequence($pageId);
        $user = Auth::user();
        self::insertEvent($pageId, $userId, 'leave', [
            'username' => $user['username'] ?? 'Usuario',
        ], $sequence);

        // Liberar locks del usuario
        Database::execute(
            "DELETE FROM collaboration_locks WHERE page_id = ? AND user_id = ?",
            [$pageId, $userId]
        );

        // Marcar sesión como inactiva
        Database::execute(
            "UPDATE collaboration_sessions SET is_active = 0 WHERE page_id = ? AND user_id = ?",
            [$pageId, $userId]
        );
    }

    /**
     * Heartbeat (mantener sesión viva).
     */
    public static function heartbeat(int $pageId, array $cursorData = []): array
    {
        $userId = (int)Auth::id();

        Database::execute(
            "UPDATE collaboration_sessions 
                SET last_heartbeat = NOW(), 
                    cursor_position = ?, 
                    cursor_selector = ?
              WHERE page_id = ? AND user_id = ?",
            [
                $cursorData['position'] ?? 0,
                $cursorData['selector'] ?? null,
                $pageId,
                $userId,
            ]
        );

        // Limpiar sesiones expiradas
        self::cleanupInactiveSessions($pageId);

        return [
            'collaborators' => self::getActiveCollaborators($pageId),
            'locks' => self::getActiveLocks($pageId),
        ];
    }

    /**
     * Publicar un evento de cambio de contenido.
     */
    public static function publishContentChange(int $pageId, array $content): int
    {
        $userId = (int)Auth::id();
        $sequence = self::nextSequence($pageId);

        self::insertEvent($pageId, $userId, 'content_change', [
            'html' => $content['html'] ?? '',
            'css' => $content['css'] ?? '',
            'component' => $content['component'] ?? null,
            'action' => $content['action'] ?? 'update', // add, remove, update
        ], $sequence);

        return $sequence;
    }

    /**
     * Publicar movimiento de cursor.
     */
    public static function publishCursorMove(int $pageId, array $cursor): int
    {
        $userId = (int)Auth::id();
        $sequence = self::nextSequence($pageId);

        self::insertEvent($pageId, $userId, 'cursor_move', [
            'x' => $cursor['x'] ?? 0,
            'y' => $cursor['y'] ?? 0,
            'selector' => $cursor['selector'] ?? null,
        ], $sequence);

        return $sequence;
    }

    /**
     * Obtener eventos nuevos desde una secuencia.
     */
    public static function getEventsSince(int $pageId, int $sinceSequence, int $limit = self::MAX_EVENTS_PER_POLL): array
    {
        $events = Database::fetchAll(
            "SELECT e.*, u.username, u.full_name
               FROM collaboration_events e
               JOIN users u ON u.id = e.user_id
              WHERE e.page_id = ? AND e.sequence > ?
              ORDER BY e.sequence ASC
              LIMIT ?",
            [$pageId, $sinceSequence, $limit]
        );

        // Decodificar payload JSON
        foreach ($events as &$e) {
            $e['payload'] = json_decode($e['payload'], true) ?: [];
        }

        return $events;
    }

    /**
     * Adquirir lock sobre un selector (componente).
     */
    public static function acquireLock(int $pageId, string $selector): array
    {
        $userId = (int)Auth::id();
        $expiresAt = date('Y-m-d H:i:s', time() + self::LOCK_DURATION);

        // Verificar si ya está bloqueado por otro usuario
        $existing = Database::fetch(
            "SELECT * FROM collaboration_locks 
              WHERE page_id = ? AND selector = ? AND expires_at > NOW() AND user_id <> ?",
            [$pageId, $selector, $userId]
        );

        if ($existing) {
            $owner = Database::fetch("SELECT username, full_name FROM users WHERE id = ?", [$existing['user_id']]);
            return [
                'success' => false,
                'locked_by' => $owner['username'] ?? 'Usuario',
                'locked_by_name' => $owner['full_name'] ?? $owner['username'],
                'expires_at' => $existing['expires_at'],
            ];
        }

        // Upsert del lock
        Database::execute(
            "INSERT INTO collaboration_locks (page_id, user_id, selector, expires_at) 
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                expires_at = VALUES(expires_at)",
            [$pageId, $userId, $selector, $expiresAt]
        );

        // Evento
        $sequence = self::nextSequence($pageId);
        self::insertEvent($pageId, $userId, 'lock_acquire', [
            'selector' => $selector,
        ], $sequence);

        return ['success' => true, 'expires_at' => $expiresAt];
    }

    /**
     * Liberar lock.
     */
    public static function releaseLock(int $pageId, string $selector): void
    {
        $userId = (int)Auth::id();
        Database::execute(
            "DELETE FROM collaboration_locks WHERE page_id = ? AND user_id = ? AND selector = ?",
            [$pageId, $userId, $selector]
        );

        $sequence = self::nextSequence($pageId);
        self::insertEvent($pageId, $userId, 'lock_release', [
            'selector' => $selector,
        ], $sequence);
    }

    /**
     * Enviar mensaje de chat.
     */
    public static function sendMessage(int $pageId, string $message): int
    {
        $userId = (int)Auth::id();
        $message = trim(mb_substr($message, 0, 500));

        $id = Database::insert(
            "INSERT INTO collaboration_messages (page_id, user_id, message) VALUES (?, ?, ?)",
            [$pageId, $userId, $message]
        );

        $sequence = self::nextSequence($pageId);
        $user = Auth::user();
        self::insertEvent($pageId, $userId, 'chat', [
            'message_id' => $id,
            'message' => $message,
            'username' => $user['username'] ?? 'Usuario',
        ], $sequence);

        return $id;
    }

    /**
     * Obtener colaboradores activos.
     */
    public static function getActiveCollaborators(int $pageId): array
    {
        $currentUserId = (int)Auth::id();
        $collaborators = Database::fetchAll(
            "SELECT s.user_id, s.session_token, s.cursor_position, s.cursor_selector,
                    s.last_heartbeat, u.username, u.full_name
               FROM collaboration_sessions s
               JOIN users u ON u.id = s.user_id
              WHERE s.page_id = ? 
                AND s.is_active = 1 
                AND s.last_heartbeat > DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND s.user_id <> ?
              ORDER BY s.last_heartbeat DESC",
            [$pageId, self::SESSION_TIMEOUT, $currentUserId]
        );

        // Asignar colores únicos a cada colaborador
        $colors = ['#ef4444', '#f59e0b', '#22c55e', '#3b82f6', '#a855f7', '#ec4899'];
        foreach ($collaborators as $i => &$c) {
            $c['color'] = $colors[$i % count($colors)];
            $c['avatar'] = mb_substr($c['full_name'] ?? $c['username'], 0, 1);
            $c['is_current'] = false;
        }

        return $collaborators;
    }

    /**
     * Obtener locks activos.
     */
    public static function getActiveLocks(int $pageId): array
    {
        $locks = Database::fetchAll(
            "SELECT l.*, u.username, u.full_name
               FROM collaboration_locks l
               JOIN users u ON u.id = l.user_id
              WHERE l.page_id = ? AND l.expires_at > NOW()",
            [$pageId]
        );

        foreach ($locks as &$l) {
            $l['avatar'] = mb_substr($l['full_name'] ?? $l['username'], 0, 1);
        }

        return $locks;
    }

    /**
     * Obtener mensajes recientes del chat.
     */
    public static function getRecentMessages(int $pageId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT m.*, u.username, u.full_name
               FROM collaboration_messages m
               JOIN users u ON u.id = m.user_id
              WHERE m.page_id = ?
              ORDER BY m.created_at DESC
              LIMIT ?",
            [$pageId, $limit]
        );
    }

    /**
     * Estadísticas de la sesión.
     */
    public static function getSessionStats(int $pageId): array
    {
        return [
            'active_collaborators' => count(self::getActiveCollaborators($pageId)),
            'active_locks' => count(self::getActiveLocks($pageId)),
            'total_events_today' => (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM collaboration_events 
                  WHERE page_id = ? AND created_at >= CURDATE()",
                [$pageId]
            )['c'],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    private static function nextSequence(int $pageId): int
    {
        // Usar timestamp en microsegundos + random para garantizar unicidad
        return (int)(microtime(true) * 10000);
    }

    private static function insertEvent(int $pageId, int $userId, string $type, array $payload, int $sequence): void
    {
        Database::insert(
            "INSERT INTO collaboration_events 
                (page_id, user_id, event_type, payload, sequence) 
             VALUES (?, ?, ?, ?, ?)",
            [$pageId, $userId, $type, json_encode($payload, JSON_UNESCAPED_UNICODE), $sequence]
        );
    }

    private static function cleanupInactiveSessions(int $pageId): void
    {
        // Marcar como inactivas las sesiones sin heartbeat
        Database::execute(
            "UPDATE collaboration_sessions 
                SET is_active = 0 
              WHERE page_id = ? 
                AND last_heartbeat < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$pageId, self::SESSION_TIMEOUT]
        );

        // Liberar locks expirados
        Database::execute(
            "DELETE FROM collaboration_locks WHERE expires_at < NOW()"
        );

        // Limpiar eventos antiguos (> 24h)
        Database::execute(
            "DELETE FROM collaboration_events 
              WHERE page_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$pageId]
        );
    }
}