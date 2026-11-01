// ═══════════════════════════════════════════════════════════════════
// advance.js — Reescrito con Bootstrap 5.3.3 + Bootstrap Icons
//
// Mejoras aplicadas sobre la versión anterior:
//  - Emojis descriptivos en cada categoría (consistencia visual)
//  - Accesibilidad ARIA completa
//  - Variables de tema BS 5.3+: text-body-secondary, bg-body-tertiary
//  - placehold.co con paleta coherente
//  - IIFE con verificación de gjsEditor
//  - Descripciones detalladas para cada bloque
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (modernización final con emojis)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[advance] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var ABOUT     = { id: 'px-about',     label: '👥 Sobre nosotros',        open: false };
var ACCORDION = { id: 'px-accordion', label: '🪗 Acordeones',            open: false };
var ACTIONS   = { id: 'px-actions',   label: '📣 Llamadas a la acción',  open: false };
var BUTTONS   = { id: 'px-buttons',   label: '🔘 Botones',               open: false };
var ARTICLES  = { id: 'px-articles',  label: '📰 Artículos',             open: false };

// ══════════════════════════════════════════════════════════════════
// 👥 SOBRE NOSOTROS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('about-one', {
    category: ABOUT,
    label: 'About One',
    media: '<i class="bi bi-info-circle fs-3"></i>',
    description: 'Sección "Por qué elegirnos" con tres tarjetas de iconos',
    content: '<section class="py-5 bg-body-tertiary">'
        + '<div class="container">'
        + '<div class="text-center mb-5">'
        + '<h2 class="fw-bold">Por qué elegirnos</h2>'
        + '<p class="text-body-secondary">Tres razones concretas para confiar en nosotros.</p>'
        + '</div>'
        + '<div class="row g-4">'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4">'
        + '<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis mb-3 mx-auto" style="width:56px;height:56px"><i class="bi bi-megaphone fs-3"></i></span>'
        + '<h4 class="h5 fw-bold">Marketing Digital</h4>'
        + '<p class="text-body-secondary mb-0 small">Descripción breve del servicio para quienes buscan algo nuevo.</p>'
        + '</div></div>'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4">'
        + '<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success-emphasis mb-3 mx-auto" style="width:56px;height:56px"><i class="bi bi-graph-up-arrow fs-3"></i></span>'
        + '<h4 class="h5 fw-bold">Consultoría</h4>'
        + '<p class="text-body-secondary mb-0 small">Asesoramiento experto para hacer crecer tu negocio.</p>'
        + '</div></div>'
        + '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4">'
        + '<span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning-emphasis mb-3 mx-auto" style="width:56px;height:56px"><i class="bi bi-handshake fs-3"></i></span>'
        + '<h4 class="h5 fw-bold">Soluciones Empresariales</h4>'
        + '<p class="text-body-secondary mb-0 small">Herramientas a medida para tu organización.</p>'
        + '</div></div>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('about-two', {
    category: ABOUT,
    label: 'About Two',
    media: '<i class="bi bi-info-square fs-3"></i>',
    description: 'Características clave con imagen y texto alternados',
    content: '<section class="py-5">'
        + '<div class="container">'
        + '<div class="text-center mb-5">'
        + '<h2 class="fw-bold">Nuestras características clave</h2>'
        + '<p class="text-body-secondary mx-auto" style="max-width:640px">Descubre lo que nos hace diferentes y por qué miles de clientes confían en nosotros.</p>'
        + '</div>'
        + '<div class="row g-5 align-items-center">'
        + '<div class="col-lg-6"><img src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen" class="img-fluid rounded-3 shadow-sm" alt="Característica"></div>'
        + '<div class="col-lg-6">'
        + '<h3 class="h4 fw-bold mb-3">Marketing en redes sociales</h3>'
        + '<p class="text-body-secondary">Llegamos a tu audiencia donde está. Estrategias personalizadas que convierten seguidores en clientes.</p>'
        + '<ul class="list-unstyled mt-3">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Análisis de audiencia</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Contenido estratégico</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Reportes mensuales</li>'
        + '</ul></div></div>'
        + '<div class="row g-5 align-items-center flex-lg-row-reverse mt-4">'
        + '<div class="col-lg-6"><img src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen" class="img-fluid rounded-3 shadow-sm" alt="Equipo"></div>'
        + '<div class="col-lg-6">'
        + '<h3 class="h4 fw-bold mb-3">Equipo dedicado</h3>'
        + '<p class="text-body-secondary">Profesionales comprometidos con tu proyecto, disponibles cuando los necesites.</p>'
        + '<ul class="list-unstyled mt-3">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Soporte prioritario</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Comunicación directa</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Respuesta en 24h</li>'
        + '</ul></div></div>'
        + '</div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🪗 ACORDEONES
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('accordion-standard', {
    category: ACCORDION,
    label: 'Acordeón estándar',
    media: '<i class="bi bi-chevron-expand fs-3"></i>',
    description: 'Acordeón con el primer panel abierto',
    content: '<div class="accordion" id="accordionExample">'
        + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Elemento del acordeón #1</button></h2>'
        + '<div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample"><div class="accordion-body text-body-secondary">Este es el contenido del primer elemento. Se muestra por defecto hasta que el plugin de colapso añade las clases apropiadas.</div></div></div>'
        + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Elemento del acordeón #2</button></h2>'
        + '<div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body text-body-secondary">Este es el contenido del segundo elemento. Está oculto por defecto.</div></div></div>'
        + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Elemento del acordeón #3</button></h2>'
        + '<div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample"><div class="accordion-body text-body-secondary">Este es el contenido del tercer elemento. Está oculto por defecto.</div></div></div>'
        + '</div>'
});

