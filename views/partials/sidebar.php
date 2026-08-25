<?php
// Fallbacks seguros para evitar errores de PHP 8
$current = $current ?? \App\Core\Auth::user() ?? [];
$username = $current['username'] ?? 'Usuario';
$fullName = $current['full_name'] ?? $username;
$role = $current['role'] ?? 'Guest';
$initial = mb_substr($fullName, 0, 1);
$cfg = $cfg ?? ['app_version' => '1.0.0'];

// Contar usuarios baneados (una sola vez)
$bannedCount = 0;
if (isset($current['id']) && can('users.ban')) {
    try {
        $bannedCount = (int)\App\Core\Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1")['c'];
    } catch (\Throwable $e) {}
}

// Contar notificaciones no leídas
$unreadNotifs = 0;
if (isset($current['id'])) {
    try {
        $unreadNotifs = \App\Services\NotificationService::unreadCount((int)$current['id']);
    } catch (\Throwable $e) {}
}
?>

<aside class="sidebar" id="sidebar">
  <!-- ═══ HEADER ═══ -->
  <div class="sidebar-header">
    <a class="sidebar-brand" href="<?= url('admin') ?>">
      <span class="brand-icon"><i class="bi bi-code-slash"></i></span>
      <span class="brand-info">
        <span class="brand-text">PePiuoX</span>
        <small>Maker v<?= e($cfg['app_version']) ?></small>
      </span>
    </a>
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Cerrar menú">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- ═══ NAVEGACIÓN ═══ -->
  <nav class="sidebar-nav">
    
    <!-- ── PRINCIPAL ── -->
    <div class="nav-label">Principal</div>
    <a class="nav-link <?= is_active('/admin', 'active') ?>" href="<?= url('admin') ?>">
      <i class="bi bi-grid-1x2"></i>
      <span>Dashboard</span>
    </a>

    <!-- ── CONTENIDO ── -->
    <div class="nav-label">Contenido</div>
    
    <?php if (!isset($current['id']) || can('pages.view')): ?>
    <a class="nav-link <?= is_active('/admin/pages', 'active') ?>" href="<?= url('admin/pages') ?>">
      <i class="bi bi-file-earmark-richtext"></i>
      <span>Páginas</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('templates.view')): ?>
    <a class="nav-link <?= is_active('/admin/templates', 'active') ?>" href="<?= url('admin/templates') ?>">
      <i class="bi bi-layout-text-window"></i>
      <span>Templates</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('menus.manage')): ?>
    <a class="nav-link <?= is_active('/admin/menus', 'active') ?>" href="<?= url('admin/menus') ?>">
      <i class="bi bi-list-nested"></i>
      <span>Menús</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('files.manage')): ?>
    <a class="nav-link <?= is_active('/admin/files', 'active') ?>" href="<?= url('admin/files') ?>">
      <i class="bi bi-folder2-open"></i>
      <span>Archivos</span>
    </a>
    <?php endif; ?>

    <!-- ── SEO (con submenú) ── -->
    <?php if (!isset($current['id']) || can('seo.manage')): ?>
    <div class="nav-item-has-submenu">
      <a class="nav-link nav-toggle <?= is_active('/admin/seo', 'active') ?>" 
         href="#" 
         data-bs-toggle="collapse" 
         data-bs-target="#submenuSeo" 
         aria-expanded="<?= is_active('/admin/seo', 'true') ?>">
        <i class="bi bi-search"></i>
        <span>SEO</span>
        <i class="bi bi-chevron-right arrow ms-auto"></i>
      </a>
      <div class="collapse submenu <?= is_active('/admin/seo', 'show') ?>" id="submenuSeo">
        <a class="nav-link" href="<?= url('admin/seo') ?>">
          <i class="bi bi-speedometer2"></i>
          <span>Panel SEO</span>
        </a>
        <a class="nav-link" href="<?= url('admin/seo/global') ?>">
          <i class="bi bi-globe"></i>
          <span>SEO Global</span>
        </a>
        <a class="nav-link" href="<?= url('admin/seo/redirects') ?>">
          <i class="bi bi-signpost-split"></i>
          <span>Redirecciones</span>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── ADMINISTRACIÓN ── -->
    <div class="nav-label">Administración</div>

    <?php if (!isset($current['id']) || can('users.view')): ?>
    <a class="nav-link <?= is_active('/admin/users', 'active') ?>" href="<?= url('admin/users') ?>">
      <i class="bi bi-people"></i>
      <span>Usuarios</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('users.ban')): ?>
    <a class="nav-link <?= is_active('/admin/users/banned', 'active') ?>" href="<?= url('admin/users/banned') ?>">
      <i class="bi bi-person-x"></i>
      <span>Usuarios Baneados</span>
      <?php if ($bannedCount > 0): ?>
        <span class="nav-badge badge-danger"><?= $bannedCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <a class="nav-link <?= is_active('/admin/roles', 'active') ?>" href="<?= url('admin/roles') ?>">
      <i class="bi bi-shield-lock"></i>
      <span>Roles y permisos</span>
    </a>

    <?php if (!isset($current['id']) || can('settings.manage')): ?>
    <a class="nav-link <?= is_active('/admin/settings', 'active') ?>" href="<?= url('admin/settings') ?>">
      <i class="bi bi-gear"></i>
      <span>Configuración</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('cache.manage')): ?>
    <a class="nav-link <?= is_active('/admin/cache', 'active') ?>" href="<?= url('admin/cache') ?>">
      <i class="bi bi-hdd"></i>
      <span>Cache</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('backups.manage')): ?>
    <a class="nav-link <?= is_active('/admin/backups', 'active') ?>" href="<?= url('admin/backups') ?>">
      <i class="bi bi-archive"></i>
      <span>Backups</span>
    </a>
    <?php endif; ?>

    <!-- ── ANÁLISIS ── -->
    <div class="nav-label">Análisis</div>

    <?php if (!isset($current['id']) || can('analytics.view')): ?>
    <a class="nav-link <?= is_active('/admin/analytics', 'active') ?>" href="<?= url('admin/analytics') ?>">
      <i class="bi bi-graph-up"></i>
      <span>Métricas</span>
    </a>
    <?php endif; ?>

    <?php if (!isset($current['id']) || can('audit.view')): ?>
    <a class="nav-link <?= is_active('/admin/audit', 'active') ?>" href="<?= url('admin/audit') ?>">
      <i class="bi bi-journal-text"></i>
      <span>Auditoría</span>
    </a>
    <?php endif; ?>

    <!-- ── SEGURIDAD (con submenú) ── -->
    <?php if (!isset($current['id']) || can('audit.view')): ?>
    <div class="nav-item-has-submenu">
      <a class="nav-link nav-toggle <?= is_active('/admin/security', 'active') ?>" 
         href="#" 
         data-bs-toggle="collapse" 
         data-bs-target="#submenuSecurity" 
         aria-expanded="<?= is_active('/admin/security', 'true') ?>">
        <i class="bi bi-shield-lock"></i>
        <span>Seguridad</span>
        <i class="bi bi-chevron-right arrow ms-auto"></i>
      </a>
      <div class="collapse submenu <?= is_active('/admin/security', 'show') ?>" id="submenuSecurity">
        <a class="nav-link" href="<?= url('admin/security/dashboard') ?>">
          <i class="bi bi-speedometer"></i>
          <span>Dashboard</span>
        </a>
        <a class="nav-link" href="<?= url('admin/audit/activity') ?>">
          <i class="bi bi-journal-text"></i>
          <span>Activity Logs</span>
        </a>
        <a class="nav-link" href="<?= url('admin/security/login-attempts') ?>">
          <i class="bi bi-key"></i>
          <span>Login Attempts</span>
        </a>
        <a class="nav-link <?= is_active('/admin/security/trusted-devices') ? 'active' : '' ?>" 
   href="<?= url('admin/security/trusted-devices') ?>">
  <i class="bi bi-laptop"></i> Dispositivos de confianza
  <?php
  $deviceCount = 0;
  if (isset($current['id'])) {
      try {
          $deviceCount = \App\Services\TrustedDeviceService::countDevices((int)$current['id']);
      } catch (\Throwable $e) {}
  }
  if ($deviceCount > 0):
  ?>
    <span class="badge badge-info ms-auto"><?= $deviceCount ?></span>
  <?php endif; ?>
</a>
      </div>
    </div>
    <?php endif; ?>

  </nav>

  <!-- ═══ FOOTER ═══ -->
  <div class="sidebar-footer">
    <!-- 2FA Status -->
    <a class="nav-link sidebar-footer-link" href="<?= url('admin/2fa') ?>">
      <i class="bi bi-shield-check"></i>
      <span>2FA</span>
      <?php if (!empty($current['two_factor_enabled'])): ?>
        <span class="nav-badge badge-success ms-auto">ON</span>
      <?php else: ?>
        <span class="nav-badge badge-warning ms-auto">OFF</span>
      <?php endif; ?>
    </a>

    <!-- User Chip -->
    <a class="user-chip" href="<?= url('admin/profile') ?>">
      <div class="avatar"><?= e(strtoupper($initial)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= e($fullName) ?></div>
        <small><?= e($role) ?></small>
      </div>
      <?php if ($unreadNotifs > 0): ?>
        <span class="nav-badge badge-danger"><?= $unreadNotifs ?></span>
      <?php endif; ?>
    </a>
  </div>
</aside>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>