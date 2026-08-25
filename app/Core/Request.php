<?php
namespace App\Core;

class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Eliminar string de consulta (?param=1)
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Normalizar slashes para Windows/Linux
        $uri = str_replace('\\', '/', $uri);
        
        // Obtener el directorio base del script (ej: '/public' o '/public/')
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        
        // Si la URI empieza con el base (ej: '/public/admin'), lo quitamos
        if ($base !== '/' && $base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        
        // Asegurar que siempre empiece con '/' y no termine con '/' (excepto si es la raíz)
        $uri = '/' . ltrim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');
        
        return $uri;
    }

    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function isPost(): bool   { return $this->method() === 'POST'; }
    public function isAjax(): bool   { return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'; }
    public function ip(): string     { return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function userAgent(): string { return $_SERVER['HTTP_USER_AGENT'] ?? ''; }

    /** JSON body (para APIs AJAX). */
    public function json(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    public static ?array $apiContext = null;
    public function getHeader(string $name): ?string {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }
}