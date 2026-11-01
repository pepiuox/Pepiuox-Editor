// ═══════════════════════════════════════════════════════════════════
// blocks-advanced.js — Componentes avanzados con Bootstrap 5.3.3
//
// Modernización completa:
//  - Font Awesome (fas/fab/far) → Bootstrap Icons (bi)
//  - via.placeholder.com → placehold.co
//  - randomuser.me → placehold.co (más ligero, sin dependencias)
//  - CSS inline excesivo → clases de utilidad BS5
//  - text-muted → text-body-secondary
//  - bg-light → bg-body-tertiary
//  - bg-dark text-white → data-bs-theme="dark"
//  - IIFE con verificación de gjsEditor
//  - Accesibilidad ARIA completa
//  - Categorías con emojis descriptivos
//
// @package Assets\Js\Blocks
// @since   2026-11-01 (modernización BS5)
// ═══════════════════════════════════════════════════════════════════
(function () {
'use strict';
if (typeof gjsEditor === 'undefined' || !gjsEditor) {
    console.warn('[blocks-advanced] gjsEditor no existe todavía; fichero omitido.');
    return;
}

// ── CATEGORÍAS ──────────────────────────────────────────────────
var GALLERY = { id: 'px-gallery',      label: '🎨 Galerías',           open: false };
var MAPS    = { id: 'px-maps',         label: '🗺️ Mapas',              open: false };
var TABLES  = { id: 'px-tables',       label: '📊 Tablas avanzadas',   open: false };
var TESTIM  = { id: 'px-testimonials', label: '⭐ Testimonios',        open: false };
var EXTRA   = { id: 'px-extra',        label: '🚀 Componentes extra',  open: false };

// ══════════════════════════════════════════════════════════════════
// 🎨 GALERÍAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('advanced-gallery-lightbox', {
    category: GALLERY,
    label: 'Galería Lightbox',
    media: '<i class="bi bi-images fs-3"></i>',
    description: 'Galería de imágenes con enlaces para lightbox',
    content: '<div class="row g-3">'
        + [1,2,3,4,5,6].map(function(n){
            var colors = ['667eea','764ba2','f093fb','4facfe','43e97b','fa709a'];
            return '<div class="col-md-4 col-sm-6">'
                + '<a href="https://placehold.co/1200x800/' + colors[n-1] + '/white?text=Imagen+' + n + '" data-lightbox="gallery" data-title="Imagen ' + n + '">'
                + '<img src="https://placehold.co/600x400/' + colors[n-1] + '/white?text=Imagen+' + n + '" class="img-fluid rounded-3 shadow-sm w-100" style="height:220px;object-fit:cover" alt="Galería ' + n + '">'
                + '</a></div>';
        }).join('')
        + '</div>'
});

gjsEditor.BlockManager.add('advanced-gallery-grid-hover', {
    category: GALLERY,
    label: 'Galería con hover',
    media: '<i class="bi bi-grid-3x3-gap fs-3"></i>',
    description: 'Galería con efecto hover y overlay',
    content: '<style>.gx-item{position:relative;overflow:hidden;border-radius:.75rem}.gx-item img{transition:transform .5s;width:100%;height:250px;object-fit:cover}.gx-item:hover img{transform:scale(1.08)}.gx-overlay{position:absolute;inset:auto 0 0 0;background:linear-gradient(to top,rgba(0,0,0,.75),transparent);color:#fff;padding:1.25rem;transform:translateY(100%);transition:transform .3s}.gx-item:hover .gx-overlay{transform:translateY(0)}</style>'
        + '<div class="row g-3">'
        + ['Proyecto 1','Proyecto 2','Proyecto 3'].map(function(t,i){
            var colors = ['667eea','764ba2','f093fb'];
            return '<div class="col-md-4"><div class="gx-item shadow-sm">'
                + '<img src="https://placehold.co/600x400/' + colors[i] + '/white?text=' + encodeURIComponent(t) + '" alt="' + t + '">'
                + '<div class="gx-overlay"><h5 class="mb-1">' + t + '</h5><p class="small mb-0 opacity-75">Descripción del proyecto</p></div>'
                + '</div></div>';
        }).join('')
        + '</div>'
});

