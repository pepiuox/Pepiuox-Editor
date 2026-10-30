// ═══════════════════════════════════════════════════════════════════
// Bloques de Bootstrap 5.3.3 — reescritos sin estilos en línea
//
// Antes casi todos traían `style="padding:20px; border:1px dashed #ddd"` y
// colores fijos: se insertaban secciones que no seguían la grilla, que no
// respondían y que había que limpiar a mano antes de publicar.
//
// Ahora usan exclusivamente clases del framework cargado en el lienzo
// (container, row/col-*, g-*, py-5, card, btn, alert, accordion, table,
// list-group, ratio, carousel, modal, offcanvas…). Los **ids no cambian**: nada
// que ya los referencie deja de funcionar.
//
// @package Assets\Js\Blocks
// @since   2026-10-30 (reescritura; ids originales conservados)
// ═══════════════════════════════════════════════════════════════════

(function () {
    'use strict';

    if (typeof gjsEditor === 'undefined' || !gjsEditor) {
        console.warn('[blocks-bootstrap] gjsEditor no existe todavía; fichero omitido.');
        return;
    }

    var L = { id: 'px-layout', label: 'Estructura y layout', open: false };
    var N = { id: 'px-nav', label: 'Navegación', open: false };
    var C = { id: 'px-content', label: 'Contenido', open: false };
    var A = { id: 'px-actions', label: 'Botones y acciones', open: false };
    var K = { id: 'px-cards', label: 'Tarjetas', open: false };
    var F = { id: 'px-forms', label: 'Formularios', open: false };
    var B = { id: 'px-bs', label: 'Componentes Bootstrap', open: false };
    var D = { id: 'px-data', label: 'Tablas y datos', open: false };
    var FT = { id: 'px-footer', label: 'Pie de página', open: false };

    /** Guía de columna vacía: borde y texto del tema, sin color fijo. */
    var col = '<div class="border border-2 border-secondary-subtle rounded-3 p-3 text-center small text-body-secondary">Columna</div>';
    var section = function (inner, extra) {
        return '<section class="py-5' + (extra ? ' ' + extra : '') + '"><div class="container">' + inner + '</div></section>';
    };
    var photo = function (alto) {
        return '<div class="d-flex align-items-center justify-content-center bg-body-secondary text-body-secondary rounded-3" style="min-height:' + (alto || 220) + 'px">'
            + '<span class="small"><i class="bi bi-image d-block mb-1 fs-3"></i>Imagen</span></div>';
    };

    // ── Layout ──────────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-container', {
        category: L, label: 'Container', media: '<i class="bi bi-square"></i>',
        description: 'Contenedor centrado con ancho máximo por breakpoint',
        content: section('<p class="mb-0">Contenido dentro de un <code>.container</code>.</p>')
    });

    gjsEditor.BlockManager.add('bootstrap-container-fluid', {
        category: L, label: 'Container fluid', media: '<i class="bi bi-arrows-expand"></i>',
        description: 'Contenedor de ancho completo',
        content: '<div class="container-fluid py-4"><p class="mb-0">Contenido a ancho completo con <code>.container-fluid</code>.</p></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-row', {
        category: L, label: 'Fila', media: '<i class="bi bi-grip-horizontal"></i>',
        description: 'Fila de la grilla con una columna',
        content: '<div class="row g-3"><div class="col-12">' + col + '</div></div>'
    });

    // Registro explícito (y no en bucle) para que la auditoría
    // tests/blocks-library/classify.js pueda verlos.
    gjsEditor.BlockManager.add('bootstrap-grid-2', {
        category: L, label: 'Grilla 2 columnas', media: '<i class="bi bi-columns-gap"></i>',
        description: 'Grilla responsive de 2 columnas que colapsa en móvil',
        content: '<div class="row g-3"><div class="col-md-6">' + col + '</div><div class="col-md-6">' + col + '</div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-grid-3', {
        category: L, label: 'Grilla 3 columnas', media: '<i class="bi bi-columns-gap"></i>',
        description: 'Grilla responsive de 3 columnas que colapsa en móvil',
        content: '<div class="row g-3"><div class="col-md-4">' + col + '</div><div class="col-md-4">' + col + '</div><div class="col-md-4">' + col + '</div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-grid-4', {
        category: L, label: 'Grilla 4 columnas', media: '<i class="bi bi-columns-gap"></i>',
        description: 'Grilla responsive de 4 columnas que colapsa en móvil',
        content: '<div class="row g-3"><div class="col-md-3">' + col + '</div><div class="col-md-3">' + col + '</div><div class="col-md-3">' + col + '</div><div class="col-md-3">' + col + '</div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-layout-sidebar', {
        category: L, label: 'Sidebar + contenido', media: '<i class="bi bi-layout-sidebar"></i>',
        description: 'Dos columnas: barra lateral fija y contenido principal',
        content: '<div class="row g-4"><aside class="col-lg-3"><div class="p-3 bg-body-tertiary rounded-3 small text-body-secondary">Barra lateral</div></aside><main class="col-lg-9">' + col + '</main></div>'
    });

    // ── Navegación ──────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-navbar', {
        category: N, label: 'Navbar', media: '<i class="bi bi-menu-button-wide"></i>',
        description: 'Barra de navegación responsive con colapso',
        content: '<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom"><div class="container">'
            + '<a class="navbar-brand fw-semibold" href="#">Tu marca</a>'
            + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bsNav1"><span class="navbar-toggler-icon"></span></button>'
            + '<div class="collapse navbar-collapse" id="bsNav1"><ul class="navbar-nav ms-auto">'
            + '<li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>'
            + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
            + '</ul></div></div></nav>'
    });

    gjsEditor.BlockManager.add('bootstrap-navbar-dark', {
        category: N, label: 'Navbar oscura', media: '<i class="bi bi-menu-up"></i>',
        description: 'Navbar con tema oscuro de Bootstrap',
        content: '<nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark"><div class="container">'
            + '<a class="navbar-brand fw-semibold" href="#">Tu marca</a>'
            + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bsNav2"><span class="navbar-toggler-icon"></span></button>'
            + '<div class="collapse navbar-collapse" id="bsNav2"><ul class="navbar-nav ms-auto">'
            + '<li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>'
            + '<li class="nav-item"><a class="nav-link" href="#">Precios</a></li>'
            + '</ul></div></div></nav>'
    });

    gjsEditor.BlockManager.add('bootstrap-dropdown', {
        category: N, label: 'Desplegable', media: '<i class="bi bi-caret-down-square"></i>',
        description: 'Botón con menú desplegable de Bootstrap',
        content: '<div class="dropdown"><button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Opciones</button>'
            + '<ul class="dropdown-menu"><li><a class="dropdown-item" href="#">Acción</a></li><li><a class="dropdown-item" href="#">Otra acción</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="#">Separada</a></li></ul></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-breadcrumb', {
        category: N, label: 'Migas de pan', media: '<i class="bi bi-chevron-right"></i>',
        description: 'Breadcrumb con elemento actual marcado',
        content: '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="#" class="text-decoration-none">Inicio</a></li><li class="breadcrumb-item"><a href="#" class="text-decoration-none">Sección</a></li><li class="breadcrumb-item active" aria-current="page">Página</li></ol></nav>'
    });

    gjsEditor.BlockManager.add('bootstrap-pagination', {
        category: N, label: 'Paginación', media: '<i class="bi bi-three-dots"></i>',
        description: 'Paginación de Bootstrap accesible',
        content: '<nav aria-label="Paginación"><ul class="pagination mb-0"><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Anterior</a></li><li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li><li class="page-item"><a class="page-link" href="#">2</a></li><li class="page-item"><a class="page-link" href="#">Siguiente</a></li></ul></nav>'
    });

    // ── Botones ─────────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-buttons-group', {
        category: A, label: 'Botones', media: '<i class="bi bi-hand-index"></i>',
        description: 'Primario, secundario y contorno en una fila',
        content: '<div class="d-flex flex-wrap gap-2"><a href="#" class="btn btn-primary">Primario</a><a href="#" class="btn btn-secondary">Secundario</a><a href="#" class="btn btn-outline-primary">Contorno</a></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-btn-lg', {
        category: A, label: 'Botón grande', media: '<i class="bi bi-square"></i>',
        description: 'Tamaño grande (btn-lg)',
        content: '<a href="#" class="btn btn-primary btn-lg">Botón grande</a>'
    });

    gjsEditor.BlockManager.add('bootstrap-btn-sm', {
        category: A, label: 'Botón pequeño', media: '<i class="bi bi-square"></i>',
        description: 'Tamaño pequeño (btn-sm)',
        content: '<a href="#" class="btn btn-primary btn-sm">Botón pequeño</a>'
    });

    gjsEditor.BlockManager.add('bootstrap-btn-outline', {
        category: A, label: 'Botón contorno', media: '<i class="bi bi-square"></i>',
        description: 'Botón sin relleno',
        content: '<a href="#" class="btn btn-outline-primary">Botón contorno</a>'
    });

    gjsEditor.BlockManager.add('bootstrap-btn-group', {
        category: A, label: 'Grupo de botones', media: '<i class="bi bi-square"></i>',
        description: 'Botones agrupados',
        content: '<div class="btn-group" role="group" aria-label="Grupo"><button type="button" class="btn btn-outline-secondary">Uno</button><button type="button" class="btn btn-outline-secondary">Dos</button><button type="button" class="btn btn-outline-secondary">Tres</button></div>'
    });

    // ── Tarjetas ────────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-card-basic', {
        category: K, label: 'Card básica', media: '<i class="bi bi-card-text"></i>',
        description: 'Tarjeta con cuerpo, título y texto',
        content: '<div class="card h-100 shadow-sm"><div class="card-body"><h3 class="h5 fw-bold card-title">Título</h3><p class="card-text text-body-secondary">Texto de la tarjeta.</p></div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-card-header-footer', {
        category: K, label: 'Card con cabecera', media: '<i class="bi bi-card-heading"></i>',
        description: 'Tarjeta con cabecera y pie diferenciados',
        content: '<div class="card"><div class="card-header small fw-semibold">Cabecera</div><div class="card-body"><p class="card-text mb-0">Contenido de la tarjeta.</p></div><div class="card-footer text-body-secondary small">Hace 2 minutos</div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-card-horizontal', {
        category: K, label: 'Card horizontal', media: '<i class="bi bi-layout-sidebar-inset"></i>',
        description: 'Tarjeta con imagen a la izquierda',
        content: '<div class="card"><div class="row g-0"><div class="col-md-4">' + photo(160) + '</div><div class="col-md-8"><div class="card-body"><h3 class="h6 fw-bold card-title">Título</h3><p class="card-text small text-body-secondary mb-0">Descripción breve del contenido.</p></div></div></div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-card-deck', {
        category: K, label: 'Grupo de tarjetas', media: '<i class="bi bi-grid"></i>',
        description: 'Tres tarjetas iguales en grilla',
        content: '<div class="row g-4">' + ['Uno', 'Dos', 'Tres'].map(function (n) {
            return '<div class="col-md-4"><div class="card h-100 shadow-sm"><div class="card-body"><h3 class="h6 fw-bold card-title">Tarjeta ' + n + '</h3><p class="card-text small text-body-secondary mb-0">Texto de ejemplo.</p></div></div></div>';
        }).join('') + '</div>'
    });

    // ── Formularios ─────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-form-basic', {
        category: F, label: 'Formulario', media: '<i class="bi bi-input-cursor-text"></i>',
        description: 'Formulario con email, contraseña y recordar',
        content: section('<form style="max-width:420px">'
            + '<div class="mb-3"><label class="form-label">Correo</label><input type="email" class="form-control" placeholder="tu@correo.com"></div>'
            + '<div class="mb-3"><label class="form-label">Contraseña</label><input type="password" class="form-control"></div>'
            + '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="bsRec"><label class="form-check-label" for="bsRec">Recordarme</label></div>'
            + '<button type="submit" class="btn btn-primary w-100">Entrar</button></form>')
    });

    gjsEditor.BlockManager.add('bootstrap-form-horizontal', {
        category: F, label: 'Formulario horizontal', media: '<i class="bi bi-layout-text-sidebar"></i>',
        description: 'Etiqueta y campo en la misma fila',
        content: '<form><div class="row mb-3 align-items-center"><label class="col-sm-3 col-form-label">Nombre</label><div class="col-sm-9"><input type="text" class="form-control" placeholder="Tu nombre"></div></div>'
            + '<div class="row mb-3 align-items-center"><label class="col-sm-3 col-form-label">Correo</label><div class="col-sm-9"><input type="email" class="form-control" placeholder="tu@correo.com"></div></div>'
            + '<div class="row"><div class="col-sm-9 offset-sm-3"><button type="submit" class="btn btn-primary">Guardar</button></div></div></form>'
    });

    gjsEditor.BlockManager.add('bootstrap-input-group', {
        category: F, label: 'Grupo de entrada', media: '<i class="bi bi-input-cursor"></i>',
        description: 'Campo con prefijo o botón adjunto',
        content: '<div class="input-group"><span class="input-group-text">@</span><input type="text" class="form-control" placeholder="usuario"></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-select', {
        category: F, label: 'Select', media: '<i class="bi bi-menu-app"></i>',
        description: 'Lista desplegable con etiqueta',
        content: '<div><label class="form-label">Elige una opción</label><select class="form-select"><option selected>Selecciona…</option><option value="1">Uno</option><option value="2">Dos</option></select></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-radio-check', {
        category: F, label: 'Check y radio', media: '<i class="bi bi-ui-checks"></i>',
        description: 'Casillas y botones de opción',
        content: '<div class="d-flex flex-column gap-2"><div class="form-check"><input class="form-check-input" type="checkbox" id="bsC1" checked><label class="form-check-label" for="bsC1">Casilla marcada</label></div>'
            + '<div class="form-check"><input class="form-check-input" type="checkbox" id="bsC2"><label class="form-check-label" for="bsC2">Casilla</label></div>'
            + '<div class="form-check"><input class="form-check-input" type="radio" name="bsR" id="bsR1" checked><label class="form-check-label" for="bsR1">Opción A</label></div>'
            + '<div class="form-check"><input class="form-check-input" type="radio" name="bsR" id="bsR2"><label class="form-check-label" for="bsR2">Opción B</label></div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-range', {
        category: F, label: 'Rango', media: '<i class="bi bi-sliders"></i>',
        description: 'Control deslizante con etiqueta',
        content: '<div><label for="bsRange" class="form-label">Nivel</label><input type="range" class="form-range" id="bsRange" min="0" max="100" value="50"></div>'
    });

    // ── Contenido ───────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-jumbotron', {
        category: C, label: 'Hero de contenido', media: '<i class="bi bi-easel"></i>',
        description: 'Bloque destacado con titular y botón',
        content: '<section class="py-5"><div class="container"><div class="p-4 p-md-5 bg-body-tertiary rounded-4">'
            + '<h1 class="display-6 fw-bold">Título destacado</h1><p class="lead text-body-secondary mb-4">Texto de apoyo para presentar la sección.</p>'
            + '<a href="#" class="btn btn-primary btn-lg">Empezar</a></div></div></section>'
    });

    gjsEditor.BlockManager.add('bootstrap-img-fluid', {
        category: C, label: 'Imagen fluida', media: '<i class="bi bi-image"></i>',
        description: 'Imagen que se adapta al ancho del contenedor',
        content: '<img src="https://placehold.co/1200x600/eee/999?text=Imagen" class="img-fluid rounded-3" alt="Descripción">'
    });

    gjsEditor.BlockManager.add('bootstrap-img-thumbnail', {
        category: C, label: 'Imagen miniatura', media: '<i class="bi bi-image-alt"></i>',
        description: 'Imagen con marco y relleno',
        content: '<img src="https://placehold.co/400x300/eee/999?text=Imagen" class="img-thumbnail" alt="Descripción">'
    });

    gjsEditor.BlockManager.add('bootstrap-figure', {
        category: C, label: 'Figura', media: '<i class="bi bi-card-image"></i>',
        description: 'Imagen con pie de foto',
        content: '<figure class="figure"><img src="https://placehold.co/600x400/eee/999?text=Imagen" class="figure-img img-fluid rounded-3" alt="Descripción"><figcaption class="figure-caption">Pie de foto de la imagen.</figcaption></figure>'
    });

    gjsEditor.BlockManager.add('bootstrap-responsive-video', {
        category: C, label: 'Vídeo responsive', media: '<i class="bi bi-play-btn"></i>',
        description: 'Vídeo 16:9 sin scripts',
        content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Vídeo" allowfullscreen loading="lazy"></iframe></div>'
    });

    // ── Componentes ─────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-alerts', {
        category: B, label: 'Alertas', media: '<i class="bi bi-exclamation-triangle"></i>',
        description: 'Cuatro alertas con colores semánticos',
        content: ['primary:Información', 'success:Correcto', 'warning:Atención', 'danger:Error'].map(function (pair) {
            var parts = pair.split(':');
            return '<div class="alert alert-' + parts[0] + '" role="alert">' + parts[1] + '</div>';
        }).join('')
    });

    gjsEditor.BlockManager.add('bootstrap-alert-dismissible', {
        category: B, label: 'Alerta cerrable', media: '<i class="bi bi-x-circle"></i>',
        description: 'Alerta con botón de cierre',
        content: '<div class="alert alert-warning alert-dismissible fade show" role="alert">Aviso importante.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-badges', {
        category: B, label: 'Etiquetas', media: '<i class="bi bi-tag"></i>',
        description: 'Badges con colores semánticos',
        content: '<div class="d-flex flex-wrap gap-2">' + ['primary', 'secondary', 'success', 'danger', 'warning'].map(function (c) {
            return '<span class="badge text-bg-' + c + '">' + c + '</span>';
        }).join('') + '</div>'
    });

    gjsEditor.BlockManager.add('bootstrap-progress', {
        category: B, label: 'Progreso', media: '<i class="bi bi-bar-chart-steps"></i>',
        description: 'Tres barras de progreso con etiqueta',
        content: '<div class="d-flex flex-column gap-3">' + [['60', 'Diseño'], ['35', 'Desarrollo'], ['85', 'Pruebas']].map(function (item) {
            return '<div><div class="d-flex justify-content-between small text-body-secondary"><span>' + item[1] + '</span><span>' + item[0] + ' %</span></div>'
                + '<div class="progress" style="height:8px" role="progressbar" aria-valuenow="' + item[0] + '" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:' + item[0] + '%"></div></div></div>';
        }).join('') + '</div>'
    });

    gjsEditor.BlockManager.add('bootstrap-list-group', {
        category: B, label: 'Lista agrupada', media: '<i class="bi bi-list-task"></i>',
        description: 'Lista con elementos activos y deshabilitados',
        content: '<ul class="list-group"><li class="list-group-item active" aria-current="true">Elemento activo</li><li class="list-group-item">Segundo elemento</li><li class="list-group-item">Tercer elemento</li><li class="list-group-item disabled">Deshabilitado</li></ul>'
    });

    gjsEditor.BlockManager.add('bootstrap-accordion', {
        category: B, label: 'Acordeón', media: '<i class="bi bi-chevron-expand"></i>',
        description: 'Acordeón de tres paneles con el primero abierto',
        content: '<div class="accordion" id="bsAcc"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#bsAcc1">Título uno</button></h2><div id="bsAcc1" class="accordion-collapse collapse show" data-bs-parent="#bsAcc"><div class="accordion-body text-body-secondary">Contenido del primer panel.</div></div></div>'
            + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bsAcc2">Título dos</button></h2><div id="bsAcc2" class="accordion-collapse collapse" data-bs-parent="#bsAcc"><div class="accordion-body text-body-secondary">Contenido del segundo panel.</div></div></div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-tabs', {
        category: B, label: 'Pestañas', media: '<i class="bi bi-folder2-open"></i>',
        description: 'Pestañas de navegación con dos paneles',
        content: '<ul class="nav nav-tabs" role="tablist"><li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bsTab1" type="button" role="tab">Primera</button></li>'
            + '<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bsTab2" type="button" role="tab">Segunda</button></li></ul>'
            + '<div class="tab-content pt-3"><div class="tab-pane fade show active" id="bsTab1" role="tabpanel">Contenido de la primera pestaña.</div><div class="tab-pane fade" id="bsTab2" role="tabpanel">Contenido de la segunda.</div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-modal-trigger', {
        category: B, label: 'Modal', media: '<i class="bi bi-window-stack"></i>',
        description: 'Botón que abre una ventana modal',
        content: '<div><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bsModal">Abrir modal</button>'
            + '<div class="modal fade" id="bsModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">'
            + '<div class="modal-header"><h5 class="modal-title">Título del modal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
            + '<div class="modal-body">Contenido del modal.</div>'
            + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary">Guardar</button></div>'
            + '</div></div></div></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-tooltip', {
        category: B, label: 'Tooltip', media: '<i class="bi bi-chat-square-text"></i>',
        description: 'Texto con ayuda emergente al pasar el ratón',
        content: '<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-title="Texto de ayuda"><button class="btn btn-outline-secondary" type="button">Pasa el ratón</button></span>'
    });

    gjsEditor.BlockManager.add('bootstrap-carousel', {
        category: B, label: 'Carrusel', media: '<i class="bi bi-images"></i>',
        description: 'Carrusel de tres diapositivas con controles',
        content: '<div id="bsCarousel" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner">'
            + [1, 2, 3].map(function (n) {
                return '<div class="carousel-item' + (n === 1 ? ' active' : '') + '">' + photo(320) + '</div>';
            }).join('')
            + '</div><button class="carousel-control-prev" type="button" data-bs-target="#bsCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>'
            + '<button class="carousel-control-next" type="button" data-bs-target="#bsCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button></div>'
    });

    // ── Tablas ──────────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-table', {
        category: D, label: 'Tabla', media: '<i class="bi bi-table"></i>',
        description: 'Tabla responsive con encabezado',
        content: '<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th scope="col">Concepto</th><th scope="col">Estado</th><th scope="col" class="text-end">Importe</th></tr></thead>'
            + '<tbody><tr><td>Diseño</td><td><span class="badge text-bg-success">Pagado</span></td><td class="text-end">1.200 €</td></tr>'
            + '<tr><td>Desarrollo</td><td><span class="badge text-bg-warning">Pendiente</span></td><td class="text-end">3.400 €</td></tr></tbody></table></div>'
    });

    gjsEditor.BlockManager.add('bootstrap-table-striped', {
        category: D, label: 'Tabla alterna', media: '<i class="bi bi-table"></i>',
        description: 'Tabla con filas alternas y hover',
        content: '<div class="table-responsive"><table class="table table-striped table-hover align-middle mb-0"><thead class="table-light"><tr><th scope="col">#</th><th scope="col">Nombre</th><th scope="col">Rol</th></tr></thead>'
            + '<tbody><tr><th scope="row">1</th><td>Ana</td><td>Diseño</td></tr><tr><th scope="row">2</th><td>Luis</td><td>Desarrollo</td></tr><tr><th scope="row">3</th><td>Marta</td><td>Producto</td></tr></tbody></table></div>'
    });

    // ── Secciones compuestas ────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-features', {
        category: K, label: 'Sección de características', media: '<i class="bi bi-grid-1x2"></i>',
        description: 'Tres características con icono en tarjetas',
        content: section('<div class="text-center mb-5"><h2 class="fw-bold">Por qué elegirnos</h2><p class="text-body-secondary mb-0">Tres motivos concretos.</p></div>'
            + '<div class="row g-4">' + ['bi-lightning-charge', 'bi-shield-check', 'bi-graph-up-arrow'].map(function (icon) {
                return '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4">'
                    + '<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis mb-3" style="width:46px;height:46px"><i class="bi ' + icon + ' fs-4"></i></span>'
                    + '<h3 class="h5 fw-bold">Característica</h3><p class="text-body-secondary mb-0">Describe una ventaja real.</p>'
                    + '</div></div></div>';
            }).join('') + '</div>', 'bg-body-tertiary')
    });

    gjsEditor.BlockManager.add('bootstrap-pricing', {
        category: K, label: 'Sección de precios', media: '<i class="bi bi-tags"></i>',
        description: 'Tres planes con el central destacado',
        content: section('<div class="text-center mb-5"><h2 class="fw-bold">Precios</h2><p class="text-body-secondary mb-0">Sin sorpresas.</p></div>'
            + '<div class="row g-4 justify-content-center">' + [['Básico', '9', false], ['Pro', '29', true], ['Empresa', '99', false]].map(function (plan) {
                return '<div class="col-md-4"><div class="card h-100 ' + (plan[2] ? 'border-primary shadow' : 'border-0 shadow-sm') + '"><div class="card-body p-4 text-center">'
                    + (plan[2] ? '<span class="badge text-bg-primary mb-2">Más elegido</span>' : '')
                    + '<h3 class="h5 fw-bold mb-1">' + plan[0] + '</h3>'
                    + '<p class="display-6 fw-bold mb-3">' + plan[1] + ' €<span class="fs-6 fw-normal text-body-secondary">/mes</span></p>'
                    + '<a href="#" class="btn ' + (plan[2] ? 'btn-primary' : 'btn-outline-secondary') + ' w-100">Elegir</a>'
                    + '</div></div></div>';
            }).join('') + '</div>')
    });

    gjsEditor.BlockManager.add('bootstrap-testimonials', {
        category: K, label: 'Sección de testimonios', media: '<i class="bi bi-chat-quote"></i>',
        description: 'Tres testimonios con valoración',
        content: section('<h2 class="fw-bold text-center mb-5">Opiniones</h2><div class="row g-4">' + [1, 2, 3].map(function () {
            return '<div class="col-lg-4"><figure class="card h-100 border-0 shadow-sm"><div class="card-body p-4">'
                + '<div class="text-warning mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>'
                + '<blockquote class="mb-3"><p class="mb-0">«Un comentario real de alguien que lo ha usado.»</p></blockquote>'
                + '<figcaption class="d-flex align-items-center gap-2"><span class="rounded-circle bg-secondary-subtle d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-person"></i></span><span class="small"><strong class="d-block">Nombre</strong><span class="text-body-secondary">Cargo</span></span></figcaption>'
                + '</div></figure></div>';
        }).join('') + '</div>', 'bg-body-tertiary')
    });

    gjsEditor.BlockManager.add('bootstrap-contact-section', {
        category: F, label: 'Sección de contacto', media: '<i class="bi bi-envelope-paper"></i>',
        description: 'Datos de contacto y formulario a dos columnas',
        content: section('<div class="row g-5"><div class="col-lg-5"><h2 class="fw-bold mb-3">Hablemos</h2><p class="text-body-secondary">Te respondemos en menos de 24 h.</p>'
            + '<ul class="list-unstyled small"><li class="d-flex gap-2 mb-2"><i class="bi bi-envelope text-primary"></i>hola@tudominio.com</li>'
            + '<li class="d-flex gap-2"><i class="bi bi-telephone text-primary"></i>+34 600 000 000</li></ul></div>'
            + '<div class="col-lg-7"><form><div class="row g-3">'
            + '<div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control"></div>'
            + '<div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control"></div>'
            + '<div class="col-12"><label class="form-label">Mensaje</label><textarea class="form-control" rows="4"></textarea></div>'
            + '<div class="col-12"><button type="submit" class="btn btn-primary">Enviar</button></div></div></form></div></div>')
    });

    // ── Pie ─────────────────────────────────────────────────────────
    gjsEditor.BlockManager.add('bootstrap-footer', {
        category: FT, label: 'Footer', media: '<i class="bi bi-layout-text-window-reverse"></i>',
        description: 'Pie con tres columnas y copyright',
        content: '<footer class="py-5 bg-body-tertiary border-top mt-5"><div class="container"><div class="row g-4">'
            + '<div class="col-lg-6"><p class="h5 fw-bold mb-2">Tu marca</p><p class="small text-body-secondary mb-0">Una frase que resuma qué haces.</p></div>'
            + '<div class="col-6 col-lg-3"><p class="text-uppercase small fw-bold text-body-secondary mb-2">Producto</p><ul class="list-unstyled small mb-0"><li class="mb-1"><a href="#" class="link-secondary text-decoration-none">Funciones</a></li><li><a href="#" class="link-secondary text-decoration-none">Precios</a></li></ul></div>'
            + '<div class="col-6 col-lg-3"><p class="text-uppercase small fw-bold text-body-secondary mb-2">Empresa</p><ul class="list-unstyled small mb-0"><li class="mb-1"><a href="#" class="link-secondary text-decoration-none">Sobre nosotros</a></li><li><a href="#" class="link-secondary text-decoration-none">Contacto</a></li></ul></div>'
            + '</div><hr class="my-4"><p class="small text-body-secondary text-center mb-0">© 2026 Tu marca. Todos los derechos reservados.</p></div></footer>'
    });
})();
