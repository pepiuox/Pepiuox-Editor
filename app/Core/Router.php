<?php
namespace App\Core;

use Throwable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Router principal con Inyección de Dependencias y control de flujo
 *
 * Dependencias:
 * - Request (App\Core\Request)
 * - Response (App\Core\Response)
 *
 * @package App\Core
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $routeCache = [];

    /** @var callable|null Contenedor DI externo */
    private $resolver = null;

    /** @var string Prefijo base para todas las rutas */
    private string $basePath = '';

    /**
     * Mapa `Controlador::método` → permiso exigido.
     *
     * Existe porque `PermissionMiddleware` no estaba aplicado en ninguna ruta y
     * anotar ~150 declaraciones a mano se desactualiza en el primer cambio. Aquí
     * se consulta **después** de resolver la ruta, cuando ya se sabe qué acción
     * va a ejecutarse. Ver `config/permissions.php`.
     *
     * @var array<string, string>
     */
    private array $permissionMap = [];

    /**
     * Rutas del panel ejecutadas sin permiso declarado, para poder medir el hueco.
     *
     * @var list<string>
     */
    private array $unmappedAdminRoutes = [];

    /**
     * Establece el mapa de permisos por acción.
     *
     * @param array<string, string> $map Claves `Controlador::método`, valores permiso.
     */
    public function setPermissionMap(array $map): void
    {
        $this->permissionMap = $map;
    }

    /**
     * Acciones del panel ejecutadas sin permiso declarado en el mapa.
     *
     * @return list<string>
     */
    public function unmappedAdminRoutes(): array
    {
        return $this->unmappedAdminRoutes;
    }

    // ──────────────────────────────────────────────────────────────
    //  CONFIGURACIÓN
    // ──────────────────────────────────────────────────────────────

    /**
     * Establece un contenedor de resolución de dependencias
     */
    public function setResolver(callable $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * Establece un prefijo base (ej: '/api/v1')
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    // ──────────────────────────────────────────────────────────────
    //  REGISTRO DE RUTAS
    // ──────────────────────────────────────────────────────────────

    public function get(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('DELETE', $path, $handler, $middlewares);
    }

    public function any(string $path, $handler, array $middlewares = []): self
    {
        $this->add('GET', $path, $handler, $middlewares);
        $this->add('POST', $path, $handler, $middlewares);
        return $this;
    }

    private function add(string $method, string $path, $handler, array $middlewares): self
    {
        $fullPath = $this->basePath . $path;
        $this->routes[] = compact('method', 'fullPath', 'handler', 'middlewares');
        $this->routeCache = [];
        return $this;
    }

    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    // ──────────────────────────────────────────────────────────────
    //  DESPACHO
    // ──────────────────────────────────────────────────────────────

    public function dispatch(Request $request): void
    {
        try {
            $method = $request->method();
            $uri = $request->uri(); // ✅ Ya normalizado por Request

            // Middlewares globales
            foreach ($this->middlewares as $middleware) {
                if ($this->executeMiddleware($middleware, $request) === false) {
                    return;
                }
            }

            // Buscar ruta
            $matched = $this->findRoute($method, $uri);
            if (!$matched) {
                Response::abort(404, "Ruta no encontrada: {$uri}");
                return;
            }

            [$route, $params] = $matched;

            // Middlewares de ruta primero: `AuthMiddleware` redirige al login a
            // quien no tiene sesión. Si el permiso se comprobara antes, un
            // visitante anónimo recibiría un 403 en lugar del formulario de
            // acceso, y además sabría que la ruta existe.
            foreach ($route['middlewares'] as $mw) {
                if ($this->executeMiddleware($mw, $request) === false) {
                    return;
                }
            }

            // Permisos por acción: ya sabemos que hay sesión y qué se va a
            // ejecutar. Antes de ejecutarlo.
            $this->enforcePermission($route['handler'], $request, $uri);

            // Ejecutar handler
            $this->executeHandler($route['handler'], $request, $params);

        } catch (Throwable $e) {
            $this->handleException($e, $request);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  BÚSQUEDA DE RUTAS
    // ──────────────────────────────────────────────────────────────

    private function findRoute(string $method, string $uri): ?array
    {
        $cacheKey = $method . ':' . $uri;
        if (isset($this->routeCache[$cacheKey])) {
            return $this->routeCache[$cacheKey];
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->toPattern($route['fullPath']);
            if (preg_match($pattern, $uri, $matches)) {
                $params = $this->extractParams($matches);
                $result = [$route, $params];
                $this->routeCache[$cacheKey] = $result;
                return $result;
            }
        }

        return null;
    }

    private function toPattern(string $path): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            fn($m) => '(?P<' . $m[1] . '>[^/]+)',
            $path
        );
        return '#^' . $pattern . '$#';
    }

    private function extractParams(array $matches): array
    {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = ctype_digit((string)$value) ? (int)$value : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $params;
    }

    // ──────────────────────────────────────────────────────────────
    //  MIDDLEWARES
    // ──────────────────────────────────────────────────────────────

    /**
     * Ejecuta un middleware. Retorna false para detener la cadena.
     */
    private function executeMiddleware($mw, Request $request)
    {
        try {
            if (is_object($mw)) {
                return $mw->handle($request);
            }
            if (is_string($mw) && class_exists($mw)) {
                return $this->resolve($mw)->handle($request);
            }
            if (is_array($mw) && count($mw) === 2) {
                [$class, $method] = $mw;
                $instance = is_string($class) ? $this->resolve($class) : $class;
                return $instance->$method($request);
            }
            if (is_callable($mw)) {
                return $mw($request);
            }
            throw new \RuntimeException('Middleware inválido: ' . gettype($mw));
        } catch (Throwable $e) {
            error_log('[Router] Middleware error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exige el permiso declarado para la acción que va a ejecutarse.
     *
     * Si la acción no está en el mapa no se exige nada adicional (comportamiento
     * anterior); las acciones del panel sin mapear se guardan en memoria y se
     * registran en el log, para que el hueco se pueda medir en lugar de quedar
     * invisible. Ver `tests/seguridad/check.php`.
     *
     * @param mixed $handler Handler de la ruta resuelta.
     */
    private function enforcePermission($handler, Request $request, string $uri): void
    {
        if (!is_array($handler) || count($handler) !== 2 || !is_string($handler[0])) {
            return; // Cierres y otros handlers sin clase: fuera del alcance del mapa.
        }

        $clave   = $handler[0] . '::' . (string) $handler[1];
        $permiso = $this->permissionMap[$clave] ?? null;

        if ($permiso === null) {
            if (str_starts_with($uri, '/admin')) {
                $this->unmappedAdminRoutes[] = $clave;
                error_log('[Permisos] Acción de panel sin permiso declarado: ' . $clave . ' (' . $uri . ')');
            }

            return;
        }

        (new \App\Middleware\PermissionMiddleware($permiso))->handle($request);
    }

    // ──────────────────────────────────────────────────────────────
    //  INYECCIÓN DE DEPENDENCIAS
    // ──────────────────────────────────────────────────────────────

    /**
     * ✅ Sanitiza preservando el tipo string
     * El controlador decide si necesita (int) o (string)
     */
    private function sanitizeParam($value)
    {
        // Sanitizar SIEMPRE como string
        $sanitized = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        
        // Si el valor es puramente numérico, marcarlo para que el Router
        // pueda convertirlo a int si el método lo requiere
        // Pero NUNCA forzar la conversión aquí
        return $sanitized;
    }

    /**
     * ✅ En callMethod, convertir a int si el parámetro es tipado como int
     *
     * Llama al método del controlador respetando su firma exacta
     */
    
    private function callMethod(object $instance, string $method, Request $request, array $params): void
    {
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Método '{$method}' no existe en " . get_class($instance));
        }

        $ref = new ReflectionMethod($instance, $method);
        $args = [];

        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Inyectar Request por tipo
            if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
                continue;
            }

            // Inyectar array $params por nombre o tipo
            if ($name === 'params' || ($type && $type->getName() === 'array' && $name !== 'data')) {
                $args[] = $params;
                continue;
            }

            // Buscar en params de la ruta
            if (array_key_exists($name, $params)) {
                $value = $params[$name];
                
                // ✅ Conversión de tipo según la firma del método
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $value = $this->castToType($value, $type->getName());
                }
                
                $args[] = $value;
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $args[] = null;
            } else {
                throw new \RuntimeException("Parámetro '{$name}' requerido en " . get_class($instance) . "::{$method}()");
            }
        }

        $ref->invokeArgs($instance, $args);
    }

    /**
     * ✅ Convierte un valor al tipo esperado por el método
     */
    private function castToType($value, string $typeName)
    {
        return match ($typeName) {
            'int'     => (int)$value,
            'float'   => (float)$value,
            'bool'    => (bool)$value,
            'string'  => (string)$value,
            'array'   => (array)$value,
            default   => $value,
        };
    }

    private function resolve(string $class)
    {
        if ($this->resolver) {
            return ($this->resolver)($class);
        }
        return $this->autoResolve($class);
    }

    private function autoResolve(string $class)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Clase no encontrada: {$class}");
        }

        $ref = new ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if (!$ctor) {
            return new $class();
        }

        $deps = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $deps[] = $this->autoResolve($type->getName());
                    continue;
                } catch (Throwable $e) {
                    // Fallback a default/null
                }
            }

            if ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $deps[] = null;
            } else {
                throw new \RuntimeException(
                    "No se puede resolver '{$param->getName()}' en {$class}"
                );
            }
        }

        return $ref->newInstanceArgs($deps);
    }

    // ──────────────────────────────────────────────────────────────
    //  EJECUCIÓN DE HANDLERS
    // ──────────────────────────────────────────────────────────────

    private function executeHandler($handler, Request $request, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = is_string($class) ? $this->resolve($class) : $class;
            $this->callMethod($instance, $method, $request, $params);
        } elseif (is_callable($handler)) {
            $handler($request, $params);
        } else {
            throw new \RuntimeException('Handler inválido');
        }
    }

   

    // ──────────────────────────────────────────────────────────────
    //  MANEJO DE ERRORES
    // ──────────────────────────────────────────────────────────────

    private function handleException(Throwable $e, Request $request): void
    {
        error_log('[Router] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

        $code = $e->getCode();
        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        if ($request->isAjax()) {
            Response::json([
                'success' => false,
                'error' => 'Error interno del servidor',
            ], $code);
        } else {
            Response::abort($code, $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  UTILIDADES
    // ──────────────────────────────────────────────────────────────

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function hasRoute(string $path, string $method = 'GET'): bool
    {
        $uri = '/' . ltrim($path, '/');
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($this->toPattern($route['fullPath']), $uri)) {
                return true;
            }
        }
        return false;
    }

    public function url(string $name, array $params = []): string
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        foreach ($this->routes as $route) {
            if (isset($route['name']) && $route['name'] === $name) {
                $path = $route['fullPath'];
                foreach ($params as $key => $value) {
                    $path = str_replace('{' . $key . '}', (string)$value, $path);
                }
                return $base . '/' . ltrim($path, '/');
            }
        }
        return $base . '/' . ltrim($name, '/');
    }
}