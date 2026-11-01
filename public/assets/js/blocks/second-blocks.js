// ═══════════════════════════════════════════════════════════════════
// second-blocks.js — Bloques complementarios Bootstrap 5.3.3
//
// Modernización:
//  - navbar-light → data-bs-theme="light"
//  - bg-light → bg-body-tertiary
//  - text-muted → text-body-secondary
//  - landing-page: CSS inline → clases Bootstrap
//  - Categorías unificadas en español
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[second-blocks] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var LAYOUT   = { id: 'layout',   label: '📐 Layout',          open: true };
var CONTENT  = { id: 'content',  label: '📝 Contenido',       open: false };
var FORMS    = { id: 'forms',    label: '📥 Formularios',     open: false };
var UTILS    = { id: 'utils',    label: '🔧 Utilidades',      open: false };
var MKTG     = { id: 'marketing',label: '📣 Marketing',       open: false };
var TEMPL    = { id: 'templates',label: '📄 Plantillas',      open: false };

// ── LAYOUT ──────────────────────────────────────────────────────
gjsEditor.BlockManager.add('navbar-bs', {
    category: LAYOUT,
    label: 'Navbar',
    media: '<i class="bi bi-list-check fs-3"></i>',
    description: 'Barra de navegación responsive',
    content: '<nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm" data-bs-theme="light">'
        + '<div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">🍇 GrapesCMS</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="mainNav"><ul class="navbar-nav ms-auto mb-2 mb-lg-0">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '</ul></div></div></nav>'
});

