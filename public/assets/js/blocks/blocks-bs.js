// ═══════════════════════════════════════════════════════════════════
// blocks.js — Bloques básicos con categorización lógica
//
// Mejoras aplicadas sobre la versión anterior:
//  - Categorías separadas por tipo (Estructura, Layout, Formularios,
//    Datos, Media, Tarjetas, Utilidades, Navegación, Hero)
//  - Emojis descriptivos en cada categoría (consistencia visual)
//  - Accesibilidad: aria-label, aria-current, role, visually-hidden
//  - Variables de tema BS 5.3+: text-body-secondary, bg-body-tertiary
//  - placehold.co con paleta coherente (en lugar de /eee/999)
//  - Descripciones detalladas para cada bloque
//  - Uso de text-bg-* en lugar de bg-* + text-white
//  - IDs originales conservados (nada que los referencie se rompe)
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (reescritura con categorías lógicas)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[blocks] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var STRUCT = { id: 'px-structure', label: '🧱 Estructura',      open: true };
var LAYOUT = { id: 'px-layout',    label: '📐 Layout',          open: false };
var FORMS  = { id: 'px-forms',     label: '📝 Formularios',     open: false };
var DATA   = { id: 'px-data',      label: '📊 Datos',           open: false };
var MEDIA  = { id: 'px-media',     label: '🖼️ Media',           open: false };
var CARDS  = { id: 'px-cards',     label: '💳 Tarjetas',        open: false };
var UTILS  = { id: 'px-utils',     label: '🔧 Utilidades',      open: false };
var NAV    = { id: 'px-nav',       label: '🧭 Navegación',      open: false };
var HERO   = { id: 'px-hero',      label: '🦸 Hero / Portada',  open: false };

/** Envuelve contenido en una sección estándar con espaciado Bootstrap. */
function section(inner, extra) {
    return '<section class="py-5' + (extra ? ' ' + extra : '') + '">'
        + '<div class="container">' + inner + '</div></section>';
}

/** Guía visual de columna vacía: borde discontinuo del tema. */
var colGuide = 'border border-2 border-secondary-subtle rounded-3 p-3 text-center small text-body-secondary';

// ══════════════════════════════════════════════════════════════════
// 🧱 ESTRUCTURA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('section', {
    category: STRUCT,
    label: 'Sección',
    media: '<i class="bi bi-layout-text-window fs-3"></i>',
    description: 'Sección con container y espaciado vertical de Bootstrap',
    content: section(
        '<h2 class="fw-bold mb-3">Título de la sección</h2>'
        + '<p class="text-body-secondary mb-0">Contenido de la sección. Sustituye este texto por el tuyo.</p>'
    )
});

gjsEditor.BlockManager.add('heading', {
    category: STRUCT,
    label: 'Título',
    media: '<i class="bi bi-type-h1 fs-3"></i>',
    description: 'Encabezado h2 con tipografía Bootstrap',
    content: '<h2 class="fw-bold">Título principal</h2>'
});

gjsEditor.BlockManager.add('text', {
    category: STRUCT,
    label: 'Texto',
    media: '<i class="bi bi-paragraph fs-3"></i>',
    description: 'Párrafo con line-height y color del tema',
    content: section(
        '<p class="mb-0 lh-lg text-body-secondary">'
        + 'Párrafo de ejemplo. Escribe aquí tu texto: la clase <code>lh-lg</code> da interlineado cómodo y '
        + '<code>text-body-secondary</code> suaviza el color secundario del tema.</p>'
    )
});

gjsEditor.BlockManager.add('image', {
    category: STRUCT,
    label: 'Imagen',
    media: '<i class="bi bi-image fs-3"></i>',
    description: 'Imagen responsive con figure y caption',
    content: '<figure class="figure w-100 mb-0">'
        + '<img src="https://placehold.co/1200x600/e9ecef/6c757d?text=Imagen" class="figure-img img-fluid rounded-3 w-100" alt="Descripción de la imagen">'
        + '<figcaption class="figure-caption text-body-secondary small mt-2">Pie de foto opcional</figcaption>'
        + '</figure>'
});

gjsEditor.BlockManager.add('button', {
    category: STRUCT,
    label: 'Botón',
    media: '<i class="bi bi-hand-index fs-3"></i>',
    description: 'Botón primario de Bootstrap',
    content: '<a href="#" class="btn btn-primary px-4">Texto del botón</a>'
});

