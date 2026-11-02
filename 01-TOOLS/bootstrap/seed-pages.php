<?php
/**
 * Siembra las páginas de presentación del sistema.
 *
 * ── Por qué es un script y no un .sql ───────────────────────────────
 * El contenido son párrafos en español con acentos, comillas y apóstrofos.
 * Escribirlo dentro de literales SQL obliga a escapar a mano y un solo fallo
 * rompe la sentencia a la mitad. Con sentencias preparadas el texto viaja como
 * parámetro y no hay nada que escapar.
 *
 * ── Qué hace, exactamente ───────────────────────────────────────────
 *  1. Crea las páginas que falten (`funciones`, `seguridad`, `como-funciona`)
 *     con contenido coherente con lo que el sistema hace de verdad.
 *  2. Las enlaza en el menú predeterminado de `header`, si no están ya.
 *  3. Con `--inicio`, reescribe el contenido de la página marcada como inicio.
 *
 * **No borra ninguna página existente.** Si un slug ya existe, se salta salvo
 * que se pase `--update`.
 *
 * ── Uso ─────────────────────────────────────────────────────────────
 *   php 01-TOOLS/bootstrap/seed-pages.php --dry-run
 *   php 01-TOOLS/bootstrap/seed-pages.php --yes
 *   php 01-TOOLS/bootstrap/seed-pages.php --yes --update
 *   php 01-TOOLS/bootstrap/seed-pages.php --yes --inicio `
 *
 * El destino se imprime y se pide confirmar, igual que en `create-admin.php`:
 * escribir en la base equivocada es un error con consecuencias.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-10-31
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

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

/** Salida normal. */
function say(string $l = ''): void { fwrite(STDOUT, $l . PHP_EOL); }

/** Salida de error. */
function oops(string $l): void { fwrite(STDERR, $l . PHP_EOL); }

$flags = array_map(
    static fn (string $a): string => ltrim($a, '-'),
    array_filter(array_slice($argv ?? [], 1), static fn (string $a): bool => str_starts_with($a, '--'))
);

$dryRun   = in_array('dry-run', $flags, true);
$si       = in_array('yes', $flags, true);
$update   = in_array('update', $flags, true);
$conInicio = in_array('inicio', $flags, true);

// ── Contenido ────────────────────────────────────────────────────────
// Cada afirmación corresponde a algo que la plataforma hace. Los límites
// conocidos se dicen: un sitio que exagera se detecta en la primera revisión.

