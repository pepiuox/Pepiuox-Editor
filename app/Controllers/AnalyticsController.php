<?php
namespace App\Controllers;
use App\Core\{Request, Response, View};
use App\Services\AnalyticsService;

class AnalyticsController
{
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