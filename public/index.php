<?php
/**
 * public/index.php
 * Punto de entrada único de la aplicación
 *
 * Flujo:
 * 1. Cargar constantes y autoloader
 * 2. Cargar helpers y configuración
 * 3. Inicializar sesión y App
 * 4. Configurar DI Container (bootstrap.php)
 * 5. Cargar rutas
 * 6. Despachar petición
 */
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// 1. CONSTANTES Y AUTOLOADER
// ═══════════════════════════════════════════════════════════════════
require __DIR__ . '/../config/paths.php';

spl_autoload_register(function (string $class) {
    if (!str_contains($class, '\\')) return;

    $prefixes = [
        'App\\Core\\'        => APP_PATH . '/Core/',
        'App\\Models\\'      => APP_PATH . '/Models/',
        'App\\Controllers\\' => APP_PATH . '/Controllers/',
        'App\\Services\\'    => APP_PATH . '/Services/',
        'App\\Middleware\\'  => APP_PATH . '/Middleware/',
        'App\\'              => APP_PATH . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ═══════════════════════════════════════════════════════════════════
// 2. HELPERS Y CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════
$helpersFile = APP_PATH . '/Helpers/functions.php';
if (!is_file($helpersFile)) {
    die('Error: No se encontró el archivo de helpers en ' . $helpersFile);
}
require_once $helpersFile;

$configFile = CONFIG_PATH . '/config.php';
if (!is_file($configFile)) {
    die('Error: No se encontró config.php');
}
$cfg = require $configFile;

date_default_timezone_set($cfg['timezone'] ?? 'UTC');

if ($cfg['debug'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ═══════════════════════════════════════════════════════════════════
// 3. VERIFICAR BASE DE DATOS
//    Sin instalador web: se retiró de la raíz por seguridad
//    (constitución v1.0.0, principio 16). Guía: docs/INSTALL.md.
// ═══════════════════════════════════════════════════════════════════
if (!is_file(CONFIG_PATH . '/database.php')) {
    http_response_code(500);
    die('Falta config/database.php. Sigue docs/INSTALL.md para configurar la base de datos.');
}

// ═══════════════════════════════════════════════════════════════════
// 4. SESIÓN Y APP BOOT
// ═══════════════════════════════════════════════════════════════════
try {
    App\Core\Session::start();
    App\Core\App::boot();
} catch (Throwable $e) {
    $msg = ($cfg['debug'] ?? false) ? $e->getMessage() : 'Error interno del servidor';
    die('Error al inicializar la aplicación: ' . $msg);
}

// ═══════════════════════════════════════════════════════════════════
// 5. BOOTSTRAP DI CONTAINER
// ═══════════════════════════════════════════════════════════════════
try {
    $bootstrapFile = APP_PATH . '/bootstrap.php';
    if (!is_file($bootstrapFile)) {
        throw new \RuntimeException('No se encontró bootstrap.php en ' . $bootstrapFile);
    }
    require_once $bootstrapFile;

    // ✅ Construir contenedor de servicios (singleton)
    $services = buildServiceContainer();

    // ✅ Inicializar Router y Request
    $router  = new App\Core\Router();
    $request = new App\Core\Request();

    // ✅ Registrar dependencias en el router
    registerDependencies($router, $services);

} catch (\Throwable $e) {
    $msg = ($cfg['debug'] ?? false)
        ? $e->getMessage()
        : 'Error interno del servidor';
    http_response_code(500);
    die('Error al inicializar la aplicación: ' . htmlspecialchars($msg));
}

// ═══════════════════════════════════════════════════════════════════
// 6. CARGAR RUTAS
// ═══════════════════════════════════════════════════════════════════
$routesFile = CONFIG_PATH . '/routes.php';
if (is_file($routesFile)) {
    $routes = require $routesFile;
    if (is_callable($routes)) {
        $routes($router, $request);
    }
} else {
    $router->get('/', [App\Controllers\PublicController::class, 'home']);
    $router->get('/{slug}', [App\Controllers\PublicController::class, 'show']);
}

// ═══════════════════════════════════════════════════════════════════
// 7. DISPATCH
// ═══════════════════════════════════════════════════════════════════

try {
    $router->dispatch($request);
} catch (\Throwable $e) {
    if ($cfg['debug'] ?? false) {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Error interno del servidor</h1>';
        error_log('[App] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    }
}