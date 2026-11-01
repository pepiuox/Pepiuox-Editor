<?php

declare(strict_types=1);

/**
 * Comprobación de la portada comercial, sin navegador.
 *
 * Ejecuta:
 *   php tests/landing/check.php
 *
 * Qué comprueba, y por qué cada cosa:
 *  1. Que el servicio devuelva contenido con la forma esperada (si no, la vista
 *     pinta secciones vacías y nadie se entera hasta verlo).
 *  2. Que la vista renderice las seis secciones con identificadores estables:
 *     los botones del hero apuntan a ellas por ancla.
 *  3. Que escape el contenido. El texto es editable desde el panel, así que un
 *     título con `<script>` no puede salir crudo.
 *  4. Que no haya atributos `style="..."`: el proyecto los eliminó del resto de
 *     vistas y la portada no va a reintroducirlos.
 *  5. Que el JSON del servicio rechace destinos peligrosos (`javascript:`).
 *
 * Sale con código 0 si todo pasa y 1 si algo falla. No escribe en la base de
 * datos: usa los valores por defecto del servicio, que son los mismos que
 * sirve una instalación recién creada.
 */

// paths.php lee SERVER_PORT; en CLI no existe y emite un aviso. Se define para
// que la salida de la prueba sea limpia y un aviso esconda un fallo real.
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
$defaults = LandingContentService::defaults();

$clavesEsperadas = [
    'enabled', 'hero_kicker', 'hero_title', 'hero_subtitle', 'hero_description',
    'hero_image', 'hero_cta_primary', 'hero_cta_secondary', 'sections',
    'features', 'specs', 'benefits', 'gallery', 'footer_note',
];
foreach ($clavesEsperadas as $clave) {
    comprobar("existe la clave «{$clave}»", array_key_exists($clave, $defaults));
}

comprobar('la portada nace desactivada (ADR-006)', $defaults['enabled'] === false);
comprobar('hay 8 tarjetas de funciones', count($defaults['features']) === 8, (string) count($defaults['features']));
comprobar('hay 8 especificaciones', count($defaults['specs']) === 8, (string) count($defaults['specs']));
comprobar('hay 4 beneficios', count($defaults['benefits']) === 4, (string) count($defaults['benefits']));
comprobar('la galería arranca vacía', $defaults['gallery'] === []);
comprobar('existe el texto de las cinco secciones', count($defaults['sections']) === 5);

echo "\n== 2. Renderizado de la vista ==\n";
$landing = $defaults;
$html    = View::renderStandalone('public.landing', ['landing' => $landing, 'appName' => 'PePiuoX Editor']);

comprobar('la vista produce HTML', strlen($html) > 2000, strlen($html) . ' bytes');

foreach (['inicio', 'presentacion', 'caracteristicas', 'especificaciones', 'beneficios', 'experiencia'] as $id) {
    comprobar("existe la sección #{$id}", str_contains($html, 'id="' . $id . '"'));
}

comprobar('hay un solo <h1>', substr_count($html, '<h1') === 1, (string) substr_count($html, '<h1'));
comprobar('hay cinco <h2>', substr_count($html, '<h2') === 5, (string) substr_count($html, '<h2'));

// El ancla del botón secundario debe apuntar a una sección que exista.
$ancla = $landing['hero_cta_secondary']['href'];
comprobar(
    'el botón secundario apunta a una sección existente (' . $ancla . ')',
    str_starts_with($ancla, '#') && str_contains($html, 'id="' . substr($ancla, 1) . '"')
);

echo "\n== 3. Escape del contenido editable ==\n";
$malicioso = $defaults;
$malicioso['hero_title']    = '<script>alert(1)</script>Titular';
$malicioso['hero_kicker']   = '"><img src=x onerror=alert(1)>';
$malicioso['features'][0]['title'] = '<b>negrita</b> no permitida';

$htmlMal = View::renderStandalone('public.landing', ['landing' => $malicioso, 'appName' => 'X']);

comprobar('el <script> del titular sale escapado', !str_contains($htmlMal, '<script>alert(1)</script>'));
comprobar('el <script> aparece como entidad', str_contains($htmlMal, '&lt;script&gt;'));
// La cadena «onerror=alert» sigue apareciendo, pero como TEXTO escapado dentro
// de un atributo: lo que no puede aparecer es la etiqueta <img cruda. La
// primera versión de esta prueba daba por fallo lo correcto.
comprobar('la inyección en el antetítulo no crea una etiqueta', !str_contains($htmlMal, '<img src=x'));
comprobar('la inyección queda como entidad', str_contains($htmlMal, '&lt;img src=x'));
comprobar('el HTML de una tarjeta se escapa', !str_contains($htmlMal, '<b>negrita</b>'));

echo "\n== 4. Sin estilos en línea ==\n";
comprobar('la vista no usa atributos style=«»', !preg_match('/\sstyle\s*=/i', $html) === true);
comprobar('el escape no introduce estilos', !preg_match('/\sstyle\s*=/i', $htmlMal) === true);

echo "\n== 5. La vista aguanta listas vacías ==\n";
$vacio = $defaults;
$vacio['features'] = [];
$vacio['specs']    = [];
$vacio['benefits'] = [];
$htmlVacio = View::renderStandalone('public.landing', ['landing' => $vacio, 'appName' => 'X']);
comprobar('las secciones siguen existiendo sin tarjetas', str_contains($htmlVacio, 'id="caracteristicas"'));
comprobar('se muestra un estado vacío', str_contains($htmlVacio, 'landing-empty'));

echo "\n== 6. Validación de destinos de los botones ==\n";
$ref = new ReflectionMethod(LandingContentService::class, 'sanitizeCta');
$ref->setAccessible(true);

comprobar('acepta una ruta interna', $ref->invoke(null, ['label' => 'Ir', 'href' => '/login']) !== null);
comprobar('acepta un ancla', $ref->invoke(null, ['label' => 'Ir', 'href' => '#seccion']) !== null);
comprobar('acepta una URL https', $ref->invoke(null, ['label' => 'Ir', 'href' => 'https://ejemplo.com/x']) !== null);
comprobar('rechaza javascript:', $ref->invoke(null, ['label' => 'Ir', 'href' => 'javascript:alert(1)']) === null);
comprobar('rechaza data:', $ref->invoke(null, ['label' => 'Ir', 'href' => 'data:text/html,<script>']) === null);
comprobar('rechaza protocol-relative //', $ref->invoke(null, ['label' => 'Ir', 'href' => '//evil.example']) === null);
comprobar('rechaza una etiqueta vacía', $ref->invoke(null, ['label' => '', 'href' => '/login']) === null);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
