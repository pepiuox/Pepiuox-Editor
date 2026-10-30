<?php
/**
 * Prueba funcional de la personalización visual pública (ejecutable por CLI).
 *
 * Verifica de verdad, contra la base de datos real y el código real, que:
 *  - los valores por defecto llegan desde la migración,
 *  - el CSS contiene las 16 variables y está bajo body.public-site,
 *  - la validación de colores y CSS rechaza lo que debe rechazar,
 *  - el sanitizador de SVG elimina código ejecutable,
 *  - la subida rechaza extensiones fuera de la lista blanca,
 *  - guardar y restablecer persisten en MySQL,
 *  - el aislamiento del panel se cumple (el CSS no tiene selectores sueltos).
 *
 * Se usa a mano: `php tests/public-theme/service-test.php`. No forma parte de la aplicación.
 */

declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST']   = 'localhost';

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

use App\Services\ImageUploadValidator;
use App\Services\PublicThemeService;
use App\Services\SettingService;
use App\Services\SiteSettings;
use App\Services\SvgSanitizer;

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  PASA  {$name}\n";
        return;
    }
    $failed++;
    echo "  FALLA {$name}" . ($detail !== '' ? " → {$detail}" : '') . "\n";
}

echo "== 0. Estado de partida ==\n";
// Esta suite escribe en la tabla `settings`. Para que dos ejecuciones seguidas
// den el mismo resultado y las comprobaciones de valores por defecto tengan
// sentido, se normaliza primero: el tema «oscuro» es la paleta original del
// sitio y sus colores coinciden con los de la migración.
PublicThemeService::resetColors('oscuro');
check('estado de partida normalizado (tema oscuro)', PublicThemeService::get('theme') === 'oscuro', PublicThemeService::get('theme'));

// Línea base de los ajustes que el bloque 12 modifica temporalmente. Se
// comprueba al final que quedaron como estaban: una prueba no debe dejar el
// sitio configurado a su gusto.
$baselineTimezone = SiteSettings::timezone();
$baselineLocale   = (string) SettingService::get('general', 'site_locale', 'es');

echo "\n== 1. Lectura de configuración ==\n";
$all = PublicThemeService::all();
check('41 claves gestionadas', count($all) === 41, 'count=' . count($all));
check('tema por defecto = oscuro', ($all['theme'] ?? '') === 'oscuro', $all['theme'] ?? 'null');
check('color primario por defecto', ($all['color_primary'] ?? '') === '#00d4aa', $all['color_primary'] ?? '');

echo "\n== 2. CSS generado ==\n";
$css = PublicThemeService::css();
$vars = substr_count($css, '--public-');
check('16+ variables --public-*', $vars >= 16, "apariciones={$vars}");
foreach (['--public-primary', '--public-secondary', '--public-accent', '--public-background',
          '--public-text', '--public-header-bg', '--public-footer-bg', '--public-sidebar-bg',
          '--public-menu-bg', '--public-menu-hover', '--public-section-bg', '--public-multinivel-bg',
          '--public-link', '--public-button', '--public-border', '--public-text-muted'] as $var) {
    check("variable {$var}", str_contains($css, $var . ':'));
}
check('ámbito body.public-site', str_contains($css, 'body.public-site {'));
check('puente con --accent del layout', str_contains($css, '--accent: var(--public-accent)'));

echo "\n== 3. Aislamiento: ningún selector suelto ==\n";
$loose = [];
foreach (explode("\n", $css) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '/*') || str_starts_with($line, '--')
        || str_contains($line, ':') && !str_contains($line, '{')) {
        continue;
    }
    if (str_contains($line, '{') && !str_starts_with($line, 'body.public-site')) {
        $loose[] = $line;
    }
}
check('todo el CSS cuelga de body.public-site', $loose === [], implode(' | ', $loose));

