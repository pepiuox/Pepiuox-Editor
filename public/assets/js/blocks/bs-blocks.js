// ═══════════════════════════════════════════════════════════════════
// blocks-bs5-unified.js
// Bloques unificados Bootstrap 5.3.3 para GrapesJS
//
// Sustituye a:
// - template.js
// - bs-blocks.js
// - blocks-bs.js
//
// Mejoras:
// - Sin IDs de bloque repetidos
// - gjsEditor.BlockManager.add directamente
// - Bootstrap 5.3.3
// - Bootstrap Icons
// - Categorías limpias y consistentes
// - Contenido variado para crear páginas más completas
// - Sin Font Awesome
// - Sin Bootstrap 4
// - Sin data-toggle/data-dismiss antiguos
// - Sin text-muted/bg-light antiguos como base principal
// - placehold.co para imágenes de prueba
// - IIFE con verificación de gjsEditor
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';

if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[blocks-bs5-unified] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ─────────────────────────────────────────────────────────────
// Categorías
// ─────────────────────────────────────────────────────────────
var CAT = {
    layout:    { id: 'b5u-layout',    label: '📐 Layout Bootstrap',       open: true },
    nav:       { id: 'b5u-nav',       label: '🧭 Navegación',             open: false },
    hero:      { id: 'b5u-hero',      label: '🦸 Heroes / Portadas',      open: false },
    content:   { id: 'b5u-content',   label: '🧩 Contenido',              open: false },
    forms:     { id: 'b5u-forms',     label: '📝 Formularios',            open: false },
    data:      { id: 'b5u-data',      label: '📊 Datos / Tablas',         open: false },
    marketing: { id: 'b5u-marketing', label: '📣 Marketing',              open: false },
    admin:     { id: 'b5u-admin',     label: '🖥️ Admin / Dashboard',      open: false },
    footer:    { id: 'b5u-footer',    label: '🦶 Pies de página',         open: false }
};

// ─────────────────────────────────────────────────────────────
// Registro seguro sin duplicados
// ─────────────────────────────────────────────────────────────
var registered = new Set();

function addBlock(id, definition) {
    var blockId = 'b5u-' + id;

    if (registered.has(blockId)) {
        console.warn('[blocks-bs5-unified] Bloque duplicado omitido:', blockId);
        return;
    }

    if (
        typeof gjsEditor.BlockManager.get === 'function' &&
        gjsEditor.BlockManager.get(blockId)
    ) {
        console.warn('[blocks-bs5-unified] Ya existe en BlockManager:', blockId);
        return;
    }

    registered.add(blockId);
    gjsEditor.BlockManager.add(blockId, definition);
}

function icon(name) {
    return '<i class="bi bi-' + name + ' fs-3"></i>';
}

function image(size, text) {
    return 'https://placehold.co/' + size + '/e9ecef/6c757d?text=' + encodeURIComponent(text || 'Imagen');
}

function section(content, extraClass) {
    return '<section class="py-5 ' + (extraClass || '') + '">'
        + '<div class="container">' + content + '</div>'
        + '</section>';
}

var colGuide = 'border border-2 border-secondary-subtle rounded-3 p-3 text-center small text-body-secondary bg-body-tertiary';

// ═══════════════════════════════════════════════════════════════════
// 📐 LAYOUT
// ═══════════════════════════════════════════════════════════════════

addBlock('layout-container', {
    category: CAT.layout,
    label: 'Container',
    media: icon('square'),
    description: 'Contenedor Bootstrap centrado con ancho máximo responsive',
    content: '<div class="container py-4">'
        + '<div class="' + colGuide + '">Contenedor .container</div>'
        + '</div>'
});

addBlock('layout-container-fluid', {
    category: CAT.layout,
    label: 'Container fluido',
    media: icon('arrows-expand'),
    description: 'Contenedor de ancho completo',
    content: '<div class="container-fluid py-4">'
        + '<div class="' + colGuide + '">Contenedor .container-fluid</div>'
        + '</div>'
});

addBlock('layout-grid-2', {
    category: CAT.layout,
    label: 'Grid 2 columnas',
    media: icon('layout-split'),
    description: 'Dos columnas responsive con separación Bootstrap',
    content: '<div class="row g-4">'
        + '<div class="col-md-6"><div class="' + colGuide + '">Columna 1</div></div>'
        + '<div class="col-md-6"><div class="' + colGuide + '">Columna 2</div></div>'
        + '</div>'
});

