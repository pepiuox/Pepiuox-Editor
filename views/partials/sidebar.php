<?php
// Fallbacks seguros para evitar errores de PHP 8
$current = $current ?? \App\Core\Auth::user() ?? [];
$username = $current['username'] ?? 'Usuario';
$fullName = $current['full_name'] ?? $username;
$role = $current['role'] ?? 'Guest';
$initial = mb_substr($fullName, 0, 1);
$cfg = $cfg ?? ['app_version' => '1.0.0'];

// Verificar si el sidebar debe estar colapsado por defecto
$sidebarCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

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

<aside class="sidebar <?= $sidebarCollapsed ? 'sidebar-collapsed' : '' ?>" id="sidebar">
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

    <!-- ── USUARIOS ── -->
    <div class="nav-label">Usuarios</div>

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
<!-- ── WEB PÚBLICA ── -->
    <?php if (!isset($current['id']) || can('settings.manage')): ?>
    <div class="nav-label">Web pública</div>
    <a class="nav-link <?= is_active('/admin/settings/landing', 'active') ?>" href="<?= url('admin/settings/landing') ?>">
      <i class="bi bi-window-sidebar"></i>
      <span>Portada</span>
    </a>
    <a class="nav-link <?= is_active('/admin/settings/footer', 'active') ?>" href="<?= url('admin/settings/footer') ?>">
      <i class="bi bi-layout-three-columns"></i>
      <span>Pie de página</span>
    </a>
    <?php endif; ?>

<!-- ── ADMINISTRACIÓN ── -->
    <div class="nav-label">Administración</div>
    <?php if (!isset($current['id']) || can('settings.manage')): ?>
    <a class="nav-link <?= is_active_exact('/admin/settings', 'active') ?>" href="<?= url('admin/settings') ?>">
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

<style>
/* ============================================================
   SIDEBAR ESTILOS
   ============================================================ */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 280px;
  background: var(--bg-card);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  z-index: 1040;
  transition: transform 0.3s ease, width 0.3s ease;
  overflow: hidden;
}

/* Colapsado */
.sidebar-collapsed {
  width: 72px;
}

.sidebar-collapsed .brand-info,
.sidebar-collapsed .nav-label,
.sidebar-collapsed .nav-link span,
.sidebar-collapsed .nav-link .arrow,
.sidebar-collapsed .sidebar-footer-link span,
.sidebar-collapsed .user-info,
.sidebar-collapsed .nav-badge,
.sidebar-collapsed .submenu {
  display: none !important;
}

.sidebar-collapsed .nav-link {
  justify-content: center;
  padding: 0.75rem;
}

.sidebar-collapsed .nav-link i {
  font-size: 1.4rem;
  margin: 0 !important;
}

.sidebar-collapsed .sidebar-brand {
  justify-content: center;
}

.sidebar-collapsed .avatar {
  width: 36px;
  height: 36px;
  font-size: 0.85rem;
}

.sidebar-collapsed .user-chip {
  justify-content: center;
  padding: 0.5rem;
}

.sidebar-collapsed .sidebar-footer-link {
  justify-content: center;
  padding: 0.5rem;
}

.sidebar-collapsed .sidebar-footer-link i {
  font-size: 1.2rem;
  margin: 0 !important;
}

/* Sidebar header */
.sidebar-header {
  padding: 1rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: var(--text-main);
}

.sidebar-brand .brand-icon {
  font-size: 1.8rem;
  color: var(--accent);
}

.sidebar-brand .brand-text {
  font-weight: 700;
  font-size: 1.2rem;
}

.sidebar-brand small {
  display: block;
  font-size: 0.65rem;
  color: var(--text-muted);
  font-weight: 400;
}

.sidebar-close-btn {
  display: none;
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.25rem;
}

/* Navegación */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 0.75rem 1rem;
  scrollbar-width: thin;
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 4px;
}

.nav-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted-light);
  padding: 0.75rem 0.75rem 0.4rem;
  font-weight: 600;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  text-decoration: none;
  transition: all var(--transition);
  cursor: pointer;
  position: relative;
}

.nav-link:hover {
  background: var(--accent-light);
  color: var(--text-main);
}

.nav-link.active {
  background: var(--accent-light);
  color: var(--accent);
}

.nav-link i {
  font-size: 1.1rem;
  min-width: 1.4rem;
  text-align: center;
}

.nav-link .arrow {
  font-size: 0.7rem;
  transition: transform 0.2s ease;
  margin-left: auto;
}

.nav-link[aria-expanded="true"] .arrow {
  transform: rotate(90deg);
}

.nav-badge {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.1rem 0.5rem;
  border-radius: 10px;
  margin-left: auto;
}

.badge-success {
  background: var(--success);
  color: #fff;
}

.badge-danger {
  background: var(--danger);
  color: #fff;
}

.badge-warning {
  background: var(--warning);
  color: #000;
}

.badge-info {
  background: var(--info);
  color: #fff;
}

/* Submenú */
.submenu {
  padding-left: 0.75rem;
  border-left: 2px solid var(--accent);
  margin-left: 0.75rem;
}

