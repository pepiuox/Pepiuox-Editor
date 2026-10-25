<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF, Auth, Database, Validator};
use App\Models\User;
use App\Services\{AuditService, NotificationService, MailService};

class PasswordController
{
    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 72;

    /**
     * Muestra el formulario de registro
     */
    public function registerForm(Request $req): void
    {
        if (Auth::check()) {
            Response::redirect(url('admin'));
        }
        Response::render(View::render('auth.register', [], 'layouts.auth'));
    }

    /**
     * Procesa el registro de un nuevo usuario
     */
    public function registerSend(Request $req): void
    {
        CSRF::guard();

        // ═══ 1. VALIDAR CAMPOS ═══
        $username = trim($req->post('username', ''));
        $email = trim($req->post('email', ''));
        $password = $req->post('password', '');
        $rpassword = $req->post('rpassword', '');
        $remember = (bool)$req->post('remember');

        // ═══ 2. VALIDACIONES BÁSICAS ═══
        if (empty($username) || empty($email) || empty($password) || empty($rpassword)) {
            Session::flash('flash.error', 'Todos los campos son obligatorios.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 3. VALIDAR NOMBRE DE USUARIO ═══
        if (strlen($username) < 3) {
            Session::flash('flash.error', 'El nombre de usuario debe tener al menos 3 caracteres.');
            Response::redirect(url('register'));
            return;
        }

        if (strlen($username) > 50) {
            Session::flash('flash.error', 'El nombre de usuario no puede exceder los 50 caracteres.');
            Response::redirect(url('register'));
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            Session::flash('flash.error', 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 4. VALIDAR EMAIL ═══
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('flash.error', 'El correo electrónico no es válido.');
            Response::redirect(url('register'));
            return;
        }

        if (strlen($email) > 100) {
            Session::flash('flash.error', 'El correo electrónico no puede exceder los 100 caracteres.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 5. VALIDAR CONTRASEÑA ═══
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            Session::flash('flash.error', 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.');
            Response::redirect(url('register'));
            return;
        }

        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            Session::flash('flash.error', 'La contraseña no puede exceder los ' . self::MAX_PASSWORD_LENGTH . ' caracteres.');
            Response::redirect(url('register'));
            return;
        }

        if ($password !== $rpassword) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 6. VALIDAR FORTALEZA DE CONTRASEÑA ═══
        if (!$this->isPasswordStrong($password)) {
            Session::flash('flash.error', 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 7. VERIFICAR SI EL USUARIO YA EXISTE ═══
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );

        if ($existingUser) {
            Session::flash('flash.error', 'El nombre de usuario o correo electrónico ya están registrados.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 8. VERIFICAR RATE LIMIT POR IP ═══
        $ip = $req->ip();
        $recentRegistrations = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM users 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND last_ip = ?",
            [$ip]
        )['c'];

        if ($recentRegistrations >= 3) {
            Session::flash('flash.error', 'Demasiados intentos de registro desde esta IP. Espera 1 hora.');
            Response::redirect(url('register'));
            return;
        }

        // ═══ 9. CREAR USUARIO ═══
        try {
            // Hash de la contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            // Insertar usuario
            $userId = Database::insert(
                "INSERT INTO users (
                    username, email, password_hash, 
                    created_at, is_active, role, locale, theme,
                    last_ip, login_count
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)",
                [
                    $username,
                    $email,
                    $passwordHash,
                    1, // is_active
                    'Guest', // role por defecto
                    'es', // locale
                    'dark', // theme
                    $ip,
                    0 // login_count
                ]
            );

            if (!$userId) {
                throw new \Exception('Error al crear el usuario.');
            }

            // ═══ 10. REGISTRAR EN AUDITORÍA ═══
            AuditService::log(
                $userId,
                'user_registered',
                "Nuevo usuario registrado: $username ($email)",
                'auth'
            );

            // ═══ 11. NOTIFICAR AL ADMIN ═══
            $this->notifyAdmins($username, $email, $userId);

            // ═══ 12. INICIAR SESIÓN AUTOMÁTICAMENTE ═══
            $user = User::find($userId);
            
            // Regenerar sesión
            Session::regenerate();
            
            // Establecer variables de sesión
            Session::set('user_id', (int)$user['id']);
            Session::set('user_role', $user['role'] ?? 'Guest');
            Session::set('user_locale', $user['locale'] ?? 'es');
            Session::set('user_theme', $user['theme'] ?? 'dark');
            Session::set('login_time', time());
            Session::set('ip_address', $ip);

            // Actualizar último login
            User::updateLastLogin((int)$user['id']);

            // Registrar sesión activa
            $this->registerSession((int)$user['id'], $ip, $req->userAgent());

            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                User::setRememberToken((int)$user['id'], $token, 30);
                
                setcookie('remember_token', $user['id'] . ':' . $token, [
                    'expires' => time() + 86400 * 30,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => false // Cambiar según configuración
                ]);
            }

            // ═══ 13. MENSAJE DE ÉXITO ═══
            Session::flash('flash.success', '¡Registro exitoso! Bienvenido ' . $username);
            
            // Redirigir al dashboard
            Response::redirect(url('admin'));

        } catch (\Throwable $e) {
            error_log('[PasswordController] Error en registerSend: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al registrar el usuario: ' . $e->getMessage());
            Response::redirect(url('register'));
        }
    }

    /**
     * Muestra el formulario de recuperación de contraseña
     */
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

        AuditService::log(Auth::id(), 'password_reset', 'Contraseña restablecida', 'users', (int)$user['id']);
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