gjsEditor.BlockManager.add('advanced-gallery-masonry', {
    category: GALLERY,
    label: 'Galería Masonry',
    media: '<i class="bi bi-grip-vertical fs-3"></i>',
    description: 'Galería estilo Pinterest con columnas fluidas',
    content: '<style>.msnry{column-count:3;column-gap:1rem}.msnry-item{break-inside:avoid;margin-bottom:1rem}.msnry-item img{width:100%;border-radius:.75rem;transition:transform .3s}.msnry-item img:hover{transform:scale(1.02)}@media(max-width:768px){.msnry{column-count:2}}@media(max-width:576px){.msnry{column-count:1}}</style>'
        + '<div class="msnry">'
        + [['400x500','667eea','1'],['400x300','764ba2','2'],['400x600','f093fb','3'],['400x400','4facfe','4'],['400x350','43e97b','5'],['400x450','fa709a','6']].map(function(s){
            return '<div class="msnry-item"><img src="https://placehold.co/' + s[0] + '/' + s[1] + '/white?text=Imagen+' + s[2] + '" alt="Masonry ' + s[2] + '"></div>';
        }).join('')
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 🗺️ MAPAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('advanced-google-maps', {
    category: MAPS,
    label: 'Google Maps',
    media: '<i class="bi bi-geo-alt fs-3"></i>',
    description: 'Mapa de Google Maps responsive',
    content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">'
        + '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316bb6de37%3A0xc8892517b33c1f09!2sTimes%20Square!5e0!3m2!1sen!2sus" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa"></iframe>'
        + '</div>'
        + '<p class="text-body-secondary text-center mt-3 small mb-0"><i class="bi bi-geo-alt-fill text-danger me-1" aria-hidden="true"></i>Times Square, Nueva York, EE. UU.</p>'
});

gjsEditor.BlockManager.add('advanced-map-custom', {
    category: MAPS,
    label: 'Mapa con información',
    media: '<i class="bi bi-pin-map fs-3"></i>',
    description: 'Mapa con tarjeta de información adicional',
    content: '<div class="card shadow-sm border-0 overflow-hidden">'
        + '<div class="ratio ratio-16x9"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837007928749!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTorre%20Eiffel!5e0!3m2!1ses!2sfr" allowfullscreen loading="lazy" title="Mapa"></iframe></div>'
        + '<div class="card-body"><div class="row g-3">'
        + '<div class="col-md-6"><h6 class="fw-bold mb-1"><i class="bi bi-geo-alt-fill text-danger me-2" aria-hidden="true"></i>Dirección</h6><p class="small text-body-secondary mb-0">Champ de Mars, 5 Avenue Anatole France, 75007 París</p></div>'
        + '<div class="col-md-6"><h6 class="fw-bold mb-1"><i class="bi bi-clock-fill text-primary me-2" aria-hidden="true"></i>Horario</h6><p class="small text-body-secondary mb-0">Lunes a Domingo: 9:00 - 18:00</p></div>'
        + '</div></div></div>'
});

gjsEditor.BlockManager.add('advanced-openstreetmap', {
    category: MAPS,
    label: 'OpenStreetMap',
    media: '<i class="bi bi-globe-americas fs-3"></i>',
    description: 'Mapa de OpenStreetMap (alternativa gratuita)',
    content: '<div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">'
        + '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.openstreetmap.org/export/embed.html?bbox=-74.0060%2C40.7128%2C-73.9352%2C40.7614&layer=mapnik&marker=40.7420%2C-73.9870" title="Mapa OSM"></iframe>'
        + '</div>'
        + '<p class="text-center mt-2 mb-0 small"><a href="https://www.openstreetmap.org/" target="_blank" rel="noopener" class="link-secondary text-decoration-none">Ver mapa más grande</a></p>'
});

// ══════════════════════════════════════════════════════════════════
// 📊 TABLAS AVANZADAS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('advanced-responsive-table', {
    category: TABLES,
    label: 'Tabla responsive',
    media: '<i class="bi bi-table fs-3"></i>',
    description: 'Tabla con scroll horizontal en móviles',
    content: '<div class="table-responsive rounded-3 shadow-sm">'
        + '<table class="table table-striped table-hover align-middle mb-0">'
        + '<thead class="table-dark"><tr><th scope="col">#</th><th scope="col">Nombre</th><th scope="col">Email</th><th scope="col">Teléfono</th><th scope="col">Ciudad</th><th scope="col">Estado</th></tr></thead>'
        + '<tbody>'
        + '<tr><th scope="row">1</th><td>Juan Pérez</td><td>juan@email.com</td><td>+34 600 000 001</td><td>Madrid</td><td><span class="badge text-bg-success">Activo</span></td></tr>'
        + '<tr><th scope="row">2</th><td>María García</td><td>maria@email.com</td><td>+34 600 000 002</td><td>Barcelona</td><td><span class="badge text-bg-success">Activo</span></td></tr>'
        + '<tr><th scope="row">3</th><td>Carlos López</td><td>carlos@email.com</td><td>+34 600 000 003</td><td>Valencia</td><td><span class="badge text-bg-warning text-dark">Pendiente</span></td></tr>'
        + '<tr><th scope="row">4</th><td>Ana Martínez</td><td>ana@email.com</td><td>+34 600 000 004</td><td>Sevilla</td><td><span class="badge text-bg-secondary">Inactivo</span></td></tr>'
        + '</tbody></table></div>'
});

