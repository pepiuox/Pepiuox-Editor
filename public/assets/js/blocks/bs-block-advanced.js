// ═══════════════════════════════════════════════════════════════════
// bs-block-advanced.js — Componentes avanzados Bootstrap 5.3.3
//
// Modernización:
//  - Font Awesome (fas, fa-2x) → Bootstrap Icons (bi)
//  - bg-light → bg-body-tertiary
//  - text-muted → text-body-secondary
//  - Categorías en español y consistentes
//  - Accesibilidad ARIA completa
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[bs-block-advanced] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var SECT  = { id: 'advanced-sections', label: '🎯 Secciones avanzadas', open: false };
var WIDG  = { id: 'advanced-widgets',  label: '📊 Widgets avanzados',   open: false };
var PAGES = { id: 'advanced-pages',    label: '📄 Páginas avanzadas',   open: false };
var DATA  = { id: 'advanced-data',     label: '📋 Datos avanzados',     open: false };

// ── PRICING SECTION ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-pricing-section', {
    category: SECT,
    label: 'Sección de precios',
    media: '<i class="bi bi-cash-coin fs-3"></i>',
    description: 'Tres planes con el central destacado',
    content: '<section class="py-5 bg-body-tertiary"><div class="container">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Planes y precios</h2>'
        + '<p class="text-body-secondary mb-0">Elige el mejor plan para tu negocio</p></div>'
        + '<div class="row g-4">'
        + '<div class="col-md-4"><div class="card border-0 shadow h-100"><div class="card-body text-center p-5">'
        + '<h4 class="fw-bold">Básico</h4><h1 class="display-4 fw-bold">9 €</h1><p class="text-body-secondary">/mes</p>'
        + '<ul class="list-unstyled my-4 text-start"><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>1 Usuario</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>5 Proyectos</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Soporte básico</li></ul>'
        + '<button class="btn btn-outline-primary w-100">Elegir plan</button></div></div></div>'
        + '<div class="col-md-4"><div class="card border-primary border-3 shadow-lg h-100 position-relative"><div class="card-body text-center p-5">'
        + '<span class="badge text-bg-primary mb-3">POPULAR</span>'
        + '<h4 class="fw-bold">Pro</h4><h1 class="display-4 fw-bold">29 €</h1><p class="text-body-secondary">/mes</p>'
        + '<ul class="list-unstyled my-4 text-start"><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>10 Usuarios</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>50 Proyectos</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Soporte premium</li></ul>'
        + '<button class="btn btn-primary w-100">Elegir plan</button></div></div></div>'
        + '<div class="col-md-4"><div class="card border-0 shadow h-100"><div class="card-body text-center p-5">'
        + '<h4 class="fw-bold">Enterprise</h4><h1 class="display-4 fw-bold">99 €</h1><p class="text-body-secondary">/mes</p>'
        + '<ul class="list-unstyled my-4 text-start"><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Usuarios ilimitados</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Proyectos ilimitados</li><li class="mb-2"><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>Soporte 24/7</li></ul>'
        + '<button class="btn btn-dark w-100">Contactar</button></div></div></div>'
        + '</div></div></section>'
});

