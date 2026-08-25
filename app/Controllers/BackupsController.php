<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, Auth, Database};
use App\Services\{BackupService, AuditService, NotificationService};

class BackupsController
{
    public function index(Request $req): void
    {
        $backups = BackupService::all();
        $stats = BackupService::stats();
        $schedules = Database::fetchAll("SELECT * FROM backup_schedule ORDER BY name");
        Response::render(View::render('backups.index', compact('backups','stats','schedules')));
    }

    public function create(Request $req): void
    {
        csrf_guard(); // Usa el helper global
        $type = $req->post('type', 'full');
        try {
            $result = BackupService::create($type, true);
            AuditService::log('backup_created', "Backup creado: $type", 'backups', $result['id']);
            NotificationService::notify(Auth::id(), 'success', 'Backup creado', "Se generó un backup $type correctamente.", url('admin/backups'), 'check-circle');
            Session::flash('flash.success', 'Backup creado correctamente.');
        } catch (\Throwable $e) {
            AuditService::log('backup_failed', "Error: " . $e->getMessage(), 'backups');
            Session::flash('flash.error', 'Error al crear backup: ' . $e->getMessage());
        }
        Response::redirect(url('admin/backups'));
    }

    public function download(Request $req, array $params): void
    {
        BackupService::download((int)$params['id']);
    }

    public function restore(Request $req, array $params): void
    {
        csrf_guard();
        if (!Auth::can('backups.restore')) {
            Session::flash('flash.error', 'Sin permisos para restaurar.');
            Response::redirect(url('admin/backups'));
        }
        try {
            BackupService::restore((int)$params['id']);
            AuditService::log('backup_restored', "Backup restaurado ID {$params['id']}", 'backups', (int)$params['id']);
            Session::flash('flash.success', 'Backup restaurado. Se recomienda cerrar sesión y volver a entrar.');
        } catch (\Throwable $e) {
            Session::flash('flash.error', 'Error al restaurar: ' . $e->getMessage());
        }
        Response::redirect(url('admin/backups'));
    }

    public function delete(Request $req, array $params): void
    {
        csrf_guard();
        BackupService::delete((int)$params['id']);
        Session::flash('flash.success', 'Backup eliminado.');
        Response::redirect(url('admin/backups'));
    }

    public function schedule(Request $req): void
    {
        CSRF::guard();
        $id = $req->post('id');
        $data = [
            'name' => $req->post('name'),
            'type' => $req->post('type', 'full'),
            'frequency' => $req->post('frequency', 'daily'),
            'retention_days' => (int)$req->post('retention_days', 30),
            'is_active' => (int)$req->post('is_active', 1),
        ];
        if ($id) {
            $fields = []; $params = [];
            foreach ($data as $k => $v) { $fields[] = "`$k` = ?"; $params[] = $v; }
            $params[] = $id;
            Database::execute("UPDATE backup_schedule SET " . implode(',', $fields) . " WHERE id = ?", $params);
        } else {
            Database::insert(
                "INSERT INTO backup_schedule (name, type, frequency, retention_days, is_active) VALUES (?,?,?,?,?)",
                [$data['name'], $data['type'], $data['frequency'], $data['retention_days'], $data['is_active']]
            );
        }
        Session::flash('flash.success', 'Programación guardada.');
        Response::redirect(url('admin/backups'));
    }

    /** Endpoint para cron externo: /cron/backup?token=XXX */
    public function cron(Request $req): void
    {
        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $token = $req->get('token');
        $expected = $cfg['cron_token'] ?? '';
        if (!$expected || $token !== $expected) {
            Response::json(['success'=>false,'message'=>'Unauthorized'], 401);
        }
        $results = BackupService::runScheduled();
        Response::json(['success'=>true,'results'=>$results]);
    }
}