gjsEditor.BlockManager.add('advanced-table-advanced', {
    category: TABLES,
    label: 'Tabla con acciones',
    media: '<i class="bi bi-table-list fs-3"></i>',
    description: 'Tabla con paginación y botones de acción',
    content: '<div class="table-responsive rounded-3 shadow-sm">'
        + '<table class="table table-bordered table-hover align-middle mb-0">'
        + '<thead class="table-primary"><tr><th scope="col">ID</th><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col" class="text-center">Acciones</th></tr></thead>'
        + '<tbody>'
        + '<tr><th scope="row">001</th><td>Laptop Pro</td><td>Electrónica</td><td>1.299 €</td>'
        + '<td><div class="progress" style="height:8px" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width:75%"></div></div><small class="text-body-secondary">75%</small></td>'
        + '<td class="text-center"><button class="btn btn-sm btn-outline-primary me-1" aria-label="Editar"><i class="bi bi-pencil" aria-hidden="true"></i></button><button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>'
        + '<tr><th scope="row">002</th><td>Smartphone X</td><td>Electrónica</td><td>899 €</td>'
        + '<td><div class="progress" style="height:8px" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning" style="width:45%"></div></div><small class="text-body-secondary">45%</small></td>'
        + '<td class="text-center"><button class="btn btn-sm btn-outline-primary me-1" aria-label="Editar"><i class="bi bi-pencil" aria-hidden="true"></i></button><button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr>'
        + '</tbody></table></div>'
        + '<nav class="mt-3" aria-label="Paginación de tabla"><ul class="pagination pagination-sm justify-content-end mb-0"><li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Anterior</a></li><li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li><li class="page-item"><a class="page-link" href="#">2</a></li><li class="page-item"><a class="page-link" href="#">3</a></li><li class="page-item"><a class="page-link" href="#">Siguiente</a></li></ul></nav>'
});