gjsEditor.BlockManager.add('accordion-flush', {
    category: ACCORDION,
    label: 'Acordeón sin bordes',
    media: '<i class="bi bi-list-task fs-3"></i>',
    description: 'Acordeón con estilo flush (sin bordes externos)',
    content: '<div class="accordion accordion-flush" id="accordionFlushExample">'
        + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">Elemento #1</button></h2>'
        + '<div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample"><div class="accordion-body text-body-secondary">Contenido de ejemplo para este acordeón con la clase <code>.accordion-flush</code> para eliminar bordes y bordes redondeados.</div></div></div>'
        + '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">Elemento #2</button></h2>'
        + '<div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample"><div class="accordion-body text-body-secondary">Otro panel con contenido adicional.</div></div></div>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 📣 LLAMADAS A LA ACCIÓN
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('action-serenity', {
    category: ACTIONS,
    label: 'CTA Serenity',
    media: '<i class="bi bi-quote fs-3"></i>',
    description: 'CTA con cita inspiradora y botón',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><div class="row"><div class="col-md-8 mx-auto text-center">'
        + '<i class="bi bi-quote display-4 text-primary mb-3 d-block"></i>'
        + '<p class="lead fst-italic mb-4">"Una maravillosa serenidad se ha apoderado de mi alma entera, como estas dulces mañanas de primavera que disfruto con todo mi corazón."</p>'
        + '<a class="btn btn-primary btn-lg px-4" href="#">Actúa ahora</a>'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('action-bold', {
    category: ACTIONS,
    label: 'CTA Audaz',
    media: '<i class="bi bi-megaphone fs-3"></i>',
    description: 'CTA con texto destacado en negrita',
    content: '<section class="py-5 bg-dark text-white" data-bs-theme="dark"><div class="container"><div class="row"><div class="col-md-8 mx-auto text-center">'
        + '<p class="display-6 fw-bold mb-4">Cuando el encantador valle se llena de vapor a mi alrededor, y el sol del mediodía golpea la superficie impenetrable del follaje de mis árboles.</p>'
        + '<a class="btn btn-warning btn-lg px-4" href="#">Haz algo</a>'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('action-friends', {
    category: ACTIONS,
    label: 'Redes sociales',
    media: '<i class="bi bi-share fs-3"></i>',
    description: 'CTA con iconos de redes sociales',
    content: '<section class="py-5"><div class="container text-center">'
        + '<h1 class="display-5 fw-bold mb-4">Nos <i class="bi bi-heart-fill d-inline text-danger"></i> los nuevos amigos</h1>'
        + '<div class="d-flex justify-content-center gap-3 fs-2">'
        + '<a href="#" class="text-body-secondary" aria-label="Facebook"><i class="bi bi-facebook"></i></a>'
        + '<a href="#" class="text-body-secondary" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
        + '<a href="#" class="text-body-secondary" aria-label="Instagram"><i class="bi bi-instagram"></i></a>'
        + '<a href="#" class="text-body-secondary" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>'
        + '<a href="#" class="text-body-secondary" aria-label="YouTube"><i class="bi bi-youtube"></i></a>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('action-split', {
    category: ACTIONS,
    label: 'CTA dividido',
    media: '<i class="bi bi-layout-split fs-3"></i>',
    description: 'CTA con texto a la izquierda e imagen a la derecha',
    content: '<section class="py-5"><div class="container"><div class="row align-items-center g-5">'
        + '<div class="col-lg-7">'
        + '<h1 class="display-5 fw-bold mb-3">No esperes. Actúa.</h1>'
        + '<p class="lead text-body-secondary mb-4">Estoy solo, y siento el encanto de la existencia en este lugar.</p>'
        + '<h3 class="h5 fw-bold">Característica principal</h3>'
        + '<p class="text-body-secondary mb-4">Me dejo caer entre la hierba alta junto al arroyo; y, mientras yago cerca de la tierra, descubro mil plantas desconocidas.</p>'
        + '<a class="btn btn-primary btn-lg px-4" href="#">Llamada a la acción</a>'
        + '</div>'
        + '<div class="col-lg-5"><img class="img-fluid rounded-3 shadow" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen" alt="Imagen"></div>'
        + '</div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🔘 BOTONES
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('btn-primary', {
    category: BUTTONS,
    label: 'Botón primario',
    media: '<i class="bi bi-square-fill fs-3"></i>',
    description: 'Botón de estilo primario',
    content: '<a class="btn btn-primary" href="#">Botón</a>'
});

gjsEditor.BlockManager.add('btn-outline-primary', {
    category: BUTTONS,
    label: 'Botón contorno',
    media: '<i class="bi bi-square fs-3"></i>',
    description: 'Botón con borde y sin relleno',
    content: '<a class="btn btn-outline-primary" href="#">Contorno</a>'
});

gjsEditor.BlockManager.add('btn-group', {
    category: BUTTONS,
    label: 'Grupo de botones',
    media: '<i class="bi bi-ui-checks-grid fs-3"></i>',
    description: 'Varios botones agrupados',
    content: '<div class="btn-group" role="group" aria-label="Grupo de botones">'
        + '<a href="#" class="btn btn-primary">Botón 1</a>'
        + '<a href="#" class="btn btn-primary">Botón 2</a>'
        + '<a href="#" class="btn btn-primary">Botón 3</a>'
        + '</div>'
});

gjsEditor.BlockManager.add('btn-dropdown', {
    category: BUTTONS,
    label: 'Botón desplegable',
    media: '<i class="bi bi-caret-down-square fs-3"></i>',
    description: 'Botón con menú desplegable',
    content: '<div class="btn-group">'
        + '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Desplegable</button>'
        + '<ul class="dropdown-menu">'
        + '<li><a class="dropdown-item" href="#">Acción</a></li>'
        + '<li><a class="dropdown-item" href="#">Otra acción</a></li>'
        + '<li><hr class="dropdown-divider"></li>'
        + '<li><a class="dropdown-item" href="#">Enlace separado</a></li>'
        + '</ul></div>'
});

gjsEditor.BlockManager.add('btn-social-twitter', {
    category: BUTTONS,
    label: 'Botón Twitter / X',
    media: '<i class="bi bi-twitter-x fs-3"></i>',
    description: 'Botón con estilo de Twitter',
    content: '<a class="btn btn-dark" href="#" target="_blank"><i class="bi bi-twitter-x me-2"></i>Tweet</a>'
});

gjsEditor.BlockManager.add('btn-social-facebook', {
    category: BUTTONS,
    label: 'Botón Facebook',
    media: '<i class="bi bi-facebook fs-3"></i>',
    description: 'Botón con estilo de Facebook',
    content: '<a class="btn btn-primary" href="#" target="_blank"><i class="bi bi-facebook me-2"></i>Compartir</a>'
});

// ══════════════════════════════════════════════════════════════════
// 📰 ARTÍCULOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('article-header-center', {
    category: ARTICLES,
    label: 'Cabecera centrada',
    media: '<i class="bi bi-type-h1 fs-3"></i>',
    description: 'Artículo con cabecera centrada',
    content: '<section class="py-5 text-center"><div class="container"><div class="row"><div class="mx-auto col-md-8">'
        + '<h1 class="display-5 fw-bold mb-3">Me dejo caer entre la hierba alta, mil plantas desconocidas se me revelan.</h1>'
        + '<p class="lead text-body-secondary">Cuando el encantador valle se llena de vapor a mi alrededor.</p>'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('article-split-image', {
    category: ARTICLES,
    label: 'Artículo con imagen',
    media: '<i class="bi bi-image fs-3"></i>',
    description: 'Artículo dividido con texto e imagen',
    content: '<section class="py-5"><div class="container"><div class="row align-items-center g-5">'
        + '<div class="col-lg-6"><h1 class="display-5 fw-bold mb-3">Oh, mi amigo</h1>'
        + '<p class="lead text-body-secondary mb-0">Oigo el zumbido del pequeño mundo entre los tallos, y me familiarizo con las innumerables formas indescriptibles de los insectos y las moscas.</p></div>'
        + '<div class="col-lg-6"><img class="img-fluid rounded-3 shadow-sm" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen" alt="Imagen"></div>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('article-features-list', {
    category: ARTICLES,
    label: 'Lista de características',
    media: '<i class="bi bi-list-check fs-3"></i>',
    description: 'Artículo con lista de características destacadas',
    content: '<section class="py-5"><div class="container"><div class="row"><div class="mx-auto col-lg-6 col-md-8">'
        + '<ul class="list-unstyled">'
        + '<li class="mb-3 d-flex gap-3"><i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i><div><strong>Absorbido</strong> <span class="text-body-secondary">en el exquisito sentido de la mera existencia tranquila, que descuido mis talentos.</span></div></li>'
        + '<li class="mb-3 d-flex gap-3"><i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i><div><strong>Momento</strong> <span class="text-body-secondary">sin embargo siento que nunca fui un artista mayor que ahora.</span></div></li>'
        + '<li class="mb-3 d-flex gap-3"><i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i><div><strong>Sol</strong> <span class="text-body-secondary">golpea la superficie superior del follaje impenetrable de mis árboles.</span></div></li>'
        + '</ul></div></div></section>'
});

gjsEditor.BlockManager.add('article-quote', {
    category: ARTICLES,
    label: 'Cita destacada',
    media: '<i class="bi bi-quote fs-3"></i>',
    description: 'Artículo con cita en bloque',
    content: '<section class="py-5"><div class="container"><div class="row"><div class="col-md-9 mx-auto">'
        + '<figure class="border-start border-4 border-primary ps-4 py-2">'
        + '<blockquote class="mb-2"><p class="lead fst-italic mb-0">"Mientras yago cerca de la tierra, mil plantas desconocidas se me revelan: cuando oigo el zumbido del pequeño mundo entre los tallos, y me familiarizo con las innumerables formas de los insectos y las moscas, entonces siento la presencia del Todopoderoso."</p></blockquote>'
        + '<figcaption class="text-body-secondary small">— Autor de la cita</figcaption>'
        + '</figure></div></div></section>'
});

console.log('[advance] ✓ Bloques avanzados modernizados con 5 categorías lógicas');
})();