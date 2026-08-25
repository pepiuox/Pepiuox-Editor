<?php
/**
 * Builder Visual Avanzado — PePiuoX Maker v2
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
$jsonHtml = json_encode($page['content_html'] ?? '<div style="padding:40px;text-align:center;"><h1>Mi Nueva Página</h1><p>Comienza a editar tu contenido aquí.</p></div>', JSON_UNESCAPED_UNICODE);
$jsonCss  = json_encode($page['content_css'] ?? "body {\n  font-family: 'Inter', sans-serif;\n  margin: 0;\n  padding: 0;\n  background: #ffffff;\n  color: #333333;\n}", JSON_UNESCAPED_UNICODE);
$jsonPhp  = json_encode($page['content_php'] ?? '', JSON_UNESCAPED_UNICODE);
$jsonJs   = json_encode($page['content_js'] ?? '', JSON_UNESCAPED_UNICODE);
$jsonAssets = json_encode($existingAssets, JSON_UNESCAPED_SLASHES);

// URLs del sistema
$uploadUrl  = url('admin/pages/' . $page['id'] . '/builder/upload');
$assetsUrl  = url('admin/pages/assets');
$saveUrl    = url('admin/pages/' . $page['id']);
$previewUrl = url($page['slug'] ?? '');
$currentUser = Auth::user();
$csrfToken = function_exists('csrf_token') ? csrf_token() : \App\Core\CSRF::token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · PePiuoX Maker</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- GrapesJS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">
    
    <!-- CodeMirror -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.css">

    <!-- ═══════════════════════════════════════════════════════════════
    CSS DEL BUILDER (inline para no depender de archivos externos)
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
            --builder-config-w: 320px;
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

        /* ═══ PANEL DE CONFIGURACIÓN (derecha) ═══ */
        .config-panel {
            width: var(--builder-config-w);
            background: var(--builder-bg-2);
            border-left: 1px solid var(--builder-border);
            display: flex;
            flex-direction: column;
            transition: width var(--builder-transition), margin var(--builder-transition);
            flex-shrink: 0;
            order: 2;
            overflow: hidden;
        }

        .config-panel.collapsed {
            width: 0 !important;
            margin-left: 0;
            border-left: none;
        }

        .config-header {
            padding: 1rem;
            border-bottom: 1px solid var(--builder-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--builder-bg-3);
            flex-shrink: 0;
        }

        .config-header h3 {
            margin: 0;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--builder-text);
        }

        .config-header h3 i { color: var(--builder-accent); }

        .config-close {
            background: transparent;
            border: none;
            color: var(--builder-text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all var(--builder-transition);
        }

        .config-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--builder-danger);
        }

        .config-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

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
        @media (max-width: 768px) {
            .config-panel {
                position: absolute;
                right: 0;
                top: 0;
                bottom: 0;
                z-index: 100;
                box-shadow: -4px 0 20px rgba(0, 0, 0, 0.4);
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
        <div class="page-title-display">
            <i class="bi bi-file-earmark-richtext"></i>
            <span id="displayTitle"><?= e($page['title']) ?></span>
            <span class="page-id-badge">#<?= (int)$page['id'] ?></span>
        </div>
    </div>
    
    <div class="topbar-center">
        <span class="autosave-status" id="autoSaveStatusTop"></span>
    </div>
    
    <div class="topbar-right">
        <button class="btn-builder btn-ghost" onclick="toggleConfigPanel()" title="Configuración">
            <i class="bi bi-sliders"></i>
            <span class="btn-label">Config</span>
        </button>
        
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

    <!-- ── Panel de Configuración (Sidebar derecho) ───────────── -->
    <aside class="config-panel" id="configPanel">
        <div class="config-header">
            <h3><i class="bi bi-gear"></i> Configuración</h3>
            <button class="config-close" onclick="toggleConfigPanel()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="config-body">
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
    <input type="hidden" name="startpage" id="formStartpage">
    <input type="hidden" name="grapesjs_data" id="formGrapesData">
    <input type="hidden" name="seo_title" id="formSeoTitle">
    <input type="hidden" name="seo_description" id="formSeoDesc">
    <input type="hidden" name="version_note" id="formVersionNote">
</form>

<!-- ═══════════════════════════════════════════════════════════════
SCRIPTS — CodeMirror
═══════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/selection/active-line.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/xml-fold.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
SCRIPTS — GrapesJS
═══════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-forms@2.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-tabs@1.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-custom-code@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-touch@0.1.1/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-parser-postcss@1.0.0/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-export@1.0.12/dist/index.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
SCRIPT PRINCIPAL DEL BUILDER
═══════════════════════════════════════════════════════════════ -->
<script>
// ═══════════════════════════════════════════════════════════════
// CONFIGURACIÓN GLOBAL (completa y sin duplicados)
// ═══════════════════════════════════════════════════════════════
const CONFIG = {
    uploadUrl: '<?= e($uploadUrl) ?>',
    assetsUrl: '<?= e($assetsUrl) ?>',
    saveUrl: '<?= e($saveUrl) ?>',
    csrfToken: '<?= e($csrfToken) ?>',
    pageId: <?= (int)$page['id'] ?>,
    // Configuración de autoguardado
    autosaveUrl: '<?= e($saveUrl) ?>/autosave',
    autosaveInterval: 30000, // 30 segundos
    autosaveEnabled: true,
};

const existingAssets = <?= $jsonAssets ?>;
const pageData = {
    html: <?= $jsonHtml ?>,
    css: <?= $jsonCss ?>,
    php: <?= $jsonPhp ?>,
    js: <?= $jsonJs ?>,
};

// ═══════════════════════════════════════════════════════════════
// ESTADO GLOBAL (declarado UNA sola vez)
// ═══════════════════════════════════════════════════════════════
let cmHtml = null, cmCss = null, cmJs = null, cmPhp = null;
let gjsEditor = null;
let currentTab = 'visual';
let isSyncing = false;
let isSaving = false;
let isAutoSaving = false;
let isDirty = false;
let configPanelOpen = true;
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
// INICIALIZACIÓN AL CARGAR LA PÁGINA
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    try {
        initCodeMirror();
        initGrapesJS();
        bindEvents();
        startAutoSave();
        console.log('✅ Builder inicializado correctamente');
    } catch (err) {
        console.error('❌ Error inicializando builder:', err);
        showToast('✗ Error al inicializar el editor: ' + err.message, 'error');
    }
});

