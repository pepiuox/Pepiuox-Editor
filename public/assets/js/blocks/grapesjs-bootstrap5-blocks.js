// ═══════════════════════════════════════════════════════════════════
// grapesjs-bootstrap5-blocks.js — Bloques Bootstrap 5.3.3
//
// Adaptado desde la sintaxis de plugin ES module a IIFE con
// gjsEditor.BlockManager.add, consistente con el resto de archivos
// del proyecto.
//
// Modernizaciones aplicadas:
//  - bm.add → gjsEditor.BlockManager.add
//  - via.placeholder.com → placehold.co
//  - text-muted → text-body-secondary
//  - bg-light → bg-body-tertiary
//  - navbar-dark → data-bs-theme="dark"
//  - bg-success text-white → text-bg-success
//  - ARIA completo
//  - Categorías en español con emojis
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[grapesjs-bootstrap5-blocks] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var LAYOUT = { id: 'bs5-layout',      label: '📐 Layout BS5',      open: true };
var BASIC  = { id: 'bs5-basic',       label: '🧱 Básicos BS5',     open: false };
var COMP   = { id: 'bs5-components',  label: '🧩 Componentes BS5', open: false };
var FORMS  = { id: 'bs5-forms',       label: '📝 Formularios BS5', open: false };
var EXTRA  = { id: 'bs5-extra',       label: '🎨 Extras BS5',      open: false };

// ══════════════════════════════════════════════════════════════════
// 📐 LAYOUTS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('bs5-container', {
    category: LAYOUT,
    label: 'Contenedor',
    media: '<i class="bi bi-square fs-3"></i>',
    description: 'Container Bootstrap 5 centrado',
    content: '<div class="container py-3 border border-2 border-secondary-subtle rounded-3 text-center small text-body-secondary">Contenedor (container)</div>'
});

gjsEditor.BlockManager.add('bs5-container-fluid', {
    category: LAYOUT,
    label: 'Contenedor Fluido',
    media: '<i class="bi bi-arrows-expand fs-3"></i>',
    description: 'Container-fluid de ancho completo',
    content: '<div class="container-fluid py-3 border border-2 border-secondary-subtle rounded-3 text-center small text-body-secondary">Contenedor fluido</div>'
});

gjsEditor.BlockManager.add('bs5-row', {
    category: LAYOUT,
    label: 'Fila (Row)',
    media: '<i class="bi bi-layout-split fs-3"></i>',
    description: 'Fila con 2 columnas',
    content: '<div class="row g-3"><div class="col"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Columna 1</div></div><div class="col"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Columna 2</div></div></div>'
});

gjsEditor.BlockManager.add('bs5-row-3cols', {
    category: LAYOUT,
    label: 'Fila 3 Columnas',
    media: '<i class="bi bi-layout-three-columns fs-3"></i>',
    description: 'Fila con 3 columnas iguales',
    content: '<div class="row g-3"><div class="col-md-4"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 1</div></div><div class="col-md-4"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 2</div></div><div class="col-md-4"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 3</div></div></div>'
});

gjsEditor.BlockManager.add('bs5-row-4cols', {
    category: LAYOUT,
    label: 'Fila 4 Columnas',
    media: '<i class="bi bi-grid-3x3-gap fs-3"></i>',
    description: 'Fila con 4 columnas iguales',
    content: '<div class="row g-3"><div class="col-md-3"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 1</div></div><div class="col-md-3"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 2</div></div><div class="col-md-3"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 3</div></div><div class="col-md-3"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">Col 4</div></div></div>'
});

gjsEditor.BlockManager.add('bs5-sidebar-layout', {
    category: LAYOUT,
    label: 'Sidebar + Contenido',
    media: '<i class="bi bi-layout-sidebar fs-3"></i>',
    description: 'Layout con barra lateral y contenido principal',
    content: '<div class="row g-3"><div class="col-md-3"><div class="p-3 bg-body-tertiary border rounded-3"><h5 class="fw-bold mb-2">Sidebar</h5><p class="small text-body-secondary mb-0">Contenido lateral</p></div></div><div class="col-md-9"><div class="p-3 bg-body-tertiary border rounded-3"><h3 class="fw-bold">Contenido principal</h3><p class="text-body-secondary mb-0">Área de contenido...</p></div></div></div>'
});

