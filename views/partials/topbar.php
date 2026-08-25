<?php
// Fallbacks seguros
$current = $current ?? \App\Core\Auth::user() ?? [];
$uid = $current['id'] ?? null;
$unread = 0;
if ($uid) {
    try {
        $unread = \App\Services\NotificationService::unreadCount((int)$uid);
    } catch (\Throwable $e) {
        $unread = 0;
    }
}

// Idioma actual
$currentLang = \App\Core\Session::get('user_locale', $current['locale'] ?? 'es');
$langs = [];
try {
    $langs = \App\Core\Database::fetchAll("SELECT code, name, native_name, flag FROM languages WHERE is_active = 1 ORDER BY sort_order");
} catch (\Throwable $e) {}

$currentLangData = current(array_filter($langs, fn($l) => $l['code'] === $currentLang)) ?: ['flag' => '🌐', 'native_name' => 'Idioma'];
?>

<header class="topbar">
  <div class="topbar-left">
    <!-- Toggle Sidebar -->
    <button class="btn-toggle-sidebar" id="toggleSidebar" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <h1 class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
  </div>
  
  <div class="topbar-right">
    <!-- Selector de Idioma -->
    <?php if (!empty($langs)): ?>
    <div class="dropdown" id="langDropdown">
      <button class="btn btn-ghost btn-sm" aria-label="Cambiar idioma" onclick="toggleDropdown('langDropdown')">
        <i class="bi bi-globe"></i>
        <span class="lang-flag"><?= e($currentLangData['flag']) ?></span>
        <span class="d-none d-lg-inline"><?= e($currentLangData['native_name']) ?></span>
      </button>
      <div class="dropdown-menu-custom">
        <?php foreach ($langs as $l): ?>
          <a class="dropdown-item-custom <?= $l['code'] === $currentLang ? 'active' : '' ?>" 
             href="<?= url('lang/'.$l['code']) ?>">
            <span class="lang-flag"><?= e($l['flag']) ?></span>
            <span><?= e($l['native_name']) ?></span>
            <?php if ($l['code'] === $currentLang): ?>
              <i class="bi bi-check ms-auto"></i>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Notificaciones -->
    <?php if ($uid): ?>
    <div class="dropdown" id="notifDropdown">
      <button class="btn btn-ghost btn-sm position-relative" 
              aria-label="Notificaciones" 
              onclick="toggleDropdown('notifDropdown', true)">
        <i class="bi bi-bell"></i>
        <?php if ($unread > 0): ?>
          <span class="notif-badge" id="notifBadge"><?= $unread ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu-custom dropdown-notif" id="notifMenu">
        <div class="dropdown-header-custom">
          <strong><?= t('notifications') ?></strong>
          <a href="<?= url('admin/notifications/mark-all') ?>" class="text-small text-primary">
            <?= t('mark_all_read') ?>
          </a>
        </div>
        <div id="notifList" class="dropdown-body-custom">
          <div class="text-center text-muted p-3">
            <i class="bi bi-hourglass-split"></i> <?= t('loading') ?>...
          </div>
        </div>
        <div class="dropdown-footer-custom">
          <a href="<?= url('admin/notifications') ?>" class="text-primary">
            <?= t('view_all') ?> <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Ver Sitio -->
    <a href="<?= url('/') ?>" class="btn btn-ghost btn-sm" target="_blank" title="Ver sitio público">
      <i class="bi bi-box-arrow-up-right"></i>
      <span class="d-none d-lg-inline">Ver sitio</span>
    </a>
    
    <!-- Perfil -->
    <a href="<?= url('admin/profile') ?>" class="btn btn-ghost btn-sm" title="Mi perfil">
      <i class="bi bi-person-circle"></i>
      <span class="d-none d-lg-inline"><?= t('profile') ?></span>
    </a>
    
    <!-- Logout -->
    <a href="<?= url('logout') ?>" class="btn btn-danger btn-sm" title="Cerrar sesión">
      <i class="bi bi-box-arrow-right"></i>
      <span class="d-none d-md-inline"><?= t('logout') ?></span>
    </a>
  </div>
