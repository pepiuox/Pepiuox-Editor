<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Services\{AnalyticsService, AnalyticsProvidersService, SettingService, AuditService};

class AnalyticsController
{
    /**
     * Permiso necesario para cambiar los proveedores de medición.
     *
     * Integrar un proveedor es configuración del sitio, no una consulta de
     * métricas: quien solo puede ver analítica no debe poder enchufar un script
     * externo en todas las páginas.
     */
    private const PERMISSION = 'settings.manage';

    /**
     * Página de integración de proveedores de medición externa.
     *
     * @param Request $req Petición actual.
     */
    public function providers(Request $req): void
    {
        $this->guardManage($req);

        $ajustes = [];
        foreach (array_keys(AnalyticsProvidersService::FIELDS) as $clave) {
            $ajustes[$clave] = (string) SettingService::get(AnalyticsProvidersService::GROUP, $clave, '');
        }

        Response::render(View::render('analytics.providers', [
            'ajustes'  => $ajustes,
            'vigentes' => AnalyticsProvidersService::vigentes(),
        ]));
    }

    /**
     * Guarda los identificadores de los proveedores.
     *
     * Lo que no encaja con el patrón de su proveedor se guarda vacío y se avisa:
     * es preferible perder la configuración a publicar código en el sitio.
     *
     * @param Request $req Petición con los campos del formulario.
     */
    public function saveProviders(Request $req): void
    {
        $this->guardManage($req);
        CSRF::guard();

        $resultado = AnalyticsProvidersService::filtrarEntrada($_POST);

        foreach ($resultado['valores'] as $clave => $valor) {
            SettingService::set(AnalyticsProvidersService::GROUP, $clave, $valor, 'string');
        }

        AuditService::log(
            Auth::id(),
            'analytics_providers_updated',
            'Proveedores de analítica actualizados',
            'settings'
        );

        if ($resultado['descartados'] === []) {
            Session::flash('flash.success', 'Proveedores de analítica guardados.');
        } else {
            Session::flash(
                'flash.error',
                'Se descartaron por formato inválido: ' . implode(', ', $resultado['descartados'])
                . '. Revisa el identificador de cada uno.'
            );
        }

        Response::redirect(url('admin/analytics/proveedores'));
    }

    /**
     * Exige el permiso de configuración o corta la petición.
     *
     * @param Request $req Petición actual.
     */
    private function guardManage(Request $req): void
    {
        if (Auth::can(self::PERMISSION)) {
            return;
        }

        if ($req->isAjax()) {
            Response::json(['success' => false, 'message' => 'No tienes permiso para cambiar la analítica.'], 403);
        }

        Response::abort(403, 'No tienes permiso para cambiar la analítica.');
    }

    public function index(Request $req): void
    {
        $days = (int)$req->get('days', 30);
        $summary = AnalyticsService::summary($days);
        $visitsByDay = AnalyticsService::visitsByDay($days);
        $loginsByDay = AnalyticsService::loginsByDay($days);
        $topPages = AnalyticsService::topPages($days);
        $suspiciousIps = AnalyticsService::suspiciousIps(min($days, 7));
        $apiUsage = AnalyticsService::apiUsage($days);
        $pageStatus = AnalyticsService::pageStatusDistribution();
        $userActivity = AnalyticsService::userActivity($days);

        Response::render(View::render('analytics.index', compact(
            'days','summary','visitsByDay','loginsByDay','topPages',
            'suspiciousIps','apiUsage','pageStatus','userActivity'
        )));
    }

    public function export(Request $req): void
    {
        $type = $req->get('type', 'visits');
        $days = (int)$req->get('days', 30);
        $csv = AnalyticsService::exportCsv($type, $days);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics_' . $type . '_' . date('Ymd') . '.csv"');
        echo $csv;
        exit;
    }

    public function apiSummary(Request $req): void
    {
        $days = (int)$req->get('days', 30);
        Response::json([
            'success' => true,
            'data' => [
                'summary' => AnalyticsService::summary($days),
                'visits' => AnalyticsService::visitsByDay($days),
                'logins' => AnalyticsService::loginsByDay($days),
            ]
        ]);
    }
}