gjsEditor.BlockManager.add('bs5-grid', {
    category: LAYOUT,
    label: 'Grid Personalizado',
    media: '<i class="bi bi-grid fs-3"></i>',
    description: 'Grid responsive de 6 celdas',
    content: '<div class="row g-3"><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">1</div></div><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">2</div></div><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">3</div></div><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">4</div></div><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">5</div></div><div class="col-6 col-md-4 col-lg-2"><div class="p-3 bg-body-tertiary border rounded-3 text-center small text-body-secondary">6</div></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🧱 BÁSICOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('bs5-button', {
    category: BASIC,
    label: 'Botón',
    media: '<i class="bi bi-hand-index fs-3"></i>',
    description: 'Botón primario',
    content: '<button class="btn btn-primary" type="button">Botón</button>'
});

gjsEditor.BlockManager.add('bs5-button-group', {
    category: BASIC,
    label: 'Grupo de Botones',
    media: '<i class="bi bi-ui-checks-grid fs-3"></i>',
    description: 'Tres botones agrupados',
    content: '<div class="btn-group" role="group" aria-label="Grupo de botones"><button type="button" class="btn btn-outline-primary">Uno</button><button type="button" class="btn btn-outline-primary">Dos</button><button type="button" class="btn btn-outline-primary">Tres</button></div>'
});

gjsEditor.BlockManager.add('bs5-link', {
    category: BASIC,
    label: 'Enlace',
    media: '<i class="bi bi-link-45deg fs-3"></i>',
    description: 'Enlace con estilo primario',
    content: '<a href="#" class="link-primary">Enlace de ejemplo</a>'
});

gjsEditor.BlockManager.add('bs5-heading', {
    category: BASIC,
    label: 'Título',
    media: '<i class="bi bi-type-h2 fs-3"></i>',
    description: 'Encabezado h2',
    content: '<h2 class="fw-bold">Título de sección</h2>'
});

gjsEditor.BlockManager.add('bs5-text', {
    category: BASIC,
    label: 'Texto',
    media: '<i class="bi bi-paragraph fs-3"></i>',
    description: 'Párrafo con estilo lead',
    content: '<p class="lead text-body-secondary">Párrafo de texto con estilo lead de Bootstrap.</p>'
});

gjsEditor.BlockManager.add('bs5-image', {
    category: BASIC,
    label: 'Imagen',
    media: '<i class="bi bi-image fs-3"></i>',
    description: 'Imagen responsive con bordes redondeados',
    content: '<img src="https://placehold.co/800x400/e9ecef/6c757d?text=Imagen" class="img-fluid rounded-3" alt="Imagen de ejemplo">'
});

gjsEditor.BlockManager.add('bs5-divider', {
    category: BASIC,
    label: 'Separador',
    media: '<i class="bi bi-hr fs-3"></i>',
    description: 'Línea horizontal separadora',
    content: '<hr class="my-4 opacity-25">'
});

gjsEditor.BlockManager.add('bs5-figure', {
    category: BASIC,
    label: 'Figura',
    media: '<i class="bi bi-card-image fs-3"></i>',
    description: 'Imagen con pie de foto',
    content: '<figure class="figure"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Figura" class="figure-img img-fluid rounded-3" alt="Figura"><figcaption class="figure-caption text-body-secondary text-center">Leyenda de la imagen</figcaption></figure>'
});

// ══════════════════════════════════════════════════════════════════
// 🧩 COMPONENTES
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('bs5-card', {
    category: COMP,
    label: 'Tarjeta',
    media: '<i class="bi bi-card-text fs-3"></i>',
    description: 'Card con imagen, título y botón',
    content: '<div class="card shadow-sm border-0"><img src="https://placehold.co/400x200/e9ecef/6c757d?text=Imagen" class="card-img-top" alt="Imagen de la tarjeta"><div class="card-body p-4"><h5 class="card-title fw-bold">Título de la tarjeta</h5><p class="card-text text-body-secondary">Contenido de ejemplo para la tarjeta de Bootstrap.</p><a href="#" class="btn btn-primary">Ir a algún lado</a></div></div>'
});

