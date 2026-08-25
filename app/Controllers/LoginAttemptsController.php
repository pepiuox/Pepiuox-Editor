<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Database, Auth, Session};

class LoginAttemptsController
{
    public function index(Request $req): void
    {
        $page = max(1, (int)$req->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $filter = $req->get('filter', 'all'); // all, failed, success
        
        $where = '';
        $params = [];
        if ($filter === 'failed') {
            $where = "WHERE successful = 0";
        } elseif ($filter === 'success') {
            $where = "WHERE successful = 1";
        }
        
        $total = (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts $where", $params)['c'];
        
        $attempts = Database::fetchAll(
            "SELECT * FROM login_attempts $where ORDER BY attempt_time DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        
        $blockedIps = Database::fetchAll("SELECT * FROM blocked_ips ORDER BY created_at DESC");
        
        $stats = [
            'total_today' => (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts WHERE DATE(attempt_time) = CURDATE()")['c'],
            'failed_today' => (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts WHERE DATE(attempt_time) = CURDATE() AND successful = 0")['c'],
            'success_today' => (int)Database::fetch("SELECT COUNT(*) AS c FROM login_attempts WHERE DATE(attempt_time) = CURDATE() AND successful = 1")['c'],
            'blocked_ips' => count($blockedIps),
            'top_failed_ips' => Database::fetchAll(
                "SELECT ip_address, COUNT(*) AS attempts, MAX(attempt_time) AS last_attempt
                   FROM login_attempts
                  WHERE successful = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY ip_address
                  HAVING attempts >= 3
                  ORDER BY attempts DESC
                  LIMIT 10"
            ),
        ];
        
        Response::render(View::render('security.login_attempts', compact(
            'attempts','blockedIps','stats','filter','page','perPage','total'
        )));
    }

    public function blockIp(Request $req): void
    {
        csrf_guard();
        $ip = trim($req->post('ip_address'));
        $reason = trim($req->post('reason'));
        $days = (int)$req->post('days', 30);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Session::flash('flash.error', 'IP inválida.');
            Response::redirect(url('admin/security/login-attempts'));
        }
        
        // NUEVO: Notificar al admin por email
        try {
            \App\Services\EmailNotificationService::notifyIpBlocked(
                $ip,
                0,
                $reason ?: 'Bloqueo manual desde el panel'
            );
        } catch (\Throwable $e) {
            error_log('[LoginAttempts] Error enviando email: ' . $e->getMessage());
        }

        Database::execute(
            "INSERT INTO blocked_ips (ip_address, reason, blocked_until) VALUES (?,?,DATE_ADD(NOW(), INTERVAL ? DAY))
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_until = VALUES(blocked_until)",
            [$ip, $reason, $days]
        );
        
        \App\Services\AuditService::log('ip_blocked', "IP bloqueada: $ip por $days días", 'security');
        Session::flash('flash.success', "IP $ip bloqueada por $days días.");
        Response::redirect(url('admin/security/login-attempts'));
    }

    public function unblockIp(Request $req, array $params): void
    {
        csrf_guard();
        Database::execute("DELETE FROM blocked_ips WHERE id = ?", [(int)$params['id']]);
        \App\Services\AuditService::log('ip_unblocked', "IP desbloqueada ID {$params['id']}", 'security');
        Session::flash('flash.success', 'IP desbloqueada.');
        Response::redirect(url('admin/security/login-attempts'));
    }

    public function cleanAttempts(Request $req): void
    {
        csrf_guard();
        $days = (int)$req->post('days', 30);
        $deleted = Database::execute(
            "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        \App\Services\AuditService::log('login_attempts_cleaned', "Eliminados $deleted intentos de >$days días", 'security');
        Session::flash('flash.success', "$deleted registros eliminados.");
        Response::redirect(url('admin/security/login-attempts'));
    }

    public function checkBlocked(Request $req): void
    {
        // Middleware para verificar si la IP está bloqueada
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $blocked = Database::fetch(
            "SELECT * FROM blocked_ips WHERE ip_address = ? AND (blocked_until IS NULL OR blocked_until > NOW())",
            [$ip]
        );
        
        if ($blocked) {
            http_response_code(403);
            echo '<h1>Acceso bloqueado</h1>';
            echo '<p>Tu IP ha sido bloqueada. Motivo: ' . htmlspecialchars($blocked['reason'] ?? 'Seguridad') . '</p>';
            if ($blocked['blocked_until']) {
                echo '<p>Bloqueo hasta: ' . htmlspecialchars($blocked['blocked_until']) . '</p>';
            }
            exit;
        }
    }
}