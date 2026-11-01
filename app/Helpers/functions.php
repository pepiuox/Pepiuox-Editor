<?php
/**
 * Helpers globales para el sistema
 * Funciones de utilidad disponibles en toda la aplicación
 */

// ============================================
// FUNCIONES DE SANEAMIENTO Y ESCAPE
// ============================================

/**
 * Escapa HTML de forma segura para salida en pantalla
 * @param string|null $str Cadena a escapar
 * @return string Cadena escapada
 */
function e(?string $str): string 
{ 
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); 
}

/**
 * Escapa HTML para atributos HTML
 * @param string $str Cadena a escapar
 * @return string Cadena escapada para atributos
 */
function ha(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Obtiene el valor de una variable, con valor por defecto si no existe
 * @param string $key Clave del parámetro
 * @param mixed $default Valor por defecto
 * @return mixed Valor encontrado o default
 */
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

/**
 * Sanitiza un slug para URLs
 * @param string $slug Slug a sanitizar
 * @return string Slug sanitizado
 */
function sanitizeSlug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Valida y sanitiza datos de entrada recursivamente
 * @param mixed $input Dato a sanitizar
 * @return mixed Dato sanitizado
 */
function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    if (is_string($input)) {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

// ============================================
// FUNCIONES DE URL Y REDIRECCIÓN
// ============================================

/**
 * Genera una URL interna de la aplicación
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return rtrim(BASE_URL, '/') . '/' . $path;
}

/**
 * Genera una URL a un asset (CSS, JS, imágenes)
 * @param string $path Ruta del asset
 * @return string URL completa del asset
 */
function asset(string $path): string {
    $path = ltrim($path, '/');
    return rtrim(ASSETS_URL, '/') . '/' . $path;
}

/**
 * Obtiene la URL base del sitio
 * @param string $path Ruta adicional
 * @return string URL base
 */
function baseUrl(string $path = ''): string
{
    return rtrim(BASE_URL ?? '', '/') . '/' . ltrim($path, '/');
}

/**
 * Obtiene la URL actual
 * @return string URL actual
 */
function currentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Redirige a una URL
 * @param string $url URL de destino
 * @param int $code Código HTTP (302 por defecto)
 */
function redirect(string $url, int $code = 302): void
{
    if ($code >= 300 && $code < 400) {
        http_response_code($code);
    }
    header('Location: ' . $url);
    exit;
}

// ============================================
// FUNCIONES DE VERIFICACIÓN DE MÉTODOS
// ============================================

/**
 * Verifica si existe un método POST
 * @return bool
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Verifica si existe un método GET
 * @return bool
 */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// ============================================
// FUNCIONES DE CSRF (TOKEN)
// ============================================

/**
 * Genera el campo oculto CSRF para formularios
 * @return string HTML del campo oculto
 */
function csrf_field(): string {
    return \App\Core\CSRF::field();
}

/**
 * Devuelve el token CSRF actual (para usar en JavaScript)
 * @return string Token CSRF
 */
function csrf_token(): string {
    return \App\Core\CSRF::token();
}

/**
 * Valida el token CSRF de la petición actual
 */
function csrf_guard(): void {
    \App\Core\CSRF::guard();
}

/**
 * Verifica token CSRF recibido
 * @param string $token Token a verificar
 * @return bool
 */
function verifyCsrfToken(string $token): bool
{
    return \App\Core\CSRF::verify($token);
}

// ============================================
// FUNCIONES DE SESSION Y FLASH
// ============================================

/**
 * Obtiene un valor antiguo de formulario (para rellenar campos)
 * @param string $key Clave del campo
 * @param mixed $default Valor por defecto
 * @return string Valor escapado
 */
function old(string $key, $default = '') {
    return e($_POST[$key] ?? $default);
}

/**
 * Guarda un mensaje flash en sesión
 * @param string $type Tipo de mensaje (success, error, info, warning)
 * @param string $message Mensaje
 */
function flash(string $type, string $message): void
{
    \App\Core\Session::flash("flash.$type", $message);
}

/**
 * Obtiene todos los mensajes flash
 * @return array Array con los mensajes flash
 */
function get_flash(): array
{
    $out = [];
    foreach (['success','error','info','warning'] as $t) {
        $m = \App\Core\Session::getFlash("flash.$t");
        if ($m) $out[$t] = $m;
    }
    return $out;
}

// ============================================
// FUNCIONES DE NAVEGACIÓN Y ACTIVOS
// ============================================

/**
 * Determina si una ruta está activa (para menús)
 * @param string $path Ruta a comparar
 * @param string $class Clase CSS a retornar si está activa
 * @return string Clase CSS o cadena vacía
 */
function is_active(string $path, string $class = 'active'): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // Normalizar para comparación
    $uri = rtrim($uri, '/');
    $path = rtrim($path, '/');
    return str_starts_with($uri, $path) ? $class : '';
}

/**
 * Igual que `is_active`, pero exige coincidencia exacta del camino.
 *
 * Para qué: `is_active('/admin/settings')` se cumple también en
 * `/admin/settings/landing`, así que la entrada «Configuración» del menú se
 * encendía junto a la de la portada. Con esta variante, cada pantalla enciende
 * una sola entrada.
 *
 * @since 2026-10-31
 */
function is_active_exact(string $path, string $class = 'active'): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return rtrim($uri, '/') === rtrim($path, '/') ? $class : '';
}