echo "\n== 4. Colores ==\n";
check('#ABC → #aabbcc', PublicThemeService::normalizeHex('#ABC') === '#aabbcc', (string) PublicThemeService::normalizeHex('#ABC'));
check('sin almohadilla se acepta', PublicThemeService::normalizeHex('00d4aa') === '#00d4aa');
check('«rojo» se rechaza', PublicThemeService::normalizeHex('rojo') === null);
check('#12345 se rechaza', PublicThemeService::normalizeHex('#12345') === null);
check('shade oscurece', PublicThemeService::shade('#ffffff', -50) === '#808080', PublicThemeService::shade('#ffffff', -50));
check('luminancia blanco = 1', abs(PublicThemeService::luminance('#ffffff') - 1.0) < 0.001);

echo "\n== 5. Validación del CSS personalizado ==\n";
[$clean, $errors] = PublicThemeService::validateCustomCss('.public-site .x { color: red; }');
check('CSS válido se acepta', $errors === [] && str_contains($clean, '.x'));
[, $errors] = PublicThemeService::validateCustomCss('</style><script>alert(1)</script>');
check('rechaza «<»', $errors !== [], 'sin errores');
[, $errors] = PublicThemeService::validateCustomCss('@import url("http://evil.test/x.css");');
check('rechaza @import', $errors !== []);
[, $errors] = PublicThemeService::validateCustomCss('a{background:url(javascript:alert(1))}');
check('rechaza javascript:', $errors !== []);

echo "\n== 5.b. Acotado del CSS personalizado ==\n";
$scoped = PublicThemeService::scopeCss('.hero { color: red; }');
check('selector simple se prefija', str_starts_with($scoped, 'body.public-site .hero'), $scoped);
$scopedMedia = PublicThemeService::scopeCss('@media (max-width: 600px){.hero{color:red}}');
check('desciende en @media', str_contains($scopedMedia, '@media (max-width: 600px)') && str_contains($scopedMedia, 'body.public-site .hero'), $scopedMedia);
$scopedRoot = PublicThemeService::scopeCss(':root{--x:1}');
check(':root se convierte en el ámbito', (bool) preg_match('/body\.public-site\s*\{--x:1\}/', $scopedRoot), $scopedRoot);
$scopedList = PublicThemeService::scopeCss('h1, h2 { margin: 0 }');
check('lista de selectores se prefija entera', substr_count($scopedList, 'body.public-site') === 2, $scopedList);
$scopedKeyframes = PublicThemeService::scopeCss('@keyframes giro{from{opacity:0}to{opacity:1}}');
check('@keyframes no se prefija', str_starts_with($scopedKeyframes, '@keyframes giro'), $scopedKeyframes);
check('CSS ya acotado no se duplica', str_starts_with(PublicThemeService::scopeCss('body.public-site .x{a:1}'), 'body.public-site .x'));

echo "\n== 6. Sanitizador de SVG ==\n";
$malicious = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
    . '<script>alert(1)</script>'
    . '<rect width="10" height="10" fill="#fff" onload="alert(2)"/>'
    . '<foreignObject><div>x</div></foreignObject>'
    . '<a href="javascript:alert(3)"><text>t</text></a>'
    . '</svg>';
$result = (new SvgSanitizer())->sanitize($malicious);
check('SVG válido tras sanear', $result['ok']);
check('sin <script>', !str_contains(strtolower($result['svg']), '<script'));
check('sin onload', !str_contains(strtolower($result['svg']), 'onload'));
check('sin foreignObject', !str_contains(strtolower($result['svg']), 'foreignobject'));
check('sin javascript:', !str_contains(strtolower($result['svg']), 'javascript:'));
check('conserva el <rect> legítimo', str_contains($result['svg'], '<rect'));
check('informa de lo eliminado', $result['removed'] !== []);
$withDoctype = (new SvgSanitizer())->sanitize('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg/>');
check('rechaza DOCTYPE/ENTITY', $withDoctype['ok'] === false);

