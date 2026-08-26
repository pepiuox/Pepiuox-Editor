<?php
namespace App\Services;

use App\Core\{Database, Auth};

/**
 * Servicio central para enviar notificaciones por email.
 * 
 * Características:
 * - Respeta las preferencias del usuario (qué emails quiere recibir)
 * - Registra todos los envíos en logs
 * - Soporta cola asíncrona (pendiente de implementar)
 * - Plantillas consistentes con el tema del CMS
 */
class EmailNotificationService
{
    /**
     * Envía un email respetando las preferencias del usuario.
     */
    public static function send(
        string $to,
        string $subject,
        string $html,
        array $options = []
    ): bool {
        $cfg = require CONFIG_PATH . '/config.php';
        
        // Verificar si los emails están habilitados globalmente
        if (!SettingService::get('mail', 'enabled', true)) {
            self::log('disabled', $to, $subject, 'Emails deshabilitados globalmente');
            return false;
        }

        // Verificar que el destinatario no esté en la lista negra
        if (self::isBlacklisted($to)) {
            self::log('blacklisted', $to, $subject, 'Destinatario en lista negra');
            return false;
        }

        // Enviar email
        $success = MailService::send($to, $subject, $html, $options);

        // Registrar en logs
        self::log(
            $success ? 'sent' : 'failed',
            $to,
            $subject,
            $success ? 'Enviado correctamente' : 'Error al enviar'
        );

        return $success;
    }

