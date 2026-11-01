<?php
/**
 * Builder Visual Avanzado — PePiuoX Editor v3
 * Ubicación: views/pages/builder.php
 *
 * Variables esperadas desde PagesController::builder():
 *   $page, $templates, $existingAssets, $seo
 */
use App\Core\Auth;

// ═══════════════════════════════════════════════════════════════════
// VERIFICACIÓN DE PERMISOS
// ═══════════════════════════════════════════════════════════════════
if (!Auth::can('pages.edit')) {
    \App\Core\Response::abort(403, 'No tienes permisos para usar el builder visual.');
}

// ═══════════════════════════════════════════════════════════════════
// DATOS DE LA PÁGINA (con fallbacks seguros)
// ═══════════════════════════════════════════════════════════════════
$page = $page ?? ['id' => 0, 'title' => 'Nueva página', 'slug' => '', 'description' => '',
                  'content_html' => '', 'content_css' => '', 'content_js' => '',
                  'content_php' => '', 'grapesjs_data' => null, 'status' => 'draft',
                  'startpage' => 0, 'template_id' => null, 'created_at' => null, 'updated_at' => null];
$templates = $templates ?? [];
$existingAssets = $existingAssets ?? [];
$seo = $seo ?? ['basic' => [], 'og' => [], 'twitter' => []];

$pageTitle = 'Builder: ' . ($page['title'] ?? 'Nueva página');

// Codificar contenido para JavaScript
// JSON_HEX_TAG evita que un </script> dentro del contenido rompa el documento.
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
$jsonHtml = json_encode($page['content_html'] ?? '<div style="padding:40px;text-align:center;"><h1>Mi Nueva Página</h1><p>Comienza a editar tu contenido aquí.</p></div>', $jsonFlags);
$jsonCss  = json_encode($page['content_css'] ?? "body {\n  font-family: 'Inter', sans-serif;\n  margin: 0;\n  padding: 0;\n  background: #ffffff;\n  color: #333333;\n}", $jsonFlags);
$jsonPhp  = json_encode($page['content_php'] ?? '', $jsonFlags);
$jsonJs   = json_encode($page['content_js'] ?? '', $jsonFlags);
$jsonGrapes = json_encode($page['grapesjs_data'] ?? null, $jsonFlags);
$jsonAssets = json_encode($existingAssets, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

// URLs del sistema
$uploadUrl  = url('admin/pages/' . $page['id'] . '/builder/upload');
$assetsUrl  = url('admin/pages/assets');
$saveUrl    = url('admin/pages/' . $page['id']);
$previewUrl = url($page['slug'] ?? '');
$currentUser = Auth::user();
$csrfToken = function_exists('csrf_token') ? csrf_token() : \App\Core\CSRF::token();

// ═══════════════════════════════════════════════════════════════════
// BLOQUES DE GRAPESJS (public/assets/js/blocks/*.js)
// Se listan desde el disco para que añadir un fichero no obligue a tocar esta
// vista. Se cargan DESPUÉS de crear el editor, porque cada fichero registra sus
// bloques con `gjsEditor.BlockManager.add(...)` y necesita la variable ya
// asignada (si se cargan como <script> suelto, gjsEditor sigue siendo null y
// todos fallan con "Cannot read properties of null").
// Exclusiones, con motivo:
//   - bs-blocks.js: duplica 5 ids de blocks-bs.js (bs-navbar, bs-hero,
//     bs-footer, bs-accordion, bs-alert) con contenido distinto.
//   - template.js: su único bloque es un layout con <script> dentro del
//     `content`, y ese contenido se ejecuta en el lienzo del editor.
// ═══════════════════════════════════════════════════════════════════
$blockUrls = editor_block_urls();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · PePiuoX Editor</title>

<!-- Activos del editor (GrapesJS + CodeMirror): los mismos que usa el editor
     de plantillas. Fuente única: views/partials/editor-assets-head.php -->
<?php $editorIncludeBootstrap = false; require VIEWS_PATH . '/partials/editor-assets-head.php'; ?>

<!-- ═══════════════════════════════════════════════════════════════
CSS DEL BUILDER (inline)
═══════════════════════════════════════════════════════════════ -->
<style>
/* ═══ Variables ═══ */
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

.builder-logo:hover { transform: scale(1.05); }

.page-title-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--builder-text);
    font-weight: 600;
}

.page-title-display i { color: var(--builder-accent); }

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
    min-width: 150px;
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

.btn-builder:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-toggle-left {
    position: relative;
}

.btn-toggle-left.active {
    background: var(--builder-accent);
    color: var(--builder-bg);
    border-color: var(--builder-accent);
}

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

/* ═══ LAYOUT PRINCIPAL ═══ */
.builder-main {
    display: flex;
    height: calc(100vh - var(--builder-topbar-h));
    overflow: hidden;
}

/* ═══════════════════════════════════════════════════════════════
🆕 SIDEBAR IZQUIERDO (con toggle)
═══════════════════════════════════════════════════════════════ */
.builder-left {
    width: var(--builder-left-w);
    background: var(--builder-bg-2);
    border-right: 1px solid var(--builder-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    order: 0;
    transition: width var(--builder-transition), margin var(--builder-transition);
    overflow: hidden;
}

.builder-left.collapsed {
    width: 0 !important;
    margin-right: 0;
    border-right: none;
}

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
    color: var(--builder-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.left-header h4 i { color: var(--builder-accent); }

/* Pestañas del sidebar izquierdo */
.left-tabs {
    display: flex;
    border-bottom: 1px solid var(--builder-border);
    background: var(--builder-bg-2);
    flex-shrink: 0;
}

.left-tab {
    flex: 1;
    padding: 0.6rem 0.5rem;
    background: transparent;
    border: none;
    color: var(--builder-text-muted);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    border-bottom: 2px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    font-family: inherit;
}

.left-tab:hover {
    color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.left-tab.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.left-content {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}

.left-pane {
    display: none;
}

.left-pane.active {
    display: block;
}

/* Pestaña de bloques: sin relleno y con scroll propio, para que la biblioteca
   ocupe todo el alto disponible del panel. */
.left-pane[data-left-pane="blocks"] {
    padding: 0;
    height: 100%;
    overflow: hidden;
}

.left-pane[data-left-pane="blocks"].active {
    display: flex;
    flex-direction: column;
}

/* ═══ Componentes del sidebar izquierdo ═══ */
.left-section {
    margin-bottom: 1rem;
}

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
}

.quick-action:hover {
    background: var(--builder-bg-3);
    border-color: var(--builder-accent);
    color: var(--builder-accent);
}

.quick-action i {
    color: var(--builder-accent);
    width: 16px;
    text-align: center;
}

.quick-action .shortcut {
    margin-left: auto;
    font-size: 0.65rem;
    color: var(--builder-text-muted);
    background: var(--builder-bg-3);
    padding: 1px 6px;
    border-radius: 3px;
    font-family: var(--font-mono);
}

/* ═══════════════════════════════════════════════════════════════
🆕 LISTA DE CAPAS CON DRAG & DROP
═══════════════════════════════════════════════════════════════ */
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
    cursor: grab;
    transition: all var(--builder-transition);
    user-select: none;
}

.layer-item:active {
    cursor: grabbing;
}

.layer-item:hover {
    background: var(--builder-bg-3);
    border-color: var(--builder-accent);
}

.layer-item.dragging {
    opacity: 0.5;
    border-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.layer-item.drag-over {
    border-color: var(--builder-accent);
    background: rgba(0, 212, 170, 0.1);
    transform: scale(1.02);
}

.layer-drag-handle {
    color: var(--builder-text-muted);
    cursor: grab;
    font-size: 0.9rem;
}

.layer-item .layer-icon {
    color: var(--builder-accent);
    font-size: 0.85rem;
}

.layer-item .layer-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.layer-item .layer-actions {
    display: flex;
    gap: 0.2rem;
    opacity: 0;
    transition: opacity var(--builder-transition);
}

.layer-item:hover .layer-actions {
    opacity: 1;
}

.layer-item .layer-actions button {
    background: transparent;
    border: none;
    color: var(--builder-text-muted);
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.75rem;
}

.layer-item .layer-actions button:hover {
    background: var(--builder-bg-3);
    color: var(--builder-accent);
}

/* Historial */
.history-item {
    padding: 0.5rem 0.6rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 4px;
    margin-bottom: 0.3rem;
    font-size: 0.75rem;
    color: var(--builder-text);
}

.history-item .history-time {
    font-size: 0.65rem;
    color: var(--builder-text-muted);
    margin-bottom: 0.2rem;
}

.history-item .history-note {
    color: var(--builder-text);
    line-height: 1.3;
}

/* ═══ PANEL DEL EDITOR ═══ */
.editor-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    order: 1;
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
    background: transparent;
    color: var(--builder-text-muted);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    white-space: nowrap;
    font-family: inherit;
}

.editor-tab:hover {
    color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.editor-tab.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.editor-content {
    flex: 1;
    position: relative;
    overflow: hidden;
}

.editor-pane {
    display: none;
    position: absolute;
    inset: 0;
}

.editor-pane.active { display: block; }

#pane-visual { background: var(--builder-bg); }

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

.statusbar-dot.saving {
    background: var(--builder-warning);
    animation: pulse 1s infinite;
}

.statusbar-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: auto;
}

/* ═══════════════════════════════════════════════════════════════
🆕 PANEL DERECHO UNIFICADO (Configuración + Comentarios)
═══════════════════════════════════════════════════════════════ */
.right-panel {
    width: var(--builder-right-w);
    background: var(--builder-bg-2);
    border-left: 1px solid var(--builder-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    order: 2;
    transition: width var(--builder-transition), margin var(--builder-transition);
    overflow: hidden;
}

.right-panel.collapsed {
    width: 0 !important;
    margin-left: 0;
    border-left: none;
}

.right-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--builder-border);
    background: var(--builder-bg-3);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.right-header h3 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--builder-text);
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

