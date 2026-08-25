<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Database, Auth};

class ActivityLogsController
{
    public function index(Request $req): void
    {
        $filters = [
            'user_id' => $req->get('user_id'),
            'action' => $req->get('action'),
            'search' => $req->get('search'),
            'date_from' => $req->get('date_from'),
            'date_to' => $req->get('date_to'),
        ];
        
        $page = max(1, (int)$req->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        [$where, $params] = self::buildWhere($filters);
        
        $total = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM activity_log $where",
            $params
        )['c'];
        
        $logs = Database::fetchAll(
            "SELECT a.*, u.username, u.full_name, u.role
               FROM activity_log a
               LEFT JOIN users u ON u.id = a.user_id
              $where
              ORDER BY a.created_at DESC
              LIMIT $perPage OFFSET $offset",
            $params
        );
        
        $users = Database::fetchAll("SELECT id, username FROM users ORDER BY username");
        $actions = Database::fetchAll("SELECT DISTINCT action FROM activity_log ORDER BY action");
        $actions = array_column($actions, 'action');
        
        $stats = [
            'total' => $total,
            'today' => (int)Database::fetch("SELECT COUNT(*) AS c FROM activity_log WHERE DATE(created_at) = CURDATE()")['c'],
            'this_week' => (int)Database::fetch("SELECT COUNT(*) AS c FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'],
            'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS c FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'],
        ];
        
        Response::render(View::render('audit.activity', compact(
            'logs','filters','page','perPage','total','users','actions','stats'
        )));
    }

    public function show(Request $req, array $params): void
    {
        $log = Database::fetch("SELECT a.*, u.username FROM activity_log a LEFT JOIN users u ON u.id = a.user_id WHERE a.id = ?", [(int)$params['id']]);
        if (!$log) Response::abort(404);
        
        $oldData = $log['old_data'] ? json_decode($log['old_data'], true) : null;
        $newData = $log['new_data'] ? json_decode($log['new_data'], true) : null;
        
        Response::render(View::render('audit.detail', compact('log','oldData','newData')));
    }

    public function export(Request $req): void
    {
        $filters = [
            'user_id' => $req->get('user_id'),
            'action' => $req->get('action'),
            'date_from' => $req->get('date_from'),
            'date_to' => $req->get('date_to'),
        ];
        
        [$where, $params] = self::buildWhere($filters);
        
        $logs = Database::fetchAll(
            "SELECT a.*, u.username FROM activity_log a LEFT JOIN users u ON u.id = a.user_id $where ORDER BY a.created_at DESC LIMIT 10000",
            $params
        );
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.csv"');
        
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID','Fecha','Usuario','Acción','Descripción','IP','User Agent','Método','URL']);
        foreach ($logs as $l) {
            fputcsv($fp, [
                $l['id'], $l['created_at'], $l['username'] ?? 'Sistema',
                $l['action'], $l['description'], $l['ip_address'],
                $l['user_agent'], $l['request_method'], $l['request_url']
            ]);
        }
        fclose($fp);
        exit;
    }

    public function clean(Request $req): void
    {
        csrf_guard();
        $days = (int)$req->post('days', 90);
        $deleted = Database::execute(
            "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        \App\Services\AuditService::log('activity_cleaned', "Eliminados $deleted registros de >$days días", 'audit');
        \App\Core\Session::flash('flash.success', "$deleted registros eliminados.");
        Response::redirect(url('admin/audit/activity'));
    }

    private static function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "a.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = "a.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(a.description LIKE ? OR a.ip_address LIKE ? OR a.request_url LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        if (!empty($filters['date_from'])) {
            $where[] = "a.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereSql, $params];
    }
}