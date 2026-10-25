gjsEditor.BlockManager.add('section',{
    category: {
        id: 'basic',
        label: 'Basic',
        open: true
    },
    label: '<i class="fas fa-layer-group"></i> Sección',
    content: '<section style="padding: 50px 20px;"><div class="container"><h2>Nueva Sección</h2><p>Contenido de la sección</p></div></section>',
    media: '<i class="fas fa-layer-group fa-2x"></i>'
});
gjsEditor.BlockManager.add('heading',{
    category: {
        id: 'basic',
        label: 'Basic',
        open: true
    },
    label: '<i class="fas fa-heading"></i> Título',
    content: '<h1>Título Principal</h1>',
    media: '<i class="fas fa-heading fa-2x"></i>'
});
gjsEditor.BlockManager.add('text',{
    category: {
        id: 'basic',
        label: 'Basic',
        open: true
    },
    label: '<i class="fas fa-paragraph"></i> Texto',
    content: '<div><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p></div>',
    media: '<i class="fas fa-paragraph fa-2x"></i>'
});
gjsEditor.BlockManager.add('image',{
    category: {
        id: 'basic',
        label: 'Basic',
        open: true
    },
    label: '<i class="fas fa-image"></i> Imagen',
    content: '<img src="https://via.placeholder.com/800x400" alt="Imagen" style="max-width:100%; height:auto; border-radius:8px;">',
    media: '<i class="fas fa-image fa-2x"></i>'
});
gjsEditor.BlockManager.add('button',{
    category: {
        id: 'basic',
        label: 'Basic',
        open: true
    },
    label: '<i class="fas fa-hand-pointer"></i> Botón',
    content: '<button class="btn btn-primary" style="padding:10px 30px; border-radius:25px;">Botón</button>',
    media: '<i class="fas fa-hand-pointer fa-2x"></i>'
});
gjsEditor.BlockManager.add('input-group',{
    category: {
        id: 'forms',
        label: 'Forms',
        open: true
    },
    label: '<i class="fas fa-keyboard"></i> Grupo Input',
    content: '<div style="margin-bottom:15px;"><label style="display:block; margin-bottom:5px;">Etiqueta</label><input type="text" placeholder="Ingresa texto" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></div>',
    media: '<i class="fas fa-keyboard fa-2x"></i>'
});
gjsEditor.BlockManager.add('contact-form',{
    category: {
        id: 'forms',
        label: 'Forms',
        open: true
    },
    label: '<i class="fas fa-envelope"></i> Formulario',
    content: '<form style="max-width:500px; margin:0 auto;"><div class="form-group" style="margin-bottom:15px;"><input type="text" placeholder="Nombre" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></div><div class="form-group" style="margin-bottom:15px;"><input type="email" placeholder="Email" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></div><div class="form-group" style="margin-bottom:15px;"><textarea placeholder="Mensaje" rows="4" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea></div><button type="submit" class="btn btn-primary" style="padding:10px 30px; border-radius:25px;">Enviar</button></form>',
    media: '<i class="fas fa-envelope fa-2x"></i>'
});
gjsEditor.BlockManager.add('footer',{
    category: {
        id: 'navs',
        label: 'Navs',
        open: true
    },
    label: '<i class="fas fa-arrow-down"></i> Pie de Página',
    content: '<footer style="background:#2c3e50; color:white; padding:40px 20px; text-align:center;"><p>&copy; 2024 Mi Empresa. Todos los derechos reservados.</p></footer>',
    media: '<i class="fas fa-arrow-down fa-2x"></i>'
});
gjsEditor.BlockManager.add('navbar',{
    category: {
        id: 'navs',
        label: 'Navs',
        open: true
    },
    label: '<i class="fas fa-bars"></i> Barra Navegación',
    content: '<nav style="background:#333; padding:15px; color:white;"><ul style="list-style:none; margin:0; padding:0; display:flex; gap:20px;"><li><a href="#" style="color:white; text-decoration:none;">Inicio</a></li><li><a href="#" style="color:white; text-decoration:none;">Servicios</a></li><li><a href="#" style="color:white; text-decoration:none;">Contacto</a></li></ul></nav>',
    media: '<i class="fas fa-bars fa-2x"></i>'
});
gjsEditor.BlockManager.add('flex-container',{
    category: {
        id: 'layouts',
        label: 'Layouts',
        open: true
    },
    label: '<i class="fas fa-arrows-alt"></i> Flex Container',
    content: '<div style="display:flex; gap:15px; flex-wrap:wrap; justify-content:center;"><div style="background:#007bff; padding:20px; color:white; border-radius:8px;">Item 1</div><div style="background:#28a745; padding:20px; color:white; border-radius:8px;">Item 2</div><div style="background:#ffc107; padding:20px; border-radius:8px;">Item 3</div></div>',
    media: '<i class="fas fa-arrows-alt fa-2x"></i>'
});
gjsEditor.BlockManager.add('row-3col',{
    category: {
        id: 'layouts',
        label: 'Layouts',
        open: true
    },
    label: '<i class="fas fa-th"></i> 3 Columnas',
    content: '<div class="row" style="display:flex; gap:20px;"><div class="col" style="flex:1; padding:20px; background:#f8f9fa;"><h3>Col 1</h3></div><div class="col" style="flex:1; padding:20px; background:#f8f9fa;"><h3>Col 2</h3></div><div class="col" style="flex:1; padding:20px; background:#f8f9fa;"><h3>Col 3</h3></div></div>',
    media: '<i class="fas fa-th fa-2x"></i>'
});
gjsEditor.BlockManager.add('row-2col',{
    category: {
        id: 'layouts',
        label: 'Layouts',
        open: true
    },
    label: '<i class="fas fa-columns"></i> 2 Columnas',
    content: '<div class="row" style="display:flex; gap:20px;"><div class="col" style="flex:1; padding:20px; background:#f8f9fa; border-radius:8px;"><h3>Columna 1</h3><p>Contenido</p></div><div class="col" style="flex:1; padding:20px; background:#f8f9fa; border-radius:8px;"><h3>Columna 2</h3><p>Contenido</p></div></div>',
    media: '<i class="fas fa-columns fa-2x"></i>'
});
gjsEditor.BlockManager.add('table',{
    category: {
        id: 'list',
        label: 'List',
        open: true
    },
    label: '<i class="fas fa-table"></i> Tabla',
    content: '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f8f9fa;"><th style="padding:10px; border:1px solid #ddd;">Encabezado 1</th><th style="padding:10px; border:1px solid #ddd;">Encabezado 2</th></tr></thead><tbody><tr><td style="padding:10px; border:1px solid #ddd;">Dato 1</td><td style="padding:10px; border:1px solid #ddd;">Dato 2</td></tr></tbody></table>',
    media: '<i class="fas fa-table fa-2x"></i>'
});
gjsEditor.BlockManager.add('list',{
    category: {
        id: 'list',
        label: 'List',
        open: true
    },
    label: '<i class="fas fa-list"></i> Lista',
    content: '<ul style="padding-left:20px;"><li>Elemento 1</li><li>Elemento 2</li><li>Elemento 3</li></ul>',
    media: '<i class="fas fa-list fa-2x"></i>'
});
gjsEditor.BlockManager.add('map',{
    category: {
        id: 'multimedia',
        label: 'Multimedia',
        open: true
    },
    label: '<i class="fas fa-map-marker-alt"></i> Mapa',
    content: '<div style="height:400px;"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316bb6de37%3A0xc8892517b33c1f09!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1641234567890!5m2!1sen!2sus" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div>',
    media: '<i class="fas fa-map-marker-alt fa-2x"></i>'
});
gjsEditor.BlockManager.add('video',{
    category: {
        id: 'multimedia',
        label: 'Multimedia',
        open: true
    },
    label: '<i class="fas fa-video"></i> Video',
    content: '<div style="position:relative; padding-bottom:56.25%; height:0;"><iframe style="position:absolute; top:0; left:0; width:100%; height:100%;" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allowfullscreen></iframe></div>',
    media: '<i class="fas fa-video fa-2x"></i>'
});
gjsEditor.BlockManager.add('card',{
    category: {
        id: 'components',
        label: 'Components',
        open: true
    },
    label: '<i class="fas fa-id-card"></i> Tarjeta',
    content: '<div class="card" style="width: 300px; border-radius:10px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);"><img src="https://via.placeholder.com/300x200" style="width:100%;"><div class="card-body" style="padding:20px;"><h5 class="card-title">Título de Tarjeta</h5><p class="card-text">Descripción de la tarjeta.</p><a href="#" class="btn btn-primary">Ver más</a></div></div>',
                        media: '<i class="fas fa-id-card fa-2x"></i>'
});
gjsEditor.BlockManager.add('pricing',{
    category: {
        id: 'templates',
        label: 'Templates',
        open: true
    },
    label: '<i class="fas fa-dollar-sign"></i> Precios',
    content: '<div style="display:flex; gap:20px; justify-content:center; flex-wrap:wrap; padding:40px 20px;"><div style="flex:1; min-width:250px; padding:20px; border:1px solid #ddd; border-radius:10px; text-align:center;"><h3>Básico</h3><p style="font-size:36px; color:#007bff;">$9<small>/mes</small></p><ul style="list-style:none; padding:0;"><li>Característica 1</li><li>Característica 2</li></ul><button class="btn btn-outline-primary">Comprar</button></div><div style="flex:1; min-width:250px; padding:20px; border:2px solid #007bff; border-radius:10px; text-align:center;"><h3>Pro</h3><p style="font-size:36px; color:#007bff;">$29<small>/mes</small></p><ul style="list-style:none; padding:0;"><li>Todas las características</li><li>Soporte prioritario</li></ul><button class="btn btn-primary">Comprar</button></div></div>',
    media: '<i class="fas fa-dollar-sign fa-2x"></i>'
});
gjsEditor.BlockManager.add('hero',{
    category: {
        id: 'templates',
        label: 'Templates',
        open: true
    },
    label: '<i class="fas fa-star"></i> Hero Section',
    content: '<div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:100px 20px; text-align:center;"><h1 style="font-size:48px; margin-bottom:20px;">Bienvenido a Mi Sitio</h1><p style="font-size:20px; margin-bottom:30px;">Una solución increíble para tu negocio</p><button class="btn btn-light btn-lg" style="padding:12px 30px; border-radius:30px;">Comenzar</button></div>',
    media: '<i class="fas fa-star fa-2x"></i>'
});
gjsEditor.BlockManager.add('icon',{
    category: {
        id: 'icons',
        label: 'Icons',
        open: true
    },
    label: '<i class="fab fa-font-awesome"></i> Iconos',
    content: '<div style="text-align:center; font-size:48px;"><i class="fas fa-star"></i> <i class="fas fa-heart"></i> <i class="fas fa-cog"></i></div>',
    media: '<i class="fab fa-font-awesome fa-2x"></i>'
});
gjsEditor.BlockManager.add('badge',{
    category: {
        id: 'especials',
        label: 'Especials',
        open: true
    },
    label: '<i class="fas fa-certificate"></i> Insignia',
    content: '<span style="background:#007bff; color:white; padding:5px 10px; border-radius:20px; font-size:12px;">Nuevo</span>',
    media: '<i class="fas fa-certificate fa-2x"></i>'
});
gjsEditor.BlockManager.add('progress',{
    category: {
        id: 'especials',
        label: 'Especials',
        open: true
    },
    label: '<i class="fas fa-chart-line"></i> Progreso',
    content: '<div style="background:#e9ecef; border-radius:10px; overflow:hidden;"><div style="background:#007bff; width:70%; padding:10px; color:white; text-align:center;">70%</div></div>',
    media: '<i class="fas fa-chart-line fa-2x"></i>'
});
gjsEditor.BlockManager.add('alert',{
    category: {
        id: 'especials',
        label: 'Especials',
        open: true
    },
    label: '<i class="fas fa-exclamation-triangle"></i> Alerta',
    content: '<div class="alert alert-success" style="padding:15px; border-radius:5px;">¡Operación exitosa!</div>',
    media: '<i class="fas fa-exclamation-triangle fa-2x"></i>'
});

