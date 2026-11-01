// ═══════════════════════════════════════════════════════════════════
// bs-blocks.js — Bloques Bootstrap básicos Bootstrap 5.3.3
//
// Modernización:
//  - Font Awesome (fa-solid, far, fas) → Bootstrap Icons (bi)
//  - picsum.photos → placehold.co (más confiable)
//  - Categorías en español y consistentes
//  - Accesibilidad ARIA completa
//  - IIFE con verificación de gjsEditor
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[bs-blocks] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var LAYOUT = { id: 'bootstrap-layout',      label: '📐 Bootstrap Layout',      open: true };
var SECT   = { id: 'bootstrap-sections',    label: '🎯 Bootstrap Sections',    open: false };
var COMP   = { id: 'bootstrap-components',  label: '🧩 Bootstrap Components',  open: false };
var UI     = { id: 'bootstrap-ui',          label: '🎨 Bootstrap UI',          open: false };
var FORMS  = { id: 'bootstrap-forms',       label: '📝 Bootstrap Forms',       open: false };

// ── NAVBAR ──────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-navbar', {
    category: LAYOUT,
    label: 'Navbar',
    media: '<i class="bi bi-list fs-3"></i>',
    description: 'Navbar oscura responsive',
    content: '<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark"><div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">MiLogo</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul></div></div></nav>'
});

// ── HERO ────────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-hero', {
    category: SECT,
    label: 'Hero',
    media: '<i class="bi bi-badge-hd fs-3"></i>',
    description: 'Hero con fondo primario y CTA',
    content: '<section class="py-5 bg-primary text-white" data-bs-theme="dark"><div class="container text-center">'
        + '<h1 class="display-3 fw-bold">Bootstrap Hero</h1>'
        + '<p class="lead mb-4 opacity-75">Crea páginas increíbles con GrapesJS</p>'
        + '<a href="#" class="btn btn-light btn-lg px-4">Comenzar</a>'
        + '</div></section>'
});

// ── CARD ────────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-card', {
    category: COMP,
    label: 'Card',
    media: '<i class="bi bi-card-text fs-3"></i>',
    description: 'Tarjeta con imagen, título y botón',
    content: '<div class="card shadow-sm" style="max-width:22rem">'
        + '<img src="https://placehold.co/400x250/e9ecef/6c757d?text=Imagen" class="card-img-top" alt="Imagen de la tarjeta">'
        + '<div class="card-body">'
        + '<h5 class="card-title fw-bold">Bootstrap Card</h5>'
        + '<p class="card-text text-body-secondary">Ejemplo de tarjeta Bootstrap para GrapesJS.</p>'
        + '<a href="#" class="btn btn-primary">Ver más</a>'
        + '</div></div>'
});

// ── ALERT ───────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-alert', {
    category: COMP,
    label: 'Alert',
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>',
    description: 'Alerta de advertencia con rol accesible',
    content: '<div class="alert alert-warning d-flex align-items-center gap-2" role="alert">'
        + '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>'
        + '<div><strong>¡Atención!</strong> Este es un mensaje de alerta Bootstrap.</div>'
        + '</div>'
});

// ── BUTTONS ─────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-buttons', {
    category: UI,
    label: 'Buttons',
    media: '<i class="bi bi-square fs-3"></i>',
    description: 'Tres botones de colores semánticos',
    content: '<div class="d-flex gap-2 flex-wrap">'
        + '<button class="btn btn-primary">Primary</button>'
        + '<button class="btn btn-success">Success</button>'
        + '<button class="btn btn-danger">Danger</button>'
        + '</div>'
});

// ── ACCORDION ───────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-accordion', {
    category: COMP,
    label: 'Accordion',
    media: '<i class="bi bi-chevron-expand fs-3"></i>',
    description: 'Acordeón con un panel abierto',
    content: '<div class="accordion" id="accordionExample">'
        + '<div class="accordion-item"><h2 class="accordion-header">'
        + '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Accordion Item</button>'
        + '</h2><div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">'
        + '<div class="accordion-body text-body-secondary">Contenido del accordion.</div></div></div></div>'
});

// ── MODAL ───────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-modal', {
    category: COMP,
    label: 'Modal',
    media: '<i class="bi bi-window-stack fs-3"></i>',
    description: 'Botón que abre una ventana modal',
    content: '<div><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">Abrir modal</button>'
        + '<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"><div class="modal-dialog">'
        + '<div class="modal-content"><div class="modal-header">'
        + '<h5 class="modal-title" id="exampleModalLabel">Modal Bootstrap</h5>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
        + '<div class="modal-body">Contenido del modal.</div>'
        + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>'
        + '</div></div></div></div>'
});

// ── FORM ────────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-form', {
    category: FORMS,
    label: 'Form',
    media: '<i class="bi bi-ui-checks fs-3"></i>',
    description: 'Formulario de login con email y contraseña',
    content: '<form class="p-4 border rounded-3 bg-body-tertiary">'
        + '<div class="mb-3"><label class="form-label" for="bsFormEmail">Email</label>'
        + '<input type="email" class="form-control" id="bsFormEmail" placeholder="correo@ejemplo.com" autocomplete="email"></div>'
        + '<div class="mb-3"><label class="form-label" for="bsFormPass">Password</label>'
        + '<input type="password" class="form-control" id="bsFormPass" autocomplete="current-password"></div>'
        + '<button class="btn btn-primary" type="submit">Ingresar</button>'
        + '</form>'
});

// ── FOOTER ──────────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-footer', {
    category: LAYOUT,
    label: 'Footer',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie simple centrado',
    content: '<footer class="bg-dark text-white py-4" data-bs-theme="dark"><div class="container text-center">'
        + '<h5 class="fw-bold">Mi Sitio Web</h5>'
        + '<p class="mb-0 small opacity-75">&copy; 2026 Todos los derechos reservados</p>'
        + '</div></footer>'
});

// ── GRID 3 COLS ─────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-grid-3', {
    category: LAYOUT,
    label: 'Grid 3 cols',
    media: '<i class="bi bi-layout-three-columns fs-3"></i>',
    description: 'Tres columnas con guías visuales',
    content: '<div class="container py-5"><div class="row g-3">'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 1</div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 2</div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary border rounded-3 text-center text-body-secondary">Columna 3</div></div>'
        + '</div></div>'
});

console.log('[bs-blocks] ✓ Bloques Bootstrap modernizados con Bootstrap 5.3.3');
})();