gjsEditor.BlockManager.add('advanced-pricing-table', {
    category: TABLES,
    label: 'Tabla comparativa de precios',
    media: '<i class="bi bi-bar-chart fs-3"></i>',
    description: 'Tabla comparativa de planes y precios',
    content: '<div class="table-responsive rounded-3 shadow-sm">'
        + '<table class="table table-bordered text-center align-middle mb-0">'
        + '<thead><tr class="table-light"><th scope="col" class="text-start" style="width:30%">Características</th><th scope="col" class="table-primary">Básico</th><th scope="col" class="table-success">Pro</th><th scope="col" class="table-info">Empresa</th></tr></thead>'
        + '<tbody>'
        + '<tr><th scope="row" class="text-start bg-body-tertiary fw-bold">Precio</th><td>9 €/mes</td><td>29 €/mes</td><td>59 €/mes</td></tr>'
        + '<tr><th scope="row" class="text-start bg-body-tertiary fw-bold">Usuarios</th><td>5 usuarios</td><td>20 usuarios</td><td><i class="bi bi-infinity text-primary" aria-hidden="true"></i> Ilimitados</td></tr>'
        + '<tr><th scope="row" class="text-start bg-body-tertiary fw-bold">Almacenamiento</th><td>10 GB</td><td>100 GB</td><td>1 TB</td></tr>'
        + '<tr><th scope="row" class="text-start bg-body-tertiary fw-bold">Soporte</th><td><i class="bi bi-x-lg text-danger" aria-hidden="true"></i><span class="visually-hidden">No</span></td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i> Email</td><td><i class="bi bi-check-lg text-success" aria-hidden="true"></i> 24/7</td></tr>'
        + '<tr class="table-light"><td colspan="4"><button class="btn btn-outline-primary">Comparar planes</button></td></tr>'
        + '</tbody></table></div>'
});

// ══════════════════════════════════════════════════════════════════
// ⭐ TESTIMONIOS
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('advanced-testimonials-carousel', {
    category: TESTIM,
    label: 'Carrusel de testimonios',
    media: '<i class="bi bi-chat-quote fs-3"></i>',
    description: 'Carrusel de testimonios con diseño moderno',
    content: '<div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">'
        + '<div class="carousel-indicators">'
        + '<button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Testimonio 1"></button>'
        + '<button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="1" aria-label="Testimonio 2"></button>'
        + '<button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="2" aria-label="Testimonio 3"></button>'
        + '</div>'
        + '<div class="carousel-inner pb-5">'
        + '<div class="carousel-item active"><div class="card border-0 text-bg-primary mx-auto" style="max-width:700px"><div class="card-body p-5 text-center">'
        + '<img src="https://placehold.co/80x80/ffffff/667eea?text=MG" class="rounded-circle mb-3 border border-3 border-white" alt="Avatar" style="width:80px;height:80px">'
        + '<i class="bi bi-quote display-5 d-block mb-2 opacity-50" aria-hidden="true"></i>'
        + '<p class="lead mb-3">"Excelente servicio, superó todas mis expectativas. El equipo es muy profesional y atento."</p>'
        + '<h5 class="mb-1 fw-bold">María González</h5><small class="opacity-75">CEO de Empresa Tech</small>'
        + '<div class="text-warning mt-2" aria-label="5 estrellas"><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i></div>'
        + '</div></div></div>'
        + '<div class="carousel-item"><div class="card border-0 text-bg-success mx-auto" style="max-width:700px"><div class="card-body p-5 text-center">'
        + '<img src="https://placehold.co/80x80/ffffff/43e97b?text=CR" class="rounded-circle mb-3 border border-3 border-white" alt="Avatar" style="width:80px;height:80px">'
        + '<i class="bi bi-quote display-5 d-block mb-2 opacity-50" aria-hidden="true"></i>'
        + '<p class="lead mb-3">"La mejor decisión que tomamos para nuestra empresa. Resultados increíbles en tiempo récord."</p>'
        + '<h5 class="mb-1 fw-bold">Carlos Rodríguez</h5><small class="opacity-75">Director de Marketing</small>'
        + '<div class="text-warning mt-2" aria-label="5 estrellas"><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i></div>'
        + '</div></div></div>'
        + '<div class="carousel-item"><div class="card border-0 text-bg-info mx-auto" style="max-width:700px"><div class="card-body p-5 text-center">'
        + '<img src="https://placehold.co/80x80/ffffff/4facfe?text=AM" class="rounded-circle mb-3 border border-3 border-white" alt="Avatar" style="width:80px;height:80px">'
        + '<i class="bi bi-quote display-5 d-block mb-2 opacity-50" aria-hidden="true"></i>'
        + '<p class="lead mb-3">"Increíble atención al cliente y productos de alta calidad. 100% recomendado."</p>'
        + '<h5 class="mb-1 fw-bold">Ana Martínez</h5><small class="opacity-75">Emprendedora</small>'
        + '<div class="text-warning mt-2" aria-label="4.5 estrellas"><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-fill" aria-hidden="true"></i><i class="bi bi-star-half" aria-hidden="true"></i></div>'
        + '</div></div></div>'
        + '</div>'
        + '<button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>'
        + '<button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button>'
        + '</div>'
});

