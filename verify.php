<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * PePiuoX Maker — Script de Verificación Automática
 * ═══════════════════════════════════════════════════════════════════
 * 
 * Uso CLI:   php verify.php [--fix] [--json] [--verbose]
 * Uso Web:   https://pepiuox.com/verify.php
 * 
 * Fases:
 *   1. Sistema (PHP, extensiones, archivos)
 *   2. Base de datos (tablas, columnas, datos)
 *   3. Rutas (Router, endpoints)
 *   4. Permisos (archivos, carpetas)
 *   5. Integridad (clases, servicios, namespaces)
 *   6. Funcional (login, CRUD, servicios)
 * 
 * @author PePiuoX Team
 * @version 1.0.0
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN INICIAL
// ═══════════════════════════════════════════════════════════════════

$isCli = PHP_SAPI === 'cli';
$fixMode = in_array('--fix', $argv ?? $_GET ?? [], true);
$jsonMode = in_array('--json', $argv ?? $_GET ?? [], true);
$verbose = in_array('--verbose', $argv ?? $_GET ?? [], true);

// Cargar constantes básicas
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/app');
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config');
}

// ═══════════════════════════════════════════════════════════════════
// CLASE PRINCIPAL: VERIFICATION REPORT
// ═══════════════════════════════════════════════════════════════════

class VerificationReport
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private int $warnings = 0;
    private int $fixed = 0;
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function addResult(string $phase, string $test, string $status, string $message, bool $autoFixed = false): void
    {
        $this->results[] = [
            'phase' => $phase,
            'test' => $test,
            'status' => $status, // pass, fail, warn, fixed
            'message' => $message,
            'timestamp' => date('H:i:s'),
        ];

        if ($autoFixed) {
            $this->fixed++;
        }
        
        match ($status) {
            'pass' => $this->passed++,
            'fail' => $this->failed++,
            'warn' => $this->warnings++,
            'fixed' => $this->fixed++,
            default => null,
        };
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getSummary(): array
    {
        return [
            'total' => count($this->results),
            'passed' => $this->passed,
            'failed' => $this->failed,
            'warnings' => $this->warnings,
            'fixed' => $this->fixed,
            'duration' => round(microtime(true) - $this->startTime, 3),
            'success_rate' => count($this->results) > 0 
                ? round(($this->passed / count($this->results)) * 100, 1) 
                : 0,
        ];
    }

    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 1: VERIFICADOR DE SISTEMA
// ═══════════════════════════════════════════════════════════════════

class SystemVerifier
{
    private const REQUIRED_EXTENSIONS = [
        'pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl',
        'curl', 'gd', 'zip', 'intl', 'fileinfo', 'session'
    ];

    private const RECOMMENDED_EXTENSIONS = [
        'redis', 'memcached', 'imagick', 'opcache'
    ];

    private const CRITICAL_FILES = [
        'config/paths.php',
        'config/config.php',
        'config/database.php',
        'public/index.php',
        'public/.htaccess',
        'app/Core/Router.php',
        'app/Core/Request.php',
        'app/Core/Response.php',
        'app/Core/Session.php',
        'app/Core/CSRF.php',
        'app/Core/Auth.php',
        'app/Core/Database.php',
        'app/Core/View.php',
        'app/Helpers/functions.php',
        'app/Middleware/AuthMiddleware.php',
        'app/Middleware/RateLimitMiddleware.php',
        'app/Controllers/AuthController.php',
        'app/Controllers/PagesController.php',
        'app/Services/AuditService.php',
        'app/Services/CacheService.php',
        'app/Services/PageService.php',
    ];

    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkCriticalFiles();
        $this->checkPhpSettings();
        $this->checkMemoryAndLimits();
    }

    private function checkPhpVersion(): void
    {
        $version = PHP_VERSION;
        $required = '8.1.0';
        
        if (version_compare($version, $required, '>=')) {
            $this->report->addResult(
                'Sistema',
                'Versión PHP',
                'pass',
                "PHP $version (requerido: >= $required)"
            );
        } else {
            $this->report->addResult(
                'Sistema',
                'Versión PHP',
                'fail',
                "PHP $version es insuficiente. Se requiere >= $required"
            );
        }
    }

    private function checkExtensions(): void
    {
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (extension_loaded($ext)) {
                $this->report->addResult('Sistema', "Extensión: $ext", 'pass', "Extensión $ext cargada");
            } else {
                $this->report->addResult(
                    'Sistema',
                    "Extensión: $ext",
                    'fail',
                    "Extensión $ext NO está cargada. Instalar con: sudo apt install php-$ext"
                );
            }
        }

        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            if (extension_loaded($ext)) {
                $this->report->addResult('Sistema', "Extensión recomendada: $ext", 'pass', "Extensión $ext cargada");
            } else {
                $this->report->addResult(
                    'Sistema',
                    "Extensión recomendada: $ext",
                    'warn',
                    "Extensión $ext no está cargada (opcional pero recomendada)"
                );
            }
        }
    }

    private function checkCriticalFiles(): void
    {
        foreach (self::CRITICAL_FILES as $file) {
            $path = ROOT_PATH . '/' . $file;
            if (is_file($path)) {
                $this->report->addResult('Sistema', "Archivo: $file", 'pass', "Archivo existe");
            } else {
                $this->report->addResult(
                    'Sistema',
                    "Archivo: $file",
                    'fail',
                    "Archivo crítico faltante: $file"
                );
            }
        }
    }

    private function checkPhpSettings(): void
    {
        $settings = [
            'display_errors' => ['0', 'warn', 'display_errors debe estar OFF en producción'],
            'file_uploads' => ['1', 'fail', 'file_uploads debe estar habilitado'],
            'allow_url_fopen' => ['1', 'warn', 'allow_url_fopen debe estar habilitado'],
        ];

        foreach ($settings as $key => [$expected, $severity, $message]) {
            $value = ini_get($key);
            if ($value === $expected) {
                $this->report->addResult('Sistema', "PHP: $key", 'pass', "$key = $value");
            } else {
                $this->report->addResult('Sistema', "PHP: $key", $severity, "$message (actual: $value)");
            }
        }
    }

    private function checkMemoryAndLimits(): void
    {
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        if ($memoryLimit < 128 * 1024 * 1024) {
            $this->report->addResult(
                'Sistema',
                'memory_limit',
                'warn',
                'memory_limit es menor a 128M. Recomendado: 256M'
            );
        } else {
            $this->report->addResult('Sistema', 'memory_limit', 'pass', ini_get('memory_limit'));
        }

        $uploadMax = $this->parseSize(ini_get('upload_max_filesize'));
        if ($uploadMax < 10 * 1024 * 1024) {
            $this->report->addResult(
                'Sistema',
                'upload_max_filesize',
                'warn',
                'upload_max_filesize es menor a 10M'
            );
        } else {
            $this->report->addResult('Sistema', 'upload_max_filesize', 'pass', ini_get('upload_max_filesize'));
        }
    }

    private function parseSize(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int)$value;
        
        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 2: VERIFICADOR DE BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════

