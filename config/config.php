<?php
/**
 * Configuración general de la aplicación.
 */
return [
    'app_name'      => 'PePiuoX Maker',
    'app_version'   => '1.0.0',
    'app_url'       => 'http://localhost:170/public', // sin trailing slash
    'timezone'      => 'Europe/Madrid',
    'locale'        => 'es_ES',
    'debug'         => true, // false en producción
    'log_errors'    => true,

    'session' => [
        'name'      => 'PEP_SESSION',
        'lifetime'  => 7200,     // 2h
        'path'      => '/',
        'domain'    => '',
        'secure'    => false,    // true si usas HTTPS
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
        'from_name' => 'PePiuoX Maker',
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
    'cron_token' => 'cambia-este-token-por-uno-seguro-' . bin2hex(random_bytes(8)),
];