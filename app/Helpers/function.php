<?php
// Helpers globales para el sistema

/**
 * Obtiene el valor de una variable, con valor por defecto si no existe
 */
function get($key, $default = null)
{
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

/**
 * Escapa HTML de forma segura
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Escapa HTML para atributos
 */
function ha(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirige a una URL
 */
function redirect(string $url, int $code = 302): void
{
    if ($code >= 300 && $code < 400) {
        http_response_code($code);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Verifica si existe un método POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Verifica si existe un método GET
 */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Obtiene la URL base del sitio
 */
function baseUrl(string $path = ''): string
{
    return rtrim(BASE_URL ?? '', '/') . '/' . ltrim($path, '/');
}

/**
 * Obtiene la URL actual
 */
function currentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Sanitiza un slug para URLs
 */
function sanitizeSlug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Valida y sanitiza datos de entrada
 */
function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // Eliminar espacios al inicio y final
    $input = trim($input);
    
    // Convertir caracteres especiales a entidades HTML
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

/**
 * Genera un token CSRF
 */
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log de depuración (solo en desarrollo)
 */
function debugLog(string $message, array $data = [])
{
    if (defined('DEBUG') && DEBUG === true) {
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($data)) {
            $logEntry .= ': ' . print_r($data, true);
        }
        error_log($logEntry);
    }
}