<?php
/** Helpers globales. */

function e(?string $v): string { 
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); 
}

/**
 * Genera una URL interna de la aplicación.
 * Ej: url('admin/users') -> http://localhost:170/public/admin/users
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    // Si BASE_URL ya termina en /public, no lo duplicamos
    return rtrim(BASE_URL, '/') . '/' . $path;
}

/**
 * Genera una URL a un asset (CSS, JS, imágenes).
 * Ej: asset('css/maker.css') -> http://localhost:170/public/assets/css/maker.css
 */
function asset(string $path): string {
    $path = ltrim($path, '/');
    return rtrim(ASSETS_URL, '/') . '/' . $path;
}

/**
 * Genera el campo oculto CSRF para formularios.
 */
function csrf_field(): string {
    return \App\Core\CSRF::field();
}
/**
 * Devuelve el token CSRF actual (para usar en JavaScript).
 */
function csrf_token(): string {
    return \App\Core\CSRF::token();
}
/**
 * Valida el token CSRF de la petición actual.
 */
function csrf_guard(): void {
    \App\Core\CSRF::guard();
}

function old(string $key, $default = '') {
    return e($_POST[$key] ?? $default);
}

function is_active(string $path, string $class = 'active'): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // Normalizar para comparación
    $uri = rtrim($uri, '/');
    $path = rtrim($path, '/');
    return str_starts_with($uri, $path) ? $class : '';
}


function dd(...$vars): void
{
    foreach ($vars as $v) { echo '<pre>'; var_dump($v); echo '</pre>'; }
    exit;
}

function flash(string $type, string $message): void
{
    \App\Core\Session::flash("flash.$type", $message);
}

function get_flash(): array
{
    $out = [];
    foreach (['success','error','info','warning'] as $t) {
        $m = \App\Core\Session::getFlash("flash.$t");
        if ($m) $out[$t] = $m;
    }
    return $out;
}

function can(string $permission): bool
{
    return \App\Core\Auth::can($permission);
}

/** Traducción */
function t(string $key, array $params = [], ?string $lang = null): string
{
    return \App\Services\TranslationService::get($key, $params, $lang);
}

/** Idioma actual */
function current_lang(): string
{
    return \App\Services\TranslationService::getLang();
}