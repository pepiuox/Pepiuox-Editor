// ═══════════════════════════════════════════════════════════════════
// grapesjs-presentations.js — Presentaciones Bootstrap 5.3.3
//
// Adaptado desde la sintaxis de plugin ES module a IIFE con
// gjsEditor.BlockManager.add. Los tipos personalizados con traits
// (hero-section, carousel, cover-section, jumbotron, album-grid)
// se han convertido en bloques estáticos con variantes predefinidas
// para mantener consistencia con el resto de archivos del proyecto.
//
// Modernizaciones aplicadas:
//  - bm.add → gjsEditor.BlockManager.add
//  - via.placeholder.com → placehold.co
//  - text-muted → text-body-secondary
//  - bg-light → bg-body-tertiary
//  - bg-dark text-white → bg-dark con data-bs-theme="dark"
//  - ARIA completo
//  - Categorías en español con emojis
//
// @package Assets\Js\Blocks
// @since   2026-11-01
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[grapesjs-presentations] gjsEditor no existe todavía; fichero omitido.');
    return;
}

var PRES = { id: 'bs5-presentations', label: '🎬 Presentaciones', open: true };

// ══════════════════════════════════════════════════════════════════
// 🦸 HERO SECTIONS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('hero-basic', {
    category: PRES,
    label: '🦸 Hero Básico',
    media: '<i class="bi bi-badge-hd fs-3"></i>',
    description: 'Hero con fondo claro y 2 botones',
    content: '<section class="py-5 bg-body-tertiary"><div class="container py-5"><div class="row align-items-center"><div class="col-lg-8 mx-auto text-center"><p class="lead mb-3 text-body-secondary">La mejor solución para tu negocio</p><h1 class="display-3 fw-bold mb-4">Bienvenido a Nuestra Plataforma</h1><p class="lead mb-5 text-body-secondary">Descubre todas las herramientas que necesitas para hacer crecer tu empresa.</p><div class="d-flex gap-3 justify-content-center flex-wrap"><button class="btn btn-primary btn-lg px-4">Comenzar ahora</button><button class="btn btn-outline-primary btn-lg px-4">Ver demo</button></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('hero-dark', {
    category: PRES,
    label: '🌙 Hero Oscuro',
    media: '<i class="bi bi-moon-stars fs-3"></i>',
    description: 'Hero con fondo oscuro',
    content: '<section class="py-5 bg-dark text-white" data-bs-theme="dark"><div class="container py-5"><div class="row align-items-center"><div class="col-lg-8 mx-auto text-center"><p class="lead mb-3 opacity-75">Innovación y excelencia</p><h1 class="display-3 fw-bold mb-4">Tecnología de Vanguardia</h1><p class="lead mb-5 opacity-90">Soluciones avanzadas que transforman la manera en que trabajas.</p><div class="d-flex gap-3 justify-content-center flex-wrap"><button class="btn btn-light btn-lg px-4">Explorar</button><button class="btn btn-outline-light btn-lg px-4">Contactar</button></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('hero-gradient', {
    category: PRES,
    label: '🌈 Hero con Gradiente',
    media: '<i class="bi bi-palette fs-3"></i>',
    description: 'Hero con fondo en gradiente',
    content: '<section class="py-5 text-white" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)" data-bs-theme="dark"><div class="container py-5"><div class="row align-items-center"><div class="col-lg-8 mx-auto text-center"><p class="lead mb-3 opacity-75">Creatividad sin límites</p><h1 class="display-3 fw-bold mb-4">Diseño Creativo</h1><p class="lead mb-5 opacity-90">Herramientas de diseño que inspiran. Crea experiencias visuales únicas.</p><div class="d-flex gap-3 justify-content-center flex-wrap"><button class="btn btn-warning btn-lg px-4">Crear ahora</button><button class="btn btn-outline-light btn-lg px-4">Ver ejemplos</button></div></div></div></div></section>'
});

