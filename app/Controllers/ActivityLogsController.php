<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Database, Auth, CSRF};
use App\Services\ActivityLoggerService;
use App\Services\AuditService;

/**
 * Controlador de logs de actividad
 * 
 * Mejora en funcionalidades de ActivityLogsController
 * 
 * @package App\Controllers
 */
class ActivityLogsController
{
    private ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Lista de logs de actividad con filtros y estadísticas
     */
    public function index(Request $request): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403, 'No tienes permisos para ver logs');
        }

        // Obtener filtros
        $filters = [
            'user_id' => $request->get('user_id', ''),
            'action' => $request->get('action', ''),
            'search' => $request->get('search', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        // Limpiar filtros vacíos
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        // Paginación
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Construir WHERE
        [$where, $params] = $this->buildWhere($filters);

        // Obtener total de registros
        $total = (int)Database::fetch(
            "SELECT COUNT(*) AS total FROM activity_log a $where",
            $params
        )['total'] ?? 0;

        // Obtener logs
        $logs = Database::fetchAll(
            "SELECT a.*, u.username, u.full_name, u.role
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             $where
             ORDER BY a.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        // Obtener datos para filtros
        $users = Database::fetchAll("SELECT id, username, full_name FROM users ORDER BY username");
        $actions = Database::fetchAll("SELECT DISTINCT action FROM activity_log ORDER BY action");
        $actions = array_column($actions, 'action');

        // Estadísticas
        $stats = [
            'total' => $total,
            'today' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
            'this_week' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'] ?? 0,
            'this_month' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0,
            'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0,
        ];

        // Acciones más frecuentes
        $topActions = Database::fetchAll(
            "SELECT action, COUNT(*) as count 
             FROM activity_log 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY action 
             ORDER BY count DESC 
             LIMIT 10"
        );

        Response::render(View::render('admin.activity-logs.index', [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
            'filters' => $filters,
            'users' => $users,
            'actions' => $actions,
            'stats' => $stats,
            'topActions' => $topActions,
        ]));
    }

    /**
     * Muestra un log específico con detalles
     */
    public function show(Request $request, array $params): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403, 'No tienes permisos para ver logs');
        }

        $id = (int)$params['id'];
        
        $log = Database::fetch(
            "SELECT a.*, u.username, u.full_name, u.email, u.role 
             FROM activity_log a 
             LEFT JOIN users u ON u.id = a.user_id 
             WHERE a.id = ?",
            [$id]
        );

        if (!$log) {
            Response::abort(404, 'Log no encontrado');
        }

        // Decodificar datos JSON si existen
        $oldData = !empty($log['old_data']) ? json_decode($log['old_data'], true) : null;
        $newData = !empty($log['new_data']) ? json_decode($log['new_data'], true) : null;

        // Obtener logs relacionados (mismo usuario, misma acción, misma IP)
        $relatedLogs = Database::fetchAll(
            "SELECT a.*, u.username 
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE (a.user_id = ? OR a.ip_address = ?)
             AND a.id != ?
             ORDER BY a.created_at DESC
             LIMIT 10",
            [$log['user_id'], $log['ip_address'], $id]
        );

        Response::render(View::render('admin.activity-logs.show', [
            'log' => $log,
            'oldData' => $oldData,
            'newData' => $newData,
            'relatedLogs' => $relatedLogs,
        ]));
    }

    /**
     * Exporta logs a CSV
     */
    public function export(Request $request): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $filters = [
            'user_id' => $request->get('user_id', ''),
            'action' => $request->get('action', ''),
            'search' => $request->get('search', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        [$where, $params] = $this->buildWhere($filters);

        $logs = Database::fetchAll(
            "SELECT a.*, u.username 
             FROM activity_log a 
             LEFT JOIN users u ON u.id = a.user_id 
             $where 
             ORDER BY a.created_at DESC 
             LIMIT 10000",
            $params
        );

        // Configurar headers para CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.csv"');

        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM para compatibilidad con Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        fputcsv($output, [
            'ID',
            'Fecha',
            'Usuario',
            'Email',
            'Acción',
            'Descripción',
            'IP',
            'User Agent',
            'Método',
            'URL',
            'Datos Antiguos',
            'Datos Nuevos'
        ]);

        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['username'] ?? 'Sistema',
                $log['email'] ?? '',
                $log['action'],
                $log['description'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? '',
                $log['request_method'] ?? '',
                $log['request_url'] ?? '',
                $log['old_data'] ?? '',
                $log['new_data'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Exporta logs a JSON
     */
    public function exportJson(Request $request): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $filters = [
            'user_id' => $request->get('user_id', ''),
            'action' => $request->get('action', ''),
            'search' => $request->get('search', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        [$where, $params] = $this->buildWhere($filters);

        $logs = Database::fetchAll(
            "SELECT a.*, u.username, u.email 
             FROM activity_log a 
             LEFT JOIN users u ON u.id = a.user_id 
             $where 
             ORDER BY a.created_at DESC 
             LIMIT 10000",
            $params
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.json"');

        echo json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Limpia logs antiguos
     */
    public function clean(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $days = (int)$request->post('days', 90);
        
        // Validar días
        if ($days < 1 || $days > 365) {
            flash('error', 'El número de días debe estar entre 1 y 365.');
            Response::redirect(url('admin/audit/activity'));
            return;
        }

        // Contar registros a eliminar
        $count = (int)Database::fetch(
            "SELECT COUNT(*) AS total FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        )['total'] ?? 0;

        if ($count === 0) {
            flash('info', 'No hay registros antiguos para eliminar.');
            Response::redirect(url('admin/audit/activity'));
            return;
        }

        // Eliminar registros
        $deleted = Database::execute(
            "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        // Registrar acción
        AuditService::log(
            'activity_cleaned',
            "Eliminados {$deleted} registros de actividad (más de {$days} días)",
            'audit'
        );

        // Registrar en logger
        $this->logger->logCurrentUser(
            'clean_activity_logs',
            "Limpió {$deleted} logs de actividad (más de {$days} días)"
        );

        flash('success', "Se eliminaron {$deleted} registros de actividad antiguos.");
        Response::redirect(url('admin/audit/activity'));
    }

    /**
     * Obtiene estadísticas en tiempo real
     */
    public function stats(Request $request): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $range = $request->get('range', 'today');
        
        $stats = match ($range) {
            'today' => [
                'total' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
                'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS total FROM activity_log WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
            ],
            'week' => [
                'total' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'] ?? 0,
                'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'] ?? 0,
            ],
            'month' => [
                'total' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0,
                'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0,
            ],
            default => [
                'total' => (int)Database::fetch("SELECT COUNT(*) AS total FROM activity_log")['total'] ?? 0,
                'unique_users' => (int)Database::fetch("SELECT COUNT(DISTINCT user_id) AS total FROM activity_log")['total'] ?? 0,
            ]
        };

        // Obtener acciones por hora (últimas 24h)
        $hourly = Database::fetchAll(
            "SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as count
             FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC"
        );

        // Obtener acciones por día (últimos 7 días)
        $daily = Database::fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
             FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        Response::json([
            'success' => true,
            'stats' => $stats,
            'hourly' => $hourly,
            'daily' => $daily,
        ]);
    }

    /**
     * Construye la cláusula WHERE para filtros
     */
    private function buildWhere(array $filters): array
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
            $where[] = "(a.description LIKE ? OR a.ip_address LIKE ? OR a.request_url LIKE ? OR a.user_agent LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
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

    /**
     * Obtiene logs de un usuario específico
     */
    public function userLogs(Request $request, array $params): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $userId = (int)$params['id'];
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetch(
            "SELECT COUNT(*) AS total FROM activity_log WHERE user_id = ?",
            [$userId]
        )['total'] ?? 0;

        $logs = Database::fetchAll(
            "SELECT * FROM activity_log 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        $user = Database::fetch("SELECT id, username, full_name, email FROM users WHERE id = ?", [$userId]);

        Response::render(View::render('admin.activity-logs.user', [
            'logs' => $logs,
            'user' => $user,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ]));
    }

    /**
     * Obtiene logs de una IP específica
     */
    public function ipLogs(Request $request, array $params): void
    {
        if (!Auth::can('admin.access')) {
            Response::abort(403);
        }

        $ip = $params['ip'] ?? '';
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetch(
            "SELECT COUNT(*) AS total FROM activity_log WHERE ip_address = ?",
            [$ip]
        )['total'] ?? 0;

        $logs = Database::fetchAll(
            "SELECT a.*, u.username 
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.ip_address = ? 
             ORDER BY a.created_at DESC 
             LIMIT ? OFFSET ?",
            [$ip, $perPage, $offset]
        );

        Response::render(View::render('admin.activity-logs.ip', [
            'logs' => $logs,
            'ip' => $ip,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ]));
    }
}