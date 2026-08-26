<?php
// ═══════════════════════════════════════════════════════════════════
// PUNTO DE ENTRADA DE LA APLICACIÓN
// ═══════════════════════════════════════════════════════════════════

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. CARGAR CONSTANTES Y CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════
require __DIR__ . '/../config/paths.php';

// ═══════════════════════════════════════════════════════════════════
// 2. AUTOLOADER PSR-4 CON MANEJO DE ERRORES
// ═══════════════════════════════════════════════════════════════════
spl_autoload_register(function (string $class) {
    // Solo procesar clases con namespace
    if (!str_contains($class, '\\')) {
        return;
    }

    // Mapeo de namespaces a directorios
    $prefixes = [
        'App\\' => APP_PATH . '/',
        'App\\Core\\' => APP_PATH . '/Core/',
        'App\\Models\\' => APP_PATH . '/Models/',
        'App\\Controllers\\' => APP_PATH . '/Controllers/',
        'App\\Services\\' => APP_PATH . '/Services/',
        'App\\Middleware\\' => APP_PATH . '/Middleware/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ═══════════════════════════════════════════════════════════════════
// 3. CARGAR HELPERS GLOBALES
// ═══════════════════════════════════════════════════════════════════
if (is_file(APP_PATH . '/Helpers/functions.php')) {
    require_once APP_PATH . '/Helpers/functions.php';
} else {
    die('Error: No se encontró el archivo de helpers en ' . APP_PATH . '/Helpers/functions.php');
}

// ═══════════════════════════════════════════════════════════════════
// 4. CARGAR CONFIGURACIÓN GENERAL
// ═══════════════════════════════════════════════════════════════════
if (!is_file(CONFIG_PATH . '/config.php')) {
    die('Error: No se encontró el archivo de configuración en ' . CONFIG_PATH . '/config.php');
}

$cfg = require CONFIG_PATH . '/config.php';
date_default_timezone_set($cfg['timezone'] ?? 'UTC');

// Configurar manejo de errores
if ($cfg['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ═══════════════════════════════════════════════════════════════════
// 5. VERIFICAR CONEXIÓN A BD
// ═══════════════════════════════════════════════════════════════════
if (!is_file(CONFIG_PATH . '/database.php')) {
    // Redirigir al instalador si no hay configuración de BD
    if (basename($_SERVER['REQUEST_URI'] ?? '') !== 'install.php') {
        header('Location: install.php');
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════
// 6. INICIALIZAR SESIÓN Y BOOTSTRAP
// ═══════════════════════════════════════════════════════════════════
try {
    App\Core\Session::start();
    App\Core\App::boot();
} catch (Throwable $e) {
    if ($cfg['debug'] ?? false) {
        die('Error al inicializar la aplicación: ' . $e->getMessage());
    } else {
        die('Error interno del servidor');
    }
}

// ═══════════════════════════════════════════════════════════════════
// 7. INICIALIZAR ROUTER Y REQUEST
// ═══════════════════════════════════════════════════════════════════
try {
    $router = new App\Core\Router();
    $request = new App\Core\Request();
} catch (Throwable $e) {
    if ($cfg['debug'] ?? false) {
        die('Error al inicializar el router: ' . $e->getMessage());
    } else {
        die('Error interno del servidor');
    }
}

// ═══════════════════════════════════════════════════════════════════
// 8. CARGAR RUTAS DESDE ARCHIVO EXTERNO
// ═══════════════════════════════════════════════════════════════════
if (is_file(CONFIG_PATH . '/routes.php')) {
    $routes = require CONFIG_PATH . '/routes.php';
    if (is_callable($routes)) {
        // Pasar el router y el request a la función de rutas
        $routes($router, $request);
    }
} else {
    // Si no existe el archivo de rutas, cargar rutas por defecto
    $router->get('/', [App\Controllers\PublicController::class, 'home']);
    $router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);
}

// ═══════════════════════════════════════════════════════════════════
// 9. DISPATCH CON MANEJO DE ERRORES
// ═══════════════════════════════════════════════════════════════════
try {
    $router->dispatch($request);
} catch (Throwable $e) {
    if ($cfg['debug'] ?? false) {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Error interno del servidor</h1>';
        error_log('[Router] Error: ' . $e->getMessage());
    }
}