gjsEditor.BlockManager.add('hero-image', {
    category: PRES,
    label: '🖼️ Hero con Imagen',
    media: '<i class="bi bi-image fs-3"></i>',
    description: 'Hero con imagen de fondo',
    content: '<section class="py-5 text-white text-center" style="background-image:url(\'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920\');background-size:cover;background-position:center;min-height:500px;display:flex;align-items:center" data-bs-theme="dark"><div class="container"><p class="lead mb-3 opacity-75">Explora el mundo</p><h1 class="display-3 fw-bold mb-4">Aventura Te Espera</h1><p class="lead mb-5 opacity-90">Descubre destinos increíbles y vive experiencias únicas.</p><div class="d-flex gap-3 justify-content-center flex-wrap"><button class="btn btn-success btn-lg px-4">Reservar viaje</button><button class="btn btn-outline-light btn-lg px-4">Ver destinos</button></div></div></section>'
});

gjsEditor.BlockManager.add('hero-split', {
    category: PRES,
    label: '⚡ Hero Split',
    media: '<i class="bi bi-layout-split fs-3"></i>',
    description: 'Hero dividido con texto e imagen',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><div class="row align-items-center g-5"><div class="col-lg-6"><p class="text-primary fw-bold mb-2">NUEVA CARACTERÍSTICA</p><h1 class="display-4 fw-bold mb-3">Revoluciona tu forma de trabajar</h1><p class="lead text-body-secondary mb-4">Descubre la nueva forma de colaborar en equipo. Herramientas intuitivas, resultados extraordinarios.</p><div class="d-flex gap-3 flex-wrap"><button class="btn btn-primary btn-lg px-4">Comenzar gratis</button><button class="btn btn-outline-secondary btn-lg px-4">Saber más</button></div><div class="mt-4 d-flex align-items-center gap-3"><div class="d-flex"><img src="https://i.pravatar.cc/40?img=1" class="rounded-circle border border-2 border-white" style="margin-right:-10px" alt=""><img src="https://i.pravatar.cc/40?img=2" class="rounded-circle border border-2 border-white" style="margin-right:-10px" alt=""><img src="https://i.pravatar.cc/40?img=3" class="rounded-circle border border-2 border-white" alt=""></div><small class="text-body-secondary">+10,000 usuarios satisfechos</small></div></div><div class="col-lg-6"><img src="https://placehold.co/600x500/e9ecef/6c757d?text=Hero+Image" class="img-fluid rounded-3 shadow-lg" alt="Hero"></div></div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🎠 CARRUSELES
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('carousel-basic', {
    category: PRES,
    label: '🎠 Carrusel Básico',
    media: '<i class="bi bi-collection-play fs-3"></i>',
    description: 'Carrusel con controles, indicadores y captions',
    content: '<div id="carouselBasic" class="carousel slide shadow-sm rounded-3 overflow-hidden" data-bs-ride="carousel"><div class="carousel-indicators"><button type="button" data-bs-target="#carouselBasic" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button><button type="button" data-bs-target="#carouselBasic" data-bs-slide-to="1" aria-label="Slide 2"></button><button type="button" data-bs-target="#carouselBasic" data-bs-slide-to="2" aria-label="Slide 3"></button></div><div class="carousel-inner"><div class="carousel-item active"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920" class="d-block w-100" alt="Slide 1" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Primera Diapositiva</h2><p class="lead mb-3">Imagen impresionante de montañas.</p><button class="btn btn-light btn-lg px-4">Explorar</button></div></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1920" class="d-block w-100" alt="Slide 2" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Segunda Diapositiva</h2><p class="lead mb-3">Paisaje natural atractivo.</p><button class="btn btn-light btn-lg px-4">Explorar</button></div></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920" class="d-block w-100" alt="Slide 3" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Tercera Diapositiva</h2><p class="lead mb-3">Bosque verde impactante.</p><button class="btn btn-light btn-lg px-4">Explorar</button></div></div></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselBasic" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselBasic" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button></div>'
});

gjsEditor.BlockManager.add('carousel-autoplay', {
    category: PRES,
    label: '🔄 Carrusel Autoplay',
    media: '<i class="bi bi-arrow-repeat fs-3"></i>',
    description: 'Carrusel con reproducción automática',
    content: '<div id="carouselAuto" class="carousel slide carousel-fade shadow-sm rounded-3 overflow-hidden" data-bs-ride="carousel"><div class="carousel-indicators"><button type="button" data-bs-target="#carouselAuto" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button><button type="button" data-bs-target="#carouselAuto" data-bs-slide-to="1" aria-label="Slide 2"></button><button type="button" data-bs-target="#carouselAuto" data-bs-slide-to="2" aria-label="Slide 3"></button></div><div class="carousel-inner"><div class="carousel-item active"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920" class="d-block w-100" alt="Slide 1" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Primera Diapositiva</h2><p class="lead mb-3">Reproducción automática activada.</p></div></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1920" class="d-block w-100" alt="Slide 2" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Segunda Diapositiva</h2><p class="lead mb-3">Transición con efecto fade.</p></div></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920" class="d-block w-100" alt="Slide 3" style="height:600px;object-fit:cover"><div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3"><h2 class="display-4 fw-bold mb-3">Tercera Diapositiva</h2><p class="lead mb-3">Cambio automático cada 5 segundos.</p></div></div></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselAuto" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselAuto" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button></div>'
});

gjsEditor.BlockManager.add('carousel-fullscreen', {
    category: PRES,
    label: '🖥️ Carrusel Fullscreen',
    media: '<i class="bi bi-arrows-fullscreen fs-3"></i>',
    description: 'Carrusel a pantalla completa',
    content: '<div id="carouselFull" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner"><div class="carousel-item active"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920" class="d-block w-100" alt="Slide 1" style="height:100vh;object-fit:cover"><div class="carousel-caption d-none d-md-block"><h2 class="display-2 fw-bold mb-3">Pantalla Completa</h2><p class="lead mb-3">Experiencia inmersiva a pantalla completa.</p><button class="btn btn-light btn-lg px-4">Comenzar</button></div></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1920" class="d-block w-100" alt="Slide 2" style="height:100vh;object-fit:cover"><div class="carousel-caption d-none d-md-block"><h2 class="display-2 fw-bold mb-3">Segunda Imagen</h2><p class="lead mb-3">Otra vista impresionante.</p></div></div></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselFull" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselFull" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button></div>'
});

gjsEditor.BlockManager.add('carousel-simple', {
    category: PRES,
    label: '📷 Carrusel Simple',
    media: '<i class="bi bi-images fs-3"></i>',
    description: 'Carrusel sin captions, solo imágenes',
    content: '<div id="carouselSimple" class="carousel slide shadow-sm rounded-3 overflow-hidden"><div class="carousel-indicators"><button type="button" data-bs-target="#carouselSimple" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button><button type="button" data-bs-target="#carouselSimple" data-bs-slide-to="1" aria-label="Slide 2"></button><button type="button" data-bs-target="#carouselSimple" data-bs-slide-to="2" aria-label="Slide 3"></button></div><div class="carousel-inner"><div class="carousel-item active"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920" class="d-block w-100" alt="Slide 1" style="height:500px;object-fit:cover"></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1920" class="d-block w-100" alt="Slide 2" style="height:500px;object-fit:cover"></div><div class="carousel-item"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920" class="d-block w-100" alt="Slide 3" style="height:500px;object-fit:cover"></div></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselSimple" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselSimple" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button></div>'
});

// ══════════════════════════════════════════════════════════════════
// 📖 COVERS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('cover-minimal', {
    category: PRES,
    label: '📖 Cover Minimalista',
    media: '<i class="bi bi-align-center fs-3"></i>',
    description: 'Cover centrado con diseño limpio',
    content: '<div class="container py-5"><div class="row align-items-center" style="min-height:75vh"><div class="col-lg-8 mx-auto text-center"><p class="text-body-secondary text-uppercase fw-bold mb-3">Diseño moderno</p><h1 class="display-2 fw-light mb-4">Simplicidad Elegante</h1><p class="lead text-body-secondary mb-5">Menos es más. Descubre la belleza en la simplicidad.</p><button class="btn btn-primary btn-lg px-5">Explorar</button></div></div></div>'
});

