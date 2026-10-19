<?php
/**
 * bootstrap.php
 * Configuración del contenedor de Inyección de Dependencias (DI)
 */

use App\Core\{Router, Request, Database, Security};
use App\Services\{
    MenuService,
    ActivityLoggerService,
    CacheService,
    AuditService,
    PageService,
    FileService,
    UserService,
    TemplatesService,
    FileManagerService
};
use App\Models\{FileModel, MediaModel};

if (!defined('APP_PATH')) {
    throw new \RuntimeException('[bootstrap] APP_PATH no está definida.');
}

/**
 * Construye el contenedor de servicios (singletons)
 */
function buildServiceContainer(): array
{
    static $services = null;

    if ($services !== null) {
        return $services;
    }

    try {
        // 1. Base de datos
        $db = new Database();

        // 2. Logger
        $logger = new ActivityLoggerService($db);

        // 3. Cache
        $cacheService = new CacheService($db);

        // 4. Audit
        $auditService = null;
        if (class_exists(AuditService::class)) {
            try {
                $auditService = new AuditService($db);
            } catch (\Throwable $e) {
                error_log('[bootstrap] AuditService no disponible: ' . $e->getMessage());
            }
        }

        // 5. Menu
        $menuService = new MenuService($db, $logger);

        // 6. Templates
        $templatesService = new TemplatesService($db, $auditService);

        // 7. Security
        $security = null;
        if (class_exists(Security::class)) {
            try {
                $security = new Security();
            } catch (\Throwable $e) {
                error_log('[bootstrap] Security no disponible: ' . $e->getMessage());
            }
        }

        // 8. FileManagerService con todas sus dependencias
        $fileManager = null;
        try {
            $fileModel = new FileModel($db);
            $mediaModel = new MediaModel($db);
            
            // Definir constantes si no existen
            if (!defined('UPLOADS_PATH')) {
                define('UPLOADS_PATH', APP_PATH . '/uploads/');
            }
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', dirname(APP_PATH));
            }

            $fileManager = new FileManagerService(
                $db,
                $fileModel,
                $mediaModel,
                $security,  // Puede ser null, FileManagerService lo maneja
                $logger
            );
        } catch (\Throwable $e) {
            error_log('[bootstrap] FileManagerService no disponible: ' . $e->getMessage());
        }

        // 9. Servicios adicionales (fallback)
        $pageService = null;
        $fileService = null;
        $userService = null;

        if (class_exists(PageService::class)) {
            try {
                // PageService exige Database Y PageModel: con solo $db lanzaba
                // ArgumentCountError y el servicio quedaba en null.
                $pageService = new PageService($db, new \App\Models\PageModel($db));
            } catch (\Throwable $e) {
                error_log('[bootstrap] PageService no disponible: ' . $e->getMessage());
            }
        }

        if (class_exists(FileService::class)) {
            try {
                $fileService = new FileService($db);
            } catch (\Throwable $e) {
                error_log('[bootstrap] FileService no disponible: ' . $e->getMessage());
            }
        }

        if (class_exists(UserService::class)) {
            try {
                $userService = new UserService($db);
            } catch (\Throwable $e) {
                error_log('[bootstrap] UserService no disponible: ' . $e->getMessage());
            }
        }

        // 10. Registrar singletons estáticos
        if (method_exists(CacheService::class, 'setInstance')) {
            CacheService::setInstance($cacheService);
        }
        if (method_exists(AuditService::class, 'setInstance')) {
            AuditService::setInstance($auditService);
        }

        $services = [
            'db'                => $db,
            'logger'            => $logger,
            'cacheService'      => $cacheService,
            'auditService'      => $auditService,
            'menuService'       => $menuService,
            'templatesService'  => $templatesService,
            'fileManager'       => $fileManager,
            'security'          => $security,
            'fileModel'         => $fileModel ?? null,
            'mediaModel'        => $mediaModel ?? null,
            'pageService'       => $pageService,
            'fileService'       => $fileService,
            'userService'       => $userService,
        ];

        return $services;

    } catch (\Throwable $e) {
        error_log('[bootstrap] Error al construir contenedor: ' . $e->getMessage());
        throw new \RuntimeException(
            'Error al inicializar servicios: ' . $e->getMessage(),
            0,
            $e
        );
    }
}

/**
 * Registra el resolver de dependencias en el Router
 */
