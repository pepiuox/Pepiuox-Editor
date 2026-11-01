// ═══════════════════════════════════════════════════════════════════
// blocks-bs-advanced.js — Bloques avanzados Bootstrap 5.3.3
//
// Modernización:
//  - navbar-light/navbar-dark → data-bs-theme
//  - bg-light → bg-body-tertiary
//  - text-muted → text-body-secondary
//  - Categorías unificadas en español
//  - Accesibilidad ARIA completa
//  - placehold.co con paleta coherente
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[blocks-bs-advanced] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var MEDIA = { id: 'media', label: '🖼️ Media y embeds', open: false };
var INTER = { id: 'interactive', label: '🎛️ Interactivos', open: false };
var DATA  = { id: 'data', label: '📊 Datos y tablas', open: false };
var PROC  = { id: 'process', label: '👥 Procesos y equipo', open: false };

// ── MEDIA & EMBEDS ──────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-gallery', {
    category: MEDIA,
    label: 'Galería de imágenes',
    media: '<i class="bi bi-images fs-3"></i>',
    description: 'Galería responsive de 4 imágenes con tarjetas',
    content: '<section class="py-5 bg-body-tertiary">'
        + '<div class="container">'
        + '<h2 class="text-center mb-4 fw-bold">Galería</h2>'
        + '<div class="row g-3">'
        + [1,2,3,4].map(function(n){
            return '<div class="col-6 col-md-4 col-lg-3">'
                + '<div class="card border-0 shadow-sm h-100">'
                + '<img src="https://placehold.co/400x300/e9ecef/6c757d?text=' + n + '" class="card-img-top" alt="Imagen ' + n + '">'
                + '<div class="card-body p-2"><p class="mb-0 small text-body-secondary">Imagen ' + n + '</p></div>'
                + '</div></div>';
        }).join('')
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('bs-video', {
    category: MEDIA,
    label: 'Vídeo responsive',
    media: '<i class="bi bi-play-circle fs-3"></i>',
    description: 'Sección oscura con vídeo embebido 16:9',
    content: '<section class="py-5 bg-dark text-white" data-bs-theme="dark">'
        + '<div class="container text-center">'
        + '<h2 class="fw-bold mb-4">Vídeo demo</h2>'
        + '<div class="row justify-content-center"><div class="col-lg-10">'
        + '<div class="ratio ratio-16x9 shadow-lg rounded-3 overflow-hidden">'
        + '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Vídeo demo" allowfullscreen loading="lazy"></iframe>'
        + '</div>'
        + '<p class="mt-3 text-body-secondary small">Reproduce el vídeo para conocer más sobre nuestros servicios.</p>'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('bs-map', {
    category: MEDIA,
    label: 'Mapa interactivo',
    media: '<i class="bi bi-geo-alt fs-3"></i>',
    description: 'Mapa de Google embebido con información de ubicación',
    content: '<section class="py-4"><div class="container">'
        + '<div class="ratio ratio-21x9 shadow-sm rounded-3 overflow-hidden">'
        + '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-74.006%2C40.712%2C-73.935%2C40.761&layer=mapnik" title="Mapa" loading="lazy" style="border:0"></iframe>'
        + '</div>'
        + '<div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">'
        + '<h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Nuestra ubicación</h5>'
        + '<a href="#" class="btn btn-outline-primary btn-sm">Abrir en Maps</a>'
        + '</div></div></section>'
});

// ── INTERACTIVOS ────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-carousel', {
    category: INTER,
    label: 'Carrusel / Slider',
    media: '<i class="bi bi-collection-play fs-3"></i>',
    description: 'Carrusel de testimonios con indicadores y controles',
    content: '<div id="heroCarousel" class="carousel slide my-4 shadow-sm rounded-3 overflow-hidden" data-bs-ride="carousel">'
        + '<div class="carousel-indicators">'
        + [0,1,2].map(function(i){
            return '<button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="' + i + '"'
                + (i === 0 ? ' class="active" aria-current="true"' : '')
                + ' aria-label="Testimonio ' + (i+1) + '"></button>';
        }).join('')
        + '</div>'
        + '<div class="carousel-inner bg-primary text-white text-center py-5">'
        + [['"Excelente servicio y resultados profesionales."', 'Ana García', 'CEO, TechStart'],
           ['"La mejor herramienta para crear sin complicaciones."', 'Carlos Mendoza', 'Director Creativo'],
           ['"Soporte rápido y diseño moderno."', 'Laura Vega', 'Marketing Manager']].map(function(t, i){
            return '<div class="carousel-item' + (i === 0 ? ' active' : '') + '"><div class="container py-4">'
                + '<p class="lead fst-italic mb-4">' + t[0] + '</p>'
                + '<h5 class="fw-bold">' + t[1] + '</h5><small class="opacity-75">' + t[2] + '</small>'
                + '</div></div>';
        }).join('')
        + '</div>'
        + '<button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">'
        + '<span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>'
        + '<button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">'
        + '<span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button>'
        + '</div>'
});