gjsEditor.BlockManager.add('cover-bold', {
    category: PRES,
    label: '💪 Cover Audaz',
    media: '<i class="bi bi-lightning-charge fs-3"></i>',
    description: 'Cover con fondo oscuro y gran impacto',
    content: '<div class="container-fluid py-5"><div class="row align-items-center" style="min-height:75vh"><div class="col-lg-10 mx-auto"><div class="p-5 bg-dark text-white rounded-4 shadow-lg" data-bs-theme="dark"><div class="row align-items-center"><div class="col-md-8"><p class="text-warning text-uppercase fw-bold mb-2">Destaca con fuerza</p><h1 class="display-1 fw-bold mb-3">IMPACTO VISUAL</h1><p class="lead opacity-75 mb-0">Diseños que capturan la atención y transmiten poder.</p></div><div class="col-md-4 text-md-end mt-3 mt-md-0"><button class="btn btn-warning btn-lg px-4">Ver más</button></div></div></div></div></div></div>'
});

gjsEditor.BlockManager.add('cover-elegant', {
    category: PRES,
    label: '✨ Cover Elegante',
    media: '<i class="bi bi-stars fs-3"></i>',
    description: 'Cover con bordes decorativos',
    content: '<div class="container py-5"><div class="row align-items-center" style="min-height:75vh"><div class="col-lg-6 mx-auto text-center"><div class="border-top border-bottom border-3 py-5"><p class="text-body-secondary fst-italic mb-3">Estilo atemporal</p><h1 class="display-3 fst-italic fw-light mb-4">Sofisticación</h1><p class="lead text-body-secondary mb-5">La elegancia es la única belleza que nunca se desvanece.</p><button class="btn btn-dark btn-lg px-5">Descubrir</button></div></div></div></div>'
});

