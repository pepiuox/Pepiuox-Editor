// ======================================================
// ADVANCED BOOTSTRAP COMPONENTS
// GRAPESJS BLOCK MANAGER
// ======================================================


// ======================================================
// PRICING SECTION
// ======================================================

gjsEditor.BlockManager.add('bs-pricing-section', {

    category: {
        id: 'advanced-sections',
        label: 'Advanced Sections',
        open: true
    },

    label: 'Pricing Section',

    media: '<i class="fas fa-dollar-sign fa-2x"></i>',

    content: `
    <section class="py-5 bg-light">

    <div class="container">

    <div class="text-center mb-5">

    <h2 class="fw-bold">
    Planes y Precios
    </h2>

    <p class="text-muted">
    Elige el mejor plan para tu negocio
    </p>

    </div>

    <div class="row g-4">

    <div class="col-md-4">

    <div class="card border-0 shadow h-100">

    <div class="card-body text-center p-5">

    <h4>Básico</h4>

    <h1 class="display-4 fw-bold">
    $9
    </h1>

    <p class="text-muted">
    /mes
    </p>

    <ul class="list-unstyled my-4">

    <li class="mb-2">1 Usuario</li>
    <li class="mb-2">5 Proyectos</li>
    <li class="mb-2">Soporte Básico</li>

    </ul>

    <button class="btn btn-outline-primary w-100">
    Elegir Plan
    </button>

    </div>

    </div>

    </div>

    <div class="col-md-4">

    <div class="card border-primary border-3 shadow-lg h-100">

    <div class="card-body text-center p-5">

    <span class="badge bg-primary mb-3">
    POPULAR
    </span>

    <h4>Pro</h4>

    <h1 class="display-4 fw-bold">
    $29
    </h1>

    <p class="text-muted">
    /mes
    </p>

    <ul class="list-unstyled my-4">

    <li class="mb-2">10 Usuarios</li>
    <li class="mb-2">50 Proyectos</li>
    <li class="mb-2">Soporte Premium</li>

    </ul>

    <button class="btn btn-primary w-100">
    Elegir Plan
    </button>

    </div>

    </div>

    </div>

    <div class="col-md-4">

    <div class="card border-0 shadow h-100">

    <div class="card-body text-center p-5">

    <h4>Enterprise</h4>

    <h1 class="display-4 fw-bold">
    $99
    </h1>

    <p class="text-muted">
    /mes
    </p>

    <ul class="list-unstyled my-4">

    <li class="mb-2">Usuarios Ilimitados</li>
    <li class="mb-2">Proyectos Ilimitados</li>
    <li class="mb-2">Soporte 24/7</li>

    </ul>

    <button class="btn btn-dark w-100">
    Contactar
    </button>

    </div>

    </div>

    </div>

    </div>

    </div>

    </section>
    `
});



// ======================================================
// TESTIMONIALS
// ======================================================

gjsEditor.BlockManager.add('bs-testimonials', {

    category: {
        id: 'advanced-sections',
        label: 'Advanced Sections',
        open: true
    },

    label: 'Testimonials',

    media: '<i class="fas fa-comments fa-2x"></i>',

    content: `
    <section class="py-5">

    <div class="container">

    <div class="text-center mb-5">

    <h2 class="fw-bold">
    Lo que dicen nuestros clientes
    </h2>

    </div>

    <div class="row g-4">

    <div class="col-md-4">

    <div class="card border-0 shadow-sm h-100">

    <div class="card-body p-4">

    <div class="mb-3 text-warning">
    ★★★★★
    </div>

    <p class="text-muted">
    Excelente servicio y soporte técnico.
    </p>

    <div class="d-flex align-items-center mt-4">

    <img
    src="https://i.pravatar.cc/60?img=1"
    class="rounded-circle me-3"
    >

    <div>
    <h6 class="mb-0">Carlos Díaz</h6>
    <small class="text-muted">
    CEO Startup
    </small>
    </div>

    </div>

    </div>

    </div>

    </div>

    <div class="col-md-4">

    <div class="card border-0 shadow-sm h-100">

    <div class="card-body p-4">

    <div class="mb-3 text-warning">
    ★★★★★
    </div>

    <p class="text-muted">
    La plataforma es rápida y muy intuitiva.
    </p>

    <div class="d-flex align-items-center mt-4">

    <img
    src="https://i.pravatar.cc/60?img=5"
    class="rounded-circle me-3"
    >

    <div>
    <h6 class="mb-0">Ana Torres</h6>
    <small class="text-muted">
    Diseñadora UX
    </small>
    </div>

    </div>

    </div>

    </div>

    </div>

    <div class="col-md-4">

    <div class="card border-0 shadow-sm h-100">

    <div class="card-body p-4">

    <div class="mb-3 text-warning">
    ★★★★★
    </div>

    <p class="text-muted">
    Totalmente recomendado para empresas.
    </p>

    <div class="d-flex align-items-center mt-4">

    <img
    src="https://i.pravatar.cc/60?img=8"
    class="rounded-circle me-3"
    >

    <div>
    <h6 class="mb-0">Luis Mendoza</h6>
    <small class="text-muted">
    Fullstack Developer
    </small>
    </div>

    </div>

    </div>

    </div>

    </div>

    </div>

    </div>

    </section>
    `
});



// ======================================================
// DASHBOARD STATS
// ======================================================