.right-close:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--builder-danger);
}

/* Pestañas del panel derecho */
.right-tabs {
    display: flex;
    border-bottom: 1px solid var(--builder-border);
    background: var(--builder-bg-2);
    flex-shrink: 0;
}

.right-tab {
    flex: 1;
    padding: 0.7rem 0.5rem;
    background: transparent;
    border: none;
    color: var(--builder-text-muted);
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--builder-transition);
    border-bottom: 2px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    font-family: inherit;
    position: relative;
}

.right-tab:hover {
    color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.right-tab.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
    background: var(--builder-bg-3);
}

.right-tab .tab-badge {
    background: var(--builder-accent);
    color: var(--builder-bg);
    font-size: 0.6rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    min-width: 18px;
    text-align: center;
}

.right-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.right-pane {
    display: none;
}

.right-pane.active {
    display: block;
}

/* Configuración */
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

.config-input, .config-textarea, .config-select {
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

.config-input:focus, .config-textarea:focus, .config-select:focus {
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

.config-divider {
    border: none;
    border-top: 1px solid var(--builder-border);
    margin: 1rem 0;
}

/* ═══════════════════════════════════════════════════════════════
🆕 ESTILOS PARA COMENTARIOS (integrados en el panel derecho)
═══════════════════════════════════════════════════════════════ */
.comments-toolbar {
    display: flex;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
}

.comments-toolbar button {
    flex: 1;
    padding: 0.4rem 0.6rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 6px;
    color: var(--builder-text-muted);
    font-size: 0.7rem;
    cursor: pointer;
    transition: all var(--builder-transition);
    font-family: inherit;
}

.comments-toolbar button:hover {
    background: var(--builder-bg-3);
    color: var(--builder-accent);
    border-color: var(--builder-accent);
}

.comments-toolbar button.active {
    background: var(--builder-accent);
    color: var(--builder-bg);
    border-color: var(--builder-accent);
}

.comment-item {
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.6rem;
    transition: all var(--builder-transition);
    cursor: pointer;
    position: relative;
}

.comment-item:hover {
    border-color: var(--builder-accent);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.comment-item.active {
    border-color: var(--builder-accent);
    box-shadow: 0 0 0 2px rgba(0, 212, 170, 0.2);
}

.comment-item.resolved {
    opacity: 0.6;
}

.comment-item.resolved .comment-message {
    text-decoration: line-through;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
}

.comment-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
}

.comment-meta {
    flex: 1;
    min-width: 0;
}

.comment-author {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--builder-text);
}

.comment-time {
    font-size: 0.65rem;
    color: var(--builder-text-muted);
}

.comment-priority {
    padding: 1px 6px;
    border-radius: 999px;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
}

.priority-low { background: rgba(139,148,158,0.15); color: var(--builder-text-muted); }
.priority-medium { background: rgba(59,130,246,0.15); color: var(--info); }
.priority-high { background: rgba(245,158,11,0.15); color: var(--warning); }
.priority-urgent { background: rgba(239,68,68,0.15); color: var(--danger); }

.comment-message {
    font-size: 0.8rem;
    color: var(--builder-text-secondary);
    line-height: 1.4;
    word-break: break-word;
    margin-bottom: 0.4rem;
}

.comment-footer {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.65rem;
    color: var(--builder-text-muted);
}

.comment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 1px 6px;
    background: var(--builder-bg-3);
    border-radius: 999px;
    font-size: 0.6rem;
}

.comment-actions {
    display: flex;
    gap: 0.25rem;
    margin-top: 0.4rem;
    padding-top: 0.4rem;
    border-top: 1px solid var(--builder-border);
}

.comment-actions button {
    padding: 0.25rem 0.5rem;
    background: transparent;
    border: 1px solid var(--builder-border);
    border-radius: 4px;
    color: var(--builder-text-muted);
    font-size: 0.65rem;
    cursor: pointer;
    transition: all var(--builder-transition);
    font-family: inherit;
}

.comment-actions button:hover {
    background: var(--builder-bg-3);
    color: var(--builder-text);
}

.comment-actions button.resolve:hover {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border-color: var(--success);
}

.comment-actions button.delete:hover {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border-color: var(--danger);
}

/* Formulario nuevo comentario */
.comment-form {
    padding: 0.75rem;
    background: var(--builder-bg-3);
    border-radius: 6px;
    margin-top: 0.5rem;
}

.comment-form textarea {
    width: 100%;
    padding: 0.5rem 0.65rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 4px;
    color: var(--builder-text);
    font-size: 0.8rem;
    font-family: inherit;
    resize: none;
    min-height: 50px;
    transition: border-color var(--builder-transition);
}

.comment-form textarea:focus {
    outline: none;
    border-color: var(--builder-accent);
}

.comment-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
    gap: 0.4rem;
}

.comment-form select {
    padding: 0.35rem 0.5rem;
    background: var(--builder-bg);
    border: 1px solid var(--builder-border);
    border-radius: 4px;
    color: var(--builder-text);
    font-size: 0.7rem;
    font-family: inherit;
}

.comment-form button {
    padding: 0.4rem 0.8rem;
    background: var(--builder-accent);
    color: var(--builder-bg);
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--builder-transition);
    font-family: inherit;
}

.comment-form button:hover {
    background: var(--builder-accent-dark);
}

/* Empty state */
.comments-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--builder-text-muted);
}

.comments-empty i {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-bottom: 0.5rem;
    display: block;
}

.comments-empty p {
    font-size: 0.8rem;
    margin: 0;
}

/* Panel que crea comments.js: overlay a la derecha, plegado por defecto.
   Antes se añadía al vuelo sin estilos y quedaba suelto al final del layout. */
#commentsPanel {
    position: fixed;
    top: var(--builder-topbar-h);
    right: 0;
    bottom: 0;
    width: 340px;
    max-width: 92vw;
    z-index: 60;
    display: flex;
    flex-direction: column;
    background: var(--builder-bg-2);
    border-left: 1px solid var(--builder-border);
    box-shadow: -8px 0 24px rgba(0, 0, 0, .35);
    transition: transform var(--builder-transition);
}

#commentsPanel.collapsed {
    transform: translateX(100%);
}

