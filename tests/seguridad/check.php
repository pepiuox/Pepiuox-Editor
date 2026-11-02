<?php

declare(strict_types=1);

/**
 * Comprobación de seguridad del panel y de las tareas programadas.
 *
 *   php tests/seguridad/check.php
 *
 * Cubre los dos cambios del 2026-10-31:
 *
 *  S-6 — el mapa de permisos por acción. No basta con que el mapa exista: lo que
 *        importa es cuántas acciones del panel quedan **sin** declarar, porque
 *        esas siguen dependiendo solo de tener sesión. Este script las cuenta y
 *        falla si aparece una nueva que nadie clasificó.
 *
 *  S-1 y S-2 — el token de las tareas programadas. Comprueba que sin token
 *        configurado se rechaza todo, que con token configurado solo pasa el
 *        correcto y que la comparación no acepta el vacío.
 *
 * No necesita base de datos ni servidor web.
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

use App\Core\CronToken;

$fallos = 0;
$total  = 0;

function comprobar(string $descripcion, bool $ok, string $detalle = ''): void
{
    global $fallos, $total;
    $total++;
    if ($ok) {
        echo "  OK    {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FALLO {$descripcion}" . ($detalle !== '' ? " -> {$detalle}" : '') . "\n";
}

// ─────────────────────────────────────────────────────────────────────
echo "== S-6 · Mapa de permisos ==\n";

$mapa = require CONFIG_PATH . '/permissions.php';
comprobar('el mapa de permisos existe y no está vacío', is_array($mapa) && $mapa !== [], (string) count($mapa));

// Acciones declaradas en routes.php, en la forma `Controlador::class, 'metodo'`.
$rutas = (string) file_get_contents(CONFIG_PATH . '/routes.php');
preg_match_all(
    '/App\\\\Controllers\\\\([A-Za-z0-9_]+)::class\s*,\s*\'([A-Za-z0-9_]+)\'/',
    $rutas,
    $coincidencias,
    PREG_SET_ORDER
);

$acciones = [];
foreach ($coincidencias as $c) {
    $acciones['App\\Controllers\\' . $c[1] . '::' . $c[2]] = true;
}
$acciones = array_keys($acciones);
sort($acciones);

comprobar('se pudieron leer acciones de routes.php', $acciones !== [], (string) count($acciones));

/**
 * Acciones que se dejan sin permiso a propósito: son de cada usuario sobre sus
 * propias cosas. Si se mapean, hay que asegurarse antes de que todos los roles
 * tienen el permiso, o se deja gente fuera de su propia pantalla.
 */
$sinPermisoAdrede = [
    'App\\Controllers\\DashboardController::index'      => 'tablero: es la pantalla de entrada de cualquier rol',
    'App\\Controllers\\ProfileController::index'        => 'perfil propio',
    'App\\Controllers\\ProfileController::update'       => 'perfil propio',
    'App\\Controllers\\ProfileController::changePassword' => 'perfil propio',
    'App\\Controllers\\NotificationsController::index'  => 'notificaciones propias',
    'App\\Controllers\\NotificationsController::list'   => 'notificaciones propias',
    'App\\Controllers\\NotificationsController::markRead' => 'notificaciones propias',
    'App\\Controllers\\NotificationsController::markAllRead' => 'notificaciones propias',
    'App\\Controllers\\NotificationsController::clearAll' => 'notificaciones propias',
    'App\\Controllers\\NotificationsController::delete' => 'notificaciones propias',
    'App\\Controllers\\AuthController::closeOtherSessions' => 'cerrar las sesiones propias',
    'App\\Controllers\\TwoFactorController::challenge'  => 'segundo factor en el acceso',
    'App\\Controllers\\TwoFactorController::recoverySuccess' => 'segundo factor en el acceso',
];

/**
 * Acciones **públicas**: se sirven sin sesión, así que un permiso no aplica. No
 * necesitan estar en el mapa y no representan un hueco de autorización.
 */
