// ======================================================
// BOOTSTRAP BLOCKS PARA GRAPESJS
// ======================================================
// ======================================================
// NAVBAR
// ======================================================

gjsEditor.BlockManager.add('bs-navbar', {
category: {
    id: 'bootstrap-layout',
        label: 'Bootstrap Layout',
        open: true
        },
        label: 'Navbar',
        media: '<i class="fas fa-bars fa-2x"></i>',
        content: `
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
    <a class="navbar-brand" href="#">
    MiLogo
    </a>
    <button
    class="navbar-toggler"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#navbarNav"
    >
    <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ms-auto">
    <li class="nav-item">
    <a class="nav-link active" href="#">
    Inicio
    </a>
    </li>
    <li class="nav-item">
    <a class="nav-link" href="#">
    Servicios
    </a>
    </li>
    <li class="nav-item">
    <a class="nav-link" href="#">
    Contacto
    </a>
    </li>
    </ul>
    </div>
    </div>
    </nav>
    `
});
// ======================================================
// HERO SECTION
// ======================================================

gjsEditor.BlockManager.add('bs-hero', {
        category: {
        id: 'bootstrap-sections',
                label: 'Bootstrap Sections',
                open: true
        },
                label: 'Hero',
                media: '<i class="fas fa-desktop fa-2x"></i>',
                content: `
    <section class="py-5 bg-primary text-white">
    <div class="container text-center">

    <h1 class="display-3 fw-bold">
    Bootstrap Hero
    </h1>

    <p class="lead">
    Crea páginas increíbles con GrapesJS
    </p>

    <a href="#" class="btn btn-light btn-lg">
    Comenzar
    </a>

    </div>
    </section>
    `
        });
// ======================================================
// CARD
// ======================================================

        gjsEditor.BlockManager.add('bs-card', {

        category: {
        id: 'bootstrap-components',
                label: 'Bootstrap Components',
                open: true
        },
                label: 'Card',
                media: '<i class="far fa-id-card fa-2x"></i>',
                content: `
    <div class="card shadow-sm" style="width: 22rem;">

    <img
    src="https://picsum.photos/400/250"
    class="card-img-top"
    >

    <div class="card-body">

    <h5 class="card-title">
    Bootstrap Card
    </h5>

    <p class="card-text">
    Ejemplo de tarjeta Bootstrap para GrapesJS.
    </p>

    <a href="#" class="btn btn-primary">
    Ver Más
    </a>

    </div>

    </div>
    `
        });
// ======================================================
// ALERT
// ======================================================

        gjsEditor.BlockManager.add('bs-alert', {

        category: {
        id: 'bootstrap-components',
                label: 'Bootstrap Components',
                open: true
        },
                label: 'Alert',
                media: '<i class="fas fa-exclamation-triangle fa-2x"></i>',
                content: `
    <div class="alert alert-warning" role="alert">

    <strong>Atención!</strong>
    Este es un mensaje de alerta Bootstrap.

    </div>
    `
        });
// ======================================================
// BUTTONS
// ======================================================

gjsEditor.BlockManager.add('bs-buttons', {
        category: {
                id: 'bootstrap-ui',
                label: 'Bootstrap UI',
                open: true
            },
                label: 'Buttons',
                media: '<i class="fas fa-square fa-2x"></i>',
                content: `
    <div class="d-flex gap-2">

    <button class="btn btn-primary">
    Primary
    </button>

    <button class="btn btn-success">
    Success
    </button>

    <button class="btn btn-danger">
    Danger
    </button>

    </div>
    `
        });
// ======================================================
// ACCORDION
// ======================================================

        gjsEditor.BlockManager.add('bs-accordion', {

        category: {
        id: 'bootstrap-components',
                label: 'Bootstrap Components',
                open: true
        },
                label: 'Accordion',
                media: '<i class="fas fa-stream fa-2x"></i>',
                content: `
    <div class="accordion" id="accordionExample">
    <div class="accordion-item">
    <h2 class="accordion-header">
    <button
    class="accordion-button"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#collapseOne"
    >
    Accordion Item
    </button>
    </h2>
    <div
    id="collapseOne"
    class="accordion-collapse collapse show"
    >

    <div class="accordion-body">
    Contenido del accordion.
    </div>

    </div>

    </div>

    </div>
    `
        });
// ======================================================
// MODAL
// ======================================================

        gjsEditor.BlockManager.add('bs-modal', {

        category: {
        id: 'bootstrap-components',
                label: 'Bootstrap Components',
                open: true
        },
                label: 'Modal',
                media: '<i class="far fa-window-maximize fa-2x"></i>',
                content: `
    <div>

    <button
    class="btn btn-primary"
    data-bs-toggle="modal"
    data-bs-target="#exampleModal"
    >
    Abrir Modal
    </button>

    <div
    class="modal fade"
    id="exampleModal"
    tabindex="-1"
    >

    <div class="modal-dialog">

    <div class="modal-content">

    <div class="modal-header">

    <h5 class="modal-title">
    Modal Bootstrap
    </h5>

    <button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
    ></button>

    </div>

    <div class="modal-body">
    Contenido del modal.
    </div>

    <div class="modal-footer">

    <button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
    >
    Cerrar
    </button>

    </div>

    </div>

    </div>

    </div>

    </div>
    `
        });
// ======================================================
// FORMULARIO
// ======================================================

        gjsEditor.BlockManager.add('bs-form', {

        category: {
        id: 'bootstrap-forms',
                label: 'Bootstrap Forms',
                open: true
        },
                label: 'Form',
                media: '<i class="fas fa-wpforms fa-2x"></i>',
                content: `
    <form class="p-4 border rounded bg-light">

    <div class="mb-3">

    <label class="form-label">
    Email
    </label>

    <input
    type="email"
    class="form-control"
    placeholder="correo@ejemplo.com"
    >

    </div>

    <div class="mb-3">

    <label class="form-label">
    Password
    </label>

    <input
    type="password"
    class="form-control"
    >

    </div>

    <button class="btn btn-primary">
    Ingresar
    </button>

    </form>
    `
        });
// ======================================================
// FOOTER
// ======================================================

        gjsEditor.BlockManager.add('bs-footer', {

        category: {
        id: 'bootstrap-layout',
                label: 'Bootstrap Layout',
                open: true
        },
                label: 'Footer',
                media: '<i class="fas fa-minus fa-2x"></i>',
                content: `
    <footer class="bg-dark text-white py-4">

    <div class="container text-center">

    <h5>
    Mi Sitio Web
    </h5>

    <p class="mb-0">
    © 2026 Todos los derechos reservados
    </p>

    </div>

    </footer>
    `
        });
// ======================================================
// GRID 3 COLUMNAS
// ======================================================

        gjsEditor.BlockManager.add('bs-grid-3', {

        category: {
        id: 'bootstrap-layout',
                label: 'Bootstrap Layout',
                open: true
        },
                label: 'Grid 3 Cols',
                media: '<i class="fas fa-columns fa-2x"></i>',
                content: `
    <div class="container py-5">

    <div class="row">

    <div class="col-md-4">
    <div class="p-4 bg-light border">
    Columna 1
    </div>
    </div>

    <div class="col-md-4">
    <div class="p-4 bg-light border">
    Columna 2
    </div>
    </div>

    <div class="col-md-4">
    <div class="p-4 bg-light border">
    Columna 3
    </div>
    </div>

    </div>

    </div>
    `
        });