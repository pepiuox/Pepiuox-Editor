<?php
declare(strict_types=1);
require __DIR__ . '/../config/paths.php';

// Autoloader
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            echo "✅ Cargado: $class → $path<br>";
            require $path;
        } else {
            echo "❌ NO ENCONTRADO: $class → $path<br>";
        }
    }
});

echo "<h2>🔍 Diagnóstico TrustedDeviceService</h2>";

// 1. Verificar archivo
$file = APP_PATH . '/Services/TrustedDeviceService.php';
echo "<h3>1. Archivo del servicio</h3>";
if (is_file($file)) {
    echo "✅ Existe: $file<br>";
    echo "📏 Tamaño: " . filesize($file) . " bytes<br>";
} else {
    echo "❌ NO EXISTE: $file<br>";
    echo "👉 Debes crearlo<br>";
}

// 2. Verificar namespace
echo "<h3>2. Namespace en el archivo</h3>";
if (is_file($file)) {
    $content = file_get_contents($file);
    if (str_contains($content, 'namespace App\\Services;')) {
        echo "✅ Namespace correcto: App\\Services<br>";
    } else {
        echo "❌ Namespace incorrecto o faltante<br>";
    }
}

// 3. Verificar clase
echo "<h3>3. Clase TrustedDeviceService</h3>";
if (class_exists('\\App\\Services\\TrustedDeviceService')) {
    echo "✅ Clase cargada correctamente<br>";
    echo "📊 Constante MAX_DEVICES_PER_USER = " . \App\Services\TrustedDeviceService::MAX_DEVICES_PER_USER . "<br>";
} else {
    echo "❌ Clase NO disponible<br>";
}

// 4. Verificar tabla en BD
echo "<h3>4. Tabla trusted_devices en BD</h3>";
try {
    require APP_PATH . '/Helpers/functions.php';
    $cfg = require CONFIG_PATH . '/config.php';
    date_default_timezone_set($cfg['timezone'] ?? 'UTC');
    \App\Core\Session::start();
    
    $exists = \App\Core\Database::fetch("SHOW TABLES LIKE 'trusted_devices'");
    if ($exists) {
        echo "✅ Tabla existe<br>";
        $count = (int)\App\Core\Database::fetch("SELECT COUNT(*) AS c FROM trusted_devices")['c'];
        echo "📊 Registros: $count<br>";
    } else {
        echo "❌ Tabla NO existe<br>";
        echo "👉 Ejecuta la migración: db/migrations_trusted_devices.sql<br>";
    }
} catch (Throwable $e) {
    echo "❌ Error BD: " . $e->getMessage() . "<br>";
}

// 5. Verificar controlador
echo "<h3>5. Controlador TrustedDevicesController</h3>";
if (class_exists('\\App\\Controllers\\TrustedDevicesController')) {
    echo "✅ Controlador cargado<br>";
} else {
    echo "❌ Controlador NO disponible<br>";
}