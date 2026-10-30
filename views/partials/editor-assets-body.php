<?php
/**
 * Activos JS compartidos del editor visual (GrapesJS + CodeMirror).
 *
 * Se incluye justo antes del script principal de cada editor. El orden importa:
 * CodeMirror (librería → modos → addons) y después GrapesJS (core → plugins).
 *
 * Versiones fijadas a propósito: con URLs sin versión, un release nuevo puede
 * cambiar la API y romper el editor sin que nadie toque el repo. El CSS de
 * assets-head y estos JS deben compartir versión.
 *
 * @package Views\Partials
 */
?>
<!-- CodeMirror -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/selection/active-line.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/xml-fold.min.js"></script>

<!-- GrapesJS y sus plugins -->
<script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-forms@2.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-tabs@1.0.6/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-custom-code@1.0.2/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-touch@0.1.1/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-plugin-export@1.0.12/dist/index.min.js"></script>
<!-- Bootstrap 5 blocks: el nombre del plugin lo publica su README como
     `grapesjs-blocks-bootstrap5`. El paquete está deprecado en npm (0.2.31 es la
     última), por eso la versión queda fijada. El lienzo carga Bootstrap 5.3.3. -->
<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-bootstrap5@0.2.31/dist/index.min.js"></script>

<!-- Utilidades compartidas: lista de plugins, filtro contra el registro real,
     carga de ficheros de bloques y estilos/scripts del lienzo. -->
<script src="<?= e(asset('js/grapesjs-shared.js')) ?>"></script>

<!-- Biblioteca visual de bloques: normaliza categorías, retira duplicados y
     decora cada tarjeta con una miniatura real. Se engancha sola al terminar
     de cargar los bloques (ver EditorKit.loadBlockScripts). -->
<script src="<?= e(asset('js/blocks-library.js')) ?>"></script>
