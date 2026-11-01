<?php

declare(strict_types=1);

/**
 * Renderiza el menú lateral como lo vería un Super Admin, para revisarlo sin navegador.
 *
 *   php tests/paneles/menu.php
 *
 * Escribe `storage/preview/menu.html` con el `<aside>` completo y comprueba que
 * las entradas nuevas están presentes y que se enciende una sola por pantalla.
 *
 * Para qué: el menú solo se pinta con sesión iniciada, así que una petición HTTP
 * anónima nunca lo atraviesa. Esto lo ejecuta de verdad, con la sesión simulada
 * y el usuario real de la base de datos.
 */

$_SERVER['SERVER_PORT'] ??= '80';
$_SERVER['HTTP_HOST']   ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/admin/settings/footer';

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

// Sesión simulada con el primer Super Admin real.
$pdo = App\Core\Database::connection();
$admin = $pdo->query("SELECT * FROM users WHERE role = 'Super admin' AND is_active = 1 ORDER BY id ASC LIMIT 1")
    ->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    fwrite(STDERR, "No hay ningún Super admin en la base: no se puede simular la sesión.\n");
    exit(1);
}

$_SESSION['user_id']   = (int) $admin['id'];
$_SESSION['user_role'] = (string) $admin['role'];

$current = $admin;
$cfg     = require CONFIG_PATH . '/config.php';

/**
 * Pinta el menú con una URI concreta y devuelve el HTML.
 */
$renderCon = static function (string $uri) use ($current, $cfg): string {
    $_SERVER['REQUEST_URI'] = $uri;

    ob_start();
    // Variables que el partial espera; el propio archivo ya define sus fallbacks.
    include VIEWS_PATH . '/partials/sidebar.php';

    return (string) ob_get_clean();
};

echo "== El menú se pinta con sesión de Super Admin ==\n";
$html = $renderCon('/admin/settings/footer');
comprobar('el menú produce HTML', strlen($html) > 3000, strlen($html) . ' bytes');
comprobar('no hay avisos de PHP en la salida', !preg_match('/(Warning|Fatal error|Notice):/', $html));

echo "\n== Accesos a los editores ==\n";
comprobar('aparece la entrada «Pie de página»', str_contains($html, '>Pie de página<') || (bool) preg_match('/>\s*Pie de página\s*</', $html));
comprobar('aparece la entrada «Portada»', (bool) preg_match('/>\s*Portada\s*</', $html));
comprobar('aparece el grupo «Web pública»', (bool) preg_match('/>\s*Web pública\s*</', $html));
comprobar('enlaza a /admin/settings/footer', str_contains($html, 'admin/settings/footer'));
comprobar('enlaza a /admin/settings/landing', str_contains($html, 'admin/settings/landing'));

echo "\n== Se enciende una sola entrada por pantalla ==\n";
$cuenta = static fn (string $h, string $frag): int => substr_count($h, $frag);

// En el editor del pie: «Pie de página» encendida y «Configuración» apagada.
comprobar(
    'en /admin/settings/footer solo se enciende «Pie de página»',
    (bool) preg_match('/nav-link active"[^>]*href="[^"]*admin\/settings\/footer/', $html)
        && !preg_match('/nav-link active"[^>]*href="[^"]*\/admin\/settings"/', $html)
);

// En Configuración: solo Configuración.
$htmlConfig = $renderCon('/admin/settings');
comprobar(
    'en /admin/settings solo se enciende «Configuración»',
    (bool) preg_match('/nav-link active"[^>]*href="[^"]*\/admin\/settings"/', $htmlConfig)
        && !preg_match('/nav-link active"[^>]*href="[^"]*admin\/settings\/footer/', $htmlConfig)
);

// En la portada: solo Portada.
$htmlPortada = $renderCon('/admin/settings/landing');
comprobar(
    'en /admin/settings/landing solo se enciende «Portada»',
    (bool) preg_match('/nav-link active"[^>]*href="[^"]*admin\/settings\/landing/', $htmlPortada)
        && !preg_match('/nav-link active"[^>]*href="[^"]*\/admin\/settings"/', $htmlPortada)
);