#commentsPanel .comments-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--builder-border);
}

#commentsPanel .comments-header h3 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

#commentsPanel .comments-filters {
    display: flex;
    border-bottom: 1px solid var(--builder-border);
}

#commentsPanel .filter-btn {
    flex: 1 1 0;
    padding: 0.5rem 0.4rem;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--builder-text-muted);
    font: inherit;
    font-size: 0.78rem;
    cursor: pointer;
}

#commentsPanel .filter-btn.active {
    color: var(--builder-accent);
    border-bottom-color: var(--builder-accent);
}

#commentsPanel .comments-list {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 0.75rem;
}

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
    animation: slideIn 0.3s ease;
    transition: all 0.3s ease;
}

.toast.error { border-left-color: var(--builder-danger); }
.toast.error i { color: var(--builder-danger); }
.toast.warning { border-left-color: var(--builder-warning); }
.toast.warning i { color: var(--builder-warning); }
.toast.info { border-left-color: #3b82f6; }
.toast.info i { color: #3b82f6; }
.toast.success i { color: var(--builder-success); }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ═══ GRAPESJS OVERRIDES ═══ */
.gjs-one-bg { background-color: var(--builder-bg-2) !important; }
.gjs-two-bg { background-color: var(--builder-bg-3) !important; }
.gjs-three-bg { background-color: var(--builder-bg) !important; }
.gjs-four-bg { background-color: var(--builder-accent) !important; }
.gjs-one-color { color: var(--builder-text) !important; }
.gjs-two-color { color: var(--builder-text-muted) !important; }
.gjs-three-color { color: var(--builder-accent) !important; }
.gjs-four-color { color: var(--builder-bg) !important; }

.gjs-block {
    border-radius: 6px;
    border: 1px solid var(--builder-border);
    background: var(--builder-bg-2);
    transition: all var(--builder-transition);
}

.gjs-block:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    border-color: var(--builder-accent);
}

.gjs-pn-panel {
    background: var(--builder-bg-2) !important;
    border-color: var(--builder-border) !important;
}

.gjs-cv-canvas { background: var(--builder-bg) !important; }
.gjs-selected { outline: 2px solid var(--builder-accent) !important; }

/* ═══ CODEMIRROR ═══ */
.CodeMirror {
    height: 100% !important;
    font-family: var(--font-mono);
    font-size: 13px;
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 1200px) {
    :root {
        --builder-left-w: 240px;
        --builder-right-w: 300px;
    }
}

@media (max-width: 992px) {
    :root {
        --builder-left-w: 220px;
        --builder-right-w: 280px;
    }
}

@media (max-width: 768px) {
    .builder-left, .right-panel {
        position: absolute;
        top: 0;
        bottom: 0;
        z-index: 100;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
    }
    .builder-left {
        left: 0;
    }
    .right-panel {
        right: 0;
    }
    .btn-label { display: none; }
    .user-chip-mini span { display: none; }
}
</style>
</head>
<body class="builder-body">

<!-- ═══════════════════════════════════════════════════════════════
TOPBAR DEL BUILDER
═══════════════════════════════════════════════════════════════ -->
<div class="builder-topbar">
    <div class="topbar-left">
        <a href="<?= url('admin/pages') ?>" class="builder-logo" title="Volver al listado">
            <i class="bi bi-arrow-left"></i>
        </a>

        <!-- ═══ 🆕 BOTÓN TOGGLE SIDEBAR IZQUIERDO ═══ -->
        <button class="btn-builder btn-ghost btn-toggle-left active" onclick="toggleLeftSidebar()" title="Mostrar/ocultar panel izquierdo">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>

        <div class="page-title-display">
            <i class="bi bi-file-earmark-richtext"></i>
            <span id="displayTitle"><?= e($page['title']) ?></span>
            <span class="page-id-badge">#<?= (int)$page['id'] ?></span>
        </div>
    </div>

    <div class="topbar-center">
        <span class="autosave-status" id="autoSaveStatus"></span>
    </div>

    <div class="topbar-right">
        <button class="btn-builder btn-ghost" onclick="reloadAssets()" title="Recargar assets">
            <i class="bi bi-arrow-clockwise"></i>
        </button>

        <button type="button" class="btn-builder btn-outline" onclick="preview()" id="previewBtn">
            <i class="bi bi-eye"></i>
            <span class="btn-label">Preview</span>
        </button>

        <button class="btn-builder btn-primary" onclick="savePage()" id="saveBtn">
            <i class="bi bi-save"></i>
            <span class="btn-label">Guardar</span>
        </button>

        <!-- ═══ 🆕 BOTÓN TOGGLE PANEL DERECHO ═══ -->
        <button class="btn-builder btn-ghost" onclick="toggleRightPanel()" title="Mostrar/ocultar configuración y comentarios">
            <i class="bi bi-layout-sidebar-reverse"></i>
        </button>

        <div class="user-chip-mini">
            <i class="bi bi-person-circle"></i>
            <span><?= e($currentUser['username'] ?? 'User') ?></span>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
LAYOUT PRINCIPAL
═══════════════════════════════════════════════════════════════ -->
<div class="builder-main">

    <!-- ═══════════════════════════════════════════════════════════
    🆕 SIDEBAR IZQUIERDO (con toggle)
    ════════════════════════════════════════════════════════════ -->
    <aside class="builder-left" id="builderLeft">
        <div class="left-header">
            <h4><i class="bi bi-tools"></i> Herramientas</h4>
        </div>

        <!-- Pestañas del sidebar izquierdo -->
        <div class="left-tabs">
            <button class="left-tab active" data-left-tab="actions" onclick="switchLeftTab('actions')">
                <i class="bi bi-lightning"></i> Acciones
            </button>
            <button class="left-tab" data-left-tab="layers" onclick="switchLeftTab('layers')">
                <i class="bi bi-layers"></i> Capas
            </button>
            <button class="left-tab" data-left-tab="history" onclick="switchLeftTab('history')">
                <i class="bi bi-clock-history"></i> Historial
            </button>
        </div>

        <div class="left-content">
            <!-- Pestaña: Acciones rápidas -->
            <div class="left-pane" data-left-pane="actions">
                <div class="left-section">
                    <div class="left-section-title">Atajos de teclado</div>
                    <button class="quick-action" onclick="savePage()">
                        <i class="bi bi-save"></i>
                        <span>Guardar página</span>
                        <span class="shortcut">Ctrl+S</span>
                    </button>
                    <button class="quick-action" onclick="preview()">
                        <i class="bi bi-eye"></i>
                        <span>Vista previa</span>
                        <span class="shortcut">Ctrl+P</span>
                    </button>
                    <button class="quick-action" onclick="switchTab('html')">
                        <i class="bi bi-code-slash"></i>
                        <span>Ver código HTML</span>
                    </button>
                    <button class="quick-action" onclick="switchTab('css')">
                        <i class="bi bi-palette2"></i>
                        <span>Ver código CSS</span>
                    </button>
                </div>

                <div class="left-section">
                    <div class="left-section-title">Dispositivos</div>
                    <button class="quick-action" onclick="setDevice('desktop')">
                        <i class="bi bi-display"></i>
                        <span>Desktop</span>
                    </button>
                    <button class="quick-action" onclick="setDevice('tablet')">
                        <i class="bi bi-tablet"></i>
                        <span>Tablet (768px)</span>
                    </button>
                    <button class="quick-action" onclick="setDevice('mobile')">
                        <i class="bi bi-phone"></i>
                        <span>Móvil (375px)</span>
                    </button>
                </div>

                <div class="left-section">
                    <div class="left-section-title">Enlaces rápidos</div>
                    <a href="<?= url('admin/pages') ?>" class="quick-action" style="text-decoration:none">
                        <i class="bi bi-arrow-left"></i>
                        <span>Volver a páginas</span>
                    </a>
                    <a href="<?= url("admin/pages/{$page['id']}/edit") ?>" class="quick-action" style="text-decoration:none">
                        <i class="bi bi-pencil"></i>
                        <span>Editor de metadatos</span>
                    </a>
                    <a href="<?= url("admin/pages/{$page['id']}/versions") ?>" class="quick-action" style="text-decoration:none" target="_blank">
                        <i class="bi bi-clock-history"></i>
                        <span>Ver versiones</span>
                    </a>
                    <a href="<?= e($previewUrl) ?>" class="quick-action" style="text-decoration:none" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Ver página publicada</span>
                    </a>
                </div>
            </div>

            <!-- Pestaña: Capas (con drag & drop) -->
            <div class="left-pane" data-left-pane="layers">
                <div class="left-section">
                    <div class="left-section-title">Componentes del canvas</div>
                    <div id="layersList">
                        <div class="comments-empty" style="padding:1rem">
                            <i class="bi bi-layers" style="font-size:1.5rem"></i>
                            <p style="font-size:0.75rem">Arrastra para reordenar capas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña: Historial -->
            <div class="left-pane" data-left-pane="history">
                <div class="left-section">
                    <div class="left-section-title">Actividad reciente</div>
                    <div id="historyList">
                        <?php if (!empty($page['created_at'])): ?>
                            <div class="history-item">
                                <div class="history-time">
                                    <i class="bi bi-calendar-event"></i> Creada: <?= e($page['created_at']) ?>
                                </div>
                            </div>
                            <div class="history-item">
                                <div class="history-time">
                                    <i class="bi bi-clock"></i> Actualizada: <?= e($page['updated_at'] ?? '—') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="comments-empty" style="padding:1rem">
                                <i class="bi bi-clock-history" style="font-size:1.5rem"></i>
                                <p style="font-size:0.75rem">Guarda la página para ver el historial</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── Panel del Editor ───────────────────────────────────── -->
    <div class="editor-panel">
        <!-- Pestañas -->
        <div class="editor-tabs">
            <button class="editor-tab active" data-tab="visual" onclick="switchTab('visual')">
                <i class="bi bi-palette"></i> Visual
            </button>
            <button class="editor-tab" data-tab="html" onclick="switchTab('html')">
                <i class="bi bi-code-slash"></i> HTML
            </button>
            <button class="editor-tab" data-tab="css" onclick="switchTab('css')">
                <i class="bi bi-palette2"></i> CSS
            </button>
            <?php if (Auth::can('pages.edit')): ?>
            <button class="editor-tab" data-tab="php" onclick="switchTab('php')">
                <i class="bi bi-filetype-php"></i> PHP
            </button>
            <?php endif; ?>
            <button class="editor-tab" data-tab="js" onclick="switchTab('js')">
                <i class="bi bi-braces"></i> JS
            </button>
        </div>

        <!-- Contenido de editores -->
        <div class="editor-content">
            <!-- GrapesJS -->
            <div class="editor-pane active" id="pane-visual">
                <div id="gjs"></div>
            </div>

            <!-- HTML -->
            <div class="editor-pane" id="pane-html">
                <textarea id="editor-html"></textarea>
            </div>

            <!-- CSS -->
            <div class="editor-pane" id="pane-css">
                <textarea id="editor-css"></textarea>
            </div>

            <!-- PHP -->
            <?php if (Auth::can('pages.edit')): ?>
            <div class="editor-pane" id="pane-php">
                <textarea id="editor-php"></textarea>
            </div>
            <?php endif; ?>

            <!-- JS -->
            <div class="editor-pane" id="pane-js">
                <textarea id="editor-js"></textarea>
            </div>
        </div>

        <!-- Barra de estado -->
        <div class="statusbar">
            <span class="statusbar-dot" id="statusDot"></span>
            <span id="statusMode">Editor visual</span>
            <span class="statusbar-separator">|</span>
            <span id="statusInfo"></span>
            <span class="statusbar-right">
                <span id="autoSaveStatusBottom" style="margin-right:1rem"></span>
                <span id="statusVersion">v<?= (int)($page['version'] ?? 1) ?></span>
                <span class="statusbar-separator">|</span>
                <span id="statusSize">0 KB</span>
            </span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
    🆕 PANEL DERECHO UNIFICADO (Configuración + Comentarios)
    ════════════════════════════════════════════════════════════ -->
    <aside class="right-panel" id="rightPanel">
        <div class="right-header">
            <h3 id="rightPanelTitle"><i class="bi bi-gear"></i> Panel</h3>
            <button class="right-close" onclick="toggleRightPanel()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Pestañas: Configuración | Comentarios -->
        <div class="right-tabs">
            <button class="right-tab active" data-right-tab="config" onclick="switchRightTab('config')">
                <i class="bi bi-gear"></i> Configuración
            </button>
            <button class="right-tab" data-right-tab="comments" onclick="switchRightTab('comments')">
                <i class="bi bi-chat-dots"></i> Comentarios
                <span class="tab-badge" id="commentsCount" style="display:none">0</span>
            </button>
        </div>

        <div class="right-content">
            <!-- ═══ PESTAÑA: CONFIGURACIÓN ═══ -->
            <div class="right-pane active" data-right-pane="config">
                <div class="config-section">
                    <label class="config-label">Título</label>
                    <input type="text" id="pageTitle" class="config-input"
                           value="<?= e($page['title']) ?>"
                           oninput="updateDisplayTitle(); autoSlug();">
                </div>
                <div class="config-section">
                    <label class="config-label">Slug URL</label>
                    <input type="text" id="pageSlug" class="config-input" value="<?= e($page['slug']) ?>">
                    <small class="config-help">Se genera automáticamente desde el título</small>
                </div>
                <div class="config-section">
                    <label class="config-label">Descripción</label>
                    <textarea id="pageDesc" class="config-textarea" rows="3"><?= e($page['description'] ?? '') ?></textarea>
                </div>
                <div class="config-section">
                    <label class="config-label">Plantilla</label>
                    <select id="pageTemplate" class="config-select">
                        <option value="">— Sin plantilla —</option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= ($page['template_id'] ?? null) == $t['id'] ? 'selected' : '' ?>>
                                <?= e($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="config-section">
                    <label class="config-label">Estado</label>
                    <select id="pageStatus" class="config-select">
                        <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>📝 Borrador</option>
                        <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>✅ Publicado</option>
                        <option value="archived" <?= ($page['status'] ?? '') === 'archived' ? 'selected' : '' ?>>📦 Archivado</option>
                    </select>
                </div>
                <div class="config-section">
                    <label class="config-checkbox">
                        <input type="checkbox" id="pageStartpage" value="1" <?= ($page['startpage'] ?? 0) ? 'checked' : '' ?>>
                        <span>🏠 Página de inicio</span>
                    </label>
                </div>
                <?php if ($page['created_at'] ?? null): ?>
                <div class="config-section">
                    <div class="config-meta">
                        <span class="meta-label">Creada:</span>
                        <span class="meta-value"><?= e($page['created_at']) ?></span>
                    </div>
                    <div class="config-meta">
                        <span class="meta-label">Actualizada:</span>
                        <span class="meta-value"><?= e($page['updated_at']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <hr class="config-divider">
                <!-- SEO Rápido -->
                <div class="config-section">
                    <label class="config-label"><i class="bi bi-search"></i> SEO</label>
                    <input type="text" id="seoTitle" class="config-input"
                           placeholder="Meta title"
                           value="<?= e($seo['basic']['title'] ?? '') ?>"
                           maxlength="70">
                    <textarea id="seoDescription" class="config-textarea" rows="2"
                              placeholder="Meta description"
                              maxlength="160"><?= e($seo['basic']['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- ═══ 🆕 PESTAÑA: COMENTARIOS ═══ -->
            <div class="right-pane" data-right-pane="comments">
                <!-- Toolbar de comentarios -->
                <div class="comments-toolbar">
                    <button onclick="toggleCommentMode()" id="btnAddComment" title="Añadir comentario en el canvas">
                        <i class="bi bi-pin-map"></i> Añadir
                    </button>
                    <button onclick="filterComments('all')" class="active" data-filter="all">
                        Todos
                    </button>
                    <button onclick="filterComments('open')" data-filter="open">
                        Abiertos
                    </button>
                    <button onclick="filterComments('resolved')" data-filter="resolved">
                        Resueltos
                    </button>
                </div>

                <!-- Lista de comentarios -->
                <div id="commentsList">
                    <div class="comments-empty">
                        <i class="bi bi-chat-dots"></i>
                        <p>Cargando comentarios...</p>
                    </div>
                </div>

                <!-- Formulario nuevo comentario (oculto hasta hacer clic en canvas) -->
                <div class="comment-form" id="commentForm" style="display:none">
                    <textarea id="newCommentText" placeholder="Escribe un comentario... Usa @usuario para mencionar"></textarea>
                    <div class="comment-form-actions">
                        <select id="newCommentPriority">
                            <option value="low">🔵 Baja</option>
                            <option value="medium" selected>🟡 Media</option>
                            <option value="high">🟠 Alta</option>
                            <option value="urgent">🔴 Urgente</option>
                        </select>
                        <button onclick="submitNewComment()">
                            <i class="bi bi-send"></i> Publicar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════════════════════
FORMULARIO OCULTO PARA GUARDAR
═══════════════════════════════════════════════════════════════ -->
<form id="saveForm" style="display:none" action="<?= e($saveUrl) ?>" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="mode" value="visual">
    <input type="hidden" name="title" id="formTitle">
    <input type="hidden" name="slug" id="formSlug">
    <input type="hidden" name="description" id="formDesc">
    <input type="hidden" name="template_id" id="formTemplate">
    <input type="hidden" name="content_html" id="formHtml">
    <input type="hidden" name="content_css" id="formCss">
    <input type="hidden" name="content_php" id="formPhp">
    <input type="hidden" name="content_js" id="formJs">
    <input type="hidden" name="status" id="formStatus">
    <input type="hidden" name="is_published" id="formIsPublished">
    <input type="hidden" name="startpage" id="formStartpage">
    <input type="hidden" name="grapesjs_data" id="formGrapesData">
    <input type="hidden" name="seo_title" id="formSeoTitle">
    <input type="hidden" name="seo_description" id="formSeoDesc">
    <input type="hidden" name="version_note" id="formVersionNote">
</form>

<!-- Activos JS del editor (CodeMirror + GrapesJS + plugins + utilidades).
     Fuente única: views/partials/editor-assets-body.php, compartido con el
     editor de plantillas (views/templates/edit.php). -->
<?php require VIEWS_PATH . '/partials/editor-assets-body.php'; ?>

<!-- Sistema de comentarios: define la clase CommentsManager que usa el builder -->
<script src="<?= e(ASSETS_URL) ?>/js/comments.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
SCRIPT PRINCIPAL DEL BUILDER
═══════════════════════════════════════════════════════════════ -->
<script>
// ═══════════════════════════════════════════════════════════════
// CONFIGURACIÓN GLOBAL
// ═══════════════════════════════════════════════════════════════
const CONFIG = {
    uploadUrl: '<?= e($uploadUrl) ?>',
    assetsUrl: '<?= e($assetsUrl) ?>',
    saveUrl: '<?= e($saveUrl) ?>',
    csrfToken: '<?= e($csrfToken) ?>',
    pageId: <?= (int)$page['id'] ?>,
    baseUrl: '<?= e(rtrim(BASE_URL ?? '', '/')) ?>',
    autosaveUrl: '<?= e($saveUrl) ?>/autosave',
    autosaveInterval: 30000,
    autosaveEnabled: true,
};

const existingAssets = <?= $jsonAssets ?>;
const pageData = {
    html: <?= $jsonHtml ?>,
    css: <?= $jsonCss ?>,
    php: <?= $jsonPhp ?>,
    js: <?= $jsonJs ?>,
    grapes: <?= $jsonGrapes ?>,
};

// Los plugins, sus opciones, los estilos del lienzo y la carga de bloques viven
// en public/assets/js/grapesjs-shared.js (window.EditorKit): el mismo código que
// usa el editor de plantillas. Aquí solo queda lo propio de páginas.
const BLOCK_SCRIPTS = <?= json_encode($blockUrls, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

// ═══════════════════════════════════════════════════════════════
// ESTADO GLOBAL
// ═══════════════════════════════════════════════════════════════
let cmHtml = null, cmCss = null, cmJs = null, cmPhp = null;
let gjsEditor = null;
let currentTab = 'visual';
let isSyncing = false;
let isSaving = false;
let isAutoSaving = false;
let isDirty = false;
let leftSidebarOpen = true;
let rightPanelOpen = true;
let currentRightTab = 'config';
let currentLeftTab = 'actions';
let lastAutoSaveTime = null;
let autoSaveTimer = null;
let syncTimer = null;

const tabLabels = {
    visual: 'Editor visual',
    html: 'HTML',
    css: 'CSS',
    php: 'PHP',
    js: 'JavaScript'
};

// ═══════════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Cada subsistema arranca por separado: si una librería del CDN falla, el
    // resto del builder sigue usable en vez de quedarse en blanco.
    var failed = [];
    var safeInit = function(label, fn) {
        try {
            fn();
        } catch (err) {
            console.error('❌ Builder · falló ' + label + ':', err);
            failed.push(label);
        }
    };

    safeInit('CodeMirror', initCodeMirror);
    safeInit('lienzo visual', initGrapesJS);
    safeInit('eventos', bindEvents);
    safeInit('autoguardado', startAutoSave);

    if (failed.length) {
        showToast('✗ No se pudo iniciar: ' + failed.join(', '), 'error');
    }

    // Los comentarios se inicializan en su propio bloque, cuando gjsEditor existe.
    console.log('✅ Builder inicializado correctamente');
});

// ═══════════════════════════════════════════════════════════════
// 🆕 TOGGLE SIDEBAR IZQUIERDO
// ═══════════════════════════════════════════════════════════════
function toggleLeftSidebar() {
    leftSidebarOpen = !leftSidebarOpen;
    var left = document.getElementById('builderLeft');
    var btn = document.querySelector('.btn-toggle-left');
    left.classList.toggle('collapsed', !leftSidebarOpen);
    btn.classList.toggle('active', leftSidebarOpen);

    setTimeout(function() {
        if (gjsEditor) gjsEditor.Canvas.refresh();
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) {
            if (cm) cm.refresh();
        });
    }, 300);
}

// ═══════════════════════════════════════════════════════════════
// 🆕 TOGGLE PANEL DERECHO
// ═══════════════════════════════════════════════════════════════
function toggleRightPanel() {
    rightPanelOpen = !rightPanelOpen;
    var right = document.getElementById('rightPanel');
    right.classList.toggle('collapsed', !rightPanelOpen);

    setTimeout(function() {
        if (gjsEditor) gjsEditor.Canvas.refresh();
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) {
            if (cm) cm.refresh();
        });
    }, 300);
}