$paginas = [
    'funciones' => [
        'title'       => 'Funciones de la plataforma',
        'description' => 'Qué hace PePiuoX Editor, módulo a módulo: contenido, navegación, identidad visual, seguridad y operación.',
        'h1'          => 'Todo lo que puedes hacer con la plataforma',
        'html'        => <<<'HTML'
<section class="py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <h1 class="h2 mb-3">Todo lo que puedes hacer con la plataforma</h1>
      <p class="lead">PePiuoX Editor es un editor de contenidos y gestor de sitio web que se instala en tu propio servidor. Reúne en un solo panel la edición del contenido, la identidad visual del sitio, la gestión de personas y las copias de seguridad.</p>
      <p>Estas son las funciones que existen hoy, no una hoja de ruta.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Contenido</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Constructor visual de bloques</h3>
      <p>Se monta cada página arrastrando bloques ya diseñados sobre Bootstrap 5, sin escribir HTML. La biblioteca de bloques es propia y coherente con el resto del panel.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Versionado con restauración</h3>
      <p>Cada guardado deja una versión recuperable. Se pueden previsualizar, restaurar o descartar sin perder el trabajo posterior.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Plantillas reutilizables</h3>
      <p>Una estructura definida una vez sirve para tantas páginas como se necesiten, con versiones propias por plantilla.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Borradores y publicación</h3>
      <p>Una página puede quedar en borrador, archivarse o publicarse, con fecha de publicación programada.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Navegación</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Menús jerárquicos</h3>
      <p>Menús multinivel con orden arrastrable, asignación por página y caché que se regenera sola al cambiar la estructura.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Portada y sitemap</h3>
      <p>La portada comercial y el <code>sitemap.xml</code> se generan desde el panel. El sitemap sigue los menús que el sitio pone en uso.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Identidad visual</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Sin tocar código</h3>
      <p>Cinco temas, dieciséis colores, diez huecos de logotipo según el contexto (escritorio, tableta, móvil, pie), favicon y CSS propio.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Legibilidad calculada</h3>
      <p>El color del texto sobre cada superficie se calcula para cumplir el contraste mínimo AA. Si se guarda un color a mano, ese manda.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Personas y seguridad</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Usuarios, roles y permisos</h3>
      <p>Cinco roles y un catálogo de permisos por módulo. Los baneos tienen caducidad y quedan registrados.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Verificación en dos pasos</h3>
      <p>Segundo factor por aplicación de códigos temporales, con códigos de recuperación de un solo uso.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Registro de accesos</h3>
      <p>Intentos fallidos, dispositivos de confianza y bloqueo de direcciones sospechosas.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Auditoría y actividad</h3>
      <p>Quién hizo qué, cuándo y desde dónde, consultable y exportable.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Operación e integración</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Copias de seguridad</h3>
      <p>Copias completas programables, con restauración desde el panel y descarga directa.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">API versionada</h3>
      <p>Endpoints en <code>/api/v1</code> con aplicaciones, claves y tokens, para que otros sistemas lean el contenido sin entrar al panel.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Internacionalización</h3>
      <p>Traducciones por idioma y grupo, editables desde el panel.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Analítica propia</h3>
      <p>Visitas y estadísticas diarias calculadas por la propia base de datos, sin servicios externos.</p>
    </div>
  </div>
</section>

<section class="py-4">
  <div class="border rounded p-4">
    <h2 class="h5 mb-2">Límites conocidos</h2>
    <p class="mb-0">La plataforma se distribuye sin licencia declarada y su autorización por permiso está en revisión: mientras tanto, conviene no dar acceso al panel a personas de poca confianza. Los detalles están en la documentación técnica.</p>
  </div>
</section>
HTML,
    ],

    'seguridad' => [
        'title'       => 'Seguridad de la plataforma',
        'description' => 'Cómo protege PePiuoX Editor las cuentas, los datos y el contenido: contraseñas, sesiones, formularios, límites de acceso y auditoría.',
        'h1'          => 'Cómo se protege el sistema',
        'html'        => <<<'HTML'
<section class="py-4">
  <div class="row">
    <div class="col-lg-9">
      <h1 class="h2 mb-3">Cómo se protege el sistema</h1>
      <p class="lead">La seguridad de esta plataforma no es una lista de intenciones: cada control descrito aquí está implementado en el código y se puede comprobar.</p>
      <p>Y lo que falta también se dice.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Cuentas y contraseñas</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Almacenamiento de contraseñas</h3>
      <p>Se guardan con <code>password_hash</code> del propio PHP, algoritmo bcrypt y coste 12. Nunca en texto plano, nunca con MD5 ni SHA1.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Verificación en dos pasos</h3>
      <p>Segundo factor por aplicación de códigos temporales, con códigos de recuperación que se consumen al usarse.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Dispositivos de confianza</h3>
      <p>Se puede marcar un equipo para no repetir el segundo factor, y revocarlo después uno a uno o todos a la vez.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Baneos con caducidad</h3>
      <p>Un baneo puede ser temporal o indefinido, queda registrado con su motivo y su autor, y caduca solo.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Sesiones y formularios</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Cookies de sesión</h3>
      <p>La cookie de sesión se emite con <code>HttpOnly</code> y <code>SameSite</code>, de modo que el JavaScript de la página no puede leerla ni enviarla desde otro sitio.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Protección de formularios</h3>
      <p>Toda operación que cambia datos exige un token CSRF emitido por el servidor. Sin él, la petición se rechaza.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Límite de intentos</h3>
      <p>El acceso, el registro, la recuperación de contraseña y el segundo factor tienen límite de peticiones por ventana de tiempo.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Base de datos</h3>
      <p>Todas las consultas usan sentencias preparadas con parámetros enlazados. La aplicación se conecta con un usuario que solo tiene permiso de lectura y escritura sobre su propia base, sin privilegios de administración.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Trazabilidad</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Auditoría</h3>
      <p>Cada acción relevante queda registrada con su autor, su fecha y su origen, y puede exportarse.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Registro de accesos</h3>
      <p>Los intentos fallidos se contabilizan y se pueden consultar por cuenta y por dirección, con opción de bloquear.</p>
    </div>
  </div>
</section>

<section class="py-4">
  <div class="border rounded p-4">
    <h2 class="h5 mb-2">Lo que todavía falta</h2>
    <p>La plataforma está en desarrollo activo. Antes de abrirla al público conviene saber que:</p>
    <ul class="mb-0">
      <li>No se emiten todavía cabeceras <code>Content-Security-Policy</code> ni <code>Strict-Transport-Security</code>.</li>
      <li>Las dependencias de interfaz se cargan desde un CDN sin atributo de integridad.</li>
      <li>La autorización por permiso se comprueba de forma desigual en algunos módulos, así que no se debe dar acceso al panel a personas de poca confianza solo por asignarles un rol.</li>
    </ul>
  </div>
</section>
HTML,
    ],

    'como-funciona' => [
        'title'       => 'Cómo funciona e instalación',
        'description' => 'Arquitectura, requisitos y puesta en marcha de PePiuoX Editor: PHP 8, MariaDB, servidor propio y nada de dependencias externas.',
        'h1'          => 'Cómo funciona por dentro',
        'html'        => <<<'HTML'
<section class="py-4">
  <div class="row">
    <div class="col-lg-9">
      <h1 class="h2 mb-3">Cómo funciona por dentro</h1>
      <p class="lead">PePiuoX Editor es una aplicación web monolítica escrita en PHP, sin framework y sin gestor de dependencias. Se instala en un servidor propio y guarda todo su contenido en una única base de datos MariaDB.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Qué necesita el servidor</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Requisitos reales</h3>
      <ul class="mb-0">
        <li>PHP 8.3 o superior con las extensiones de base de datos, cadenas multibyte, imágenes y compresión.</li>
        <li>MariaDB 11 o superior. El esquema usa colaciones propias de MariaDB.</li>
        <li>Apache con el módulo de reescritura, o nginx con la configuración equivalente.</li>
        <li>Carpetas escribibles para caché, registros, subidas y copias.</li>
      </ul>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Lo que no hace falta</h3>
      <ul class="mb-0">
        <li>No hace falta Composer: el proyecto se autocarga con su propio cargador PSR-4.</li>
        <li>No hace falta un intérprete de plantillas ni un marco de trabajo.</li>
        <li>No hace falta ningún servicio externo: la analítica y las copias se calculan en el propio servidor.</li>
      </ul>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Cómo está organizado</h2>
  <div class="row g-4">
    <div class="col-md-6">
      <h3 class="h6">Capas</h3>
      <p>Cada petición pasa por un único punto de entrada que carga la configuración, arranca la sesión, resuelve la ruta y delega en un controlador. El controlador no habla con la base de datos: lo hace a través de un servicio, y el servicio a través de un modelo.</p>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Datos</h3>
      <p>El esquema se compone de tablas de contenido, de personas y permisos, de operación y de configuración. Las vistas y los disparadores de la base de datos mantienen parte de las estadísticas al día.</p>
    </div>
  </div>
</section>

<section class="py-3">
  <h2 class="h4 mb-4">Puesta en marcha, en corto</h2>
  <div class="row">
    <div class="col-lg-9">
      <ol class="mb-0">
        <li>Crear la base de datos y un usuario con permiso de lectura y escritura solo sobre ella.</li>
        <li>Importar el archivo de instalación limpia, que crea la estructura y los catálogos sin datos de nadie.</li>
        <li>Rellenar el archivo de credenciales fuera del repositorio.</li>
        <li>Crear la cuenta administradora desde la terminal, con la contraseña escrita por quien instala.</li>
        <li>Entrar, configurar el nombre del sitio, la identidad visual y los menús.</li>
      </ol>
    </div>
  </div>
</section>

<section class="py-4">
  <div class="border rounded p-4">
    <h2 class="h5 mb-2">Documentación</h2>
    <p class="mb-0">La guía de instalación completa, el manual de uso y las especificaciones técnicas acompañan al proyecto y describen exactamente lo que hay, incluidas sus limitaciones.</p>
  </div>
</section>
HTML,
    ],
];

