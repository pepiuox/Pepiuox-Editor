// 📌 Coloca este código DESPUÉS de la inicialización de `editor` en editor.php

// ─────────────────────────────────────────────
// 🏗️ LAYOUT
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('navbar-bs', {
    category: { 
        id: 'layout', 
        label: 'Layout', 
        open: true 
    },
    label: 'Navbar',
    content: `
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
    <a class="navbar-brand fw-bold" href="#">🍇 GrapesCMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
    <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>
    </ul>
    </div>
    </div>
    </nav>`,
    media: '<i class="bi bi-list-check fs-3"></i>'
});

gjsEditor.BlockManager.add('hero-section', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Hero',
    content: `
    <section class="bg-primary text-white py-5">
    <div class="container text-center py-5">
    <h1 class="display-4 fw-bold mb-3">Título Principal</h1>
    <p class="lead mb-4">Subtítulo descriptivo que captura la atención del visitante.</p>
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
    <button class="btn btn-light btn-lg px-4 gap-3">Comenzar</button>
    <button class="btn btn-outline-light btn-lg px-4">Saber más</button>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-badge-hd fs-3"></i>'
});

gjsEditor.BlockManager.add('features-3col', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Features 3 Columnas',
    content: `
    <section class="py-5">
    <div class="container">
    <div class="row g-4 text-center">
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-lightning-charge"></i></div>
    <h5 class="card-title">Rápido</h5>
    <p class="card-text text-muted">Descripción breve del beneficio o característica.</p>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-check"></i></div>
    <h5 class="card-title">Seguro</h5>
    <p class="card-text text-muted">Descripción breve del beneficio o característica.</p>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-gear"></i></div>
    <h5 class="card-title">Flexible</h5>
    <p class="card-text text-muted">Descripción breve del beneficio o característica.</p>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-grid-3x3 fs-3"></i>'
});

