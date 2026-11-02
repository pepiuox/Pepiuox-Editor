<?php
namespace App\Services;

use App\Core\{Database, Auth};

/**
 * Gestiona los códigos de recuperación 2FA de un solo uso.
 * 
 * Características:
 * - Códigos hasheados con bcrypt (nunca se almacenan en texto plano)
 * - Un solo uso por código
 * - Auditoría completa (IP, fecha de uso)
 * - Notificaciones automáticas al usar un código
 * - Regeneración masiva con invalidación de anteriores
 */
class TwoFactorRecoveryService
{
    /** Número de códigos a generar */
    public const CODES_PER_USER = 10;
    
    /** Longitud de cada código (sin guion) */
    public const CODE_LENGTH = 8;
    
    /** Caracteres permitidos (sin caracteres confusos: 0/O, 1/I/l) */
    public const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    
    /** Umbral para advertir al usuario */
    public const LOW_CODES_THRESHOLD = 3;

    /**
     * Genera códigos de recuperación para un usuario.
     * Invalida los códigos anteriores.
     *
     * ── Transacción ─────────────────────────────────────────────────
     * Si quien llama ya tiene una transacción abierta, este método se suma a
     * ella y **no la cierra**: la abre él, la termina él.
     *
     * Antes abría siempre la suya. Llamado desde `TwoFactorController::verify()`
     * —que ya tenía una abierta— PDO respondía «There is already an active
     * transaction» y la activación de 2FA fallaba entera. El `begin()` estaba
     * además fuera del `try`, así que la excepción se escapaba sin que este
     * método la registrara.
     *
     * Cuando participa en la transacción de quien llama y algo falla, el error
     * **se relanza** en vez de devolver una lista vacía: solo quien abrió la
     * transacción puede revertir todo el trabajo que contiene, y tragárselo
     * dejaría una activación a medias confirmada por el llamador.
     *
     * @return array Códigos en texto plano (SOLO se devuelven aquí, nunca se guardan así)
     * @throws \Throwable Si participa en la transacción del llamador y el trabajo falla.
     */
    public static function generateForUser(int $userId): array
    {
        $propia = !Database::inTransaction();

        try {
            if ($propia) {
                Database::begin();
            }

            // Invalidar códigos anteriores (no borrar, mantener auditoría)
            Database::execute(
                "UPDATE two_factor_recovery_codes 
                    SET used_at = NOW() 
                  WHERE user_id = ? AND used_at IS NULL",
                [$userId]
            );

            $plainCodes = [];
            
            // Generar códigos únicos
            for ($i = 0; $i < self::CODES_PER_USER; $i++) {
                $code = self::generateUniqueCode();
                $plainCodes[] = $code;
                
                // Guardar hash (NUNCA el código en texto plano)
                Database::insert(
                    "INSERT INTO two_factor_recovery_codes (user_id, code_hash) VALUES (?, ?)",
                    [$userId, password_hash($code, PASSWORD_BCRYPT, ['cost' => 10])]
                );
            }

            if ($propia) {
                Database::commit();
            }

            // Registrar en auditoría
            AuditService::log(
                Auth::id(), '2fa_recovery_generated',
                "Generados " . self::CODES_PER_USER . " códigos de recuperación",
                'security',
                $userId
            );

            return $plainCodes;
        } catch (\Throwable $e) {
            error_log('[2FA Recovery] Error generando códigos: ' . $e->getMessage());

            if ($propia) {
                Database::rollbackIfActive();
                return [];
            }

            // La transacción es del llamador: él decide. Se relanza.
            throw $e;
        }
    }

    /**
     * Verifica un código de recuperación.
     * Si es válido, lo marca como usado.
     * 
     * @return bool True si el código es válido y fue consumido
     */
    public static function verifyAndConsume(int $userId, string $code): bool
    {
        $code = self::normalizeCode($code);
        if (strlen($code) !== self::CODE_LENGTH) {
            return false;
        }

        // Obtener códigos no usados del usuario
        $codes = Database::fetchAll(
            "SELECT id, code_hash FROM two_factor_recovery_codes 
              WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );

        foreach ($codes as $row) {
            // Verificar hash con password_verify (timing-safe)
            if (password_verify($code, $row['code_hash'])) {
                // Marcar como usado
                Database::execute(
                    "UPDATE two_factor_recovery_codes 
                        SET used_at = NOW(), 
                            used_ip = ? 
                      WHERE id = ?",
                    [$_SERVER['REMOTE_ADDR'] ?? null, $row['id']]
                );

                // Auditoría
                AuditService::log(
                    Auth::id(), '2fa_recovery_used',
                    "Código de recuperación usado",
                    'security',
                    $userId
                );

                // Notificar al usuario por email
                self::notifyRecoveryUsed($userId);

                return true;
            }
        }

        return false;
    }