$inicioHtml = <<<'HTML'
<section class="py-5">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <p class="text-uppercase small mb-2">Editor y gestor de contenidos</p>
      <h1 class="display-6 mb-3">Publica, versiona y controla tu web desde un solo panel</h1>
      <p class="lead mb-4">Contenido, diseño y seguridad en la misma herramienta, instalada en tu propio servidor y sin dependencias de terceros.</p>
      <p class="mb-0">Esta plataforma reúne constructor visual, control de versiones, plantillas, menús y personalización visual completa. Todo se guarda en una única base de datos y cada cambio deja rastro.</p>
    </div>
  </div>
</section>

<section class="py-4">
  <div class="row g-4">
    <div class="col-md-4">
      <h3 class="h6">Contenido con historial</h3>
      <p>Cada guardado deja una versión recuperable. Editar no es arriesgar: se puede volver atrás.</p>
    </div>
    <div class="col-md-4">
      <h3 class="h6">Identidad sin código</h3>
      <p>Temas, colores, logotipos y tipografía se cambian desde el panel, con el contraste calculado.</p>
    </div>
    <div class="col-md-4">
      <h3 class="h6">Operación en tus manos</h3>
      <p>Copias de seguridad, auditoría y estadísticas sin depender de servicios externos.</p>
    </div>
  </div>
