<?php
/**
 * Vista: editor de plantillas (templates).
 *
 * Usa el MISMO modelo que el editor de páginas (views/pages/builder.php):
 *   - GrapesJS con los mismos plugins, bloques y lienzo (views/partials/editor-assets-*).
 *   - CodeMirror para HTML/CSS/JS/PHP con el mismo tema y atajos.
 *   - Sincronización bidireccional lienzo ↔ código y guardado por fetch.
 *
 * Lo que es propio de plantillas: no hay autoguardado (no existe tabla para
 * ello) y no hay subida de imágenes desde aquí (no hay endpoint; el gestor de
 * assets muestra la biblioteca de medios ya subida).
 *
 * @package Views\Templates
 */

use App\Core\Auth;

$pageTitle = $template ? 'Editar plantilla' : 'Nueva plantilla';
$t = $template ?? [
    'id' => null, 'name' => '', 'description' => '',
    'content_html' => '', 'content_css' => '', 'content_js' => '',
    'content_php' => '', 'grapesjs_data' => null,
    'is_active' => 1, 'is_default' => 0, 'is_system_template' => 0,
];

$templateId  = isset($t['id']) ? (int) $t['id'] : 0;
$isNew       = $templateId === 0;
$canEditPhp  = Auth::can('templates.edit_php');
$csrfToken   = \App\Core\CSRF::token();

// JSON_HEX_TAG evita que un </script> dentro del contenido rompa el documento.
$jsonFlags   = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
$jsonHtml    = json_encode((string) ($t['content_html'] ?? ''), $jsonFlags);
$jsonCss     = json_encode((string) ($t['content_css'] ?? ''), $jsonFlags);
$jsonJs      = json_encode((string) ($t['content_js'] ?? ''), $jsonFlags);
$jsonPhp     = json_encode((string) ($t['content_php'] ?? ''), $jsonFlags);
$jsonGrapes  = json_encode($t['grapesjs_data'] ?? null, $jsonFlags);

$saveUrl     = $isNew ? url('admin/templates') : url('admin/templates/' . $templateId);
$versionsUrl = $isNew ? '' : url('admin/templates/' . $templateId . '/versions');
$assetsUrl   = url('admin/pages/assets');
$listUrl     = url('admin/templates');

// Los mismos bloques que el builder de páginas: la lista se descubre en disco,
// así que añadir un fichero en assets/js/blocks lo carga también aquí.
$blockExcluded = ['bs-blocks.js', 'template.js'];
$blockFiles = [];
foreach (glob(ASSETS_PATH . '/js/blocks/*.js') ?: [] as $blockFile) {
    $blockName = basename($blockFile);
    if (in_array($blockName, $blockExcluded, true)) {
        continue;
    }
    $blockFiles[] = $blockName;
}
sort($blockFiles);
$blockUrls = array_map(static fn (string $name): string => asset('js/blocks/' . $name), $blockFiles);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · PePiuoX Editor</title>

<!-- Activos del editor: exactamente los mismos que el builder de páginas -->
<?php $editorIncludeBootstrap = true; require VIEWS_PATH . '/partials/editor-assets-head.php'; ?>

<style>
/* ═══ Variables: misma paleta que el builder ═══ */
:root {
    --tpl-bg: #0d1117;
    --tpl-bg-2: #161b22;
    --tpl-bg-3: #1c2333;
    --tpl-border: #30363d;
    --tpl-text: #e6edf3;
    --tpl-muted: #8b949e;
    --tpl-accent: #00d4aa;
    --tpl-accent-dark: #00a88a;
    --tpl-danger: #ef4444;
    --tpl-warning: #f59e0b;
    --tpl-success: #22c55e;
    --tpl-radius: 8px;
    --tpl-topbar-h: 56px;
    --tpl-t: .18s ease;
    --tpl-mono: 'JetBrains Mono', monospace;
}

* { box-sizing: border-box; }

body.tpl-body {
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--tpl-bg);
    color: var(--tpl-text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 14px;
}