/**
 * URLs de los ficheros de bloques del constructor visual, en orden.
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * Los tres editores visuales (páginas, plantillas y el pie de página) cargan los
 * bloques desde `public/assets/js/blocks/*.js`. Antes cada uno construía su lista
 * por su cuenta, así que el pie cargaba ficheros que los otros dos excluían y sus
 * bloques no se comportaban igual. La regla vive aquí una sola vez.
 *
 * ── Exclusiones, con motivo ─────────────────────────────────────────
 *   - `bs-blocks.js`: duplica cinco ids de `blocks-bs.js` (`bs-navbar`,
 *     `bs-hero`, `bs-footer`, `bs-accordion`, `bs-alert`) con contenido distinto.
 *     GrapesJS se queda con el primero que registra y el resto se pisa.
 *   - `template.js`: su único bloque lleva un `<script>` dentro del `content`, y
 *     ese contenido se ejecuta en el lienzo del editor.
 *
 * Los ficheros se ordenan alfabéticamente a propósito: el orden decide quién gana
 * cuando dos registran el mismo id.
 *
 * @return list<string> URLs absolutas, listas para pasar a `EditorKit.loadBlockScripts`.
 * @since 2026-10-31
 */
function editor_block_urls(): array {
    // Exclusiones, con el motivo al lado.
    //
    // OJO con los nombres: si un fichero se renombra y la lista sigue apuntando
    // al nombre viejo, la exclusión deja de aplicarse EN SILENCIO y el fichero
    // empieza a cargarse. Por eso `tests/paneles/menu.php` comprueba que cada
    // nombre excluido exista de verdad en disco.
    $excluidos = [
        // Inyecta un <script> con setTimeout dentro del contenido de un bloque;
        // ese código se ejecuta en el lienzo del editor. Es el mismo motivo por
        // el que se excluyó template.js antes de que se borrara.
        'bootstrap-blocks-consolidated.js',
    ];
    $nombres = [];

    foreach (glob(ASSETS_PATH . '/js/blocks/*.js') ?: [] as $archivo) {
        $nombre = basename($archivo);
        if (in_array($nombre, $excluidos, true)) {
            continue;
        }
        $nombres[] = $nombre;
    }

    sort($nombres);

    return array_map(static fn (string $n): string => asset('js/blocks/' . $n), $nombres);
}

// ============================================
// FUNCIONES DE PERMISOS Y AUTENTICACIÓN
// ============================================

/**
 * Verifica si el usuario tiene un permiso específico
 * @param string $permission Permiso a verificar
 * @return bool
 */
function can(string $permission): bool
{
    return \App\Core\Auth::can($permission);
}

// ============================================
// FUNCIONES DE TRADUCCIÓN
// ============================================

/**
 * Traduce una clave a texto en el idioma actual
 * @param string $key Clave de traducción
 * @param array $params Parámetros para reemplazo
 * @param string|null $lang Idioma específico (opcional)
 * @return string Texto traducido
 */
function t(string $key, array $params = [], ?string $lang = null): string
{
    return \App\Services\TranslationService::get($key, $params, $lang);
}

