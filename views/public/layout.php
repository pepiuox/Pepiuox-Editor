<?php
/**
 * Layout público para páginas del sitio.
 *
 * Variables esperadas (las entrega PublicController):
 *   $page    array  título, descripción y slug de la página
 *   $menu    array  árbol de menú para PageRenderer::renderMenuHtml()
 *   $content string HTML ya construido de la página
 *   $appName string Nombre de la aplicación (opcional)
 *   $authUser array|null Usuario en sesión (opcional)
 */

use App\Core\Auth;
use App\Services\PageRenderer;
use App\Services\SiteSettings;

// Metadatos: la página manda; si no trae, se usan los valores por defecto de
// Configuración → SEO (y, en última instancia, el nombre y la descripción del
// sitio). El título se compone aquí y se escapa una sola vez.
$pageTitleRaw    = (string) ($page['title'] ?? '');
$pageDescRaw     = (string) ($page['description'] ?? '');
$pageSlug        = (string) ($page['slug'] ?? '');
$pageTitle       = e(SiteSettings::titleFor($pageTitleRaw));
$pageDescription = SiteSettings::descriptionFor($pageDescRaw);
$siteLanguage    = SiteSettings::language();

// Nombre del sitio: llega del controlador (que lo lee de Configuración); si
// faltase, se resuelve aquí con la misma regla.
$appName = trim((string) ($appName ?? '')) !== '' ? trim((string) $appName) : SiteSettings::name();

// Marca en dos tonos: primera palabra neutra, resto en acento.
$brandParts = preg_split('/\s+/', $appName, 2) ?: [];
$brandFirst = $brandParts[0] ?? $appName;
$brandRest  = $brandParts[1] ?? '';

// Estado de sesión. Con sesión: acceso al panel. Sin sesión: ingreso y registro.
$isLoggedIn = Auth::check();
$authUser   = $authUser ?? ($isLoggedIn ? Auth::user() : null);