gjsEditor.BlockManager.add('bs-modal', {
    category: INTER,
    label: 'Modal / Popup',
    media: '<i class="bi bi-window fs-3"></i>',
    description: 'Botón que abre una ventana modal centrada',
    content: '<div class="text-center my-4">'
        + '<button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#customModal1">Abrir modal</button>'
        + '</div>'
        + '<div class="modal fade" id="customModal1" tabindex="-1" aria-labelledby="customModal1Label" aria-hidden="true">'
        + '<div class="modal-dialog modal-dialog-centered">'
        + '<div class="modal-content">'
        + '<div class="modal-header"><h5 class="modal-title fw-bold" id="customModal1Label">Título del modal</h5>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
        + '<div class="modal-body"><p class="mb-0">Contenido editable. Ideal para formularios, avisos o detalles adicionales.</p></div>'
        + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>'
        + '<button type="button" class="btn btn-primary">Confirmar</button></div>'
        + '</div></div></div>'
});

gjsEditor.BlockManager.add('bs-offcanvas', {
    category: INTER,
    label: 'Panel lateral (Offcanvas)',
    media: '<i class="bi bi-layout-sidebar-inset fs-3"></i>',
    description: 'Panel deslizable desde la derecha',
    content: '<div class="text-center my-4">'
        + '<button class="btn btn-outline-dark btn-lg" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidePanel1" aria-controls="sidePanel1">Abrir panel</button>'
        + '</div>'
        + '<div class="offcanvas offcanvas-end" tabindex="-1" id="sidePanel1" aria-labelledby="sidePanel1Label">'
        + '<div class="offcanvas-header"><h5 class="offcanvas-title fw-bold" id="sidePanel1Label">Menú lateral</h5>'
        + '<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button></div>'
        + '<div class="offcanvas-body">'
        + '<p class="text-body-secondary">Contenido deslizable. Ideal para filtros, menús o formularios rápidos.</p>'
        + '<hr><h6 class="fw-bold">Enlaces</h6>'
        + '<ul class="nav flex-column">'
        + '<li class="nav-item mb-2"><a href="#" class="nav-link p-0">Inicio</a></li>'
        + '<li class="nav-item mb-2"><a href="#" class="nav-link p-0">Servicios</a></li>'
        + '</ul></div></div>'
});

