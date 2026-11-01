// ═══════════════════════════════════════════════════════════════════
// pro-templates.js — Plantillas rápidas y secciones completas
//
// Mejoras aplicadas:
//  - text-muted → text-body-secondary (variables de tema BS 5.3+)
//  - Accesibilidad ARIA en acordeones, tabs, modales
//  - data-bs-theme="dark" donde aplique
//  - IDs únicos y accesibles
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (mejoras BS 5.3.3)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[pro-templates] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var QUICK  = { id: 'px-quick',  label: 'Plantillas rápidas', open: true };
var HERO   = { id: 'px-hero',   label: 'Encabezados y hero', open: false };
var NAV    = { id: 'px-nav',    label: 'Navegación',         open: false };
var FOOTER = { id: 'px-footer', label: 'Pie de página',      open: false };

var photo = function (label, ratio) {
    return '<div class="d-flex align-items-center justify-content-center rounded-3" '
        + 'style="min-height:' + (ratio || 320) + 'px;background:linear-gradient(135deg,#e9ecef,#dee2e6);color:#adb5bd">'
        + '<span class="text-center small"><i class="bi bi-image d-block mb-1" style="font-size:2rem" aria-hidden="true"></i>' + label + '</span>'
        + '</div>';
};

// ── 1. Hero con CTA ─────────────────────────────────────────────
gjsEditor.BlockManager.add('px-hero-cta', {
    category: QUICK,
    label: 'Hero con CTA',
    pxBadge: 'Plantilla',
    pxKeywords: 'hero cabecera portada llamada accion landing inicio',
    media: '<i class="bi bi-easel2"></i>',
    description: 'Hero a dos columnas con titular, subtítulo, botones e imagen',
    content: '<section class="py-5" style="background:linear-gradient(135deg,#0d1117,#1f2937);color:#fff">'
        + '<div class="container py-4"><div class="row align-items-center g-5">'
        + '<div class="col-lg-6">'
        + '<span class="badge rounded-pill bg-success-subtle text-success-emphasis mb-3 px-3 py-2">Nuevo</span>'
        + '<h1 class="display-4 fw-bold lh-1 mb-3">Construye algo que la gente quiera usar</h1>'
        + '<p class="lead opacity-75 mb-4">Una frase clara que explique qué haces, para quién y por qué importa.</p>'
        + '<div class="d-flex flex-wrap gap-2">'
        + '<a href="#" class="btn btn-success btn-lg px-4">Empezar ahora</a>'
        + '<a href="#" class="btn btn-outline-light btn-lg px-4">Ver demo</a>'
        + '</div>'
        + '<p class="small opacity-50 mt-3 mb-0"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Sin tarjeta · Cancela cuando quieras</p>'
        + '</div>'
        + '<div class="col-lg-6">' + photo('Imagen principal del hero', 340) + '</div>'
        + '</div></div></section>'
});

