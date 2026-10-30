<?php
namespace App\Core;

/**
 * Clase Request: encapsula la petición HTTP
 *
 * Soporta lectura transparente de datos desde:
 * - $_GET (query string)
 * - $_POST (form-urlencoded / multipart)
 * - php://input (JSON body)
 *
 * @package App\Core
 */
class Request
{
    /** @var array|null Cache del body JSON parseado */
    private ?array $jsonBody = null;

    /** @var bool Flag para saber si el body ya fue leído */
    private bool $jsonParsed = false;

    /** @var array|null Contexto API (autenticación, etc.) */
    public static ?array $apiContext = null;

    // ──────────────────────────────────────────────────────────────
    //  MÉTODOS HTTP
    // ──────────────────────────────────────────────────────────────

    public function method(): string
    {
        // Soporte para method override (formularios HTML)
        $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
        if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'])) {
            return strtoupper($override);
        }
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    // ──────────────────────────────────────────────────────────────
    //  URI
    // ──────────────────────────────────────────────────────────────

    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Eliminar query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Normalizar slashes
        $uri = str_replace('\\', '/', $uri);

        // Quitar el directorio base del script (cuando la app vive en un
        // subdirectorio). La comparación es por SEGMENTO de ruta, no por texto:
        // con un `str_starts_with` a secas, `/public-theme.css` se recortaba a
        // `/-theme.css` porque «/public» es prefijo de «/public-…», y la ruta
        // del CSS dinámico acababa cayendo en el 404 del catch-all.
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && $base !== '/' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
            $uri = substr($uri, strlen($base));
        }

        // Normalizar: siempre empieza con '/', nunca termina con '/' (excepto raíz)
        $uri = '/' . ltrim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    // ──────────────────────────────────────────────────────────────
    //  LECTURA DE DATOS (GET / POST / JSON)
    // ──────────────────────────────────────────────────────────────

    /**
     * Lee un parámetro de la query string
     */
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Lee un parámetro del body (POST form o JSON)
     *
     * ✅ CORREGIDO: Hace fallback automático al body JSON
     * cuando Content-Type es application/json
     */
    public function post(string $key, $default = null)
    {
        // 1. Intentar $_POST (form-urlencoded / multipart)
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        // 2. Fallback al body JSON
        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }

        return $default;
    }

    /**
     * Lee un parámetro de cualquier fuente (POST > GET > JSON)
     */
    public function input(string $key, $default = null)
    {
        return $this->post($key) ?? $this->get($key) ?? $default;
    }

    /**
     * Retorna todos los datos de la petición
     */
    public function all(): array
    {
        return array_merge($this->json(), $_GET, $_POST);
    }

    /**
     * Retorna solo los datos del body JSON
     *
     * ✅ Cachea el resultado para no leer php://input múltiples veces
     */
    public function json(): array
    {
        if (!$this->jsonParsed) {
            $this->jsonParsed = true;
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            // Solo parsear si el Content-Type es JSON
            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                $data = json_decode($raw, true);
                $this->jsonBody = is_array($data) ? $data : [];
            } else {
                $this->jsonBody = [];
            }
        }
        return $this->jsonBody;
    }

    // ──────────────────────────────────────────────────────────────
    //  HEADERS Y METADATOS
    // ──────────────────────────────────────────────────────────────

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    }

    public function isJson(): bool
    {
        return stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }

    public function ip(): string
    {
        // Priorizar X-Forwarded-For (proxy/CDN)
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            return $ips[0]; // Primera IP = cliente real
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function getHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    /**
     * Obtiene el token CSRF desde headers o POST
     */
    public function csrfToken(): ?string
    {
        return $this->getHeader('X-CSRF-TOKEN')
            ?? $this->post('_csrf_token')
            ?? $this->get('_csrf_token');
    }
}