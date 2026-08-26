<?php
/**
 * Página pública - Carga y renderiza páginas creadas en el CMS
 * 
 * Uso:
 * - /page.php (muestra página de inicio)
 * - /page.php?slug=mi-pagina (muestra página específica)
 * - /page.php?id=5 (muestra página por ID)
 * - /page/{slug} (a través del router)
 * 
 * @package Public
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. BOOTSTRAP DEL SISTEMA
// ═══════════════════════════════════════════════════════════════════
// Cargar el sistema
//require_once __DIR__ . '/../config/paths.php';

// Cargar constantes de rutas
$baseDir = __DIR__;
$configPath = $baseDir . '/../config';

if (!is_file($configPath . '/paths.php')) {
    die('Error: No se encontró el archivo de rutas en ' . $configPath . '/paths.php');
}

require_once $configPath . '/paths.php';

// Verificar que las constantes estén definidas
if (!defined('APP_PATH')) {
    die('Error: APP_PATH no está definido. Verifica config/paths.php');
}

// ═══════════════════════════════════════════════════════════════════
// 2. AUTOLOADER PSR-4
// ═══════════════════════════════════════════════════════════════════
spl_autoload_register(function (string $class) {
    // Solo procesar clases con namespace App\
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relativeClass = substr($class, 4);
    $file = APP_PATH . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (is_file($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// ═══════════════════════════════════════════════════════════════════
// 3. CARGAR HELPERS Y CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════
$helpersFile = APP_PATH . '/Helpers/functions.php';
if (is_file($helpersFile)) {
    require_once $helpersFile;
}

$configFile = CONFIG_PATH . '/config.php';
if (is_file($configFile)) {
    $cfg = require $configFile;
    date_default_timezone_set($cfg['timezone'] ?? 'UTC');
}

// ═══════════════════════════════════════════════════════════════════
// 4. INICIALIZAR SESIÓN
// ═══════════════════════════════════════════════════════════════════
try {
    App\Core\Session::start();
} catch (Throwable $e) {
    error_log('[page.php] Error al iniciar sesión: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════
// 5. IMPORTAR CLASES NECESARIAS
// ═══════════════════════════════════════════════════════════════════
use App\Services\PageRenderer;
use App\Core\Auth;
use App\Core\Response;
use App\Core\View;

// ═══════════════════════════════════════════════════════════════════
// 6. INICIALIZAR RENDERER
// ═══════════════════════════════════════════════════════════════════
try {
    $renderer = new PageRenderer();
} catch (Throwable $e) {
    error_log('[page.php] Error al inicializar PageRenderer: ' . $e->getMessage());
    Response::abort(500, 'Error al cargar el renderizador de páginas');
}

// ═══════════════════════════════════════════════════════════════════
// 7. DETERMINAR QUÉ PÁGINA CARGAR
// ═══════════════════════════════════════════════════════════════════
$page = null;
$slug = null;
$id = null;

// 1. Si se especifica un slug
if (!empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    if ($slug !== '') {
        $page = $renderer->getPageBySlug($slug);
    }
}

// 2. Si se especifica un ID
if (!$page && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $page = $renderer->getPageContent($id);
    }
}

// 3. Si no se especifica nada o los parámetros son inválidos, cargar página de inicio
if (!$page) {
    $page = $renderer->getHomePage();
}

// Si no hay página de inicio, mostrar 404
if (!$page) {
    Response::abort(404, 'Página no encontrada');
}

// ═══════════════════════════════════════════════════════════════════
// 8. CARGAR MENÚ
// ═══════════════════════════════════════════════════════════════════
try {
    $menu = $renderer->getMenu();
} catch (Throwable $e) {
    error_log('[page.php] Error al cargar menú: ' . $e->getMessage());
    $menu = [];
}

// ═══════════════════════════════════════════════════════════════════
// 9. PREPARAR DATOS DE LA PÁGINA
// ═══════════════════════════════════════════════════════════════════
$pageTitle = $renderer->getPageSeoTitle($page) ?: ($page['title'] ?? 'PePiuoX Editor');
$pageDescription = $renderer->getPageSeoDescription($page) ?: ($page['description'] ?? '');
$pageHtml = $page['content_html'] ?? '';
$pageCss = $page['content_css'] ?? '';
$pageJs = $page['content_js'] ?? '';

// Limpiar tags body si vienen del editor
$pageHtml = str_replace(['<body>', '</body>'], '', $pageHtml);

// Obtener usuario actual (si está logueado)
$currentUser = Auth::user();

// Obtener la URL actual para el menú activo
$currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = parse_url($currentUrl, PHP_URL_PATH) ?: '/';

// ═══════════════════════════════════════════════════════════════════
// 10. RENDERIZAR PÁGINA
// ═══════════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'es', ENT_QUOTES, 'UTF-8') ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="generator" content="PePiuoX Editor">
    <meta name="robots" content="<?= htmlspecialchars($page['robots'] ?? 'index, follow', ENT_QUOTES, 'UTF-8') ?>">
    
    <?php if (!empty($page['canonical'])): ?>
    <link rel="canonical" href="<?= htmlspecialchars($page['canonical'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            --accent-rgb: 0, 212, 170;
            --radius: 12px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --menu-gap: .75rem;
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: var(--font-family);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            padding: 0.75rem 0;
            min-height: 70px;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .navbar-brand .brand-accent {
            color: var(--accent);
        }

        .navbar-toggler {
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 0.5rem 0.75rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--accent-rgb), 0.25);
        }

        /* ═══════════════════════════════════════════════════════════
        MENÚ MULTINIVEL
        ═══════════════════════════════════════════════════════════ */
        .navbar .navbar-nav {
            gap: 0.25rem;
        }

        .navbar .nav-item {
            position: relative;
        }

        .navbar .nav-link {
            color: var(--text-muted);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            transition: all var(--transition);
            font-weight: 500;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link:focus {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        .navbar .nav-link.active {
            color: var(--accent);
            background: rgba(var(--accent-rgb), 0.1);
        }

        .nav-link-parent {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            width: 100%;
            cursor: pointer;
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
            background: rgba(var(--accent-rgb), .08);
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
            cursor: pointer;
            font-size: 0.875rem;
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
            min-width: 220px;
        }

        .navbar .dropdown-item {
            border-radius: .45rem;
            color: var(--text-muted);
            transition: background-color .2s ease, color .2s ease;
            padding: 0.5rem 0.75rem;
            font-weight: 400;
        }

        .navbar .dropdown-item:hover {
            background: rgba(var(--accent-rgb), .08);
            color: var(--accent);
        }

        .navbar .dropdown-item.active {
            color: var(--accent);
            background: rgba(var(--accent-rgb), .1);
        }

        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu > .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -0.25rem;
            margin-left: var(--menu-gap);
            transform: translateX(10px);
        }

        /* Desktop: hover */
        @media (min-width: 992px) {
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

            .navbar .dropdown-submenu > .dropdown-menu {
                left: 0;
                top: 100%;
                margin-left: 1rem;
            }

            .navbar-main .navbar-collapse {
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: var(--radius);
                padding: 1rem;
                margin-top: 0.75rem;
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
            padding: 0.5rem 1.25rem;
            transition: all var(--transition);
        }

        .btn-accent:hover {
            background: var(--accent-hover);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(var(--accent-rgb), 0.3);
        }

        .btn-outline-accent {
            border: 1px solid var(--accent);
            color: var(--accent);
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all var(--transition);
            background: transparent;
        }

        .btn-outline-accent:hover {
            background: rgba(var(--accent-rgb), 0.1);
            border-color: var(--accent-hover);
            color: var(--accent-hover);
        }

        /* ═══════════════════════════════════════════════════════════
        CONTENIDO PRINCIPAL
        ═══════════════════════════════════════════════════════════ */
        main {
            flex: 1;
            padding: 2rem 0;
        }

        /* ═══════════════════════════════════════════════════════════
        FOOTER
        ═══════════════════════════════════════════════════════════ */
        .footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 3rem 0 1.5rem;
            margin-top: auto;
        }

        .footer a {
            color: var(--text-muted);
        }

        .footer a:hover {
            color: var(--accent);
        }

        .footer .footer-heading {
            color: var(--text-main);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .footer .footer-link {
            display: block;
            padding: 0.25rem 0;
            font-size: 0.875rem;
        }

        /* ═══════════════════════════════════════════════════════════
        SCROLLBAR
        ═══════════════════════════════════════════════════════════ */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
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
        @media (max-width: 767.98px) {
            .navbar-brand {
                font-size: 1.25rem;
            }

            main {
                padding: 1rem 0;
            }

            .footer {
                padding: 2rem 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- ═══════════════════════════════════════════════════════════
    NAVBAR
    ═══════════════════════════════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg navbar-main sticky-top" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>">
                <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                PePiuoX<span class="brand-accent"> Maker</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#mainNav" aria-controls="mainNav" 
                    aria-expanded="false" aria-label="Alternar navegación">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <?= $renderer->renderMenuHtml($menu, $currentPath) ?>
                
                <!-- Acciones de usuario -->
                <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0">
                    <?php if ($currentUser): ?>
                        <a href="<?= url('admin') ?>" class="btn-outline-accent btn btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Panel
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
        <div class="container">
            <?= $pageHtml ?>
        </div>
    </main>

    <!-- ═══════════════════════════════════════════════════════════
    FOOTER
    ═══════════════════════════════════════════════════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                        PePiuoX Editor
                    </h5>
                    <p class="text-muted small">
                        Plataforma profesional de creación y gestión de contenido web.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x fs-5"></i></a>
                        <a href="#" aria-label="GitHub"><i class="bi bi-github fs-5"></i></a>
                        <a href="#" aria-label="YouTube"><i class="bi bi-youtube fs-5"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Navegación</h6>
                    <a href="<?= url('/') ?>" class="footer-link">Inicio</a>
                    <a href="<?= url('about') ?>" class="footer-link">Acerca de</a>
                    <a href="<?= url('services') ?>" class="footer-link">Servicios</a>
                    <a href="<?= url('blog') ?>" class="footer-link">Blog</a>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Legal</h6>
                    <a href="<?= url('privacy') ?>" class="footer-link">Privacidad</a>
                    <a href="<?= url('terms') ?>" class="footer-link">Términos</a>
                    <a href="<?= url('cookies') ?>" class="footer-link">Cookies</a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h6 class="footer-heading">Contacto</h6>
                    <p class="text-muted small mb-1">
                        <i class="bi bi-envelope me-2"></i>info@pepiuox.com
                    </p>
                    <p class="text-muted small">
                        <i class="bi bi-geo-alt me-2"></i>España
                    </p>
                </div>
            </div>
            <hr style="border-color: var(--border-color); margin: 2rem 0 1rem;">
            <p class="text-center small text-muted mb-0">
                &copy; <?= date('Y') ?> PePiuoX Editor. Todos los derechos reservados.
                <span class="d-block d-sm-inline">Versión <?= APP_VERSION ?? '1.0.0' ?></span>
            </p>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════
    SCRIPTS
    ═══════════════════════════════════════════════════════════ -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript de la página -->
    <?php if (!empty($pageJs)): ?>
    <script><?= $pageJs ?></script>
    <?php endif; ?>
    
    <!-- Script del menú -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                link.addEventListener('click', function() {
                    if (!isDesktop()) {
                        closeAllSubmenus();
                    }
                });
            });
            
            // Cerrar submenus cuando el navbar colapsa
            document.querySelectorAll('.navbar-collapse').forEach(collapse => {
                collapse.addEventListener('hidden.bs.collapse', function() {
                    closeAllSubmenus(this);
                });
            });
            
            // Limpiar estados móviles al cambiar tamaño
            let resizeTimer = null;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (isDesktop()) {
                        closeAllSubmenus();
                    }
                }, 150);
            });
        });
    </script>
</body>
</html>