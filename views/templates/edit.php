<?php
/**
 * Vista: editor de plantillas (templates).
 *
 * Réplica del modelo y del armazón de views/pages/builder.php:
 *   - Misma estructura de alturas: topbar fija + .builder-main con height
 *     calc(100vh - topbar) + paneles laterales + .editor-content relativo con
 *     panes absolutos. (Antes la vista envolvía el layout en un <form> visible,
 *     que rompía la cadena de flex y dejaba el lienzo y CodeMirror a 0 px.)
 *   - Mismos activos (views/partials/editor-assets-*) y misma lógica compartida
 *     (public/assets/js/grapesjs-shared.js → window.EditorKit).
 *   - Mismo flujo de guardado: formulario oculto + fetch + toasts.
 *
 * Propio de plantillas: no hay autoguardado (no existe tabla equivalente a
 * page_autosave) ni subida de imágenes (no hay endpoint para plantillas; el
 * gestor de assets muestra la biblioteca de medios ya existente).
 *
 * @package Views\Templates
 */

use App\Core\Auth;

$isNew      = empty($template['id']);
$templateId = $isNew ? 0 : (int) $template['id'];
$pageTitle  = $isNew ? 'Nueva plantilla' : 'Editar plantilla';

$t = array_merge([
    'id' => null, 'name' => '', 'description' => '',
    'content_html' => '', 'content_css' => '', 'content_js' => '',
    'content_php' => '', 'grapesjs_data' => null,
    'is_active' => 1, 'is_default' => 0, 'is_system_template' => 0,
    'created_at' => null, 'updated_at' => null,
], $template ?? []);

$canEditPhp = Auth::can('templates.edit_php');
$csrfToken  = \App\Core\CSRF::token();
$currentUser = Auth::user();

// JSON_HEX_TAG evita que un </script> dentro del contenido rompa el documento.
$jsonFlags  = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
$jsonHtml   = json_encode((string) ($t['content_html'] ?? ''), $jsonFlags);
$jsonCss    = json_encode((string) ($t['content_css'] ?? ''), $jsonFlags);
$jsonJs     = json_encode((string) ($t['content_js'] ?? ''), $jsonFlags);
$jsonPhp    = json_encode((string) ($t['content_php'] ?? ''), $jsonFlags);
$jsonGrapes = json_encode($t['grapesjs_data'] ?? null, $jsonFlags);

$saveUrl     = $isNew ? url('admin/templates') : url('admin/templates/' . $templateId);
$versionsUrl = $isNew ? '' : url('admin/templates/' . $templateId . '/versions');
$assetsUrl   = url('admin/pages/assets');
$listUrl     = url('admin/templates');

// Los mismos bloques que el builder de páginas (lista descubierta en disco).
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
<?php $editorIncludeBootstrap = false; require VIEWS_PATH . '/partials/editor-assets-head.php'; ?>

<style>
/* ═══ Variables (mismas que el builder de páginas) ═══ */
:root {
    --builder-bg: #0d1117;
    --builder-bg-2: #161b22;
    --builder-bg-3: #1c2333;
    --builder-border: #30363d;
    --builder-text: #e6edf3;
    --builder-text-muted: #8b949e;
    --builder-accent: #00d4aa;
    --builder-accent-dark: #00a88a;
    --builder-danger: #ef4444;
    --builder-warning: #f59e0b;
    --builder-success: #22c55e;
    --builder-topbar-h: 56px;
    --builder-left-w: 280px;
    --builder-right-w: 340px;
    --builder-radius: 8px;
    --builder-transition: 0.2s ease;
    --font-mono: 'JetBrains Mono', monospace;
}

* { box-sizing: border-box; }

body.builder-body {
    margin: 0;
    padding: 0;
    background: var(--builder-bg);
    color: var(--builder-text);
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    overflow: hidden;
    height: 100vh;
}

/* ═══ TOPBAR ═══ */
.builder-topbar {
    height: var(--builder-topbar-h);
    background: var(--builder-bg-2);
    border-bottom: 1px solid var(--builder-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    gap: 1rem;
    flex-shrink: 0;
}

.topbar-left, .topbar-center, .topbar-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.builder-logo {
    width: 36px;
    height: 36px;
    border-radius: var(--builder-radius);
    background: linear-gradient(135deg, var(--builder-accent), var(--builder-accent-dark));
    color: var(--builder-bg);
    display: grid;
    place-items: center;
    font-size: 1.1rem;
    text-decoration: none;
    transition: transform var(--builder-transition);
}

.builder-logo:hover { transform: scale(1.05); color: var(--builder-bg); }

.page-title-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--builder-text);
    font-weight: 600;
    min-width: 0;
}

