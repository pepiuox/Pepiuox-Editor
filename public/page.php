<?php
/**
 * Página pública - Carga y renderiza páginas creadas en el CMS
 * 
 * Uso:
 * - /public/page.php (muestra página de inicio)
 * - /public/page.php?slug=mi-pagina (muestra página específica)
 * - /public/page.php?id=5 (muestra página por ID)
 */

declare(strict_types=1);

// Cargar el sistema
require_once __DIR__ . '/../config/paths.php';

// Autoloader
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) require $path;
    }
});

// Cargar helpers
require APP_PATH . '/Helpers/functions.php';

// Iniciar sesión
App\Core\Session::start();

use App\Services\PageRenderer;
use App\Core\Auth;

// ═══════════════════════════════════════════════════════════════════
// INICIALIZAR RENDERER
// ═══════════════════════════════════════════════════════════════════
$renderer = new PageRenderer();

// ═══════════════════════════════════════════════════════════════════
// DETERMINAR QUÉ PÁGINA CARGAR
// ═══════════════════════════════════════════════════════════════════
$page = null;

// 1. Si se especifica un slug
if (!empty($_GET['slug'])) {
    $slug = $renderer->sanitizeSlug($_GET['slug']);
    $page = $renderer->getPageBySlug($slug);
}

// 2. Si se especifica un ID
elseif (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $page = $renderer->getPageContent($id);
}

// 3. Si no se especifica nada, cargar página de inicio
else {
    $page = $renderer->getHomePage();
}

