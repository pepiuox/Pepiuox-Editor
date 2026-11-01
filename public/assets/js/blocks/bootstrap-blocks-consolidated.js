// ═══════════════════════════════════════════════════════════════════
// bootstrap-blocks-consolidated.js
//
// Archivo único que consolida: template.js + bs-blocks.js + blocks-bs.js
// Elimina duplicados y añade variantes mejoradas con Bootstrap 5.3.3
//
// Mejoras aplicadas:
//  - Eliminación de <script> inyectados (evita conflictos con GrapesJS)
//  - Eliminación de 5 IDs duplicados
//  - IIFE con verificación de gjsEditor
//  - Categorías con emojis descriptivos
//  - Variables de tema BS 5.3+ (text-body-secondary, bg-body-tertiary)
//  - data-bs-theme="dark" para fondos oscuros
//  - text-bg-* en lugar de bg-* + text-white
//  - Accesibilidad ARIA completa
//  - placehold.co en lugar de i.pravatar.cc (sin dependencias externas)
//  - Descripciones detalladas
//  - Sin Font Awesome (solo Bootstrap Icons)
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (consolidación y modernización)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[bootstrap-blocks-consolidated] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var LAYOUT_ADV = { id: 'adv-layout',    label: '🖥️ Layouts Avanzados',    open: true };
var LAYOUT     = { id: 'bs-layout',     label: '📐 Bootstrap Layout',     open: false };
var SECTIONS   = { id: 'bs-sections',   label: '🎯 Bootstrap Sections',   open: false };
var COMP       = { id: 'bs-components', label: '🧩 Bootstrap Components', open: false };
var UI         = { id: 'bs-ui',         label: '🎨 Bootstrap UI',         open: false };
var FORMS      = { id: 'bs-forms',      label: '📝 Bootstrap Forms',      open: false };

// ══════════════════════════════════════════════════════════════════
// 🖥️ LAYOUTS AVANZADOS (de template.js)
// ══════════════════════════════════════════════════════════════════