a { color: var(--tpl-accent); text-decoration: none; }
a:hover { color: var(--tpl-accent-dark); }

:where(a, button, .btn-tpl, input, textarea, select):focus-visible {
    outline: 2px solid var(--tpl-accent);
    outline-offset: 2px;
    border-radius: var(--tpl-radius);
}

/* ═══ Barra superior ═══ */
.tpl-topbar {
    min-height: var(--tpl-topbar-h);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .5rem 1rem;
    background: var(--tpl-bg-2);
    border-bottom: 1px solid var(--tpl-border);
    flex: 0 0 auto;
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

.tpl-state {
    font-size: .78rem;
    color: var(--tpl-muted);
    font-style: italic;
    min-width: 140px;
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
    border-color: var(--tpl-accent);
}

.btn-tpl.btn-primary {
    background: var(--tpl-accent);
    color: #04120f;
    font-weight: 600;
}

.btn-tpl.btn-primary:hover { background: var(--tpl-accent-dark); color: #fff; }

.btn-tpl.btn-outline {
    border-color: var(--tpl-accent);
    color: var(--tpl-accent);
}

.btn-tpl.btn-outline:hover { background: rgba(0, 212, 170, .12); color: var(--tpl-accent-dark); }

.btn-tpl:disabled { opacity: .6; cursor: not-allowed; }

/* ═══ Estructura: barra de metadatos + editor que ocupa el resto ═══ */
.tpl-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: .75rem;
    padding: .75rem 1rem;
    background: var(--tpl-bg-2);
    border-bottom: 1px solid var(--tpl-border);
    flex: 0 0 auto;
}

.field label {
    display: block;
    margin-bottom: .25rem;
    font-size: .72rem;
    font-weight: 600;
    color: var(--tpl-muted);
    text-transform: uppercase;
    letter-spacing: .03em;
}

.field label .req { color: var(--tpl-danger); }

.field input[type="text"] {
    width: 100%;
    padding: .45rem .6rem;
    border: 1px solid var(--tpl-border);
    border-radius: 6px;
    background: var(--tpl-bg);
    color: var(--tpl-text);
    font: inherit;
    font-size: .85rem;
}

.field input::placeholder { color: #5b6572; }

.field input:focus {
    outline: none;
    border-color: var(--tpl-accent);
    box-shadow: 0 0 0 3px rgba(0, 212, 170, .12);
}

.tpl-layout {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* ═══ Pestañas ═══ */
.tpl-tabs {
    display: flex;
    background: var(--tpl-bg-2);
    border-bottom: 1px solid var(--tpl-border);
    overflow-x: auto;
    flex: 0 0 auto;
}

.tpl-tabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.1rem;
    border: 0;
    border-bottom: 3px solid transparent;
    background: transparent;
    color: var(--tpl-muted);
    font: inherit;
    font-size: .84rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: color var(--tpl-t), background-color var(--tpl-t), border-color var(--tpl-t);
}

.tpl-tabs .nav-link:hover { color: var(--tpl-accent); background: var(--tpl-bg-3); }

.tpl-tabs .nav-link.active {
    color: var(--tpl-accent);
    border-bottom-color: var(--tpl-accent);
    background: var(--tpl-bg-3);
}

/* ═══ Paneles ═══ */
.tpl-panes {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
    background: var(--tpl-bg);
}

.tab-pane {
    display: none;
    position: absolute;
    inset: 0;
}

.tab-pane.active { display: block; }

#gjs { height: 100%; width: 100%; }

.gjs-cv-canvas { background: #ffffff !important; }

.CodeMirror {
    height: 100% !important;
    font-family: var(--tpl-mono);
    font-size: 13px;
}

.CodeMirror-gutters { border-right: 1px solid var(--tpl-border); }

/* ═══ Barra de estado ═══ */
.tpl-statusbar {
    height: 28px;
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 0 1rem;
    background: var(--tpl-bg-2);
    border-top: 1px solid var(--tpl-border);
    color: var(--tpl-muted);
    font-size: .75rem;
    flex: 0 0 auto;
}

.tpl-statusbar .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--tpl-success);
}

.tpl-statusbar .dot.saving { background: var(--tpl-warning); animation: tplPulse 1s infinite; }

.tpl-statusbar-right { margin-left: auto; display: flex; align-items: center; gap: .75rem; }

@keyframes tplPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

/* ═══ Opciones ═══ */
.tpl-options {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.1rem;
    padding: .6rem 1rem;
    background: var(--tpl-bg-2);
    border-top: 1px solid var(--tpl-border);
    flex: 0 0 auto;
}

.tpl-check {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .84rem;
    color: var(--tpl-muted);
    cursor: pointer;
    margin: 0;
}

.tpl-check input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--tpl-accent);
    cursor: pointer;
}

