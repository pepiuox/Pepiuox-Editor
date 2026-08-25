<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): self
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    public function any(string $path, $handler, array $middlewares = []): self
    {
        $this->add('GET', $path, $handler, $middlewares);
        $this->add('POST', $path, $handler, $middlewares);
        return $this;
    }

    private function add(string $method, string $path, $handler, array $middlewares): self
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares');
        return $this;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = rtrim($request->uri(), '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->toPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {

                // ═══ MIDDLEWARES — SOPORTE COMPLETO ═══
                foreach ($route['middlewares'] as $mw) {
                    if (is_object($mw)) {
                        // Objeto ya instanciado: new RateLimitMiddleware(...)
                        $mw->handle($request);
                    } elseif (is_string($mw) && class_exists($mw)) {
                        // String con nombre de clase: AuthMiddleware::class
                        (new $mw())->handle($request);
                    } elseif (is_array($mw) && count($mw) === 2) {
                        // Array: [Clase::class, 'metodo']
                        [$class, $method] = $mw;
                        (new $class())->$method($request);
                    } else {
                        throw new \RuntimeException("Middleware inválido: " . gettype($mw));
                    }
                }

                // Params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Handler
                $h = $route['handler'];
                if (is_array($h)) {
                    [$class, $method] = $h;
                    (new $class())->$method($request, $params);
                } else {
                    $h($request, $params);
                }
                return;
            }
        }

        Response::abort(404, 'Página no encontrada');
    }

    private function toPattern(string $path): string
    {
        $p = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $p . '$#';
    }
}