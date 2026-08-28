<?php
namespace App\Services;

use App\Core\{Database, Auth};
use Throwable;

/**
 * Servicio de registro de actividades
 *
 * Registra acciones de usuarios en la tabla `activity_logs`.
 *
 * @package App\Services
 */
class ActivityLoggerService
{
    private Database $db;
    private string $logTable = 'activity_logs';
    private bool $enabled = true;

    /**
     * Constructor con inyección de dependencias
     *
     * @param Database $db Instancia de la base de datos
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Habilita/deshabilita el logging (útil para tests o modo mantenimiento)
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    // ──────────────────────────────────────────────────────────────
    //  MÉTODOS PRINCIPALES DE LOGGING
    // ──────────────────────────────────────────────────────────────

    /**
     * Registra una actividad genérica
     *
     * @param int|null $userId ID del usuario (null si es anónimo/sistema)
     * @param string $action Nombre de la acción (ej: 'menu_item_created')
     * @param string|null $details Detalles adicionales
     * @param string|null $ipAddress IP del cliente
     * @param string|null $userAgent User-Agent del cliente
     * @return bool True si se registró correctamente
     */
    public function log(
        ?int $userId,
        string $action,
        ?string $details = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        if (!$this->enabled) {
            return false;
        }

        try {
            $ipAddress = $ipAddress ?? $this->getClientIp();
            $userAgent = $userAgent ?? $this->getUserAgent();

            return $this->db->execute(
                "INSERT INTO {$this->logTable}
                 (user_id, action, details, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$userId, $action, $details, $ipAddress, $userAgent]
            );
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error logging activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra actividad del usuario actualmente autenticado
     *
     * @param string $action Nombre de la acción
     * @param string|null $details Detalles adicionales
     * @return bool
     */
    public function logCurrentUser(string $action, ?string $details = null): bool
    {
        // Verificar que Auth esté disponible y haya usuario logueado
        $userId = $this->getCurrentUserId();

        if ($userId === null) {
            // Fallback: registrar como sistema/anónimo
            return $this->log(null, $action, $details);
        }

        return $this->log($userId, $action, $details);
    }

    /**
     * Registra actividad con contexto completo (método unificado)
     *
     * @param string $action
     * @param string|null $details
     * @param string $context ('user', 'system', 'api')
     * @return bool
     */
    public function logWithContext(
        string $action,
        ?string $details = null,
        string $context = 'user'
    ): bool {
        $userId = match ($context) {
            'user'   => $this->getCurrentUserId(),
            'system' => null,
            'api'    => $this->getApiUserId(),
            default  => null,
        };

        return $this->log($userId, $action, $details);
    }

    // ──────────────────────────────────────────────────────────────
    //  CONSULTAS Y REPORTES
    // ──────────────────────────────────────────────────────────────

    /**
     * Obtiene actividades con filtros y paginación
     */
    public function getActivities(
        int $limit = 50,
        int $offset = 0,
        array $filters = []
    ): array {
        try {
            $sql = "SELECT al.*, u.username, u.email
                    FROM {$this->logTable} al
                    LEFT JOIN users u ON al.user_id = u.id
                    WHERE 1=1";
            $params = [];

            $this->applyFilters($sql, $params, $filters);
            $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error en getActivities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene total de registros con filtros
     */
    public function getTotalCount(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->logTable} al WHERE 1=1";
            $params = [];
            $this->applyFilters($sql, $params, $filters);

            $result = $this->db->fetch($sql, $params);
            return (int)($result['total'] ?? 0);
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error en getTotalCount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene logs por usuario
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->logTable}
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?",
                [$userId, $limit]
            );
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error en getUserLogs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia logs antiguos
     */
    public function cleanOldLogs(int $days = 90): int
    {
        try {
            return (int)$this->db->execute(
                "DELETE FROM {$this->logTable}
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            );
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error en cleanOldLogs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene estadísticas de actividad
     */
    public function getActivityStatistics(int $days = 30): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT action, COUNT(*) as total, DATE(created_at) as date
                 FROM {$this->logTable}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY action, DATE(created_at)
                 ORDER BY date DESC, total DESC",
                [$days]
            );
        } catch (Throwable $e) {
            error_log('[ActivityLogger] Error en getActivityStatistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene usuarios activos para filtros
     */
    public function getActiveUsers(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT id, username, email FROM users
                 WHERE status = 'active' ORDER BY username"
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ──────────────────────────────────────────────────────────────

    /**
     * Obtiene el ID del usuario actual de forma segura
     */
    private function getCurrentUserId(): ?int
    {
        try {
            if (!class_exists(Auth::class)) {
                return null;
            }
            $id = Auth::id();
            return $id ? (int)$id : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Obtiene el ID del usuario desde contexto API
     */
    private function getApiUserId(): ?int
    {
        if (isset(\App\Core\Request::$apiContext['user_id'])) {
            return (int)\App\Core\Request::$apiContext['user_id'];
        }
        return $this->getCurrentUserId();
    }

    /**
     * Obtiene la IP del cliente de forma segura
     */
    private function getClientIp(): string
    {
        if (PHP_SAPI === 'cli') {
            return '127.0.0.1';
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            return $ips[0];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtiene el User-Agent de forma segura
     */
    private function getUserAgent(): string
    {
        if (PHP_SAPI === 'cli') {
            return 'CLI';
        }
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    }

    /**
     * Aplica filtros comunes a las consultas SQL
     */
    private function applyFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND al.action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
    }
}