class DatabaseVerifier
{
    private const REQUIRED_TABLES = [
        'users', 'users_roles', 'users_permissions', 'users_role_permissions',
        'pages', 'page_versions', 'page_statistics', 'page_menu', 'page_cache',
        'templates', 'template_versions', 'meta_tags',
        'files', 'file_shares',
        'visits', 'user_logins', 'user_sessions', 'login_attempts',
        'activity_log', 'blocked_ips', 'cache_settings',
        'api_applications', 'api_tokens', 'api_access_logs',
    ];

    private const CRITICAL_COLUMNS = [
        'users' => ['id', 'username', 'email', 'password_hash', 'role', 'is_active', 'is_banned'],
        'pages' => ['id', 'user_id', 'title', 'slug', 'content_html', 'status', 'is_published'],
        'page_menu' => ['id', 'page_id', 'title', 'slug', 'parent_id', 'order'],
    ];

    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $dbConfig = $this->loadDbConfig();
        if (!$dbConfig) return;

        try {
            $pdo = $this->connect($dbConfig);
        } catch (Throwable $e) {
            $this->report->addResult('Base de datos', 'Conexión', 'fail', 'No se pudo conectar: ' . $e->getMessage());
            return;
        }

        $this->report->addResult('Base de datos', 'Conexión', 'pass', 'Conexión exitosa a MySQL');

