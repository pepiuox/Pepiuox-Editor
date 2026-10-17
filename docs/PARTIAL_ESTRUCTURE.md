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
│   ├── database.php       # Configuración de BD (lee credenciales del entorno)
│   ├── env.php            # Carga de variables de entorno (DB_*)
│   └── paths.php          # Rutas absolutas
├── db/                    # Scripts SQL
│   └── db-full.sql        # Estructura y datos base
├── docs/                  # Documentación
│   ├── ROUTES.md          # Documentación de rutas
│   └── INSTALL.md         # Guía de instalación
├── 01-TOOLS/              # Herramientas y credenciales (fuera de la raíz web)
│   ├── mysql/             # Credenciales de MySQL (.env ignorado) + test de conexión
│   └── diagnostics/       # Scripts retirados de public/ (no accesibles por web)
├── 02-DOCS/               # Memoria del proyecto (wiki del harness)
├── public/                # Document root (accesible públicamente)
│   ├── .htaccess          # Reglas de Apache
│   ├── index.php          # Front controller
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
