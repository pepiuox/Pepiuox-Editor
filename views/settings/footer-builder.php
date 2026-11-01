<?php
/**
 * Pie de página con el constructor visual.
 *
 * Usa el **mismo** GrapesJS que el constructor de páginas y de plantillas:
 * los activos vienen de `views/partials/editor-assets-head|body.php` y la
 * configuración del lienzo de `public/assets/js/grapesjs-shared.js`
 * (`window.EditorKit`). No hay un segundo editor distinto: es el mismo, con otro
 * contenido.
 *
 * Qué se guarda: `content_html`, `content_css`, `content_js` y `grapesjs_data`,
 * exactamente las mismas cuatro piezas que una página, para que el proyecto tenga
 * una sola forma de guardar contenido visual.
 *
 * Cómo convive con el editor estructurado: si el interruptor «Usar este pie»
 * está activo y hay HTML, el layout pinta esto. Si no, sigue pintando las
 * columnas de la pantalla anterior. Nada se pierde al probar.
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

$htmlInicial = (string) ($builder['html'] ?? '');
$cssInicial  = (string) ($builder['css'] ?? '');
$jsInicial   = (string) ($builder['js'] ?? '');
$dataInicial = (string) ($builder['data'] ?? '');
$activo      = !empty($builder['enabled']);
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
        <h1 class="h3 mb-1"><i class="bi bi-magic me-2"></i>Pie de página · constructor</h1>
        <p class="text-secondary mb-0">
            El mismo editor que las páginas. Se compone el pie con bloques y se guarda.
            <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver el sitio</a>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings/footer') ?>">
            <i class="bi bi-list-ul"></i> Versión con campos
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings') ?>">
            <i class="bi bi-arrow-left"></i> Configuración
        </a>
    </div>
</div>

<div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-3" role="alert">
    <div>
        <strong>¿Qué pie se está mostrando en el sitio?</strong>
        <?= $activo
            ? 'Ahora mismo manda <strong>este</strong> pie del constructor.'
            : 'Ahora mismo manda el <strong>pie por campos</strong>. Al marcar la casilla y guardar, el sitio pasará a mostrar este.' ?>
    </div>
    <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="builder_enabled"
               form="saveForm" name="builder_enabled" value="1" <?= $activo ? 'checked' : '' ?>>
        <label class="form-check-label" for="builder_enabled">Usar este pie</label>
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

<form id="saveForm" method="post" action="<?= url('admin/settings/footer/builder') ?>" class="mt-3">
    <?= csrf_field() ?>
    <input type="hidden" name="content_html" id="formHtml">
    <input type="hidden" name="content_css" id="formCss">
    <input type="hidden" name="content_js" id="formJs">
    <input type="hidden" name="grapesjs_data" id="formData">

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="btnSave">
            <i class="bi bi-save me-1"></i>Guardar pie
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/settings/footer') ?>">
            Volver a los campos
        </a>
        <span class="align-self-center text-secondary small" id="estadoGuardado"></span>
    </div>
</form>

<script>
    // CSS inicial del pie compuesto, si lo hay.
    window.__FOOTER_CSS__ = <?= json_encode($cssInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__FOOTER_JS__  = <?= json_encode($jsInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__FOOTER_DATA__ = <?= json_encode($dataInicial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php include VIEWS_PATH . '/partials/editor-assets-body.php'; ?>

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

    const editor = grapesjs.init({
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
            // El endpoint responde {success, data:[{src,…}]}, que es justo el
            // formato que GrapesJS entiende; no hace falta transformarlo.
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
    // Sin ella se cargaban `bs-blocks.js` (duplica cinco ids con contenido
    // distinto) y `template.js` (mete un bloque con código ejecutable dentro del
    // lienzo), y los
    // bloques no se comportaban como en el resto del sistema.
    // `JSON_HEX_TAG` escapa las etiquetas de cierre dentro de las rutas, para que
    // ninguna pueda cortar este bloque de script antes de tiempo. (Escribir la
    // etiqueta de cierre literal aquí fue el fallo que rompía todo el editor.)
    const BLOCK_SCRIPTS = <?= json_encode(editor_block_urls(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

    kit.loadBlockScripts(BLOCK_SCRIPTS, editor, (n) => {
        document.getElementById('estadoGuardado').textContent = n + ' fichero(s) de bloques cargados.';
    });

    // Contenido previo: si hay proyecto guardado manda él; si no, el HTML.
    if (datos) {
        try {
            editor.loadProjectData(datos);
        } catch (e) {
            console.warn('[Pie] No se pudo restaurar el proyecto guardado:', e);
        }
    }

    const guardar = () => {
        document.getElementById('formHtml').value = editor.getHtml();
        document.getElementById('formCss').value  = editor.getCss();
        document.getElementById('formJs').value   = editor.getJs ? editor.getJs() : '';
        document.getElementById('formData').value = JSON.stringify(editor.getProjectData());
    };

    document.getElementById('saveForm').addEventListener('submit', guardar);
    document.getElementById('btnSave').addEventListener('click', guardar);

    editor.on('update', () => {
        document.getElementById('estadoGuardado').textContent = 'Cambios sin guardar…';
    });

    document.getElementById('estadoGuardado').textContent = datos
        ? 'Proyecto del constructor restaurado.'
        : (window.__FOOTER_CSS__
            ? 'Pie previo cargado desde su HTML.'
            : 'Lienzo vacío: arrastra bloques para componer el pie.');
})();
</script>
