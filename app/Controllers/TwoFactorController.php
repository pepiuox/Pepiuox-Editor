<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database};
use App\Services\{TotpService, AuditService, TwoFactorRecoveryService};

class TwoFactorController
{
    /**
     * Muestra la página de configuración 2FA.
     * Si ya está activo, muestra el estado y opción de regenerar códigos.
     */
    public function setup(Request $req): void
    {
        $user = Auth::user();
        if (!$user) Response::redirect(url('login'));

        // Si ya está activo, mostrar estado + códigos restantes
        if ((int)($user['two_factor_enabled'] ?? 0)) {
            $remaining = TwoFactorRecoveryService::remainingCodes((int)$user['id']);
            $history = TwoFactorRecoveryService::getUsageHistory((int)$user['id'], 10);
            Response::render(View::render('2fa.enabled', compact('user', 'remaining', 'history')));
            return;
        }

        // Generar secreto nuevo en sesión si no existe
        if (!Session::has('2fa_secret')) {
            Session::set('2fa_secret', TotpService::generateSecret());
        }
        
        $secret = Session::get('2fa_secret');
        $qr = TotpService::getQrUrl($secret, $user['email']);

        Response::render(View::render('2fa.setup', compact('user', 'secret', 'qr')));
    }

    /**
     * Verifica el código TOTP inicial y activa 2FA.
     * Genera los códigos de recuperación y los muestra UNA SOLA VEZ.
     */
    public function verify(Request $req): void
    {
        CSRF::guard();
        $user = Auth::user();
        if (!$user) Response::redirect(url('login'));

        $secret = Session::get('2fa_secret');
        if (!$secret) Response::redirect(url('admin/2fa'));

        $code = $req->post('code');
        if (!TotpService::verify($secret, (string)$code)) {
            Session::flash('flash.error', 'Código inválido. Inténtalo de nuevo.');
            Response::redirect(url('admin/2fa'));
        }

        Database::begin();
        try {
            // Activar 2FA
            Database::execute(
                "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?",
                [$secret, $user['id']]
            );

            // Generar códigos de recuperación
            $recoveryCodes = TwoFactorRecoveryService::generateForUser((int)$user['id']);

            Database::commit();

            // Limpiar secreto de sesión
            Session::delete('2fa_secret');

            // Guardar códigos en sesión temporal para mostrarlos UNA vez
            Session::set('2fa_new_recovery_codes', $recoveryCodes);

            AuditService::log('2fa_enabled', '2FA activado', 'security', (int)$user['id']);
            
            // Redirigir a vista que muestra los códigos
            Response::redirect(url('admin/2fa/recovery-success'));

        } catch (\Throwable $e) {
            Database::rollback();
            Session::flash('flash.error', 'Error al activar 2FA: ' . $e->getMessage());
            Response::redirect(url('admin/2fa'));
        }
    }

    /**
     * Muestra los códigos de recuperación recién generados (UNA SOLA VEZ).
     */
    public function recoverySuccess(Request $req): void
    {
        $codes = Session::get('2fa_new_recovery_codes');
        
        // Si no hay códigos en sesión, redirigir
        if (!$codes || !is_array($codes)) {
            Session::flash('flash.info', 'Tus códigos de recuperación ya fueron mostrados. Puedes regenerarlos desde tu perfil.');
            Response::redirect(url('admin/2fa'));
        }

        // Formatear códigos para mostrar
        $formattedCodes = array_map(
            fn($c) => TwoFactorRecoveryService::formatCode($c),
            $codes
        );

        // Eliminar de sesión DESPUÉS de pasarlos a la vista
        Session::delete('2fa_new_recovery_codes');

        Response::render(View::render('2fa.recovery-success', compact('formattedCodes')));
    }

