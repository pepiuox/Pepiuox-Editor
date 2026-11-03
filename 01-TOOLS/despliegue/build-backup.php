<?php
/**
 * Construye el paquete de producción en `backup/`.
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * El paquete que se sube a un servidor real no puede salir de copiar el
 * repositorio a mano: arrastra tests, documentación interna, volcados con
 * datos personales, residuos de desarrollo y el `.env` de esta máquina. Un
 * archivo olvidado en el sitio equivocado es un incidente, no una molestia.
 *
 * Este script produce el paquete completo de una sola pasada y **verifica lo
 * que produce**:
 *
 *   1. Copia a `backup/` solo la lista blanca de archivos de producción.
 *   2. Crea las carpetas vacías que la aplicación necesita escribir.
 *   3. Escribe `01-TOOLS/mysql/.env` con el dominio ya puesto: en el servidor
 *      solo se rellenan las credenciales de la base.
 *   4. Genera la base de datos limpia en una base aislada de verdad: importa
 *      el esquema, aplica las migraciones pendientes, crea el superadmin,
 *      siembra la página de introducción, limpia los registros operativos y
 *      vuelca el resultado.
 *   5. Reimporta ese volcado en una segunda base aislada y comprueba que
 *      arranca: el superadmin entra con su contraseña, la portada existe y
 *      los catálogos están completos.
 *   6. Escribe `INSTALAR.md` con la contraseña temporal y los pasos reales.
 *
 * Si cualquiera de esas comprobaciones falla, el script termina con error y
 * no se declara nada como terminado.
 *
 * ── Uso ─────────────────────────────────────────────────────────────
 *   php 01-TOOLS/despliegue/build-backup.php --dry-run
 *   php 01-TOOLS/despliegue/build-backup.php --yes
 *   php 01-TOOLS/despliegue/build-backup.php --yes --sin-db
 *
 *   --dry-run   enseña qué se copiaría y qué se descarta; no escribe nada
 *   --yes       confirma el borrado y la reconstrucción de `backup/`
 *   --sin-db    solo los archivos; no toca la base de datos
 *   --dominio=  dominio del sitio (por defecto https://pepiuox.net)
 *   --desde-base=  construye el SQL a partir de esa base existente
 *   --destino=  nombre de la carpeta del paquete (por defecto `backup`). Sirve
 *               cuando `backup/` está bloqueada por otro proceso: se construye
 *               en otra carpeta y se renombra cuando quede libre.
 *
 * ── Seguridad ───────────────────────────────────────────────────────
 * Solo CLI. No copia nunca `01-TOOLS/mysql/.env`, `.env`, `db/backup/`, los
 * volcados de referencia ni `uploads/` con contenido. La contraseña del
 * superadmin se genera aquí y su hash viaja dentro del `.sql`; por eso el
 * `INSTALAR.md` del paquete pide cambiarla en el primer acceso.
 *
 * La contraseña de root **no** se pasa por línea de comandos: se escribe un
 * archivo de opciones temporal con permisos restringidos que se borra al
 * terminar.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-11-02
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

// Las credenciales de la base viven en `01-TOOLS/mysql/.env`, que NO viaja al
// paquete. Se cargan aquí para poder crear bases de trabajo aisladas.
require __DIR__ . '/../../config/env.php';
load_env_file(__DIR__ . '/../../01-TOOLS/mysql/.env');

// ── Constantes ───────────────────────────────────────────────────────
const PROYECTO   = __DIR__ . '/../..';
const PAQUETE    = PROYECTO . '/backup';
const DEPOSITO   = PROYECTO . '/storage/preview';
const MIN_PASS   = 12;

/** Salida normal. */
function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }

/** Salida de error. */
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

/** Título de sección. */
function seccion(string $t): void { say(''); say('── ' . $t . ' ' . str_repeat('─', max(0, 60 - mb_strlen($t)))); }

// ── Argumentos ───────────────────────────────────────────────────────
$args     = array_slice($argv ?? [], 1);
$dryRun   = in_array('--dry-run', $args, true);
$si       = in_array('--yes', $args, true);
$sinDb    = in_array('--sin-db', $args, true);
$dominio  = 'https://pepiuox.net';
$desdeBase = null;
$destinoNombre = 'backup';

foreach ($args as $a) {
    if (str_starts_with($a, '--dominio=')) {
        $dominio = rtrim(substr($a, 10), '/');
    }
    if (str_starts_with($a, '--desde-base=')) {
        $desdeBase = trim(substr($a, 13));
    }
    if (str_starts_with($a, '--destino=')) {
        $destinoNombre = trim(substr($a, 10));
    }
}

if ($desdeBase !== null && $desdeBase === '') {
    oops('--desde-base= necesita el nombre de una base de datos.');
    exit(2);
}

if (!$dryRun && !$si) {
    oops('Sin --yes no se escribe nada. `backup/` se borra y se reconstruye entero.');
    oops('Usa --dry-run para ver el efecto primero.');
    exit(2);
}

$raiz = realpath(PROYECTO);
if ($raiz === false) {
    oops('No se pudo resolver la raíz del proyecto.');
    exit(1);
}
$paqueteReal = $raiz . DIRECTORY_SEPARATOR . ($destinoNombre === 'backup' ? 'backup' : $destinoNombre);

// ── Lista blanca: lo que SÍ viaja al servidor ────────────────────────
//
// Cada entrada es una carpeta (relativa a la raíz) o un archivo suelto. Lo que
// no está aquí no se copia: la lista es de inclusión, no de exclusión, porque
// un archivo nuevo en el repositorio debe decidirse a mano, no colarse solo.
$arboles = [
    'app',
    'config',
    'views',
    'public',
    'db/migrations',
    'docs',
    '01-TOOLS/bootstrap',
    '01-TOOLS/despliegue',
    '01-TOOLS/mantenimiento',
];

$sueltos = [
    'index.php',
    '.htaccess',
    'install.php',
    '01-TOOLS/.gitignore',
    '01-TOOLS/mysql/.env.example',
    '01-TOOLS/mysql/README.md',
    '01-TOOLS/mysql/CREDENTIALS.md',
    '01-TOOLS/mysql/test_connection.php',
    'uploads/.htaccess',
];

// ── Lista negra: residuos que NO pueden viajar ───────────────────────
//
// Los residuos de `app/` están documentados en docs/ESPECIFICACIONES_TECNICAS.md
// (riesgo R-03): declaran la misma clase que un archivo vivo, nadie los
// referencia y el autoloader nunca los carga. Viajar al servidor solo añade
// superficie y confusión.
$excluidos = [
    // Controladores sin ruta que declaran una clase duplicada.
    'app/Controllers/EditorController.php',
    'app/Controllers/PageController.php',
    'app/Controllers/PagesController-.php',
    'app/Controllers/PagesController--.php',
    'app/Controllers/PagesController---.php',
    'app/Controllers/PagesController-ai.php',
    'app/Controllers/PagesController1.php',
    'app/Controllers/PasswordController--.php',
    'app/Controllers/SecureEncryptionController.php',
    'app/Controllers/SecurityController.php',
    // Modelos y servicios abandonados del dominio de páginas.
    'app/Models/Page0.php',
    'app/Models/Page1.php',
    'app/Models/PageModel0.php',
    'app/Models/PageModel1.php',
    'app/Models/PageModel2.php',
    'app/Models/PageModel-ai.php',
    'app/Models/PageVersion0.php',
    'app/Services/PageService0.php',
    'app/Services/PageService-ai.php',
    'app/Services/PagePublic.php',
    'app/Services/PagePublicervice-ai.php',
    // Vistas de autenticación abandonadas (no las referencia ninguna ruta).
    'views/auth/forgot0.php',
    'views/auth/register0.php',
    'views/auth/reset0.php',
    // Maquetas de diseño y copias de hojas de estilo que no se cargan.
    'public/assets/_comparar-disenos.html',
    'public/assets/_new_prediseno-dashboard.html',
    'public/assets/_prediseno-dashboard.html',
    'public/assets/css/maker0.css',
    // Artefacto generado: el sitemap lo reescribe la aplicación al publicar.
    'public/sitemap.xml',
    // Informe interno de validación: no es documentación para quien instala.
    'docs/INFORME_DE_VALIDACION.md',
    // El propio generador del paquete no hace falta en el servidor.
    '01-TOOLS/despliegue/build-backup.php',
];

// Patrones de nombre que nunca viajan (hojas de cálculo de trabajo, binarios).
$excluirPatron = [
    '#^docs/[^/]+\.csv$#i',
    '#^docs/[^/]+\.odt$#i',
    '#(^|/)\.DS_Store$#',
    '#(^|/)Thumbs\.db$#i',
    '#~$#',
];

// ── 0. Comprobaciones previas ────────────────────────────────────────
seccion('0. Comprobaciones previas');

$faltan = [];
foreach (array_merge($arboles, $sueltos) as $ruta) {
    if (!file_exists($raiz . '/' . $ruta)) {
        $faltan[] = $ruta;
    }
}
if ($faltan !== []) {
    oops('Faltan rutas de la lista blanca: ' . implode(', ', $faltan));
    exit(1);
}
say('Lista blanca: ' . count($arboles) . ' carpetas y ' . count($sueltos) . ' archivos sueltos, todos presentes.');

// El borrado es la única operación destructiva del script. `--destino=` permite
// cambiar el nombre de la carpeta —útil cuando `backup/` está bloqueada por otro
// proceso—, así que la comprobación tiene que seguir siendo estricta: un solo
// segmento de ruta, dentro de la raíz, y nunca el nombre de una carpeta del
// proyecto que se borraría con el paquete.
$protegidos = [
    'app', 'config', 'views', 'public', 'db', 'docs', '01-TOOLS', '02-DOCS',
    'storage', 'uploads', 'tests', 'private', 'nbproject', '.git', '.rsc',
    '.dsh', '.opencode', 'automation-projects',
];