.page-title-display i { color: var(--builder-accent); }

.page-title-display span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.page-id-badge {
    font-size: 0.7rem;
    padding: 2px 8px;
    background: var(--builder-bg-3);
    border: 1px solid var(--builder-border);
    border-radius: 999px;
    color: var(--builder-text-muted);
    font-weight: 500;
}

.autosave-status {
    font-size: 0.8rem;
    color: var(--builder-text-muted);
    font-style: italic;
    min-width: 170px;
}

/* ═══ BOTONES ═══ */
.btn-builder {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.9rem;
    border: 1px solid transparent;
    border-radius: var(--builder-radius);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    text-decoration: none;
    white-space: nowrap;
    font-family: inherit;
}

.btn-builder.btn-ghost {
    background: transparent;
    color: var(--builder-text-muted);
    border-color: var(--builder-border);
}

.btn-builder.btn-ghost:hover {
    background: var(--builder-bg-3);
    color: var(--builder-text);
    border-color: var(--builder-accent);
}

.btn-builder.btn-outline {
    background: transparent;
    color: var(--builder-accent);
    border-color: var(--builder-accent);
}

.btn-builder.btn-outline:hover {
    background: var(--builder-accent);
    color: var(--builder-bg);
}

.btn-builder.btn-primary {
    background: var(--builder-accent);
    color: var(--builder-bg);
    border-color: var(--builder-accent);
    font-weight: 600;
}

.btn-builder.btn-primary:hover {
    background: var(--builder-accent-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 212, 170, 0.3);
}

.btn-builder:disabled { opacity: 0.6; cursor: not-allowed; }

.user-chip-mini {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.7rem;
    background: var(--builder-bg-3);
    border: 1px solid var(--builder-border);
    border-radius: 999px;
    font-size: 0.8rem;
    color: var(--builder-text-muted);
}

.user-chip-mini i { color: var(--builder-accent); }

/* ═══ LAYOUT PRINCIPAL (la clave de las alturas) ═══ */
.builder-main {
    display: flex;
    height: calc(100vh - var(--builder-topbar-h));
    overflow: hidden;
}

/* ═══ SIDEBAR IZQUIERDO ═══ */
.builder-left {
    width: var(--builder-left-w);
    background: var(--builder-bg-2);
    border-right: 1px solid var(--builder-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: width var(--builder-transition);
    overflow: hidden;
}

.builder-left.collapsed { width: 0 !important; border-right: none; }

.left-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--builder-border);
    background: var(--builder-bg-3);
    flex-shrink: 0;
}

.left-header h4 {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.left-header h4 i { color: var(--builder-accent); }

.left-tabs {
    display: flex;
    border-bottom: 1px solid var(--builder-border);
    flex-shrink: 0;
}

.left-tab {
    flex: 1;
    padding: 0.6rem 0.5rem;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--builder-text-muted);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    font-family: inherit;
}

.left-tab:hover { color: var(--builder-accent); background: var(--builder-bg-3); }

.left-tab.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.left-content { flex: 1; overflow-y: auto; padding: 0.75rem; }

.left-pane { display: none; }
.left-pane.active { display: block; }

.left-section { margin-bottom: 1rem; }

.left-section-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--builder-text-muted);
    font-weight: 600;
    margin-bottom: 0.5rem;
    padding: 0 0.25rem;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0.7rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 6px;
    color: var(--builder-text);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all var(--builder-transition);
    margin-bottom: 0.4rem;
    width: 100%;
    text-align: left;
    font-family: inherit;
    text-decoration: none;
}

.quick-action:hover {
    background: var(--builder-bg-3);
    border-color: var(--builder-accent);
    color: var(--builder-accent);
}

.quick-action i { color: var(--builder-accent); width: 16px; text-align: center; }

.quick-action .shortcut {
    margin-left: auto;
    font-size: 0.65rem;
    color: var(--builder-text-muted);
    background: var(--builder-bg-3);
    padding: 1px 6px;
    border-radius: 3px;
    font-family: var(--font-mono);
}

/* Capas */
.layer-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.6rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 4px;
    margin-bottom: 0.3rem;
    font-size: 0.75rem;
    color: var(--builder-text);
    cursor: pointer;
    transition: all var(--builder-transition);
    user-select: none;
}

.layer-item:hover { background: var(--builder-bg-3); border-color: var(--builder-accent); }
.layer-item .layer-icon { color: var(--builder-accent); font-size: 0.85rem; }

.layer-item .layer-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty-state {
    text-align: center;
    padding: 1rem;
    color: var(--builder-text-muted);
    font-size: 0.75rem;
}