// ═══════════════════════════════════════════════════════════════
// 🆕 PESTAÑAS DEL PANEL DERECHO
// ═══════════════════════════════════════════════════════════════
function switchRightTab(tab) {
    currentRightTab = tab;

    document.querySelectorAll('.right-tab').forEach(function(t) {
        t.classList.toggle('active', t.dataset.rightTab === tab);
    });

    document.querySelectorAll('.right-pane').forEach(function(p) {
        p.classList.toggle('active', p.dataset.rightPane === tab);
    });

    var titleEl = document.getElementById('rightPanelTitle');
    if (tab === 'config') {
        titleEl.innerHTML = '<i class="bi bi-gear"></i> Configuración';
    } else if (tab === 'comments') {
        titleEl.innerHTML = '<i class="bi bi-chat-dots"></i> Comentarios';
    }
}

// ═══════════════════════════════════════════════════════════════
// 🆕 PESTAÑAS DEL SIDEBAR IZQUIERDO
// ═══════════════════════════════════════════════════════════════
function switchLeftTab(tab) {
    currentLeftTab = tab;

    document.querySelectorAll('.left-tab').forEach(function(t) {
        t.classList.toggle('active', t.dataset.leftTab === tab);
    });

    document.querySelectorAll('.left-pane').forEach(function(p) {
        p.classList.toggle('active', p.dataset.leftPane === tab);
    });
}