</section>

<section class="py-4">
  <div class="row g-3">
    <div class="col-md-auto"><a class="btn btn-primary" href="/funciones">Ver todas las funciones</a></div>
    <div class="col-md-auto"><a class="btn btn-outline-secondary" href="/seguridad">Cómo se protege</a></div>
    <div class="col-md-auto"><a class="btn btn-outline-secondary" href="/como-funciona">Cómo funciona</a></div>
  </div>
</section>
HTML;

// ── Conexión ─────────────────────────────────────────────────────────
try {
    $pdo = App\Core\Database::connection();
} catch (Throwable $e) {
    oops('No se pudo conectar: ' . $e->getMessage());
    exit(1);
}

$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
say('Base de datos destino: «' . $dbName . '» en ' . (getenv('DB_HOST') ?: '127.0.0.1'));

if (!$dryRun && !$si) {
    if (!stream_isatty(STDIN)) {
        oops('Sin terminal interactiva no se puede confirmar el destino. Añade --yes o usa --dry-run.');
        exit(2);
    }
    fwrite(STDOUT, 'Para continuar, escribe el nombre exacto de la base («' . $dbName . '»): ');
    if (trim((string) fgets(STDIN)) !== $dbName) {
        oops('El nombre no coincide. No se escribió nada.');
        exit(1);
    }
}

// ── Autor y menú ─────────────────────────────────────────────────────
$autorId = (int) $pdo->query("SELECT id FROM users WHERE role = 'Super admin' AND is_active = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($autorId === 0) {
    oops('No hay ningún Super admin. Crea la cuenta primero: 01-TOOLS/bootstrap/create-admin.php');
    exit(1);
}

$menuId = (int) $pdo->query("SELECT id FROM menus WHERE is_default = 1 AND location = 'header' AND is_active = 1 ORDER BY id ASC LIMIT 1")->fetchColumn();
$menuNombre = '';
if ($menuId > 0) {
    $menuNombre = (string) $pdo->query("SELECT name FROM menus WHERE id = " . $menuId)->fetchColumn();
}

say('Autor de las páginas: usuario #' . $autorId);
say($menuId > 0
    ? 'Menú donde se enlazan: «' . $menuNombre . '» (#' . $menuId . ')'
    : 'No hay menú predeterminado en header: las páginas se crean sin enlazar.');
say($dryRun ? "MODO SIMULACIÓN: no se escribe nada.\n" : "\n");

// ── Páginas ──────────────────────────────────────────────────────────
$creadas = 0;
$actualizadas = 0;
$saltadas = 0;

