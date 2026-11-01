<?php

declare(strict_types=1);

/**
 * Prueba del catálogo de diseños del pie (`footer_designs`).
 *
 *   DB_NAME=<base_de_pruebas> php tests/footer/designs.php
 *
 * Escribe de verdad: crea, activa, duplica y borra diseños. **Úsala solo contra
 * una base de pruebas.** Si `DB_NAME` termina en `_test` o empieza por `zz_` se
 * da por buena; en cualquier otro caso el script se niega a ejecutarse.
 *
 * Qué comprueba:
 *  - crear exige nombre, lo recorta y rechaza duplicados
 *  - solo un diseño puede estar activo a la vez
 *  - el diseño activo es el que pinta el sitio (`builder()`)
 *  - desactivar devuelve el pie al modo de campos
 *  - duplicar copia el contenido con nombre libre
 *  - borrar el activo se niega; borrar uno inactivo funciona
 *  - los topes de tamaño se respetan
 */

$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

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

use App\Services\FooterContentService;

$total = 0;
$fallos = 0;

function comprobar(string $descripcion, bool $ok, string $detalle = ''): void
{
    global $total, $fallos;
    $total++;
    if ($ok) {
        echo "  OK    {$descripcion}\n";
        return;
    }
    $fallos++;
    echo "  FALLO {$descripcion}" . ($detalle !== '' ? " -> {$detalle}" : '') . "\n";
}

$db = (string) (getenv('DB_NAME') ?: '');
if ($db === '') {
    fwrite(STDERR, "Falta DB_NAME. Esta prueba escribe: apunta a una base de pruebas.\n");
    exit(2);
}
if (!str_ends_with($db, '_test') && !str_starts_with($db, 'zz_')) {
    fwrite(STDERR, "DB_NAME=\"{$db}\" no parece una base de pruebas. Abortado sin tocar nada.\n");
    exit(2);
}

echo "Base de pruebas: {$db}\n\n";

// Limpieza inicial: la tabla debe existir y estar vacía.
try {
    App\Core\Database::execute('DELETE FROM footer_designs');
} catch (Throwable $e) {
    fwrite(STDERR, "No existe la tabla footer_designs en {$db}: aplica la migración primero.\n");
    exit(2);
}

// ─────────────────────────────────────────────────────────────────────
echo "== Crear ==\n";

$vacio = FooterContentService::createDesign('   ');
comprobar('un nombre vacío se rechaza', !$vacio['ok'] && $vacio['id'] === null);

$uno = FooterContentService::createDesign('  Pie oscuro  ', 1);
comprobar('se crea con nombre recortado', $uno['ok'] && $uno['id'] > 0, (string) $uno['error']);

$repetido = FooterContentService::createDesign('Pie oscuro', 1);
comprobar('un nombre repetido se rechaza', !$repetido['ok'], 'permitiría dos diseños con el mismo nombre');

$largo = FooterContentService::createDesign(str_repeat('x', 200), 1);
comprobar('un nombre demasiado largo se rechaza', !$largo['ok']);

$dos = FooterContentService::createDesign('Pie mínimo', 1);
comprobar('se puede crear un segundo diseño', $dos['ok'] && $dos['id'] > 0);

$lista = FooterContentService::designs();
comprobar('el listado devuelve los dos', count($lista) === 2, (string) count($lista));
comprobar('el listado trae el nombre recortado', $lista[0]['name'] === 'Pie mínimo');

// ─────────────────────────────────────────────────────────────────────
echo "\n== Guardar contenido ==\n";

$guardado = FooterContentService::saveDesign((int) $uno['id'], [
    'content_html'  => '<div class="diseno-uno">HOLA-UNO</div>',
    'content_css'   => '.diseno-uno{color:red}',
    'content_js'    => '',
    'grapesjs_data' => '{"pages":[]}',
]);
comprobar('el contenido se guarda', $guardado['ok'], implode(' ', $guardado['errores']));

$leido = FooterContentService::findDesign((int) $uno['id']);
comprobar('el HTML se lee de vuelta', str_contains((string) $leido['content_html'], 'HOLA-UNO'));

$enorme = FooterContentService::saveDesign((int) $uno['id'], [
    'content_html' => str_repeat('x', FooterContentService::MAX_HTML + 1),
    'content_css' => '', 'content_js' => '', 'grapesjs_data' => '',
]);
comprobar('un HTML que pasa del tope se rechaza', !$enorme['ok'] && $enorme['errores'] !== []);

$inexistente = FooterContentService::saveDesign(999999, ['content_html' => 'x']);
comprobar('guardar en un diseño inexistente falla', !$inexistente['ok']);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Activar: solo uno a la vez ==\n";

FooterContentService::flush();
comprobar('sin nada activo, no hay diseño', FooterContentService::activeDesign() === null);
comprobar('sin nada activo, el sitio usa el pie por campos', !FooterContentService::usesBuilder());

comprobar('activar el primero funciona', FooterContentService::activateDesign((int) $uno['id']));
FooterContentService::flush();

$activo = FooterContentService::activeDesign();
comprobar('el activo es el primero', $activo !== null && (int) $activo['id'] === (int) $uno['id']);
comprobar('el sitio pasa a usar el constructor', FooterContentService::usesBuilder());
comprobar('builder() devuelve su HTML', str_contains(FooterContentService::builder()['html'], 'HOLA-UNO'));

FooterContentService::activateDesign((int) $dos['id']);
FooterContentService::flush();
$cuantosActivos = 0;
foreach (FooterContentService::designs() as $d) {
    if ($d['is_active']) {
        $cuantosActivos++;
    }
}
comprobar('al activar otro, queda UNO solo activo', $cuantosActivos === 1, (string) $cuantosActivos);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Duplicar y borrar ==\n";

$copia = FooterContentService::duplicateDesign((int) $uno['id'], 1);
comprobar('duplicar crea un diseño nuevo', $copia['ok'] && (int) $copia['id'] !== (int) $uno['id']);

$original = FooterContentService::findDesign((int) $uno['id']);
$copiaFila = FooterContentService::findDesign((int) $copia['id']);
comprobar('la copia trae el mismo HTML', $copiaFila['content_html'] === $original['content_html']);
comprobar('la copia NO queda activa', (int) $copiaFila['is_active'] === 0);

$borrarActivo = FooterContentService::deleteDesign((int) $dos['id']);
comprobar('borrar el activo se niega', !$borrarActivo['ok'], 'dejaría el sitio sin pie');

$borrarInactivo = FooterContentService::deleteDesign((int) $uno['id']);
comprobar('borrar uno inactivo funciona', $borrarInactivo['ok'], $borrarInactivo['error']);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Volver al pie por campos ==\n";

comprobar('desactivar funciona', FooterContentService::deactivate());
FooterContentService::flush();
comprobar('ya no hay diseño activo', FooterContentService::activeDesign() === null);
comprobar('el sitio vuelve al pie por campos', !FooterContentService::usesBuilder());

App\Core\Database::execute('DELETE FROM footer_designs');

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
