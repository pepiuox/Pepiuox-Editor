<?php
// ═══════════════════════════════════════════════════════════════════
// PUNTO DE ENTRADA DE LA APLICACIÓN
// ═══════════════════════════════════════════════════════════════════

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. CARGAR CONSTANTES Y CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════
require __DIR__ . '/config/paths.php';

// ═══════════════════════════════════════════════════════════════════
// 2. AUTOLOADER PSR-4 CON MANEJO DE ERRORES
// ═══════════════════════════════════════════════════════════════════
spl_autoload_register(function (string $class) {
    // Solo procesar clases con namespace App\
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
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
// 5. VERIFICAR QUE ESTÁ LA CONFIGURACIÓN DE BD
//    config/database.php solo LEE las credenciales: viven en
//    01-TOOLS/mysql/.env (git lo ignora) o en variables del proceso.
//    Como no hay instalador web (constitución, principio 16), este
//    mensaje es la única ayuda que ve quien llega sin configurar.
//    Guía completa: docs/INSTALL.md
// ═══════════════════════════════════════════════════════════════════
if (!is_file(CONFIG_PATH . '/database.php')) {
    http_response_code(500);
    die(
        'Falta config/database.php. Ese archivo lee las credenciales; no las lleva. '
        . 'Copia 01-TOOLS/mysql/.env.example a 01-TOOLS/mysql/.env y rellena DB_NAME, '
        . 'DB_USER y DB_PASSWORD. Guía: docs/INSTALL.md'
    );
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
    }
    die('Error interno del servidor');
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
    }
    die('Error interno del servidor');
}

// ═══════════════════════════════════════════════════════════════════
// 8. CARGAR RUTAS DESDE ARCHIVO EXTERNO O RUTAS POR DEFECTO
// ═══════════════════════════════════════════════════════════════════
if (is_file(CONFIG_PATH . '/routes.php')) {
    $routes = require CONFIG_PATH . '/routes.php';
    if (is_callable($routes)) {
        $routes($router, $request);
    }
} else {
    // Rutas por defecto si no existe el archivo
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

// Aquí vivía listarCarpeta(), una ayuda de desarrollo que se llamaba al final de
// CADA petición y volcaba el árbol de archivos del proyecto dentro de la página.
// Con el document root en la raíz, eso publicaba config/, db/ y 01-TOOLS/ a
// cualquiera que abriese el sitio. Se quitó el 2026-10-17. Si necesitas ver el
// árbol, usa `tree` o `ls -R`: no hace falta que lo imprima la aplicación.