// ═══════════════════════════════════════════════════════════════
// CAMBIAR DISPOSITIVO
// ═══════════════════════════════════════════════════════════════
function setDevice(device) {
    if (!gjsEditor) return;
    gjsEditor.setDevice(device.charAt(0).toUpperCase() + device.slice(1));
    showToast('Vista: ' + device, 'info');
}

// ═══════════════════════════════════════════════════════════════
// CODEMIRROR
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
            'Ctrl-S': function() { savePage(); }
        }
    };

    cmHtml = CodeMirror.fromTextArea(document.getElementById('editor-html'), {
        ...cmOptions, mode: 'htmlmixed'
    });
    cmCss = CodeMirror.fromTextArea(document.getElementById('editor-css'), {
        ...cmOptions, mode: 'css'
    });
    const phpEl = document.getElementById('editor-php');
    cmPhp = phpEl ? CodeMirror.fromTextArea(phpEl, {
        ...cmOptions, mode: 'application/x-httpd-php'
    }) : null;
    cmJs = CodeMirror.fromTextArea(document.getElementById('editor-js'), {
        ...cmOptions, mode: 'javascript'
    });

    cmHtml.setValue(pageData.html || '');
    cmCss.setValue(pageData.css || '');
    if (cmPhp) cmPhp.setValue(pageData.php || '');
    cmJs.setValue(pageData.js || '');

    [cmHtml, cmCss, cmJs, cmPhp].forEach(cm => {
        if (!cm) return;
        cm.on('change', function() {
            isDirty = true;
            updateSizeInfo();
            updateAutoSaveIndicator();
        });
        cm.on('cursorActivity', function() {
            if (getActiveCM() === cm) updateStatusInfo();
        });
    });
}

