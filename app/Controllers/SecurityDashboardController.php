<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Database, Auth};

class SecurityDashboardController
{
    /**
     * Dashboard principal de seguridad.
     * Todas las consultas están protegidas con try-catch para evitar
     * que un error en una tabla rompa todo el dashboard.
     */
    public function index(Request $req): void
    {
        // Verificar permisos
        if (!Auth::can('security.dashboard') && !Auth::can('audit.view')) {
            Response::abort(403, 'No tienes permisos para ver el dashboard de seguridad.');
        }

        $period = max(1, min(90, (int)$req->get('period', 7)));
        $since = date('Y-m-d H:i:s', strtotime("-{$period} days"));

        // ═══════════════════════════════════════════════════════════════
        // ESTADÍSTICAS GENERALES (con fallbacks)
        // ═══════════════════════════════════════════════════════════════
        $stats = [
            'total_attempts'   => 0,
            'failed_attempts'  => 0,
            'success_attempts' => 0,
            'blocked_ips'      => 0,
            'banned_users'     => 0,
            'active_sessions'  => 0,
            'two_factor_users' => 0,
        ];

        try {
            $stats['total_attempts'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ?", 
                [$since]
            )['c'];
            
            $stats['failed_attempts'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ? AND successful = 0", 
                [$since]
            )['c'];
            
            $stats['success_attempts'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= ? AND successful = 1", 
                [$since]
            )['c'];
            
            $stats['blocked_ips'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM blocked_ips WHERE blocked_until IS NULL OR blocked_until > NOW()"
            )['c'];
            
            $stats['banned_users'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM users WHERE is_banned = 1"
            )['c'];
            
            $stats['active_sessions'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM user_sessions WHERE last_activity > ?", 
                [time() - 1800]
            )['c'];
            
            $stats['two_factor_users'] = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM users WHERE two_factor_enabled = 1 AND is_active = 1"
            )['c'];
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en stats: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // INTENTOS POR HORA (últimas 24h)
        // ═══════════════════════════════════════════════════════════════
        $hourly = [];
        try {
            $hourlyRaw = Database::fetchAll("
                SELECT 
                    HOUR(attempt_time) AS hour,
                    COUNT(*) AS total,
                    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) AS success,
                    SUM(CASE WHEN successful = 0 THEN 1 ELSE 0 END) AS failed
                FROM login_attempts
                WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY HOUR(attempt_time)
                ORDER BY hour ASC
            ");
            
            // Rellenar horas faltantes
            for ($h = 0; $h < 24; $h++) {
                $found = current(array_filter($hourlyRaw, fn($r) => (int)$r['hour'] === $h)) ?: false;
                $hourly[] = $found ?: [
                    'hour' => $h, 
                    'total' => 0, 
                    'success' => 0, 
                    'failed' => 0
                ];
            }
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en hourly: ' . $e->getMessage());
            // Generar array vacío de 24 horas
            for ($h = 0; $h < 24; $h++) {
                $hourly[] = ['hour' => $h, 'total' => 0, 'success' => 0, 'failed' => 0];
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // INTENTOS POR DÍA
        // ═══════════════════════════════════════════════════════════════
        $dailyAttempts = [];
        try {
            $dailyAttempts = Database::fetchAll("
                SELECT 
                    DATE(attempt_time) AS day,
                    COUNT(*) AS total,
                    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) AS success,
                    SUM(CASE WHEN successful = 0 THEN 1 ELSE 0 END) AS failed
                FROM login_attempts
                WHERE attempt_time >= ?
                GROUP BY DATE(attempt_time)
                ORDER BY day ASC
            ", [$since]);
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en daily: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // TOP IPs FALLIDAS
        // ═══════════════════════════════════════════════════════════════
        $topFailedIps = [];
        try {
            $topFailedIps = Database::fetchAll("
                SELECT 
                    ip_address,
                    COUNT(*) AS attempts,
                    COUNT(DISTINCT username) AS users_targeted,
                    MAX(attempt_time) AS last_attempt
                FROM login_attempts
                WHERE attempt_time >= ? AND successful = 0
                GROUP BY ip_address
                HAVING attempts >= 3
                ORDER BY attempts DESC
                LIMIT 10
            ", [$since]);
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en topFailedIps: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // TOP USUARIOS ATACADOS
        // ═══════════════════════════════════════════════════════════════
        $targetedUsers = [];
        try {
            $targetedUsers = Database::fetchAll("
                SELECT 
                    username,
                    COUNT(*) AS attempts,
                    COUNT(DISTINCT ip_address) AS unique_ips,
                    MAX(attempt_time) AS last_attempt
                FROM login_attempts
                WHERE attempt_time >= ? AND successful = 0
                GROUP BY username
                ORDER BY attempts DESC
                LIMIT 10
            ", [$since]);
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en targetedUsers: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // IPs BLOQUEADAS ACTIVAS
        // ═══════════════════════════════════════════════════════════════
        $activeBlockedIps = [];
        try {
            $activeBlockedIps = Database::fetchAll("
                SELECT * FROM blocked_ips
                WHERE blocked_until IS NULL OR blocked_until > NOW()
                ORDER BY created_at DESC
                LIMIT 20
            ");
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en activeBlockedIps: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // USUARIOS BANEADOS
        // ═══════════════════════════════════════════════════════════════
        $bannedUsers = [];
        try {
            $bannedUsers = Database::fetchAll("
                SELECT id, username, email, role, banned_reason, updated_at
                FROM users WHERE is_banned = 1
                ORDER BY updated_at DESC
                LIMIT 10
            ");
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en bannedUsers: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // SESIONES ACTIVAS
        // ═══════════════════════════════════════════════════════════════
        $activeSessions = [];
        try {
            $activeSessions = Database::fetchAll("
                SELECT 
                    s.id, s.user_id, s.ip_address, s.user_agent, s.last_activity,
                    u.username, u.role
                FROM user_sessions s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.last_activity > ?
                ORDER BY s.last_activity DESC
                LIMIT 20
            ", [time() - 1800]);
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en activeSessions: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // ACTIVIDAD SOSPECHOSA (últimas 24h)
        // ═══════════════════════════════════════════════════════════════
        $suspiciousActivity = [];
        try {
            $suspiciousActivity = Database::fetchAll("
                SELECT 
                    a.*, u.username
                FROM activity_log a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND (
                      a.action LIKE '%failed%'
                      OR a.action LIKE '%blocked%'
                      OR a.action LIKE '%banned%'
                      OR a.action LIKE '%deleted%'
                      OR a.action LIKE '%login%'
                  )
                ORDER BY a.created_at DESC
                LIMIT 30
            ");
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en suspiciousActivity: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // USO DE API
        // ═══════════════════════════════════════════════════════════════
        $apiUsage = [];
        try {
            $apiUsage = Database::fetchAll("
                SELECT 
                    DATE(created_at) AS day,
                    COUNT(*) AS calls,
                    AVG(execution_time) AS avg_time,
                    SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) AS errors
                FROM api_access_logs
                WHERE created_at >= ?
                GROUP BY DATE(created_at)
                ORDER BY day ASC
            ", [$since]);
        } catch (\Throwable $e) {
            error_log('[SecurityDashboard] Error en apiUsage: ' . $e->getMessage());
        }

        // ═══════════════════════════════════════════════════════════════
        // RENDERIZAR VISTA
        // ═══════════════════════════════════════════════════════════════
        Response::render(View::render('security.dashboard', compact(
            'period', 'stats', 'hourly', 'dailyAttempts', 'topFailedIps',
            'targetedUsers', 'activeBlockedIps', 'bannedUsers',
            'activeSessions', 'suspiciousActivity', 'apiUsage'
        )));
    }

    /**
     * Endpoint AJAX para actualización en tiempo real.
     */
    public function realtime(Request $req): void
    {
        if (!Auth::check()) {
            Response::json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        try {
            $stats = [
                'attempts_last_hour' => (int)Database::fetch(
                    "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                )['c'],
                'failed_last_hour' => (int)Database::fetch(
                    "SELECT COUNT(*) AS c FROM login_attempts WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR) AND successful = 0"
                )['c'],
                'active_sessions' => (int)Database::fetch(
                    "SELECT COUNT(*) AS c FROM user_sessions WHERE last_activity > ?", 
                    [time() - 1800]
                )['c'],
                'blocked_ips' => (int)Database::fetch(
                    "SELECT COUNT(*) AS c FROM blocked_ips WHERE blocked_until IS NULL OR blocked_until > NOW()"
                )['c'],
            ];

            Response::json(['success' => true, 'data' => $stats]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}