gjsEditor.BlockManager.add('bs5-card-horizontal', {
    category: COMP,
    label: 'Tarjeta Horizontal',
    media: '<i class="bi bi-layout-sidebar-inset fs-3"></i>',
    description: 'Card con imagen a la izquierda',
    content: '<div class="card shadow-sm border-0 mb-3"><div class="row g-0"><div class="col-md-4"><img src="https://placehold.co/200x200/e9ecef/6c757d?text=Img" class="img-fluid rounded-start" alt="Imagen"></div><div class="col-md-8"><div class="card-body p-4"><h5 class="card-title fw-bold">Tarjeta horizontal</h5><p class="card-text text-body-secondary">Texto descriptivo de la tarjeta.</p></div></div></div></div>'
});

gjsEditor.BlockManager.add('bs5-alert', {
    category: COMP,
    label: 'Alerta',
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>',
    description: 'Alerta cerrable con rol accesible',
    content: '<div class="alert alert-primary alert-dismissible fade show d-flex align-items-center" role="alert"><i class="bi bi-info-circle-fill me-2" aria-hidden="true"></i><div><strong>¡Atención!</strong> Este es un mensaje de alerta.</div><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>'
});

gjsEditor.BlockManager.add('bs5-badge', {
    category: COMP,
    label: 'Etiqueta (Badge)',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Badge con color semántico',
    content: '<span class="badge text-bg-primary">Nuevo</span>'
});

gjsEditor.BlockManager.add('bs5-list-group', {
    category: COMP,
    label: 'Lista',
    media: '<i class="bi bi-list-ul fs-3"></i>',
    description: 'List group con elementos activos y deshabilitados',
    content: '<ul class="list-group shadow-sm"><li class="list-group-item active" aria-current="true">Elemento activo</li><li class="list-group-item">Segundo elemento</li><li class="list-group-item">Tercer elemento</li><li class="list-group-item disabled" aria-disabled="true">Elemento deshabilitado</li></ul>'
});

gjsEditor.BlockManager.add('bs5-accordion', {
    category: COMP,
    label: 'Acordeón',
    media: '<i class="bi bi-chevron-expand fs-3"></i>',
    description: 'Acordeón con dos paneles',
    content: '<div class="accordion" id="accordionBS5"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Sección 1</button></h2><div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionBS5"><div class="accordion-body text-body-secondary">Contenido de la primera sección.</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Sección 2</button></h2><div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionBS5"><div class="accordion-body text-body-secondary">Contenido de la segunda sección.</div></div></div></div>'
});

gjsEditor.BlockManager.add('bs5-tabs', {
    category: COMP,
    label: 'Pestañas',
    media: '<i class="bi bi-folder2-open fs-3"></i>',
    description: 'Navegación por pestañas con 3 paneles',
    content: '<ul class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button class="nav-link active" id="tab1-btn" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" aria-controls="tab1" aria-selected="true">Pestaña 1</button></li><li class="nav-item" role="presentation"><button class="nav-link" id="tab2-btn" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" aria-controls="tab2" aria-selected="false">Pestaña 2</button></li><li class="nav-item" role="presentation"><button class="nav-link" id="tab3-btn" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" aria-controls="tab3" aria-selected="false">Pestaña 3</button></li></ul><div class="tab-content p-3 border border-top-0 bg-body rounded-bottom-3" id="tabContent"><div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-btn">Contenido de la pestaña 1</div><div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-btn">Contenido de la pestaña 2</div><div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tab3-btn">Contenido de la pestaña 3</div></div>'
});

gjsEditor.BlockManager.add('bs5-navbar', {
    category: COMP,
    label: 'Barra de Navegación',
    media: '<i class="bi bi-menu-button-wide fs-3"></i>',
    description: 'Navbar responsive con dropdown',
    content: '<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark"><div class="container-fluid"><a class="navbar-brand fw-bold" href="#">Marca</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav"><li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li><li class="nav-item"><a class="nav-link" href="#">Enlaces</a></li><li class="nav-item"><a class="nav-link" href="#">Servicios</a></li><li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">Más</a><ul class="dropdown-menu"><li><a class="dropdown-item" href="#">Opción 1</a></li><li><a class="dropdown-item" href="#">Opción 2</a></li></ul></li></ul></div></div></nav>'
});

