<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use PDOException;
use Throwable;

/**
 * Servicio de registro de actividades
 * 
 * @package App\Services
 */
class ActivityLoggerService
{
    private Database $db;
    private string $table = 'user_activities';
    private string $logTable = 'activity_logs';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Registra una actividad de usuario
     */
    public function log(
        ?int $userId,
        string $action,
        ?string $details = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        try {
            $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

            return $this->db->execute(
                "INSERT INTO activity_logs 
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
     * Registra actividad del usuario actual
     */
    public function logCurrentUser(string $action, ?string $details = null): bool
    {
        return $this->log(Auth::id(), $action, $details);
    }

    /**
     * Obtiene actividades con filtros y paginación
     */
    public function getActivities(
        int $limit = 50,
        int $offset = 0,
        array $filters = []
    ): array {
        $sql = "SELECT 
                    al.*,
                    u.username,
                    u.email
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];

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

        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene total de registros con filtros
     */
    public function getTotalCount(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];

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

        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtiene logs por usuario
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM activity_logs
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Limpia logs antiguos
     */
    public function cleanOldLogs(int $days = 90): int
    {
        return (int)$this->db->execute(
            "DELETE FROM activity_logs
             WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    /**
     * Obtiene usuarios activos para filtros
     */
    public function getActiveUsers(): array
    {
        return $this->db->fetchAll(
            "SELECT id, username, email FROM users WHERE status = 'active' ORDER BY username"
        );
    }

    /**
     * Obtiene estadísticas de actividad
     */
    public function getActivityStatistics(int $days = 30): array
    {
        return $this->db->fetchAll(
            "SELECT 
                action,
                COUNT(*) as total,
                DATE(created_at) as date
             FROM activity_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY action, DATE(created_at)
             ORDER BY date DESC, total DESC",
            [$days]
        );
    }

    /**
     * Obtiene actividad por tipo
     */
    public function getActivitiesByType(string $action, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT 
                al.*, 
                u.username, 
                u.email,
                DATE_FORMAT(al.created_at, '%d/%m/%Y %H:%i:%s') as formatted_date
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.action = ?
             ORDER BY al.created_at DESC
             LIMIT ?",
            [$action, $limit]
        );
    }

    /**
     * Obtiene actividades por rango de fechas
     */
    public function getActivitiesByDateRange(string $startDate, string $endDate): array
    {
        return $this->db->fetchAll(
            "SELECT 
                al.*, 
                u.username, 
                u.email,
                DATE_FORMAT(al.created_at, '%d/%m/%Y %H:%i:%s') as formatted_date
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE DATE(al.created_at) BETWEEN ? AND ?
             ORDER BY al.created_at DESC",
            [$startDate, $endDate]
        );
    }

    /**
     * Obtiene actividades de usuarios
     */
    public function getUserActivities(?int $userId = null, int $limit = 50): array
    {
        $sql = "SELECT 
                    ua.*, 
                    u.username, 
                    u.role,
                    DATE_FORMAT(ua.created_at, '%d/%m/%Y %H:%i:%s') as formatted_date
                FROM " . $this->table . " ua
                LEFT JOIN users u ON ua.user_id = u.id";

        $params = [];
        if ($userId !== null) {
            $sql .= " WHERE ua.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY ua.created_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Registra actividad con múltiples métodos (backward compatibility)
     */
    public function logActivity(int $userId, string $activityType, string $description): bool
    {
        return $this->db->execute(
            "INSERT INTO " . $this->table . " 
             (user_id, activity_type, description, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $activityType,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
            ]
        );
    }

    /**
     * Obtiene usuarios para filtros
     */
    public function getUsers(): array
    {
        return $this->db->fetchAll("SELECT id, username FROM users ORDER BY username");
    }
}