$publicas = [
    'App\\Controllers\\PublicController::home'    => 'portada pública',
    'App\\Controllers\\PublicController::show'    => 'página pública',
    'App\\Controllers\\PublicController::sitemap' => 'sitemap',
    'App\\Controllers\\PublicThemeController::css'      => 'hoja de estilos del tema',
    'App\\Controllers\\PublicThemeController::manifest' => 'manifiesto de la aplicación web',
    'App\\Controllers\\SeoController::robots'     => 'robots.txt',
    'App\\Controllers\\AuthController::showLogin' => 'formulario de acceso',
    'App\\Controllers\\AuthController::login'     => 'acceso',
    'App\\Controllers\\AuthController::logout'    => 'salida',
    'App\\Controllers\\AuthController::verify2FA' => 'segundo factor en el acceso',
    'App\\Controllers\\PasswordController::registerForm' => 'registro',
    'App\\Controllers\\PasswordController::registerSend' => 'registro',
    'App\\Controllers\\PasswordController::forgotForm'   => 'recuperación de contraseña',
    'App\\Controllers\\PasswordController::forgotSend'   => 'recuperación de contraseña',
    'App\\Controllers\\PasswordController::resetForm'    => 'restablecimiento de contraseña',
    'App\\Controllers\\PasswordController::resetSave'    => 'restablecimiento de contraseña',
    'App\\Controllers\\LanguageController::switch'       => 'cambio de idioma público',
    'App\\Controllers\\ApiController::pagesIndex'  => 'API v1: se autentica por token Bearer',
    'App\\Controllers\\ApiController::pagesShow'   => 'API v1: se autentica por token Bearer',
    'App\\Controllers\\ApiController::menusShow'   => 'API v1: se autentica por token Bearer',
    'App\\Controllers\\ApiController::me'          => 'API v1: se autentica por token Bearer',
];

/**
 * Acciones **autenticadas por token**, no por sesión: son los endpoints de
 * tareas programadas. Mapearlas a un permiso las dejaría exigiendo un usuario
 * logueado y rompería el cron — se comprobó al hacerlo: respondían 403.
 */
$porToken = [
    'App\\Controllers\\BackupsController::cron'            => 'cron: token en la URL',
    'App\\Controllers\\BannedUsersController::processExpired' => 'cron: token en la URL',
];

$mapeadas   = [];
$sinMapear  = [];
$permitidas = 0;
foreach ($acciones as $accion) {
    if (array_key_exists($accion, $mapa)) {
        $mapeadas[] = $accion;
    } elseif (array_key_exists($accion, $sinPermisoAdrede)
           || array_key_exists($accion, $publicas)
           || array_key_exists($accion, $porToken)) {
        $permitidas++;
    } else {
        $sinMapear[] = $accion;
    }
}

printf("        acciones en rutas: %d · con permiso: %d · justificadas sin permiso: %d · sin clasificar: %d\n",
    count($acciones), count($mapeadas), $permitidas, count($sinMapear));

comprobar(
    'toda acción del panel tiene permiso o motivo para no tenerlo',
    $sinMapear === [],
    implode(', ', $sinMapear)
);

// Cada permiso del mapa debe ser un nombre que exista en el catálogo del
// proyecto. Se lista aquí para no depender de la base de datos.
$catalogo = [
    '2fa.manage', 'analytics.view', 'api.manage', 'audit.view', 'backups.manage',
    'backups.restore', 'cache.manage', 'collaboration.use', 'comments.create',
    'comments.delete', 'comments.resolve', 'comments.view', 'components.manage',
    'files.delete', 'files.manage', 'files.share', 'files.upload', 'files.view',
    'menus.manage', 'notifications.manage', 'pages.create', 'pages.delete',
    'pages.edit', 'pages.publish', 'pages.restore', 'pages.view', 'profile.edit',
    'redirects.manage', 'reports.manage', 'roles.manage', 'security.ban_users',
    'security.dashboard', 'seo.global', 'seo.manage', 'settings.manage',
    'templates.create', 'templates.delete', 'templates.edit', 'templates.view',
    'translations.manage', 'trusted_devices.manage', 'users.ban',
    'users.ban.permanent', 'users.create', 'users.delete', 'users.edit',
    'users.view', 'users.view.ban_history',
];

$permisosUsados = array_values(array_unique(array_values($mapa)));
$desconocidos   = array_values(array_diff($permisosUsados, $catalogo));
comprobar('todos los permisos del mapa existen en el catálogo', $desconocidos === [], implode(', ', $desconocidos));

// ─────────────────────────────────────────────────────────────────────
echo "\n== S-1 / S-2 · Token de tareas programadas ==\n";

putenv('CRON_TOKEN');
comprobar('sin token configurado se rechaza el vacío', CronToken::isValid('') === false);
comprobar('sin token configurado se rechaza cualquier valor', CronToken::isValid('abc') === false);

$secreto = 'token-de-prueba-' . bin2hex(random_bytes(6));
putenv('CRON_TOKEN=' . $secreto);
comprobar('con token configurado se acepta el correcto', CronToken::isValid($secreto) === true);
comprobar('se rechaza uno incorrecto', CronToken::isValid($secreto . 'x') === false);
comprobar('se rechaza el vacío aunque haya token', CronToken::isValid('') === false);
comprobar('se rechaza un prefijo del token', CronToken::isValid(substr($secreto, 0, 8)) === false);
putenv('CRON_TOKEN');

