<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Database};
use App\Models\User;
use App\Services\{MailService, AuditService};

class PasswordController
{
    public function forgotForm(Request $req): void
    {
        Response::render(View::render('auth.forgot', [], 'layouts.auth'));
    }

    public function forgotSend(Request $req): void
    {
        CSRF::guard();
        $email = trim($req->post('email') ?? '');
        // Mensaje genérico (no revelar si existe)
        $msg = 'Si el email existe, recibirás instrucciones en unos minutos.';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('flash.info', $msg);
            Response::redirect(url('forgot'));
        }

        $user = User::findByEmail($email);
        if ($user && (int)$user['is_active']) {
            $token = bin2hex(random_bytes(32));
            Database::execute(
                "UPDATE users SET recovery_token = ?, recovery_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?",
                [$token, $user['id']]
            );
            $cfg = require dirname(__DIR__, 2) . '/config/config.php';
            $link = rtrim($cfg['app_url'], '/') . '/reset?token=' . $token;
            $html = self::template("Restablecer contraseña",
                "<p>Hola " . htmlspecialchars($user['full_name'] ?? $user['username']) . ",</p>
                 <p>Has solicitado restablecer tu contraseña. Haz clic en el enlace (válido 1 hora):</p>
                 <p><a href=\"$link\" style=\"background:#00d4aa;color:#0d1117;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block\">Restablecer contraseña</a></p>
                 <p>Si no has sido tú, ignora este email.</p>");
            MailService::send($email, 'Restablecer contraseña · PePiuoX Editor', $html);
        }

        Session::flash('flash.info', $msg);
        Response::redirect(url('forgot'));
    }

    public function resetForm(Request $req): void
    {
        $token = $req->get('token');
        if (!$token) Response::redirect(url('forgot'));
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE recovery_token = ? AND recovery_expires > NOW()",
            [$token]
        );
        if (!$user) {
            Session::flash('flash.error', 'Enlace inválido o expirado.');
            Response::redirect(url('forgot'));
        }
        Response::render(View::render('auth.reset', ['token' => $token], 'layouts.auth'));
    }

    public function resetSave(Request $req): void
    {
        CSRF::guard();
        $token = $req->post('token');
        $pass = $req->post('password');
        $pass2 = $req->post('password_confirmation');

        if (!$pass || strlen($pass) < 8) {
            Session::flash('flash.error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirect(url('reset?token=' . $token));
        }
        if ($pass !== $pass2) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url('reset?token=' . $token));
        }

        $user = Database::fetch(
            "SELECT id FROM users WHERE recovery_token = ? AND recovery_expires > NOW()",
            [$token]
        );
        if (!$user) {
            Session::flash('flash.error', 'Enlace inválido o expirado.');
            Response::redirect(url('forgot'));
        }

        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $hash = password_hash($pass, $cfg['password']['algo'], $cfg['password']['options']);
        Database::execute(
            "UPDATE users SET password_hash = ?, recovery_token = NULL, recovery_expires = NULL WHERE id = ?",
            [$hash, $user['id']]
        );

        AuditService::log('password_reset', 'Contraseña restablecida', 'users', (int)$user['id']);
        Session::flash('flash.success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
        Response::redirect(url('login'));
    }

    private static function template(string $title, string $body): string
    {
        return "<!DOCTYPE html><html><head><meta charset='utf-8'></head>
                <body style='font-family:Inter,sans-serif;background:#0d1117;padding:30px'>
                <div style='max-width:600px;margin:auto;background:#1c2333;border:1px solid #30363d;border-radius:12px;padding:30px;color:#e6edf3'>
                  <h2 style='color:#00d4aa;margin:0 0 20px'>$title</h2>
                  <div style='line-height:1.6'>$body</div>
                  <hr style='border:none;border-top:1px solid #30363d;margin:20px 0'>
                  <small style='color:#8b949e'>PePiuoX Editor</small>
                </div></body></html>";
    }
}