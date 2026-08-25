<?php
namespace App\Services;

use App\Core\{Database, Auth};

/**
 * Gestiona los dispositivos de confianza para saltarse el 2FA.
 * 
 * Flujo:
 * 1. Usuario hace login con 2FA exitoso
 * 2. Marca "Confiar en este dispositivo por 30 días"
 * 3. Se genera un hash único del dispositivo + token en cookie
 * 4. Próximos logins: si el token es válido, se salta el 2FA
 */
class TrustedDeviceService
{
    public const COOKIE_NAME = 'trusted_device';
    public const DEFAULT_DAYS = 30;
    public const MAX_DEVICES_PER_USER = 10;

    /**
     * Genera un hash único del dispositivo basado en user agent + IP.
     */
    public static function generateDeviceHash(string $userAgent, string $ip): string
    {
        return hash('sha256', $userAgent . '|' . $ip . '|' . CONFIG_PATH);
    }

    /**
     * Detecta plataforma y navegador del user agent.
     */
    public static function detectDeviceInfo(string $userAgent): array
    {
        $platform = 'Desconocido';
        $browser = 'Desconocido';

        // Plataforma
        if (stripos($userAgent, 'Windows') !== false) $platform = 'Windows';
        elseif (stripos($userAgent, 'Mac') !== false) $platform = 'macOS';
        elseif (stripos($userAgent, 'Linux') !== false) $platform = 'Linux';
        elseif (stripos($userAgent, 'Android') !== false) $platform = 'Android';
        elseif (stripos($userAgent, 'iPhone') || stripos($userAgent, 'iPad')) $platform = 'iOS';

        // Navegador
        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) {
            $browser = 'Opera';
        }

        return compact('platform', 'browser');
    }

    /**
     * Genera un nombre descriptivo para el dispositivo.
     */
    public static function generateDeviceName(string $userAgent): string
    {
        $info = self::detectDeviceInfo($userAgent);
        return "{$info['browser']} en {$info['platform']}";
    }

    /**
     * Registra un nuevo dispositivo de confianza.
     * 
     * @return array Datos del dispositivo (incluye token para cookie)
     */
   /**
 * Registra un nuevo dispositivo de confianza.
 * 
 * @return array Datos del dispositivo (incluye token para cookie)
 */
public static function trustDevice(int $userId, string $userAgent, string $ip, int $days = self::DEFAULT_DAYS): array
{
    $deviceHash = self::generateDeviceHash($userAgent, $ip);
    $rememberToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));
    $deviceName = self::generateDeviceName($userAgent);
    $info = self::detectDeviceInfo($userAgent);

    // Eliminar dispositivo anterior si existe (mismo hash)
    Database::execute(
        "DELETE FROM trusted_devices WHERE user_id = ? AND device_hash = ?",
        [$userId, $deviceHash]
    );

    // Limitar dispositivos por usuario
    self::enforceDeviceLimit($userId);

    // Insertar nuevo
    Database::insert(
        "INSERT INTO trusted_devices 
            (user_id, device_hash, device_name, user_agent, ip_address, 
             platform, browser, remember_token, expires_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $userId, $deviceHash, $deviceName, $userAgent, $ip,
            $info['platform'], $info['browser'], $rememberToken, $expiresAt
        ]
    );

    // Establecer cookie
    self::setCookie($userId, $deviceHash, $rememberToken, $days);

    // ═══ NUEVO: Notificar al usuario por email ═══
    self::notifyUserOfNewDevice($userId, $deviceName, $info, $ip);

     AuditService::log(
    'trusted_device_added',
    "Dispositivo de confianza añadido: $deviceName ($info[platform]/$info[browser])",
    'security',
    $userId,
    [
        'device_name' => $deviceName,
        'platform' => $info['platform'],
        'browser' => $info['browser'],
        'ip_address' => $ip,
        'expires_at' => $expiresAt,
        'email_sent' => true,
    ]
    );

    return [
        'device_hash' => $deviceHash,
        'remember_token' => $rememberToken,
        'device_name' => $deviceName,
        'expires_at' => $expiresAt,
    ];
    
}

/**
 * Notifica al usuario por email y panel sobre el nuevo dispositivo.
 */