// ── 2. Características ──────────────────────────────────────────
gjsEditor.BlockManager.add('px-features', {
    category: QUICK,
    label: 'Sección de características',
    pxBadge: 'Plantilla',
    pxKeywords: 'caracteristicas features beneficios iconos columnas servicios',
    media: '<i class="bi bi-grid-1x2"></i>',
    description: 'Tres características en tarjetas con icono, título y texto',
    content: '<section class="py-5 bg-white"><div class="container">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Todo lo que necesitas, en un sitio</h2>'
        + '<p class="text-body-secondary mb-0">Tres razones concretas por las que elegirte.</p></div>'
        + '<div class="row g-4">'
        + [['bi-lightning-charge', 'Rápido de verdad', 'Carga en menos de un segundo sin trucos.'],
           ['bi-shield-check', 'Seguro por diseño', 'Cifrado y respaldo diario desde el primer día.'],
           ['bi-graph-up-arrow', 'Escala contigo', 'De 10 a 10.000 usuarios sin cambiar de plan.']].map(function(f){
            return '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4">'
                + '<div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success-emphasis mb-3" style="width:48px;height:48px">'
                + '<i class="bi ' + f[0] + '" style="font-size:1.4rem" aria-hidden="true"></i></div>'
                + '<h3 class="h5 fw-bold">' + f[1] + '</h3>'
                + '<p class="text-body-secondary mb-0">' + f[2] + '</p>'
                + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── 3. Testimonios ──────────────────────────────────────────────
gjsEditor.BlockManager.add('px-testimonials', {
    category: QUICK,
    label: 'Sección de testimonios',
    pxBadge: 'Plantilla',
    pxKeywords: 'testimonios opiniones resenas clientes prueba social',
    media: '<i class="bi bi-chat-quote"></i>',
    description: 'Tres testimonios con valoración, cita y autor',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<h2 class="fw-bold text-center mb-5">Lo que dicen quienes ya lo usan</h2>'
        + '<div class="row g-4">'
        + [1,2,3].map(function(){
            return '<div class="col-lg-4"><figure class="card h-100 border-0 shadow-sm mb-0"><div class="card-body p-4">'
                + '<div class="text-warning mb-2" aria-label="5 estrellas">'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i>'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i>'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i></div>'
                + '<blockquote class="mb-4"><p class="mb-0">«Cambiamos de herramienta en una tarde y no volvimos atrás.»</p></blockquote>'
                + '<figcaption class="d-flex align-items-center gap-3">'
                + '<span class="rounded-circle bg-secondary-subtle d-inline-flex align-items-center justify-content-center" style="width:42px;height:42px"><i class="bi bi-person" aria-hidden="true"></i></span>'
                + '<span><strong class="d-block small">Nombre Apellido</strong><span class="text-body-secondary small">Cargo, Empresa</span></span>'
                + '</figcaption></div></figure></div>';
        }).join('')
        + '</div></div></section>'
});

// ── 4. Precios ──────────────────────────────────────────────────
gjsEditor.BlockManager.add('px-pricing', {
    category: QUICK,
    label: 'Sección de precios',
    pxBadge: 'Plantilla',
    pxKeywords: 'precios pricing planes tarifas suscripcion comparativa',
    media: '<i class="bi bi-tags"></i>',
    description: 'Tres planes con precio, ventajas y botón; el central destacado',
    content: '<section class="py-5 bg-white"><div class="container">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Precios simples</h2>'
        + '<p class="text-body-secondary mb-0">Sin letra pequeña. Cambia o cancela cuando quieras.</p></div>'
        + '<div class="row g-4 justify-content-center">'
        + [['Básico', '9', false], ['Pro', '29', true], ['Empresa', '99', false]].map(function(plan){
            var h = plan[2];
            return '<div class="col-md-4"><div class="card h-100 ' + (h ? 'border-success shadow' : 'border-0 shadow-sm') + '"><div class="card-body p-4">'
                + (h ? '<span class="badge text-bg-success mb-2">Más elegido</span>' : '')
                + '<h3 class="h5 fw-bold mb-1">' + plan[0] + '</h3>'
                + '<p class="text-body-secondary small mb-3">Para equipos que empiezan</p>'
                + '<p class="display-6 fw-bold mb-3">' + plan[1] + ' €<span class="fs-6 fw-normal text-body-secondary">/mes</span></p>'
                + '<ul class="list-unstyled small mb-4">'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>5 proyectos</li>'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Soporte por correo</li>'
                + '<li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Analíticas básicas</li>'
                + '</ul>'
                + '<a href="#" class="btn ' + (h ? 'btn-success' : 'btn-outline-dark') + ' w-100">Elegir plan</a>'
                + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── 5. FAQ ──────────────────────────────────────────────────────
gjsEditor.BlockManager.add('px-faq', {
    category: QUICK,
    label: 'Sección de preguntas frecuentes',
    pxBadge: 'Plantilla',
    pxKeywords: 'faq preguntas frecuentes dudas acordeon ayuda',
    media: '<i class="bi bi-question-circle"></i>',
    description: 'Acordeón de preguntas frecuentes con la primera abierta',
    content: '<section class="py-5 bg-body-tertiary"><div class="container" style="max-width:820px">'
        + '<h2 class="fw-bold text-center mb-4">Preguntas frecuentes</h2>'
        + '<div class="accordion" id="faqPX">'
        + [['¿Puedo probarlo antes de pagar?', 'Sí: 14 días completos sin tarjeta. Al terminar decides.'],
           ['¿Cómo funciona el cambio de plan?', 'Se aplica al instante y se prorratea lo que ya has pagado.'],
           ['¿Dónde se guardan mis datos?', 'En servidores de la UE, con copia diaria y cifrado en reposo.']].map(function(q, i){
            var id = 'faqPX' + (i+1);
            return '<div class="accordion-item">'
                + '<h3 class="accordion-header"><button class="accordion-button ' + (i > 0 ? 'collapsed' : '') + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + id + '" aria-expanded="' + (i === 0 ? 'true' : 'false') + '" aria-controls="' + id + '">'
                + q[0] + '</button></h3>'
                + '<div id="' + id + '" class="accordion-collapse collapse ' + (i === 0 ? 'show' : '') + '" data-bs-parent="#faqPX">'
                + '<div class="accordion-body text-body-secondary">' + q[1] + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── 6. Estadísticas ─────────────────────────────────────────────
gjsEditor.BlockManager.add('px-stats', {
    category: QUICK,
    label: 'Sección de estadísticas',
    pxBadge: 'Plantilla',
    pxKeywords: 'estadisticas numeros metricas contadores kpi cifras',
    media: '<i class="bi bi-bar-chart"></i>',
    description: 'Cuatro cifras destacadas sobre fondo oscuro',
    content: '<section class="py-5" style="background:#0d1117;color:#fff" data-bs-theme="dark">'
        + '<div class="container"><div class="row text-center g-4">'
        + [['12.400', 'usuarios activos'], ['99,98 %', 'disponibilidad'], ['4,9/5', 'valoración media'], ['< 40 ms', 'respuesta media']].map(function(s){
            return '<div class="col-6 col-lg-3">'
                + '<p class="display-6 fw-bold mb-1" style="color:#00d4aa">' + s[0] + '</p>'
                + '<p class="small text-uppercase mb-0" style="letter-spacing:.06em;opacity:.65">' + s[1] + '</p>'
                + '</div>';
        }).join('')
        + '</div></div></section>'
});

// ── 7. Contacto ─────────────────────────────────────────────────
gjsEditor.BlockManager.add('px-contact', {
    category: QUICK,
    label: 'Sección de contacto',
    pxBadge: 'Plantilla',
    pxKeywords: 'contacto formulario contacto direccion telefono email',
    media: '<i class="bi bi-envelope-paper"></i>',
    description: 'Contacto a dos columnas: datos a la izquierda, formulario a la derecha',
    content: '<section class="py-5 bg-white"><div class="container"><div class="row g-5">'
        + '<div class="col-lg-5"><h2 class="fw-bold mb-3">Hablemos</h2>'
        + '<p class="text-body-secondary">Cuéntanos qué necesitas y te respondemos en menos de 24 h laborables.</p>'
        + '<ul class="list-unstyled small">'
        + '<li class="d-flex gap-2 mb-2"><i class="bi bi-envelope text-success" aria-hidden="true"></i><span>hola@tudominio.com</span></li>'
        + '<li class="d-flex gap-2 mb-2"><i class="bi bi-telephone text-success" aria-hidden="true"></i><span>+34 600 000 000</span></li>'
        + '<li class="d-flex gap-2"><i class="bi bi-geo-alt text-success" aria-hidden="true"></i><span>Calle Ejemplo 1, Madrid</span></li>'
        + '</ul></div>'
        + '<div class="col-lg-7"><form><div class="row g-3">'
        + '<div class="col-md-6"><label class="form-label" for="ctName">Nombre</label><input type="text" class="form-control" id="ctName" placeholder="Tu nombre" autocomplete="name"></div>'
        + '<div class="col-md-6"><label class="form-label" for="ctEmail">Correo</label><input type="email" class="form-control" id="ctEmail" placeholder="tu@correo.com" autocomplete="email"></div>'
        + '<div class="col-12"><label class="form-label" for="ctSubject">Asunto</label><input type="text" class="form-control" id="ctSubject" placeholder="Motivo del mensaje"></div>'
        + '<div class="col-12"><label class="form-label" for="ctMsg">Mensaje</label><textarea class="form-control" id="ctMsg" rows="4" placeholder="Escríbelo aquí"></textarea></div>'
        + '<div class="col-12"><button type="submit" class="btn btn-success px-4">Enviar mensaje</button></div>'
        + '</div></form></div></div></div></section>'
});

// ── 8. CTA ──────────────────────────────────────────────────────
gjsEditor.BlockManager.add('px-cta', {
    category: QUICK,
    label: 'Llamada a la acción',
    pxBadge: 'Plantilla',
    pxKeywords: 'cta llamada accion banda final conversion suscripcion',
    media: '<i class="bi bi-megaphone"></i>',
    description: 'Banda de conversión con titular y botón destacado',
    content: '<section class="py-5" style="background:#00d4aa;color:#04120f"><div class="container">'
        + '<div class="row align-items-center g-4">'
        + '<div class="col-lg-8"><h2 class="h1 fw-bold mb-1">Empieza gratis hoy</h2>'
        + '<p class="mb-0 opacity-75">14 días de prueba. Sin tarjeta. Sin compromiso.</p></div>'
        + '<div class="col-lg-4 text-lg-end"><a href="#" class="btn btn-dark btn-lg px-4">Crear cuenta</a></div>'
        + '</div></div></section>'
});

// ── 9. Landing corporativa ──────────────────────────────────────
gjsEditor.BlockManager.add('px-landing-corporate', {
    category: QUICK,
    label: 'Landing corporativa',
    pxBadge: 'Página',
    pxKeywords: 'landing page completa corporativa empresa inicio secciones',
    media: '<i class="bi bi-window-desktop"></i>',
    description: 'Página completa: navbar, hero, beneficios y pie, todo editable',
    content: '<div>'
        + '<nav class="navbar navbar-expand-lg bg-white border-bottom"><div class="container">'
        + '<a class="navbar-brand fw-bold" href="#">TU MARCA</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPX" aria-controls="navPX" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navPX"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">'
        + '<li class="nav-item"><a class="nav-link" href="#">Producto</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Precios</a></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '<li class="nav-item"><a class="btn btn-success ms-lg-2" href="#">Prueba gratis</a></li>'
        + '</ul></div></div></nav>'
        + '<section class="py-5 text-center" style="background:#f8f9fa"><div class="container py-4">'
        + '<h1 class="display-5 fw-bold mb-3">Software que resuelve un problema concreto</h1>'
        + '<p class="lead text-body-secondary mx-auto mb-4" style="max-width:640px">Explica en dos líneas qué haces y para quién.</p>'
        + '<a href="#" class="btn btn-success btn-lg px-4">Empezar ahora</a>'
        + '</div></section>'
        + '<section class="py-5"><div class="container"><div class="row g-4">'
        + [1,2,3].map(function(n){
            return '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4">'
                + '<h3 class="h5 fw-bold">Beneficio ' + n + '</h3>'
                + '<p class="text-body-secondary mb-0">Describe una ventaja real, con un dato si puedes.</p>'
                + '</div></div></div>';
        }).join('')
        + '</div></div></section>'
        + '<footer class="py-4 border-top bg-white"><div class="container d-flex flex-wrap justify-content-between gap-2 small text-body-secondary">'
        + '<span>© 2026 Tu marca. Todos los derechos reservados.</span>'
        + '<span><a href="#" class="link-secondary text-decoration-none">Privacidad</a> · <a href="#" class="link-secondary text-decoration-none">Términos</a></span>'
        + '</div></footer></div>'
});

// ── 10. Página de servicios ─────────────────────────────────────
gjsEditor.BlockManager.add('px-page-services', {
    category: QUICK,
    label: 'Página de servicios',
    pxBadge: 'Página',
    pxKeywords: 'servicios pagina que hacemos oferta catalogo',
    media: '<i class="bi bi-briefcase"></i>',
    description: 'Listado de servicios en tarjetas horizontales con icono',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<header class="text-center mb-5"><h1 class="fw-bold">Servicios</h1>'
        + '<p class="text-body-secondary mb-0">Cuatro formas de trabajar con nosotros.</p></header>'
        + '<div class="row g-4">'
        + [['bi-palette', 'Diseño de producto', 'Interfaces que se entienden sin explicación.'],
           ['bi-code-slash', 'Desarrollo a medida', 'Web rápida, accesible y mantenible.'],
           ['bi-phone', 'Aplicaciones móviles', 'iOS y Android desde una sola base.'],
           ['bi-graph-up', 'Consultoría', 'Auditoría técnica y plan de mejora.']].map(function(s){
            return '<div class="col-md-6"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4 d-flex gap-3">'
                + '<span class="rounded-3 bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px"><i class="bi ' + s[0] + '" style="font-size:1.3rem" aria-hidden="true"></i></span>'
                + '<div><h2 class="h5 fw-bold mb-1">' + s[1] + '</h2>'
                + '<p class="text-body-secondary mb-2">' + s[2] + '</p>'
                + '<a href="#" class="small text-decoration-none">Saber más <i class="bi bi-arrow-right" aria-hidden="true"></i></a>'
                + '</div></div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── 11. Hero centrado ───────────────────────────────────────────
gjsEditor.BlockManager.add('px-hero-centered', {
    category: HERO,
    label: 'Hero centrado',
    pxKeywords: 'hero centrado cabecera titulo simple',
    media: '<i class="bi bi-align-center"></i>',
    description: 'Cabecera centrada con titular, subtítulo y botón',
    content: '<section class="py-5 text-center bg-white"><div class="container py-5">'
        + '<h1 class="display-6 fw-bold mb-3">Un titular claro y directo</h1>'
        + '<p class="lead text-body-secondary mx-auto mb-4" style="max-width:620px">Una línea que explique el valor y quite la duda principal del visitante.</p>'
        + '<a href="#" class="btn btn-success px-4">Empezar</a>'
        + '</div></section>'
});

// ── 12. Navbar con logo ─────────────────────────────────────────
gjsEditor.BlockManager.add('px-navbar-logo', {
    category: NAV,
    label: 'Navbar con logotipo',
    pxKeywords: 'navbar menu navegacion logo cabecera bootstrap',
    media: '<i class="bi bi-menu-button-wide"></i>',
    description: 'Barra de navegación responsive con logotipo, desplegable y botón',
    content: '<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top"><div class="container">'
        + '<a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="#">'
        + '<span class="rounded-3 bg-success d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;color:#04120f"><i class="bi bi-layers-fill" aria-hidden="true"></i></span>'
        + 'TU MARCA</a>'
        + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogoPX" aria-controls="navLogoPX" aria-expanded="false" aria-label="Alternar navegación"><span class="navbar-toggler-icon"></span></button>'
        + '<div class="collapse navbar-collapse" id="navLogoPX"><ul class="navbar-nav ms-auto gap-lg-2">'
        + '<li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Inicio</a></li>'
        + '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">Servicios</a>'
        + '<ul class="dropdown-menu dropdown-menu-end">'
        + '<li><a class="dropdown-item" href="#">Diseño</a></li>'
        + '<li><a class="dropdown-item" href="#">Desarrollo</a></li>'
        + '<li><hr class="dropdown-divider"></li>'
        + '<li><a class="dropdown-item" href="#">Consultoría</a></li>'
        + '</ul></li>'
        + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
        + '<li class="nav-item"><a class="btn btn-success" href="#">Presupuesto</a></li>'
        + '</ul></div></div></nav>'
});

// ── 13. Footer corporativo ──────────────────────────────────────
gjsEditor.BlockManager.add('px-footer-corporate', {
    category: FOOTER,
    label: 'Footer corporativo',
    pxKeywords: 'footer pie pagina enlaces redes contacto corporativo',
    media: '<i class="bi bi-layout-text-window-reverse"></i>',
    description: 'Pie completo con descripción, redes, dos columnas de enlaces y contacto',
    content: '<footer class="py-5" style="background:#0d1117;color:#f8f9fa" data-bs-theme="dark"><div class="container"><div class="row g-4">'
        + '<div class="col-lg-4"><p class="h5 fw-bold mb-2">TU MARCA</p>'
        + '<p class="small mb-3" style="opacity:.7">Una frase que resuma qué haces y para quién.</p>'
        + '<div class="d-flex gap-2">'
        + '<a href="#" class="btn btn-sm btn-outline-light" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>'
        + '<a href="#" class="btn btn-sm btn-outline-light" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>'
        + '<a href="#" class="btn btn-sm btn-outline-light" aria-label="GitHub"><i class="bi bi-github"></i></a>'
        + '</div></div>'
        + '<div class="col-6 col-lg-2"><p class="text-uppercase small fw-bold mb-2" style="opacity:.6">Producto</p>'
        + '<ul class="list-unstyled small mb-0"><li class="mb-1"><a href="#" class="link-light text-decoration-none" style="opacity:.75">Funciones</a></li><li class="mb-1"><a href="#" class="link-light text-decoration-none" style="opacity:.75">Precios</a></li><li><a href="#" class="link-light text-decoration-none" style="opacity:.75">Novedades</a></li></ul></div>'
        + '<div class="col-6 col-lg-2"><p class="text-uppercase small fw-bold mb-2" style="opacity:.6">Empresa</p>'
        + '<ul class="list-unstyled small mb-0"><li class="mb-1"><a href="#" class="link-light text-decoration-none" style="opacity:.75">Sobre nosotros</a></li><li class="mb-1"><a href="#" class="link-light text-decoration-none" style="opacity:.75">Empleo</a></li><li><a href="#" class="link-light text-decoration-none" style="opacity:.75">Contacto</a></li></ul></div>'
        + '<div class="col-lg-4"><p class="text-uppercase small fw-bold mb-2" style="opacity:.6">Contacto</p>'
        + '<p class="small mb-1" style="opacity:.75"><i class="bi bi-envelope me-2" aria-hidden="true"></i>hola@tudominio.com</p>'
        + '<p class="small mb-0" style="opacity:.75"><i class="bi bi-telephone me-2" aria-hidden="true"></i>+34 600 000 000</p>'
        + '</div></div>'
        + '<hr style="border-color:rgba(255,255,255,.15);margin:2rem 0 1rem">'
        + '<p class="small text-center mb-0" style="opacity:.55">© 2026 Tu marca. Todos los derechos reservados.</p>'
        + '</div></footer>'
});

console.log('[pro-templates] ✓ Plantillas mejoradas con Bootstrap 5.3.3');
})();