// ── TESTIMONIALS ────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-testimonials', {
    category: SECT,
    label: 'Testimonios',
    media: '<i class="bi bi-chat-quote fs-3"></i>',
    description: 'Tres testimonios con valoración y avatar',
    content: '<section class="py-5"><div class="container">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Lo que dicen nuestros clientes</h2></div>'
        + '<div class="row g-4">'
        + [['https://placehold.co/60x60/667eea/ffffff?text=CD', 'Carlos Díaz', 'CEO Startup', 'Excelente servicio y soporte técnico.'],
           ['https://placehold.co/60x60/764ba2/ffffff?text=AT', 'Ana Torres', 'Diseñadora UX', 'La plataforma es rápida y muy intuitiva.'],
           ['https://placehold.co/60x60/4facfe/ffffff?text=LM', 'Luis Mendoza', 'Fullstack Developer', 'Totalmente recomendado para empresas.']].map(function(t){
            return '<div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4">'
                + '<div class="mb-3 text-warning" aria-label="5 estrellas">'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i>'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i>'
                + '<i class="bi bi-star-fill" aria-hidden="true"></i></div>'
                + '<p class="text-body-secondary mb-0">' + t[3] + '</p>'
                + '<div class="d-flex align-items-center mt-4">'
                + '<img src="' + t[0] + '" class="rounded-circle me-3" width="60" height="60" alt="Avatar">'
                + '<div><h6 class="mb-0 fw-bold">' + t[1] + '</h6><small class="text-body-secondary">' + t[2] + '</small></div>'
                + '</div></div></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── DASHBOARD STATS ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-dashboard-stats', {
    category: WIDG,
    label: 'Dashboard Stats',
    media: '<i class="bi bi-speedometer2 fs-3"></i>',
    description: 'Cuatro estadísticas en tarjetas oscuras',
    content: '<section class="py-4 bg-dark text-white" data-bs-theme="dark"><div class="container">'
        + '<div class="row g-4 text-center">'
        + [['15K', 'Usuarios'], ['320', 'Clientes'], ['98%', 'Satisfacción'], ['24/7', 'Soporte']].map(function(s){
            return '<div class="col-md-3 col-6"><div class="p-4 border border-secondary rounded-3 h-100">'
                + '<h1 class="display-5 fw-bold">' + s[0] + '</h1>'
                + '<p class="mb-0 text-body-secondary">' + s[1] + '</p></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── LOGIN PAGE ──────────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-login-page', {
    category: PAGES,
    label: 'Página de login',
    media: '<i class="bi bi-box-arrow-in-right fs-3"></i>',
    description: 'Página de inicio de sesión centrada',
    content: '<section class="vh-100 d-flex align-items-center bg-body-tertiary"><div class="container"><div class="row justify-content-center"><div class="col-md-5">'
        + '<div class="card border-0 shadow-lg"><div class="card-body p-5">'
        + '<div class="text-center mb-4"><h2 class="fw-bold">Iniciar sesión</h2>'
        + '<p class="text-body-secondary mb-0">Bienvenido nuevamente</p></div>'
        + '<form><div class="mb-3"><label class="form-label" for="loginEmail2">Email</label>'
        + '<input type="email" class="form-control form-control-lg" id="loginEmail2" placeholder="correo@ejemplo.com" autocomplete="email"></div>'
        + '<div class="mb-4"><label class="form-label" for="loginPass2">Contraseña</label>'
        + '<input type="password" class="form-control form-control-lg" id="loginPass2" autocomplete="current-password"></div>'
        + '<button class="btn btn-primary btn-lg w-100" type="submit">Entrar</button></form>'
        + '</div></div></div></div></div></section>'
});

// ── FEATURES GRID ───────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-features-grid', {
    category: SECT,
    label: 'Features Grid',
    media: '<i class="bi bi-grid-3x3-gap fs-3"></i>',
    description: 'Tres características con iconos grandes',
    content: '<section class="py-5"><div class="container">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Características principales</h2></div>'
        + '<div class="row g-4">'
        + [['bi-lightning-charge', 'text-primary', 'Rápido', 'Optimizado para alto rendimiento.'],
           ['bi-shield-lock', 'text-success', 'Seguro', 'Protección avanzada de datos.'],
           ['bi-cloud', 'text-info', 'Cloud', 'Acceso desde cualquier lugar.']].map(function(f){
            return '<div class="col-md-4"><div class="p-4 border rounded-3 h-100 text-center">'
                + '<div class="mb-3"><i class="bi ' + f[0] + ' ' + f[1] + '" style="font-size:3rem" aria-hidden="true"></i></div>'
                + '<h4 class="fw-bold">' + f[2] + '</h4>'
                + '<p class="text-body-secondary mb-0">' + f[3] + '</p></div></div>';
        }).join('')
        + '</div></div></section>'
});

// ── ADVANCED TABLE ──────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-advanced-table', {
    category: DATA,
    label: 'Tabla avanzada',
    media: '<i class="bi bi-table fs-3"></i>',
    description: 'Tabla con avatares, badges y acciones',
    content: '<div class="table-responsive p-4"><table class="table table-hover align-middle">'
        + '<thead class="table-dark"><tr><th scope="col">#</th><th scope="col">Usuario</th><th scope="col">Email</th><th scope="col">Estado</th><th scope="col" class="text-center">Acciones</th></tr></thead>'
        + '<tbody><tr><th scope="row">1</th>'
        + '<td><div class="d-flex align-items-center"><img src="https://placehold.co/40x40/667eea/ffffff?text=JP" class="rounded-circle me-2" width="40" height="40" alt="Avatar">Juan Pérez</div></td>'
        + '<td>juan@email.com</td>'
        + '<td><span class="badge text-bg-success">Activo</span></td>'
        + '<td class="text-center"><button class="btn btn-sm btn-primary me-1" aria-label="Editar"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button></td></tr>'
        + '</tbody></table></div>'
});

console.log('[bs-block-advanced] ✓ Componentes avanzados modernizados con Bootstrap 5.3.3');
})();