gjsEditor.BlockManager.add('footer-custom', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Footer',
    content: `
    <footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
    <div class="row g-4">
    <div class="col-md-4">
    <h5 class="fw-bold mb-3">🍇 GrapesCMS</h5>
    <p class="text-muted small">Editor visual de páginas web construido con PHP, MySQL y GrapesJS.</p>
    </div>
    <div class="col-md-4">
    <h6 class="fw-bold mb-3">Enlaces</h6>
    <ul class="list-unstyled small">
    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Inicio</a></li>
    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Documentación</a></li>
    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Soporte</a></li>
    </ul>
    </div>
    <div class="col-md-4">
    <h6 class="fw-bold mb-3">Redes</h6>
    <div class="d-flex gap-3 fs-5">
    <a href="#" class="text-light"><i class="bi bi-facebook"></i></a>
    <a href="#" class="text-light"><i class="bi bi-twitter-x"></i></a>
    <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="mt-3 text-muted small">&copy; 2026 Todos los derechos reservados.</p>
    </div>
    </div>
    </div>
    </footer>`,
    media: '<i class="bi bi-layout-footer fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📝 CONTENIDO
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('image-text-split', {
    category: { id: 'content', label: 'Contenido', open: true },
    label: 'Imagen + Texto',
    content: `
    <section class="py-5">
    <div class="container">
    <div class="row align-items-center g-5">
    <div class="col-lg-6">
    <img src="https://placehold.co/600x400/e9ecef/6c757d?text=Imagen+Editable" class="img-fluid rounded shadow" alt="Placeholder">
    </div>
    <div class="col-lg-6">
    <h2 class="fw-bold mb-3">Título de la Sección</h2>
    <p class="lead text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
    <p class="mb-4">Más detalles y contenido editable que puedes personalizar según tus necesidades.</p>
    <a href="#" class="btn btn-outline-primary">Leer más</a>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-image-text fs-3"></i>'
});

gjsEditor.BlockManager.add('testimonial-card', {
    category: { id: 'content', label: 'Contenido', open: true },
    label: 'Testimonio',
    content: `
    <div class="bg-light p-4 rounded shadow-sm my-3">
    <div class="d-flex align-items-center mb-3">
    <img src="https://placehold.co/60x60/6c757d/ffffff?text=U" class="rounded-circle me-3" alt="Avatar">
    <div>
    <h6 class="mb-0 fw-bold">Nombre Usuario</h6>
    <small class="text-muted">Cargo / Empresa</small>
    </div>
    </div>
    <blockquote class="blockquote mb-0 small">
    <p class="fst-italic">"Excelente servicio, superó todas mis expectativas. Totalmente recomendado para cualquier proyecto."</p>
    <footer class="blockquote-footer mt-2 text-muted"><cite>★★★★★</cite></footer>
    </blockquote>
    </div>`,
    media: '<i class="bi bi-chat-quote fs-3"></i>'
});

gjsEditor.BlockManager.add('pricing-card', {
    category: { id: 'content', label: 'Contenido', open: true },
    label: 'Plan de Precios',
    content: `
    <div class="card border-0 shadow-sm text-center p-3 my-3">
    <div class="card-header bg-transparent border-0 pt-3">
    <h4 class="fw-bold">Plan Pro</h4>
    <div class="display-6 fw-bold my-3">$29<span class="fs-6 text-muted">/mes</span></div>
    </div>
    <div class="card-body">
    <ul class="list-unstyled mb-4 small">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Característica 1</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Característica 2</li>
    <li class="mb-2 text-muted"><i class="bi bi-x-circle me-2"></i>Característica 3</li>
    </ul>
    <button class="btn btn-outline-primary w-100">Seleccionar</button>
    </div>
    </div>`,
    media: '<i class="bi bi-tag fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📥 FORMULARIOS
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('contact-form-card', {
    category: { id: 'forms', label: 'Formularios', open: true },
    label: 'Formulario Contacto',
    content: `
    <section class="py-5">
    <div class="container">
    <div class="row justify-content-center">
    <div class="col-md-8">
    <div class="card border-0 shadow">
    <div class="card-body p-5">
    <h3 class="text-center mb-4">Contáctanos</h3>
    <form>
    <div class="row g-3">
    <div class="col-md-6">
    <label class="form-label">Nombre</label>
    <input type="text" class="form-control" placeholder="Tu nombre">
    </div>
    <div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" placeholder="correo@ejemplo.com">
    </div>
    <div class="col-12">
    <label class="form-label">Asunto</label>
    <input type="text" class="form-control" placeholder="Asunto del mensaje">
    </div>
    <div class="col-12">
    <label class="form-label">Mensaje</label>
    <textarea class="form-control" rows="4" placeholder="Escribe tu mensaje aquí..."></textarea>
    </div>
    <div class="col-12 text-center mt-4">
    <button type="submit" class="btn btn-primary btn-lg px-5">Enviar Mensaje</button>
    </div>
    </div>
    </form>
    </div>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-envelope-paper fs-3"></i>'
});

gjsEditor.BlockManager.add('newsletter-box', {
    category: { id: 'forms', label: 'Formularios', open: true },
    label: 'Newsletter',
    content: `
    <div class="bg-primary text-white p-4 rounded shadow-sm my-3">
    <div class="row align-items-center g-3">
    <div class="col-md-6">
    <h5 class="mb-1">📬 Suscríbete al boletín</h5>
    <p class="mb-0 small opacity-75">Recibe las últimas novedades directamente en tu correo.</p>
    </div>
    <div class="col-md-6">
    <div class="input-group">
    <input type="email" class="form-control" placeholder="tu@email.com">
    <button class="btn btn-dark" type="button">Suscribir</button>
    </div>
    </div>
    </div>
    </div>`,
    media: '<i class="bi bi-mailbox fs-3"></i>'
});

// ─────────────────────────────────────────────
// 🔧 UTILIDADES
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('divider', {
    category: { id: 'utils', label: 'Utilidades', open: true },
    label: 'Divisor',
    content: '<hr class="my-5 opacity-25">',
    media: '<i class="bi bi-hr fs-3"></i>'
});

gjsEditor.BlockManager.add('spacer', {
    category: { id: 'utils', label: 'Utilidades', open: true },
    label: 'Espaciador',
    content: '<div class="py-5 bg-transparent"></div>',
    media: '<i class="bi bi-arrows-expand fs-3"></i>'
});

gjsEditor.BlockManager.add('cta-banner', {
    category: { id: 'utils', label: 'Utilidades', open: true },
    label: 'Llamada a la Acción',
    content: `
    <section class="py-4 bg-warning">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-center text-md-start">
    <div>
    <h4 class="fw-bold mb-1">¿Listo para comenzar?</h4>
    <p class="mb-0 text-dark opacity-75">Únete a miles de usuarios que ya confían en nosotros.</p>
    </div>
    <a href="#" class="btn btn-dark btn-lg px-4 flex-shrink-0">Crear cuenta gratis</a>
    </div>
    </section>`,
    media: '<i class="bi bi-megaphone fs-3"></i>'
});

gjsEditor.BlockManager.add('landing-page', {
    category: 'Plantillas',
    label: 'Landing',
    content: `
    <main>
    <section class="hero">
    <h1>Mi Startup</h1>
    <p>Landing creada con GrapesJS</p>
    </section>

    <section class="features">
    <div class="feature">Feature 1</div>
    <div class="feature">Feature 2</div>
    <div class="feature">Feature 3</div>
    </section>
    </main>

    <style>
    .hero{
        padding:100px;
        background:#0f172a;
        color:white;
        text-align:center;
    }

    .features{
        display:flex;
        gap:20px;
        padding:40px;
    }

    .feature{
        flex:1;
        padding:20px;
        background:#f3f4f6;
        border-radius:12px;
    }
    </style>
    `
});