</header>
<style>
.dropdown { position: relative; }
.dropdown-menu-custom {
  display: none;
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  min-width: 280px;
  z-index: 1060;
  animation: fadeIn 0.2s ease;
}
.dropdown.open .dropdown-menu-custom { display: block; }
.dropdown-header-custom {
  padding: .75rem 1rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.dropdown-body-custom { max-height: 360px; overflow-y: auto; }
.dropdown-footer-custom {
  padding: .6rem 1rem;
  border-top: 1px solid var(--border);
  text-align: center;
}
.dropdown-item-custom {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .6rem 1rem;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-light);
  transition: background var(--transition-fast);
}
.dropdown-item-custom:hover { background: var(--bg-hover); color: var(--accent); }
.notif-item {
  display: flex;
  gap: .75rem;
  padding: .75rem 1rem;
  border-bottom: 1px solid var(--border-light);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.notif-item:hover { background: var(--bg-hover); }
.notif-item.unread { background: rgba(0,212,170,0.04); }
.notif-item .icon {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: grid; place-items: center;
  flex-shrink: 0;
  background: rgba(0,212,170,0.1);
  color: var(--accent);
}
.notif-item.error .icon { background: rgba(239,68,68,0.1); color: var(--danger); }
.notif-item.warning .icon { background: rgba(245,158,11,0.1); color: var(--warning); }
.notif-item.success .icon { background: rgba(34,197,94,0.1); color: var(--success); }
.notif-item .content { flex: 1; min-width: 0; }
.notif-item .title { font-weight: 600; color: var(--text-primary); font-size: .85rem; }
.notif-item .msg { font-size: .75rem; color: var(--text-muted); margin-top: 2px; }
.notif-item .time { font-size: .7rem; color: var(--text-dim); margin-top: 4px; }
.notif-badge {
  position: absolute;
  top: -4px; right: -4px;
  background: var(--danger);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  padding: 0 5px;
  border: 2px solid var(--bg-card);
}
</style>

<script>
function toggleNotifications() {
  const dd = document.getElementById('notifDropdown');
  dd.classList.toggle('open');
  if (dd.classList.contains('open')) loadNotifications();
}
document.addEventListener('click', (e) => {
  const dd = document.getElementById('notifDropdown');
  const ld = document.getElementById('langDropdown');
  if (dd && !dd.contains(e.target)) dd.classList.remove('open');
  if (ld && !ld.contains(e.target)) ld.classList.remove('open');
});

async function loadNotifications() {
  const list = document.getElementById('notifList');
  try {
    const r = await fetch('<?= url("admin/notifications/list") ?>', { headers: {'X-Requested-With':'XMLHttpRequest'} });
    const d = await r.json();
    if (!d.success || !d.data.length) {
      list.innerHTML = '<div class="text-center text-muted p-3"><?= t("no_notifications") ?></div>';
      return;
    }
    list.innerHTML = d.data.map(n => `
      <a href="${n.link || '#'}" class="notif-item ${n.is_read==0?'unread':''} ${n.type}" onclick="markRead(${n.id})">
        <div class="icon"><i class="bi bi-${n.icon || 'bell'}"></i></div>
        <div class="content">
          <div class="title">${escapeHtml(n.title)}</div>
          ${n.message ? `<div class="msg">${escapeHtml(n.message)}</div>` : ''}
          <div class="time">${timeAgo(n.created_at)}</div>
        </div>
      </a>
    `).join('');
  } catch(e) {
    list.innerHTML = '<div class="text-center text-muted p-3">Error</div>';
  }
}
async function markRead(id) {
  await fetch(`<?= url("admin/notifications") ?>/${id}/read`, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'} });
  const badge = document.getElementById('notifBadge');
  if (badge) {
    const n = Math.max(0, parseInt(badge.textContent) - 1);
    if (n === 0) badge.remove(); else badge.textContent = n;
  }
}
function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function timeAgo(dt) {
  const s = Math.floor((Date.now()/1000) - (new Date(dt).getTime()/1000));
  if (s < 60) return 'ahora';
  if (s < 3600) return Math.floor(s/60) + ' min';
  if (s < 86400) return Math.floor(s/3600) + ' h';
  return Math.floor(s/86400) + ' d';
}
// Polling cada 60s
setInterval(() => {
  fetch('<?= url("admin/notifications/list") ?>', { headers: {'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json()).then(d => {
      if (d.success) {
        const badge = document.getElementById('notifBadge');
        if (d.unread > 0) {
          if (badge) badge.textContent = d.unread;
          else {
            const btn = document.querySelector('#notifDropdown button');
            const b = document.createElement('span');
            b.className = 'notif-badge'; b.id = 'notifBadge'; b.textContent = d.unread;
            btn.appendChild(b);
          }
        } else if (badge) badge.remove();
      }
    }).catch(()=>{});
}, 60000);
</script>