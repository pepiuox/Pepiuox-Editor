<?php
namespace App\Middleware;

use App\Core\{Request, Response, Session, Database, Auth};
use App\Services\RateLimiter;

class RateLimitMiddleware
{
    private string $prefix;
    private int $maxAttempts;
    private int $decaySeconds;
    private string $identifier;

    /**
     * @param string $prefix Prefijo de la clave (ej: "login", "forgot")
     * @param int $maxAttempts Máximo de intentos permitidos
     * @param int $decaySeconds Ventana de tiempo en segundos
     * @param string $identifier Cómo identificar al usuario ('ip', 'ip+user', 'user')
     */
    public function __construct(
        string $prefix,
        int $maxAttempts = 5,
        int $decaySeconds = 60,
        string $identifier = 'ip'
    ) {
        $this->prefix = $prefix;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->identifier = $identifier;
    }

    public function handle(Request $request): void
    {
        try {
            $key = $this->buildKey($request);
            
            // Verificar si ya está limitado
            if (RateLimiter::tooManyAttempts($key, $this->maxAttempts, $this->decaySeconds)) {
                $retryAfter = RateLimiter::availableIn($key);
                
                // Registrar en auditoría
                $this->logBlockedAttempt($request, $key);
                
                // Responder según el tipo de petición
                if ($request->isAjax() || str_starts_with($request->uri(), '/api/')) {
                    Response::json([
                        'success' => false,
                        'message' => 'Demasiados intentos. Inténtalo de nuevo en ' . $retryAfter . ' segundos.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => $retryAfter,
                    ], 429);
                }
                
                // Para peticiones normales, redirigir con mensaje
                Session::flash(
                    'flash.error',
                    "Demasiados intentos. Inténtalo de nuevo en $retryAfter segundos."
                );
                Response::redirect($request->uri());
            }
            
            // Registrar el intento
            $result = RateLimiter::attempt($key, $this->maxAttempts, $this->decaySeconds);
            
            // Añadir headers de rate limit a la respuesta
            header('X-RateLimit-Limit: ' . $this->maxAttempts);
            header('X-RateLimit-Remaining: ' . $result['remaining']);
            header('X-RateLimit-Reset: ' . (time() + $this->decaySeconds));
            
            if ($result['limited']) {
                header('Retry-After: ' . $result['retry_after']);
            }
            
        } catch (\Throwable $e) {
            // Se deja pasar la petición, y se dice en el log con esas palabras.
            //
            // Por qué aquí se falla en abierto y en la construcción en cerrado:
            // este `catch` se dispara cuando el almacenamiento del contador no
            // responde, y si la base de datos no responde la aplicación entera
            // está caída (no hay sesión, no hay usuario, no hay contraseña que
            // comprobar). Bloquear el acceso aquí no protegería nada: solo
            // añadiría un error menos claro. La construcción sí se cierra,
            // porque ahí el fallo es de código y la ruta quedaría sin límite.
            error_log(
                '[RateLimit] No se pudo aplicar el límite (' . $this->prefix . '): '
                . $e->getMessage() . ' — se permite la petición.'
            );
        }
    }

    /**
     * Construye la clave única para rate limiting.
     */
    private function buildKey(Request $request): string
    {
        $parts = [];
        
        if ($this->identifier === 'ip' || $this->identifier === 'ip+user') {
            $parts[] = $request->ip();
        }
        
        if (($this->identifier === 'user' || $this->identifier === 'ip+user') && Auth::check()) {
            $parts[] = 'u' . Auth::id();
        }
        
        // Si no hay identificador, usar IP por defecto
        if (empty($parts)) {
            $parts[] = $request->ip();
        }
        
        return $this->prefix . ':' . md5(implode(':', $parts));
    }

    /**
     * Registra el intento bloqueado en auditoría.
     */
    private function logBlockedAttempt(Request $request, string $key): void
    {
        try {
            Database::insert(
                "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, request_method, request_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    Auth::id(),
                    'rate_limit_exceeded',
                    "Rate limit excedido: {$this->prefix} ({$this->maxAttempts}/{$this->decaySeconds}s)",
                    $request->ip(),
                    $request->userAgent(),
                    $request->method(),
                    $request->uri(),
                ]
            );
        } catch (\Throwable $e) {
            // Silencioso: no debe romper la respuesta
        }
    }
}