.empty-state i { font-size: 1.5rem; opacity: 0.3; display: block; margin-bottom: 0.4rem; }

/* ═══ PANEL DEL EDITOR ═══ */
.editor-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

.editor-tabs {
    display: flex;
    background: var(--builder-bg-2);
    border-bottom: 1px solid var(--builder-border);
    overflow-x: auto;
    flex-shrink: 0;
}

.editor-tab {
    padding: 0.75rem 1.25rem;
    border: none;
    border-bottom: 3px solid transparent;
    background: transparent;
    color: var(--builder-text-muted);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    display: flex;
    align-items: center;
    gap: 0.4rem;
    white-space: nowrap;
    font-family: inherit;
}

.editor-tab:hover { color: var(--builder-accent); background: var(--builder-bg-3); }

.editor-tab.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.editor-content {
    flex: 1;
    position: relative;
    overflow: hidden;
    min-height: 0;
}

.editor-pane {
    display: none;
    position: absolute;
    inset: 0;
}

.editor-pane.active { display: block; }

#pane-visual { background: var(--builder-bg); }

#gjs { height: 100%; width: 100%; }

/* ═══ STATUSBAR ═══ */
.statusbar {
    height: 28px;
    background: var(--builder-bg-2);
    border-top: 1px solid var(--builder-border);
    display: flex;
    align-items: center;
    padding: 0 1rem;
    font-size: 0.75rem;
    color: var(--builder-text-muted);
    gap: 0.75rem;
    flex-shrink: 0;
}

.statusbar-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--builder-success);
}

.statusbar-dot.saving { background: var(--builder-warning); animation: pulse 1s infinite; }

.statusbar-right { display: flex; align-items: center; gap: 0.75rem; margin-left: auto; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ═══ PANEL DERECHO ═══ */
.right-panel {
    width: var(--builder-right-w);
    background: var(--builder-bg-2);
    border-left: 1px solid var(--builder-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: width var(--builder-transition);
    overflow: hidden;
}

.right-panel.collapsed { width: 0 !important; border-left: none; }

.right-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--builder-border);
    background: var(--builder-bg-3);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.right-header h3 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.right-header h3 i { color: var(--builder-accent); }

.right-close {
    background: transparent;
    border: none;
    color: var(--builder-text-muted);
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all var(--builder-transition);
}

.right-close:hover { background: rgba(239, 68, 68, 0.1); color: var(--builder-danger); }

.right-content { flex: 1; overflow-y: auto; padding: 1rem; }

.config-section { margin-bottom: 1rem; }

.config-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--builder-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.4rem;
}

.config-input, .config-textarea {
    width: 100%;
    padding: 0.55rem 0.75rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 6px;
    color: var(--builder-text);
    font-size: 0.85rem;
    font-family: inherit;
    transition: all var(--builder-transition);
}

.config-input:focus, .config-textarea:focus {
    outline: none;
    border-color: var(--builder-accent);
    box-shadow: 0 0 0 3px rgba(0, 212, 170, 0.1);
}

.config-textarea { resize: vertical; min-height: 60px; }

.config-help {
    display: block;
    font-size: 0.7rem;
    color: var(--builder-text-muted);
    margin-top: 0.25rem;
    font-style: italic;
}

.config-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--builder-text);
    padding: 0.4rem 0;
}

.config-checkbox input {
    width: 16px;
    height: 16px;
    accent-color: var(--builder-accent);
    cursor: pointer;
}

.config-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    padding: 0.3rem 0;
    color: var(--builder-text-muted);
}

.config-divider { border: none; border-top: 1px solid var(--builder-border); margin: 1rem 0; }

/* ═══ TOASTS ═══ */
.toast-container {
    position: fixed;
    top: 70px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 360px;
}

