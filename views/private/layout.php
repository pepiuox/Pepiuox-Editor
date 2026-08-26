<?php
/**
 * Layout público para páginas del sitio
 * 
 * Variables esperadas:
 * - $page: Array con datos de la página (title, description, slug, content_html, etc.)
 * - $menu: Array con la estructura del menú
 * - $content: String con el contenido HTML renderizado (opcional)
 * - $seo: Array con datos SEO (opcional)
 * - $meta: Array con meta tags adicionales (opcional)
 * 
 * @var array|null $page Datos de la página
 * @var array $menu Estructura del menú
 * @var string|null $content Contenido HTML pre-renderizado
 * @var array|null $seo Datos SEO de la página
 * @var array|null $meta Meta tags adicionales
 */

use App\Services\PageRenderer;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Core\Auth;

// ═══════════════════════════════════════════════════════════════
// 1. VALIDAR Y PREPARAR DATOS
// ═══════════════════════════════════════════════════════════════

// Asegurar que $page existe
if (!isset($page) || !is_array($page)) {
    $page = [];
}

// Asegurar que $menu existe
if (!isset($menu) || !is_array($menu)) {
    $menu = [];
}

// Obtener renderer con manejo de errores
try {
    $renderer = new PageRenderer();
} catch (Throwable $e) {
    $renderer = null;
    error_log('[Layout] Error al instanciar PageRenderer: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// 2. PREPARAR CONFIGURACIÓN DEL SITIO
// ═══════════════════════════════════════════════════════════════

try {
    $siteName = SettingService::get('general', 'site_name', 'PePiuoX Editor');
    $siteLocale = SettingService::get('general', 'site_locale', 'es_ES');
    $defaultOgImage = SettingService::get('seo', 'default_og_image', '');
    $gaTracking = SettingService::get('seo', 'ga_tracking', '');
    $gscVerification = SettingService::get('seo', 'gsc_verification', '');
} catch (Throwable $e) {
    $siteName = 'PePiuoX Editor';
    $siteLocale = 'es_ES';
    $defaultOgImage = '';
    $gaTracking = '';
    $gscVerification = '';
    error_log('[Layout] Error al obtener configuración: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// 3. PREPARAR DATOS PARA EL HEAD
// ═══════════════════════════════════════════════════════════════

// Título de la página
$pageTitle = $page['title'] ?? $siteName;
$pageTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');

// Descripción
$pageDescription = $page['description'] ?? '';
$pageDescription = htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8');

// Slug para canonical
$pageSlug = $page['slug'] ?? '';
$pageSlug = htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8');

// URL base para canonical
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$canonicalUrl = $baseUrl . '/' . ltrim($pageSlug, '/');
if (empty($pageSlug) || $pageSlug === 'home' || $pageSlug === 'index') {
    $canonicalUrl = $baseUrl . '/';
}

// ═══════════════════════════════════════════════════════════════
// 4. PREPARAR SEO ADICIONAL (integrado con SeoService)
// ═══════════════════════════════════════════════════════════════

// Datos SEO por defecto
$seoData = $seo ?? [];

// Priorizar SEO de la página sobre valores por defecto
$title = $seoData['basic']['title'] ?? $pageTitle;
$description = $seoData['basic']['description'] ?? $pageDescription;
$robots = $seoData['basic']['robots'] ?? 'index, follow';
$canonical = $seoData['basic']['canonical'] ?? $canonicalUrl;

// Open Graph
$ogTitle = $seoData['og']['title'] ?? $title;
$ogDescription = $seoData['og']['description'] ?? $description;
$ogImage = $seoData['og']['image'] ?? $defaultOgImage;
$ogType = $seoData['og']['type'] ?? 'website';
$ogUrl = $seoData['og']['url'] ?? $canonicalUrl;

// Twitter Cards
$twitterCard = $seoData['twitter']['card'] ?? 'summary_large_image';
$twitterTitle = $seoData['twitter']['title'] ?? $title;
$twitterDescription = $seoData['twitter']['description'] ?? $description;
$twitterImage = $seoData['twitter']['image'] ?? $ogImage;
$twitterSite = SettingService::get('seo', 'twitter_site', '');

// ═══════════════════════════════════════════════════════════════
// 5. PREPARAR EL MENÚ
// ═══════════════════════════════════════════════════════════════

// Renderizar menú HTML
try {
    if ($renderer) {
        $menuHtml = $renderer->renderMenuHtml($menu);
    } else {
        $menuHtml = '<ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center"></ul>';
    }
} catch (Throwable $e) {
    $menuHtml = '<ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center"></ul>';
    error_log('[Layout] Error al renderizar menú: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// 6. VERIFICAR AUTENTICACIÓN
// ═══════════════════════════════════════════════════════════════

try {
    $isLoggedIn = Auth::check();
    $user = Auth::user();
    $isAdmin = Auth::can('admin.access');
} catch (Throwable $e) {
    $isLoggedIn = false;
    $user = null;
    $isAdmin = false;
}

// ═══════════════════════════════════════════════════════════════
// 7. GENERAR JSON-LD (integrado con SeoService)
// ═══════════════════════════════════════════════════════════════

try {
    $jsonLd = SeoService::jsonLd($page, $seoData);
} catch (Throwable $e) {
    $jsonLd = '';
    error_log('[Layout] Error al generar JSON-LD: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// 8. INICIAR HTML
// ═══════════════════════════════════════════════════════════════

?><!DOCTYPE html>
<html lang="<?= $siteLocale ?>" data-bs-theme="dark">
<head>
    <!-- ═══ META BÁSICOS ═══ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="generator" content="PePiuoX Editor">
    
    <!-- ═══ TÍTULO Y DESCRIPCIÓN ═══ -->
    <title><?= $title ?></title>
    <?php if (!empty($description)): ?>
    <meta name="description" content="<?= $description ?>">
    <?php endif; ?>
    
    <!-- ═══ ROBOTS Y CANONICAL ═══ -->
    <meta name="robots" content="<?= $robots ?>">
    <?php if (!empty($canonical)): ?>
    <link rel="canonical" href="<?= $canonical ?>">
    <?php endif; ?>
    
    <!-- ═══ OPEN GRAPH ═══ -->
    <meta property="og:title" content="<?= $ogTitle ?>">
    <?php if (!empty($ogDescription)): ?>
    <meta property="og:description" content="<?= $ogDescription ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?= $ogType ?>">
    <meta property="og:url" content="<?= $ogUrl ?>">
    <meta property="og:site_name" content="<?= $siteName ?>">
    <meta property="og:locale" content="<?= $siteLocale ?>">
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    
    <!-- ═══ TWITTER CARDS ═══ -->
    <meta name="twitter:card" content="<?= $twitterCard ?>">
    <meta name="twitter:title" content="<?= $twitterTitle ?>">
    <?php if (!empty($twitterDescription)): ?>
    <meta name="twitter:description" content="<?= $twitterDescription ?>">
    <?php endif; ?>
    <?php if (!empty($twitterImage)): ?>
    <meta name="twitter:image" content="<?= $twitterImage ?>">
    <?php endif; ?>
    <?php if (!empty($twitterSite)): ?>
    <meta name="twitter:site" content="<?= $twitterSite ?>">
    <?php endif; ?>
    
    <!-- ═══ VERIFICACIÓN GOOGLE SEARCH CONSOLE ═══ -->
    <?php if (!empty($gscVerification)): ?>
    <meta name="google-site-verification" content="<?= $gscVerification ?>">
    <?php endif; ?>
    
    <!-- ═══ FAVICON ═══ -->
    <link rel="icon" type="image/svg+xml" href="<?= asset('favicon.svg') ?>">
    <link rel="shortcut icon" href="<?= asset('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>">
    
    <!-- ═══ META TAGS ADICIONALES ═══ -->
    <?php if (isset($meta) && is_array($meta)): ?>
        <?php foreach ($meta as $name => $value): ?>
            <?php if (is_string($value)): ?>
                <meta name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" 
                      content="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- ═══ JSON-LD SCHEMA.ORG ═══ -->
    <?= $jsonLd ?>
    
    <!-- ═══ BOOTSTRAP 5.3 ═══ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- ═══ GOOGLE FONTS ═══ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- ESTILOS DEL SISTEMA                                      -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <style>
        /* ============================================================
           1. VARIABLES CSS
           ============================================================ */
        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --bg-input: #0d1117;
            --border-color: #30363d;
            --text-main: #e6edf3;
            --text-muted: #8b949e;
            --text-muted-light: #6e7681;
            --accent: #00d4aa;
            --accent-hover: #00a88a;
            --accent-light: rgba(0, 212, 170, 0.15);
            --accent-dark: #008c72;
            --success: #2ea043;
            --warning: #d29922;
            --danger: #f85149;
            --info: #58a6ff;
            --radius: 12px;
            --radius-sm: 8px;
            --radius-lg: 16px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --menu-gap: 0.75rem;
            --navbar-height: 72px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.4);
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ============================================================
           2. RESET Y BASE
           ============================================================ */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: var(--font-family);
            font-size: 16px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        ::selection {
            background: var(--accent);
            color: var(--bg-main);
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 5px;
            border: 2px solid var(--bg-main);
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* ============================================================
           3. ENLACES
           ============================================================ */
        a {
            color: var(--accent);
            text-decoration: none;
            transition: color var(--transition);
        }
        a:hover {
            color: var(--accent-hover);
            text-decoration: none;
        }

        /* ============================================================
           4. NAVBAR
           ============================================================ */
        .navbar-main {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 0;
            min-height: var(--navbar-height);
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text-main) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-brand .brand-icon { color: var(--accent); font-size: 1.75rem; }
        .navbar-brand .brand-accent { color: var(--accent); }
        .navbar-brand:hover { color: var(--accent) !important; }

        /* ============================================================
           5. MENÚ MULTINIVEL
           ============================================================ */
        .navbar .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition);
            position: relative;
        }
        .navbar .nav-link:hover,
        .navbar .nav-link:focus {
            color: var(--text-main);
            background: var(--accent-light);
        }
        .navbar .nav-link.active {
            color: var(--accent);
            background: var(--accent-light);
        }

        .navbar .dropdown-menu {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.5rem;
            box-shadow: var(--shadow-lg);
            min-width: 220px;
        }
        .navbar .dropdown-item {
            color: var(--text-muted);
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: all var(--transition);
        }
        .navbar .dropdown-item:hover,
        .navbar .dropdown-item:focus {
            background: var(--accent-light);
            color: var(--accent);
        }

        .nav-link-parent,
        .dropdown-item-parent {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            width: 100%;
            border-radius: var(--radius-sm);
            transition: background var(--transition);
        }
        .nav-link-parent { padding: 0.5rem 1rem; }
        .dropdown-item-parent { padding: 0.5rem 0.75rem; }
        .nav-link-parent:hover,
        .dropdown-item-parent:hover { background: var(--accent-light); }

        .split-link {
            flex: 1 1 auto;
            min-width: 0;
            text-decoration: none;
            color: inherit;
            transition: color var(--transition);
        }
        .nav-link-parent .split-link { color: var(--text-muted); }
        .nav-link-parent:hover .split-link { color: var(--text-main); }
        .dropdown-item-parent .split-link { color: var(--text-muted); }
        .dropdown-item-parent:hover .split-link { color: var(--accent); }

        .menu-caret {
            border: 0;
            background: transparent;
            color: inherit;
            opacity: 0.6;
            padding: 0.25rem 0.4rem;
            border-radius: var(--radius-sm);
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition);
            cursor: pointer;
        }
        .menu-caret:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.08);
            color: var(--accent);
        }
        .menu-caret i {
            font-size: 0.65rem;
            transition: transform 0.2s ease;
            pointer-events: none;
        }

        /* ============================================================
           6. MENÚ: ESCRITORIO
           ============================================================ */
        @media (min-width: 992px) {
            .navbar .dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translateY(8px);
                transition: all 0.2s ease;
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
            .navbar .dropdown-submenu { position: relative; }
            .navbar .dropdown-submenu > .dropdown-menu {
                top: -0.5rem;
                left: 100%;
                margin-left: var(--menu-gap);
                transform: translateX(8px);
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

        /* ============================================================
           7. MENÚ: MÓVIL
           ============================================================ */
        @media (max-width: 991.98px) {
            .navbar .dropdown-menu {
                display: none;
                position: static;
                background: transparent;
                border: 0;
                box-shadow: none;
                margin: 0.25rem 0 0.5rem 1rem;
                padding-left: 0.75rem;
                border-left: 2px solid var(--accent);
                border-radius: 0;
                width: 100%;
            }
            .navbar .dropdown-menu.show { display: block; }
            .navbar .dropdown-submenu > .dropdown-menu { margin-left: 0.5rem; }
            .navbar .navbar-collapse {
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: var(--radius);
                padding: 1rem;
                margin-top: 0.75rem;
                max-height: 70vh;
                overflow-y: auto;
            }
            .navbar .nav-link-parent { padding: 0.5rem 0.25rem; }
            .navbar .dropdown-item-parent { padding: 0.375rem 0.25rem; }
        }

        /* ============================================================
           8. BOTONES
           ============================================================ */
        .btn-accent {
            background: var(--accent);
            color: var(--bg-main) !important;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.6rem 1.5rem;
            transition: all var(--transition);
        }
        .btn-accent:hover {
            background: var(--accent-hover);
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 212, 170, 0.3);
        }
        .btn-accent:active { transform: translateY(0); }

        .btn-outline-accent {
            border: 1px solid var(--accent);
            color: var(--accent) !important;
            background: transparent;
            border-radius: var(--radius-sm);
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all var(--transition);
        }
        .btn-outline-accent:hover {
            background: var(--accent-light);
            border-color: var(--accent-hover);
            color: var(--accent-hover) !important;
        }
        .btn-sm { padding: 0.4rem 1rem; font-size: 0.875rem; }

        /* ============================================================
           9. CONTENIDO PRINCIPAL
           ============================================================ */
        main { flex: 1 0 auto; }
        .page-content { min-height: 60vh; padding: 2rem 0; }
        .page-body { animation: fadeIn 0.4s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           10. FOOTER
           ============================================================ */
        .footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
            flex-shrink: 0;
        }
        .footer a {
            color: var(--text-muted);
            transition: color var(--transition);
        }
        .footer a:hover { color: var(--accent); }
        .footer .footer-heading {
            color: var(--text-main);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .footer hr {
            border-color: var(--border-color);
            margin: 1.5rem 0;
        }

        /* ============================================================
           11. UTILIDADES
           ============================================================ */
        .text-accent { color: var(--accent); }
        .bg-accent-light { background: var(--accent-light); }
        .border-accent { border-color: var(--accent) !important; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        .shadow-hover { transition: box-shadow var(--transition); }
        .shadow-hover:hover { box-shadow: var(--shadow-md); }

        /* ============================================================
           12. RESPONSIVE
           ============================================================ */
        @media (max-width: 768px) {
            .navbar-brand { font-size: 1.25rem; }
            .page-content { padding: 1rem 0; }
            .footer { padding: 2rem 0 1rem; }
            .btn-accent, .btn-outline-accent { padding: 0.5rem 1rem; font-size: 0.875rem; }
        }
        @media (max-width: 576px) {
            .navbar-main { padding: 0.5rem 0; }
            .navbar-brand { font-size: 1.1rem; }
            .navbar-brand .brand-icon { font-size: 1.25rem; }
            .page-content { padding: 0.5rem 0; }
        }

        /* ============================================================
           13. ACCESIBILIDAD
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
    </style>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- ESTILOS DE LA PÁGINA (content_css de la página)        -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <?php if (!empty($page['content_css'])): ?>
    <style><?= $page['content_css'] ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- NAVBAR                                                  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg navbar-main" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>" aria-label="Inicio">
                <i class="bi bi-layers-fill brand-icon"></i>
                <span>PePiuoX<span class="brand-accent"> Maker</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#mainNav" 
                    aria-controls="mainNav" 
                    aria-expanded="false" 
                    aria-label="Alternar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <!-- Menú principal -->
                <?= $menuHtml ?>
                
                <!-- Acciones de usuario -->
                <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0">
                    <?php if ($isLoggedIn && $user): ?>
                        <?php if ($isAdmin): ?>
                        <a href="<?= url('admin') ?>" class="btn-outline-accent btn btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i>
                            <span class="d-none d-sm-inline">Panel Admin</span>
                            <span class="d-inline d-sm-none">Admin</span>
                        </a>
                        <?php endif; ?>
                        <a href="<?= url('logout') ?>" class="btn-accent btn btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            <span class="d-none d-sm-inline">Salir</span>
                            <span class="d-inline d-sm-none">Salir</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" class="btn-outline-accent btn btn-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            <span class="d-none d-sm-inline">Ingresar</span>
                            <span class="d-inline d-sm-none">Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- CONTENIDO PRINCIPAL                                     -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <main>
        <?php if (isset($content) && !empty($content)): ?>
            <!-- Si se proporciona contenido pre-renderizado -->
            <?= $content ?>
        <?php elseif (!empty($page)): ?>
            <!-- Si se proporciona una página, usar el renderer -->
            <?php 
            try {
                if ($renderer) {
                    echo $renderer->buildPageHtml($page);
                } else {
                    // Fallback básico
                    echo '<div class="container page-content"><div class="page-body">';
                    echo $page['content_html'] ?? '<p class="text-muted text-center py-5">Contenido no disponible</p>';
                    echo '</div></div>';
                }
            } catch (Throwable $e) {
                echo '<div class="container page-content"><div class="page-body">';
                echo '<div class="alert alert-danger">';
                echo '<p class="mb-0">Error al cargar el contenido de la página.</p>';
                if (defined('DEBUG') && DEBUG) {
                    echo '<pre class="mt-2 small">' . htmlspecialchars($e->getMessage()) . '</pre>';
                }
                echo '</div></div></div>';
                error_log('[Layout] Error al renderizar página: ' . $e->getMessage());
            }
            ?>
        <?php else: ?>
            <!-- No hay contenido ni página -->
            <div class="container page-content">
                <div class="page-body text-center py-5">
                    <h1 class="display-4 mb-3">Bienvenido a PePiuoX Editor</h1>
                    <p class="lead text-muted">Comienza a crear tu contenido.</p>
                    <?php if ($isLoggedIn && $isAdmin): ?>
                    <a href="<?= url('admin/pages/create') ?>" class="btn-accent btn mt-3">
                        <i class="bi bi-plus-circle me-2"></i>Crear primera página
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- FOOTER                                                  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="footer-heading">
                        <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                        PePiuoX Editor
                    </h5>
                    <p class="text-muted small">
                        Plataforma de creación y gestión de contenido profesional.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x fs-5"></i></a>
                        <a href="#" aria-label="GitHub"><i class="bi bi-github fs-5"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3 text-muted">Navegación</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= url('/') ?>">Inicio</a></li>
                        <li><a href="<?= url('about') ?>">Acerca de</a></li>
                        <li><a href="<?= url('contact') ?>">Contacto</a></li>
                        <?php if ($isLoggedIn && $isAdmin): ?>
                        <li><a href="<?= url('admin') ?>">Panel Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3 text-muted">Legal</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= url('privacy') ?>">Política de Privacidad</a></li>
                        <li><a href="<?= url('terms') ?>">Términos de Uso</a></li>
                        <li><a href="<?= url('cookies') ?>">Política de Cookies</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-uppercase small fw-bold mb-3 text-muted">Contacto</h6>
                    <p class="small text-muted">
                        <i class="bi bi-envelope me-2"></i>info@pepiuox.com
                    </p>
                    <p class="small text-muted">
                        <i class="bi bi-github me-2"></i>
                        <a href="https://github.com/pepiuox" target="_blank" rel="noopener noreferrer">
                            github.com/pepiuox
                        </a>
                    </p>
                    <div class="mt-3">
                        <a href="<?= url('sitemap.xml') ?>" class="text-muted small">
                            <i class="bi bi-sitemap me-1"></i>Mapa del sitio
                        </a>
                    </div>
                </div>
            </div>
            <hr>
            <p class="text-center small text-muted mb-0">
                &copy; <?= date('Y') ?> PePiuoX Editor. Todos los derechos reservados.
                <span class="d-block d-sm-inline mt-1 mt-sm-0">
                    <i class="bi bi-code-slash me-1"></i>Hecho con <i class="bi bi-heart-fill text-accent"></i>
                </span>
            </p>
        </div>
    </footer>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- SCRIPTS                                                 -->
    <!-- ══════════════════════════════════════════════════════════ -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- SCRIPTS DEL MENÚ                                        -->
    <!-- ══════════════════════════════════════════════════════════ -->
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

            // Manejar clics en las flechas
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

            // Cerrar menús al hacer clic fuera (móvil)
            document.addEventListener('click', function(event) {
                if (isDesktop()) return;
                if (!event.target.closest('li.has-submenu')) {
                    closeAllSubmenus();
                }
            });

            // Cerrar menús al hacer clic en enlaces
            document.querySelectorAll('.split-link, a.dropdown-item').forEach(link => {
                link.addEventListener('click', function() {
                    if (!isDesktop()) {
                        const menu = this.closest('.dropdown-menu');
                        if (menu) {
                            const parentLi = menu.closest('li.has-submenu');
                            if (parentLi) {
                                closeSiblings(parentLi);
                                menu.classList.remove('show');
                                const caret = parentLi.querySelector('.menu-caret');
                                if (caret) caret.setAttribute('aria-expanded', 'false');
                            }
                        }
                    }
                });
            });

            // Cerrar menús cuando el navbar colapsa
            document.querySelectorAll('.navbar-collapse').forEach(collapse => {
                collapse.addEventListener('hidden.bs.collapse', function() {
                    closeAllSubmenus(this);
                });
            });

            // Limpiar estados al cambiar tamaño
            let resizeTimer = null;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (isDesktop()) {
                        closeAllSubmenus();
                    }
                }, 150);
            });

            // Inicializar tooltips y popovers
            if (typeof bootstrap !== 'undefined') {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                    new bootstrap.Tooltip(el);
                });
                document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
                    new bootstrap.Popover(el);
                });
            }
        });
    </script>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- SCRIPTS DE LA PÁGINA (content_js de la página)         -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <?php if (!empty($page['content_js'])): ?>
    <script><?= $page['content_js'] ?></script>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- GOOGLE ANALYTICS                                        -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <?php if (!empty($gaTracking)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaTracking, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($gaTracking, ENT_QUOTES, 'UTF-8') ?>');
    </script>
    <?php endif; ?>
</body>
</html>