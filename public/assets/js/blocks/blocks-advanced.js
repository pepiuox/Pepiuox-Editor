// Componentes avanzados para GrapesJS
// Galería Lightbox, Mapas, Tablas responsivas, Testimonios Carousel, etc.
    // ==================== GALERÍA LIGHTBOX ====================

    // Galería Lightbox simple
    gjsEditor.BlockManager.add('advanced-gallery-lightbox', {
        category: {
            id: 'advanced-gallery',
            label: '🎨 Galerías y Lightbox',
            open: true
        },
        label: 'Galería Lightbox',
        content: `<div class="gallery-lightbox">
        <div class="row g-3">
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/667eea/white?text=Imagen+1" data-lightbox="gallery" data-title="Imagen 1">
        <img src="https://via.placeholder.com/400x300/667eea/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 1">
        </a>
        </div>
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/764ba2/white?text=Imagen+2" data-lightbox="gallery" data-title="Imagen 2">
        <img src="https://via.placeholder.com/400x300/764ba2/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 2">
        </a>
        </div>
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/f093fb/white?text=Imagen+3" data-lightbox="gallery" data-title="Imagen 3">
        <img src="https://via.placeholder.com/400x300/f093fb/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 3">
        </a>
        </div>
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/4facfe/white?text=Imagen+4" data-lightbox="gallery" data-title="Imagen 4">
        <img src="https://via.placeholder.com/400x300/4facfe/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 4">
        </a>
        </div>
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/43e97b/white?text=Imagen+5" data-lightbox="gallery" data-title="Imagen 5">
        <img src="https://via.placeholder.com/400x300/43e97b/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 5">
        </a>
        </div>
        <div class="col-md-4">
        <a href="https://via.placeholder.com/800x600/fa709a/white?text=Imagen+6" data-lightbox="gallery" data-title="Imagen 6">
        <img src="https://via.placeholder.com/400x300/fa709a/white?text=Click+para+ampliar" class="img-fluid rounded" alt="Galería 6">
        </a>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-images fa-2x"></i>',
        description: 'Galería de imágenes con efecto lightbox'
    });

    // Galería tipo grid con hover
    gjsEditor.BlockManager.add('advanced-gallery-grid-hover', {
        category: 'advanced-gallery',
        label: 'Galería Grid Hover',
        content: `<div class="gallery-grid-hover">
        <style>
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            cursor: pointer;
        }
        .gallery-item img {
            transition: transform 0.5s ease;
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        .gallery-overlay {
            position: absolute;
            bottom: -100%;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 20px;
            transition: bottom 0.3s ease;
        }
        .gallery-item:hover .gallery-overlay {
            bottom: 0;
        }
        </style>
        <div class="row g-3">
        <div class="col-md-4">
        <div class="gallery-item">
        <img src="https://via.placeholder.com/400x250/667eea/white?text=Proyecto+1" alt="Proyecto 1">
        <div class="gallery-overlay">
        <h4>Proyecto 1</h4>
        <p>Descripción del proyecto</p>
        </div>
        </div>
        </div>
        <div class="col-md-4">
        <div class="gallery-item">
        <img src="https://via.placeholder.com/400x250/764ba2/white?text=Proyecto+2" alt="Proyecto 2">
        <div class="gallery-overlay">
        <h4>Proyecto 2</h4>
        <p>Descripción del proyecto</p>
        </div>
        </div>
        </div>
        <div class="col-md-4">
        <div class="gallery-item">
        <img src="https://via.placeholder.com/400x250/f093fb/white?text=Proyecto+3" alt="Proyecto 3">
        <div class="gallery-overlay">
        <h4>Proyecto 3</h4>
        <p>Descripción del proyecto</p>
        </div>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-th-large fa-2x"></i>',
        description: 'Galería con efecto hover overlay'
    });

    // Galería Masonry (disposición tipo Pinterest)
    gjsEditor.BlockManager.add('advanced-gallery-masonry', {
        category: 'advanced-gallery',
        label: 'Galería Masonry',
        content: `<div class="gallery-masonry">
        <style>
        .masonry-grid {
            column-count: 3;
            column-gap: 1rem;
        }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1rem;
        }
        .masonry-item img {
            width: 100%;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .masonry-item img:hover {
            transform: scale(1.02);
        }
        @media (max-width: 768px) {
            .masonry-grid {
                column-count: 2;
            }
        }
        @media (max-width: 576px) {
            .masonry-grid {
                column-count: 1;
            }
        }
        </style>
        <div class="masonry-grid">
        <div class="masonry-item"><img src="https://via.placeholder.com/400x500/667eea/white?text=Imagen+1" alt="Masonry 1"></div>
        <div class="masonry-item"><img src="https://via.placeholder.com/400x300/764ba2/white?text=Imagen+2" alt="Masonry 2"></div>
        <div class="masonry-item"><img src="https://via.placeholder.com/400x600/f093fb/white?text=Imagen+3" alt="Masonry 3"></div>
        <div class="masonry-item"><img src="https://via.placeholder.com/400x400/4facfe/white?text=Imagen+4" alt="Masonry 4"></div>
        <div class="masonry-item"><img src="https://via.placeholder.com/400x350/43e97b/white?text=Imagen+5" alt="Masonry 5"></div>
        <div class="masonry-item"><img src="https://via.placeholder.com/400x450/fa709a/white?text=Imagen+6" alt="Masonry 6"></div>
        </div>
        </div>`,
        media: '<i class="fas fa-grip-vertical fa-2x"></i>',
        description: 'Galería estilo Pinterest con columnas fluidas'
    });

    // ==================== MAPAS EMBEBIDOS ====================

    // Mapa Google Maps básico
    gjsEditor.BlockManager.add('advanced-google-maps', {
        category: {
            id: 'advanced-maps',
            label: '🗺️ Mapas Embebidos',
            open: true
        },
        label: 'Google Maps',
        content: `<div class="google-maps-container">
        <style>
        .map-responsive {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .map-responsive iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        </style>
        <div class="map-responsive">
        <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316bb6de37%3A0xc8892517b33c1f09!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1641234567890!5m2!1sen!2sus"
        allowfullscreen=""
        loading="lazy">
        </iframe>
        </div>
        <div class="mt-3 text-center">
        <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Times Square, New York, USA</p>
        </div>
        </div>`,
        media: '<i class="fas fa-map-marked-alt fa-2x"></i>',
        description: 'Mapa de Google Maps responsive'
    });

    // Mapa con marcador personalizado
    gjsEditor.BlockManager.add('advanced-map-custom', {
        category: 'advanced-maps',
        label: 'Mapa con Marcador',
        content: `<div class="custom-map">
        <div class="card shadow-sm">
        <div class="position-relative">
        <div style="height: 400px;">
        <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837007928749!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTorre%20Eiffel!5e0!3m2!1ses!2sfr!4v1641234567890!5m2!1ses!2sfr"
        width="100%"
        height="100%"
        style="border:0; position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
        allowfullscreen=""
        loading="lazy">
        </iframe>
        </div>
        <div class="position-absolute bottom-0 start-0 p-3 bg-dark bg-opacity-75 text-white rounded-end">
        <i class="fas fa-map-pin me-2"></i>
        <strong>¡Estamos aquí!</strong>
        </div>
        </div>
        <div class="card-body">
        <div class="row">
        <div class="col-md-6">
        <h6><i class="fas fa-location-dot text-danger me-2"></i>Dirección</h6>
        <p class="small">Champ de Mars, 5 Avenue Anatole France, 75007 París, Francia</p>
        </div>
        <div class="col-md-6">
        <h6><i class="fas fa-clock text-info me-2"></i>Horario</h6>
        <p class="small">Lunes a Domingo: 9:00 - 18:00</p>
        </div>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-map-pin fa-2x"></i>',
        description: 'Mapa con información adicional y marcador'
    });

    // Mapa de OpenStreetMap
    gjsEditor.BlockManager.add('advanced-openstreetmap', {
        category: 'advanced-maps',
        label: 'OpenStreetMap',
        content: `<div class="openstreetmap-container">
        <style>
        .osm-map {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        </style>
        <div class="osm-map">
        <iframe
        width="100%"
        height="100%"
        frameborder="0"
        scrolling="no"
        marginheight="0"
        marginwidth="0"
        src="https://www.openstreetmap.org/export/embed.html?bbox=-74.0060%2C40.7128%2C-73.9352%2C40.7614&layer=mapnik&marker=40.7420%2C-73.9870">
        </iframe>
        </div>
        <div class="mt-2 text-center">
        <small><a href="https://www.openstreetmap.org/" target="_blank" class="text-decoration-none">Ver mapa más grande</a></small>
        </div>
        </div>`,
        media: '<i class="fas fa-globe-americas fa-2x"></i>',
        description: 'Mapa de OpenStreetMap (alternativa gratuita)'
    });

    // ==================== TABLAS RESPONSIVAS ====================

    // Tabla responsive con scroll
    gjsEditor.BlockManager.add('advanced-responsive-table', {
        category: {
            id: 'advanced-tables',
            label: '📊 Tablas Responsivas',
            open: true
        },
        label: 'Tabla Responsiva',
        content: `<div class="table-responsive">
        <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
        <th>#</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Teléfono</th>
        <th>Ciudad</th>
        <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td>1</td>
        <td>Juan Pérez</td>
        <td>juan@email.com</td>
        <td>+123 456 789</td>
        <td>Madrid</td>
        <td><span class="badge bg-success">Activo</span></td>
        </tr>
        <tr>
        <td>2</td>
        <td>María García</td>
        <td>maria@email.com</td>
        <td>+123 456 790</td>
        <td>Barcelona</td>
        <td><span class="badge bg-success">Activo</span></td>
        </tr>
        <tr>
        <td>3</td>
        <td>Carlos López</td>
        <td>carlos@email.com</td>
        <td>+123 456 791</td>
        <td>Valencia</td>
        <td><span class="badge bg-warning">Pendiente</span></td>
        </tr>
        <tr>
        <td>4</td>
        <td>Ana Martínez</td>
        <td>ana@email.com</td>
        <td>+123 456 792</td>
        <td>Sevilla</td>
        <td><span class="badge bg-secondary">Inactivo</span></td>
        </tr>
        </tbody>
        </table>
        </div>`,
        media: '<i class="fas fa-table fa-2x"></i>',
        description: 'Tabla con scroll horizontal en dispositivos móviles'
    });

    // Tabla con ordenamiento y paginación
    gjsEditor.BlockManager.add('advanced-table-advanced', {
        category: 'advanced-tables',
        label: 'Tabla Avanzada',
        content: `<div class="table-responsive">
        <table class="table table-bordered table-hover">
        <thead class="bg-primary text-white">
        <tr>
        <th>ID</th>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Precio</th>
        <th>Stock</th>
        <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td>001</td>
        <td>Laptop Pro</td>
        <td>Electrónica</td>
        <td>$1,299</td>
        <td>
        <div class="progress" style="height: 20px;">
        <div class="progress-bar bg-success" style="width: 75%">75%</div>
        </div>
        </td>
        <td>
        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
        </td>
        </tr>
        <tr>
        <td>002</td>
        <td>Smartphone X</td>
        <td>Electrónica</td>
        <td>$899</td>
        <td>
        <div class="progress" style="height: 20px;">
        <div class="progress-bar bg-warning" style="width: 45%">45%</div>
        </div>
        </td>
        <td>
        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
        </td>
        </tr>
        </tbody>
        </table>
        <nav>
        <ul class="pagination justify-content-end">
        <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
        </ul>
        </nav>
        </div>`,
        media: '<i class="fas fa-table-list fa-2x"></i>',
        description: 'Tabla con paginación y elementos interactivos'
    });

    // Tabla de precios comparativa
    gjsEditor.BlockManager.add('advanced-pricing-table', {
        category: 'advanced-tables',
        label: 'Tabla Comparativa',
        content: `<div class="table-responsive">
        <table class="table table-bordered text-center">
        <thead>
        <tr class="bg-light">
        <th style="width: 25%">Características</th>
        <th class="bg-primary text-white">Básico</th>
        <th class="bg-success text-white">Pro</th>
        <th class="bg-info text-white">Empresa</th>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td class="bg-light fw-bold">Precio</td>
        <td>$9/mes</td>
        <td>$29/mes</td>
        <td>$59/mes</td>
        </tr>
        <tr>
        <td class="bg-light fw-bold">Usuarios</td>
        <td>5 usuarios</td>
        <td>20 usuarios</td>
        <td><i class="fas fa-infinity"></i> Ilimitados</td>
        </tr>
        <tr>
        <td class="bg-light fw-bold">Almacenamiento</td>
        <td>10 GB</td>
        <td>100 GB</td>
        <td>1 TB</td>
        </tr>
        <tr>
        <td class="bg-light fw-bold">Soporte</td>
        <td><i class="fas fa-times text-danger"></i></td>
        <td><i class="fas fa-check text-success"></i> Email</td>
        <td><i class="fas fa-check text-success"></i> 24/7</td>
        </tr>
        <tr class="bg-light">
        <td colspan="4">
        <button class="btn btn-outline-primary">Comparar planes</button>
        </td>
        </tr>
        </tbody>
        </table>
        </div>`,
        media: '<i class="fas fa-chart-simple fa-2x"></i>',
        description: 'Tabla comparativa de precios y características'
    });

    // ==================== TESTIMONIOS EN CARRUSEL ====================

    // Carrusel de testimonios simple
    gjsEditor.BlockManager.add('advanced-testimonials-carousel', {
        category: {
            id: 'advanced-testimonials',
            label: '⭐ Testimonios y Reseñas',
            open: true
        },
        label: 'Carrusel Testimonios',
        content: `<div class="testimonials-carousel">
        <style>
        .testimonial-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
        }
        .testimonial-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            margin-bottom: 20px;
        }
        .testimonial-text {
            font-size: 1.2rem;
            font-style: italic;
            margin-bottom: 20px;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 20px;
        }
        </style>

        <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
        <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
        <div class="carousel-item active">
        <div class="testimonial-card">
        <img src="https://randomuser.me/api/portraits/women/68.jpg" class="testimonial-avatar" alt="Avatar">
        <i class="fas fa-quote-left fa-2x mb-3 opacity-50"></i>
        <p class="testimonial-text">"Excelente servicio, superó todas mis expectativas. El equipo es muy profesional y atento."</p>
        <h5 class="mb-1">María González</h5>
        <small class="opacity-75">CEO de Empresa Tech</small>
        <div class="mt-2">
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        </div>
        </div>
        </div>
        <div class="carousel-item">
        <div class="testimonial-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="testimonial-avatar" alt="Avatar">
        <i class="fas fa-quote-left fa-2x mb-3 opacity-50"></i>
        <p class="testimonial-text">"La mejor decisión que tomamos para nuestra empresa. Resultados increíbles en tiempo récord."</p>
        <h5 class="mb-1">Carlos Rodríguez</h5>
        <small class="opacity-75">Director de Marketing</small>
        <div class="mt-2">
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        </div>
        </div>
        </div>
        <div class="carousel-item">
        <div class="testimonial-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <img src="https://randomuser.me/api/portraits/women/45.jpg" class="testimonial-avatar" alt="Avatar">
        <i class="fas fa-quote-left fa-2x mb-3 opacity-50"></i>
        <p class="testimonial-text">"Increíble atención al cliente y productos de alta calidad. 100% recomendado."</p>
        <h5 class="mb-1">Ana Martínez</h5>
        <small class="opacity-75">Emprendedora</small>
        <div class="mt-2">
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star text-warning"></i>
        <i class="fas fa-star-half-alt text-warning"></i>
        </div>
        </div>
        </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        </button>
        </div>
        </div>`,
        media: '<i class="fas fa-comment-dots fa-2x"></i>',
        description: 'Carrusel de testimonios con diseño moderno'
    });

    // Grid de testimonios
    gjsEditor.BlockManager.add('advanced-testimonials-grid', {
        category: 'advanced-testimonials',
        label: 'Grid Testimonios',
        content: `<div class="testimonials-grid">
        <style>
        .testimonial-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        .testimonial-item:hover {
            transform: translateY(-5px);
        }
        .testimonial-avatar-small {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .rating i {
            color: #ffc107;
            margin-right: 2px;
        }
        </style>
        <div class="row g-4">
        <div class="col-md-4">
        <div class="testimonial-item">
        <div class="d-flex align-items-center mb-3">
        <img src="https://randomuser.me/api/portraits/men/1.jpg" class="testimonial-avatar-small" alt="Avatar">
        <div>
        <h6 class="mb-0">Juan Pérez</h6>
        <small class="text-muted">Cliente Premium</small>
        </div>
        </div>
        <div class="rating mb-3">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        </div>
        <p class="mb-0">"Servicio excepcional. Muy contento con los resultados obtenidos."</p>
        </div>
        </div>
        <div class="col-md-4">
        <div class="testimonial-item">
        <div class="d-flex align-items-center mb-3">
        <img src="https://randomuser.me/api/portraits/women/2.jpg" class="testimonial-avatar-small" alt="Avatar">
        <div>
        <h6 class="mb-0">Laura Fernández</h6>
        <small class="text-muted">Cliente Regular</small>
        </div>
        </div>
        <div class="rating mb-3">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star-half-alt"></i>
        </div>
        <p class="mb-0">"Muy profesionales y atentos. Resolvieron todas mis dudas rápidamente."</p>
        </div>
        </div>
        <div class="col-md-4">
        <div class="testimonial-item">
        <div class="d-flex align-items-center mb-3">
        <img src="https://randomuser.me/api/portraits/men/3.jpg" class="testimonial-avatar-small" alt="Avatar">
        <div>
        <h6 class="mb-0">Pedro Sánchez</h6>
        <small class="text-muted">Nuevo Cliente</small>
        </div>
        </div>
        <div class="rating mb-3">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="far fa-star"></i>
        </div>
        <p class="mb-0">"Buena relación calidad-precio. Cumplen con lo prometido."</p>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-users fa-2x"></i>',
        description: 'Grid de testimonios con diseño de tarjetas'
    });

    // Testimonios con video
    gjsEditor.BlockManager.add('advanced-video-testimonials', {
        category: 'advanced-testimonials',
        label: 'Testimonios Video',
        content: `<div class="video-testimonials">
        <style>
        .video-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            cursor: pointer;
        }
        .video-card img {
            transition: transform 0.3s;
        }
        .video-card:hover img {
            transform: scale(1.05);
        }
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            transition: all 0.3s;
        }
        .play-button:hover {
            background: rgba(0,0,0,0.9);
            width: 70px;
            height: 70px;
        }
        </style>
        <div class="row g-4">
        <div class="col-md-6">
        <div class="video-card">
        <img src="https://via.placeholder.com/600x400/667eea/white?text=Video+Testimonial+1" class="img-fluid w-100" alt="Video testimonio">
        <div class="play-button">
        <i class="fas fa-play"></i>
        </div>
        <div class="p-3 bg-white">
        <h6>Testimonio de Cliente Satisfecho</h6>
        <small class="text-muted">Juan Pérez - 2:30 min</small>
        </div>
        </div>
        </div>
        <div class="col-md-6">
        <div class="video-card">
        <img src="https://via.placeholder.com/600x400/764ba2/white?text=Video+Testimonial+2" class="img-fluid w-100" alt="Video testimonio">
        <div class="play-button">
        <i class="fas fa-play"></i>
        </div>
        <div class="p-3 bg-white">
        <h6>Caso de Éxito Empresarial</h6>
        <small class="text-muted">María García - 3:15 min</small>
        </div>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-video fa-2x"></i>',
        description: 'Testimonios en formato video'
    });

    // ==================== COMPONENTES ADICIONALES AVANZADOS ====================

    // Timeline / Línea de tiempo
    gjsEditor.BlockManager.add('advanced-timeline', {
        category: {
            id: 'advanced-extra',
            label: '🚀 Componentes Avanzados',
            open: true
        },
        label: 'Línea de Tiempo',
        content: `<div class="timeline">
        <style>
        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 30px;
        }
        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .timeline-badge::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 14px;
            width: 2px;
            height: calc(100% + 30px);
            background: #dee2e6;
        }
        .timeline-item:last-child .timeline-badge::before {
            display: none;
        }
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        </style>
        <div class="timeline-item">
        <div class="timeline-badge"><i class="fas fa-check"></i></div>
        <div class="timeline-content">
        <h5>2024 - Lanzamiento</h5>
        <p>Inicio del proyecto y primera versión</p>
        </div>
        </div>
        <div class="timeline-item">
        <div class="timeline-badge"><i class="fas fa-chart-line"></i></div>
        <div class="timeline-content">
        <h5>2025 - Crecimiento</h5>
        <p>Expansión a nuevos mercados</p>
        </div>
        </div>
        <div class="timeline-item">
        <div class="timeline-badge"><i class="fas fa-trophy"></i></div>
        <div class="timeline-content">
        <h5>2026 - Consolidación</h5>
        <p>Líderes en el sector</p>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-timeline fa-2x"></i>',
        description: 'Línea de tiempo para mostrar hitos importantes'
    });

    // Contador animado
    gjsEditor.BlockManager.add('advanced-counter-animated', {
        category: 'advanced-extra',
        label: 'Contador Animado',
        content: `<div class="counter-section">
        <style>
        .counter-card {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .counter-card:hover {
            transform: translateY(-10px);
        }
        .counter-number {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }
        </style>
        <div class="row g-4">
        <div class="col-md-3">
        <div class="counter-card">
        <i class="fas fa-users fa-3x"></i>
        <div class="counter-number">1,234+</div>
        <h6>Clientes Satisfechos</h6>
        </div>
        </div>
        <div class="col-md-3">
        <div class="counter-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <i class="fas fa-code-branch fa-3x"></i>
        <div class="counter-number">567+</div>
        <h6>Proyectos Completados</h6>
        </div>
        </div>
        <div class="col-md-3">
        <div class="counter-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
        <i class="fas fa-trophy fa-3x"></i>
        <div class="counter-number">45+</div>
        <h6>Premios Ganados</h6>
        </div>
        </div>
        <div class="col-md-3">
        <div class="counter-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
        <i class="fas fa-globe fa-3x"></i>
        <div class="counter-number">89+</div>
        <h6>Países Alcanzados</h6>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-chart-simple fa-2x"></i>',
        description: 'Contadores animados para estadísticas'
    });

    // FAQ Acordeón avanzado
    gjsEditor.BlockManager.add('advanced-faq-accordion', {
        category: 'advanced-extra',
        label: 'FAQ Acordeón',
        content: `<div class="faq-section">
        <div class="accordion" id="faqAccordion">
        <div class="accordion-item">
        <h2 class="accordion-header">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
        ¿Cómo puedo comenzar?
        </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
        Puedes comenzar registrándote en nuestra plataforma y seleccionando el plan que mejor se adapte a tus necesidades.
        </div>
        </div>
        </div>
        <div class="accordion-item">
        <h2 class="accordion-header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
        ¿Cuál es el tiempo de entrega?
        </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
        El tiempo de entrega varía según el proyecto, generalmente entre 2-4 semanas para proyectos estándar.
        </div>
        </div>
        </div>
        <div class="accordion-item">
        <h2 class="accordion-header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
        ¿Ofrecen soporte técnico?
        </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
        Sí, ofrecemos soporte 24/7 a través de chat, email y teléfono para todos nuestros clientes.
        </div>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-question-circle fa-2x"></i>',
        description: 'Sección de preguntas frecuentes'
    });

    // Team grid / Equipo
    gjsEditor.BlockManager.add('advanced-team-grid', {
        category: 'advanced-extra',
        label: 'Grid de Equipo',
        content: `<div class="team-section">
        <style>
        .team-card {
            text-align: center;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .team-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .team-info {
            padding: 20px;
        }
        .team-social {
            margin-top: 15px;
        }
        .team-social a {
            color: #666;
            margin: 0 8px;
            font-size: 18px;
            transition: color 0.3s;
        }
        .team-social a:hover {
            color: #007bff;
        }
        </style>
        <div class="row g-4">
        <div class="col-md-4">
        <div class="team-card">
        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="team-img" alt="Miembro">
        <div class="team-info">
        <h5>Juan Pérez</h5>
        <p class="text-muted">CEO & Fundador</p>
        <div class="team-social">
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-github"></i></a>
        </div>
        </div>
        </div>
        </div>
        <div class="col-md-4">
        <div class="team-card">
        <img src="https://randomuser.me/api/portraits/women/68.jpg" class="team-img" alt="Miembro">
        <div class="team-info">
        <h5>María García</h5>
        <p class="text-muted">Directora Creativa</p>
        <div class="team-social">
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-behance"></i></a>
        <a href="#"><i class="fab fa-dribbble"></i></a>
        </div>
        </div>
        </div>
        </div>
        <div class="col-md-4">
        <div class="team-card">
        <img src="https://randomuser.me/api/portraits/men/75.jpg" class="team-img" alt="Miembro">
        <div class="team-info">
        <h5>Carlos López</h5>
        <p class="text-muted">Lead Developer</p>
        <div class="team-social">
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-github"></i></a>
        <a href="#"><i class="fab fa-stack-overflow"></i></a>
        </div>
        </div>
        </div>
        </div>
        </div>
        </div>`,
        media: '<i class="fas fa-user-friends fa-2x"></i>',
        description: 'Presentación del equipo con redes sociales'
    });

    // Newsletter / Suscripción
    gjsEditor.BlockManager.add('advanced-newsletter', {
        category: 'advanced-extra',
        label: 'Newsletter',
        content: `<div class="newsletter-section">
        <style>
        .newsletter-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            color: white;
        }
        .newsletter-input-group {
            max-width: 500px;
            margin: 30px auto 0;
            display: flex;
            gap: 10px;
        }
        .newsletter-input-group input {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
        }
        .newsletter-input-group button {
            padding: 12px 30px;
            border-radius: 30px;
            background: #ff6b6b;
            color: white;
            border: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        .newsletter-input-group button:hover {
            background: #ff5252;
        }
        </style>
        <div class="newsletter-box">
        <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
        <h3>Suscríbete a nuestro Newsletter</h3>
        <p>Recibe las últimas noticias y ofertas especiales</p>
        <div class="newsletter-input-group">
        <input type="email" placeholder="Tu correo electrónico">
        <button>Suscribirme</button>
        </div>
        <small class="d-block mt-3 opacity-75">No spam, solo contenido valioso</small>
        </div>
        </div>`,
        media: '<i class="fas fa-mail-bulk fa-2x"></i>',
        description: 'Formulario de suscripción a newsletter'
    });

    console.log('✅ Componentes avanzados agregados exitosamente');
    console.log('  - Galerías Lightbox (3 tipos)');
    console.log('  - Mapas embebidos (3 tipos)');
    console.log('  - Tablas responsivas (3 tipos)');
    console.log('  - Testimonios Carousel (3 tipos)');
    console.log('  - Componentes avanzados (5 tipos)');

