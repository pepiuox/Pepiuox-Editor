<?php
/**
 * Activos CSS compartidos del editor visual (GrapesJS + CodeMirror).
 *
 * Lo incluyen views/pages/builder.php (editor de páginas) y
 * views/templates/edit.php (editor de plantillas) para que ambos usen
 * exactamente las mismas versiones, iconos y temas. Si una versión se sube,
 * se sube aquí una sola vez y los dos editores van juntos.
 *
 * Variables opcionales:
 *   $editorIncludeBootstrap (bool) Emite Bootstrap 5 en la página. El builder
 *                                  de páginas no lo necesita (su interfaz es
 *                                  propia); el editor de plantillas sí.
 *
 * @package Views\Partials
 */

$editorIncludeBootstrap = $editorIncludeBootstrap ?? true;
?>
<!-- Fuentes -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<?php if ($editorIncludeBootstrap): ?>
<!-- Bootstrap 5 (solo interfaz de la página; el lienzo carga el suyo) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<?php endif; ?>

<!-- Iconos: los bloques usan bootstrap-icons (`bi bi-*`) y Font Awesome (`fas fa-*`) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">

<!-- GrapesJS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">

<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/fold/foldgutter.min.css">
