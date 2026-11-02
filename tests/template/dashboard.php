<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PePiuoX - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
</head>
<body>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-code-slash"></i>
            </div>
            <div class="brand-text">
                <h5>PePiuoX</h5>
                <small>Maker v1.0.0</small>
            </div>
        </div>

        <!-- Menu -->
        <nav class="sidebar-menu">
            <!-- PRINCIPAL -->
            <div class="menu-section-title">Principal</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-grid-1x2"></i>
                        Dashboard
                    </a>
                </li>
            </ul>

            <!-- CONTENIDO -->
            <div class="menu-section-title">Contenido</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-file-earmark-text"></i>
                        Páginas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-layout-text-window"></i>
                        Templates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-list"></i>
                        Menús
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-folder"></i>
                        Archivos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link has-submenu" href="#" data-target="submenu-seo">
                        <i class="bi bi-search"></i>
                        SEO
                        <i class="bi bi-chevron-right arrow"></i>
                    </a>
                    <ul class="submenu" id="submenu-seo">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Configuración SEO</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link has-submenu" href="#" data-target="submenu-seo-meta">
                                Meta Tags
                                <i class="bi bi-chevron-right arrow"></i>
                            </a>
                            <ul class="sub-submenu" id="submenu-seo-meta">
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Global</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Por página</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Sitemap</a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- USUARIOS -->
            <div class="menu-section-title">Usuarios</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-people"></i>
                        Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-person-x"></i>
                        Usuarios Baneados
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link has-submenu" href="#" data-target="submenu-roles">
                        <i class="bi bi-shield-lock"></i>
                        Roles y permisos
                        <i class="bi bi-chevron-right arrow"></i>
                    </a>
                    <ul class="submenu" id="submenu-roles">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Roles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Permisos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Asignaciones</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-shield-check"></i>
                        2FA
                        <span class="ms-auto">
                            <span class="badge-off" style="background-color:#ffc107;color:#000;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:700;">OFF</span>
                        </span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User Profile -->
        <div class="sidebar-user">
            <div class="user-avatar">J</div>
            <div class="user-info">
                <div class="user-name">jose mantilla</div>
                <div class="user-role">Super admin</div>
            </div>
        </div>
    </aside>

    <!-- Topbar -->
    <header class="topbar" id="topbar">
        <button class="toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h4 class="page-title">Dashboard</h4>

        <div class="topbar-actions">
            <button class="action-btn" title="Idioma">
                <i class="bi bi-globe"></i>
            </button>
            <button class="action-btn" title="Notificaciones">
                <i class="bi bi-bell"></i>
                <span class="badge-dot"></span>
            </button>
            <a href="#" class="btn btn-ghost btn-sm" target="_blank" title="Ver sitio público">
      <i class="bi bi-box-arrow-up-right"></i>
      <span class="d-none d-lg-inline">Ver sitio</span>
    </a>
            <button class="action-btn" title="Pantalla completa">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <button class="action-btn" title="Perfil">
                <i class="bi bi-person-circle"></i>
            </button>
            <button class="action-btn btn-logout" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6 animate-in">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-number">3</div>
                    <div class="stat-label">Usuarios</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 animate-in">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-file-earmark"></i>
                    </div>
                    <div class="stat-number">12</div>
                    <div class="stat-label">Páginas</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 animate-in">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-number">12</div>
                    <div class="stat-label">Publicadas</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 animate-in">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="stat-number">0</div>
                    <div class="stat-label">Borradores</div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row g-4">
            <!-- Recent Pages -->
            <div class="col-xl-8 animate-in">
                <div class="content-card">
                    <div class="card-header">
                        <i class="bi bi-clock"></i>
                        <h5>Páginas recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-custom">
                            <table class="table table-dark-custom">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Autor</th>
                                        <th>Estado</th>
                                        <th>Actualizada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Desarrollo</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-11-01 01:58:13</td>
                                    </tr>
                                    <tr>
                                        <td>Portafolio</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-11-01 01:41:19</td>
                                    </tr>
                                    <tr>
                                        <td>proyectos</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-11-01 01:04:04</td>
                                    </tr>
                                    <tr>
                                        <td>quienes somos</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-11-01 00:34:13</td>
                                    </tr>
                                    <tr>
                                        <td>Inicio</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-10-31 20:39:13</td>
                                    </tr>
                                    <tr>
                                        <td>Cómo funciona e instalación</td>
                                        <td>pepiuox</td>
                                        <td><span class="status-badge published">Published</span></td>
                                        <td>2026-10-31 20:26:25</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-xl-4 animate-in">
                <div class="content-card">
                    <div class="card-header">
                        <i class="bi bi-activity"></i>
                        <h5>Actividad reciente</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <div class="activity-item">
                                <div class="timeline-dot"></div>
                                <div class="activity-time">2026-10-25 13:16:13</div>
                                <div class="activity-title">Sistema · user_deleted</div>
                                <div class="activity-desc">Usuario eliminado: zz_files_admin</div>
                            </div>
                            <div class="activity-item">
                                <div class="timeline-dot"></div>
                                <div class="activity-time">2026-10-25 13:14:38</div>
                                <div class="activity-title">Sistema · user_created</div>
                                <div class="activity-desc">Usuario creado: zz_files_admin</div>
                            </div>
                            <div class="activity-item">
                                <div class="timeline-dot"></div>
                                <div class="activity-time">2026-10-25 12:50:39</div>
                                <div class="activity-title">Sistema · user_deleted</div>
                                <div class="activity-desc">Usuario eliminado: zz_dnd_admin</div>
                            </div>
                            <div class="activity-item">
                                <div class="timeline-dot"></div>
                                <div class="activity-time">2026-10-25 12:49:46</div>
                                <div class="activity-title">Sistema · user_created</div>
                                <div class="activity-desc">Usuario creado: zz_dnd_admin</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