    /**
     * Notifica al administrador sobre una IP bloqueada automáticamente.
     */
    public static function notifyIpBlocked(string $ip, int $attempts, string $reason): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail) return false;

        $html = EmailTemplate::securityAlert(
            'IP bloqueada automáticamente',
            "El sistema ha bloqueado automáticamente la IP <strong>$ip</strong> tras detectar múltiples intentos fallidos.",
            [
                'Dirección IP' => $ip,
                'Intentos fallidos' => (string)$attempts,
                'Motivo' => $reason,
                'Fecha' => date('d/m/Y H:i:s'),
            ]
        );

        return self::send(
            $adminEmail,
            '⚠️ IP bloqueada automáticamente - ' . $ip,
            $html
        );
    }

    /**
     * Notifica sobre actividad sospechosa (múltiples IPs atacando).
     */
    public static function notifySuspiciousActivity(array $topIps): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail || empty($topIps)) return false;

        $ipList = '<ul style="color:#e6edf3;line-height:1.8">';
        foreach (array_slice($topIps, 0, 5) as $ip) {
            $ipList .= '<li><code>' . htmlspecialchars($ip['ip_address']) . '</code> — <strong>' . (int)$ip['attempts'] . '</strong> intentos</li>';
        }
        $ipList .= '</ul>';

        $html = EmailTemplate::securityAlert(
            'Actividad sospechosa detectada',
            "Se han detectado múltiples IPs con intentos fallidos en las últimas horas:<br>$ipList",
            [
                'Total IPs sospechosas' => (string)count($topIps),
                'Período' => 'Últimas 24 horas',
                'Recomendación' => 'Revisar el Dashboard de Seguridad',
            ]
        );

        return self::send(
            $adminEmail,
            '⚠️ Actividad sospechosa detectada',
            $html
        );
    }

    /**
     * Notifica sobre un nuevo login desde IP diferente.
     */
    public static function notifyNewLogin(array $user, string $ip, string $userAgent): bool
    {
        if (empty($user['email'])) return false;
        
        // No notificar si el usuario desactivó estas notificaciones
        if (!self::userWantsEmail((int)$user['id'], 'new_login')) return false;

        $cfg = require CONFIG_PATH . '/config.php';
        $profileUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/profile';
        
        $html = EmailTemplate::newLogin(
            $user['full_name'] ?? $user['username'],
            $ip,
            $userAgent,
            $profileUrl
        );
        $html = str_replace('{{datetime}}', date('d/m/Y H:i:s'), $html);

        return self::send(
            $user['email'],
            '🔐 Nuevo inicio de sesión detectado',
            $html
        );
    }

    /**
     * Notifica al usuario sobre su baneo.
     */
    public static function notifyUserBanned(array $user, string $reason, ?int $duration, ?string $expiresAt): bool
    {
        if (empty($user['email'])) return false;

        $html = EmailTemplate::userBanned(
            $user['full_name'] ?? $user['username'],
            $reason,
            $duration,
            $expiresAt
        );

        return self::send(
            $user['email'],
            'Tu cuenta ha sido suspendida',
            $html
        );
    }

    /**
     * Notifica al usuario sobre su desbaneo.
     */
    public static function notifyUserUnbanned(array $user): bool
    {
        if (empty($user['email'])) return false;

        $cfg = require CONFIG_PATH . '/config.php';
        $loginUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/login';

        $html = EmailTemplate::userUnbanned(
            $user['full_name'] ?? $user['username'],
            $loginUrl
        );

        return self::send(
            $user['email'],
            '✅ Tu cuenta ha sido restaurada',
            $html
        );
    }

    /**
     * Notifica al administrador sobre un backup completado.
     */
    public static function notifyBackupCompleted(array $backup): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail) return false;

        $size = self::formatSize((int)$backup['size']);
        $cfg = require CONFIG_PATH . '/config.php';
        $downloadUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/backups/' . $backup['id'] . '/download';

        $html = EmailTemplate::info(
            '✅ Backup completado',
            "El backup del sistema se ha generado correctamente.",
            'Descargar backup',
            $downloadUrl
        );

        // Añadir detalles
        $detailsHtml = '<table class="table-stats">';
        $detailsHtml .= '<tr><td>Archivo</td><td><code>' . htmlspecialchars($backup['filename']) . '</code></td></tr>';
        $detailsHtml .= '<tr><td>Tamaño</td><td>' . $size . '</td></tr>';
        $detailsHtml .= '<tr><td>Tipo</td><td>' . htmlspecialchars($backup['type']) . '</td></tr>';
        $detailsHtml .= '<tr><td>Fecha</td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
        $detailsHtml .= '</table>';

        $html = str_replace('</h1>', '</h1>' . $detailsHtml, $html);

        return self::send(
            $adminEmail,
            '✅ Backup completado - ' . $backup['filename'],
            $html
        );
    }

    /**
     * Notifica al administrador sobre un backup fallido.
     */
    public static function notifyBackupFailed(string $error): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail) return false;

        $html = EmailTemplate::securityAlert(
            '❌ Error en backup',
            'El sistema intentó generar un backup pero ocurrió un error.',
            ['Error' => $error, 'Fecha' => date('d/m/Y H:i:s')]
        );

        return self::send(
            $adminEmail,
            '❌ Error al generar backup',
            $html
        );
    }

    /**
     * Notifica sobre intentos de login fallidos para un usuario específico.
     */
    public static function notifyFailedLoginAttempts(array $user, int $attempts, string $lastIp): bool
    {
        if (empty($user['email'])) return false;
        if (!self::userWantsEmail((int)$user['id'], 'failed_login')) return false;

        $cfg = require CONFIG_PATH . '/config.php';
        $profileUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/profile';

        $html = EmailTemplate::securityAlert(
            'Múltiples intentos de login fallidos',
            "Se han detectado <strong>$attempts intentos fallidos</strong> de inicio de sesión en tu cuenta.",
            [
                'Intentos fallidos' => (string)$attempts,
                'Última IP' => $lastIp,
                'Fecha' => date('d/m/Y H:i:s'),
            ]
        );

        return self::send(
            $user['email'],
            '⚠️ Intentos de login fallidos en tu cuenta',
            $html
        );
    }

    /**
     * Notifica sobre un nuevo usuario creado.
     */
    public static function notifyNewUser(array $user): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail) return false;

        $cfg = require CONFIG_PATH . '/config.php';
        $usersUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/users';

        $html = EmailTemplate::info(
            '👤 Nuevo usuario registrado',
            "Se ha creado un nuevo usuario en el sistema.",
            'Ver usuarios',
            $usersUrl
        );

        $detailsHtml = '<table class="table-stats">';
        $detailsHtml .= '<tr><td>Usuario</td><td><strong>' . htmlspecialchars($user['username']) . '</strong></td></tr>';
        $detailsHtml .= '<tr><td>Email</td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
        $detailsHtml .= '<tr><td>Rol</td><td>' . htmlspecialchars($user['role'] ?? 'Guest') . '</td></tr>';
        $detailsHtml .= '<tr><td>Fecha</td><td>' . date('d/m/Y H:i:s') . '</td></tr>';
        $detailsHtml .= '</table>';

        $html = str_replace('</h1>', '</h1>' . $detailsHtml, $html);

        return self::send(
            $adminEmail,
            '👤 Nuevo usuario: ' . $user['username'],
            $html
        );
    }

    /**
     * Email de bienvenida para nuevo usuario.
     */
    public static function sendWelcomeEmail(array $user, string $tempPassword = null): bool
    {
        if (empty($user['email'])) return false;

        $cfg = require CONFIG_PATH . '/config.php';
        $loginUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/login';

        $html = EmailTemplate::welcome(
            $user['full_name'] ?? $user['username'],
            $loginUrl
        );

        // Si hay contraseña temporal, añadirla
        if ($tempPassword) {
            $credHtml = '<div class="alert alert-info"><strong>Tus credenciales temporales:</strong><br>Usuario: <code>' . htmlspecialchars($user['username']) . '</code><br>Contraseña: <code>' . htmlspecialchars($tempPassword) . '</code><br><small>Te recomendamos cambiarla tras el primer login.</small></div>';
            $html = str_replace('<p style="text-align:center', $credHtml . '<p style="text-align:center', $html);
        }

        return self::send(
            $user['email'],
            '👋 Bienvenido a ' . SettingService::get('general', 'site_name', 'PePiuoX Editor'),
            $html
        );
    }

    /**
     * Resumen diario/semanal de seguridad para administradores.
     */
    public static function sendSecurityDigest(string $period = 'daily'): bool
    {
        $adminEmail = self::getAdminEmail();
        if (!$adminEmail) return false;

        $days = $period === 'weekly' ? 7 : 1;
        $since = date('Y-m-d H:i:s', strtotime("-$days days"));

        $stats = [
            'total_attempts' => (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ?", [$since])['c'],
            'failed' => (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ? AND successful = 0", [$since])['c'],
            'blocked_ips' => (int)Database::fetch("SELECT COUNT(*) AS c FROM blocked_ips WHERE created_at >= ?", [$since])['c'],
            'new_users' => (int)Database::fetch("SELECT COUNT(*) AS c FROM users WHERE created_at >= ?", [$since])['c'],
        ];

        $cfg = require CONFIG_PATH . '/config.php';
        $dashboardUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/security/dashboard';

        $periodLabel = $period === 'weekly' ? 'semanal' : 'diario';
        $html = EmailTemplate::info(
            "📊 Resumen de seguridad $periodLabel",
            "Aquí tienes el resumen de actividad de seguridad de los últimos $days día(s).",
            'Ver Dashboard de Seguridad',
            $dashboardUrl
        );

        $statsHtml = '<table class="table-stats">';
        $statsHtml .= '<tr><td>Intentos de login totales</td><td>' . number_format($stats['total_attempts']) . '</td></tr>';
        $statsHtml .= '<tr><td>Intentos fallidos</td><td style="color:#ef4444">' . number_format($stats['failed']) . '</td></tr>';
        $statsHtml .= '<tr><td>IPs bloqueadas</td><td style="color:#f59e0b">' . number_format($stats['blocked_ips']) . '</td></tr>';
        $statsHtml .= '<tr><td>Nuevos usuarios</td><td style="color:#22c55e">' . number_format($stats['new_users']) . '</td></tr>';
        $statsHtml .= '</table>';

        $html = str_replace('</h1>', '</h1>' . $statsHtml, $html);

        return self::send(
            $adminEmail,
            "📊 Resumen de seguridad $periodLabel - " . date('d/m/Y'),
            $html
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTODOS AUXILIARES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obtiene el email del administrador principal.
     */
    private static function getAdminEmail(): ?string
    {
        $admin = Database::fetch(
            "SELECT email FROM users WHERE role = 'Super admin' AND is_active = 1 ORDER BY id ASC LIMIT 1"
        );
        return $admin['email'] ?? null;
    }

    /**
     * Verifica si un usuario quiere recibir un tipo específico de email.
     */
    /**
 * Verifica si un usuario quiere recibir un tipo específico de email.
 */
private static function userWantsEmail(int $userId, string $type): bool
{
    // Tipos críticos que NO se pueden desactivar
    $criticalTypes = ['security_alerts', 'password_reset', 'account_locked'];
    if (in_array($type, $criticalTypes, true)) {
        return true;
    }

    try {
        $pref = Database::fetch(
            "SELECT value FROM settings WHERE `group` = ? AND `key` = ?",
            ["user_{$userId}", "email_{$type}"]
        );
        
        if ($pref) {
            return (bool)$pref['value'];
        }
    } catch (\Throwable $e) {
        // Si falla la consulta, permitir por defecto
    }
    
    return true;
}

    /**
     * Verifica si un email está en la lista negra.
     */
    private static function isBlacklisted(string $email): bool
    {
        $blacklist = SettingService::get('mail', 'blacklist', []);
        if (!is_array($blacklist)) return false;
        
        foreach ($blacklist as $pattern) {
            if (fnmatch($pattern, $email)) return true;
        }
        return false;
    }

    /**
     * Registra el envío en logs.
     */
    private static function log(string $status, string $to, string $subject, string $message): void
    {
        try {
            $logDir = (require CONFIG_PATH . '/config.php')['logs']['path'] ?? ROOT_PATH . '/storage/logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
            
            $entry = [
                'timestamp' => date('c'),
                'status' => $status,
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'from' => Auth::id() ? 'user:' . Auth::id() : 'system',
            ];
            
            file_put_contents(
                $logDir . '/mail.log',
                json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n---\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            error_log('[EmailNotification] Error al registrar log: ' . $e->getMessage());
        }
    }

    /**
     * Formatea tamaño de archivo.
     */
    private static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    /**
 * Notifica al usuario que se ha añadido un nuevo dispositivo de confianza.
 * Alerta de seguridad crítica.
 */
public static function notifyNewTrustedDevice(array $user, array $deviceInfo): bool
{
    if (empty($user['email'])) return false;
    
    // Respetar preferencias del usuario (si desactivó emails de seguridad)
    if (!self::userWantsEmail((int)$user['id'], 'security_alerts')) {
        return false;
    }

    try {
        $html = EmailTemplate::newTrustedDevice($user, $deviceInfo);
        
        return self::send(
            $user['email'],
            '🔐 Nuevo dispositivo de confianza añadido a tu cuenta',
            $html,
            ['priority' => 'high']
        );
    } catch (\Throwable $e) {
        error_log('[EmailNotification] Error enviando alerta de nuevo dispositivo: ' . $e->getMessage());
        return false;
    }
}
}