// ═══════════════════════════════════════════════════════════════════
// Bloques básicos — reescritos con clases de Bootstrap 5.3.3
//
// Antes cada bloque traía estilos en línea (padding:50px 20px, colores fijos,
// bordes discontinuos de guía). Eso producía secciones que no respondían, que
// ignoraban el tema del sitio y que había que limpiar a mano.
//
// Ahora usan las utilidades nativas del framework cargado en el lienzo:
// `container`, `row`/`col-*`, `g-*`, `py-5`, `card`, `btn`, `list-group`,
// `table`, `ratio`, `ratio-16x9`, `alert`… Los ids de bloque NO cambian, así que
// nada que ya los referencie deja de funcionar.
//
// @package Assets\Js\Blocks
// @since   2026-10-30 (reescritura; ids originales conservados)
// ═══════════════════════════════════════════════════════════════════

(function () {
    'use strict';

    if (typeof gjsEditor === 'undefined' || !gjsEditor) {
        console.warn('[blocks] gjsEditor no existe todavía; fichero omitido.');
        return;
    }

    var BASIC = { id: 'px-content', label: 'Contenido', open: false };

    /** Envuelve contenido en una sección estándar (espaciado Bootstrap). */
    function section(inner) {
        return '<section class="py-5"><div class="container">' + inner + '</div></section>';
    }

    gjsEditor.BlockManager.add('section', {
        category: BASIC,
        label: 'Sección',
        content: section('<h2 class="fw-bold mb-3">Título de la sección</h2><p class="text-body-secondary mb-0">Contenido de la sección. Sustituye este texto por el tuyo.</p>'),
        media: '<i class="bi bi-layout-text-window"></i>',
        description: 'Sección con container y espaciado vertical de Bootstrap'
    });

    /** Guía visual de columna vacía: discontinuo y gris del tema. */
    var colGuide = 'border border-2 border-secondary-subtle rounded-3 p-3 text-center small text-body-secondary';

    gjsEditor.BlockManager.add('heading', {
        category: BASIC,
        label: 'Título',
        content: '<h2 class="fw-bold">Título principal</h2>',
        media: '<i class="bi bi-type-h1"></i>',
        description: 'Encabezado h2 con tipografía Bootstrap'
    });

    gjsEditor.BlockManager.add('text', {
        category: BASIC,
        label: 'Texto',
        content: section('<p class="mb-0 lh-lg">Párrafo de ejemplo. Escribe aquí tu texto: la clase lh-lg da interlineado cómodo y text-body-secondary suaviza el color secundario.</p>'),
        media: '<i class="bi bi-paragraph"></i>',
        description: 'Párrafo con line-height y color del tema'
    });

    gjsEditor.BlockManager.add('image', {
        category: BASIC,
        label: 'Imagen',
        content: '<figure class="figure w-100 mb-0"><img src="https://placehold.co/1200x600/eee/999?text=Imagen" class="figure-img img-fluid rounded-3 w-100" alt="Descripción de la imagen"><figcaption class="figure-caption">Pie de foto opcional</figcaption></figure>',
        media: '<i class="bi bi-image"></i>',
        description: 'Imagen responsive con figure y caption'
    });

    gjsEditor.BlockManager.add('button', {
        category: BASIC,
        label: 'Botón',
        content: '<a href="#" class="btn btn-primary px-4">Texto del botón</a>',
        media: '<i class="bi bi-hand-index"></i>',
        description: 'Botón primario de Bootstrap'
    });

    gjsEditor.BlockManager.add('input-group', {
        category: BASIC,
        label: 'Campo con botón',
        content: '<div class="input-group"><input type="text" class="form-control" placeholder="Escribe aquí"><button class="btn btn-outline-secondary" type="button">Buscar</button></div>',
        media: '<i class="bi bi-input-cursor"></i>',
        description: 'Grupo de entrada con botón adjunto'
    });

    gjsEditor.BlockManager.add('contact-form', {
        category: BASIC,
        label: 'Formulario de contacto',
        content: section('<form class="mx-auto" style="max-width:560px">'
            + '<div class="row g-3">'
            + '<div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" placeholder="Tu nombre"></div>'
            + '<div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" placeholder="tu@correo.com"></div>'
            + '<div class="col-12"><label class="form-label">Mensaje</label><textarea class="form-control" rows="4" placeholder="Cuéntanos"></textarea></div>'
            + '<div class="col-12"><button type="submit" class="btn btn-primary">Enviar</button></div>'
            + '</div></form>'),
        media: '<i class="bi bi-envelope-paper"></i>',
        description: 'Formulario de contacto con grilla y validación visual Bootstrap'
    });

    gjsEditor.BlockManager.add('footer', {
        category: BASIC,
        label: 'Pie de página',
        content: '<footer class="py-4 border-top mt-5"><div class="container d-flex flex-wrap justify-content-between gap-2 small text-body-secondary">'
            + '<span>© 2026 Tu marca. Todos los derechos reservados.</span>'
            + '<span><a href="#" class="link-secondary text-decoration-none">Privacidad</a> · <a href="#" class="link-secondary text-decoration-none">Términos</a></span>'
            + '</div></footer>',
        media: '<i class="bi bi-layout-text-window-reverse"></i>',
        description: 'Pie simple con enlaces legales'
    });

    gjsEditor.BlockManager.add('navbar', {
        category: BASIC,
        label: 'Barra de navegación',
        content: '<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">'
            + '<div class="container">'
            + '<a class="navbar-brand fw-semibold" href="#">Tu marca</a>'
            + '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navBasic"><span class="navbar-toggler-icon"></span></button>'
            + '<div class="collapse navbar-collapse" id="navBasic"><ul class="navbar-nav ms-auto">'
            + '<li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>'
            + '<li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>'
            + '<li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>'
            + '</ul></div></div></nav>',
        media: '<i class="bi bi-menu-button-wide"></i>',
        description: 'Navbar responsive con colapso Bootstrap'
    });

    gjsEditor.BlockManager.add('flex-container', {
        category: BASIC,
        label: 'Contenedor flex',
        content: '<div class="d-flex gap-3 align-items-center justify-content-between p-3 border rounded-3">'
            + '<div class="' + colGuide + ' flex-fill">Elemento</div>'
            + '<div class="' + colGuide + ' flex-fill">Elemento</div>'
            + '</div>',
        media: '<i class="bi bi-distribute-horizontal"></i>',
        description: 'Fila flex de Bootstrap con utilidades d-flex y gap'
    });

    gjsEditor.BlockManager.add('row-2col', {
        category: BASIC,
        label: 'Fila de 2 columnas',
        content: '<div class="row g-4"><div class="col-md-6"><div class="' + colGuide + '">Columna 1</div></div><div class="col-md-6"><div class="' + colGuide + '">Columna 2</div></div></div>',
        media: '<i class="bi bi-layout-split"></i>',
        description: 'Grilla de 2 columnas que colapsa en móvil'
    });

    gjsEditor.BlockManager.add('row-3col', {
        category: BASIC,
        label: 'Fila de 3 columnas',
        content: '<div class="row g-4">'
            + ['1', '2', '3'].map(function (n) { return '<div class="col-md-4"><div class="' + colGuide + '">Columna ' + n + '</div></div>'; }).join('')
            + '</div>',
        media: '<i class="bi bi-layout-three-columns"></i>',
        description: 'Grilla de 3 columnas responsive'
    });

    gjsEditor.BlockManager.add('table', {
        category: BASIC,
        label: 'Tabla',
        content: '<div class="table-responsive"><table class="table table-striped table-hover align-middle mb-0">'
            + '<thead><tr><th scope="col">#</th><th scope="col">Concepto</th><th scope="col" class="text-end">Importe</th></tr></thead>'
            + '<tbody><tr><th scope="row">1</th><td>Servicio de diseño</td><td class="text-end">1.200 €</td></tr>'
            + '<tr><th scope="row">2</th><td>Desarrollo</td><td class="text-end">3.400 €</td></tr></tbody>'
            + '</table></div>',
        media: '<i class="bi bi-table"></i>',
        description: 'Tabla responsive con filas alternas y hover'
    });

    gjsEditor.BlockManager.add('list', {
        category: BASIC,
        label: 'Lista',
        content: '<ul class="list-group"><li class="list-group-item">Primer elemento</li><li class="list-group-item">Segundo elemento</li><li class="list-group-item">Tercer elemento</li></ul>',
        media: '<i class="bi bi-list-ul"></i>',
        description: 'Lista con list-group de Bootstrap'
    });

    gjsEditor.BlockManager.add('map', {
        category: BASIC,
        label: 'Mapa',
        content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden"><iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-3.72%2C40.40%2C-3.66%2C40.43&amp;layer=mapnik" title="Mapa" loading="lazy" style="border:0"></iframe></div>',
        media: '<i class="bi bi-geo-alt"></i>',
        description: 'Mapa embebido con proporción 16:9 y sin scripts'
    });

    gjsEditor.BlockManager.add('video', {
        category: BASIC,
        label: 'Vídeo',
        content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Vídeo" allowfullscreen loading="lazy"></iframe></div>',
        media: '<i class="bi bi-play-btn"></i>',
        description: 'Vídeo responsive con la clase ratio de Bootstrap'
    });

    gjsEditor.BlockManager.add('card', {
        category: BASIC,
        label: 'Tarjeta',
        content: '<div class="card h-100 shadow-sm"><div class="card-body"><h3 class="h5 fw-bold card-title">Título de la tarjeta</h3><p class="card-text text-body-secondary">Texto breve que acompaña al título.</p><a href="#" class="btn btn-primary btn-sm">Acción</a></div></div>',
        media: '<i class="bi bi-card-text"></i>',
        description: 'Card con título, texto y botón'
    });

    gjsEditor.BlockManager.add('pricing', {
        category: BASIC,
        label: 'Precio',
        content: '<div class="card text-center h-100 border-primary shadow-sm"><div class="card-body p-4">'
            + '<p class="text-uppercase small fw-bold text-primary mb-2">Plan</p>'
            + '<p class="display-5 fw-bold mb-1">29 €<span class="fs-6 fw-normal text-body-secondary">/mes</span></p>'
            + '<p class="text-body-secondary small mb-4">Todo lo necesario para empezar</p>'
            + '<a href="#" class="btn btn-primary w-100">Elegir plan</a>'
            + '</div></div>',
        media: '<i class="bi bi-tag"></i>',
        description: 'Tarjeta de precio con importe y botón'
    });

    gjsEditor.BlockManager.add('hero', {
        category: BASIC,
        label: 'Hero',
        content: '<section class="py-5 text-center bg-body-tertiary"><div class="container py-4">'
            + '<h1 class="display-5 fw-bold mb-3">Construye algo que la gente quiera usar</h1>'
            + '<p class="lead text-body-secondary mx-auto mb-4" style="max-width:640px">Una frase que explique qué haces y para quién.</p>'
            + '<a href="#" class="btn btn-primary btn-lg px-4">Empezar ahora</a>'
            + '</div></section>',
        media: '<i class="bi bi-easel"></i>',
        description: 'Cabecera centrada con titular, subtítulo y CTA'
    });

    gjsEditor.BlockManager.add('icon', {
        category: BASIC,
        label: 'Icono',
        content: '<i class="bi bi-star-fill fs-1 text-primary" aria-hidden="true"></i>',
        media: '<i class="bi bi-star"></i>',
        description: 'Icono de Bootstrap Icons'
    });

    gjsEditor.BlockManager.add('badge', {
        category: BASIC,
        label: 'Etiqueta',
        content: '<span class="badge rounded-pill text-bg-primary">Nuevo</span>',
        media: '<i class="bi bi-tag-fill"></i>',
        description: 'Badge de Bootstrap con color semántico'
    });

    gjsEditor.BlockManager.add('progress', {
        category: BASIC,
        label: 'Barra de progreso',
        content: '<div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="height:10px">'
            + '<div class="progress-bar" style="width:65%"></div></div>',
        media: '<i class="bi bi-bar-chart-steps"></i>',
        description: 'Barra de progreso con rol y valores accesibles'
    });

    gjsEditor.BlockManager.add('alert', {
        category: BASIC,
        label: 'Alerta',
        content: '<div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="alert">'
            + '<i class="bi bi-check-circle-fill"></i><div>Operación completada correctamente.</div></div>',
        media: '<i class="bi bi-exclamation-triangle"></i>',
        description: 'Alerta de éxito con icono y rol accesible'
    });
})();