$userName = (string) ($authUser['username'] ?? $authUser['email'] ?? 'Mi cuenta');
$userRole = (string) ($authUser['role'] ?? \App\Core\Session::get('user_role') ?? 'Usuario');
$userInitial = function_exists('mb_substr') ? mb_substr($userName, 0, 1) : substr($userName, 0, 1);
$userInitial = strtoupper($userInitial !== '' ? $userInitial : 'U');
?>
<!DOCTYPE html>
<html lang="<?= e($siteLanguage) ?>" data-bs-theme="<?= \App\Services\PublicThemeService::isDark() ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php if ($pageDescription !== ''): ?>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?= e($appName) ?>">
    <?php if ($pageDescription !== ''): ?>
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <?php endif; ?>

    <?php
    // Favicon gestionado desde Configuración → Favicon. Sólo se emiten las
    // etiquetas de los archivos que existen de verdad (más el manifiesto).
    $faviconTags = \App\Services\PublicThemeService::faviconTags();
    ?>
    <?php if ($faviconTags !== ''): ?>
    <?= $faviconTags ?>
    <?php endif; ?>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos del sistema -->
    <style>
        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --bg-input: #0d1117;
            --border-color: #30363d;
            --border-soft: rgba(255, 255, 255, .07);
            --text-main: #e6edf3;
            --text-muted: #8b949e;
            --accent: #00d4aa;
            --accent-hover: #00a88a;
            --danger: #ff7b72;
            --radius: 12px;
            --radius-sm: 10px;
            --radius-pill: 999px;
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --menu-gap: .75rem;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, .35);
            --shadow-lg: 0 16px 40px rgba(0, 0, 0, .45);
        }

        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Tamaño del logotipo configurable. Vive también aquí, y no sólo en la
           hoja dinámica, para que un fallo de /public-theme.css no dibuje el
           logo a tamaño natural. */
        .brand-logo {
            max-height: 44px;
            width: auto;
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

        /* Foco visible y consistente en todo lo interactivo */
        :where(a, button, .btn):focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
            border-radius: var(--radius-sm);
        }

        /* ═══════════ Navbar ═══════════ */
        .navbar-main {
            background: rgba(22, 27, 34, .72);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid var(--border-soft);
            padding: .7rem 0;
            transition: background-color var(--transition), box-shadow var(--transition),
                        border-color var(--transition);
        }

        /* Sombra al desplazar: la separa del contenido sin cargarla en reposo */
        .navbar-main.is-scrolled {
            background: rgba(22, 27, 34, .93);
            border-bottom-color: var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            padding: 0;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -.015em;
            color: var(--text-main);
        }

        .navbar-brand:hover {
            color: var(--text-main);
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1.05rem;
            color: #04120f;
            background: linear-gradient(135deg, var(--accent), #0891b2);
            box-shadow: 0 6px 18px rgba(0, 212, 170, .28);
            transition: transform var(--transition), box-shadow var(--transition);
        }

        .navbar-brand:hover .brand-mark {
            transform: translateY(-1px) rotate(-4deg);
            box-shadow: 0 10px 24px rgba(0, 212, 170, .4);
        }

        .brand-accent {
            color: var(--accent);
        }

        .navbar-toggler {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: .4rem .6rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(0, 212, 170, .25);
        }

        /* ═══════════ Menú multinivel ═══════════ */
        .navbar .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            font-size: .95rem;
            padding: .5rem .85rem;
            border-radius: var(--radius-pill);
            transition: color var(--transition), background-color var(--transition);
        }

        .navbar .nav-link:hover,
        .navbar .nav-link:focus-visible {
            color: var(--text-main);
            background: rgba(255, 255, 255, .06);
        }

        .nav-link-parent,
        .dropdown-item-parent {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .35rem;
            width: 100%;
        }

        .nav-link-parent {
            border-radius: var(--radius-pill);
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
            color: inherit;
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
            box-shadow: var(--shadow-lg);
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

        /* ═══════════ Acciones de sesión ═══════════ */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: 1rem;
        }

        @media (min-width: 992px) {
            .nav-actions {
                margin-top: 0;
                margin-left: .75rem;
                padding-left: .75rem;
                border-left: 1px solid var(--border-soft);
            }
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.2;
            padding: .55rem 1rem;
            border-radius: var(--radius-pill);
            white-space: nowrap;
            transition: background-color var(--transition), border-color var(--transition),
                        color var(--transition), transform var(--transition), box-shadow var(--transition);
        }

        .nav-btn i {
            font-size: 1rem;
        }

        .btn-nav-ghost {
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-main);
        }

        .btn-nav-ghost:hover,
        .btn-nav-ghost:focus-visible {
            border-color: var(--accent);
            background: rgba(0, 212, 170, .1);
            color: var(--accent);
        }

        .btn-nav-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            padding: 0;
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-muted);
            transition: all var(--transition);
        }

        .btn-nav-icon:hover,
        .btn-nav-icon:focus-visible {
            border-color: rgba(255, 123, 114, .5);
            background: rgba(255, 123, 114, .1);
            color: var(--danger);
        }

        /* Identidad del usuario con sesión iniciada */
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            max-width: 220px;
            padding: .3rem .75rem .3rem .35rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-pill);
            background: rgba(255, 255, 255, .03);
            color: var(--text-main);
            text-decoration: none;
            transition: border-color var(--transition), background-color var(--transition);
        }

        .user-chip:hover {
            border-color: var(--accent);
            background: rgba(0, 212, 170, .08);
            color: var(--text-main);
        }

        .user-chip .avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            border-radius: 50%;
            font-size: .85rem;
            font-weight: 700;
            color: #04120f;
            background: linear-gradient(135deg, var(--accent), #0891b2);
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.15;
        }

        .user-name,
        .user-role {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-name {
            font-size: .85rem;
            font-weight: 600;
        }

        .user-role {
            font-size: .7rem;
            color: var(--text-muted);
        }

        /* ═══════════ Botones generales ═══════════ */
        .btn-accent {
            background: var(--accent);
            color: #04120f;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-pill);
            padding: .6rem 1.35rem;
            transition: all var(--transition);
        }

        .btn-accent:hover,
        .btn-accent:focus-visible {
            background: var(--accent-hover);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 212, 170, .3);
        }

        .btn-outline-accent {
            border: 1px solid var(--accent);
            color: var(--accent);
            border-radius: var(--radius-pill);
            padding: .6rem 1.35rem;
            font-weight: 500;
            transition: all var(--transition);
        }

        .btn-outline-accent:hover {
            background: rgba(0, 212, 170, .1);
            border-color: var(--accent-hover);
            color: var(--accent-hover);
        }

        /* ═══════════ Footer ═══════════ */
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

        /* Scrollbar */
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

        /* ═══════════ Responsive ═══════════ */
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
                margin-top: .75rem;
                box-shadow: var(--shadow-lg);
            }

            .navbar .nav-link {
                padding: .55rem .75rem;
            }

            /* Las acciones ocupan el ancho y se separan del menú */
            .nav-actions {
                flex-wrap: wrap;
                gap: .5rem;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--border-soft);
            }

            .nav-actions .nav-btn {
                flex: 1 1 auto;
            }

            .user-chip {
                flex: 1 1 100%;
                max-width: none;
            }
        }

        @media (max-width: 575.98px) {
            .navbar-brand {
                font-size: 1.05rem;
            }

            .brand-mark {
                width: 34px;
                height: 34px;
                font-size: .95rem;
            }

            .nav-btn {
                font-size: .85rem;
                padding: .5rem .85rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }

            .navbar-brand:hover .brand-mark {
                transform: none;
            }
        }
    </style>

    <?php
    // Hoja de estilos de la personalización pública (Configuración → Colores).
    // Va DESPUÉS del <style> base a propósito: así los tokens configurados
    // ganan tanto por orden como por especificidad (`body.public-site`).
    // El layout del panel no incluye esta hoja en ningún caso.
    ?>
    <link rel="stylesheet" href="<?= e(public_theme_css_url()) ?>">
