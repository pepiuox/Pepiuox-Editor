<?php
/**
 * Archivo de rutas de la aplicación
 * 
 * Este archivo contiene todas las rutas del sistema organizadas por módulos
 * 
 * @package Config
 */

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

// ═══════════════════════════════════════════════════════════════════
// DEFINICIÓN DE MIDDLEWARES
// ═══════════════════════════════════════════════════════════════════

$auth = [App\Middleware\AuthMiddleware::class];
$apiMW = [App\Middleware\ApiAuthMiddleware::class];

// Rate limiting.
//
// Si la construcción falla, **no se degrada a «sin límite»**: se coloca el
// guardián que falla en cerrado (`RateLimitUnavailableMiddleware`), que responde
// 503 en vez de aceptar intentos sin contar. Un limitador roto no puede
// convertirse en acceso libre a fuerza bruta (deuda S-5).
try {
    $rateLimitLogin = [new App\Middleware\RateLimitMiddleware('login', 5, 60)];
    $rateLimitRegister = [new App\Middleware\RateLimitMiddleware('register', 3, 3600)];
    $rateLimitForgot = [new App\Middleware\RateLimitMiddleware('forgot', 3, 3600)];
    $rateLimitReset = [new App\Middleware\RateLimitMiddleware('reset', 5, 3600)];
    $rateLimit2FA = [new App\Middleware\RateLimitMiddleware('2fa', 3, 60)];
} catch (Throwable $e) {
    error_log(
        '[RateLimit] No se pudo construir el limitador: ' . $e->getMessage()
        . '. Las rutas sensibles fallarán en cerrado (503) hasta que se corrija.'
    );

    // Las cinco, sin excepción. Antes `$rateLimitRegister` ni se reiniciaba, así
    // que quedaba sin definir y su uso habría lanzado un error más confuso que
    // el original.
    $sinProteccion  = [App\Middleware\RateLimitUnavailableMiddleware::class];
    $rateLimitLogin    = $sinProteccion;
    $rateLimitRegister = $sinProteccion;
    $rateLimitForgot   = $sinProteccion;
    $rateLimitReset    = $sinProteccion;
    $rateLimit2FA      = $sinProteccion;
}

/**
 * Función para registrar rutas en el router
 * 
 * @param Router $router
 * @param Request $request
 * @return void
 */
