<?php
namespace App\Services;

/**
 * Genera plantillas HTML consistentes para todos los emails del sistema.
 * Usa el tema oscuro del CMS para mantener la identidad visual.
 */
class EmailTemplate
{
    /**
     * Envuelve el contenido en la plantilla base del CMS.
     */
    public static function wrap(string $title, string $body, string $preheader = ''): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
        $cfg = require CONFIG_PATH . '/config.php';
        $baseUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$title</title>
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
<style>
body { margin: 0; padding: 0; background: #0d1117; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.preheader { display: none; font-size: 1px; color: #0d1117; line-height: 1px; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; }
.wrapper { width: 100%; background: #0d1117; padding: 40px 20px; }
.container { max-width: 600px; margin: 0 auto; background: #1c2333; border: 1px solid #30363d; border-radius: 12px; overflow: hidden; }
.header { background: linear-gradient(135deg, rgba(0,212,170,0.15), rgba(13,17,23,0.95)); padding: 30px; text-align: center; border-bottom: 1px solid #30363d; }
.logo { font-size: 24px; font-weight: 700; color: #00d4aa; text-decoration: none; }
.logo-sub { display: block; font-size: 12px; color: #8b949e; margin-top: 4px; font-weight: 400; }
.body { padding: 30px; color: #e6edf3; }
.body h1 { color: #ffffff; font-size: 22px; margin: 0 0 16px 0; font-weight: 700; }
.body h2 { color: #ffffff; font-size: 18px; margin: 24px 0 12px 0; font-weight: 600; }
.body p { color: #e6edf3; font-size: 15px; line-height: 1.6; margin: 0 0 16px 0; }
.body a { color: #00d4aa; text-decoration: none; }
.body a:hover { color: #00a88a; text-decoration: underline; }
.btn { display: inline-block; background: #00d4aa; color: #0d1117 !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; margin: 8px 0; }
.btn:hover { background: #00a88a; text-decoration: none; }
.btn-danger { background: #ef4444; color: #ffffff !important; }
.btn-danger:hover { background: #dc2626; }
.btn-ghost { background: transparent; color: #00d4aa !important; border: 1px solid #30363d; }
.alert { padding: 16px; border-radius: 8px; margin: 16px 0; border-left: 4px solid; }
.alert-info { background: rgba(59,130,246,0.08); border-color: #3b82f6; color: #e6edf3; }
.alert-warning { background: rgba(245,158,11,0.08); border-color: #f59e0b; color: #e6edf3; }
.alert-danger { background: rgba(239,68,68,0.08); border-color: #ef4444; color: #e6edf3; }
.alert-success { background: rgba(34,197,94,0.08); border-color: #22c55e; color: #e6edf3; }
.code { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 12px; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #00d4aa; word-break: break-all; }
.table-stats { width: 100%; border-collapse: collapse; margin: 16px 0; }
.table-stats td { padding: 10px 12px; border-bottom: 1px solid #30363d; color: #e6edf3; font-size: 14px; }
.table-stats td:first-child { color: #8b949e; width: 50%; }
.table-stats td:last-child { font-weight: 600; text-align: right; }
.divider { border: none; border-top: 1px solid #30363d; margin: 24px 0; }
.footer { padding: 24px 30px; background: #161b22; border-top: 1px solid #30363d; text-align: center; }
.footer p { color: #8b949e; font-size: 12px; margin: 0 0 8px 0; }
.footer a { color: #00d4aa; font-size: 12px; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-danger { background: rgba(239,68,68,0.15); color: #ef4444; }
.badge-warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-success { background: rgba(34,197,94,0.15); color: #22c55e; }
.badge-info { background: rgba(59,130,246,0.15); color: #3b82f6; }
@media only screen and (max-width: 620px) {
  .wrapper { padding: 20px 10px !important; }
  .body, .footer { padding: 20px !important; }
  .body h1 { font-size: 20px !important; }
}
</style>
</head>
<body>
<span class="preheader">$preheader</span>
<div class="wrapper">
  <div class="container">
    <div class="header">
      <a href="$baseUrl" class="logo">
        ⚡ $siteName
        <span class="logo-sub">Sistema de notificaciones</span>
      </a>
    </div>
    <div class="body">
      $body
    </div>
    <div class="footer">
      <p>© $year $siteName · Todos los derechos reservados</p>
      <p>Este es un email automático, por favor no respondas a este mensaje.</p>
      <p><a href="$baseUrl/admin/profile">Gestionar preferencias</a> · <a href="$baseUrl">Visitar el sitio</a></p>
    </div>
  </div>
</div>
</body>
</html>
HTML;
    }

    /**
     * Email de alerta de seguridad (IP bloqueada, ataque detectado, etc.)
     */
    public static function securityAlert(string $title, string $message, array $details = []): string
    {
        $detailsHtml = '';
        if (!empty($details)) {
            $detailsHtml = '<table class="table-stats">';
            foreach ($details as $label => $value) {
                $detailsHtml .= '<tr><td>' . htmlspecialchars($label) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
            }
            $detailsHtml .= '</table>';
        }

        $body = <<<HTML
<div class="alert alert-danger">
  <strong>⚠️ Alerta de seguridad</strong>
</div>
<h1>$title</h1>
<p>$message</p>
$detailsHtml
<hr class="divider">
<p style="color:#8b949e;font-size:13px">
  Si no reconoces esta actividad, te recomendamos cambiar tu contraseña inmediatamente y revisar el 
  <a href="{{dashboard_url}}">Dashboard de Seguridad</a>.
</p>
HTML;

        return self::wrap($title, $body, 'Alerta de seguridad - ' . $title);
    }

    /**
     * Email informativo (backup completado, usuario creado, etc.)
     */
    public static function info(string $title, string $message, ?string $buttonText = null, ?string $buttonUrl = null): string
    {
        $button = '';
        if ($buttonText && $buttonUrl) {
            $button = '<p style="text-align:center;margin:24px 0"><a href="' . htmlspecialchars($buttonUrl) . '" class="btn">' . htmlspecialchars($buttonText) . '</a></p>';
        }

        $body = <<<HTML
<h1>$title</h1>
<p>$message</p>
$button
HTML;

        return self::wrap($title, $body, $title);
    }

    /**
     * Email de bienvenida
     */
    public static function welcome(string $userName, string $loginUrl): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
        $body = <<<HTML
<h1>¡Bienvenido a $siteName, $userName! 👋</h1>
<p>Tu cuenta ha sido creada exitosamente. Ya puedes comenzar a usar todas las herramientas del CMS.</p>
<p style="text-align:center;margin:24px 0">
  <a href="$loginUrl" class="btn">Iniciar sesión</a>
</p>
<hr class="divider">
<h2>Primeros pasos</h2>
<ul style="color:#e6edf3;line-height:1.8">
  <li>🔐 Activa la autenticación en dos pasos (2FA) desde tu perfil</li>
  <li>📝 Crea tu primera página desde el menú "Páginas"</li>
  <li>🎨 Personaliza la apariencia desde "Templates"</li>
  <li>📊 Explora las métricas en "Analytics"</li>
</ul>
<p style="color:#8b949e;font-size:13px">Si no solicitaste esta cuenta, ignora este email.</p>
HTML;

        return self::wrap('Bienvenido a ' . $siteName, $body, 'Tu cuenta ha sido creada');
    }

    /**
     * Email de recuperación de contraseña
     */
    public static function passwordReset(string $userName, string $resetUrl, int $minutes = 60): string
    {
        $body = <<<HTML
<h1>Restablecer contraseña</h1>
<p>Hola <strong>$userName</strong>,</p>
<p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón para continuar:</p>
<p style="text-align:center;margin:24px 0">
  <a href="$resetUrl" class="btn">Restablecer contraseña</a>
</p>
<div class="alert alert-warning">
  <strong>⏱️ Este enlace expira en $minutes minutos.</strong><br>
  Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
  <span class="code">$resetUrl</span>
</div>
<hr class="divider">
<p style="color:#8b949e;font-size:13px">
  Si no solicitaste restablecer tu contraseña, puedes ignorar este email de forma segura. 
  Tu contraseña actual permanecerá sin cambios.
</p>
HTML;

        return self::wrap('Restablecer contraseña', $body, 'Solicitud de restablecimiento de contraseña');
    }

    /**
     * Email de notificación de baneo
     */
    public static function userBanned(string $userName, string $reason, ?string $duration, ?string $expiresAt): string
    {
        $durationText = $duration ? "$duration días (hasta $expiresAt)" : "permanentemente";
        $body = <<<HTML
<div class="alert alert-danger">
  <strong>Cuenta suspendida</strong>
</div>
<h1>Hola $userName,</h1>
<p>Tu cuenta ha sido <strong>baneada $durationText</strong>.</p>
<h2>Motivo del baneo</h2>
<div class="code">$reason</div>
<hr class="divider">
<p style="color:#8b949e;font-size:13px">
  Si consideras que esta decisión fue un error, puedes contactar al equipo de administración 
  respondiendo a este email o a través de los canales oficiales de soporte.
</p>
HTML;

        return self::wrap('Cuenta suspendida', $body, 'Tu cuenta ha sido baneada');
    }

    /**
     * Email de desbaneo
     */
    public static function userUnbanned(string $userName, string $loginUrl): string
    {
        $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
        $body = <<<HTML
<div class="alert alert-success">
  <strong>✅ Cuenta restaurada</strong>
</div>
<h1>Hola $userName,</h1>
<p>Tu cuenta en <strong>$siteName</strong> ha sido restaurada y ya puedes volver a acceder normalmente.</p>
<p style="text-align:center;margin:24px 0">
  <a href="$loginUrl" class="btn">Iniciar sesión</a>
</p>
<p style="color:#8b949e;font-size:13px">Te recomendamos cambiar tu contraseña si crees que tu cuenta pudo haber sido comprometida.</p>
HTML;

        return self::wrap('Cuenta restaurada', $body, 'Tu cuenta ha sido restaurada');
    }

    /**
     * Email de nuevo inicio de sesión (IP diferente)
     */
    public static function newLogin(string $userName, string $ip, string $userAgent, string $profileUrl): string
    {
        $body = <<<HTML
<div class="alert alert-warning">
  <strong>🔐 Nuevo inicio de sesión detectado</strong>
</div>
<h1>Hola $userName,</h1>
<p>Hemos detectado un inicio de sesión en tu cuenta desde una ubicación o dispositivo diferente al habitual.</p>
<h2>Detalles del inicio de sesión</h2>
<table class="table-stats">
  <tr><td>Dirección IP</td><td>$ip</td></tr>
  <tr><td>Dispositivo</td><td style="font-size:12px;word-break:break-all">$userAgent</td></tr>
  <tr><td>Fecha y hora</td><td>{{datetime}}</td></tr>
</table>
<div class="alert alert-info">
  <strong>¿Fuiste tú?</strong> Si reconoces esta actividad, no necesitas hacer nada.
</div>
<p style="text-align:center;margin:24px 0">
  <a href="$profileUrl" class="btn btn-ghost">Revisar mi perfil</a>
</p>
<hr class="divider">
<p style="color:#8b949e;font-size:13px">
  Si no fuiste tú, te recomendamos <strong>cambiar tu contraseña inmediatamente</strong> y activar la autenticación en dos pasos (2FA).
</p>
HTML;

        return self::wrap('Nuevo inicio de sesión', $body, 'Inicio de sesión desde nueva ubicación');
    }

    /**
 * Email de alerta de seguridad cuando se añade un nuevo dispositivo de confianza.
 */
public static function newTrustedDevice(array $user, array $deviceInfo): string
{
    $siteName = SettingService::get('general', 'site_name', 'PePiuoX Maker');
    $userName = htmlspecialchars($user['full_name'] ?? $user['username']);
    $cfg = require CONFIG_PATH . '/config.php';
    $devicesUrl = rtrim($cfg['app_url'] ?? BASE_URL, '/') . '/admin/security/trusted-devices';
    
    $browser = htmlspecialchars($deviceInfo['browser'] ?? 'Desconocido');
    $platform = htmlspecialchars($deviceInfo['platform'] ?? 'Desconocido');
    $ip = htmlspecialchars($deviceInfo['ip'] ?? 'Desconocida');
    $deviceName = htmlspecialchars($deviceInfo['device_name'] ?? "$browser en $platform");
    $datetime = date('d/m/Y H:i:s');
    $expiresAt = date('d/m/Y', strtotime('+30 days'));

    $body = <<<HTML
<h1>🔐 Nuevo dispositivo de confianza añadido</h1>
<p>Hola <strong>$userName</strong>,</p>
<p>Se ha registrado un <strong>nuevo dispositivo de confianza</strong> en tu cuenta. Esto significa que durante los próximos 30 días podrás iniciar sesión en este dispositivo <strong>sin necesidad de ingresar el código 2FA</strong>.</p>

<div class="alert alert-info">
  <strong>📱 Detalles del dispositivo</strong>
</div>

<table class="table-stats">
  <tr><td>Dispositivo</td><td><strong>$deviceName</strong></td></tr>
  <tr><td>Navegador</td><td>$browser</td></tr>
  <tr><td>Plataforma</td><td>$platform</td></tr>
  <tr><td>Dirección IP</td><td><code>$ip</code></td></tr>
  <tr><td>Fecha y hora</td><td>$datetime</td></tr>
  <tr><td>Expira el</td><td>$expiresAt</td></tr>
</table>

<div class="alert alert-warning">
  <strong>⚠️ ¿No fuiste tú?</strong><br>
  Si no reconoces este dispositivo, alguien podría haber accedido a tu cuenta. 
  Te recomendamos tomar estas acciones inmediatamente:
</div>

<ol style="color:#e6edf3;line-height:1.8;padding-left:1.5rem">
  <li>Ve a <a href="$devicesUrl">Dispositivos de confianza</a> y revoca el dispositivo sospechoso</li>
  <li><strong>Cambia tu contraseña</strong> inmediatamente</li>
  <li>Revisa tu historial de actividad en el panel</li>
  <li>Considera regenerar tus códigos de recuperación 2FA</li>
</ol>

<p style="text-align:center;margin:24px 0">
  <a href="$devicesUrl" class="btn">Revisar mis dispositivos</a>
</p>

<hr class="divider">

<p style="color:#8b949e;font-size:13px">
  Este es un email automático de seguridad. Si fuiste tú quien añadió este dispositivo, 
  puedes ignorar este mensaje. Los dispositivos de confianza expiran automáticamente 
  después de 30 días de inactividad.
</p>
HTML;

    return self::wrap(
        '🔐 Nuevo dispositivo de confianza - ' . $siteName,
        $body,
        'Alerta de seguridad: Nuevo dispositivo de confianza añadido a tu cuenta'
    );
}
}