// ═══════════════════════════════════════════════════════════════
// GRAPESJS
// ═══════════════════════════════════════════════════════════════
function initGrapesJS() {
    // Solo se piden los plugins que el navegador tiene realmente registrados.
    var pluginSelection = EditorKit.filterPlugins(EditorKit.PLUGIN_NAMES);
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
            assets: existingAssets || [],
            upload: CONFIG.uploadUrl,
            uploadName: 'files[]',
            multiUpload: true,
            autoAdd: true,
            accept: 'image/*',
            uploadFile: function(e) {
                handleAssetUpload(e);
            }
        },
        selectorManager: { componentFirst: true },
        components: pageData.html || '<div></div>',
        style: pageData.css || '',
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

    // Restaurar el proyecto guardado: conserva ids, estilos y componentes que
    // el HTML/CSS plano no puede representar. Sin esto el canvas se reconstruía
    // desde cero en cada carga.
    if (pageData.grapes) {
        try {
            var project = typeof pageData.grapes === 'string'
                ? JSON.parse(pageData.grapes)
                : pageData.grapes;
            if (project && project.pages) {
                gjsEditor.loadProjectData(project);
            }
        } catch (err) {
            console.warn('No se pudo restaurar el proyecto del canvas:', err);
        }
    }

    gjsEditor.on('update', function() {
        // Marcar cambios reales del canvas: sin esto el autoguardado nunca
        // se disparaba al editar visualmente.
        isDirty = true;
        updateAutoSaveIndicator();
        if (isSyncing) return;
        isSyncing = true;
        try {
            cmHtml.setValue(gjsEditor.getHtml());
            cmCss.setValue(gjsEditor.getCss());
            updateSizeInfo();
            updateLayersList();
        } catch (err) {
            console.warn('Error sincronizando GrapesJS → CodeMirror:', err);
        }
        setTimeout(function() { isSyncing = false; }, 150);
    });

    cmHtml.on('change', function() {
        if (isSyncing) return;
        clearTimeout(syncTimer);
        syncTimer = setTimeout(function() {
            isSyncing = true;
            try {
                gjsEditor.setComponents(cmHtml.getValue());
            } catch (err) {
                console.warn('Error sincronizando HTML:', err);
            }
            updateSizeInfo();
            setTimeout(function() { isSyncing = false; }, 200);
        }, 600);
    });

    cmCss.on('change', function() {
        if (isSyncing) return;
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

    gjsEditor.on('change:canvasOffset', function() {
        isDirty = true;
        updateAutoSaveIndicator();
    });

    gjsEditor.on('component:selected', function(component) {
        updateLayersList();
    });

    // Los ficheros de bloques registran con `gjsEditor.BlockManager.add(...)`:
    // hay que inyectarlos con el editor ya creado. La carga vive en EditorKit.
    EditorKit.loadBlockScripts(BLOCK_SCRIPTS, gjsEditor);
}

// ═══════════════════════════════════════════════════════════════
// 🆕 ACTUALIZAR LISTA DE CAPAS CON DRAG & DROP
// ═══════════════════════════════════════════════════════════════
function updateLayersList() {
    if (!gjsEditor) return;
    var wrapper = gjsEditor.getWrapper();
    if (!wrapper) return;

    var layersList = document.getElementById('layersList');
    if (!layersList) return;

    var html = '';
    var components = [];

    function traverse(component, depth) {
        if (depth > 5) return;
        var type = component.get('type') || 'default';
        var tagName = component.get('tagName') || 'div';
        var classes = (component.getClasses() || []).join('.');
        var name = tagName + (classes ? '.' + classes : '');

        components.push({
            type: type,
            name: name,
            component: component,
            depth: depth
        });

        component.components().forEach(function(child) {
            traverse(child, depth + 1);
        });
    }

    wrapper.components().forEach(function(child) {
        traverse(child, 0);
    });

    if (components.length === 0) {
        layersList.innerHTML = '<div class="comments-empty" style="padding:1rem"><i class="bi bi-layers" style="font-size:1.5rem"></i><p style="font-size:0.75rem">No hay componentes</p></div>';
        return;
    }

    components.slice(0, 30).forEach(function(comp, index) {
        var icon = getComponentIcon(comp.type);
        var padding = comp.depth * 12;
        html += '<div class="layer-item" draggable="true" data-index="' + index + '" data-component-id="' + comp.component.getId() + '" style="padding-left:' + (6 + padding) + 'px">';
        html += '<i class="bi bi-grip-vertical layer-drag-handle"></i>';
        html += '<i class="bi bi-' + icon + ' layer-icon"></i>';
        html += '<span class="layer-name">' + escapeHtml(comp.name) + '</span>';
        html += '</div>';
    });

    layersList.innerHTML = html;

    // Inicializar drag & drop
    initDragAndDrop();
}

function getComponentIcon(type) {
    var icons = {
        'text': 'type',
        'image': 'image',
        'link': 'link-45deg',
        'video': 'camera-video',
        'script': 'code-slash',
        'svg': 'vector-pen',
        'label': 'tag',
        'input': 'ui-checks',
        'form': 'ui-radios',
        'button': 'cursor',
        'default': 'square'
    };
    return icons[type] || 'square';
}

// ═══════════════════════════════════════════════════════════════
// 🆕 DRAG & DROP PARA CAPAS
// ═══════════════════════════════════════════════════════════════
let draggedElement = null;

function initDragAndDrop() {
    var layerItems = document.querySelectorAll('.layer-item');
    
    layerItems.forEach(function(item) {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('click', handleLayerClick);
    });
}

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    var items = document.querySelectorAll('.layer-item');
    items.forEach(function(item) {
        item.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedElement !== this) {
        // Reordenar en el DOM
        var parent = this.parentNode;
        var allItems = Array.from(parent.querySelectorAll('.layer-item'));
        var draggedIndex = allItems.indexOf(draggedElement);
        var droppedIndex = allItems.indexOf(this);
        
        if (draggedIndex < droppedIndex) {
            this.parentNode.insertBefore(draggedElement, this.nextSibling);
        } else {
            this.parentNode.insertBefore(draggedElement, this);
        }
        
        // Reordenar en GrapesJS
        reorderComponentsInGrapesJS();
    }
    
    return false;
}

function handleLayerClick(e) {
    var componentId = this.dataset.componentId;
    if (componentId && gjsEditor) {
        var component = gjsEditor.DomComponents.getComponents().depthFirst().filter(function(c) {
            return c.getId() === componentId;
        })[0];
        if (component) {
            gjsEditor.select(component);
        }
    }
}

function reorderComponentsInGrapesJS() {
    if (!gjsEditor) return;
    
    var layerItems = document.querySelectorAll('.layer-item');
    var wrapper = gjsEditor.getWrapper();
    if (!wrapper) return;
    
    var components = wrapper.components();
    var newOrder = [];
    
    layerItems.forEach(function(item) {
        var componentId = item.dataset.componentId;
        var component = components.filter(function(c) {
            return c.getId() === componentId;
        })[0];
        if (component) {
            newOrder.push(component);
        }
    });
    
    // Limpiar y reañadir en el nuevo orden
    components.reset([]);
    newOrder.forEach(function(comp) {
        components.add(comp);
    });
    
    showToast('✓ Capas reordenadas', 'success');
}

// ═══════════════════════════════════════════════════════════════
// BIND DE EVENTOS
// ═══════════════════════════════════════════════════════════════
function bindEvents() {
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            savePage();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            document.getElementById('previewBtn').click();
        }
        if (e.key === 'Escape' && rightPanelOpen) {
            toggleRightPanel();
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
        stopAutoSave();
    });

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoSave();
        } else {
            startAutoSave();
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// PESTAÑAS DEL EDITOR
// ═══════════════════════════════════════════════════════════════
function switchTab(tab) {
    if (tab === currentTab) return;

    currentTab = tab;

    document.querySelectorAll('.editor-tab').forEach(function(t) {
        t.classList.toggle('active', t.dataset.tab === tab);
    });

    document.querySelectorAll('.editor-pane').forEach(function(p) {
        p.classList.toggle('active', p.id === 'pane-' + tab);
    });

    document.getElementById('statusMode').textContent = tabLabels[tab] || tab;

    setTimeout(function() {
        if (tab === 'html') cmHtml.refresh();
        if (tab === 'css') cmCss.refresh();
        if (tab === 'php' && cmPhp) cmPhp.refresh();
        if (tab === 'js') cmJs.refresh();
    }, 10);

    if (tab === 'visual') {
        isSyncing = true;
        try {
            gjsEditor.setComponents(cmHtml.getValue());
            gjsEditor.setStyle(cmCss.getValue());
        } catch (err) {
            console.warn('Error sincronizando al visual:', err);
        }
        setTimeout(function() { isSyncing = false; }, 200);
    }

    updateStatusInfo();
}

// ═══════════════════════════════════════════════════════════════
// FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════
function getActiveCM() {
    return { html: cmHtml, css: cmCss, php: cmPhp, js: cmJs }[currentTab];
}

function updateDisplayTitle() {
    var t = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('displayTitle').textContent = t;
    document.title = t + ' · Builder';
}

function autoSlug() {
    var title = document.getElementById('pageTitle').value;
    var slug = title.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('pageSlug').value = slug || 'page-' + Date.now();
}

function generateSlug(text) {
    return (text || '').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'page-' + Date.now();
}

function updateSizeInfo() {
    try {
        var html = cmHtml ? cmHtml.getValue() : '';
        var css = cmCss ? cmCss.getValue() : '';
        var size = new Blob([html, css]).size;
        var kb = (size / 1024).toFixed(1);
        document.getElementById('statusSize').textContent = kb + ' KB';
    } catch (err) {
        console.warn('Error actualizando tamaño:', err);
    }
}

function updateStatusInfo() {
    var cm = getActiveCM();
    if (cm) {
        var pos = cm.getCursor();
        document.getElementById('statusInfo').textContent =
            'Línea ' + (pos.line + 1) + ', Col ' + (pos.ch + 1);
    } else {
        document.getElementById('statusInfo').textContent = '';
    }
}

function showToast(msg, type) {
    type = type || 'success';
    var c = document.getElementById('toastContainer');
    var t = document.createElement('div');
    t.className = 'toast ' + type;
    var iconMap = {
        success: 'check-circle-fill',
        error: 'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    };
    t.innerHTML = '<i class="bi bi-' + (iconMap[type] || 'info-circle-fill') + '"></i> ' + msg;
    c.appendChild(t);
    setTimeout(function() {
        t.style.opacity = '0';
        t.style.transform = 'translateX(100%)';
        setTimeout(function() { t.remove(); }, 300);
    }, 3500);
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ═══════════════════════════════════════════════════════════════
// SUBIDA DE ASSETS
// ═══════════════════════════════════════════════════════════════
function handleAssetUpload(e) {
    var files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
    if (!files || files.length === 0) return;

    var formData = new FormData();
    var validFiles = [];

    for (var i = 0; i < files.length; i++) {
        if (files[i].size > 5 * 1024 * 1024) {
            showToast('⚠ Archivo demasiado grande: ' + files[i].name, 'warning');
            continue;
        }
        if (!files[i].type.startsWith('image/')) {
            showToast('⚠ Solo imágenes: ' + files[i].name, 'warning');
            continue;
        }
        formData.append('files[]', files[i]);
        validFiles.push(files[i]);
    }

    if (validFiles.length === 0) return;

    formData.append('_csrf_token', CONFIG.csrfToken);
    showToast('📤 Subiendo ' + validFiles.length + ' imagen(es)...', 'info');

    fetch(CONFIG.uploadUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success && result.data && result.data.length > 0) {
            result.data.forEach(function(asset) {
                gjsEditor.AssetManager.add({
                    src: asset.src,
                    name: asset.name,
                    type: 'image',
                    width: asset.width,
                    height: asset.height,
                });
            });
            gjsEditor.AssetManager.render();
            showToast('✓ ' + result.data.length + ' imagen(es) cargada(s)', 'success');
        }
        if (result.errors && result.errors.length > 0) {
            result.errors.forEach(function(err) { showToast('⚠ ' + err, 'warning'); });
        }
    })
    .catch(function(err) {
        console.error('Error subiendo:', err);
        showToast('✗ Error al subir: ' + err.message, 'error');
    });
}

function reloadAssets() {
    fetch(CONFIG.assetsUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        var assets = result.data || result;
        if (!Array.isArray(assets)) return;
        gjsEditor.AssetManager.clear();
        gjsEditor.AssetManager.add(assets);
        gjsEditor.AssetManager.render();
        showToast('✓ Assets recargados: ' + assets.length, 'success');
    })
    .catch(function(err) {
        showToast('✗ Error al recargar', 'error');
    });
}