.toast {
    padding: 0.75rem 1rem;
    background: var(--builder-bg-3);
    border: 1px solid var(--builder-border);
    border-left: 4px solid var(--builder-success);
    border-radius: var(--builder-radius);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    color: var(--builder-text);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.toast.error { border-left-color: var(--builder-danger); }
.toast.warning { border-left-color: var(--builder-warning); }
.toast.info { border-left-color: #3b82f6; }

/* ═══ GRAPESJS / CODEMIRROR ═══ */
.gjs-one-bg { background-color: var(--builder-bg-2) !important; }
.gjs-two-bg { background-color: var(--builder-bg-3) !important; }
.gjs-three-bg { background-color: var(--builder-bg) !important; }
.gjs-four-bg { background-color: var(--builder-accent) !important; }
.gjs-one-color { color: var(--builder-text) !important; }
.gjs-two-color { color: var(--builder-text-muted) !important; }
.gjs-three-color { color: var(--builder-accent) !important; }
.gjs-four-color { color: var(--builder-bg) !important; }

.gjs-cv-canvas { background: var(--builder-bg) !important; }
.gjs-selected { outline: 2px solid var(--builder-accent) !important; }
.gjs-pn-panel { background-color: var(--builder-bg-2) !important; border-color: var(--builder-border) !important; }

.CodeMirror { height: 100% !important; font-family: var(--font-mono); font-size: 13px; }
.CodeMirror-gutters { border-right: 1px solid var(--builder-border); }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 1200px) {
    :root { --builder-left-w: 240px; --builder-right-w: 300px; }
}

@media (max-width: 992px) {
    :root { --builder-left-w: 220px; --builder-right-w: 280px; }
}

@media (max-width: 768px) {
    .builder-left, .right-panel {
        position: absolute;
        top: var(--builder-topbar-h);
        bottom: 0;
        z-index: 100;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
    }
    .builder-left { left: 0; }
    .right-panel { right: 0; }
    .btn-label { display: none; }
    .user-chip-mini span { display: none; }
    .autosave-status { display: none; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition-duration: .001ms !important;
        animation-duration: .001ms !important;
    }
}
</style>
</head>
<body class="builder-body">

<!-- ═══ TOPBAR ═══ -->
<div class="builder-topbar">
    <div class="topbar-left">
        <a href="<?= e($listUrl) ?>" class="builder-logo" title="Volver a plantillas">
            <i class="bi bi-arrow-left"></i>
        </a>

        <button type="button" class="btn-builder btn-ghost active" id="toggleLeft" title="Mostrar/ocultar panel izquierdo">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>

        <div class="page-title-display">
            <i class="bi bi-layout-text-window"></i>
            <span id="displayName"><?= e($t['name'] !== '' ? $t['name'] : $pageTitle) ?></span>
            <?php if (!$isNew): ?>
                <span class="page-id-badge">#<?= $templateId ?></span>
                <?php if (!empty($t['is_system_template'])): ?>
                    <span class="page-id-badge">sistema</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="topbar-center">
        <span class="autosave-status" id="saveState"></span>
    </div>

    <div class="topbar-right">
        <?php if (!$isNew): ?>
            <a href="<?= e($versionsUrl) ?>" class="btn-builder btn-ghost" title="Versiones de la plantilla">
                <i class="bi bi-clock-history"></i><span class="btn-label">Versiones</span>
            </a>
        <?php endif; ?>

        <button type="button" class="btn-builder btn-primary" id="saveBtn">
            <i class="bi bi-save"></i><span class="btn-label">Guardar</span>
        </button>

        <button type="button" class="btn-builder btn-ghost" id="toggleRight" title="Mostrar/ocultar configuración">
            <i class="bi bi-layout-sidebar-reverse"></i>
        </button>

        <div class="user-chip-mini">
            <i class="bi bi-person-circle"></i>
            <span><?= e($currentUser['username'] ?? 'Usuario') ?></span>
        </div>
    </div>
</div>

<!-- ═══ LAYOUT ═══ -->
<div class="builder-main">

    <!-- ── Panel izquierdo: acciones y capas ── -->
    <aside class="builder-left" id="builderLeft">
        <div class="left-header">
            <h4><i class="bi bi-tools"></i> Herramientas</h4>
        </div>

        <div class="left-tabs">
            <button type="button" class="left-tab active" data-left-tab="actions">
                <i class="bi bi-lightning"></i> Acciones
            </button>
            <button type="button" class="left-tab" data-left-tab="layers">
                <i class="bi bi-layers"></i> Capas
            </button>
        </div>

        <div class="left-content">
            <div class="left-pane active" data-left-pane="actions">
                <div class="left-section">
                    <div class="left-section-title">Atajos</div>
                    <button type="button" class="quick-action" id="quickSave">
                        <i class="bi bi-save"></i>
                        <span>Guardar plantilla</span>
                        <span class="shortcut">Ctrl+S</span>
                    </button>
                    <?php if (!$isNew): ?>
                    <button type="button" class="quick-action" id="quickSaveVersion">
                        <i class="bi bi-clock-history"></i>
                        <span>Guardar versión</span>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="quick-action" data-goto-tab="html">
                        <i class="bi bi-code-slash"></i>
                        <span>Ver código HTML</span>
                    </button>
                    <button type="button" class="quick-action" data-goto-tab="css">
                        <i class="bi bi-palette2"></i>
                        <span>Ver código CSS</span>
                    </button>
                </div>

                <div class="left-section">
                    <div class="left-section-title">Dispositivos</div>
                    <button type="button" class="quick-action" data-device="desktop">
                        <i class="bi bi-display"></i><span>Desktop</span>
                    </button>
                    <button type="button" class="quick-action" data-device="tablet">
                        <i class="bi bi-tablet"></i><span>Tablet (768px)</span>
                    </button>
                    <button type="button" class="quick-action" data-device="mobile">
                        <i class="bi bi-phone"></i><span>Móvil (375px)</span>
                    </button>
                </div>

                <div class="left-section">
                    <div class="left-section-title">Enlaces</div>
                    <a href="<?= e($listUrl) ?>" class="quick-action">
                        <i class="bi bi-arrow-left"></i><span>Volver a plantillas</span>
                    </a>
                    <?php if (!$isNew): ?>
                    <a href="<?= e($versionsUrl) ?>" class="quick-action">
                        <i class="bi bi-clock-history"></i><span>Ver versiones</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="left-pane" data-left-pane="layers">
                <div class="left-section">
                    <div class="left-section-title">Componentes del lienzo</div>
                    <div id="layersList">
                        <div class="empty-state">
                            <i class="bi bi-layers"></i>
                            Sin componentes todavía
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── Editor ── -->
    <div class="editor-panel">
        <div class="editor-tabs">
            <button type="button" class="editor-tab active" data-tab="visual">
                <i class="bi bi-palette"></i> Visual
            </button>
            <button type="button" class="editor-tab" data-tab="html">
                <i class="bi bi-code-slash"></i> HTML
            </button>
            <button type="button" class="editor-tab" data-tab="css">
                <i class="bi bi-palette2"></i> CSS
            </button>
            <button type="button" class="editor-tab" data-tab="js">
                <i class="bi bi-braces"></i> JS
            </button>
            <?php if ($canEditPhp): ?>
            <button type="button" class="editor-tab" data-tab="php">
                <i class="bi bi-filetype-php"></i> PHP
            </button>
            <?php endif; ?>
        </div>

        <div class="editor-content">
            <div class="editor-pane active" id="pane-visual">
                <div id="gjs"></div>
            </div>
            <div class="editor-pane" id="pane-html">
                <textarea id="editor-html"></textarea>
            </div>
            <div class="editor-pane" id="pane-css">
                <textarea id="editor-css"></textarea>
            </div>
            <div class="editor-pane" id="pane-js">
                <textarea id="editor-js"></textarea>
            </div>
            <?php if ($canEditPhp): ?>
            <div class="editor-pane" id="pane-php">
                <textarea id="editor-php"></textarea>
            </div>
            <?php endif; ?>
        </div>

        <div class="statusbar">
            <span class="statusbar-dot" id="statusDot"></span>
            <span id="statusMode">Editor visual</span>
            <span>|</span>
            <span id="statusInfo"></span>
            <span class="statusbar-right">
                <span id="statusSize">0 KB</span>
                <span>|</span>
                <span><?= $isNew ? 'Plantilla nueva' : 'Plantilla #' . $templateId ?></span>
            </span>
        </div>
    </div>

    <!-- ── Panel derecho: configuración ── -->
    <aside class="right-panel" id="rightPanel">
        <div class="right-header">
            <h3><i class="bi bi-gear"></i> Configuración</h3>
            <button type="button" class="right-close" id="closeRight">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="right-content">
            <div class="config-section">
                <label class="config-label" for="tplName">Nombre</label>
                <input type="text" id="tplName" class="config-input"
                       value="<?= e($t['name']) ?>" placeholder="Nombre de la plantilla">
            </div>

            <div class="config-section">
                <label class="config-label" for="tplDescription">Descripción</label>
                <textarea id="tplDescription" class="config-textarea" rows="3"
                          placeholder="Para qué sirve esta plantilla"><?= e($t['description']) ?></textarea>
            </div>

            <div class="config-section">
                <label class="config-checkbox">
                    <input type="checkbox" id="tplActive" <?= !empty($t['is_active']) ? 'checked' : '' ?>>
                    <span>Plantilla activa</span>
                </label>
                <label class="config-checkbox">
                    <input type="checkbox" id="tplDefault" <?= !empty($t['is_default']) ? 'checked' : '' ?>>
                    <span>Usar por defecto</span>
                </label>
                <label class="config-checkbox">
                    <input type="checkbox" id="tplCreateVersion" checked>
                    <span>Guardar versión al guardar</span>
                </label>
            </div>

            <?php if (!$isNew): ?>
            <hr class="config-divider">
            <div class="config-section">
                <div class="config-meta">
                    <span>ID</span><span>#<?= $templateId ?></span>
                </div>
                <div class="config-meta">
                    <span>Tipo</span>
                    <span><?= !empty($t['is_system_template']) ? 'Del sistema' : 'Personalizada' ?></span>
                </div>
                <div class="config-meta">
                    <span>Creada</span>
                    <span><?= e(substr((string) ($t['created_at'] ?? '—'), 0, 16)) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <hr class="config-divider">
            <div class="config-section">
                <label class="config-label"><i class="bi bi-images"></i> Medios</label>
                <small class="config-help">
                    El gestor de bloques e imágenes usa la biblioteca de medios existente.
                    Para subir imágenes nuevas, usa el editor de páginas.
                </small>
            </div>
        </div>
    </aside>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══ FORMULARIO OCULTO PARA GUARDAR (mismo patrón que el builder) ═══ -->
<form id="saveForm" style="display:none" action="<?= e($saveUrl) ?>" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="name" id="formName">
    <input type="hidden" name="description" id="formDescription">
    <input type="hidden" name="content_html" id="formHtml">
    <input type="hidden" name="content_css" id="formCss">
    <input type="hidden" name="content_js" id="formJs">
    <input type="hidden" name="content_php" id="formPhp">
    <input type="hidden" name="grapesjs_data" id="formGrapesData">
    <input type="hidden" name="is_active" id="formActive">
    <input type="hidden" name="is_default" id="formDefault">
    <input type="hidden" name="create_version" id="formCreateVersion">
</form>

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
    canEditPhp: <?= $canEditPhp ? 'true' : 'false' ?>,
    defaultName: <?= json_encode($pageTitle, JSON_UNESCAPED_UNICODE) ?>
};

