<?php
// Fallbacks seguros para evitar errores de PHP 8 "Undefined array key"
$current = $current ?? \App\Core\Auth::user() ?? [];
$username = $current['username'] ?? 'Usuario';
$fullName = $current['full_name'] ?? $username;
$role = $current['role'] ?? 'Guest';
$initial = mb_substr($fullName, 0, 1);
$cfg = $cfg ?? ['app_version' => '1.0.0'];
?>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a class="sidebar-brand" href="<?= url('admin') ?>">
      <span class="brand-icon"><i class="bi bi-code-slash"></i></span>
      <span>
        <span class="brand-text">PePiuoX</span>
        <small>Maker v<?= e($cfg['app_version']) ?></small>
      </span>
    </a>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Principal</div>
    <a class="nav-link <?= is_active('/admin') ?>" href="<?= url('admin') ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>

    <div class="nav-label">Contenido</div>
    <?php if (can('pages.view')): ?>
    <a class="nav-link <?= is_active('/admin/pages') ? 'active' : '' ?>" href="<?= url('admin/pages') ?>"><i class="bi bi-file-earmark-richtext"></i> Páginas</a>
    <?php endif; ?>
    <?php if (can('templates.view')): ?>
    <a class="nav-link <?= is_active('/admin/templates') ? 'active' : '' ?>" href="<?= url('admin/templates') ?>"><i class="bi bi-layout-text-window"></i> Templates</a>
    <?php endif; ?>
    <?php if (can('menus.manage')): ?>
    <a class="nav-link <?= is_active('/admin/menus') ? 'active' : '' ?>" href="<?= url('admin/menus') ?>"><i class="bi bi-list-nested"></i> Menús</a>
    <?php endif; ?>

    <div class="nav-label">Administración</div>
    <?php if (!isset($current['id']) || can('users.ban')): ?>
<a class="nav-link <?= is_active('/admin/users/banned') ? 'active' : '' ?>" href="<?= url('admin/users/banned') ?>">
  <i class="bi bi-person-x"></i> Usuarios Baneados
  <?php
    $bannedCount = \App\Core\Database::fetch("SELECT COUNT(*) AS c FROM users WHERE is_banned = 1")['c'] ?? 0;
    if ($bannedCount > 0):
  ?>
    <span class="badge badge-danger ms-auto"><?= $bannedCount ?></span>
  <?php endif; ?>
</a>
<?php endif; ?>
    <?php if (can('users.view')): ?>
    <a class="nav-link <?= is_active('/admin/users') ? 'active' : '' ?>" href="<?= url('admin/users') ?>"><i class="bi bi-people"></i> Usuarios</a>
    <?php endif; ?>
    <a class="nav-link <?= is_active('/admin/roles') ? 'active' : '' ?>" href="<?= url('admin/roles') ?>"><i class="bi bi-shield-lock"></i> Roles y permisos</a>
    <?php if (can('files.manage')): ?>
    <a class="nav-link <?= is_active('/admin/files') ? 'active' : '' ?>" href="<?= url('admin/files') ?>"><i class="bi bi-folder2-open"></i> Archivos</a>
    <?php endif; ?>
    <?php if (can('seo.manage')): ?>
    <a class="nav-link <?= is_active('/admin/seo') ? 'active' : '' ?>" href="<?= url('admin/seo') ?>">
  <i class="bi bi-search"></i> SEO
  <i class="bi bi-chevron-right arrow ms-auto"></i>
</a>
<div class="collapse">
  <a class="nav-link" href="<?= url('admin/seo') ?>">Panel SEO</a>
  <a class="nav-link" href="<?= url('admin/seo/global') ?>">SEO Global</a>
  <a class="nav-link" href="<?= url('admin/seo/redirects') ?>">Redirecciones</a>
</div>
<?php endif; ?>
<div class="nav-label">Seguridad & Auditoría</div>
<?php if (!isset($current['id']) || can('audit.view')): ?>
<a class="nav-link <?= is_active('/admin/security/dashboard') ? 'active' : '' ?>" href="<?= url('admin/security/dashboard') ?>">
  <i class="bi bi-shield-lock"></i> Dashboard Seguridad
</a>
<a class="nav-link <?= is_active('/admin/audit/activity') ? 'active' : '' ?>" href="<?= url('admin/audit/activity') ?>">
  <i class="bi bi-journal-text"></i> Activity Logs
</a>
<a class="nav-link <?= is_active('/admin/security/login-attempts') ? 'active' : '' ?>" href="<?= url('admin/security/login-attempts') ?>">
  <i class="bi bi-key"></i> Login Attempts
</a>
<?php endif; ?>
    <!-- Dentro de "Administración" -->
<?php if (!isset($current['id']) || can('audit.view')): ?>
<div class="nav-label">Seguridad & Auditoría</div>
<a class="nav-link <?= is_active('/admin/audit/activity') ? 'active' : '' ?>" href="<?= url('admin/audit/activity') ?>">
  <i class="bi bi-journal-text"></i> Activity Logs
</a>
<a class="nav-link <?= is_active('/admin/security') ? 'active' : '' ?>" href="<?= url('admin/security/login-attempts') ?>">
  <i class="bi bi-shield-lock"></i> Login Attempts
</a>
<?php endif; ?>
    <?php if (can('cache.manage')): ?>
    <a class="nav-link <?= is_active('/admin/cache') ? 'active' : '' ?>" href="<?= url('admin/cache') ?>"><i class="bi bi-hdd"></i> Cache</a>
    <?php endif; ?>
    <?php if (can('settings.manage')): ?>
    <a class="nav-link <?= is_active('/admin/settings') ? 'active' : ''?>" href="<?= url('admin/settings') ?>"><i class="bi bi-gear"></i> Configuración</a>
    <?php endif; ?>
    <?php if (can('audit.view')): ?>
    <a class="nav-link <?= is_active('/admin/audit') ? 'active' : '' ?>" href="<?= url('admin/audit') ?>"><i class="bi bi-journal-text"></i> Auditoría</a>
    <?php endif; ?>
    <?php if (can('analytics.view')): ?>
    <a class="nav-link <?= is_active('/admin/analytics') ? 'active' : '' ?>" href="<?= url('admin/analytics') ?>"><i class="bi bi-graph-up"></i> Métricas</a>
    <?php endif; ?>
    <?php if (can('backups.manage')): ?>
    <a class="nav-link <?= is_active('/admin/backups') ? 'active' : '' ?>" href="<?= url('admin/backups') ?>"><i class="bi bi-archive"></i> Backups</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <a class="nav-link" href="<?= url('admin/2fa') ?>" style="padding:.4rem .7rem;font-size:.8rem">
      <i class="bi bi-shield-check"></i> 2FA <?= $current['two_factor_enabled'] ? '<span class="badge badge-success">ON</span>' : '' ?>
      </a>
      <div class="avatar"><?= e(mb_substr($current['full_name'] ?? $current['username'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= e($current['full_name'] ?? $current['username']) ?></div>
        <small><?= e($current['role']) ?></small>
      </div>
    </div>
  </div>
</aside>