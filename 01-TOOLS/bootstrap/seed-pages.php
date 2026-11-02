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

// ── Contenido de la portada (página de inicio) ───────────────────────
//
// Se siembra como HTML + CSS de una página normal: el constructor lo carga
// igual que cualquier otro contenido y se puede rehacer con bloques.
//
// Reglas que cumple:
//   - Bootstrap 5.3 para la maqueta (container/row/col/cards/accordion).
//   - Los colores NO van en atributos style ni en un <style> dentro del HTML:
//     el saneador elimina el <style> al publicar. Van en `content_css`, con
//     variables CSS dentro de `.pi-home`, para que no se escapen al resto del sitio.
//   - Sin <script>: el layout público ya carga el bundle de Bootstrap, así que el
//     acordeón funciona sin añadir nada.
$inicioHtml = <<<'HTML'
<div class="pi-home">

  <!-- ═══ Portada ═══ -->
  <section class="pi-hero py-5">
    <div class="container py-lg-4">
      <div class="row align-items-center g-5">
        <div class="col-lg-7">
          <span class="pi-badge mb-3"><i class="bi bi-stars me-1"></i>Sitio en marcha</span>
          <h1 class="display-4 fw-bold lh-sm mb-3">Tu contenido, tu diseño, tus reglas</h1>
          <p class="lead mb-4">
            Publica sin depender de nadie. Constructor visual, plantillas, menús, control de versiones y
            copias de seguridad en la misma herramienta, instalada en tu propio servidor.
          </p>
          <div class="d-flex flex-wrap gap-3 mb-4">
            <a class="btn btn-lg pi-btn-primary" href="/funciones">
              <i class="bi bi-grid-1x2 me-2"></i>Ver funciones
            </a>
            <a class="btn btn-lg pi-btn-ghost" href="/como-funciona">
              Cómo funciona<i class="bi bi-arrow-right ms-2"></i>
            </a>
          </div>
          <div class="d-flex flex-wrap gap-4 pi-hero-facts">
            <div><strong>100%</strong><span>en tu servidor</span></div>
            <div><strong>0</strong><span>dependencias externas</span></div>
            <div><strong>∞</strong><span>páginas y versiones</span></div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="pi-hero-panel">
            <div class="pi-hero-panel__head">
              <span class="pi-dot"></span><span class="pi-dot"></span><span class="pi-dot"></span>
              <em>panel de administración</em>
            </div>
            <ul class="pi-hero-panel__list">
              <li><i class="bi bi-check2-circle"></i>Constructor con bloques listos</li>
              <li><i class="bi bi-check2-circle"></i>Portada y pie editables</li>
              <li><i class="bi bi-check2-circle"></i>Versiones por página</li>
              <li><i class="bi bi-check2-circle"></i>Copias de seguridad programadas</li>
              <li><i class="bi bi-check2-circle"></i>Tareas automáticas con token</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Características ═══ -->
  <section class="py-5">
    <div class="container py-lg-3">
      <div class="text-center mb-5">
        <span class="pi-eyebrow">Lo esencial, resuelto</span>
        <h2 class="display-6 fw-bold mb-2">Todo lo que hace falta para publicar</h2>
        <p class="pi-muted mb-0">Sin plugins de terceros ni servicios que puedan cambiar de condiciones.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <article class="pi-card h-100">
            <span class="pi-icon"><i class="bi bi-magic"></i></span>
            <h3 class="h5 fw-bold mt-3 mb-2">Constructor visual</h3>
            <p class="pi-muted mb-0">
              Compón arrastrando bloques de Bootstrap 5, ajusta estilos y revisa el resultado en móvil,
              tableta y escritorio.
            </p>
          </article>
        </div>
        <div class="col-md-4">
          <article class="pi-card h-100">
            <span class="pi-icon"><i class="bi bi-shield-check"></i></span>
            <h3 class="h5 fw-bold mt-3 mb-2">Seguridad de serie</h3>
            <p class="pi-muted mb-0">
              Consultas preparadas, permisos por acción, protección CSRF, límites de peticiones y sesiones
              con cookie segura.
            </p>
          </article>
        </div>
        <div class="col-md-4">
          <article class="pi-card h-100">
            <span class="pi-icon"><i class="bi bi-clock-history"></i></span>
            <h3 class="h5 fw-bold mt-3 mb-2">Historial y copias</h3>
            <p class="pi-muted mb-0">
              Cada cambio queda registrado y cada página guarda versiones. Las copias se programan por cron.
            </p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Cómo funciona ═══ -->
  <section class="pi-band py-5">
    <div class="container py-lg-3">
      <div class="row g-4 align-items-center">
        <div class="col-lg-4">
          <span class="pi-eyebrow">En tres pasos</span>
          <h2 class="display-6 fw-bold mb-3">De la idea a la web publicada</h2>
          <p class="pi-muted mb-4">
            No hay que preparar entornos ni aprender un lenguaje de plantillas: se elige, se escribe y se publica.
          </p>
          <a class="btn pi-btn-primary" href="/como-funciona">Ver el detalle</a>
        </div>
        <div class="col-lg-8">
          <ol class="pi-steps list-unstyled mb-0">
            <li>
              <span class="pi-step-num">1</span>
              <div>
                <h3 class="h6 fw-bold mb-1">Elige una plantilla</h3>
                <p class="pi-muted mb-0">Cada página empieza con una estructura lista para editar.</p>
              </div>
            </li>
            <li>
              <span class="pi-step-num">2</span>
              <div>
                <h3 class="h6 fw-bold mb-1">Compón con bloques</h3>
                <p class="pi-muted mb-0">Arrastra secciones, cambia textos, imágenes y colores.</p>
              </div>
            </li>
            <li>
              <span class="pi-step-num">3</span>
              <div>
                <h3 class="h6 fw-bold mb-1">Publica</h3>
                <p class="pi-muted mb-0">Al guardar, el sitio queda actualizado y el sitemap se regenera.</p>
              </div>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Bloque destacado ═══ -->
  <section class="py-5">
    <div class="container py-lg-3">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <img class="pi-shot img-fluid rounded-4" alt="Vista del panel de administración"
               src="https://placehold.co/960x640/1B5E20/FEF08A?text=Panel+de+administraci%C3%B3n">
        </div>
        <div class="col-lg-6">
          <span class="pi-eyebrow">Contenido con criterio</span>
          <h2 class="display-6 fw-bold mb-3">Una portada que no parece una plantilla genérica</h2>
          <p class="pi-muted mb-3">
            Este texto es contenido de ejemplo, no una promesa de marketing. Edítalo o bórralo: la página es
            tuya y se cambia desde el constructor, como cualquier otra.
          </p>
          <ul class="pi-checks list-unstyled mb-4">
            <li><i class="bi bi-check2"></i>Secciones con jerarquía clara y contraste suficiente.</li>
            <li><i class="bi bi-check2"></i>Colores en variables CSS: se cambian en un sitio.</li>
            <li><i class="bi bi-check2"></i>Maqueta con la rejilla de Bootstrap 5.3.</li>
            <li><i class="bi bi-check2"></i>Sin dependencias ni código que se ejecute solo.</li>
          </ul>
          <a class="btn pi-btn-outline" href="/seguridad">Cómo se protege</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Cifras ═══ -->
  <section class="pi-metrics py-5">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-lg-3"><div class="pi-metric"><strong>52</strong><span>tablas con integridad</span></div></div>
        <div class="col-6 col-lg-3"><div class="pi-metric"><strong>294</strong><span>bloques disponibles</span></div></div>
        <div class="col-6 col-lg-3"><div class="pi-metric"><strong>3</strong><span>idiomas de contenido</span></div></div>
        <div class="col-6 col-lg-3"><div class="pi-metric"><strong>24/7</strong><span>tareas programadas</span></div></div>
      </div>
    </div>
  </section>

  <!-- ═══ Cita ═══ -->
  <section class="py-5">
    <div class="container py-lg-3">
      <figure class="pi-quote mx-auto text-center mb-0">
        <i class="bi bi-quote pi-quote__mark"></i>
        <blockquote class="mb-3">
          Una plataforma que se puede leer, auditar y mover de servidor sin pedir permiso a nadie.
        </blockquote>
        <figcaption class="pi-muted">Texto de ejemplo — sustitúyelo por una opinión real cuando la tengas</figcaption>
      </figure>
    </div>
  </section>

  <!-- ═══ Preguntas frecuentes ═══ -->
  <section class="pi-band py-5">
    <div class="container py-lg-3">
      <div class="row g-5">
        <div class="col-lg-4">
          <span class="pi-eyebrow">Dudas frecuentes</span>
          <h2 class="display-6 fw-bold mb-2">Antes de empezar</h2>
          <p class="pi-muted mb-0">Cuatro respuestas cortas. El resto, en el manual de uso.</p>
        </div>
        <div class="col-lg-8">
          <div class="accordion pi-accordion" id="piFaq">
            <div class="accordion-item">
              <h3 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#piFaq1" aria-expanded="true" aria-controls="piFaq1">
                  ¿Necesito saber programar?
                </button>
              </h3>
              <div id="piFaq1" class="accordion-collapse collapse show" data-bs-parent="#piFaq">
                <div class="accordion-body">
                  Para el día a día, no. El constructor cubre textos, imágenes, columnas y estilos. El editor de
                  código está ahí por si hace falta algo puntual.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h3 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#piFaq2" aria-expanded="false" aria-controls="piFaq2">
                  ¿Dónde se guarda el contenido?
                </button>
              </h3>
              <div id="piFaq2" class="accordion-collapse collapse" data-bs-parent="#piFaq">
                <div class="accordion-body">
                  En tu base de datos, en tu servidor. Las imágenes van a <code>uploads/</code> y las copias a
                  <code>storage/backups</code>.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h3 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#piFaq3" aria-expanded="false" aria-controls="piFaq3">
                  ¿Puedo deshacer un cambio?
                </button>
              </h3>
              <div id="piFaq3" class="accordion-collapse collapse" data-bs-parent="#piFaq">
                <div class="accordion-body">
                  Sí. Cada página conserva sus versiones anteriores y puedes volver a una de ellas desde el panel.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h3 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#piFaq4" aria-expanded="false" aria-controls="piFaq4">
                  ¿Y si quiero cambiar esta portada?
                </button>
              </h3>
              <div id="piFaq4" class="accordion-collapse collapse" data-bs-parent="#piFaq">
                <div class="accordion-body">
                  Esta página es una más: <em>Páginas → Inicio → Constructor</em>. Cambia, borra o reordena lo
                  que quieras.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Llamada final ═══ -->
  <section class="pi-cta py-5">
    <div class="container py-lg-3 text-center">
      <h2 class="display-6 fw-bold mb-3">Ya está instalado. Ahora hazlo tuyo.</h2>
      <p class="lead pi-muted mb-4 mx-auto pi-cta__lead">
        Cambia este texto, sube tu logotipo y publica la primera página real.
      </p>
      <div class="d-flex flex-wrap gap-3 justify-content-center">
        <a class="btn btn-lg pi-btn-primary" href="/admin">Entrar al panel</a>
        <a class="btn btn-lg pi-btn-ghost" href="/funciones">Explorar funciones</a>
      </div>
    </div>
  </section>

