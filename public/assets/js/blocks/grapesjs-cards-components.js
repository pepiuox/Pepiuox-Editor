// ═══════════════════════════════════════════════════════════════════
// grapesjs-cards-components.js — Cards especializadas Bootstrap 5.3.3
//
// Adaptado desde la sintaxis de plugin ES module a IIFE con
// gjsEditor.BlockManager.add. Los tipos personalizados con traits
// (product-card, service-card, pricing-card) se han convertido en
// bloques estáticos con variantes predefinidas.
//
// Modernizaciones aplicadas:
//  - bm.add → gjsEditor.BlockManager.add
//  - via.placeholder.com → placehold.co
//  - text-muted → text-body-secondary
//  - bg-light → bg-body-tertiary
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
    console.warn('[grapesjs-cards-components] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var CARDS = { id: 'bs5-cards', label: '💳 Cards Especializadas', open: true };

// ══════════════════════════════════════════════════════════════════
// 🛍️ CARDS DE PRODUCTO
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('product-card-basic', {
    category: CARDS,
    label: '🛍️ Card Producto Básico',
    media: '<i class="bi bi-bag fs-3"></i>',
    description: 'Card de producto simple',
    content: '<div class="card h-100 shadow-sm border-0"><div class="position-relative"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Producto" class="card-img-top" alt="Producto"></div><div class="card-body d-flex flex-column"><h5 class="card-title fw-bold">Producto Básico</h5><p class="card-text text-body-secondary flex-grow-1">Descripción del producto con características principales.</p><div class="mt-auto"><div class="mb-3"><span class="h4 text-primary fw-bold">$49.99</span></div><button class="btn btn-primary w-100">Añadir al carrito</button></div></div></div>'
});

gjsEditor.BlockManager.add('product-card-sale', {
    category: CARDS,
    label: '🏷️ Card Producto en Oferta',
    media: '<i class="bi bi-tag fs-3"></i>',
    description: 'Card de producto con descuento',
    content: '<div class="card h-100 shadow-sm border-0"><div class="position-relative"><span class="badge text-bg-danger position-absolute top-0 end-0 m-2">-50%</span><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Oferta" class="card-img-top" alt="Producto en oferta"></div><div class="card-body d-flex flex-column"><h5 class="card-title fw-bold">Producto en Oferta</h5><p class="card-text text-body-secondary flex-grow-1">Aprovecha esta oferta por tiempo limitado.</p><div class="mt-auto"><div class="mb-3"><small class="text-body-secondary text-decoration-line-through me-2">$59.99</small><span class="h4 text-danger fw-bold">$29.99</span></div><button class="btn btn-danger w-100">Comprar ahora</button></div></div></div>'
});

gjsEditor.BlockManager.add('product-card-premium', {
    category: CARDS,
    label: '⭐ Card Producto Premium',
    media: '<i class="bi bi-star fs-3"></i>',
    description: 'Card de producto premium con badge',
    content: '<div class="card h-100 shadow-sm border-0"><div class="position-relative"><span class="badge text-bg-success position-absolute top-0 end-0 m-2">NUEVO</span><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Premium" class="card-img-top" alt="Producto premium"></div><div class="card-body d-flex flex-column"><h5 class="card-title fw-bold">Producto Premium</h5><p class="card-text text-body-secondary flex-grow-1">Producto de alta calidad con características exclusivas.</p><div class="mt-auto"><div class="mb-3"><span class="h4 text-warning fw-bold">$199.99</span></div><button class="btn btn-warning w-100">Comprar Premium</button></div></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🛠️ CARDS DE SERVICIO
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('service-card-basic', {
    category: CARDS,
    label: '🛠️ Card Servicio Básico',
    media: '<i class="bi bi-briefcase fs-3"></i>',
    description: 'Card de servicio con ícono',
    content: '<div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4"><div class="display-1 mb-3"><span class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;font-size:2.5rem">💼</span></div><h4 class="card-title mb-3 fw-bold">Consultoría</h4><p class="card-text text-body-secondary mb-4">Asesoramiento profesional para tu negocio.</p><ul class="list-unstyled text-start mb-4"><li class="mb-2"><i class="text-primary">✓</i> Análisis completo</li><li class="mb-2"><i class="text-primary">✓</i> Recomendaciones personalizadas</li><li class="mb-2"><i class="text-primary">✓</i> Seguimiento continuo</li></ul><a href="#" class="text-primary fw-semibold text-decoration-none">Saber más →</a></div></div>'
});

gjsEditor.BlockManager.add('service-card-dev', {
    category: CARDS,
    label: '💻 Card Servicio Desarrollo',
    media: '<i class="bi bi-code-slash fs-3"></i>',
    description: 'Card de servicio de desarrollo',
    content: '<div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4"><div class="display-1 mb-3"><span class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;font-size:2.5rem">👨‍💻</span></div><h4 class="card-title mb-3 fw-bold">Desarrollo Web</h4><p class="card-text text-body-secondary mb-4">Creamos soluciones web a medida.</p><ul class="list-unstyled text-start mb-4"><li class="mb-2"><i class="text-success">✓</i> Diseño responsive</li><li class="mb-2"><i class="text-success">✓</i> Optimizado para SEO</li><li class="mb-2"><i class="text-success">✓</i> Soporte técnico incluido</li></ul><a href="#" class="text-success fw-semibold text-decoration-none">Ver proyectos →</a></div></div>'
});

gjsEditor.BlockManager.add('service-card-design', {
    category: CARDS,
    label: '🎨 Card Servicio Diseño',
    media: '<i class="bi bi-palette fs-3"></i>',
    description: 'Card de servicio de diseño',
    content: '<div class="card h-100 border-0 shadow-sm"><div class="card-body text-center p-4"><div class="display-1 mb-3"><span class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;font-size:2.5rem">🎨</span></div><h4 class="card-title mb-3 fw-bold">Diseño Gráfico</h4><p class="card-text text-body-secondary mb-4">Diseños creativos y profesionales.</p><ul class="list-unstyled text-start mb-4"><li class="mb-2"><i class="text-danger">✓</i> Identidad visual</li><li class="mb-2"><i class="text-danger">✓</i> Material publicitario</li><li class="mb-2"><i class="text-danger">✓</i> Revisiones ilimitadas</li></ul><a href="#" class="text-danger fw-semibold text-decoration-none">Ver portafolio →</a></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 💰 CARDS DE PRECIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('pricing-card-basic', {
    category: CARDS,
    label: '💰 Card Precio Básico',
    media: '<i class="bi bi-cash-coin fs-3"></i>',
    description: 'Plan de precios básico',
    content: '<div class="card h-100 shadow-sm border-0 position-relative"><div class="card-header bg-body-tertiary text-center py-4"><h4 class="fw-bold mb-2">Básico</h4><p class="mb-0 text-body-secondary">Para uso personal</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$9</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3"><i class="text-primary">✓</i> 1 usuario</li><li class="mb-3"><i class="text-primary">✓</i> 5 GB de almacenamiento</li><li class="mb-3"><i class="text-primary">✓</i> Soporte por email</li><li class="mb-3"><i class="text-primary">✓</i> Acceso a documentación</li><li class="mb-3"><i class="text-primary">✓</i> Actualizaciones mensuales</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-primary btn-lg w-100">Comenzar</button></div></div>'
});

gjsEditor.BlockManager.add('pricing-card-pro', {
    category: CARDS,
    label: '⭐ Card Precio Pro (Destacado)',
    media: '<i class="bi bi-star-fill fs-3"></i>',
    description: 'Plan de precios destacado',
    content: '<div class="card h-100 shadow-lg border-primary border-2 position-relative"><span class="badge text-bg-success position-absolute top-0 start-50 translate-middle">MÁS POPULAR</span><div class="card-header text-bg-success text-center py-4"><h4 class="fw-bold mb-2">Profesional</h4><p class="mb-0 opacity-75">Para equipos pequeños</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$29</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3"><i class="text-success">✓</i> 10 usuarios</li><li class="mb-3"><i class="text-success">✓</i> 50 GB de almacenamiento</li><li class="mb-3"><i class="text-success">✓</i> Soporte prioritario</li><li class="mb-3"><i class="text-success">✓</i> API access</li><li class="mb-3"><i class="text-success">✓</i> Actualizaciones semanales</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-success btn-lg w-100">Comenzar prueba</button></div></div>'
});

gjsEditor.BlockManager.add('pricing-card-enterprise', {
    category: CARDS,
    label: '🏢 Card Precio Empresarial',
    media: '<i class="bi bi-building fs-3"></i>',
    description: 'Plan de precios empresarial',
    content: '<div class="card h-100 shadow-sm border-0 position-relative"><div class="card-header bg-body-tertiary text-center py-4"><h4 class="fw-bold mb-2">Empresarial</h4><p class="mb-0 text-body-secondary">Para grandes organizaciones</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$99</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3"><i class="text-dark">✓</i> Usuarios ilimitados</li><li class="mb-3"><i class="text-dark">✓</i> Almacenamiento ilimitado</li><li class="mb-3"><i class="text-dark">✓</i> Soporte 24/7</li><li class="mb-3"><i class="text-dark">✓</i> API premium</li><li class="mb-3"><i class="text-dark">✓</i> SLA garantizado</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-dark btn-lg w-100">Contactar ventas</button></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🎯 SECCIONES COMPLETAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('products-section', {
    category: CARDS,
    label: '🛍️ Sección de Productos',
    media: '<i class="bi bi-shop fs-3"></i>',
    description: 'Sección completa con 3 productos',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><h2 class="text-center mb-2 fw-bold">Nuestros Productos</h2><p class="text-center text-body-secondary mb-5">Descubre nuestra selección de productos</p><div class="row g-4"><div class="col-md-4"><div class="card h-100 shadow-sm border-0"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Producto+1" class="card-img-top" alt="Producto 1"><div class="card-body p-4"><h5 class="card-title fw-bold">Producto 1</h5><p class="card-text text-body-secondary">Descripción breve del producto.</p><div class="d-flex justify-content-between align-items-center"><span class="h5 text-primary mb-0">$49.99</span><button class="btn btn-primary">Comprar</button></div></div></div></div><div class="col-md-4"><div class="card h-100 shadow-sm border-0"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Producto+2" class="card-img-top" alt="Producto 2"><div class="card-body p-4"><h5 class="card-title fw-bold">Producto 2</h5><p class="card-text text-body-secondary">Descripción breve del producto.</p><div class="d-flex justify-content-between align-items-center"><span class="h5 text-primary mb-0">$79.99</span><button class="btn btn-primary">Comprar</button></div></div></div></div><div class="col-md-4"><div class="card h-100 shadow-sm border-0"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=Producto+3" class="card-img-top" alt="Producto 3"><div class="card-body p-4"><h5 class="card-title fw-bold">Producto 3</h5><p class="card-text text-body-secondary">Descripción breve del producto.</p><div class="d-flex justify-content-between align-items-center"><span class="h5 text-primary mb-0">$99.99</span><button class="btn btn-primary">Comprar</button></div></div></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('services-section', {
    category: CARDS,
    label: '🛠️ Sección de Servicios',
    media: '<i class="bi bi-gear fs-3"></i>',
    description: 'Sección completa con 3 servicios',
    content: '<section class="py-5"><div class="container"><h2 class="text-center mb-2 fw-bold">Nuestros Servicios</h2><p class="text-center text-body-secondary mb-5">Soluciones profesionales para tu negocio</p><div class="row g-4"><div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4"><div class="card-body"><div class="display-1 mb-3">💼</div><h4 class="fw-bold">Consultoría</h4><p class="text-body-secondary">Asesoramiento experto para tu empresa.</p><a href="#" class="btn btn-outline-primary">Saber más</a></div></div></div><div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4"><div class="card-body"><div class="display-1 mb-3">💻</div><h4 class="fw-bold">Desarrollo</h4><p class="text-body-secondary">Soluciones web a medida.</p><a href="#" class="btn btn-outline-success">Saber más</a></div></div></div><div class="col-md-4"><div class="card h-100 border-0 shadow-sm text-center p-4"><div class="card-body"><div class="display-1 mb-3">🎨</div><h4 class="fw-bold">Diseño</h4><p class="text-body-secondary">Diseños creativos y profesionales.</p><a href="#" class="btn btn-outline-danger">Saber más</a></div></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('pricing-section', {
    category: CARDS,
    label: '💰 Sección de Precios',
    media: '<i class="bi bi-tags fs-3"></i>',
    description: 'Sección completa con 3 planes de precios',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><h2 class="text-center mb-2 fw-bold">Planes y Precios</h2><p class="text-center text-body-secondary mb-5">Elige el plan que mejor se adapte a tus necesidades</p><div class="row g-4 align-items-stretch"><div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-header bg-body-tertiary text-center py-4"><h4 class="fw-bold">Básico</h4><p class="mb-0 text-body-secondary">Para uso personal</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$9</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3">✓ 1 usuario</li><li class="mb-3">✓ 5 GB almacenamiento</li><li class="mb-3">✓ Soporte por email</li><li class="mb-3">✓ Acceso básico</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-primary btn-lg w-100">Comenzar</button></div></div></div><div class="col-md-4"><div class="card h-100 shadow-lg border-primary border-2"><div class="card-header text-bg-primary text-center py-4"><h4 class="fw-bold">Profesional</h4><p class="mb-0 opacity-75">Para equipos</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$29</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3">✓ 10 usuarios</li><li class="mb-3">✓ 50 GB almacenamiento</li><li class="mb-3">✓ Soporte prioritario</li><li class="mb-3">✓ API access</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-success btn-lg w-100">Comenzar prueba</button></div></div></div><div class="col-md-4"><div class="card h-100 shadow-sm border-0"><div class="card-header bg-body-tertiary text-center py-4"><h4 class="fw-bold">Empresarial</h4><p class="mb-0 text-body-secondary">Para grandes empresas</p></div><div class="card-body text-center py-4"><div class="mb-4"><span class="display-3 fw-bold">$99</span><span class="text-body-secondary">/mes</span></div><ul class="list-unstyled text-start"><li class="mb-3">✓ Usuarios ilimitados</li><li class="mb-3">✓ Almacenamiento ilimitado</li><li class="mb-3">✓ Soporte 24/7</li><li class="mb-3">✓ SLA garantizado</li></ul></div><div class="card-footer bg-transparent border-0 text-center pb-4"><button class="btn btn-dark btn-lg w-100">Contactar ventas</button></div></div></div></div></div></section>'
});

console.log('[grapesjs-cards-components] ✓ Cards especializadas modernizadas con BS 5.3.3');
})();