gjsEditor.BlockManager.add('cover-modern', {
    category: PRES,
    label: '🚀 Cover Moderno',
    media: '<i class="bi bi-rocket-takeoff fs-3"></i>',
    description: 'Cover split con imagen y texto',
    content: '<div class="container-fluid py-5"><div class="row align-items-center" style="min-height:75vh"><div class="col-lg-10 mx-auto"><div class="row g-4 align-items-center"><div class="col-md-6"><div class="bg-primary text-white p-5 rounded-4 shadow" data-bs-theme="dark"><p class="text-uppercase fw-bold mb-2 opacity-75">Tecnología avanzada</p><h1 class="display-3 fw-bold mb-3">Innovación</h1><p class="lead opacity-90 mb-4">Soluciones del futuro disponibles hoy.</p><button class="btn btn-light btn-lg px-4">Comenzar</button></div></div><div class="col-md-6"><div class="bg-body-tertiary p-5 rounded-4 shadow-sm"><img src="https://placehold.co/500x500/e9ecef/6c757d?text=Cover+Image" class="rounded-3 img-fluid" alt="Cover"></div></div></div></div></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 🎯 JUMBOTRONS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('jumbotron-basic', {
    category: PRES,
    label: '🎯 Jumbotron Básico',
    media: '<i class="bi bi-easel fs-3"></i>',
    description: 'Jumbotron con fondo claro',
    content: '<div class="p-5 mb-4 bg-body-tertiary rounded-3"><div class="container py-5"><p class="lead mb-2">Bienvenido</p><h1 class="display-4 fw-bold mb-3">Hello, world!</h1><p class="lead mb-4">Este es un jumbotron simple diseñado para ocupar todo el ancho de la página padre.</p><hr class="my-4"><p class="mb-4">Usa utilidades para aumentar y disminuir el tamaño según sea necesario.</p><button class="btn btn-primary btn-lg px-4">Saber más</button></div></div>'
});

