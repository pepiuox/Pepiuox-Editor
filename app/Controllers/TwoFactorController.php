<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database};
use App\Services\{TotpService, AuditService, TwoFactorRecoveryService};

class TwoFactorController
{
    /**
     * Minutos que los códigos recién generados siguen disponibles en la sesión.
     *
     * La página promete dos acciones —descargar el TXT y copiar la lista— además
     * de mostrarlos. Para que la descarga funcione, los códigos tienen que
     * sobrevivir al renderizado de la página; si no, el enlace de descarga llega
     * a un `downloadCodes()` que ya no encuentra nada y devuelve un 302.
     *
     * Diez minutos son de sobra para guardarlos y acotan lo que un código en
     * claro vive en la sesión del servidor.
     */
    private const MINUTOS_CODIGOS = 10;

    /**
     * Devuelve los códigos recién generados si siguen vigentes; si no, null.
     *
     * No los borra: se muestran en la página y siguen disponibles para la
     * descarga. Los borra `downloadCodes()` al entregar el archivo, o esta misma
     * función cuando caducan.
     *
     * @return list<string>|null Códigos en texto plano, o null si no hay o caducaron.
     */
    private static function codigosRecientes(): ?array
    {
        $codigos = Session::get('2fa_new_recovery_codes');
        $generados = (int) Session::get('2fa_new_recovery_codes_at', 0);

        if (!is_array($codigos) || $codigos === [] || $generados === 0) {
            return null;
        }

        if (time() - $generados > self::MINUTOS_CODIGOS * 60) {
            self::olvidarCodigos();
            return null;
        }

        return array_values(array_map('strval', $codigos));
    }

    /**
     * Guarda los códigos recién generados para mostrarlos y descargarlos.
     *
     * @param list<string> $codigos Códigos en texto plano.
     */
    private static function recordarCodigos(array $codigos): void
    {
        Session::set('2fa_new_recovery_codes', $codigos);
        Session::set('2fa_new_recovery_codes_at', time());
    }

    /** Retira de la sesión los códigos mostrados. */
    private static function olvidarCodigos(): void
    {
        Session::delete('2fa_new_recovery_codes');
        Session::delete('2fa_new_recovery_codes_at');
    }

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

            // Guardar códigos en sesión temporal para mostrarlos y descargarlos
            self::recordarCodigos($recoveryCodes);

            AuditService::log(Auth::id(), '2fa_enabled', '2FA activado', 'security', (int)$user['id']);
            
            // Redirigir a vista que muestra los códigos
            Response::redirect(url('admin/2fa/recovery-success'));

        } catch (\Throwable $e) {
            // `rollbackIfActive()` y no `rollback()`: si el fallo ocurrió
            // después de cerrar la transacción, un rollback a secas lanzaría
            // «There is no active transaction» dentro del propio catch y
            // sustituiría el error real por uno que no dice nada.
            Database::rollbackIfActive();
            Session::flash('flash.error', 'Error al activar 2FA: ' . $e->getMessage());
            Response::redirect(url('admin/2fa'));
        }
    }

    /**
     * Muestra los códigos de recuperación recién generados.
     *
     * No los retira de la sesión: la propia página ofrece descargarlos y
     * copiarlos, y borrarlos aquí dejaba el enlace de descarga sin datos.
     * Caducan solos a los `MINUTOS_CODIGOS` minutos y `downloadCodes()` los
     * elimina al entregar el archivo.
     */
    public function recoverySuccess(Request $req): void
    {
        $codes = self::codigosRecientes();

        // Si no hay códigos vigentes en sesión, redirigir
        if ($codes === null) {
            Session::flash('flash.info', 'Los códigos ya no están disponibles. Puedes regenerarlos desde tu perfil.');
            Response::redirect(url('admin/2fa'));
        }

        // Formatear códigos para mostrar
        $formattedCodes = array_map(
            fn($c) => TwoFactorRecoveryService::formatCode($c),
            $codes
        );

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

        // Guardar en sesión para poder mostrarlos y descargarlos
        self::recordarCodigos($codes);
        
        Session::flash('flash.success', 'Códigos regenerados. Guárdalos en un lugar seguro.');
        Response::redirect(url('admin/2fa/recovery-success'));
    }

    /**
     * Descarga los códigos de recuperación como archivo de texto.
     */
    public function downloadCodes(Request $req): void
    {
        $codes = self::codigosRecientes();
        
        if ($codes === null) {
            Session::flash('flash.error', 'Los códigos ya no están disponibles para descargar. Regéneralos desde tu perfil.');
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

        // La descarga es el acto de guardarlos: a partir de aquí ya no se
        // vuelven a mostrar en esta sesión.
        self::olvidarCodigos();

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="codigos-recuperacion-2fa.txt"');
        header('Content-Length: ' . strlen($content));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
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

            AuditService::log(Auth::id(), '2fa_disabled', '2FA desactivado', 'security', (int)$user['id']);
            Session::flash('flash.success', '2FA desactivado correctamente.');
        } catch (\Throwable $e) {
            Database::rollbackIfActive();
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