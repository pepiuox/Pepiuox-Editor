<?php

/**
 * Siembra los temas de diseño como plantillas del sistema.
 *
 *   php 01-TOOLS/bootstrap/seed-themes.php --list
 *   php 01-TOOLS/bootstrap/seed-themes.php --dry-run
 *   php 01-TOOLS/bootstrap/seed-themes.php --yes
 *   php 01-TOOLS/bootstrap/seed-themes.php --yes --only=claridad
 *
 * ── Por qué plantillas y no otra cosa ───────────────────────────────
 * `templates` ya guarda `content_html` y `content_css`, y el panel las ofrece en
 * «Páginas → Nueva página → elegir plantilla». Sembrar ahí los temas da una
 * galería real sin inventar una pantalla nueva ni otro modelo de datos: al crear
 * una página se elige un tema y se edita con el constructor.
 *
 * ── Sobre las referencias ───────────────────────────────────────────
 * Los temas se inspiran en PATRONES de sitios muy visitados (ritmo vertical,
 * jerarquía tipográfica, densidad, familia de color), no en su identidad: no se
 * copian logotipos, textos, marcas ni código de nadie. Cada tema declara de qué
 * familia de referencias toma el patrón.
 *
 * ── Reglas que cumplen ──────────────────────────────────────────────
 *  - Maqueta con la rejilla de Bootstrap 5.3 y sus utilidades.
 *  - Colores en `content_css`, como variables acotadas a una clase contenedora:
 *    un `<style>` dentro del HTML lo elimina el saneador al publicar.
 *  - Sin `<script>`: el layout público ya carga el bundle de Bootstrap.
 *  - Idempotente: no duplica plantillas (busca por nombre) y `--update` reescribe.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-11-01
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$raiz = dirname(__DIR__, 2);

// paths.php deriva la URL base de estas claves; en CLI no existen y avisaría.
$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST']   ??= 'localhost';
$_SERVER['SCRIPT_NAME'] ??= '/public/index.php';

require $raiz . '/config/paths.php';

spl_autoload_register(static function (string $class) use ($raiz): void {
    if (str_starts_with($class, 'App\\')) {
        $path = $raiz . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once $raiz . '/app/Helpers/functions.php';

function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

$args = array_slice($argv ?? [], 1);
$dryRun = in_array('--dry-run', $args, true);
$si = in_array('--yes', $args, true);
$listar = in_array('--list', $args, true);
$update = in_array('--update', $args, true);
$solo = '';
foreach ($args as $a) {
    if (str_starts_with($a, '--only=')) {
        $solo = substr($a, 7);
    }
}

// ═══════════════════════════════════════════════════════════════════
// Catálogo de temas
// ═══════════════════════════════════════════════════════════════════

$temas = require __DIR__ . '/themes/catalogo.php';

if ($listar) {
    say('Temas disponibles (' . count($temas) . '):');
    say();
    foreach ($temas as $slug => $t) {
        say('  ' . str_pad($slug, 16) . $t['name']);
        say('      ' . $t['description']);
        say('      patrón tomado de: ' . $t['inspiration']);
        say();
    }
    exit(0);
}

if ($solo !== '' && !isset($temas[$solo])) {
    oops('No existe el tema «' . $solo . '». Usa --list para verlos.');
    exit(2);
}

if ($solo !== '') {
    $temas = [$solo => $temas[$solo]];
}

try {
    $pdo = App\Core\Database::connection();
} catch (Throwable $e) {
    oops('No se pudo conectar: ' . $e->getMessage());
    exit(1);
}

$db = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
say('Base de datos destino: «' . $db . '»');
say('Modo: ' . ($dryRun ? 'simulación' : ($si ? 'escribir' : 'sin --yes no se escribe nada')));
say();

if (!$dryRun && !$si) {
    oops('Añade --yes para escribir, o --dry-run para ver el efecto.');
    exit(2);
}

$creadas = 0;
$actualizadas = 0;
$saltadas = 0;

foreach ($temas as $slug => $tema) {
    $busca = $pdo->prepare('SELECT id FROM templates WHERE name = ? LIMIT 1');
    $busca->execute([$tema['name']]);
    $id = (int) ($busca->fetchColumn() ?: 0);

    if ($id > 0 && !$update) {
        say('  = ' . $tema['name'] . ' ya existe (#' . $id . '). Usa --update para reescribirla.');
        $saltadas++;
        continue;
    }

    if ($id > 0) {
        if (!$dryRun) {
            $upd = $pdo->prepare('UPDATE templates SET description = ?, content_html = ?, content_css = ? WHERE id = ?');
            $upd->execute([$tema['description'], $tema['html'], $tema['css'], $id]);
        }
        say('  ~ ' . $tema['name'] . ' actualizada (#' . $id . ')');
        $actualizadas++;
        continue;
    }

    if (!$dryRun) {
        $ins = $pdo->prepare(
            'INSERT INTO templates (name, description, content_html, content_css, content_php, content_js,
                                    grapesjs_data, is_system_template, is_active, is_default, created_by)
             VALUES (?, ?, ?, ?, \'\', \'\', NULL, 1, 1, 0, NULL)'
        );
        $ins->execute([$tema['name'], $tema['description'], $tema['html'], $tema['css']]);
        $id = (int) $pdo->lastInsertId();
    }

    say('  + ' . $tema['name'] . ' creada' . ($id > 0 ? ' (#' . $id . ')' : ''));
    $creadas++;
}

say();
say($dryRun
    ? 'Simulación: ' . $creadas . ' por crear, ' . $actualizadas . ' por actualizar, ' . $saltadas . ' ya existentes.'
    : 'Hecho: ' . $creadas . ' creada(s), ' . $actualizadas . ' actualizada(s), ' . $saltadas . ' ya existente(s).');

if (!$dryRun && ($creadas > 0 || $actualizadas > 0)) {
    say();
    say('Ya aparecen en «Páginas → Nueva página» al elegir plantilla.');
    say('Para aplicar un tema a la portada: crea la página con esa plantilla y márcala como inicio.');
}

exit(0);