gjsEditor.BlockManager.add('input-group', {
    category: STRUCT,
    label: 'Campo con botón',
    media: '<i class="bi bi-input-cursor fs-3"></i>',
    description: 'Grupo de entrada con botón adjunto',
    content: '<div class="input-group">'
        + '<input type="text" class="form-control" placeholder="Escribe aquí" aria-label="Campo de búsqueda">'
        + '<button class="btn btn-outline-secondary" type="button">Buscar</button>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 📐 LAYOUT
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('flex-container', {
    category: LAYOUT,
    label: 'Contenedor flex',
    media: '<i class="bi bi-distribute-horizontal fs-3"></i>',
    description: 'Fila flex de Bootstrap con utilidades d-flex y gap',
    content: '<div class="d-flex gap-3 align-items-center justify-content-between p-3 border rounded-3">'
        + '<div class="' + colGuide + ' flex-fill">Elemento</div>'
        + '<div class="' + colGuide + ' flex-fill">Elemento</div>'
        + '</div>'
});

gjsEditor.BlockManager.add('row-2col', {
    category: LAYOUT,
    label: 'Fila de 2 columnas',
    media: '<i class="bi bi-layout-split fs-3"></i>',
    description: 'Grilla de 2 columnas que colapsa en móvil',
    content: '<div class="row g-4">'
        + '<div class="col-md-6"><div class="' + colGuide + '">Columna 1</div></div>'
        + '<div class="col-md-6"><div class="' + colGuide + '">Columna 2</div></div>'
        + '</div>'
});

gjsEditor.BlockManager.add('row-3col', {
    category: LAYOUT,
    label: 'Fila de 3 columnas',
    media: '<i class="bi bi-layout-three-columns fs-3"></i>',
    description: 'Grilla de 3 columnas responsive',
    content: '<div class="row g-4">'
        + ['1', '2', '3'].map(function (n) {
            return '<div class="col-md-4"><div class="' + colGuide + '">Columna ' + n + '</div></div>';
        }).join('')
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 📝 FORMULARIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('contact-form', {
    category: FORMS,
    label: 'Formulario de contacto',
    media: '<i class="bi bi-envelope-paper fs-3"></i>',
    description: 'Formulario de contacto con grilla y validación visual Bootstrap',
    content: section(
        '<form class="mx-auto" style="max-width:560px">'
        + '<div class="row g-3">'
        + '<div class="col-md-6"><label class="form-label" for="cfName">Nombre</label>'
        + '<input type="text" class="form-control" id="cfName" placeholder="Tu nombre" autocomplete="name"></div>'
        + '<div class="col-md-6"><label class="form-label" for="cfEmail">Correo</label>'
        + '<input type="email" class="form-control" id="cfEmail" placeholder="tu@correo.com" autocomplete="email"></div>'
        + '<div class="col-12"><label class="form-label" for="cfMsg">Mensaje</label>'
        + '<textarea class="form-control" id="cfMsg" rows="4" placeholder="Cuéntanos"></textarea></div>'
        + '<div class="col-12"><button type="submit" class="btn btn-primary">Enviar</button></div>'
        + '</div></form>'
    )
});

// ══════════════════════════════════════════════════════════════════
// 📊 DATOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('table', {
    category: DATA,
    label: 'Tabla',
    media: '<i class="bi bi-table fs-3"></i>',
    description: 'Tabla responsive con filas alternas y hover',
    content: '<div class="table-responsive rounded-3 shadow-sm">'
        + '<table class="table table-striped table-hover align-middle mb-0">'
        + '<thead class="table-light"><tr><th scope="col">#</th><th scope="col">Concepto</th><th scope="col" class="text-end">Importe</th></tr></thead>'
        + '<tbody><tr><th scope="row">1</th><td>Servicio de diseño</td><td class="text-end">1.200 €</td></tr>'
        + '<tr><th scope="row">2</th><td>Desarrollo</td><td class="text-end">3.400 €</td></tr></tbody>'
        + '</table></div>'
});

gjsEditor.BlockManager.add('list', {
    category: DATA,
    label: 'Lista',
    media: '<i class="bi bi-list-ul fs-3"></i>',
    description: 'Lista con list-group de Bootstrap',
    content: '<ul class="list-group shadow-sm">'
        + '<li class="list-group-item active" aria-current="true">Elemento activo</li>'
        + '<li class="list-group-item">Segundo elemento</li>'
        + '<li class="list-group-item">Tercer elemento</li>'
        + '</ul>'
});

// ══════════════════════════════════════════════════════════════════
// 🖼️ MEDIA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('map', {
    category: MEDIA,
    label: 'Mapa',
    media: '<i class="bi bi-geo-alt fs-3"></i>',
    description: 'Mapa embebido con proporción 16:9 y sin scripts',
    content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">'
        + '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-3.72%2C40.40%2C-3.66%2C40.43&amp;layer=mapnik" title="Mapa" loading="lazy" style="border:0"></iframe>'
        + '</div>'
});

