<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth, Database, Validator};
use App\Models\User;
use App\Services\{AuditService, NotificationService, TotpService};

class AuthController
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_WINDOW_MINUTES = 15;

    /** Muestra el formulario de login */
    public function showLogin(Request $req): void
    {
        if (Auth::check()) Response::redirect(url('admin'));
        Response::render(View::render('auth.login', [], 'layouts.auth'));
    }

    /** Procesa el login con todas las protecciones */
    public function login(Request $req): void
    {
        CSRF::guard();

        $ip = $req->ip();
        $userAgent = $req->userAgent();
        $username = trim($req->post('username', ''));
        $password = $req->post('password', '');
        $remember = (bool)$req->post('remember');

        // ═══ 1. VERIFICAR SI LA IP ESTÁ BLOQUEADA ═══
        try {
            $blocked = Database::fetch(
                "SELECT * FROM blocked_ips WHERE ip_address = ? AND (blocked_until IS NULL OR blocked_until > NOW())",
                [$ip]
            );
            if ($blocked) {
                $this->recordAttempt($username, $ip, $userAgent, false);
                // ✅ CORREGIDO: AuditService::log() con userId = null
                AuditService::log(null, 'login_blocked_ip', "Login bloqueado desde IP $ip", 'security');
                Session::flash('flash.error', 'Acceso bloqueado. Contacta al administrador.');
                Response::redirect(url('login'));
            }
        } catch (\Throwable $e) {
            error_log('[Auth] Error verificando IP bloqueada: ' . $e->getMessage());
        }

        // ═══ 2. RATE LIMIT POR IP ═══
        try {
            $recentFails = (int)Database::fetch(
                "SELECT COUNT(*) AS c FROM login_attempts 
                 WHERE ip_address = ? AND successful = 0 
                   AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$ip, self::BLOCK_WINDOW_MINUTES]
            )['c'];

            if ($recentFails >= self::MAX_FAILED_ATTEMPTS) {
                Database::execute(
                    "INSERT INTO blocked_ips (ip_address, reason, blocked_until) 
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
                     ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_until = VALUES(blocked_until)",
                    [$ip, 'Bloqueo automático por múltiples intentos fallidos']
                );
                // ✅ CORREGIDO: AuditService::log() con userId = null
                AuditService::log(null, 'ip_auto_blocked', "IP $ip bloqueada ($recentFails intentos)", 'security');
                $this->notifySuperAdmin('warning', 'IP bloqueada automáticamente', 
                    "La IP $ip fue bloqueada tras $recentFails intentos fallidos.", 
                    url('admin/security/dashboard'), 'shield-exclamation');
                Session::flash('flash.error', 'Demasiados intentos fallidos. IP bloqueada por 30 minutos.');
                Response::redirect(url('login'));
            }
        } catch (\Throwable $e) {
            error_log('[Auth] Error verificando rate limit: ' . $e->getMessage());
        }

        // ═══ 3. VALIDACIÓN BÁSICA ═══
        $v = new Validator($req->all());
        if (!$v->validate([
            'username' => ['label' => 'Usuario', 'rules' => ['required']],
            'password' => ['label' => 'Contraseña', 'rules' => ['required']],
        ])) {
            Session::flash('flash.error', 'Completa todos los campos.');
            Response::redirect(url('login'));
        }

        // ═══ 4. BUSCAR USUARIO ═══
        $user = User::findByUsername($username);
        if (!$user) {
            $user = User::findByEmail($username);
        }

        // ═══ 5. VERIFICAR CREDENCIALES ═══
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordAttempt($username, $ip, $userAgent, false);
            // ✅ CORREGIDO: AuditService::log() con userId = null (no sabemos quién es)
            AuditService::log(null, 'login_failed', "Login fallido para: $username", 'security');
            Session::flash('flash.error', 'Credenciales inválidas.');
            Response::redirect(url('login'));
        }

        // ═══ 6. VERIFICAR SI ESTÁ BANEADO ═══
        if ((int)($user['is_banned'] ?? 0)) {
            $this->recordAttempt($username, $ip, $userAgent, false);
            // ✅ CORREGIDO: AuditService::log() con userId del usuario
            AuditService::log((int)$user['id'], 'login_banned_user', "Login intentado por usuario baneado: $username", 'security');
            Session::flash('flash.error', 'Tu cuenta ha sido baneada. Motivo: ' . ($user['banned_reason'] ?? 'No especificado'));
            Response::redirect(url('login'));
        }

        // ═══ 7. VERIFICAR SI ESTÁ ACTIVO ═══
        if (!(int)$user['is_active']) {
            $this->recordAttempt($username, $ip, $userAgent, false);
            Session::flash('flash.error', 'Cuenta inactiva. Contacta al administrador.');
            Response::redirect(url('login'));
        }

        // ═══ 8. VERIFICAR 2FA ═══
        if ((int)($user['two_factor_enabled'] ?? 0)) {
            // Verificar si el dispositivo es de confianza
            if (\App\Services\TrustedDeviceService::isTrusted((int)$user['id'])) {
                // ✅ CORREGIDO: AuditService::log() con userId
                AuditService::log((int)$user['id'], '2fa_skipped_trusted_device', 
                    "2FA omitido por dispositivo de confianza", 'security');
                Session::flash('flash.info', 'Sesión iniciada desde dispositivo de confianza.');
                $this->completeLogin($user, $ip, $userAgent, $remember);
                return;
            }

            // Si no es de confianza, pedir código 2FA
            $this->recordAttempt($username, $ip, $userAgent, true);
            Session::set('2fa_pending_uid', (int)$user['id']);
            Session::set('2fa_pending_remember', $remember);
            Session::set('2fa_pending_ip', $ip);
            Session::set('2fa_pending_useragent', $userAgent);
            Response::redirect(url('2fa/challenge'));
        }

        // ═══ 9. LOGIN EXITOSO ═══
        $this->completeLogin($user, $ip, $userAgent, $remember);
    }

    /** Verifica el código 2FA y completa el login */
    public function verify2FA(Request $req): void
    {
        CSRF::guard();

        $uid = Session::get('2fa_pending_uid');
        if (!$uid) {
            Session::flash('flash.error', 'Sesión 2FA expirada. Inicia sesión nuevamente.');
            Response::redirect(url('login'));
        }

        $user = User::find((int)$uid);
        if (!$user || !(int)($user['two_factor_enabled'] ?? 0)) {
            Session::delete('2fa_pending_uid');
            Session::flash('flash.error', 'Usuario no encontrado o 2FA desactivado.');
            Response::redirect(url('login'));
        }

        $totpCode = $req->post('code', '');
        $recoveryCode = $req->post('recovery_code', '');
        $mode = $req->post('mode', 'totp');
        $trustDevice = (bool)$req->post('trust_device');

        $verified = false;

        // ═══ MODO TOTP ═══
        if ($mode === 'totp' && !empty($totpCode)) {
            if (TotpService::verify($user['two_factor_secret'], (string)$totpCode)) {
                $verified = true;
            } else {
                // ✅ CORREGIDO: AuditService::log() con userId
                AuditService::log((int)$user['id'], '2fa_failed', "Código TOTP inválido para: {$user['username']}", 'security');
                Session::flash('flash.error', 'Código TOTP inválido.');
                Response::redirect(url('2fa/challenge'));
            }
        }
        // ═══ MODO RECUPERACIÓN ═══
        elseif ($mode === 'recovery' && !empty($recoveryCode)) {
            if (\App\Services\TwoFactorRecoveryService::verifyAndConsume((int)$user['id'], $recoveryCode)) {
                $verified = true;
                Session::set('2fa_used_recovery', true);
                Session::set('2fa_recovery_warning', 
                    \App\Services\TwoFactorRecoveryService::remainingCodes((int)$user['id']));
            } else {
                // ✅ CORREGIDO: AuditService::log() con userId
                AuditService::log((int)$user['id'], '2fa_recovery_failed', "Código recuperación inválido: {$user['username']}", 'security');
                Session::flash('flash.error', 'Código de recuperación inválido o ya usado.');
                Response::redirect(url('2fa/challenge'));
            }
        } else {
            Session::flash('flash.error', 'Debes ingresar un código.');
            Response::redirect(url('2fa/challenge'));
        }

        if (!$verified) {
            Response::redirect(url('2fa/challenge'));
        }

        // Registrar dispositivo de confianza si se solicitó
        if ($trustDevice) {
            $ip = Session::get('2fa_pending_ip', $req->ip());
            $userAgent = Session::get('2fa_pending_useragent', $req->userAgent());
            
            try {
                \App\Services\TrustedDeviceService::trustDevice(
                    (int)$user['id'],
                    $userAgent,
                    $ip,
                    30
                );
                Session::flash('flash.success', 'Dispositivo marcado como de confianza por 30 días.');
            } catch (\Throwable $e) {
                error_log('[Auth] Error registrando dispositivo de confianza: ' . $e->getMessage());
            }
        }

        // Recuperar datos de sesión
        $remember = (bool)Session::get('2fa_pending_remember', false);
        $ip = Session::get('2fa_pending_ip', $req->ip());
        $userAgent = Session::get('2fa_pending_useragent', $req->userAgent());

        // Limpiar sesión temporal
        Session::delete('2fa_pending_uid');
        Session::delete('2fa_pending_remember');
        Session::delete('2fa_pending_ip');
        Session::delete('2fa_pending_useragent');

        // Completar login
        $this->completeLogin($user, $ip, $userAgent, $remember);
    }

    /**
     * Completa el login exitoso.
     */
    private function completeLogin(array $user, string $ip, string $userAgent, bool $remember): void
    {
        // Regenerar ID de sesión (previene session fixation)
        Session::regenerate();

        // ═══ ESTABLECER VARIABLES DE SESIÓN ═══
        Session::set('user_id', (int)$user['id']);
        Session::set('user_role', $user['role'] ?? 'Guest');
        Session::set('user_locale', $user['locale'] ?? 'es');
        Session::set('user_theme', $user['theme'] ?? 'dark');
        Session::set('login_time', time());
        Session::set('ip_address', $ip);

        // Actualizar último login
        User::updateLastLogin((int)$user['id']);

        // Registrar sesión activa
        $this->registerSession((int)$user['id'], $ip, $userAgent);

        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::setRememberToken((int)$user['id'], $token, 30);
            
            try {
                $cfg = require CONFIG_PATH . '/config.php';
                setcookie('remember_token', $user['id'] . ':' . $token, [
                    'expires'  => time() + 86400 * 30,
                    'path'     => '/',
                    'httponly'  => true,
                    'samesite' => 'Lax',
                    'secure'   => $cfg['session']['secure'] ?? false,
                ]);
            } catch (\Throwable $e) {
                error_log('[Auth] Error configurando remember cookie: ' . $e->getMessage());
            }
        }

        // ✅ CORREGIDO: AuditService::log() con userId
        AuditService::log((int)$user['id'], 'login_success', "Login exitoso: {$user['username']}", 'security');

        // Notificar si es un login desde IP nueva
        $lastIp = $user['last_ip'] ?? null;
        if ($lastIp && $lastIp !== $ip) {
            try {
                NotificationService::notify(
                    (int)$user['id'],
                    'warning',
                    'Nuevo inicio de sesión',
                    "Se detectó un login desde una IP diferente: $ip",
                    url('admin/profile'),
                    'shield-exclamation'
                );
            } catch (\Throwable $e) {
                error_log('[Auth] Error notificando nuevo login: ' . $e->getMessage());
            }

            if (class_exists('\App\Services\EmailNotificationService')) {
                try {
                    \App\Services\EmailNotificationService::notifyNewLogin($user, $ip, $userAgent);
                } catch (\Throwable $e) {
                    error_log('[Auth] Error enviando email de nuevo login: ' . $e->getMessage());
                }
            }
        }

        Response::redirect(url('admin'));
    }

    /** Registra intento de login */
    private function recordAttempt(string $username, string $ip, string $userAgent, bool $success): void
    {
        try {
            Database::insert(
                "INSERT INTO login_attempts (username, ip_address, successful, user_agent) VALUES (?,?,?,?)",
                [$username, $ip, $success ? 1 : 0, $userAgent]
            );
        } catch (\Throwable $e) {
            error_log('[Auth] Error registrando intento: ' . $e->getMessage());
        }
    }

    /** Registra sesión activa */
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
            error_log('[Auth] Error registrando sesión: ' . $e->getMessage());
        }
    }

    /** Cierre de sesión */
    public function logout(Request $request): void
    {
        $userId = Auth::id();
        $username = Auth::user()['username'] ?? 'desconocido';

        // Cerrar sesión
        Session::destroy();

        // ✅ CORREGIDO: AuditService::log() con userId
        AuditService::log(
            $userId,                    // ?int (puede ser null si no está autenticado)
            'user_logout',              // string
            "Usuario '{$username}' cerró sesión", // ?string
            'auth'                      // ?string
        );

        // También registrar en ActivityLoggerService
        if (isset($this->logger)) {
            $this->logger->log(
                $userId,
                'user_logout',
                "Usuario '{$username}' cerró sesión",
                $request->ip(),
                $request->userAgent()
            );
        }
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
            ]);
        }
        
        Response::redirect(url('login'));
    }

    /** Cierra todas las sesiones excepto la actual */
    public function closeOtherSessions(Request $req): void
    {
        $uid = Auth::id();
        if (!$uid) Response::redirect(url('login'));

        try {
            $deleted = Database::execute(
                "DELETE FROM user_sessions WHERE user_id = ? AND id <> ?",
                [$uid, session_id()]
            );
            // ✅ CORREGIDO: AuditService::log() con userId
            AuditService::log($uid, 'sessions_closed', "Otras sesiones cerradas ($deleted)", 'security');
            Session::flash('flash.success', 'Otras sesiones cerradas correctamente.');
        } catch (\Throwable $e) {
            Session::flash('flash.error', 'Error al cerrar sesiones.');
        }

        Response::redirect(url('admin/profile'));
    }

    /**
     * Notifica al Super Admin
     */
    private function notifySuperAdmin(string $type, string $title, string $message, string $link, string $icon): void
    {
        try {
            $superAdmin = Database::fetch(
                "SELECT id FROM users WHERE role = 'Super admin' AND is_active = 1 ORDER BY id ASC LIMIT 1"
            );

            if ($superAdmin) {
                NotificationService::notify(
                    (int)$superAdmin['id'],
                    $type,
                    $title,
                    $message,
                    $link,
                    $icon
                );
            }
        } catch (\Throwable $e) {
            error_log('[Auth] Error notificando al Super Admin: ' . $e->getMessage());
        }
    }

    /**
     * Autologin desde remember_token
     */
    public static function checkRemember(): bool
    {
        if (Auth::check()) return true;

        $cookie = $_COOKIE['remember_token'] ?? null;
        if (!$cookie) return false;

        $parts = explode(':', $cookie);
        if (count($parts) !== 2) return false;

        [$userId, $token] = $parts;

        try {
            $user = Database::fetch(
                "SELECT * FROM users 
                  WHERE id = ? 
                    AND remember_token = ? 
                    AND remember_expires > NOW() 
                    AND is_active = 1 
                    AND is_banned = 0",
                [(int)$userId, $token]
            );

            if ($user) {
                Session::regenerate();
                Session::set('user_id', (int)$user['id']);
                Session::set('user_role', $user['role']);
                Session::set('user_locale', $user['locale'] ?? 'es');
                Session::set('user_theme', $user['theme'] ?? 'dark');
                Session::set('login_time', time());

                // ✅ CORREGIDO: AuditService::log() con userId
                AuditService::log((int)$user['id'], 'autologin', "Auto-login: {$user['username']}", 'security');
                return true;
            }
        } catch (\Throwable $e) {
            error_log('[Auth] Error en autologin: ' . $e->getMessage());
        }

        return false;
    }
}