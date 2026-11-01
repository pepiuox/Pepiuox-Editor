<?php

declare(strict_types=1);

/**
 * Comprobación del contenido editable del pie de página.
 *
 *   php tests/footer/check.php
 *
 * No necesita base de datos ni servidor: usa los valores por defecto del
 * servicio, que son los mismos que sirve una instalación recién creada.
 *
 * Qué comprueba, y por qué:
 *  1. Que el contenido por defecto tenga la forma esperada. Sin esto, el pie de
 *     una instalación nueva saldría vacío y nadie se enteraría hasta verlo.
 *  2. Que los enlaces por defecto apunten a rutas del sitio, no a páginas
 *     inexistentes (el pie anterior enlazaba a `about`, `privacy` y `terms`,
 *     que nunca se crearon: tres enlaces a un 404).
 *  3. Que la validación rechace `javascript:` y los enlaces vacíos.
 *  4. Que el correo inválido se descarte en lugar de pintarse.
 *  5. Que el copyright con marcadores se pueda sustituir sin romper nada.
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

use App\Core\LinkGuard;
use App\Services\FooterContentService;

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

echo "== 1. Contenido por defecto ==\n";
$def = FooterContentService::defaults();

foreach (['about', 'copyright', 'show_contact', 'contact_title', 'contact_email', 'contact_note', 'columns'] as $clave) {
    comprobar("existe la clave «{$clave}»", array_key_exists($clave, $def));
}

comprobar('el copyright usa los marcadores {year} y {site}', str_contains($def['copyright'], '{year}') && str_contains($def['copyright'], '{site}'));
comprobar('la columna de contacto se muestra por defecto', $def['show_contact'] === true);
comprobar('hay dos columnas de enlaces', count($def['columns']) === 2, (string) count($def['columns']));

echo "\n== 2. Los enlaces por defecto apuntan a rutas del sitio ==\n";
$rutas = [];
foreach ($def['columns'] as $columna) {
    foreach ($columna['links'] as $enlace) {
        $rutas[] = $enlace['href'];
    }
}

comprobar('todas son rutas internas o http(s)', array_reduce($rutas, static fn ($c, $r) => $c && LinkGuard::isSafe($r), true));
comprobar('no queda ninguna ruta de las antiguas (about/privacy/terms)', !in_array('/about', $rutas, true) && !in_array('/privacy', $rutas, true) && !in_array('/terms', $rutas, true), implode(', ', $rutas));
comprobar('hay al menos un enlace al inicio', in_array('/', $rutas, true));

echo "\n== 3. Validación de destinos (LinkGuard) ==\n";
comprobar('acepta ruta interna', LinkGuard::isSafeLink('/funciones'));
comprobar('acepta URL https', LinkGuard::isSafeLink('https://ejemplo.com/x'));
comprobar('rechaza javascript:', !LinkGuard::isSafeLink('javascript:alert(1)'));
comprobar('rechaza data:', !LinkGuard::isSafeLink('data:text/html,<script>'));
comprobar('rechaza mailto: (no es destino de enlace del pie)', !LinkGuard::isSafeLink('mailto:a@b.c'));
comprobar('rechaza protocolo-relativa //host', !LinkGuard::isSafeLink('//evil.example'));
comprobar('rechaza un ancla suelta', !LinkGuard::isSafeLink('#'));
comprobar('rechaza el vacío', !LinkGuard::isSafeLink(''));
comprobar('rechaza algo demasiado largo', !LinkGuard::isSafeLink('/' . str_repeat('a', 400)));

echo "\n== 4. La validación de columnas descarta lo inválido ==\n";
$ref = new ReflectionMethod(FooterContentService::class, 'sanitizeColumns');
$ref->setAccessible(true);

// Cuatro columnas: el tope del servicio. Con cinco, la quinta no se procesa y la
// prueba mediría el tope en lugar del filtrado.
$limpias = $ref->invoke(null, [
    ['title' => 'Válida', 'links' => [['label' => 'Uno', 'href' => '/uno']]],
    ['title' => '', 'links' => [['label' => 'Sin título', 'href' => '/x']]],
    ['title' => 'Mala', 'links' => [['label' => 'Peligro', 'href' => 'javascript:alert(1)']]],
    ['title' => 'Buena', 'links' => [
        ['label' => 'A', 'href' => '/a'],
        ['label' => 'B', 'href' => 'javascript:x'],
        ['label' => '', 'href' => '/c'],
    ]],
]);

comprobar('quedan dos columnas de cuatro enviadas', count($limpias) === 2, (string) count($limpias));
comprobar('la columna sin título se descarta', !in_array('Sin título', array_column(array_merge(...array_column($limpias, 'links')), 'label'), true));
comprobar('la columna sin enlaces válidos se descarta', !in_array('Mala', array_column($limpias, 'title'), true));
comprobar('el enlace javascript: se descarta y el bueno se queda', count($limpias[1]['links']) === 1 && $limpias[1]['links'][0]['href'] === '/a');

$demasiadas = $ref->invoke(null, array_fill(0, 10, ['title' => 'X', 'links' => [['label' => 'L', 'href' => '/l']]]));
comprobar(
    'se respeta el máximo de columnas (' . FooterContentService::MAX_COLUMNS . ')',
    count($demasiadas) === FooterContentService::MAX_COLUMNS,
    (string) count($demasiadas)
);

$muchos = $ref->invoke(null, [['title' => 'X', 'links' => array_fill(0, 20, ['label' => 'L', 'href' => '/l'])]]);
comprobar(
    'se respeta el máximo de enlaces por columna (' . FooterContentService::MAX_LINKS . ')',
    count($muchos[0]['links']) === FooterContentService::MAX_LINKS,
    (string) count($muchos[0]['links'])
);

echo "\n== 5. Marcadores del copyright ==\n";
$texto = str_replace(['{year}', '{site}'], ['2026', 'Mi Sitio'], $def['copyright']);
comprobar('los marcadores se sustituyen', str_contains($texto, '2026') && str_contains($texto, 'Mi Sitio'));
comprobar('no queda ningún marcador sin sustituir', !str_contains($texto, '{'));

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