// Copia para revisarla a ojo.
$dir = dirname(__DIR__, 2) . '/storage/preview';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
file_put_contents($dir . '/menu.html', $html);
echo "\nCopia del menú: storage/preview/menu.html (" . number_format(strlen($html)) . " bytes)\n";

// ─────────────────────────────────────────────────────────────────────
echo "\n== Constructor del pie (GrapesJS) — PÁGINA COMPLETA ==\n";

// Se renderiza con el layout del panel, no la vista suelta. La versión anterior
// de esta prueba usaba `View::partial` y por eso no detectó que un `</script>`
// literal en un comentario cerraba el bloque antes de tiempo y el resto del
// código salía como texto visible.
$_SERVER['REQUEST_URI'] = '/admin/settings/footer/builder';

try {
    $htmlBuilder = App\Core\View::render('settings.footer-builder', [
        'design'    => [
            'id' => 1, 'name' => 'Prueba', 'content_html' => '<p>x</p>', 'content_css' => '',
            'content_js' => '', 'grapesjs_data' => '', 'is_active' => 0,
        ],
        'assets'    => (new App\Services\AssetUploadService())->existing(),
        'uploadUrl' => url('admin/settings/footer/builder/upload'),
    ]);
} catch (Throwable $e) {
    $htmlBuilder = '';
    oops('Error al renderizar el constructor: ' . $e->getMessage());
}

comprobar('la página completa se renderiza', strlen($htmlBuilder) > 20000, (string) strlen($htmlBuilder));
comprobar('no hay avisos de PHP', !preg_match('/(Warning|Fatal error|Notice):/', $htmlBuilder));

// El fallo que rompía el editor: un cierre de script literal dentro de un
// comentario JS. Se detecta por desbalance entre aperturas y cierres.
$abre   = substr_count($htmlBuilder, '<script');
$cierra = substr_count($htmlBuilder, '</script>');
comprobar(
    'las etiquetas <script> están balanceadas',
    $abre === $cierra,
    "abren {$abre} y cierran {$cierra}: algo corta un bloque antes de tiempo"
);

// Ningún cuerpo de script puede contener etiquetas de script: si aparecen, el
// navegador las interpreta y el resto del código se pinta como texto.
$cuerposRotos = 0;
if (preg_match_all('#<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is', $htmlBuilder, $cuerpos)) {
    foreach ($cuerpos[1] as $cuerpo) {
        if (preg_match('#</?script#i', $cuerpo)) {
            $cuerposRotos++;
        }
    }
}
comprobar('ningún bloque de script contiene etiquetas de script', $cuerposRotos === 0, (string) $cuerposRotos);

comprobar('existe el contenedor de GrapesJS', str_contains($htmlBuilder, 'id="gjs"'));
comprobar('carga el núcleo de GrapesJS', str_contains($htmlBuilder, 'grapesjs@'));
comprobar('carga la utilidad compartida EditorKit', str_contains($htmlBuilder, 'grapesjs-shared.js'));
comprobar('carga la biblioteca de bloques', str_contains($htmlBuilder, 'blocks-library.js'));
comprobar('declara la lista de bloques en el HTML servido',
    (bool) preg_match('/const BLOCK_SCRIPTS = \[[^\]]*js\/blocks\//s', $htmlBuilder));
comprobar('llama a loadBlockScripts', str_contains($htmlBuilder, 'loadBlockScripts'));
comprobar('configura el gestor de imágenes (assetManager)', str_contains($htmlBuilder, 'assetManager:'));
comprobar('el gestor recibe las imágenes existentes', str_contains($htmlBuilder, 'assets: ASSETS'));
comprobar('el gestor apunta al endpoint de subida',
    str_contains($htmlBuilder, '/admin/settings/footer/builder/upload'));
comprobar('tiene los cuatro campos que se guardan', str_contains($htmlBuilder, 'name="content_html"')
    && str_contains($htmlBuilder, 'name="content_css"')
    && str_contains($htmlBuilder, 'name="content_js"')
    && str_contains($htmlBuilder, 'name="grapesjs_data"'));