</head>
<body class="public-site">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-main sticky-top" id="siteNav" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>" aria-label="<?= e($appName) ?> · Inicio">
                <?php
                // Logo del header servido con <picture>: el navegador elige la
                // variante por tamaño de pantalla (y por preferencia de color)
                // sin JavaScript ni descargas de más.
                $logoDesktop = public_logo('logo_desktop', 'logo_header');
                $logoTablet  = public_logo('logo_tablet');
                $logoMobile  = public_logo('logo_mobile');
                $logoDark    = public_logo('logo_dark');
                $logoLight   = public_logo('logo_light');
                $logoAlt     = public_logo_alt();
                ?>
                <?php if ($logoDesktop !== null): ?>
                <picture class="brand-picture">
                    <?php
                    // Orden deliberado: primero los tamaños y después la
                    // preferencia de color. `<picture>` se queda con la PRIMERA
                    // <source> que coincide, así que si las de esquema fueran
                    // antes, un móvil en modo oscuro recibiría siempre el logo
                    // de escritorio oscuro en vez de su variante de tamaño.
                    ?>
                    <?php if ($logoMobile !== null): ?>
                    <source media="(max-width: 575.98px)" srcset="<?= e($logoMobile) ?>">
                    <?php endif; ?>
                    <?php if ($logoTablet !== null): ?>
                    <source media="(max-width: 991.98px)" srcset="<?= e($logoTablet) ?>">
                    <?php endif; ?>
                    <?php if ($logoDark !== null): ?>
                    <source media="(prefers-color-scheme: dark)" srcset="<?= e($logoDark) ?>">
                    <?php endif; ?>
                    <?php if ($logoLight !== null): ?>
                    <source media="(prefers-color-scheme: light)" srcset="<?= e($logoLight) ?>">
                    <?php endif; ?>
                    <img class="brand-logo" src="<?= e($logoDesktop) ?>" alt="<?= e($logoAlt) ?>">
                </picture>
                <?php else: ?>
                <span class="brand-mark" aria-hidden="true"><i class="bi bi-layers-fill"></i></span>
                <span class="brand-word"><?= e($brandFirst) ?><?php if ($brandRest !== ''): ?><span class="brand-accent"> <?= e($brandRest) ?></span><?php endif; ?></span>
                <?php endif; ?>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false"
                    aria-label="Abrir o cerrar el menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <?= (new PageRenderer())->renderMenuHtml($menu ?? []) ?>

                <!-- Acciones según el estado de sesión -->
                <div class="nav-actions">
                    <?php if ($isLoggedIn): ?>
                        <a class="user-chip" href="<?= url('admin/profile') ?>"
                           title="Ir a mi perfil" aria-label="Mi perfil: <?= e($userName) ?>">
                            <span class="avatar" aria-hidden="true"><?= e($userInitial) ?></span>
                            <span class="user-meta">
                                <span class="user-name"><?= e($userName) ?></span>
                                <span class="user-role"><?= e($userRole) ?></span>
                            </span>
                        </a>
                        <a class="btn btn-accent nav-btn" href="<?= url('admin') ?>">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i><span>Panel Admin</span>
                        </a>
                        <a class="btn btn-nav-icon" href="<?= url('logout') ?>"
                           title="Cerrar sesión" aria-label="Cerrar sesión">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-nav-ghost nav-btn" href="<?= url('login') ?>">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>Iniciar sesión</span>
                        </a>
                        <a class="btn btn-accent nav-btn" href="<?= url('register') ?>">
                            <i class="bi bi-person-plus" aria-hidden="true"></i><span>Crear cuenta</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <?php $logoFooter = public_logo('logo_footer', 'logo_desktop', 'logo_header'); ?>
                    <h5 class="fw-bold mb-3">
                        <?php if ($logoFooter !== null): ?>
                            <img class="brand-logo brand-logo--footer" src="<?= e($logoFooter) ?>" alt="<?= e(public_logo_alt()) ?>">
                        <?php else: ?>
                            <i class="bi bi-layers-fill me-2" style="color: var(--accent);"></i>
                            <?= e($appName) ?>
                        <?php endif; ?>
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
                &copy; <?= date('Y') ?> <?= e($appName) ?>. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

            // Elevación de la navbar al desplazar
            const nav = document.getElementById('siteNav');
            if (nav) {
                const syncNavShadow = () => {
                    nav.classList.toggle('is-scrolled', window.scrollY > 8);
                };
                syncNavShadow();
                window.addEventListener('scroll', syncNavShadow, { passive: true });
            }
        });
    </script>
</body>
</html>
