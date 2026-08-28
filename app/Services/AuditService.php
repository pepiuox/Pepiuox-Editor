<?php
namespace App\Services;

use App\Core\{Database, Auth};
use Throwable;

/**
 * Servicio de auditoría del sistema
 *
 * Registra acciones críticas en la tabla `audit_logs`.
 * Soporta llamadas por instancia Y estáticas (legacy).
 *
 * @package App\Services
 */
class AuditService
{
    private Database $db;
    private string $table = 'audit_logs';

    /** @var self|null Instancia singleton para llamadas estáticas */
    private static ?self $instance = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
        self::$instance = $this; // Auto-registro
    }

    // ═══════════════════════════════════════════════════════════════
    //  COMPATIBILIDAD ESTÁTICA (legacy)
    // ═══════════════════════════════════════════════════════════════

    /**
     * ✅ Permite llamadas como AuditService::log(...)
     */
    public static function log(?int $userId, string $action, ?string $details = null, ?string $context = null): bool
    {
        if (self::$instance === null) {
            error_log('[AuditService] No hay instancia registrada para llamada estática');
            return false;
        }
        return self::$instance->logInstance($userId, $action, $details, $context);
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════════
    //  MÉTODOS DE ESCRITURA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Registra una acción de auditoría (instancia)
     */
    public function logInstance(?int $userId, string $action, ?string $details = null, ?string $context = null): bool
    {
        try {
            return (bool)$this->db->execute(
                "INSERT INTO {$this->table}
                 (user_id, action, details, context, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $action,
                    $details,
                    $context,
                    $this->getClientIp(),
                    $this->getUserAgent(),
                ]
            );
        } catch (Throwable $e) {
            error_log('[AuditService] Error en logInstance: ' . $e->getMessage());
            return false;
        }
    }

   

    // ═══════════════════════════════════════════════════════════════
    //  MÉTODOS DE LECTURA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obtiene logs con filtros y paginación
     */
    public function getLogs(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        try {
            $sql = "SELECT a.*, u.username, u.email, u.role
                    FROM {$this->table} a
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE 1=1";
            $params = [];

            $this->applyFilters($sql, $params, $filters);
            $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            error_log('[AuditService] Error en getLogs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene total de registros con filtros
     */
    public function getTotalCount(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) AS c FROM {$this->table} a WHERE 1=1";
            $params = [];
            $this->applyFilters($sql, $params, $filters);

            $result = $this->db->fetch($sql, $params);
            return (int)($result['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * ✅ NUEVO: Busca un log por ID
     */
    public function find(int $id): ?array
    {
        try {
            return $this->db->fetch(
                "SELECT a.*, u.username, u.email, u.role
                 FROM {$this->table} a
                 LEFT JOIN users u ON a.user_id = u.id
                 WHERE a.id = ?",
                [$id]
            );
        } catch (Throwable $e) {
            error_log('[AuditService] Error en find: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ NUEVO: Obtiene logs por usuario
     */
    public function getLogsByUser(int $userId, int $limit = 100): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->table}
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?",
                [$userId, $limit]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * ✅ NUEVO: Obtiene logs por IP
     */
    public function getLogsByIp(string $ip, int $limit = 100): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT a.*, u.username
                 FROM {$this->table} a
                 LEFT JOIN users u ON a.user_id = u.id
                 WHERE a.ip_address = ?
                 ORDER BY a.created_at DESC
                 LIMIT ?",
                [$ip, $limit]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  ESTADÍSTICAS Y REPORTES
    // ═══════════════════════════════════════════════════════════════

    /**
     * ✅ NUEVO: Estadísticas para dashboard
     */
    public function getStatistics(int $days = 30): array
    {
        try {
            $today = $this->db->fetch(
                "SELECT COUNT(*) AS c FROM {$this->table}
                 WHERE DATE(created_at) = CURDATE()"
            );
            $week = $this->db->fetch(
                "SELECT COUNT(*) AS c FROM {$this->table}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $unique = $this->db->fetch(
                "SELECT COUNT(DISTINCT user_id) AS c FROM {$this->table}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 AND user_id IS NOT NULL",
                [$days]
            );
            $total = $this->db->fetch(
                "SELECT COUNT(*) AS c FROM {$this->table}"
            );

            return [
                'total'        => (int)($total['c'] ?? 0),
                'today'        => (int)($today['c'] ?? 0),
                'this_week'    => (int)($week['c'] ?? 0),
                'unique_users' => (int)($unique['c'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'today' => 0, 'this_week' => 0, 'unique_users' => 0];
        }
    }

    /**
     * ✅ NUEVO: Estadísticas por acción
     */
    public function getStatisticsByAction(int $days = 30): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT action, COUNT(*) AS total
                 FROM {$this->table}
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY action
                 ORDER BY total DESC
                 LIMIT 20",
                [$days]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * ✅ NUEVO: Lista de acciones únicas (para filtros)
     */
    public function getDistinctActions(): array
    {
        try {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT action FROM {$this->table} ORDER BY action ASC"
            );
            return array_column($rows, 'action');
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * ✅ NUEVO: Usuarios activos (para filtros)
     */
    public function getActiveUsers(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT DISTINCT u.id, u.username, u.email
                 FROM {$this->table} a
                 INNER JOIN users u ON u.id = a.user_id
                 ORDER BY u.username ASC"
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  MANTENIMIENTO
    // ═══════════════════════════════════════════════════════════════

    /**
     * ✅ NUEVO: Limpia logs antiguos
     */
    public function cleanOldLogs(int $days = 90): int
    {
        try {
            return (int)$this->db->execute(
                "DELETE FROM {$this->table}
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            );
        } catch (Throwable $e) {
            error_log('[AuditService] Error en cleanOldLogs: ' . $e->getMessage());
            return 0;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Aplica filtros a la consulta SQL
     */
    private function applyFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND a.action LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['context'])) {
            $sql .= " AND a.context = ?";
            $params[] = $filters['context'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (a.details LIKE ? OR a.ip_address LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
    }

    /**
     * ✅ Obtiene IP de forma segura (compatible CLI)
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
     * ✅ Obtiene User-Agent de forma segura
     */
    private function getUserAgent(): string
    {
        if (PHP_SAPI === 'cli') {
            return 'CLI';
        }
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    }
}