$nombreValido = $destinoNombre !== ''
    && $destinoNombre !== '.'
    && $destinoNombre !== '..'
    && !str_contains($destinoNombre, '/')
    && !str_contains($destinoNombre, '\\')
    && !in_array($destinoNombre, $protegidos, true);

if (!$nombreValido) {
    oops('--destino= no puede ser «' . $destinoNombre . '»: un solo nombre de carpeta, '
        . 'distinto de las del proyecto.');
    exit(1);
}

$padre = realpath(dirname($paqueteReal)) ?: '';
if ($padre !== $raiz || basename($paqueteReal) !== $destinoNombre) {
    oops('El destino no resuelve a <raíz>/' . $destinoNombre . ': se aborta por seguridad.');
    exit(1);
}
say('Destino: ' . $paqueteReal);

if ($dryRun) {
    say('MODO SIMULACIÓN: no se copia, no se borra y no se toca la base de datos.');
}

// ── 1. Copia de los archivos de producción ───────────────────────────
seccion('1. Archivos de producción');

if (!$dryRun) {
    if (is_dir($paqueteReal)) {
        if (!borrarArbol($paqueteReal)) {
            oops('No se pudo vaciar ' . $paqueteReal);
            exit(1);
        }
        say('backup/ vaciado.');
    }
    if (!mkdir($paqueteReal, 0775, true) && !is_dir($paqueteReal)) {
        oops('No se pudo crear ' . $paqueteReal);
        exit(1);
    }
}

$copiados = 0;
$descartados = 0;
$bytes = 0;

foreach (array_merge($arboles, $sueltos) as $ruta) {
    $origen = $raiz . '/' . $ruta;

    if (is_file($origen)) {
        $r = copiarArchivo($ruta, $origen);
        if ($r === 'copia') { $copiados++; $bytes += (int) filesize($origen); }
        else { $descartados++; }
        continue;
    }

    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($origen, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterador as $item) {
        /** @var SplFileInfo $item */
        $relativa = substr($item->getPathname(), mb_strlen($raiz) + 1);
        $relativa = str_replace('\\', '/', $relativa);

        if ($item->isDir()) {
            if (!$dryRun && !is_dir($paqueteReal . '/' . $relativa)) {
                mkdir($paqueteReal . '/' . $relativa, 0775, true);
            }
            continue;
        }

        $r = copiarArchivo($relativa, $item->getPathname());
        if ($r === 'copia') { $copiados++; $bytes += (int) $item->getSize(); }
        else { $descartados++; }
    }
}

say('Copiados: ' . $copiados . ' archivos (' . number_format($bytes / 1048576, 2) . ' MB).');
say('Descartados por lista negra: ' . $descartados . '.');

// ── 2. Carpetas escribibles vacías ───────────────────────────────────
seccion('2. Carpetas que la aplicación escribe');

$carpetasVacias = ['storage', 'storage/backups', 'storage/cache', 'storage/logs', 'storage/versions', 'uploads', 'db/install'];

foreach ($carpetasVacias as $c) {
    if ($dryRun) {
        say('  se crearía ' . $c . '/');
        continue;
    }
    $destino = $paqueteReal . '/' . $c;
    if (!is_dir($destino) && !mkdir($destino, 0775, true) && !is_dir($destino)) {
        oops('No se pudo crear ' . $c);
        exit(1);
    }
    // `.gitkeep` para que la carpeta sobreviva a un empaquetado en ZIP y se vea
    // en un listado: sobre el servidor no molesta y no lo lee nadie.
    if (!is_file($destino . '/.gitkeep') && $c !== 'db/install') {
        file_put_contents($destino . '/.gitkeep', '');
    }
    say('  ' . $c . '/');
}

// ── 3. El `.env` del paquete ─────────────────────────────────────────
seccion('3. 01-TOOLS/mysql/.env');

$cronToken = bin2hex(random_bytes(24));
$envTexto = construirEnv($dominio, $cronToken);

if ($dryRun) {
    say('  se escribiría 01-TOOLS/mysql/.env (dominio ' . $dominio . ', CRON_TOKEN generado).');
} else {
    file_put_contents($paqueteReal . '/01-TOOLS/mysql/.env', $envTexto);
    @chmod($paqueteReal . '/01-TOOLS/mysql/.env', 0600);
    say('  escrito con APP_URL=' . $dominio . ', APP_DEBUG=false, SESSION_SECURE=true.');
    say('  queda por rellenar: DB_NAME, DB_USER, DB_PASSWORD (y DB_ROOT_* si se usa).');
}

// ── 4. Base de datos limpia ──────────────────────────────────────────
seccion('4. Base de datos limpia');

$meta = [
    'generado'     => date('c'),
    'dominio'      => $dominio,
    'usuario'      => 'admin',
    'email'        => 'admin@' . preg_replace('#^https?://#', '', $dominio),
    'password'     => '',
    'sql'          => 'db/install/initial_database.sql',
    'tablas'       => 0,
    'verificado'   => false,
    'origen'       => $desdeBase ?? '',
    'paginas'      => 0,
    'menus'        => 0,
];

if ($sinDb) {
    say('Omitida (--sin-db). El paquete queda sin SQL de instalación.');
} elseif ($dryRun) {
    say($desdeBase === null
        ? '  se construiría en una base aislada y se verificaría reimportándola.'
        : '  se volcaría «' . $desdeBase . '», sin DEFINER, y se verificaría reimportándola.');
} else {
    $password = generarPassword(20);
    $meta['password'] = $password;

    $resultado = $desdeBase === null
        ? construirBaseLimpia($raiz, $paqueteReal, $password, $meta['usuario'], $meta['email'])
        : construirDesdeBase($raiz, $paqueteReal, $password, $meta['usuario'], $desdeBase);

    if (!$resultado['ok']) {
        oops('La base de datos limpia NO se pudo construir: ' . $resultado['motivo']);
        oops('El paquete queda incompleto a propósito: sin SQL no se puede instalar.');
        exit(1);
    }

    $meta['tablas']     = $resultado['tablas'];
    $meta['paginas']    = $resultado['paginas'] ?? 0;
    $meta['menus']      = $resultado['menus'] ?? 0;
    $meta['verificado'] = true;
    say('  ' . $resultado['tablas'] . ' tablas volcadas y verificadas.');
}

// ── 5. Versión de la publicación ─────────────────────────────────────
seccion('5. Versión de la publicación');

// Cada compilación es una publicación del código. El paquete lleva su versión
// escrita: el número de la aplicación, cuándo se compiló y sobre qué commit, de
// modo que un paquete suelto en un servidor se pueda identificar sin adivinar.

$version = versionAplicacion($raiz);
$commit = commitActual($raiz);
$meta['version'] = $version;
$meta['commit'] = $commit;

if ($dryRun) {
    say('  versión ' . $version . ' · commit ' . $commit);
} else {
    file_put_contents($paqueteReal . '/VERSION', textoVersion($meta, $sinDb));
    say('  escrito VERSION: ' . $version . ' · commit ' . $commit);
}

// ── 6. INSTALAR.md ───────────────────────────────────────────────────
seccion('6. INSTALAR.md');

if ($dryRun) {
    say('  se escribiría INSTALAR.md con la contraseña temporal y los pasos.');
} else {
    file_put_contents($paqueteReal . '/INSTALAR.md', construirInstalar($meta, $sinDb));
    say('  escrito: ' . ($sinDb ? 'sin contraseña (no hay base)' : 'con la contraseña temporal del superadmin.'));
}

// ── 7. Manifiesto ────────────────────────────────────────────────────
seccion('7. Manifiesto del paquete');

if (!$dryRun) {
    // El paquete no se versiona (el `.gitignore` del repositorio ya excluye
    // `backup/`), así que este archivo no existe para autoignorarse: existe para
    // que, si alguien inicializa git sobre el paquete en el servidor, los
    // secretos y los datos generados queden fuera. `01-TOOLS/despliegue/verificar.php`
    // comprueba justamente eso.
    file_put_contents($paqueteReal . '/.gitignore', "# Secretos y datos generados: no se versionan.\n"
        . "# Lo comprueba 01-TOOLS/despliegue/verificar.php.\n"
        . "01-TOOLS/mysql/.env\n"
        . ".env\n"
        . "storage/backups/\n"
        . "storage/cache/\n"
        . "storage/logs/\n"
        . "storage/versions/\n"
        . "uploads/*\n"
        . "!uploads/.htaccess\n"
        . "!uploads/.gitkeep\n");

    $total = 0;
    $mapa = [];
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($paqueteReal, FilesystemIterator::SKIP_DOTS)
    ) as $item) {
        /** @var SplFileInfo $item */
        if (!$item->isFile()) { continue; }
        $rel = str_replace('\\', '/', substr($item->getPathname(), mb_strlen($paqueteReal) + 1));
        $primer = explode('/', $rel)[0];
        $mapa[$primer] = ($mapa[$primer] ?? 0) + 1;
        $total++;
    }
    ksort($mapa);

    foreach ($mapa as $dir => $n) {
        say(sprintf('  %-22s %4d archivos', $dir, $n));
    }
    say(sprintf('  %-22s %4d archivos', 'TOTAL', $total));
}