.tpl-options-actions { margin-left: auto; display: flex; gap: .5rem; }

/* ═══ Toasts: mismos que el builder ═══ */
.toast-container {
    position: fixed;
    top: 70px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    max-width: 360px;
}

.toast {
    padding: .75rem 1rem;
    background: var(--tpl-bg-3);
    border: 1px solid var(--tpl-border);
    border-left: 4px solid var(--tpl-success);
    border-radius: var(--tpl-radius);
    box-shadow: 0 10px 25px rgba(0, 0, 0, .4);
    color: var(--tpl-text);
    font-size: .85rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    transition: opacity .3s ease, transform .3s ease;
}

.toast.error { border-left-color: var(--tpl-danger); }
.toast.warning { border-left-color: var(--tpl-warning); }
.toast.info { border-left-color: #3b82f6; }

@media (max-width: 768px) {
    .tpl-state { display: none; }
    .btn-tpl .btn-label { display: none; }
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
        <a href="<?= e($listUrl) ?>" class="tpl-logo" title="Volver a plantillas">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="tpl-title">
            <i class="bi bi-layout-text-window"></i>
            <span id="displayName"><?= e($t['name'] !== '' ? $t['name'] : $pageTitle) ?></span>
            <?php if (!$isNew): ?>
                <span class="tpl-badge">#<?= $templateId ?></span>
                <?php if (!empty($t['is_system_template'])): ?>
                    <span class="tpl-badge" title="Plantilla del sistema">sistema</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tpl-state" id="saveState"></div>

    <div class="tpl-topbar-right">
        <?php if (!$isNew): ?>
            <a href="<?= e($versionsUrl) ?>" class="btn-tpl btn-ghost">
                <i class="bi bi-clock-history"></i><span class="btn-label">Versiones</span>
            </a>
        <?php endif; ?>
        <button type="button" class="btn-tpl btn-primary" id="btnSave">
            <i class="bi bi-save"></i><span class="btn-label">Guardar</span>
        </button>
    </div>
</header>

<!-- ═══ METADATOS ═══ -->
<form id="templateForm" method="post" action="<?= e($saveUrl) ?>">
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="grapesjs_data" id="grapesjs-data" value="">

    <div class="tpl-meta">
        <div class="field">
            <label for="tplName">Nombre <span class="req">*</span></label>
            <input type="text" name="name" id="tplName" required
                   value="<?= e($t['name']) ?>" placeholder="Nombre de la plantilla">
        </div>
        <div class="field">
            <label for="tplDescription">Descripción</label>
            <input type="text" name="description" id="tplDescription"
                   value="<?= e($t['description']) ?>" placeholder="Para qué sirve esta plantilla">
        </div>
    </div>

    <!-- ═══ EDITOR ═══ -->
    <div class="tpl-layout">
        <nav class="tpl-tabs" id="editorTabs" role="tablist" aria-label="Modo de edición">
            <button type="button" class="nav-link active" data-tab="visual" role="tab" aria-selected="true">
                <i class="bi bi-palette"></i> Visual
            </button>
            <button type="button" class="nav-link" data-tab="html" role="tab" aria-selected="false">
                <i class="bi bi-code-slash"></i> HTML
            </button>
            <button type="button" class="nav-link" data-tab="css" role="tab" aria-selected="false">
                <i class="bi bi-palette2"></i> CSS
            </button>
            <button type="button" class="nav-link" data-tab="js" role="tab" aria-selected="false">
                <i class="bi bi-braces"></i> JS
            </button>
            <?php if ($canEditPhp): ?>
            <button type="button" class="nav-link" data-tab="php" role="tab" aria-selected="false">
                <i class="bi bi-filetype-php"></i> PHP
            </button>
            <?php endif; ?>
        </nav>

        <div class="tpl-panes">
            <div class="tab-pane active" id="pane-visual" data-pane="visual" role="tabpanel">
                <div id="gjs"></div>
            </div>
            <div class="tab-pane" id="pane-html" data-pane="html" role="tabpanel">
                <textarea id="editor-html"></textarea>
            </div>
            <div class="tab-pane" id="pane-css" data-pane="css" role="tabpanel">
                <textarea id="editor-css"></textarea>
            </div>
            <div class="tab-pane" id="pane-js" data-pane="js" role="tabpanel">
                <textarea id="editor-js"></textarea>
            </div>
            <?php if ($canEditPhp): ?>
            <div class="tab-pane" id="pane-php" data-pane="php" role="tabpanel">
                <textarea id="editor-php"></textarea>
            </div>
            <?php endif; ?>
        </div>

        <div class="tpl-statusbar">
            <span class="dot" id="statusDot"></span>
            <span id="statusMode">Editor visual</span>
            <span>|</span>
            <span id="statusInfo"></span>
            <span class="tpl-statusbar-right">
                <span id="statusSize">0 KB</span>
                <span>|</span>
                <span id="statusTemplate">
                    <?= $isNew ? 'Plantilla nueva' : 'Plantilla #' . $templateId ?>
                </span>
            </span>
        </div>

        <div class="tpl-options">
            <label class="tpl-check">
                <input type="checkbox" name="is_active" value="1" id="isActive" <?= !empty($t['is_active']) ? 'checked' : '' ?>>
                <span>Activa</span>
            </label>
            <label class="tpl-check">
                <input type="checkbox" name="is_default" value="1" id="isDefault" <?= !empty($t['is_default']) ? 'checked' : '' ?>>
                <span>Por defecto</span>
            </label>
            <label class="tpl-check">
                <input type="checkbox" name="create_version" value="1" id="createVersion" checked>
                <span>Guardar versión al guardar</span>
            </label>

            <div class="tpl-options-actions">
                <?php if (!$isNew): ?>
                <button type="button" class="btn-tpl btn-outline" id="btnSaveVersion">
                    <i class="bi bi-clock-history"></i><span class="btn-label">Guardar versión</span>
                </button>
                <?php endif; ?>
                <a href="<?= e($listUrl) ?>" class="btn-tpl btn-ghost">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<div class="toast-container" id="toastContainer"></div>

<!-- Activos del editor: mismos que el builder (CodeMirror + GrapesJS + plugins) -->
<?php require VIEWS_PATH . '/partials/editor-assets-body.php'; ?>

<script>
// ═══════════════════════════════════════════════════════════════
// CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════
const CONFIG = {
    saveUrl: '<?= e($saveUrl) ?>',
    versionsUrl: '<?= e($versionsUrl) ?>',
    assetsUrl: '<?= e($assetsUrl) ?>',
    csrfToken: '<?= e($csrfToken) ?>',
    templateId: <?= $templateId ?>,
    isNew: <?= $isNew ? 'true' : 'false' ?>,
    canEditPhp: <?= $canEditPhp ? 'true' : 'false' ?>
};

const templateData = {
    html: <?= $jsonHtml ?>,
    css: <?= $jsonCss ?>,
    js: <?= $jsonJs ?>,
    php: <?= $jsonPhp ?>,
    grapes: <?= $jsonGrapes ?>
};

// Ficheros de bloques, servidos por PHP desde public/assets/js/blocks.
const BLOCK_SCRIPTS = <?= json_encode($blockUrls, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

const existingAssets = [];

// ═══════════════════════════════════════════════════════════════
// ESTADO
// ═══════════════════════════════════════════════════════════════
let cmHtml = null, cmCss = null, cmJs = null, cmPhp = null;
let gjsEditor = null;
let currentTab = 'visual';
let isSyncing = false;
let isSaving = false;
let isDirty = false;
let syncTimer = null;

const tabLabels = { visual: 'Editor visual', html: 'HTML', css: 'CSS', js: 'JavaScript', php: 'PHP' };

// ═══════════════════════════════════════════════════════════════
// ARRANQUE
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    var failed = [];
    var safeInit = function(label, fn) {
        try {
            fn();
        } catch (err) {
            console.error('❌ Editor de plantillas · falló ' + label + ':', err);
            failed.push(label);
        }
    };

    safeInit('CodeMirror', initCodeMirror);
    safeInit('lienzo visual', initGrapesJS);
    safeInit('eventos', bindEvents);

    if (failed.length) {
        showToast('✗ No se pudo iniciar: ' + failed.join(', '), 'error');
    }
    updateStatus();
    updateSizeInfo();
});

// ═══════════════════════════════════════════════════════════════
// CODEMIRROR (mismas opciones que el builder)
// ═══════════════════════════════════════════════════════════════
function initCodeMirror() {
    const cmOptions = {
        theme: 'dracula',
        lineNumbers: true,
        lineWrapping: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        autoCloseTags: true,
        styleActiveLine: true,
        tabSize: 2,
        indentUnit: 2,
        indentWithTabs: false,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-S': function() { saveTemplate(); }
        }
    };

    cmHtml = CodeMirror.fromTextArea(document.getElementById('editor-html'), Object.assign({}, cmOptions, { mode: 'htmlmixed' }));
    cmCss  = CodeMirror.fromTextArea(document.getElementById('editor-css'), Object.assign({}, cmOptions, { mode: 'css' }));
    cmJs   = CodeMirror.fromTextArea(document.getElementById('editor-js'), Object.assign({}, cmOptions, { mode: 'javascript' }));

    const phpEl = document.getElementById('editor-php');
    cmPhp = phpEl ? CodeMirror.fromTextArea(phpEl, Object.assign({}, cmOptions, { mode: 'application/x-httpd-php' })) : null;

    cmHtml.setValue(templateData.html || '');
    cmCss.setValue(templateData.css || '');
    cmJs.setValue(templateData.js || '');
    if (cmPhp) cmPhp.setValue(templateData.php || '');

    [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) {
        if (!cm) return;
        cm.on('change', function() {
            isDirty = true;
            updateStatus();
            updateSizeInfo();
        });
    });
}