echo "\n== 7. Validador de subidas ==\n";
$validator = new ImageUploadValidator(['png', 'jpg', 'jpeg', 'svg', 'webp'], 2097152);
$bad = $validator->validate(['name' => 'shell.php', 'tmp_name' => '/tmp/x', 'error' => 0, 'size' => 10]);
check('rechaza .php', $bad['ok'] === false && str_contains((string) $bad['error'], 'Formato no permitido'), (string) $bad['error']);
$big = $validator->validate(['name' => 'a.png', 'tmp_name' => '/tmp/x', 'error' => 0, 'size' => 99999999]);
check('rechaza tamaño excesivo', $big['ok'] === false && str_contains((string) $big['error'], 'supera el máximo'), (string) $big['error']);
$noUpload = $validator->validate(['name' => 'a.png', 'tmp_name' => '/tmp/x', 'error' => 0, 'size' => 10]);
check('exige subida HTTP real', $noUpload['ok'] === false && str_contains((string) $noUpload['error'], 'subida válida'), (string) $noUpload['error']);
$noFile = $validator->validate(null);
check('sin archivo da error claro', $noFile['ok'] === false);

echo "\n== 8. Persistencia y restablecimiento ==\n";
// Punto de partida conocido: si una ejecución anterior se interrumpió a medias,
// el estado podría haberse quedado en «claro» y las comparaciones de abajo
// fallarían sin que nada estuviera roto. Se normaliza antes de medir.
PublicThemeService::resetColors('oscuro');
$versionBefore = PublicThemeService::version();
$rejected = PublicThemeService::save(['theme' => 'claro', 'colors' => ['color_primary' => 'no-es-color']]);
check('guardado inválido no persiste', $rejected['ok'] === false && $rejected['errors'] !== []);
check('el tema no cambió tras el rechazo', PublicThemeService::get('theme') === 'oscuro', PublicThemeService::get('theme'));

$ok = PublicThemeService::save([
    'theme'  => 'claro',
    'colors' => ['color_primary' => '#0D6EFD', 'color_background' => '#FFFFFF'],
    'logo_alt' => 'Logotipo de prueba',
]);
check('guardado válido', $ok['ok'], implode(' ', $ok['errors']));
check('HEX normalizado en BD', PublicThemeService::get('color_primary') === '#0d6efd', PublicThemeService::get('color_primary'));
check('tema persistido', PublicThemeService::get('theme') === 'claro');
check('isDark() refleja el fondo claro', PublicThemeService::isDark() === false);
check('la versión cambia al guardar', PublicThemeService::version() !== $versionBefore);

$reset = PublicThemeService::resetColors('oscuro');
check('restablecer colores', $reset['ok']);
check('vuelve al primario por defecto', PublicThemeService::get('color_primary') === '#00d4aa', PublicThemeService::get('color_primary'));
check('isDark() vuelve a true', PublicThemeService::isDark() === true);

echo "\n== 9. Favicon y manifiesto ==\n";
// Se parte de un estado limpio: si una ejecución anterior (o la prueba E2E)
// dejó imágenes, estas comprobaciones fallarían sin que nada estuviera roto.
$assetService = new \App\Services\SettingsAssetService();
foreach (array_merge(array_keys(PublicThemeService::LOGO_SLOTS), array_keys(PublicThemeService::FAVICON_SLOTS)) as $slot) {
    $assetService->delete($slot);
}
check('sin favicon no hay etiquetas', PublicThemeService::faviconTags() === '', PublicThemeService::faviconTags());
$manifest = PublicThemeService::manifest();
check('manifiesto con nombre', isset($manifest['name']) && $manifest['name'] !== '');
check('manifiesto sin iconos si no hay archivos', $manifest['icons'] === []);
check('assetUrl de un slot sin configurar es null', PublicThemeService::assetUrl('logo_header') === null);
check('slot inexistente es null', PublicThemeService::assetUrl('slot_inventado') === null);