// ═══════════════════════════════════════════════════════════════
// AUTOGUARDADO
// ═══════════════════════════════════════════════════════════════
function startAutoSave() {
    if (!CONFIG.autosaveEnabled) return;
    stopAutoSave();
    autoSaveTimer = setInterval(function() {
        if (isDirty && !isSaving && !isAutoSaving) {
            performAutoSave();
        }
    }, CONFIG.autosaveInterval);
}

function stopAutoSave() {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
        autoSaveTimer = null;
    }
}

function updateAutoSaveIndicator() {
    // El id real en la barra superior es autoSaveStatus (antes se buscaba
    // autoSaveStatusTop, que no existe: el indicador nunca se actualizaba).
    var indicatorTop = document.getElementById('autoSaveStatus');
    var indicatorBottom = document.getElementById('autoSaveStatusBottom');
    if (!indicatorTop && !indicatorBottom) return;

    var html = '';
    if (isAutoSaving) {
        html = '<i class="bi bi-cloud-arrow-up" style="color:var(--builder-warning)"></i> Autoguardando...';
    } else if (lastAutoSaveTime) {
        var ago = Math.floor((Date.now() - lastAutoSaveTime) / 1000);
        var timeText = ago < 60 ? ago + 's' : (ago < 3600 ? Math.floor(ago/60) + 'min' : Math.floor(ago/3600) + 'h');
        html = '<i class="bi bi-cloud-check" style="color:var(--builder-success)"></i> Autoguardado hace ' + timeText;
    } else if (isDirty) {
        html = '<i class="bi bi-pencil-square" style="color:var(--builder-warning)"></i> Cambios sin guardar';
    } else {
        html = '<i class="bi bi-check-circle" style="color:var(--builder-success)"></i> Todo guardado';
    }

    if (indicatorTop) indicatorTop.innerHTML = html;
    if (indicatorBottom) indicatorBottom.innerHTML = html;
}

