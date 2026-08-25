<?php
declare(strict_types=1);

// 1. Cargar rutas absolutas primero
require __DIR__ . '/../config/paths.php';

// 2. Autoloader PSR-4 usando las constantes definidas
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) require $path;
    }
});

// 3. Helpers globales
require APP_PATH . '/Helpers/functions.php';

// 4. Configuración general
$cfg = require CONFIG_PATH . '/config.php';
date_default_timezone_set($cfg['timezone'] ?? 'UTC');

if ($cfg['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 5. Iniciar sesión y Router
App\Core\Session::start();
App\Core\App::boot();
$router = new App\Core\Router();
$req    = new App\Core\Request();


// Bloquear install si ya existe config válida
if (basename($req->uri()) !== 'install.php' && !is_file(__DIR__ . '/../config/database.php')) {
    App\Core\Response::redirect('install.php');
}

// ====== PÚBLICAS ======
$router->get('/',              [App\Controllers\PublicController::class, 'home']);
$router->get('/sitemap.xml',   [App\Controllers\PublicController::class, 'sitemap']);
$router->get('/login',         [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login',        [App\Controllers\AuthController::class, 'login']);
$router->get('/logout',        [App\Controllers\AuthController::class, 'logout']);

// ====== ADMIN ======
$auth = [App\Middleware\AuthMiddleware::class];

$router->get('/admin',              [App\Controllers\DashboardController::class, 'index'], $auth);

// Dashboard de seguridad
$router->get('/admin/security/dashboard',          [App\Controllers\SecurityDashboardController::class, 'index'],     $auth);
$router->get('/admin/security/dashboard/realtime', [App\Controllers\SecurityDashboardController::class, 'realtime'], $auth);

// Cerrar otras sesiones
$router->post('/admin/profile/sessions/close', [App\Controllers\AuthController::class, 'closeOtherSessions'], $auth);

// ====== CONFIGURACIÓN / SETTINGS ======
$router->get('/admin/settings',          [App\Controllers\SettingsController::class, 'index'],   $auth);
$router->post('/admin/settings/update',  [App\Controllers\SettingsController::class, 'update'],  $auth);

// ====== ACTIVITY LOGS ======
$router->get('/admin/audit/activity',                [App\Controllers\ActivityLogsController::class, 'index'],   $auth);
$router->get('/admin/audit/activity/export',         [App\Controllers\ActivityLogsController::class, 'export'],  $auth);
$router->post('/admin/audit/activity/clean',         [App\Controllers\ActivityLogsController::class, 'clean'],   $auth);
$router->get('/admin/audit/activity/{id}',           [App\Controllers\ActivityLogsController::class, 'show'],    $auth);

// ====== LOGIN ATTEMPTS & SECURITY ======
$router->get('/admin/security/login-attempts',              [App\Controllers\LoginAttemptsController::class, 'index'],       $auth);
$router->post('/admin/security/login-attempts/block',       [App\Controllers\LoginAttemptsController::class, 'blockIp'],     $auth);
$router->post('/admin/security/login-attempts/{id}/unblock',[App\Controllers\LoginAttemptsController::class, 'unblockIp'],   $auth);
$router->post('/admin/security/login-attempts/clean',       [App\Controllers\LoginAttemptsController::class, 'cleanAttempts'],$auth);

// Usuarios
$router->get('/admin/users',            [App\Controllers\UsersController::class, 'index'],  $auth);
$router->get('/admin/users/create',     [App\Controllers\UsersController::class, 'create'], $auth);
$router->post('/admin/users',           [App\Controllers\UsersController::class, 'store'],  $auth);
$router->get('/admin/users/{id}/edit',  [App\Controllers\UsersController::class, 'edit'],   $auth);
$router->post('/admin/users/{id}',      [App\Controllers\UsersController::class, 'update'], $auth);
$router->post('/admin/users/{id}/delete',[App\Controllers\UsersController::class, 'delete'],$auth);

// ====== USUARIOS BANEADOS ======
$router->get('/admin/users/banned',                         [App\Controllers\BannedUsersController::class, 'index'],          $auth);
$router->post('/admin/users/banned/ban',                    [App\Controllers\BannedUsersController::class, 'ban'],            $auth);
$router->post('/admin/users/banned/{id}/unban',             [App\Controllers\BannedUsersController::class, 'unban'],          $auth);
$router->post('/admin/users/banned/{id}/extend',            [App\Controllers\BannedUsersController::class, 'extend'],         $auth);
$router->get('/admin/users/banned/{id}/history',            [App\Controllers\BannedUsersController::class, 'history'],        $auth);
$router->get('/admin/users/banned/history',                 [App\Controllers\BannedUsersController::class, 'globalHistory'],  $auth);
$router->post('/admin/users/banned/bulk-ban',               [App\Controllers\BannedUsersController::class, 'bulkBan'],        $auth);
$router->post('/admin/users/banned/bulk-unban',             [App\Controllers\BannedUsersController::class, 'bulkUnban'],      $auth);
$router->get('/admin/users/banned/search',                  [App\Controllers\BannedUsersController::class, 'searchUsers'],    $auth);
$router->get('/cron/process-expired-bans',                  [App\Controllers\BannedUsersController::class, 'processExpired']);

// Páginas
$router->get('/admin/pages',              [App\Controllers\PagesController::class, 'index'],  $auth);
$router->get('/admin/pages/create',       [App\Controllers\PagesController::class, 'create'], $auth);
$router->post('/admin/pages',             [App\Controllers\PagesController::class, 'store'],  $auth);
$router->get('/admin/pages/{id}/edit',    [App\Controllers\PagesController::class, 'edit'],   $auth);
$router->post('/admin/pages/{id}',        [App\Controllers\PagesController::class, 'update'], $auth);
$router->post('/admin/pages/{id}/delete', [App\Controllers\PagesController::class, 'delete'], $auth);
$router->post('/admin/pages/{id}/autosave',[App\Controllers\PagesController::class, 'autosave'], $auth);

// Versiones
$router->get('/admin/pages/{id}/versions',                     [App\Controllers\VersionsController::class, 'index'],   $auth);
$router->get('/admin/pages/{id}/versions/{vid}/preview',       [App\Controllers\VersionsController::class, 'preview'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/restore',      [App\Controllers\VersionsController::class, 'restore'],$auth);
$router->post('/admin/pages/{id}/versions/{vid}/delete',       [App\Controllers\VersionsController::class, 'delete'], $auth);

// Templates
$router->get('/admin/templates',              [App\Controllers\TemplatesController::class, 'index'],     $auth);
$router->get('/admin/templates/create',       [App\Controllers\TemplatesController::class, 'create'],    $auth);
$router->post('/admin/templates',             [App\Controllers\TemplatesController::class, 'store'],     $auth);
$router->get('/admin/templates/{id}/edit',    [App\Controllers\TemplatesController::class, 'edit'],      $auth);
$router->post('/admin/templates/{id}',        [App\Controllers\TemplatesController::class, 'update'],    $auth);
$router->post('/admin/templates/{id}/delete', [App\Controllers\TemplatesController::class, 'delete'],    $auth);
$router->post('/admin/templates/{id}/duplicate',[App\Controllers\TemplatesController::class, 'duplicate'],$auth);

// Menús
$router->get('/admin/menus',              [App\Controllers\MenusController::class, 'index'],   $auth);
$router->post('/admin/menus',             [App\Controllers\MenusController::class, 'store'],   $auth);
$router->post('/admin/menus/{id}',        [App\Controllers\MenusController::class, 'update'],  $auth);
$router->post('/admin/menus/{id}/delete', [App\Controllers\MenusController::class, 'delete'],  $auth);
$router->post('/admin/menus/reorder',     [App\Controllers\MenusController::class, 'reorder'], $auth);

// Cache
$router->get('/admin/cache',              [App\Controllers\CacheController::class, 'index'],      $auth);
$router->post('/admin/cache/clear',       [App\Controllers\CacheController::class, 'clear'],      $auth);
$router->post('/admin/cache/regenerate',  [App\Controllers\CacheController::class, 'regenerate'], $auth);

// Auditoría
$router->get('/admin/audit',              [App\Controllers\AuditController::class, 'index'], $auth);

// ====== ROLES ======
$router->get('/admin/roles',              [App\Controllers\RolesController::class, 'index'],   $auth);
$router->get('/admin/roles/create',       [App\Controllers\RolesController::class, 'create'],  $auth);
$router->post('/admin/roles',             [App\Controllers\RolesController::class, 'store'],   $auth);
$router->get('/admin/roles/{id}/edit',    [App\Controllers\RolesController::class, 'edit'],    $auth);
$router->post('/admin/roles/{id}',        [App\Controllers\RolesController::class, 'update'],  $auth);
$router->post('/admin/roles/{id}/delete', [App\Controllers\RolesController::class, 'delete'],  $auth);

// ====== ARCHIVOS ======
$router->get('/admin/files',              [App\Controllers\FilesController::class, 'index'],     $auth);
$router->post('/admin/files/upload',      [App\Controllers\FilesController::class, 'upload'],    $auth);
$router->post('/admin/files/{id}/delete', [App\Controllers\FilesController::class, 'delete'],    $auth);
$router->get('/admin/files/{id}/download',[App\Controllers\FilesController::class, 'download'],  $auth);
$router->get('/admin/files/{id}/share',   [App\Controllers\FilesController::class, 'shareView'], $auth);
$router->post('/admin/files/{id}/share',  [App\Controllers\FilesController::class, 'share'],     $auth);
$router->post('/admin/files/wysiwyg-upload', [App\Controllers\FilesController::class, 'wysiwygUpload'], $auth);

// ====== 2FA ======
$router->get('/admin/2fa',              [App\Controllers\TwoFactorController::class, 'setup'],          $auth);
$router->post('/admin/2fa/verify',      [App\Controllers\TwoFactorController::class, 'verify'],         $auth);
$router->post('/admin/2fa/disable',     [App\Controllers\TwoFactorController::class, 'disable'],        $auth);
$router->get('/2fa/challenge',          [App\Controllers\TwoFactorController::class, 'challenge']);
$router->post('/2fa/challenge/verify',  [App\Controllers\TwoFactorController::class, 'challengeVerify']);

// ====== PERFIL ======
$router->get('/admin/profile',              [App\Controllers\ProfileController::class, 'index'],          $auth);
$router->post('/admin/profile',             [App\Controllers\ProfileController::class, 'update'],         $auth);
$router->post('/admin/profile/password',    [App\Controllers\ProfileController::class, 'changePassword'], $auth);

// ====== NOTIFICACIONES ======
$router->get('/admin/notifications',             [App\Controllers\NotificationsController::class, 'index'],      $auth);
$router->get('/admin/notifications/list',        [App\Controllers\NotificationsController::class, 'list'],       $auth);
$router->post('/admin/notifications/mark-all',   [App\Controllers\NotificationsController::class, 'markAllRead'],$auth);
$router->post('/admin/notifications/clear',      [App\Controllers\NotificationsController::class, 'clearAll'],   $auth);
$router->post('/admin/notifications/{id}/read',  [App\Controllers\NotificationsController::class, 'markRead'],   $auth);
$router->post('/admin/notifications/{id}/delete',[App\Controllers\NotificationsController::class, 'delete'],     $auth);

// ====== API (admin) ======
$router->get('/admin/api',                       [App\Controllers\ApiManageController::class, 'index'],          $auth);
$router->get('/admin/api/create',                [App\Controllers\ApiManageController::class, 'create'],         $auth);
$router->post('/admin/api',                      [App\Controllers\ApiManageController::class, 'store'],          $auth);
$router->get('/admin/api/{id}',                  [App\Controllers\ApiManageController::class, 'show'],           $auth);
$router->post('/admin/api/{id}',                 [App\Controllers\ApiManageController::class, 'update'],         $auth);
$router->post('/admin/api/{id}/delete',          [App\Controllers\ApiManageController::class, 'delete'],         $auth);
$router->get('/admin/api/{id}/regenerate',       [App\Controllers\ApiManageController::class, 'regenerate'],     $auth);
$router->post('/admin/api/{id}/tokens',          [App\Controllers\ApiManageController::class, 'createToken'],    $auth);
$router->post('/admin/api/{id}/tokens/{tid}/revoke',[App\Controllers\ApiManageController::class, 'revokeToken'], $auth);

// ====== API REST pública (sin auth de sesión, con middleware de token) ======
$apiMW = [App\Middleware\ApiAuthMiddleware::class];
$router->get('/api/v1/pages',         [App\Controllers\ApiController::class, 'pagesIndex'], $apiMW);
$router->get('/api/v1/pages/{slug}',  [App\Controllers\ApiController::class, 'pagesShow'],  $apiMW);
$router->get('/api/v1/menus/{slug}',  [App\Controllers\ApiController::class, 'menusShow'],  $apiMW);
$router->get('/api/v1/me',            [App\Controllers\ApiController::class, 'me'],         $apiMW);

// ====== COMPONENTES ======
$router->get('/admin/components',              [App\Controllers\ComponentsController::class, 'index'],   $auth);
$router->get('/admin/components/create',       [App\Controllers\ComponentsController::class, 'create'],  $auth);
$router->post('/admin/components',             [App\Controllers\ComponentsController::class, 'store'],   $auth);
$router->get('/admin/components/api',          [App\Controllers\ComponentsController::class, 'apiList'], $auth);
$router->get('/admin/components/{id}/edit',    [App\Controllers\ComponentsController::class, 'edit'],    $auth);
$router->post('/admin/components/{id}',        [App\Controllers\ComponentsController::class, 'update'],  $auth);
$router->post('/admin/components/{id}/delete', [App\Controllers\ComponentsController::class, 'delete'],  $auth);

// ====== I18N ======
$router->get('/lang/{code}',                   [App\Controllers\LanguageController::class, 'switch']);
$router->get('/admin/translations',            [App\Controllers\TranslationsController::class, 'index'], $auth);
$router->post('/admin/translations/save',      [App\Controllers\TranslationsController::class, 'save'],  $auth);
$router->post('/admin/translations/add',       [App\Controllers\TranslationsController::class, 'add'],   $auth);

// ====== BACKUPS ======
$router->get('/admin/backups',                    [App\Controllers\BackupsController::class, 'index'],     $auth);
$router->post('/admin/backups/create',            [App\Controllers\BackupsController::class, 'create'],    $auth);
$router->get('/admin/backups/{id}/download',      [App\Controllers\BackupsController::class, 'download'],  $auth);
$router->post('/admin/backups/{id}/restore',      [App\Controllers\BackupsController::class, 'restore'],   $auth);
$router->post('/admin/backups/{id}/delete',       [App\Controllers\BackupsController::class, 'delete'],    $auth);
$router->post('/admin/backups/schedule',          [App\Controllers\BackupsController::class, 'schedule'],  $auth);
$router->get('/cron/backup',                      [App\Controllers\BackupsController::class, 'cron']);

// ====== ANALYTICS ======
$router->get('/admin/analytics',                  [App\Controllers\AnalyticsController::class, 'index'],      $auth);
$router->get('/admin/analytics/export',           [App\Controllers\AnalyticsController::class, 'export'],     $auth);
$router->get('/admin/analytics/api',              [App\Controllers\AnalyticsController::class, 'apiSummary'], $auth);

// ====== SEO ======
$router->get('/admin/seo',                        [App\Controllers\SeoController::class, 'index'],             $auth);
$router->get('/admin/seo/global',                 [App\Controllers\SeoController::class, 'global'],           $auth);
$router->post('/admin/seo/global/save',           [App\Controllers\SeoController::class, 'globalSave'],       $auth);
$router->get('/admin/seo/{id}/analyze',           [App\Controllers\SeoController::class, 'analyze'],          $auth);
$router->get('/admin/seo/redirects',              [App\Controllers\SeoController::class, 'redirects'],        $auth);
$router->post('/admin/seo/redirects/store',       [App\Controllers\SeoController::class, 'redirectStore'],    $auth);
$router->post('/admin/seo/redirects/{id}/delete', [App\Controllers\SeoController::class, 'redirectDelete'],   $auth);
$router->post('/admin/seo/sitemap/regenerate',    [App\Controllers\SeoController::class, 'regenerateSitemap'],$auth);

// ====== SEO AVANZADO ======
$router->get('/admin/seo/{id}/competitor',           [App\Controllers\SeoController::class, 'competitor'],      $auth);
$router->get('/admin/seo/{id}/meta-generator',       [App\Controllers\SeoController::class, 'metaGenerator'],  $auth);
$router->post('/admin/seo/{id}/generate-meta',       [App\Controllers\SeoController::class, 'generateMeta'],   $auth);
$router->post('/admin/seo/{id}/apply-meta',          [App\Controllers\SeoController::class, 'applyMeta'],      $auth);

// Rutas públicas SEO
// ═══ PÚBLICAS ═══
$router->get('/',              [App\Controllers\PublicController::class, 'home']);
$router->get('/sitemap.xml',   [App\Controllers\PublicController::class, 'sitemap']);
$router->get('/robots.txt',    [App\Controllers\SeoController::class, 'robots']);
$router->get('/login',         [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login',        [App\Controllers\AuthController::class, 'login']);
$router->get('/logout',        [App\Controllers\AuthController::class, 'logout']);

// 2FA challenge (público pero requiere sesión parcial)
$router->get('/2fa/challenge',         [App\Controllers\TwoFactorController::class, 'challenge']);
$router->post('/2fa/challenge/verify', [App\Controllers\AuthController::class, 'verify2FA']);

// ====== RECUPERACIÓN DE CONTRASEÑA (públicas) ======
$router->get('/forgot',        [App\Controllers\PasswordController::class, 'forgotForm']);
$router->post('/forgot',       [App\Controllers\PasswordController::class, 'forgotSend']);
$router->get('/reset',         [App\Controllers\PasswordController::class, 'resetForm']);
$router->post('/reset',        [App\Controllers\PasswordController::class, 'resetSave']);

// ====== FRONTEND PÚBLICO (al final, como catch-all) ======
$router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);

$router->dispatch($req);