// ═══════════════════════════════════════════════════════════════
// GRAPESJS (mismo modelo que el builder: EditorKit + bloques del proyecto)
// ═══════════════════════════════════════════════════════════════
function initGrapesJS() {
    if (gjsEditor) return gjsEditor;

    const pluginSelection = EditorKit.filterPlugins(EditorKit.PLUGIN_NAMES);
    if (pluginSelection.skipped.length) {
        console.warn('Plugins no disponibles (se omiten): ' + pluginSelection.skipped.join(', '));
        showToast('⚠ Sin plugin: ' + pluginSelection.skipped.join(', '), 'warning');
    }

    gjsEditor = grapesjs.init({
        container: '#gjs',
        showOffsets: true,
        fromElement: false,
        noticeOnUnload: false,
        storageManager: false,
        height: '100%',
        assetManager: {
            // Biblioteca de medios ya subida (la subida se hace desde el editor
            // de páginas: no hay endpoint de subida para plantillas).
            assets: existingAssets,
            autoAdd: true,
            accept: 'image/*'
        },
        selectorManager: { componentFirst: true },
        components: templateData.html || '',
        style: templateData.css || '',
        plugins: pluginSelection.enabled,
        pluginsOpts: EditorKit.PLUGINS_OPTS,
        canvas: {
            styles: EditorKit.CANVAS_STYLES,
            scripts: EditorKit.CANVAS_SCRIPTS
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile', width: '375px', widthMedia: '480px' }
            ]
        },
        styleManager: {
            sectors: [
                { name: 'Dimensiones', open: true, properties: ['width', 'height', 'max-width', 'min-height', 'margin', 'padding'] },
                { name: 'Tipografía', open: true, properties: ['font-family', 'font-size', 'font-weight', 'color', 'line-height', 'letter-spacing', 'text-align', 'text-decoration', 'text-transform'] },
                { name: 'Fondo', open: false, properties: ['background-color', 'background-image', 'background-size', 'background-position', 'background-repeat'] },
                { name: 'Bordes', open: false, properties: ['border-radius', 'border-width', 'border-style', 'border-color'] },
                { name: 'Efectos', open: false, properties: ['box-shadow', 'opacity', 'transition', 'transform'] },
                { name: 'Posición', open: false, properties: ['position', 'top', 'right', 'bottom', 'left', 'display', 'flex-direction', 'justify-content', 'align-items', 'gap', 'overflow'] }
            ]
        },
        layerManager: { scrollLayers: true },
        traitManager: { scrollTraits: true },
        colorPicker: { appendTo: 'parent' }
    });

    // Restaurar el proyecto guardado (ids, estilos y componentes que el HTML/CSS
    // plano no puede representar). Mismo mecanismo que el builder de páginas.
    if (templateData.grapes) {
        try {
            var project = typeof templateData.grapes === 'string'
                ? JSON.parse(templateData.grapes)
                : templateData.grapes;
            if (project && project.pages) {
                gjsEditor.loadProjectData(project);
            }
        } catch (err) {
            console.warn('No se pudo restaurar el proyecto del lienzo:', err);
        }
    }

    gjsEditor.on('update', function() {
        isDirty = true;
        updateStatus();
        if (isSyncing) return;
        isSyncing = true;
        try {
            cmHtml.setValue(gjsEditor.getHtml());
            cmCss.setValue(gjsEditor.getCss());
            updateSizeInfo();
        } catch (err) {
            console.warn('Error sincronizando lienzo → código:', err);
        }
        setTimeout(function() { isSyncing = false; }, 150);
    });

    // Código → lienzo, con retardo para no reconstruir en cada tecla.
    cmHtml.on('change', function() {
        if (isSyncing || !gjsEditor) return;
        clearTimeout(syncTimer);
        syncTimer = setTimeout(function() {
            isSyncing = true;
            try {
                gjsEditor.setComponents(cmHtml.getValue());
            } catch (err) {
                console.warn('Error sincronizando HTML:', err);
            }
            setTimeout(function() { isSyncing = false; }, 200);
        }, 600);
    });

    cmCss.on('change', function() {
        if (isSyncing || !gjsEditor) return;
        clearTimeout(syncTimer);
        syncTimer = setTimeout(function() {
            isSyncing = true;
            try {
                gjsEditor.setStyle(cmCss.getValue());
            } catch (err) {
                console.warn('Error sincronizando CSS:', err);
            }
            setTimeout(function() { isSyncing = false; }, 200);
        }, 600);
    });

    // Los ficheros de bloques registran con `gjsEditor.BlockManager.add(...)`:
    // hay que inyectarlos con el editor ya creado (EditorKit comparte el loader).
    EditorKit.loadBlockScripts(BLOCK_SCRIPTS, gjsEditor, function() {
        updateStatus();
    });

    // Biblioteca de medios compartida con el editor de páginas.
    loadAssets();

    return gjsEditor;
}

