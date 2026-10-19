<?php
/**
 * Vista: Editor de Templates
 * GrapesJS + CodeMirror con sistema de versiones
 */
$pageTitle = $template ? 'Editar template' : 'Nuevo template';
$t = $template ?? [
    'id' => null, 'name' => '', 'description' => '',
    'content_html' => '', 'content_css' => '', 'content_js' => '',
    'content_php' => '', 'grapesjs_data' => null,
    'is_active' => 1, 'is_default' => 0, 'is_system_template' => 0,
];

// ═══════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════
if (!function_exists('_can')) {
    function _can(string $permission): bool
    {
        if (class_exists('\App\Core\Auth') && method_exists('\App\Core\Auth', 'can')) {
            return \App\Core\Auth::can($permission);
        }
        return false;
    }
}

if (!function_exists('_tplCsrfToken')) {
    function _tplCsrfToken(): string
    {
        if (class_exists('\App\Core\CSRF') && method_exists('\App\Core\CSRF', 'token')) {
            return (string)\App\Core\CSRF::token();
        }
        return htmlspecialchars((string)($_SESSION['_csrf_token'] ?? ($_SESSION['csrf_token'] ?? '')));
    }
}

$canEditPhp = _can('templates.edit_php');

// ═══════════════════════════════════════════════════════════════════
// PARSEO SEGURO DE grapesjs_data
// ═══════════════════════════════════════════════════════════════════
$initialHtml = $t['content_html'] ?? '';
$initialCss = $t['content_css'] ?? '';
$grapesjsData = $t['grapesjs_data'] ?? '';

