<?php
/**
 * Constructor visual del pie de página, sobre un diseño del catálogo.
 *
 * Usa el **mismo** GrapesJS que el constructor de páginas y de plantillas:
 * los activos vienen de `views/partials/editor-assets-head|body.php` y la
 * configuración del lienzo de `public/assets/js/grapesjs-shared.js`
 * (`window.EditorKit`). No hay un segundo editor distinto.
 *
 * Qué se guarda: `content_html`, `content_css`, `content_js` y `grapesjs_data` en
 * la fila de `footer_designs`, las mismas cuatro piezas que guarda una página.
 *
 * El interruptor «usar en el sitio» activa el diseño al guardar: así el cambio se
 * ve con un solo paso, sin volver al catálogo.
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

$htmlInicial = (string) ($design['content_html'] ?? '');
$cssInicial  = (string) ($design['content_css'] ?? '');
$jsInicial   = (string) ($design['content_js'] ?? '');
$dataInicial = (string) ($design['grapesjs_data'] ?? '');
$activo      = (int) ($design['is_active'] ?? 0) === 1;
$designId    = (int) ($design['id'] ?? 0);

$accionGuardar = url('admin/settings/footer/designs/' . $designId . '/builder');
?>

<?php
// Igual que el builder de páginas: NO se emite Bootstrap desde aquí. El panel de
// administración ya carga su propia versión (5.3.8); traer otra (5.3.3) duplicaba
// hojas y peleaba con el diseño del panel. El lienzo sí carga el suyo, aparte.
$editorIncludeBootstrap = false;
require VIEWS_PATH . '/partials/editor-assets-head.php';
?>

<style>
    /* Contenedor del constructor dentro del panel. */
    .gjs-editor-wrap { border: 1px solid var(--border-color, #30363d); border-radius: 10px; overflow: hidden; }
    #gjs { min-height: 60vh; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-magic me-2"></i><?= e($design['name'] ?? 'Diseño del pie') ?></h1>
        <p class="text-secondary mb-0">
            El mismo editor que las páginas. Se compone el pie con bloques y se guarda.
            <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver el sitio</a>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings/footer/designs') ?>">
            <i class="bi bi-collection"></i> Catálogo
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings/footer') ?>">
            <i class="bi bi-list-ul"></i> Pie por campos
        </a>
    </div>
</div>

<div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-3" role="alert">
    <div>
        <strong>¿Se verá este diseño en el sitio?</strong>
        <?= $activo
            ? 'Sí: este diseño está activo y es el que se muestra ahora.'
            : 'Ahora mismo el sitio muestra otro pie. Marca la casilla y guarda para usar este diseño.' ?>
    </div>
    <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="builder_enabled"
               form="saveForm" name="builder_enabled" value="1" <?= $activo ? 'checked' : '' ?>>
        <label class="form-check-label" for="builder_enabled">Usar este diseño en el sitio</label>
    </div>
</div>

<div class="alert alert-secondary small" role="note">
    <strong>Imágenes y logotipos.</strong> El bloque «Imagen» abre el gestor de medios: ahí están las
    imágenes ya subidas (incluidos los logotipos de <em>Configuración → Logotipos</em>) y un botón para
    subir nuevas. Se guardan en <code>uploads/</code>, fuera del código y validadas por tipo y tamaño.
</div>

<div class="gjs-editor-wrap">
    <div id="gjs"><?= $htmlInicial ?></div>
</div>

<form id="saveForm" method="post" action="<?= e($accionGuardar) ?>" class="mt-3">
    <?= csrf_field() ?>
    <input type="hidden" name="content_html" id="formHtml">
    <input type="hidden" name="content_css" id="formCss">
    <input type="hidden" name="content_js" id="formJs">
    <input type="hidden" name="grapesjs_data" id="formData">

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="btnSave">
            <i class="bi bi-save me-1"></i>Guardar diseño
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/settings/footer/designs') ?>">
            Volver al catálogo
        </a>
        <span class="align-self-center text-secondary small" id="estadoGuardado"></span>
    </div>
</form>

<script>
    // CSS y proyecto iniciales del diseño, si los hay.
    window.__FOOTER_CSS__  = <?= json_encode($cssInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    window.__FOOTER_JS__   = <?= json_encode($jsInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    window.__FOOTER_DATA__ = <?= json_encode($dataInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>

<?php require VIEWS_PATH . '/partials/editor-assets-body.php'; ?>

<script>
(() => {
    'use strict';

    if (!window.grapesjs || !window.EditorKit) {
        document.getElementById('estadoGuardado').textContent =
            'No se pudo cargar el editor. Revisa la conexión con el CDN.';
        return;
    }

    const kit = window.EditorKit;
    const plugins = kit.filterPlugins(kit.PLUGIN_NAMES);

    let datos = null;
    if (window.__FOOTER_DATA__) {
        try { datos = JSON.parse(window.__FOOTER_DATA__); } catch (e) { datos = null; }
    }

    // Imágenes: las ya subidas y un endpoint para subir más. El `assetManager`
    // es lo que hace que el bloque «Imagen» tenga algo que elegir; sin él, el
    // bloque aparece pero no hay ninguna imagen para poner.
    const ASSETS = <?= json_encode(
        array_map(static fn (array $a): array => ['src' => $a['src'], 'name' => $a['name'], 'type' => 'image'], $assets ?? []),
        JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
    ) ?>;

    // ⚠️ La instancia DEBE quedar en la global `gjsEditor`.
    //
    // Los ficheros de `public/assets/js/blocks/*.js` registran sus bloques con
    // `gjsEditor.BlockManager.add(...)` — 188 llamadas — y empiezan con un guardia
    // `if (typeof gjsEditor === 'undefined' || !gjsEditor) { …omitido }`.
    // Con una instancia llamada de otra forma, todos los ficheros salen por ese
    // guardia, no se registra ni un bloque y el panel derecho queda vacío.
    // Asignar a `window` explícitamente porque este bloque es `'use strict'`: una
    // asignación a variable no declarada lanzaría ReferenceError.
    window.gjsEditor = grapesjs.init({
        container: '#gjs',
        height: '60vh',
        fromElement: !datos,
        storageManager: false,
        plugins: plugins.enabled,
        pluginsOpts: kit.PLUGINS_OPTS,
        assetManager: {
            assets: ASSETS,
            upload: <?= json_encode($uploadUrl ?? '', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
            uploadName: 'files',
            autoAdd: true,
            multiple: false,
        },
        canvas: {
            styles: kit.CANVAS_STYLES,
            scripts: kit.CANVAS_SCRIPTS,
        },
        deviceManager: {
            devices: [
                { name: 'Escritorio', width: '' },
                { name: 'Tableta', width: '768px', widthMedia: '992px' },
                { name: 'Móvil', width: '375px', widthMedia: '575px' },
            ],
        },
    });

    // Bloques del constructor: MISMOS ficheros, mismas exclusiones y mismo orden
    // que el editor de páginas y el de plantillas. La regla vive en
    // `editor_block_urls()` (app/Helpers/functions.php) y aquí solo se consume.
    // Ninguna etiqueta de script se escribe literalmente en este bloque: hacerlo
    // corta el script y todo lo que sigue sale como texto en la página.
    const BLOCK_SCRIPTS = <?= json_encode(editor_block_urls(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

    kit.loadBlockScripts(BLOCK_SCRIPTS, window.gjsEditor, (n) => {
        document.getElementById('estadoGuardado').textContent = n + ' fichero(s) de bloques cargados.';
    });

    // Contenido previo: si hay proyecto guardado manda él; si no, el HTML.
    if (datos) {
        try {
            window.gjsEditor.loadProjectData(datos);
        } catch (e) {
            console.warn('[Pie] No se pudo restaurar el proyecto guardado:', e);
        }
    }

    const guardar = () => {
        document.getElementById('formHtml').value = window.gjsEditor.getHtml();
        document.getElementById('formCss').value  = window.gjsEditor.getCss();
        document.getElementById('formJs').value   = window.gjsEditor.getJs ? window.gjsEditor.getJs() : '';
        document.getElementById('formData').value = JSON.stringify(window.gjsEditor.getProjectData());
    };

    document.getElementById('saveForm').addEventListener('submit', guardar);
    document.getElementById('btnSave').addEventListener('click', guardar);

    window.gjsEditor.on('update', () => {
        document.getElementById('estadoGuardado').textContent = 'Cambios sin guardar…';
    });

    document.getElementById('estadoGuardado').textContent = datos
        ? 'Proyecto del constructor restaurado.'
        : (window.__FOOTER_CSS__
            ? 'Pie previo cargado desde su HTML.'
            : 'Lienzo vacío: arrastra bloques para componer el pie.');
})();
</script>