gjsEditor.BlockManager.add('jumbotron-primary', {
    category: PRES,
    label: '🎨 Jumbotron Primario',
    media: '<i class="bi bi-palette-fill fs-3"></i>',
    description: 'Jumbotron con fondo primario',
    content: '<div class="p-5 mb-4 text-bg-primary rounded-3" data-bs-theme="dark"><div class="container py-5 my-5"><p class="lead mb-2">La mejor solución</p><h1 class="display-4 fw-bold mb-3">Bienvenido a Nuestra Plataforma</h1><p class="lead mb-4">Descubre todas las características que hemos preparado para ti. Comienza tu viaje ahora mismo.</p><hr class="my-4 border-light"><p class="mb-4">Usa utilidades para aumentar y disminuir el tamaño según sea necesario.</p><button class="btn btn-light btn-lg px-4">Comenzar</button></div></div>'
});

gjsEditor.BlockManager.add('jumbotron-dark', {
    category: PRES,
    label: '🌙 Jumbotron Oscuro',
    media: '<i class="bi bi-moon-stars fs-3"></i>',
    description: 'Jumbotron con fondo oscuro',
    content: '<div class="p-5 mb-4 text-bg-dark rounded-3" data-bs-theme="dark"><div class="container-fluid py-5"><p class="lead mb-2">Innovación</p><h1 class="display-4 fw-bold mb-3">Tecnología Avanzada</h1><p class="lead mb-4">Soluciones de vanguardia para empresas modernas. Transforma tu negocio con nuestras herramientas.</p><hr class="my-4 border-light"><p class="mb-4">Usa utilidades para aumentar y disminuir el tamaño según sea necesario.</p><button class="btn btn-light btn-lg px-4">Explorar</button></div></div>'
});

gjsEditor.BlockManager.add('jumbotron-fluid', {
    category: PRES,
    label: '🌊 Jumbotron Fluido',
    media: '<i class="bi bi-arrows-fullscreen fs-3"></i>',
    description: 'Jumbotron a ancho completo',
    content: '<div class="p-5 mb-4 text-bg-success rounded-3" data-bs-theme="dark"><div class="container-fluid py-5"><p class="lead mb-2">Sin restricciones</p><h1 class="display-4 fw-bold mb-3">Ancho Completo</h1><p class="lead mb-4">Este jumbotron ocupa todo el ancho de la ventana, ideal para secciones destacadas.</p><hr class="my-4 border-light"><p class="mb-4">Usa utilidades para aumentar y disminuir el tamaño según sea necesario.</p><button class="btn btn-light btn-lg px-4">Ver más</button></div></div>'
});

// ══════════════════════════════════════════════════════════════════
// 📸 ALBUMS / GALERÍAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('album-3cols', {
    category: PRES,
    label: '📸 Álbum 3 Columnas',
    media: '<i class="bi bi-grid-3x3 fs-3"></i>',
    description: 'Galería de 3 columnas con hover',
    content: '<style>.album-item{position:relative;overflow:hidden;border-radius:.5rem}.album-item img{transition:transform .3s ease}.album-item:hover img{transform:scale(1.1)}.album-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7),transparent);opacity:0;transition:opacity .3s;display:flex;align-items:flex-end;padding:1rem;color:#fff}.album-item:hover .album-overlay{opacity:1}</style><div class="container py-5"><div class="text-center mb-5"><h2 class="display-5 fw-bold mb-3">Galería de Fotos</h2><p class="lead text-body-secondary">Una colección de imágenes impresionantes</p></div><div class="row g-3"><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600" class="img-fluid w-100" alt="Montañas" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Montañas</h5></div></div></div><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600" class="img-fluid w-100" alt="Naturaleza" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Naturaleza</h5></div></div></div><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=600" class="img-fluid w-100" alt="Bosque" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Bosque</h5></div></div></div><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600" class="img-fluid w-100" alt="Valle" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Valle</h5></div></div></div><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=600" class="img-fluid w-100" alt="Lago" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Lago</h5></div></div></div><div class="col-md-4"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1418065460487-3e41a6c84dc5?w=600" class="img-fluid w-100" alt="Bosque Verde" style="height:250px;object-fit:cover"><div class="album-overlay"><h5 class="mb-0">Bosque Verde</h5></div></div></div></div></div>'
});

