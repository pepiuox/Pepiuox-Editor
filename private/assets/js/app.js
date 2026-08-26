document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggleSidebar');
  const closeBtn = document.getElementById('sidebarCloseBtn');
  const overlay = document.getElementById('sidebarOverlay');
  const body = document.body;

  // ═══ TOGGLE SIDEBAR ═══
  function toggleSidebar() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
      // En móvil: mostrar/ocultar con overlay
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      body.classList.toggle('sidebar-open');
    } else {
      // En desktop: colapsar completamente
      sidebar.classList.toggle('collapsed');
      body.classList.toggle('sidebar-collapsed');
      
      // Guardar estado en localStorage
      const isCollapsed = sidebar.classList.contains('collapsed');
      localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
  }

  function closeSidebar() {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    body.classList.remove('sidebar-open');
  }

  // Event listeners
  if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  // Cerrar sidebar en móvil al hacer clic en un enlace
  if (window.innerWidth <= 768) {
    sidebar.querySelectorAll('.nav-link:not(.nav-toggle)').forEach(link => {
      link.addEventListener('click', closeSidebar);
    });
  }

  // ═══ RESTAURAR ESTADO DEL SIDEBAR (Desktop) ═══
  if (window.innerWidth > 768) {
    const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (wasCollapsed) {
      sidebar.classList.add('collapsed');
      body.classList.add('sidebar-collapsed');
    }
  }

  // ═══ SUBMENÚS (Collapse) ═══
  document.querySelectorAll('.nav-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = toggle.getAttribute('data-bs-target');
      const submenu = document.querySelector(targetId);
      
      if (!submenu) return;

      const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
      
      // Toggle estado
      toggle.setAttribute('aria-expanded', !isExpanded);
      submenu.classList.toggle('show');
      
      // Guardar estado en localStorage
      const submenuStates = JSON.parse(localStorage.getItem('submenuStates') || '{}');
      submenuStates[targetId] = !isExpanded;
      localStorage.setItem('submenuStates', JSON.stringify(submenuStates));
    });
  });

  // ═══ RESTAURAR ESTADO DE SUBMENÚS ═══
  const submenuStates = JSON.parse(localStorage.getItem('submenuStates') || '{}');
  Object.entries(submenuStates).forEach(([targetId, isOpen]) => {
    const submenu = document.querySelector(targetId);
    const toggle = document.querySelector(`[data-bs-target="${targetId}"]`);
    
    if (submenu && toggle) {
      if (isOpen) {
        submenu.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
      } else {
        submenu.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  // ═══ CERRAR DROPDOWNS AL HACER CLIC FUERA ═══
  document.addEventListener('click', (e) => {
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown && !notifDropdown.contains(e.target)) {
      notifDropdown.classList.remove('open');
    }
  });

  // ═══ AUTO-CERRAR ALERTAS ═══
  document.querySelectorAll('.alert-dismissible .btn-close').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alert').remove());
  });

  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });
});

// ═══ NOTIFICACIONES ═══
function toggleNotifications() {
  const dd = document.getElementById('notifDropdown');
  dd.classList.toggle('open');
  if (dd.classList.contains('open')) loadNotifications();
}