// ─────────────────────────────────────────────────────────────────────
echo "\n== Rutas de cron con guardián ==\n";
$rutasCron = preg_match_all('/\$router->get\(\'\/cron\//', $rutas);
comprobar('las rutas /cron/* están declaradas', $rutasCron > 0, (string) $rutasCron);

$guardianes = preg_match_all('/\\\\App\\\\Core\\\\CronToken::require\(\$request\)/', $rutas);
printf("        rutas /cron/*: %d · llamadas al guardián en routes.php: %d\n", $rutasCron, $guardianes);

$bans = (string) file_get_contents(APP_PATH . '/Controllers/BannedUsersController.php');
comprobar(
    '/cron/process-expired-bans exige token (S-1)',
    str_contains($bans, 'CronToken::require'),
    'el endpoint que escribe en la base de datos sigue sin comprobar credencial'
);

$respuestasCron = (string) file_get_contents(APP_PATH . '/Controllers/BackupsController.php');
comprobar('el cron de copias usa el guardián central', str_contains($respuestasCron, 'CronToken::require'));

$config = (string) file_get_contents(CONFIG_PATH . '/config.php');
comprobar(
    'el token se lee del entorno y no se genera (S-2)',
    str_contains($config, "env('CRON_TOKEN')"),
    'el token seguiría cambiando en cada carga'
);
comprobar(
    'el token ya no se concatena con random_bytes en la asignación (S-2)',
    !preg_match("/'cron_token'\s*=>\s*'[^']*'\s*\.\s*bin2hex/", $config) === true,
    'el valor esperado cambiaría entre comprobación y comprobación'
);

// ─────────────────────────────────────────────────────────────────────
echo "\n== S-5 · El limitador falla en cerrado ==\n";

$rutasPhp = (string) file_get_contents(CONFIG_PATH . '/routes.php');

comprobar(
    'la construcción del limitador no degrada a arreglos vacíos',
    !preg_match('/catch\s*\(Throwable\s*\$e\)\s*\{\s*\$rateLimitLogin\s*=\s*\[\];/', $rutasPhp),
    'un fallo al construir el limitador dejaría las rutas sensibles sin límite'
);
comprobar(
    'las cinco variables se reinician en el catch',
    substr_count($rutasPhp, '$rateLimitRegister = $sinProteccion;') === 1,
    '`$rateLimitRegister` quedaba sin definir y su uso lanzaba un error más confuso'
);
comprobar(
    'el guardián de fallo en cerrado existe y se usa',
    class_exists('App\\Middleware\\RateLimitUnavailableMiddleware')
        && str_contains($rutasPhp, 'RateLimitUnavailableMiddleware'),
    'no hay nada que impida aceptar peticiones sin límite'
);

$noDisponible = (string) file_get_contents(APP_PATH . '/Middleware/RateLimitUnavailableMiddleware.php');
comprobar('el guardián responde 503 y no deja pasar', str_contains($noDisponible, '503'));

$limitador = (string) file_get_contents(APP_PATH . '/Middleware/RateLimitMiddleware.php');
comprobar(
    'el fallo en tiempo de ejecución se registra diciendo que permite la petición',
    str_contains($limitador, 'se permite la petición'),
    'el log anterior decía solo «Error» y no advertía de que la petición pasaba'
);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Deuda 17 · Rutas que fallaban por desajuste archivo↔clase ==\n";

// El cargador resuelve `App\Controllers\X` buscando `app/Controllers/X.php`. Si
// el archivo no se llama igual que la clase, la ruta no encuentra nada y
// responde 500. Estas dos clases estaban en archivos con otro nombre.
comprobar(
    'ComponentsController resuelve (archivo con el nombre correcto)',
    class_exists('App\\Controllers\\ComponentsController'),
    'el archivo no se llama ComponentsController.php'
);
comprobar(
    'LanguageController resuelve (archivo con el nombre correcto)',
    class_exists('App\\Controllers\\LanguageController'),
    'el archivo no se llama LanguageController.php'
);
comprobar(
    'no quedan `require` del page.php inexistente',
    !str_contains($rutasPhp, "require __DIR__ . '/../page.php'"),
    'las rutas /page seguían apuntando a un archivo que no existe'
);
comprobar(
    'las clases viejas ya no existen con el nombre antiguo',
    !class_exists('App\\Controllers\\ComponentController')
        && !class_exists('App\\Controllers\\LenguageController'),
    'quedó un duplicado con el nombre mal escrito'
);

