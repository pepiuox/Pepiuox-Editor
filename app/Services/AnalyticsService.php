<?php
namespace App\Services;
use App\Core\Database;

class AnalyticsService
{
    /** Registra un evento analítico. */
    public static function track(string $eventType, array $data = []): void
    {
        try {
            Database::insert(
                "INSERT INTO analytics_events (event_type, entity_type, entity_id, user_id, session_id, ip_address, user_agent, referrer, url, metadata)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                [
                    $eventType,
                    $data['entity_type'] ?? null,
                    $data['entity_id'] ?? null,
                    $data['user_id'] ?? \App\Core\Auth::id(),
                    session_id(),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $_SERVER['HTTP_REFERER'] ?? null,
                    $_SERVER['REQUEST_URI'] ?? null,
                    isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
                ]
            );
        } catch (\Throwable $e) { /* silencioso */ }
    }

    /** Resumen para el dashboard. */
    public static function summary(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));

        $visits = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM visits WHERE visit_date >= ?", [$since . ' 00:00:00']
        )['c'];

        $uniqueVisitors = (int)Database::fetch(
            "SELECT COUNT(DISTINCT ip_address) AS c FROM visits WHERE visit_date >= ?", [$since . ' 00:00:00']
        )['c'];

        $logins = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM user_logins WHERE login_date >= ? AND login_success = 1", [$since]
        )['c'];

        $failedLogins = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ? AND successful = 0", [$since . ' 00:00:00']
        )['c'];

        $newUsers = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM users WHERE created_at >= ?", [$since . ' 00:00:00']
        )['c'];

        return compact('visits','uniqueVisitors','logins','failedLogins','newUsers');
    }

    /** Visitas por día (últimos N días). */
    public static function visitsByDay(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        $rows = Database::fetchAll(
            "SELECT DATE(visit_date) AS day, COUNT(*) AS visits, COUNT(DISTINCT ip_address) AS unique_visitors
               FROM visits
              WHERE visit_date >= ?
              GROUP BY DATE(visit_date)
              ORDER BY day ASC",
            [$since . ' 00:00:00']
        );
        // Rellenar días sin datos
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $found = current(array_filter($rows, fn($r) => $r['day'] === $day)) ?: ['day'=>$day,'visits'=>0,'unique_visitors'=>0];
            $out[] = $found;
        }
        return $out;
    }

    /** Top páginas más visitadas. */
    public static function topPages(int $days = 30, int $limit = 10): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        return Database::fetchAll(
            "SELECT p.id, p.title, p.slug, COUNT(v.id) AS visits, COUNT(DISTINCT v.ip_address) AS unique_visitors
               FROM pages p
               LEFT JOIN visits v ON v.page_id = p.id AND v.visit_date >= ?
              WHERE p.status = 'published'
              GROUP BY p.id
              ORDER BY visits DESC
              LIMIT ?",
            [$since . ' 00:00:00', $limit]
        );
    }

    /** Logins por día. */
    public static function loginsByDay(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        $rows = Database::fetchAll(
            "SELECT login_date AS day,
                    SUM(CASE WHEN login_success=1 THEN 1 ELSE 0 END) AS success,
                    SUM(CASE WHEN login_success=0 THEN 1 ELSE 0 END) AS failed
               FROM user_logins
              WHERE login_date >= ?
              GROUP BY login_date
              ORDER BY day ASC",
            [$since]
        );
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $found = current(array_filter($rows, fn($r) => $r['day'] === $day)) ?: ['day'=>$day,'success'=>0,'failed'=>0];
            $out[] = $found;
        }
        return $out;
    }

    /** IPs sospechosas (muchos intentos fallidos). */
    public static function suspiciousIps(int $days = 7, int $threshold = 5): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        return Database::fetchAll(
            "SELECT ip_address, COUNT(*) AS attempts, MAX(attempt_time) AS last_attempt
               FROM login_attempts
              WHERE attempt_time >= ? AND successful = 0
              GROUP BY ip_address
              HAVING attempts >= ?
              ORDER BY attempts DESC
              LIMIT 20",
            [$since . ' 00:00:00', $threshold]
        );
    }

    /** Uso de API. */
    public static function apiUsage(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        return Database::fetchAll(
            "SELECT DATE(created_at) AS day, COUNT(*) AS calls, AVG(execution_time) AS avg_time
               FROM api_access_logs
              WHERE created_at >= ?
              GROUP BY DATE(created_at)
              ORDER BY day ASC",
            [$since . ' 00:00:00']
        );
    }

    /** Distribución de estados de páginas. */
    public static function pageStatusDistribution(): array
    {
        return Database::fetchAll(
            "SELECT status, COUNT(*) AS total FROM pages GROUP BY status"
        );
    }

    /** Actividad de usuarios (últimos N días). */
    public static function userActivity(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-$days days"));
        return Database::fetchAll(
            "SELECT u.id, u.username, u.role,
                    COUNT(DISTINCT DATE(l.login_date)) AS active_days,
                    COUNT(l.id) AS total_logins,
                    MAX(l.login_date) AS last_login
               FROM users u
               LEFT JOIN user_logins l ON u.id = l.user_id AND l.login_date >= ? AND l.login_success = 1
              WHERE u.is_active = 1
              GROUP BY u.id
              ORDER BY total_logins DESC
              LIMIT 20",
            [$since]
        );
    }

    /** Exporta a CSV. */
    public static function exportCsv(string $type, int $days = 30): string
    {
        $data = match($type) {
            'visits' => self::visitsByDay($days),
            'logins' => self::loginsByDay($days),
            'pages'  => self::topPages($days, 100),
            default  => [],
        };
        if (!$data) return '';
        $fp = fopen('php://temp', 'w');
        fputcsv($fp, array_keys($data[0]));
        foreach ($data as $row) fputcsv($fp, $row);
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
}