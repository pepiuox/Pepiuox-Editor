<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. BOOTSTRAP INICIAL
// ═══════════════════════════════════════════════════════════════════
require __DIR__ . '/../config/paths.php';

spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) require $path;
    }
});

require APP_PATH . '/Helpers/functions.php';

$cfg = require CONFIG_PATH . '/config.php';
date_default_timezone_set($cfg['timezone'] ?? 'UTC');

if ($cfg['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

App\Core\Session::start();
App\Core\App::boot();

$router = new App\Core\Router();
$req    = new App\Core\Request();

// ═══════════════════════════════════════════════════════════════════
// PÁGINAS PÚBLICAS
// ═══════════════════════════════════════════════════════════════════
$router->get('/page', function() use ($req) {
    require __DIR__ . '/page.php';
});

$router->get('/page/{slug}', function() use ($req) {
    $_GET['slug'] = $req->uri();
    require __DIR__ . '/page.php';
});

// Redirigir al instalador si no hay BD configurada
if (basename($req->uri()) !== 'install.php' && !is_file(CONFIG_PATH . '/database.php')) {
    App\Core\Response::redirect('install.php');
}

// ═══════════════════════════════════════════════════════════════════
// 2. MIDDLEWARES
// ═══════════════════════════════════════════════════════════════════
$auth           = [App\Middleware\AuthMiddleware::class];
$apiMW          = [App\Middleware\ApiAuthMiddleware::class];
$rateLimitLogin = [new App\Middleware\RateLimitMiddleware('login', 5, 60)];
$rateLimitForgot= [new App\Middleware\RateLimitMiddleware('forgot', 3, 3600)];
$rateLimitReset = [new App\Middleware\RateLimitMiddleware('reset', 5, 3600)];
$rateLimit2FA   = [new App\Middleware\RateLimitMiddleware('2fa', 3, 60)];

// ═══════════════════════════════════════════════════════════════════
// 3. RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════════════

// ── SEO público ──────────────────────────────────────────────────
$router->get('/',             [App\Controllers\PublicController::class, 'home']);
$router->get('/sitemap.xml',  [App\Controllers\PublicController::class, 'sitemap']);
$router->get('/robots.txt',   [App\Controllers\SeoController::class, 'robots']);

// ── Autenticación ────────────────────────────────────────────────
$router->get('/login',  [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login'], $rateLimitLogin);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

// ── Recuperación de contraseña ───────────────────────────────────
$router->get('/forgot',  [App\Controllers\PasswordController::class, 'forgotForm']);
$router->post('/forgot', [App\Controllers\PasswordController::class, 'forgotSend'], $rateLimitForgot);
$router->get('/reset',   [App\Controllers\PasswordController::class, 'resetForm']);
$router->post('/reset',  [App\Controllers\PasswordController::class, 'resetSave'], $rateLimitReset);

// ── 2FA Challenge (público con sesión parcial) ───────────────────
$router->get('/2fa/challenge',         [App\Controllers\TwoFactorController::class, 'challenge']);
$router->post('/2fa/challenge/verify', [App\Controllers\AuthController::class, 'verify2FA'], $rateLimit2FA);

// ── Internacionalización ─────────────────────────────────────────
$router->get('/lang/{code}', [App\Controllers\LanguageController::class, 'switch']);

// ── Cron endpoints (protegidos por token) ────────────────────────
$router->get('/cron/backup', [App\Controllers\BackupsController::class, 'cron']);

$router->get('/cron/process-expired-bans', [App\Controllers\BannedUsersController::class, 'processExpired']);

$router->get('/cron/cleanup-trusted-devices', function() use ($req) {
    $cfg = require CONFIG_PATH . '/config.php';
    $token = $req->get('token');
    $expected = $cfg['cron_token'] ?? '';
    if (!$expected || $token !== $expected) {
        \App\Core\Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    $cleaned = \App\Services\TrustedDeviceService::cleanupExpired();
    \App\Core\Response::json(['success' => true, 'cleaned' => $cleaned]);
});

$router->get('/cron/cleanup-collaboration', function() use ($req) {
    $cfg = require CONFIG_PATH . '/config.php';
    $token = $req->get('token');
    $expected = $cfg['cron_token'] ?? '';
    if (!$expected || $token !== $expected) {
        \App\Core\Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    $cleaned = \App\Core\Database::execute(
        "UPDATE collaboration_sessions SET is_active = 0 WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 30 SECOND)"
    );
    $locksCleaned = \App\Core\Database::execute("DELETE FROM collaboration_locks WHERE expires_at < NOW()");
    $eventsCleaned = \App\Core\Database::execute(
        "DELETE FROM collaboration_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    \App\Core\Response::json([
        'success' => true,
        'cleaned' => ['sessions' => $cleaned, 'locks' => $locksCleaned, 'events' => $eventsCleaned]
    ]);
});

// ═══════════════════════════════════════════════════════════════════
// 4. API REST PÚBLICA (Bearer Token)
// ═══════════════════════════════════════════════════════════════════
$router->get('/api/v1/pages',        [App\Controllers\ApiController::class, 'pagesIndex'], $apiMW);
$router->get('/api/v1/pages/{slug}', [App\Controllers\ApiController::class, 'pagesShow'],  $apiMW);
$router->get('/api/v1/menus/{slug}', [App\Controllers\ApiController::class, 'menusShow'],  $apiMW);
$router->get('/api/v1/me',           [App\Controllers\ApiController::class, 'me'],         $apiMW);

// ═══════════════════════════════════════════════════════════════════
// 5. PANEL ADMINISTRATIVO (requiere autenticación)
// ═══════════════════════════════════════════════════════════════════

// ── Dashboard principal ──────────────────────────────────────────
$router->get('/admin', [App\Controllers\DashboardController::class, 'index'], $auth);

// ── Perfil de usuario ────────────────────────────────────────────
$router->get('/admin/profile',                 [App\Controllers\ProfileController::class, 'index'],           $auth);
$router->post('/admin/profile',                [App\Controllers\ProfileController::class, 'update'],          $auth);
$router->post('/admin/profile/password',       [App\Controllers\ProfileController::class, 'changePassword'],  $auth);
$router->post('/admin/profile/sessions/close', [App\Controllers\AuthController::class, 'closeOtherSessions'], $auth);

// ═══════════════════════════════════════════════════════════════════
// USUARIOS
// ═══════════════════════════════════════════════════════════════════

// Rutas fijas ANTES que {id}
$router->get('/admin/users',              [App\Controllers\UsersController::class, 'index'],   $auth);
$router->get('/admin/users/create',       [App\Controllers\UsersController::class, 'create'],  $auth);
$router->post('/admin/users',             [App\Controllers\UsersController::class, 'store'],   $auth);

// Rutas con {id}
$router->get('/admin/users/{id}/edit',        [App\Controllers\UsersController::class, 'edit'],           $auth);
$router->post('/admin/users/{id}',            [App\Controllers\UsersController::class, 'update'],         $auth);
$router->post('/admin/users/{id}/delete',     [App\Controllers\UsersController::class, 'delete'],         $auth);
$router->post('/admin/users/{id}/password',   [App\Controllers\UsersController::class, 'changePassword'], $auth);
// ═══════════════════════════════════════════════════════════════════
// USUARIOS BANEADOS
// ⚠️ IMPORTANTE: Rutas fijas ANTES que rutas con {id}
// ═══════════════════════════════════════════════════════════════════

// 1️⃣ RUTAS FIJAS (sin parámetros) — VAN PRIMERO
$router->get('/admin/users/banned',              [App\Controllers\BannedUsersController::class, 'index'],          $auth);
$router->get('/admin/users/banned/history',      [App\Controllers\BannedUsersController::class, 'globalHistory'],  $auth);
$router->get('/admin/users/banned/search',       [App\Controllers\BannedUsersController::class, 'searchUsers'],    $auth);
$router->post('/admin/users/banned/ban',         [App\Controllers\BannedUsersController::class, 'ban'],            $auth);
$router->post('/admin/users/banned/bulk-ban',    [App\Controllers\BannedUsersController::class, 'bulkBan'],        $auth);
$router->post('/admin/users/banned/bulk-unban',  [App\Controllers\BannedUsersController::class, 'bulkUnban'],      $auth);

// 2️⃣ RUTAS CON {id} — VAN DESPUÉS
$router->post('/admin/users/banned/{id}/unban',  [App\Controllers\BannedUsersController::class, 'unban'],          $auth);
$router->post('/admin/users/banned/{id}/extend', [App\Controllers\BannedUsersController::class, 'extend'],         $auth);
$router->get('/admin/users/banned/{id}/history', [App\Controllers\BannedUsersController::class, 'history'],        $auth);

$router->get('/admin/users/search', [App\Controllers\UsersController::class, 'search'], $auth);

// ── Roles y permisos ─────────────────────────────────────────────
$router->get('/admin/roles',              [App\Controllers\RolesController::class, 'index'],   $auth);
$router->get('/admin/roles/create',       [App\Controllers\RolesController::class, 'create'],  $auth);
$router->post('/admin/roles',             [App\Controllers\RolesController::class, 'store'],   $auth);
$router->get('/admin/roles/{id}/edit',    [App\Controllers\RolesController::class, 'edit'],    $auth);
$router->post('/admin/roles/{id}',        [App\Controllers\RolesController::class, 'update'],  $auth);
$router->post('/admin/roles/{id}/delete', [App\Controllers\RolesController::class, 'delete'],  $auth);

// ── Páginas (⚠️ RUTAS FIJAS ANTES DE {id}) ───────────────────────
$router->get('/admin/pages',                      [App\Controllers\PagesController::class, 'index'],      $auth);
$router->get('/admin/pages/create',               [App\Controllers\PagesController::class, 'create'],     $auth);
$router->post('/admin/pages',                     [App\Controllers\PagesController::class, 'store'],      $auth);
$router->get('/admin/pages/assets',               [App\Controllers\PagesController::class, 'getAssets'],  $auth); // ← FIJA
$router->get('/admin/pages/{id}/builder',         [App\Controllers\PagesController::class, 'builder'],    $auth);
$router->post('/admin/pages/{id}/builder/upload', [App\Controllers\PagesController::class, 'uploadAsset'],$auth);
$router->get('/admin/pages/{id}/edit',            [App\Controllers\PagesController::class, 'edit'],       $auth);
$router->post('/admin/pages/{id}',                [App\Controllers\PagesController::class, 'update'],     $auth);
$router->post('/admin/pages/{id}/delete',         [App\Controllers\PagesController::class, 'delete'],     $auth);
// Autosave (GET para obtener, POST para guardar, POST para limpiar — Router no soporta DELETE)
$router->get('/admin/pages/{id}/autosave',        [App\Controllers\PagesController::class, 'getAutosave'],    $auth);
$router->post('/admin/pages/{id}/autosave',       [App\Controllers\PagesController::class, 'autosave'],       $auth);
$router->post('/admin/pages/{id}/autosave/clear', [App\Controllers\PagesController::class, 'clearAutosave'],  $auth); // ← POST en vez de DELETE

// ── Versiones de páginas ─────────────────────────────────────────
$router->get('/admin/pages/{id}/versions',                       [App\Controllers\VersionsController::class, 'index'],   $auth);
$router->get('/admin/pages/{id}/versions/{vid}/preview',         [App\Controllers\VersionsController::class, 'preview'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/restore',        [App\Controllers\VersionsController::class, 'restore'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/delete',         [App\Controllers\VersionsController::class, 'delete'],  $auth);

// ═══ COMENTARIOS EN COMPONENTES ═══
$router->get('/admin/pages/{id}/comments',                    [App\Controllers\CommentsController::class, 'index'],    $auth);
$router->post('/admin/pages/{id}/comments',                   [App\Controllers\CommentsController::class, 'create'],   $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/reply',    [App\Controllers\CommentsController::class, 'reply'],    $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/resolve',  [App\Controllers\CommentsController::class, 'resolve'],  $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/reopen',   [App\Controllers\CommentsController::class, 'reopen'],   $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/delete',   [App\Controllers\CommentsController::class, 'delete'],   $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/move',     [App\Controllers\CommentsController::class, 'move'],     $auth);
$router->get('/admin/profile/mentions',                     [App\Controllers\CommentsController::class, 'mentions'], $auth);

// ── Templates ────────────────────────────────────────────────────
$router->get('/admin/templates',                 [App\Controllers\TemplatesController::class, 'index'],      $auth);
$router->get('/admin/templates/create',          [App\Controllers\TemplatesController::class, 'create'],     $auth);
$router->post('/admin/templates',                [App\Controllers\TemplatesController::class, 'store'],      $auth);
$router->get('/admin/templates/{id}/edit',       [App\Controllers\TemplatesController::class, 'edit'],       $auth);
$router->post('/admin/templates/{id}',           [App\Controllers\TemplatesController::class, 'update'],     $auth);
$router->post('/admin/templates/{id}/delete',    [App\Controllers\TemplatesController::class, 'delete'],     $auth);
$router->post('/admin/templates/{id}/duplicate', [App\Controllers\TemplatesController::class, 'duplicate'],  $auth);

// ── Menús (⚠️ reorder ANTES de {id}) ─────────────────────────────
$router->get('/admin/menus',              [App\Controllers\MenusController::class, 'index'],    $auth);
$router->post('/admin/menus',             [App\Controllers\MenusController::class, 'store'],    $auth);
$router->post('/admin/menus/reorder',     [App\Controllers\MenusController::class, 'reorder'],  $auth); // ← FIJA
$router->post('/admin/menus/{id}',        [App\Controllers\MenusController::class, 'update'],   $auth);
$router->post('/admin/menus/{id}/delete', [App\Controllers\MenusController::class, 'delete'],   $auth);

// ── SEO (⚠️ RUTAS FIJAS ANTES DE {id}) ───────────────────────────
$router->get('/admin/seo',                        [App\Controllers\SeoController::class, 'index'],              $auth);
$router->get('/admin/seo/global',                 [App\Controllers\SeoController::class, 'global'],             $auth); // ← FIJA
$router->post('/admin/seo/global/save',           [App\Controllers\SeoController::class, 'globalSave'],         $auth);
$router->get('/admin/seo/redirects',              [App\Controllers\SeoController::class, 'redirects'],          $auth); // ← FIJA
$router->post('/admin/seo/redirects/store',       [App\Controllers\SeoController::class, 'redirectStore'],      $auth);
$router->post('/admin/seo/redirects/{id}/delete', [App\Controllers\SeoController::class, 'redirectDelete'],     $auth);
$router->post('/admin/seo/sitemap/regenerate',    [App\Controllers\SeoController::class, 'regenerateSitemap'],  $auth);
// Rutas con {id} al final
$router->get('/admin/seo/{id}/analyze',           [App\Controllers\SeoController::class, 'analyze'],            $auth);
$router->get('/admin/seo/{id}/competitor',        [App\Controllers\SeoController::class, 'competitor'],         $auth);
$router->get('/admin/seo/{id}/meta-generator',    [App\Controllers\SeoController::class, 'metaGenerator'],      $auth);
$router->post('/admin/seo/{id}/generate-meta',    [App\Controllers\SeoController::class, 'generateMeta'],       $auth);
$router->post('/admin/seo/{id}/apply-meta',       [App\Controllers\SeoController::class, 'applyMeta'],          $auth);

// ── Archivos (⚠️ wysiwyg-upload ANTES de {id}) ───────────────────
$router->get('/admin/files',                    [App\Controllers\FilesController::class, 'index'],          $auth);
$router->post('/admin/files/upload',            [App\Controllers\FilesController::class, 'upload'],         $auth);
$router->post('/admin/files/wysiwyg-upload',    [App\Controllers\FilesController::class, 'wysiwygUpload'],  $auth); // ← FIJA
$router->get('/admin/files/{id}/download',      [App\Controllers\FilesController::class, 'download'],       $auth);
$router->get('/admin/files/{id}/share',         [App\Controllers\FilesController::class, 'shareView'],      $auth);
$router->post('/admin/files/{id}/share',        [App\Controllers\FilesController::class, 'share'],          $auth);
$router->post('/admin/files/{id}/delete',       [App\Controllers\FilesController::class, 'delete'],         $auth);

// ── 2FA (configuración, requiere auth) ───────────────────────────
$router->get('/admin/2fa',                    [App\Controllers\TwoFactorController::class, 'setup'],             $auth);
$router->post('/admin/2fa/verify',            [App\Controllers\TwoFactorController::class, 'verify'],            $auth);
$router->post('/admin/2fa/disable',           [App\Controllers\TwoFactorController::class, 'disable'],           $auth);
$router->get('/admin/2fa/recovery-success',   [App\Controllers\TwoFactorController::class, 'recoverySuccess'],   $auth); // ← FIJA
$router->post('/admin/2fa/regenerate-codes',  [App\Controllers\TwoFactorController::class, 'regenerateCodes'],   $auth);
$router->get('/admin/2fa/download-codes',     [App\Controllers\TwoFactorController::class, 'downloadCodes'],     $auth); // ← FIJA

// ── Notificaciones (⚠️ mark-all/clear ANTES de {id}) ─────────────
$router->get('/admin/notifications',              [App\Controllers\NotificationsController::class, 'index'],       $auth);
$router->get('/admin/notifications/list',         [App\Controllers\NotificationsController::class, 'list'],        $auth); // ← FIJA
$router->post('/admin/notifications/mark-all',    [App\Controllers\NotificationsController::class, 'markAllRead'], $auth); // ← FIJA
$router->post('/admin/notifications/clear',       [App\Controllers\NotificationsController::class, 'clearAll'],    $auth); // ← FIJA
$router->post('/admin/notifications/{id}/read',   [App\Controllers\NotificationsController::class, 'markRead'],    $auth);
$router->post('/admin/notifications/{id}/delete', [App\Controllers\NotificationsController::class, 'delete'],      $auth);

// ── Cache ────────────────────────────────────────────────────────
$router->get('/admin/cache',              [App\Controllers\CacheController::class, 'index'],       $auth);
$router->post('/admin/cache/clear',       [App\Controllers\CacheController::class, 'clear'],       $auth);
$router->post('/admin/cache/regenerate',  [App\Controllers\CacheController::class, 'regenerate'],  $auth);

// ── Configuración del sistema ────────────────────────────────────
$router->get('/admin/settings',          [App\Controllers\SettingsController::class, 'index'],   $auth);
$router->post('/admin/settings/update',  [App\Controllers\SettingsController::class, 'update'],  $auth);

// ── Backups ──────────────────────────────────────────────────────
$router->get('/admin/backups',                    [App\Controllers\BackupsController::class, 'index'],      $auth);
$router->post('/admin/backups/create',            [App\Controllers\BackupsController::class, 'create'],     $auth);
$router->post('/admin/backups/schedule',          [App\Controllers\BackupsController::class, 'schedule'],   $auth);
$router->get('/admin/backups/{id}/download',      [App\Controllers\BackupsController::class, 'download'],   $auth);
$router->post('/admin/backups/{id}/restore',      [App\Controllers\BackupsController::class, 'restore'],    $auth);
$router->post('/admin/backups/{id}/delete',       [App\Controllers\BackupsController::class, 'delete'],     $auth);

// ── API Management ───────────────────────────────────────────────
$router->get('/admin/api',                            [App\Controllers\ApiManageController::class, 'index'],           $auth);
$router->get('/admin/api/create',                     [App\Controllers\ApiManageController::class, 'create'],          $auth);
$router->post('/admin/api',                           [App\Controllers\ApiManageController::class, 'store'],           $auth);
$router->get('/admin/api/{id}',                       [App\Controllers\ApiManageController::class, 'show'],            $auth);
$router->post('/admin/api/{id}',                      [App\Controllers\ApiManageController::class, 'update'],          $auth);
$router->post('/admin/api/{id}/delete',               [App\Controllers\ApiManageController::class, 'delete'],          $auth);
$router->get('/admin/api/{id}/regenerate',            [App\Controllers\ApiManageController::class, 'regenerate'],      $auth);
$router->post('/admin/api/{id}/tokens',               [App\Controllers\ApiManageController::class, 'createToken'],     $auth);
$router->post('/admin/api/{id}/tokens/{tid}/revoke',  [App\Controllers\ApiManageController::class, 'revokeToken'],     $auth);

// ── Componentes (⚠️ api ANTES de {id}) ───────────────────────────
$router->get('/admin/components',              [App\Controllers\ComponentsController::class, 'index'],   $auth);
$router->get('/admin/components/api',          [App\Controllers\ComponentsController::class, 'apiList'], $auth); // ← FIJA
$router->get('/admin/components/create',       [App\Controllers\ComponentsController::class, 'create'],  $auth);
$router->post('/admin/components',             [App\Controllers\ComponentsController::class, 'store'],   $auth);
$router->get('/admin/components/{id}/edit',    [App\Controllers\ComponentsController::class, 'edit'],    $auth);
$router->post('/admin/components/{id}',        [App\Controllers\ComponentsController::class, 'update'],  $auth);
$router->post('/admin/components/{id}/delete', [App\Controllers\ComponentsController::class, 'delete'],  $auth);

// ── Traducciones ─────────────────────────────────────────────────
$router->get('/admin/translations',          [App\Controllers\TranslationsController::class, 'index'],  $auth);
$router->post('/admin/translations/save',    [App\Controllers\TranslationsController::class, 'save'],   $auth);
$router->post('/admin/translations/add',     [App\Controllers\TranslationsController::class, 'add'],    $auth);

// ── Analytics (⚠️ export/api ANTES de catch-all) ─────────────────
$router->get('/admin/analytics',        [App\Controllers\AnalyticsController::class, 'index'],       $auth);
$router->get('/admin/analytics/export', [App\Controllers\AnalyticsController::class, 'export'],      $auth); // ← FIJA
$router->get('/admin/analytics/api',    [App\Controllers\AnalyticsController::class, 'apiSummary'],  $auth); // ← FIJA

// ── Auditoría general ────────────────────────────────────────────
$router->get('/admin/audit', [App\Controllers\AuditController::class, 'index'], $auth);

// ── Activity Logs (⚠️ export ANTES de {id}) ──────────────────────
$router->get('/admin/audit/activity',              [App\Controllers\ActivityLogsController::class, 'index'],   $auth);
$router->get('/admin/audit/activity/export',       [App\Controllers\ActivityLogsController::class, 'export'],  $auth);
$router->post('/admin/audit/activity/clean',       [App\Controllers\ActivityLogsController::class, 'clean'],   $auth);
$router->get('/admin/audit/activity/{id}',         [App\Controllers\ActivityLogsController::class, 'show'],    $auth);

// ── Seguridad (⚠️ rutas fijas ANTES de {id}) ─────────────────────
$router->get('/admin/security/dashboard',                  [App\Controllers\SecurityDashboardController::class, 'index'],     $auth);
$router->get('/admin/security/dashboard/realtime',         [App\Controllers\SecurityDashboardController::class, 'realtime'],  $auth);
$router->get('/admin/security/login-attempts',             [App\Controllers\LoginAttemptsController::class, 'index'],         $auth);
$router->post('/admin/security/login-attempts/block',      [App\Controllers\LoginAttemptsController::class, 'blockIp'],       $auth);
$router->post('/admin/security/login-attempts/clean',      [App\Controllers\LoginAttemptsController::class, 'cleanAttempts'], $auth);
$router->post('/admin/security/login-attempts/{id}/unblock',[App\Controllers\LoginAttemptsController::class, 'unblockIp'],    $auth);

// ═══ DISPOSITIVOS DE CONFIANZA ═══
// ⚠️ IMPORTANTE: revoke-all ANTES que {id}/revoke
$router->get('/admin/security/trusted-devices',                 [App\Controllers\TrustedDevicesController::class, 'index'],      $auth);
$router->post('/admin/security/trusted-devices/revoke-all',     [App\Controllers\TrustedDevicesController::class, 'revokeAll'],  $auth);
$router->post('/admin/security/trusted-devices/{id}/revoke',    [App\Controllers\TrustedDevicesController::class, 'revoke'],     $auth);

// ── Colaboración en tiempo real ──────────────────────────────────
$router->get('/admin/collaboration/{id}/join',            [App\Controllers\CollaborationController::class, 'join'],            $auth);
$router->post('/admin/collaboration/{id}/leave',          [App\Controllers\CollaborationController::class, 'leave'],          $auth);
$router->post('/admin/collaboration/{id}/heartbeat',      [App\Controllers\CollaborationController::class, 'heartbeat'],      $auth);
$router->get('/admin/collaboration/{id}/poll',            [App\Controllers\CollaborationController::class, 'poll'],            $auth);
$router->post('/admin/collaboration/{id}/publish-change', [App\Controllers\CollaborationController::class, 'publishChange'],  $auth);
$router->post('/admin/collaboration/{id}/publish-cursor', [App\Controllers\CollaborationController::class, 'publishCursor'],  $auth);
$router->post('/admin/collaboration/{id}/acquire-lock',   [App\Controllers\CollaborationController::class, 'acquireLock'],    $auth);
$router->post('/admin/collaboration/{id}/release-lock',   [App\Controllers\CollaborationController::class, 'releaseLock'],    $auth);
$router->post('/admin/collaboration/{id}/send-message',   [App\Controllers\CollaborationController::class, 'sendMessage'],    $auth);
$router->get('/admin/collaboration/{id}/messages',        [App\Controllers\CollaborationController::class, 'getMessages'],    $auth);
$router->get('/admin/collaboration/{id}/stats',           [App\Controllers\CollaborationController::class, 'stats'],           $auth);

// ═══════════════════════════════════════════════════════════════════
// 6. CATCH-ALL (SIEMPRE AL FINAL)
// ═══════════════════════════════════════════════════════════════════
$router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);

// ═══════════════════════════════════════════════════════════════════
// 7. DISPATCH
// ═══════════════════════════════════════════════════════════════════
$router->dispatch($req);