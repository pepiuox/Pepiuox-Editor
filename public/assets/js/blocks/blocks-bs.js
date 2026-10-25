// ─────────────────────────────────────────────
// 📐 LAYOUT
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-navbar', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Navbar',
    content: `<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
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

gjsEditor.BlockManager.add('bs-hero', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Hero Section',
    content: `<section class="bg-primary text-white py-5">
    <div class="container text-center py-5">
    <h1 class="display-4 fw-bold mb-3">Título Principal</h1>
    <p class="lead mb-4 opacity-75">Subtítulo descriptivo que captura la atención del visitante.</p>
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
    <button class="btn btn-light btn-lg px-4 gap-3">Comenzar</button>
    <button class="btn btn-outline-light btn-lg px-4">Saber más</button>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-badge-hd fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-footer', {
    category: { id: 'layout', label: 'Layout', open: true },
    label: 'Footer',
    content: `<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
    <div class="row g-4">
    <div class="col-md-4">
    <h5 class="fw-bold mb-3">🍇 GrapesCMS</h5>
    <p class="text-muted small">Editor visual de páginas web construido con PHP y Bootstrap.</p>
    </div>
    <div class="col-md-4">
    <h6 class="fw-bold mb-3">Enlaces</h6>
    <ul class="list-unstyled small">
    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Inicio</a></li>
    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">Documentación</a></li>
    </ul>
    </div>
    <div class="col-md-4">
    <h6 class="fw-bold mb-3">Redes</h6>
    <div class="d-flex gap-3 fs-5">
    <a href="#" class="text-light"><i class="bi bi-facebook"></i></a>
    <a href="#" class="text-light"><i class="bi bi-twitter-x"></i></a>
    <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
    </div>
    </div>
    </div>
    </div>
    </footer>`,
    media: '<i class="bi bi-layout-footer fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📝 CONTENIDO
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-cards-row', {
    category: { id: 'content', label: 'Contenido', open: false },
    label: 'Tarjetas 3 Columnas',
    content: `<section class="py-5">
    <div class="container">
    <div class="row g-4">
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
    <div class="card-body text-center p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-lightning-charge"></i></div>
    <h5 class="card-title fw-bold">Rápido</h5>
    <p class="card-text text-muted">Descripción breve del beneficio.</p>
    </div>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
    <div class="card-body text-center p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-check"></i></div>
    <h5 class="card-title fw-bold">Seguro</h5>
    <p class="card-text text-muted">Descripción breve del beneficio.</p>
    </div>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
    <div class="card-body text-center p-4">
    <div class="fs-1 text-primary mb-3"><i class="bi bi-gear"></i></div>
    <h5 class="card-title fw-bold">Flexible</h5>
    <p class="card-text text-muted">Descripción breve del beneficio.</p>
    </div>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-grid-3x3 fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-accordion', {
    category: { id: 'content', label: 'Contenido', open: false },
    label: 'Acordeón FAQ',
    content: `<div class="accordion" id="faqAccordion1">
    <div class="accordion-item">
    <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">¿Cómo funciona?</button></h2>
    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion1"><div class="accordion-body">Respuesta detallada aquí...</div></div>
    </div>
    <div class="accordion-item">
    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">¿Tiene costo?</button></h2>
    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion1"><div class="accordion-body">Otra respuesta detallada...</div></div>
    </div>
    </div>`,
    media: '<i class="bi bi-chevron-expand fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-tabs', {
    category: { id: 'content', label: 'Contenido', open: false },
    label: 'Pestañas (Tabs)',
                        content: `<ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">Pestaña 1</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">Pestaña 2</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">Pestaña 3</button></li>
                        </ul>
                        <div class="tab-content p-3 border border-top-0 bg-white" id="myTabContent">
                        <div class="tab-pane fade show active" id="tab1">Contenido de la pestaña 1...</div>
                        <div class="tab-pane fade" id="tab2">Contenido de la pestaña 2...</div>
                        <div class="tab-pane fade" id="tab3">Contenido de la pestaña 3...</div>
                        </div>`,
                        media: '<i class="bi bi-folder2-open fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📥 FORMULARIOS
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-login', {
    category: { id: 'forms', label: 'Formularios', open: false },
    label: 'Login Card',
    content: `<section class="py-5 bg-light">
    <div class="container">
    <div class="row justify-content-center">
    <div class="col-md-5">
    <div class="card shadow border-0 p-4">
    <h3 class="text-center mb-4">Iniciar Sesión</h3>
    <form>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" placeholder="correo@ejemplo.com"></div>
    <div class="mb-3"><label class="form-label">Contraseña</label><input type="password" class="form-control" placeholder="••••••••"></div>
    <div class="d-grid"><button type="submit" class="btn btn-primary">Entrar</button></div>
    </form>
    <div class="text-center mt-3"><a href="#" class="small text-muted">¿Olvidaste tu contraseña?</a></div>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-box-arrow-in-right fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-search-bar', {
    category: { id: 'forms', label: 'Formularios', open: false },
    label: 'Barra de Búsqueda',
    content: `<div class="container my-4">
    <div class="row justify-content-center">
    <div class="col-md-8">
    <div class="input-group input-group-lg shadow-sm">
    <input type="text" class="form-control" placeholder="¿Qué estás buscando?">
    <button class="btn btn-primary px-4" type="button"><i class="bi bi-search"></i> Buscar</button>
    </div>
    </div>
    </div>
    </div>`,
    media: '<i class="bi bi-search fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📊 DATOS & UTILIDADES
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-pricing', {
    category: { id: 'pricing', label: 'Precios', open: false },
    label: 'Tabla de Precios',
    content: `<section class="py-5 bg-light">
    <div class="container">
    <div class="row g-4 text-center">
    <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3">
    <h5 class="text-muted fw-bold">Básico</h5>
    <div class="display-6 fw-bold my-3">$9<span class="fs-6 text-muted">/mes</span></div>
    <ul class="list-unstyled mb-4 small text-start">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 Usuario</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>5 Proyectos</li>
    </ul>
    <button class="btn btn-outline-primary w-100">Elegir</button>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card border-primary shadow-sm p-3">
    <div class="badge bg-primary mb-2">Popular</div>
    <h5 class="text-primary fw-bold">Pro</h5>
    <div class="display-6 fw-bold my-3">$29<span class="fs-6 text-muted">/mes</span></div>
    <ul class="list-unstyled mb-4 small text-start">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>5 Usuarios</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Proyectos ilimitados</li>
    </ul>
    <button class="btn btn-primary w-100">Elegir</button>
    </div>
    </div>
    <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3">
    <h5 class="text-muted fw-bold">Empresas</h5>
    <div class="display-6 fw-bold my-3">$99<span class="fs-6 text-muted">/mes</span></div>
    <ul class="list-unstyled mb-4 small text-start">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Usuarios ilimitados</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Soporte 24/7</li>
    </ul>
    <button class="btn btn-outline-dark w-100">Contactar</button>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-tag fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-progress', {
    category: { id: 'utils', label: 'Utilidades', open: false },
    label: 'Barra de Progreso',
    content: `<div class="container my-4">
    <div class="mb-2 d-flex justify-content-between"><span>Habilidad 1</span><span>85%</span></div>
    <div class="progress mb-3" style="height: 8px;"><div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div></div>
    <div class="mb-2 d-flex justify-content-between"><span>Habilidad 2</span><span>60%</span></div>
    <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" role="progressbar" style="width: 60%"></div></div>
    </div>`,
    media: '<i class="bi bi-bar-chart-line fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-alert', {
    category: { id: 'utils', label: 'Utilidades', open: false },
    label: 'Alerta Dismissible',
    content: `<div class="alert alert-success alert-dismissible fade show my-3" role="alert">
    <i class="bi bi-check-circle me-2"></i>¡Operación realizada con éxito!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`,
    media: '<i class="bi bi-exclamation-triangle fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-breadcrumb', {
    category: { id: 'utils', label: 'Utilidades', open: false },
    label: 'Breadcrumbs',
    content: `<nav class="my-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
    <li class="breadcrumb-item"><a href="#">Categoría</a></li>
    <li class="breadcrumb-item active">Página Actual</li>
    </ol>
    </nav>`,
    media: '<i class="bi bi-slash-square fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-pagination', {
    category: { id: 'utils', label: 'Utilidades', open: false },
    label: 'Paginación',
    content: `<nav class="my-4" aria-label="Page navigation">
    <ul class="pagination justify-content-center mb-0">
    <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
    <li class="page-item active"><a class="page-link" href="#">1</a></li>
    <li class="page-item"><a class="page-link" href="#">2</a></li>
    <li class="page-item"><a class="page-link" href="#">3</a></li>
    <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
    </ul>
    </nav>`,
    media: '<i class="bi bi-three-dots fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-badge', {
    category: { id: 'utils', label: 'Utilidades', open: false },
    label: 'Badges / Etiquetas',
    content: `<div class="my-3">
    <span class="badge bg-primary">Nuevo</span>
    <span class="badge bg-success ms-1">Activo</span>
    <span class="badge bg-warning text-dark ms-1">Pendiente</span>
    <span class="badge bg-danger ms-1">Urgente</span>
    <span class="badge bg-secondary ms-1">Archivo</span>
    </div>`,
    media: '<i class="bi bi-bookmark fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-cta', {
    category: { id: 'marketing', label: 'Marketing', open: false },
    label: 'Call to Action',
    content: `<section class="py-4 bg-warning">
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

