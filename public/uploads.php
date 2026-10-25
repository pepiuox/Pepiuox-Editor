<?php
/**
 * public/uploads.php
 *
 * Sirve los ficheros subidos que viven en ROOT/uploads.
 *
 * Por qué existe: la carpeta `uploads/` está fuera del document root a
 * propósito (no se puede ejecutar código subido), pero las URLs que genera la
 * aplicación —`url('uploads/...')`, BASE_URL incluido— apuntan a
 * `<BASE_URL>/uploads/...`. Sin este punto de servicio esas URLs daban 404 y
 * por eso el editor visual no mostraba las imágenes cargadas.
 *
 * Lo alcanza la regla de reescritura de `.htaccess` (raíz del proyecto):
 *   RewriteRule ^uploads/(.+)$        public/uploads.php [L,QSA]
 *   RewriteRule ^public/uploads/(.+)$ public/uploads.php [L,QSA]
 * La segunda cubre las URLs históricas que ya están guardadas dentro de
 * `pages.content_html` y `pages.grapesjs_data`.
 *
 * Seguridad:
 *  - Solo extensiones de imagen de la lista blanca.
 *  - Rechaza `..`, bytes nulos y barras invertidas en la ruta.
 *  - El destino se resuelve con realpath() y debe quedar dentro de UPLOADS_PATH.
 *  - `X-Content-Type-Options: nosniff`; los SVG se sirven con CSP `sandbox`
 *    para que un SVG con scripts no ejecute código en el origen del editor.
 *
 * @package Public
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/paths.php';

/**
 * Corta la petición con un error en texto plano.
 *
 * @param int    $code    Código HTTP
 * @param string $message Mensaje para el cliente
 * @return never
 */
function uploads_abort(int $code, string $message): void
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo $message;
    exit;
}

$contentTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
];

$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$requestPath = rawurldecode($requestPath);

$marker = '/uploads/';
$position = strpos($requestPath, $marker);
if ($position === false) {
    uploads_abort(404, 'No encontrado');
}

$relative = ltrim(substr($requestPath, $position + strlen($marker)), '/');

if ($relative === ''
    || str_contains($relative, "\0")
    || str_contains($relative, '\\')
    || preg_match('#(^|/)\.\.(/|$)#', $relative) === 1) {
    uploads_abort(404, 'No encontrado');
}

$extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
if (!isset($contentTypes[$extension])) {
    uploads_abort(404, 'No encontrado');
}

$baseDir = realpath(UPLOADS_PATH);
$target  = realpath(UPLOADS_PATH . '/' . $relative);

if ($baseDir === false || $target === false || !is_file($target)) {
    uploads_abort(404, 'No encontrado');
}

$baseDir = str_replace('\\', '/', $baseDir);
$target  = str_replace('\\', '/', $target);
if (!str_starts_with($target, $baseDir . '/')) {
    uploads_abort(404, 'No encontrado');
}

$mtime = (int) filemtime($target);
$size  = (int) filesize($target);
$etag  = '"' . md5($relative . '-' . $mtime . '-' . $size) . '"';

// Respuestas condicionales: evita reenviar imágenes ya cacheadas por el navegador.
$ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
    http_response_code(304);
    exit;
}

$ifModifiedSince = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
if ($ifModifiedSince !== '' && strtotime($ifModifiedSince) >= $mtime) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . $size);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

if ($extension === 'svg') {
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}

readfile($target);
