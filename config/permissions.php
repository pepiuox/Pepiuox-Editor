<?php
/**
 * Mapa de permisos por acción del panel.
 *
 * ── Qué resuelve ────────────────────────────────────────────────────
 * `App\Middleware\PermissionMiddleware` existía desde el principio y no estaba
 * aplicado en **ninguna** ruta: cualquier sesión abierta alcanzaba todo el
 * panel. Anotar a mano las ~150 rutas de `config/routes.php` habría quedado
 * desactualizado en el primer cambio, y una ruta nueva nacería sin protección
 * sin que nadie lo notara.
 *
 * Aquí hay **una sola lista**, y `App\Core\Router` la consulta después de
 * resolver la ruta: sabe qué controlador y qué método van a ejecutarse, así que
 * la clave es `Controlador::método`.
 *
 * ── Reglas ─────────────────────────────────────────────────────────
 *  1. Si la acción está en este mapa, se exige el permiso. Sin permiso → 403.
 *  2. Si **no** está, no se exige nada más allá de la sesión (es el
 *     comportamiento anterior). Esas rutas quedan registradas en el log con el
 *     prefijo `[Permisos]` para que el hueco sea visible en vez de silencioso.
 *  3. `tests/seguridad/check.php` cuenta cuántas rutas del panel están mapeadas
 *     y cuántas no. El objetivo es que la segunda cifra baje a cero.
 *
 * ── Por qué algunas acciones se dejan sin mapear a propósito ────────
 * El panel propio (`ProfileController`), las notificaciones y el tablero son
 * para cualquier usuario con sesión: exigirles un permiso específico dejaría a
 * cuentas legítimas fuera de su propia pantalla. No es un olvido, es una
 * decisión; si se mapean, hay que comprobar antes que todos los roles tienen el
 * permiso asignado.
 *
 * @package PePiuoX\Editor\Config
 * @since   2026-10-31
 */

use App\Controllers\ActivityLogsController;
use App\Controllers\AnalyticsController;
use App\Controllers\ApiManageController;
use App\Controllers\AuditController;
use App\Controllers\BackupsController;
use App\Controllers\BannedUsersController;
use App\Controllers\CacheController;
use App\Controllers\CollaborationController;
use App\Controllers\CommentsController;
use App\Controllers\ComponentsController;
use App\Controllers\FilesController;
use App\Controllers\LoginAttemptsController;
use App\Controllers\MenuController;
use App\Controllers\PagesController;
use App\Controllers\RolesController;
use App\Controllers\SeoController;
use App\Controllers\SecurityDashboardController;
use App\Controllers\SettingsController;
use App\Controllers\TemplatesController;
use App\Controllers\TranslationsController;
use App\Controllers\TrustedDevicesController;
use App\Controllers\TwoFactorController;
use App\Controllers\UsersController;
use App\Controllers\VersionsController;