seccion('Resultado');
if ($dryRun) {
    say('Simulación terminada. Nada escrito.');
} elseif ($sinDb) {
    say('Paquete de archivos construido. BASE DE DATOS NO INCLUIDA: usa --sin-db solo si sabes por qué.');
} else {
    say('Paquete completo y verificado.');
    say('');
    say('  Superadmin : ' . $meta['usuario'] . ' / ' . $meta['email']);
    say('  Contraseña : ' . $meta['password']);
    say('  Cambiar en el primer acceso: Perfil → Contraseña.');
}
exit(0);

// ═════════════════════════════════════════════════════════════════════
// Funciones
// ═════════════════════════════════════════════════════════════════════

/**
 * Lee la versión de la aplicación desde `config/config.php`.
 *
 * Se lee con una expresión regular en lugar de incluir el archivo: incluirlo
 * cargaría el entorno y tendría efectos que aquí no hacen falta.
 *
 * @param string $raiz Raíz del proyecto.
 * @return string Versión, o «0.0.0» si no se puede leer.
 */
function versionAplicacion(string $raiz): string
{
    $config = @file_get_contents($raiz . '/config/config.php');
    if (is_string($config) && preg_match("/'app_version'\s*=>\s*'([^']+)'/", $config, $m) === 1) {
        return $m[1];
    }

    return '0.0.0';
}

/**
 * Devuelve el commit sobre el que se compila, si git está disponible.
 *
 * @param string $raiz Raíz del proyecto.
 * @return string Hash corto, o «sin-git» si no se puede averiguar.
 */
function commitActual(string $raiz): string
{
    $git = buscarBinario('git');
    $comando = $git !== null
        ? [$git, '-C', $raiz, 'rev-parse', '--short', 'HEAD']
        : ['git', '-C', $raiz, 'rev-parse', '--short', 'HEAD'];

    $r = ejecutar($comando);
    if ($r['codigo'] !== 0) {
        return 'sin-git';
    }

    $hash = trim(explode("\n", $r['salida'])[0]);

    return preg_match('/^[0-9a-f]{7,40}$/i', $hash) === 1 ? $hash : 'sin-git';
}

/**
 * Compone el contenido del archivo `VERSION` del paquete.
 *
 * Un paquete suelto en un servidor tiene que poder identificarse sin adivinar
 * de qué publicación salió.
 *
 * @param array<string, mixed> $meta  Datos de la compilación.
 * @param bool                 $sinDb Si la compilación no incluyó base de datos.
 * @return string Contenido del archivo.
 */
function textoVersion(array $meta, bool $sinDb): string
{
    $version = (string) ($meta['version'] ?? '0.0.0');
    $commit = (string) ($meta['commit'] ?? 'sin-git');
    $origen = (string) ($meta['origen'] ?? '');
    $dominio = (string) ($meta['dominio'] ?? '');
    $fecha = date('Y-m-d H:i:s');

    $lineas = [
        'PePiuoX Editor ' . $version,
        'Compilado:        ' . $fecha,
        'Commit:           ' . $commit,
        'Dominio destino:  ' . $dominio,
        'Origen de datos:  ' . ($origen !== '' ? 'base «' . $origen . '»' : 'instalación limpia'),
    ];

    if ($sinDb) {
        $lineas[] = 'Base de datos:    no incluida en este paquete';
    } else {
        $lineas[] = 'Base de datos:    db/install/initial_database.sql';
        $lineas[] = 'Contenido:        ' . (int) ($meta['tablas'] ?? 0) . ' tablas · '
            . (int) ($meta['paginas'] ?? 0) . ' páginas · ' . (int) ($meta['menus'] ?? 0) . ' menús';
    }

    $lineas[] = '';
    $lineas[] = 'Este archivo identifica la publicación. No se edita a mano: lo escribe';
    $lineas[] = '01-TOOLS/despliegue/build-backup.php en cada compilación.';

    return implode(PHP_EOL, $lineas) . PHP_EOL;
}

/**
 * Copia un archivo al paquete, saltándolo si está en la lista negra.
 *
 * @param string $relativa Ruta relativa a la raíz del proyecto.
 * @param string $origen   Ruta absoluta de origen.
 * @return string 'copia' si se copió, 'descarta' si se saltó.
 */
function copiarArchivo(string $relativa, string $origen): string
{
    global $excluidos, $excluirPatron, $paqueteReal, $dryRun;

    if (in_array($relativa, $excluidos, true)) {
        return 'descarta';
    }
    foreach ($excluirPatron as $patron) {
        if (preg_match($patron, $relativa) === 1) {
            return 'descarta';
        }
    }

    if (!$dryRun) {
        $destino = $paqueteReal . '/' . $relativa;
        $dir = dirname($destino);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            oops('No se pudo crear el directorio ' . $dir);
            return 'descarta';
        }
        if (!copy($origen, $destino)) {
            oops('No se pudo copiar ' . $relativa);
            return 'descarta';
        }
    }

    return 'copia';
}

/**
 * Borra un directorio y todo su contenido.
 *
 * No sigue enlaces simbólicos: un enlace dentro de `backup/` apuntaría fuera y
 * borraría código del proyecto.
 *
 * @param string $dir Ruta absoluta.
 * @return bool Éxito.
 */
function borrarArbol(string $dir): bool
{
    $ok = true;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        /** @var SplFileInfo $item */
        if ($item->isLink()) {
            @unlink($item->getPathname());
        } elseif ($item->isDir()) {
            $ok = @rmdir($item->getPathname()) && $ok;
        } else {
            $ok = @unlink($item->getPathname()) && $ok;
        }
    }

    return @rmdir($dir) && $ok;
}

/**
 * Genera una contraseña aleatoria sin caracteres ambiguos.
 *
 * @param int $longitud Longitud.
 * @return string Contraseña.
 */
function generarPassword(int $longitud = 20): string
{
    $alfabeto = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $maximo = strlen($alfabeto) - 1;
    $salida = '';
    for ($i = 0; $i < $longitud; $i++) {
        $salida .= $alfabeto[random_int(0, $maximo)];
    }

    return $salida;
}

/**
 * Compone el `.env` que viaja en el paquete.
 *
 * Lleva el dominio y los indicadores de producción ya puestos, un CRON_TOKEN
 * válido y **ninguna credencial**: las de la base se rellenan en el servidor.
 *
 * @param string $dominio   URL pública sin barra final.
 * @param string $cronToken Token de las tareas programadas.
 * @return string Contenido del archivo.
 */
function construirEnv(string $dominio, string $cronToken): string
{
    return <<<ENV
# ═══════════════════════════════════════════════════════════════════
# Credenciales y configuración de {$dominio}
#
# ESTE ARCHIVO SE EDITA EN EL SERVIDOR, NO AQUÍ.
# Lo único obligatorio antes de arrancar son las cuatro claves DB_ de abajo.
# Todo lo demás ya viene puesto para producción.
# ═══════════════════════════════════════════════════════════════════

# ── Base de datos · RELLENAR ────────────────────────────────────────
# Los datos los da el panel del hosting al crear la base y el usuario.
# El usuario debe tener SOLO SELECT, INSERT, UPDATE, DELETE sobre esta base:
# la aplicación nunca modifica el esquema, así que no necesita más.

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pepiuox_net
DB_USER=
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_uca1400_ai_ci

# ── Cuenta administrativa · OPCIONAL ────────────────────────────────
# No la usa la aplicación. Solo hace falta para la consola de MySQL,
# para importar el volcado y para phpMyAdmin. Si se dejan vacías, los
# scripts que necesitan esquema pedirán las credenciales por otro lado.

DB_ROOT_USER=
DB_ROOT_PASSWORD=

# ── Sitio ───────────────────────────────────────────────────────────
# URL pública, sin barra final y sin /public. La usan el sitemap y los
# enlaces absolutos.

APP_URL={$dominio}

# Producción: nunca mostrar trazas de error al visitante.
APP_DEBUG=false

# La cookie de sesión solo viaja por HTTPS. El dominio sirve TLS.
SESSION_SECURE=true

# ── Tareas programadas ──────────────────────────────────────────────
# Sin este valor, los seis endpoints /cron/* responden 401 a propósito:
# uno de ellos escribe en la base de datos. Ya viene generado.
# Si se cambia aquí, hay que cambiarlo también en el crontab.

CRON_TOKEN={$cronToken}
ENV;
}

/**
 * Copia un volcado quitando las sentencias que fijan la base de datos.
 *
 * `CREATE DATABASE ...` y `USE ...` hacen que un volcado importado en la base
 * equivocada se instale en la base que dice el archivo, no en la que se le
 * pasa. El paquete de producción no puede llevar esa trampa.
 *
 * @param string $origen  Volcado de entrada.
 * @param string $destino Archivo filtrado que se escribe.
 * @return int|null Número de sentencias filtradas, o null si no se pudo leer.
 */
function filtrarBaseDeDatos(string $origen, string $destino): ?int
{
    $contenido = @file_get_contents($origen);
    if ($contenido === false) {
        return null;
    }

    $filtradas = 0;
    $lineas = preg_split('/\R/', $contenido) ?: [];
    $salida = [];
    $saltando = false;

    foreach ($lineas as $linea) {
        // Una sentencia puede ocupar varias líneas: `CREATE DATABASE ...`
        // suele venir partido con `DEFAULT CHARACTER SET` y `COLLATE` debajo.
        // Se descarta hasta el `;` de cierre, no solo la primera línea.
        if ($saltando) {
            if (str_contains($linea, ';')) {
                $saltando = false;
            }
            continue;
        }

        if (preg_match('/^\s*CREATE\s+DATABASE\b/i', $linea) === 1) {
            $salida[] = '-- [build-backup] sentencia de base fija retirada: ' . trim($linea);
            $filtradas++;
            if (!str_contains($linea, ';')) {
                $saltando = true;
            }
            continue;
        }

        if (preg_match('/^\s*USE\s+[`"\'\w]/i', $linea) === 1) {
            $salida[] = '-- [build-backup] sentencia de base fija retirada: ' . trim($linea);
            $filtradas++;
            continue;
        }

        $salida[] = $linea;
    }

    if (@file_put_contents($destino, implode("\n", $salida)) === false) {
        return null;
    }

    return $filtradas;
}