const templateData = {
    html: <?= $jsonHtml ?>,
    css: <?= $jsonCss ?>,
    js: <?= $jsonJs ?>,
    php: <?= $jsonPhp ?>,
    grapes: <?= $jsonGrapes ?>
};

const BLOCK_SCRIPTS = <?= json_encode($blockUrls, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

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
let leftOpen = true;
let rightOpen = true;

const tabLabels = {
    visual: 'Editor visual', html: 'HTML', css: 'CSS', js: 'JavaScript', php: 'PHP'
};

// ═══════════════════════════════════════════════════════════════
// ARRANQUE (cada subsistema aislado, como en el builder)
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

    safeInit('código', initCodeMirror);
    safeInit('lienzo visual', initGrapesJS);
    safeInit('eventos', bindEvents);

    if (failed.length) {
        showToast('✗ No se pudo iniciar: ' + failed.join(', '), 'error');
    }

    updateSaveState();
    updateSizeInfo();
    console.log('✅ Editor de plantillas inicializado');
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

    // Contenido inicial: aquí es donde se "carga" lo guardado.
    cmHtml.setValue(templateData.html || '');
    cmCss.setValue(templateData.css || '');
    cmJs.setValue(templateData.js || '');
    if (cmPhp) cmPhp.setValue(templateData.php || '');

    [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) {
        if (!cm) return;
        cm.on('change', function() {
            isDirty = true;
            updateSaveState();
            updateSizeInfo();
        });
        cm.on('cursorActivity', function() {
            if (getActiveCM() === cm) updateStatusInfo();
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
        console.warn('Plugins de GrapesJS no disponibles (se omiten): ' + pluginSelection.skipped.join(', '));
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
            // Biblioteca de medios compartida con el editor de páginas.
            assets: [],
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
        updateSaveState();
        if (isSyncing) return;
        isSyncing = true;
        try {
            cmHtml.setValue(gjsEditor.getHtml());
            cmCss.setValue(gjsEditor.getCss());
            updateSizeInfo();
            updateLayers();
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

    gjsEditor.on('component:selected', function() { updateLayers(); });

    // Los ficheros de bloques registran con `gjsEditor.BlockManager.add(...)`:
    // hay que inyectarlos con el editor ya creado (loader compartido).
    EditorKit.loadBlockScripts(BLOCK_SCRIPTS, gjsEditor);

    loadAssets();
    updateLayers();

    // El lienzo se creó con el panel visible; si arranca oculto, refrescar.
    setTimeout(function() { if (gjsEditor) gjsEditor.refresh(); }, 150);

    return gjsEditor;
}

// ═══════════════════════════════════════════════════════════════
// CAPAS (misma idea que el builder)
// ═══════════════════════════════════════════════════════════════
function updateLayers() {
    if (!gjsEditor) return;
    const wrapper = gjsEditor.getWrapper();
    const list = document.getElementById('layersList');
    if (!wrapper || !list) return;

    const items = [];
    const traverse = function(component, depth) {
        if (depth > 4) return;
        const tag = component.get('tagName') || 'div';
        const classes = (component.getClasses() || []).join('.');
        items.push({
            id: component.getId(),
            name: tag + (classes ? '.' + classes : ''),
            depth: depth
        });
        component.components().forEach(function(child) { traverse(child, depth + 1); });
    };
    wrapper.components().forEach(function(child) { traverse(child, 0); });

    if (!items.length) {
        list.innerHTML = '<div class="empty-state"><i class="bi bi-layers"></i>Sin componentes todavía</div>';
        return;
    }

    let html = '';
    items.slice(0, 40).forEach(function(item) {
        html += '<div class="layer-item" data-component-id="' + item.id + '" style="padding-left:' + (6 + item.depth * 12) + 'px">'
              + '<i class="bi bi-square layer-icon"></i>'
              + '<span class="layer-name">' + escapeHtml(item.name) + '</span>'
              + '</div>';
    });
    list.innerHTML = html;

    list.querySelectorAll('.layer-item').forEach(function(el) {
        el.addEventListener('click', function() {
            const id = el.dataset.componentId;
            if (!id || !gjsEditor) return;
            const found = gjsEditor.DomComponents.getComponents().filter(function(c) {
                return c.getId() === id;
            })[0];
            if (found) gjsEditor.select(found);
        });
    });
}

// ═══════════════════════════════════════════════════════════════
// EVENTOS
// ═══════════════════════════════════════════════════════════════
function bindEvents() {
    document.querySelectorAll('.editor-tab').forEach(function(tab) {
        tab.addEventListener('click', function() { switchTab(tab.dataset.tab); });
    });

    document.querySelectorAll('.left-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.left-tab').forEach(function(t) {
                t.classList.toggle('active', t === tab);
            });
            document.querySelectorAll('.left-pane').forEach(function(pane) {
                pane.classList.toggle('active', pane.dataset.leftPane === tab.dataset.leftTab);
            });
        });
    });

    document.getElementById('saveBtn').addEventListener('click', saveTemplate);

    const quickSave = document.getElementById('quickSave');
    if (quickSave) quickSave.addEventListener('click', saveTemplate);

    const quickVersion = document.getElementById('quickSaveVersion');
    if (quickVersion) quickVersion.addEventListener('click', saveVersion);

    document.querySelectorAll('[data-goto-tab]').forEach(function(el) {
        el.addEventListener('click', function() { switchTab(el.dataset.gotoTab); });
    });

    document.querySelectorAll('[data-device]').forEach(function(el) {
        el.addEventListener('click', function() {
            setDevice(el.dataset.device);
        });
    });

    document.getElementById('toggleLeft').addEventListener('click', function() {
        leftOpen = !leftOpen;
        document.getElementById('builderLeft').classList.toggle('collapsed', !leftOpen);
        this.classList.toggle('active', leftOpen);
        refreshCanvas();
    });

    document.getElementById('toggleRight').addEventListener('click', function() {
        rightOpen = !rightOpen;
        document.getElementById('rightPanel').classList.toggle('collapsed', !rightOpen);
        refreshCanvas();
    });

    document.getElementById('closeRight').addEventListener('click', function() {
        rightOpen = false;
        document.getElementById('rightPanel').classList.add('collapsed');
        refreshCanvas();
    });

    const nameInput = document.getElementById('tplName');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            document.getElementById('displayName').textContent = nameInput.value || CONFIG.defaultName;
            isDirty = true;
            updateSaveState();
        });
    }

    const descInput = document.getElementById('tplDescription');
    if (descInput) {
        descInput.addEventListener('input', function() { isDirty = true; updateSaveState(); });
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
}

