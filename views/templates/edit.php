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
<html lang="es">
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
    
    <style>
        /* Estilos generales */
        body {
            background: #f5f7fa;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0 !important;
        }
        
        /* Estilos de Tabs */
        .nav-tabs .nav-link {
            cursor: pointer;
            background: transparent;
            border: 1px solid transparent;
            color: #6c757d;
            padding: 10px 20px;
            transition: all 0.2s;
            border-radius: 8px 8px 0 0;
        }
        
        .nav-tabs .nav-link:hover {
            color: #0d6efd;
            background: rgba(13, 110, 253, 0.05);
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            border-color: #dee2e6 #dee2e6 transparent;
            color: #0d6efd;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 6px;
        }
        
        /* Contenido de Tabs */
        .tab-content {
            background: white;
            border-radius: 0 0 8px 8px;
        }
        
        .tab-pane {
            display: none;
            padding: 20px 0 0 0;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        /* CodeMirror */
        .CodeMirror {
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
        }
        
        /* GrapesJS */
        #gjs-editor {
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .gjs-cv-canvas {
            background: #ffffff !important;
        }
        
        /* Notificaciones */
        .alert-fixed {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            border-radius: 12px;
        }
        
        .btn-ghost {
            background: transparent;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }
        
        .btn-ghost:hover {
            background: #f8f9fa;
            color: #0d6efd;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .CodeMirror, #gjs-editor {
                height: 400px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0"><i class="bi bi-layout-text-window"></i> <?= e($pageTitle) ?></h3>
            <div class="d-flex gap-2">
                <?php if ($template): ?>
                    <a href="<?= url('admin/templates/' . (int)$t['id'] . '/versions') ?>" class="btn btn-info btn-sm">
                        <i class="bi bi-clock-history"></i> Versiones
                    </a>
                <?php endif; ?>
                <a href="<?= url('admin/templates') ?>" class="btn btn-ghost btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="post"
                  action="<?= url($template ? "admin/templates/{$t['id']}" : 'admin/templates') ?>"
                  id="templateForm">
                <?= \App\Core\CSRF::field() ?>

                <!-- ═══ METADATOS ═══ -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($t['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" name="description" class="form-control"
                               value="<?= e($t['description']) ?>">
                    </div>
                </div>

                <!-- ═══ TABS ═══ -->
                <ul class="nav nav-tabs" id="editorTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active" data-tab="visual" role="tab">
                            <i class="bi bi-palette"></i> Visual
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-tab="html" role="tab">
                            <i class="bi bi-code-slash"></i> HTML
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-tab="css" role="tab">
                            <i class="bi bi-palette2"></i> CSS
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-tab="js" role="tab">
                            <i class="bi bi-braces"></i> JS
                        </button>
                    </li>
                    <?php if ($canEditPhp): ?>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-tab="php" role="tab">
                            <i class="bi bi-filetype-php"></i> PHP
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- ═══ CONTENIDO DE TABS ═══ -->
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

                <!-- ═══ OPCIONES ═══ -->
                <div class="d-flex gap-3 mt-4 flex-wrap">
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

                <!-- ═══ ACCIONES ═══ -->
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                    <?php if ($template): ?>
                    <button type="button" class="btn btn-info" id="btnSaveVersion">
                        <i class="bi bi-clock-history"></i> Guardar versión
                    </button>
                    <?php endif; ?>
                    <a href="<?= url('admin/templates') ?>" class="btn btn-ghost">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

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