gjsEditor.BlockManager.add('video', {
    category: MEDIA,
    label: 'Vídeo',
    media: '<i class="bi bi-play-btn fs-3"></i>',
    description: 'Vídeo responsive con la clase ratio de Bootstrap',
    content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">'
        + '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Vídeo" allowfullscreen loading="lazy"></iframe>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 💳 TARJETAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('card', {
    category: CARDS,
    label: 'Tarjeta',
    media: '<i class="bi bi-card-text fs-3"></i>',
    description: 'Card con título, texto y botón',
    content: '<div class="card h-100 shadow-sm border-0">'
        + '<div class="card-body p-4">'
        + '<h3 class="h5 fw-bold card-title">Título de la tarjeta</h3>'
        + '<p class="card-text text-body-secondary">Texto breve que acompaña al título.</p>'
        + '<a href="#" class="btn btn-primary btn-sm">Acción</a>'
        + '</div></div>'
});

gjsEditor.BlockManager.add('pricing', {
    category: CARDS,
    label: 'Precio',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Tarjeta de precio con importe y botón',
    content: '<div class="card text-center h-100 border-primary border-2 shadow-sm">'
        + '<div class="card-body p-4">'
        + '<p class="text-uppercase small fw-bold text-primary mb-2">Plan</p>'
        + '<p class="display-5 fw-bold mb-1">29 €<span class="fs-6 fw-normal text-body-secondary">/mes</span></p>'
        + '<p class="text-body-secondary small mb-4">Todo lo necesario para empezar</p>'
        + '<a href="#" class="btn btn-primary w-100">Elegir plan</a>'
        + '</div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🦸 HERO / PORTADA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('hero', {
    category: HERO,
    label: 'Hero',
    media: '<i class="bi bi-easel fs-3"></i>',
    description: 'Cabecera centrada con titular, subtítulo y CTA',
    content: '<section class="py-5 text-center bg-body-tertiary">'
        + '<div class="container py-4">'
        + '<h1 class="display-5 fw-bold mb-3">Construye algo que la gente quiera usar</h1>'
        + '<p class="lead text-body-secondary mx-auto mb-4" style="max-width:640px">Una frase que explique qué haces y para quién.</p>'
        + '<a href="#" class="btn btn-primary btn-lg px-4">Empezar ahora</a>'
        + '</div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🔧 UTILIDADES
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('icon', {
    category: UTILS,
    label: 'Icono',
    media: '<i class="bi bi-star fs-3"></i>',
    description: 'Icono de Bootstrap Icons',
    content: '<i class="bi bi-star-fill fs-1 text-primary" aria-hidden="true"></i>'
});

gjsEditor.BlockManager.add('badge', {
    category: UTILS,
    label: 'Etiqueta',
    media: '<i class="bi bi-tag-fill fs-3"></i>',
    description: 'Badge de Bootstrap con color semántico',
    content: '<span class="badge rounded-pill text-bg-primary">Nuevo</span>'
});

gjsEditor.BlockManager.add('progress', {
    category: UTILS,
    label: 'Barra de progreso',
    media: '<i class="bi bi-bar-chart-steps fs-3"></i>',
    description: 'Barra de progreso con rol y valores accesibles',
    content: '<div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="height:10px">'
        + '<div class="progress-bar" style="width:65%"></div></div>'
});

gjsEditor.BlockManager.add('alert', {
    category: UTILS,
    label: 'Alerta',
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>',
    description: 'Alerta de éxito con icono y rol accesible',
    content: '<div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="alert">'
        + '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>'
        + '<div>Operación completada correctamente.</div>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 🧭 NAVEGACIÓN
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('navbar', {
    category: NAV,
    label: 'Barra de navegación',
    media: '<i class="bi bi-menu-button-wide fs-3"></i>',
    description: 'Navbar responsive con colapso Bootstrap',
    content: '<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom shadow-sm">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-semibold" href="#">Tu marca</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navBasic" aria-controls="navBasic" aria-expanded="false" aria-label="Alternar navegación">'
        + '<span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navBasic"><ul class="navbar-nav ms-auto">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul></div></div></nav>'
});

gjsEditor.BlockManager.add('footer', {
    category: NAV,
    label: 'Pie de página',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie simple con enlaces legales',
    content: '<footer class="py-4 border-top mt-5 bg-body-tertiary">'
        + '<div class="container d-flex flex-wrap justify-content-between gap-2 small text-body-secondary">'
        + '<span>© 2026 Tu marca. Todos los derechos reservados.</span>'
        + '<span><a href="#" class="link-secondary text-decoration-none">Privacidad</a> · <a href="#" class="link-secondary text-decoration-none">Términos</a></span>'
        + '</div></footer>'
});

console.log('[blocks] ✓ Bloques básicos modernizados con 9 categorías lógicas');
})();