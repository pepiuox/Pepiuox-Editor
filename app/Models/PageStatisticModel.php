<?php
namespace App\Models;

use App\Core\Database;

/**
 * Modelo de estadísticas de páginas
 * 
 * @package App\Models
 */
class PageStatisticsModel
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Registra una visita a una página
     */
    public function registerVisit(int $pageId, string $ipAddress, ?string $userAgent = null): bool
    {
        try {
            $this->db->execute(
                "INSERT INTO visits (page_id, ip_address, user_agent, visit_date)
                 VALUES (?, ?, ?, NOW())",
                [$pageId, $ipAddress, $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '']
            );

            return $this->updateStatistics($pageId);
        } catch (\Throwable $e) {
            error_log('[PageStatistics] Error al registrar visita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza las estadísticas de una página
     */
    private function updateStatistics(int $pageId): bool
    {
        return $this->db->execute(
            "INSERT INTO page_statistics (page_id, visits, last_visit)
             VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                visits = visits + 1,
                last_visit = NOW()",
            [$pageId]
        );
    }

    /**
     * Obtiene estadísticas de una página
     */
    public function getPageStatistics(int $pageId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM page_statistics WHERE page_id = ?",
            [$pageId]
        );
    }

    /**
     * Obtiene las páginas más visitadas
     */
    public function getMostVisited(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.title, p.slug, s.visits, s.last_visit
             FROM pages p
             INNER JOIN page_statistics s ON p.id = s.page_id
             WHERE p.is_published = 1 AND p.status = 'published'
             ORDER BY s.visits DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene visitas por fecha
     */
    public function getVisitsByDate(int $pageId, string $startDate, string $endDate): array
    {
        return $this->db->fetchAll(
            "SELECT DATE(visit_date) as date, COUNT(*) as count
             FROM visits
             WHERE page_id = ? AND visit_date BETWEEN ? AND ?
             GROUP BY DATE(visit_date)
             ORDER BY date ASC",
            [$pageId, $startDate, $endDate]
        );
    }

    /**
     * Obtiene total de visitas de todas las páginas
     */
    public function getTotalVisits(): int
    {
        $result = $this->db->fetch("SELECT SUM(visits) as total FROM page_statistics");
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtiene visitas por IP
     */
    public function getVisitsByIP(string $ip): array
    {
        return $this->db->fetchAll(
            "SELECT v.*, p.title as page_title
             FROM visits v
             INNER JOIN pages p ON v.page_id = p.id
             WHERE v.ip_address = ?
             ORDER BY v.visit_date DESC",
            [$ip]
        );
    }

    /**
     * Limpia estadísticas antiguas
     */
    public function cleanOldVisits(int $days = 30): int
    {
        return $this->db->execute(
            "DELETE FROM visits WHERE visit_date < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    /**
     * Obtiene estadísticas de páginas por usuario
     */
    public function getStatsByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.title, p.slug, COALESCE(s.visits, 0) as visits, s.last_visit
             FROM pages p
             LEFT JOIN page_statistics s ON p.id = s.page_id
             WHERE p.user_id = ? AND p.is_published = 1
             ORDER BY s.visits DESC",
            [$userId]
        );
    }
}