gjsEditor.BlockManager.add('bs5-hero', {
    category: COMP,
    label: 'Hero Section',
    media: '<i class="bi bi-badge-hd fs-3"></i>',
    description: 'Hero con título, descripción y botón',
    content: '<div class="p-5 mb-4 bg-body-tertiary rounded-3"><div class="container-fluid py-5"><h1 class="display-5 fw-bold">Título principal</h1><p class="col-md-8 fs-4 text-body-secondary">Descripción breve del hero section con un mensaje atractivo para el usuario.</p><button class="btn btn-primary btn-lg" type="button">Acción principal</button></div></div>'
});

gjsEditor.BlockManager.add('bs5-modal', {
    category: COMP,
    label: 'Modal',
    media: '<i class="bi bi-window-stack fs-3"></i>',
    description: 'Botón que abre una ventana modal',
    content: '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Abrir modal</button><div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Título del modal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><p class="mb-0">Contenido del modal...</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary">Guardar</button></div></div></div></div>'
});

gjsEditor.BlockManager.add('bs5-breadcrumb', {
    category: COMP,
    label: 'Breadcrumb',
    media: '<i class="bi bi-chevron-right fs-3"></i>',
    description: 'Migas de pan con elemento actual marcado',
    content: '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="#" class="text-decoration-none">Inicio</a></li><li class="breadcrumb-item"><a href="#" class="text-decoration-none">Categoría</a></li><li class="breadcrumb-item active" aria-current="page">Página actual</li></ol></nav>'
});

gjsEditor.BlockManager.add('bs5-pagination', {
    category: COMP,
    label: 'Paginación',
    media: '<i class="bi bi-three-dots fs-3"></i>',
    description: 'Paginación accesible',
    content: '<nav aria-label="Paginación"><ul class="pagination mb-0"><li class="page-item"><a class="page-link" href="#" aria-label="Anterior">«</a></li><li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li><li class="page-item"><a class="page-link" href="#">2</a></li><li class="page-item"><a class="page-link" href="#">3</a></li><li class="page-item"><a class="page-link" href="#" aria-label="Siguiente">»</a></li></ul></nav>'
});

gjsEditor.BlockManager.add('bs5-spinner', {
    category: COMP,
    label: 'Spinner',
    media: '<i class="bi bi-arrow-repeat fs-3"></i>',
    description: 'Indicador de carga',
    content: '<div class="d-flex justify-content-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>'
});

gjsEditor.BlockManager.add('bs5-progress', {
    category: COMP,
    label: 'Barra de Progreso',
    media: '<i class="bi bi-bar-chart-steps fs-3"></i>',
    description: 'Barra de progreso al 65%',
    content: '<div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="height:10px"><div class="progress-bar bg-success" style="width:65%">65%</div></div>'
});

gjsEditor.BlockManager.add('bs5-table', {
    category: COMP,
    label: 'Tabla',
    media: '<i class="bi bi-table fs-3"></i>',
    description: 'Tabla con filas alternas y hover',
    content: '<div class="table-responsive rounded-3 shadow-sm"><table class="table table-striped table-hover align-middle mb-0"><thead class="table-light"><tr><th scope="col">#</th><th scope="col">Nombre</th><th scope="col">Email</th><th scope="col" class="text-center">Acciones</th></tr></thead><tbody><tr><th scope="row">1</th><td>Juan Pérez</td><td>juan@ejemplo.com</td><td class="text-center"><button class="btn btn-sm btn-primary">Editar</button></td></tr><tr><th scope="row">2</th><td>María López</td><td>maria@ejemplo.com</td><td class="text-center"><button class="btn btn-sm btn-primary">Editar</button></td></tr></tbody></table></div>'
});

