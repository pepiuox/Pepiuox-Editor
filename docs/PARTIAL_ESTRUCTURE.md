pepioux-maker/
├── app/                    # Lógica de la aplicación
│   ├── Controllers/        # Controladores
│   ├── Core/              # Núcleo (Router, Request, etc.)
│   ├── Helpers/           # Funciones auxiliares
│   ├── Middleware/        # Middlewares (Auth, CSRF, etc.)
│   ├── Models/            # Modelos de datos
│   └── Services/          # Servicios de negocio
├── config/                # Configuración
│   ├── config.php         # Configuración general
│   ├── database.php       # Configuración de BD
│   └── paths.php          # Rutas absolutas
├── db/                    # Scripts SQL
│   ├── db.sql             # Estructura base
│   └── migrations.sql     # Migraciones adicionales
├── docs/                  # Documentación
│   ├── ROUTES.md          # Documentación de rutas
│   └── INSTALL.md         # Esta guía
├── public/                # Document root (accesible públicamente)
│   ├── .htaccess          # Reglas de Apache
│   ├── index.php          # Front controller
│   ├── install.php        # Instalador web
│   └── assets/            # CSS, JS, imágenes
│       ├── css/
│       ├── js/
│       └── images/
├── storage/               # Archivos privados (NO accesibles vía web)
│   ├── backups/           # Backups del sistema
│   ├── cache/             # Cache de páginas
│   ├── logs/              # Logs del sistema
│   └── versions/          # Versiones de archivos
├── uploads/               # Archivos subidos por usuarios
├── views/                 # Plantillas de vistas
│   ├── layouts/           # Layouts principales
│   ├── partials/          # Componentes reutilizables
│   └── [módulos]/         # Vistas por módulo
└── .htaccess              # Protección de carpetas sensibles