comprobar('tiene el interruptor de publicación', str_contains($htmlBuilder, 'name="builder_enabled"'));
comprobar('tiene token CSRF', str_contains($htmlBuilder, '_csrf_token'));

// ─────────────────────────────────────────────────────────────────────
echo "\n== Contrato de la instancia: los bloques necesitan la global gjsEditor ==\n";

// Los ficheros de bloques registran con `gjsEditor.BlockManager.add(...)` y
// empiezan con `if (typeof gjsEditor === 'undefined') { …omitido }`. Si la
// instancia se llama de otra forma, no se registra NI UN bloque y el panel
// derecho del constructor aparece vacío. Es el fallo que tuvo el editor del pie.
comprobar(
    'la vista publica la instancia como window.gjsEditor',
    (bool) preg_match('/window\.gjsEditor\s*=\s*grapesjs\.init\s*\(/', $htmlBuilder),
    'con otro nombre, todos los ficheros de bloques se omiten'
);

comprobar(
    'la carga de bloques recibe esa misma instancia',
    (bool) preg_match('/loadBlockScripts\(\s*BLOCK_SCRIPTS\s*,\s*window\.gjsEditor/', $htmlBuilder),
    'los bloques se registrarían en un editor distinto del que se ve'
);

// El contrato se comprueba también en el lado de los ficheros de bloques: si
// algún día dejan de usar `gjsEditor`, esta prueba pedirá revisar la vista.
$bloques = glob(ROOT_PATH . '/public/assets/js/blocks/*.js') ?: [];
$conGjsEditor = 0;
foreach ($bloques as $f) {
    if (str_contains((string) file_get_contents($f), 'gjsEditor')) {
        $conGjsEditor++;
    }
}
comprobar(
    'los ficheros de bloques registran contra gjsEditor',
    $conGjsEditor > 0,
    'los bloques ya no usan esa global: revisa el nombre en la vista'
);
comprobar(
    'los otros dos editores también la usan',
    str_contains((string) file_get_contents(VIEWS_PATH . '/pages/builder.php'), 'gjsEditor')
        && str_contains((string) file_get_contents(VIEWS_PATH . '/templates/edit.php'), 'gjsEditor'),
    'páginas y plantillas asignan gjsEditor: los tres deben coincidir'
);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Bloques del constructor: la misma lista en los tres editores ==\n";

$bloques = editor_block_urls();

comprobar('hay ficheros de bloques en disco', $bloques !== [], (string) count($bloques));
comprobar(
    'excluye bs-blocks.js (duplica cinco ids con contenido distinto)',
    !in_array('bs-blocks.js', array_map('basename', $bloques), true),
    'volvería a pisar los bloques de blocks-bs.js'
);
comprobar(
    'excluye template.js (mete un <script> en el lienzo)',
    !in_array('template.js', array_map('basename', $bloques), true),
    'su contenido se ejecutaría en el editor'
);
comprobar('la lista está ordenada alfabéticamente', array_map('basename', $bloques) === (static function (array $a) {
    sort($a);
    return $a;
})(array_map('basename', $bloques)));

foreach ([
    'views/pages/builder.php'      => 'editor de páginas',
    'views/templates/edit.php'     => 'editor de plantillas',
    'views/settings/footer-builder.php' => 'constructor del pie',
] as $archivo => $etiqueta) {
    $fuente = (string) file_get_contents(ROOT_PATH . '/' . $archivo);
    comprobar(
        'el ' . $etiqueta . ' usa la lista compartida',
        str_contains($fuente, 'editor_block_urls()'),
        'tendría su propia lista y volvería a divergir'
    );
    comprobar(
        'el ' . $etiqueta . ' no recorre la carpeta de bloques por su cuenta',
        !preg_match("/glob\([^)]*js\/blocks/", $fuente),
        'habría duplicado la regla de exclusiones'
    );
}

// El constructor del pie debe recibir la lista ya resuelta.
comprobar(
    'el constructor del pie pasa los bloques al editor',
    str_contains($htmlBuilder, 'BLOCK_SCRIPTS') && str_contains($htmlBuilder, 'loadBlockScripts')
);

echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