gjsEditor.BlockManager.add('advanced-testimonials-grid', {
    category: TESTIM,
    label: 'Grid de testimonios',
    media: '<i class="bi bi-people fs-3"></i>',
    description: 'Grid de testimonios con tarjetas',
    content: '<div class="row g-4">'
        + [['JP','Juan Pérez','Cliente Premium','"Servicio excepcional. Muy contento con los resultados obtenidos."',['full','full','full','full','full']],
           ['LF','Laura Fernández','Cliente Regular','"Muy profesionales y atentos. Resolvieron todas mis dudas rápidamente."',['full','full','full','full','half']],
           ['PS','Pedro Sánchez','Nuevo Cliente','"Buena relación calidad-precio. Cumplen con lo prometido."',['full','full','full','full','empty']]].map(function(t){
            return '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm p-4">'
                + '<div class="d-flex align-items-center mb-3">'
                + '<img src="https://placehold.co/60x60/667eea/white?text=' + t[0] + '" class="rounded-circle me-3" alt="Avatar" style="width:60px;height:60px">'
                + '<div><h6 class="mb-0 fw-bold">' + t[1] + '</h6><small class="text-body-secondary">' + t[2] + '</small></div></div>'
                + '<div class="text-warning mb-3" aria-label="Valoración">' + t[4].map(function(s){ return s === 'full' ? '<i class="bi bi-star-fill" aria-hidden="true"></i>' : s === 'half' ? '<i class="bi bi-star-half" aria-hidden="true"></i>' : '<i class="bi bi-star" aria-hidden="true"></i>'; }).join('') + '</div>'
                + '<p class="mb-0 text-body-secondary fst-italic">' + t[3] + '</p>'
                + '</div></div>';
        }).join('')
        + '</div>'
});

gjsEditor.BlockManager.add('advanced-video-testimonials', {
    category: TESTIM,
    label: 'Testimonios en vídeo',
    media: '<i class="bi bi-camera-video fs-3"></i>',
    description: 'Testimonios en formato vídeo con botón de reproducción',
    content: '<style>.vcard{position:relative;border-radius:.75rem;overflow:hidden;cursor:pointer}.vcard img{transition:transform .3s;width:100%;height:280px;object-fit:cover}.vcard:hover img{transform:scale(1.05)}.vplay{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,.7);width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;transition:all .3s}.vcard:hover .vplay{background:rgba(0,0,0,.9);width:72px;height:72px}</style>'
        + '<div class="row g-4">'
        + '<div class="col-md-6"><div class="vcard shadow-sm"><img src="https://placehold.co/600x400/667eea/white?text=Video+Testimonial+1" alt="Video testimonio 1"><div class="vplay"><i class="bi bi-play-fill" aria-hidden="true"></i></div><div class="p-3 bg-body-tertiary"><h6 class="mb-1 fw-bold">Testimonio de cliente satisfecho</h6><small class="text-body-secondary">Juan Pérez · 2:30 min</small></div></div></div>'
        + '<div class="col-md-6"><div class="vcard shadow-sm"><img src="https://placehold.co/600x400/764ba2/white?text=Video+Testimonial+2" alt="Video testimonio 2"><div class="vplay"><i class="bi bi-play-fill" aria-hidden="true"></i></div><div class="p-3 bg-body-tertiary"><h6 class="mb-1 fw-bold">Caso de éxito empresarial</h6><small class="text-body-secondary">María García · 3:15 min</small></div></div></div>'
        + '</div>'
});