function refreshCanvas() {
    setTimeout(function() {
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) { if (cm && cm.refresh) cm.refresh(); });
        if (gjsEditor) gjsEditor.refresh();
    }, 250);
}

function setDevice(device) {
    if (!gjsEditor) return;
    gjsEditor.setDevice(device.charAt(0).toUpperCase() + device.slice(1));
    showToast('Vista: ' + device, 'info');
}

// ═══════════════════════════════════════════════════════════════
// PESTAÑAS
// ═══════════════════════════════════════════════════════════════
function switchTab(tab) {
    if (tab === currentTab) {
        refreshTab(tab);
        return;
    }
    currentTab = tab;

    document.querySelectorAll('.editor-tab').forEach(function(t) {
        t.classList.toggle('active', t.dataset.tab === tab);
    });
    document.querySelectorAll('.editor-pane').forEach(function(pane) {
        pane.classList.toggle('active', pane.id === 'pane-' + tab);
    });

    document.getElementById('statusMode').textContent = tabLabels[tab] || tab;
    refreshTab(tab);
    updateStatusInfo();
}

function refreshTab(tab) {
    setTimeout(function() {
        if (tab === 'html' && cmHtml) cmHtml.refresh();
        if (tab === 'css' && cmCss) cmCss.refresh();
        if (tab === 'js' && cmJs) cmJs.refresh();
        if (tab === 'php' && cmPhp) cmPhp.refresh();
        if (tab === 'visual' && gjsEditor) gjsEditor.refresh();
    }, 20);
}

