<?php
namespace App\Core;

/**
 * Router principal de la aplicación
 * 
 * @package App\Core
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Registra una ruta GET
     */
    public function get(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    /**
     * Registra una ruta POST
     */
    public function post(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    /**
     * Registra una ruta que acepta GET y POST
     */
    public function any(string $path, $handler, array $middlewares = []): self
    {
        $this->add('GET', $path, $handler, $middlewares);
        $this->add('POST', $path, $handler, $middlewares);
        return $this;
    }

    /**
     * Registra una ruta con método específico
     */
    private function add(string $method, string $path, $handler, array $middlewares): self
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares');
        return $this;
    }

    /**
     * Registra un middleware global
     */
    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Despacha la petición
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $this->normalizeUri($request->uri());

        // Ejecutar middlewares globales
        foreach ($this->middlewares as $middleware) {
            $middleware($request);
        }

        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->toPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                // Ejecutar middlewares de la ruta
                foreach ($route['middlewares'] as $mw) {
                    $this->executeMiddleware($mw, $request);
                }

                // Extraer parámetros
                $params = $this->extractParams($matches);

                // Ejecutar handler
                $this->executeHandler($route['handler'], $request, $params);
                return;
            }
        }

        // No se encontró ruta
        $this->handleNotFound();
    }

    /**
     * Normaliza la URI
     */
    private function normalizeUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '';
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Convierte una ruta a patrón regex
     */
    private function toPattern(string $path): string
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $path);

        return '#^' . $pattern . '$#';
    }

    /**
     * Extrae parámetros de los matches
     */
    private function extractParams(array $matches): array
    {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $this->sanitizeParam($value);
            }
        }
        return $params;
    }

    /**
     * Sanitiza un parámetro
     */
    private function sanitizeParam($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Ejecuta un middleware
     */
    private function executeMiddleware($mw, Request $request): void
    {
        if (is_object($mw)) {
            // Objeto ya instanciado
            $mw->handle($request);
        } elseif (is_string($mw) && class_exists($mw)) {
            // Clase con método handle
            (new $mw())->handle($request);
        } elseif (is_array($mw) && count($mw) === 2) {
            // [Clase::class, 'metodo']
            [$class, $method] = $mw;
            if (is_string($class)) {
                (new $class())->$method($request);
            } else {
                $class->$method($request);
            }
        } elseif (is_callable($mw)) {
            // Callable
            $mw($request);
        } else {
            throw new \RuntimeException('Middleware inválido: ' . gettype($mw));
        }
    }

    /**
     * Ejecuta el handler
     */
    private function executeHandler($handler, Request $request, array $params): void
    {
        if (is_array($handler)) {
            // [Controller::class, 'method']
            [$class, $method] = $handler;
            if (is_string($class)) {
                (new $class())->$method($request, $params);
            } else {
                $class->$method($request, $params);
            }
        } elseif (is_callable($handler)) {
            // Callable
            $handler($request, $params);
        } else {
            throw new \RuntimeException('Handler inválido');
        }
    }

    /**
     * Maneja rutas no encontradas
     */
    private function handleNotFound(): void
    {
        Response::abort(404, 'Página no encontrada');
    }

    /**
     * Obtiene todas las rutas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Verifica si una ruta existe
     */
    public function hasRoute(string $path, string $method = 'GET'): bool
    {
        $uri = $this->normalizeUri($path);
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = $this->toPattern($route['path']);
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera una URL a partir del nombre de la ruta
     */
    public function url(string $name, array $params = []): string
    {
        // Buscar ruta por nombre (si se soporta)
        foreach ($this->routes as $route) {
            if (isset($route['name']) && $route['name'] === $name) {
                $path = $route['path'];
                foreach ($params as $key => $value) {
                    $path = str_replace('{' . $key . '}', $value, $path);
                }
                return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
            }
        }
        return rtrim(BASE_URL, '/') . '/' . ltrim($name, '/');
    }
}