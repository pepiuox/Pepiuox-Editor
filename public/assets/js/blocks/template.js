// ═══════════════════════════════════════════════════════════════════
// template.js — Plantilla Admin Dashboard con Bootstrap 5.3.3
//
// Modernización:
//  - Font Awesome (fas) → Bootstrap Icons (bi)
//  - i.pravatar.cc → placehold.co (sin dependencias externas)
//  - CSS inline → clases de utilidad BS5
//  - Categorías con emojis descriptivos
//  - IIFE con verificación de gjsEditor
//  - Accesibilidad ARIA completa
//  - data-bs-theme="dark" para sidebar
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (modernización BS5.3.3)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[template] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var LAYOUT = { id: 'advanced-layouts', label: '🖥️ Layouts avanzados', open: true };

gjsEditor.BlockManager.add('bs-admin-layout', {
    category: LAYOUT,
    label: 'Admin Dashboard',
    media: '<i class="bi bi-speedometer2 fs-3"></i>',
    description: 'Layout completo de panel de administración con sidebar, topbar y contenido',
    content: '<style>'
        + 'body{overflow-x:hidden}'
        + '.admin-wrapper{min-height:100vh;background:var(--bs-body-bg)}'
        + '.sidebar{width:260px;min-height:100vh;background:#111827;transition:all .3s ease;position:fixed;top:0;left:0;z-index:1050;color:#fff}'
        + '.sidebar-brand{padding:24px;font-size:1.4rem;font-weight:bold;border-bottom:1px solid rgba(255,255,255,.08)}'
        + '.sidebar .nav-link{color:#cbd5e1;padding:14px 24px;border-radius:8px;margin:4px 12px;transition:all .2s}'
        + '.sidebar .nav-link:hover{background:#1e293b;color:#fff}'
        + '.sidebar .nav-link.active{background:#2563eb;color:#fff}'
        + '.main-wrapper{margin-left:260px;transition:all .3s ease}'
        + '.topbar{height:70px;background:var(--bs-body-bg);border-bottom:1px solid var(--bs-border-color);padding:0 20px;position:sticky;top:0;z-index:1040}'
        + '.content-area{padding:30px}'
        + '.dashboard-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.05)}'
        + '@media(max-width:991px){.sidebar{left:-260px}.sidebar.active{left:0}.main-wrapper{margin-left:0}.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1040}.sidebar-overlay.active{display:block}}'
        + '</style>'
        + '<div class="admin-wrapper">'
        + '<div class="sidebar-overlay"></div>'
        + '<aside class="sidebar"><div class="sidebar-brand">AdminPanel</div><div class="p-3">'
        + '<ul class="nav flex-column">'
        + '<li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-house-door me-2" aria-hidden="true"></i>Dashboard</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people me-2" aria-hidden="true"></i>Usuarios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart me-2" aria-hidden="true"></i>Reportes</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear me-2" aria-hidden="true"></i>Configuración</a></li>'
        + '</ul></div></aside>'
        + '<div class="main-wrapper">'
        + '<nav class="topbar d-flex align-items-center justify-content-between">'
        + '<div class="d-flex align-items-center"><button class="btn btn-light me-3" id="sidebarToggle" aria-label="Alternar sidebar"><i class="bi bi-list" aria-hidden="true"></i></button><h5 class="mb-0">Dashboard</h5></div>'
        + '<div class="d-flex align-items-center"><div class="dropdown">'
        + '<button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">'
        + '<img src="https://placehold.co/40x40/667eea/white?text=A" class="rounded-circle me-2" width="40" height="40" alt="Avatar">Admin</button>'
        + '<ul class="dropdown-menu dropdown-menu-end">'
        + '<li><a class="dropdown-item" href="#">Perfil</a></li>'
        + '<li><a class="dropdown-item" href="#">Configuración</a></li>'
        + '<li><hr class="dropdown-divider"></li>'
        + '<li><a class="dropdown-item text-danger" href="#">Logout</a></li>'
        + '</ul></div></div></nav>'
        + '<main class="content-area">'
        + '<div class="row g-4 mb-4">'
        + '<div class="col-md-3"><div class="card dashboard-card"><div class="card-body"><h6 class="text-body-secondary">Usuarios</h6><h2 class="fw-bold mb-0">12,540</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card dashboard-card"><div class="card-body"><h6 class="text-body-secondary">Ventas</h6><h2 class="fw-bold mb-0">$24K</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card dashboard-card"><div class="card-body"><h6 class="text-body-secondary">Órdenes</h6><h2 class="fw-bold mb-0">1,240</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card dashboard-card"><div class="card-body"><h6 class="text-body-secondary">Tickets</h6><h2 class="fw-bold mb-0">87</h2></div></div></div>'
        + '</div>'
        + '<div class="card dashboard-card"><div class="card-body">'
        + '<div class="d-flex justify-content-between mb-4"><h5 class="fw-bold mb-0">Usuarios recientes</h5><button class="btn btn-primary">Nuevo Usuario</button></div>'
        + '<div class="table-responsive"><table class="table align-middle mb-0">'
        + '<thead><tr><th scope="col">Usuario</th><th scope="col">Email</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>'
        + '<tbody>'
        + '<tr><td><div class="d-flex align-items-center"><img src="https://placehold.co/45x45/667eea/white?text=JP" class="rounded-circle me-2" width="45" height="45" alt="Avatar">Juan Pérez</div></td><td>juan@email.com</td><td><span class="badge text-bg-success">Activo</span></td><td><button class="btn btn-sm btn-light me-1">Editar</button><button class="btn btn-sm btn-danger">Eliminar</button></td></tr>'
        + '<tr><td><div class="d-flex align-items-center"><img src="https://placehold.co/45x45/764ba2/white?text=ML" class="rounded-circle me-2" width="45" height="45" alt="Avatar">María López</div></td><td>maria@email.com</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td><td><button class="btn btn-sm btn-light me-1">Editar</button><button class="btn btn-sm btn-danger">Eliminar</button></td></tr>'
        + '</tbody></table></div></div></div>'
        + '</main></div></div>'
        + '<script>setTimeout(()=>{const s=document.querySelector(".sidebar"),t=document.querySelector("#sidebarToggle"),o=document.querySelector(".sidebar-overlay");if(t)t.addEventListener("click",()=>{s.classList.toggle("active");o.classList.toggle("active")});if(o)o.addEventListener("click",()=>{s.classList.remove("active");o.classList.remove("active")})},500);</script>'
});

console.log('[template] ✓ Admin Dashboard modernizado con Bootstrap 5.3.3');
})();