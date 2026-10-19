<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database, Validator};
use App\Models\User;
use App\Services\{AuditService, NotificationService};

/**
 * Controlador de gestión de contraseñas y registro
 * 
 * @package App\Controllers
 */
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
        if (Auth::check()) {
            Response::redirect(url('admin'));
        }
        Response::render(View::render('auth.forgot', [], 'layouts.auth'));
    }

    /**
     * Procesa la solicitud de recuperación de contraseña
     */
    public function forgotSend(Request $req): void
    {
        CSRF::guard();

        $email = trim($req->post('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('flash.error', 'Por favor, ingresa un correo electrónico válido.');
            Response::redirect(url('forgot'));
            return;
        }

        // ═══ VERIFICAR RATE LIMIT ═══
        $ip = $req->ip();
        $recentRequests = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM users 
             WHERE recovery_expires >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
             AND last_ip = ?",
            [$ip]
        )['c'];

        if ($recentRequests >= 3) {
            Session::flash('flash.error', 'Demasiados intentos. Espera 15 minutos.');
            Response::redirect(url('forgot'));
            return;
        }

        // ═══ BUSCAR USUARIO ═══
        $user = Database::fetch(
            "SELECT id, username, email FROM users WHERE email = ? AND is_active = 1 AND is_banned = 0",
            [$email]
        );

        if (!$user) {
            // No revelar si el email existe o no por seguridad
            Session::flash('flash.success', 'Si el correo existe, recibirás un enlace de recuperación.');
            Response::redirect(url('login'));
            return;
        }

        // ═══ GENERAR TOKEN DE RECUPERACIÓN ═══
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        try {
            // Guardar token en la base de datos
            Database::execute(
                "UPDATE users 
                 SET recovery_token = ?, recovery_expires = ? 
                 WHERE id = ?",
                [$token, $expires, (int)$user['id']]
            );

            // ═══ ENVIAR EMAIL ═══
            $resetLink = url('reset') . '?token=' . $token . '&email=' . urlencode($email);

            // Intentar enviar email
            if ($this->sendResetEmail($user['email'], $user['username'], $resetLink)) {
                // Registrar en auditoría
                AuditService::log(
                    (int)$user['id'],
                    'password_reset_requested',
                    "Solicitud de recuperación de contraseña para: {$user['username']}",
                    'auth'
                );

                Session::flash('flash.success', 'Se ha enviado un enlace de recuperación a tu correo electrónico.');
            } else {
                // Si falla el email, guardar token para uso manual
                AuditService::log(
                    (int)$user['id'],
                    'password_reset_requested_failed',
                    "Error al enviar email de recuperación para: {$user['username']}",
                    'auth'
                );
                Session::flash('flash.error', 'Error al enviar el correo de recuperación. Contacta al administrador.');
            }

        } catch (\Throwable $e) {
            error_log('[PasswordController] Error en forgotSend: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al procesar la solicitud.');
        }

        Response::redirect(url('login'));
    }

    /**
     * Muestra el formulario para restablecer la contraseña
     */
    public function resetForm(Request $req): void
    {
        if (Auth::check()) {
            Response::redirect(url('admin'));
        }

        $token = $req->get('token', '');
        $email = $req->get('email', '');

        if (empty($token) || empty($email)) {
            Session::flash('flash.error', 'Enlace de recuperación inválido.');
            Response::redirect(url('login'));
            return;
        }

        // Verificar token
        $user = Database::fetch(
            "SELECT id, username, email FROM users 
             WHERE email = ? AND recovery_token = ? AND recovery_expires > NOW()
             AND is_active = 1 AND is_banned = 0",
            [$email, $token]
        );

        if (!$user) {
            Session::flash('flash.error', 'El enlace de recuperación ha expirado o es inválido.');
            Response::redirect(url('login'));
            return;
        }

        Response::render(View::render('auth.reset', [
            'token' => $token,
            'email' => $email
        ], 'layouts.auth'));
    }

    /**
     * Procesa el restablecimiento de contraseña
     */
    public function resetSave(Request $req): void
    {
        CSRF::guard();

        $token = $req->post('token', '');
        $email = $req->post('email', '');
        $password = $req->post('password', '');
        $rpassword = $req->post('rpassword', '');

        // ═══ VALIDAR CAMPOS ═══
        if (empty($token) || empty($email) || empty($password) || empty($rpassword)) {
            Session::flash('flash.error', 'Todos los campos son obligatorios.');
            Response::redirect(url('reset') . '?token=' . $token . '&email=' . urlencode($email));
            return;
        }

        // ═══ VALIDAR CONTRASEÑA ═══
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            Session::flash('flash.error', 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.');
            Response::redirect(url('reset') . '?token=' . $token . '&email=' . urlencode($email));
            return;
        }

        if ($password !== $rpassword) {
            Session::flash('flash.error', 'Las contraseñas no coinciden.');
            Response::redirect(url('reset') . '?token=' . $token . '&email=' . urlencode($email));
            return;
        }

        if (!$this->isPasswordStrong($password)) {
            Session::flash('flash.error', 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.');
            Response::redirect(url('reset') . '?token=' . $token . '&email=' . urlencode($email));
            return;
        }

        // ═══ VERIFICAR TOKEN ═══
        $user = Database::fetch(
            "SELECT id, username FROM users 
             WHERE email = ? AND recovery_token = ? AND recovery_expires > NOW()
             AND is_active = 1 AND is_banned = 0",
            [$email, $token]
        );

        if (!$user) {
            Session::flash('flash.error', 'El enlace de recuperación ha expirado o es inválido.');
            Response::redirect(url('login'));
            return;
        }

        // ═══ ACTUALIZAR CONTRASEÑA ═══
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            Database::execute(
                "UPDATE users 
                 SET password_hash = ?, 
                     recovery_token = NULL, 
                     recovery_expires = NULL,
                     updated_at = NOW()
                 WHERE id = ?",
                [$passwordHash, (int)$user['id']]
            );

            // ═══ REGISTRAR EN AUDITORÍA ═══
            AuditService::log(
                (int)$user['id'],
                'password_reset_success',
                "Contraseña restablecida exitosamente para: {$user['username']}",
                'auth'
            );

            Session::flash('flash.success', 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.');
            Response::redirect(url('login'));

        } catch (\Throwable $e) {
            error_log('[PasswordController] Error en resetSave: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al restablecer la contraseña.');
            Response::redirect(url('reset') . '?token=' . $token . '&email=' . urlencode($email));
        }
    }

    /**
     * Registra una sesión activa
     */
    private function registerSession(int $userId, string $ip, string $userAgent): void
    {
        try {
            $sessionId = session_id();
            Database::execute(
                "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, payload, last_activity)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), last_activity = VALUES(last_activity)",
                [$sessionId, $userId, $ip, $userAgent, '', time()]
            );
        } catch (\Throwable $e) {
            error_log('[PasswordController] Error registrando sesión: ' . $e->getMessage());
        }
    }

    /**
     * Valida la fortaleza de la contraseña
     */
    private function isPasswordStrong(string $password): bool
    {
        // Debe tener al menos:
        // - Una mayúscula
        // - Una minúscula
        // - Un número
        // - Un carácter especial
        return preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }

    /**
     * Envía email de recuperación
     */
    private function sendResetEmail(string $email, string $username, string $resetLink): bool
    {
        try {
            // Si existe el servicio de email
            if (class_exists('\App\Services\EmailNotificationService')) {
                return \App\Services\EmailNotificationService::sendPasswordReset($email, $username, $resetLink);
            }

            // Si no existe, usar mail() nativo
            $subject = "Recuperación de contraseña - " . (defined('APP_NAME') ? APP_NAME : 'CMS');
            $message = "Hola $username,\n\n";
            $message .= "Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace:\n\n";
            $message .= "$resetLink\n\n";
            $message .= "Este enlace expirará en 1 hora.\n\n";
            $message .= "Si no solicitaste esto, ignora este mensaje.\n\n";
            $message .= "Saludos,\nEl equipo de " . (defined('APP_NAME') ? APP_NAME : 'CMS');

            $headers = "From: " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'noreply@localhost') . "\r\n";
            $headers .= "Reply-To: " . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'noreply@localhost') . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            return mail($email, $subject, $message, $headers);

        } catch (\Throwable $e) {
            error_log('[PasswordController] Error enviando email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica a los administradores sobre nuevo registro
     */
    private function notifyAdmins(string $username, string $email, int $userId): void
    {
        try {
            // Buscar administradores activos
            $admins = Database::fetchAll(
                "SELECT id FROM users 
                 WHERE role IN ('Super admin', 'Admin') 
                 AND is_active = 1 
                 AND is_banned = 0"
            );

            foreach ($admins as $admin) {
                if (class_exists('\App\Services\NotificationService')) {
                    NotificationService::notify(
                        (int)$admin['id'],
                        'info',
                        'Nuevo usuario registrado',
                        "El usuario '$username' ($email) se ha registrado en el sistema.",
                        url('admin/users/' . $userId . '/edit'),
                        'person-plus'
                    );
                }
            }

            // También registrar en auditoría
            AuditService::log(
                null,
                'new_user_registration',
                "Nuevo usuario registrado: $username ($email)",
                'auth'
            );

        } catch (\Throwable $e) {
            error_log('[PasswordController] Error notificando admins: ' . $e->getMessage());
        }
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