// ══════════════════════════════════════════════════════════════════
// 🚀 COMPONENTES EXTRA
// ══════════════════════════════════════════════════════════════════
gjsEditor.BlockManager.add('advanced-timeline', {
    category: EXTRA,
    label: 'Línea de tiempo',
    media: '<i class="bi bi-clock-history fs-3"></i>',
    description: 'Línea de tiempo para mostrar hitos',
    content: '<style>.tl-item{position:relative;padding-left:40px;margin-bottom:2rem}.tl-badge{position:absolute;left:0;top:0;width:32px;height:32px;border-radius:50%;background:var(--bs-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px}.tl-badge::before{content:"";position:absolute;top:32px;left:15px;width:2px;height:calc(100% + 1rem);background:var(--bs-border-color)}.tl-item:last-child .tl-badge::before{display:none}</style>'
        + '<div class="tl-item"><div class="tl-badge"><i class="bi bi-rocket-takeoff" aria-hidden="true"></i></div><div class="card border-0 shadow-sm"><div class="card-body"><h5 class="fw-bold mb-1">2024 — Lanzamiento</h5><p class="text-body-secondary mb-0">Inicio del proyecto y primera versión pública.</p></div></div></div>'
        + '<div class="tl-item"><div class="tl-badge"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></div><div class="card border-0 shadow-sm"><div class="card-body"><h5 class="fw-bold mb-1">2025 — Crecimiento</h5><p class="text-body-secondary mb-0">Expansión a nuevos mercados internacionales.</p></div></div></div>'
        + '<div class="tl-item"><div class="tl-badge"><i class="bi bi-trophy" aria-hidden="true"></i></div><div class="card border-0 shadow-sm"><div class="card-body"><h5 class="fw-bold mb-1">2026 — Consolidación</h5><p class="text-body-secondary mb-0">Líderes del sector con presencia global.</p></div></div></div>'
});

gjsEditor.BlockManager.add('advanced-counter-animated', {
    category: EXTRA,
    label: 'Contadores animados',
    media: '<i class="bi bi-speedometer2 fs-3"></i>',
    description: 'Contadores para estadísticas destacadas',
    content: '<section class="py-5 bg-body-tertiary"><div class="container"><div class="row g-4 text-center">'
        + [['bi-people','1.234+','Clientes satisfechos','primary'],['bi-code-slash','567+','Proyectos completados','success'],['bi-trophy','45+','Premios ganados','warning'],['bi-globe2','89+','Países alcanzados','info']].map(function(c){
            return '<div class="col-md-3 col-6"><div class="card border-0 shadow-sm h-100 p-4">'
                + '<i class="bi ' + c[0] + ' fs-1 text-' + c[3] + ' mb-2 d-block" aria-hidden="true"></i>'
                + '<div class="display-5 fw-bold mb-1">' + c[1] + '</div>'
                + '<p class="text-body-secondary small mb-0">' + c[2] + '</p></div></div>';
        }).join('')
        + '</div></div></section>'
});

gjsEditor.BlockManager.add('advanced-faq-accordion', {
    category: EXTRA,
    label: 'FAQ Acordeón',
    media: '<i class="bi bi-question-circle fs-3"></i>',
    description: 'Sección de preguntas frecuentes',
    content: '<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-lg-8">'
        + '<div class="text-center mb-5"><h2 class="fw-bold">Preguntas frecuentes</h2><p class="text-body-secondary mb-0">Resolvemos tus dudas más comunes.</p></div>'
        + '<div class="accordion" id="faqAccordion">'
        + '<div class="accordion-item border-0 shadow-sm mb-2 rounded-3"><h2 class="accordion-header"><button class="accordion-button rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">¿Cómo puedo comenzar?</button></h2><div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion"><div class="accordion-body text-body-secondary">Puedes comenzar registrándote en nuestra plataforma y seleccionando el plan que mejor se adapte a tus necesidades. El proceso es rápido y sencillo.</div></div></div>'
        + '<div class="accordion-item border-0 shadow-sm mb-2 rounded-3"><h2 class="accordion-header"><button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">¿Cuál es el tiempo de entrega?</button></h2><div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-body-secondary">El tiempo de entrega varía según el proyecto, generalmente entre 2-4 semanas para proyectos estándar.</div></div></div>'
        + '<div class="accordion-item border-0 shadow-sm mb-2 rounded-3"><h2 class="accordion-header"><button class="accordion-button collapsed rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">¿Ofrecen soporte técnico?</button></h2><div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-body-secondary">Sí, ofrecemos soporte 24/7 a través de chat, email y teléfono para todos nuestros clientes.</div></div></div>'
        + '</div></div></div></section>'
});