    /**
     * Regenera los códigos de recuperación del usuario actual.
     */
    public function regenerateCodes(Request $req): void
    {
        CSRF::guard();
        $user = Auth::user();
        if (!$user) Response::redirect(url('login'));

        if (!(int)($user['two_factor_enabled'] ?? 0)) {
            Session::flash('flash.error', 'Primero debes activar 2FA.');
            Response::redirect(url('admin/2fa'));
        }

        // Confirmación explícita
        if (!$req->post('confirm')) {
            Session::flash('flash.error', 'Debes confirmar la regeneración.');
            Response::redirect(url('admin/2fa'));
        }

        // Generar nuevos códigos
        $codes = TwoFactorRecoveryService::generateForUser((int)$user['id']);
        
        if (empty($codes)) {
            Session::flash('flash.error', 'Error al generar códigos.');
            Response::redirect(url('admin/2fa'));
        }

        // Guardar en sesión para mostrar UNA vez
        Session::set('2fa_new_recovery_codes', $codes);
        
        Session::flash('flash.success', 'Códigos regenerados. Guárdalos en un lugar seguro.');
        Response::redirect(url('admin/2fa/recovery-success'));
    }

    /**
     * Descarga los códigos de recuperación como archivo de texto.
     */
    public function downloadCodes(Request $req): void
    {
        $codes = Session::get('2fa_new_recovery_codes');
        
        if (!$codes || !is_array($codes)) {
            Session::flash('flash.error', 'No hay códigos disponibles para descargar.');
            Response::redirect(url('admin/2fa'));
        }

        $user = Auth::user();
        $siteName = \App\Services\SettingService::get('general', 'site_name', 'PePiuoX Editor');
        
        $content = "═══════════════════════════════════════════════════════════\n";
        $content .= "  CÓDIGOS DE RECUPERACIÓN 2FA - $siteName\n";
        $content .= "═══════════════════════════════════════════════════════════\n\n";
        $content .= "Usuario: " . ($user['username'] ?? 'N/A') . "\n";
        $content .= "Generados: " . date('d/m/Y H:i:s') . "\n\n";
        $content .= "⚠️  IMPORTANTE:\n";
        $content .= "   • Cada código es de UN SOLO USO\n";
        $content .= "   • Guárdalos en un lugar seguro\n";
        $content .= "   • NO los compartas con nadie\n";
        $content .= "   • Si los pierdes, no podrás recuperar tu cuenta\n\n";
        $content .= "───────────────────────────────────────────────────────────\n";
        
        foreach ($codes as $i => $code) {
            $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $formatted = TwoFactorRecoveryService::formatCode($code);
            $content .= "  [$num]  $formatted\n";
        }
        
        $content .= "───────────────────────────────────────────────────────────\n";
        $content .= "\nTotal de códigos: " . count($codes) . "\n";

        // Eliminar de sesión tras descargar
        Session::delete('2fa_new_recovery_codes');

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="codigos-recuperacion-2fa.txt"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    /**
     * Desactiva 2FA y invalida todos los códigos de recuperación.
     */
    public function disable(Request $req): void
    {
        CSRF::guard();
        $user = Auth::user();
        if (!$user) Response::redirect(url('login'));

        Database::begin();
        try {
            Database::execute(
                "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?",
                [$user['id']]
            );
            
            // Invalidar todos los códigos de recuperación
            TwoFactorRecoveryService::invalidateAll((int)$user['id']);

            Database::commit();

            AuditService::log('2fa_disabled', '2FA desactivado', 'security', (int)$user['id']);
            Session::flash('flash.success', '2FA desactivado correctamente.');
        } catch (\Throwable $e) {
            Database::rollback();
            Session::flash('flash.error', 'Error al desactivar 2FA.');
        }

        Response::redirect(url('admin/2fa'));
    }

    /**
     * Muestra el formulario de challenge 2FA (login con 2FA).
     */
    public function challenge(Request $req): void
    {
        if (!Session::has('2fa_pending_uid')) {
            Response::redirect(url('login'));
        }
        Response::render(View::render('2fa.challenge', [], 'layouts.auth'));
    }
}