if (!empty($t['grapesjs_data'])) {
    $decoded = json_decode((string)$t['grapesjs_data'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $grapesjsData = $decoded;
        // Extraer componentes y estilos del proyecto GrapesJS
        if (isset($decoded['pages']) && is_array($decoded['pages']) && isset($decoded['pages'][0])) {
            $page = $decoded['pages'][0];
            if (!empty($page['component'])) {
                $initialHtml = $page['component'];
            }
            if (!empty($page['styles'])) {
                $initialCss = $page['styles'];
            }
        } elseif (isset($decoded['components']) && !empty($decoded['components'])) {
            $initialHtml = $decoded['components'];
            if (!empty($decoded['styles'])) {
                $initialCss = $decoded['styles'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- GrapesJS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">
    
    <!-- CodeMirror -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        /* Misma paleta y componentes que el builder de páginas */
        :root {
            --tpl-bg: #0d1117;
            --tpl-bg-2: #161b22;
            --tpl-bg-3: #1c2333;
            --tpl-border: #30363d;
            --tpl-border-soft: rgba(255, 255, 255, .07);
            --tpl-text: #e6edf3;
            --tpl-muted: #8b949e;
            --tpl-accent: #00d4aa;
            --tpl-accent-dark: #00a88a;
            --tpl-danger: #ef4444;
            --tpl-radius: 8px;
            --tpl-topbar-h: 56px;
            --tpl-t: .18s ease;
            --tpl-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; }

        body.tpl-body {
            margin: 0;
            background: var(--tpl-bg);
            color: var(--tpl-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            min-height: 100vh;
        }

        a { color: var(--tpl-accent); text-decoration: none; }
        a:hover { color: var(--tpl-accent-dark); }

        :where(a, button, .btn-tpl, input, textarea):focus-visible {
            outline: 2px solid var(--tpl-accent);
            outline-offset: 2px;
            border-radius: var(--tpl-radius);
        }

        /* ═══ Barra superior ═══ */
        .tpl-topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            min-height: var(--tpl-topbar-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .5rem 1rem;
            background: rgba(22, 27, 34, .92);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid var(--tpl-border);
        }

        .tpl-topbar-left,
        .tpl-topbar-right {
            display: flex;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }

        .tpl-logo {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            border-radius: var(--tpl-radius);
            color: #04120f;
            background: linear-gradient(135deg, var(--tpl-accent), var(--tpl-accent-dark));
            transition: transform var(--tpl-t);
        }

        .tpl-logo:hover { transform: scale(1.05); color: #04120f; }

        .tpl-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            min-width: 0;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
        }

        .tpl-title i { color: var(--tpl-accent); }

        .tpl-title > span:nth-child(2) {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tpl-badge {
            font-size: .72rem;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--tpl-bg-3);
            border: 1px solid var(--tpl-border);
            color: var(--tpl-muted);
            font-weight: 500;
        }

        /* ═══ Botones ═══ */
        .btn-tpl {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .8rem;
            border: 1px solid transparent;
            border-radius: var(--tpl-radius);
            font: inherit;
            font-size: .82rem;
            font-weight: 500;
            color: var(--tpl-text);
            background: transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color var(--tpl-t), border-color var(--tpl-t), color var(--tpl-t);
        }

        .btn-tpl.btn-ghost {
            color: var(--tpl-muted);
            border-color: var(--tpl-border);
            background: transparent;
        }

        .btn-tpl.btn-ghost:hover {
            color: var(--tpl-text);
            background: rgba(255, 255, 255, .06);
        }

        .btn-tpl.btn-primary,
        .btn-tpl.btn-info {
            background: var(--tpl-accent);
            color: #04120f;
            font-weight: 600;
            border-color: transparent;
        }

        .btn-tpl.btn-primary:hover,
        .btn-tpl.btn-info:hover {
            background: var(--tpl-accent-dark);
            color: #fff;
        }

        .btn-tpl.btn-outline {
            border-color: var(--tpl-accent);
            color: var(--tpl-accent);
            background: transparent;
        }

        .btn-tpl.btn-outline:hover {
            background: rgba(0, 212, 170, .12);
            border-color: var(--tpl-accent-dark);
            color: var(--tpl-accent-dark);
        }

        /* ═══ Contenedor ═══ */
        .tpl-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.25rem 1rem 2.5rem;
        }

        .tpl-panel {
            background: var(--tpl-bg-2);
            border: 1px solid var(--tpl-border);
            border-radius: 12px;
            padding: 1.25rem;
        }

        /* ═══ Metadatos ═══ */
        .tpl-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: .9rem;
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            margin-bottom: .3rem;
            font-size: .76rem;
            font-weight: 600;
            color: var(--tpl-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .field label .req { color: var(--tpl-danger); }

        .field input[type="text"],
        .field input[type="url"],
        .field textarea,
        .field select {
            width: 100%;
            padding: .5rem .65rem;
            border: 1px solid var(--tpl-border);
            border-radius: 6px;
            background: var(--tpl-bg);
            color: var(--tpl-text);
            font: inherit;
            font-size: .85rem;
        }

        .field input::placeholder,
        .field textarea::placeholder { color: #5b6572; }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            outline: 2px solid rgba(0, 212, 170, .35);
            outline-offset: 1px;
            border-color: var(--tpl-accent);
            box-shadow: none;
        }

        /* ═══ Pestañas ═══ */
        .tpl-tabs {
            display: flex;
            gap: .25rem;
            padding: .35rem;
            margin-bottom: .9rem;
            background: var(--tpl-bg);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
            overflow-x: auto;
        }

        .tpl-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .9rem;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--tpl-muted);
            font: inherit;
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color var(--tpl-t), color var(--tpl-t);
        }

        .tpl-tabs .nav-link i { margin: 0; font-size: .95rem; }

        .tpl-tabs .nav-link:hover {
            color: var(--tpl-text);
            background: rgba(255, 255, 255, .05);
        }

        .tpl-tabs .nav-link.active {
            color: var(--tpl-accent);
            background: rgba(0, 212, 170, .12);
            font-weight: 600;
        }

        /* ═══ Área de edición ═══ */
        .tab-content {
            background: var(--tpl-bg);
            border: 1px solid var(--tpl-border);
            border-radius: var(--tpl-radius);
            overflow: hidden;
        }

        .tab-pane {
            display: none;
            padding: 0;
        }

        .tab-pane.active {
            display: block;
        }

        /* CodeMirror */
        .CodeMirror {
            height: 620px;
            border: 0;
            border-radius: 0;
            font-family: var(--tpl-mono);
            font-size: 13px;
        }

        .CodeMirror-gutters {
            border-right: 1px solid var(--tpl-border);
        }

        /* GrapesJS: el lienzo conserva fondo claro para editar la plantilla,
           el marco es oscuro como el resto del editor. */
        #gjs-editor {
            height: 620px;
            border: 0;
            border-radius: 0;
            overflow: hidden;
        }

        .gjs-cv-canvas {
            background: #ffffff !important;
        }

        /* ═══ Pie del panel ═══ */
        .tpl-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .9rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--tpl-border);
        }

        .tpl-options {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.1rem;
        }

        .tpl-options .form-check {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin: 0;
        }

        .tpl-options .form-check-input {
            width: 16px;
            height: 16px;
            margin: 0;
            background-color: var(--tpl-bg);
            border-color: var(--tpl-border);
            accent-color: var(--tpl-accent);
        }

        .tpl-options .form-check-input:checked {
            background-color: var(--tpl-accent);
            border-color: var(--tpl-accent);
        }

        .tpl-options .form-check-label {
            font-size: .84rem;
            color: var(--tpl-muted);
            cursor: pointer;
        }

        .tpl-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .tpl-actions .btn-tpl {
            padding: .55rem 1rem;
        }

        /* ═══ Avisos: mismo lenguaje que los toasts del builder ═══ */
        .alert-fixed {
            position: fixed;
            top: calc(var(--tpl-topbar-h) + 12px);
            right: 16px;
            z-index: 9999;
            min-width: 280px;
            max-width: 460px;
            padding: .65rem .9rem;
            border: 1px solid var(--tpl-border);
            border-left: 3px solid var(--tpl-accent);
            border-radius: var(--tpl-radius);
            background: var(--tpl-bg-2);
            color: var(--tpl-text);
            font-size: .84rem;
            box-shadow: 0 10px 28px rgba(0, 0, 0, .45);
        }

        .alert-fixed.alert-success { border-left-color: #22c55e; }
        .alert-fixed.alert-danger { border-left-color: var(--tpl-danger); }
        .alert-fixed.alert-warning { border-left-color: #f59e0b; }
        .alert-fixed.alert-info { border-left-color: #3b82f6; }

        .alert-fixed .btn-close {
            filter: invert(1) grayscale(1) brightness(2);
            opacity: .6;
            padding: .6rem;
        }

        .alert-fixed .btn-close:hover { opacity: 1; }

        /* ═══ Responsive ═══ */
        @media (max-width: 768px) {
            .CodeMirror,
            #gjs-editor {
                height: 440px;
            }

            .tpl-panel { padding: 1rem; }

            .tpl-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .tpl-actions .btn-tpl { flex: 1 1 auto; justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: .001ms !important;
                animation-duration: .001ms !important;
            }
        }
    </style>
</head>
<body class="tpl-body">

<!-- ═══ BARRA SUPERIOR ═══ -->
<header class="tpl-topbar">
    <div class="tpl-topbar-left">
        <a href="<?= url('admin/templates') ?>" class="tpl-logo" title="Volver a plantillas">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="tpl-title">
            <i class="bi bi-layout-text-window"></i>
            <span><?= e($pageTitle) ?></span>
            <?php if ($template): ?>
                <span class="tpl-badge">#<?= (int)$t['id'] ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tpl-topbar-right">
        <?php if ($template): ?>
            <a href="<?= url('admin/templates/' . (int)$t['id'] . '/versions') ?>" class="btn-tpl btn-ghost">
                <i class="bi bi-clock-history"></i><span class="btn-label">Versiones</span>
            </a>
        <?php endif; ?>
        <button type="submit" form="templateForm" class="btn-tpl btn-primary" id="btnSave">
            <i class="bi bi-check-lg"></i><span class="btn-label">Guardar</span>
        </button>
    </div>
</header>

<!-- ═══ CONTENIDO ═══ -->
<main class="tpl-main">
    <form method="post"
          action="<?= url($template ? "admin/templates/{$t['id']}" : 'admin/templates') ?>"
          id="templateForm"
          class="tpl-panel">
        <?= \App\Core\CSRF::field() ?>

        <!-- ═══ METADATOS ═══ -->
        <section class="tpl-meta">
            <div class="field">
                <label for="tplName">Nombre <span class="req">*</span></label>
                <input type="text" name="name" id="tplName" value="<?= e($t['name']) ?>" required
                       placeholder="Nombre de la plantilla">
            </div>
            <div class="field">
                <label for="tplDescription">Descripción</label>
                <input type="text" name="description" id="tplDescription"
                       value="<?= e($t['description']) ?>" placeholder="Para qué sirve esta plantilla">
            </div>
        </section>

        <!-- ═══ PESTAÑAS ═══ -->
        <nav class="tpl-tabs" id="editorTabs" role="tablist" aria-label="Modo de edición">
            <button type="button" class="nav-link active" data-tab="visual" role="tab">
                <i class="bi bi-palette"></i> Visual
            </button>
            <button type="button" class="nav-link" data-tab="html" role="tab">
                <i class="bi bi-code-slash"></i> HTML
            </button>
            <button type="button" class="nav-link" data-tab="css" role="tab">
                <i class="bi bi-palette2"></i> CSS
            </button>
            <button type="button" class="nav-link" data-tab="js" role="tab">
                <i class="bi bi-braces"></i> JS
            </button>
            <?php if ($canEditPhp): ?>
            <button type="button" class="nav-link" data-tab="php" role="tab">
                <i class="bi bi-filetype-php"></i> PHP
            </button>
            <?php endif; ?>
        </nav>

        <!-- ═══ CONTENIDO DE PESTAÑAS ═══ -->
        <div class="tab-content">
            <!-- Visual (GrapesJS) -->
            <div class="tab-pane active" id="pane-visual" data-tab="visual" role="tabpanel">
                <div id="gjs-editor"></div>
            </div>

            <!-- HTML -->
            <div class="tab-pane" id="pane-html" data-tab="html" role="tabpanel">
                <textarea name="content_html" id="cm-html"><?= e($initialHtml) ?></textarea>
            </div>

            <!-- CSS -->
            <div class="tab-pane" id="pane-css" data-tab="css" role="tabpanel">
                <textarea name="content_css" id="cm-css"><?= e($initialCss) ?></textarea>
            </div>

            <!-- JS -->
            <div class="tab-pane" id="pane-js" data-tab="js" role="tabpanel">
                <textarea name="content_js" id="cm-js"><?= e($t['content_js'] ?? '') ?></textarea>
            </div>

            <!-- PHP -->
            <?php if ($canEditPhp): ?>
            <div class="tab-pane" id="pane-php" data-tab="php" role="tabpanel">
                <textarea name="content_php" id="cm-php"><?= e($t['content_php'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══ CAMPO OCULTO GRAPESJS ═══ -->
        <input type="hidden" name="grapesjs_data" id="grapesjs-data"
               value="<?= e(is_array($grapesjsData) ? json_encode($grapesjsData) : $grapesjsData) ?>">

        <!-- ═══ OPCIONES Y ACCIONES ═══ -->
        <footer class="tpl-footer">
            <div class="tpl-options">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                           <?= !empty($t['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Activo</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault"
                           <?= !empty($t['is_default']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isDefault">Por defecto</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="create_version" value="1" class="form-check-input" id="createVersion" checked>
                    <label class="form-check-label" for="createVersion">Guardar versión</label>
                </div>
            </div>

            <div class="tpl-actions">
                <?php if ($template): ?>
                <button type="button" class="btn-tpl btn-outline" id="btnSaveVersion">
                    <i class="bi bi-clock-history"></i> Guardar versión
                </button>
                <?php endif; ?>
                <a href="<?= url('admin/templates') ?>" class="btn-tpl btn-ghost">Cancelar</a>
            </div>
        </footer>
    </form>
</main>

<!-- ═══════════════════════════════════════════════════════════════════
     DEPENDENCIAS CDN
═══════════════════════════════════════════════════════════════════ -->
<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- GrapesJS -->
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>

<!-- CodeMirror -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>

<script>
// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN GLOBAL
// ═══════════════════════════════════════════════════════════════════
const CSRF_TOKEN = '<?= _tplCsrfToken() ?>';
const URL_VERSIONS = '<?= $template ? url("admin/templates/{$t['id']}/versions") : "" ?>';
const URL_COMPONENTS = '<?= url("admin/components/api") ?>';

// Datos iniciales para GrapesJS
const INITIAL_HTML = <?= json_encode($initialHtml) ?>;
const INITIAL_CSS = <?= json_encode($initialCss) ?>;

// ═══════════════════════════════════════════════════════════════════
// DOCUMENT READY
// ═══════════════════════════════════════════════════════════════════
$(document).ready(function() {
    
    // ═══════════════════════════════════════════════════════════════
    // 1. SISTEMA DE TABS MEJORADO
    // ═══════════════════════════════════════════════════════════════
    const tabButtons = $('#editorTabs .nav-link');
    const tabPanes = $('.tab-pane');
    const editors = {};

    // Función para cambiar de tab
    function switchTab(tabId) {
        // Desactivar todos los tabs
        tabButtons.removeClass('active');
        tabPanes.removeClass('active');
        
        // Activar el tab seleccionado
        $(`#editorTabs .nav-link[data-tab="${tabId}"]`).addClass('active');
        $(`#pane-${tabId}`).addClass('active');
        
        // Refrescar CodeMirror después de mostrar
        setTimeout(function() {
            if (editors[tabId]) {
                editors[tabId].refresh();
            }
            // Refrescar GrapesJS si es visual
            if (tabId === 'visual' && window.gjsEditor) {
                window.gjsEditor.refresh();
                // También redimensionar el canvas
                setTimeout(function() {
                    window.gjsEditor.refresh();
                }, 100);
            }
        }, 50);
    }

    // Evento click en tabs
    tabButtons.on('click', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        
        // Si es visual y GrapesJS no está inicializado, inicializarlo
        if (tab === 'visual' && !window.gjsEditor) {
            initGrapesJS();
        }
        
        switchTab(tab);
    });

    // ═══════════════════════════════════════════════════════════════
    // 2. INICIALIZAR CODEMIRROR
    // ═══════════════════════════════════════════════════════════════
    function initCodeMirror(textareaId, mode) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return null;
        
        const editor = CodeMirror.fromTextArea(textarea, {
            mode: mode,
            theme: 'dracula',
            lineNumbers: true,
            lineWrapping: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 2,
            tabSize: 2,
            extraKeys: {
                "Ctrl-S": function() {
                    $('#btnSave').click();
                }
            }
        });
        
        // Forzar refresh
        setTimeout(function() {
            editor.refresh();
        }, 100);
        
        return editor;
    }

    editors.html = initCodeMirror('cm-html', 'htmlmixed');
    editors.css = initCodeMirror('cm-css', 'css');
    editors.js = initCodeMirror('cm-js', 'javascript');
    <?php if ($canEditPhp): ?>
    editors.php = initCodeMirror('cm-php', 'application/x-httpd-php');
    <?php endif; ?>

    console.log('✅ CodeMirror inicializado:', Object.keys(editors));

    // ═══════════════════════════════════════════════════════════════
    // 3. INICIALIZAR GRAPESJS (solo si es necesario)
    // ═══════════════════════════════════════════════════════════════
    function initGrapesJS() {
        if (window.gjsEditor) {
            console.log('⚠️ GrapesJS ya está inicializado');
            return window.gjsEditor;
        }

        const editorContainer = document.getElementById('gjs-editor');
        if (!editorContainer) {
            console.error('❌ No se encontró el contenedor de GrapesJS');
            return null;
        }

        console.log('🔄 Inicializando GrapesJS...');
        
        try {
            const gjsEditor = grapesjs.init({
                container: '#gjs-editor',
                height: '600px',
                width: 'auto',
                fromElement: false,
                storageManager: { 
                    type: 'none',
                    autoload: false,
                    autosave: false
                },
                components: INITIAL_HTML || '<div style="padding: 50px; text-align: center; color: #6c757d;">Contenido vacío - Comienza a diseñar</div>',
                style: INITIAL_CSS || '',
                plugins: ['gjs-blocks-basic'],
                pluginsOpts: {
                    'gjs-blocks-basic': { 
                        flexGrid: true 
                    }
                },
                deviceManager: {
                    devices: [
                        { name: 'Desktop', width: '' },
                        { name: 'Tablet', width: '768px', widthMedia: '992px' },
                        { name: 'Mobile', width: '320px', widthMedia: '480px' }
                    ]
                },
                canvas: {
                    scripts: [],
                    styles: []
                },
                // Evitar problemas de autoguardado
                storage: {
                    type: 'none'
                },
                // Configuración adicional para estabilidad
                undoManager: {
                    trackSelection: true
                }
            });
            
            window.gjsEditor = gjsEditor;
            
            // Forzar refresh después de la inicialización
            setTimeout(function() {
                gjsEditor.refresh();
                gjsEditor.setComponents(INITIAL_HTML || '<div>Contenido vacío</div>');
                gjsEditor.setStyle(INITIAL_CSS || '');
            }, 100);
            
            console.log('✅ GrapesJS inicializado correctamente');
            
            // Cargar componentes personalizados
            loadCustomComponents(gjsEditor);
            
            return gjsEditor;
            
        } catch (e) {
            console.error('❌ Error al inicializar GrapesJS:', e);
            showNotification('Error al cargar el editor visual', 'danger');
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. CARGAR COMPONENTES PERSONALIZADOS
    // ═══════════════════════════════════════════════════════════════
    function loadCustomComponents(editor) {
        if (!editor || !URL_COMPONENTS) return;
        
        // Verificar si la URL es válida
        if (URL_COMPONENTS === '') {
            console.log('ℹ️ No hay componentes personalizados configurados');
            return;
        }
        
        $.ajax({
            url: URL_COMPONENTS,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (!data || !data.success || !Array.isArray(data.data)) {
                    console.log('ℹ️ No hay componentes disponibles');
                    return;
                }

                const bm = editor.BlockManager;
                const grouped = {};

                data.data.forEach(function(c) {
                    const cat = c.category || 'General';
                    if (!grouped[cat]) grouped[cat] = [];
                    grouped[cat].push(c);
                });

                Object.entries(grouped).forEach(function([cat, items]) {
                    items.forEach(function(c) {
                        try {
                            bm.add('custom-' + c.id, {
                                label: c.name,
                                category: '📦 ' + cat,
                                content: c.html || '<div>Componente vacío</div>',
                                style: c.css || ''
                            });
                        } catch (e) {
                            console.warn('Error al agregar componente:', c.name, e);
                        }
                    });
                });
                console.log('✅ Componentes cargados:', data.data.length);
            },
            error: function(err) {
                console.warn('⚠️ Error al cargar componentes:', err);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. SINCRONIZACIÓN BIDIRECCIONAL
    // ═══════════════════════════════════════════════════════════════
    function syncFromGrapesJS() {
        if (!window.gjsEditor) return;
        try {
            const html = window.gjsEditor.getHtml();
            const css = window.gjsEditor.getCss();
            const projectData = window.gjsEditor.getProjectData();

            if (editors.html) editors.html.setValue(html);
            if (editors.css) editors.css.setValue(css);
            
            const grapesjsDataEl = document.getElementById('grapesjs-data');
            if (grapesjsDataEl) {
                grapesjsDataEl.value = JSON.stringify(projectData);
            }
        } catch (e) {
            console.warn('Error en syncFromGrapesJS:', e);
        }
    }

    function syncToGrapesJS() {
        if (!window.gjsEditor) return;
        try {
            if (editors.html) {
                window.gjsEditor.setComponents(editors.html.getValue());
            }
            if (editors.css) {
                window.gjsEditor.setStyle(editors.css.getValue());
            }
            // Refrescar después de sincronizar
            setTimeout(function() {
                window.gjsEditor.refresh();
            }, 100);
        } catch (e) {
            console.warn('Error en syncToGrapesJS:', e);
        }
    }

    // Exponer funciones globalmente
    window.syncFromGrapesJS = syncFromGrapesJS;
    window.syncToGrapesJS = syncToGrapesJS;

    // ═══════════════════════════════════════════════════════════════
    // 6. INICIALIZAR GRAPESJS SI EL TAB VISUAL ESTÁ ACTIVO
    // ═══════════════════════════════════════════════════════════════
    // Verificar si el tab visual está activo al cargar
    if ($('#pane-visual').hasClass('active')) {
        initGrapesJS();
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. EVENTOS DE FORMULARIO
    // ═══════════════════════════════════════════════════════════════
    $('#templateForm').on('submit', function(e) {
        // Si GrapesJS está inicializado, sincronizar
        if (window.gjsEditor) {
            syncFromGrapesJS();
        }
        
        // Guardar los textareas de CodeMirror
        Object.values(editors).forEach(function(editor) {
            if (editor && typeof editor.save === 'function') {
                editor.save();
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // 8. BOTÓN "GUARDAR VERSIÓN"
    // ═══════════════════════════════════════════════════════════════
    <?php if ($template): ?>
    $('#btnSaveVersion').on('click', function() {
        if (!confirm('¿Guardar una versión del estado actual?')) return;

        // Sincronizar desde GrapesJS si está activo
        if (window.gjsEditor) {
            syncFromGrapesJS();
        }

        // Obtener valores actualizados
        const payload = {
            content_html: editors.html ? editors.html.getValue() : '',
            content_css: editors.css ? editors.css.getValue() : '',
            content_js: editors.js ? editors.js.getValue() : '',
            <?php if ($canEditPhp): ?>
            content_php: editors.php ? editors.php.getValue() : '',
            <?php endif; ?>
            action: 'create'
        };

        $.ajax({
            url: URL_VERSIONS,
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: JSON.stringify(payload),
            success: function(data) {
                showNotification(
                    data.success ? '✅ Versión guardada correctamente' : '❌ Error: ' + (data.error || 'desconocido'),
                    data.success ? 'success' : 'danger'
                );
            },
            error: function() {
                showNotification('❌ Error de red', 'danger');
            }
        });
    });
    <?php endif; ?>

    // ═══════════════════════════════════════════════════════════════
    // 9. NOTIFICACIONES
    // ═══════════════════════════════════════════════════════════════
    window.showNotification = function(message, type = 'info') {
        const icons = {
            success: 'check-circle-fill',
            danger: 'exclamation-triangle-fill',
            info: 'info-circle-fill',
            warning: 'exclamation-triangle-fill'
        };

        const alertDiv = $(`
            <div class="alert alert-${type} alert-dismissible fade show shadow alert-fixed" role="alert">
                <i class="bi bi-${icons[type] || 'info-circle-fill'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        $('body').append(alertDiv);
        
        setTimeout(function() {
            alertDiv.alert('close');
        }, 5000);
    };

    // ═══════════════════════════════════════════════════════════════
    // 10. MANEJO DE ERRORES Y DEBUG
    // ═══════════════════════════════════════════════════════════════
    // Mostrar estado del editor en consola
    console.log('✅ Editor de templates cargado correctamente');
    console.log('📊 Estado:', {
        'Tabs': tabButtons.length,
        'CodeMirror': Object.keys(editors).length,
        'GrapesJS': !!window.gjsEditor,
        'Template ID': <?= $template ? (int)$t['id'] : 'null' ?>
    });

    // Tecla de ayuda: Ctrl+Shift+D para debug
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            console.log('🔍 Debug info:', {
                'gjsEditor': window.gjsEditor,
                'editors': editors,
                'initialHtml': INITIAL_HTML,
                'initialCss': INITIAL_CSS
            });
            showNotification('Debug info en consola', 'info');
        }
    });
});
</script>
</body>
</html>