    /**
     * Cuenta cuántos códigos no usados le quedan al usuario.
     */
    public static function remainingCodes(int $userId): int
    {
        return (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM two_factor_recovery_codes 
              WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        )['c'];
    }

    /**
     * Verifica si el usuario debe regenerar sus códigos (tiene pocos o ninguno).
     */
    public static function needsRegeneration(int $userId): bool
    {
        return self::remainingCodes($userId) <= self::LOW_CODES_THRESHOLD;
    }

    /**
     * Obtiene el historial de uso de códigos (solo metadatos, no los códigos).
     */
    public static function getUsageHistory(int $userId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT used_at, used_ip 
               FROM two_factor_recovery_codes 
              WHERE user_id = ? AND used_at IS NOT NULL 
              ORDER BY used_at DESC 
              LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Invalida todos los códigos de un usuario (ej: al desactivar 2FA).
     */
    public static function invalidateAll(int $userId): int
    {
        return Database::execute(
            "UPDATE two_factor_recovery_codes 
                SET used_at = NOW() 
              WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );
    }

    /**
     * Formatea un código para mostrarlo (XXXX-XXXX).
     */
    public static function formatCode(string $code): string
    {
        $code = self::normalizeCode($code);
        if (strlen($code) !== self::CODE_LENGTH) {
            return $code;
        }
        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }

    /**
     * Genera un código único (no repetido en la BD).
     */
    private static function generateUniqueCode(): string
    {
        $charset = self::CHARSET;
        $charsetLen = strlen($charset);
        
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= $charset[random_int(0, $charsetLen - 1)];
            }
            
            // Verificar que no exista ya (muy improbable con 8 chars, pero por seguridad)
            $hash = password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
            $exists = Database::fetch(
                "SELECT 1 FROM two_factor_recovery_codes WHERE code_hash = ?",
                [$hash]
            );
        } while ($exists);
        
        return $code;
    }

    /**
     * Normaliza un código: quita espacios, guiones, convierte a mayúsculas.
     */
    private static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = str_replace(['-', ' ', '.'], '', $code);
        return $code;
    }

    /**
     * Notifica al usuario que se usó un código de recuperación.
     */
    private static function notifyRecoveryUsed(int $userId): void
    {
        try {
            $user = Database::fetch(
                "SELECT id, username, email, full_name FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user || empty($user['email'])) return;

            $remaining = self::remainingCodes($userId);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

            // Notificación en panel
            NotificationService::notify(
                $userId,
                'warning',
                'Código de recuperación 2FA usado',
                "Se usó un código de recuperación desde IP $ip. Te quedan $remaining códigos.",
                url('admin/profile'),
                'shield-exclamation'
            );

            // Email si el servicio existe
            if (class_exists('\App\Services\EmailNotificationService')) {
                $html = self::buildRecoveryUsedEmail($user, $ip, $remaining);
                \App\Services\EmailNotificationService::send(
                    $user['email'],
                    '⚠️ Código de recuperación 2FA usado',
                    $html
                );
            }
        } catch (\Throwable $e) {
            error_log('[2FA Recovery] Error notificando: ' . $e->getMessage());
        }
    }

    /**
     * Construye el HTML del email de notificación.
     */
    private static function buildRecoveryUsedEmail(array $user, string $ip, int $remaining): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');
        $userName = htmlspecialchars($user['full_name'] ?? $user['username']);
        $profileUrl = (require CONFIG_PATH . '/config.php')['app_url'] . '/admin/profile';
        
        $warning = $remaining <= 2 
            ? '<div class="alert alert-danger"><strong>⚠️ URGENTE:</strong> Solo te quedan <strong>' . $remaining . ' códigos</strong>. Regenera tus códigos inmediatamente desde tu perfil.</div>'
            : '<div class="alert alert-warning">Te quedan <strong>' . $remaining . ' códigos</strong> disponibles.</div>';

        return EmailTemplate::wrap(
            'Código de recuperación usado',
            "<h1>Hola $userName,</h1>
             <p>Se ha utilizado uno de tus códigos de recuperación 2FA para iniciar sesión en tu cuenta.</p>
             <table class='table-stats'>
               <tr><td>IP utilizada</td><td><code>$ip</code></td></tr>
               <tr><td>Fecha y hora</td><td>" . date('d/m/Y H:i:s') . "</td></tr>
               <tr><td>Códigos restantes</td><td><strong>$remaining</strong></td></tr>
             </table>
             $warning
             <p style='text-align:center;margin:24px 0'>
               <a href='$profileUrl' class='btn'>Revisar mi perfil</a>
             </p>
             <hr class='divider'>
             <p style='color:#8b949e;font-size:13px'>
               Si no fuiste tú, <strong>cambia tu contraseña inmediatamente</strong> y contacta al administrador.
             </p>",
            'Alerta de seguridad - Código de recuperación usado'
        );
    }
}