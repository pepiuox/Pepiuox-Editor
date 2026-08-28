<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, CSRF, Session, Database};
use App\Services\{AuditService, ActivityLoggerService};
use Throwable;

/**
 * Controlador de auditoría general
 *
 * @package App\Controllers
 */
class AuditController
{
    private Database $db;
    private AuditService $auditService;
    private ActivityLoggerService $logger;

    /**
     * ✅ Constructor con todas las dependencias inyectadas
     */
    public function __construct(
        Database $db,
        AuditService $auditService,
        ActivityLoggerService $logger
    ) {
        $this->db           = $db;
        $this->auditService = $auditService;
        $this->logger       = $logger;
    }

    /**
     * Panel principal de auditoría con filtros y paginación
     */
    public function index(Request $request): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403, 'No tienes permisos para ver la auditoría');
        }

        try {
            $page    = max(1, (int)$request->get('page', 1));
            $limit   = 50;
            $offset  = ($page - 1) * $limit;

            // Filtros
            $filters = [
                'user_id'   => $request->get('user_id'),
                'action'    => $request->get('action'),
                'context'   => $request->get('context'),
                'search'    => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to'   => $request->get('date_to'),
            ];

            $logs  = $this->auditService->getLogs($limit, $offset, $filters);
            $total = $this->auditService->getTotalCount($filters);
            $stats = $this->auditService->getStatistics(30);

            // Datos para filtros
            $users   = $this->auditService->getActiveUsers();
            $actions = $this->auditService->getDistinctActions();

            // ✅ Vista: audit.index → views/audit/index.php
            Response::render(View::render('audit.index', [
                'logs'    => $logs,
                'total'   => $total,
                'page'    => $page,
                'limit'   => $limit,
                'filters' => $filters,
                'stats'   => $stats,
                'users'   => $users,
                'actions' => $actions,
            ]));
        } catch (Throwable $e) {
            error_log('[AuditController] Error en index: ' . $e->getMessage());
            Response::render(View::render('audit.index', [
                'logs'    => [],
                'total'   => 0,
                'page'    => 1,
                'limit'   => 50,
                'filters' => [],
                'stats'   => ['total' => 0, 'today' => 0, 'this_week' => 0, 'unique_users' => 0],
                'users'   => [],
                'actions' => [],
            ]));
        }
    }

    /**
     * ✅ NUEVO: Detalle de un log
     */
    public function show(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $id = (int)$params['id'];
        $log = $this->auditService->find($id);

        if (!$log) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Log no encontrado']);
            } else {
                Response::abort(404, 'Registro no encontrado');
            }
            return;
        }

        if ($request->isAjax()) {
            Response::json(['success' => true, 'log' => $log]);
        } else {
            Response::render(View::render('audit.detail', ['log' => $log]));
        }
    }

    /**
     * ✅ NUEVO: Estadísticas en JSON
     */
    public function stats(Request $request): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        try {
            $stats     = $this->auditService->getStatistics(30);
            $byAction  = $this->auditService->getStatisticsByAction(30);

            Response::json([
                'success'   => true,
                'stats'     => $stats,
                'by_action' => $byAction,
            ]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'error' => 'Error al obtener estadísticas']);
        }
    }

    /**
     * ✅ NUEVO: Exporta a CSV
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

        $logs = $this->auditService->getLogs(10000, 0, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-logs-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($output, ['ID', 'Fecha', 'Usuario', 'Acción', 'Contexto', 'Detalles', 'IP', 'User-Agent']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'] ?? '',
                $log['created_at'] ?? '',
                $log['username'] ?? 'Sistema',
                $log['action'] ?? '',
                $log['context'] ?? '',
                $log['details'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * ✅ NUEVO: Exporta a JSON
     */
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

        $logs = $this->auditService->getLogs(10000, 0, $filters);
        Response::json(['success' => true, 'logs' => $logs]);
    }

    /**
     * ✅ NUEVO: Limpia logs antiguos
     */
    public function clean(Request $request): void
    {
        CSRF::guard();
        if (!Auth::can('audit.delete')) {
            Response::abort(403);
        }

        $days = (int)$request->post('days', 90);

        try {
            $deleted = $this->auditService->cleanOldLogs($days);

            // Registrar la acción de limpieza
            $this->auditService->log(
                Auth::id(),
                'audit_logs_cleaned',
                "Eliminó {$deleted} registros de auditoría anteriores a {$days} días",
                'audit'
            );

            if ($request->isAjax()) {
                Response::json([
                    'success' => true,
                    'message' => "Se eliminaron {$deleted} registros",
                    'deleted' => $deleted,
                ]);
            } else {
                Session::flash('flash.success', "Se eliminaron {$deleted} registros antiguos");
                Response::redirect(url('admin/audit'));
            }
        } catch (Throwable $e) {
            error_log('[AuditController] Error en clean: ' . $e->getMessage());
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Error al limpiar'], 500);
            } else {
                Session::flash('flash.error', 'Error al limpiar registros');
                Response::redirect(url('admin/audit'));
            }
        }
    }

    /**
     * ✅ NUEVO: Logs de un usuario específico
     */
    public function userLogs(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $userId = (int)$params['id'];
        $logs = $this->auditService->getLogsByUser($userId, 200);
        Response::json(['success' => true, 'logs' => $logs]);
    }

    /**
     * ✅ NUEVO: Logs por IP
     */
    public function ipLogs(Request $request, array $params): void
    {
        if (!Auth::can('audit.view')) {
            Response::abort(403);
        }

        $ip = $params['ip'] ?? '';
        $logs = $this->auditService->getLogsByIp($ip, 200);
        Response::json(['success' => true, 'logs' => $logs]);
    }
}