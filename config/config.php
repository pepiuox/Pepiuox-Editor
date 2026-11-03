<?php
/**
 * Configuración general de la aplicación.
 */

// Se carga el entorno aquí para que `cron_token` tenga un valor **estable**.
// Antes se generaba con `bin2hex(random_bytes(8))` en cada carga del archivo,
// así que el token cambiaba en cada petición y los seis endpoints `/cron/*`
// respondían siempre 401: no había forma de configurarlo.
require_once __DIR__ . '/env.php';
load_env_file(__DIR__ . '/../01-TOOLS/mysql/.env');

return [
    'app_name'      => 'PePiuoX Editor',
    'app_version'   => '1.1.1',
    // URL pública del sitio, sin barra final y sin /public.
    //
    // Se toma de APP_URL (en 01-TOOLS/mysql/.env o en el entorno del proceso), y
    // si no está se mantiene el valor de desarrollo. Así el dominio se cambia al
    // desplegar sin tocar el código ni la base de datos. Es también la base del
    // sitemap cuando no hay una guardada en «Sitemap» (ver SitemapService::baseUrl).
    'app_url'       => (string) (env('APP_URL') ?: 'http://localhost:170'),
    'timezone'      => 'Europe/Madrid',
    'locale'        => 'es_ES',
    // Modo depuración: APP_DEBUG=false en producción. Si no se define, queda en
    // true para no cambiar el comportamiento de desarrollo.
    'debug'         => env('APP_DEBUG') === null
        ? true
        : (bool) filter_var((string) env('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'log_errors'    => true,

    'session' => [
        'name'      => 'PEP_SESSION',
        'lifetime'  => 7200,     // 2h
        'path'      => '/',
        'domain'    => '',
        // Cookie Secure: se activa sola cuando la petición llega por HTTPS. En
        // producción con TLS es obligatoria; en local por http, una cookie Secure
        // no se enviaría nunca y la sesión no funcionaría.
        'secure'    => env('SESSION_SECURE') !== null
            ? (bool) filter_var((string) env('SESSION_SECURE'), FILTER_VALIDATE_BOOLEAN)
            : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly'   => true,
        'samesite'  => 'Lax',
    ],

    'csrf' => [
        'token_name' => '_csrf_token',
        'lifetime'   => 7200,
    ],

    'cache' => [
        'path' => __DIR__ . '/../storage/cache',
        'ttl'  => 3600,
    ],

    'logs' => [
        'path' => __DIR__ . '/../storage/logs',
    ],

    'uploads' => [
        'path'       => __DIR__ . '/../uploads',
        'max_size'   => 10 * 1024 * 1024, // 10MB
        'allowed'    => ['jpg','jpeg','png','gif','webp','svg','pdf','zip'],
    ],

    'password' => [
        'algo'   => PASSWORD_DEFAULT,
        'options'=> ['cost' => 12],
    ],

    'roles' => [
        'SUPER_ADMIN' => 'Super admin',
        'ADMIN'       => 'Admin',
        'MANAGER'     => 'Manager',
        'USER'        => 'Stantard User',
        'GUEST'       => 'Guest',
    ],
    
    'mail' => [
        'from'      => 'no-reply@tudominio.com',
        'from_name' => 'PePiuoX Editor',
        'smtp_host' => '', // dejar vacío para usar mail() nativo
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_encryption' => 'tls', // tls, ssl, o vacío
    ],
    // NUEVO: Emails administrativos
    'email_notifications' => [
        'enabled' => true,
        'admin_email' => '', // Si está vacío, usa el email del Super Admin
        'security_alerts' => true,
        'backup_alerts' => true,
        'daily_digest' => false,
        'weekly_digest' => true,
    ],
    // Token de las tareas programadas (`/cron/*`).
    //
    // Debe ser un secreto **estable**, definido en `01-TOOLS/mysql/.env` como
    // CRON_TOKEN o en el entorno del proceso. Vacío = los endpoints rechazan
    // toda petición: cerrar por defecto es lo correcto cuando no hay secreto,
    // porque un cron sin autenticación puede escribir en la base de datos.
    //
    // Genera uno con: php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
    'cron_token' => (string) (env('CRON_TOKEN') ?? ''),
];