async function loadNotifications() {
  const list = document.getElementById('notifList');
  if (!list) return;
  
  try {
    const r = await fetch(`${window.BASE_URL || ''}/admin/notifications/list`, { 
      headers: {'X-Requested-With':'XMLHttpRequest'} 
    });
    const d = await r.json();
    
    if (!d.success || !d.data.length) {
      list.innerHTML = '<div class="text-center text-muted p-3">No tienes notificaciones</div>';
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
    list.innerHTML = '<div class="text-center text-muted p-3">Error al cargar</div>';
  }
}

async function markRead(id) {
  try {
    await fetch(`${window.BASE_URL || ''}/admin/notifications/${id}/read`, { 
      method:'POST', 
      headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'} 
    });
    const badge = document.getElementById('notifBadge');
    if (badge) {
      const n = Math.max(0, parseInt(badge.textContent) - 1);
      if (n === 0) badge.remove(); 
      else badge.textContent = n;
    }
  } catch(e) {}
}

function escapeHtml(s) { 
  const d = document.createElement('div'); 
  d.textContent = s; 
  return d.innerHTML; 
}

function timeAgo(dt) {
  const s = Math.floor((Date.now()/1000) - (new Date(dt).getTime()/1000));
  if (s < 60) return 'ahora';
  if (s < 3600) return Math.floor(s/60) + ' min';
  if (s < 86400) return Math.floor(s/3600) + ' h';
  return Math.floor(s/86400) + ' d';
}

// ═══ TOAST HELPER ═══
function toast(msg, type='success') {
  let c = document.querySelector('.toast-container');
  if (!c) { 
    c = document.createElement('div'); 
    c.className='toast-container'; 
    document.body.appendChild(c); 
  }
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  const icon = {
    success:'check-circle',
    error:'exclamation-triangle',
    info:'info-circle',
    warning:'exclamation-circle'
  }[type] || 'info-circle';
  t.innerHTML = `<i class="bi bi-${icon}"></i> ${msg}`;
  c.appendChild(t);
  setTimeout(() => { 
    t.style.opacity='0'; 
    setTimeout(()=>t.remove(), 300); 
  }, 3000);
}


// Fetch helper con CSRF
async function api(url, options = {}) {
  const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
  const headers = Object.assign({
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  }, options.headers || {});
  return fetch(url, Object.assign({ credentials:'same-origin', headers }, options));
}

// ═══════════════════════════════════════════════════════════════════
// DROPDOWNS (Topbar)
// ═══════════════════════════════════════════════════════════════════

/**
 * Toggle dropdown con cierre automático de otros dropdowns
 */
function toggleDropdown(dropdownId, loadNotifs = false) {
  const dropdown = document.getElementById(dropdownId);
  if (!dropdown) return;
  
  const isOpen = dropdown.classList.contains('open');
  
  // Cerrar todos los dropdowns
  document.querySelectorAll('.dropdown.open').forEach(d => {
    if (d.id !== dropdownId) d.classList.remove('open');
  });
  
  // Toggle el dropdown actual
  dropdown.classList.toggle('open');
  
  // Cargar notificaciones si es el dropdown de notificaciones
  if (loadNotifs && !isOpen) {
    loadNotifications();
  }
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', (e) => {
  const dropdowns = document.querySelectorAll('.dropdown');
  dropdowns.forEach(dropdown => {
    if (!dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
});

// Cerrar dropdowns con tecla Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.dropdown.open').forEach(d => {
      d.classList.remove('open');
    });
  }
});

// ═══════════════════════════════════════════════════════════════════
// NOTIFICACIONES
// ═══════════════════════════════════════════════════════════════════

/**
 * Carga las notificaciones vía AJAX
 */
async function loadNotifications() {
  const list = document.getElementById('notifList');
  if (!list) return;
  
  // Mostrar loading
  list.innerHTML = '<div class="text-center text-muted p-3"><i class="bi bi-hourglass-split"></i> Cargando...</div>';
  
  try {
    const r = await fetch(`${window.BASE_URL || ''}/admin/notifications/list`, { 
      headers: {'X-Requested-With':'XMLHttpRequest'} 
    });
    const d = await r.json();
    
    if (!d.success || !d.data.length) {
      list.innerHTML = `
        <div class="text-center text-muted p-4">
          <i class="bi bi-bell-slash" style="font-size:2rem"></i>
          <p class="mt-2 mb-0">No tienes notificaciones</p>
        </div>
      `;
      return;
    }
    
    list.innerHTML = d.data.map(n => `
      <a href="${n.link || '#'}" 
         class="notif-item ${n.is_read == 0 ? 'unread' : ''} ${n.type}" 
         onclick="markNotificationRead(${n.id}, event)">
        <div class="icon">
          <i class="bi bi-${n.icon || 'bell'}"></i>
        </div>
        <div class="content">
          <div class="title">${escapeHtml(n.title)}</div>
          ${n.message ? `<div class="msg">${escapeHtml(n.message)}</div>` : ''}
          <div class="time">
            <i class="bi bi-clock"></i> ${timeAgo(n.created_at)}
          </div>
        </div>
      </a>
    `).join('');
  } catch(e) {
    console.error('Error loading notifications:', e);
    list.innerHTML = `
      <div class="text-center text-muted p-4">
        <i class="bi bi-exclamation-triangle" style="font-size:2rem"></i>
        <p class="mt-2 mb-0">Error al cargar notificaciones</p>
      </div>
    `;
  }
}