/**
 * Obtiene el idioma actual
 * @return string Código del idioma
 */
function current_lang(): string
{
    return \App\Services\TranslationService::getLang();
}

// ============================================
// FUNCIONES DE DEPURACIÓN
// ============================================

/**
 * Debug dump - muestra contenido de variables y termina
 * @param mixed ...$vars Variables a mostrar
 */
function dd(...$vars): void
{
    foreach ($vars as $v) { 
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px;">';
        var_dump($v); 
        echo '</pre>'; 
    }
    exit;
}

/**
 * Log de depuración (solo en desarrollo)
 * @param string $message Mensaje de log
 * @param array $data Datos adicionales
 */
function debugLog(string $message, array $data = []): void
{
    if (defined('DEBUG') && DEBUG === true) {
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($data)) {
            $logEntry .= ': ' . print_r($data, true);
        }
        error_log($logEntry);
    }
}

// ============================================
// FUNCIONES LEGACY (DEPRECATED)
// ============================================

/**
 * @deprecated Usar e() en su lugar
 */
function h(string $str): string
{
    return e($str);
}

/**
 * Funciones auxiliares para el sistema de archivos
 */

if (!function_exists('_fileIcon')) {
    function _fileIcon(string $category): string {
        return match($category) {
            'image'    => 'image',
            'document' => 'file-earmark-text',
            'audio'    => 'music-note',
            'video'    => 'camera-video',
            'archive'  => 'file-zip',
            default    => 'file-earmark',
        };
    }
}

if (!function_exists('_fileIconColor')) {
    function _fileIconColor(string $category): string {
        return match($category) {
            'image'    => '#22c55e',
            'document' => '#3b82f6',
            'audio'    => '#f59e0b',
            'video'    => '#ef4444',
            'archive'  => '#8b5cf6',
            default    => '#8b949e',
        };
    }
}

if (!function_exists('_formatFileSize')) {
    function _formatFileSize($bytes): string {
        $bytes = (int)($bytes ?? 0);
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}

if (!function_exists('_fileExtension')) {
    function _fileExtension(string $filename): string {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

// ✅ NUEVAS FUNCIONES PARA METADATA

if (!function_exists('_getFileMetadata')) {
    function _getFileMetadata(?array $file): array {
        if (!$file || empty($file['metadata'])) {
            return [];
        }
        
        // Si metadata es string JSON, decodificarlo
        if (is_string($file['metadata'])) {
            return json_decode($file['metadata'], true) ?? [];
        }
        
        return $file['metadata'] ?? [];
    }
}

if (!function_exists('_getFileExif')) {
    function _getFileExif(?array $file): ?array {
        $metadata = _getFileMetadata($file);
        return $metadata['exif'] ?? null;
    }
}

if (!function_exists('_getFileDimensions')) {
    function _getFileDimensions(?array $file): ?array {
        $metadata = _getFileMetadata($file);
        if (!empty($metadata['exif']['width']) && !empty($metadata['exif']['height'])) {
            return [
                'width' => $metadata['exif']['width'],
                'height' => $metadata['exif']['height']
            ];
        }
        return null;
    }
}

if (!function_exists('_fileIsImage')) {
    function _fileIsImage(?array $file): bool {
        return ($file['category'] ?? '') === 'image';
    }
}

if (!function_exists('_fileIsVideo')) {
    function _fileIsVideo(?array $file): bool {
        return ($file['category'] ?? '') === 'video';
    }
}

if (!function_exists('_fileIsAudio')) {
    function _fileIsAudio(?array $file): bool {
        return ($file['category'] ?? '') === 'audio';
    }
}

if (!function_exists('_fileIsDocument')) {
    function _fileIsDocument(?array $file): bool {
        return ($file['category'] ?? '') === 'document';
    }
}

// ============================================
// HELPERS DE LA WEB PÚBLICA
// ============================================
// getPublicSettings(), public_logo(), public_favicon_tags()… Viven aparte para
// no engordar este archivo y para que sea evidente que son del frontend público
// y no del panel. Se cargan siempre: son envoltorios sin coste hasta que se usan.
if (is_file(__DIR__ . '/public_settings.php')) {
    require_once __DIR__ . '/public_settings.php';
}