// ─────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────
echo "\n== Acceso a los editores desde el panel ==\n";

// Los editores de contenido del sitio público deben estar en el menú lateral,
// no solo dentro de Configuración: son elementos del sistema.
$sidebar = (string) file_get_contents(VIEWS_PATH . '/partials/sidebar.php');

comprobar(
    'el menú enlaza al editor del pie',
    str_contains($sidebar, "url('admin/settings/footer')"),
    'el editor del pie no tiene entrada en el menú lateral'
);
comprobar(
    'el menú enlaza al editor de la portada',
    str_contains($sidebar, "url('admin/settings/landing')"),
    'el editor de la portada no tiene entrada en el menú lateral'
);
// Comprobación por posiciones en lugar de una expresión regular: el bloque del
// menú lleva etiquetas PHP y comillas por dentro, y una regex se vuelve frágil.
$posGuardia = strpos($sidebar, "can('settings.manage')");
$posEtiqueta = strpos($sidebar, 'Web pública');
$posPie = strpos($sidebar, "url('admin/settings/footer')");

comprobar(
    'el bloque del menú está entre la guarda de permiso y el enlace al pie',
    $posGuardia !== false && $posEtiqueta !== false && $posPie !== false
        && $posGuardia < $posEtiqueta && $posEtiqueta < $posPie,
    'las entradas del menú no están dentro de la guarda settings.manage'
);
comprobar(
    'existe is_active_exact para no encender dos entradas a la vez',
    function_exists('is_active_exact'),
    'sin coincidencia exacta, «Configuración» se enciende también en sus subpáginas'
);

// ─────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────
echo "\n== Deudas 7 y 16 · Volcados SQL sin secretos ==\n";

/**
 * Ningún `.sql` versionado bajo `db/` puede llevar hashes de contraseñas ni las
 * filas de aplicaciones/tokens de API. `db/backup/` se excluye a propósito: un
 * respaldo real SÍ contiene los datos, y por eso está ignorado por git.
 */
$sqlVersionados = array_merge(
    glob(ROOT_PATH . '/db/*.sql') ?: [],
    glob(ROOT_PATH . '/db/install/*.sql') ?: [],
    glob(ROOT_PATH . '/db/migrations/*.sql') ?: []
);

comprobar('hay volcados SQL versionados que revisar', $sqlVersionados !== [], (string) count($sqlVersionados));

$conHashes = [];
$conApi = [];
foreach ($sqlVersionados as $archivo) {
    $contenido = (string) file_get_contents($archivo);
    $nombre = basename($archivo);

    // Longitud tolerante: un hash malformado de 61 caracteres también cuenta.
    if (preg_match('/\'\$2[aby]\$\d\d\$[.\/A-Za-z0-9]{20,60}\'/', $contenido) === 1) {
        $conHashes[] = $nombre;
    }
    if (preg_match('/INSERT INTO `api_(applications|tokens)`/i', $contenido) === 1) {
        $conApi[] = $nombre;
    }
}

comprobar('ningún volcado versionado lleva hashes de contraseñas', $conHashes === [], implode(', ', $conHashes));
comprobar('ningún volcado versionado lleva filas de aplicaciones o tokens de API', $conApi === [], implode(', ', $conApi));

$gitignore = (string) file_get_contents(ROOT_PATH . '/.gitignore');
comprobar(
    'db/backup/ está ignorado por git',
    preg_match('/^\s*db\/backup\/?\s*$/m', $gitignore) === 1,
    'un respaldo completo no puede versionarse: lleva datos, hashes y secretos'
);

comprobar(
    'existe la herramienta de saneamiento de volcados',
    is_file(ROOT_PATH . '/01-TOOLS/mantenimiento/sanitize-dump.php'),
    'sin ella, el siguiente volcado vuelve a llevar secretos'
);
comprobar(
    'existe la verificación de publicación',
    is_file(ROOT_PATH . '/01-TOOLS/despliegue/verificar.php'),
    'sin ella no hay comprobación previa al despliegue'
);
comprobar(
    'existe la herramienta de rotación de tokens de API',
    is_file(ROOT_PATH . '/01-TOOLS/mantenimiento/rotate-api-tokens.php'),
    'quitar un secreto del archivo no lo invalida'
);

// ─────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────
echo "\n== Saneado del contenido publicado ==\n";

