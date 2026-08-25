<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database};
use App\Services\{BanService, AuditService};

class BannedUsersController
{
    public function index(Request $req): void
    {
        $filters = [
            'search'    => $req->get('search'),
            'permanent' => $req->get('filter') === 'permanent',
            'temporary' => $req->get('filter') === 'temporary',
            'expired'   => $req->get('filter') === 'expired',
        ];

        try {
            $bannedUsers = BanService::getBannedUsers($filters);
            $stats = BanService::getStats();
            $topReasons = BanService::getTopReasons();
        } catch (\Throwable $e) {
            error_log('[BannedUsers] Error en index: ' . $e->getMessage());
            $bannedUsers = [];
            $stats = ['total_banned' => 0, 'permanent' => 0, 'temporary' => 0, 'expired' => 0];
            $topReasons = [];
        }

        $filter = $req->get('filter', 'all');
        Response::render(View::render('users.banned', compact('bannedUsers', 'stats', 'topReasons', 'filters', 'filter')));
    }

    /**
     * Desbanear usuario — MÉTODO CRÍTICO
     */
    public function unban(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!(Auth::can('users.ban') ?? false)) {
            Session::flash('flash.error', 'Sin permisos para desbanear usuarios.');
            Response::redirect(url('admin/users/banned'));
        }

        $userId = (int)($params['id'] ?? 0);
        if ($userId <= 0) {
            Session::flash('flash.error', 'ID de usuario inválido.');
            Response::redirect(url('admin/users/banned'));
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            Session::flash('flash.error', 'Usuario no encontrado.');
            Response::redirect(url('admin/users/banned'));
        }

        $reactivate = (bool)$req->post('reactivate', true);
        $notes = trim($req->post('notes', ''));

        try {
            $success = BanService::unban($userId, $notes, $reactivate);
            
            if ($success) {
                AuditService::log(
                    'user_unbanned',
                    "Usuario desbaneado: {$user['username']}",
                    'users',
                    $userId
                );
                Session::flash('flash.success', "Usuario {$user['username']} desbaneado correctamente.");
            } else {
                Session::flash('flash.error', 'No se pudo desbanear al usuario.');
            }
        } catch (\Throwable $e) {
            error_log('[BannedUsers] Error en unban: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al desbanear: ' . $e->getMessage());
        }

        Response::redirect(url('admin/users/banned'));
    }

    public function extend(Request $req, array $params): void
    {
        CSRF::guard();
        
        if (!(Auth::can('users.ban') ?? false)) {
            Session::flash('flash.error', 'Sin permisos.');
            Response::redirect(url('admin/users/banned'));
        }

        $userId = (int)($params['id'] ?? 0);
        $days = (int)$req->post('days', 7);
        $notes = trim($req->post('notes', ''));

        if ($userId <= 0 || $days < 1) {
            Session::flash('flash.error', 'Datos inválidos.');
            Response::redirect(url('admin/users/banned'));
        }

        try {
            BanService::extend($userId, $days, $notes);
            Session::flash('flash.success', "Baneo extendido $days días.");
        } catch (\Throwable $e) {
            Session::flash('flash.error', 'Error al extender: ' . $e->getMessage());
        }

        Response::redirect(url('admin/users/banned'));
    }

    public function ban(Request $req): void
    {
        CSRF::guard();
        
        if (!(Auth::can('users.ban') ?? false)) {
            Session::flash('flash.error', 'Sin permisos.');
            Response::redirect(url('admin/users'));
        }

        $userId = (int)$req->post('user_id', 0);
        $reason = trim($req->post('reason', ''));
        $duration = $req->post('duration');
        $notifyEmail = (bool)$req->post('notify_email', false);

        if ($userId <= 0 || empty($reason)) {
            Session::flash('flash.error', 'Usuario y motivo son obligatorios.');
            Response::redirect(url('admin/users'));
        }

        $durationDays = null;
        if ($duration !== 'permanent' && is_numeric($duration)) {
            $durationDays = max(1, (int)$duration);
        }

        try {
            $success = BanService::ban($userId, $reason, $durationDays, null, $notifyEmail);
            
            if ($success) {
                Session::flash('flash.success', 'Usuario baneado correctamente.');
            } else {
                Session::flash('flash.error', 'No se pudo banear al usuario.');
            }
        } catch (\Throwable $e) {
            Session::flash('flash.error', 'Error al banear: ' . $e->getMessage());
        }

        Response::redirect(url('admin/users/banned'));
    }

    public function history(Request $req, array $params): void
    {
        $userId = (int)$params['id'];
        $user = \App\Models\User::find($userId);
        if (!$user) Response::abort(404);

        $history = BanService::getHistory($userId);
        Response::render(View::render('users.ban_history', compact('user', 'history')));
    }

    public function globalHistory(Request $req): void
    {
        $history = BanService::getGlobalHistory(200);
        Response::render(View::render('users.ban_global_history', compact('history')));
    }

    public function searchUsers(Request $req): void
    {
        $q = trim($req->get('q', ''));
        if (strlen($q) < 2) {
            Response::json(['success' => true, 'data' => []]);
            return;
        }

        try {
            $users = Database::fetchAll(
                "SELECT id, username, email, full_name, role, is_banned 
                   FROM users 
                  WHERE is_banned = 0 AND is_active = 1 
                    AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)
                    AND role <> 'Super admin'
                  ORDER BY username 
                  LIMIT 20",
                ["%$q%", "%$q%", "%$q%"]
            );
            Response::json(['success' => true, 'data' => $users]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function processExpired(Request $req): void
    {
        $count = BanService::processExpiredBans();
        Response::json(['success' => true, 'unbanned' => $count]);
    }
}