addBlock('layout-grid-3', {
    category: CAT.layout,
    label: 'Grid 3 columnas',
    media: icon('layout-three-columns'),
    description: 'Tres columnas responsive',
    content: '<div class="row g-4">'
        + '<div class="col-md-4"><div class="' + colGuide + '">Columna 1</div></div>'
        + '<div class="col-md-4"><div class="' + colGuide + '">Columna 2</div></div>'
        + '<div class="col-md-4"><div class="' + colGuide + '">Columna 3</div></div>'
        + '</div>'
});

addBlock('layout-grid-4', {
    category: CAT.layout,
    label: 'Grid 4 columnas',
    media: icon('grid-3x3-gap'),
    description: 'Cuatro columnas responsive',
    content: '<div class="row g-3">'
        + '<div class="col-6 col-md-3"><div class="' + colGuide + '">1</div></div>'
        + '<div class="col-6 col-md-3"><div class="' + colGuide + '">2</div></div>'
        + '<div class="col-6 col-md-3"><div class="' + colGuide + '">3</div></div>'
        + '<div class="col-6 col-md-3"><div class="' + colGuide + '">4</div></div>'
        + '</div>'
});

addBlock('layout-sidebar-content', {
    category: CAT.layout,
    label: 'Sidebar + contenido',
    media: icon('layout-sidebar'),
    description: 'Layout de página con barra lateral y contenido principal',
    content: '<div class="row g-4">'
        + '<aside class="col-lg-3">'
        + '<div class="p-4 bg-body-tertiary rounded-3 border">'
        + '<h5 class="fw-bold mb-3">Menú lateral</h5>'
        + '<ul class="nav flex-column small">'
        + '<li class="nav-item"><a class="nav-link active px-0" href="#">Resumen</a></li>'
        + '<li class="nav-item"><a class="nav-link px-0" href="#">Documentación</a></li>'
        + '<li class="nav-item"><a class="nav-link px-0" href="#">Ajustes</a></li>'
        + '</ul>'
        + '</div>'
        + '</aside>'
        + '<main class="col-lg-9">'
        + '<div class="p-4 border rounded-3">'
        + '<h2 class="fw-bold">Contenido principal</h2>'
        + '<p class="text-body-secondary mb-0">Área principal para textos, imágenes, tablas o componentes.</p>'
        + '</div>'
        + '</main>'
        + '</div>'
});

// ═══════════════════════════════════════════════════════════════════
// 🧭 NAVEGACIÓN
// ═══════════════════════════════════════════════════════════════════

addBlock('nav-navbar-light', {
    category: CAT.nav,
    label: 'Navbar clara',
    media: icon('menu-button-wide'),
    description: 'Barra de navegación clara responsive con botón móvil',
    content: '<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom shadow-sm">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">Mi Marca</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#b5uNavLight" aria-controls="b5uNavLight" aria-expanded="false" aria-label="Alternar navegación">'
        + '<span class="navbar-toggler-icon"></span>'
        + '</button>'
        + '<div class="collapse navbar-collapse" id="b5uNavLight">'
        + '<ul class="navbar-nav ms-auto mb-2 mb-lg-0">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Precios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul>'
        + '<a href="#" class="btn btn-primary ms-lg-3">Empezar</a>'
        + '</div>'
        + '</div>'
        + '</nav>'
});

addBlock('nav-navbar-dark', {
    category: CAT.nav,
    label: 'Navbar oscura',
    media: icon('menu-up'),
    description: 'Navbar oscura usando data-bs-theme de Bootstrap 5.3',
    content: '<nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">AdminBrand</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#b5uNavDark" aria-controls="b5uNavDark" aria-expanded="false" aria-label="Alternar navegación">'
        + '<span class="navbar-toggler-icon"></span>'
        + '</button>'
        + '<div class="collapse navbar-collapse" id="b5uNavDark">'
        + '<ul class="navbar-nav ms-auto">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Panel</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Usuarios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Reportes</a></li>'
        + '</ul>'
        + '</div>'
        + '</div>'
        + '</nav>'
});

addBlock('nav-breadcrumb', {
    category: CAT.nav,
    label: 'Breadcrumb',
    media: icon('chevron-right'),
    description: 'Migas de pan accesibles',
    content: '<nav aria-label="breadcrumb">'
        + '<ol class="breadcrumb mb-0">'
        + '<li class="breadcrumb-item"><a href="#" class="text-decoration-none">Inicio</a></li>'
        + '<li class="breadcrumb-item"><a href="#" class="text-decoration-none">Categoría</a></li>'
        + '<li class="breadcrumb-item active" aria-current="page">Página actual</li>'
        + '</ol>'
        + '</nav>'
});