gjsEditor.BlockManager.add('album-4cols', {
    category: PRES,
    label: '🖼️ Álbum 4 Columnas',
    media: '<i class="bi bi-grid fs-3"></i>',
    description: 'Galería de 4 columnas sin títulos',
    content: '<style>.album-item{position:relative;overflow:hidden;border-radius:.5rem}.album-item img{transition:transform .3s ease}.album-item:hover img{transform:scale(1.1)}</style><div class="container py-5"><div class="text-center mb-5"><h2 class="display-5 fw-bold mb-3">Portfolio</h2><p class="lead text-body-secondary">Nuestros mejores trabajos</p></div><div class="row g-2"><div class="col-md-3 col-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400" class="img-fluid w-100" alt="Imagen 1" style="height:200px;object-fit:cover"></div></div><div class="col-md-3 col-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=400" class="img-fluid w-100" alt="Imagen 2" style="height:200px;object-fit:cover"></div></div><div class="col-md-3 col-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=400" class="img-fluid w-100" alt="Imagen 3" style="height:200px;object-fit:cover"></div></div><div class="col-md-3 col-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=400" class="img-fluid w-100" alt="Imagen 4" style="height:200px;object-fit:cover"></div></div></div></div>'
});

gjsEditor.BlockManager.add('album-2cols', {
    category: PRES,
    label: '📷 Álbum 2 Columnas',
    media: '<i class="bi bi-image fs-3"></i>',
    description: 'Galería de 2 columnas con títulos grandes',
    content: '<style>.album-item{position:relative;overflow:hidden;border-radius:.5rem}.album-item img{transition:transform .3s ease}.album-item:hover img{transform:scale(1.1)}.album-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7),transparent);opacity:0;transition:opacity .3s;display:flex;align-items:flex-end;padding:1.5rem;color:#fff}.album-item:hover .album-overlay{opacity:1}</style><div class="container py-5"><div class="text-center mb-5"><h2 class="display-5 fw-bold mb-3">Momentos Especiales</h2><p class="lead text-body-secondary">Recuerdos inolvidables</p></div><div class="row g-4"><div class="col-md-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800" class="img-fluid w-100" alt="Imagen 1" style="height:350px;object-fit:cover"><div class="album-overlay"><h4 class="mb-0">Título 1</h4></div></div></div><div class="col-md-6"><div class="album-item shadow-sm"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800" class="img-fluid w-100" alt="Imagen 2" style="height:350px;object-fit:cover"><div class="album-overlay"><h4 class="mb-0">Título 2</h4></div></div></div></div></div>'
});

gjsEditor.BlockManager.add('album-masonry', {
    category: PRES,
    label: '🎨 Álbum Masonry',
    media: '<i class="bi bi-grip-vertical fs-3"></i>',
    description: 'Galería tipo Pinterest con alturas variables',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><div class="text-center mb-5"><h2 class="display-5 fw-bold mb-3">Galería Creativa</h2><p class="lead text-body-secondary">Diseño tipo masonry con alturas variables</p></div><div class="row g-3"><div class="col-md-4"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600" class="img-fluid rounded-3 shadow-sm mb-3" alt="Imagen 1" style="height:300px;object-fit:cover;width:100%"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 2" style="height:200px;object-fit:cover;width:100%"></div><div class="col-md-4"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=600" class="img-fluid rounded-3 shadow-sm mb-3" alt="Imagen 3" style="height:200px;object-fit:cover;width:100%"><img src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600" class="img-fluid rounded-3 shadow-sm mb-3" alt="Imagen 4" style="height:300px;object-fit:cover;width:100%"></div><div class="col-md-4"><img src="https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=600" class="img-fluid rounded-3 shadow-sm mb-3" alt="Imagen 5" style="height:250px;object-fit:cover;width:100%"><img src="https://images.unsplash.com/photo-1418065460487-3e41a6c84dc5?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 6" style="height:250px;object-fit:cover;width:100%"></div></div></div></section>'
});