return function(Router $router, Request $request) use (
    $auth, $apiMW,
    $rateLimitLogin, $rateLimitRegister, $rateLimitForgot, $rateLimitReset, $rateLimit2FA
) {

// ── Permisos por acción ─────────────────────────────────────────────
// El router consulta este mapa después de resolver la ruta y antes de ejecutar
// el controlador. Es lo que conecta `PermissionMiddleware`, que hasta ahora
// existía sin estar aplicado en ninguna ruta. Las acciones sin declarar siguen
// funcionando con solo sesión, pero quedan registradas en el log con el
// prefijo `[Permisos]` para que el hueco se pueda medir.
$router->setPermissionMap(require CONFIG_PATH . '/permissions.php');

// ═══════════════════════════════════════════════════════════════════
// 1. RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════════════

// ── 1.1. SEO Público ─────────────────────────────────────────────
$router->get('/', [App\Controllers\PublicController::class, 'home']);
$router->get('/sitemap.xml', [App\Controllers\PublicController::class, 'sitemap']);
$router->get('/robots.txt', [App\Controllers\SeoController::class, 'robots']);

// ── 1.1.b. Identidad visual de la web pública ────────────────────
// Recursos anónimos que pide el navegador de cualquier visitante: la hoja de
// estilos generada desde la configuración y el manifiesto de iconos. Sólo
// lectura; la escritura vive bajo /admin/settings/brand*.
$router->get('/public-theme.css', [App\Controllers\PublicThemeController::class, 'css']);
$router->get('/site.webmanifest', [App\Controllers\PublicThemeController::class, 'manifest']);

// ── 1.2. Alias heredados de página ───────────────────────────────
// Antes estas dos rutas hacían `require` de `page.php` en la raíz del proyecto,
// un archivo que nunca existió (el real está en `public/`): respondían 500.
// Ahora son alias de las rutas públicas que ya funcionan, así que `/page/x` se
// comporta igual que `/x` sin duplicar la lógica de renderizado.
$router->get('/page', [App\Controllers\PublicController::class, 'home']);
$router->get('/page/{slug}', [App\Controllers\PublicController::class, 'show']);

// ── 1.3. Autenticación ───────────────────────────────────────────
$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login'], $rateLimitLogin);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);
$router->get('/register', [App\Controllers\PasswordController::class, 'registerForm']);
$router->post('/register', [App\Controllers\PasswordController::class, 'registerSend'], $rateLimitRegister);
// ── 1.4. Recuperación de contraseña ─────────────────────────────
$router->get('/forgot', [App\Controllers\PasswordController::class, 'forgotForm']);
$router->post('/forgot', [App\Controllers\PasswordController::class, 'forgotSend'], $rateLimitForgot);
$router->get('/reset', [App\Controllers\PasswordController::class, 'resetForm']);
$router->post('/reset', [App\Controllers\PasswordController::class, 'resetSave'], $rateLimitReset);

// ── 1.5. 2FA Challenge ───────────────────────────────────────────
$router->get('/2fa/challenge', [App\Controllers\TwoFactorController::class, 'challenge']);
$router->post('/2fa/challenge/verify', [App\Controllers\AuthController::class, 'verify2FA'], $rateLimit2FA);

// ── 1.6. Internacionalización ────────────────────────────────────
$router->get('/lang/{code}', [App\Controllers\LanguageController::class, 'switch']);

// ── 1.7. Cron endpoints (protegidos por token) ──────────────────
$router->get('/cron/backup', [App\Controllers\BackupsController::class, 'cron']);
$router->get('/cron/process-expired-bans', [App\Controllers\BannedUsersController::class, 'processExpired']);

// Cron - Cleanup rate limits
$router->get('/cron/cleanup-rate-limits', function() use ($request) {
    // Autenticación centralizada del cron (S-1 y S-2 de la deuda de seguridad).
    \App\Core\CronToken::require($request);
    $cleaned = \App\Services\RateLimiter::cleanup();
    Response::json(['success' => true, 'cleaned' => $cleaned]);
});

// Cron - Security digest
$router->get('/cron/security-digest', function() use ($request) {
    // Autenticación centralizada del cron (S-1 y S-2 de la deuda de seguridad).
    \App\Core\CronToken::require($request);
    $period = $request->get('period', 'daily');
    $success = \App\Services\EmailNotificationService::sendSecurityDigest($period);
    Response::json(['success' => $success, 'period' => $period]);
});

// Cron - Cleanup trusted devices
$router->get('/cron/cleanup-trusted-devices', function() use ($request) {
    // Autenticación centralizada del cron (S-1 y S-2 de la deuda de seguridad).
    \App\Core\CronToken::require($request);
    $cleaned = \App\Services\TrustedDeviceService::cleanupExpired();
    Response::json(['success' => true, 'cleaned' => $cleaned]);
});

// Cron - Cleanup collaboration
$router->get('/cron/cleanup-collaboration', function() use ($request) {
    // Autenticación centralizada del cron (S-1 y S-2 de la deuda de seguridad).
    \App\Core\CronToken::require($request);
    $cleaned = Database::execute(
        "UPDATE collaboration_sessions SET is_active = 0 WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 30 SECOND)"
    );
    $locksCleaned = Database::execute("DELETE FROM collaboration_locks WHERE expires_at < NOW()");
    $eventsCleaned = Database::execute(
        "DELETE FROM collaboration_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    Response::json([
        'success' => true,
        'cleaned' => ['sessions' => $cleaned, 'locks' => $locksCleaned, 'events' => $eventsCleaned]
    ]);
});

// ═══════════════════════════════════════════════════════════════════
// 2. API REST PÚBLICA (autenticación por token Bearer)
// ═══════════════════════════════════════════════════════════════════

$router->get('/api/v1/pages', [App\Controllers\ApiController::class, 'pagesIndex'], $apiMW);
$router->get('/api/v1/pages/{slug}', [App\Controllers\ApiController::class, 'pagesShow'], $apiMW);
$router->get('/api/v1/menus/{slug}', [App\Controllers\ApiController::class, 'menusShow'], $apiMW);
$router->get('/api/v1/me', [App\Controllers\ApiController::class, 'me'], $apiMW);

// API - Menu (público, sin autenticación)
$router->get('/api/menu', [App\Controllers\MenuController::class, 'getMenuJson']);
$router->get('/api/menu/breadcrumb/{id}', [App\Controllers\MenuController::class, 'getBreadcrumb']);

// ═══════════════════════════════════════════════════════════════════
// 3. PANEL ADMINISTRATIVO (requiere autenticación)
// ═══════════════════════════════════════════════════════════════════

// ── 3.1. Dashboard Principal ─────────────────────────────────────
$router->get('/admin', [App\Controllers\DashboardController::class, 'index'], $auth);

// ── 3.2. Perfil de Usuario ───────────────────────────────────────
$router->get('/admin/profile', [App\Controllers\ProfileController::class, 'index'], $auth);
$router->post('/admin/profile', [App\Controllers\ProfileController::class, 'update'], $auth);
$router->post('/admin/profile/password', [App\Controllers\ProfileController::class, 'changePassword'], $auth);
$router->post('/admin/profile/sessions/close', [App\Controllers\AuthController::class, 'closeOtherSessions'], $auth);