gjsEditor.BlockManager.add('advanced-team-grid', {
    category: EXTRA,
    label: 'Grid de equipo',
    media: '<i class="bi bi-people-fill fs-3"></i>',
    description: 'Presentación del equipo con redes sociales',
    content: '<style>.tcard{text-align:center;border-radius:.75rem;overflow:hidden;transition:transform .3s}.tcard:hover{transform:translateY(-8px)}.tcard img{width:100%;height:280px;object-fit:cover}.tcard .tsocial a{color:var(--bs-secondary-color);margin:0 .5rem;font-size:1.1rem;transition:color .3s}.tcard .tsocial a:hover{color:var(--bs-primary)}</style>'
        + '<div class="row g-4">'
        + [['JP','Juan Pérez','CEO y Fundador',['bi-linkedin','bi-twitter-x','bi-github']],
           ['MG','María García','Directora Creativa',['bi-linkedin','bi-behance','bi-dribbble']],
           ['CL','Carlos López','Lead Developer',['bi-linkedin','bi-github','bi-stack-overflow']]].map(function(m){
            return '<div class="col-md-4"><div class="tcard card border-0 shadow-sm">'
                + '<img src="https://placehold.co/400x400/667eea/white?text=' + m[0] + '" alt="' + m[1] + '">'
                + '<div class="card-body p-4">'
                + '<h5 class="fw-bold mb-1">' + m[1] + '</h5>'
                + '<p class="text-body-secondary small mb-3">' + m[2] + '</p>'
                + '<div class="tsocial">' + m[3].map(function(i){ return '<a href="#" aria-label="' + i.replace('bi-','') + '"><i class="bi ' + i + '" aria-hidden="true"></i></a>'; }).join('') + '</div>'
                + '</div></div></div>';
        }).join('')
        + '</div>'
});

gjsEditor.BlockManager.add('advanced-newsletter', {
    category: EXTRA,
    label: 'Newsletter',
    media: '<i class="bi bi-envelope-paper fs-3"></i>',
    description: 'Formulario de suscripción a newsletter',
    content: '<section class="py-5"><div class="container"><div class="card border-0 shadow text-bg-primary overflow-hidden" data-bs-theme="dark">'
        + '<div class="card-body p-5 text-center">'
        + '<i class="bi bi-envelope-paper-heart display-3 mb-3 d-block" aria-hidden="true"></i>'
        + '<h3 class="fw-bold mb-2">Suscríbete a nuestro newsletter</h3>'
        + '<p class="mb-4 opacity-75">Recibe las últimas noticias y ofertas especiales directamente en tu correo.</p>'
        + '<form class="row g-2 justify-content-center"><div class="col-md-6"><input type="email" class="form-control form-control-lg" placeholder="tu@correo.com" aria-label="Correo electrónico" autocomplete="email"></div><div class="col-md-auto"><button type="submit" class="btn btn-light btn-lg px-4 fw-bold">Suscribirme</button></div></form>'
        + '<small class="d-block mt-3 opacity-75"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Sin spam, solo contenido valioso</small>'
        + '</div></div></div></section>'
});

console.log('[blocks-advanced] ✓ Componentes avanzados modernizados con Bootstrap 5.3.3');
})();