.submenu .nav-link {
  padding: 0.4rem 0.75rem;
  font-size: 0.9rem;
}

.submenu .nav-link i {
  font-size: 0.9rem;
  min-width: 1.2rem;
}

/* Sidebar footer */
.sidebar-footer {
  border-top: 1px solid var(--border-color);
  padding: 0.75rem;
  flex-shrink: 0;
}

.sidebar-footer-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  text-decoration: none;
  transition: all var(--transition);
  font-size: 0.9rem;
}

.sidebar-footer-link:hover {
  background: var(--accent-light);
  color: var(--text-main);
}

.sidebar-footer-link i {
  font-size: 1rem;
  min-width: 1.4rem;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-sm);
  color: var(--text-main);
  text-decoration: none;
  transition: all var(--transition);
  margin-top: 0.5rem;
}

.user-chip:hover {
  background: var(--accent-light);
}

.user-chip .avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--accent);
  color: var(--bg-main);
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.user-chip .user-info {
  flex: 1;
  min-width: 0;
}

.user-chip .user-name {
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-chip small {
  display: block;
  font-size: 0.7rem;
  color: var(--text-muted);
}

/* Overlay para móvil */
.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1039;
  backdrop-filter: blur(4px);
}

.sidebar-overlay.active {
  display: block;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 991.98px) {
  .sidebar {
    transform: translateX(-100%);
    width: 280px;
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  .sidebar-close-btn {
    display: block;
  }

  .sidebar-overlay.active {
    display: block;
  }
}

@media (min-width: 992px) {
  .sidebar {
    transform: translateX(0);
  }

  .sidebar.sidebar-collapsed ~ .topbar {
    margin-left: 72px;
  }

  .sidebar.sidebar-collapsed ~ .main-content {
    margin-left: 72px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  // ============================================================
  // Elementos
  // ============================================================
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('toggleSidebar');
  const closeBtn = document.getElementById('sidebarCloseBtn');

  // ============================================================
  // Funciones
  // ============================================================
  
  /**
   * Abre el sidebar
   */
  function openSidebar() {
    if (window.innerWidth < 992) {
      sidebar.classList.add('sidebar-open');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  /**
   * Cierra el sidebar
   */
  function closeSidebar() {
    if (window.innerWidth < 992) {
      sidebar.classList.remove('sidebar-open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  /**
   * Toggle del sidebar (colapsar en desktop, abrir/cerrar en móvil)
   */
  function toggleSidebar() {
    if (window.innerWidth < 992) {
      if (sidebar.classList.contains('sidebar-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    } else {
      // En desktop: colapsar/expandir
      sidebar.classList.toggle('sidebar-collapsed');
      // Guardar preferencia en cookie
      const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
      document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;
      // Disparar evento de redimensionamiento para ajustar contenido
      window.dispatchEvent(new Event('resize'));
    }
  }

  /**
   * Verifica si el sidebar está colapsado en desktop
   */
  function isSidebarCollapsed() {
    return window.innerWidth >= 992 && sidebar.classList.contains('sidebar-collapsed');
  }

  // ============================================================
  // Event Listeners
  // ============================================================

  // Toggle button
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      toggleSidebar();
    });
  }

  // Close button (móvil)
  if (closeBtn) {
    closeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      closeSidebar();
    });
  }

  // Overlay click (móvil)
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      e.preventDefault();
      closeSidebar();
    });
  }

  // Tecla Escape para cerrar en móvil
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && window.innerWidth < 992) {
      closeSidebar();
    }
  });

  // Cerrar al hacer click en un enlace del menú (móvil)
  if (sidebar) {
    sidebar.querySelectorAll('.nav-link:not(.nav-toggle)').forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.innerWidth < 992 && !this.classList.contains('nav-toggle')) {
          closeSidebar();
        }
      });
    });
  }

  // Ajustar al cambiar tamaño de ventana
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      if (window.innerWidth >= 992) {
        // En desktop: asegurar que el overlay esté cerrado
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Si el sidebar estaba abierto en móvil, cerrarlo
        sidebar.classList.remove('sidebar-open');
        
        // Aplicar estado de colapso desde cookie
        const isCollapsed = document.cookie.includes('sidebar_collapsed=true');
        if (isCollapsed) {
          sidebar.classList.add('sidebar-collapsed');
        } else {
          sidebar.classList.remove('sidebar-collapsed');
        }
      } else {
        // En móvil: asegurar que no esté colapsado
        sidebar.classList.remove('sidebar-collapsed');
      }
    }, 150);
  });

  // ============================================================
  // Inicialización
  // ============================================================
  
  // Verificar si hay estado guardado en cookie para desktop
  if (window.innerWidth >= 992) {
    const isCollapsed = document.cookie.includes('sidebar_collapsed=true');
    if (isCollapsed) {
      sidebar.classList.add('sidebar-collapsed');
    }
  }

  // ============================================================
  // Exponer funciones globalmente
  // ============================================================
  window.toggleSidebar = toggleSidebar;
  window.openSidebar = openSidebar;
  window.closeSidebar = closeSidebar;
  window.isSidebarCollapsed = isSidebarCollapsed;
});
</script>