/**
 * Marca una notificación como leída
 */
async function markNotificationRead(id, event) {
  event.preventDefault();
  
  try {
    await fetch(`${window.BASE_URL || ''}/admin/notifications/${id}/read`, { 
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
      }
    });
    
    // Actualizar badge
    const badge = document.getElementById('notifBadge');
    if (badge) {
      const current = parseInt(badge.textContent);
      const newCount = Math.max(0, current - 1);
      
      if (newCount === 0) {
        badge.remove();
      } else {
        badge.textContent = newCount;
      }
    }
    
    // Marcar visualmente como leída
    const item = event.currentTarget;
    item.classList.remove('unread');
    
    // Navegar al enlace
    const link = item.getAttribute('href');
    if (link && link !== '#') {
      window.location.href = link;
    }
  } catch(e) {
    console.error('Error marking notification as read:', e);
  }
}

/**
 * Polling de notificaciones cada 60 segundos
 */
let notifPollingInterval = null;

function startNotificationPolling() {
  if (notifPollingInterval) return; // Ya está corriendo
  
  notifPollingInterval = setInterval(async () => {
    try {
      const r = await fetch(`${window.BASE_URL || ''}/admin/notifications/list`, { 
        headers: {'X-Requested-With':'XMLHttpRequest'} 
      });
      const d = await r.json();
      
      if (d.success) {
        const badge = document.getElementById('notifBadge');
        
        if (d.unread > 0) {
          if (badge) {
            badge.textContent = d.unread;
          } else {
            // Crear badge si no existe
            const btn = document.querySelector('#notifDropdown button');
            if (btn) {
              const newBadge = document.createElement('span');
              newBadge.className = 'notif-badge';
              newBadge.id = 'notifBadge';
              newBadge.textContent = d.unread;
              btn.appendChild(newBadge);
            }
          }
        } else if (badge) {
          badge.remove();
        }
      }
    } catch(e) {
      console.error('Error polling notifications:', e);
    }
  }, 60000); // 60 segundos
}

function stopNotificationPolling() {
  if (notifPollingInterval) {
    clearInterval(notifPollingInterval);
    notifPollingInterval = null;
  }
}

// Iniciar polling al cargar la página
if (document.getElementById('notifDropdown')) {
  startNotificationPolling();
}

// Detener polling al salir de la página
window.addEventListener('beforeunload', stopNotificationPolling);

// ═══════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

/**
 * Convierte timestamp a tiempo relativo
 */
function timeAgo(dt) {
  if (!dt) return '';
  
  const now = Date.now() / 1000;
  const then = new Date(dt).getTime() / 1000;
  const diff = Math.floor(now - then);
  
  if (diff < 0) return 'ahora';
  if (diff < 60) return 'ahora';
  if (diff < 3600) {
    const mins = Math.floor(diff / 60);
    return `hace ${mins} ${mins === 1 ? 'minuto' : 'minutos'}`;
  }
  if (diff < 86400) {
    const hours = Math.floor(diff / 3600);
    return `hace ${hours} ${hours === 1 ? 'hora' : 'horas'}`;
  }
  if (diff < 604800) {
    const days = Math.floor(diff / 86400);
    return `hace ${days} ${days === 1 ? 'día' : 'días'}`;
  }
  if (diff < 2592000) {
    const weeks = Math.floor(diff / 604800);
    return `hace ${weeks} ${weeks === 1 ? 'semana' : 'semanas'}`;
  }
  if (diff < 31536000) {
    const months = Math.floor(diff / 2592000);
    return `hace ${months} ${months === 1 ? 'mes' : 'meses'}`;
  }
  const years = Math.floor(diff / 31536000);
  return `hace ${years} ${years === 1 ? 'año' : 'años'}`;
}