// ═══════════════════════════════════════════════════════════════
// PESTAÑAS Y EVENTOS
// ═══════════════════════════════════════════════════════════════
function switchTab(tab) {
    if (tab === currentTab) {
        refreshTab(tab);
        return;
    }
    currentTab = tab;

    document.querySelectorAll('.tpl-tabs .nav-link').forEach(function(btn) {
        const active = btn.dataset.tab === tab;
        btn.classList.toggle('active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    document.querySelectorAll('.tab-pane').forEach(function(pane) {
        pane.classList.toggle('active', pane.dataset.pane === tab);
    });

    document.getElementById('statusMode').textContent = tabLabels[tab] || tab;
    refreshTab(tab);
    updateStatus();
}

function refreshTab(tab) {
    setTimeout(function() {
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) { if (cm) cm.refresh(); });
        if (tab === 'visual' && gjsEditor) gjsEditor.refresh();
    }, 20);
}

function bindEvents() {
    document.querySelectorAll('.tpl-tabs .nav-link').forEach(function(btn) {
        btn.addEventListener('click', function() { switchTab(btn.dataset.tab); });
    });

    document.getElementById('btnSave').addEventListener('click', saveTemplate);

    const versionBtn = document.getElementById('btnSaveVersion');
    if (versionBtn) versionBtn.addEventListener('click', saveVersion);

    const nameInput = document.getElementById('tplName');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            document.getElementById('displayName').textContent = nameInput.value || '<?= e($pageTitle) ?>';
            isDirty = true;
            updateStatus();
        });
    }

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveTemplate();
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Crear una plantilla no tiene endpoint JSON: se envía el formulario y el
    // controlador redirige a la edición.
    document.getElementById('templateForm').addEventListener('submit', function() {
        syncFormBeforeSend();
    });
}