// El saneador elimina manejadores `on*`. El patrón debe ir anclado a un espacio
// previo: sin el ancla, `aria-controls="x"` coincidía por dentro («on»+«trols»+«=»)
// y quedaba mutilado a `aria-cr`, rompiendo la accesibilidad y el acordeón.
$renderer = new \App\Services\PageRenderer();
$metodo = new \ReflectionMethod(\App\Services\PageRenderer::class, 'sanitizeHtml');
$metodo->setAccessible(true);
$sanear = static fn (string $html): string => (string) $metodo->invoke($renderer, $html);

$conAria = $sanear('<div aria-controls="panel1" aria-expanded="true" role="region">x</div>');
comprobar(
    'conserva aria-controls (no lo confunde con un manejador on*)',
    str_contains($conAria, 'aria-controls="panel1"') && str_contains($conAria, 'aria-expanded'),
    $conAria
);

$conEventos = $sanear('<div onclick="alert(1)">a</div><a href="#" onmouseover="x()">b</a>');
comprobar(
    'elimina onclick',
    !str_contains($conEventos, 'onclick'),
    $conEventos
);
comprobar(
    'elimina onmouseover',
    !str_contains($conEventos, 'onmouseover'),
    $conEventos
);
comprobar(
    'el texto del contenido sobrevive al saneado',
    str_contains($conEventos, '>a<') && str_contains($conEventos, '>b<'),
    $conEventos
);

$conBoton = $sanear('<button data-bs-toggle="collapse" data-bs-target="#p1">Abrir</button>');
comprobar(
    'conserva <button> y sus data-bs-* (el acordeón de Bootstrap abre)',
    str_contains($conBoton, '<button') && str_contains($conBoton, 'data-bs-toggle'),
    $conBoton
);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Temas de color: contraste WCAG AA ==\n";

/** Luminancia relativa según WCAG 2.1. */
$lum = static function (string $hex): float {
    $hex = ltrim($hex, '#');
    $f = static fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    return 0.2126 * $f(hexdec(substr($hex, 0, 2)) / 255)
        + 0.7152 * $f(hexdec(substr($hex, 2, 2)) / 255)
        + 0.0722 * $f(hexdec(substr($hex, 4, 2)) / 255);
};

$contraste = static function (string $a, string $b) use ($lum): float {
    $la = $lum($a);
    $lb = $lum($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
};

$presets = \App\Services\PublicThemeService::THEME_PRESETS;

// Las 16 claves que un preset debe rellenar. `defaults()` lista algunas más (las
// variantes `*_text`), que no forman parte del preset.
$clavesPreset = [
    'color_primary', 'color_secondary', 'color_accent', 'color_background', 'color_section_bg',
    'color_text', 'color_text_muted', 'color_link', 'color_button', 'color_border',
    'color_header_bg', 'color_footer_bg', 'color_sidebar_bg', 'color_menu_bg',
    'color_multinivel_bg', 'color_menu_hover',
];

comprobar(
    'hay al menos siete temas para elegir',
    count($presets) >= 7,
    'solo hay ' . count($presets)
);

$incompletos = [];
$sinContraste = [];

foreach ($presets as $id => $preset) {
    $c = $preset['colors'];

    foreach ($clavesPreset as $clave) {
        if (!isset($c[$clave]) || preg_match('/^#[0-9a-f]{6}$/i', (string) $c[$clave]) !== 1) {
            $incompletos[] = $id . '.' . $clave;
        }
    }
    if (!isset($c['color_text'], $c['color_background'])) {
        continue;
    }

    // AA para texto normal: 4.5:1 sobre el fondo, sobre el fondo de sección y en
    // los enlaces (un enlace que no se distingue es un fallo de accesibilidad).
    foreach (['color_text', 'color_text_muted', 'color_link'] as $rol) {
        if (isset($c[$rol]) && $contraste($c[$rol], $c['color_background']) < 4.5) {
            $sinContraste[] = $id . '.' . $rol . ' ' . round($contraste($c[$rol], $c['color_background']), 2) . ':1';
        }
    }
    if (isset($c['color_section_bg'], $c['color_text'])
        && $contraste($c['color_text'], $c['color_section_bg']) < 4.5) {
        $sinContraste[] = $id . '.texto/sección ' . round($contraste($c['color_text'], $c['color_section_bg']), 2) . ':1';
    }
}

comprobar(
    'cada tema define los 16 colores con formato #rrggbb',
    $incompletos === [],
    implode(', ', array_slice($incompletos, 0, 6))
);

comprobar(
    'texto, atenuado y enlaces cumplen AA (4.5:1) en todos los temas',
    $sinContraste === [],
    implode(' · ', array_slice($sinContraste, 0, 6))
);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
