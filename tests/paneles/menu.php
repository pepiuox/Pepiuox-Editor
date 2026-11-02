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
    'template.js ya no existe y no se carga',
    !in_array('template.js', array_map('basename', $bloques), true),
    'su contenido se ejecutaría en el editor'
);
comprobar(
    'bootstrap-blocks-consolidated.js se carga (ya sin <script> en el contenido)',
    in_array('bootstrap-blocks-consolidated.js', array_map('basename', $bloques), true),
    'se le quitaron las inyecciones: debe entrar'
);
comprobar(
    'blocks-bs5-unified.js se carga (fichero unificado)',
    in_array('blocks-bs5-unified.js', array_map('basename', $bloques), true),
    'es el fichero unificado que sustituye a los antiguos'
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

// ─────────────────────────────────────────────────────────────────────
echo "\n== Integración de los ficheros de bloques ==\n";

// Cada fichero debe registrarse contra la global que esperan (gjsEditor) y no
// puede repetir un id que ya registre otro fichero cargado: GrapesJS se queda
// con uno y el otro bloque desaparece del panel sin avisar.
// Ids que hoy se registran más de una vez y están ACEPTADOS, con el motivo.
// Cualquier otro repetido hace fallar la prueba: así una colisión nueva se ve en
// la batería en lugar de aparecer como un bloque que se pisa en el panel.
$colisionesAceptadas = [
    'hero-split' => 'blocks-bs5-unified.js y grapesjs-presentations.js definen el mismo id; gana el último en cargar',
    'hero-dark'  => 'blocks-bs5-unified.js y grapesjs-presentations.js definen el mismo id; gana el último en cargar',
    'bs-modal'   => 'bootstrap-blocks-consolidated.js y blocks-bs-advanced.js definen el mismo id; gana el último en cargar',
];

$registrados = [];
$repetidos = [];
$sinGlobal = [];

foreach ($bloques as $url) {
    $nombre = basename($url);
    $ruta = ROOT_PATH . '/public/assets/js/blocks/' . $nombre;
    $codigo = (string) file_get_contents($ruta);

    if (!str_contains($codigo, 'gjsEditor')) {
        $sinGlobal[] = $nombre;
    }

    // Dos formas de registrar: directa y mediante un helper del propio fichero.
    // El helper se detecta por su CUERPO, con llaves equilibradas. Mirar solo
    // «cerca de un BlockManager.add» daba falsos positivos: `icon('table')` es un
    // nombre de icono, no un id de bloque, y aparecía como colisión inexistente.
    $ids = [];
    preg_match_all('/BlockManager\s*\.\s*add\s*\(\s*[\'"]([^\'"]+)[\'"]/', $codigo, $mDirecta);
    $ids = $mDirecta[1];

    preg_match_all('/function\s+([A-Za-z_$][\w$]*)\s*\([^)]*\)\s*\{/', $codigo, $mFns, PREG_OFFSET_CAPTURE);
    foreach ($mFns[1] as $i => $fn) {
        $inicio = $mFns[0][$i][1] + strlen($mFns[0][$i][0]);
        $nivel = 1;
        $p = $inicio;
        $len = strlen($codigo);
        while ($p < $len && $nivel > 0) {
            if ($codigo[$p] === '{') {
                $nivel++;
            } elseif ($codigo[$p] === '}') {
                $nivel--;
            }
            $p++;
        }
        $cuerpo = substr($codigo, $inicio, $p - $inicio);

        // Solo cuenta si es el propio helper quien añade bloques.
        if (!str_contains($cuerpo, 'BlockManager')) {
            continue;
        }
        preg_match_all('/' . preg_quote($fn[0], '/') . '\s*\(\s*[\'"]([^\'"]+)[\'"]/', $codigo, $mUso);
        $ids = array_merge($ids, $mUso[1]);
    }

    // Solo cuentan los ids con forma de slug. Un helper del fichero puede
    // recibir por primer argumento un fragmento de HTML (`<h2 class="…">`), y eso
    // no es un id: sin este filtro la prueba inventa colisiones que no existen.
    $ids = array_filter($ids, static fn (string $id): bool => preg_match('/^[a-z0-9][a-z0-9._-]*$/i', $id) === 1);

    foreach (array_unique($ids) as $id) {
        if (isset($registrados[$id]) && !isset($colisionesAceptadas[$id])) {
            $repetidos[] = $id . ' (' . $registrados[$id] . ' + ' . $nombre . ')';
        }
        if (!isset($registrados[$id])) {
            $registrados[$id] = $nombre;
        }
    }
}

comprobar(
    'todos los ficheros cargados registran contra gjsEditor',
    $sinGlobal === [],
    implode(', ', $sinGlobal) . ' no usan la global: sus bloques no aparecerían'
);

comprobar(
    'no hay colisiones de id nuevas entre ficheros cargados',
    $repetidos === [],
    implode(' · ', array_slice($repetidos, 0, 6))
);

// Una exclusión que apunta a un fichero inexistente deja de aplicarse sin avisar:
// el fichero nuevo se cuela. Pasó al renombrar blocks-bs.js → el unificado se
// cargaba con su <script> dentro y nadie se enteró.
$fuenteHelper = (string) file_get_contents(APP_PATH . '/Helpers/functions.php');
$huerfanas = [];
if (preg_match('/\$excluidos = \[(.*?)\];/s', $fuenteHelper, $mExcl)) {
    preg_match_all("/'([^']+\.js)'/", $mExcl[1], $mNombres);
    foreach ($mNombres[1] as $excluido) {
        if (!is_file(ROOT_PATH . '/public/assets/js/blocks/' . $excluido)) {
            $huerfanas[] = $excluido;
        }
    }
}
comprobar(
    'toda exclusión de bloques apunta a un fichero que existe',
    $huerfanas === [],
    implode(', ', $huerfanas) . ' no existen: la exclusión quedó huérfana y ese fichero se carga sin querer'
);

comprobar(
    'el catálogo de bloques es amplio',
    count($registrados) >= 200,
    'solo se registran ' . count($registrados) . ' bloques'
);

// Los excluidos lo están por un motivo concreto.
comprobar(
    'template.js ya no existe y no se carga',
    !is_file(ROOT_PATH . '/public/assets/js/blocks/template.js')
        && !in_array('template.js', array_map('basename', $bloques), true)
);

comprobar(
    'bootstrap-blocks-consolidated.js ya no inyecta <script> dentro del contenido',
    preg_match("/['\"]\s*<\s*script/i", (string) file_get_contents(ROOT_PATH . '/public/assets/js/blocks/bootstrap-blocks-consolidated.js')) === 0,
    'volvería a ejecutar código en el lienzo: habría que excluirlo otra vez'
);

comprobar(
    'blocks-bs5-unified.js (unificado) sí se carga',
    in_array('blocks-bs5-unified.js', array_map('basename', $bloques), true),
    'el fichero unificado no se está sirviendo'
);

// Ningún fichero cargado puede meter código ejecutable dentro del contenido de
// un bloque: eso se ejecuta en el lienzo del editor. Es la razón por la que se
// excluyeron template.js y bootstrap-blocks-consolidated.js en su momento.
$conInyeccion = [];
foreach ($bloques as $url) {
    $nombre = basename($url);
    $codigo = (string) file_get_contents(ROOT_PATH . '/public/assets/js/blocks/' . $nombre);
    if (preg_match("/['\"]\s*<\s*script/i", $codigo) === 1) {
        $conInyeccion[] = $nombre;
    }
}
comprobar(
    'ningún fichero cargado inyecta <script> en el contenido',
    $conInyeccion === [],
    implode(', ', $conInyeccion) . ' meterían código ejecutable en el lienzo'
);


// ─────────────────────────────────────────────────────────────────────
echo "\n== El CSS no puede impedir cerrar una categoría ==\n";

// GrapesJS cierra una categoría con un estilo EN LÍNEA (`display: none`). Un
// `display: ... !important` sobre ese contenedor gana al estilo en línea, así que
// la categoría se marca cerrada pero las tarjetas siguen viéndose. Hace falta una
// regla explícita para el estado cerrado.
$cssBloques = (string) file_get_contents(ROOT_PATH . '/public/assets/css/blocks-library.css');

comprobar(
    'existe una regla que oculta las tarjetas al cerrar la categoría',
    preg_match('/\.gjs-open\)\s*\.gjs-blocks-c\s*\{[^}]*display:\s*none\s*!important/i', $cssBloques) === 1,
    'sin ella, el `display: grid !important` gana al display inline de GrapesJS'
);

comprobar(
    'la regla del estado cerrado es específica de la categoría',
    str_contains($cssBloques, '.gjs-block-category:not(.gjs-open)'),
    'una regla global ocultaría también las categorías abiertas'
);

// ─────────────────────────────────────────────────────────────────────
echo "\n== Posición del botón de plegar y de la topbar ==\n";

$css = (string) file_get_contents(ROOT_PATH . '/public/assets/css/maker.css');

// Las reglas se comprueban SIN comentarios: los propios comentarios mencionan
// propiedades ("Sin position:fixed…") y una búsqueda de texto los confundiría con
// declaraciones reales.
$cssSinComentarios = (string) preg_replace('#/\*.*?\*/#s', '', $css);

// El botón vive DENTRO de la topbar, en el flujo y a la izquierda del selector de
// idioma. No debe volver a `position: fixed`: la topbar lleva backdrop-filter, que
// la convierte en contenedor de los descendientes fijos, y cualquier coordenada se
// medía desde ella (el botón acababa flotando sobre el título).
comprobar(
    'el botón de plegar NO está fijado a la ventana',
    preg_match('/(?<![.\w])\.btn-toggle-sidebar\s*\{[^}]*position:\s*fixed/is', $cssSinComentarios) === 0,
    'con position:fixed el backdrop-filter de la topbar descoloca el botón'
);

// El desplazamiento del sidebar tiene que estar en .main, que es quien contiene la
// topbar; si vive solo en .main-content, la cabecera queda debajo del sidebar.
comprobar(
    'el desplazamiento del sidebar está en .main',
    preg_match('/^\.main\s*\{[^}]*margin-left:\s*var\(--sidebar-width\)/ims', $cssSinComentarios) === 1,
    'la topbar volvería a quedar tapada por el sidebar'
);

comprobar(
    '.main-content no duplica el desplazamiento',
    preg_match('/^\.main-content\s*\{[^}]*margin-left:\s*0/im', $cssSinComentarios) === 1,
    'dos márgenes sumarían el doble de separación'
);

$topbarPhp = (string) file_get_contents(VIEWS_PATH . '/partials/topbar.php');

// La cabecera real empieza la línea; un comentario podría mencionarla antes.
preg_match('/^<header class="topbar">/m', $topbarPhp, $mHeader, PREG_OFFSET_CAPTURE);
$posHeader = $mHeader[0][1] ?? false;
$posBoton = strpos($topbarPhp, 'id="toggleSidebar"');
$posIdioma = strpos($topbarPhp, 'Selector de Idioma');

comprobar(
    'el botón está dentro de la topbar, no antes de ella',
    $posHeader !== false && $posBoton !== false && $posBoton > $posHeader,
    'fuera de la topbar vuelve a depender de coordenadas de pantalla'
);

comprobar(
    'el botón queda a la izquierda del selector de idioma',
    $posBoton !== false && $posIdioma !== false && $posBoton < $posIdioma,
    'el orden visual pedido es: plegar · idioma'
);

comprobar(
    'la anchura de la hoja coincide con la del sidebar real',
    preg_match('/--sidebar-width:\s*280px/', $cssSinComentarios) === 1,
    'con 260 px, el contenido se metía 20 px bajo el menú de 280 px'
);
// ─────────────────────────────────────────────────────────────────────
echo "\n== Choque de la clase sidebar-collapsed ==\n";

// El JS pone `sidebar-collapsed` en el <body>. Si el parcial del sidebar define
// `.sidebar-collapsed { width: 72px }` sin ámbito, esa regla alcanza al BODY y
// la página entera se encoge a 72 px: es lo que ocurría al plegar el menú.
$sidebarPhp = (string) file_get_contents(VIEWS_PATH . '/partials/sidebar.php');

preg_match_all('/(?<![\w.])\.sidebar-collapsed/', $sidebarPhp, $sueltos);
comprobar(
    'ninguna regla .sidebar-collapsed del partial se escapa al body',
    $sueltos[0] === [],
    'quedan ' . count($sueltos[0]) . ' selectores sin ámbito: encogerían el <body>'
);

comprobar(
    'las reglas del menú plegado están acotadas al aside',
    str_contains($sidebarPhp, '.sidebar.sidebar-collapsed'),
    'el modificador debe exigir también la clase .sidebar'
);

comprobar(
    'maker.css solo usa sidebar-collapsed sobre body',
    preg_match('/(?<!body)\.sidebar-collapsed/', $cssSinComentarios) === 0,
    'un selector suelto en maker.css también alcanzaría al body'
);


// ─────────────────────────────────────────────────────────────────────
echo "\n== Rediseño híbrido: plegado en modo icono ==\n";

// El aside se queda como columna de iconos de 70 px en vez de salir de la
// pantalla, y el contenido acompaña con ese mismo ancho.
comprobar(
    'existe el ancho de icono (70 px)',
    preg_match('/--sidebar-icon-width:\s*70px/', $cssSinComentarios) === 1,
    'sin este token el modo icono no tiene medida'
);

comprobar(
    'el aside plegado conserva ancho en vez de desplazarse',
    preg_match('/\.sidebar\.collapsed\s*\{[^}]*width:\s*var\(--sidebar-icon-width\)/is', $cssSinComentarios) === 1,
    'si se traduce fuera, el contenido se recoloca de golpe'
);

comprobar(
    'al plegar, el contenido se ajusta al ancho de icono',
    preg_match('/body\.sidebar-collapsed \.main\s*\{[^}]*margin-left:\s*var\(--sidebar-icon-width\)/is', $cssSinComentarios) === 1,
    'con margin 0 el contenido se metería bajo el menú'
);

comprobar(
    'el aside plegado no se desplaza fuera de la pantalla',
    preg_match('/\.sidebar\.collapsed\s*\{[^}]*transform:\s*none/is', $cssSinComentarios) === 1,
    'sin transform:none ocupa 70px pero no se ve: franja vacía a la izquierda'
);
comprobar(
    'el ítem activo del menú lleva marca visual propia',
    preg_match('/\.sidebar \.nav-link\.active::before/', $cssSinComentarios) === 1,
    'la señal de dónde estás es la barra de acento'
);
echo "\n" . str_repeat('-', 56) . "\n";
printf("Comprobaciones: %d · Fallos: %d\n", $total, $fallos);
exit($fallos === 0 ? 0 : 1);