function registerDependencies(Router $router, array $services): void
{
    $router->setResolver(function (string $class) use ($services) {
        $bindings = [
            // ═══ PÁGINAS ═══
            
            App\Controllers\PagesController::class => function() use ($services) {
                return new App\Controllers\PagesController(
                    $services['db'],
                    $services['pageService'] ?? null
                );
            },

            // ═══ PAGE CONTROLLER (público) ═══
            App\Controllers\PageController::class => function() use ($services) {
                return new App\Controllers\PageController(
                    $services['pageModel'] ?? null,
                    $services['pageStatsModel'] ?? null,
                    $services['pageRenderer'] ?? null,
                    $services['pagePublicService'] ?? null
                );
            },

            App\Controllers\UsersController::class => function() use ($services) {
                return new App\Controllers\UsersController(
                    $services['userService'] ?? null
                );
            },
                    
            // ═══ MENÚS ═══
            App\Controllers\MenuController::class => function() use ($services) {
                return new App\Controllers\MenuController(
                    $services['menuService'],
                    $services['cacheService'],
                    $services['auditService'],
                    $services['logger']
                );
            },

            // ═══ TEMPLATES ═══
            App\Controllers\TemplatesController::class => function() use ($services) {
                return new App\Controllers\TemplatesController(
                    $services['templatesService']
                );
            },

            // ═══ AUDIT ═══
            App\Controllers\AuditController::class => function() use ($services) {
                return new App\Controllers\AuditController(
                    $services['db'],
                    $services['auditService'],
                    $services['logger']
                );
            },

            // ═══ ACTIVITY LOGS ═══
            App\Controllers\ActivityLogsController::class => function() use ($services) {
                return new App\Controllers\ActivityLogsController(
                    $services['logger'],
                    $services['db']
                );
            },

            // ═══ CACHE ═══
            App\Controllers\CacheController::class => function() use ($services) {
                return new App\Controllers\CacheController(
                    $services['cacheService']
                );
            },

            // ═══ FILES - VERSIÓN CORREGIDA ═══
            App\Controllers\FilesController::class => function() use ($services) {
                $fileManager = $services['fileManager'] ?? null;
                
                // Si no existe, crearlo con fallback
                if (!$fileManager) {
                    try {
                        $fileModel = new FileModel($services['db']);
                        $mediaModel = new MediaModel($services['db']);
                        
                        if (!defined('UPLOADS_PATH')) {
                            define('UPLOADS_PATH', APP_PATH . '/uploads/');
                        }
                        if (!defined('ROOT_PATH')) {
                            define('ROOT_PATH', dirname(APP_PATH));
                        }

                        $fileManager = new FileManagerService(
                            $services['db'],
                            $fileModel,
                            $mediaModel,
                            $services['security'] ?? null,
                            $services['logger']
                        );
                    } catch (Throwable $e) {
                        error_log('[bootstrap] Error creando FileManagerService: ' . $e->getMessage());
                    }
                }
                
                // Asegurar que FileManager no sea null
                if (!$fileManager) {
                    error_log('[bootstrap] ¡ADVERTENCIA! FileManagerService es NULL');
                }
                
                return new App\Controllers\FilesController(
                    $services['db'],
                    $services['logger'],
                    $services['cacheService'],
                    $services['auditService'],
                    $fileManager  // Puede ser null, FilesController lo maneja
                );
            },
        ];

        if (isset($bindings[$class])) {
            return $bindings[$class]();
        }

        return autoResolve($class, $services);
    });
}

/**
 * Auto-resolución de dependencias usando Reflection
 */
function autoResolve(string $class, array $services): object
{
    if (!class_exists($class)) {
        throw new \RuntimeException("Clase no encontrada: {$class}");
    }

    $ref = new \ReflectionClass($class);
    $ctor = $ref->getConstructor();

    if (!$ctor) {
        return new $class();
    }

    $deps = [];
    foreach ($ctor->getParameters() as $param) {
        $type = $param->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $depClass = $type->getName();

            // Buscar en servicios por tipo
            foreach ($services as $service) {
                if (is_object($service) && $service instanceof $depClass) {
                    $deps[] = $service;
                    continue 2;
                }
            }

            // Resolver recursivamente
            try {
                $deps[] = autoResolve($depClass, $services);
                continue;
            } catch (\Throwable $e) {
                error_log("[autoResolve] No se pudo resolver '$depClass' para '$class': " . $e->getMessage());
            }
        }

        if ($param->isDefaultValueAvailable()) {
            $deps[] = $param->getDefaultValue();
            continue;
        }

        if ($param->allowsNull()) {
            $deps[] = null;
            continue;
        }

        throw new \RuntimeException(sprintf(
            "No se puede resolver el parámetro '\$%s' de '%s' (tipo: %s)",
            $param->getName(),
            $class,
            $type ? $type->getName() : 'sin tipo'
        ));
    }

    return $ref->newInstanceArgs($deps);
}
/**
 * Crea un FileManagerService fallback si los modelos no están disponibles
 */
function createFallbackFileManager(array $services): ?FileManagerService
{
    try {
        // Intentar crear modelos básicos
        $fileModel  = class_exists(FileModel::class)  ? new FileModel($services['db'])  : null;
        $mediaModel = class_exists(MediaModel::class) ? new MediaModel($services['db']) : null;
        $security   = class_exists(Security::class)   ? new Security()                  : null;

        if ($fileModel && $mediaModel && $security) {
            return new FileManagerService(
                $services['db'],
                $fileModel,
                $mediaModel,
                $security,
                $services['logger']
            );
        }
    } catch (Throwable $e) {
        error_log('[bootstrap] Error en fallback FileManager: ' . $e->getMessage());
    }
    return null;
}
// ═══════════════════════════════════════════════════════════════════
//  FUNCIONES DE AYUDA PARA DEPURACIÓN
// ═══════════════════════════════════════════════════════════════════

/**
 * Verifica el estado de los servicios cargados
 */
function checkServicesStatus(array $services): array
{
    $status = [];
    foreach ($services as $name => $service) {
        $status[$name] = [
            'loaded' => $service !== null,
            'type' => $service !== null ? get_class($service) : 'null'
        ];
    }
    return $status;
}

/**
 * Devuelve información de depuración del contenedor
 */
function debugContainer(array $services): string
{
    $output = "=== DI Container Status ===\n";
    foreach ($services as $name => $service) {
        $status = $service !== null ? '✅ ' . get_class($service) : '❌ null';
        $output .= sprintf("  %-20s %s\n", $name . ':', $status);
    }
    return $output;
}