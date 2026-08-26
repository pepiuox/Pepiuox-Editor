<?php
/**
 * Script de diagnóstico - ELIMINAR DESPUÉS DE USAR
 */

echo "<h1>🔍 Diagnóstico del Sistema</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#0d1117;color:#e6edf3}
      .ok{color:#22c55e} .error{color:#ef4444} .warn{color:#f59e0b}
      pre{background:#161b22;padding:10px;border-radius:5px;overflow:auto}</style>";

// 1. Verificar constantes
echo "<h2>1. Constantes</h2>";
$constants = ['ROOT_PATH', 'APP_PATH', 'CONFIG_PATH', 'VIEWS_PATH', 'UPLOADS_PATH'];
foreach ($constants as $const) {
    if (defined($const)) {
        echo "<p class='ok'>✓ $const = " . constant($const) . "</p>";
    } else {
        echo "<p class='error'>✗ $const NO DEFINIDA</p>";
    }
}

// 2. Verificar archivos críticos
echo "<h2>2. Archivos Críticos</h2>";
$files = [
    'config/paths.php',
    'config/config.php',
    'config/database.php',
    'app/Helpers/functions.php',
    'app/Core/Router.php',
    'app/Core/View.php',
    'app/Controllers/PagesController.php',
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        echo "<p class='ok'>✓ $file</p>";
    } else {
        echo "<p class='error'>✗ $file NO ENCONTRADO</p>";
    }
}

// 3. Verificar autoloader
echo "<h2>3. Autoloader</h2>";
require __DIR__ . '/../config/paths.php';

spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) return;
    $relativeClass = substr($class, 4);
    $path = APP_PATH . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($path)) {
        require_once $path;
        echo "<p class='ok'>✓ Clase cargada: $class</p>";
    } else {
        echo "<p class='error'>✗ Clase NO encontrada: $class (ruta: $path)</p>";
    }
});

// Probar cargar clases críticas
$classes = [
    'App\\Core\\Router',
    'App\\Core\\View',
    'App\\Core\\Database',
    'App\\Controllers\\PagesController',
    'App\\Controllers\\PublicController',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p class='ok'>✓ Clase existe: $class</p>";
    } else {
        echo "<p class='error'>✗ Clase NO existe: $class</p>";
    }
}

// 4. Verificar configuración de BD
echo "<h2>4. Configuración de Base de Datos</h2>";
if (file_exists(__DIR__ . '/../config/database.php')) {
    try {
        $dbConfig = require __DIR__ . '/../config/database.php';
        echo "<p class='ok'>✓ Configuración de BD cargada</p>";
        echo "<pre>";
        echo "Host: " . ($dbConfig['host'] ?? 'N/A') . "\n";
        echo "Database: " . ($dbConfig['dbname'] ?? 'N/A') . "\n";
        echo "Username: " . ($dbConfig['username'] ?? 'N/A') . "\n";
        echo "</pre>";
        
        // Probar conexión
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            echo "<p class='ok'>✓ Conexión a BD exitosa</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
        }
    } catch (Throwable $e) {
        echo "<p class='error'>✗ Error al cargar configuración: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>✗ Archivo database.php no encontrado</p>";
}

// 5. Verificar permisos de carpetas
echo "<h2>5. Permisos de Carpetas</h2>";
$dirs = ['storage', 'uploads', 'storage/cache', 'storage/logs'];
foreach ($dirs as $dir) {
    $fullPath = __DIR__ . '/../' . $dir;
    if (is_dir($fullPath)) {
        if (is_writable($fullPath)) {
            echo "<p class='ok'>✓ $dir (escribible)</p>";
        } else {
            echo "<p class='warn'>⚠ $dir (NO escribible)</p>";
        }
    } else {
        echo "<p class='warn'>⚠ $dir (no existe, se creará automáticamente)</p>";
    }
}

echo "<hr><p><strong>Diagnóstico completado. Elimina este archivo después de usar.</strong></p>";