// ── DATOS & TABLAS ──────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-table', {
    category: DATA,
    label: 'Tabla responsive',
    media: '<i class="bi bi-table fs-3"></i>',
    description: 'Tabla con filas alternas, hover y badges de estado',
    content: '<div class="container my-4">'
        + '<h4 class="mb-3 fw-bold">Listado de datos</h4>'
        + '<div class="table-responsive shadow-sm rounded-3">'
        + '<table class="table table-hover table-striped align-middle mb-0">'
        + '<thead class="table-light"><tr><th scope="col">#</th><th scope="col">Nombre</th><th scope="col">Cargo</th><th scope="col">Estado</th><th scope="col" class="text-center">Acción</th></tr></thead>'
        + '<tbody>'
        + '<tr><th scope="row">1</th><td>María López</td><td>Desarrolladora</td><td><span class="badge text-bg-success">Activo</span></td><td class="text-center"><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>'
        + '<tr><th scope="row">2</th><td>Juan Pérez</td><td>Diseñador</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td><td class="text-center"><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>'
        + '<tr><th scope="row">3</th><td>Sofía Ruiz</td><td>Marketing</td><td><span class="badge text-bg-danger">Inactivo</span></td><td class="text-center"><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>'
        + '</tbody></table></div></div>'
});

gjsEditor.BlockManager.add('bs-comparison', {
    category: DATA,
    label: 'Tabla comparativa',
    media: '<i class="bi bi-arrow-left-right fs-3"></i>',
    description: 'Comparación de planes con precios y características',
    content: '<section class="py-5"><div class="container">'
        + '<h3 class="text-center mb-4 fw-bold">Comparación de planes</h3>'
        + '<div class="table-responsive shadow-sm rounded-3">'
        + '<table class="table table-bordered align-middle text-center mb-0">'
        + '<thead class="table-light"><tr><th scope="col" class="text-start">Característica</th><th scope="col">Básico</th><th scope="col">Pro</th><th scope="col">Empresas</th></tr></thead>'
        + '<tbody>'
        + '<tr><th scope="row" class="text-start fw-bold">Usuarios</th><td>1</td><td>10</td><td>Ilimitados</td></tr>'
        + '<tr><th scope="row" class="text-start fw-bold">Almacenamiento</th><td>5 GB</td><td>50 GB</td><td>500 GB</td></tr>'
        + '<tr><th scope="row" class="text-start fw-bold">Soporte</th><td><i class="bi bi-dash text-body-secondary" aria-hidden="true"></i><span class="visually-hidden">No</span></td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i><span class="visually-hidden">Sí</span></td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i><span class="visually-hidden">Sí</span></td></tr>'
        + '<tr><th scope="row" class="text-start fw-bold">API</th><td><i class="bi bi-x-lg text-danger" aria-hidden="true"></i><span class="visually-hidden">No</span></td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i><span class="visually-hidden">Sí</span></td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i><span class="visually-hidden">Sí</span></td></tr>'
        + '<tr class="table-light fw-bold"><th scope="row" class="text-start">Precio</th><td>9 €/mes</td><td>29 €/mes</td><td>99 €/mes</td></tr>'
        + '</tbody></table></div></div></section>'
});

gjsEditor.BlockManager.add('bs-stats', {
    category: DATA,
    label: 'Estadísticas / Contadores',
    media: '<i class="bi bi-graph-up-arrow fs-3"></i>',
    description: 'Sección oscura con 4 cifras destacadas',
    content: '<section class="py-5 bg-dark text-white" data-bs-theme="dark">'
        + '<div class="container"><div class="row text-center g-4">'
        + [['1.250+', 'Clientes', 'text-warning'],
           ['98%', 'Satisfacción', 'text-info'],
           ['350+', 'Proyectos', 'text-success'],
           ['24/7', 'Soporte', 'text-danger']].map(function(s){
            return '<div class="col-6 col-md-3">'
                + '<div class="display-4 fw-bold ' + s[2] + '">' + s[0] + '</div>'
                + '<p class="mb-0 small text-uppercase opacity-75">' + s[1] + '</p>'
                + '</div>';
        }).join('')
        + '</div></div></section>'
});

