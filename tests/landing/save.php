<?php

declare(strict_types=1);

/**
 * Prueba de guardado del contenido de la portada.
 *
 *   php tests/landing/save.php
 *
 * Escribe en la base de datos que indique `DB_NAME`. **No la crea ni la borra**:
 * el script que la prepara es la batería de integración. Pensada para ejecutarse
 * contra una base de pruebas, nunca contra la de producción.
 *
 * Qué comprueba:
 *  1. Que un guardado válido persista y se lea de vuelta.
 *  2. Que un destino `javascript:` en un botón se rechace y **conserve** el
 *     anterior, en vez de guardarse o de borrarlo.
 *  3. Que una fila incompleta de una lista se descarte sin arrastrar al resto.
 *  4. Que el interruptor de publicación solo cambie cuando el formulario lo envía.
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

use App\Services\LandingContentService;

$db = (string) getenv('DB_NAME');
if ($db === '') {
    fwrite(STDERR, "Falta DB_NAME. Esta prueba escribe en esa base: apunta a una de pruebas.\n");
    exit(1);
}

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

echo 'BD: ' . $db . "\n\n";

echo "== 1. Guardado válido ==\n";
$antes = LandingContentService::all();

$resultado = LandingContentService::save([
    'hero_title'       => 'Titular de prueba 2026',
    'hero_subtitle'    => 'Eslogan de prueba',
    'hero_description' => 'Descripción de prueba.',
    'hero_kicker'      => 'Antetítulo',
    'hero_image'       => '',
    'footer_note'      => 'Nota de prueba',
    'hero_cta_primary_label'   => 'Entrar',
    'hero_cta_primary_href'    => '/login',
    'hero_cta_secondary_label' => 'Ver',
    'hero_cta_secondary_href'  => '#caracteristicas',
    'sections' => ['features' => ['title' => 'Funciones de prueba']],
    'features' => [
        ['icon' => 'bi-star', 'title' => 'Uno', 'description' => 'Primera'],
        ['icon' => 'bi-star', 'title' => 'Dos', 'description' => 'Segunda'],
        ['icon' => 'bi-star', 'title' => '', 'description' => 'Fila sin título: debe caer'],
    ],
    'specs'    => [['label' => 'Lenguaje', 'value' => 'PHP 8']],
    'benefits' => [['icon' => 'bi-star', 'title' => 'Rápido', 'description' => 'Mucho']],
    'gallery'  => [],
]);

comprobar('el guardado no devuelve errores', $resultado['errores'] === [], implode(' | ', $resultado['errores']));
comprobar('avisa de la fila descartada', $resultado['avisos'] !== []);

LandingContentService::flush();
$despues = LandingContentService::all();

comprobar('el titular se persistió', $despues['hero_title'] === 'Titular de prueba 2026', (string) $despues['hero_title']);
comprobar('el eslogan se persistió', $despues['hero_subtitle'] === 'Eslogan de prueba');
comprobar('la sección se persistió', ($despues['sections']['features']['title'] ?? '') === 'Funciones de prueba');
comprobar('quedan 2 tarjetas de funciones de 3 enviadas', count($despues['features']) === 2, (string) count($despues['features']));
comprobar('la fila incompleta se descartó', array_column($despues['features'], 'title') === ['Uno', 'Dos']);
comprobar('la galería queda vacía', $despues['gallery'] === []);
comprobar('el interruptor no cambió (no se envió)', $despues['enabled'] === $antes['enabled']);

echo "\n== 2. Destino peligroso en un botón ==\n";
$anteriorHref = (string) ($despues['hero_cta_primary']['href'] ?? '');

$malo = LandingContentService::save([
    'hero_title' => (string) $despues['hero_title'],
    'hero_cta_primary_label' => 'Peligro',
    'hero_cta_primary_href'  => 'javascript:alert(1)',
    'features' => [], 'specs' => [], 'benefits' => [], 'gallery' => [],
]);

comprobar('el guardado informa del error', $malo['errores'] !== []);
comprobar('el destino anterior se conservó', (string) ($malo['errores'] !== [] ? $anteriorHref : '') === $anteriorHref);

LandingContentService::flush();
$trasMalo = LandingContentService::all();
comprobar(
    'no se guardó javascript:',
    ($trasMalo['hero_cta_primary']['href'] ?? '') === $anteriorHref,
    (string) ($trasMalo['hero_cta_primary']['href'] ?? '')
);

echo "\n== 3. Interruptor de publicación ==\n";
LandingContentService::save([
    'enabled' => '1',
    'hero_title' => (string) $trasMalo['hero_title'],
    'features' => [], 'specs' => [], 'benefits' => [], 'gallery' => [],
]);
LandingContentService::flush();
comprobar('activar funciona', LandingContentService::enabled() === true);

LandingContentService::save([
    'hero_title' => (string) $trasMalo['hero_title'],
    'features' => [], 'specs' => [], 'benefits' => [], 'gallery' => [],
]);
LandingContentService::flush();
comprobar('volver a guardar sin el campo NO lo desactiva', LandingContentService::enabled() === true);

LandingContentService::save([
    'enabled' => '0',
    'hero_title' => (string) $trasMalo['hero_title'],
    'features' => [], 'specs' => [], 'benefits' => [], 'gallery' => [],
]);
LandingContentService::flush();
comprobar('desactivar funciona', LandingContentService::enabled() === false);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