// ══════════════════════════════════════════════════════════════════
// 📝 FORMULARIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('bs5-form', {
    category: FORMS,
    label: 'Formulario',
    media: '<i class="bi bi-ui-checks fs-3"></i>',
    description: 'Formulario completo con validación visual',
    content: '<form class="row g-3"><div class="col-md-6"><label class="form-label" for="bs5Nombre">Nombre</label><input type="text" class="form-control" id="bs5Nombre" placeholder="Tu nombre" autocomplete="name"></div><div class="col-md-6"><label class="form-label" for="bs5Apellido">Apellido</label><input type="text" class="form-control" id="bs5Apellido" placeholder="Tu apellido" autocomplete="family-name"></div><div class="col-12"><label class="form-label" for="bs5Email">Email</label><input type="email" class="form-control" id="bs5Email" placeholder="correo@ejemplo.com" autocomplete="email"></div><div class="col-12"><label class="form-label" for="bs5Msg">Mensaje</label><textarea class="form-control" id="bs5Msg" rows="3"></textarea></div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="terms"><label class="form-check-label" for="terms">Acepto los términos</label></div></div><div class="col-12"><button type="submit" class="btn btn-primary">Enviar</button></div></form>'
});

gjsEditor.BlockManager.add('bs5-input', {
    category: FORMS,
    label: 'Input',
    media: '<i class="bi bi-input-cursor-text fs-3"></i>',
    description: 'Campo de texto con etiqueta y ayuda',
    content: '<div class="mb-3"><label class="form-label" for="bs5Input">Etiqueta</label><input type="text" class="form-control" id="bs5Input" placeholder="Placeholder"><div class="form-text">Texto de ayuda opcional.</div></div>'
});

gjsEditor.BlockManager.add('bs5-select', {
    category: FORMS,
    label: 'Select',
    media: '<i class="bi bi-menu-app fs-3"></i>',
    description: 'Lista desplegable con etiqueta',
    content: '<div class="mb-3"><label class="form-label" for="bs5Select">Selecciona una opción</label><select class="form-select" id="bs5Select"><option selected>Elige...</option><option value="1">Opción 1</option><option value="2">Opción 2</option><option value="3">Opción 3</option></select></div>'
});

gjsEditor.BlockManager.add('bs5-checkbox', {
    category: FORMS,
    label: 'Checkbox',
    media: '<i class="bi bi-check2-square fs-3"></i>',
    description: 'Casilla de verificación',
    content: '<div class="form-check"><input class="form-check-input" type="checkbox" id="check1"><label class="form-check-label" for="check1">Opción de checkbox</label></div>'
});

gjsEditor.BlockManager.add('bs5-radio', {
    category: FORMS,
    label: 'Radio',
    media: '<i class="bi bi-record-circle fs-3"></i>',
    description: 'Dos botones de opción',
    content: '<div class="form-check"><input class="form-check-input" type="radio" name="radioGroup" id="radio1" checked><label class="form-check-label" for="radio1">Opción 1</label></div><div class="form-check"><input class="form-check-input" type="radio" name="radioGroup" id="radio2"><label class="form-check-label" for="radio2">Opción 2</label></div>'
});

gjsEditor.BlockManager.add('bs5-switch', {
    category: FORMS,
    label: 'Switch',
    media: '<i class="bi bi-toggle-on fs-3"></i>',
    description: 'Interruptor activado',
    content: '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="switch1" checked><label class="form-check-label" for="switch1">Interruptor activado</label></div>'
});

gjsEditor.BlockManager.add('bs5-input-group', {
    category: FORMS,
    label: 'Grupo de Input',
    media: '<i class="bi bi-input-cursor fs-3"></i>',
    description: 'Campo con prefijo adjunto',
    content: '<div class="input-group mb-3"><span class="input-group-text">@</span><input type="text" class="form-control" placeholder="Usuario" aria-label="Nombre de usuario"></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🎨 EXTRAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('bs5-pricing-card', {
    category: EXTRA,
    label: 'Tarjeta de Precios',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Card de precios con plan destacado',
    content: '<div class="card text-center shadow-sm border-0"><div class="card-header text-bg-primary"><h4 class="my-0 fw-normal">Plan Pro</h4></div><div class="card-body p-4"><h1 class="card-title pricing-card-title">$29<small class="text-body-secondary">/mes</small></h1><ul class="list-unstyled mt-3 mb-4"><li>10 usuarios</li><li>2 GB de almacenamiento</li><li>Soporte por email</li><li>Acceso a la comunidad</li></ul><button type="button" class="w-100 btn btn-lg btn-primary">Contratar</button></div></div>'
});

