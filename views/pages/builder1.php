<?php
/**
 * Builder Visual Avanzado — PePiuoX Maker
 * Ubicación: views/pages/builder.php
 * 
 * Variables esperadas desde PagesController::builder():
 *   $page, $templates, $existingAssets, $seo
 */

use App\Core\Auth;

// Verificación de permisos (doble seguridad)
if (!Auth::can('pages.edit')) {
    \App\Core\Response::abort(403, 'No tienes permisos para usar el builder visual.');
}

$pageTitle = 'Builder: ' . ($page['title'] ?? 'Nueva página');

// Codificar contenido para JavaScript
$jsonHtml = json_encode($page['content_html'] ?? '<div style="padding:40px;text-align:center;"><h1>Mi Nueva Página</h1><p>Comienza a editar tu contenido aquí.</p></div>', JSON_UNESCAPED_UNICODE);
$jsonCss  = json_encode($page['content_css'] ?? "body {\n  font-family: 'Inter', sans-serif;\n  margin: 0;\n  padding: 0;\n  background: #ffffff;\n  color: #333333;\n}", JSON_UNESCAPED_UNICODE);
$jsonPhp  = json_encode($page['content_php'] ?? '', JSON_UNESCAPED_UNICODE);
$jsonJs   = json_encode($page['content_js'] ?? '', JSON_UNESCAPED_UNICODE);

// URLs del sistema (COHERENTES con Router.php)
$uploadUrl  = url('admin/pages/' . $page['id'] . '/builder/upload');
$assetsUrl  = url('admin/pages/assets');
$saveUrl    = url('admin/pages/' . $page['id']);
$previewUrl = url($page['slug']);
$currentUser = Auth::user();
?>


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
    
    <!-- Estilos del Builder -->
    <link rel="stylesheet" href="<?= asset('css/builder.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/collaboration.css') ?>">


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
        <span class="autosave-status" id="autoSaveStatus"></span>
    </div>
    
    <div class="topbar-right">
        <button class="btn-builder btn-ghost" onclick="toggleConfigPanel()" title="Configuración">
            <i class="bi bi-sliders"></i>
            <span class="btn-label">Config</span>
        </button>
        
        <button class="btn-builder btn-ghost" onclick="reloadAssets()" title="Recargar assets">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        
        <a href="<?= e($previewUrl) ?>" target="_blank" class="btn-builder btn-outline" id="previewBtn">
            <i class="bi bi-eye"></i>
            <span class="btn-label">Preview</span>
        </a>
        
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
            <span style="margin-left:auto" class="statusbar-right">
                <!-- ═══ NUEVO: Indicador de autoguardado ═══ -->
                <span id="autoSaveStatus" style="margin-right:1rem">
                    <i class="bi bi-check-circle" style="color:var(--success)"></i> Todo guardado
                </span>
                <span id="statusVersion">v<?= (int)($page['version'] ?? 1) ?></span>
                <span class="statusbar-separator">|</span>
                <span id="statusSize">0 KB</span>
            </span>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════════════════════
FORMULARIO OCULTO PARA GUARDAR
═══════════════════════════════════════════════════════════════ -->
<form id="saveForm" style="display:none">
    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
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
SCRIPTS
═══════════════════════════════════════════════════════════════ -->
<!-- CodeMirror -->
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

<!-- GrapesJS -->
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-forms@2.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-tabs@1.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-custom-code@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-touch@0.1.1/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-parser-postcss@1.0.0/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-export@1.0.12/dist/index.min.js"></script>

<script>
// ═══════════════════════════════════════════════════════════════
// CONFIGURACIÓN GLOBAL
// ═══════════════════════════════════════════════════════════════
const CONFIG = {
    uploadUrl: '<?= e($uploadUrl) ?>',
    assetsUrl: '<?= e($assetsUrl) ?>',
    saveUrl: '<?= e($saveUrl) ?>',
    csrfToken: '<?= e(csrf_token()) ?>',
    pageId: <?= (int)$page['id'] ?>,
};

const existingAssets = <?= json_encode($existingAssets ?? [], JSON_UNESCAPED_SLASHES) ?>;

const pageData = {
    html: <?= $jsonHtml ?>,
    css: <?= $jsonCss ?>,
    php: <?= $jsonPhp ?>,
    js: <?= $jsonJs ?>,
};

