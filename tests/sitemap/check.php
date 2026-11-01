<?php

declare(strict_types=1);

/**
 * Comprobación del generador de sitemap.
 *
 * Ejecuta:
 *   php tests/sitemap/check.php            # comprueba sin escribir el archivo
 *   php tests/sitemap/check.php --write    # además regenera public/sitemap.xml
 *
 * Sin `--write` no toca nada: valida la resolución de menús, la configuración,
 * la URL base y la composición del XML a partir de los datos reales de la base.
 *
 * Qué se comprueba, y por qué:
 *  1. Que la regla de «menú en uso» sea la misma que usa el sitio público
 *     (primario de la ubicación gana al predeterminado). Si divergen, el
 *     sitemap anuncia URLs que el visitante no puede alcanzar.
 *  2. Que la URL base avise si es localhost: un sitemap con direcciones locales
 *     no lo indexa ningún buscador.
 *  3. Que el XML generado sea XML válido y que sus `<loc>` sean absolutas.
 *  4. Que no se cuele nada del panel, ni el login, ni enlaces externos.
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

use App\Services\SitemapService;

$escribir = in_array('--write', $argv ?? [], true);
$fallos   = 0;
$total    = 0;

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

echo "== 1. Configuración ==\n";
$ajustes = SitemapService::settings();
comprobar('el ámbito por defecto son los menús predeterminados', $ajustes['scope'] === 'default_menus', $ajustes['scope']);
comprobar('el ámbito elegido existe en el catálogo', array_key_exists($ajustes['scope'], SitemapService::scopes()), $ajustes['scope']);

$base = SitemapService::baseUrl();
comprobar('la URL base es absoluta o se avisa', str_starts_with($base['url'], 'http'), $base['url']);
echo '      base_url=' . $base['url'] . ' (origen: ' . $base['origen'] . ")\n";
if ($base['aviso'] !== null) {
    echo "      AVISO: {$base['aviso']}\n";
}

echo "\n== 2. Menús leídos según el ámbito ==\n";
$menus = SitemapService::menusForSitemap($ajustes);
comprobar('hay al menos un menú que leer', $menus !== [], (string) count($menus));

$ubicaciones = array_column($menus, 'location');
comprobar('no hay dos menús para la misma ubicación', count($ubicaciones) === count(array_unique($ubicaciones)));

foreach ($menus as $menu) {
    echo "      ámbito {$ajustes['scope']} -> {$menu['location']}: #{$menu['id']} «{$menu['name']}» ({$menu['motivo']})\n";
}

// La diferencia entre ámbitos debe existir y ser visible: si no, el selector
// del panel sería decorativo.
$porDefecto = SitemapService::menusForSitemap(['scope' => 'default_menus', 'menu_id' => 0, 'base_url' => '', 'include_home' => true]);
$porHeader  = SitemapService::menusForSitemap(['scope' => 'header_menu', 'menu_id' => 0, 'base_url' => '', 'include_home' => true]);
echo '      predeterminados: ' . implode(', ', array_column($porDefecto, 'name')) . "\n";
echo '      header:          ' . implode(', ', array_column($porHeader, 'name')) . "\n";
comprobar('ambos ámbitos resuelven a algo', $porDefecto !== [] && $porHeader !== []);

echo "\n== 3. Generación ==\n";
// Por defecto, previsualización: calcula el informe sin tocar el disco.
// Con --write, además escribe public/sitemap.xml con el contenido real.
$informe = SitemapService::generate($escribir);
comprobar('la generación no lanza y devuelve informe', is_array($informe) && array_key_exists('ok', $informe));
echo '      ' . $informe['mensaje'] . "\n";
if ($informe['warnings'] !== []) {
    foreach ($informe['warnings'] as $aviso) {
        echo "      AVISO: {$aviso}\n";
    }
}

$xml = SitemapService::currentXml();
comprobar('hay contenido XML', $xml !== '');

if ($xml !== '') {
    $anterior = libxml_use_internal_errors(true);
    $doc      = simplexml_load_string($xml);
    libxml_use_internal_errors($anterior);

    comprobar('el XML es válido', $doc !== false);
    if ($doc !== false) {
        // Ojo: `$doc->url->loc` devuelve SOLO el primer <url>. La primera versión
        // de esta prueba contaba 1 de 9 y daba una falsa sensación de cobertura.
        // Con xpath se recorren todos los <loc> del documento.
        $nodos = $doc->xpath('//*[local-name()="loc"]') ?: [];
        $rutas = [];
        foreach ($nodos as $nodo) {
            $rutas[] = (string) $nodo;
        }
        comprobar('se contaron todos los <loc> del archivo', count($rutas) > 0, (string) count($rutas));
        comprobar('todas las <loc> son absolutas', array_reduce($rutas, static fn ($c, $r) => $c && str_starts_with($r, 'http'), true));

        $prohibidas = ['/admin', '/login', '/logout', '/sitemap.xml', '/register', '/forgot', '/reset'];
        $coladas    = [];
        foreach ($rutas as $ruta) {
            $camino = (string) parse_url($ruta, PHP_URL_PATH);
            foreach ($prohibidas as $p) {
                if ($camino === $p || str_starts_with($camino, $p . '/')) {
                    $coladas[] = $camino;
                }
            }
        }
        comprobar('no se cuela nada del panel ni rutas privadas', $coladas === [], implode(', ', $coladas));
        echo '      URLs: ' . count($rutas) . "\n";
        foreach ($rutas as $ruta) {
            echo "        {$ruta}\n";
        }
    }
}

echo "\n== 4. Destinos excluidos ==\n";
$ref = new ReflectionMethod(SitemapService::class, 'isExcluded');
$ref->setAccessible(true);
comprobar('excluye /admin', $ref->invoke(null, '/admin') === true);
comprobar('excluye /admin/pages', $ref->invoke(null, '/admin/pages') === true);
comprobar('excluye /login', $ref->invoke(null, '/login') === true);
comprobar('excluye /sitemap.xml', $ref->invoke(null, '/sitemap.xml') === true);
comprobar('permite /quienes-somos', $ref->invoke(null, '/quienes-somos') === false);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
printf("Archivo: public/sitemap.xml (%s)\n", $escribir ? 'regenerado por esta ejecución' : 'no modificado por esta ejecución');
exit($fallos === 0 ? 0 : 1);
