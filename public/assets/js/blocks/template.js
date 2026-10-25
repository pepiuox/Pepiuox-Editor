// ======================================================
// RESPONSIVE ADMIN LAYOUT
// SIDEBAR + TOPBAR + MAIN CONTENT
// BOOTSTRAP 5 + GRAPESJS BLOCK
// ======================================================

gjsEditor.BlockManager.add('bs-admin-layout', {

    category: {
        id: 'advanced-layouts',
        label: 'Advanced Layouts',
        open: true
    },

    label: 'Admin Dashboard',

    media: '<i class="fas fa-columns fa-2x"></i>',

    content: `

    <style>

    body{
        overflow-x:hidden;
    }

    .admin-wrapper{
        min-height:100vh;
        background:#f5f7fb;
    }

    /* =========================
     *    SIDEBAR
     * ==========================*/

    .sidebar{
        width:260px;
        min-height:100vh;
        background:#111827;
        transition:all .3s ease;
        position:fixed;
        top:0;
        left:0;
        z-index:1050;
    }

    .sidebar-brand{
        padding:24px;
        font-size:1.4rem;
        font-weight:bold;
        color:#fff;
        border-bottom:1px solid rgba(255,255,255,.08);
    }

    .sidebar .nav-link{
        color:#cbd5e1;
        padding:14px 24px;
        border-radius:8px;
        margin:4px 12px;
        transition:all .2s;
    }

    .sidebar .nav-link:hover{
        background:#1e293b;
        color:#fff;
    }

    .sidebar .nav-link.active{
        background:#2563eb;
        color:#fff;
    }

    /* =========================
     *    MAIN AREA
     * ==========================*/

    .main-wrapper{
        margin-left:260px;
        transition:all .3s ease;
    }

    /* =========================
     *    TOPBAR
     * ==========================*/

    .topbar{
        height:70px;
        background:#fff;
        border-bottom:1px solid #e5e7eb;
        padding:0 20px;
        position:sticky;
        top:0;
        z-index:1040;
    }

    .content-area{
        padding:30px;
    }

    /* =========================
     *    CARDS
     * ==========================*/

    .dashboard-card{
        border:none;
        border-radius:16px;
        box-shadow:0 2px 10px rgba(0,0,0,.05);
    }

    /* =========================
     *    MOBILE
     * ==========================*/

    @media(max-width:991px){

        .sidebar{
            left:-260px;
        }

        .sidebar.active{
            left:0;
        }

        .main-wrapper{
            margin-left:0;
        }

        .sidebar-overlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.4);
            z-index:1040;
        }

        .sidebar-overlay.active{
            display:block;
        }

    }

    </style>

    <div class="admin-wrapper">

    <!-- =========================
    SIDEBAR OVERLAY
    ========================== -->

    <div class="sidebar-overlay"></div>

    <!-- =========================
    SIDEBAR
    ========================== -->

    <aside class="sidebar">

    <div class="sidebar-brand">
    AdminPanel
    </div>

    <div class="p-3">

    <ul class="nav flex-column">

    <li class="nav-item">
    <a class="nav-link active" href="#">
    <i class="fas fa-home me-2"></i>
    Dashboard
    </a>
    </li>

    <li class="nav-item">
    <a class="nav-link" href="#">
    <i class="fas fa-users me-2"></i>
    Usuarios
    </a>
    </li>

    <li class="nav-item">
    <a class="nav-link" href="#">
    <i class="fas fa-chart-bar me-2"></i>
    Reportes
    </a>
    </li>

    <li class="nav-item">
    <a class="nav-link" href="#">
    <i class="fas fa-cog me-2"></i>
    Configuración
    </a>
    </li>

    </ul>

    </div>

    </aside>

    <!-- =========================
    MAIN WRAPPER
    ========================== -->

    <div class="main-wrapper">

    <!-- =========================
    TOP NAVBAR
    ========================== -->

    <nav class="topbar d-flex align-items-center justify-content-between">

    <div class="d-flex align-items-center">

    <button
    class="btn btn-light me-3"
    id="sidebarToggle"
    >
    <i class="fas fa-bars"></i>
    </button>

    <h5 class="mb-0">
    Dashboard
    </h5>

    </div>

    <div class="d-flex align-items-center">

    <div class="dropdown">

    <button
    class="btn btn-light dropdown-toggle"
    data-bs-toggle="dropdown"
    >

    <img
    src="https://i.pravatar.cc/40"
    class="rounded-circle me-2"
    >

    Admin

    </button>

    <ul class="dropdown-menu dropdown-menu-end">

    <li>
    <a class="dropdown-item" href="#">
    Perfil
    </a>
    </li>

    <li>
    <a class="dropdown-item" href="#">
    Configuración
    </a>
    </li>

    <li><hr class="dropdown-divider"></li>

    <li>
    <a class="dropdown-item text-danger" href="#">
    Logout
    </a>
    </li>

    </ul>

    </div>

    </div>

    </nav>

    <!-- =========================
    CONTENT
    ========================== -->

    <main class="content-area">

    <!-- STATS -->

    <div class="row g-4 mb-4">

    <div class="col-md-3">

    <div class="card dashboard-card">

    <div class="card-body">

    <h6 class="text-muted">
    Usuarios
    </h6>

    <h2 class="fw-bold">
    12,540
    </h2>

    </div>

    </div>

    </div>

    <div class="col-md-3">

    <div class="card dashboard-card">

    <div class="card-body">

    <h6 class="text-muted">
    Ventas
    </h6>

    <h2 class="fw-bold">
    $24K
    </h2>

    </div>

    </div>

    </div>

    <div class="col-md-3">

    <div class="card dashboard-card">

    <div class="card-body">

    <h6 class="text-muted">
    Órdenes
    </h6>

    <h2 class="fw-bold">
    1,240
    </h2>

    </div>

    </div>

    </div>

    <div class="col-md-3">

    <div class="card dashboard-card">

    <div class="card-body">

    <h6 class="text-muted">
    Tickets
    </h6>

    <h2 class="fw-bold">
    87
    </h2>

    </div>

    </div>

    </div>

    </div>

    <!-- TABLE -->

    <div class="card dashboard-card">

    <div class="card-body">

    <div class="d-flex justify-content-between mb-4">

    <h5>
    Usuarios recientes
    </h5>

    <button class="btn btn-primary">
    Nuevo Usuario
    </button>

    </div>

    <div class="table-responsive">

    <table class="table align-middle">

    <thead>

    <tr>

    <th>Usuario</th>
    <th>Email</th>
    <th>Estado</th>
    <th>Acciones</th>

    </tr>

    </thead>

    <tbody>

    <tr>

    <td>

    <div class="d-flex align-items-center">

    <img
    src="https://i.pravatar.cc/45?img=5"
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

    <button class="btn btn-sm btn-light">
    Editar
    </button>

    <button class="btn btn-sm btn-danger">
    Eliminar
    </button>

    </td>

    </tr>

    <tr>

    <td>

    <div class="d-flex align-items-center">

    <img
    src="https://i.pravatar.cc/45?img=8"
    class="rounded-circle me-2"
    >

    María López

    </div>

    </td>

    <td>
    maria@email.com
    </td>

    <td>
    <span class="badge bg-warning">
    Pendiente
    </span>
    </td>

    <td>

    <button class="btn btn-sm btn-light">
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

    </div>

    </div>

    </main>

    </div>

    </div>

    <script>

    setTimeout(() => {

        const sidebar =
        document.querySelector('.sidebar');

        const toggle =
        document.querySelector('#sidebarToggle');

        const overlay =
        document.querySelector('.sidebar-overlay');

        if(toggle){

            toggle.addEventListener('click', () => {

                sidebar.classList.toggle('active');

                overlay.classList.toggle('active');

            });

        }

        if(overlay){

            overlay.addEventListener('click', () => {

                sidebar.classList.remove('active');

                overlay.classList.remove('active');

            });

        }

    }, 500);

    </script>

    `
});