gjsEditor.BlockManager.add('album-lightbox', {
    category: PRES,
    label: '💡 Álbum con Lightbox',
    media: '<i class="bi bi-zoom-in fs-3"></i>',
    description: 'Galería con enlaces para lightbox',
    content: '<section class="py-5"><div class="container"><div class="text-center mb-5"><h2 class="display-5 fw-bold mb-3">Galería Interactiva</h2><p class="lead text-body-secondary">Haz clic en las imágenes para ampliarlas</p></div><div class="row g-3"><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 1" style="height:250px;object-fit:cover;width:100%"></a></div><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 2" style="height:250px;object-fit:cover;width:100%"></a></div><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 3" style="height:250px;object-fit:cover;width:100%"></a></div><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 4" style="height:250px;object-fit:cover;width:100%"></a></div><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 5" style="height:250px;object-fit:cover;width:100%"></a></div><div class="col-md-4 col-sm-6"><a href="https://images.unsplash.com/photo-1418065460487-3e41a6c84dc5?w=1200" class="d-block"><img src="https://images.unsplash.com/photo-1418065460487-3e41a6c84dc5?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 6" style="height:250px;object-fit:cover;width:100%"></a></div></div></div></section>'
});

// ══════════════════════════════════════════════════════════════════
// 🎬 PRESENTACIÓN COMPLETA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('presentation-full', {
    category: PRES,
    label: '🎬 Presentación Completa',
    media: '<i class="bi bi-window-desktop fs-3"></i>',
    description: 'Landing completa: hero + features + galería + CTA',
    content: '<section class="text-white text-center py-5" style="background-image:url(\'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920\');background-size:cover;background-position:center;min-height:80vh;display:flex;align-items:center" data-bs-theme="dark"><div class="container"><h1 class="display-2 fw-bold mb-4">Bienvenido al Futuro</h1><p class="lead mb-5">Descubre experiencias únicas que transformarán tu perspectiva</p><button class="btn btn-light btn-lg px-5 me-3">Comenzar</button><button class="btn btn-outline-light btn-lg px-5">Saber más</button></div></section><section class="py-5 bg-body-tertiary"><div class="container"><div class="text-center mb-5"><h2 class="display-5 fw-bold">Características Principales</h2><p class="lead text-body-secondary">Todo lo que necesitas en un solo lugar</p></div><div class="row g-4"><div class="col-md-4 text-center"><div class="p-4 bg-body rounded-3 shadow-sm h-100"><div class="display-1 mb-3">🚀</div><h4 class="fw-bold">Rápido</h4><p class="text-body-secondary mb-0">Rendimiento optimizado para la mejor experiencia</p></div></div><div class="col-md-4 text-center"><div class="p-4 bg-body rounded-3 shadow-sm h-100"><div class="display-1 mb-3">🔒</div><h4 class="fw-bold">Seguro</h4><p class="text-body-secondary mb-0">Protección de datos de primer nivel</p></div></div><div class="col-md-4 text-center"><div class="p-4 bg-body rounded-3 shadow-sm h-100"><div class="display-1 mb-3">💡</div><h4 class="fw-bold">Intuitivo</h4><p class="text-body-secondary mb-0">Fácil de usar para cualquier persona</p></div></div></div></div></section><section class="py-5"><div class="container"><div class="text-center mb-5"><h2 class="display-5 fw-bold">Nuestra Galería</h2><p class="lead text-body-secondary">Explora nuestros mejores momentos</p></div><div class="row g-3"><div class="col-md-4"><img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 1" style="height:250px;object-fit:cover;width:100%"></div><div class="col-md-4"><img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 2" style="height:250px;object-fit:cover;width:100%"></div><div class="col-md-4"><img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=600" class="img-fluid rounded-3 shadow-sm" alt="Imagen 3" style="height:250px;object-fit:cover;width:100%"></div></div></div></section><section class="py-5 text-bg-primary text-center" data-bs-theme="dark"><div class="container"><h2 class="display-5 fw-bold mb-3">¿Listo para comenzar?</h2><p class="lead mb-4 opacity-75">Únete a miles de usuarios satisfechos hoy mismo</p><button class="btn btn-light btn-lg px-5">Comenzar ahora</button></div></section>'
});

console.log('[grapesjs-presentations] ✓ Presentaciones Bootstrap 5 modernizadas con BS 5.3.3');
})();