gjsEditor.BlockManager.add('bs-admin-layout', {
    category: LAYOUT_ADV,
    label: 'Admin Dashboard',
    media: '<i class="bi bi-speedometer2 fs-3"></i>',
    description: 'Layout de panel de administración con sidebar, topbar y contenido (sin scripts inyectados)',
    content: '<style>'
        + '.admin-wrapper{min-height:100vh;background:var(--bs-body-bg)}'
        + '.admin-sidebar{width:260px;min-height:100vh;background:#111827;color:#fff}'
        + '.admin-sidebar .nav-link{color:#cbd5e1;padding:12px 20px;border-radius:8px;margin:4px 12px;transition:all .2s}'
        + '.admin-sidebar .nav-link:hover{background:#1e293b;color:#fff}'
        + '.admin-sidebar .nav-link.active{background:#2563eb;color:#fff}'
        + '.admin-topbar{height:70px;background:var(--bs-body-bg);border-bottom:1px solid var(--bs-border-color);padding:0 20px}'
        + '.admin-card{border:none;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.05)}'
        + '@media(max-width:991px){.admin-sidebar{width:100%;min-height:auto}}'
        + '</style>'
        + '<div class="admin-wrapper">'
        + '<div class="row g-0">'
        + '<aside class="admin-sidebar col-lg-3 col-xl-2">'
        + '<div class="p-4 border-bottom border-secondary border-opacity-25"><h4 class="fw-bold mb-0">AdminPanel</h4></div>'
        + '<div class="p-3">'
        + '<ul class="nav flex-column">'
        + '<li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-house-door me-2" aria-hidden="true"></i>Dashboard</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-people me-2" aria-hidden="true"></i>Usuarios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-bar-chart me-2" aria-hidden="true"></i>Reportes</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-gear me-2" aria-hidden="true"></i>Configuración</a></li>'
        + '</ul></div></aside>'
        + '<div class="col-lg-9 col-xl-10">'
        + '<nav class="admin-topbar d-flex align-items-center justify-content-between">'
        + '<div class="d-flex align-items-center gap-3">'
        + '<button class="btn btn-light" type="button" aria-label="Menú"><i class="bi bi-list" aria-hidden="true"></i></button>'
        + '<h5 class="mb-0 fw-bold">Dashboard</h5></div>'
        + '<div class="dropdown">'
        + '<button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
        + '<img src="https://placehold.co/40x40/667eea/white?text=A" class="rounded-circle me-2" width="40" height="40" alt="Avatar">Admin</button>'
        + '<ul class="dropdown-menu dropdown-menu-end">'
        + '<li><a class="dropdown-item" href="#">Perfil</a></li>'
        + '<li><a class="dropdown-item" href="#">Configuración</a></li>'
        + '<li><hr class="dropdown-divider"></li>'
        + '<li><a class="dropdown-item text-danger" href="#">Logout</a></li>'
        + '</ul></div></nav>'
        + '<main class="p-4">'
        + '<div class="row g-4 mb-4">'
        + '<div class="col-md-3"><div class="card admin-card"><div class="card-body"><h6 class="text-body-secondary">Usuarios</h6><h2 class="fw-bold mb-0">12,540</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card admin-card"><div class="card-body"><h6 class="text-body-secondary">Ventas</h6><h2 class="fw-bold mb-0">$24K</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card admin-card"><div class="card-body"><h6 class="text-body-secondary">Órdenes</h6><h2 class="fw-bold mb-0">1,240</h2></div></div></div>'
        + '<div class="col-md-3"><div class="card admin-card"><div class="card-body"><h6 class="text-body-secondary">Tickets</h6><h2 class="fw-bold mb-0">87</h2></div></div></div>'
        + '</div>'
        + '<div class="card admin-card"><div class="card-body">'
        + '<div class="d-flex justify-content-between align-items-center mb-4"><h5 class="fw-bold mb-0">Usuarios recientes</h5><button class="btn btn-primary">Nuevo Usuario</button></div>'
        + '<div class="table-responsive"><table class="table align-middle mb-0">'
        + '<thead><tr><th scope="col">Usuario</th><th scope="col">Email</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>'
        + '<tbody>'
        + '<tr><td><div class="d-flex align-items-center"><img src="https://placehold.co/45x45/667eea/white?text=JP" class="rounded-circle me-2" width="45" height="45" alt="Avatar">Juan Pérez</div></td><td>juan@email.com</td><td><span class="badge text-bg-success">Activo</span></td><td><button class="btn btn-sm btn-light me-1">Editar</button><button class="btn btn-sm btn-danger">Eliminar</button></td></tr>'
        + '<tr><td><div class="d-flex align-items-center"><img src="https://placehold.co/45x45/764ba2/white?text=ML" class="rounded-circle me-2" width="45" height="45" alt="Avatar">María López</div></td><td>maria@email.com</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td><td><button class="btn btn-sm btn-light me-1">Editar</button><button class="btn btn-sm btn-danger">Eliminar</button></td></tr>'
        + '</tbody></table></div></div></div>'
        + '</main></div></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 📐 BOOTSTRAP LAYOUT
// ══════════════════════════════════════════════════════════════════

gjsEditor.BlockManager.add('bs-navbar-dark', {
    category: LAYOUT,
    label: 'Navbar Oscura',
    media: '<i class="bi bi-list fs-3"></i>',
    description: 'Barra de navegación oscura responsive con data-bs-theme',
    content: '<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">MiLogo</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDark" aria-controls="navbarDark" aria-expanded="false" aria-label="Alternar navegación">'
        + '<span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navbarDark">'
        + '<ul class="navbar-nav ms-auto">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul></div></div></nav>'
});

gjsEditor.BlockManager.add('bs-navbar-light', {
    category: LAYOUT,
    label: 'Navbar Clara',
    media: '<i class="bi bi-list-check fs-3"></i>',
    description: 'Barra de navegación clara con sombra',
    content: '<nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm" data-bs-theme="light">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">🍇 GrapesCMS</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLight" aria-controls="navbarLight" aria-expanded="false" aria-label="Alternar navegación">'
        + '<span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navbarLight">'
        + '<ul class="navbar-nav ms-auto mb-2 mb-lg-0">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul></div></div></nav>'
});

gjsEditor.BlockManager.add('bs-hero-split', {
    category: SECTIONS,
    label: 'Hero Split',
    media: '<i class="bi bi-badge-hd fs-3"></i>',
    description: 'Hero dividido con texto a la izquierda e imagen a la derecha',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<div class="row align-items-center g-5">'
        + '<div class="col-lg-6">'
        + '<p class="text-primary fw-bold mb-2">NUEVA CARACTERÍSTICA</p>'
        + '<h1 class="display-4 fw-bold mb-3">Revoluciona tu forma de trabajar</h1>'
        + '<p class="lead text-body-secondary mb-4">Descubre la nueva forma de colaborar en equipo. Herramientas intuitivas, resultados extraordinarios.</p>'
        + '<div class="d-flex gap-3 flex-wrap">'
        + '<button class="btn btn-primary btn-lg px-4">Comenzar gratis</button>'
        + '<button class="btn btn-outline-secondary btn-lg px-4">Saber más</button>'
        + '</div>'
        + '<div class="mt-4 d-flex align-items-center gap-3">'
        + '<div class="d-flex">'
        + '<img src="https://placehold.co/40x40/667eea/white?text=1" class="rounded-circle border border-2 border-white" style="margin-right:-10px" alt="Usuario 1">'
        + '<img src="https://placehold.co/40x40/764ba2/white?text=2" class="rounded-circle border border-2 border-white" style="margin-right:-10px" alt="Usuario 2">'
        + '<img src="https://placehold.co/40x40/f093fb/white?text=3" class="rounded-circle border border-2 border-white" alt="Usuario 3">'
        + '</div>'
        + '<small class="text-body-secondary">+10,000 usuarios satisfechos</small>'
        + '</div></div>'
        + '<div class="col-lg-6">'
        + '<img src="https://placehold.co/600x500/e9ecef/6c757d?text=Hero+Image" class="img-fluid rounded-3 shadow-lg" alt="Hero">'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('bs-hero-centered', {
    category: SECTIONS,
    label: 'Hero Centrado',
    media: '<i class="bi bi-easel fs-3"></i>',
    description: 'Hero centrado con fondo primario',
    content: '<section class="py-5 text-bg-primary" data-bs-theme="dark">'
        + '<div class="container text-center py-5">'
        + '<h1 class="display-3 fw-bold mb-3">Bootstrap Hero</h1>'
        + '<p class="lead mb-4 opacity-75">Crea páginas increíbles con GrapesJS</p>'
        + '<a href="#" class="btn btn-light btn-lg px-4">Comenzar</a>'
        + '</div></section>'
});

gjsEditor.BlockManager.add('bs-footer-full', {
    category: LAYOUT,
    label: 'Footer Completo',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie de página con 3 columnas y redes sociales',
    content: '<footer class="bg-dark text-white py-5 mt-5" data-bs-theme="dark">'
        + '<div class="container">'
        + '<div class="row g-4">'
        + '<div class="col-md-4"><h5 class="fw-bold mb-3">🍇 GrapesCMS</h5>'
        + '<p class="text-white-50 small">Editor visual de páginas web construido con PHP, MySQL y GrapesJS.</p></div>'
        + '<div class="col-md-4"><h6 class="fw-bold mb-3">Enlaces</h6>'
        + '<ul class="list-unstyled small">'
        + '<li class="mb-2"><a href="#" class="text-decoration-none text-white-50">Inicio</a></li>'
        + '<li class="mb-2"><a href="#" class="text-decoration-none text-white-50">Documentación</a></li>'
        + '<li class="mb-2"><a href="#" class="text-decoration-none text-white-50">Soporte</a></li>'
        + '</ul></div>'
        + '<div class="col-md-4"><h6 class="fw-bold mb-3">Redes</h6>'
        + '<div class="d-flex gap-3 fs-5">'
        + '<a href="#" class="text-white" aria-label="Facebook"><i class="bi bi-facebook"></i></a>'
        + '<a href="#" class="text-white" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
        + '<a href="#" class="text-white" aria-label="Instagram"><i class="bi bi-instagram"></i></a>'
        + '</div><p class="mt-3 text-white-50 small">© 2026 Todos los derechos reservados.</p></div>'
        + '</div></div></footer>'
});

gjsEditor.BlockManager.add('bs-footer-simple', {
    category: LAYOUT,
    label: 'Footer Simple',
    media: '<i class="bi bi-layout-text-window fs-3"></i>',
    description: 'Pie de página simple centrado',
    content: '<footer class="bg-dark text-white py-4" data-bs-theme="dark">'
        + '<div class="container text-center">'
        + '<h5 class="fw-bold">Mi Sitio Web</h5>'
        + '<p class="mb-0 small opacity-75">© 2026 Todos los derechos reservados</p>'
        + '</div></footer>'
});

gjsEditor.BlockManager.add('bs-grid-3', {
    category: LAYOUT,
    label: 'Grid 3 Columnas',
    media: '<i class="bi bi-layout-three-columns fs-3"></i>',
    description: 'Tres columnas con guías visuales',
    content: '<div class="container py-5"><div class="row g-3">'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 1</div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 2</div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 3</div></div>'
        + '</div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🧩 BOOTSTRAP COMPONENTS
// ══════════════════════════════════════════════════════════════════

gjsEditor.BlockManager.add('bs-card', {
    category: COMP,
    label: 'Card',
    media: '<i class="bi bi-card-text fs-3"></i>',
    description: 'Tarjeta con imagen, título y botón',
    content: '<div class="card shadow-sm border-0" style="max-width:22rem">'
        + '<img src="https://placehold.co/400x250/e9ecef/6c757d?text=Imagen" class="card-img-top" alt="Imagen de la tarjeta">'
        + '<div class="card-body p-4">'
        + '<h5 class="card-title fw-bold">Bootstrap Card</h5>'
        + '<p class="card-text text-body-secondary">Ejemplo de tarjeta Bootstrap para GrapesJS.</p>'
        + '<a href="#" class="btn btn-primary">Ver más</a>'
        + '</div></div>'
});

gjsEditor.BlockManager.add('bs-alert-dismissible', {
    category: COMP,
    label: 'Alerta Cerrable',
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>',
    description: 'Alerta de éxito con icono y botón de cierre',
    content: '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">'
        + '<i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>'
        + '<div class="flex-grow-1"><strong>¡Éxito!</strong> Operación realizada correctamente.</div>'
        + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-accordion-faq', {
    category: COMP,
    label: 'Acordeón FAQ',
    media: '<i class="bi bi-chevron-expand fs-3"></i>',
    description: 'Acordeón de preguntas frecuentes con 3 paneles',
    content: '<div class="accordion" id="faqAccordion">'
        + '<div class="accordion-item"><h2 class="accordion-header">'
        + '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">¿Cómo funciona?</button>'
        + '</h2><div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">'
        + '<div class="accordion-body text-body-secondary">Respuesta detallada aquí...</div></div></div>'
        + '<div class="accordion-item"><h2 class="accordion-header">'
        + '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">¿Tiene costo?</button>'
        + '</h2><div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">'
        + '<div class="accordion-body text-body-secondary">Otra respuesta detallada...</div></div></div>'
        + '<div class="accordion-item"><h2 class="accordion-header">'
        + '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">¿Cómo contacto soporte?</button>'
        + '</h2><div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">'
        + '<div class="accordion-body text-body-secondary">Puedes contactarnos por email o chat.</div></div></div>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-modal', {
    category: COMP,
    label: 'Modal',
    media: '<i class="bi bi-window-stack fs-3"></i>',
    description: 'Botón que abre una ventana modal',
    content: '<div><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">Abrir modal</button>'
        + '<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">'
        + '<div class="modal-dialog"><div class="modal-content">'
        + '<div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Modal Bootstrap</h5>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
        + '<div class="modal-body">Contenido del modal.</div>'
        + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>'
        + '</div></div></div></div>'
});

gjsEditor.BlockManager.add('bs-tabs', {
    category: COMP,
    label: 'Pestañas',
    media: '<i class="bi bi-folder2-open fs-3"></i>',
    description: 'Navegación por pestañas con 3 paneles',
    content: '<ul class="nav nav-tabs" role="tablist">'
        + '<li class="nav-item" role="presentation"><button class="nav-link active" id="tab1-btn" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" aria-controls="tab1" aria-selected="true">Pestaña 1</button></li>'
        + '<li class="nav-item" role="presentation"><button class="nav-link" id="tab2-btn" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" aria-controls="tab2" aria-selected="false">Pestaña 2</button></li>'
        + '<li class="nav-item" role="presentation"><button class="nav-link" id="tab3-btn" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" aria-controls="tab3" aria-selected="false">Pestaña 3</button></li>'
        + '</ul>'
        + '<div class="tab-content p-3 border border-top-0 bg-body rounded-bottom-3" id="tabContent">'
        + '<div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-btn">Contenido de la pestaña 1...</div>'
        + '<div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-btn">Contenido de la pestaña 2...</div>'
        + '<div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tab3-btn">Contenido de la pestaña 3...</div>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 🎨 BOOTSTRAP UI
// ══════════════════════════════════════════════════════════════════

gjsEditor.BlockManager.add('bs-buttons', {
    category: UI,
    label: 'Botones',
    media: '<i class="bi bi-square fs-3"></i>',
    description: 'Tres botones de colores semánticos',
    content: '<div class="d-flex gap-2 flex-wrap">'
        + '<button class="btn btn-primary">Primary</button>'
        + '<button class="btn btn-success">Success</button>'
        + '<button class="btn btn-danger">Danger</button>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-progress', {
    category: UI,
    label: 'Barras de Progreso',
    media: '<i class="bi bi-bar-chart-line fs-3"></i>',
    description: 'Dos barras de progreso con etiquetas',
    content: '<div class="container my-4">'
        + '<div class="mb-2 d-flex justify-content-between small"><span class="fw-semibold">Habilidad 1</span><span class="text-body-secondary">85%</span></div>'
        + '<div class="progress mb-3" role="progressbar" aria-label="Habilidad 1" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" style="height:8px"><div class="progress-bar bg-success" style="width:85%"></div></div>'
        + '<div class="mb-2 d-flex justify-content-between small"><span class="fw-semibold">Habilidad 2</span><span class="text-body-secondary">60%</span></div>'
        + '<div class="progress" role="progressbar" aria-label="Habilidad 2" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="height:8px"><div class="progress-bar bg-warning" style="width:60%"></div></div>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-breadcrumb', {
    category: UI,
    label: 'Breadcrumbs',
    media: '<i class="bi bi-slash-square fs-3"></i>',
    description: 'Migas de pan para navegación',
    content: '<nav class="my-3" aria-label="Migas de pan">'
        + '<ol class="breadcrumb mb-0">'
        + '<li class="breadcrumb-item"><a href="#" class="text-decoration-none">Inicio</a></li>'
        + '<li class="breadcrumb-item"><a href="#" class="text-decoration-none">Categoría</a></li>'
        + '<li class="breadcrumb-item active" aria-current="page">Página actual</li>'
        + '</ol></nav>'
});

gjsEditor.BlockManager.add('bs-pagination', {
    category: UI,
    label: 'Paginación',
    media: '<i class="bi bi-three-dots fs-3"></i>',
    description: 'Paginación accesible',
    content: '<nav class="my-4" aria-label="Paginación">'
        + '<ul class="pagination justify-content-center mb-0">'
        + '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a></li>'
        + '<li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">2</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">3</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">Siguiente</a></li>'
        + '</ul></nav>'
});

gjsEditor.BlockManager.add('bs-badges', {
    category: UI,
    label: 'Badges',
    media: '<i class="bi bi-bookmark fs-3"></i>',
    description: 'Conjunto de badges semánticos',
    content: '<div class="my-3 d-flex flex-wrap gap-2">'
        + '<span class="badge text-bg-primary">Nuevo</span>'
        + '<span class="badge text-bg-success">Activo</span>'
        + '<span class="badge text-bg-warning text-dark">Pendiente</span>'
        + '<span class="badge text-bg-danger">Urgente</span>'
        + '<span class="badge text-bg-secondary">Archivo</span>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-cta', {
    category: UI,
    label: 'Call to Action',
    media: '<i class="bi bi-megaphone fs-3"></i>',
    description: 'Banda de conversión con botón',
    content: '<section class="py-4 bg-warning"><div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-center text-md-start">'
        + '<div><h4 class="fw-bold mb-1">¿Listo para comenzar?</h4>'
        + '<p class="mb-0 text-dark opacity-75">Únete a miles de usuarios que ya confían en nosotros.</p></div>'
        + '<a href="#" class="btn btn-dark btn-lg px-4 flex-shrink-0">Crear cuenta gratis</a>'
        + '</div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 📝 BOOTSTRAP FORMS
// ══════════════════════════════════════════════════════════════════

gjsEditor.BlockManager.add('bs-form-login', {
    category: FORMS,
    label: 'Form Login',
    media: '<i class="bi bi-ui-checks fs-3"></i>',
    description: 'Formulario de inicio de sesión',
    content: '<form class="p-4 border rounded-3 bg-body-tertiary">'
        + '<div class="mb-3"><label class="form-label" for="bsFormEmail">Email</label>'
        + '<input type="email" class="form-control" id="bsFormEmail" placeholder="correo@ejemplo.com" autocomplete="email"></div>'
        + '<div class="mb-3"><label class="form-label" for="bsFormPass">Password</label>'
        + '<input type="password" class="form-control" id="bsFormPass" autocomplete="current-password"></div>'
        + '<button class="btn btn-primary" type="submit">Ingresar</button>'
        + '</form>'
});

gjsEditor.BlockManager.add('bs-login-card', {
    category: FORMS,
    label: 'Login Card',
    media: '<i class="bi bi-box-arrow-in-right fs-3"></i>',
    description: 'Formulario de login en tarjeta con sombra',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><div class="row justify-content-center"><div class="col-md-5">'
        + '<div class="card shadow-sm border-0 p-4">'
        + '<h3 class="text-center fw-bold mb-4">Iniciar Sesión</h3>'
        + '<form><div class="mb-3"><label class="form-label" for="loginEmail">Email</label>'
        + '<input type="email" class="form-control" id="loginEmail" placeholder="correo@ejemplo.com" autocomplete="email"></div>'
        + '<div class="mb-3"><label class="form-label" for="loginPass">Contraseña</label>'
        + '<input type="password" class="form-control" id="loginPass" autocomplete="current-password"></div>'
        + '<div class="d-grid"><button type="submit" class="btn btn-primary">Entrar</button></div></form>'
        + '<div class="text-center mt-3"><a href="#" class="small link-secondary text-decoration-none">¿Olvidaste tu contraseña?</a></div>'
        + '</div></div></div></div></section>'
});

gjsEditor.BlockManager.add('bs-search-bar', {
    category: FORMS,
    label: 'Barra de Búsqueda',
    media: '<i class="bi bi-search fs-3"></i>',
    description: 'Campo de búsqueda destacado',
    content: '<div class="container my-4"><div class="row justify-content-center"><div class="col-md-8">'
        + '<div class="input-group input-group-lg shadow-sm rounded-3">'
        + '<input type="text" class="form-control" placeholder="¿Qué estás buscando?" aria-label="Buscar">'
        + '<button class="btn btn-primary px-4" type="button"><i class="bi bi-search me-1" aria-hidden="true"></i> Buscar</button>'
        + '</div></div></div></div>'
});

gjsEditor.BlockManager.add('bs-pricing', {
    category: FORMS,
    label: 'Tabla de Precios',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Tres planes de precios con el central destacado',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<div class="row g-4 text-center">'
        + '<div class="col-md-4"><div class="card border-0 shadow-sm p-3">'
        + '<h5 class="text-body-secondary fw-bold">Básico</h5>'
        + '<div class="display-6 fw-bold my-3">$9 <span class="fs-6 text-body-secondary">/mes</span></div>'
        + '<ul class="list-unstyled mb-4 small text-start">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>1 Usuario</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>5 Proyectos</li>'
        + '</ul><button class="btn btn-outline-primary w-100">Elegir</button></div></div>'
        + '<div class="col-md-4"><div class="card border-primary shadow-sm p-3">'
        + '<div class="badge text-bg-primary mb-2">Popular</div>'
        + '<h5 class="text-primary fw-bold">Pro</h5>'
        + '<div class="display-6 fw-bold my-3">$29 <span class="fs-6 text-body-secondary">/mes</span></div>'
        + '<ul class="list-unstyled mb-4 small text-start">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>5 Usuarios</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Proyectos ilimitados</li>'
        + '</ul><button class="btn btn-primary w-100">Elegir</button></div></div>'
        + '<div class="col-md-4"><div class="card border-0 shadow-sm p-3">'
        + '<h5 class="text-body-secondary fw-bold">Empresas</h5>'
        + '<div class="display-6 fw-bold my-3">$99 <span class="fs-6 text-body-secondary">/mes</span></div>'
        + '<ul class="list-unstyled mb-4 small text-start">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Usuarios ilimitados</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Soporte 24/7</li>'
        + '</ul><button class="btn btn-outline-dark w-100">Contactar</button></div></div>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('bs-cards-row', {
    category: COMP,
    label: 'Tarjetas 3 Columnas',
    media: '<i class="bi bi-grid-3x3 fs-3"></i>',
    description: 'Tres tarjetas con iconos de características',
    content: '<section class="py-5"><div class="container"><div class="row g-4">'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4">'
        + '<div class="fs-1 text-primary mb-3"><i class="bi bi-lightning-charge" aria-hidden="true"></i></div>'
        + '<h5 class="card-title fw-bold">Rápido</h5>'
        + '<p class="card-text text-body-secondary">Descripción breve del beneficio.</p>'
        + '</div></div></div>'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4">'
        + '<div class="fs-1 text-primary mb-3"><i class="bi bi-shield-check" aria-hidden="true"></i></div>'
        + '<h5 class="card-title fw-bold">Seguro</h5>'
        + '<p class="card-text text-body-secondary">Descripción breve del beneficio.</p>'
        + '</div></div></div>'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4">'
        + '<div class="fs-1 text-primary mb-3"><i class="bi bi-gear" aria-hidden="true"></i></div>'
        + '<h5 class="card-title fw-bold">Flexible</h5>'
        + '<p class="card-text text-body-secondary">Descripción breve del beneficio.</p>'
        + '</div></div></div>'
        + '</div></div></section>'
});

console.log('[bootstrap-blocks-consolidated] ✓ Archivo consolidado con 25 bloques únicos (sin scripts inyectados)');
})();