echo "\n== 9.b. Huecos con y sin destino ==\n";
$visibles = PublicThemeService::visibleLogoSlots();
check('se ofrecen 9 de los 10 huecos de logo', count($visibles) === 9, 'count=' . count($visibles));
check('logo_sidebar se oculta (sin componente)', !isset($visibles['logo_sidebar']));
check('logo_menu sí se ofrece (menú móvil)', isset($visibles['logo_menu']));
check('logo_multinivel sí se ofrece (desplegables)', isset($visibles['logo_multinivel']));
check('el catálogo completo sigue teniendo 10', count(PublicThemeService::LOGO_SLOTS) === 10);
check('cada hueco declara dónde se pinta', !in_array('', array_column(PublicThemeService::LOGO_SLOTS, 'where'), true));

echo "\n== 10. Corrección del defecto preexistente ==\n";
check('SettingService::clearCache() existe', method_exists(SettingService::class, 'clearCache'));

echo "\n== 11. Helpers ==\n";
check('getPublicSettings() disponible', function_exists('getPublicSettings'));
check('getPublicSettings() devuelve 41 claves', count(getPublicSettings()) === 41);
check('public_theme_css_url() incluye versión', str_contains(public_theme_css_url(), 'public-theme.css?v='));
check('public_logo() devuelve null sin configuración', public_logo('logo_header') === null);

echo "\n== 12. Identidad del sitio: manda Configuración, no el archivo ==\n";
check('name() devuelve el nombre guardado', SiteSettings::name() === trim((string) SettingService::get('general', 'site_name', '')), SiteSettings::name());
check('language() normaliza es_ES a es', (static function (): bool {
    $original = (string) SettingService::get('general', 'site_locale', 'es');
    SettingService::set('general', 'site_locale', 'es_ES');
    SiteSettings::flush();
    $result = SiteSettings::language() === 'es';
    SettingService::set('general', 'site_locale', $original !== '' ? $original : 'es');
    SiteSettings::flush();
    return $result;
})(), SiteSettings::language());
check('titleFor compone «página · sitio»', SiteSettings::titleFor('Inicio') === 'Inicio · ' . SiteSettings::name(), SiteSettings::titleFor('Inicio'));
check('titleFor no duplica el nombre', SiteSettings::titleFor(SiteSettings::name()) === SiteSettings::name(), SiteSettings::titleFor(SiteSettings::name()));
check('titleFor sin título cae al por defecto', SiteSettings::titleFor('') === SiteSettings::defaultTitle(), SiteSettings::titleFor(''));
check('descriptionFor respeta la de la página', SiteSettings::descriptionFor('Propia') === 'Propia');
check('descriptionFor cae al defecto', SiteSettings::descriptionFor('') === SiteSettings::defaultDescription(), SiteSettings::descriptionFor(''));

$originalTz = (string) SettingService::get('general', 'site_timezone', 'Europe/Madrid');
SettingService::set('general', 'site_timezone', 'America/Lima');
SiteSettings::flush();
check('timezone() lee la guardada', SiteSettings::timezone() === 'America/Lima', SiteSettings::timezone());
\App\Core\App::boot();
check('App::boot aplica la zona horaria guardada', date_default_timezone_get() === 'America/Lima', date_default_timezone_get());
SettingService::set('general', 'site_timezone', $originalTz !== '' ? $originalTz : 'Europe/Madrid');
SiteSettings::flush();
date_default_timezone_set(SiteSettings::timezone());

echo "\n== 13. Request::uri() no recorta prefijos de texto ==\n";
// Regresión: con la app servida en /public/index.php, `str_starts_with($uri,
// '/public')` convertía «/public-theme.css» en «/-theme.css» y la hoja dinámica
// acababa en el 404 del catch-all. En Apache pasaba siempre; en el servidor
// embebido no, porque su SCRIPT_NAME es distinto.
$originalServer = $_SERVER;
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$request = new \App\Core\Request();