/**
 * Resuelve la ruta de un binario de MariaDB/MySQL.
 *
 * Orden: variable de entorno, PATH, y por último las instalaciones conocidas
 * de esta máquina. Devuelve null si no lo encuentra: el script lo dice y para,
 * en lugar de fallar a mitad.
 *
 * @param string $nombre 'mysql' o 'mysqldump'.
 * @return string|null Ruta absoluta o null.
 */
function buscarBinario(string $nombre): ?string
{
    static $cache = [];
    if (array_key_exists($nombre, $cache)) {
        return $cache[$nombre];
    }

    $variable = $nombre === 'mysql' ? 'MYSQL_BIN' : 'MYSQLDUMP_BIN';
    $desdeEnv = getenv($variable);
    if (is_string($desdeEnv) && $desdeEnv !== '' && is_file($desdeEnv)) {
        return $cache[$nombre] = $desdeEnv;
    }

    $carpetas = array_filter(explode(PATH_SEPARATOR, (string) getenv('PATH')));
    $carpetas[] = 'C:/ZampGUI/mariadb/bin';
    $carpetas[] = 'C:/xampp/mysql/bin';
    $carpetas[] = 'C:/laragon/bin/mysql/mysql-8.4-winx64/bin';
    foreach (glob('C:/Program Files/MariaDB*/bin') ?: [] as $g) {
        $carpetas[] = $g;
    }

    foreach ($carpetas as $carpeta) {
        $candidato = rtrim($carpeta, '/\\') . '/' . $nombre . '.exe';
        if (is_file($candidato)) {
            return $cache[$nombre] = $candidato;
        }
        $candidato = rtrim($carpeta, '/\\') . '/' . $nombre;
        if (is_file($candidato)) {
            return $cache[$nombre] = $candidato;
        }
    }

    return $cache[$nombre] = null;
}

/**
 * Ejecuta un comando externo y devuelve su código de salida y su salida.
 *
 * Se usa siempre con matriz de argumentos (nunca con una cadena): así ningún
 * valor puede interpretarse como opción adicional del binario.
 *
 * @param list<string>          $comando Programa y argumentos.
 * @param array<string,string> $entorno Variables de entorno a añadir.
 * @param resource|null        $stdin   Flujo de entrada, o null.
 * @return array{codigo:int,salida:string}
 */
function ejecutar(array $comando, array $entorno = [], $stdin = null): array
{
    $descriptores = [
        0 => $stdin ?? ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    // `getenv()` sin argumentos devuelve el entorno completo: un hijo que solo
    // recibiera las variables añadidas perdería PATH y todo lo demás.
    $proceso = proc_open($comando, $descriptores, $tuberias, null, array_merge(getenv(), $entorno));
    if (!is_resource($proceso)) {
        return ['codigo' => -1, 'salida' => 'no se pudo lanzar el proceso'];
    }

    $salida = (string) stream_get_contents($tuberias[1]);
    $errores = (string) stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);
    $codigo = proc_close($proceso);

    return ['codigo' => $codigo, 'salida' => trim($salida . "\n" . $errores)];
}

/**
 * Construye la base de datos limpia y la verifica.
 *
 * Pasos, todos contra bases aisladas que se borran al terminar:
 *   1. Importa `db/install/initial_database.sql` (esquema y catálogos).
 *   2. Aplica las migraciones `.up.sql` pendientes con la cuenta administrativa.
 *   3. Crea el superadmin con `create-admin.php`.
 *   4. Siembra la página de introducción con `seed-pages.php --only-inicio`.
 *   5. Vacía los registros operativos que esos dos pasos dejan escritos.
 *   6. Vuelca el resultado con `mysqldump`.
 *   7. Reimporta el volcado en una segunda base y comprueba que arranca.
 *
 * @param string $raiz     Raíz del proyecto.
 * @param string $paquete  Ruta del paquete.
 * @param string $password Contraseña temporal del superadmin.
 * @param string $usuario  Nombre de usuario del superadmin.
 * @param string $email    Correo del superadmin.
 * @return array{ok:bool,motivo:string,tablas:int}
 */
