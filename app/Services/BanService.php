<?php
namespace App\Services;

use App\Core\{Database, Auth};
use App\Models\User;

class BanService
{
    /**
     * Banea un usuario.
     * 
     * @param int $userId ID del usuario a banear
     * @param string $reason Motivo del baneo
     * @param int|null $duration Duración en días (null = permanente)
     * @param string|null $notes Notas adicionales
     * @param bool $notifyEmail Enviar email al usuario
     * @return bool
     */
    public static function ban(
        int $userId, 
        string $reason, 
        ?int $duration = null,
        ?string $notes = null,
        bool $notifyEmail = false
    ): bool {
        $user = User::find($userId);
        if (!$user) return false;

        // No permitir banear Super Admin (excepto si eres Super Admin)
        $currentUser = Auth::user();
        if ($user['role'] === 'Super admin' && ($currentUser['role'] ?? '') !== 'Super admin') {
            return false;
        }

        // No permitir autobaneo
        if ($userId === (int)Auth::id()) {
            return false;
        }

        $expiresAt = $duration ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;
        $bannedBy = Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        Database::begin();
        try {
            // Actualizar usuario
            Database::execute(
                "UPDATE users SET 
                    is_banned = 1, 
                    banned_reason = ?, 
                    banned_by = ?, 
                    banned_at = NOW(),
                    ban_expires_at = ?,
                    is_active = 0
                 WHERE id = ?",
                [$reason, $bannedBy, $expiresAt, $userId]
            );

            // Registrar en historial
            Database::insert(
                "INSERT INTO ban_history (user_id, action, reason, duration_days, expires_at, performed_by, ip_address, user_agent, notes)
                 VALUES (?, 'banned', ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $reason, $duration, $expiresAt, $bannedBy, $ip, $ua, $notes]
            );

            // Cerrar todas las sesiones activas del usuario
            Database::execute("DELETE FROM user_sessions WHERE user_id = ?", [$userId]);

            // Invalidar remember token
            User::clearRememberToken($userId);

            // Auditoría
            AuditService::log(
                Auth::id(), 'user_banned',
                "Usuario baneado: {$user['username']}. Motivo: $reason" . ($duration ? " ({$duration} días)" : " (permanente)"),
                'users',
                $userId,
                ['reason' => $reason, 'duration' => $duration, 'expires_at' => $expiresAt]
            );

            // Notificar al admin que realizó la acción
            if ($bannedBy && $bannedBy !== $userId) {
                NotificationService::notify(
                    (int)$bannedBy,
                    'warning',
                    'Usuario baneado',
                    "Has baneado a {$user['username']}" . ($duration ? " por $duration días" : " permanentemente") . ".",
                    url("admin/users/banned"),
                    'person-x'
                );
            }

            // Notificar al usuario por email si se solicitó
            if ($notifyEmail && !empty($user['email'])) {
                \App\Services\EmailNotificationService::notifyUserBanned($user, $reason, $duration, $expiresAt);
            }           

            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[BanService] Error al banear: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desbanea un usuario.
     */
    public static function unban(int $userId, ?string $notes = null, bool $reactivate = true): bool
    {
        $user = User::find($userId);
        if (!$user || !(int)$user['is_banned']) return false;

        $unbannedBy = Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        Database::begin();
        try {
            Database::execute(
                "UPDATE users SET 
                    is_banned = 0, 
                    banned_reason = NULL, 
                    banned_by = NULL, 
                    banned_at = NULL,
                    ban_expires_at = NULL,
                    is_active = ?
                 WHERE id = ?",
                [$reactivate ? 1 : 0, $userId]
            );

            Database::insert(
                "INSERT INTO ban_history (user_id, action, previous_reason, performed_by, ip_address, user_agent, notes)
                 VALUES (?, 'unbanned', ?, ?, ?, ?, ?)",
                [$userId, $user['banned_reason'], $unbannedBy, $ip, $ua, $notes]
            );

            AuditService::log(
                Auth::id(), 'user_unbanned',
                "Usuario desbaneado: {$user['username']}",
                'users',
                $userId
            );

            if ($unbannedBy) {
                NotificationService::notify(
                    (int)$unbannedBy,
                    'success',
                    'Usuario desbaneado',
                    "Has desbaneado a {$user['username']}.",
                    url("admin/users"),
                    'person-check'
                );
            }

            // Notificar al usuario por email
             if (!empty($user['email'])) {
                \App\Services\EmailNotificationService::notifyUserUnbanned($user);
            }

            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log('[BanService] Error al desbanear: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extiende la duración de un baneo existente.
     */
    public static function extend(int $userId, int $additionalDays, ?string $notes = null): bool
    {
        $user = User::find($userId);
        if (!$user || !(int)$user['is_banned']) return false;

        $currentExpires = $user['ban_expires_at'] ? strtotime($user['ban_expires_at']) : time();
        $newExpires = date('Y-m-d H:i:s', $currentExpires + ($additionalDays * 86400));

        Database::execute(
            "UPDATE users SET ban_expires_at = ? WHERE id = ?",
            [$newExpires, $userId]
        );

        Database::insert(
            "INSERT INTO ban_history (user_id, action, reason, duration_days, expires_at, performed_by, notes)
             VALUES (?, 'extended', CONCAT('Extendido +{$additionalDays} días'), ?, ?, ?, ?)",
            [$userId, $additionalDays, $newExpires, Auth::id(), $notes]
        );

        AuditService::log(Auth::id(), 'ban_extended', "Baneo extendido +{$additionalDays} días para {$user['username']}", 'users', $userId);
        return true;
    }

    /**
     * Obtiene usuarios baneados con filtros.
     */
    public static function getBannedUsers(array $filters = []): array
    {
        $where = ["u.is_banned = 1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.banned_reason LIKE ?)";
            $s = "%{$filters['search']}%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($filters['permanent'])) {
            $where[] = "u.ban_expires_at IS NULL";
        }
        if (!empty($filters['temporary'])) {
            $where[] = "u.ban_expires_at IS NOT NULL";
        }
        if (!empty($filters['expired'])) {
            $where[] = "u.ban_expires_at IS NOT NULL AND u.ban_expires_at < NOW()";
        }

        $whereSql = implode(' AND ', $where);
        return Database::fetchAll(
            "SELECT u.*, 
                    banner.username AS banned_by_name,
                    CASE 
                        WHEN u.ban_expires_at IS NULL THEN 'permanent'
                        WHEN u.ban_expires_at < NOW() THEN 'expired'
                        ELSE 'active'
                    END AS ban_status
               FROM users u
               LEFT JOIN users banner ON banner.id = u.banned_by
              WHERE $whereSql
              ORDER BY u.banned_at DESC",
            $params
        );
    }

    /**
     * Historial de baneos de un usuario.
     */
    public static function getHistory(int $userId): array
    {
        return Database::fetchAll(
            "SELECT h.*, u.username AS performed_by_name
               FROM ban_history h
               LEFT JOIN users u ON u.id = h.performed_by
              WHERE h.user_id = ?
              ORDER BY h.created_at DESC",
            [$userId]
        );
    }

    /**
     * Historial global de baneos.
     */
    public static function getGlobalHistory(int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT h.*, 
                    u.username AS user_name,
                    p.username AS performed_by_name
               FROM ban_history h
               LEFT JOIN users u ON u.id = h.user_id
               LEFT JOIN users p ON p.id = h.performed_by
              ORDER BY h.created_at DESC
              LIMIT ?",
            [$limit]
        );
    }

    /**
     * Estadísticas de baneos.
     */
    public static function getStats(): array
    {
        return [
            'total_banned' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1")['c'],
            'permanent' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1 AND ban_expires_at IS NULL")['c'],
            'temporary' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1 AND ban_expires_at IS NOT NULL AND ban_expires_at > NOW()")['c'],
            'expired' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1 AND ban_expires_at IS NOT NULL AND ban_expires_at < NOW()")['c'],
            'banned_today' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1 AND DATE(banned_at) = CURDATE()")['c'],
            'banned_this_week' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1 AND banned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'],
        ];
    }

    /**
     * Motivos más comunes de baneo.
     */
    public static function getTopReasons(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT banned_reason, COUNT(*) AS count
               FROM users
              WHERE is_banned = 1 AND banned_reason IS NOT NULL AND banned_reason != ''
              GROUP BY banned_reason
              ORDER BY count DESC
              LIMIT ?",
            [$limit]
        );
    }

    /**
     * Procesa baneos expirados automáticamente.
     */
    public static function processExpiredBans(): int
    {
        $expired = Database::fetchAll(
            "SELECT id, username FROM users WHERE is_banned = 1 AND ban_expires_at IS NOT NULL AND ban_expires_at < NOW()"
        );

        foreach ($expired as $user) {
            Database::execute(
                "UPDATE users SET is_banned = 0, banned_reason = NULL, banned_by = NULL, banned_at = NULL, ban_expires_at = NULL, is_active = 1 WHERE id = ?",
                [$user['id']]
            );
            Database::insert(
                "INSERT INTO ban_history (user_id, action, reason, notes) VALUES (?, 'unbanned', NULL, 'Baneo expirado automáticamente')",
                [$user['id']]
            );
        }

        return count($expired);
    }

    /**
     * Envía notificación de baneo por email.
     */
    private static function sendBanNotification(array $user, string $reason, ?int $duration, ?string $expiresAt): void
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');
        $durationText = $duration ? "$duration días (hasta $expiresAt)" : "permanentemente";
        
        $html = "<h2>Cuenta baneada</h2>
                 <p>Hola " . htmlspecialchars($user['full_name'] ?? $user['username']) . ",</p>
                 <p>Tu cuenta en <strong>$siteName</strong> ha sido baneada <strong>$durationText</strong>.</p>
                 <p><strong>Motivo:</strong> " . htmlspecialchars($reason) . "</p>
                 <p>Si consideras que ha sido un error, contacta con el equipo de soporte.</p>";
        
        MailService::send($user['email'], 'Cuenta baneada · ' . $siteName, $html);
    }

    /**
     * Envía notificación de desbaneo por email.
     */
    private static function sendUnbanNotification(array $user): void
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');
        $cfg = require CONFIG_PATH . '/config.php';
        $loginUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/login';
        
        $html = "<h2>Cuenta restaurada</h2>
                 <p>Hola " . htmlspecialchars($user['full_name'] ?? $user['username']) . ",</p>
                 <p>Tu cuenta en <strong>$siteName</strong> ha sido restaurada y ya puedes volver a acceder.</p>
                 <p><a href=\"$loginUrl\" style=\"background:#00d4aa;color:#0d1117;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block\">Iniciar sesión</a></p>";
        
        MailService::send($user['email'], 'Cuenta restaurada · ' . $siteName, $html);
    }
}