// ═══════════════════════════════════════════════════════════════
// CODEMIRROR
// ═══════════════════════════════════════════════════════════════
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
        'Ctrl-S': () => savePage(),
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

// Cargar contenido inicial
cmHtml.setValue(pageData.html);
cmCss.setValue(pageData.css);
if (cmPhp) cmPhp.setValue(pageData.php);
cmJs.setValue(pageData.js);

// ═══════════════════════════════════════════════════════════════
// GRAPESJS
// ═══════════════════════════════════════════════════════════════
const gjsEditor = grapesjs.init({
    container: '#gjs',
    showOffsets: true,
    fromElement: false,
    noticeOnUnload: false,
    storageManager: false,
    height: '100%',
    
    // Asset Manager
    assetManager: {
        assets: existingAssets,
        upload: CONFIG.uploadUrl,
        uploadName: 'files[]',
        multiUpload: true,
        autoAdd: true,
        accept: 'image/*',
        
        uploadFile: function(e) {
            const files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
            if (!files || files.length === 0) return;
            
            const formData = new FormData();
            const validFiles = [];
            
            for (let i = 0; i < files.length; i++) {
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
            
            // CSRF token en el FormData
            formData.append('_csrf_token', CONFIG.csrfToken);
            
            showToast('📤 Subiendo ' + validFiles.length + ' imagen(es)...', 'info');
            
            fetch(CONFIG.uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(result => {
                if (result.success && result.data && result.data.length > 0) {
                    result.data.forEach(asset => {
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
                    result.errors.forEach(err => showToast('⚠ ' + err, 'warning'));
                }
            })
            .catch(err => {
                console.error('Error subiendo:', err);
                showToast('✗ Error al subir: ' + err.message, 'error');
            });
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

// ═══════════════════════════════════════════════════════════════
// SINCRONIZACIÓN GRAPESJS ↔ CODEMIRROR
// ═══════════════════════════════════════════════════════════════
let isSyncing = false;
let syncTimer = null;

// GrapesJS → CodeMirror
gjsEditor.on('update', () => {
    if (isSyncing) return;
    isSyncing = true;
    cmHtml.setValue(gjsEditor.getHtml());
    cmCss.setValue(gjsEditor.getCss());
    updateSizeInfo();
    setTimeout(() => { isSyncing = false; }, 150);
});

// CodeMirror HTML → GrapesJS (con debounce)
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

// CodeMirror CSS → GrapesJS (con debounce)
cmCss.on('change', () => {
    if (isSyncing) return;
    clearTimeout(syncTimer);
    syncTimer = setTimeout(() => {
        isSyncing = true;
        try { gjsEditor.setStyle(cmCss.getValue()); } catch(e) {}
        setTimeout(() => { isSyncing = false; }, 200);
    }, 600);
});

// ═══════════════════════════════════════════════════════════════
// PESTAÑAS DEL EDITOR
// ═══════════════════════════════════════════════════════════════
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
    
    // Refrescar CodeMirror al hacer visible
    setTimeout(() => {
        if (tab === 'html') cmHtml.refresh();
        if (tab === 'css') cmCss.refresh();
        if (tab === 'php' && cmPhp) cmPhp.refresh();
        if (tab === 'js') cmJs.refresh();
    }, 10);
    
    // Si volvemos al visual, sincronizar desde CodeMirror
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

// ═══════════════════════════════════════════════════════════════
// PANEL DE CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════
let configPanelOpen = true;

function toggleConfigPanel() {
    configPanelOpen = !configPanelOpen;
    document.getElementById('configPanel').classList.toggle('collapsed', !configPanelOpen);
    
    setTimeout(() => {
        gjsEditor.Canvas.refresh();
        [cmHtml, cmCss, cmPhp, cmJs].forEach(cm => cm && cm.refresh());
    }, 300);
}

// ═══════════════════════════════════════════════════════════════
// FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════
function updateDisplayTitle() {
    const t = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('displayTitle').textContent = t;
    document.title = t + ' · Builder';
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
    t.innerHTML = `<i class="bi bi-${getToastIcon(type)}"></i> ${msg}`;
    c.appendChild(t);
    
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(100%)';
        setTimeout(() => t.remove(), 300);
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

function reloadAssets() {
    fetch(CONFIG.assetsUrl, { 
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(result => {
        const assets = result.data || result;
        if (!Array.isArray(assets)) return;
        gjsEditor.AssetManager.clear();
        gjsEditor.AssetManager.add(assets);
        gjsEditor.AssetManager.render();
        showToast('✓ Assets recargados: ' + assets.length, 'success');
    })
    .catch(err => showToast('✗ Error al recargar', 'error'));
}

// ═══════════════════════════════════════════════════════════════
// SISTEMA DE AUTOGUARDADO AUTOMÁTICO (cada 30 segundos)
// ═══════════════════════════════════════════════════════════════
let autoSaveTimer = null;
let autoSaveCountdown = CONFIG.autosaveInterval / 1000;
let countdownTimer = null;
let isAutoSaving = false;
let lastAutoSaveTime = null;

/**
 * Inicia el sistema de autoguardado automático.
 */
function startAutoSave() {
    if (!CONFIG.autosaveEnabled) return;
    
    // Limpiar timer anterior si existe
    stopAutoSave();
    
    // Resetear contador
    autoSaveCountdown = CONFIG.autosaveInterval / 1000;
    updateAutoSaveIndicator();
    
    // Timer principal (ejecuta autoguardado cada 30s)
    autoSaveTimer = setInterval(() => {
        if (isDirty && !isSaving && !isAutoSaving) {
            performAutoSave();
        }
        autoSaveCountdown = CONFIG.autosaveInterval / 1000;
    }, CONFIG.autosaveInterval);
    
    // Timer de cuenta regresiva (actualiza UI cada segundo)
    countdownTimer = setInterval(() => {
        if (autoSaveCountdown > 0) {
            autoSaveCountdown--;
            updateAutoSaveIndicator();
        }
    }, 1000);
    
    console.log('🔄 Autoguardado iniciado (cada ' + (CONFIG.autosaveInterval / 1000) + 's)');
}

/**
 * Detiene el sistema de autoguardado.
 */
function stopAutoSave() {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
        autoSaveTimer = null;
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

/**
 * Actualiza el indicador visual de autoguardado en la barra de estado.
 */
function updateAutoSaveIndicator() {
    const indicator = document.getElementById('autoSaveStatus');
    if (!indicator) return;
    
    if (isAutoSaving) {
        indicator.innerHTML = '<i class="bi bi-cloud-arrow-up" style="color:var(--warning)"></i> Autoguardando...';
        return;
    }
    
    if (lastAutoSaveTime) {
        const ago = Math.floor((Date.now() - lastAutoSaveTime) / 1000);
        let timeText;
        if (ago < 60) {
            timeText = ago + 's';
        } else if (ago < 3600) {
            timeText = Math.floor(ago / 60) + 'min';
        } else {
            timeText = Math.floor(ago / 3600) + 'h';
        }
        indicator.innerHTML = `<i class="bi bi-cloud-check" style="color:var(--success)"></i> Autoguardado hace ${timeText}`;
    } else if (isDirty) {
        indicator.innerHTML = `<i class="bi bi-pencil-square" style="color:var(--warning)"></i> Cambios sin guardar (próximo auto en ${autoSaveCountdown}s)`;
    } else {
        indicator.innerHTML = `<i class="bi bi-check-circle" style="color:var(--success)"></i> Todo guardado`;
    }
}

/**
 * Ejecuta el autoguardado automático.
 */
function performAutoSave() {
    if (isAutoSaving || isSaving) return;
    isAutoSaving = true;
    
    // Actualizar indicador
    updateAutoSaveIndicator();
    
    // Preparar datos
    const data = {
        content_html: cmHtml.getValue(),
        content_css: cmCss.getValue(),
        content_js: cmJs.getValue(),
        content_php: cmPhp ? cmPhp.getValue() : '',
        grapesjs_data: JSON.stringify(gjsEditor.getProjectData()),
        _csrf_token: CONFIG.csrfToken
    };
    
    const formData = new FormData();
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    fetch(CONFIG.autosaveUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            lastAutoSaveTime = Date.now();
            // Toast discreto (solo la primera vez cada 5 minutos)
            if (!window._lastAutoSaveToast || Date.now() - window._lastAutoSaveToast > 300000) {
                showToast('💾 Autoguardado automático', 'info');
                window._lastAutoSaveToast = Date.now();
            }
        } else {
            console.warn('⚠️ Error en autoguardado:', result.message);
        }
    })
    .catch(err => {
        console.error('✗ Error en autoguardado:', err);
    })
    .finally(() => {
        isAutoSaving = false;
        updateAutoSaveIndicator();
    });
}

/**
 * Recupera el autosave anterior al cargar la página (si existe).
 */
function recoverAutoSave() {
    fetch(CONFIG.autosaveUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(result => {
        if (!result.success || !result.data) return;
        
        const autosave = result.data;
        const ageMinutes = Math.floor(autosave.age_seconds / 60);
        
        // No mostrar si el autosave es muy antiguo (> 24h)
        if (autosave.age_seconds > 86400) return;
        
        // No mostrar si no hay contenido real
        const hasContent = autosave.content_html && autosave.content_html.trim().length > 0;
        if (!hasContent) return;
        
        // Mostrar modal de recuperación
        showAutoSaveRecoveryModal(autosave, ageMinutes);
    })
    .catch(err => {
        console.error('Error recuperando autosave:', err);
    });
}

/**
 * Muestra el modal para recuperar el autosave.
 */
function showAutoSaveRecoveryModal(autosave, ageMinutes) {
    // Crear modal si no existe
    let modal = document.getElementById('autosaveRecoveryModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'autosaveRecoveryModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeAutoSaveModal()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="bi bi-cloud-arrow-down text-warning"></i> Autosave encontrado</h3>
                    <button class="modal-close" onclick="closeAutoSaveModal()">×</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        Se encontró una versión autoguardada <strong>hace <span id="autosaveAge"></span> minutos</strong>.
                    </div>
                    <p>¿Deseas recuperar esta versión?</p>
                    <div class="alert alert-info" style="font-size:.85rem">
                        <strong>Contenido del autosave:</strong>
                        <div id="autosavePreview" style="margin-top:.5rem;max-height:150px;overflow-y:auto;background:var(--bg-principal);padding:.75rem;border-radius:4px;font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted)"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ghost" onclick="discardAutoSave()">
                        <i class="bi bi-x-circle"></i> Descartar
                    </button>
                    <button class="btn btn-primary" onclick="applyAutoSave()">
                        <i class="bi bi-cloud-download"></i> Recuperar versión
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Guardar datos del autosave
    window._pendingAutoSave = autosave;
    
    // Actualizar contenido del modal
    document.getElementById('autosaveAge').textContent = ageMinutes;
    
    // Preview del contenido (primeros 500 chars)
    const preview = (autosave.content_html || '').substring(0, 500);
    document.getElementById('autosavePreview').textContent = preview + (preview.length === 500 ? '...' : '');
    
    // Mostrar modal
    modal.classList.add('active');
}

/**
 * Cierra el modal de recuperación sin aplicar.
 */
function closeAutoSaveModal() {
    const modal = document.getElementById('autosaveRecoveryModal');
    if (modal) modal.classList.remove('active');
}

/**
 * Descarta el autosave (lo elimina de la BD).
 */
function discardAutoSave() {
    fetch(CONFIG.autosaveUrl, {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CONFIG.csrfToken
        },
        credentials: 'same-origin'
    })
    .catch(err => console.error('Error descartando autosave:', err))
    .finally(() => {
        closeAutoSaveModal();
        window._pendingAutoSave = null;
        showToast('Autosave descartado', 'info');
    });
}

/**
 * Aplica el autosave al editor.
 */
function applyAutoSave() {
    const autosave = window._pendingAutoSave;
    if (!autosave) return;
    
    try {
        // Aplicar contenido a CodeMirror
        cmHtml.setValue(autosave.content_html || '');
        cmCss.setValue(autosave.content_css || '');
        cmJs.setValue(autosave.content_js || '');
        if (cmPhp) cmPhp.setValue(autosave.content_php || '');
        
        // Aplicar a GrapesJS si hay datos del proyecto
        if (autosave.grapesjs_data) {
            try {
                const projectData = JSON.parse(autosave.grapesjs_data);
                gjsEditor.loadProjectData(projectData);
            } catch (e) {
                console.warn('No se pudo cargar datos de GrapesJS:', e);
            }
        }
        
        // Marcar como dirty para que el próximo guardado manual guarde todo
        isDirty = true;
        
        // Eliminar autosave de la BD (ya fue aplicado)
        fetch(CONFIG.autosaveUrl, {
            method: 'POST',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CONFIG.csrfToken
            },
            credentials: 'same-origin'
        }).catch(() => {});
        
        closeAutoSaveModal();
        window._pendingAutoSave = null;
        
        showToast('✓ Versión recuperada correctamente', 'success');
        
        // Cambiar a pestaña visual para ver el resultado
        switchTab('visual');
        
    } catch (err) {
        console.error('Error aplicando autosave:', err);
        showToast('✗ Error al recuperar: ' + err.message, 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
// GUARDAR PÁGINA (con integración de autoguardado)
// ═══════════════════════════════════════════════════════════════
let isSaving = false;

function savePage() {
    if (isSaving) return;
    isSaving = true;
    
    // Pausar autoguardado durante guardado manual
    stopAutoSave();
    
    const btn = document.getElementById('saveBtn');
    const dot = document.getElementById('statusDot');
    
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span class="btn-label">Guardando...</span>';
    btn.disabled = true;
    dot.classList.add('saving');
    document.getElementById('autoSaveStatus').textContent = 'Guardando...';
    
    // Llenar formulario
    document.getElementById('formTitle').value = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('formSlug').value = document.getElementById('pageSlug').value || generateSlug(document.getElementById('pageTitle').value);
    document.getElementById('formDesc').value = document.getElementById('pageDesc').value;
    document.getElementById('formTemplate').value = document.getElementById('pageTemplate').value;
    document.getElementById('formHtml').value = cmHtml.getValue();
    document.getElementById('formCss').value = cmCss.getValue();
    document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
    document.getElementById('formJs').value = cmJs.getValue();
    document.getElementById('formStatus').value = document.getElementById('pageStatus').value;
    document.getElementById('formStartpage').value = document.getElementById('pageStartpage').checked ? '1' : '0';
    document.getElementById('formGrapesData').value = JSON.stringify(gjsEditor.getProjectData());
    document.getElementById('formSeoTitle').value = document.getElementById('seoTitle').value;
    document.getElementById('formSeoDesc').value = document.getElementById('seoDescription').value;
    document.getElementById('formVersionNote').value = 'Edición desde builder visual';
    
    const formData = new FormData(document.getElementById('saveForm'));
    
    fetch(CONFIG.saveUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showToast('✓ Página guardada correctamente', 'success');
            document.getElementById('autoSaveStatus').innerHTML = 
                '<i class="bi bi-check-circle" style="color:var(--success)"></i> Guardado: ' + new Date().toLocaleTimeString();
            
            // Actualizar preview URL si cambió el slug
            const newSlug = document.getElementById('pageSlug').value;
            document.getElementById('previewBtn').href = '/' + newSlug;
            
            // Marcar como limpio
            isDirty = false;
            lastAutoSaveTime = Date.now();
            
            // ═══ NUEVO: Limpiar autosave de la BD ═══
            fetch(CONFIG.autosaveUrl, {
                method: 'POST',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CONFIG.csrfToken
                },
                credentials: 'same-origin'
            }).catch(() => {});
            
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
        
        // Reiniciar autoguardado
        setTimeout(() => {
            startAutoSave();
            updateAutoSaveIndicator();
        }, 1000);
    });
}

function generateSlug(text) {
    return (text || '').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'page-' + Date.now();
}

// ═══════════════════════════════════════════════════════════════
// ATAJOS DE TECLADO
// ═══════════════════════════════════════════════════════════════
document.addEventListener('keydown', (e) => {
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

// ═══════════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ═══════════════════════════════════════════════════════════════
window.addEventListener('load', () => {
    setTimeout(() => {
        gjsEditor.Canvas.refresh();
        updateSizeInfo();
        
        // ═══ NUEVO: Iniciar autoguardado y recuperar autosave ═══
        startAutoSave();
        
        // Intentar recuperar autosave anterior después de 2 segundos
        setTimeout(() => {
            recoverAutoSave();
        }, 2000);
        
    }, 200);
});

// Marcar como "dirty" al hacer cambios
let isDirty = false;
[cmHtml, cmCss, cmJs].forEach(cm => {
    if (!cm) return;
    cm.on('change', () => { 
        isDirty = true;
        updateAutoSaveIndicator();
    });
});
gjsEditor.on('change:canvasOffset', () => { 
    isDirty = true;
    updateAutoSaveIndicator();
});

// Advertir al salir sin guardar
window.addEventListener('beforeunload', (e) => {
    if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
    // Detener autoguardado al salir
    stopAutoSave();
});

// Pausar autoguardado cuando la pestaña no está visible (ahorra recursos)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAutoSave();
    } else {
        startAutoSave();
    }
});

// ═══════════════════════════════════════════════════════════════
// GUARDAR PÁGINA (AJAX con detección por PagesController)
// ═══════════════════════════════════════════════════════════════
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
    
    // Llenar formulario
    document.getElementById('formTitle').value = document.getElementById('pageTitle').value || 'Sin título';
    document.getElementById('formSlug').value = document.getElementById('pageSlug').value || generateSlug(document.getElementById('pageTitle').value);
    document.getElementById('formDesc').value = document.getElementById('pageDesc').value;
    document.getElementById('formTemplate').value = document.getElementById('pageTemplate').value;
    document.getElementById('formHtml').value = cmHtml.getValue();
    document.getElementById('formCss').value = cmCss.getValue();
    document.getElementById('formPhp').value = cmPhp ? cmPhp.getValue() : '';
    document.getElementById('formJs').value = cmJs.getValue();
    document.getElementById('formStatus').value = document.getElementById('pageStatus').value;
    document.getElementById('formStartpage').value = document.getElementById('pageStartpage').checked ? '1' : '0';
    document.getElementById('formGrapesData').value = JSON.stringify(gjsEditor.getProjectData());
    document.getElementById('formSeoTitle').value = document.getElementById('seoTitle').value;
    document.getElementById('formSeoDesc').value = document.getElementById('seoDescription').value;
    document.getElementById('formVersionNote').value = 'Edición desde builder visual';
    
    const formData = new FormData(document.getElementById('saveForm'));
    
    // ⚠️ CLAVE: Header X-Requested-With para que PagesController devuelva JSON
    fetch(CONFIG.saveUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showToast('✓ Página guardada correctamente', 'success');
            document.getElementById('autoSaveStatus').textContent = 
                'Guardado: ' + new Date().toLocaleTimeString();
            
            // Actualizar preview URL si cambió el slug
            const newSlug = document.getElementById('pageSlug').value;
            document.getElementById('previewBtn').href = '/' + newSlug;
            
            // Marcar como limpio
            isDirty = false;
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

// ═══════════════════════════════════════════════════════════════
// ATAJOS DE TECLADO
// ═══════════════════════════════════════════════════════════════
document.addEventListener('keydown', (e) => {
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

// ═══════════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ═══════════════════════════════════════════════════════════════
window.addEventListener('load', () => {
    setTimeout(() => {
        gjsEditor.Canvas.refresh();
        updateSizeInfo();
    }, 200);
});

// Marcar como "dirty" al hacer cambios
let isDirty = false;
[cmHtml, cmCss, cmJs].forEach(cm => {
    if (!cm) return;
    cm.on('change', () => { isDirty = true; });
});
gjsEditor.on('change:canvasOffset', () => { isDirty = true; });

// Advertir al salir sin guardar
window.addEventListener('beforeunload', (e) => {
    if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
<script src="<?= asset('js/collaboration.js') ?>"></script>
<script>
// ═══ INICIALIZAR COLABORACIÓN ═══
window.CURRENT_USER_ID = <?= (int)(Auth::id() ?? 0) ?>;

// Esperar a que GrapesJS esté listo
window.addEventListener('load', () => {
    setTimeout(() => {
        if (typeof gjsEditor !== 'undefined') {
            window.initCollaboration(
                <?= (int)$page['id'] ?>,
                gjsEditor,
                {
                    baseUrl: '<?= e(rtrim(BASE_URL ?? '', '/')) ?>',
                    csrfToken: '<?= e(csrf_token()) ?>',
                    pollInterval: 2000,
                    heartbeatInterval: 10000,
                }
            );
        }
    }, 1000);
});

// Botón toggle en topbar
document.addEventListener('DOMContentLoaded', () => {
    const topbarRight = document.querySelector('.topbar-right');
    if (topbarRight && typeof collabClient !== 'undefined') {
        const btn = document.createElement('button');
        btn.className = 'btn-builder btn-ghost btn-collab-toggle';
        btn.onclick = () => collabClient.togglePanel();
        btn.title = 'Colaboración';
        btn.innerHTML = `
            <i class="bi bi-people-fill"></i>
            <span class="btn-label">Colaborar</span>
            <span class="collab-indicator"></span>
        `;
        // Insertar antes del botón de usuario
        const userChip = topbarRight.querySelector('.user-chip-mini');
        if (userChip) {
            topbarRight.insertBefore(btn, userChip);
        } else {
            topbarRight.appendChild(btn);
        }
    }
});
</script>