gjsEditor.BlockManager.add('hero-section', {
    category: LAYOUT,
    label: 'Hero',
    media: '<i class="bi bi-badge-hd fs-3"></i>',
    description: 'Cabecera principal con CTA',
    content: '<section class="bg-primary text-white py-5" data-bs-theme="dark"><div class="container text-center py-5">'
        + '<h1 class="display-4 fw-bold mb-3">Título Principal</h1>'
        + '<p class="lead mb-4 opacity-75">Subtítulo descriptivo que captura la atención del visitante.</p>'
        + '<div class="d-grid gap-2 d-sm-flex justify-content-sm-center">'
        + '<button class="btn btn-light btn-lg px-4">Comenzar</button>'
        + '<button class="btn btn-outline-light btn-lg px-4">Saber más</button>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('features-3col', {
    category: LAYOUT,
    label: 'Features 3 columnas',
    media: '<i class="bi bi-grid-3x3 fs-3"></i>',
    description: 'Tres características con iconos en tarjetas',
    content: '<section class="py-5"><div class="container"><div class="row g-4 text-center">'
        + [['bi-lightning-charge', 'Rápido', 'Descripción breve del beneficio o característica.'],
           ['bi-shield-check', 'Seguro', 'Descripción breve del beneficio o característica.'],
           ['bi-gear', 'Flexible', 'Descripción breve del beneficio o característica.']].map(function(f){
            return '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm p-4">'
                + '<div class="fs-1 text-primary mb-3"><i class="bi ' + f[0] + '" aria-hidden="true"></i></div>'
                + '<h5 class="card-title fw-bold">' + f[1] + '</h5>'
                + '<p class="card-text text-body-secondary">' + f[2] + '</p>'
                + '</div></div>';
        }).join('')
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('footer-custom', {
    category: LAYOUT,
    label: 'Footer',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie de página con enlaces y redes sociales',
    content: '<footer class="bg-dark text-white py-5 mt-5" data-bs-theme="dark"><div class="container"><div class="row g-4">'
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
        + '</div><p class="mt-3 text-white-50 small">&copy; 2026 Todos los derechos reservados.</p></div>'
        + '</div></div></footer>'
});

// ── CONTENIDO ───────────────────────────────────────────────────
gjsEditor.BlockManager.add('image-text-split', {
    category: CONTENT,
    label: 'Imagen + Texto',
    media: '<i class="bi bi-image-text fs-3"></i>',
    description: 'Sección dividida con imagen y texto alternados',
    content: '<section class="py-5"><div class="container"><div class="row align-items-center g-5">'
        + '<div class="col-lg-6"><img src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen+Editable" class="img-fluid rounded-3 shadow-sm" alt="Placeholder"></div>'
        + '<div class="col-lg-6"><h2 class="fw-bold mb-3">Título de la sección</h2>'
        + '<p class="lead text-body-secondary">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>'
        + '<p class="mb-4 text-body-secondary">Más detalles y contenido editable que puedes personalizar según tus necesidades.</p>'
        + '<a href="#" class="btn btn-outline-primary">Leer más</a></div>'
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('testimonial-card', {
    category: CONTENT,
    label: 'Testimonio',
    media: '<i class="bi bi-chat-quote fs-3"></i>',
    description: 'Tarjeta de testimonio con avatar y valoración',
    content: '<div class="bg-body-tertiary p-4 rounded-3 shadow-sm my-3">'
        + '<div class="d-flex align-items-center mb-3">'
        + '<img src="https://placehold.co/60x60/6c757d/ffffff?text=U" class="rounded-circle me-3" width="60" height="60" alt="Avatar">'
        + '<div><h6 class="mb-0 fw-bold">Nombre Usuario</h6><small class="text-body-secondary">Cargo / Empresa</small></div></div>'
        + '<blockquote class="blockquote mb-0 small"><p class="fst-italic mb-2">"Excelente servicio, superó todas mis expectativas. Totalmente recomendado."</p>'
        + '<footer class="blockquote-footer mt-2 text-body-secondary mb-0"><cite>★★★★★</cite></footer>'
        + '</blockquote></div>'
});

gjsEditor.BlockManager.add('pricing-card', {
    category: CONTENT,
    label: 'Plan de precios',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Tarjeta de precio con características y botón',
    content: '<div class="card border-0 shadow-sm text-center p-3 my-3">'
        + '<div class="card-header bg-transparent border-0 pt-3"><h4 class="fw-bold">Plan Pro</h4>'
        + '<div class="display-6 fw-bold my-3">29 €<span class="fs-6 text-body-secondary">/mes</span></div></div>'
        + '<div class="card-body"><ul class="list-unstyled mb-4 small">'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Característica 1</li>'
        + '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Característica 2</li>'
        + '<li class="text-body-secondary"><i class="bi bi-x-circle text-body-secondary me-2" aria-hidden="true"></i>Característica 3</li>'
        + '</ul><button class="btn btn-outline-primary w-100">Seleccionar</button></div></div>'
});

// ── FORMULARIOS ─────────────────────────────────────────────────
gjsEditor.BlockManager.add('contact-form-card', {
    category: FORMS,
    label: 'Formulario de contacto',
    media: '<i class="bi bi-envelope-paper fs-3"></i>',
    description: 'Formulario completo en tarjeta con sombra',
    content: '<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-md-8">'
        + '<div class="card border-0 shadow"><div class="card-body p-5">'
        + '<h3 class="text-center mb-4">Contáctanos</h3>'
        + '<form><div class="row g-3">'
        + '<div class="col-md-6"><label class="form-label" for="cf2Name">Nombre</label><input type="text" class="form-control" id="cf2Name" placeholder="Tu nombre" autocomplete="name"></div>'
        + '<div class="col-md-6"><label class="form-label" for="cf2Email">Email</label><input type="email" class="form-control" id="cf2Email" placeholder="correo@ejemplo.com" autocomplete="email"></div>'
        + '<div class="col-12"><label class="form-label" for="cf2Subject">Asunto</label><input type="text" class="form-control" id="cf2Subject" placeholder="Asunto del mensaje"></div>'
        + '<div class="col-12"><label class="form-label" for="cf2Msg">Mensaje</label><textarea class="form-control" id="cf2Msg" rows="4" placeholder="Escribe tu mensaje aquí..."></textarea></div>'
        + '<div class="col-12 text-center mt-4"><button type="submit" class="btn btn-primary btn-lg px-5">Enviar mensaje</button></div>'
        + '</div></form></div></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('newsletter-box', {
    category: FORMS,
    label: 'Newsletter',
    media: '<i class="bi bi-mailbox fs-3"></i>',
    description: 'Caja de suscripción con input y botón',
    content: '<div class="bg-primary text-white p-4 rounded-3 shadow-sm my-3" data-bs-theme="dark">'
        + '<div class="row align-items-center g-3">'
        + '<div class="col-md-6"><h5 class="mb-1">📬 Suscríbete al boletín</h5>'
        + '<p class="mb-0 small opacity-75">Recibe las últimas novedades directamente en tu correo.</p></div>'
        + '<div class="col-md-6"><form><div class="input-group">'
        + '<input type="email" class="form-control" placeholder="tu@email.com" aria-label="Correo electrónico" autocomplete="email">'
        + '<button class="btn btn-light fw-bold" type="submit">Suscribir</button>'
        + '</div></form></div></div></div>'
});

// ── UTILIDADES ──────────────────────────────────────────────────
gjsEditor.BlockManager.add('divider', {
    category: UTILS,
    label: 'Divisor',
    media: '<i class="bi bi-hr fs-3"></i>',
    description: 'Línea horizontal separadora',
    content: '<hr class="my-5 opacity-25">'
});

gjsEditor.BlockManager.add('spacer', {
    category: UTILS,
    label: 'Espaciador',
    media: '<i class="bi bi-arrows-expand fs-3"></i>',
    description: 'Espacio vertical transparente',
    content: '<div class="py-5 bg-transparent" aria-hidden="true"></div>'
});

gjsEditor.BlockManager.add('cta-banner', {
    category: UTILS,
    label: 'Llamada a la acción',
    media: '<i class="bi bi-megaphone fs-3"></i>',
    description: 'Banner CTA con botón destacado',
    content: '<section class="py-4 bg-warning"><div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-center text-md-start">'
        + '<div><h4 class="fw-bold mb-1">¿Listo para comenzar?</h4>'
        + '<p class="mb-0 text-dark opacity-75">Únete a miles de usuarios que ya confían en nosotros.</p></div>'
        + '<a href="#" class="btn btn-dark btn-lg px-4 flex-shrink-0">Crear cuenta gratis</a>'
        + '</div></section>'
});

// ── PLANTILLAS ──────────────────────────────────────────────────
gjsEditor.BlockManager.add('landing-page', {
    category: TEMPL,
    label: 'Landing',
    media: '<i class="bi bi-window-stack fs-3"></i>',
    description: 'Landing page completa con hero y features (sin CSS inline)',
    content: '<main>'
        + '<section class="py-5 text-white text-center" style="background:#0f172a;min-height:400px;display:flex;align-items:center" data-bs-theme="dark">'
        + '<div class="container"><h1 class="display-4 fw-bold mb-3">Mi Startup</h1>'
        + '<p class="lead opacity-75 mb-0">Landing creada con GrapesJS</p></div></section>'
        + '<section class="py-5"><div class="container">'
        + '<div class="row g-4">'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary rounded-3 h-100"><h5 class="fw-bold">Feature 1</h5><p class="text-body-secondary mb-0">Descripción breve de la primera característica.</p></div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary rounded-3 h-100"><h5 class="fw-bold">Feature 2</h5><p class="text-body-secondary mb-0">Descripción breve de la segunda característica.</p></div></div>'
        + '<div class="col-md-4"><div class="p-4 bg-body-tertiary rounded-3 h-100"><h5 class="fw-bold">Feature 3</h5><p class="text-body-secondary mb-0">Descripción breve de la tercera característica.</p></div></div>'
        + '</div></div></section></main>'
});

console.log('[second-blocks] ✓ Bloques complementarios modernizados con Bootstrap 5.3.3');
})();