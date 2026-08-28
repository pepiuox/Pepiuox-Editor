<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, CSRF, Session};
use App\Core\Database;
use App\Services\ActivityLoggerService;
use Throwable;

/**
 * Controlador de registros de actividad
 *
 * @package App\Controllers
 */
class ActivityLogsController
{
    private ActivityLoggerService $logger;
    private Database $db;

    public function __construct(ActivityLoggerService $logger, Database $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Muestra el listado de actividades con filtros y paginación
     */
    public function index(Request $request): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403, 'No tienes permisos para ver los registros de actividad');
        }

        try {
            $page    = max(1, (int)$request->get('page', 1));
            $perPage = 50;
            $offset  = ($page - 1) * $perPage;

            $filters = [
                'user_id'   => $request->get('user_id'),
                'action'    => $request->get('action'),
                'date_from' => $request->get('date_from'),
                'date_to'   => $request->get('date_to'),
                'search'    => $request->get('search'),
            ];

            $logs  = $this->logger->getActivities($perPage, $offset, $filters);
            $total = $this->logger->getTotalCount($filters);

            $stats = [
                'total'        => $total,
                'today'        => $this->countToday(),
                'this_week'    => $this->countThisWeek(),
                'unique_users' => $this->countUniqueUsers(30),
            ];

            $users   = $this->logger->getActiveUsers();
            $actions = $this->getDistinctActions();

            // ✅ Vista correcta: audit.activity → views/audit/activity.php
            Response::render(View::render('audit.activity', [
                'logs'      => $logs,
                'total'     => $total,
                'page'      => $page,
                'perPage'   => $perPage,
                'filters'   => $filters,
                'stats'     => $stats,
                'users'     => $users,
                'actions'   => $actions,
            ]));
        } catch (Throwable $e) {
            error_log('[ActivityLogsController] Error en index: ' . $e->getMessage());
            Response::render(View::render('audit.activity', [
                'logs' => [], 'total' => 0, 'page' => 1, 'perPage' => 50,
                'filters' => [], 'stats' => ['total' => 0, 'today' => 0, 'this_week' => 0, 'unique_users' => 0],
                'users' => [], 'actions' => [],
            ]));
        }
    }

    /**
     * Muestra el detalle de una actividad
     */
    public function show(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $id = (int)$params['id'];
        try {
            $log = $this->db->fetch(
                "SELECT al.*, u.username, u.email, u.role
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.id = ?",
                [$id]
            );

            if (!$log) {
                Response::abort(404, 'Registro no encontrado');
            }

            Response::render(View::render('audit.detail', ['log' => $log]));
        } catch (Throwable $e) {
            error_log('[ActivityLogsController] Error en show: ' . $e->getMessage());
            Response::abort(500, 'Error al cargar el detalle');
        }
    }

    /**
     * Exporta registros a CSV
     */
    public function export(Request $request): void
    {
        if (!Auth::can('audit.export')) {
            Response::abort(403);
        }

        $filters = [
            'user_id'   => $request->get('user_id'),
            'action'    => $request->get('action'),
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
            'search'    => $request->get('search'),
        ];

        $logs = $this->logger->getActivities(10000, 0, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity-logs-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Fecha', 'Usuario', 'Acción', 'Descripción', 'IP', 'User-Agent']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'] ?? '',
                $log['username'] ?? 'Sistema',
                $log['action'] ?? '',
                $log['details'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportJson(Request $request): void
    {
        if (!Auth::can('audit.export')) {
            Response::abort(403);
        }

        $filters = [
            'user_id'   => $request->get('user_id'),
            'action'    => $request->get('action'),
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
        ];

        $logs = $this->logger->getActivities(10000, 0, $filters);
        Response::json(['success' => true, 'logs' => $logs]);
    }

    public function stats(Request $request): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $stats = [
            'total'        => $this->logger->getTotalCount(),
            'today'        => $this->countToday(),
            'this_week'    => $this->countThisWeek(),
            'unique_users' => $this->countUniqueUsers(30),
            'by_action'    => $this->logger->getActivityStatistics(30),
        ];

        Response::json(['success' => true, 'stats' => $stats]);
    }

    public function userLogs(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $userId = (int)$params['id'];
        $logs   = $this->logger->getUserLogs($userId, 200);
        Response::json(['success' => true, 'logs' => $logs]);
    }

    public function ipLogs(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $ip = $params['ip'] ?? '';
        try {
            $logs = $this->db->fetchAll(
                "SELECT al.*, u.username
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.ip_address = ?
                 ORDER BY al.created_at DESC
                 LIMIT 200",
                [$ip]
            );
            Response::json(['success' => true, 'logs' => $logs]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error al consultar logs']);
        }
    }

    public function clean(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('audit.delete')) {
            Response::abort(403);
        }

        $days = (int)$request->post('days', 90);

        try {
            $deleted = $this->logger->cleanOldLogs($days);
            Session::flash('flash.success', "Se eliminaron {$deleted} registros antiguos");
        } catch (Throwable $e) {
            error_log('[ActivityLogsController] Error en clean: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al limpiar registros');
        }

        Response::redirect(url('admin/audit/activity'));
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ──────────────────────────────────────────────────────────────

    private function countToday(): int
    {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()"
            );
            return (int)($result['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function countThisWeek(): int
    {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as total FROM activity_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            return (int)($result['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function countUniqueUsers(int $days): int
    {
        try {
            $result = $this->db->fetch(
                "SELECT COUNT(DISTINCT user_id) as total FROM activity_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND user_id IS NOT NULL",
                [$days]
            );
            return (int)($result['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function getDistinctActions(): array
    {
        try {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT action FROM activity_logs ORDER BY action ASC"
            );
            return array_column($rows, 'action');
        } catch (Throwable $e) {
            return [];
        }
    }
}