private static function notifyUserOfNewDevice(int $userId, string $deviceName, array $info, string $ip): void
{
    try {
        $user = Database::fetch(
            "SELECT id, username, email, full_name FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || empty($user['email'])) return;

        $deviceInfo = [
            'device_name' => $deviceName,
            'browser' => $info['browser'],
            'platform' => $info['platform'],
            'ip' => $ip,
        ];

        // 1. Notificación en panel (inmediata)
        NotificationService::notify(
            $userId,
            'warning',
            '🔐 Nuevo dispositivo de confianza',
            "Se añadió '$deviceName' desde IP $ip. Si no fuiste tú, revócalo inmediatamente.",
            url('admin/security/trusted-devices'),
            'shield-exclamation'
        );

        // 2. Email de alerta de seguridad
        if (class_exists('\App\Services\EmailNotificationService')) {
            \App\Services\EmailNotificationService::notifyNewTrustedDevice($user, $deviceInfo);
        }
    } catch (\Throwable $e) {
        error_log('[TrustedDevice] Error notificando nuevo dispositivo: ' . $e->getMessage());
        // No romper el flujo si falla la notificación
    }
}

    /**
     * Verifica si el dispositivo actual es de confianza para un usuario.
     */
    public static function isTrusted(int $userId): bool
    {
        $cookie = self::getCookieData();
        if (!$cookie) return false;

        // Verificar que el user_id de la cookie coincide
        if ((int)$cookie['user_id'] !== $userId) return false;

        // Buscar en BD
        $device = Database::fetch(
            "SELECT * FROM trusted_devices 
              WHERE user_id = ? 
                AND device_hash = ? 
                AND remember_token = ? 
                AND expires_at > NOW()
              LIMIT 1",
            [$userId, $cookie['device_hash'], $cookie['remember_token']]
        );

        if (!$device) return false;

        // Actualizar last_used_at
        Database::execute(
            "UPDATE trusted_devices SET last_used_at = NOW() WHERE id = ?",
            [$device['id']]
        );

        return true;
    }

    /**
     * Lista todos los dispositivos de confianza de un usuario.
     */
    public static function listDevices(int $userId): array
    {
        $devices = Database::fetchAll(
            "SELECT * FROM trusted_devices WHERE user_id = ? ORDER BY last_used_at DESC",
            [$userId]
        );

        $currentHash = null;
        $cookie = self::getCookieData();
        if ($cookie) $currentHash = $cookie['device_hash'];

        foreach ($devices as &$d) {
            $d['is_current'] = ($d['device_hash'] === $currentHash);
            $d['icon'] = self::getDeviceIcon($d['browser']);
        }

        return $devices;
    }

    /**
     * Revoca un dispositivo específico.
     */
    public static function revokeDevice(int $userId, int $deviceId): bool
    {
        $device = Database::fetch(
            "SELECT * FROM trusted_devices WHERE id = ? AND user_id = ?",
            [$deviceId, $userId]
        );

        if (!$device) return false;

        Database::execute("DELETE FROM trusted_devices WHERE id = ?", [$deviceId]);

        // Si es el dispositivo actual, eliminar cookie
        $cookie = self::getCookieData();
        if ($cookie && $cookie['device_hash'] === $device['device_hash']) {
            self::clearCookie();
        }

        AuditService::log(
            'trusted_device_revoked',
            "Dispositivo revocado: {$device['device_name']}",
            'security',
            $userId
        );

        return true;
    }

    /**
     * Revoca TODOS los dispositivos de un usuario (incluido el actual).
     */
    public static function revokeAllDevices(int $userId): int
    {
        $count = Database::execute(
            "DELETE FROM trusted_devices WHERE user_id = ?",
            [$userId]
        );

        self::clearCookie();

        AuditService::log(
            'trusted_devices_revoked_all',
            "Todos los dispositivos de confianza revocados ($count)",
            'security',
            $userId
        );

        return $count;
    }

    /**
     * Limpia dispositivos expirados (para cron).
     */
    public static function cleanupExpired(): int
    {
        return Database::execute(
            "DELETE FROM trusted_devices WHERE expires_at < NOW()"
        );
    }

    /**
     * Cuenta dispositivos de confianza de un usuario.
     */
    public static function countDevices(int $userId): int
    {
        return (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM trusted_devices WHERE user_id = ? AND expires_at > NOW()",
            [$userId]
        )['c'];
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    private static function setCookie(int $userId, string $deviceHash, string $token, int $days): void
    {
        $value = base64_encode(json_encode([
            'user_id' => $userId,
            'device_hash' => $deviceHash,
            'remember_token' => $token,
        ]));

        $cfg = require CONFIG_PATH . '/config.php';
        setcookie(self::COOKIE_NAME, $value, [
            'expires' => time() + ($days * 86400),
            'path' => '/',
            'httponly' => true,
            'secure' => $cfg['session']['secure'] ?? false,
            'samesite' => 'Lax',
        ]);
    }

    private static function getCookieData(): ?array
    {
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!$cookie) return null;

        $decoded = json_decode(base64_decode($cookie), true);
        if (!is_array($decoded)) return null;
        if (!isset($decoded['user_id'], $decoded['device_hash'], $decoded['remember_token'])) {
            return null;
        }

        return $decoded;
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);
    }

    private static function enforceDeviceLimit(int $userId): void
    {
        $count = self::countDevices($userId);
        if ($count >= self::MAX_DEVICES_PER_USER) {
            // Eliminar los más antiguos
            Database::execute(
                "DELETE FROM trusted_devices 
                  WHERE user_id = ? 
                  ORDER BY last_used_at ASC 
                  LIMIT ?",
                [$userId, $count - self::MAX_DEVICES_PER_USER + 1]
            );
        }
    }

    private static function getDeviceIcon(string $browser): string
    {
        return match (strtolower($browser)) {
            'chrome' => 'chrome',
            'firefox' => 'firefox',
            'safari' => 'safari',
            'edge' => 'edge',
            'opera' => 'opera',
            default => 'globe',
        };
    }
}