return [

    // ── Páginas y su contenido ───────────────────────────────────────
    PagesController::class . '::index'        => 'pages.view',
    PagesController::class . '::builder'      => 'pages.view',
    PagesController::class . '::edit'         => 'pages.view',
    PagesController::class . '::getAssets'    => 'pages.view',
    PagesController::class . '::getAutosave'  => 'pages.view',
    PagesController::class . '::create'       => 'pages.create',
    PagesController::class . '::store'        => 'pages.create',
    PagesController::class . '::update'       => 'pages.edit',
    PagesController::class . '::autosave'     => 'pages.edit',
    PagesController::class . '::clearAutosave' => 'pages.edit',
    PagesController::class . '::uploadAsset'  => 'pages.edit',
    PagesController::class . '::delete'       => 'pages.delete',

    // ── Versiones ────────────────────────────────────────────────────
    // Se comprueban también dentro del controlador; aquí quedan declaradas para
    // que el mapa sea la vista completa de quién puede qué.
    VersionsController::class . '::index'   => 'pages.view',
    VersionsController::class . '::preview' => 'pages.view',
    VersionsController::class . '::restore' => 'pages.restore',
    VersionsController::class . '::delete'  => 'pages.delete',

    // ── Plantillas ───────────────────────────────────────────────────
    TemplatesController::class . '::index'          => 'templates.view',
    TemplatesController::class . '::versions'       => 'templates.view',
    TemplatesController::class . '::create'         => 'templates.create',
    TemplatesController::class . '::store'          => 'templates.create',
    TemplatesController::class . '::edit'           => 'templates.edit',
    TemplatesController::class . '::update'         => 'templates.edit',
    TemplatesController::class . '::uploadAsset'    => 'templates.edit',
    TemplatesController::class . '::duplicate'      => 'templates.edit',
    TemplatesController::class . '::createVersionAjax' => 'templates.edit',
    TemplatesController::class . '::restoreVersion' => 'templates.edit',
    TemplatesController::class . '::delete'         => 'templates.delete',
    TemplatesController::class . '::deleteVersion'  => 'templates.delete',

    // ── Menús ────────────────────────────────────────────────────────
    MenuController::class . '::index'        => 'menus.manage',
    MenuController::class . '::create'       => 'menus.manage',
    MenuController::class . '::store'        => 'menus.manage',
    MenuController::class . '::edit'         => 'menus.manage',
    MenuController::class . '::update'       => 'menus.manage',
    MenuController::class . '::delete'       => 'menus.manage',
    MenuController::class . '::duplicate'    => 'menus.manage',
    MenuController::class . '::toggleStatus' => 'menus.manage',
    MenuController::class . '::setDefault'   => 'menus.manage',
    MenuController::class . '::addItem'      => 'menus.manage',
    MenuController::class . '::deleteItem'   => 'menus.manage',
    MenuController::class . '::moveItem'     => 'menus.manage',
    MenuController::class . '::reorderItems' => 'menus.manage',
    MenuController::class . '::sync'         => 'menus.manage',
    MenuController::class . '::repair'       => 'menus.manage',
    MenuController::class . '::regenerateCache' => 'menus.manage',
    MenuController::class . '::getBreadcrumb'   => 'menus.manage',
    MenuController::class . '::getMenuJson'     => 'menus.manage',
    MenuController::class . '::stats'           => 'menus.manage',

    // ── Usuarios ─────────────────────────────────────────────────────
    UsersController::class . '::index'          => 'users.view',
    UsersController::class . '::search'         => 'users.view',
    UsersController::class . '::edit'           => 'users.view',
    UsersController::class . '::create'         => 'users.create',
    UsersController::class . '::store'          => 'users.create',
    UsersController::class . '::update'         => 'users.edit',
    UsersController::class . '::changePassword' => 'users.edit',
    UsersController::class . '::delete'         => 'users.delete',

    // ── Baneos ───────────────────────────────────────────────────────
    BannedUsersController::class . '::index'           => 'users.view',
    BannedUsersController::class . '::globalHistory'   => 'users.view.ban_history',
    BannedUsersController::class . '::searchUsers'     => 'users.view',
    BannedUsersController::class . '::history'         => 'users.view.ban_history',
    BannedUsersController::class . '::ban'             => 'users.ban',
    BannedUsersController::class . '::bulkBan'         => 'users.ban',
    BannedUsersController::class . '::bulkUnban'       => 'users.ban',
    BannedUsersController::class . '::unban'           => 'users.ban',
    BannedUsersController::class . '::extend'          => 'users.ban',
    // `::processExpired` tampoco se mapea: es el endpoint `/cron/*`, protegido
    // por token. Su guardián está dentro del controlador (S-1).

    // ── Roles ────────────────────────────────────────────────────────
    RolesController::class . '::index'  => 'roles.manage',
    RolesController::class . '::create' => 'roles.manage',
    RolesController::class . '::store'  => 'roles.manage',
    RolesController::class . '::edit'   => 'roles.manage',
    RolesController::class . '::update' => 'roles.manage',
    RolesController::class . '::delete' => 'roles.manage',

    // ── Configuración e identidad ────────────────────────────────────
    SettingsController::class . '::index'            => 'settings.manage',
    SettingsController::class . '::update'           => 'settings.manage',
    SettingsController::class . '::updatePublic'     => 'settings.manage',
    SettingsController::class . '::uploadAsset'      => 'settings.manage',
    SettingsController::class . '::deleteAsset'      => 'settings.manage',
    SettingsController::class . '::resetPublic'      => 'settings.manage',
    SettingsController::class . '::landing'          => 'settings.manage',
    SettingsController::class . '::saveLanding'      => 'settings.manage',
    SettingsController::class . '::previewLanding'   => 'settings.manage',
    SettingsController::class . '::footer'           => 'settings.manage',
    SettingsController::class . '::saveFooter'       => 'settings.manage',
    SettingsController::class . '::footerBuilder'    => 'settings.manage',
    SettingsController::class . '::saveFooterBuilder' => 'settings.manage',
    SettingsController::class . '::footerUpload'     => 'settings.manage',

    // ── SEO y sitemap ────────────────────────────────────────────────
    SeoController::class . '::index'             => 'seo.manage',
    SeoController::class . '::global'            => 'seo.manage',
    SeoController::class . '::globalSave'        => 'seo.manage',
    SeoController::class . '::analyze'           => 'seo.manage',
    SeoController::class . '::competitor'        => 'seo.manage',
    SeoController::class . '::metaGenerator'     => 'seo.manage',
    SeoController::class . '::generateMeta'      => 'seo.manage',
    SeoController::class . '::applyMeta'         => 'seo.manage',
    SeoController::class . '::regenerateSitemap' => 'seo.manage',
    SeoController::class . '::sitemap'           => 'seo.manage',
    SeoController::class . '::saveSitemapSettings' => 'seo.manage',
    SeoController::class . '::redirects'         => 'redirects.manage',
    SeoController::class . '::redirectStore'     => 'redirects.manage',
    SeoController::class . '::redirectDelete'    => 'redirects.manage',

    // ── Archivos ─────────────────────────────────────────────────────
    FilesController::class . '::index'          => 'files.view',
    FilesController::class . '::advancedSearch' => 'files.view',
    FilesController::class . '::download'       => 'files.view',
    FilesController::class . '::upload'         => 'files.upload',
    FilesController::class . '::wysiwygUpload'  => 'files.upload',
    FilesController::class . '::sync'           => 'files.manage',
    FilesController::class . '::export'         => 'files.manage',
    FilesController::class . '::updateMetadata' => 'files.manage',
    FilesController::class . '::delete'         => 'files.delete',
    FilesController::class . '::shareView'      => 'files.share',
    FilesController::class . '::share'          => 'files.share',

    // ── Copias de seguridad ──────────────────────────────────────────
    BackupsController::class . '::index'    => 'backups.manage',
    BackupsController::class . '::create'   => 'backups.manage',
    BackupsController::class . '::schedule' => 'backups.manage',
    BackupsController::class . '::download' => 'backups.manage',
    BackupsController::class . '::delete'   => 'backups.manage',
    BackupsController::class . '::restore'  => 'backups.restore',
    // `::cron` NO se mapea a propósito. Se autentica por token
    // (`App\Core\CronToken`), no por sesión: mapearlo a un permiso lo dejaba
    // exigiendo un usuario logueado y rompía la tarea programada entera. Se
    // detectó al probarlo: respondía 403 y ningún cron habría funcionado.

    // ── API ──────────────────────────────────────────────────────────
    ApiManageController::class . '::index'         => 'api.manage',
    ApiManageController::class . '::create'        => 'api.manage',
    ApiManageController::class . '::store'         => 'api.manage',
    ApiManageController::class . '::show'          => 'api.manage',
    ApiManageController::class . '::update'        => 'api.manage',
    ApiManageController::class . '::delete'        => 'api.manage',
    ApiManageController::class . '::regenerate'    => 'api.manage',
    ApiManageController::class . '::createToken'   => 'api.manage',
    ApiManageController::class . '::revokeToken'   => 'api.manage',

    // ── Traducciones, analítica, auditoría ───────────────────────────
    TranslationsController::class . '::index' => 'translations.manage',
    TranslationsController::class . '::save'  => 'translations.manage',
    TranslationsController::class . '::add'   => 'translations.manage',

    AnalyticsController::class . '::index'      => 'analytics.view',
    AnalyticsController::class . '::export'     => 'analytics.view',
    AnalyticsController::class . '::apiSummary' => 'analytics.view',

    AuditController::class . '::index'       => 'audit.view',
    AuditController::class . '::export'      => 'audit.view',
    AuditController::class . '::exportJson'  => 'audit.view',
    AuditController::class . '::stats'       => 'audit.view',
    AuditController::class . '::show'        => 'audit.view',
    AuditController::class . '::userLogs'    => 'audit.view',
    AuditController::class . '::ipLogs'      => 'audit.view',
    AuditController::class . '::clean'       => 'audit.view',

    ActivityLogsController::class . '::index'      => 'audit.view',
    ActivityLogsController::class . '::export'     => 'audit.view',
    ActivityLogsController::class . '::exportJson' => 'audit.view',
    ActivityLogsController::class . '::stats'      => 'audit.view',
    ActivityLogsController::class . '::show'       => 'audit.view',
    ActivityLogsController::class . '::userLogs'   => 'audit.view',
    ActivityLogsController::class . '::ipLogs'     => 'audit.view',
    ActivityLogsController::class . '::clean'      => 'audit.view',

    // ── Seguridad, 2FA y dispositivos ────────────────────────────────
    // El tablero de seguridad se descubrió sin mapear al escribir la prueba:
    // estaba accesible con solo tener sesión.
    SecurityDashboardController::class . '::index'    => 'security.dashboard',
    SecurityDashboardController::class . '::realtime' => 'security.dashboard',

    LoginAttemptsController::class . '::index'        => 'security.dashboard',
    LoginAttemptsController::class . '::blockIp'      => 'security.dashboard',
    LoginAttemptsController::class . '::cleanAttempts' => 'security.dashboard',
    LoginAttemptsController::class . '::unblockIp'    => 'security.dashboard',

    TrustedDevicesController::class . '::index'      => 'trusted_devices.manage',
    TrustedDevicesController::class . '::revokeAll'  => 'trusted_devices.manage',
    TrustedDevicesController::class . '::revoke'     => 'trusted_devices.manage',

    TwoFactorController::class . '::setup'            => '2fa.manage',
    TwoFactorController::class . '::verify'           => '2fa.manage',
    TwoFactorController::class . '::disable'          => '2fa.manage',
    TwoFactorController::class . '::regenerateCodes'  => '2fa.manage',
    TwoFactorController::class . '::downloadCodes'    => '2fa.manage',

    // ── Caché, componentes, comentarios y colaboración ───────────────
    CacheController::class . '::index'      => 'cache.manage',
    CacheController::class . '::clear'      => 'cache.manage',
    CacheController::class . '::regenerate' => 'cache.manage',

    ComponentsController::class . '::index'  => 'components.manage',
    ComponentsController::class . '::apiList' => 'components.manage',
    ComponentsController::class . '::create' => 'components.manage',
    ComponentsController::class . '::store'  => 'components.manage',
    ComponentsController::class . '::edit'   => 'components.manage',
    ComponentsController::class . '::update' => 'components.manage',
    ComponentsController::class . '::delete' => 'components.manage',

    CommentsController::class . '::index'   => 'comments.view',
    CommentsController::class . '::mentions' => 'comments.view',
    CommentsController::class . '::create'  => 'comments.create',
    CommentsController::class . '::reply'   => 'comments.create',
    CommentsController::class . '::resolve' => 'comments.resolve',
    CommentsController::class . '::reopen'  => 'comments.resolve',
    CommentsController::class . '::delete'  => 'comments.delete',
    CommentsController::class . '::move'    => 'comments.delete',

    CollaborationController::class . '::join'           => 'collaboration.use',
    CollaborationController::class . '::leave'          => 'collaboration.use',
    CollaborationController::class . '::heartbeat'      => 'collaboration.use',
    CollaborationController::class . '::poll'           => 'collaboration.use',
    CollaborationController::class . '::publishChange'  => 'collaboration.use',
    CollaborationController::class . '::publishCursor'  => 'collaboration.use',
    CollaborationController::class . '::acquireLock'    => 'collaboration.use',
    CollaborationController::class . '::releaseLock'    => 'collaboration.use',
    CollaborationController::class . '::sendMessage'    => 'collaboration.use',
    CollaborationController::class . '::getMessages'    => 'collaboration.use',
    CollaborationController::class . '::stats'          => 'collaboration.use',
];
