<?php

declare(strict_types=1);

/**
 * Genera una copia estática de la portada para revisarla sin navegador ni sesión.
 *
 *   php tests/landing/snapshot.php
 *
 * Escribe `storage/preview/portada.html`, que se abre directamente en el
 * navegador. Ese archivo NO se sirve por HTTP: `storage/` está fuera del
 * document root y el `.htaccess` de la raíz sólo publica `public/`.
 *
 * Aviso honesto sobre la copia: la hoja de estilos dinámica
 * (`/public-theme.css`, que genera los colores del tema) es la única que no
 * cargará al abrir el archivo desde el disco, porque su ruta es absoluta al
 * servidor. El resto —Bootstrap desde CDN y los estilos embebidos del layout y
 * de la portada— se ve igual. Para ver la portada con los colores reales, usa
 * la ruta `/admin/settings/landing/preview` dentro de la aplicación.
 *
 * Esto es una herramienta de revisión, no una prueba: la prueba es
 * `tests/landing/check.php`.
 */

$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST']   ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';

require __DIR__ . '/../../config/paths.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once APP_PATH . '/Helpers/functions.php';

use App\Core\View;
use App\Services\LandingContentService;
use App\Services\PageRenderer;
use App\Services\PublicThemeService;
use App\Services\SiteSettings;

$landing = LandingContentService::all();
$appName = SiteSettings::name();

$content = View::renderStandalone('public.landing', [
    'landing' => $landing,
    'appName' => $appName,
]);

try {
    $menu = (new PageRenderer())->getMenu();
} catch (\Throwable $e) {
    fwrite(STDERR, 'Aviso: no se pudo leer el menú (' . $e->getMessage() . "). Se genera sin menú.\n");
    $menu = [];
}

$html = View::renderStandalone('public.layout', [
    'page' => [
        'title'       => (string) ($landing['hero_title'] ?? ''),
        'description' => (string) ($landing['hero_subtitle'] ?? ''),
        'slug'        => 'inicio',
    ],
    'menu'     => $menu,
    'content'  => $content,
    'appName'  => $appName,
    'authUser' => null,
]);

$dir = dirname(__DIR__, 2) . '/storage/preview';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "No se pudo crear {$dir}\n");
    exit(1);
}

// La hoja dinámica del tema se sirve en /public-theme.css con una ruta absoluta,
// así que al abrir la copia desde el disco no cargaría y los colores elegidos en
// el panel se perderían. Se inserta en línea para que la copia sea fiel y
// autocontenida. Se usa una llamada de retorno porque el CSS puede contener `$`.
$cssInlineado = false;
try {
    $css = PublicThemeService::css() . "\n" . PublicThemeService::customCss();
    if (trim($css) !== '') {
        $html = (string) preg_replace_callback(
            '#<link[^>]*public-theme\.css[^>]*>#i',
            static fn (): string => '<style id="tema-en-linea">' . $css . '</style>',
            $html,
            1
        );
        $cssInlineado = true;
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Aviso: no se pudo insertar la hoja del tema (' . $e->getMessage() . ").\n");
}

$file = $dir . '/portada.html';
if (file_put_contents($file, $html) === false) {
    fwrite(STDERR, "No se pudo escribir {$file}\n");
    exit(1);
}

echo 'Copia escrita: ' . $file . ' (' . number_format(strlen($html)) . " bytes)\n";
echo 'Hoja del tema insertada en línea: ' . ($cssInlineado ? 'sí' : 'no') . "\n";
echo 'Ábrela en el navegador para revisar la portada.' . "\n";
echo 'Con sesión y datos en vivo: /admin/settings/landing/preview' . "\n";