// ── 3.3. Gestión de Usuarios ─────────────────────────────────────
$router->get('/admin/users', [App\Controllers\UsersController::class, 'index'], $auth);
$router->get('/admin/users/create', [App\Controllers\UsersController::class, 'create'], $auth);
$router->post('/admin/users', [App\Controllers\UsersController::class, 'store'], $auth);
$router->get('/admin/users/search', [App\Controllers\UsersController::class, 'search'], $auth);
$router->get('/admin/users/{id}/edit', [App\Controllers\UsersController::class, 'edit'], $auth);
$router->post('/admin/users/{id}', [App\Controllers\UsersController::class, 'update'], $auth);
$router->post('/admin/users/{id}/delete', [App\Controllers\UsersController::class, 'delete'], $auth);
$router->post('/admin/users/{id}/password', [App\Controllers\UsersController::class, 'changePassword'], $auth);

// ── 3.4. Usuarios Baneados ───────────────────────────────────────
$router->get('/admin/users/banned', [App\Controllers\BannedUsersController::class, 'index'], $auth);
$router->get('/admin/users/banned/history', [App\Controllers\BannedUsersController::class, 'globalHistory'], $auth);
$router->get('/admin/users/banned/search', [App\Controllers\BannedUsersController::class, 'searchUsers'], $auth);
$router->post('/admin/users/banned/ban', [App\Controllers\BannedUsersController::class, 'ban'], $auth);
$router->post('/admin/users/banned/bulk-ban', [App\Controllers\BannedUsersController::class, 'bulkBan'], $auth);
$router->post('/admin/users/banned/bulk-unban', [App\Controllers\BannedUsersController::class, 'bulkUnban'], $auth);
$router->post('/admin/users/banned/{id}/unban', [App\Controllers\BannedUsersController::class, 'unban'], $auth);
$router->post('/admin/users/banned/{id}/extend', [App\Controllers\BannedUsersController::class, 'extend'], $auth);
$router->get('/admin/users/banned/{id}/history', [App\Controllers\BannedUsersController::class, 'history'], $auth);

// ── 3.5. Roles y Permisos ────────────────────────────────────────
$router->get('/admin/roles', [App\Controllers\RolesController::class, 'index'], $auth);
$router->get('/admin/roles/create', [App\Controllers\RolesController::class, 'create'], $auth);
$router->post('/admin/roles', [App\Controllers\RolesController::class, 'store'], $auth);
$router->get('/admin/roles/{id}/edit', [App\Controllers\RolesController::class, 'edit'], $auth);
$router->post('/admin/roles/{id}', [App\Controllers\RolesController::class, 'update'], $auth);
$router->post('/admin/roles/{id}/delete', [App\Controllers\RolesController::class, 'delete'], $auth);

// ── 3.6. Páginas ─────────────────────────────────────────────────
$router->get('/admin/pages', [App\Controllers\PagesController::class, 'index'], $auth);
$router->get('/admin/pages/create', [App\Controllers\PagesController::class, 'create'], $auth);
$router->post('/admin/pages', [App\Controllers\PagesController::class, 'store'], $auth);
$router->get('/admin/pages/assets', [App\Controllers\PagesController::class, 'getAssets'], $auth);
$router->get('/admin/pages/{id}/builder', [App\Controllers\PagesController::class, 'builder'], $auth);
$router->post('/admin/pages/{id}/builder/upload', [App\Controllers\PagesController::class, 'uploadAsset'], $auth);
$router->get('/admin/pages/{id}/edit', [App\Controllers\PagesController::class, 'edit'], $auth);
$router->post('/admin/pages/{id}', [App\Controllers\PagesController::class, 'update'], $auth);
$router->post('/admin/pages/{id}/delete', [App\Controllers\PagesController::class, 'delete'], $auth);
$router->get('/admin/pages/{id}/autosave', [App\Controllers\PagesController::class, 'getAutosave'], $auth);
$router->post('/admin/pages/{id}/autosave', [App\Controllers\PagesController::class, 'autosave'], $auth);
$router->post('/admin/pages/{id}/autosave/clear', [App\Controllers\PagesController::class, 'clearAutosave'], $auth);

// ── 3.7. Comentarios en Páginas ──────────────────────────────────
$router->get('/admin/pages/{id}/comments', [App\Controllers\CommentsController::class, 'index'], $auth);
$router->post('/admin/pages/{id}/comments', [App\Controllers\CommentsController::class, 'create'], $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/reply', [App\Controllers\CommentsController::class, 'reply'], $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/resolve', [App\Controllers\CommentsController::class, 'resolve'], $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/reopen', [App\Controllers\CommentsController::class, 'reopen'], $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/delete', [App\Controllers\CommentsController::class, 'delete'], $auth);
$router->post('/admin/pages/{id}/comments/{comment_id}/move', [App\Controllers\CommentsController::class, 'move'], $auth);
$router->get('/admin/profile/mentions', [App\Controllers\CommentsController::class, 'mentions'], $auth);

