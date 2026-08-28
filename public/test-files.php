<?php
/**
 * Script de prueba para verificar el sistema de archivos
 * Ejecutar: php test_files.php
 */

// Cargar configuración
require_once __DIR__ . '/../config/paths.php';

use App\Core\Database;
use App\Models\FileModel;
use App\Models\MediaModel;
use App\Services\FileManagerService;
use App\Core\Security;
use App\Services\ActivityLoggerService;

echo "=== TEST DE FILE MANAGER ===\n\n";

try {
    // 1. Verificar constantes
    echo "1. Verificando constantes:\n";
    echo "   APP_PATH: " . (defined('APP_PATH') ? APP_PATH : 'NO DEFINIDA') . "\n\n";
    echo "   UPLOADS_PATH: " . (defined('UPLOADS_PATH') ? UPLOADS_PATH : 'NO DEFINIDA') . "\n\n";
    echo "   ROOT_PATH: " . (defined('ROOT_PATH') ? ROOT_PATH : 'NO DEFINIDA') . "\n\n";

    // 2. Crear dependencias
    echo "2. Creando dependencias:\n";
    $db = new Database();
    echo "   ✅ Database: OK\n". "\n\n";
    
    $logger = new ActivityLoggerService($db);
    echo "   ✅ Logger: OK\n". "\n\n";
    
    $fileModel = new FileModel($db);
    echo "   ✅ FileModel: OK\n". "\n\n";
    
    $mediaModel = new MediaModel($db);
    echo "   ✅ MediaModel: OK\n". "\n\n";
    
    $security = new Security();
    echo "   ✅ Security: OK\n\n";

    // 3. Crear FileManagerService
    echo "3. Creando FileManagerService:\n";
    $fileManager = new FileManagerService(
        $db,
        $fileModel,
        $mediaModel,
        $security,
        $logger
    );
    echo "   ✅ FileManagerService creado correctamente\n\n";

    // 4. Verificar métodos básicos
    echo "4. Verificando métodos:\n";
    echo "   getCategories(): " . implode(', ', $fileManager->getCategories()) . "\n";
    echo "   formatFileSize(1024): " . $fileManager->formatFileSize(1024) . "\n";
    echo "   getFileIcon('pdf'): " . $fileManager->getFileIcon('pdf') . "\n";
    echo "   getCategoryColor('image'): " . $fileManager->getCategoryColor('image') . "\n\n";

    echo "✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE\n";

} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Traza:\n" . $e->getTraceAsString() . "\n";
}