// ═══════════════════════════════════════════════════════════════
// INICIALIZAR CODEMIRROR
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

    // Cargar contenido inicial
    cmHtml.setValue(pageData.html || '');
    cmCss.setValue(pageData.css || '');
    if (cmPhp) cmPhp.setValue(pageData.php || '');
    cmJs.setValue(pageData.js || '');

    // Detectar cambios
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
// INICIALIZAR GRAPESJS
// ═══════════════════════════════════════════════════════════════
function initGrapesJS() {
    gjsEditor = grapesjs.init({
        container: '#gjs',
        showOffsets: true,
        fromElement: false,
        noticeOnUnload: false,
        storageManager: false,
        height: '100%',
        
        // Asset Manager
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
        
        plugins: [
            'gjs-blocks-basic',
            'grapesjs-plugin-forms',
            'grapesjs-tabs',
            'grapesjs-custom-code',
            'grapesjs-touch',
            'grapesjs-parser-postcss',
            'grapesjs-plugin-export'
        ],
        pluginsOpts: {
            'gjs-blocks-basic': { flexGrid: true },
            'grapesjs-plugin-forms': {},
            'grapesjs-tabs': { tabsBlock: { category: 'Extra' } },
            'grapesjs-custom-code': {},
        },
        
        canvas: {
            styles: [
                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'
            ],
            scripts: [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
            ]
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

    // Sincronización GrapesJS → CodeMirror
    gjsEditor.on('update', function() {
        if (isSyncing) return;
        isSyncing = true;
        try {
            cmHtml.setValue(gjsEditor.getHtml());
            cmCss.setValue(gjsEditor.getCss());
            updateSizeInfo();
        } catch (err) {
            console.warn('Error sincronizando GrapesJS → CodeMirror:', err);
        }
        setTimeout(function() { isSyncing = false; }, 150);
    });

    // Sincronización CodeMirror → GrapesJS (con debounce)
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

    // Detectar cambios en GrapesJS
    gjsEditor.on('change:canvasOffset', function() {
        isDirty = true;
        updateAutoSaveIndicator();
    });
}

// ═══════════════════════════════════════════════════════════════
// BIND DE EVENTOS
// ═══════════════════════════════════════════════════════════════
function bindEvents() {
    // Atajos de teclado globales
    document.addEventListener('keydown', function(e) {
        // Ctrl+S / Cmd+S → Guardar
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            savePage();
        }
        // Ctrl+P → Vista previa
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            document.getElementById('previewBtn').click();
        }
        // Escape → Cerrar panel config
        if (e.key === 'Escape' && configPanelOpen) {
            toggleConfigPanel();
        }
    });

    // Advertir al salir sin guardar
    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
        stopAutoSave();
    });

    // Pausar autoguardado cuando la pestaña no está visible
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
    
    // Actualizar pestañas activas
    document.querySelectorAll('.editor-tab').forEach(function(t) {
        t.classList.toggle('active', t.dataset.tab === tab);
    });
    
    // Mostrar/ocultar paneles
    document.querySelectorAll('.editor-pane').forEach(function(p) {
        p.classList.toggle('active', p.id === 'pane-' + tab);
    });
    
    // Actualizar barra de estado
    document.getElementById('statusMode').textContent = tabLabels[tab] || tab;
    
    // Refrescar CodeMirror al hacer visible
    setTimeout(function() {
        if (tab === 'html') cmHtml.refresh();
        if (tab === 'css') cmCss.refresh();
        if (tab === 'php' && cmPhp) cmPhp.refresh();
        if (tab === 'js') cmJs.refresh();
    }, 10);
    
    // Si volvemos al visual, sincronizar desde CodeMirror
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
// PANEL DE CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════
function toggleConfigPanel() {
    configPanelOpen = !configPanelOpen;
    var panel = document.getElementById('configPanel');
    panel.classList.toggle('collapsed', !configPanelOpen);
    
    setTimeout(function() {
        if (gjsEditor) gjsEditor.Canvas.refresh();
        [cmHtml, cmCss, cmJs, cmPhp].forEach(function(cm) {
            if (cm) cm.refresh();
        });
    }, 300);
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

function getToastIcon(type) {
    return {
        success: 'check-circle-fill',
        error: 'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    }[type] || 'info-circle-fill';
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
// AUTOGUARDADO AUTOMÁTICO
// ═══════════════════════════════════════════════════════════════
function startAutoSave() {
    if (!CONFIG.autosaveEnabled) return;
    stopAutoSave();
    
    autoSaveTimer = setInterval(function() {
        if (isDirty && !isSaving && !isAutoSaving) {
            performAutoSave();
        }
    }, CONFIG.autosaveInterval);
    
    console.log('🔄 Autoguardado iniciado (cada ' + (CONFIG.autosaveInterval / 1000) + 's)');
}

function stopAutoSave() {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
        autoSaveTimer = null;
    }
}

function updateAutoSaveIndicator() {
    var indicatorTop = document.getElementById('autoSaveStatusTop');
    var indicatorBottom = document.getElementById('autoSaveStatusBottom');
    if (!indicatorTop) return;
    
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
    
    indicatorTop.innerHTML = html;
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
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            lastAutoSaveTime = Date.now();
            isDirty = false;
            // Toast discreto (solo cada 5 minutos)
            if (!window._lastAutoSaveToast || Date.now() - window._lastAutoSaveToast > 300000) {
                showToast('💾 Autoguardado automático', 'info');
                window._lastAutoSaveToast = Date.now();
            }
        } else {
            console.warn('⚠️ Error en autoguardado:', result.message);
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
// GUARDAR PÁGINA (manual)
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
    
    // Llenar formulario
    document.getElementById('formTitle').value = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('formSlug').value = document.getElementById('pageSlug').value || generateSlug(document.getElementById('pageTitle').value);
    document.getElementById('formDesc').value = document.getElementById('pageDesc').value;
    document.getElementById('formTemplate').value = document.getElementById('pageTemplate').value;
    document.getElementById('formHtml').value = cmHtml ? cmHtml.getValue() : '';
    document.getElementById('formCss').value = cmCss ? cmCss.getValue() : '';
    document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
    document.getElementById('formJs').value = cmJs ? cmJs.getValue() : '';
    document.getElementById('formStatus').value = document.getElementById('pageStatus').value;
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
        // Intentar parsear como JSON
        var contentType = r.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return r.json();
        }
        // Si no es JSON, el guardado fue exitoso (redirect)
        return { success: true, message: 'Guardado correctamente' };
    })
    .then(function(result) {
        if (result.success) {
            showToast('✓ Página guardada correctamente', 'success');
            isDirty = false;
            lastAutoSaveTime = Date.now();
            
            // Actualizar preview URL si cambió el slug
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
        
        // Reiniciar autoguardado
        setTimeout(function() {
            startAutoSave();
        }, 1000);
    });
}
</script>
<!-- ═══ SISTEMA DE COMENTARIOS ═══ -->
<script src="<?= asset('js/comments.js') ?>"></script>
<script>
// Inicializar CommentsManager después de GrapesJS
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que gjsEditor esté disponible
    const initComments = setInterval(function() {
        if (typeof gjsEditor !== 'undefined' && gjsEditor) {
            clearInterval(initComments);
            
            commentsManager = new CommentsManager(
                <?= (int)$page['id'] ?>,
                gjsEditor,
                {
                    baseUrl: '<?= e(rtrim(BASE_URL ?? '', '/')) ?>',
                    csrfToken: '<?= e(csrf_token()) ?>',
                }
            );
            
            // Añadir botón en topbar
            const topbarRight = document.querySelector('.topbar-right');
            if (topbarRight) {
                const btn = document.createElement('button');
                btn.className = 'btn-builder btn-ghost';
                btn.onclick = () => commentsManager.togglePanel();
                btn.title = 'Comentarios';
                btn.innerHTML = `
                    <i class="bi bi-chat-dots"></i>
                    <span class="btn-label">Comentarios</span>
                `;
                // Insertar antes del botón de usuario
                const userChip = topbarRight.querySelector('.user-chip-mini');
                if (userChip) {
                    topbarRight.insertBefore(btn, userChip);
                } else {
                    topbarRight.appendChild(btn);
                }
            }
        }
    }, 100);
});
</script>
<script>
    // ═══════════════════════════════════════════════════════════════
