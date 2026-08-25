<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Auth, Session};
use App\Services\{TrustedDeviceService, AuditService};

class TrustedDevicesController
{
    /**
     * Lista dispositivos de confianza del usuario actual.
     */
    public function index(Request $req): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        try {
            $userId = (int)$user['id'];
            $devices = TrustedDeviceService::listDevices($userId);
            $stats = TrustedDeviceService::getStats($userId);
        } catch (\Throwable $e) {
            error_log('[TrustedDevices] Error en index: ' . $e->getMessage());
            // Valores por defecto para evitar que la vista rompa
            $devices = [];
            $stats = ['total' => 0, 'active' => 0, 'current' => 0];
            Session::flash('flash.error', 'Error al cargar dispositivos: ' . $e->getMessage());
        }

        Response::render(View::render('security.trusted_devices', compact('devices', 'stats')));
    }

    /**
     * Revoca un dispositivo específico.
     */
    public function revoke(Request $req, array $params): void
    {
        \App\Core\CSRF::guard();
        
        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        $deviceId = (int)($params['id'] ?? 0);
        if ($deviceId <= 0) {
            Session::flash('flash.error', 'ID de dispositivo inválido.');
            Response::redirect(url('admin/security/trusted-devices'));
        }

        if (TrustedDeviceService::revokeDevice((int)$user['id'], $deviceId)) {
            Session::flash('flash.success', 'Dispositivo revocado correctamente.');
        } else {
            Session::flash('flash.error', 'No se pudo revocar el dispositivo.');
        }

        Response::redirect(url('admin/security/trusted-devices'));
    }

    /**
     * Revoca TODOS los dispositivos del usuario.
     */
    public function revokeAll(Request $req): void
    {
        \App\Core\CSRF::guard();
        
        $user = Auth::user();
        if (!$user) {
            Response::redirect(url('login'));
        }

        $count = TrustedDeviceService::revokeAllDevices((int)$user['id']);
        Session::flash(
            'flash.success', 
            "$count dispositivo(s) revocado(s). Tendrás que volver a verificar 2FA en cada uno."
        );
        Response::redirect(url('admin/security/trusted-devices'));
    }
}