</div>
HTML;

// ── CSS de la portada, con la paleta de la marca ─────────────────────
//
// Va en `content_css` y no dentro del HTML: al publicar, el saneador elimina los
// <style> que vengan en el contenido. Todo va dentro de `.pi-home` para que estas
// variables y estos estilos no afecten al resto del sitio.
$inicioCss = <<<'CSS'
.pi-home {
  --pi-orange: #C46A00;
  --pi-amber: #F0B429;
  --pi-yellow: #FDE047;
  --pi-yellow-soft: #FEF08A;
  --pi-cream: #FFFFF0;
  --pi-green: #4B8B5A;
  --pi-green-dark: #1B5E20;
  --pi-green-deep: #0D3B0D;
  --pi-green-ink: #041A04;
  --pi-radius: 18px;
  background: var(--pi-cream);
  color: var(--pi-green-ink);
}

.pi-home h1, .pi-home h2, .pi-home h3 { color: var(--pi-green-deep); letter-spacing: -.015em; }
.pi-home .pi-muted { color: #2c3d2e; }
.pi-home code { color: var(--pi-orange); background: rgba(196,106,0,.08); padding: .1em .35em; border-radius: 4px; }

/* Portada */
.pi-hero {
  background:
    radial-gradient(1100px 520px at 15% -10%, rgba(75,139,90,.55), transparent 60%),
    linear-gradient(155deg, var(--pi-green-deep) 0%, var(--pi-green-dark) 55%, var(--pi-green) 100%);
  color: var(--pi-cream);
}
.pi-hero h1 { color: #fff; }
.pi-hero .lead { color: var(--pi-yellow-soft); }
.pi-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  background: rgba(253,224,71,.15);
  color: var(--pi-yellow);
  border: 1px solid rgba(253,224,71,.45);
  border-radius: 999px;
  padding: .35rem .8rem;
  font-size: .8rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase;
}
.pi-hero-facts { color: var(--pi-cream); }
.pi-hero-facts > div { display: flex; flex-direction: column; }
.pi-hero-facts strong { color: var(--pi-yellow); font-size: 1.5rem; line-height: 1.1; }
.pi-hero-facts span { font-size: .82rem; opacity: .85; }

.pi-hero-panel {
  background: rgba(4,26,4,.62);
  border: 1px solid rgba(254,240,138,.25);
  border-radius: var(--pi-radius);
  padding: 1rem 1.15rem 1.25rem;
  box-shadow: 0 24px 60px rgba(0,0,0,.35);
}
.pi-hero-panel__head {
  display: flex; align-items: center; gap: .4rem;
  padding-bottom: .7rem; margin-bottom: .8rem;
  border-bottom: 1px solid rgba(254,240,138,.18);
  color: var(--pi-yellow-soft); font-size: .78rem;
}
.pi-hero-panel__head em { margin-left: .4rem; font-style: normal; opacity: .8; }
.pi-dot { width: .55rem; height: .55rem; border-radius: 50%; background: var(--pi-amber); display: inline-block; }
.pi-dot:nth-child(2) { background: var(--pi-yellow); }
.pi-dot:nth-child(3) { background: var(--pi-green); }
.pi-hero-panel__list { list-style: none; margin: 0; padding: 0; }
.pi-hero-panel__list li {
  display: flex; align-items: center; gap: .55rem;
  color: var(--pi-cream); font-size: .92rem;
  padding: .38rem 0;
}
.pi-hero-panel__list i { color: var(--pi-yellow); }

/* Botones */
.pi-btn-primary {
  background: var(--pi-amber);
  border: 1px solid var(--pi-amber);
  color: var(--pi-green-ink);
  font-weight: 600;
  border-radius: 999px;
  padding: .7rem 1.5rem;
}
.pi-btn-primary:hover, .pi-btn-primary:focus {
  background: var(--pi-yellow);
  border-color: var(--pi-yellow);
  color: var(--pi-green-ink);
}
.pi-btn-ghost {
  background: transparent;
  border: 1px solid rgba(255,255,255,.45);
  color: var(--pi-cream);
  border-radius: 999px;
  padding: .7rem 1.5rem;
}
.pi-btn-ghost:hover, .pi-btn-ghost:focus { background: rgba(255,255,255,.12); color: #fff; border-color: #fff; }
.pi-btn-outline {
  border: 1px solid var(--pi-green-dark);
  color: var(--pi-green-deep);
  border-radius: 999px;
  padding: .6rem 1.35rem;
  font-weight: 600;
}
.pi-btn-outline:hover { background: var(--pi-green-dark); color: var(--pi-cream); }

/* Cabeceras de sección */
.pi-eyebrow {
  display: inline-block;
  color: var(--pi-orange);
  font-size: .78rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
  margin-bottom: .5rem;
}

/* Tarjetas */
.pi-card {
  background: #fff;
  border: 1px solid rgba(27,94,32,.12);
  border-radius: var(--pi-radius);
  padding: 1.6rem 1.4rem;
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.pi-card:hover {
  transform: translateY(-4px);
  border-color: rgba(196,106,0,.45);
  box-shadow: 0 18px 40px rgba(13,59,13,.14);
}
.pi-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 46px; height: 46px;
  border-radius: 13px;
  background: linear-gradient(140deg, var(--pi-yellow), var(--pi-amber));
  color: var(--pi-green-ink);
  font-size: 1.3rem;
}

/* Franja y pasos */
.pi-band { background: linear-gradient(180deg, #F7F7E6 0%, var(--pi-cream) 100%); }
.pi-steps li {
  display: flex; gap: 1rem; align-items: flex-start;
  background: #fff;
  border: 1px solid rgba(27,94,32,.12);
  border-radius: 14px;
  padding: 1rem 1.15rem;
  margin-bottom: .75rem;
}
.pi-steps li:last-child { margin-bottom: 0; }
.pi-step-num {
  flex: 0 0 auto;
  width: 34px; height: 34px;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 50%;
  background: var(--pi-green-dark); color: var(--pi-yellow);
  font-weight: 700;
}

/* Imagen y comprobaciones */
.pi-shot { box-shadow: 0 22px 50px rgba(13,59,13,.2); }
.pi-checks li { display: flex; gap: .55rem; align-items: flex-start; padding: .22rem 0; }
.pi-checks i { color: var(--pi-green); font-weight: 700; }

/* Cifras */
.pi-metrics {
  background: linear-gradient(150deg, var(--pi-green-deep), var(--pi-green-dark) 70%, var(--pi-green));
  color: var(--pi-cream);
}
.pi-metric strong { display: block; font-size: 2.1rem; line-height: 1.05; color: var(--pi-yellow); }
.pi-metric span { font-size: .85rem; opacity: .88; }

/* Cita */
.pi-quote { max-width: 780px; }
.pi-quote__mark { font-size: 2.6rem; color: var(--pi-amber); line-height: 1; }
.pi-quote blockquote {
  font-size: 1.35rem; line-height: 1.45; font-weight: 500;
  color: var(--pi-green-deep);
}
.pi-quote figcaption { font-size: .9rem; }

/* Acordeón */
.pi-accordion .accordion-item {
  border: 1px solid rgba(27,94,32,.14);
  border-radius: 12px !important;
  margin-bottom: .6rem;
  overflow: hidden;
  background: #fff;
}
.pi-accordion .accordion-button {
  font-weight: 600;
  color: var(--pi-green-deep);
  background: #fff;
}
.pi-accordion .accordion-button:not(.collapsed) {
  color: var(--pi-green-deep);
  background: var(--pi-yellow-soft);
  box-shadow: none;
}
.pi-accordion .accordion-button:focus { box-shadow: 0 0 0 .2rem rgba(240,180,41,.35); }
.pi-accordion .accordion-body { color: #2c3d2e; }

/* Llamada final */
.pi-cta { background: linear-gradient(160deg, var(--pi-yellow-soft), var(--pi-cream) 70%); }
.pi-cta__lead { max-width: 620px; }

/* Responsive */
@media (max-width: 991.98px) {
  .pi-hero-facts { gap: 1.25rem !important; }
  .pi-quote blockquote { font-size: 1.15rem; }
}
@media (prefers-reduced-motion: reduce) {
  .pi-card, .pi-card:hover { transition: none; transform: none; }
}
CSS;

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
                                content_html, content_css, startpage, is_published, status)
             VALUES (?, NULL, NULL, NULL, ?, ?, ?, NULL, ?, ?, 1, 1, ?)'
        );
        $insInicio->execute([
            $autorId,
            'Inicio',
            'inicio',
            'Página de bienvenida de la instalación.',
            $inicioHtml,
            $inicioCss,
            'published',
        ]);
        $idInicio = (int) $pdo->lastInsertId();
        say('  + página de inicio «inicio» creada (#' . $idInicio . '), marcada como portada');
        say('      edítala en Páginas → su título → Constructor, como cualquier otra');
        $creadas++;
        $inicio = ['id' => $idInicio, 'slug' => 'inicio'];
    }
} elseif ($conInicio && !$dryRun) {
    $upd = $pdo->prepare('UPDATE pages SET content_html = ?, content_css = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute([$inicioHtml, $inicioCss, (int) $inicio['id']]);
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
