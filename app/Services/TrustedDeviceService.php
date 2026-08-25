<?php
namespace App\Services;

use App\Core\{Database, Auth};

/**
 * Servicio de Dispositivos de Confianza
 * Permite omitir el 2FA en dispositivos marcados como confiables.
 */
class TrustedDeviceService
{
    public const HEARTBEAT_INTERVAL = 10;      // segundos
    public const SESSION_TIMEOUT = 30;         // segundos sin heartbeat = inactivo
    public const LOCK_DURATION = 60;           // segundos de duración del lock
    public const DEFAULT_DAYS = 30;            // días de validez del dispositivo
    public const MAX_DEVICES_PER_USER = 10;    // máximo de dispositivos por usuario

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
        elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) $platform = 'iOS';

        // Navegador
        if (stripos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
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
     * Obtiene el icono Bootstrap para un navegador.
     */
    public static function getDeviceIcon(string $browser): string
    {
        return match (strtolower($browser)) {
            'chrome'  => 'chrome',
            'firefox' => 'firefox',
            'safari'  => 'safari',
            'edge'    => 'edge',
            'opera'   => 'opera',
            default   => 'globe',
        };
    }

    /**
     * Registra un nuevo dispositivo de confianza.
     * 
     * @return array Datos del dispositivo
     */
    public static function trustDevice(int $userId, string $userAgent, string $ip, int $days = self::DEFAULT_DAYS): array
    {
        $deviceHash = self::generateDeviceHash($userAgent, $ip);
        $rememberToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($days * 86400));
        $deviceName = self::generateDeviceName($userAgent);
        $info = self::detectDeviceInfo($userAgent);

        // Eliminar dispositivo anterior si existe (mismo hash)
        try {
            Database::execute(
                "DELETE FROM trusted_devices WHERE user_id = ? AND device_hash = ?",
                [$userId, $deviceHash]
            );
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error eliminando hash duplicado: ' . $e->getMessage());
        }

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

        // Notificar al usuario
        self::notifyUserOfNewDevice($userId, $deviceName, $info, $ip);

        // Auditoría
        AuditService::log(
            'trusted_device_added',
            "Dispositivo de confianza añadido: $deviceName",
            'security',
            $userId,
            [
                'device_name' => $deviceName,
                'platform' => $info['platform'],
                'browser' => $info['browser'],
                'ip_address' => $ip,
                'expires_at' => $expiresAt,
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
     * Verifica si el dispositivo actual es de confianza para un usuario.
     */
    public static function isTrusted(int $userId): bool
    {
        $cookie = self::getCookieData();
        if (!$cookie) return false;

        // Verificar que el user_id de la cookie coincide
        if ((int)$cookie['user_id'] !== $userId) return false;

        try {
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

            // Actualizar last_heartbeat
            Database::execute(
                "UPDATE trusted_devices SET last_heartbeat = NOW() WHERE id = ?",
                [$device['id']]
            );

            return true;
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error verificando confianza: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todos los dispositivos de confianza de un usuario.
     */
    public static function listDevices(int $userId): array
    {
        try {
            $devices = Database::fetchAll(
                "SELECT * FROM trusted_devices 
                  WHERE user_id = ? 
                  ORDER BY last_heartbeat DESC",
                [$userId]
            );
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error listando dispositivos: ' . $e->getMessage());
            return [];
        }

        $currentHash = null;
        $cookie = self::getCookieData();
        if ($cookie) $currentHash = $cookie['device_hash'];

        foreach ($devices as &$d) {
            $d['is_current'] = ($d['device_hash'] === $currentHash);
            $d['icon'] = self::getDeviceIcon($d['browser'] ?? 'unknown');
        }

        return $devices;
    }

    /**
     * Revoca un dispositivo específico.
     */
    public static function revokeDevice(int $userId, int $deviceId): bool
    {
        try {
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
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error revocando dispositivo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoca TODOS los dispositivos de un usuario.
     */
    public static function revokeAllDevices(int $userId): int
    {
        try {
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
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error revocando todos: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene estadísticas de dispositivos del usuario.
     */
    public static function getStats(int $userId): array
    {
        try {
            $total = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM trusted_devices WHERE user_id = ?",
                [$userId]
            )['c'];

            $active = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM trusted_devices 
                  WHERE user_id = ? AND expires_at > NOW()",
                [$userId]
            )['c'];

            $currentHash = null;
            $cookie = self::getCookieData();
            if ($cookie) $currentHash = $cookie['device_hash'];

            $current = 0;
            if ($currentHash) {
                $current = (int)Database::fetch(
                    "SELECT COUNT(*) AS c FROM trusted_devices 
                      WHERE user_id = ? AND device_hash = ?",
                    [$userId, $currentHash]
                )['c'];
            }

            return compact('total', 'active', 'current');
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error obteniendo stats: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'current' => 0];
        }
    }

    /**
     * Limpia dispositivos expirados (para cron).
     */
    public static function cleanupExpired(): int
    {
        try {
            return Database::execute(
                "DELETE FROM trusted_devices WHERE expires_at < NOW()"
            );
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error en cleanup: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cuenta dispositivos activos de un usuario.
     */
    public static function countDevices(int $userId): int
    {
        try {
            return (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM trusted_devices 
                  WHERE user_id = ? AND expires_at > NOW()",
                [$userId]
            )['c'];
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    private static function setCookie(int $userId, string $deviceHash, string $token, int $days): void
    {
        try {
            $value = base64_encode(json_encode([
                'user_id' => $userId,
                'device_hash' => $deviceHash,
                'remember_token' => $token,
            ]));

            $cfg = require CONFIG_PATH . '/config.php';
            setcookie('trusted_device', $value, [
                'expires' => time() + ($days * 86400),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $cfg['session']['secure'] ?? false,
            ]);
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error estableciendo cookie: ' . $e->getMessage());
        }
    }

    private static function getCookieData(): ?array
    {
        $cookie = $_COOKIE['trusted_device'] ?? null;
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
        setcookie('trusted_device', '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);
    }

    private static function enforceDeviceLimit(int $userId): void
    {
        try {
            $count = self::countDevices($userId);
            if ($count >= self::MAX_DEVICES_PER_USER) {
                Database::execute(
                    "DELETE FROM trusted_devices 
                      WHERE user_id = ? 
                      ORDER BY last_heartbeat ASC 
                      LIMIT ?",
                    [$userId, $count - self::MAX_DEVICES_PER_USER + 1]
                );
            }
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error enforcing limit: ' . $e->getMessage());
        }
    }

    private static function notifyUserOfNewDevice(int $userId, string $deviceName, array $info, string $ip): void
    {
        try {
            // Notificación en panel
            NotificationService::notify(
                $userId,
                'warning',
                '🔐 Nuevo dispositivo de confianza',
                "Se añadió '$deviceName' desde IP $ip. Si no fuiste tú, revócalo inmediatamente.",
                url('admin/security/trusted-devices'),
                'shield-exclamation'
            );

            // Email si el servicio existe
            if (class_exists('\App\Services\EmailNotificationService')) {
                $user = Database::fetch(
                    "SELECT id, username, email, full_name FROM users WHERE id = ?",
                    [$userId]
                );
                if ($user && !empty($user['email'])) {
                    \App\Services\EmailNotificationService::notifyNewTrustedDevice($user, [
                        'device_name' => $deviceName,
                        'browser' => $info['browser'],
                        'platform' => $info['platform'],
                        'ip' => $ip,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('[TrustedDevice] Error notificando: ' . $e->getMessage());
        }
    }
}