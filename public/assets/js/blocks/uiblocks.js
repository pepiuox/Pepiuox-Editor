// ═══════════════════════════════════════════════════════════════════
// uiblocks.js — Bloques UI con Bootstrap 5.3.3
//
// Modernización completa desde Bootstrap 4 a Bootstrap 5.3.3:
//  - data-dismiss → data-bs-dismiss
//  - data-toggle → data-bs-toggle
//  - sr-only → visually-hidden
//  - mr-auto → ms-auto
//  - close → btn-close
//  - Font Awesome (fa/fab) → Bootstrap Icons (bi)
//  - Clases fdb-box personalizadas → clases nativas BS5
//  - Categorías con emojis descriptivos
//  - IIFE con verificación de gjsEditor
//  - Accesibilidad ARIA completa
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (migración BS4 → BS5.3.3)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[uiblocks] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var HEADERS  = { id: 'ui-headers',  label: '🧭 Cabeceras',        open: false };
var FEAT     = { id: 'ui-features', label: '✨ Características',  open: false };
var PRICING  = { id: 'ui-pricing',  label: '💰 Precios',          open: false };
var TEAM     = { id: 'ui-team',     label: '👥 Equipo',           open: false };
var CARDS    = { id: 'ui-cards',    label: '💳 Tarjetas',         open: false };
var FORMS    = { id: 'ui-forms',    label: '📝 Formularios',      open: false };
var FOOTERS  = { id: 'ui-footers',  label: '🦶 Pies de página',   open: false };
var MEDIA    = { id: 'ui-media',    label: '🖼️ Media',            open: false };
var UICOMP   = { id: 'ui-components', label: '🧩 Componentes UI', open: false };

// ══════════════════════════════════════════════════════════════════
// 🧭 CABECERAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('navbar-dark', {
    category: HEADERS,
    label: 'Navbar Dark',
    media: '<i class="bi bi-menu-button-wide fs-3"></i>',
    description: 'Barra de navegación oscura responsive',
    content: '<header class="bg-dark" data-bs-theme="dark"><div class="container"><nav class="navbar navbar-expand-md navbar-dark">'
        + '<a class="navbar-brand" href="#"><img src="https://placehold.co/600x400/e9ecef/6c757d?text=Logo" height="30" alt="Logo"></a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDark" aria-controls="navbarDark" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navbarDark"><ul class="navbar-nav me-auto mb-2 mb-md-0">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Features</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>'
        + '</ul><a class="btn btn-outline-light ms-md-3" href="#">Button</a></div></nav></div></header>'
});