gjsEditor.BlockManager.add('bs5-footer', {
    category: EXTRA,
    label: 'Footer',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie de página con 3 columnas',
    content: '<footer class="bg-dark text-white py-5" data-bs-theme="dark"><div class="container"><div class="row g-4"><div class="col-md-4"><h5 class="fw-bold">Empresa</h5><p class="text-white-50 small">Descripción breve de la empresa.</p></div><div class="col-md-4"><h5 class="fw-bold">Enlaces</h5><ul class="list-unstyled small"><li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Inicio</a></li><li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Servicios</a></li><li><a href="#" class="text-white-50 text-decoration-none">Contacto</a></li></ul></div><div class="col-md-4"><h5 class="fw-bold">Contacto</h5><p class="text-white-50 small mb-0">info@ejemplo.com<br>+34 123 456 789</p></div></div><hr class="border-secondary my-4"><p class="text-center text-white-50 small mb-0">© 2026 Empresa. Todos los derechos reservados.</p></div></footer>'
});

gjsEditor.BlockManager.add('bs5-features', {
    category: EXTRA,
    label: 'Sección de Características',
    media: '<i class="bi bi-grid-1x2 fs-3"></i>',
    description: 'Tres características con iconos',
    content: '<section class="py-5"><div class="container"><h2 class="text-center mb-5 fw-bold">Nuestras características</h2><div class="row g-4"><div class="col-md-4 text-center"><div class="p-3 text-bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px"><i class="bi bi-lightning-charge fs-3" aria-hidden="true"></i></div><h5 class="fw-bold">Rápido</h5><p class="text-body-secondary mb-0">Rendimiento optimizado para la mejor experiencia.</p></div><div class="col-md-4 text-center"><div class="p-3 text-bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px"><i class="bi bi-shield-check fs-3" aria-hidden="true"></i></div><h5 class="fw-bold">Seguro</h5><p class="text-body-secondary mb-0">Protección de datos de primer nivel.</p></div><div class="col-md-4 text-center"><div class="p-3 text-bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px"><i class="bi bi-lightbulb fs-3" aria-hidden="true"></i></div><h5 class="fw-bold">Intuitivo</h5><p class="text-body-secondary mb-0">Fácil de usar para cualquier persona.</p></div></div></div></section>'
});

gjsEditor.BlockManager.add('bs5-testimonial', {
    category: EXTRA,
    label: 'Testimonio',
    media: '<i class="bi bi-chat-quote fs-3"></i>',
    description: 'Tarjeta de testimonio con avatar',
    content: '<div class="card border-0 shadow-sm"><div class="card-body p-4"><p class="mb-3 fst-italic text-body-secondary">"Un producto excelente, superó todas mis expectativas. Lo recomiendo totalmente."</p><div class="d-flex align-items-center"><img src="https://placehold.co/50x50/6c757d/ffffff?text=A" class="rounded-circle me-3" width="50" height="50" alt="Avatar"><div><strong>Ana García</strong><div class="text-body-secondary small">CEO, Empresa S.A.</div></div></div></div></div>'
});

gjsEditor.BlockManager.add('bs5-cta', {
    category: EXTRA,
    label: 'Call to Action',
    media: '<i class="bi bi-megaphone fs-3"></i>',
    description: 'Banda de conversión con botón',
    content: '<div class="text-bg-primary text-center py-5 rounded-3" data-bs-theme="dark"><h2 class="display-6 fw-bold">¿Listo para empezar?</h2><p class="lead mb-4 opacity-75">Únete a miles de usuarios satisfechos hoy mismo.</p><button class="btn btn-light btn-lg px-4">Comenzar ahora</button></div>'
});

console.log('[grapesjs-bootstrap5-blocks] ✓ Bloques Bootstrap 5 modernizados con BS 5.3.3');
})();