function construirBaseLimpia(string $raiz, string $paquete, string $password, string $usuario, string $email): array
{
    $mysql     = buscarBinario('mysql');
    $mysqldump = buscarBinario('mysqldump');

    if ($mysql === null || $mysqldump === null) {
        return [
            'ok' => false,
            'motivo' => 'no se encontró ' . ($mysql === null ? 'mysql' : 'mysqldump')
                . '. Define MYSQL_BIN y MYSQLDUMP_BIN en el entorno.',
            'tablas' => 0,
        ];
    }
    say('  cliente: ' . $mysql);
    say('  volcador: ' . $mysqldump);

    $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
    $puerto = (string) (getenv('DB_PORT') ?: '3306');
    $rootUser = (string) (getenv('DB_ROOT_USER') ?: '');
    $rootPass = (string) (getenv('DB_ROOT_PASSWORD') ?: '');

    if ($rootUser === '' || $rootPass === '') {
        return ['ok' => false, 'motivo' => 'faltan DB_ROOT_USER / DB_ROOT_PASSWORD en 01-TOOLS/mysql/.env', 'tablas' => 0];
    }

    // Archivo de opciones temporal: la contraseña no viaja por la línea de
    // comandos (quedaría en el historial y en la lista de procesos).
    if (!is_dir(DEPOSITO) && !mkdir(DEPOSITO, 0775, true) && !is_dir(DEPOSITO)) {
        return ['ok' => false, 'motivo' => 'no se pudo crear storage/preview', 'tablas' => 0];
    }
    $cnf = DEPOSITO . '/.build-mysql-' . bin2hex(random_bytes(4)) . '.cnf';
    file_put_contents($cnf, "[client]\nuser={$rootUser}\npassword=\"{$rootPass}\"\nhost={$host}\nport={$puerto}\nprotocol=TCP\n");
    @chmod($cnf, 0600);

    $sufijo = date('YmdHis') . '_' . bin2hex(random_bytes(3));
    $baseOrigen = 'pepiuox_build_' . $sufijo;
    $basePrueba = 'pepiuox_check_' . $sufijo;
    $pdo = null;

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$puerto};charset=utf8mb4",
            $rootUser,
            $rootPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach ([$baseOrigen, $basePrueba] as $base) {
            $pdo->exec("DROP DATABASE IF EXISTS `{$base}`");
            $pdo->exec("CREATE DATABASE `{$base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci");
        }
        say('  bases aisladas: ' . $baseOrigen . ' / ' . $basePrueba);

        // ── 1. Esquema y catálogos ───────────────────────────────────
        //
        // El volcado de origen trae `CREATE DATABASE web_editor_db` y
        // `USE web_editor_db`. Importado tal cual, la instalación acabaría en
        // una base con ese nombre aunque se le pase otra: el `USE` manda. Se
        // filtran esas dos sentencias y se importa en la base aislada.
        $fichero = $raiz . '/db/install/initial_database.sql';
        if (!is_file($fichero)) {
            return ['ok' => false, 'motivo' => 'no existe db/install/initial_database.sql', 'tablas' => 0];
        }
        $limpio = DEPOSITO . '/.build-esquema-' . bin2hex(random_bytes(4)) . '.sql';
        $filtradas = filtrarBaseDeDatos($fichero, $limpio);
        if ($filtradas === null) {
            return ['ok' => false, 'motivo' => 'no se pudo preparar el esquema para importar', 'tablas' => 0];
        }
        say('  sentencias de base fija filtradas del origen: ' . $filtradas . '.');

        $entrada = fopen($limpio, 'rb');
        $r = ejecutar(
            [$mysql, '--defaults-extra-file=' . $cnf, '--skip-ssl', '--default-character-set=utf8mb4', $baseOrigen],
            [],
            $entrada
        );
        if ($entrada !== false) {
            fclose($entrada);
        }
        @unlink($limpio);
        if ($r['codigo'] !== 0) {
            return ['ok' => false, 'motivo' => 'la importación del esquema falló: ' . $r['salida'], 'tablas' => 0];
        }
        say('  esquema importado.');

        // ── 2. Migraciones pendientes ────────────────────────────────
        //
        // `initial_database.sql` es un volcado de una fecha concreta. Cada
        // migración posterior trae su cambio, y alguna crea una tabla entera
        // (`footer_designs`). Sin este paso, una instalación nueva nace con el
        // esquema viejo. `apply.php` es idempotente: omite lo ya aplicado.
        $migraciones = glob($raiz . '/db/migrations/*.up.sql') ?: [];
        sort($migraciones);
        foreach ($migraciones as $migracion) {
            $r = ejecutar(
                [PHP_BINARY, $raiz . '/db/migrations/apply.php', '--admin', basename($migracion)],
                ['DB_NAME' => $baseOrigen]
            );
            if ($r['codigo'] !== 0) {
                return [
                    'ok' => false,
                    'motivo' => 'la migración ' . basename($migracion) . ' falló: ' . $r['salida'],
                    'tablas' => 0,
                ];
            }
            say('  migración aplicada: ' . basename($migracion));
        }

        // ── 3. Superadmin ────────────────────────────────────────────
        //
        // Los dos scripts de siembra conectan con `App\Core\Database`, que usa
        // DB_USER / DB_PASSWORD. La cuenta de la aplicación solo tiene permisos
        // sobre SU base, no sobre esta base de trabajo recién creada, así que se
        // les pasan las credenciales administrativas solo para estas dos
        // llamadas. El resultado no cambia: escriben datos, no esquema.
        $credencialesTrabajo = [
            'DB_NAME'     => $baseOrigen,
            'DB_USER'     => $rootUser,
            'DB_PASSWORD' => $rootPass,
        ];

        // Las dos tablas que este build rellena arrancan su numeración en 1: una
        // instalación limpia no debería empezar por el usuario 8 ni la página 21
        // solo porque el volcado de origen venía de una base usada.
        foreach (['users', 'pages'] as $tabla) {
            $pdo->exec("ALTER TABLE `{$baseOrigen}`.`{$tabla}` AUTO_INCREMENT = 1");
        }

        $r = ejecutar(
            [
                PHP_BINARY, $raiz . '/01-TOOLS/bootstrap/create-admin.php',
                '--username=' . $usuario, '--email=' . $email, '--yes',
            ],
            $credencialesTrabajo + ['ADMIN_PASSWORD' => $password]
        );
        if ($r['codigo'] !== 0) {
            return ['ok' => false, 'motivo' => 'no se pudo crear el superadmin: ' . $r['salida'], 'tablas' => 0];
        }
        say('  superadmin creado: ' . $usuario);

        // ── 4. Página de introducción ────────────────────────────────
        //
        // `seed-pages.php` regenera el sitemap del proyecto al terminar. Es un
        // efecto sobre el repositorio, no sobre el paquete: se guarda el
        // original y se restaura, para no dejar cambios que nadie pidió.
        $sitemap = $raiz . '/public/sitemap.xml';
        $sitemapOriginal = is_file($sitemap) ? (string) file_get_contents($sitemap) : null;

        $r = ejecutar(
            [PHP_BINARY, $raiz . '/01-TOOLS/bootstrap/seed-pages.php', '--yes', '--only-inicio'],
            $credencialesTrabajo
        );

        if ($sitemapOriginal !== null) {
            file_put_contents($sitemap, $sitemapOriginal);
        }

        if ($r['codigo'] !== 0) {
            return ['ok' => false, 'motivo' => 'no se pudo sembrar la portada: ' . $r['salida'], 'tablas' => 0];
        }
        say('  página de introducción sembrada.');

        // ── 5. Registros operativos fuera ────────────────────────────
        //
        // Los dos scripts anteriores dejan rastro: el disparador
        // `after_user_insert` escribe en `activity_log`, el sembrador toca
        // `pages`, y el login del sembrador no ocurre pero sí otras marcas.
        // Un SQL de instalación no lleva historial de nadie (RF-10).
        $operativas = [
            'activity_log', 'activity_logs', 'audit_logs', 'analytics_events', 'api_access_logs',
            'api_applications', 'api_tokens', 'notifications', 'login_attempts', 'user_logins',
            'user_sessions', 'user_activities', 'visits', 'daily_stats', 'page_statistics',
            'page_versions', 'page_autosave', 'page_cache', 'backups', 'ban_history',
            'trusted_devices', 'two_factor_recovery_codes', 'file_shares', 'reports',
            'seo_audit', 'blocked_ips', 'collaboration_events', 'collaboration_locks',
            'collaboration_messages', 'collaboration_sessions',
        ];
        $existentes = $pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = " . $pdo->quote($baseOrigen) . " AND TABLE_TYPE = 'BASE TABLE'"
        )->fetchAll(PDO::FETCH_COLUMN);

        $limpiadas = 0;
        foreach ($operativas as $tabla) {
            if (!in_array($tabla, $existentes, true)) {
                continue;
            }
            $pdo->exec("DELETE FROM `{$baseOrigen}`.`{$tabla}`");
            $limpiadas++;
        }
        say('  ' . $limpiadas . ' tablas operativas vaciadas.');

        // ── 6. Volcado ───────────────────────────────────────────────
        $destino = $paquete . '/db/install/initial_database.sql';
        if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0775, true) && !is_dir(dirname($destino))) {
            return ['ok' => false, 'motivo' => 'no se pudo crear db/install en el paquete', 'tablas' => 0];
        }
        $temporal = DEPOSITO . '/.build-dump-' . bin2hex(random_bytes(4)) . '.sql';

        $r = ejecutar([
            $mysqldump,
            '--defaults-extra-file=' . $cnf,
            '--skip-ssl',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--add-drop-table',
            '--result-file=' . $temporal,
            $baseOrigen,
        ]);
        if ($r['codigo'] !== 0 || !is_file($temporal)) {
            return ['ok' => false, 'motivo' => 'el volcado falló: ' . $r['salida'], 'tablas' => 0];
        }

        $tablas = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = " . $pdo->quote($baseOrigen) . " AND TABLE_TYPE = 'BASE TABLE'"
        )->fetchColumn();

        // Cabecera propia delante del volcado: dice qué es y qué no es.
        $volcado = (string) file_get_contents($temporal);
        @unlink($temporal);

        // `mysqldump` marca cada disparador, vista, función y procedimiento con
        // `DEFINER=`root`@`localhost``. Importado con una cuenta sin privilegios
        // globales —lo normal en un hosting— eso da ERROR 1227 («Access denied;
        // you need the SET USER privilege») y **la importación se detiene ahí**:
        // las tablas que vienen detrás del primer disparador no se crean, y las
        // vistas, disparadores y rutinas, que van al final, tampoco.
        //
        // Se retira la cláusula y cada objeto queda con el definidor de quien
        // importa, que es lo correcto en una instalación nueva: ese usuario es
        // el dueño de la base y mantiene permisos sobre las tablas que los
        // objetos necesitan.
        $definer = substr_count($volcado, 'DEFINER=`');
        $volcado = quitarDefiner($volcado);

        if (substr_count($volcado, 'DEFINER=`') !== 0) {
            return [
                'ok' => false,
                'motivo' => 'quedaron cláusulas DEFINER en el volcado y no se puede instalar sin privilegios de administrador',
                'tablas' => 0,
            ];
        }
        say('  cláusulas DEFINER retiradas: ' . $definer . ' (quedan 0).');

        $volcado = desenvolverDisparadores($volcado);
        say('  disparadores sin envoltura de versión.');

        $cabecera = cabeceraSql($tablas, $password, $usuario, $email);
        file_put_contents($destino, $cabecera . $volcado);
        say('  volcado escrito: db/install/initial_database.sql (' . number_format(filesize($destino) / 1024) . ' KB).');

        // ── 7. Verificación sobre una base nueva ─────────────────────
        $entrada = fopen($destino, 'rb');
        $r = ejecutar(
            [$mysql, '--defaults-extra-file=' . $cnf, '--skip-ssl', '--default-character-set=utf8mb4', $basePrueba],
            [],
            $entrada
        );
        if ($entrada !== false) {
            fclose($entrada);
        }
        if ($r['codigo'] !== 0) {
            return ['ok' => false, 'motivo' => 'el volcado NO se puede reimportar: ' . $r['salida'], 'tablas' => 0];
        }

        $fallos = verificarBasePrueba($pdo, $basePrueba, $password, $usuario);
        if ($fallos !== []) {
            return ['ok' => false, 'motivo' => 'la verificación del volcado falló: ' . implode(' · ', $fallos), 'tablas' => 0];
        }
        say('  verificado: reimportado en una base nueva y comprobado.');

        return ['ok' => true, 'motivo' => '', 'tablas' => $tablas];
    } catch (Throwable $e) {
        return ['ok' => false, 'motivo' => $e->getMessage(), 'tablas' => 0];
    } finally {
        @unlink($cnf);
        // Las bases de trabajo se borran siempre, también cuando algo falla:
        // dejar bases a medias en el servidor de quien construye es basura.
        if ($pdo instanceof PDO) {
            foreach ([$baseOrigen, $basePrueba] as $base) {
                try {
                    $pdo->exec("DROP DATABASE IF EXISTS `{$base}`");
                } catch (Throwable) {
                    // Si no se puede borrar, se avisa; no se oculta.
                    oops('No se pudo eliminar la base de trabajo ' . $base);
                }
            }
        }
    }
}

/**
 * Comprueba que la base recién importada sirve para arrancar.
 *
 * No basta con que el `IMPORT` no dé error: se comprueba lo que de verdad se
 * necesita para entrar y publicar.
 *
 * @param PDO    $pdo      Conexión administrativa.
 * @param string $base     Nombre de la base a comprobar.
 * @param string $password Contraseña temporal que debería validar.
 * @param string $usuario  Usuario del superadmin.
 * @return list<string> Fallos encontrados; vacío si todo cuadra.
 */