// PREVISUALIZACIÓN EN VIVO (sin necesidad de guardar)
// ═══════════════════════════════════════════════════════════════
let previewWindow = null;
let previewDevice = 'desktop'; // desktop | tablet | mobile

// ═══════════════════════════════════════════════════════════════
// PREVISUALIZACIÓN EN VIVO
// ═══════════════════════════════════════════════════════════════
var previewWindow = null;
var previewDevice = 'desktop';

function preview() {
    // Sincronizar contenido actual
    var html = '', css = '', js = '';
    
    if (currentTab === 'visual') {
        html = gjsEditor.getHtml();
        css = gjsEditor.getCss();
        js = cmJs.getValue();
    } else {
        html = cmHtml.getValue();
        css = cmCss.getValue();
        js = cmJs.getValue();
    }
    
    var pageTitle = document.getElementById('pageTitle').value || 'Vista previa';
    
    var fullHtml = '<!DOCTYPE html>\n' +
'<html lang="es">\n' +
'<head>\n' +
'    <meta charset="UTF-8">\n' +
'    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n' +
'    <title>' + escapeHtml(pageTitle) + ' · Vista previa</title>\n' +
'    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">\n' +
'    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">\n' +
'    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">\n' +
'    <style>\n' +
'        * { margin: 0; padding: 0; box-sizing: border-box; }\n' +
'        body { font-family: "DM Sans", sans-serif; background: #f5f5f5; padding-top: 60px; }\n' +
'        .preview-toolbar {\n' +
'            position: fixed; top: 0; left: 0; right: 0; height: 60px;\n' +
'            background: #1c2333; border-bottom: 1px solid #30363d;\n' +
'            display: flex; align-items: center; justify-content: space-between;\n' +
'            padding: 0 20px; z-index: 9999;\n' +
'        }\n' +
'        .preview-toolbar .left { display: flex; align-items: center; gap: 15px; color: #e6edf3; }\n' +
'        .preview-toolbar .logo {\n' +
'            width: 32px; height: 32px; border-radius: 6px;\n' +
'            background: linear-gradient(135deg, #00d4aa, #00a88a);\n' +
'            display: grid; place-items: center; color: #0d1117; font-weight: 700;\n' +
'        }\n' +
'        .preview-toolbar .title { font-size: 14px; font-weight: 600; }\n' +
'        .preview-toolbar .badge-live {\n' +
'            background: #ef4444; color: white; padding: 2px 8px;\n' +
'            border-radius: 999px; font-size: 10px; font-weight: 700;\n' +
'            text-transform: uppercase; animation: pulse 2s infinite;\n' +
'        }\n' +
'        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }\n' +
'        .preview-toolbar .devices { display: flex; gap: 8px; }\n' +
'        .preview-toolbar .device-btn {\n' +
'            background: transparent; border: 1px solid #30363d; color: #8b949e;\n' +
'            padding: 6px 12px; border-radius: 6px; cursor: pointer;\n' +
'            font-size: 13px; transition: all 0.2s;\n' +
'        }\n' +
'        .preview-toolbar .device-btn:hover { background: #21283b; color: #e6edf3; }\n' +
'        .preview-toolbar .device-btn.active { background: #00d4aa; color: #0d1117; border-color: #00d4aa; }\n' +
'        .preview-container {\n' +
'            display: flex; justify-content: center; padding: 20px;\n' +
'            min-height: calc(100vh - 60px); transition: all 0.3s ease;\n' +
'        }\n' +
'        .preview-frame {\n' +
'            background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.15);\n' +
'            transition: all 0.3s ease; width: 100%; max-width: 100%;\n' +
'            min-height: calc(100vh - 100px); border-radius: 8px; overflow: hidden;\n' +
'        }\n' +
'        .preview-container[data-device="tablet"] .preview-frame { max-width: 768px; border: 8px solid #1c2333; }\n' +
'        .preview-container[data-device="mobile"] .preview-frame { max-width: 375px; border: 8px solid #1c2333; border-radius: 20px; }\n' +
'        .preview-content img { max-width: 100%; height: auto; }\n' +
'    </style>\n' +
'    <style id="user-css">' + css + '</style>\n' +
'</head>\n' +
'<body>\n' +
'    <div class="preview-toolbar">\n' +
'        <div class="left">\n' +
'            <div class="logo">P</div>\n' +
'            <div class="title">' + escapeHtml(pageTitle) + '</div>\n' +
'            <span class="badge-live">● EN VIVO</span>\n' +
'        </div>\n' +
'        <div class="devices">\n' +
'            <button class="device-btn active" data-device="desktop" onclick="setPreviewDevice(\'desktop\')">🖥️ Desktop</button>\n' +
'            <button class="device-btn" data-device="tablet" onclick="setPreviewDevice(\'tablet\')">📱 Tablet</button>\n' +
'            <button class="device-btn" data-device="mobile" onclick="setPreviewDevice(\'mobile\')">📱 Móvil</button>\n' +
'        </div>\n' +
'    </div>\n' +
'    <div class="preview-container" data-device="desktop">\n' +
'        <div class="preview-frame">\n' +
'            <div class="preview-content">' + (html || '<p style="text-align:center;padding:40px;color:#8b949e;">El contenido aparecerá aquí</p>') + '</div>\n' +
'        </div>\n' +
'    </div>\n' +
'    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></' + 'script>\n' +
'    <script>try { ' + js + ' } catch(e) { console.error(e); }</' + 'script>\n' +
'    <script>\n' +
'        function setPreviewDevice(device) {\n' +
'            document.querySelector(".preview-container").setAttribute("data-device", device);\n' +
'            document.querySelectorAll(".device-btn").forEach(function(btn) {\n' +
'                btn.classList.toggle("active", btn.dataset.device === device);\n' +
'            });\n' +
'        }\n' +
'    </' + 'script>\n' +
'</body>\n' +
'</html>';
    
    if (previewWindow && !previewWindow.closed) {
        previewWindow.document.open();
        previewWindow.document.write(fullHtml);
        previewWindow.document.close();
        previewWindow.focus();
    } else {
        previewWindow = window.open('', '_blank', 'width=1200,height=800');
        if (previewWindow) {
            previewWindow.document.open();
            previewWindow.document.write(fullHtml);
            previewWindow.document.close();
        } else {
            showToast('⚠ Permite popups para previsualizar', 'warning');
        }
    }
}

