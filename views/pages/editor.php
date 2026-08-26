<?php
/**
 * Vista del Editor Visual - PePiuoX Editor
 * 
 * Variables esperadas:
 *   $page, $jsonHtml, $jsonCss, $jsonPhp, $jsonJs, $jsonAssets
 */

$pageTitle = 'Editor: ' . ($page['title'] ?? 'Nueva página');
$pageId = (int)($page['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · PePiuoX Editor</title>

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

    <!-- Estilos del Editor -->
    <style>
        :root {
            --editor-bg: #0d1117;
            --editor-bg-2: #161b22;
            --editor-bg-3: #1c2333;
            --editor-border: #30363d;
            --editor-text: #e6edf3;
            --editor-text-muted: #8b949e;
            --editor-accent: #00d4aa;
            --editor-accent-dark: #00a88a;
            --editor-danger: #ef4444;
            --editor-warning: #f59e0b;
            --editor-success: #22c55e;
            --editor-topbar-h: 56px;
            --editor-sidebar-w: 320px;
            --editor-radius: 8px;
            --editor-transition: 0.2s ease;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            background: var(--editor-bg);
            color: var(--editor-text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            overflow: hidden;
            height: 100vh;
        }

        /* Topbar */
        .editor-topbar {
            height: var(--editor-topbar-h);
            background: var(--editor-bg-2);
            border-bottom: 1px solid var(--editor-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            gap: 1rem;
        }

        .topbar-left, .topbar-center, .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .editor-logo {
            width: 36px;
            height: 36px;
            border-radius: var(--editor-radius);
            background: linear-gradient(135deg, var(--editor-accent), var(--editor-accent-dark));
            color: var(--editor-bg);
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            text-decoration: none;
            font-weight: 700;
        }

        .page-title-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--editor-text);
            font-weight: 600;
        }

        .page-id-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            background: var(--editor-bg-3);
            border: 1px solid var(--editor-border);
            border-radius: 999px;
            color: var(--editor-text-muted);
        }

        .btn-editor {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.9rem;
            border: 1px solid transparent;
            border-radius: var(--editor-radius);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--editor-transition);
            text-decoration: none;
            font-family: inherit;
        }

        .btn-editor.btn-ghost {
            background: transparent;
            color: var(--editor-text-muted);
            border-color: var(--editor-border);
        }

        .btn-editor.btn-ghost:hover {
            background: var(--editor-bg-3);
            color: var(--editor-text);
            border-color: var(--editor-accent);
        }

        .btn-editor.btn-primary {
            background: var(--editor-accent);
            color: var(--editor-bg);
            border-color: var(--editor-accent);
            font-weight: 600;
        }

        .btn-editor.btn-primary:hover {
            background: var(--editor-accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 212, 170, 0.3);
        }

        .user-chip-mini {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.7rem;
            background: var(--editor-bg-3);
            border: 1px solid var(--editor-border);
            border-radius: 999px;
            font-size: 0.8rem;
            color: var(--editor-text-muted);
        }

        /* Layout principal */
        .editor-main {
            display: flex;
            height: calc(100vh - var(--editor-topbar-h));
        }

        /* Sidebar */
        .editor-sidebar {
            width: var(--editor-sidebar-w);
            background: var(--editor-bg-2);
            border-right: 1px solid var(--editor-border);
            display: flex;
            flex-direction: column;
            transition: width var(--editor-transition);
            overflow: hidden;
        }

        .editor-sidebar.collapsed {
            width: 0;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid var(--editor-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--editor-bg-3);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-close {
            background: transparent;
            border: none;
            color: var(--editor-text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .sidebar-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--editor-danger);
        }

        .sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .config-section {
            margin-bottom: 1rem;
        }

        .config-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--editor-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
        }

        .config-input, .config-textarea, .config-select {
            width: 100%;
            padding: 0.55rem 0.75rem;
            background: var(--editor-bg);
            border: 1px solid var(--editor-border);
            border-radius: 6px;
            color: var(--editor-text);
            font-size: 0.85rem;
            font-family: inherit;
        }

        .config-input:focus, .config-textarea:focus, .config-select:focus {
            outline: none;
            border-color: var(--editor-accent);
        }

        .config-textarea {
            resize: vertical;
            min-height: 60px;
        }

        .config-help {
            display: block;
            font-size: 0.7rem;
            color: var(--editor-text-muted);
            margin-top: 0.25rem;
            font-style: italic;
        }

        .config-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .config-checkbox input {
            width: 16px;
            height: 16px;
            accent-color: var(--editor-accent);
        }

        /* Panel del editor */
        .editor-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .editor-tabs {
            display: flex;
            background: var(--editor-bg-2);
            border-bottom: 1px solid var(--editor-border);
            overflow-x: auto;
        }

        .editor-tab {
            padding: 0.75rem 1.25rem;
            border: none;
            background: transparent;
            color: var(--editor-text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-family: inherit;
        }

        .editor-tab:hover {
            color: var(--editor-accent);
            background: var(--editor-bg-3);
        }

        .editor-tab.active {
            color: var(--editor-accent);
            border-bottom-color: var(--editor-accent);
            background: var(--editor-bg-3);
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

        .editor-pane.active {
            display: block;
        }

        #pane-visual {
            background: var(--editor-bg);
        }

        .statusbar {
            height: 28px;
            background: var(--editor-bg-2);
            border-top: 1px solid var(--editor-border);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            font-size: 0.75rem;
            color: var(--editor-text-muted);
            gap: 0.75rem;
        }

        .statusbar-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--editor-success);
        }

        .statusbar-dot.saving {
            background: var(--editor-warning);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Toasts */
        .toast-container {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .toast {
            padding: 0.75rem 1rem;
            background: var(--editor-bg-3);
            border: 1px solid var(--editor-border);
            border-left: 4px solid var(--editor-success);
            border-radius: var(--editor-radius);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            color: var(--editor-text);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .toast.error { border-left-color: var(--editor-danger); }
        .toast.warning { border-left-color: var(--editor-warning); }

        /* GrapesJS overrides */
        .gjs-one-bg { background-color: var(--editor-bg-2) !important; }
        .gjs-two-bg { background-color: var(--editor-bg-3) !important; }
        .gjs-three-bg { background-color: var(--editor-bg) !important; }
        .gjs-four-bg { background-color: var(--editor-accent) !important; }
        .gjs-one-color { color: var(--editor-text) !important; }
        .gjs-two-color { color: var(--editor-text-muted) !important; }
        .gjs-three-color { color: var(--editor-accent) !important; }
        .gjs-four-color { color: var(--editor-bg) !important; }

        .gjs-block {
            border-radius: 6px;
            border: 1px solid var(--editor-border);
            background: var(--editor-bg-2);
        }

        .gjs-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border-color: var(--editor-accent);
        }

        .gjs-cv-canvas {
            background: var(--editor-bg) !important;
        }

        .gjs-selected {
            outline: 2px solid var(--editor-accent) !important;
        }

        /* CodeMirror */
        .CodeMirror {
            height: 100% !important;
            font-family: var(--font-mono);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="editor-topbar">
        <div class="topbar-left">
            <a href="<?= url('admin/pages') ?>" class="editor-logo" title="Volver al listado">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="page-title-display">
                <i class="bi bi-file-earmark-richtext"></i>
                <span id="displayTitle"><?= e($page['title']) ?></span>
                <span class="page-id-badge">#<?= $pageId ?></span>
            </div>
        </div>
        
        <div class="topbar-center">
            <span id="autoSaveStatus" style="font-size:0.8rem;color:var(--editor-text-muted);font-style:italic"></span>
        </div>
        
        <div class="topbar-right">
            <button class="btn-editor btn-ghost" onclick="toggleSidebar()" title="Configuración">
                <i class="bi bi-sliders"></i>
                <span class="btn-label">Config</span>
            </button>
            <button class="btn-editor btn-ghost" onclick="reloadAssets()" title="Recargar assets">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <?php if ($pageId > 0): ?>
            <a href="<?= url("admin/pages/$pageId/preview") ?>" target="_blank" class="btn-editor btn-ghost" id="previewBtn">
                <i class="bi bi-eye"></i>
                <span class="btn-label">Preview</span>
            </a>
            <?php endif; ?>
            <button class="btn-editor btn-primary" onclick="savePage()" id="saveBtn">
                <i class="bi bi-save"></i>
                <span class="btn-label">Guardar</span>
            </button>
            <div class="user-chip-mini">
                <i class="bi bi-person-circle"></i>
                <span><?= e(Auth::user()['username'] ?? 'User') ?></span>
            </div>
        </div>
    </div>

    <!-- Layout principal -->
    <div class="editor-main">
        <!-- Sidebar -->
        <aside class="editor-sidebar" id="editorSidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-gear"></i> Configuración</h3>
                <button class="sidebar-close" onclick="toggleSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="sidebar-body">
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
                    <div style="font-size:0.75rem;color:var(--editor-text-muted)">
                        <div>Creada: <?= e($page['created_at']) ?></div>
                        <div>Actualizada: <?= e($page['updated_at']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Panel del editor -->
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
                <span style="margin-left:auto" class="statusbar-right">
                    <span id="statusVersion">v<?= (int)($page['version'] ?? 1) ?></span>
                    <span class="statusbar-separator">|</span>
                    <span id="statusSize">0 KB</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Formulario oculto -->
    <form id="saveForm" method="POST" action="<?= url('admin/pages/editor/save') ?>" style="display:none">
        <?= CSRF::field() ?>
        <input type="hidden" name="page_id" id="formPageId" value="<?= $pageId ?>">
        <input type="hidden" name="title" id="formTitle">
        <input type="hidden" name="slug" id="formSlug">
        <input type="hidden" name="description" id="formDesc">
        <input type="hidden" name="content_html" id="formHtml">
        <input type="hidden" name="content_css" id="formCss">
        <input type="hidden" name="content_php" id="formPhp">
        <input type="hidden" name="content_js" id="formJs">
        <input type="hidden" name="status" id="formStatus">
        <input type="hidden" name="startpage" id="formStartpage">
    </form>

    <!-- Scripts -->
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

    <script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-forms@2.0.6/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-tabs@1.0.6/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-custom-code@1.0.2/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-touch@0.1.1/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-parser-postcss@1.0.0/dist/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-export@1.0.12/dist/index.min.js"></script>

    <script>
        const CONFIG = {
            uploadUrl: '<?= url('admin/pages/editor/upload') ?>',
            saveUrl: '<?= url('admin/pages/editor/save') ?>',
            csrfToken: '<?= e(csrf_token()) ?>',
            pageId: <?= $pageId ?>
        };

        const existingAssets = <?= $jsonAssets ?>;
        const pageData = {
            html: <?= $jsonHtml ?>,
            css: <?= $jsonCss ?>,
            php: <?= $jsonPhp ?>,
            js: <?= $jsonJs ?>
        };

        // Inicialización de CodeMirror
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
            foldGutter: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-S': () => savePage()
            }
        };

        const cmHtml = CodeMirror.fromTextArea(document.getElementById('editor-html'), {
            ...cmOptions, mode: 'htmlmixed'
        });
        const cmCss = CodeMirror.fromTextArea(document.getElementById('editor-css'), {
            ...cmOptions, mode: 'css'
        });
        const cmPhp = document.getElementById('editor-php') 
            ? CodeMirror.fromTextArea(document.getElementById('editor-php'), {
                ...cmOptions, mode: 'application/x-httpd-php'
            })
            : null;
        const cmJs = CodeMirror.fromTextArea(document.getElementById('editor-js'), {
            ...cmOptions, mode: 'javascript'
        });

        cmHtml.setValue(pageData.html || '');
        cmCss.setValue(pageData.css || '');
        if (cmPhp) cmPhp.setValue(pageData.php || '');
        cmJs.setValue(pageData.js || '');

        // Inicialización de GrapesJS
        const gjsEditor = grapesjs.init({
            container: '#gjs',
            showOffsets: true,
            fromElement: false,
            noticeOnUnload: false,
            storageManager: false,
            height: '100%',
            assetManager: {
                assets: existingAssets,
                upload: CONFIG.uploadUrl,
                uploadName: 'files[]',
                multiUpload: true,
                autoAdd: true,
                accept: 'image/*'
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
                'grapesjs-custom-code': {}
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

        // Sincronización GrapesJS ↔ CodeMirror
        let isSyncing = false;
        let syncTimer = null;

        gjsEditor.on('update', () => {
            if (isSyncing) return;
            isSyncing = true;
            cmHtml.setValue(gjsEditor.getHtml());
            cmCss.setValue(gjsEditor.getCss());
            updateSizeInfo();
            setTimeout(() => { isSyncing = false; }, 150);
        });

        cmHtml.on('change', () => {
            if (isSyncing) return;
            clearTimeout(syncTimer);
            syncTimer = setTimeout(() => {
                isSyncing = true;
                try { gjsEditor.setComponents(cmHtml.getValue()); } catch(e) {}
                updateSizeInfo();
                setTimeout(() => { isSyncing = false; }, 200);
            }, 600);
        });

        cmCss.on('change', () => {
            if (isSyncing) return;
            clearTimeout(syncTimer);
            syncTimer = setTimeout(() => {
                isSyncing = true;
                try { gjsEditor.setStyle(cmCss.getValue()); } catch(e) {}
                setTimeout(() => { isSyncing = false; }, 200);
            }, 600);
        });

        // Pestañas del editor
        let currentTab = 'visual';
        const tabLabels = {
            visual: 'Editor visual',
            html: 'HTML',
            css: 'CSS',
            php: 'PHP',
            js: 'JavaScript'
        };

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.editor-tab').forEach(t => {
                t.classList.toggle('active', t.dataset.tab === tab);
            });
            document.querySelectorAll('.editor-pane').forEach(p => {
                p.classList.toggle('active', p.id === 'pane-' + tab);
            });
            document.getElementById('statusMode').textContent = tabLabels[tab] || tab;
            setTimeout(() => {
                if (tab === 'html') cmHtml.refresh();
                if (tab === 'css') cmCss.refresh();
                if (tab === 'php' && cmPhp) cmPhp.refresh();
                if (tab === 'js') cmJs.refresh();
            }, 10);
            if (tab === 'visual') {
                isSyncing = true;
                try { gjsEditor.setComponents(cmHtml.getValue()); } catch(e) {}
                try { gjsEditor.setStyle(cmCss.getValue()); } catch(e) {}
                setTimeout(() => { isSyncing = false; }, 200);
            }
            updateStatusInfo();
        }

        function updateStatusInfo() {
            const cm = { html: cmHtml, css: cmCss, php: cmPhp, js: cmJs }[currentTab];
            if (cm) {
                const pos = cm.getCursor();
                document.getElementById('statusInfo').textContent = 
                    `Línea ${pos.line + 1}, Col ${pos.ch + 1}`;
            } else {
                document.getElementById('statusInfo').textContent = '';
            }
        }

        [cmHtml, cmCss, cmPhp, cmJs].forEach(cm => {
            if (!cm) return;
            cm.on('cursorActivity', () => {
                if ({ html: cmHtml, css: cmCss, php: cmPhp, js: cmJs }[currentTab] === cm) {
                    updateStatusInfo();
                }
            });
            cm.on('change', updateSizeInfo);
        });

        // Sidebar
        let sidebarOpen = true;
        function toggleSidebar() {
            sidebarOpen = !sidebarOpen;
            document.getElementById('editorSidebar').classList.toggle('collapsed', !sidebarOpen);
            setTimeout(() => {
                gjsEditor.Canvas.refresh();
                [cmHtml, cmCss, cmPhp, cmJs].forEach(cm => cm && cm.refresh());
            }, 300);
        }

        // Funciones auxiliares
        function updateDisplayTitle() {
            const t = document.getElementById('pageTitle').value || 'Sin título';
            document.getElementById('displayTitle').textContent = t;
            document.title = t + ' · Editor';
        }

        function autoSlug() {
            const title = document.getElementById('pageTitle').value;
            const slug = title.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('pageSlug').value = slug || 'page-' + Date.now();
        }

        function updateSizeInfo() {
            const html = cmHtml.getValue();
            const css = cmCss.getValue();
            const size = new Blob([html, css]).size;
            const kb = (size / 1024).toFixed(1);
            document.getElementById('statusSize').textContent = kb + ' KB';
        }

        function showToast(msg, type = 'success') {
            const c = document.getElementById('toastContainer');
            const t = document.createElement('div');
            t.className = 'toast toast-' + type;
            t.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${msg}`;
            c.appendChild(t);
            setTimeout(() => {
                t.style.opacity = '0';
                t.style.transform = 'translateX(100%)';
                setTimeout(() => t.remove(), 300);
            }, 3500);
        }

        function reloadAssets() {
            fetch(CONFIG.uploadUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(assets => {
                    gjsEditor.AssetManager.clear();
                    gjsEditor.AssetManager.add(assets);
                    gjsEditor.AssetManager.render();
                    showToast('✓ Assets recargados: ' + assets.length, 'success');
                })
                .catch(err => showToast('✗ Error al recargar', 'error'));
        }

        // Guardar página
        let isSaving = false;
        function savePage() {
            if (isSaving) return;
            isSaving = true;
            const btn = document.getElementById('saveBtn');
            const dot = document.getElementById('statusDot');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span class="btn-label">Guardando...</span>';
            btn.disabled = true;
            dot.classList.add('saving');
            document.getElementById('autoSaveStatus').textContent = 'Guardando...';

            document.getElementById('formTitle').value = document.getElementById('pageTitle').value || 'Sin título';
            document.getElementById('formSlug').value = document.getElementById('pageSlug').value || generateSlug(document.getElementById('pageTitle').value);
            document.getElementById('formDesc').value = document.getElementById('pageDesc').value;
            document.getElementById('formHtml').value = cmHtml.getValue();
            document.getElementById('formCss').value = cmCss.getValue();
            document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
            document.getElementById('formJs').value = cmJs.getValue();
            document.getElementById('formStatus').value = document.getElementById('pageStatus').value;
            document.getElementById('formStartpage').value = document.getElementById('pageStartpage').checked ? '1' : '0';

            const formData = new FormData(document.getElementById('saveForm'));
            fetch(CONFIG.saveUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    showToast('✓ Página guardada correctamente', 'success');
                    document.getElementById('autoSaveStatus').textContent = 
                        'Guardado: ' + new Date().toLocaleTimeString();
                    if (result.id && !CONFIG.pageId) {
                        CONFIG.pageId = result.id;
                        document.getElementById('formPageId').value = result.id;
                    }
                } else {
                    showToast('✗ ' + (result.message || 'Error al guardar'), 'error');
                }
            })
            .catch(err => {
                showToast('✗ Error: ' + err.message, 'error');
            })
            .finally(() => {
                isSaving = false;
                btn.innerHTML = '<i class="bi bi-save"></i> <span class="btn-label">Guardar</span>';
                btn.disabled = false;
                dot.classList.remove('saving');
                setTimeout(() => {
                    document.getElementById('autoSaveStatus').textContent = '';
                }, 3000);
            });
        }

        function generateSlug(text) {
            return (text || '').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'page-' + Date.now();
        }

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                savePage();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                document.getElementById('previewBtn')?.click();
            }
            if (e.key === 'Escape' && sidebarOpen) {
                toggleSidebar();
            }
        });

        // Inicialización
        window.addEventListener('load', () => {
            setTimeout(() => {
                gjsEditor.Canvas.refresh();
                updateSizeInfo();
            }, 200);
        });

        let isDirty = false;
        [cmHtml, cmCss, cmJs].forEach(cm => {
            cm.on('change', () => { isDirty = true; });
        });
        gjsEditor.on('change:canvasOffset', () => { isDirty = true; });

        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>