function verificarBasePrueba(PDO $pdo, string $base, string $password, string $usuario): array
{
    $fallos = [];
    $q = static fn (string $sql): mixed => $pdo->query(str_replace('{{db}}', $base, $sql))->fetchColumn();

    $tablas = (int) $q("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='{{db}}' AND TABLE_TYPE='BASE TABLE'");
    if ($tablas < 53) {
        $fallos[] = 'solo ' . $tablas . ' tablas (se esperaban 53 o más)';
    }

    // La tabla que el volcado viejo no traía.
    if ((int) $q("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='{{db}}' AND TABLE_NAME='footer_designs'") !== 1) {
        $fallos[] = 'falta la tabla footer_designs (migración sin aplicar)';
    }

    $disparadores = (int) $q("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='{{db}}'");
    if ($disparadores < 4) {
        $fallos[] = 'solo ' . $disparadores . ' disparadores (se esperaban 4)';
    }

    $vistas = (int) $q("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA='{{db}}'");
    if ($vistas < 2) {
        $fallos[] = 'solo ' . $vistas . ' vistas (se esperaban 2)';
    }

    // El superadmin: fila única, rol correcto y contraseña que valida.
    $admin = $pdo->query(
        "SELECT u.id, u.username, u.password_hash, u.role, u.role_id, r.name AS rol_catalogo
           FROM `{$base}`.`users` u
           LEFT JOIN `{$base}`.`users_roles` r ON r.idRol = u.role_id"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (count($admin) !== 1) {
        $fallos[] = 'hay ' . count($admin) . ' usuarios (se esperaba exactamente 1)';
    } else {
        $u = $admin[0];
        if ($u['username'] !== $usuario) {
            $fallos[] = 'el usuario no es «' . $usuario . '»';
        }
        if ($u['role'] !== 'Super admin') {
            $fallos[] = 'el nivel no es «Super admin» sino «' . $u['role'] . '»';
        }
        if ($u['rol_catalogo'] !== 'Super Admin') {
            $fallos[] = 'el rol del catálogo no es «Super Admin» sino «' . (string) $u['rol_catalogo'] . '»';
        }
        if (!password_verify($password, (string) $u['password_hash'])) {
            $fallos[] = 'la contraseña temporal no valida contra el hash guardado';
        }
    }

    // La portada.
    $portadas = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`pages` WHERE startpage = 1 AND is_published = 1 AND status = 'published'");
    if ($portadas !== 1) {
        $fallos[] = 'hay ' . $portadas . ' portadas publicadas (se esperaba 1)';
    }
    $contenido = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`pages` WHERE startpage = 1 AND CHAR_LENGTH(COALESCE(content_html,'')) > 2000");
    if ($contenido !== 1) {
        $fallos[] = 'la portada no tiene contenido HTML';
    }

    // Los catálogos sin los que el panel no arranca: no basta con que existan,
    // tienen que estar completos. Cada clave de esta lista la lee una vista.
    //
    // `landing.*` no está aquí a propósito: la portada comercial es opcional y
    // `LandingContentService` completa sus valores con `defaults()`, así que una
    // base sin esas claves funciona igual (la portada comercial queda apagada).
    $settings = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`settings`");
    if ($settings < 60) {
        $fallos[] = 'solo ' . $settings . ' ajustes (se esperaban 60 o más)';
    }
    $clavesObligatorias = [
        ['general', 'site_name'],
        ['public', 'theme'],
        ['public', 'color_primary'],
        ['footer', 'copyright'],
        ['seo', 'default_meta_title'],
        ['cache', 'enabled'],
    ];
    foreach ($clavesObligatorias as [$grupo, $clave]) {
        $n = (int) $pdo->query(
            "SELECT COUNT(*) FROM `{$base}`.`settings`
              WHERE `group` = " . $pdo->quote($grupo) . " AND `key` = " . $pdo->quote($clave)
        )->fetchColumn();
        if ($n !== 1) {
            $fallos[] = 'falta el ajuste ' . $grupo . '.' . $clave;
        }
    }

    $roles = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`users_roles`");
    if ($roles < 5) {
        $fallos[] = 'solo ' . $roles . ' roles';
    }
    $permisos = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`users_role_permissions` WHERE role_id = 1");
    if ($permisos < 50) {
        $fallos[] = 'el rol de superadministrador solo tiene ' . $permisos . ' permisos';
    }

    // Nada de historial de nadie.
    foreach (['activity_log', 'audit_logs', 'notifications', 'login_attempts', 'api_tokens', 'api_applications', 'user_logins'] as $tabla) {
        $n = (int) $q("SELECT COUNT(*) FROM `{{db}}`.`{$tabla}`");
        if ($n !== 0) {
            $fallos[] = 'la tabla operativa ' . $tabla . ' trae ' . $n . ' filas';
        }
    }

    return $fallos;
}

/**
 * Construye el SQL de instalación a partir de una base de datos existente.
 *
 * Se usa cuando el sitio ya está montado —contenido, menús, plantillas y
 * ajustes— y lo que se quiere es subir ESO al servidor, no una instalación en
 * blanco.
 *
 * Lo que hace, en orden:
 *   1. Vuelca la base con `mysqldump`, una fila por sentencia (más fácil de
 *      revisar y de filtrar).
 *   2. Retira las cláusulas `DEFINER` (ver `quitarDefiner()`).
 *   3. Sustituye el hash de la contraseña del superadministrador por uno de una
 *      contraseña temporal nueva, para que la que anuncia el `INSTALAR.md`
 *      funcione de verdad en el servidor.
 *   4. Verifica que el resultado se reimporta en una base nueva y que el
 *      contenido está: páginas, menús, ajustes y la cuenta.
 *
 * No modifica la base de origen: solo la lee.
 *
 * @param string $raiz     Raíz del proyecto.
 * @param string $paquete  Ruta del paquete.
 * @param string $password Contraseña temporal nueva.
 * @param string $usuario  Usuario del superadministrador.
 * @param string $base     Base de origen.
 * @return array{ok:bool,motivo:string,tablas:int,paginas:int,menus:int}
 */
