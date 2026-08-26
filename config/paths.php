<?php
/**
 * Configuración centralizada de Rutas y URLs
 * Evita problemas de inclusión de archivos y generación de enlaces.
 */

// ═══════════════════════════════════════════════════════════════
// 1. RUTAS ABSOLUTAS DEL SISTEMA (Filesystem)
// Usadas para: include, require, file_get_contents, mkdir, etc.
// ═══════════════════════════════════════════════════════════════

// ROOT_PATH apunta a la carpeta raíz del proyecto (donde está la carpeta config/)
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));

define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('PRIVATE_PATH', ROOT_PATH . '/private');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('LOGS_PATH', STORAGE_PATH . '/logs');

// ═══════════════════════════════════════════════════════════════
// 2. URLS WEB (Navegador)
// Usadas para: <link>, <img>, <script>, Location header, etc.
// ═══════════════════════════════════════════════════════════════

// Detectar protocolo (http o https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detectar el subdirectorio base automáticamente.
// Si SCRIPT_NAME es "/public/index.php", dirname será "/public".
// Si SCRIPT_NAME es "/index.php", dirname será "/".
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

// URL base de la aplicación (ej: http://localhost:170/public)
define('BASE_URL', $protocol . $host . $basePath);

// URLs específicas de recursos
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// ═══════════════════════════════════════════════════════════════
// 3. ALIAS DE COMPATIBILIDAD (Para migrar código antiguo fácilmente)
// ═══════════════════════════════════════════════════════════════
define('SITE_URL', BASE_URL);
define('URL_BASE', BASE_URL); 
define('BASE_PATH', $basePath . '/'); // ej: 'public/' o '/'
define('VIEWS', 'views'); // Relativo a ROOT_PATH

// Crear directorios necesarios si no existen
$directories = [
    CACHE_PATH,
    LOGS_PATH,
    UPLOADS_PATH,
    STORAGE_PATH . '/cache/pages',
    STORAGE_PATH . '/cache/data',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