$_SERVER['REQUEST_URI'] = '/public-theme.css';
check('/public-theme.css no se recorta', $request->uri() === '/public-theme.css', $request->uri());

$_SERVER['REQUEST_URI'] = '/public-theme.css?v=abc';
check('la query no afecta', $request->uri() === '/public-theme.css', $request->uri());

$_SERVER['REQUEST_URI'] = '/site.webmanifest';
check('/site.webmanifest intacta', $request->uri() === '/site.webmanifest', $request->uri());

$_SERVER['SCRIPT_NAME'] = '/app/index.php';
$_SERVER['REQUEST_URI'] = '/app/pagina';
check('un subdirectorio real sí se recorta', $request->uri() === '/pagina', $request->uri());

$_SERVER['REQUEST_URI'] = '/app/page/apple';
check('sólo se recorta el primer segmento', $request->uri() === '/page/apple', $request->uri());

echo "\n== 14. Legibilidad: el tema nunca deja texto invisible ==\n";
check('contraste blanco/negro = 21:1', abs(PublicThemeService::contrastRatio('#ffffff', '#000000') - 21.0) < 0.01, (string) PublicThemeService::contrastRatio('#ffffff', '#000000'));
check('texto sobre fondo claro es oscuro', PublicThemeService::contrastColor('#ffffff') === '#06110e', PublicThemeService::contrastColor('#ffffff'));
check('texto sobre fondo oscuro es claro', PublicThemeService::contrastColor('#000000') === '#ffffff', PublicThemeService::contrastColor('#000000'));
check('un color que ya cumple no se toca', PublicThemeService::ensureContrast('#e6edf3', '#0d1117') === '#e6edf3', PublicThemeService::ensureContrast('#e6edf3', '#0d1117'));
check('una pareja ilegible se corrige', PublicThemeService::contrastRatio(PublicThemeService::ensureContrast('#777777', '#888888'), '#888888') >= 4.5, PublicThemeService::ensureContrast('#777777', '#888888'));
check('mix a la mitad', PublicThemeService::mix('#000000', '#ffffff', 0.5) === '#808080', PublicThemeService::mix('#000000', '#ffffff', 0.5));

echo "\n== 14.b. Texto sobre cada superficie: automático y editable ==\n";
check('hay 7 superficies configurables', count(PublicThemeService::SURFACE_TEXT_LABELS) === 7, 'count=' . count(PublicThemeService::SURFACE_TEXT_LABELS));
$resueltos = PublicThemeService::resolvedSurfaceTexts();
check('todas las superficies tienen color efectivo', count($resueltos) === 7 && !in_array('', $resueltos, true), implode(',', $resueltos));

// Automático: el texto del menú debe cumplir AA sobre el fondo del menú.
$malos = [];
foreach (PublicThemeService::SURFACE_TEXT_LABELS as $key => $meta) {
    $fondo = (string) PublicThemeService::get($meta['surface']);
    $ratio = PublicThemeService::contrastRatio($resueltos[$key], $fondo);
    if ($ratio < 4.5) {
        $malos[] = $key . '=' . round($ratio, 2);
    }
}
check('el automático cumple AA en las 7 superficies', $malos === [], implode(', ', $malos));

// Editable: un valor explícito manda sobre el cálculo…
$antes = $resueltos['color_menu_text'];
$editado = PublicThemeService::save(['colors' => ['color_menu_text' => '#FF00aa']]);
check('guardar el texto del menú', $editado['ok'], implode(' ', $editado['errors']));
check('el valor elegido manda', PublicThemeService::resolvedSurfaceTexts()['color_menu_text'] === '#ff00aa', PublicThemeService::resolvedSurfaceTexts()['color_menu_text']);
check('el CSS publica ese color', str_contains(PublicThemeService::css(), '--public-menu-text: #ff00aa'), 'no aparece en el CSS');