function getActiveCM() {
    return { visual: null, html: cmHtml, css: cmCss, js: cmJs, php: cmPhp }[currentTab];
}

// ═══════════════════════════════════════════════════════════════
// GUARDAR
// ═══════════════════════════════════════════════════════════════
function saveTemplate() {
    if (isSaving) return;

    // Rellenar el formulario oculto con el estado actual (texto + proyecto).
    document.getElementById('formName').value = document.getElementById('tplName').value || CONFIG.defaultName;
    document.getElementById('formDescription').value = document.getElementById('tplDescription').value;
    document.getElementById('formHtml').value = cmHtml ? cmHtml.getValue() : '';
    document.getElementById('formCss').value = cmCss ? cmCss.getValue() : '';
    document.getElementById('formJs').value = cmJs ? cmJs.getValue() : '';
    document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
    document.getElementById('formGrapesData').value = gjsEditor ? JSON.stringify(gjsEditor.getProjectData()) : '';
    document.getElementById('formActive').value = document.getElementById('tplActive').checked ? '1' : '0';
    document.getElementById('formDefault').value = document.getElementById('tplDefault').checked ? '1' : '0';
    document.getElementById('formCreateVersion').value = document.getElementById('tplCreateVersion').checked ? '1' : '0';

    const form = document.getElementById('saveForm');

    // Plantilla nueva: POST normal (el controlador redirige a la edición, que es
    // donde ya existe id para el AJAX y las versiones).
    if (CONFIG.isNew) {
        form.submit();
        return;
    }

    isSaving = true;
    const btn = document.getElementById('saveBtn');
    const dot = document.getElementById('statusDot');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i><span class="btn-label">Guardando…</span>';
    btn.disabled = true;
    if (dot) dot.classList.add('saving');
    updateSaveState();

    fetch(CONFIG.saveUrl, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(r) {
        const contentType = r.headers.get('content-type') || '';
        const isJson = contentType.indexOf('application/json') !== -1;

        // Un 4xx/5xx no es un guardado correcto, aunque traiga HTML.
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
        updateSaveState();
    })
    .catch(function(err) {
        showToast('✗ ' + err.message, 'error');
    })
    .finally(function() {
        isSaving = false;
        btn.innerHTML = original;
        btn.disabled = false;
        if (dot) dot.classList.remove('saving');
        updateSaveState();
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
// BIBLIOTECA DE MEDIOS (solo lectura)
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
// UI: estado, tamaño, posición y avisos
// ═══════════════════════════════════════════════════════════════
function updateSaveState() {
    const el = document.getElementById('saveState');
    if (!el) return;
    if (isSaving) {
        el.innerHTML = '<i class="bi bi-cloud-arrow-up" style="color:var(--builder-warning)"></i> Guardando…';
    } else if (isDirty) {
        el.innerHTML = '<i class="bi bi-pencil-square" style="color:var(--builder-warning)"></i> Cambios sin guardar';
    } else {
        el.innerHTML = '<i class="bi bi-check-circle" style="color:var(--builder-success)"></i> Todo guardado';
    }
}

function updateSizeInfo() {
    const el = document.getElementById('statusSize');
    if (!el) return;
    try {
        const html = cmHtml ? cmHtml.getValue() : '';
        const css = cmCss ? cmCss.getValue() : '';
        el.textContent = (new Blob([html, css]).size / 1024).toFixed(1) + ' KB';
    } catch (err) {
        el.textContent = '—';
    }
}

function updateStatusInfo() {
    const el = document.getElementById('statusInfo');
    if (!el) return;
    const cm = getActiveCM();
    if (cm) {
        const pos = cm.getCursor();
        el.textContent = 'Línea ' + (pos.line + 1) + ', Col ' + (pos.ch + 1);
    } else {
        el.textContent = 'Arrastra bloques al lienzo';
    }
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
</body>
</html>
