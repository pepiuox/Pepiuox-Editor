// Componentes y bloques de Bootstrap 5 para GrapesJS
// Este archivo debe ser incluido después de la inicialización del editor

    // ==================== LAYOUTS Y ESTRUCTURAS ====================

    // Contenedor básico
    gjsEditor.BlockManager.add('bootstrap-container', {
        category: {
            id: 'bootstrap-layouts',
            label: '📐 Layouts Bootstrap',
            open: true
        },
        label: 'Container',
        content: '<div class="container" style="padding: 20px; border: 1px dashed #ddd;"><p>Contenido del container</p></div>',
        media: '<i class="fas fa-square fa-2x"></i>',
        description: 'Contenedor fluido responsive de Bootstrap'
    });

    // Container fluido
    gjsEditor.BlockManager.add('bootstrap-container-fluid', {
        category: 'bootstrap-layouts',
        label: 'Container Fluid',
        content: '<div class="container-fluid" style="padding: 20px; border: 1px dashed #ddd;"><p>Contenedor fluido (100% ancho)</p></div>',
                            media: '<i class="fas fa-expand fa-2x"></i>',
                            description: 'Contenedor de ancho completo'
    });

    // Fila (Row)
    gjsEditor.BlockManager.add('bootstrap-row', {
        category: 'bootstrap-layouts',
        label: 'Row (Fila)',
                            content: '<div class="row" style="padding: 10px; border: 1px dashed #007bff;"><div class="col-12" style="background: #f8f9fa; padding: 10px;">Columna</div></div>',
                            media: '<i class="fas fa-grip-horizontal fa-2x"></i>',
                            description: 'Sistema de filas para grid'
    });

    // Grid 2 columnas
    gjsEditor.BlockManager.add('bootstrap-grid-2', {
        category: 'bootstrap-layouts',
        label: 'Grid 2 Columnas',
        content: '<div class="row g-3"><div class="col-md-6" style="background: #e3f2fd; padding: 15px; border-radius: 5px;">Columna 1</div><div class="col-md-6" style="background: #f8f9fa; padding: 15px; border-radius: 5px;">Columna 2</div></div>',
        media: '<i class="fas fa-columns fa-2x"></i>',
        description: 'Grid de 2 columnas responsive'
    });

    // Grid 3 columnas
    gjsEditor.BlockManager.add('bootstrap-grid-3', {
        category: 'bootstrap-layouts',
        label: 'Grid 3 Columnas',
        content: '<div class="row g-3"><div class="col-md-4" style="background: #e3f2fd; padding: 15px; border-radius: 5px;">Col 1</div><div class="col-md-4" style="background: #f8f9fa; padding: 15px; border-radius: 5px;">Col 2</div><div class="col-md-4" style="background: #e3f2fd; padding: 15px; border-radius: 5px;">Col 3</div></div>',
        media: '<i class="fas fa-th fa-2x"></i>',
        description: 'Grid de 3 columnas responsive'
    });

    // Grid 4 columnas
    gjsEditor.BlockManager.add('bootstrap-grid-4', {
        category: 'bootstrap-layouts',
        label: 'Grid 4 Columnas',
        content: '<div class="row g-3"><div class="col-md-3" style="background: #e3f2fd; padding: 10px;">Col 1</div><div class="col-md-3" style="background: #f8f9fa; padding: 10px;">Col 2</div><div class="col-md-3" style="background: #e3f2fd; padding: 10px;">Col 3</div><div class="col-md-3" style="background: #f8f9fa; padding: 10px;">Col 4</div></div>',
        media: '<i class="fas fa-border-all fa-2x"></i>',
        description: 'Grid de 4 columnas responsive'
    });

    // Layout con sidebar
    gjsEditor.BlockManager.add('bootstrap-layout-sidebar', {
        category: 'bootstrap-layouts',
        label: 'Layout con Sidebar',
        content: '<div class="row"><div class="col-md-3" style="background: #f8f9fa; padding: 20px;"><h4>Sidebar</h4><ul><li>Menú 1</li><li>Menú 2</li></ul></div><div class="col-md-9" style="padding: 20px;"><h3>Contenido Principal</h3><p>Aquí va el contenido principal de la página</p></div></div>',
        media: '<i class="fas fa-indent fa-2x"></i>',
        description: 'Layout con barra lateral izquierda'
    });

    // ==================== COMPONENTES DE NAVEGACIÓN ====================

    // Navbar básica
    gjsEditor.BlockManager.add('bootstrap-navbar', {
        category: {
            id: 'bootstrap-navigation',
            label: '🧭 Navegación Bootstrap',
            open: true
        },
        label: 'Navbar Básica',
        content: '<nav class="navbar navbar-expand-lg navbar-light bg-light"><div class="container"><a class="navbar-brand" href="#">Logo</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li><li class="nav-item"><a class="nav-link" href="#">Productos</a></li><li class="nav-item"><a class="nav-link" href="#">Servicios</a></li><li class="nav-item"><a class="nav-link" href="#">Contacto</a></li></ul></div></div></nav>',
        media: '<i class="fas fa-bars fa-2x"></i>',
        description: 'Barra de navegación responsive'
    });

    // Navbar oscura
    gjsEditor.BlockManager.add('bootstrap-navbar-dark', {
        category: 'bootstrap-navigation',
        label: 'Navbar Oscura',
        content: '<nav class="navbar navbar-expand-lg navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="#">Mi Sitio</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDark"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarDark"><ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="#">Inicio</a></li><li class="nav-item"><a class="nav-link" href="#">Acerca de</a></li><li class="nav-item"><a class="nav-link" href="#">Servicios</a></li><li class="nav-item"><a class="nav-link" href="#">Contacto</a></li></ul></div></div></nav>',
        media: '<i class="fas fa-moon fa-2x"></i>',
        description: 'Barra de navegación con tema oscuro'
    });

    // Menú dropdown
    gjsEditor.BlockManager.add('bootstrap-dropdown', {
        category: 'bootstrap-navigation',
        label: 'Dropdown Menu',
        content: '<div class="dropdown"><button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Menú Desplegable</button><ul class="dropdown-menu"><li><a class="dropdown-item" href="#">Opción 1</a></li><li><a class="dropdown-item" href="#">Opción 2</a></li><li><a class="dropdown-item" href="#">Opción 3</a></li></ul></div>',
        media: '<i class="fas fa-caret-down fa-2x"></i>',
        description: 'Menú desplegable'
    });

    // Breadcrumb
    gjsEditor.BlockManager.add('bootstrap-breadcrumb', {
        category: 'bootstrap-navigation',
        label: 'Breadcrumb',
        content: '<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Inicio</a></li><li class="breadcrumb-item"><a href="#">Categoría</a></li><li class="breadcrumb-item active" aria-current="page">Página Actual</li></ol></nav>',
        media: '<i class="fas fa-link fa-2x"></i>',
        description: 'Migas de pan para navegación'
    });

    // Paginación
    gjsEditor.BlockManager.add('bootstrap-pagination', {
        category: 'bootstrap-navigation',
        label: 'Paginación',
        content: '<nav aria-label="Page navigation"><ul class="pagination justify-content-center"><li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li><li class="page-item active"><a class="page-link" href="#">1</a></li><li class="page-item"><a class="page-link" href="#">2</a></li><li class="page-item"><a class="page-link" href="#">3</a></li><li class="page-item"><a class="page-link" href="#">Siguiente</a></li></ul></nav>',
        media: '<i class="fas fa-ellipsis-h fa-2x"></i>',
        description: 'Paginación para listados'
    });

    // ==================== BOTONES Y LINKS ====================

    // Botones variados
    gjsEditor.BlockManager.add('bootstrap-buttons-group', {
        category: {
            id: 'bootstrap-buttons',
            label: '🔘 Botones Bootstrap',
            open: true
        },
        label: 'Grupo Botones',
        content: '<div class="d-flex gap-2 flex-wrap"><button class="btn btn-primary">Primario</button><button class="btn btn-secondary">Secundario</button><button class="btn btn-success">Éxito</button><button class="btn btn-danger">Peligro</button><button class="btn btn-warning">Advertencia</button><button class="btn btn-info">Info</button></div>',
        media: '<i class="fas fa-hand-pointer fa-2x"></i>',
        description: 'Variedad de botones con diferentes estilos'
    });

    // Botón grande
    gjsEditor.BlockManager.add('bootstrap-btn-lg', {
        category: 'bootstrap-buttons',
        label: 'Botón Grande',
        content: '<button class="btn btn-primary btn-lg" style="padding: 12px 30px; font-size: 18px;">Botón Grande</button>',
        media: '<i class="fas fa-arrow-up fa-2x"></i>',
        description: 'Botón de tamaño grande'
    });

    // Botón pequeño
    gjsEditor.BlockManager.add('bootstrap-btn-sm', {
        category: 'bootstrap-buttons',
        label: 'Botón Pequeño',
        content: '<button class="btn btn-info btn-sm" style="padding: 5px 15px; font-size: 12px;">Botón Pequeño</button>',
        media: '<i class="fas fa-arrow-down fa-2x"></i>',
        description: 'Botón de tamaño pequeño'
    });

    // Botón outline
    gjsEditor.BlockManager.add('bootstrap-btn-outline', {
        category: 'bootstrap-buttons',
        label: 'Botón Outline',
        content: '<button class="btn btn-outline-primary">Botón Outline</button>',
        media: '<i class="fas fa-border-all fa-2x"></i>',
        description: 'Botón con estilo outline'
    });

    // Grupo de botones
    gjsEditor.BlockManager.add('bootstrap-btn-group', {
        category: 'bootstrap-buttons',
        label: 'Grupo Botones',
        content: '<div class="btn-group" role="group"><button class="btn btn-outline-secondary">Izquierda</button><button class="btn btn-outline-secondary">Centro</button><button class="btn btn-outline-secondary">Derecha</button></div>',
        media: '<i class="fas fa-object-group fa-2x"></i>',
        description: 'Grupo de botones agrupados'
    });

    // ==================== TARJETAS (CARDS) ====================

    // Card básica
    gjsEditor.BlockManager.add('bootstrap-card-basic', {
        category: {
            id: 'bootstrap-cards',
            label: '🃏 Tarjetas Bootstrap',
            open: true
        },
        label: 'Card Básica',
        content: '<div class="card" style="width: 100%; max-width: 350px;"><img src="https://via.placeholder.com/350x200" class="card-img-top" alt="Imagen"><div class="card-body"><h5 class="card-title">Título de la Tarjeta</h5><p class="card-text">Este es un texto de ejemplo para la tarjeta. Puedes agregar cualquier contenido aquí.</p><a href="#" class="btn btn-primary">Ver más</a></div></div>',
        media: '<i class="fas fa-id-card fa-2x"></i>',
        description: 'Tarjeta con imagen, título y botón'
    });

    // Card con header y footer
    gjsEditor.BlockManager.add('bootstrap-card-header-footer', {
        category: 'bootstrap-cards',
        label: 'Card con Header/Footer',
        content: '<div class="card"><div class="card-header"><strong>Encabezado</strong></div><div class="card-body"><h5 class="card-title">Título especial</h5><p class="card-text">Contenido principal de la tarjeta</p></div><div class="card-footer text-muted">Pie de página</div></div>',
        media: '<i class="fas fa-arrows-alt-v fa-2x"></i>',
        description: 'Tarjeta con encabezado y pie de página'
    });

    // Card horizontal
    gjsEditor.BlockManager.add('bootstrap-card-horizontal', {
        category: 'bootstrap-cards',
        label: 'Card Horizontal',
        content: '<div class="card mb-3" style="max-width: 540px;"><div class="row g-0"><div class="col-md-4"><img src="https://via.placeholder.com/180x180" class="img-fluid rounded-start" alt="..."></div><div class="col-md-8"><div class="card-body"><h5 class="card-title">Tarjeta Horizontal</h5><p class="card-text">Esta es una tarjeta más ancha con texto de apoyo para un contenido adicional.</p><p class="card-text"><small class="text-muted">Última actualización hace 3 mins</small></p></div></div></div></div>',
        media: '<i class="fas fa-arrows-alt-h fa-2x"></i>',
        description: 'Tarjeta con diseño horizontal'
    });

    // Card deck
    gjsEditor.BlockManager.add('bootstrap-card-deck', {
        category: 'bootstrap-cards',
        label: 'Deck de Cards (3)',
                            content: '<div class="row row-cols-1 row-cols-md-3 g-4"><div class="col"><div class="card h-100"><img src="https://via.placeholder.com/300x150" class="card-img-top"><div class="card-body"><h5 class="card-title">Card 1</h5><p class="card-text">Contenido de la primera tarjeta</p></div></div></div><div class="col"><div class="card h-100"><img src="https://via.placeholder.com/300x150" class="card-img-top"><div class="card-body"><h5 class="card-title">Card 2</h5><p class="card-text">Contenido de la segunda tarjeta</p></div></div></div><div class="col"><div class="card h-100"><img src="https://via.placeholder.com/300x150" class="card-img-top"><div class="card-body"><h5 class="card-title">Card 3</h5><p class="card-text">Contenido de la tercera tarjeta</p></div></div></div></div>',
                            media: '<i class="fas fa-th-large fa-2x"></i>',
                            description: 'Conjunto de 3 tarjetas responsivas'
    });

    // ==================== FORMULARIOS ====================

    // Formulario básico
    gjsEditor.BlockManager.add('bootstrap-form-basic', {
        category: {
            id: 'bootstrap-forms',
            label: '📝 Formularios Bootstrap',
            open: true
        },
        label: 'Formulario Básico',
        content: '<form><div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" placeholder="nombre@ejemplo.com"></div><div class="mb-3"><label for="password" class="form-label">Contraseña</label><input type="password" class="form-control" id="password"></div><div class="mb-3 form-check"><input type="checkbox" class="form-check-input" id="check"><label class="form-check-label" for="check">Recordarme</label></div><button type="submit" class="btn btn-primary">Enviar</button></form>',
        media: '<i class="fas fa-envelope fa-2x"></i>',
        description: 'Formulario de contacto básico'
    });

    // Formulario horizontal
    gjsEditor.BlockManager.add('bootstrap-form-horizontal', {
        category: 'bootstrap-forms',
        label: 'Formulario Horizontal',
        content: '<form><div class="row mb-3"><label class="col-sm-2 col-form-label">Email</label><div class="col-sm-10"><input type="email" class="form-control"></div></div><div class="row mb-3"><label class="col-sm-2 col-form-label">Contraseña</label><div class="col-sm-10"><input type="password" class="form-control"></div></div><button type="submit" class="btn btn-primary">Enviar</button></form>',
        media: '<i class="fas fa-rows fa-2x"></i>',
        description: 'Formulario con diseño horizontal'
    });

    // Input group
    gjsEditor.BlockManager.add('bootstrap-input-group', {
        category: 'bootstrap-forms',
        label: 'Input Group',
        content: '<div class="input-group mb-3"><span class="input-group-text">@</span><input type="text" class="form-control" placeholder="Username"></div><div class="input-group mb-3"><input type="text" class="form-control" placeholder="Cantidad"><span class="input-group-text">USD</span></div>',
        media: '<i class="fas fa-dollar-sign fa-2x"></i>',
        description: 'Grupos de input con texto adicional'
    });

    // Select dropdown
    gjsEditor.BlockManager.add('bootstrap-select', {
        category: 'bootstrap-forms',
        label: 'Select Desplegable',
        content: '<select class="form-select"><option selected>Selecciona una opción</option><option value="1">Opción 1</option><option value="2">Opción 2</option><option value="3">Opción 3</option></select>',
        media: '<i class="fas fa-caret-square-down fa-2x"></i>',
        description: 'Menú desplegable de selección'
    });

    // Radio y Checkbox group
    gjsEditor.BlockManager.add('bootstrap-radio-check', {
        category: 'bootstrap-forms',
        label: 'Radio & Checkbox',
        content: '<div class="mb-3"><div class="form-check"><input class="form-check-input" type="radio" name="radio"><label class="form-check-label">Opción 1</label></div><div class="form-check"><input class="form-check-input" type="radio" name="radio"><label class="form-check-label">Opción 2</label></div></div><div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label">Acepto términos</label></div><div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label">Recibir notificaciones</label></div></div>',
        media: '<i class="fas fa-check-square fa-2x"></i>',
        description: 'Grupos de radio buttons y checkboxes'
    });

    // Range slider
    gjsEditor.BlockManager.add('bootstrap-range', {
        category: 'bootstrap-forms',
        label: 'Range Slider',
        content: '<label class="form-label">Slider de rango</label><input type="range" class="form-range" min="0" max="100" step="1" value="50">',
        media: '<i class="fas fa-sliders-h fa-2x"></i>',
        description: 'Control deslizante para seleccionar rangos'
    });

    // ==================== COMPONENTES DE CONTENIDO ====================

    // Jumbotron / Hero section
    gjsEditor.BlockManager.add('bootstrap-jumbotron', {
        category: {
            id: 'bootstrap-content',
            label: '📄 Contenido Bootstrap',
            open: true
        },
        label: 'Hero Section',
        content: '<div class="bg-light p-5 rounded-3 text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;"><h1 class="display-4">¡Bienvenido!</h1><p class="lead">Esta es una sección destacada para llamar la atención del usuario.</p><hr class="my-4"><p>Información adicional sobre tu producto o servicio.</p><button class="btn btn-light btn-lg">Comenzar</button></div>',
                            media: '<i class="fas fa-star fa-2x"></i>',
                            description: 'Sección principal destacada (Hero)'
    });

    // Alertas
    gjsEditor.BlockManager.add('bootstrap-alerts', {
        category: 'bootstrap-content',
        label: 'Alertas',
        content: '<div class="alert alert-success" role="alert"><strong>Éxito!</strong> Operación completada correctamente.</div><div class="alert alert-danger" role="alert"><strong>Error!</strong> Ha ocurrido un problema.</div><div class="alert alert-warning" role="alert"><strong>Advertencia!</strong> Revisa tus datos.</div><div class="alert alert-info" role="alert"><strong>Info!</strong> Mensaje informativo.</div>',
        media: '<i class="fas fa-exclamation-triangle fa-2x"></i>',
        description: 'Mensajes de alerta con diferentes estilos'
    });

    // Alert con botón cerrar
    gjsEditor.BlockManager.add('bootstrap-alert-dismissible', {
        category: 'bootstrap-content',
        label: 'Alert Cerrar',
        content: '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>¡Importante!</strong> Este mensaje se puede cerrar.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
        media: '<i class="fas fa-times-circle fa-2x"></i>',
        description: 'Alerta con botón para cerrar'
    });

    // Badges
    gjsEditor.BlockManager.add('bootstrap-badges', {
        category: 'bootstrap-content',
        label: 'Badges',
        content: '<div><h1>Ejemplo de título <span class="badge bg-primary">Nuevo</span></h1><button class="btn btn-primary">Notificaciones <span class="badge bg-light text-dark">4</span></button><p><span class="badge bg-secondary">Secundario</span> <span class="badge bg-success">Éxito</span> <span class="badge bg-danger">Peligro</span> <span class="badge bg-warning">Advertencia</span> <span class="badge bg-info">Info</span> <span class="badge bg-dark">Oscuro</span></p></div>',
        media: '<i class="fas fa-certificate fa-2x"></i>',
        description: 'Insignias y contadores'
    });

    // Progress bars
    gjsEditor.BlockManager.add('bootstrap-progress', {
        category: 'bootstrap-content',
        label: 'Barras Progreso',
        content: '<div><div class="progress mb-3"><div class="progress-bar bg-success" style="width: 25%">25%</div></div><div class="progress mb-3"><div class="progress-bar bg-info" style="width: 50%">50%</div></div><div class="progress mb-3"><div class="progress-bar bg-warning" style="width: 75%">75%</div></div><div class="progress"><div class="progress-bar bg-danger" style="width: 100%">100%</div></div></div>',
        media: '<i class="fas fa-chart-line fa-2x"></i>',
        description: 'Barras de progreso personalizables'
    });

    // List group
    gjsEditor.BlockManager.add('bootstrap-list-group', {
        category: 'bootstrap-content',
        label: 'Lista de Grupos',
        content: '<ul class="list-group"><li class="list-group-item active">Elemento activo</li><li class="list-group-item">Elemento 2</li><li class="list-group-item disabled">Elemento deshabilitado</li><li class="list-group-item"><a href="#">Elemento con enlace</a></li></ul>',
        media: '<i class="fas fa-list-ul fa-2x"></i>',
        description: 'Listas agrupadas con estilos'
    });

    // Accordion
    gjsEditor.BlockManager.add('bootstrap-accordion', {
        category: 'bootstrap-content',
        label: 'Acordeón',
        content: '<div class="accordion" id="accordionExample"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">Sección 1</button></h2><div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#accordionExample"><div class="accordion-body">Contenido de la sección 1</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">Sección 2</button></h2><div id="collapse2" class="accordion-collapse collapse"><div class="accordion-body">Contenido de la sección 2</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">Sección 3</button></h2><div id="collapse3" class="accordion-collapse collapse"><div class="accordion-body">Contenido de la sección 3</div></div></div></div>',
        media: '<i class="fas fa-layer-group fa-2x"></i>',
        description: 'Componente acordeón colapsable'
    });

    // Tabs
    gjsEditor.BlockManager.add('bootstrap-tabs', {
        category: 'bootstrap-content',
        label: 'Pestañas (Tabs)',
                            content: '<ul class="nav nav-tabs" id="myTab" role="tablist"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">Pestaña 1</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">Pestaña 2</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">Pestaña 3</button></li></ul><div class="tab-content mt-3"><div class="tab-pane fade show active" id="tab1">Contenido de la primera pestaña</div><div class="tab-pane fade" id="tab2">Contenido de la segunda pestaña</div><div class="tab-pane fade" id="tab3">Contenido de la tercera pestaña</div></div>',
                            media: '<i class="fas fa-table-tabs fa-2x"></i>',
                            description: 'Sistema de pestañas navegables'
    });

    // Modal trigger
    gjsEditor.BlockManager.add('bootstrap-modal-trigger', {
        category: 'bootstrap-content',
        label: 'Modal (Ventana)',
                            content: '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Abrir Modal</button><div class="modal fade" id="exampleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Título del Modal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Contenido del modal aquí...</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary">Guardar</button></div></div></div></div>',
                            media: '<i class="fas fa-window-maximize fa-2x"></i>',
                            description: 'Botón que abre una ventana modal'
    });

    // Tooltip
    gjsEditor.BlockManager.add('bootstrap-tooltip', {
        category: 'bootstrap-content',
        label: 'Tooltip',
        content: '<button type="button" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Este es un tooltip">Tooltip</button>',
        media: '<i class="fas fa-comment-dots fa-2x"></i>',
        description: 'Tooltips informativos'
    });

    // ==================== TABLAS ====================

    // Tabla básica
    gjsEditor.BlockManager.add('bootstrap-table', {
        category: {
            id: 'bootstrap-tables',
            label: '📊 Tablas Bootstrap',
            open: true
        },
        label: 'Tabla Básica',
        content: '<table class="table"><thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th></tr></thead><tbody><tr><td>1</td><td>Juan Pérez</td><td>juan@email.com</td><td>Admin</td></tr><tr><td>2</td><td>María García</td><td>maria@email.com</td><td>Usuario</td></tr><tr><td>3</td><td>Carlos López</td><td>carlos@email.com</td><td>Editor</td></tr></tbody></table>',
        media: '<i class="fas fa-table fa-2x"></i>',
        description: 'Tabla básica con estilos Bootstrap'
    });

    // Tabla con estilos
    gjsEditor.BlockManager.add('bootstrap-table-striped', {
        category: 'bootstrap-tables',
        label: 'Tabla Estilos',
        content: '<table class="table table-striped table-hover"><thead class="table-dark"><tr><th>#</th><th>Producto</th><th>Precio</th><th>Stock</th></tr></thead><tbody><tr><td>1</td><td>Producto A</td><td>$100</td><td><span class="badge bg-success">Disponible</span></td></tr><tr><td>2</td><td>Producto B</td><td>$150</td><td><span class="badge bg-warning">Bajo stock</span></td></tr><tr><td>3</td><td>Producto C</td><td>$200</td><td><span class="badge bg-danger">Agotado</span></td></tr></tbody></table>',
        media: '<i class="fas fa-table-list fa-2x"></i>',
        description: 'Tabla con rayas, hover y cabecera oscura'
    });

    // ==================== MEDIA Y MULTIMEDIA ====================

    // Imagen responsive
    gjsEditor.BlockManager.add('bootstrap-img-fluid', {
        category: {
            id: 'bootstrap-media',
            label: '🎬 Multimedia Bootstrap',
            open: true
        },
        label: 'Imagen Responsive',
        content: '<img src="https://via.placeholder.com/800x400" class="img-fluid rounded" alt="Imagen responsive">',
        media: '<i class="fas fa-image fa-2x"></i>',
        description: 'Imagen que se adapta al contenedor'
    });

    // Thumbnail
    gjsEditor.BlockManager.add('bootstrap-img-thumbnail', {
        category: 'bootstrap-media',
        label: 'Thumbnail',
        content: '<img src="https://via.placeholder.com/200x200" class="img-thumbnail" alt="Thumbnail">',
        media: '<i class="fas fa-thumbtack fa-2x"></i>',
        description: 'Imagen con borde de miniatura'
    });

    // Figure con caption
    gjsEditor.BlockManager.add('bootstrap-figure', {
        category: 'bootstrap-media',
        label: 'Figura con caption',
        content: '<figure class="figure"><img src="https://via.placeholder.com/400x300" class="figure-img img-fluid rounded" alt="..."><figcaption class="figure-caption">Pie de foto para la imagen</figcaption></figure>',
        media: '<i class="fas fa-image fa-2x"></i>',
        description: 'Imagen con texto descriptivo'
    });

    // Video responsive
    gjsEditor.BlockManager.add('bootstrap-responsive-video', {
        category: 'bootstrap-media',
        label: 'Video Responsive',
        content: '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video" allowfullscreen></iframe></div>',
        media: '<i class="fas fa-video fa-2x"></i>',
        description: 'Video con aspecto responsive 16:9'
    });

    // Carrusel de imágenes
    gjsEditor.BlockManager.add('bootstrap-carousel', {
        category: 'bootstrap-media',
        label: 'Carrusel',
        content: '<div id="carouselExample" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner"><div class="carousel-item active"><img src="https://via.placeholder.com/800x400/007bff/white?text=Slide+1" class="d-block w-100" alt="Slide 1"></div><div class="carousel-item"><img src="https://via.placeholder.com/800x400/28a745/white?text=Slide+2" class="d-block w-100" alt="Slide 2"></div><div class="carousel-item"><img src="https://via.placeholder.com/800x400/dc3545/white?text=Slide+3" class="d-block w-100" alt="Slide 3"></div></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button></div>',
        media: '<i class="fas fa-images fa-2x"></i>',
        description: 'Carrusel de imágenes interactivo'
    });

    // ==================== SECCIONES ESPECIALES ====================

    // Footer
    gjsEditor.BlockManager.add('bootstrap-footer', {
        category: {
            id: 'bootstrap-special',
            label: '✨ Secciones Especiales',
            open: true
        },
        label: 'Footer Simple',
        content: '<footer class="bg-dark text-white mt-5 py-4"><div class="container"><div class="row"><div class="col-md-6"><h5>Mi Sitio Web</h5><p>Descripción de la empresa o sitio web.</p></div><div class="col-md-3"><h5>Enlaces</h5><ul class="nav flex-column"><li class="nav-item"><a href="#" class="nav-link text-white p-0">Inicio</a></li><li class="nav-item"><a href="#" class="nav-link text-white p-0">Acerca de</a></li><li class="nav-item"><a href="#" class="nav-link text-white p-0">Servicios</a></li></ul></div><div class="col-md-3"><h5>Contacto</h5><p>Email: info@misitio.com<br>Tel: (123) 456-7890</p></div></div><hr><div class="text-center"><small>&copy; 2024 Mi Sitio Web. Todos los derechos reservados.</small></div></div></footer>',
                            media: '<i class="fas fa-arrow-down fa-2x"></i>',
                            description: 'Pie de página completo'
    });

    // Featured section con íconos
    gjsEditor.BlockManager.add('bootstrap-features', {
        category: 'bootstrap-special',
        label: 'Sección Características',
        content: '<div class="container py-5"><div class="row text-center"><div class="col-md-4 mb-4"><div class="display-4 text-primary"><i class="fas fa-rocket"></i></div><h3>Rápido</h3><p>Optimizado para velocidad y rendimiento</p></div><div class="col-md-4 mb-4"><div class="display-4 text-success"><i class="fas fa-shield-alt"></i></div><h3>Seguro</h3><p>Protección de datos garantizada</p></div><div class="col-md-4 mb-4"><div class="display-4 text-info"><i class="fas fa-headset"></i></div><h3>Soporte 24/7</h3><p>Atención al cliente permanente</p></div></div></div>',
        media: '<i class="fas fa-star-of-life fa-2x"></i>',
        description: 'Sección de características con íconos'
    });

    // Pricing table
    gjsEditor.BlockManager.add('bootstrap-pricing', {
        category: 'bootstrap-special',
        label: 'Tabla de Precios',
        content: '<div class="container py-5"><div class="row"><div class="col-md-4 mb-4"><div class="card h-100 text-center"><div class="card-header"><h3>Básico</h3></div><div class="card-body"><h2 class="card-title">$9<small class="text-muted">/mes</small></h2><ul class="list-unstyled mt-3 mb-4"><li>10 usuarios</li><li>5GB almacenamiento</li><li>Soporte básico</li></ul><button class="btn btn-outline-primary w-100">Comprar</button></div></div></div><div class="col-md-4 mb-4"><div class="card h-100 text-center border-primary"><div class="card-header"><h3>Pro</h3></div><div class="card-body"><h2 class="card-title">$29<small class="text-muted">/mes</small></h2><ul class="list-unstyled mt-3 mb-4"><li>Usuarios ilimitados</li><li>50GB almacenamiento</li><li>Soporte prioritario</li></ul><button class="btn btn-primary w-100">Comprar</button></div></div></div><div class="col-md-4 mb-4"><div class="card h-100 text-center"><div class="card-header"><h3>Empresa</h3></div><div class="card-body"><h2 class="card-title">$59<small class="text-muted">/mes</small></h2><ul class="list-unstyled mt-3 mb-4"><li>Usuarios ilimitados</li><li>200GB almacenamiento</li><li>Soporte dedicado</li></ul><button class="btn btn-outline-primary w-100">Contactar</button></div></div></div></div></div>',
        media: '<i class="fas fa-dollar-sign fa-2x"></i>',
        description: 'Tabla comparativa de precios'
    });

    // Testimonios
    gjsEditor.BlockManager.add('bootstrap-testimonials', {
        category: 'bootstrap-special',
        label: 'Testimonios',
        content: '<div class="container py-5"><div class="row"><div class="col-md-4"><div class="card h-100"><div class="card-body"><i class="fas fa-quote-left fa-2x text-muted mb-3"></i><p class="card-text">Excelente servicio, muy recomendado. La atención al cliente es increíble.</p><div class="mt-3"><strong>Juan Pérez</strong><br><small class="text-muted">CEO Empresa X</small></div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><i class="fas fa-quote-left fa-2x text-muted mb-3"></i><p class="card-text">La mejor plataforma que he utilizado. Funcionalidades increíbles y soporte rápido.</p><div class="mt-3"><strong>María García</strong><br><small class="text-muted">Directora Creativa</small></div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><i class="fas fa-quote-left fa-2x text-muted mb-3"></i><p class="card-text">Producto fantástico. Mi negocio ha crecido gracias a esta herramienta.</p><div class="mt-3"><strong>Carlos López</strong><br><small class="text-muted">Fundador</small></div></div></div></div></div></div>',
        media: '<i class="fas fa-comments fa-2x"></i>',
        description: 'Sección de testimonios de clientes'
    });

    // Contact section
    gjsEditor.BlockManager.add('bootstrap-contact-section', {
        category: 'bootstrap-special',
        label: 'Sección Contacto',
        content: '<div class="container py-5"><div class="row"><div class="col-md-6"><h2>Contáctanos</h2><form><div class="mb-3"><input type="text" class="form-control" placeholder="Nombre"></div><div class="mb-3"><input type="email" class="form-control" placeholder="Email"></div><div class="mb-3"><textarea class="form-control" rows="5" placeholder="Mensaje"></textarea></div><button class="btn btn-primary">Enviar</button></form></div><div class="col-md-6"><h2>Información</h2><p><i class="fas fa-map-marker-alt me-2"></i> Dirección: Calle Principal 123</p><p><i class="fas fa-phone me-2"></i> Teléfono: (123) 456-7890</p><p><i class="fas fa-envelope me-2"></i> Email: contacto@misitio.com</p><div class="mt-4"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316bb6de37%3A0xc8892517b33c1f09!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1641234567890!5m2!1sen!2sus" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div></div></div></div>',
                            media: '<i class="fas fa-address-card fa-2x"></i>',
                            description: 'Sección completa de contacto con mapa'
    });

    console.log('✅ 50+ bloques de Bootstrap agregados exitosamente');