function performAutoSave() {
    if (isAutoSaving || isSaving) return;
    isAutoSaving = true;
    updateAutoSaveIndicator();

    var data = {
        content_html: cmHtml ? cmHtml.getValue() : '',
        content_css: cmCss ? cmCss.getValue() : '',
        content_js: cmJs ? cmJs.getValue() : '',
        content_php: cmPhp ? cmPhp.getValue() : '',
        grapesjs_data: gjsEditor ? JSON.stringify(gjsEditor.getProjectData()) : '',
        _csrf_token: CONFIG.csrfToken
    };

    var formData = new FormData();
    Object.keys(data).forEach(function(key) { formData.append(key, data[key]); });

    fetch(CONFIG.autosaveUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) {
        // Sin esta comprobación un 500 se leía como respuesta vacía y el
        // autoguardado fallaba en silencio.
        if (!r.ok) {
            throw new Error('El servidor respondió ' + r.status);
        }
        return r.json();
    })
    .then(function(result) {
        if (result.success) {
            lastAutoSaveTime = Date.now();
            isDirty = false;
            if (!window._lastAutoSaveToast || Date.now() - window._lastAutoSaveToast > 300000) {
                showToast('💾 Autoguardado automático', 'info');
                window._lastAutoSaveToast = Date.now();
            }
        }
    })
    .catch(function(err) {
        console.error('✗ Error en autoguardado:', err);
    })
    .finally(function() {
        isAutoSaving = false;
        updateAutoSaveIndicator();
    });
}

// ═══════════════════════════════════════════════════════════════
// VISTA PREVIA
// ═══════════════════════════════════════════════════════════════
// La vista previa no existía: la barra y los accesos rápidos llamaban a
// preview() y el navegador lanzaba "preview is not defined".
function preview() {
    var html = cmHtml ? cmHtml.getValue() : '';
    var css = cmCss ? cmCss.getValue() : '';
    var js = cmJs ? cmJs.getValue() : '';

    // Si se está editando en el lienzo, se previsualiza lo último del canvas.
    if (gjsEditor && currentTab === 'visual') {
        try {
            html = gjsEditor.getHtml();
            css = gjsEditor.getCss();
        } catch (err) {
            console.warn('No se pudo leer el canvas para la vista previa:', err);
        }
    }

    var doc = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
        + '<meta name="viewport" content="width=device-width, initial-scale=1">'
        + '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
        + '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">'
        + '<style>' + css + '</style></head><body>' + html
        + (js ? '<script>' + js + '<\/script>' : '')
        + '</body></html>';

    var blob = new Blob([doc], { type: 'text/html;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var win = window.open(url, '_blank');

    if (!win) {
        showToast('✗ El navegador bloqueó la ventana de vista previa', 'error');
    }

    setTimeout(function() { URL.revokeObjectURL(url); }, 60000);
}

// ═══════════════════════════════════════════════════════════════
// GUARDAR PÁGINA
// ═══════════════════════════════════════════════════════════════
function savePage() {
    if (isSaving) return;
    isSaving = true;
    stopAutoSave();

    var btn = document.getElementById('saveBtn');
    var dot = document.getElementById('statusDot');
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span class="btn-label">Guardando...</span>';
    btn.disabled = true;
    dot.classList.add('saving');
    updateAutoSaveIndicator();

    document.getElementById('formTitle').value = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('formSlug').value = document.getElementById('pageSlug').value || generateSlug(document.getElementById('pageTitle').value);
    document.getElementById('formDesc').value = document.getElementById('pageDesc').value;
    document.getElementById('formTemplate').value = document.getElementById('pageTemplate').value;
    document.getElementById('formHtml').value = cmHtml ? cmHtml.getValue() : '';
    document.getElementById('formCss').value = cmCss ? cmCss.getValue() : '';
    document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
    document.getElementById('formJs').value = cmJs ? cmJs.getValue() : '';
    // El builder tiene un único control de publicación (el select «Estado»), así
    // que is_published se deriva de él. Sin esto solo se enviaba `status`: la
    // página quedaba con status=published pero is_published=0, y el frontend
    // —que exige los dos— la trataba como inexistente y devolvía 404.
    var statusValue = document.getElementById('pageStatus').value;
    document.getElementById('formStatus').value = statusValue;
    document.getElementById('formIsPublished').value = (statusValue === 'published') ? '1' : '0';
    document.getElementById('formStartpage').value = document.getElementById('pageStartpage').checked ? '1' : '0';
    document.getElementById('formGrapesData').value = gjsEditor ? JSON.stringify(gjsEditor.getProjectData()) : '';
    document.getElementById('formSeoTitle').value = document.getElementById('seoTitle').value;
    document.getElementById('formSeoDesc').value = document.getElementById('seoDescription').value;
    document.getElementById('formVersionNote').value = 'Edición desde builder visual';

    var formData = new FormData(document.getElementById('saveForm'));

    fetch(CONFIG.saveUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(r) {
        var contentType = r.headers.get('content-type') || '';
        var isJson = contentType.indexOf('application/json') !== -1;

        // Un 4xx/5xx con cuerpo HTML (o vacío) se daba por guardado: el builder
        // decía "Página guardada" aunque el servidor hubiera fallado.
        if (!r.ok) {
            return (isJson ? r.json() : Promise.resolve({})).then(function(body) {
                throw new Error(body.message || body.error || ('El servidor respondió ' + r.status));
            });
        }

        return isJson ? r.json() : { success: true, message: 'Guardado correctamente' };
    })
    .then(function(result) {
        if (result.success) {
            showToast('✓ Página guardada correctamente', 'success');
            isDirty = false;
            lastAutoSaveTime = Date.now();
            var newSlug = document.getElementById('pageSlug').value;
            if (newSlug) {
                document.getElementById('previewBtn').href = '/' + newSlug;
            }
        } else {
            showToast('✗ ' + (result.message || 'Error al guardar'), 'error');
        }
    })
    .catch(function(err) {
        showToast('✗ Error: ' + err.message, 'error');
    })
    .finally(function() {
        isSaving = false;
        btn.innerHTML = '<i class="bi bi-save"></i> <span class="btn-label">Guardar</span>';
        btn.disabled = false;
        dot.classList.remove('saving');
        updateAutoSaveIndicator();
        setTimeout(function() {
            startAutoSave();
        }, 1000);
    });
}

// ═══════════════════════════════════════════════════════════════
// INICIALIZACIÓN FINAL
// ═══════════════════════════════════════════════════════════════
window.addEventListener('load', function() {
    setTimeout(function() {
        if (gjsEditor) gjsEditor.Canvas.refresh();
        updateSizeInfo();
    }, 200);
});
</script>

<!-- ═══ SISTEMA DE COMENTARIOS ═══ -->

<script>
// Inicializar CommentsManager después de GrapesJS
document.addEventListener('DOMContentLoaded', function() {
    var initComments = setInterval(function() {
        if (typeof gjsEditor === 'undefined' || !gjsEditor) return;

        clearInterval(initComments);

        // comments.js puede no haber cargado: sin él no hay gestor de comentarios.
        if (typeof CommentsManager === 'undefined') {
            console.warn('comments.js no cargó: los comentarios quedan desactivados.');
            return;
        }

        try {
            commentsManager = new CommentsManager(
                <?= (int)$page['id'] ?>,
                gjsEditor,
                {
                    baseUrl: '<?= e(rtrim(BASE_URL ?? '', '/')) ?>',
                    csrfToken: '<?= e(csrf_token()) ?>',
                }
            );

            // El panel se crea abierto; se deja plegado hasta que se pida.
            var panel = document.getElementById('commentsPanel');
            if (panel) panel.classList.add('collapsed');
        } catch (err) {
            console.error('Error inicializando comentarios:', err);
        }
    }, 100);
});
</script>
</body>
</html>