// ═══════════════════════════════════════════════════════════════
// SINCRONIZACIÓN CON EL FORMULARIO
// ═══════════════════════════════════════════════════════════════
function syncFormBeforeSend() {
    if (gjsEditor) {
        const dataEl = document.getElementById('grapesjs-data');
        if (dataEl) dataEl.value = JSON.stringify(gjsEditor.getProjectData());
    }
}

function buildFormData() {
    syncFormBeforeSend();
    const form = document.getElementById('templateForm');
    const fd = new FormData(form);

    // Los textareas están tapados por CodeMirror: se envían sus valores reales.
    fd.set('content_html', cmHtml ? cmHtml.getValue() : '');
    fd.set('content_css', cmCss ? cmCss.getValue() : '');
    fd.set('content_js', cmJs ? cmJs.getValue() : '');
    if (CONFIG.canEditPhp) {
        fd.set('content_php', cmPhp ? cmPhp.getValue() : '');
    }
    // Los checkboxes sin marcar no viajan en FormData: se envían explícitos.
    fd.set('is_active', document.getElementById('isActive').checked ? '1' : '0');
    fd.set('is_default', document.getElementById('isDefault').checked ? '1' : '0');
    fd.set('create_version', document.getElementById('createVersion').checked ? '1' : '0');
    fd.set('_csrf_token', CONFIG.csrfToken);

    return fd;
}