gjsEditor.BlockManager.add('bs-dashboard-stats', {

    category: {
        id: 'advanced-widgets',
        label: 'Advanced Widgets',
        open: true
    },

    label: 'Dashboard Stats',

    media: '<i class="fas fa-chart-line fa-2x"></i>',

    content: `
    <section class="py-4 bg-dark text-white">

    <div class="container">

    <div class="row g-4 text-center">

    <div class="col-md-3">

    <div class="p-4 border rounded">

    <h1 class="display-5 fw-bold">
    15K
    </h1>

    <p class="mb-0">
    Usuarios
    </p>

    </div>

    </div>

    <div class="col-md-3">

    <div class="p-4 border rounded">

    <h1 class="display-5 fw-bold">
    320
    </h1>

    <p class="mb-0">
    Clientes
    </p>

    </div>

    </div>

    <div class="col-md-3">

    <div class="p-4 border rounded">

    <h1 class="display-5 fw-bold">
    98%
    </h1>

    <p class="mb-0">
    Satisfacción
    </p>

    </div>

    </div>

    <div class="col-md-3">

    <div class="p-4 border rounded">

    <h1 class="display-5 fw-bold">
    24/7
    </h1>

    <p class="mb-0">
    Soporte
    </p>

    </div>

    </div>

    </div>

    </div>

    </section>
    `
});



// ======================================================
// LOGIN PAGE
// ======================================================

gjsEditor.BlockManager.add('bs-login-page', {

    category: {
        id: 'advanced-pages',
        label: 'Advanced Pages',
        open: true
    },

    label: 'Login Page',

    media: '<i class="fas fa-sign-in-alt fa-2x"></i>',

    content: `
    <section
    class="vh-100 d-flex align-items-center bg-light"
    >

    <div class="container">

    <div class="row justify-content-center">

    <div class="col-md-5">

    <div class="card border-0 shadow-lg">

    <div class="card-body p-5">

    <div class="text-center mb-4">

    <h2 class="fw-bold">
    Iniciar Sesión
    </h2>

    <p class="text-muted">
    Bienvenido nuevamente
    </p>

    </div>

    <form>

    <div class="mb-3">

    <label class="form-label">
    Email
    </label>

    <input
    type="email"
    class="form-control form-control-lg"
    >

    </div>

    <div class="mb-4">

    <label class="form-label">
    Contraseña
    </label>

    <input
    type="password"
    class="form-control form-control-lg"
    >

    </div>

    <button
    class="btn btn-primary btn-lg w-100"
    >
    Entrar
    </button>

    </form>

    </div>

    </div>

    </div>

    </div>

    </div>

    </section>
    `
});



// ======================================================
// FEATURES SECTION
// ======================================================

gjsEditor.BlockManager.add('bs-features-grid', {
    category: {
        id: 'advanced-sections',
        label: 'Advanced Sections',
        open: true
    },
    label: 'Features Grid',
    media: '<i class="fas fa-th-large fa-2x"></i>',
    content: `
    <section class="py-5">
    <div class="container">
    <div class="text-center mb-5">
    <h2 class="fw-bold">
    Características Principales
    </h2>
    </div>
    <div class="row g-4">
    <div class="col-md-4">
    <div class="p-4 border rounded h-100 text-center">
    <div class="mb-3">
    <i class="fas fa-bolt fa-3x text-primary"></i>
    </div>
    <h4>Rápido</h4>
    <p class="text-muted">
    Optimizado para alto rendimiento.
    </p>
    </div>
    </div>
    <div class="col-md-4">
    <div class="p-4 border rounded h-100 text-center">
    <div class="mb-3">
    <i class="fas fa-lock fa-3x text-success"></i>
    </div>
    <h4>Seguro</h4>
    <p class="text-muted">
    Protección avanzada de datos.
    </p>
    </div>
    </div>
    <div class="col-md-4">
    <div class="p-4 border rounded h-100 text-center">
    <div class="mb-3">
    <i class="fas fa-cloud fa-3x text-info"></i>
    </div>
    <h4>Cloud</h4>
    <p class="text-muted">
    Acceso desde cualquier lugar.
    </p>
    </div>
    </div>
    </div>
    </div>
    </section>
    `
});



// ======================================================
// ADVANCED TABLE
// ======================================================

gjsEditor.BlockManager.add('bs-advanced-table', {
    category: {
        id: 'advanced-data',
        label: 'Advanced Data',
        open: true
    },
    label: 'Advanced Table',
    media: '<i class="fas fa-table fa-2x"></i>',
    content: `
    <div class="table-responsive p-4">
    <table class="table table-hover align-middle">
    <thead class="table-dark">
    <tr>
    <th>#</th>
    <th>Usuario</th>
    <th>Email</th>
    <th>Estado</th>
    <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td>1</td>
    <td>
    <div class="d-flex align-items-center">
    <img
    src="https://i.pravatar.cc/40?img=3"
    class="rounded-circle me-2"
    >
    Juan Pérez
    </div>
    </td>
    <td>
    juan@email.com
    </td>
    <td>
    <span class="badge bg-success">
    Activo
    </span>
    </td>
    <td>
    <button class="btn btn-sm btn-primary">
    Editar
    </button>
    <button class="btn btn-sm btn-danger">
    Eliminar
    </button>
    </td>
    </tr>
    </tbody>
    </table>
    </div>
    `
});