// ── 3.8. Versiones de Páginas ────────────────────────────────────
$router->get('/admin/pages/{id}/versions', [App\Controllers\VersionsController::class, 'index'], $auth);
$router->get('/admin/pages/{id}/versions/{vid}/preview', [App\Controllers\VersionsController::class, 'preview'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/restore', [App\Controllers\VersionsController::class, 'restore'], $auth);
$router->post('/admin/pages/{id}/versions/{vid}/delete', [App\Controllers\VersionsController::class, 'delete'], $auth);

// ── 3.9. Templates ───────────────────────────────────────────────
// ✅ Rutas literales primero
$router->get('/admin/templates', [App\Controllers\TemplatesController::class, 'index'], $auth);
$router->get('/admin/templates/create', [App\Controllers\TemplatesController::class, 'create'], $auth);
$router->post('/admin/templates', [App\Controllers\TemplatesController::class, 'store'], $auth);

// ✅ Rutas parametrizadas al final
$router->get('/admin/templates/{id}/edit', [App\Controllers\TemplatesController::class, 'edit'], $auth);
$router->post('/admin/templates/{id}/builder/upload', [App\Controllers\TemplatesController::class, 'uploadAsset'], $auth);
$router->get('/admin/templates/{id}/versions', [App\Controllers\TemplatesController::class, 'versions'], $auth); // ✅ NUEVA
$router->post('/admin/templates/{id}', [App\Controllers\TemplatesController::class, 'update'], $auth);
$router->post('/admin/templates/{id}/versions', [App\Controllers\TemplatesController::class, 'createVersionAjax'], $auth); // ✅ NUEVA
$router->post('/admin/templates/{id}/versions/{vid}/restore', [App\Controllers\TemplatesController::class, 'restoreVersion'], $auth); // ✅ NUEVA
$router->post('/admin/templates/{id}/versions/{vid}/delete', [App\Controllers\TemplatesController::class, 'deleteVersion'], $auth); // ✅ NUEVA
$router->post('/admin/templates/{id}/delete', [App\Controllers\TemplatesController::class, 'delete'], $auth);
$router->post('/admin/templates/{id}/duplicate', [App\Controllers\TemplatesController::class, 'duplicate'], $auth);

// ═══════════════════════════════════════════════════════════════════
// ── 3.10. Menús ───────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════

// ✅ RUTAS LITERALES (ANTES de las parametrizadas)
$router->get('/admin/menus', [App\Controllers\MenuController::class, 'index'], $auth);
$router->get('/admin/menus/create', [App\Controllers\MenuController::class, 'create'], $auth);
$router->post('/admin/menus', [App\Controllers\MenuController::class, 'store'], $auth);
$router->post('/admin/menus/sync', [App\Controllers\MenuController::class, 'sync'], $auth);
$router->post('/admin/menus/repair', [App\Controllers\MenuController::class, 'repair'], $auth);
$router->post('/admin/menus/regenerate', [App\Controllers\MenuController::class, 'regenerateCache'], $auth);

// ✅ Rutas API/JSON (literales, antes de {id})
$router->get('/admin/menus/json', [App\Controllers\MenuController::class, 'getMenuJson'], $auth);
$router->get('/admin/menus/stats', [App\Controllers\MenuController::class, 'stats'], $auth);

// ✅ RUTAS PARAMETRIZADAS (AL FINAL)
$router->get('/admin/menus/{id}/edit', [App\Controllers\MenuController::class, 'edit'], $auth);
$router->get('/admin/menus/{id}/breadcrumb', [App\Controllers\MenuController::class, 'getBreadcrumb'], $auth);
$router->post('/admin/menus/{id}', [App\Controllers\MenuController::class, 'update'], $auth);
$router->post('/admin/menus/{id}/delete', [App\Controllers\MenuController::class, 'delete'], $auth);
$router->post('/admin/menus/{id}/duplicate', [App\Controllers\MenuController::class, 'duplicate'], $auth);
$router->post('/admin/menus/{id}/toggle', [App\Controllers\MenuController::class, 'toggleStatus'], $auth);
$router->post('/admin/menus/{id}/default', [App\Controllers\MenuController::class, 'setDefault'], $auth);

// Ítems de un menú
$router->post('/admin/menus/{id}/items', [App\Controllers\MenuController::class, 'addItem'], $auth);
$router->post('/admin/menus/{id}/items/reorder', [App\Controllers\MenuController::class, 'reorderItems'], $auth);
$router->post('/admin/menus/{id}/items/{item}/delete', [App\Controllers\MenuController::class, 'deleteItem'], $auth);
$router->post('/admin/menus/{id}/items/{item}/move', [App\Controllers\MenuController::class, 'moveItem'], $auth);

// ── 3.11. SEO ────────────────────────────────────────────────────
$router->get('/admin/seo', [App\Controllers\SeoController::class, 'index'], $auth);
$router->get('/admin/seo/global', [App\Controllers\SeoController::class, 'global'], $auth);
$router->post('/admin/seo/global/save', [App\Controllers\SeoController::class, 'globalSave'], $auth);
$router->get('/admin/seo/redirects', [App\Controllers\SeoController::class, 'redirects'], $auth);
$router->post('/admin/seo/redirects/store', [App\Controllers\SeoController::class, 'redirectStore'], $auth);
$router->post('/admin/seo/redirects/{id}/delete', [App\Controllers\SeoController::class, 'redirectDelete'], $auth);
$router->post('/admin/seo/sitemap/regenerate', [App\Controllers\SeoController::class, 'regenerateSitemap'], $auth);

// Sitemap: pantalla propia. El sitemap dejó de ser «todas las publicadas» y pasa
// a depender del menú en uso, así que necesita mostrar qué entra y por qué.
// Ojo con el orden: van antes de las rutas con `{id}`.
$router->get('/admin/seo/sitemap', [App\Controllers\SeoController::class, 'sitemap'], $auth);
$router->post('/admin/seo/sitemap/settings', [App\Controllers\SeoController::class, 'saveSitemapSettings'], $auth);
$router->get('/admin/seo/{id}/analyze', [App\Controllers\SeoController::class, 'analyze'], $auth);
$router->get('/admin/seo/{id}/competitor', [App\Controllers\SeoController::class, 'competitor'], $auth);
$router->get('/admin/seo/{id}/meta-generator', [App\Controllers\SeoController::class, 'metaGenerator'], $auth);
$router->post('/admin/seo/{id}/generate-meta', [App\Controllers\SeoController::class, 'generateMeta'], $auth);
$router->post('/admin/seo/{id}/apply-meta', [App\Controllers\SeoController::class, 'applyMeta'], $auth);

// ═══════════════════════════════════════════════════════════════════
// ── 3.12. Archivos ────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════

// ✅ RUTAS LITERALES (ANTES de las parametrizadas)
$router->get('/admin/files', [App\Controllers\FilesController::class, 'index'], $auth);
$router->post('/admin/files/upload', [App\Controllers\FilesController::class, 'upload'], $auth);
$router->post('/admin/files/wysiwyg-upload', [App\Controllers\FilesController::class, 'wysiwygUpload'], $auth);
// Registra en la tabla `files` lo que ya está en uploads/ (subido desde el editor
// visual o copiado a mano). Sin esto el gestor no los veía.
$router->post('/admin/files/sync', [App\Controllers\FilesController::class, 'sync'], $auth);

// ✅ RUTAS PARAMETRIZADAS (AL FINAL)
$router->get('/admin/files/{id}/download', [App\Controllers\FilesController::class, 'download'], $auth);
$router->get('/admin/files/{id}/share', [App\Controllers\FilesController::class, 'shareView'], $auth);
$router->post('/admin/files/{id}/share', [App\Controllers\FilesController::class, 'share'], $auth);
$router->post('/admin/files/{id}/delete', [App\Controllers\FilesController::class, 'delete'], $auth);

// ✅ Búsqueda avanzada con metadata
$router->get('/admin/files/search/advanced', [App\Controllers\FilesController::class, 'advancedSearch'], $auth);
$router->post('/admin/files/search/advanced', [App\Controllers\FilesController::class, 'advancedSearch'], $auth);

// ✅ Actualizar metadata
$router->post('/admin/files/{id}/metadata', [App\Controllers\FilesController::class, 'updateMetadata'], $auth);

// ✅ Exportar archivos con metadata
$router->get('/admin/files/export', [App\Controllers\FilesController::class, 'export'], $auth);

// ── 3.13. 2FA ────────────────────────────────────────────────────
$router->get('/admin/2fa', [App\Controllers\TwoFactorController::class, 'setup'], $auth);
$router->post('/admin/2fa/verify', [App\Controllers\TwoFactorController::class, 'verify'], $auth);
$router->post('/admin/2fa/disable', [App\Controllers\TwoFactorController::class, 'disable'], $auth);
$router->get('/admin/2fa/recovery-success', [App\Controllers\TwoFactorController::class, 'recoverySuccess'], $auth);
$router->post('/admin/2fa/regenerate-codes', [App\Controllers\TwoFactorController::class, 'regenerateCodes'], $auth);
$router->get('/admin/2fa/download-codes', [App\Controllers\TwoFactorController::class, 'downloadCodes'], $auth);

// ── 3.14. Notificaciones ─────────────────────────────────────────
$router->get('/admin/notifications', [App\Controllers\NotificationsController::class, 'index'], $auth);
$router->get('/admin/notifications/list', [App\Controllers\NotificationsController::class, 'list'], $auth);
$router->post('/admin/notifications/mark-all', [App\Controllers\NotificationsController::class, 'markAllRead'], $auth);
$router->post('/admin/notifications/clear', [App\Controllers\NotificationsController::class, 'clearAll'], $auth);
$router->post('/admin/notifications/{id}/read', [App\Controllers\NotificationsController::class, 'markRead'], $auth);
$router->post('/admin/notifications/{id}/delete', [App\Controllers\NotificationsController::class, 'delete'], $auth);

// ── 3.15. Cache ──────────────────────────────────────────────────
// ✅ Rutas literales primero
$router->get('/admin/cache', [App\Controllers\CacheController::class, 'index'], $auth);
$router->post('/admin/cache/clear', [App\Controllers\CacheController::class, 'clear'], $auth);
$router->post('/admin/cache/regenerate', [App\Controllers\CacheController::class, 'regenerate'], $auth);

// ── 3.16. Configuración del Sistema ──────────────────────────────
$router->get('/admin/settings', [App\Controllers\SettingsController::class, 'index'], $auth);
$router->post('/admin/settings/update', [App\Controllers\SettingsController::class, 'update'], $auth);

// ── 3.16.b. Personalización visual de la web pública ─────────────
// Todas comprueban además `settings.manage` dentro del controlador: el
// middleware $auth sólo garantiza que hay sesión, no qué puede hacer.
$router->post('/admin/settings/brand', [App\Controllers\SettingsController::class, 'updatePublic'], $auth);
$router->post('/admin/settings/brand/asset', [App\Controllers\SettingsController::class, 'uploadAsset'], $auth);
$router->post('/admin/settings/brand/asset/delete', [App\Controllers\SettingsController::class, 'deleteAsset'], $auth);
$router->post('/admin/settings/brand/reset', [App\Controllers\SettingsController::class, 'resetPublic'], $auth);

// Vista previa de la portada comercial. Existe para que el dueño pueda mirarla
// sin activarla: `landing.enabled` sigue en 0 hasta que él decida (ADR-006).
$router->get('/admin/settings/landing/preview', [App\Controllers\SettingsController::class, 'previewLanding'], $auth);

// Editor del contenido de la portada. Pantalla propia: son listas repetibles
// con alta y baja de filas, no un grupo más del formulario de configuración.
$router->get('/admin/settings/landing', [App\Controllers\SettingsController::class, 'landing'], $auth);
$router->post('/admin/settings/landing', [App\Controllers\SettingsController::class, 'saveLanding'], $auth);

// Editor del pie de página. Misma pantalla propia que la portada: el pie son
// columnas de enlaces con alta y baja de filas.
$router->get('/admin/settings/footer', [App\Controllers\SettingsController::class, 'footer'], $auth);
$router->post('/admin/settings/footer', [App\Controllers\SettingsController::class, 'saveFooter'], $auth);

// Catálogo de diseños del pie (`footer_designs`).
//
// ORDEN IMPORTANTE: las rutas con literal (`deactivate`) van ANTES que las
// parametrizadas (`{id}/...`), porque el router devuelve la PRIMERA coincidencia:
// si `{id}` se registrara antes, `/designs/deactivate` entraría por ahí y
// `deactivate` se leería como un id.
$router->get('/admin/settings/footer/designs', [App\Controllers\SettingsController::class, 'footerDesigns'], $auth);
$router->post('/admin/settings/footer/designs', [App\Controllers\SettingsController::class, 'createFooterDesign'], $auth);
$router->post('/admin/settings/footer/designs/deactivate', [App\Controllers\SettingsController::class, 'deactivateFooterDesign'], $auth);
$router->post('/admin/settings/footer/designs/{id}/activate', [App\Controllers\SettingsController::class, 'activateFooterDesign'], $auth);
$router->post('/admin/settings/footer/designs/{id}/duplicate', [App\Controllers\SettingsController::class, 'duplicateFooterDesign'], $auth);
$router->post('/admin/settings/footer/designs/{id}/delete', [App\Controllers\SettingsController::class, 'deleteFooterDesign'], $auth);

// El pie con el constructor visual: el mismo GrapesJS que las páginas, un lienzo
// por diseño del catálogo.
$router->get('/admin/settings/footer/designs/{id}/builder', [App\Controllers\SettingsController::class, 'footerBuilder'], $auth);
$router->post('/admin/settings/footer/designs/{id}/builder', [App\Controllers\SettingsController::class, 'saveFooterBuilder'], $auth);

// Compatibilidad: la ruta anterior no tenía id. Lleva al catálogo, que es donde
// ahora se elige qué diseño editar.
$router->get('/admin/settings/footer/builder', [App\Controllers\SettingsController::class, 'footerDesigns'], $auth);

// Subida de imágenes del pie. El LISTADO de lo ya subido no se duplica aquí:
// se reutiliza /admin/pages/assets, que ya existe y devuelve lo mismo.
$router->post('/admin/settings/footer/builder/upload', [App\Controllers\SettingsController::class, 'footerUpload'], $auth);

// ── 3.17. Backups ────────────────────────────────────────────────
$router->get('/admin/backups', [App\Controllers\BackupsController::class, 'index'], $auth);
$router->post('/admin/backups/create', [App\Controllers\BackupsController::class, 'create'], $auth);
$router->post('/admin/backups/schedule', [App\Controllers\BackupsController::class, 'schedule'], $auth);
$router->get('/admin/backups/{id}/download', [App\Controllers\BackupsController::class, 'download'], $auth);
$router->post('/admin/backups/{id}/restore', [App\Controllers\BackupsController::class, 'restore'], $auth);
$router->post('/admin/backups/{id}/delete', [App\Controllers\BackupsController::class, 'delete'], $auth);

// ── 3.18. API Management ─────────────────────────────────────────
$router->get('/admin/api', [App\Controllers\ApiManageController::class, 'index'], $auth);
$router->get('/admin/api/create', [App\Controllers\ApiManageController::class, 'create'], $auth);
$router->post('/admin/api', [App\Controllers\ApiManageController::class, 'store'], $auth);
$router->get('/admin/api/{id}', [App\Controllers\ApiManageController::class, 'show'], $auth);
$router->post('/admin/api/{id}', [App\Controllers\ApiManageController::class, 'update'], $auth);
$router->post('/admin/api/{id}/delete', [App\Controllers\ApiManageController::class, 'delete'], $auth);
$router->get('/admin/api/{id}/regenerate', [App\Controllers\ApiManageController::class, 'regenerate'], $auth);
$router->post('/admin/api/{id}/tokens', [App\Controllers\ApiManageController::class, 'createToken'], $auth);
$router->post('/admin/api/{id}/tokens/{tid}/revoke', [App\Controllers\ApiManageController::class, 'revokeToken'], $auth);

// ── 3.19. Componentes ────────────────────────────────────────────
$router->get('/admin/components', [App\Controllers\ComponentsController::class, 'index'], $auth);
$router->get('/admin/components/api', [App\Controllers\ComponentsController::class, 'apiList'], $auth);
$router->get('/admin/components/create', [App\Controllers\ComponentsController::class, 'create'], $auth);
$router->post('/admin/components', [App\Controllers\ComponentsController::class, 'store'], $auth);
$router->get('/admin/components/{id}/edit', [App\Controllers\ComponentsController::class, 'edit'], $auth);
$router->post('/admin/components/{id}', [App\Controllers\ComponentsController::class, 'update'], $auth);
$router->post('/admin/components/{id}/delete', [App\Controllers\ComponentsController::class, 'delete'], $auth);

// ── 3.20. Traducciones ───────────────────────────────────────────
$router->get('/admin/translations', [App\Controllers\TranslationsController::class, 'index'], $auth);
$router->post('/admin/translations/save', [App\Controllers\TranslationsController::class, 'save'], $auth);
$router->post('/admin/translations/add', [App\Controllers\TranslationsController::class, 'add'], $auth);

// ── 3.21. Analytics ──────────────────────────────────────────────
$router->get('/admin/analytics', [App\Controllers\AnalyticsController::class, 'index'], $auth);
$router->get('/admin/analytics/export', [App\Controllers\AnalyticsController::class, 'export'], $auth);
$router->get('/admin/analytics/api', [App\Controllers\AnalyticsController::class, 'apiSummary'], $auth);
// Proveedores de medición externa. `Router::toPattern()` ancla cada ruta con
// `^…$`, así que `/admin/analytics` no se traga esta: el orden da igual.
$router->get('/admin/analytics/proveedores', [App\Controllers\AnalyticsController::class, 'providers'], $auth);
$router->post('/admin/analytics/proveedores', [App\Controllers\AnalyticsController::class, 'saveProviders'], $auth);

// ── 3.22. Auditoría ──────────────────────────────────────────────
// ORDEN IMPORTANTE. `Router::findRoute()` recorre las rutas en el orden en que
// se registran y devuelve **la primera** que coincide. Por eso toda ruta literal
// va antes que cualquier ruta con `{parámetro}`: `/admin/audit/{id}` habría
// capturado `/admin/audit/activity` con id = "activity" (casteado a 0), y la
// pantalla de actividad quedaba inalcanzable.
//
// Se comprueba en `tests/rutas/check.php`: si alguien registra una literal
// después de una parametrizada que la tapa, la prueba falla.

// Todas las literales de auditoría y de actividad, primero.
$router->get('/admin/audit', [App\Controllers\AuditController::class, 'index'], $auth);
$router->get('/admin/audit/export', [App\Controllers\AuditController::class, 'export'], $auth);
$router->get('/admin/audit/export-json', [App\Controllers\AuditController::class, 'exportJson'], $auth);
$router->get('/admin/audit/stats', [App\Controllers\AuditController::class, 'stats'], $auth);
$router->post('/admin/audit/clean', [App\Controllers\AuditController::class, 'clean'], $auth);

$router->get('/admin/audit/activity', [App\Controllers\ActivityLogsController::class, 'index'], $auth);
$router->get('/admin/audit/activity/export', [App\Controllers\ActivityLogsController::class, 'export'], $auth);
$router->get('/admin/audit/activity/export-json', [App\Controllers\ActivityLogsController::class, 'exportJson'], $auth);
$router->get('/admin/audit/activity/stats', [App\Controllers\ActivityLogsController::class, 'stats'], $auth);
$router->post('/admin/audit/activity/clean', [App\Controllers\ActivityLogsController::class, 'clean'], $auth);

// Después, las parametrizadas.
$router->get('/admin/audit/activity/user/{id}', [App\Controllers\ActivityLogsController::class, 'userLogs'], $auth);
$router->get('/admin/audit/activity/ip/{ip}', [App\Controllers\ActivityLogsController::class, 'ipLogs'], $auth);
$router->get('/admin/audit/activity/{id}', [App\Controllers\ActivityLogsController::class, 'show'], $auth);

$router->get('/admin/audit/user/{id}', [App\Controllers\AuditController::class, 'userLogs'], $auth);
$router->get('/admin/audit/ip/{ip}', [App\Controllers\AuditController::class, 'ipLogs'], $auth);
$router->get('/admin/audit/{id}', [App\Controllers\AuditController::class, 'show'], $auth);

// ── 3.24. Seguridad - Dashboard ──────────────────────────────────
$router->get('/admin/security/dashboard', [App\Controllers\SecurityDashboardController::class, 'index'], $auth);
$router->get('/admin/security/dashboard/realtime', [App\Controllers\SecurityDashboardController::class, 'realtime'], $auth);

// ── 3.25. Seguridad - Login Attempts ─────────────────────────────
$router->get('/admin/security/login-attempts', [App\Controllers\LoginAttemptsController::class, 'index'], $auth);
$router->post('/admin/security/login-attempts/block', [App\Controllers\LoginAttemptsController::class, 'blockIp'], $auth);
$router->post('/admin/security/login-attempts/clean', [App\Controllers\LoginAttemptsController::class, 'cleanAttempts'], $auth);
$router->post('/admin/security/login-attempts/{id}/unblock', [App\Controllers\LoginAttemptsController::class, 'unblockIp'], $auth);

// ── 3.26. Seguridad - Dispositivos de Confianza ──────────────────
$router->get('/admin/security/trusted-devices', [App\Controllers\TrustedDevicesController::class, 'index'], $auth);
$router->post('/admin/security/trusted-devices/revoke-all', [App\Controllers\TrustedDevicesController::class, 'revokeAll'], $auth);
$router->post('/admin/security/trusted-devices/{id}/revoke', [App\Controllers\TrustedDevicesController::class, 'revoke'], $auth);

// ── 3.27. Colaboración en Tiempo Real ────────────────────────────
$router->get('/admin/collaboration/{id}/join', [App\Controllers\CollaborationController::class, 'join'], $auth);
$router->post('/admin/collaboration/{id}/leave', [App\Controllers\CollaborationController::class, 'leave'], $auth);
$router->post('/admin/collaboration/{id}/heartbeat', [App\Controllers\CollaborationController::class, 'heartbeat'], $auth);
$router->get('/admin/collaboration/{id}/poll', [App\Controllers\CollaborationController::class, 'poll'], $auth);
$router->post('/admin/collaboration/{id}/publish-change', [App\Controllers\CollaborationController::class, 'publishChange'], $auth);
$router->post('/admin/collaboration/{id}/publish-cursor', [App\Controllers\CollaborationController::class, 'publishCursor'], $auth);
$router->post('/admin/collaboration/{id}/acquire-lock', [App\Controllers\CollaborationController::class, 'acquireLock'], $auth);
$router->post('/admin/collaboration/{id}/release-lock', [App\Controllers\CollaborationController::class, 'releaseLock'], $auth);
$router->post('/admin/collaboration/{id}/send-message', [App\Controllers\CollaborationController::class, 'sendMessage'], $auth);
$router->get('/admin/collaboration/{id}/messages', [App\Controllers\CollaborationController::class, 'getMessages'], $auth);
$router->get('/admin/collaboration/{id}/stats', [App\Controllers\CollaborationController::class, 'stats'], $auth);

// ═══════════════════════════════════════════════════════════════════
// 4. CATCH-ALL (SIEMPRE AL FINAL)
// ═══════════════════════════════════════════════════════════════════
$router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);

}; // Fin de la función de rutas