foreach ($paginas as $slug => $pagina) {
    $existe = $pdo->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');
    $existe->execute([$slug]);
    $id = (int) ($existe->fetchColumn() ?: 0);

    if ($id > 0 && !$update) {
        say('  = ' . $slug . ' ya existe (#' . $id . '): se deja como está. Usa --update para reescribir su contenido.');
        $saltadas++;
        continue;
    }

    if ($id > 0) {
        if (!$dryRun) {
            $upd = $pdo->prepare(
                'UPDATE pages SET title = ?, description = ?, h1 = ?, content_html = ?, updated_at = NOW() WHERE id = ?'
            );
            $upd->execute([$pagina['title'], $pagina['description'], $pagina['h1'], $pagina['html'], $id]);
        }
        say('  ~ ' . $slug . ' actualizada (#' . $id . ')');
        $actualizadas++;
    } else {
        if (!$dryRun) {
            $ins = $pdo->prepare(
                'INSERT INTO pages (user_id, template_id, menu_id, parent_id, title, slug, description, h1,
                                    content_html, startpage, is_published, status)
                 VALUES (?, NULL, NULL, NULL, ?, ?, ?, ?, ?, 0, 1, ?)'
            );
            $ins->execute([
                $autorId, $pagina['title'], $slug, $pagina['description'],
                $pagina['h1'], $pagina['html'], 'published',
            ]);
            $id = (int) $pdo->lastInsertId();
        }
        say('  + ' . $slug . ' creada' . ($id > 0 ? ' (#' . $id . ')' : ''));
        $creadas++;
    }

    // Enlace en el menú, si no está ya.
    if ($menuId > 0 && $id > 0) {
        $yaEsta = $pdo->prepare('SELECT COUNT(*) FROM page_menu WHERE menu_id = ? AND page_id = ?');
        $yaEsta->execute([$menuId, $id]);

        if ((int) $yaEsta->fetchColumn() === 0) {
            if (!$dryRun) {
                $orden = (int) $pdo->query(
                    'SELECT COALESCE(MAX(`order`), 0) + 1 FROM page_menu WHERE menu_id = ' . $menuId
                )->fetchColumn();

                $item = $pdo->prepare(
                    'INSERT INTO page_menu (menu_id, page_id, title, slug, url, icon, `order`, `sort`, parent_id, level,
                                            is_published, status, target)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 1, 1, ?, ?)'
                );
                $item->execute([
                    $menuId, $id, $pagina['title'], $slug, '/' . $slug, 'bi-link-45deg',
                    $orden, $orden, 'published', '_self',
                ]);
            }
            say('      enlazada en el menú');
        }
    }
}

// ── Página de inicio ─────────────────────────────────────────────────
//
// La portada del sitio es una PÁGINA MÁS: se crea aquí y luego se edita con el
// constructor visual, igual que cualquier otra. No hay un editor aparte para
// ella. `pages.user_id` es obligatorio, así que no puede sembrarse en
// `initial_database.sql` (allí todavía no existe ningún usuario): este es el
// primer momento en que hay un autor al que atribuirla.
$inicio = $pdo->query('SELECT id, slug FROM pages WHERE startpage = 1 ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);

if (!$inicio) {
    if ($autorId <= 0) {
        oops('  ! No hay ningún usuario al que atribuir la página de inicio.');
        oops('    Crea el primer administrador y repite: php 01-TOOLS/bootstrap/create-admin.php --generate');
    } elseif ($dryRun) {
        say('  + página de inicio «inicio» se crearía y quedaría marcada como portada');
        $creadas++;
    } else {
        $insInicio = $pdo->prepare(
            'INSERT INTO pages (user_id, template_id, menu_id, parent_id, title, slug, description, h1,
                                content_html, startpage, is_published, status)
             VALUES (?, NULL, NULL, NULL, ?, ?, ?, NULL, ?, 1, 1, ?)'
        );
        $insInicio->execute([
            $autorId,
            'Inicio',
            'inicio',
            'Página de bienvenida de la instalación.',
            $inicioHtml,
            'published',
        ]);
        $idInicio = (int) $pdo->lastInsertId();
        say('  + página de inicio «inicio» creada (#' . $idInicio . '), marcada como portada');
        say('      edítala en Páginas → su título → Constructor, como cualquier otra');
        $creadas++;
        $inicio = ['id' => $idInicio, 'slug' => 'inicio'];
    }
} elseif ($conInicio && !$dryRun) {
    $upd = $pdo->prepare('UPDATE pages SET content_html = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute([$inicioHtml, (int) $inicio['id']]);
    say('  ~ contenido de la página de inicio («' . $inicio['slug'] . '», #' . $inicio['id'] . ') reescrito');
} elseif ($conInicio && $dryRun) {
    say('  ~ se reescribiría el contenido de la página de inicio («' . $inicio['slug'] . '», #' . $inicio['id'] . ')');
}

say('');
say($dryRun
    ? 'Simulación terminada: ' . $creadas . ' por crear, ' . $actualizadas . ' por actualizar, ' . $saltadas . ' ya existentes.'
    : 'Hecho: ' . $creadas . ' creada(s), ' . $actualizadas . ' actualizada(s), ' . $saltadas . ' ya existente(s).');

if (!$dryRun && ($creadas > 0 || $actualizadas > 0)) {
    // El sitemap depende del menú: se regenera para que las páginas nuevas entren.
    try {
        $informe = App\Services\SitemapService::generate();
        say('Sitemap regenerado: ' . $informe['mensaje']);
    } catch (Throwable $e) {
        oops('No se pudo regenerar el sitemap: ' . $e->getMessage());
    }
}

exit(0);