// …y vaciarlo lo devuelve a automático.
$vaciado = PublicThemeService::save(['colors' => ['color_menu_text' => '']]);
check('vaciar lo devuelve a automático', $vaciado['ok'] && PublicThemeService::resolvedSurfaceTexts()['color_menu_text'] === $antes, PublicThemeService::resolvedSurfaceTexts()['color_menu_text']);

// El fallo reportado fue «el texto del botón de ir al admin no se ve»: el CSS
// publicado debe fijar el color del texto de los botones, no heredarlo.
$css = PublicThemeService::css();
check('el CSS fija el color del texto de .btn-accent', (bool) preg_match('/\.btn-accent[^{]*\{[^}]*color:\s*var\(--public-button-text\)/i', $css), 'sin color en .btn-accent');
check('el CSS fija el color del texto de .btn-primary', (bool) preg_match('/\.btn-primary[^{]*\{[^}]*color:\s*#[0-9a-f]{6}/i', $css), 'sin color en .btn-primary');
check('el CSS pinta el menú con su propio color', str_contains($css, '--public-menu-text'), 'sin variable de menú');
check('el CSS cubre los enlaces del menú', (bool) preg_match('/\.navbar \.nav-link[^{]*\{[^}]*color:\s*var\(--public-menu-text\)/s', $css), 'sin regla para .nav-link');
check('el CSS cubre los desplegables', str_contains($css, 'var(--public-multinivel-text)'));
check('el CSS cubre el pie', str_contains($css, 'var(--public-footer-text)'));
check('el CSS hereda los tokens de Bootstrap', str_contains($css, '--bs-body-color: var(--public-text)'));

$peores = [];
foreach (PublicThemeService::THEME_PRESETS as $id => $preset) {
    $c    = $preset['colors'];
    $fondo = $c['color_background'];
    $pares = [
        'texto'  => PublicThemeService::contrastRatio(PublicThemeService::ensureContrast($c['color_text'], $fondo), $fondo),
        'tenue'  => PublicThemeService::contrastRatio(PublicThemeService::ensureContrast($c['color_text_muted'], $fondo), $fondo),
        'enlace' => PublicThemeService::contrastRatio(PublicThemeService::ensureContrast($c['color_link'], $fondo), $fondo),
        'botón'  => PublicThemeService::contrastRatio(PublicThemeService::contrastColor($c['color_button']), $c['color_button']),
    ];
    foreach ($pares as $que => $ratio) {
        if ($ratio < 4.5) {
            $peores[] = "{$id}/{$que}={$ratio}";
        }
    }
}
check('los 5 temas predefinidos cumplen AA en texto, atenuado, enlace y botón', $peores === [], implode(', ', $peores));

$_SERVER = $originalServer;

echo "\n== 14. Limpieza (la prueba no debe dejar rastro en el sitio) ==\n";
$cleanup = PublicThemeService::save(['logo_alt' => '']);
check('texto alternativo devuelto a vacío', $cleanup['ok'] && PublicThemeService::get('logo_alt') === '', PublicThemeService::get('logo_alt'));
check('tema final = oscuro', PublicThemeService::get('theme') === 'oscuro', PublicThemeService::get('theme'));
check('zona horaria restaurada a la de partida', SiteSettings::timezone() === $baselineTimezone, SiteSettings::timezone() . ' ≠ ' . $baselineTimezone);
check('idioma restaurado al de partida', (string) SettingService::get('general', 'site_locale', 'es') === $baselineLocale, (string) SettingService::get('general', 'site_locale', 'es'));
check('sin CSS personalizado guardado', PublicThemeService::get('custom_css') === '', PublicThemeService::get('custom_css'));

echo "\n─────────────────────────────────────────\n";
printf("Resultado: %d pasan, %d fallan\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);

