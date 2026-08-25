<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. CARGA INICIAL
// ═══════════════════════════════════════════════════════════════════
require __DIR__ . '/../config/paths.php';

// Autoloader PSR-4
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) require $path;
    }
});

// Helpers globales
require APP_PATH . '/Helpers/functions.php';

// Configuración general
$cfg = require CONFIG_PATH . '/config.php';
date_default_timezone_set($cfg['timezone'] ?? 'UTC');

if ($cfg['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}



// ═══════════════════════════════════════════════════════════════════
// 2. SESIÓN Y BOOTSTRAP
// ═══════════════════════════════════════════════════════════════════
App\Core\Session::start();
App\Core\App::boot();

$router = new App\Core\Router();
$req    = new App\Core\Request();

$router->get('/cron/cleanup-rate-limits', function() use ($req) {
    $cfg = require CONFIG_PATH . '/config.php';
    $token = $req->get('token');
    $expected = $cfg['cron_token'] ?? '';
    
    if (!$expected || $token !== $expected) {
        \App\Core\Response::json(['success'=>false,'message'=>'Unauthorized'], 401);
    }
    
    $cleaned = \App\Services\RateLimiter::cleanup();
    \App\Core\Response::json(['success'=>true,'cleaned'=>$cleaned]);
});

$router->get('/cron/security-digest', function() use ($req) {
    $cfg = require CONFIG_PATH . '/config.php';
    $token = $req->get('token');
    $expected = $cfg['cron_token'] ?? '';
    
    if (!$expected || $token !== $expected) {
        \App\Core\Response::json(['success'=>false,'message'=>'Unauthorized'], 401);
    }
    
    $period = $req->get('period', 'daily');
    $success = \App\Services\EmailNotificationService::sendSecurityDigest($period);
    \App\Core\Response::json(['success'=>$success, 'period' => $period]);
});

// Redirigir al instalador si no hay configuración de BD
if (basename($req->uri()) !== 'install.php' && !is_file(CONFIG_PATH . '/database.php')) {
    App\Core\Response::redirect('install.php');
}

// ═══════════════════════════════════════════════════════════════════
// 3. MIDDLEWARES
// ═══════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS PROTEGIDAS POR RATE LIMIT
// ═══════════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════
// MIDDLEWARES
// ═══════════════════════════════════════════════════════════════════
$auth = [App\Middleware\AuthMiddleware::class];

// Rate limiting (objetos instanciados)
$rateLimitLogin = [new App\Middleware\RateLimitMiddleware('login', 5, 60)];
$rateLimitForgot = [new App\Middleware\RateLimitMiddleware('forgot', 3, 3600)];
$rateLimitReset = [new App\Middleware\RateLimitMiddleware('reset', 5, 3600)];
$rateLimit2FA = [new App\Middleware\RateLimitMiddleware('2fa', 5, 60)];

$apiMW = [App\Middleware\ApiAuthMiddleware::class];

// ═══════════════════════════════════════════════════════════════════
// 4. RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════════════

// ── 4.1. SEO Público ─────────────────────────────────────────────
$router->get('/',              [App\Controllers\PublicController::class, 'home']);
$router->get('/sitemap.xml',   [App\Controllers\PublicController::class, 'sitemap']);
$router->get('/robots.txt',    [App\Controllers\SeoController::class, 'robots']);

// ═══════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS
// ═══════════════════════════════════════════════════════════════════

// ── 4.2. Autenticación ───────────────────────────────────────────
// Autenticación (5 intentos por minuto)
// Login con rate limit

$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login'], $rateLimitLogin);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

// ── 4.3. Recuperación de contraseña ──────────────────────────────
// Recuperación de contraseña (3 emails por hora)
// Recuperación de contraseña con rate limit
$router->get('/forgot', [App\Controllers\PasswordController::class, 'forgotForm']);
$router->post('/forgot', [App\Controllers\PasswordController::class, 'forgotSend'], $rateLimitForgot);
$router->get('/reset', [App\Controllers\PasswordController::class, 'resetForm']);
$router->post('/reset', [App\Controllers\PasswordController::class, 'resetSave'], $rateLimitReset);

// ── 4.4. 2FA Challenge (sesión parcial) ──────────────────────────
// 2FA Challenge (5 intentos por minuto)
// 2FA con rate limit
$router->get('/2fa/challenge', [App\Controllers\TwoFactorController::class, 'challenge']);
$router->post('/2fa/challenge/verify', [App\Controllers\AuthController::class, 'verify2FA'], $rateLimit2FA);

// ── 4.5. Internacionalización pública ────────────────────────────
$router->get('/lang/{code}', [App\Controllers\LanguageController::class, 'switch']);

// ── 4.6. Cron endpoints (protegidos por token) ───────────────────
$router->get('/cron/backup',                [App\Controllers\BackupsController::class, 'cron']);
$router->get('/cron/process-expired-bans',  [App\Controllers\BannedUsersController::class, 'processExpired']);

// ═══════════════════════════════════════════════════════════════════
// 5. API REST PÚBLICA (autenticación por token Bearer)
// ═══════════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════
// API REST PÚBLICA
// ═══════════════════════════════════════════════════════════════════
$router->get('/api/v1/pages',         [App\Controllers\ApiController::class, 'pagesIndex'], $apiMW);
$router->get('/api/v1/pages/{slug}',  [App\Controllers\ApiController::class, 'pagesShow'],  $apiMW);
$router->get('/api/v1/menus/{slug}',  [App\Controllers\ApiController::class, 'menusShow'],  $apiMW);
$router->get('/api/v1/me',            [App\Controllers\ApiController::class, 'me'],         $apiMW);

// ═══════════════════════════════════════════════════════════════════
// 6. PANEL ADMINISTRATIVO (requiere autenticación)
// ═══════════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════
// RUTAS ADMIN CON AUTENTICACIÓN
// ═══════════════════════════════════════════════════════════════════════

// ── 6.1. Dashboard Principal ─────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════
// PANEL ADMIN (con auth)
// ═══════════════════════════════════════════════════════════════════
$router->get('/admin', [App\Controllers\DashboardController::class, 'index'], $auth);

// ── 6.2. Perfil de Usuario ───────────────────────────────────────
$router->get('/admin/profile',                  [App\Controllers\ProfileController::class, 'index'],          $auth);
$router->post('/admin/profile',                 [App\Controllers\ProfileController::class, 'update'],         $auth);
$router->post('/admin/profile/password',        [App\Controllers\ProfileController::class, 'changePassword'], $auth);
$router->post('/admin/profile/sessions/close',  [App\Controllers\AuthController::class, 'closeOtherSessions'], $auth);

// ── 6.3. Gestión de Usuarios ─────────────────────────────────────
$router->get('/admin/users',              [App\Controllers\UsersController::class, 'index'],   $auth);
$router->get('/admin/users/create',       [App\Controllers\UsersController::class, 'create'],  $auth);
$router->post('/admin/users',             [App\Controllers\UsersController::class, 'store'],   $auth);
$router->get('/admin/users/{id}/edit',    [App\Controllers\UsersController::class, 'edit'],    $auth);
$router->post('/admin/users/{id}',        [App\Controllers\UsersController::class, 'update'],  $auth);
$router->post('/admin/users/{id}/delete', [App\Controllers\UsersController::class, 'delete'],  $auth);
$router->post('/admin/users/{id}/password', [App\Controllers\UsersController::class, 'changePassword'], $auth);
// ── 6.4. Usuarios Baneados (ORDEN IMPORTANTE: rutas fijas antes de {id}) ─
$router->get('/admin/users/banned',                [App\Controllers\BannedUsersController::class, 'index'],          $auth);
$router->get('/admin/users/banned/history',        [App\Controllers\BannedUsersController::class, 'globalHistory'],  $auth);
$router->get('/admin/users/banned/search',         [App\Controllers\BannedUsersController::class, 'searchUsers'],    $auth);
$router->post('/admin/users/banned/ban',           [App\Controllers\BannedUsersController::class, 'ban'],            $auth);
$router->post('/admin/users/banned/bulk-ban',      [App\Controllers\BannedUsersController::class, 'bulkBan'],        $auth);
$router->post('/admin/users/banned/bulk-unban',    [App\Controllers\BannedUsersController::class, 'bulkUnban'],      $auth);
$router->post('/admin/users/banned/{id}/unban',    [App\Controllers\BannedUsersController::class, 'unban'],          $auth);
$router->post('/admin/users/banned/{id}/extend',   [App\Controllers\BannedUsersController::class, 'extend'],         $auth);
$router->get('/admin/users/banned/{id}/history',   [App\Controllers\BannedUsersController::class, 'history'],        $auth);

// ── 6.5. Roles y Permisos ────────────────────────────────────────
$router->get('/admin/roles',              [App\Controllers\RolesController::class, 'index'],   $auth);
$router->get('/admin/roles/create',       [App\Controllers\RolesController::class, 'create'],  $auth);
$router->post('/admin/roles',             [App\Controllers\RolesController::class, 'store'],   $auth);
$router->get('/admin/roles/{id}/edit',    [App\Controllers\RolesController::class, 'edit'],    $auth);
$router->post('/admin/roles/{id}',        [App\Controllers\RolesController::class, 'update'],  $auth);
$router->post('/admin/roles/{id}/delete', [App\Controllers\RolesController::class, 'delete'],  $auth);

// ── 6.6. Páginas ─────────────────────────────────────────────────
$router->get('/admin/pages',              [App\Controllers\PagesController::class, 'index'],    $auth);
$router->get('/admin/pages/create',       [App\Controllers\PagesController::class, 'create'],   $auth);
$router->post('/admin/pages',             [App\Controllers\PagesController::class, 'store'],    $auth);
$router->get('/admin/pages/{id}/edit',    [App\Controllers\PagesController::class, 'edit'],     $auth);
$router->post('/admin/pages/{id}',        [App\Controllers\PagesController::class, 'update'],   $auth);
$router->post('/admin/pages/{id}/delete', [App\Controllers\PagesController::class, 'delete'],   $auth);
$router->post('/admin/pages/{id}/autosave', [App\Controllers\PagesController::class, 'autosave'], $auth);

// Páginas - Builder visual avanzado
$router->get('/admin/pages/{id}/builder',        [App\Controllers\PagesController::class, 'builder'],      $auth);
$router->post('/admin/pages/{id}/builder/upload',[App\Controllers\PagesController::class, 'uploadAsset'],  $auth);
$router->get('/admin/pages/assets',              [App\Controllers\PagesController::class, 'getAssets'],    $auth);

// ── 6.7. Versiones de Páginas ────────────────────────────────────
$router->get('/admin/pages/{id}/versions',                        [App\Controllers\VersionsController::class, 'index'],   $auth);
$router->get('/admin/pages/{id}/versions/{vid}/preview',          [App\Controllers\VersionsController::class, 'preview'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/restore',         [App\Controllers\VersionsController::class, 'restore'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/delete',          [App\Controllers\VersionsController::class, 'delete'],  $auth);

// ── 6.8. Templates ───────────────────────────────────────────────
$router->get('/admin/templates',                [App\Controllers\TemplatesController::class, 'index'],      $auth);
$router->get('/admin/templates/create',         [App\Controllers\TemplatesController::class, 'create'],     $auth);
$router->post('/admin/templates',               [App\Controllers\TemplatesController::class, 'store'],      $auth);
$router->get('/admin/templates/{id}/edit',      [App\Controllers\TemplatesController::class, 'edit'],       $auth);
$router->post('/admin/templates/{id}',          [App\Controllers\TemplatesController::class, 'update'],     $auth);
$router->post('/admin/templates/{id}/delete',   [App\Controllers\TemplatesController::class, 'delete'],     $auth);
$router->post('/admin/templates/{id}/duplicate',[App\Controllers\TemplatesController::class, 'duplicate'],  $auth);

// ── 6.9. Menús ───────────────────────────────────────────────────
$router->get('/admin/menus',              [App\Controllers\MenusController::class, 'index'],    $auth);
$router->post('/admin/menus',             [App\Controllers\MenusController::class, 'store'],    $auth);
$router->post('/admin/menus/reorder',     [App\Controllers\MenusController::class, 'reorder'],  $auth);
$router->post('/admin/menus/{id}',        [App\Controllers\MenusController::class, 'update'],   $auth);
$router->post('/admin/menus/{id}/delete', [App\Controllers\MenusController::class, 'delete'],   $auth);

// ── 6.10. SEO (ORDEN IMPORTANTE: rutas fijas antes de {id}) ──────
$router->get('/admin/seo',                        [App\Controllers\SeoController::class, 'index'],              $auth);
$router->get('/admin/seo/global',                 [App\Controllers\SeoController::class, 'global'],             $auth);
$router->post('/admin/seo/global/save',           [App\Controllers\SeoController::class, 'globalSave'],         $auth);
$router->get('/admin/seo/redirects',              [App\Controllers\SeoController::class, 'redirects'],          $auth);
$router->post('/admin/seo/redirects/store',       [App\Controllers\SeoController::class, 'redirectStore'],      $auth);
$router->post('/admin/seo/redirects/{id}/delete', [App\Controllers\SeoController::class, 'redirectDelete'],     $auth);
$router->post('/admin/seo/sitemap/regenerate',    [App\Controllers\SeoController::class, 'regenerateSitemap'],  $auth);
// Rutas con {id} al final para evitar conflictos
$router->get('/admin/seo/{id}/analyze',           [App\Controllers\SeoController::class, 'analyze'],            $auth);
$router->get('/admin/seo/{id}/competitor',        [App\Controllers\SeoController::class, 'competitor'],         $auth);
$router->get('/admin/seo/{id}/meta-generator',    [App\Controllers\SeoController::class, 'metaGenerator'],      $auth);
$router->post('/admin/seo/{id}/generate-meta',    [App\Controllers\SeoController::class, 'generateMeta'],       $auth);
$router->post('/admin/seo/{id}/apply-meta',       [App\Controllers\SeoController::class, 'applyMeta'],          $auth);

// ── 6.11. Archivos ───────────────────────────────────────────────
$router->get('/admin/files',                    [App\Controllers\FilesController::class, 'index'],          $auth);
$router->post('/admin/files/upload',            [App\Controllers\FilesController::class, 'upload'],         $auth);
$router->post('/admin/files/wysiwyg-upload',    [App\Controllers\FilesController::class, 'wysiwygUpload'],  $auth);
$router->get('/admin/files/{id}/download',      [App\Controllers\FilesController::class, 'download'],       $auth);
$router->get('/admin/files/{id}/share',         [App\Controllers\FilesController::class, 'shareView'],      $auth);
$router->post('/admin/files/{id}/share',        [App\Controllers\FilesController::class, 'share'],          $auth);
$router->post('/admin/files/{id}/delete',       [App\Controllers\FilesController::class, 'delete'],         $auth);

// ── 6.12. Componentes Reutilizables ──────────────────────────────
$router->get('/admin/components',              [App\Controllers\ComponentsController::class, 'index'],   $auth);
$router->get('/admin/components/api',          [App\Controllers\ComponentsController::class, 'apiList'], $auth);
$router->get('/admin/components/create',       [App\Controllers\ComponentsController::class, 'create'],  $auth);
$router->post('/admin/components',             [App\Controllers\ComponentsController::class, 'store'],   $auth);
$router->get('/admin/components/{id}/edit',    [App\Controllers\ComponentsController::class, 'edit'],    $auth);
$router->post('/admin/components/{id}',        [App\Controllers\ComponentsController::class, 'update'],  $auth);
$router->post('/admin/components/{id}/delete', [App\Controllers\ComponentsController::class, 'delete'],  $auth);

// ── 6.13. 2FA (Autenticación en dos pasos) ──────────────────────
// 2FA (configuración, requiere auth)
// ====== 2FA (configuración, requiere auth) ======
$router->get('/admin/2fa',                    [App\Controllers\TwoFactorController::class, 'setup'],             $auth);
$router->post('/admin/2fa/verify',            [App\Controllers\TwoFactorController::class, 'verify'],            $auth);
$router->post('/admin/2fa/disable',           [App\Controllers\TwoFactorController::class, 'disable'],           $auth);
$router->get('/admin/2fa/recovery-success',   [App\Controllers\TwoFactorController::class, 'recoverySuccess'],   $auth);
$router->post('/admin/2fa/regenerate-codes',  [App\Controllers\TwoFactorController::class, 'regenerateCodes'],   $auth);
$router->get('/admin/2fa/download-codes',     [App\Controllers\TwoFactorController::class, 'downloadCodes'],     $auth);



// ── 6.14. Notificaciones ─────────────────────────────────────────
$router->get('/admin/notifications',              [App\Controllers\NotificationsController::class, 'index'],       $auth);
$router->get('/admin/notifications/list',         [App\Controllers\NotificationsController::class, 'list'],        $auth);
$router->post('/admin/notifications/mark-all',    [App\Controllers\NotificationsController::class, 'markAllRead'], $auth);
$router->post('/admin/notifications/clear',       [App\Controllers\NotificationsController::class, 'clearAll'],    $auth);
$router->post('/admin/notifications/{id}/read',   [App\Controllers\NotificationsController::class, 'markRead'],    $auth);
$router->post('/admin/notifications/{id}/delete', [App\Controllers\NotificationsController::class, 'delete'],      $auth);

// ── 6.15. Cache ──────────────────────────────────────────────────
$router->get('/admin/cache',              [App\Controllers\CacheController::class, 'index'],       $auth);
$router->post('/admin/cache/clear',       [App\Controllers\CacheController::class, 'clear'],       $auth);
$router->post('/admin/cache/regenerate',  [App\Controllers\CacheController::class, 'regenerate'],  $auth);

// ── 6.16. Configuración del Sistema ──────────────────────────────
$router->get('/admin/settings',          [App\Controllers\SettingsController::class, 'index'],   $auth);
$router->post('/admin/settings/update',  [App\Controllers\SettingsController::class, 'update'],  $auth);

// ── 6.17. Backups ────────────────────────────────────────────────
$router->get('/admin/backups',                [App\Controllers\BackupsController::class, 'index'],       $auth);
$router->post('/admin/backups/create',        [App\Controllers\BackupsController::class, 'create'],      $auth);
$router->post('/admin/backups/schedule',      [App\Controllers\BackupsController::class, 'schedule'],    $auth);
$router->get('/admin/backups/{id}/download',  [App\Controllers\BackupsController::class, 'download'],    $auth);
$router->post('/admin/backups/{id}/restore',  [App\Controllers\BackupsController::class, 'restore'],     $auth);
$router->post('/admin/backups/{id}/delete',   [App\Controllers\BackupsController::class, 'delete'],      $auth);

// ── 6.18. API Management (Admin) ─────────────────────────────────
$router->get('/admin/api',                            [App\Controllers\ApiManageController::class, 'index'],           $auth);
$router->get('/admin/api/create',                     [App\Controllers\ApiManageController::class, 'create'],          $auth);
$router->post('/admin/api',                           [App\Controllers\ApiManageController::class, 'store'],           $auth);
$router->get('/admin/api/{id}',                       [App\Controllers\ApiManageController::class, 'show'],            $auth);
$router->post('/admin/api/{id}',                      [App\Controllers\ApiManageController::class, 'update'],          $auth);
$router->post('/admin/api/{id}/delete',               [App\Controllers\ApiManageController::class, 'delete'],          $auth);
$router->get('/admin/api/{id}/regenerate',            [App\Controllers\ApiManageController::class, 'regenerate'],      $auth);
$router->post('/admin/api/{id}/tokens',               [App\Controllers\ApiManageController::class, 'createToken'],     $auth);
$router->post('/admin/api/{id}/tokens/{tid}/revoke',  [App\Controllers\ApiManageController::class, 'revokeToken'],     $auth);

// ── 6.19. Internacionalización (Admin) ───────────────────────────
$router->get('/admin/translations',          [App\Controllers\TranslationsController::class, 'index'],  $auth);
$router->post('/admin/translations/save',    [App\Controllers\TranslationsController::class, 'save'],   $auth);
$router->post('/admin/translations/add',     [App\Controllers\TranslationsController::class, 'add'],    $auth);

// ── 6.20. Métricas / Analytics ───────────────────────────────────
$router->get('/admin/analytics',        [App\Controllers\AnalyticsController::class, 'index'],       $auth);
$router->get('/admin/analytics/export', [App\Controllers\AnalyticsController::class, 'export'],      $auth);
$router->get('/admin/analytics/api',    [App\Controllers\AnalyticsController::class, 'apiSummary'],  $auth);

// ── 6.21. Auditoría General ──────────────────────────────────────
$router->get('/admin/audit', [App\Controllers\AuditController::class, 'index'], $auth);

// ── 6.22. Activity Logs (ORDEN IMPORTANTE: export antes de {id}) ─
$router->get('/admin/audit/activity',              [App\Controllers\ActivityLogsController::class, 'index'],   $auth);
$router->get('/admin/audit/activity/export',       [App\Controllers\ActivityLogsController::class, 'export'],  $auth);
$router->post('/admin/audit/activity/clean',       [App\Controllers\ActivityLogsController::class, 'clean'],   $auth);
$router->get('/admin/audit/activity/{id}',         [App\Controllers\ActivityLogsController::class, 'show'],    $auth);

// ── 6.23. Seguridad - Dashboard ──────────────────────────────────
$router->get('/admin/security/dashboard',           [App\Controllers\SecurityDashboardController::class, 'index'],     $auth);
$router->get('/admin/security/dashboard/realtime',  [App\Controllers\SecurityDashboardController::class, 'realtime'],  $auth);

// ── 6.24. Seguridad - Login Attempts (ORDEN: block antes de {id}) ─
$router->get('/admin/security/login-attempts',              [App\Controllers\LoginAttemptsController::class, 'index'],         $auth);
$router->post('/admin/security/login-attempts/block',       [App\Controllers\LoginAttemptsController::class, 'blockIp'],       $auth);
$router->post('/admin/security/login-attempts/clean',       [App\Controllers\LoginAttemptsController::class, 'cleanAttempts'], $auth);
$router->post('/admin/security/login-attempts/{id}/unblock',[App\Controllers\LoginAttemptsController::class, 'unblockIp'],     $auth);

// Dispositivos de confianza
$router->get('/admin/security/trusted-devices',              [App\Controllers\TrustedDevicesController::class, 'index'],      $auth);
$router->post('/admin/security/trusted-devices/revoke-all',  [App\Controllers\TrustedDevicesController::class, 'revokeAll'],  $auth);
$router->post('/admin/security/trusted-devices/{id}/revoke', [App\Controllers\TrustedDevicesController::class, 'revoke'],     $auth);
$router->get('/cron/cleanup-trusted-devices',                function() use ($req) {
    $cfg = require CONFIG_PATH . '/config.php';
    $token = $req->get('token');
    $expected = $cfg['cron_token'] ?? '';
    if (!$expected || $token !== $expected) {
        \App\Core\Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    $cleaned = \App\Services\TrustedDeviceService::cleanupExpired();
    \App\Core\Response::json(['success' => true, 'cleaned' => $cleaned]);
});
// ═══════════════════════════════════════════════════════════════════
// 7. CATCH-ALL: Frontend Público (DEBE IR AL FINAL)
// ═══════════════════════════════════════════════════════════════════
$router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);

// ═══════════════════════════════════════════════════════════════════
// 8. DISPATCH
// ═══════════════════════════════════════════════════════════════════
$router->dispatch($req);