function construirDesdeBase(string $raiz, string $paquete, string $password, string $usuario, string $base): array
{
    $mysqldump = buscarBinario('mysqldump');
    $mysql = buscarBinario('mysql');
    if ($mysqldump === null || $mysql === null) {
        return ['ok' => false, 'motivo' => 'no se encontró mysqldump o mysql en el sistema', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
    }

    $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
    $puerto = (string) (getenv('DB_PORT') ?: '3306');
    $rootUser = (string) (getenv('DB_ROOT_USER') ?: '');
    $rootPass = (string) (getenv('DB_ROOT_PASSWORD') ?: '');
    if ($rootUser === '' || $rootPass === '') {
        return ['ok' => false, 'motivo' => 'faltan DB_ROOT_USER / DB_ROOT_PASSWORD en 01-TOOLS/mysql/.env', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
    }

    if (!is_dir(DEPOSITO) && !mkdir(DEPOSITO, 0775, true) && !is_dir(DEPOSITO)) {
        return ['ok' => false, 'motivo' => 'no se pudo crear storage/preview', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
    }

    $cnf = DEPOSITO . '/.from-base-' . bin2hex(random_bytes(4)) . '.cnf';
    file_put_contents($cnf, "[client]\nuser={$rootUser}\npassword=\"{$rootPass}\"\nhost={$host}\nport={$puerto}\nprotocol=TCP\n");
    @chmod($cnf, 0600);

    $sufijo = date('YmdHis') . '_' . bin2hex(random_bytes(3));
    $basePrueba = 'pepiuox_frombase_' . $sufijo;
    $temporal = DEPOSITO . '/.from-base-' . bin2hex(random_bytes(4)) . '.sql';
    $pdo = null;

    try {
        $pdo = new PDO("mysql:host={$host};port={$puerto};charset=utf8mb4", $rootUser, $rootPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $existe = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($base)
        )->fetchColumn();
        if ($existe !== 1) {
            return ['ok' => false, 'motivo' => 'la base «' . $base . '» no existe', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        say('  base de origen: ' . $base);

        // ── 1. Volcado ───────────────────────────────────────────────
        $r = ejecutar([
            $mysqldump,
            '--defaults-extra-file=' . $cnf,
            '--skip-ssl',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--skip-extended-insert',
            '--default-character-set=utf8mb4',
            '--result-file=' . $temporal,
            $base,
        ]);
        if ($r['codigo'] !== 0 || !is_file($temporal)) {
            return ['ok' => false, 'motivo' => 'el volcado falló: ' . $r['salida'], 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        $volcado = (string) file_get_contents($temporal);
        @unlink($temporal);
        say('  volcado de origen: ' . number_format(strlen($volcado) / 1024) . ' KB');

        // ── 2. Sin DEFINER ───────────────────────────────────────────
        $definer = substr_count($volcado, 'DEFINER=`');
        $volcado = quitarDefiner($volcado);
        if (substr_count($volcado, 'DEFINER=`') !== 0) {
            return ['ok' => false, 'motivo' => 'quedaron cláusulas DEFINER y el archivo no se podría importar sin privilegios de administrador', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        say('  cláusulas DEFINER retiradas: ' . $definer . ' (quedan 0).');

        $volcado = desenvolverDisparadores($volcado);
        say('  disparadores sin envoltura de versión.');

        // ── 3. Contraseña del superadministrador ─────────────────────
        $filasUsuario = preg_match_all('/^INSERT INTO `users` VALUES \(/m', $volcado);
        if ($filasUsuario !== 1) {
            return [
                'ok' => false,
                'motivo' => 'la base tiene ' . $filasUsuario . ' usuarios y este generador espera exactamente 1 '
                    . '(el superadministrador del sitio). Quita los demás o dilo para adaptarlo.',
                'tablas' => 0,
                'paginas' => 0,
                'menus' => 0,
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        $sustituidos = 0;
        $volcado = (string) preg_replace_callback(
            '/^INSERT INTO `users` VALUES \(.*\);$/m',
            static function (array $m) use ($hash, &$sustituidos): string {
                // Con `preg_replace` el propio hash rompería la sustitución: sus
                // `$2` y `$12` se leen como retro-referencias y el hash sale
                // cortado. Con `preg_replace_callback` el valor va literal.
                $nueva = (string) preg_replace_callback(
                    "/'\\\$2[aby]\\\$[^']+'/",
                    static fn (): string => "'" . $hash . "'",
                    $m[0],
                    1,
                    $cuenta
                );
                $sustituidos += $cuenta;

                return $nueva;
            },
            $volcado
        );
        if ($sustituidos !== 1) {
            return ['ok' => false, 'motivo' => 'no se pudo sustituir el hash de la contraseña del superadministrador', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        say('  contraseña del superadministrador renovada para el paquete.');

        // ── 4. Nada operativo ────────────────────────────────────────
        //
        // La base de origen está limpia, pero esto no se fía: si mañana tiene
        // registros de acceso, sesiones o tokens, no viajan al servidor.
        $operativas = [
            'activity_log', 'activity_logs', 'audit_logs', 'analytics_events', 'api_access_logs',
            'api_applications', 'api_tokens', 'notifications', 'login_attempts', 'user_logins',
            'user_sessions', 'user_activities', 'visits', 'daily_stats', 'page_statistics',
            'page_versions', 'page_autosave', 'page_cache', 'backups', 'ban_history',
            'trusted_devices', 'two_factor_recovery_codes', 'file_shares', 'reports',
            'seo_audit', 'blocked_ips', 'collaboration_events', 'collaboration_locks',
            'collaboration_messages', 'collaboration_sessions',
        ];
        foreach ($operativas as $tabla) {
            $volcado = (string) preg_replace(
                '/^INSERT INTO `' . $tabla . '` VALUES .*;$\R?/m',
                '',
                $volcado
            );
        }

        // ── 5. Escribir con cabecera ─────────────────────────────────
        $tablas = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $pdo->quote($base) . " AND TABLE_TYPE = 'BASE TABLE'"
        )->fetchColumn();
        $paginas = (int) $pdo->query("SELECT COUNT(*) FROM `{$base}`.`pages`")->fetchColumn();
        $menus = (int) $pdo->query("SELECT COUNT(*) FROM `{$base}`.`menus`")->fetchColumn();

        $destino = $paquete . '/db/install/initial_database.sql';
        if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0775, true) && !is_dir(dirname($destino))) {
            return ['ok' => false, 'motivo' => 'no se pudo crear db/install en el paquete', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        file_put_contents($destino, cabeceraSql($tablas, $password, $usuario, 'admin@pepiuox.net', $base, $paginas, $menus) . $volcado);
        say('  volcado escrito: db/install/initial_database.sql (' . number_format(filesize($destino) / 1024) . ' KB).');

        // ── 6. Verificar reimportando ────────────────────────────────
        $pdo->exec("DROP DATABASE IF EXISTS `{$basePrueba}`");
        $pdo->exec("CREATE DATABASE `{$basePrueba}` CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci");

        $entrada = fopen($destino, 'rb');
        $r = ejecutar(
            [$mysql, '--defaults-extra-file=' . $cnf, '--skip-ssl', '--default-character-set=utf8mb4', $basePrueba],
            [],
            $entrada
        );
        if ($entrada !== false) {
            fclose($entrada);
        }
        if ($r['codigo'] !== 0) {
            return ['ok' => false, 'motivo' => 'el volcado NO se puede reimportar: ' . $r['salida'], 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }

        $fallos = verificarBasePrueba($pdo, $basePrueba, $password, $usuario);
        if ($fallos !== []) {
            return ['ok' => false, 'motivo' => 'la verificación del volcado falló: ' . implode(' · ', $fallos), 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }

        // El contenido tiene que haber viajado, no solo el esquema.
        $pg = (int) $pdo->query("SELECT COUNT(*) FROM `{$basePrueba}`.`pages`")->fetchColumn();
        $mn = (int) $pdo->query("SELECT COUNT(*) FROM `{$basePrueba}`.`menus`")->fetchColumn();
        if ($pg !== $paginas || $mn !== $menus) {
            return ['ok' => false, 'motivo' => 'el volcado no conservó el contenido: ' . $pg . '/' . $paginas . ' páginas, ' . $mn . '/' . $menus . ' menús', 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
        }
        say('  verificado: reimportado con ' . $pg . ' páginas y ' . $mn . ' menús.');

        return ['ok' => true, 'motivo' => '', 'tablas' => $tablas, 'paginas' => $paginas, 'menus' => $menus];
    } catch (Throwable $e) {
        return ['ok' => false, 'motivo' => $e->getMessage(), 'tablas' => 0, 'paginas' => 0, 'menus' => 0];
    } finally {
        @unlink($cnf);
        @unlink($temporal);
        if ($pdo instanceof PDO) {
            try {
                $pdo->exec("DROP DATABASE IF EXISTS `{$basePrueba}`");
            } catch (Throwable) {
                oops('No se pudo eliminar la base de trabajo ' . $basePrueba);
            }
        }
    }
}

/**
 * Escribe los disparadores como sentencias normales, sin el comentario de versión.
 *
 * `mysqldump` envuelve cada disparador en un comentario de versión que abre con
 * `/*!50003` y cierra con asterisco-barra. El cliente de MySQL cierra ese
 * comentario en el PRIMER asterisco-barra que encuentra. Si el cuerpo del
 * disparador lleva dentro un comentario de bloque —por ejemplo el del
 * `after_user_insert` de esta base, con las líneas opcionales comentadas— ese
 * cierre interno llega antes de tiempo, la sentencia se parte por el primer `;`
 * y la importación falla con `ERROR 1064`.
 *
 * Quitando la envoltura el disparador es SQL normal y el `DELIMITER ;;` que lo
 * rodea hace su trabajo. El comentario de versión solo servía para saltarse la
 * sentencia en servidores anteriores a 5.0.3; aquí se exige MariaDB 11.
 *
 * @param string $sql Volcado original.
 * @return string Volcado con los disparadores en SQL plano.
 */
function desenvolverDisparadores(string $sql): string
{
    return (string) preg_replace(
        '/\/\*!50003 CREATE\*\/ \/\*!50003 TRIGGER (.*?)\*\/;;/s',
        'CREATE TRIGGER $1;;',
        $sql
    );
}

/**
 * Retira del volcado las cláusulas `DEFINER=usuario@host`.
 *
 * Aparecen de tres formas distintas y las tres tienen que quedar limpias:
 *
 *   1. Disparadores: el comentario de versión 50017 con el DEFINER, entre el
 *      CREATE y el TRIGGER.
 *   2. Funciones y procedimientos: `CREATE DEFINER=... FUNCTION ...`, que se
 *      convierte en `CREATE FUNCTION ...`.
 *   3. Vistas: el comentario de versión 50013 con el DEFINER y
 *      `SQL SECURITY DEFINER`. Aquí solo se va el DEFINER; `SQL SECURITY`
 *      se conserva, porque es una propiedad del objeto y no una credencial.
 *
 * @param string $sql Volcado original.
 * @return string Volcado sin cláusulas DEFINER.
 */
function quitarDefiner(string $sql): string
{
    // El usuario y el host van entre comillas invertidas; se exige ese formato
    // para no tocar el `DEFINER` de `SQL SECURITY DEFINER`.
    $sql = (string) preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s*/', '', $sql);

    // Los disparadores dejan el comentario de versión vacío; se retira entero.
    return (string) preg_replace('/\/\*!500\d\d\s*\*\/\s*/', '', $sql);
}

/**
 * Compone la cabecera que va delante del volcado.
 *
 * @param int    $tablas   Número de tablas volcadas.
 * @param string $password Contraseña temporal.
 * @param string $usuario  Usuario del superadmin.
 * @param string $email    Correo del superadmin.
 * @return string Cabecera SQL.
 */
function cabeceraSql(
    int $tablas,
    string $password,
    string $usuario,
    string $email,
    ?string $origen = null,
    int $paginas = 0,
    int $menus = 0
): string {
    $fecha = date('Y-m-d');
    $hora  = date('H:i');

    $queContiene = $origen === null
        ? "--   El esquema completo ({$tablas} tablas, sus vistas, disparadores y rutinas),\n"
          . "--   los catálogos del sistema (roles, permisos, ajustes, idiomas,\n"
          . "--   traducciones), UNA cuenta superadministradora y UNA página de\n"
          . "--   introducción publicada como portada del sitio."
        : "--   El sitio ya montado, volcado desde la base «{$origen}»: el esquema completo\n"
          . "--   ({$tablas} tablas, sus vistas, disparadores y rutinas), los catálogos del\n"
          . "--   sistema, {$paginas} páginas, {$menus} menús, los ajustes de la marca y UNA\n"
          . "--   cuenta superadministradora.";

    $queNoContiene = "--   Ni historial de accesos, ni sesiones, ni aplicaciones o tokens de API, ni\n"
        . "--   registros operativos de ningún tipo: esas tablas se vacían al generar el\n"
        . "--   archivo, aunque la base de origen las tuviera con datos.";

    $nota = <<<TXT
-- ═══════════════════════════════════════════════════════════════════
-- PePiuoX Editor — BASE DE DATOS PARA PRODUCCIÓN
-- Generada: {$fecha} {$hora} · Dominio de destino: https://pepiuox.net
--
-- QUÉ CONTIENE
{$queContiene}
--
-- LA CUENTA
--   Usuario     : {$usuario}
--   Correo      : {$email}
--   Contraseña  : {$password}
--   CAMBIA ESA CONTRASEÑA EN EL PRIMER ACCESO. Está escrita en este archivo y
--   en INSTALAR.md: cualquiera que tenga el paquete la conoce.
--
-- QUÉ NO CONTIENE
{$queNoContiene}
--
-- CÓMO SE IMPORTA
--   Importa este archivo en una base de datos VACÍA:
--     mysql -u <usuario> -p <basededatos> < initial_database.sql
--   o por phpMyAdmin → Importar. No crea ni borra la base de datos: solo
--   rellena la que le des.
--
--   El usuario de la base necesita CREATE, ALTER, INDEX, REFERENCES, TRIGGER,
--   CREATE VIEW, CREATE ROUTINE, EXECUTE y SHOW VIEW para poder importar esto.
--   NO necesita privilegios de administrador: este archivo ya no lleva
--   cláusulas DEFINER, así que los disparadores, vistas y rutinas quedan a
--   nombre de quien importa. Sin esa limpieza, importarlo con una cuenta
--   normal falla con «ERROR 1227: Access denied; you need the SET USER
--   privilege» y la importación se corta a la mitad.
--
--   Después del import se le pueden retirar los permisos de esquema: la
--   aplicación solo necesita SELECT, INSERT, UPDATE y DELETE.
-- ═══════════════════════════════════════════════════════════════════

TXT;

    return $nota;
}

/**
 * Compone el `INSTALAR.md` del paquete.
 *
 * @param array<string,mixed> $meta Datos del build.
 * @param bool                $sinDb Si la base no se generó.
 * @return string Markdown.
 */
function construirInstalar(array $meta, bool $sinDb): string
{
    $dominio = (string) $meta['dominio'];
    $host = preg_replace('#^https?://#', '', $dominio);
    $usuario = (string) $meta['usuario'];
    $email = (string) $meta['email'];
    $password = (string) $meta['password'];
    $tablas = (int) $meta['tablas'];
    $fecha = date('Y-m-d');
    $version = (string) ($meta['version'] ?? '0.0.0');
    $commit = (string) ($meta['commit'] ?? 'sin-git');
    $origenTexto = ((string) ($meta['origen'] ?? '')) !== ''
        ? 'base «' . $meta['origen'] . '»'
        : 'instalación limpia';

    $bloqueCredenciales = $sinDb
        ? "> **La base de datos no se generó en este paquete.** Vuelve a construir con\n> `php 01-TOOLS/despliegue/build-backup.php --yes`."
        : <<<TXT
### Credenciales de la cuenta inicial

| | |
|---|---|
| Usuario | `{$usuario}` |
| Correo | `{$email}` |
| Contraseña | `{$password}` |

> **Cambia esa contraseña en el primer acceso** (Perfil → Contraseña). Está escrita
> en este archivo y dentro del `.sql`: cualquiera que tenga el paquete la conoce.
TXT;

    $bloqueBd = $sinDb
        ? 'Falta el archivo `db/install/initial_database.sql`.'
        : (($meta['origen'] ?? '') !== ''
            ? "`db/install/initial_database.sql` · **{$tablas} tablas** y el contenido del sitio ya montado:\n**{$meta['paginas']} páginas**, **{$meta['menus']} menús**, los ajustes de la marca y la cuenta\ninicial. Sin registros de acceso, sesiones ni tokens."
            : "`db/install/initial_database.sql` · **{$tablas} tablas**, catálogos del sistema, la cuenta\ninicial y una página de introducción publicada como portada. **Sin datos de nadie.**");

    // Se usa heredoc con marcador y no `nowdoc` porque hay variables.
    return <<<MD
# Instalación en {$host}

Paquete de producción generado el **{$fecha}** para **{$dominio}**.

| | |
|---|---|
| Versión | **{$version}** |
| Commit | `{$commit}` |
| Origen de datos | {$origenTexto} |

La versión y el commit están también en el archivo `VERSION` de la raíz del paquete.

No incluye tests, documentación interna del desarrollo, respaldos, volcados con
datos, residuos de código ni credenciales de ninguna otra máquina.

---

## 1. Requisitos del servidor

| | Mínimo |
|---|---|
| PHP | 8.2 o superior |
| Extensiones | `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`, `zip`, `gd`, `curl` |
| Base de datos | **MariaDB 11 o superior** (el esquema usa la colación `utf8mb4_uca1400_ai_ci`, que MySQL no tiene) |
| Servidor web | Apache con `mod_rewrite`, o nginx con la reescritura equivalente |
| Document root | la carpeta **`public/`**, nunca la raíz del paquete |

Comprobación, desde la raíz del paquete:

```bash
php 01-TOOLS/despliegue/verificar.php
```

---

## 2. Subir y preparar

```bash
# 1. Descomprimir fuera del document root
unzip backup.zip -d /var/www/pepiuox
cd /var/www/pepiuox

# 2. Permisos de escritura para el usuario del servidor web
sudo chown -R www-data:www-data storage uploads
sudo chmod -R 775 storage uploads
```

El document root de `{$host}` debe apuntar a **`public/`**.

---

## 3. Base de datos

Crea la base **vacía** y un usuario para la aplicación:

```sql
CREATE DATABASE pepiuox_net CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
CREATE USER 'pepiuox_app'@'localhost' IDENTIFIED BY '<contraseña nueva>';
GRANT ALL PRIVILEGES ON pepiuox_net.* TO 'pepiuox_app'@'localhost';
```

El `GRANT ALL` es **solo para poder importar** (el volcado crea tablas, índices,
disparadores, vistas y rutinas). Cuando termine la importación, redúcelo:

```sql
REVOKE ALL PRIVILEGES ON pepiuox_net.* FROM 'pepiuox_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON pepiuox_net.* TO 'pepiuox_app'@'localhost';
FLUSH PRIVILEGES;
```

La aplicación nunca modifica el esquema: con esos cuatro permisos funciona toda.

---

## 4. Importar la base limpia

{$bloqueBd}

```bash
mysql -u pepiuox_app -p pepiuox_net < db/install/initial_database.sql
```

O por phpMyAdmin: selecciona la base → **Importar** → elige el archivo → Continuar.

Si el hosting no permite importar rutinas o disparadores, pide que activen
`log_bin_trust_function_creators` o importa con el usuario administrador que da
el panel. Sin disparadores la aplicación arranca, pero las estadísticas de
página no se actualizan solas.

---

## 5. Editar el `.env`

Un solo archivo, `01-TOOLS/mysql/.env`. El dominio y los indicadores de
producción ya vienen puestos; **rellena las cuatro claves de la base**:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pepiuox_net
DB_USER=pepiuox_app
DB_PASSWORD=<la contraseña que pusiste en el paso 3>
```

Nada más es obligatorio. `APP_URL` ya vale `{$dominio}`, `APP_DEBUG=false` y
`SESSION_SECURE=true`. `CRON_TOKEN` ya viene generado.

> Si editas `CRON_TOKEN`, cámbialo también en el crontab del paso 7.

---

## 6. Primer acceso

{$bloqueCredenciales}

Entra en `{$dominio}/login`, cambia la contraseña y revisa
**Configuración → General** para poner el nombre real del sitio.

La portada que verás es la página de introducción del sistema: es una página
normal, se edita en **Páginas → Inicio → Constructor** y se puede reescribir o
sustituir entera.

---

## 7. Tareas programadas

Copia el `CRON_TOKEN` de `01-TOOLS/mysql/.env` y añade al crontab:

```cron
15 3 * * *  curl -fsS "{$dominio}/cron/backup?token=<CRON_TOKEN>" > /dev/null
*/30 * * * * curl -fsS "{$dominio}/cron/cleanup-rate-limits?token=<CRON_TOKEN>" > /dev/null
```

Los seis endpoints están documentados en `docs/CHECKLIST_PUBLICACION.md` §5. Sin
token responden 401, a propósito.

---

## 8. Qué hay dentro

```
app/                    código: controladores, modelos, servicios, núcleo
views/                  plantillas del panel y del sitio público
config/                 rutas, permisos, configuración
public/                 document root: index.php, .htaccess, assets
db/install/             el SQL limpio (el del paso 4)
db/migrations/          migraciones con su reversión
01-TOOLS/bootstrap/     crear el admin y sembrar páginas
01-TOOLS/despliegue/    verificación previa a publicar
01-TOOLS/mantenimiento/ rotar contraseñas y tokens de API
01-TOOLS/mysql/         el .env (el del paso 5)
docs/                   manual de instalación y de usuario
storage/                caché, registros y copias (vacías, escribibles)
uploads/                imágenes subidas (vacía, escribible)
```

**Fuera del paquete a propósito:** `tests/`, `02-DOCS/`, `db/backup/`, los
volcados de referencia con datos reales, `uploads/` de ejemplo, el `.env` de
desarrollo, las maquetas de diseño y los controladores y modelos duplicados
documentados como residuo en `docs/ESPECIFICACIONES_TECNICAS.md` (riesgo R-03).

---

## 9. Si algo no arranca

| Síntoma | Causa habitual |
|---|---|
| 500 en todas las rutas | El document root no es `public/`, o falta `mod_rewrite` |
| «No se puede conectar a la base» | `DB_*` mal en el `.env`, o el usuario no existe |
| `Unknown collation: utf8mb4_uca1400_ai_ci` | El servidor es MySQL, no MariaDB |
| Los assets dan 404 | El sitio no se sirve desde la raíz del dominio |
| Los `/cron/*` dan 401 | Falta `CRON_TOKEN` o no coincide con el del crontab |
| No deja guardar en el panel | Permisos de `storage/` (debe escribir el usuario del servidor web) |
| La portada responde 404 | La importación del `.sql` no llegó a completarse |
| No aparecen estadísticas | Faltan los disparadores: la importación se hizo sin privilegios |

MD;
}