// ═══════════════════════════════════════════════════════════════
// GUARDAR
// ═══════════════════════════════════════════════════════════════
function saveTemplate() {
    if (isSaving) return;

    // Plantilla nueva: POST normal, el controlador redirige a la edición.
    if (CONFIG.isNew) {
        syncFormBeforeSend();
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) { if (cm && cm.save) cm.save(); });
        document.getElementById('templateForm').submit();
        return;
    }

    isSaving = true;
    const btn = document.getElementById('btnSave');
    const dot = document.getElementById('statusDot');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i><span class="btn-label">Guardando…</span>';
    btn.disabled = true;
    if (dot) dot.classList.add('saving');

    fetch(CONFIG.saveUrl, {
        method: 'POST',
        body: buildFormData(),
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(r) {
        const contentType = r.headers.get('content-type') || '';
        const isJson = contentType.indexOf('application/json') !== -1;

        if (!r.ok) {
            return (isJson ? r.json() : Promise.resolve({})).then(function(body) {
                throw new Error(body.message || body.error || ('El servidor respondió ' + r.status));
            });
        }
        return isJson ? r.json() : { success: true, message: 'Plantilla guardada' };
    })
    .then(function(result) {
        if (result.success) {
            isDirty = false;
            showToast('✓ Plantilla guardada', 'success');
        } else {
            showToast('✗ ' + (result.message || result.error || 'Error al guardar'), 'error');
        }
        updateStatus();
    })
    .catch(function(err) {
        showToast('✗ ' + err.message, 'error');
    })
    .finally(function() {
        isSaving = false;
        btn.innerHTML = original;
        btn.disabled = false;
        if (dot) dot.classList.remove('saving');
    });
}