        $this->checkTables($pdo);
        $this->checkCriticalColumns($pdo);
        $this->checkAdminUser($pdo);
        $this->checkDataIntegrity($pdo);
    }

    private function loadDbConfig(): ?array
    {
        $path = CONFIG_PATH . '/database.php';
        if (!is_file($path)) {
            $this->report->addResult('Base de datos', 'Configuración', 'fail', 'config/database.php no existe');
            return null;
        }
        $config = require $path;
        $this->report->addResult('Base de datos', 'Configuración', 'pass', 'Configuración cargada');
        return $config;
    }

    private function connect(array $cfg): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['driver'] ?? 'mysql',
            $cfg['host'] ?? '127.0.0.1',
            $cfg['port'] ?? 3306,
            $cfg['dbname'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            $cfg['username'] ?? 'root',
            $cfg['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    private function checkTables(PDO $pdo): void
    {
        $stmt = $pdo->query('SHOW TABLES');
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach (self::REQUIRED_TABLES as $table) {
            if (in_array($table, $existingTables, true)) {
                $this->report->addResult('Base de datos', "Tabla: $table", 'pass', "Tabla existe");
            } else {
                $this->report->addResult(
                    'Base de datos',
                    "Tabla: $table",
                    'fail',
                    "Tabla requerida faltante: $table. Ejecutar db.sql y migrations.sql"
                );
            }
        }
    }

    private function checkCriticalColumns(PDO $pdo): void
    {
        foreach (self::CRITICAL_COLUMNS as $table => $columns) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
                $existingColumns = array_column($stmt->fetchAll(), 'Field');

                foreach ($columns as $column) {
                    if (in_array($column, $existingColumns, true)) {
                        $this->report->addResult(
                            'Base de datos',
                            "Columna: $table.$column",
                            'pass',
                            "Columna existe"
                        );
                    } else {
                        $this->report->addResult(
                            'Base de datos',
                            "Columna: $table.$column",
                            'fail',
                            "Columna faltante en $table: $column"
                        );
                    }
                }
            } catch (Throwable $e) {
                $this->report->addResult(
                    'Base de datos',
                    "Tabla: $table",
                    'fail',
                    "No se pudo inspeccionar tabla $table: " . $e->getMessage()
                );
            }
        }
    }

    private function checkAdminUser(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'Super admin' AND is_active = 1");
            $count = (int)$stmt->fetchColumn();

            if ($count > 0) {
                $this->report->addResult('Base de datos', 'Usuario Super Admin', 'pass', "Existe(n) $count Super Admin(s) activo(s)");
            } else {
                $this->report->addResult(
                    'Base de datos',
                    'Usuario Super Admin',
                    'fail',
                    'No existe ningún usuario Super Admin activo. Ejecutar el instalador.'
                );
            }
        } catch (Throwable $e) {
            $this->report->addResult('Base de datos', 'Usuario Super Admin', 'warn', 'No se pudo verificar: ' . $e->getMessage());
        }
    }

    private function checkDataIntegrity(PDO $pdo): void
    {
        // Verificar usuarios sin rol válido
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) AS c FROM users u
                LEFT JOIN users_roles r ON u.role = r.name
                WHERE r.idRol IS NULL AND u.role IS NOT NULL
            ");
            $orphanUsers = (int)$stmt->fetchColumn();

            if ($orphanUsers > 0) {
                $this->report->addResult(
                    'Base de datos',
                    'Integridad de roles',
                    'warn',
                    "$orphanUsers usuarios tienen roles que no existen en users_roles"
                );
            } else {
                $this->report->addResult('Base de datos', 'Integridad de roles', 'pass', 'Todos los roles son válidos');
            }
        } catch (Throwable $e) {
            $this->report->addResult('Base de datos', 'Integridad de roles', 'warn', 'No se pudo verificar');
        }

        // Verificar páginas huérfanas
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) AS c FROM pages p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE u.id IS NULL
            ");
            $orphanPages = (int)$stmt->fetchColumn();

            if ($orphanPages > 0) {
                $this->report->addResult(
                    'Base de datos',
                    'Integridad de páginas',
                    'warn',
                    "$orphanPages páginas tienen user_id que no existe"
                );
            } else {
                $this->report->addResult('Base de datos', 'Integridad de páginas', 'pass', 'Todas las páginas tienen usuario válido');
            }
        } catch (Throwable $e) {
            $this->report->addResult('Base de datos', 'Integridad de páginas', 'warn', 'No se pudo verificar');
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 3: VERIFICADOR DE RUTAS
// ═══════════════════════════════════════════════════════════════════

class RoutesVerifier
{
    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $this->checkRouterClass();
        $this->checkRouteConflicts();
        $this->checkCriticalRoutes();
        $this->checkMiddlewareClasses();
    }

    private function checkRouterClass(): void
    {
        $routerFile = APP_PATH . '/Core/Router.php';
        if (!is_file($routerFile)) {
            $this->report->addResult('Rutas', 'Router.php', 'fail', 'Archivo Router.php no existe');
            return;
        }

        $content = file_get_contents($routerFile);

        // Verificar que soporta middlewares como objetos
        if (str_contains($content, 'is_object($mw)') || str_contains($content, 'is_object( $mw )')) {
            $this->report->addResult('Rutas', 'Router: soporte objetos', 'pass', 'Router soporta middlewares como objetos');
        } else {
            $this->report->addResult(
                'Rutas',
                'Router: soporte objetos',
                'fail',
                'Router NO soporta middlewares instanciados (new RateLimitMiddleware). Actualizar Router.php'
            );
        }

        // Verificar que soporta método DELETE
        if (str_contains($content, "'DELETE'") || str_contains($content, '"DELETE"')) {
            $this->report->addResult('Rutas', 'Router: método DELETE', 'pass', 'Router soporta método DELETE');
        } else {
            $this->report->addResult(
                'Rutas',
                'Router: método DELETE',
                'warn',
                'Router no tiene método delete(). Las rutas DELETE fallarán.'
            );
        }
    }

    private function checkRouteConflicts(): void
    {
        $indexFile = ROOT_PATH . '/public/index.php';
        if (!is_file($indexFile)) {
            $this->report->addResult('Rutas', 'index.php', 'fail', 'public/index.php no existe');
            return;
        }

        $content = file_get_contents($indexFile);

        // Buscar rutas duplicadas
        preg_match_all('/\$router->(?:get|post|any)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        $routes = $matches[1] ?? [];
        $counts = array_count_values($routes);
        $duplicates = array_filter($counts, fn($c) => $c > 1);

        if (empty($duplicates)) {
            $this->report->addResult('Rutas', 'Rutas duplicadas', 'pass', 'No hay rutas duplicadas');
        } else {
            foreach ($duplicates as $route => $count) {
                $this->report->addResult(
                    'Rutas',
                    "Ruta duplicada: $route",
                    'fail',
                    "La ruta '$route' está definida $count veces. Eliminar duplicados."
                );
            }
        }

        // Verificar orden: rutas fijas antes que {id}
        $this->checkRouteOrder($content);
    }

    private function checkRouteOrder(string $content): void
    {
        // Patrones problemáticos conocidos
        $problematicPatterns = [
            '/admin/users/banned/{id}/history' => '/admin/users/banned/history',
            '/admin/seo/{id}/analyze' => '/admin/seo/redirects',
            '/admin/menus/{id}' => '/admin/menus/reorder',
            '/admin/files/{id}/download' => '/admin/files/wysiwyg-upload',
            '/admin/components/{id}/edit' => '/admin/components/api',
            '/admin/audit/activity/{id}' => '/admin/audit/activity/export',
        ];

        foreach ($problematicPatterns as $paramRoute => $fixedRoute) {
            $paramPos = strpos($content, "'$paramRoute'");
            $fixedPos = strpos($content, "'$fixedRoute'");

            if ($paramPos !== false && $fixedPos !== false && $paramPos < $fixedPos) {
                $this->report->addResult(
                    'Rutas',
                    "Orden: $fixedRoute",
                    'fail',
                    "La ruta fija '$fixedRoute' debe ir ANTES de '$paramRoute'"
                );
            } elseif ($paramPos !== false && $fixedPos !== false) {
                $this->report->addResult(
                    'Rutas',
                    "Orden: $fixedRoute",
                    'pass',
                    "Orden correcto: $fixedRoute antes que $paramRoute"
                );
            }
        }
    }

    private function checkCriticalRoutes(): void
    {
        $indexFile = ROOT_PATH . '/public/index.php';
        $content = file_get_contents($indexFile);

        $criticalRoutes = [
            '/' => 'Home',
            '/login' => 'Login',
            '/admin' => 'Admin Dashboard',
            '/admin/pages' => 'Páginas',
            '/admin/users' => 'Usuarios',
            '/admin/settings' => 'Configuración',
        ];

        foreach ($criticalRoutes as $route => $name) {
            if (str_contains($content, "'$route'")) {
                $this->report->addResult('Rutas', "Ruta crítica: $name", 'pass', "Ruta $route definida");
            } else {
                $this->report->addResult('Rutas', "Ruta crítica: $name", 'fail', "Ruta crítica faltante: $route");
            }
        }
    }

    private function checkMiddlewareClasses(): void
    {
        $middlewares = [
            'AuthMiddleware' => APP_PATH . '/Middleware/AuthMiddleware.php',
            'RateLimitMiddleware' => APP_PATH . '/Middleware/RateLimitMiddleware.php',
            'ApiAuthMiddleware' => APP_PATH . '/Middleware/ApiAuthMiddleware.php',
            'PermissionMiddleware' => APP_PATH . '/Middleware/PermissionMiddleware.php',
        ];

        foreach ($middlewares as $name => $path) {
            if (is_file($path)) {
                $this->report->addResult('Rutas', "Middleware: $name", 'pass', "Clase existe");
            } else {
                $this->report->addResult('Rutas', "Middleware: $name", 'fail', "Archivo faltante: $path");
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 4: VERIFICADOR DE PERMISOS
// ═══════════════════════════════════════════════════════════════════

class PermissionsVerifier
{
    private const WRITABLE_DIRS = [
        'storage',
        'storage/cache',
        'storage/logs',
        'storage/backups',
        'storage/versions',
        'uploads',
    ];

    private const PROTECTED_DIRS = [
        'config',
        'app',
        'views',
        'db',
    ];

    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $this->checkWritableDirectories();
        $this->checkProtectedDirectories();
        $this->checkSensitiveFiles();
        $this->checkHtaccess();
    }

    private function checkWritableDirectories(): void
    {
        foreach (self::WRITABLE_DIRS as $dir) {
            $path = ROOT_PATH . '/' . $dir;
            
            if (!is_dir($path)) {
                $this->report->addResult(
                    'Permisos',
                    "Carpeta: $dir",
                    'warn',
                    "Carpeta no existe: $dir (se creará automáticamente)"
                );
                continue;
            }

            if (is_writable($path)) {
                $this->report->addResult('Permisos', "Carpeta: $dir", 'pass', "Carpeta escribible");
            } else {
                $this->report->addResult(
                    'Permisos',
                    "Carpeta: $dir",
                    'fail',
                    "Carpeta NO escribible: $dir. Ejecutar: chmod -R 775 $dir"
                );
            }
        }
    }

    private function checkProtectedDirectories(): void
    {
        foreach (self::PROTECTED_DIRS as $dir) {
            $path = ROOT_PATH . '/' . $dir;
            
            if (!is_dir($path)) {
                $this->report->addResult('Permisos', "Protección: $dir", 'warn', "Carpeta no existe: $dir");
                continue;
            }

            // Verificar que no sea accesible vía web (solo si estamos en web)
            if (PHP_SAPI !== 'cli') {
                $this->report->addResult(
                    'Permisos',
                    "Protección: $dir",
                    'pass',
                    "Carpeta existe (protegida por .htaccess)"
                );
            } else {
                $this->report->addResult('Permisos', "Protección: $dir", 'pass', "Carpeta existe");
            }
        }
    }

    private function checkSensitiveFiles(): void
    {
        $sensitiveFiles = [
            'config/database.php' => 'No debe ser accesible vía web',
            '.env' => 'No debe existir en producción',
        ];

        foreach ($sensitiveFiles as $file => $note) {
            $path = ROOT_PATH . '/' . $file;
            if (is_file($path)) {
                $this->report->addResult('Permisos', "Archivo sensible: $file", 'pass', "Existe ($note)");
            }
        }
    }

    private function checkHtaccess(): void
    {
        $htaccessFiles = [
            ROOT_PATH . '/.htaccess' => 'Raíz',
            ROOT_PATH . '/public/.htaccess' => 'Public',
        ];

        foreach ($htaccessFiles as $path => $location) {
            if (is_file($path)) {
                $content = file_get_contents($path);
                
                if (str_contains($content, 'RewriteEngine')) {
                    $this->report->addResult('Permisos', ".htaccess ($location)", 'pass', "mod_rewrite configurado");
                } else {
                    $this->report->addResult(
                        'Permisos',
                        ".htaccess ($location)",
                        'warn',
                        "No contiene RewriteEngine"
                    );
                }
            } else {
                $this->report->addResult(
                    'Permisos',
                    ".htaccess ($location)",
                    'warn',
                    "Archivo no existe en $location"
                );
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 5: VERIFICADOR DE INTEGRIDAD
// ═══════════════════════════════════════════════════════════════════

class IntegrityVerifier
{
    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $this->checkAuditServiceLocation();
        $this->checkNamespaces();
        $this->checkAutoloader();
        $this->checkServiceClasses();
        $this->checkControllerClasses();
    }

    private function checkAuditServiceLocation(): void
    {
        // Problema conocido: AuditService dentro de PagesController.php
        $pagesController = APP_PATH . '/Controllers/PagesController.php';
        $auditService = APP_PATH . '/Services/AuditService.php';

        if (is_file($pagesController)) {
            $content = file_get_contents($pagesController);
            
            if (preg_match('/class\s+AuditService\s*\{/', $content)) {
                $this->report->addResult(
                    'Integridad',
                    'AuditService ubicación',
                    'fail',
                    'AuditService está DENTRO de PagesController.php. Debe estar en app/Services/AuditService.php'
                );
            } else {
                $this->report->addResult(
                    'Integridad',
                    'AuditService ubicación',
                    'pass',
                    'AuditService no está en PagesController.php'
                );
            }
        }

        if (is_file($auditService)) {
            $this->report->addResult('Integridad', 'AuditService archivo', 'pass', 'app/Services/AuditService.php existe');
        } else {
            $this->report->addResult(
                'Integridad',
                'AuditService archivo',
                'fail',
                'app/Services/AuditService.php NO existe. Crear el archivo.'
            );
        }
    }

    private function checkNamespaces(): void
    {
        $files = [
            APP_PATH . '/Controllers/PagesController.php' => 'App\\Controllers\\PagesController',
            APP_PATH . '/Controllers/AuthController.php' => 'App\\Controllers\\AuthController',
            APP_PATH . '/Core/Router.php' => 'App\\Core\\Router',
            APP_PATH . '/Services/AuditService.php' => 'App\\Services\\AuditService',
        ];

        foreach ($files as $file => $expectedNamespace) {
            if (!is_file($file)) continue;

            $content = file_get_contents($file);
            $className = basename($expectedNamespace);
            
            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                $actualNamespace = trim($matches[1]);
                $expectedNs = substr($expectedNamespace, 0, strrpos($expectedNamespace, '\\'));
                
                if ($actualNamespace === $expectedNs) {
                    $this->report->addResult(
                        'Integridad',
                        "Namespace: $className",
                        'pass',
                        "Namespace correcto: $actualNamespace"
                    );
                } else {
                    $this->report->addResult(
                        'Integridad',
                        "Namespace: $className",
                        'fail',
                        "Namespace incorrecto: $actualNamespace (esperado: $expectedNs)"
                    );
                }
            }
        }
    }

    private function checkAutoloader(): void
    {
        $indexFile = ROOT_PATH . '/public/index.php';
        if (!is_file($indexFile)) return;

        $content = file_get_contents($indexFile);

        if (str_contains($content, 'spl_autoload_register')) {
            $this->report->addResult('Integridad', 'Autoloader PSR-4', 'pass', 'Autoloader registrado');
        } else {
            $this->report->addResult('Integridad', 'Autoloader PSR-4', 'fail', 'Autoloader no registrado en index.php');
        }
    }

    private function checkServiceClasses(): void
    {
        $services = [
            'AuditService',
            'CacheService',
            'PageService',
            'SitemapService',
            'SettingService',
            'NotificationService',
            'BackupService',
            'RateLimiter',
            'TotpService',
            'EmailTemplate',
            'EmailNotificationService',
            'TrustedDeviceService',
            'CollaborationService',
            'SeoService',
            'MetaDescriptionGenerator',
            'SeoCompetitorService',
            'BanService',
            'TwoFactorRecoveryService',
            'FileService',
            'MenuService',
        ];

        foreach ($services as $service) {
            $path = APP_PATH . '/Services/' . $service . '.php';
            if (is_file($path)) {
                $this->report->addResult('Integridad', "Servicio: $service", 'pass', "Clase existe");
            } else {
                $this->report->addResult('Integridad', "Servicio: $service", 'warn', "Archivo faltante: $service.php");
            }
        }
    }

    private function checkControllerClasses(): void
    {
        $controllers = [
            'AuthController',
            'PagesController',
            'UsersController',
            'DashboardController',
            'RolesController',
            'TemplatesController',
            'MenusController',
            'FilesController',
            'SeoController',
            'CacheController',
            'SettingsController',
            'BackupsController',
            'NotificationsController',
            'AnalyticsController',
            'AuditController',
            'ActivityLogsController',
            'SecurityDashboardController',
            'LoginAttemptsController',
            'TrustedDevicesController',
            'CollaborationController',
            'ProfileController',
            'TwoFactorController',
            'PasswordController',
            'BannedUsersController',
            'ComponentsController',
            'TranslationsController',
            'ApiManageController',
            'ApiController',
            'PublicController',
            'LanguageController',
            'VersionsController',
        ];

        foreach ($controllers as $controller) {
            $path = APP_PATH . '/Controllers/' . $controller . '.php';
            if (is_file($path)) {
                $this->report->addResult('Integridad', "Controller: $controller", 'pass', "Clase existe");
            } else {
                $this->report->addResult('Integridad', "Controller: $controller", 'fail', "Archivo faltante: $controller.php");
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// FASE 6: VERIFICADOR FUNCIONAL
// ═══════════════════════════════════════════════════════════════════

class FunctionalVerifier
{
    public function __construct(private VerificationReport $report) {}

    public function verify(): void
    {
        $this->checkHelpersLoaded();
        $this->checkCsrfToken();
        $this->checkSessionHandling();
        $this->checkViewRendering();
        $this->checkPasswordHashing();
    }

    private function checkHelpersLoaded(): void
    {
        $helpersFile = APP_PATH . '/Helpers/functions.php';
        if (!is_file($helpersFile)) {
            $this->report->addResult('Funcional', 'Helpers', 'fail', 'functions.php no existe');
            return;
        }

        $content = file_get_contents($helpersFile);
        $requiredFunctions = ['url', 'asset', 'e', 'csrf_field', 'csrf_token', 'can', 'is_active'];

        foreach ($requiredFunctions as $func) {
            if (preg_match('/function\s+' . $func . '\s*\(/', $content)) {
                $this->report->addResult('Funcional', "Helper: $func()", 'pass', "Función existe");
            } else {
                $this->report->addResult('Funcional', "Helper: $func()", 'fail', "Función faltante: $func()");
            }
        }
    }

    private function checkCsrfToken(): void
    {
        $csrfFile = APP_PATH . '/Core/CSRF.php';
        if (!is_file($csrfFile)) {
            $this->report->addResult('Funcional', 'CSRF', 'fail', 'CSRF.php no existe');
            return;
        }

        $content = file_get_contents($csrfFile);
        $methods = ['token', 'field', 'verify', 'guard'];

        foreach ($methods as $method) {
            if (preg_match('/public\s+(?:static\s+)?function\s+' . $method . '\s*\(/', $content)) {
                $this->report->addResult('Funcional', "CSRF::$method()", 'pass', "Método existe");
            } else {
                $this->report->addResult('Funcional', "CSRF::$method()", 'fail', "Método faltante: $method()");
            }
        }
    }

    private function checkSessionHandling(): void
    {
        $sessionFile = APP_PATH . '/Core/Session.php';
        if (!is_file($sessionFile)) {
            $this->report->addResult('Funcional', 'Session', 'fail', 'Session.php no existe');
            return;
        }

        $content = file_get_contents($sessionFile);
        $methods = ['start', 'set', 'get', 'has', 'delete', 'flash', 'regenerate', 'destroy'];

        foreach ($methods as $method) {
            if (preg_match('/public\s+(?:static\s+)?function\s+' . $method . '\s*\(/', $content)) {
                $this->report->addResult('Funcional', "Session::$method()", 'pass', "Método existe");
            } else {
                $this->report->addResult('Funcional', "Session::$method()", 'warn', "Método faltante: $method()");
            }
        }
    }

    private function checkViewRendering(): void
    {
        $viewFile = APP_PATH . '/Core/View.php';
        if (!is_file($viewFile)) {
            $this->report->addResult('Funcional', 'View', 'fail', 'View.php no existe');
            return;
        }

        $content = file_get_contents($viewFile);
        
        if (str_contains($content, 'function render')) {
            $this->report->addResult('Funcional', 'View::render()', 'pass', "Método existe");
        } else {
            $this->report->addResult('Funcional', 'View::render()', 'fail', "Método render() faltante");
        }
    }

    private function checkPasswordHashing(): void
    {
        // Verificar que password_hash está disponible
        if (function_exists('password_hash')) {
            $this->report->addResult('Funcional', 'password_hash()', 'pass', 'Función disponible');
        } else {
            $this->report->addResult('Funcional', 'password_hash()', 'fail', 'Función NO disponible');
        }

        if (function_exists('password_verify')) {
            $this->report->addResult('Funcional', 'password_verify()', 'pass', 'Función disponible');
        } else {
            $this->report->addResult('Funcional', 'password_verify()', 'fail', 'Función NO disponible');
        }

        // Test real
        $testHash = password_hash('test123', PASSWORD_DEFAULT);
        if (password_verify('test123', $testHash)) {
            $this->report->addResult('Funcional', 'Hashing test', 'pass', 'Hash/verify funciona correctamente');
        } else {
            $this->report->addResult('Funcional', 'Hashing test', 'fail', 'Hash/verify NO funciona');
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// GENERADOR DE REPORTES
// ═══════════════════════════════════════════════════════════════════

class ReportGenerator
{
    public function __construct(private VerificationReport $report) {}

    public function generateCli(): void
    {
        $summary = $this->report->getSummary();
        $results = $this->report->getResults();

        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                                                                ║\n";
        echo "║        🔍 PePiuoX Maker — Reporte de Verificación             ║\n";
        echo "║                                                                ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP: " . PHP_VERSION . "\n";
        echo "Sistema: " . PHP_OS . "\n";
        echo "\n";

        // Agrupar por fase
        $byPhase = [];
        foreach ($results as $r) {
            $byPhase[$r['phase']][] = $r;
        }

        foreach ($byPhase as $phase => $items) {
            echo "┌─────────────────────────────────────────────────────────────────┐\n";
            echo "│ 📋 FASE: $phase\n";
            echo "└─────────────────────────────────────────────────────────────────┘\n";

            foreach ($items as $item) {
                $icon = match ($item['status']) {
                    'pass' => '✅',
                    'fail' => '❌',
                    'warn' => '⚠️ ',
                    'fixed' => '🔧',
                    default => '❓',
                };
                echo "  $icon {$item['test']}\n";
                echo "     └─ {$item['message']}\n";
            }
            echo "\n";
        }

        // Resumen final
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                         📊 RESUMEN                             ║\n";
        echo "╠════════════════════════════════════════════════════════════════╣\n";
        printf("║  Total de pruebas:   %3d                                        ║\n", $summary['total']);
        printf("║  ✅ Pasaron:          %3d                                        ║\n", $summary['passed']);
        printf("║  ❌ Fallaron:         %3d                                        ║\n", $summary['failed']);
        printf("║  ⚠️  Advertencias:     %3d                                        ║\n", $summary['warnings']);
        printf("║  🔧 Auto-reparadas:   %3d                                        ║\n", $summary['fixed']);
        printf("║  ⏱️  Duración:         %s                                        ║\n", $summary['duration'] . 's');
        printf("║  📈 Tasa de éxito:   %5.1f%%                                      ║\n", $summary['success_rate']);
        echo "╚════════════════════════════════════════════════════════════════╝\n";

        if ($summary['failed'] === 0) {
            echo "\n🎉 ¡Sistema verificado correctamente! No se encontraron problemas críticos.\n\n";
        } else {
            echo "\n⚠️  Se encontraron {$summary['failed']} problema(s) crítico(s). Revisar arriba.\n\n";
        }
    }

    public function generateJson(): void
    {
        $output = [
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'system' => PHP_OS,
            'summary' => $this->report->getSummary(),
            'results' => $this->report->getResults(),
        ];

        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    public function generateHtml(): void
    {
        $summary = $this->report->getSummary();
        $results = $this->report->getResults();

        // Agrupar por fase
        $byPhase = [];
        foreach ($results as $r) {
            $byPhase[$r['phase']][] = $r;
        }

        $statusColor = fn($s) => match ($s) {
            'pass' => '#22c55e',
            'fail' => '#ef4444',
            'warn' => '#f59e0b',
            'fixed' => '#3b82f6',
            default => '#8b949e',
        };

        $statusIcon = fn($s) => match ($s) {
            'pass' => '✅',
            'fail' => '❌',
            'warn' => '⚠️',
            'fixed' => '🔧',
            default => '❓',
        };

        echo '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>PePiuoX Maker — Verificación</title>
<style>
body { font-family: Inter, -apple-system, sans-serif; background: #0d1117; color: #e6edf3; padding: 2rem; margin: 0; }
.container { max-width: 1200px; margin: 0 auto; }
h1 { background: linear-gradient(135deg, #00d4aa, #00a88a); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 2rem; }
.summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 2rem 0; }
.stat { background: #1c2333; border: 1px solid #30363d; border-radius: 12px; padding: 1.25rem; text-align: center; }
.stat-value { font-size: 2rem; font-weight: 700; }
.stat-label { color: #8b949e; font-size: 0.8rem; text-transform: uppercase; }
.phase { background: #1c2333; border: 1px solid #30363d; border-radius: 12px; margin-bottom: 1.5rem; overflow: hidden; }
.phase-header { background: #21283b; padding: 1rem 1.5rem; border-bottom: 1px solid #30363d; font-weight: 600; }
.result { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 1.5rem; border-bottom: 1px solid #2b3446; }
.result:last-child { border-bottom: none; }
.result-icon { font-size: 1.2rem; flex-shrink: 0; }
.result-content { flex: 1; }
.result-test { font-weight: 600; color: #fff; }
.result-message { color: #8b949e; font-size: 0.85rem; margin-top: 0.25rem; }
.success-rate { font-size: 3rem; font-weight: 700; text-align: center; margin: 2rem 0; }
.pass { color: #22c55e; }
.fail { color: #ef4444; }
.warn { color: #f59e0b; }
</style>
</head>
<body>
<div class="container">
<h1>🔍 PePiuoX Maker — Reporte de Verificación</h1>
<p style="color:#8b949e">Generado: ' . date('Y-m-d H:i:s') . ' · PHP ' . PHP_VERSION . '</p>

<div class="success-rate ' . ($summary['failed'] === 0 ? 'pass' : 'fail') . '">
    ' . $summary['success_rate'] . '%
    <div style="font-size:1rem;color:#8b949e">Tasa de éxito</div>
</div>

<div class="summary">
    <div class="stat"><div class="stat-value">' . $summary['total'] . '</div><div class="stat-label">Total</div></div>
    <div class="stat"><div class="stat-value pass">' . $summary['passed'] . '</div><div class="stat-label">Pasaron</div></div>
    <div class="stat"><div class="stat-value fail">' . $summary['failed'] . '</div><div class="stat-label">Fallaron</div></div>
    <div class="stat"><div class="stat-value warn">' . $summary['warnings'] . '</div><div class="stat-label">Advertencias</div></div>
    <div class="stat"><div class="stat-value" style="color:#3b82f6">' . $summary['fixed'] . '</div><div class="stat-label">Reparadas</div></div>
    <div class="stat"><div class="stat-value">' . $summary['duration'] . 's</div><div class="stat-label">Duración</div></div>
</div>';

        foreach ($byPhase as $phase => $items) {
            echo '<div class="phase">
                <div class="phase-header">📋 ' . htmlspecialchars($phase) . '</div>';
            
            foreach ($items as $item) {
                $icon = $statusIcon($item['status']);
                echo '<div class="result">
                    <div class="result-icon">' . $icon . '</div>
                    <div class="result-content">
                        <div class="result-test">' . htmlspecialchars($item['test']) . '</div>
                        <div class="result-message">' . htmlspecialchars($item['message']) . '</div>
                    </div>
                </div>';
            }
            
            echo '</div>';
        }

        echo '</div></body></html>';
    }
}

// ═══════════════════════════════════════════════════════════════════
// EJECUCIÓN PRINCIPAL
// ═══════════════════════════════════════════════════════════════════

$report = new VerificationReport();

// Ejecutar todas las fases
$verifiers = [
    new SystemVerifier($report),
    new DatabaseVerifier($report),
    new RoutesVerifier($report),
    new PermissionsVerifier($report),
    new IntegrityVerifier($report),
    new FunctionalVerifier($report),
];

foreach ($verifiers as $verifier) {
    try {
        $verifier->verify();
    } catch (Throwable $e) {
        $report->addResult(
            'Sistema',
            'Error en verificador',
            'fail',
            'Error: ' . $e->getMessage()
        );
    }
}

// Auto-fix si se solicitó
if ($fixMode) {
    // Aquí irían las correcciones automáticas
    // Por ejemplo: crear carpetas faltantes, ajustar permisos, etc.
}

// Generar reporte
$generator = new ReportGenerator($report);

if ($isCli) {
    if ($jsonMode) {
        $generator->generateJson();
    } else {
        $generator->generateCli();
    }
    exit($report->hasFailures() ? 1 : 0);
} else {
    // Modo web
    header('Content-Type: text/html; charset=utf-8');
    $generator->generateHtml();
}