addBlock('nav-pagination', {
    category: CAT.nav,
    label: 'Paginación',
    media: icon('three-dots'),
    description: 'Paginación Bootstrap accesible',
    content: '<nav aria-label="Paginación">'
        + '<ul class="pagination mb-0">'
        + '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a></li>'
        + '<li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">2</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">3</a></li>'
        + '<li class="page-item"><a class="page-link" href="#">Siguiente</a></li>'
        + '</ul>'
        + '</nav>'
});

// ═══════════════════════════════════════════════════════════════════
// 🦸 HEROES / PORTADAS
// ═══════════════════════════════════════════════════════════════════

addBlock('hero-centered', {
    category: CAT.hero,
    label: 'Hero centrado',
    media: icon('easel'),
    description: 'Hero limpio con titular, subtítulo y CTA',
    content: section(
        '<div class="row justify-content-center text-center py-5">'
        + '<div class="col-lg-8">'
        + '<span class="badge rounded-pill text-bg-primary mb-3">Bootstrap 5</span>'
        + '<h1 class="display-4 fw-bold mb-3">Crea páginas modernas más rápido</h1>'
        + '<p class="lead text-body-secondary mb-4">Bloques listos para construir landings, páginas corporativas, paneles y sitios de contenido con GrapesJS.</p>'
        + '<div class="d-flex justify-content-center gap-2 flex-wrap">'
        + '<a href="#" class="btn btn-primary btn-lg px-4">Comenzar</a>'
        + '<a href="#" class="btn btn-outline-secondary btn-lg px-4">Ver demo</a>'
        + '</div>'
        + '</div>'
        + '</div>',
        'bg-body-tertiary'
    )
});

addBlock('hero-split', {
    category: CAT.hero,
    label: 'Hero imagen + texto',
    media: icon('image-text'),
    description: 'Hero dividido con texto a la izquierda e imagen a la derecha',
    content: section(
        '<div class="row align-items-center g-5">'
        + '<div class="col-lg-6">'
        + '<p class="text-primary fw-bold text-uppercase small mb-2">Nuevo sistema visual</p>'
        + '<h1 class="display-5 fw-bold mb-3">Diseña contenido flexible y responsive</h1>'
        + '<p class="lead text-body-secondary mb-4">Combina secciones, tarjetas, formularios y componentes Bootstrap sin limpiar CSS antiguo.</p>'
        + '<ul class="list-unstyled mb-4">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Clases nativas Bootstrap 5.3</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Sin dependencias de Font Awesome</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Bloques únicos y mejor categorizados</li>'
        + '</ul>'
        + '<a href="#" class="btn btn-primary btn-lg">Crear página</a>'
        + '</div>'
        + '<div class="col-lg-6">'
        + '<img src="' + image('700x520', 'Hero Bootstrap') + '" class="img-fluid rounded-4 shadow" alt="Hero Bootstrap">'
        + '</div>'
        + '</div>'
    )
});

addBlock('hero-dark', {
    category: CAT.hero,
    label: 'Hero oscuro',
    media: icon('moon-stars'),
    description: 'Hero de alto contraste con fondo oscuro',
    content: '<section class="py-5 bg-dark text-white" data-bs-theme="dark">'
        + '<div class="container py-5">'
        + '<div class="row align-items-center g-4">'
        + '<div class="col-lg-8">'
        + '<h1 class="display-4 fw-bold mb-3">Una base sólida para tu próximo sitio</h1>'
        + '<p class="lead opacity-75 mb-0">Componentes oscuros, accesibles y listos para editar en GrapesJS.</p>'
        + '</div>'
        + '<div class="col-lg-4 text-lg-end">'
        + '<a href="#" class="btn btn-light btn-lg px-4">Empezar ahora</a>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</section>'
});

// ═══════════════════════════════════════════════════════════════════
// 🧩 CONTENIDO
// ═══════════════════════════════════════════════════════════════════