// ═══════════════════════════════════════════════════════════════
// GUARDAR VERSIÓN
// ═══════════════════════════════════════════════════════════════
function saveVersion() {
    if (!CONFIG.versionsUrl) return;
    if (!window.confirm('¿Guardar una versión del estado actual?')) return;

    // FormData con _csrf_token: CSRF::guard() lee $_POST, no cabeceras.
    const fd = new FormData();
    fd.set('_csrf_token', CONFIG.csrfToken);
    fd.set('content_html', cmHtml ? cmHtml.getValue() : '');
    fd.set('content_css', cmCss ? cmCss.getValue() : '');
    fd.set('content_js', cmJs ? cmJs.getValue() : '');
    fd.set('content_php', CONFIG.canEditPhp && cmPhp ? cmPhp.getValue() : '');

    fetch(CONFIG.versionsUrl, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) {
        if (!r.ok) throw new Error('El servidor respondió ' + r.status);
        return r.json();
    })
    .then(function(result) {
        if (result.success) {
            showToast('✓ ' + (result.message || 'Versión guardada'), 'success');
        } else {
            showToast('✗ ' + (result.error || result.message || 'No se pudo guardar la versión'), 'error');
        }
    })
    .catch(function(err) {
        showToast('✗ ' + err.message, 'error');
    });
}

// ═══════════════════════════════════════════════════════════════
// BIBLIOTECA DE MEDIOS
// ═══════════════════════════════════════════════════════════════
function loadAssets() {
    if (!CONFIG.assetsUrl || !gjsEditor) return;
    fetch(CONFIG.assetsUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.ok ? r.json() : Promise.resolve(null); })
    .then(function(result) {
        const assets = result && (result.data || result);
        if (Array.isArray(assets) && assets.length && gjsEditor) {
            gjsEditor.AssetManager.add(assets);
        }
    })
    .catch(function(err) {
        console.warn('No se pudo cargar la biblioteca de medios:', err);
    });
}

// ═══════════════════════════════════════════════════════════════
// UI: estado, tamaño y avisos
// ═══════════════════════════════════════════════════════════════
function updateStatus() {
    const state = document.getElementById('saveState');
    if (state) {
        state.innerHTML = isSaving
            ? '<i class="bi bi-cloud-arrow-up"></i> Guardando…'
            : (isDirty
                ? '<i class="bi bi-pencil-square"></i> Cambios sin guardar'
                : '<i class="bi bi-check-circle"></i> Sin cambios pendientes');
    }
    const info = document.getElementById('statusInfo');
    const cm = { visual: null, html: cmHtml, css: cmCss, js: cmJs, php: cmPhp }[currentTab];
    if (info) {
        if (cm) {
            const pos = cm.getCursor();
            info.textContent = 'Línea ' + (pos.line + 1) + ', Col ' + (pos.ch + 1);
        } else {
            info.textContent = 'Arrastra bloques desde el panel derecho del lienzo';
        }
    }
}

function updateSizeInfo() {
    try {
        const html = cmHtml ? cmHtml.getValue() : '';
        const css = cmCss ? cmCss.getValue() : '';
        const size = new Blob([html, css]).size;
        const el = document.getElementById('statusSize');
        if (el) el.textContent = (size / 1024).toFixed(1) + ' KB';
    } catch (err) { /* Blob no disponible: sin tamaño */ }
}

function showToast(msg, type) {
    type = type || 'success';
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'check-circle-fill',
        error: 'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    };
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<i class="bi bi-' + (icons[type] || 'info-circle-fill') + '"></i> ' + msg;
    container.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}
</script>
</body>
</html>
