// ─────────────────────────────────────────────
// 🖼️ MEDIA & EMBEDS
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-gallery', {
    category: { id: 'media', label: 'Media & Embeds', open: false },
    label: 'Galería de Imágenes',
    content: `<section class="py-5 bg-light">
    <div class="container">
    <h2 class="text-center mb-4 fw-bold">Galería</h2>
    <div class="row g-3">
    <div class="col-6 col-md-4 col-lg-3"><div class="card border-0 shadow-sm"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=1" class="card-img-top" alt="img"><div class="card-body p-2"><p class="mb-0 small text-muted">Imagen 1</p></div></div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="card border-0 shadow-sm"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=2" class="card-img-top" alt="img"><div class="card-body p-2"><p class="mb-0 small text-muted">Imagen 2</p></div></div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="card border-0 shadow-sm"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=3" class="card-img-top" alt="img"><div class="card-body p-2"><p class="mb-0 small text-muted">Imagen 3</p></div></div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="card border-0 shadow-sm"><img src="https://placehold.co/400x300/e9ecef/6c757d?text=4" class="card-img-top" alt="img"><div class="card-body p-2"><p class="mb-0 small text-muted">Imagen 4</p></div></div></div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-images fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-video', {
    category: { id: 'media', label: 'Media & Embeds', open: false },
    label: 'Video Responsive',
    content: `<section class="py-5 bg-dark text-white">
    <div class="container text-center">
    <h2 class="fw-bold mb-4">Video Demo</h2>
    <div class="row justify-content-center">
    <div class="col-lg-10">
    <div class="ratio ratio-16x9 shadow-lg rounded overflow-hidden">
    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video" allowfullscreen></iframe>
    </div>
    <p class="mt-3 text-muted small">Reproduce el video para conocer más sobre nuestros servicios.</p>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-play-circle fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-map', {
    category: { id: 'media', label: 'Media & Embeds', open: false },
    label: 'Mapa Interactivo',
    content: `<section class="py-4">
    <div class="container">
    <div class="ratio ratio-21x9 shadow-sm rounded overflow-hidden">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3976.123!2d-74.006!3d40.712!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQyJzQ2LjAiTiA3NMKwMDAnMjEuNiJX!5e0!3m2!1ses!2sus!4v1234567890!5m2!1ses!2sus" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
    </div>
    <div class="mt-3 d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Nuestra Ubicación</h5>
    <a href="#" class="btn btn-outline-primary btn-sm">Abrir en Maps</a>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-geo-alt fs-3"></i>'
});

// ─────────────────────────────────────────────
// 🎛️ INTERACTIVOS
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-carousel', {
    category: { id: 'interactive', label: 'Interactivos', open: false },
    label: 'Carrusel / Slider',
    content: `<div id="heroCarousel" class="carousel slide my-4 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
    <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner bg-primary text-white text-center py-5">
    <div class="carousel-item active"><div class="container"><p class="lead fst-italic mb-4">"Excelente servicio y resultados profesionales."</p><h5 class="fw-bold">Ana García</h5><small>CEO, TechStart</small></div></div>
    <div class="carousel-item"><div class="container"><p class="lead fst-italic mb-4">"La mejor herramienta para crear sin complicaciones."</p><h5 class="fw-bold">Carlos Mendoza</h5><small>Director Creativo</small></div></div>
    <div class="carousel-item"><div class="container"><p class="lead fst-italic mb-4">"Soporte rápido y diseño moderno."</p><h5 class="fw-bold">Laura Vega</h5><small>Marketing Manager</small></div></div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>`,
    media: '<i class="bi bi-collection-play fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-modal', {
    category: { id: 'interactive', label: 'Interactivos', open: false },
    label: 'Modal / Popup',
    content: `<div class="text-center my-4">
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#customModal1">Abrir Modal</button>
    </div>
    <div class="modal fade" id="customModal1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold">Título del Modal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p>Contenido editable. Ideal para formularios, avisos o detalles adicionales.</p></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary">Confirmar</button></div>
    </div>
    </div>
    </div>`,
    media: '<i class="bi bi-window fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-offcanvas', {
    category: { id: 'interactive', label: 'Interactivos', open: false },
    label: 'Panel Lateral (Offcanvas)',
                        content: `<div class="text-center my-4">
                        <button class="btn btn-outline-dark btn-lg" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidePanel1">Abrir Panel</button>
                        </div>
                        <div class="offcanvas offcanvas-end" tabindex="-1" id="sidePanel1">
                        <div class="offcanvas-header"><h5 class="offcanvas-title fw-bold">Menú Lateral</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
                        <div class="offcanvas-body">
                        <p>Contenido deslizable. Ideal para filtros, menús o formularios rápidos.</p>
                        <hr><h6>Enlaces</h6>
                        <ul class="nav flex-column">
                        <li class="nav-item mb-2"><a href="#" class="nav-link text-dark p-0">Inicio</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link text-dark p-0">Servicios</a></li>
                        </ul>
                        </div>
                        </div>`,
                        media: '<i class="bi bi-layout-sidebar-inset fs-3"></i>'
});

// ─────────────────────────────────────────────
// 📊 DATOS & TABLAS
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-table', {
    category: { id: 'data', label: 'Datos & Tablas', open: false },
    label: 'Tabla Responsive',
    content: `<div class="container my-4">
    <h4 class="mb-3 fw-bold">Listado de Datos</h4>
    <div class="table-responsive shadow-sm rounded">
    <table class="table table-hover table-striped align-middle mb-0">
    <thead class="bg-light"><tr><th>#</th><th>Nombre</th><th>Cargo</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody>
    <tr><td>1</td><td>María López</td><td>Desarrolladora</td><td><span class="badge bg-success">Activo</span></td><td><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>
    <tr><td>2</td><td>Juan Pérez</td><td>Diseñador</td><td><span class="badge bg-warning text-dark">Pendiente</span></td><td><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>
    <tr><td>3</td><td>Sofía Ruiz</td><td>Marketing</td><td><span class="badge bg-danger">Inactivo</span></td><td><button class="btn btn-sm btn-outline-primary">Ver</button></td></tr>
    </tbody>
    </table>
    </div>
    </div>`,
    media: '<i class="bi bi-table fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-comparison', {
    category: { id: 'data', label: 'Datos & Tablas', open: false },
    label: 'Tabla Comparativa',
    content: `<section class="py-5">
    <div class="container">
    <h3 class="text-center mb-4 fw-bold">Comparación de Planes</h3>
    <div class="table-responsive shadow-sm rounded">
    <table class="table table-bordered align-middle text-center mb-0">
    <thead class="bg-light"><tr><th class="text-start">Característica</th><th>Básico</th><th>Pro</th><th>Empresas</th></tr></thead>
    <tbody>
    <tr><td class="text-start fw-bold">Usuarios</td><td>1</td><td>10</td><td>Ilimitados</td></tr>
    <tr><td class="text-start fw-bold">Almacenamiento</td><td>5 GB</td><td>50 GB</td><td>500 GB</td></tr>
    <tr><td class="text-start fw-bold">Soporte</td><td><i class="bi bi-dash text-muted"></i></td><td><i class="bi bi-check text-success"></i></td><td><i class="bi bi-check text-success"></i></td></tr>
    <tr><td class="text-start fw-bold">API</td><td><i class="bi bi-x text-danger"></i></td><td><i class="bi bi-check text-success"></i></td><td><i class="bi bi-check text-success"></i></td></tr>
    <tr class="bg-light fw-bold"><td class="text-start">Precio</td><td>$9/mes</td><td>$29/mes</td><td>$99/mes</td></tr>
    </tbody>
    </table>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-arrow-left-right fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-stats', {
    category: { id: 'data', label: 'Datos & Tablas', open: false },
    label: 'Estadísticas / Contadores',
    content: `<section class="py-5 bg-dark text-white">
    <div class="container">
    <div class="row text-center g-4">
    <div class="col-6 col-md-3"><div class="display-4 fw-bold text-warning">1,250+</div><p class="mb-0 small text-uppercase opacity-75">Clientes</p></div>
    <div class="col-6 col-md-3"><div class="display-4 fw-bold text-info">98%</div><p class="mb-0 small text-uppercase opacity-75">Satisfacción</p></div>
    <div class="col-6 col-md-3"><div class="display-4 fw-bold text-success">350+</div><p class="mb-0 small text-uppercase opacity-75">Proyectos</p></div>
    <div class="col-6 col-md-3"><div class="display-4 fw-bold text-danger">24/7</div><p class="mb-0 small text-uppercase opacity-75">Soporte</p></div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-graph-up-arrow fs-3"></i>'
});

// ─────────────────────────────────────────────
// 👥 PROCESOS & EQUIPO
// ─────────────────────────────────────────────
gjsEditor.BlockManager.add('bs-team', {
    category: { id: 'process', label: 'Procesos & Equipo', open: false },
    label: 'Equipo de Trabajo',
    content: `<section class="py-5 bg-light">
    <div class="container text-center">
    <h2 class="fw-bold mb-5">Nuestro Equipo</h2>
    <div class="row g-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><img src="https://placehold.co/150x150/6c757d/ffffff?text=1" class="rounded-circle mx-auto mb-3" width="120" height="120" alt="Member"><h5 class="fw-bold mb-1">Nombre Completo</h5><p class="text-muted small mb-3">Cargo / Especialidad</p><div class="d-flex justify-content-center gap-3 fs-5"><a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a><a href="#" class="text-dark"><i class="bi bi-github"></i></a><a href="#" class="text-info"><i class="bi bi-twitter-x"></i></a></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><img src="https://placehold.co/150x150/6c757d/ffffff?text=2" class="rounded-circle mx-auto mb-3" width="120" height="120" alt="Member"><h5 class="fw-bold mb-1">Nombre Completo</h5><p class="text-muted small mb-3">Cargo / Especialidad</p><div class="d-flex justify-content-center gap-3 fs-5"><a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a><a href="#" class="text-dark"><i class="bi bi-github"></i></a><a href="#" class="text-info"><i class="bi bi-twitter-x"></i></a></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><img src="https://placehold.co/150x150/6c757d/ffffff?text=3" class="rounded-circle mx-auto mb-3" width="120" height="120" alt="Member"><h5 class="fw-bold mb-1">Nombre Completo</h5><p class="text-muted small mb-3">Cargo / Especialidad</p><div class="d-flex justify-content-center gap-3 fs-5"><a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a><a href="#" class="text-dark"><i class="bi bi-github"></i></a><a href="#" class="text-info"><i class="bi bi-twitter-x"></i></a></div></div></div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-people fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-timeline', {
    category: { id: 'process', label: 'Procesos & Equipo', open: false },
    label: 'Línea de Tiempo',
    content: `<section class="py-5 bg-light">
    <div class="container">
    <h2 class="text-center mb-5 fw-bold">Nuestra Historia</h2>
    <div class="row justify-content-center">
    <div class="col-lg-8">
    <div class="border-start border-3 border-primary ps-4 ms-3">
    <div class="mb-4 position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill bg-primary" style="left: -1.15rem !important;">2020</span><h5 class="fw-bold">Fundación</h5><p class="text-muted mb-0">Iniciamos el proyecto con visión clara.</p></div>
    <div class="mb-4 position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill bg-success" style="left: -1.15rem !important;">2022</span><h5 class="fw-bold">Primer Hito</h5><p class="text-muted mb-0">Lanzamiento beta y primeros 100 usuarios.</p></div>
    <div class="position-relative"><span class="position-absolute start-0 translate-middle badge rounded-pill bg-warning text-dark" style="left: -1.15rem !important;">2024</span><h5 class="fw-bold">Expansión</h5><p class="text-muted mb-0">Superamos los 10,000 usuarios activos.</p></div>
    </div>
    </div>
    </div>
    </div>
    </section>`,
    media: '<i class="bi bi-clock-history fs-3"></i>'
});

gjsEditor.BlockManager.add('bs-steps', {
    category: { id: 'process', label: 'Procesos & Equipo', open: false },
    label: 'Pasos / Wizard',
    content: `<div class="container my-5">
    <ul class="nav nav-pills nav-fill mb-4">
    <li class="nav-item"><a class="nav-link active" href="#">1. Cuenta</a></li>
    <li class="nav-item"><a class="nav-link disabled" href="#">2. Datos</a></li>
    <li class="nav-item"><a class="nav-link disabled" href="#">3. Confirmación</a></li>
    </ul>
    <div class="card border-0 shadow-sm p-4">
    <h5 class="fw-bold mb-3">Completa tu registro</h5>
    <p class="text-muted">Ejemplo visual de wizard para formularios largos o checkout.</p>
    <div class="d-flex justify-content-between mt-4"><button class="btn btn-outline-secondary" disabled>Anterior</button><button class="btn btn-primary">Siguiente</button></div>
    </div>
    </div>`,
    media: '<i class="bi bi-signpost-split fs-3"></i>'
});