addBlock('content-features', {
    category: CAT.content,
    label: 'Features 3 columnas',
    media: icon('grid-1x2'),
    description: 'Tres tarjetas de beneficios con iconos Bootstrap',
    content: section(
        '<div class="text-center mb-5">'
        + '<h2 class="fw-bold">Todo lo necesario para crear contenido</h2>'
        + '<p class="text-body-secondary mb-0">Bloques pensados para maquetar más rápido.</p>'
        + '</div>'
        + '<div class="row g-4">'
        + [
            ['lightning-charge', 'Rápido', 'Inserta secciones listas y edita el contenido al instante.', 'primary'],
            ['shield-check', 'Consistente', 'Usa clases Bootstrap 5.3 y variables semánticas del tema.', 'success'],
            ['sliders', 'Flexible', 'Combina componentes, formularios, tablas y tarjetas.', 'warning']
        ].map(function (item) {
            return '<div class="col-md-4">'
                + '<div class="card h-100 border-0 shadow-sm">'
                + '<div class="card-body p-4 text-center">'
                + '<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-' + item[3] + '-subtle text-' + item[3] + '-emphasis mb-3" style="width:56px;height:56px">'
                + '<i class="bi bi-' + item[0] + ' fs-3"></i>'
                + '</span>'
                + '<h3 class="h5 fw-bold">' + item[1] + '</h3>'
                + '<p class="text-body-secondary mb-0">' + item[2] + '</p>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('')
        + '</div>'
    )
});

addBlock('content-card-image', {
    category: CAT.content,
    label: 'Card con imagen',
    media: icon('card-image'),
    description: 'Tarjeta con imagen superior, texto y botón',
    content: '<div class="card shadow-sm border-0" style="max-width:24rem">'
        + '<img src="' + image('600x360', 'Card imagen') + '" class="card-img-top" alt="Imagen de tarjeta">'
        + '<div class="card-body p-4">'
        + '<h5 class="card-title fw-bold">Título de tarjeta</h5>'
        + '<p class="card-text text-body-secondary">Texto breve para presentar un servicio, producto o recurso destacado.</p>'
        + '<a href="#" class="btn btn-primary">Leer más</a>'
        + '</div>'
        + '</div>'
});

addBlock('content-card-horizontal', {
    category: CAT.content,
    label: 'Card horizontal',
    media: icon('layout-sidebar-inset'),
    description: 'Tarjeta horizontal con imagen y contenido',
    content: '<div class="card border-0 shadow-sm overflow-hidden">'
        + '<div class="row g-0">'
        + '<div class="col-md-4">'
        + '<img src="' + image('500x400', 'Imagen') + '" class="img-fluid w-100 h-100" style="object-fit:cover" alt="Imagen">'
        + '</div>'
        + '<div class="col-md-8">'
        + '<div class="card-body p-4">'
        + '<h5 class="card-title fw-bold">Contenido destacado</h5>'
        + '<p class="card-text text-body-secondary">Ideal para noticias, servicios, entradas de blog o presentación de proyectos.</p>'
        + '<a href="#" class="btn btn-outline-primary btn-sm">Ver detalle</a>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
});

addBlock('content-buttons', {
    category: CAT.content,
    label: 'Botones',
    media: icon('hand-index'),
    description: 'Grupo de botones Bootstrap con variantes',
    content: '<div class="d-flex flex-wrap gap-2">'
        + '<button class="btn btn-primary" type="button">Primario</button>'
        + '<button class="btn btn-secondary" type="button">Secundario</button>'
        + '<button class="btn btn-success" type="button">Éxito</button>'
        + '<button class="btn btn-outline-dark" type="button">Contorno</button>'
        + '</div>'
});

addBlock('content-alert-dismissible', {
    category: CAT.content,
    label: 'Alerta cerrable',
    media: icon('exclamation-triangle'),
    description: 'Alerta Bootstrap con botón de cierre',
    content: '<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">'
        + '<i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>'
        + '<div class="flex-grow-1"><strong>Atención:</strong> revisa este mensaje antes de continuar.</div>'
        + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
        + '</div>'
});

addBlock('content-accordion-faq', {
    category: CAT.content,
    label: 'FAQ Acordeón',
    media: icon('chevron-expand'),
    description: 'Acordeón de preguntas frecuentes',
    content: '<div class="accordion" id="b5uAccordionFaq">'
        + '<div class="accordion-item">'
        + '<h2 class="accordion-header">'
        + '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#b5uFaqOne" aria-expanded="true" aria-controls="b5uFaqOne">¿Cómo empiezo?</button>'
        + '</h2>'
        + '<div id="b5uFaqOne" class="accordion-collapse collapse show" data-bs-parent="#b5uAccordionFaq">'
        + '<div class="accordion-body text-body-secondary">Arrastra un bloque al lienzo y edita textos, imágenes y estilos desde GrapesJS.</div>'
        + '</div>'
        + '</div>'
        + '<div class="accordion-item">'
        + '<h2 class="accordion-header">'
        + '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#b5uFaqTwo" aria-expanded="false" aria-controls="b5uFaqTwo">¿Usa Bootstrap 5?</button>'
        + '</h2>'
        + '<div id="b5uFaqTwo" class="accordion-collapse collapse" data-bs-parent="#b5uAccordionFaq">'
        + '<div class="accordion-body text-body-secondary">Sí. Está adaptado a Bootstrap 5.3.3, data-bs-* y Bootstrap Icons.</div>'
        + '</div>'
        + '</div>'
        + '</div>'
});

addBlock('content-tabs', {
    category: CAT.content,
    label: 'Tabs',
    media: icon('folder2-open'),
    description: 'Pestañas Bootstrap con tres paneles',
    content: '<ul class="nav nav-tabs" id="b5uTabs" role="tablist">'
        + '<li class="nav-item" role="presentation"><button class="nav-link active" id="b5u-tab-one" data-bs-toggle="tab" data-bs-target="#b5u-pane-one" type="button" role="tab" aria-controls="b5u-pane-one" aria-selected="true">Descripción</button></li>'
        + '<li class="nav-item" role="presentation"><button class="nav-link" id="b5u-tab-two" data-bs-toggle="tab" data-bs-target="#b5u-pane-two" type="button" role="tab" aria-controls="b5u-pane-two" aria-selected="false">Detalles</button></li>'
        + '<li class="nav-item" role="presentation"><button class="nav-link" id="b5u-tab-three" data-bs-toggle="tab" data-bs-target="#b5u-pane-three" type="button" role="tab" aria-controls="b5u-pane-three" aria-selected="false">FAQ</button></li>'
        + '</ul>'
        + '<div class="tab-content p-4 border border-top-0 rounded-bottom-3">'
        + '<div class="tab-pane fade show active" id="b5u-pane-one" role="tabpanel" aria-labelledby="b5u-tab-one">Contenido principal de la pestaña.</div>'
        + '<div class="tab-pane fade" id="b5u-pane-two" role="tabpanel" aria-labelledby="b5u-tab-two">Información adicional, especificaciones o documentación.</div>'
        + '<div class="tab-pane fade" id="b5u-pane-three" role="tabpanel" aria-labelledby="b5u-tab-three">Preguntas frecuentes o notas importantes.</div>'
        + '</div>'
});

addBlock('content-modal', {
    category: CAT.content,
    label: 'Modal',
    media: icon('window-stack'),
    description: 'Botón que abre una ventana modal Bootstrap',
    content: '<div>'
        + '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#b5uModal">Abrir modal</button>'
        + '<div class="modal fade" id="b5uModal" tabindex="-1" aria-labelledby="b5uModalLabel" aria-hidden="true">'
        + '<div class="modal-dialog modal-dialog-centered">'
        + '<div class="modal-content">'
        + '<div class="modal-header">'
        + '<h5 class="modal-title" id="b5uModalLabel">Título del modal</h5>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>'
        + '</div>'
        + '<div class="modal-body">Contenido editable del modal. Puedes insertar texto, formularios o llamadas a la acción.</div>'
        + '<div class="modal-footer">'
        + '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>'
        + '<button type="button" class="btn btn-primary">Guardar cambios</button>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
});

// ═══════════════════════════════════════════════════════════════════
// 📝 FORMULARIOS
// ═══════════════════════════════════════════════════════════════════

addBlock('forms-contact', {
    category: CAT.forms,
    label: 'Formulario contacto',
    media: icon('envelope-paper'),
    description: 'Formulario de contacto completo',
    content: section(
        '<div class="row justify-content-center">'
        + '<div class="col-lg-8">'
        + '<div class="card border-0 shadow-sm">'
        + '<div class="card-body p-4 p-md-5">'
        + '<h2 class="fw-bold mb-3 text-center">Contáctanos</h2>'
        + '<p class="text-body-secondary text-center mb-4">Te responderemos lo antes posible.</p>'
        + '<form>'
        + '<div class="row g-3">'
        + '<div class="col-md-6"><label class="form-label" for="b5uContactName">Nombre</label><input type="text" class="form-control" id="b5uContactName" autocomplete="name" placeholder="Tu nombre"></div>'
        + '<div class="col-md-6"><label class="form-label" for="b5uContactEmail">Email</label><input type="email" class="form-control" id="b5uContactEmail" autocomplete="email" placeholder="tu@email.com"></div>'
        + '<div class="col-12"><label class="form-label" for="b5uContactSubject">Asunto</label><input type="text" class="form-control" id="b5uContactSubject" placeholder="Motivo del mensaje"></div>'
        + '<div class="col-12"><label class="form-label" for="b5uContactMsg">Mensaje</label><textarea class="form-control" id="b5uContactMsg" rows="4" placeholder="Escribe tu mensaje"></textarea></div>'
        + '<div class="col-12"><button type="submit" class="btn btn-primary px-4">Enviar mensaje</button></div>'
        + '</div>'
        + '</form>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
    )
});

addBlock('forms-login', {
    category: CAT.forms,
    label: 'Login card',
    media: icon('box-arrow-in-right'),
    description: 'Tarjeta de inicio de sesión',
    content: '<div class="row justify-content-center">'
        + '<div class="col-md-5 col-lg-4">'
        + '<div class="card border-0 shadow-sm">'
        + '<div class="card-body p-4">'
        + '<h3 class="fw-bold text-center mb-4">Iniciar sesión</h3>'
        + '<form>'
        + '<div class="mb-3"><label class="form-label" for="b5uLoginEmail">Email</label><input type="email" class="form-control" id="b5uLoginEmail" autocomplete="email"></div>'
        + '<div class="mb-3"><label class="form-label" for="b5uLoginPass">Contraseña</label><input type="password" class="form-control" id="b5uLoginPass" autocomplete="current-password"></div>'
        + '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="b5uRemember"><label class="form-check-label" for="b5uRemember">Recordarme</label></div>'
        + '<button type="submit" class="btn btn-primary w-100">Entrar</button>'
        + '</form>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
});

addBlock('forms-newsletter', {
    category: CAT.forms,
    label: 'Newsletter',
    media: icon('mailbox'),
    description: 'Caja de suscripción a newsletter',
    content: '<section class="py-5 text-bg-primary" data-bs-theme="dark">'
        + '<div class="container">'
        + '<div class="row align-items-center g-4">'
        + '<div class="col-lg-6">'
        + '<h2 class="fw-bold mb-2">Suscríbete al boletín</h2>'
        + '<p class="mb-0 opacity-75">Recibe novedades, consejos y recursos directamente en tu correo.</p>'
        + '</div>'
        + '<div class="col-lg-6">'
        + '<form class="input-group input-group-lg">'
        + '<input type="email" class="form-control" placeholder="tu@email.com" aria-label="Correo electrónico" autocomplete="email">'
        + '<button class="btn btn-light fw-semibold" type="submit">Suscribirme</button>'
        + '</form>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</section>'
});

addBlock('forms-search', {
    category: CAT.forms,
    label: 'Buscador',
    media: icon('search'),
    description: 'Barra de búsqueda grande',
    content: '<div class="container py-4">'
        + '<div class="row justify-content-center">'
        + '<div class="col-lg-8">'
        + '<div class="input-group input-group-lg shadow-sm">'
        + '<input type="search" class="form-control" placeholder="Buscar contenido..." aria-label="Buscar">'
        + '<button class="btn btn-primary px-4" type="button"><i class="bi bi-search me-1"></i>Buscar</button>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>'
});

// ═══════════════════════════════════════════════════════════════════
// 📊 DATOS / TABLAS
// ═══════════════════════════════════════════════════════════════════

addBlock('data-table', {
    category: CAT.data,
    label: 'Tabla responsive',
    media: icon('table'),
    description: 'Tabla con hover, badges y scroll responsive',
    content: '<div class="table-responsive rounded-3 shadow-sm">'
        + '<table class="table table-hover table-striped align-middle mb-0">'
        + '<thead class="table-light">'
        + '<tr><th scope="col">#</th><th scope="col">Nombre</th><th scope="col">Estado</th><th scope="col" class="text-end">Importe</th></tr>'
        + '</thead>'
        + '<tbody>'
        + '<tr><th scope="row">1</th><td>Diseño web</td><td><span class="badge text-bg-success">Pagado</span></td><td class="text-end">1.200 €</td></tr>'
        + '<tr><th scope="row">2</th><td>Desarrollo</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td><td class="text-end">3.400 €</td></tr>'
        + '<tr><th scope="row">3</th><td>SEO</td><td><span class="badge text-bg-secondary">Borrador</span></td><td class="text-end">600 €</td></tr>'
        + '</tbody>'
        + '</table>'
        + '</div>'
});

addBlock('data-stats', {
    category: CAT.data,
    label: 'Estadísticas',
    media: icon('bar-chart'),
    description: 'Cuatro métricas destacadas',
    content: section(
        '<div class="row g-4 text-center">'
        + [
            ['12K+', 'Usuarios', 'primary'],
            ['98%', 'Satisfacción', 'success'],
            ['350+', 'Proyectos', 'warning'],
            ['24/7', 'Soporte', 'info']
        ].map(function (s) {
            return '<div class="col-6 col-lg-3">'
                + '<div class="card border-0 shadow-sm h-100">'
                + '<div class="card-body p-4">'
                + '<div class="display-5 fw-bold text-' + s[2] + '">' + s[0] + '</div>'
                + '<p class="text-body-secondary mb-0">' + s[1] + '</p>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('')
        + '</div>',
        'bg-body-tertiary'
    )
});

addBlock('data-progress', {
    category: CAT.data,
    label: 'Barras de progreso',
    media: icon('bar-chart-steps'),
    description: 'Barras de progreso con etiquetas',
    content: '<div class="d-flex flex-column gap-3">'
        + [
            ['Diseño', '85', 'bg-success'],
            ['Desarrollo', '65', 'bg-primary'],
            ['Marketing', '45', 'bg-warning']
        ].map(function (p) {
            return '<div>'
                + '<div class="d-flex justify-content-between small mb-1">'
                + '<span class="fw-semibold">' + p[0] + '</span>'
                + '<span class="text-body-secondary">' + p[1] + '%</span>'
                + '</div>'
                + '<div class="progress" role="progressbar" aria-label="' + p[0] + '" aria-valuenow="' + p[1] + '" aria-valuemin="0" aria-valuemax="100" style="height:8px">'
                + '<div class="progress-bar ' + p[2] + '" style="width:' + p[1] + '%"></div>'
                + '</div>'
                + '</div>';
        }).join('')
        + '</div>'
});

// ═══════════════════════════════════════════════════════════════════
// 📣 MARKETING
// ═══════════════════════════════════════════════════════════════════

addBlock('marketing-pricing', {
    category: CAT.marketing,
    label: 'Pricing 3 planes',
    media: icon('tags'),
    description: 'Sección de precios con plan destacado',
    content: section(
        '<div class="text-center mb-5">'
        + '<h2 class="fw-bold">Planes simples</h2>'
        + '<p class="text-body-secondary mb-0">Elige el plan que mejor se adapte a tu proyecto.</p>'
        + '</div>'
        + '<div class="row g-4">'
        + [
            ['Básico', '9', false],
            ['Pro', '29', true],
            ['Empresa', '99', false]
        ].map(function (p) {
            return '<div class="col-md-4">'
                + '<div class="card h-100 ' + (p[2] ? 'border-primary border-2 shadow position-relative' : 'border-0 shadow-sm') + '">'
                + '<div class="card-body p-4 text-center">'
                + (p[2] ? '<span class="badge text-bg-primary position-absolute top-0 start-50 translate-middle">Popular</span>' : '')
                + '<h3 class="h5 fw-bold">' + p[0] + '</h3>'
                + '<p class="display-5 fw-bold my-3">' + p[1] + ' €<span class="fs-6 fw-normal text-body-secondary">/mes</span></p>'
                + '<ul class="list-unstyled text-start small mb-4">'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Proyectos ilimitados</li>'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Soporte por email</li>'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Actualizaciones incluidas</li>'
                + '</ul>'
                + '<a href="#" class="btn ' + (p[2] ? 'btn-primary' : 'btn-outline-primary') + ' w-100">Elegir plan</a>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('')
        + '</div>',
        'bg-body-tertiary'
    )
});

addBlock('marketing-cta', {
    category: CAT.marketing,
    label: 'CTA banner',
    media: icon('megaphone'),
    description: 'Llamada a la acción horizontal',
    content: '<section class="py-5 bg-warning">'
        + '<div class="container">'
        + '<div class="row align-items-center g-3">'
        + '<div class="col-lg-8">'
        + '<h2 class="fw-bold mb-1">¿Listo para crear tu próxima página?</h2>'
        + '<p class="mb-0 text-dark opacity-75">Empieza con bloques limpios, responsive y compatibles con Bootstrap 5.</p>'
        + '</div>'
        + '<div class="col-lg-4 text-lg-end">'
        + '<a href="#" class="btn btn-dark btn-lg px-4">Crear ahora</a>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</section>'
});

// ═══════════════════════════════════════════════════════════════════
// 🖥️ ADMIN / DASHBOARD
// ═══════════════════════════════════════════════════════════════════

addBlock('admin-dashboard', {
    category: CAT.admin,
    label: 'Admin Dashboard',
    media: icon('speedometer2'),
    description: 'Layout de dashboard sin scripts personalizados, con sidebar responsive y tabla',
    content: '<section class="py-4 bg-body-tertiary">'
        + '<div class="container-fluid">'
        + '<div class="row g-4">'
        + '<aside class="col-lg-3 col-xl-2">'
        + '<div class="card border-0 shadow-sm">'
        + '<div class="card-body">'
        + '<h5 class="fw-bold mb-3">AdminPanel</h5>'
        + '<div class="nav flex-column nav-pills">'
        + '<a class="nav-link active" href="#"><i class="bi bi-house-door me-2"></i>Dashboard</a>'
        + '<a class="nav-link" href="#"><i class="bi bi-people me-2"></i>Usuarios</a>'
        + '<a class="nav-link" href="#"><i class="bi bi-bar-chart me-2"></i>Reportes</a>'
        + '<a class="nav-link" href="#"><i class="bi bi-gear me-2"></i>Ajustes</a>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</aside>'
        + '<main class="col-lg-9 col-xl-10">'
        + '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">'
        + '<div><h1 class="h3 fw-bold mb-1">Dashboard</h1><p class="text-body-secondary mb-0">Resumen general del sistema</p></div>'
        + '<button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo</button>'
        + '</div>'
        + '<div class="row g-3 mb-4">'
        + [
            ['Usuarios', '12.540', 'people', 'primary'],
            ['Ventas', '24K €', 'cash-coin', 'success'],
            ['Órdenes', '1.240', 'bag-check', 'warning'],
            ['Tickets', '87', 'life-preserver', 'danger']
        ].map(function (card) {
            return '<div class="col-6 col-xl-3">'
                + '<div class="card border-0 shadow-sm h-100">'
                + '<div class="card-body">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<div><p class="text-body-secondary small mb-1">' + card[0] + '</p><h3 class="fw-bold mb-0">' + card[1] + '</h3></div>'
                + '<span class="rounded-3 bg-' + card[3] + '-subtle text-' + card[3] + '-emphasis d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="bi bi-' + card[2] + ' fs-4"></i></span>'
                + '</div>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('')
        + '</div>'
        + '<div class="card border-0 shadow-sm">'
        + '<div class="card-body">'
        + '<h5 class="fw-bold mb-3">Usuarios recientes</h5>'
        + '<div class="table-responsive">'
        + '<table class="table align-middle mb-0">'
        + '<thead class="table-light"><tr><th>Usuario</th><th>Email</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>'
        + '<tbody>'
        + '<tr><td>Juan Pérez</td><td>juan@email.com</td><td><span class="badge text-bg-success">Activo</span></td><td class="text-end"><button class="btn btn-sm btn-outline-primary">Editar</button></td></tr>'
        + '<tr><td>María López</td><td>maria@email.com</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td><td class="text-end"><button class="btn btn-sm btn-outline-primary">Editar</button></td></tr>'
        + '</tbody>'
        + '</table>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</main>'
        + '</div>'
        + '</div>'
        + '</section>'
});

// ═══════════════════════════════════════════════════════════════════
// 🦶 FOOTER
// ═══════════════════════════════════════════════════════════════════

addBlock('footer-corporate', {
    category: CAT.footer,
    label: 'Footer corporativo',
    media: icon('layout-text-window-reverse'),
    description: 'Pie de página con columnas, enlaces y redes',
    content: '<footer class="py-5 bg-dark text-white" data-bs-theme="dark">'
        + '<div class="container">'
        + '<div class="row g-4">'
        + '<div class="col-lg-4">'
        + '<h5 class="fw-bold">Mi Marca</h5>'
        + '<p class="text-white-50 small mb-3">Creamos experiencias digitales rápidas, accesibles y fáciles de mantener.</p>'
        + '<div class="d-flex gap-3 fs-5">'
        + '<a href="#" class="text-white" aria-label="Facebook"><i class="bi bi-facebook"></i></a>'
        + '<a href="#" class="text-white" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
        + '<a href="#" class="text-white" aria-label="Instagram"><i class="bi bi-instagram"></i></a>'
        + '</div>'
        + '</div>'
        + '<div class="col-6 col-lg-2">'
        + '<h6 class="fw-bold text-uppercase small text-white-50">Producto</h6>'
        + '<ul class="list-unstyled small">'
        + '<li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Funciones</a></li>'
        + '<li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Precios</a></li>'
        + '<li><a href="#" class="text-white-50 text-decoration-none">Demo</a></li>'
        + '</ul>'
        + '</div>'
        + '<div class="col-6 col-lg-2">'
        + '<h6 class="fw-bold text-uppercase small text-white-50">Empresa</h6>'
        + '<ul class="list-unstyled small">'
        + '<li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Nosotros</a></li>'
        + '<li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Blog</a></li>'
        + '<li><a href="#" class="text-white-50 text-decoration-none">Contacto</a></li>'
        + '</ul>'
        + '</div>'
        + '<div class="col-lg-4">'
        + '<h6 class="fw-bold text-uppercase small text-white-50">Contacto</h6>'
        + '<p class="small text-white-50 mb-1"><i class="bi bi-envelope me-2"></i>hola@tudominio.com</p>'
        + '<p class="small text-white-50 mb-0"><i class="bi bi-telephone me-2"></i>+34 600 000 000</p>'
        + '</div>'
        + '</div>'
        + '<hr class="border-secondary my-4">'
        + '<p class="small text-center text-white-50 mb-0">© 2026 Mi Marca. Todos los derechos reservados.</p>'
        + '</div>'
        + '</footer>'
});

console.log('[blocks-bs5-unified] ✓ ' + registered.size + ' bloques únicos Bootstrap 5 registrados');

})();