// ══════════════════════════════════════════════════════════════════
// ✨ CARACTERÍSTICAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('features-grid', {
    category: FEAT,
    label: 'Features Grid',
    media: '<i class="bi bi-grid-3x3 fs-3"></i>',
    description: 'Grid de tres características con iconos',
    content: '<section class="py-5"><div class="container">'
        + '<div class="row text-center"><div class="col-12"><h1 class="fw-bold">Features</h1></div></div>'
        + '<div class="row text-center justify-content-center mt-5 g-4">'
        + '<div class="col-12 col-sm-4 col-xl-3"><img alt="Icono 1" class="mb-3" src="https://placehold.co/80x80/667eea/white?text=1" width="80" height="80"><h3 class="h5 fw-bold">Feature 1</h3><p class="text-body-secondary">Descripción breve de la primera característica destacada.</p></div>'
        + '<div class="col-12 col-sm-4 col-xl-3"><img alt="Icono 2" class="mb-3" src="https://placehold.co/80x80/764ba2/white?text=2" width="80" height="80"><h3 class="h5 fw-bold">Feature 2</h3><p class="text-body-secondary">Descripción breve de la segunda característica destacada.</p></div>'
        + '<div class="col-12 col-sm-4 col-xl-3"><img alt="Icono 3" class="mb-3" src="https://placehold.co/80x80/f093fb/white?text=3" width="80" height="80"><h3 class="h5 fw-bold">Feature 3</h3><p class="text-body-secondary">Descripción breve de la tercera característica destacada.</p></div>'
        + '</div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 💰 PRECIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('pricing-3-cols', {
    category: PRICING,
    label: 'Pricing 3 Columns',
    media: '<i class="bi bi-tags fs-3"></i>',
    description: 'Tres planes de precios en columnas',
    content: '<section class="py-5"><div class="container">'
        + '<div class="row text-center"><div class="col"><h1 class="fw-bold">Pricing</h1></div></div>'
        + '<div class="row mt-5 align-items-center g-4">'
        + '<div class="col-12 col-lg-4"><div class="card h-100 border-0 shadow-sm p-4 text-center">'
        + '<h2 class="h4 fw-bold">Hobby</h2><p class="text-body-secondary">Plan básico para empezar</p>'
        + '<p class="display-5 fw-bold my-4">99 €</p>'
        + '<a href="#" class="btn btn-dark">Buy Now</a></div></div>'
        + '<div class="col-12 col-lg-4"><div class="card h-100 border-primary border-2 shadow p-4 text-center">'
        + '<h2 class="h4 fw-bold">Advanced</h2><p class="text-body-secondary">Para equipos en crecimiento</p>'
        + '<p class="display-5 fw-bold my-4">299 €</p>'
        + '<a href="#" class="btn btn-primary">Buy Now</a></div></div>'
        + '<div class="col-12 col-lg-4"><div class="card h-100 border-0 shadow-sm p-4 text-center">'
        + '<h2 class="h4 fw-bold">Business</h2><p class="text-body-secondary">Para grandes empresas</p>'
        + '<p class="display-5 fw-bold my-4">799 €</p>'
        + '<a href="#" class="btn btn-dark">Buy Now</a></div></div>'
        + '</div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 👥 EQUIPO
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('team-cards', {
    category: TEAM,
    label: 'Team Cards',
    media: '<i class="bi bi-people fs-3"></i>',
    description: 'Tarjetas del equipo con foto y cargo',
    content: '<section class="py-5"><div class="container">'
        + '<div class="row text-center justify-content-center"><div class="col-8"><h1 class="fw-bold">Our Team</h1><p class="lead text-body-secondary">Conoce a las personas detrás del proyecto.</p></div></div>'
        + '<div class="row g-4 mt-3">'
        + [['SD','Sara Doe','Founder'],['JS','John Smith','Co-founder'],['JD','Jane Doe','Designer'],['MR','Mike Ross','Developer']].map(function(m){
            return '<div class="col-sm-6 col-lg-3"><div class="card h-100 border-0 shadow-sm overflow-hidden">'
                + '<img src="https://placehold.co/400x300/667eea/white?text=' + m[0] + '" class="card-img-top" alt="' + m[1] + '" style="height:240px;object-fit:cover">'
                + '<div class="card-body p-3">'
                + '<h3 class="h6 fw-bold mb-1">' + m[1] + '</h3>'
                + '<p class="text-body-secondary small mb-0">' + m[2] + '</p>'
                + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 💳 TARJETAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('card-image-top', {
    category: CARDS,
    label: 'Card with Image',
    media: '<i class="bi bi-card-image fs-3"></i>',
    description: 'Tarjeta con imagen superior',
    content: '<div class="card shadow-sm border-0" style="max-width:22rem">'
        + '<img class="card-img-top" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen" alt="Imagen de la tarjeta">'
        + '<div class="card-body p-4">'
        + '<h4 class="card-title fw-bold">Card title</h4>'
        + '<p class="card-text text-body-secondary">Texto breve de ejemplo para construir sobre el título de la tarjeta.</p>'
        + '<a href="#" class="btn btn-primary">Go somewhere</a>'
        + '</div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 📝 FORMULARIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('contact-form-map', {
    category: FORMS,
    label: 'Contact Form + Map',
    media: '<i class="bi bi-envelope-paper fs-3"></i>',
    description: 'Formulario de contacto con mapa embebido',
    content: '<section class="py-5"><div class="container-fluid p-0">'
        + '<div class="ratio ratio-21x9"><iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-3.72%2C40.40%2C-3.66%2C40.43&layer=mapnik" title="Mapa" loading="lazy" style="border:0"></iframe></div>'
        + '</div><div class="container mt-5"><div class="row g-5">'
        + '<div class="col-12 col-md-6 col-lg-5"><h2 class="fw-bold">Contact Us</h2>'
        + '<p class="lead text-body-secondary">Cuéntanos qué necesitas y te respondemos en menos de 24 h.</p>'
        + '<p class="h5 mt-4"><strong class="text-body-secondary">Email:</strong> <a href="mailto:hello@website.com">hello@website.com</a></p>'
        + '<p class="lead"><strong class="text-body-secondary">Phone:</strong> <a href="tel:+441231231232">+44 123 123 1232</a></p></div>'
        + '<div class="col-12 col-md-6"><form><div class="row g-3">'
        + '<div class="col"><label class="form-label" for="cfFirst">First name</label><input type="text" class="form-control" id="cfFirst" placeholder="First name" autocomplete="given-name"></div>'
        + '<div class="col"><label class="form-label" for="cfLast">Last name</label><input type="text" class="form-control" id="cfLast" placeholder="Last name" autocomplete="family-name"></div>'
        + '<div class="col-12"><label class="form-label" for="cfEmail">Email</label><input type="email" class="form-control" id="cfEmail" placeholder="Enter email" autocomplete="email"></div>'
        + '<div class="col-12"><label class="form-label" for="cfMsg">Mensaje</label><textarea class="form-control" id="cfMsg" rows="3" placeholder="How can we help?"></textarea></div>'
        + '<div class="col-12"><button type="submit" class="btn btn-primary">Submit</button></div>'
        + '</div></form></div></div></div></section>'
});

gjsEditor.BlockManager.add('newsletter-form', {
    category: FORMS,
    label: 'Newsletter Subscribe',
    media: '<i class="bi bi-mailbox fs-3"></i>',
    description: 'Sección de suscripción a newsletter',
    content: '<section class="py-5 text-bg-primary text-center" data-bs-theme="dark"><div class="container">'
        + '<div class="row justify-content-center"><div class="col-md-8">'
        + '<h2 class="fw-bold">Subscribe to Our Newsletter</h2>'
        + '<p class="mb-4 opacity-75">Stay updated with our latest news and offers.</p>'
        + '<form class="d-flex justify-content-center gap-2 flex-wrap">'
        + '<input type="email" class="form-control" placeholder="Enter your email" style="max-width:300px" aria-label="Correo electrónico" autocomplete="email">'
        + '<button type="submit" class="btn btn-light">Subscribe</button>'
        + '</form></div></div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🦶 PIES DE PÁGINA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('footer-large', {
    category: FOOTERS,
    label: 'Large Footer',
    media: '<i class="bi bi-layout-text-window-reverse fs-3"></i>',
    description: 'Pie de página grande con múltiples columnas',
    content: '<footer class="py-5 bg-body-tertiary border-top"><div class="container">'
        + '<div class="row align-items-top text-center text-sm-start g-4">'
        + '<div class="col-12 col-sm-6 col-md-4 col-lg-3"><h3 class="h6 fw-bold">Group 1</h3>'
        + '<nav class="nav flex-column"><a class="nav-link px-0 active" aria-current="page" href="#">Home</a>'
        + '<a class="nav-link px-0" href="#">Features</a><a class="nav-link px-0" href="#">Pricing</a><a class="nav-link px-0" href="#">Team</a></nav></div>'
        + '<div class="col-12 col-sm-6 col-md-4 col-lg-3"><h3 class="h6 fw-bold">Group 2</h3>'
        + '<nav class="nav flex-column"><a class="nav-link px-0 active" href="#">Privacy Policy</a>'
        + '<a class="nav-link px-0" href="#">Terms</a><a class="nav-link px-0" href="#">FAQ</a><a class="nav-link px-0" href="#">Support</a></nav></div>'
        + '<div class="col-12 col-md-4 col-lg-3"><h3 class="h6 fw-bold">About Us</h3>'
        + '<p class="text-body-secondary small">Descripción breve de la empresa y su misión.</p></div>'
        + '<div class="col-12 col-lg-3"><h3 class="h6 fw-bold">Follow Us</h3>'
        + '<p class="lead"><a href="#" class="link-secondary me-2" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
        + '<a href="#" class="link-secondary me-2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>'
        + '<a href="#" class="link-secondary" aria-label="Instagram"><i class="bi bi-instagram"></i></a></p></div>'
        + '</div><div class="row mt-4"><div class="col text-center"><small class="text-body-secondary">© 2026 Your Company. All Rights Reserved</small></div></div>'
        + '</div></footer>'
});

// ══════════════════════════════════════════════════════════════════
// 🖼️ MEDIA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('image-gallery', {
    category: MEDIA,
    label: 'Image Gallery',
    media: '<i class="bi bi-grid-3x3 fs-3"></i>',
    description: 'Galería simple de 3 imágenes',
    content: '<div class="py-5"><div class="container"><div class="row g-3">'
        + '<div class="col-md-4"><img class="img-fluid rounded-3 shadow-sm d-block w-100" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen+1" alt="Galería 1" style="height:250px;object-fit:cover"></div>'
        + '<div class="col-md-4"><img class="img-fluid rounded-3 shadow-sm d-block w-100" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen+2" alt="Galería 2" style="height:250px;object-fit:cover"></div>'
        + '<div class="col-md-4"><img class="img-fluid rounded-3 shadow-sm d-block w-100" src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen+3" alt="Galería 3" style="height:250px;object-fit:cover"></div>'
        + '</div></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🧩 COMPONENTES UI
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('ui-alert', {
    category: UICOMP,
    label: 'Alert Box',
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>',
    description: 'Alerta primaria con botón de cierre',
    content: '<div class="alert alert-primary alert-dismissible fade show d-flex align-items-center" role="alert">'
        + '<i class="bi bi-info-circle-fill me-2" aria-hidden="true"></i>'
        + '<div class="flex-grow-1"><h4 class="alert-heading mb-1">Well done!</h4>'
        + '<p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p></div>'
        + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
        + '</div>'
});

gjsEditor.BlockManager.add('ui-tabs', {
    category: UICOMP,
    label: 'Tabs',
    media: '<i class="bi bi-folder2-open fs-3"></i>',
    description: 'Pestañas con tres paneles',
    content: '<div class="py-1">'
        + '<ul class="nav nav-tabs" role="tablist">'
        + '<li class="nav-item" role="presentation"><a href="#tabone" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabone" role="tab" aria-controls="tabone" aria-selected="true">Tab 1</a></li>'
        + '<li class="nav-item" role="presentation"><a class="nav-link" href="#tabtwo" data-bs-toggle="tab" data-bs-target="#tabtwo" role="tab" aria-controls="tabtwo" aria-selected="false">Tab 2</a></li>'
        + '<li class="nav-item" role="presentation"><a href="#tabthree" class="nav-link" data-bs-toggle="tab" data-bs-target="#tabthree" role="tab" aria-controls="tabthree" aria-selected="false">Tab 3</a></li>'
        + '</ul>'
        + '<div class="tab-content mt-2 p-3 border border-top-0 bg-body rounded-bottom-3">'
        + '<div class="tab-pane fade show active" id="tabone" role="tabpanel"><p class="mb-0">When I hear the buzz of the little world among the stalks.</p></div>'
        + '<div class="tab-pane fade" id="tabtwo" role="tabpanel"><p class="mb-0">Who formed us in his own image, and the breath of that universal love.</p></div>'
        + '<div class="tab-pane fade" id="tabthree" role="tabpanel"><p class="mb-0">In my soul and absorb its power, like the form of a beloved mistress.</p></div>'
        + '</div></div>'
});

console.log('[uiblocks] ✓ Bloques UI migrados de Bootstrap 4 a Bootstrap 5.3.3');
})();