/**
 * Escapa HTML para evitar XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Construye el documento HTML completo para la preview
 */
function buildPreviewDocument(html, css, js) {
    const pageTitle = document.getElementById('pageTitle').value || 'Vista previa';
    
    return `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(pageTitle)} · Vista previa</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Barra de herramientas de preview -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            padding-top: 60px;
        }
        
        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #1c2333;
            border-bottom: 1px solid #30363d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .preview-toolbar .left {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #e6edf3;
        }
        
        .preview-toolbar .logo {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: linear-gradient(135deg, #00d4aa, #00a88a);
            display: grid;
            place-items: center;
            color: #0d1117;
            font-weight: 700;
        }
        
        .preview-toolbar .title {
            font-size: 14px;
            font-weight: 600;
        }
        
        .preview-toolbar .badge-live {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .preview-toolbar .devices {
            display: flex;
            gap: 8px;
        }
        
        .preview-toolbar .device-btn {
            background: transparent;
            border: 1px solid #30363d;
            color: #8b949e;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .preview-toolbar .device-btn:hover {
            background: #21283b;
            color: #e6edf3;
        }
        
        .preview-toolbar .device-btn.active {
            background: #00d4aa;
            color: #0d1117;
            border-color: #00d4aa;
        }
        
        .preview-toolbar .actions {
            display: flex;
            gap: 8px;
        }
        
        .preview-toolbar .action-btn {
            background: transparent;
            border: 1px solid #30363d;
            color: #8b949e;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .preview-toolbar .action-btn:hover {
            background: #21283b;
            color: #e6edf3;
        }
        
        .preview-container {
            display: flex;
            justify-content: center;
            padding: 20px;
            min-height: calc(100vh - 60px);
            transition: all 0.3s ease;
        }
        
        .preview-frame {
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            width: 100%;
            max-width: 100%;
            min-height: calc(100vh - 100px);
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Modos de dispositivo */
        .preview-container[data-device="tablet"] .preview-frame {
            max-width: 768px;
            border: 8px solid #1c2333;
        }
        
        .preview-container[data-device="mobile"] .preview-frame {
            max-width: 375px;
            border: 8px solid #1c2333;
            border-radius: 20px;
        }
        
        .preview-content {
            padding: 0;
        }
        
        /* Estilos del contenido */
        .preview-content img {
            max-width: 100%;
            height: auto;
        }
        
        .preview-content a {
            color: #00d4aa;
        }
        
        /* Aviso cuando no hay contenido */
        .preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            color: #8b949e;
            text-align: center;
            padding: 40px;
        }
        
        .preview-empty i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
    
    <!-- CSS del usuario -->
    <style id="user-css">
        ${css}
    </style>
</head>
<body>
    <!-- Barra de herramientas -->
    <div class="preview-toolbar">
        <div class="left">
            <div class="logo">P</div>
            <div class="title">${escapeHtml(pageTitle)}</div>
            <span class="badge-live">● EN VIVO</span>
        </div>
        
        <div class="devices">
            <button class="device-btn active" data-device="desktop" onclick="setPreviewDevice('desktop')">
                <i class="bi bi-display"></i> Desktop
            </button>
            <button class="device-btn" data-device="tablet" onclick="setPreviewDevice('tablet')">
                <i class="bi bi-tablet"></i> Tablet
            </button>
            <button class="device-btn" data-device="mobile" onclick="setPreviewDevice('mobile')">
                <i class="bi bi-phone"></i> Móvil
            </button>
        </div>
        
        <div class="actions">
            <button class="action-btn" onclick="refreshPreview()" title="Actualizar">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
            <button class="action-btn" onclick="window.close()" title="Cerrar">
                <i class="bi bi-x-lg"></i> Cerrar
            </button>
        </div>
    </div>
    
    <!-- Contenedor de preview -->
    <div class="preview-container" data-device="desktop">
        <div class="preview-frame">
            <div class="preview-content">
                ${html || '<div class="preview-empty"><i class="bi bi-file-earmark"></i><p>El contenido aparecerá aquí</p></div>'}
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS del usuario -->
    <script>
        try {
            ${js}
        } catch(e) {
            console.error('Error en JavaScript del usuario:', e);
        }
    </script>
    
    <!-- Script de control de preview -->
    <script>
        function setPreviewDevice(device) {
            document.querySelector('.preview-container').setAttribute('data-device', device);
            document.querySelectorAll('.device-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.device === device);
            });
        }
        
        function refreshPreview() {
            // Recargar el contenido (se ejecuta desde la ventana padre)
            if (window.opener && window.opener.preview) {
                window.opener.preview();
            } else {
                location.reload();
            }
        }
        
        // Comunicación con la ventana padre para actualización en tiempo real
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'update-preview') {
                if (event.data.html !== undefined) {
                    document.querySelector('.preview-content').innerHTML = event.data.html;
                }
                if (event.data.css !== undefined) {
                    document.getElementById('user-css').textContent = event.data.css;
                }
            }
        });
    </script>
</body>
</html>`;
}

