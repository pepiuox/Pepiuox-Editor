<?php

declare(strict_types=1);

/**
 * Comprobación de resolución de rutas.
 *
 *   php tests/rutas/check.php
 *
 * ── Qué problema vigila ─────────────────────────────────────────────
 * `Router::findRoute()` recorre las rutas **en el orden en que se registran** y
 * devuelve la primera que coincide. Eso hace que una ruta con parámetro
 * registrada antes tape a una literal del mismo número de segmentos.
 *
 * Pasó de verdad: `/admin/audit/{id}` se registraba antes que
 * `/admin/audit/activity`, así que la pantalla de actividad era inalcanzable y
 * la petición acababa en `AuditController::show` con id = "activity".
 *
 * Esta prueba hace dos cosas:
 *  1. Resuelve URIs reales con el router y comprueba a qué acción van.
 *  2. Recorre todo `config/routes.php` buscando literales tapadas por una
 *     parametrizada anterior. Así el problema no puede volver por otro sitio.
 */

$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST']   ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['REQUEST_METHOD'] ??= 'GET';

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

use App\Core\Request;
use App\Core\Router;

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

// ── Cargar el router con las rutas reales ────────────────────────────
$router = new Router();
$registrar = require CONFIG_PATH . '/routes.php';
$registrar($router, new Request());

$findRoute = new ReflectionMethod(Router::class, 'findRoute');
$findRoute->setAccessible(true);

/**
 * Resuelve una URI y devuelve "Controlador::metodo" o null.
 */
$resolver = static function (string $metodo, string $uri) use ($findRoute, $router): ?string {
    $resultado = $findRoute->invoke($router, $metodo, $uri);
    if (!is_array($resultado) || !isset($resultado[0]['handler'])) {
        return null;
    }

    $handler = $resultado[0]['handler'];
    if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
        return $handler[0] . '::' . $handler[1];
    }

    return '(cierre)';
};

echo "== 1. Rutas críticas resuelven a la acción correcta ==\n";

$esperado = [
    ['GET', '/', 'App\Controllers\PublicController::home'],
    ['GET', '/funciones', 'App\Controllers\PublicController::show'],
    ['GET', '/page', 'App\Controllers\PublicController::home'],
    ['GET', '/page/funciones', 'App\Controllers\PublicController::show'],
    ['GET', '/lang/es', 'App\Controllers\LanguageController::switch'],
    ['GET', '/login', 'App\Controllers\AuthController::showLogin'],
    ['POST', '/login', 'App\Controllers\AuthController::login'],
    ['GET', '/admin/components', 'App\Controllers\ComponentsController::index'],
    ['POST', '/admin/components', 'App\Controllers\ComponentsController::store'],
    ['GET', '/admin/audit', 'App\Controllers\AuditController::index'],
    ['GET', '/admin/audit/export', 'App\Controllers\AuditController::export'],
    ['GET', '/admin/audit/activity', 'App\Controllers\ActivityLogsController::index'],
    ['GET', '/admin/audit/activity/export', 'App\Controllers\ActivityLogsController::export'],
    ['GET', '/admin/audit/activity/stats', 'App\Controllers\ActivityLogsController::stats'],
    ['POST', '/admin/audit/activity/clean', 'App\Controllers\ActivityLogsController::clean'],
    ['GET', '/admin/audit/activity/77', 'App\Controllers\ActivityLogsController::show'],
    ['GET', '/admin/audit/77', 'App\Controllers\AuditController::show'],
    ['GET', '/admin/audit/user/3', 'App\Controllers\AuditController::userLogs'],
    ['GET', '/admin/settings/landing', 'App\Controllers\SettingsController::landing'],
    ['GET', '/admin/seo/sitemap', 'App\Controllers\SeoController::sitemap'],
    ['POST', '/admin/seo/sitemap/regenerate', 'App\Controllers\SeoController::regenerateSitemap'],
];

foreach ($esperado as [$metodo, $uri, $accion]) {
    $obtenido = $resolver($metodo, $uri);
    comprobar(
        $metodo . ' ' . $uri . ' → ' . substr(strrchr($accion, '\\') ?: '', 1),
        $obtenido === $accion,
        'resolvió a ' . ($obtenido ?? '(nada)')
    );
}

// ── 2. Ninguna literal tapada por una parametrizada anterior ─────────
echo "\n== 2. Ninguna ruta literal queda tapada ==\n";

$fuente = (string) file_get_contents(CONFIG_PATH . '/routes.php');
preg_match_all(
    '/\$router->(get|post|put|delete)\(\s*\'([^\']+)\'/',
    $fuente,
    $coincidencias,
    PREG_SET_ORDER
);

$registradas = [];
foreach ($coincidencias as $c) {
    $registradas[] = ['metodo' => strtoupper($c[1]), 'ruta' => $c[2]];
}
comprobar('se pudieron leer las rutas declaradas', count($registradas) > 100, (string) count($registradas));

/**
 * ¿La ruta parametrizada `$patron` coincide con la literal `$ruta`?
 * Los segmentos `{...}` aceptan cualquier cosa.
 */
$tapa = static function (string $patron, string $ruta): bool {
    $a = explode('/', trim($patron, '/'));
    $b = explode('/', trim($ruta, '/'));

    if (count($a) !== count($b)) {
        return false;
    }

    foreach ($a as $i => $segmento) {
        $esParametro = str_starts_with($segmento, '{') && str_ends_with($segmento, '}');
        if (!$esParametro && $segmento !== $b[$i]) {
            return false;
        }
    }

    // Una ruta idéntica a sí misma no es una tapa.
    return $patron !== $ruta;
};

$tapadas = [];
foreach ($registradas as $i => $literal) {
    if (str_contains($literal['ruta'], '{')) {
        continue; // Solo se revisan las literales.
    }

    foreach (array_slice($registradas, 0, $i) as $anterior) {
        if ($anterior['metodo'] !== $literal['metodo']) {
            continue;
        }
        if ($tapa($anterior['ruta'], $literal['ruta'])) {
            $tapadas[] = $literal['metodo'] . ' ' . $literal['ruta'] . ' tapada por ' . $anterior['ruta'];
            break;
        }
    }
}

comprobar(
    'ninguna literal está registrada después de una parametrizada que la capture',
    $tapadas === [],
    implode(' | ', $tapadas)
);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