// ── PROCESOS & EQUIPO ───────────────────────────────────────────
gjsEditor.BlockManager.add('bs-team', {
    category: PROC,
    label: 'Equipo de trabajo',
    media: '<i class="bi bi-people fs-3"></i>',
    description: 'Tres miembros del equipo con redes sociales',
    content: '<section class="py-5 bg-body-tertiary"><div class="container text-center">'
        + '<h2 class="fw-bold mb-5">Nuestro equipo</h2>'
        + '<div class="row g-4">'
        + [['1', 'Nombre Completo', 'Cargo / Especialidad'],
           ['2', 'Nombre Completo', 'Cargo / Especialidad'],
           ['3', 'Nombre Completo', 'Cargo / Especialidad']].map(function(m){
            return '<div class="col-md-4"><div class="card border-0 shadow-sm p-3 h-100">'
                + '<img src="https://placehold.co/150x150/6c757d/ffffff?text=' + m[0] + '" class="rounded-circle mx-auto mb-3" width="120" height="120" alt="Miembro del equipo">'
                + '<h5 class="fw-bold mb-1">' + m[1] + '</h5>'
                + '<p class="text-body-secondary small mb-3">' + m[2] + '</p>'
                + '<div class="d-flex justify-content-center gap-3 fs-5">'
                + '<a href="#" class="text-primary" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>'
                + '<a href="#" class="text-body-secondary" aria-label="GitHub"><i class="bi bi-github"></i></a>'
                + '<a href="#" class="text-info" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
                + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('bs-timeline', {
    category: PROC,
    label: 'Línea de tiempo',
    media: '<i class="bi bi-clock-history fs-3"></i>',
    description: 'Timeline vertical con hitos destacados',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<h2 class="text-center mb-5 fw-bold">Nuestra historia</h2>'
        + '<div class="row justify-content-center"><div class="col-lg-8">'
        + '<div class="border-start border-3 border-primary ps-4 ms-3">'
        + '<div class="mb-4 position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill text-bg-primary" style="left:-1.15rem!important">2020</span><h5 class="fw-bold">Fundación</h5><p class="text-body-secondary mb-0">Iniciamos el proyecto con visión clara.</p></div>'
        + '<div class="mb-4 position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill text-bg-success" style="left:-1.15rem!important">2022</span><h5 class="fw-bold">Primer hito</h5><p class="text-body-secondary mb-0">Lanzamiento beta y primeros 100 usuarios.</p></div>'
        + '<div class="position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill text-bg-warning text-dark" style="left:-1.15rem!important">2024</span><h5 class="fw-bold">Expansión</h5><p class="text-body-secondary mb-0">Superamos los 10.000 usuarios activos.</p></div>'
        + '</div></div></div></div></section>'
});

gjsEditor.BlockManager.add('bs-steps', {
    category: PROC,
    label: 'Pasos / Wizard',
    media: '<i class="bi bi-signpost-split fs-3"></i>',
    description: 'Wizard de 3 pasos con navegación',
    content: '<div class="container my-5">'
        + '<ul class="nav nav-pills nav-fill mb-4" role="tablist">'
        + '<li class="nav-item"><a class="nav-link active" href="#" aria-current="step">1. Cuenta</a></li>'
        + '<li class="nav-item"><a class="nav-link disabled" href="#" tabindex="-1">2. Datos</a></li>'
        + '<li class="nav-item"><a class="nav-link disabled" href="#" tabindex="-1">3. Confirmación</a></li>'
        + '</ul>'
        + '<div class="card border-0 shadow-sm p-4">'
        + '<h5 class="fw-bold mb-3">Completa tu registro</h5>'
        + '<p class="text-body-secondary mb-0">Ejemplo visual de wizard para formularios largos o checkout.</p>'
        + '<div class="d-flex justify-content-between mt-4"><button class="btn btn-outline-secondary" disabled>Anterior</button><button class="btn btn-primary">Siguiente</button></div>'
        + '</div></div>'
});

console.log('[blocks-bs-advanced] ✓ Bloques avanzados modernizados con Bootstrap 5.3.3');
})();