// ═══════════════════════════════════════════════════════════════════
// VERIFICAR SI LA PÁGINA EXISTE
// ═══════════════════════════════════════════════════════════════════
if (!$page) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Página no encontrada · PePiuoX Editor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body {
                background: #0d1117;
                color: #e6edf3;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Inter', sans-serif;
            }
            .error-container {
                text-align: center;
                padding: 3rem;
            }
            .error-code {
                font-size: 8rem;
                font-weight: 700;
                color: #00d4aa;
                line-height: 1;
                margin-bottom: 1rem;
            }
            .error-message {
                font-size: 1.5rem;
                color: #8b949e;
                margin-bottom: 2rem;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">Página no encontrada</div>
            <a href="<?= url('/') ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-house"></i> Volver al inicio
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// CARGAR MENÚ
// ═══════════════════════════════════════════════════════════════════
$menu = $renderer->getMenu();

// ═══════════════════════════════════════════════════════════════════
// PREPARAR DATOS
// ═══════════════════════════════════════════════════════════════════
$pageTitle = $renderer->getPageSeoTitle($page);
$pageDescription = $renderer->getPageSeoDescription($page);
$pageHtml = $page['content_html'] ?? '';
$pageCss = $page['content_css'] ?? '';
$pageJs = $page['content_js'] ?? '';

// Limpiar tags body si vienen del editor
$pageHtml = str_replace(['<body>', '</body>'], '', $pageHtml);

// Usuario actual (si está logueado)
$currentUser = Auth::user();

// ═══════════════════════════════════════════════════════════════════
// RENDERIZAR PÁGINA
// ═══════════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="generator" content="PePiuoX Editor">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CSS de la página -->
    <?php if (!empty($pageCss)): ?>
    <style><?= $pageCss ?></style>
    <?php endif; ?>
    
    <!-- Estilos del sistema -->
    <style>
        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --bg-input: #0d1117;
            --border-color: #30363d;
            --text-main: #e6edf3;
            --text-muted: #8b949e;
            --accent: #00d4aa;
            --accent-hover: #00a88a;
            --radius: 12px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --menu-gap: .75rem;
        }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }

        ::selection {
            background: var(--accent);
            color: var(--bg-main);
        }

        a {
            color: var(--accent);
            text-decoration: none;
            transition: color var(--transition);
        }

        a:hover {
            color: var(--accent-hover);
        }

        /* ═══════════════════════════════════════════════════════════
        NAVBAR
        ═══════════════════════════════════════════════════════════ */
        .navbar-main {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        .navbar-brand .brand-accent {
            color: var(--accent);
        }

        .navbar-toggler {
            border: none;
            color: var(--text-main);
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        /* ═══════════════════════════════════════════════════════════
        MENÚ MULTINIVEL
        ═══════════════════════════════════════════════════════════ */
        .nav-link-parent,
        .dropdown-item-parent {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .35rem;
            width: 100%;
        }

        .nav-link-parent {
            border-radius: .5rem;
        }

        .dropdown-item-parent {
            border-radius: .5rem;
            padding-right: .55rem;
        }

        .split-link {
            flex: 1 1 auto;
            min-width: 0;
            text-decoration: none;
            color: inherit;
            transition: color .2s ease;
        }

        .navbar .nav-link-parent .split-link {
            color: var(--text-muted);
        }

        .navbar .nav-link-parent:hover .split-link {
            color: var(--text-main);
        }

        .navbar .nav-link-parent .split-link:hover {
            color: var(--accent);
        }

        .dropdown-item-parent .split-link {
            color: var(--text-muted);
        }

        .dropdown-item-parent:hover {
            background: rgba(0, 212, 170, .08);
        }

        .dropdown-item-parent:hover .split-link {
            color: var(--accent);
        }

        .menu-caret {
            border: 0;
            background: transparent;
            color: inherit;
            opacity: .75;
            padding: .25rem .4rem;
            border-radius: .45rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            transition: opacity .2s ease, background-color .2s ease, color .2s ease;
        }

        .menu-caret:hover {
            opacity: 1;
            background: rgba(255, 255, 255, .08);
            color: var(--accent);
        }

        .menu-caret i {
            font-size: .75rem;
            transition: transform .18s ease;
            pointer-events: none;
        }

        .navbar .dropdown-menu {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: .85rem;
            box-shadow: 0 16px 40px rgba(0, 0, 0, .35);
            padding: .45rem;
            z-index: 1050;
        }

        .navbar .dropdown-item {
            border-radius: .45rem;
            color: var(--text-muted);
            transition: background-color .2s ease, color .2s ease;
        }

        .navbar .dropdown-item:hover {
            background: rgba(0, 212, 170, .08);
            color: var(--accent);
        }

        /* Desktop: hover */
        @media (min-width: 992px) {
            .navbar .nav-item.dropdown {
                position: relative;
            }

            .navbar .dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translateY(10px);
                transition: opacity .18s ease, transform .18s ease, visibility .18s;
                margin-top: var(--menu-gap);
            }

            .navbar .nav-item.dropdown:hover > .dropdown-menu,
            .navbar .nav-item.dropdown:focus-within > .dropdown-menu,
            .navbar .dropdown-submenu:hover > .dropdown-menu,
            .navbar .dropdown-submenu:focus-within > .dropdown-menu {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translateY(0);
            }

            .navbar .nav-item.dropdown > .dropdown-menu::before {
                content: "";
                position: absolute;
                top: calc(-1 * var(--menu-gap));
                left: 0;
                right: 0;
                height: var(--menu-gap);
            }

            .navbar .dropdown-submenu {
                position: relative;
            }

            .navbar .dropdown-submenu > .dropdown-menu {
                top: 0;
                left: 100%;
                margin-top: -0.25rem;
                margin-left: var(--menu-gap);
                transform: translateX(10px);
            }

            .navbar .dropdown-submenu:hover > .dropdown-menu,
            .navbar .dropdown-submenu:focus-within > .dropdown-menu {
                transform: translateX(0);
            }

            .navbar .dropdown-submenu > .dropdown-menu::before {
                content: "";
                position: absolute;
                top: 0;
                bottom: 0;
                left: calc(-1 * var(--menu-gap));
                width: var(--menu-gap);
            }

            .navbar .nav-item.dropdown:hover .dropdown-caret i {
                transform: rotate(180deg);
            }

            .navbar .dropdown-submenu:hover .submenu-caret i {
                transform: translateX(2px);
            }
        }

        /* Móvil: click */
        @media (max-width: 991.98px) {
            .navbar .dropdown-menu {
                display: none;
                position: static;
                background: transparent;
                border: 0;
                box-shadow: none;
                margin: .35rem 0 .65rem 1rem;
                padding-left: .65rem;
                border-left: 2px solid var(--accent);
                border-radius: 0;
            }

            .navbar .dropdown-menu.show {
                display: block;
                opacity: 1;
                visibility: visible;
                transform: none;
                pointer-events: auto;
            }
        }

        /* ═══════════════════════════════════════════════════════════
        BOTONES
        ═══════════════════════════════════════════════════════════ */
        .btn-accent {
            background: var(--accent);
            color: var(--bg-main);
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            transition: all var(--transition);
        }

        .btn-accent:hover {
            background: var(--accent-hover);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0, 212, 170, 0.3);
        }

        .btn-outline-accent {
            border: 1px solid var(--accent);
            color: var(--accent);
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all var(--transition);
        }

        .btn-outline-accent:hover {
            background: rgba(0, 212, 170, 0.1);
            border-color: var(--accent-hover);
            color: var(--accent-hover);
        }

        /* ═══════════════════════════════════════════════════════════
        FOOTER
        ═══════════════════════════════════════════════════════════ */
        .footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }

        .footer a {
            color: var(--text-muted);
        }

        .footer a:hover {
            color: var(--accent);
        }

        /* ═══════════════════════════════════════════════════════════
        SCROLLBAR
        ═══════════════════════════════════════════════════════════ */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        /* ═══════════════════════════════════════════════════════════
        RESPONSIVE
        ═══════════════════════════════════════════════════════════ */
        @media (max-width: 991.98px) {
            .dropdown-submenu > .dropdown-menu {
                left: 0;
                top: 100%;
                margin-left: 1rem;
                border-left: 2px solid var(--accent);
            }

            .navbar-main .navbar-collapse {
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: var(--radius);
                padding: 1rem;
                margin-top: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- ═══════════════════════════════════════════════════════════
    NAVBAR
    ═══════════════════════════════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg navbar-main sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>">
                <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                PePiuoX<span class="brand-accent"> Maker</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <?= $renderer->renderMenuHtml($menu) ?>
                
                <!-- Acciones de usuario -->
                <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0">
                    <?php if ($currentUser): ?>
                        <a href="<?= url('admin') ?>" class="btn-outline-accent btn btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Panel Admin
                        </a>
                        <a href="<?= url('logout') ?>" class="btn-accent btn btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i> Salir
                        </a>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" class="btn-outline-accent btn btn-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                        </a>
                        <a href="<?= url('register') ?>" class="btn-accent btn btn-sm">
                            <i class="bi bi-person-plus me-1"></i> Registrarse
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════════════════════
    CONTENIDO PRINCIPAL
    ═══════════════════════════════════════════════════════════ -->
    <main>
        <?= $pageHtml ?>
    </main>

    <!-- ═══════════════════════════════════════════════════════════
    FOOTER
    ═══════════════════════════════════════════════════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                        PePiuoX Editor
                    </h5>
                    <p class="text-muted small">
                        Plataforma de creación y gestión de contenido profesional.
                    </p>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color: var(--text-muted);">Navegación</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= url('/') ?>">Inicio</a></li>
                        <li><a href="<?= url('about') ?>">Acerca de</a></li>
                        <li><a href="<?= url('services') ?>">Servicios</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color: var(--text-muted);">Legal</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= url('privacy') ?>">Privacidad</a></li>
                        <li><a href="<?= url('terms') ?>">Términos</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color: var(--text-muted);">Contacto</h6>
                    <p class="small text-muted">info@pepiuox.com</p>
                </div>
            </div>
            <hr style="border-color: var(--border-color); margin: 2rem 0 1rem;">
            <p class="text-center small text-muted mb-0">
                &copy; <?= date('Y') ?> PePiuoX Editor. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript de la página -->
    <?php if (!empty($pageJs)): ?>
    <script><?= $pageJs ?></script>
    <?php endif; ?>
    
    <!-- Script del menú -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            'use strict';
            
            const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;
            
            const closeAllSubmenus = (root = document) => {
                root.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                root.querySelectorAll('.menu-caret').forEach(button => {
                    button.setAttribute('aria-expanded', 'false');
                });
            };
            
            const closeSiblings = (currentLi) => {
                const parent = currentLi.parentElement;
                if (!parent) return;
                
                Array.from(parent.children).forEach(sibling => {
                    if (sibling === currentLi) return;
                    
                    sibling.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    sibling.querySelectorAll('.menu-caret').forEach(button => {
                        button.setAttribute('aria-expanded', 'false');
                    });
                });
            };
            
            // Flechas para abrir/cerrar submenus
            document.querySelectorAll('.menu-caret').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    if (isDesktop()) return;
                    
                    const li = this.closest('li.has-submenu');
                    if (!li) return;
                    
                    const menu = li.querySelector(':scope > .dropdown-menu');
                    if (!menu) return;
                    
                    const wasOpen = menu.classList.contains('show');
                    closeSiblings(li);
                    menu.classList.toggle('show', !wasOpen);
                    this.setAttribute('aria-expanded', String(!wasOpen));
                });
            });
            
            // Cerrar menús al hacer click fuera
            document.addEventListener('click', function(event) {
                if (isDesktop()) return;
                
                if (!event.target.closest('li.has-submenu')) {
                    closeAllSubmenus();
                }
            });
            
            // Cerrar menús al hacer click en un enlace
            document.querySelectorAll('.split-link, a.dropdown-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (!isDesktop()) {
                        closeAllSubmenus();
                    }
                });
            });
            
            // Cerrar submenus cuando el navbar colapsa
            document.querySelectorAll('.navbar-collapse').forEach(collapse => {
                collapse.addEventListener('hidden.bs.collapse', () => {
                    closeAllSubmenus(collapse);
                });
            });
            
            // Limpiar estados móviles al cambiar tamaño
            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (isDesktop()) {
                        closeAllSubmenus();
                    }
                }, 150);
            });
        });
    </script>
</body>
</html>