/**
 * Cambia el dispositivo de preview
 */
function setPreviewDevice(device) {
    previewDevice = device;
    updatePreviewDevice(device);
}

/**
 * Actualiza el dispositivo en la ventana de preview
 */
function updatePreviewDevice(device) {
    if (previewWindow && !previewWindow.closed) {
        try {
            previewWindow.setPreviewDevice(device);
        } catch(e) {
            console.warn('No se pudo cambiar dispositivo:', e);
        }
    }
}

/**
 * Refresca la preview con el contenido actual
 */
function refreshPreview() {
    if (previewWindow && !previewWindow.closed) {
        preview();
    }
}

/**
 * Sincroniza el contenido a los campos ocultos
 */
function syncToHidden() {
    if (currentTab === 'visual') {
        document.getElementById('hidden-html').value = gjsEditor.getHtml();
        document.getElementById('hidden-css').value = gjsEditor.getCss();
        document.getElementById('hidden-js').value = cmJs ? cmJs.getValue() : '';
        if (cmPhp) document.getElementById('hidden-php').value = cmPhp.getValue();
        document.getElementById('grapesjs-data').value = JSON.stringify(gjsEditor.getProjectData());
    } else {
        document.getElementById('hidden-html').value = cmHtml.getValue();
        document.getElementById('hidden-css').value = cmCss.getValue();
        document.getElementById('hidden-js').value = cmJs.getValue();
        if (cmPhp) document.getElementById('hidden-php').value = cmPhp.getValue();
        document.getElementById('grapesjs-data').value = JSON.stringify(gjsEditor.getProjectData());
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTUALIZACIÓN EN TIEMPO REAL DE LA PREVIEW
// ═══════════════════════════════════════════════════════════════
let previewUpdateTimer = null;

function schedulePreviewUpdate() {
    if (!previewWindow || previewWindow.closed) return;
    
    clearTimeout(previewUpdateTimer);
    previewUpdateTimer = setTimeout(() => {
        updateLivePreview();
    }, 500); // Debounce de 500ms
}

function updateLivePreview() {
    if (!previewWindow || previewWindow.closed) return;
    
    try {
        let html, css;
        
        if (currentTab === 'visual') {
            html = gjsEditor.getHtml();
            css = gjsEditor.getCss();
        } else {
            html = cmHtml.getValue();
            css = cmCss.getValue();
        }
        
        // Enviar mensaje a la ventana de preview
        previewWindow.postMessage({
            type: 'update-preview',
            html: html,
            css: css
        }, '*');
    } catch(e) {
        console.warn('Error actualizando preview:', e);
    }
}

// Escuchar cambios en los editores para actualizar preview
if (typeof cmHtml !== 'undefined') {
    cmHtml.on('change', schedulePreviewUpdate);
    cmCss.on('change', schedulePreviewUpdate);
}

if (typeof gjsEditor !== 'undefined') {
    gjsEditor.on('update', schedulePreviewUpdate);
}
    </script>

</body>
</html>