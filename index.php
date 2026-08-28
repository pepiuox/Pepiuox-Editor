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
// 5. VERIFICAR CONEXIÓN A BD Y REDIRIGIR AL INSTALADOR
// ═══════════════════════════════════════════════════════════════════
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$currentFile = basename(parse_url($currentUri, PHP_URL_PATH) ?: '');

if (!is_file(CONFIG_PATH . '/database.php') && $currentFile !== 'install.php') {
    header('Location: install.php');
    exit;
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



function listarCarpeta($ruta, $nivel = 0)
{
    $elementos = array_diff(scandir($ruta), ['.', '..']);

    $carpetas = [];
    $archivos = [];

    foreach ($elementos as $elemento) {
        $rutaCompleta = $ruta . DIRECTORY_SEPARATOR . $elemento;

        if (is_dir($rutaCompleta)) {
            $carpetas[] = $elemento;
        } else {
            $archivos[] = $elemento;
        }
    }

    // Orden alfabético
    natcasesort($carpetas);
    natcasesort($archivos);

    // Primero carpetas
    foreach ($carpetas as $carpeta) {
        $rutaCompleta = $ruta . DIRECTORY_SEPARATOR . $carpeta;

        echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
        echo "📁 <strong>$carpeta</strong><br>";

        listarCarpeta($rutaCompleta, $nivel + 1);
    }

    // Después archivos
    foreach ($archivos as $archivo) {
        echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
        echo "📄 $archivo<br>";
    }
}

listarCarpeta(__DIR__);

