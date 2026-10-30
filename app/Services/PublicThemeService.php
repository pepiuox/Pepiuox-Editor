<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * Identidad visual y tema de la **web pública**.
 *
 * Es la única fuente de verdad de la personalización pública: lee el grupo
 * `public` de la tabla `settings` en **una sola consulta** por petición, la
 * cachea en memoria estática y genera con ella el CSS, las etiquetas de
 * favicon y el manifiesto. Ninguna vista pública consulta la base de datos.
 *
 * Aislamiento: todo lo que produce esta clase se inyecta **sólo** desde
 * `views/public/layout.php` y todo su CSS está bajo el ámbito
 * `body.public-site`. El panel administrativo no incluye ni la hoja ni las
 * variables, así que no puede heredarlas (ver `docs/PUBLIC_THEME.md`).
 *
 * Los valores por defecto viven en código (`DEFAULTS`), no sólo en la
 * migración: si falta la fila o la base de datos no responde, el sitio
 * público sigue renderizándose con la paleta original en vez de romperse.
 *
 * @package App\Services
 * @since   2026-10-26
 */
final class PublicThemeService
{
    /** Grupo de configuración que usa esta funcionalidad. */
    public const GROUP = 'public';

    /** Tope de longitud del CSS personalizado (100 KB). */
    public const MAX_CUSTOM_CSS_BYTES = 102400;

    /**
     * Áreas de logotipo configurables.
     *
     * `key`    → sufijo de la clave en `settings` (`public_<key>`).
     * `label`  → etiqueta humana.
     * `hint`   → tamaño recomendado que muestra la interfaz.
     * `where`  → dónde se pinta de verdad. Si un hueco no tiene consumidor se
     *            oculta en la interfaz (`HIDDEN_SLOTS`) para no ofrecer un
     *            control que no hace nada.
     *
     * @var array<string, array{label: string, hint: string, where: string}>
     */
    public const LOGO_SLOTS = [
        'logo_header'     => ['label' => 'Logo principal del encabezado', 'hint' => 'Ancho 240–320 px, alto libre, fondo transparente', 'where' => 'Cabecera del sitio'],
        'logo_desktop'    => ['label' => 'Logo para escritorio',          'hint' => 'Ancho 320 px a 2x (640 px)', 'where' => 'Cabecera, pantallas de 992 px o más'],
        'logo_tablet'     => ['label' => 'Logo para tablet',              'hint' => 'Ancho 220 px a 2x (440 px)', 'where' => 'Cabecera, pantallas de 576 a 991 px'],
        'logo_mobile'     => ['label' => 'Logo para móvil',               'hint' => 'Ancho 160 px a 2x (320 px)', 'where' => 'Cabecera, pantallas de menos de 576 px'],
        'logo_menu'       => ['label' => 'Logo del menú principal',       'hint' => 'Cuadrado 64×64 px', 'where' => 'Cabecera del menú desplegado en móvil'],
        'logo_multinivel' => ['label' => 'Logo de menús multinivel',      'hint' => 'Cuadrado 40×40 px', 'where' => 'Cabecera de cada desplegable y submenú'],
        'logo_footer'     => ['label' => 'Logo del pie de página',        'hint' => 'Ancho 200 px, alto libre', 'where' => 'Pie del sitio'],
        'logo_sidebar'    => ['label' => 'Logo de la barra lateral',      'hint' => 'Ancho 180 px, alto libre', 'where' => 'Sin componente: se oculta en la interfaz'],
        'logo_dark'       => ['label' => 'Logo alternativo para fondos oscuros', 'hint' => 'Versión clara de la marca', 'where' => 'Cabecera con el sistema en modo oscuro'],
        'logo_light'      => ['label' => 'Logo alternativo para fondos claros',  'hint' => 'Versión oscura de la marca', 'where' => 'Cabecera con el sistema en modo claro'],
    ];

    /**
     * Huecos que se guardan pero **no se ofrecen** en la interfaz porque el
     * sitio público todavía no tiene el componente donde pintarlos.
     *
     * `logo_sidebar`: no existe barra lateral en el layout público. El valor
     * sigue en `settings` y `public_logo('logo_sidebar')` lo devuelve, así que
     * en cuanto haya un componente con esa clase basta con quitar la clave de
     * esta lista.
     *
     * @var array<int, string>
     */
    public const HIDDEN_SLOTS = ['logo_sidebar'];

    /**
     * Huecos de favicon configurables.
     *
     * @var array<string, array{label: string, hint: string, sizes: string}>
     */
    public const FAVICON_SLOTS = [
        'favicon_ico'      => ['label' => 'favicon.ico',      'hint' => 'ICO de 16, 32 o 48 px', 'sizes' => 'any'],
        'favicon_16'       => ['label' => 'favicon-16x16',    'hint' => 'PNG de 16×16 px',       'sizes' => '16x16'],
        'favicon_32'       => ['label' => 'favicon-32x32',    'hint' => 'PNG de 32×32 px',       'sizes' => '32x32'],
        'apple_touch_icon' => ['label' => 'apple-touch-icon', 'hint' => 'PNG de 180×180 px',     'sizes' => '180x180'],
    ];

    /**
     * Las 16 claves de color y su etiqueta.
     *
     * El orden es el que muestra la interfaz de administración.
     *
     * @var array<string, string>
     */
    public const COLOR_LABELS = [
        'color_primary'       => 'Color primario',
        'color_secondary'     => 'Color secundario',
        'color_accent'        => 'Color de acento',
        'color_background'    => 'Fondo principal',
        'color_section_bg'    => 'Fondo de secciones',
        'color_text'          => 'Texto principal',
        'color_text_muted'    => 'Texto secundario',
        'color_link'          => 'Enlaces',
        'color_button'        => 'Botones',
        'color_border'        => 'Bordes',
        'color_header_bg'     => 'Fondo del header',
        'color_footer_bg'     => 'Fondo del footer',
        'color_sidebar_bg'    => 'Fondo del sidebar',
        'color_menu_bg'       => 'Fondo del menú principal',
        'color_multinivel_bg' => 'Fondo de menús multinivel',
        'color_menu_hover'    => 'Estados hover y active',
    ];

    /**
     * Temas predefinidos: paleta completa de las 16 claves.
     *
     * «Personalizado» no es un preset: significa «usa los colores guardados».
     * Al pulsar un preset, la interfaz rellena los 16 campos con estos valores
     * y al guardar se persisten como cualquier otro color. Así hay una sola
     * fuente de verdad (las 16 claves) y nunca hay colores «virtuales».
     *
     * @var array<string, array{label: string, description: string, colors: array<string, string>}>
     */
    public const THEME_PRESETS = [
        'claro' => [
            'label'       => 'Claro',
            'description' => 'Fondo blanco, texto oscuro. Máxima legibilidad con luz ambiental.',
            'colors'      => [
                'color_primary'       => '#0d6efd',
                'color_secondary'     => '#6c757d',
                'color_accent'        => '#ffc107',
                'color_background'    => '#ffffff',
                'color_section_bg'    => '#f8f9fa',
                'color_text'          => '#212529',
                'color_text_muted'    => '#6c757d',
                'color_link'          => '#0d6efd',
                'color_button'        => '#0d6efd',
                'color_border'        => '#dee2e6',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#f8f9fa',
                'color_sidebar_bg'    => '#f8f9fa',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#ffffff',
                'color_menu_hover'    => '#e9ecef',
            ],
        ],
        'oscuro' => [
            'label'       => 'Oscuro',
            'description' => 'La paleta original del sitio: grafito y verde azulado.',
            'colors'      => [
                'color_primary'       => '#00d4aa',
                'color_secondary'     => '#8b949e',
                'color_accent'        => '#00d4aa',
                'color_background'    => '#0d1117',
                'color_section_bg'    => '#161b22',
                'color_text'          => '#e6edf3',
                'color_text_muted'    => '#8b949e',
                'color_link'          => '#00d4aa',
                'color_button'        => '#00d4aa',
                'color_border'        => '#30363d',
                'color_header_bg'     => '#0d1117',
                'color_footer_bg'     => '#161b22',
                'color_sidebar_bg'    => '#161b22',
                'color_menu_bg'       => '#0d1117',
                'color_multinivel_bg' => '#161b22',
                'color_menu_hover'    => '#1f6f5f',
            ],
        ],
        'corporativo' => [
            'label'       => 'Corporativo',
            'description' => 'Azul marino con acento dorado. Transmite institución y confianza.',
            'colors'      => [
                'color_primary'       => '#1b3a5c',
                'color_secondary'     => '#4a6fa5',
                'color_accent'        => '#c9a227',
                'color_background'    => '#f4f6f9',
                'color_section_bg'    => '#ffffff',
                'color_text'          => '#1b2733',
                'color_text_muted'    => '#5a6b7b',
                'color_link'          => '#1b3a5c',
                'color_button'        => '#1b3a5c',
                'color_border'        => '#d5dde5',
                'color_header_bg'     => '#1b3a5c',
                'color_footer_bg'     => '#14293f',
                'color_sidebar_bg'    => '#eef2f6',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#ffffff',
                'color_menu_hover'    => '#dce5ef',
            ],
        ],
        'minimalista' => [
            'label'       => 'Minimalista',
            'description' => 'Blanco, negro y un borde fino. El contenido es lo único que destaca.',
            'colors'      => [
                'color_primary'       => '#111111',
                'color_secondary'     => '#555555',
                'color_accent'        => '#111111',
                'color_background'    => '#ffffff',
                'color_section_bg'    => '#ffffff',
                'color_text'          => '#111111',
                'color_text_muted'    => '#666666',
                'color_link'          => '#111111',
                'color_button'        => '#111111',
                'color_border'        => '#e5e5e5',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#ffffff',
                'color_sidebar_bg'    => '#fafafa',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#ffffff',
                'color_menu_hover'    => '#f2f2f2',
            ],
        ],
    ];

    /**
     * Valores por defecto de todas las claves gestionadas.
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $defaults = [
            'theme'              => 'oscuro',
            'logo_alt'           => '',
            'custom_css'         => '',
            'custom_css_enabled' => '0',
        ];

        foreach (array_keys(self::LOGO_SLOTS) as $slot) {
            $defaults[$slot] = '';
        }
        foreach (array_keys(self::FAVICON_SLOTS) as $slot) {
            $defaults[$slot] = '';
        }
        foreach (self::THEME_PRESETS['oscuro']['colors'] as $key => $value) {
            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /** @var array<string, string>|null Caché de petición. */
    private static ?array $values = null;

    /**
     * Devuelve toda la configuración pública, ya mezclada con los defectos.
     *
     * Una sola consulta por petición (`SELECT ... WHERE group = ?`), cacheada
     * en estático. Si la base de datos falla se devuelven los valores por
     * defecto: la web pública nunca se cae por su configuración visual.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        $values = self::defaults();

        try {
            $rows = Database::fetchAll(
                'SELECT `key`, `value` FROM `settings` WHERE `group` = ?',
                [self::GROUP]
            );
            foreach ($rows as $row) {
                $key = (string) $row['key'];
                if (array_key_exists($key, $values)) {
                    $values[$key] = (string) ($row['value'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            error_log('[PublicTheme] No se pudo leer la configuración pública: ' . $e->getMessage());
        }

        // Una fila vacía no puede dejar el sitio sin color ni sin tema: cae al
        // defecto. `custom_css` y `logo_alt` sí admiten vacío a propósito.
        $defaults = self::defaults();
        foreach ($values as $key => $value) {
            if ($value === '' && $key !== 'custom_css' && $key !== 'logo_alt') {
                $values[$key] = $defaults[$key] ?? '';
            }
        }

        return self::$values = $values;
    }

    /**
     * Invalida la caché de petición. Obligatorio tras guardar.
     */
    public static function flush(): void
    {
        self::$values = null;
    }

    /**
     * Valor de una clave concreta.
     */
    public static function get(string $key, ?string $default = null): string
    {
        $all = self::all();
        return $all[$key] ?? $default ?? (self::defaults()[$key] ?? '');
    }

    /**
     * ¿Está activado el CSS personalizado?
     */
    public static function customCssEnabled(): bool
    {
        return self::get('custom_css_enabled') === '1';
    }

    /**
     * URL de un logotipo configurado, o null si no hay ninguno.
     *
     * Acepta los slots de `LOGO_SLOTS` y los de `FAVICON_SLOTS`.
     */
    public static function assetUrl(string $slot): ?string
    {
        $relative = self::assetRelativePath($slot);
        if ($relative === null) {
            return null;
        }
        return url('uploads/' . $relative);
    }

    /**
     * Ruta relativa (dentro de `uploads/`) del archivo de un slot, si existe
     * de verdad en disco y la ruta guardada es de confianza.
     */
    public static function assetRelativePath(string $slot): ?string
    {
        if (array_key_exists($slot, self::LOGO_SLOTS)) {
            $prefix = 'settings/logos/';
        } elseif (array_key_exists($slot, self::FAVICON_SLOTS)) {
            $prefix = 'settings/favicons/';
        } else {
            return null;
        }

        $value = self::get($slot);
        if ($value === '' || !str_starts_with($value, $prefix)) {
            return null;
        }
        // Sólo nombre de archivo simple: sin `..`, sin barras, sin esquemas.
        $name = substr($value, strlen($prefix));
        if ($name === '' || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,120}$/', $name) !== 1) {
            return null;
        }
        if (str_contains($name, '..')) {
            return null;
        }

        return is_file(UPLOADS_PATH . '/' . $prefix . $name) ? $value : null;
    }

    /**
     * Primer logotipo disponible de una cadena de preferencia.
     *
     * @param string ...$slots Slots en orden de preferencia.
     */
    public static function firstLogo(string ...$slots): ?string
    {
        foreach ($slots as $slot) {
            $url = self::assetUrl($slot);
            if ($url !== null) {
                return $url;
            }
        }
        return null;
    }

    /**
     * Texto alternativo de los logotipos.
     *
     * Orden: el texto configurado en Configuración → Identidad visual y, si
     * está vacío, el nombre del sitio guardado en Configuración → General.
     */
    public static function logoAlt(): string
    {
        $alt = trim(self::get('logo_alt'));
        if ($alt !== '') {
            return $alt;
        }
        return SiteSettings::name();
    }

    /**
     * ¿El fondo configurado es oscuro? Decide `data-bs-theme` del documento
     * público, que es lo que hace que los componentes de Bootstrap cambien
     * de piel junto con los tokens propios.
     */
    public static function isDark(): bool
    {
        return self::luminance(self::get('color_background')) < 0.5;
    }

    /**
     * CSS generado para la web pública.
     *
     * Contiene: las 16 variables `--public-*`, el puente hacia los tokens que
     * el layout base ya usaba (`--bg-main`, `--accent`, …) y unas pocas reglas
     * de área (header, footer, menú, multinivel, sidebar). Todo bajo
     * `body.public-site` para que no pueda alcanzar al panel.
     */
    public static function css(): string
    {
        $values = self::all();

        $declarations = '';
        foreach (self::COLOR_LABELS as $key => $label) {
            $hex = self::normalizeHex($values[$key] ?? '') ?? self::defaults()[$key];
            $var = str_replace('color_', '', $key);
            $var = str_replace('_bg', '-bg', $var);
            $declarations .= '    --public-' . str_replace('_', '-', $var) . ': ' . $hex . ";\n";
        }

        $accent      = self::normalizeHex($values['color_accent']) ?? '#00d4aa';
        $border      = self::normalizeHex($values['color_border']) ?? '#30363d';
        $accentHover = self::shade($accent, self::luminance($accent) < 0.5 ? 14 : -14);
        [$br, $bg, $bb] = self::toRgb($border);

        $css = "/* Generado por PublicThemeService — no editar a mano. */\n";
        $css .= "body.public-site {\n";
        $css .= $declarations;
        $css .= "\n    /* Puente con los tokens del layout base del sitio público. */\n";
        $css .= "    --bg-main: var(--public-background);\n";
        $css .= "    --bg-card: var(--public-section-bg);\n";
        $css .= "    --bg-input: var(--public-background);\n";
        $css .= "    --text-main: var(--public-text);\n";
        $css .= "    --text-muted: var(--public-text-muted);\n";
        $css .= "    --accent: var(--public-accent);\n";
        $css .= "    --accent-hover: {$accentHover};\n";
        $css .= "    --border-color: var(--public-border);\n";
        $css .= "    --border-soft: rgba({$br}, {$bg}, {$bb}, .18);\n";
        $css .= "    background-color: var(--public-background);\n";
        $css .= "    color: var(--public-text);\n";
        $css .= "}\n";

        $css .= <<<CSS
body.public-site a { color: var(--public-link); }
body.public-site a:hover,
body.public-site a:focus-visible { color: var(--accent-hover); }
body.public-site .navbar-main { background-color: var(--public-header-bg); border-bottom-color: var(--public-border); }
body.public-site .navbar-collapse { background-color: var(--public-menu-bg); }
body.public-site .navbar .nav-link:hover,
body.public-site .navbar .nav-link:focus-visible,
body.public-site .navbar .nav-link.active { background-color: var(--public-menu-hover); }
body.public-site .navbar .dropdown-menu,
body.public-site .navbar .dropdown-submenu > .dropdown-menu { background-color: var(--public-multinivel-bg); border-color: var(--public-border); }
body.public-site .footer { background-color: var(--public-footer-bg); border-top-color: var(--public-border); }
body.public-site .sidebar,
body.public-site .public-sidebar { background-color: var(--public-sidebar-bg); }
body.public-site .btn-accent { background-color: var(--public-button); border-color: var(--public-button); }
body.public-site .btn-accent:hover,
body.public-site .btn-accent:focus-visible { background-color: var(--accent-hover); border-color: var(--accent-hover); }
/* Primario y secundario necesitan consumidor propio: si no, son dos colores
   configurables que no cambian nada en pantalla. */
body.public-site .btn-primary { background-color: var(--public-primary); border-color: var(--public-primary); }
body.public-site .btn-primary:hover,
body.public-site .btn-primary:focus-visible { background-color: var(--public-secondary); border-color: var(--public-secondary); }
body.public-site .text-muted,
body.public-site .form-text,
body.public-site small.text-muted { color: var(--public-secondary) !important; }
body.public-site .brand-logo { max-height: 44px; width: auto; }
body.public-site .brand-logo--footer { max-height: 36px; }
body.public-site .brand-logo--sidebar { max-height: 32px; }
body.public-site .brand-logo--menu { max-height: 40px; width: auto; }
CSS;

        // ── Logo de menús multinivel ────────────────────────────────
        // Se pinta como cabecera de cada desplegable (.dropdown-menu), incluidos
        // los submenús, sin tocar el generador de menús: cero DOM añadido y
        // cero cambios cuando no hay logo configurado.
        $multinivelLogo = self::assetUrl('logo_multinivel');
        if ($multinivelLogo !== null) {
            $safeUrl = str_replace(['"', '\\', '(', ')'], ['%22', '%5C', '%28', '%29'], $multinivelLogo);
            $css .= "\nbody.public-site .navbar ul.dropdown-menu::before {\n"
                . "    content: \"\";\n"
                . "    display: block;\n"
                . "    height: 28px;\n"
                . "    margin: .4rem .75rem .3rem;\n"
                . "    background-image: url(\"{$safeUrl}\");\n"
                . "    background-repeat: no-repeat;\n"
                . "    background-position: left center;\n"
                . "    background-size: contain;\n"
                . "}\n";
        }

        return $css . "\n";
    }

    /**
     * Huecos de logotipo que la interfaz debe mostrar.
     *
     * Excluye los de `HIDDEN_SLOTS`: son ajustes que aún no tienen componente
     * donde pintarse y ofrecerlos sería prometer algo que no ocurre.
     *
     * @return array<string, array{label: string, hint: string, where: string}>
     */
    public static function visibleLogoSlots(): array
    {
        return array_diff_key(self::LOGO_SLOTS, array_flip(self::HIDDEN_SLOTS));
    }

    /**
     * CSS personalizado del administrador, ya validado y **acotado** al sitio
     * público, o cadena vacía si está desactivado o no supera la validación.
     */
    public static function customCss(): string
    {
        if (!self::customCssEnabled()) {
            return '';
        }
        $raw = self::get('custom_css');
        if (trim($raw) === '') {
            return '';
        }
        [$clean, $errors] = self::validateCustomCss($raw);
        if ($errors !== []) {
            error_log('[PublicTheme] CSS personalizado descartado: ' . implode(' ', $errors));
            return '';
        }
        return self::scopeCss($clean);
    }

    /**
     * Acota el CSS libre bajo `body.public-site`.
     *
     * El administrador escribe CSS sin prefijo; sin este paso, una regla como
     * `a { color: red }` afectaría a todo el documento público. Se antepone el
     * ámbito a cada selector de primer nivel y se desciende por `@media`,
     * `@supports`, `@layer` y `@container`. `@keyframes`, `@font-face` y
     * `@page` se dejan intactos porque no aceptan prefijo.
     *
     * Es un transformador **de mejor esfuerzo, no un parser de CSS**: ante una
     * construcción que no entiende, la regla se queda como está y el navegador
     * la ignora. El modo de fallo es «no se aplica», nunca «se aplica global».
     *
     * @param string $css   CSS ya validado.
     * @param string $scope Selector de ámbito.
     */
    public static function scopeCss(string $css, string $scope = 'body.public-site'): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }
        return self::scopeNodes(self::parseCssNodes($css), $scope);
    }

    /**
     * Separa el CSS en nodos «preludio + cuerpo» de primer nivel.
     *
     * @return array<int, array{prelude: string, body: ?string}>
     */
    private static function parseCssNodes(string $css): array
    {
        $nodes = [];
        $buffer = '';
        $length = strlen($css);

        for ($i = 0; $i < $length; $i++) {
            $char = $css[$i];

            // Los comentarios se arrastran con el preludio, no se interpretan.
            if ($char === '/' && ($css[$i + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $i + 2);
                $end = $end === false ? $length : $end + 2;
                $buffer .= substr($css, $i, $end - $i);
                $i = $end - 1;
                continue;
            }

            if ($char === '{') {
                $level = 1;
                $j = $i + 1;
                while ($j < $length && $level > 0) {
                    if ($css[$j] === '{') {
                        $level++;
                    } elseif ($css[$j] === '}') {
                        $level--;
                    }
                    $j++;
                }
                $nodes[] = [
                    'prelude' => trim($buffer),
                    'body'    => substr($css, $i + 1, max(0, $j - $i - 2)),
                ];
                $buffer = '';
                $i = $j - 1;
                continue;
            }

            if ($char === '}') {
                continue; // cierre huérfano: se descarta
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $nodes[] = ['prelude' => trim($buffer), 'body' => null];
        }

        return $nodes;
    }

    /**
     * Aplica el ámbito a una lista de nodos ya separados.
     *
     * @param array<int, array{prelude: string, body: ?string}> $nodes
     */
    private static function scopeNodes(array $nodes, string $scope): string
    {
        $out = '';

        foreach ($nodes as $node) {
            $prelude = $node['prelude'];
            $body    = $node['body'];

            if ($body === null) {
                // Suelto: @charset, un comentario final… se deja tal cual.
                $out .= $prelude . "\n";
                continue;
            }

            if ($prelude !== '' && $prelude[0] === '@') {
                preg_match('/^@([a-zA-Z-]+)/', $prelude, $match);
                $atRule = strtolower($match[1] ?? '');
                if (in_array($atRule, ['media', 'supports', 'layer', 'container'], true)) {
                    $out .= $prelude . " {\n" . self::scopeNodes(self::parseCssNodes($body), $scope) . "}\n";
                } else {
                    // @keyframes, @font-face, @page: no admiten prefijo de selector.
                    $out .= $prelude . ' {' . $body . "}\n";
                }
                continue;
            }

            $selectors = [];
            foreach (explode(',', $prelude) as $selector) {
                $selector = trim($selector);
                if ($selector === '') {
                    continue;
                }
                if (str_starts_with($selector, $scope)) {
                    $selectors[] = $selector;
                    continue;
                }
                // `:root`, `html`, `body` y `*` se refieren al documento entero:
                // dentro del sitio público, el equivalente es el propio ámbito.
                if (in_array($selector, [':root', 'html', 'body', '*'], true)) {
                    $selectors[] = $scope;
                    continue;
                }
                $selectors[] = $scope . ' ' . $selector;
            }

            if ($selectors === []) {
                continue;
            }

            $out .= implode(', ', $selectors) . ' {' . $body . "}\n";
        }

        return $out;
    }

    /**
     * Valida el CSS personalizado.
     *
     * No intenta interpretar CSS: rechaza las construcciones que permitirían
     * salir del bloque `<style>` o ejecutar código, y deja pasar el resto.
     * El CSS nunca se carga en el panel, así que el riesgo se limita al sitio
     * público, pero se filtra igualmente.
     *
     * @return array{0: string, 1: array<int, string>} [css limpio, errores]
     */
    public static function validateCustomCss(string $css): array
    {
        $errors = [];
        $clean  = str_replace(["\0", "\r\n", "\r"], ['', "\n", "\n"], $css);

        if (strlen($clean) > self::MAX_CUSTOM_CSS_BYTES) {
            $errors[] = 'El CSS supera el límite de ' . (self::MAX_CUSTOM_CSS_BYTES / 1024) . ' KB.';
        }

        $forbidden = [
            '<'                     => 'No se permite el carácter «<» (podría cerrar la etiqueta style).',
            '@import'               => 'No se permite @import: enlaza hojas de estilo externas.',
            'expression('           => 'No se permite expression(), exclusivo de Internet Explorer.',
            'javascript:'           => 'No se permiten URL javascript:.',
            'vbscript:'             => 'No se permiten URL vbscript:.',
            'behavior:'             => 'No se permite behavior:.',
            '-moz-binding'          => 'No se permite -moz-binding.',
        ];

        $lower = strtolower($clean);
        foreach ($forbidden as $needle => $message) {
            if (str_contains($lower, $needle)) {
                $errors[] = $message;
            }
        }

        return [trim($clean), $errors];
    }

    /**
     * Etiquetas `<link>` del `<head>` público.
     *
     * Sólo se emiten las que corresponden a archivos realmente presentes, así
     * que borrar un favicon lo quita del HTML sin dejar referencias rotas.
     */
    public static function faviconTags(): string
    {
        $tags = [];

        if (($ico = self::assetUrl('favicon_ico')) !== null) {
            $tags[] = '<link rel="icon" href="' . e($ico) . '" sizes="any">';
        }
        if (($png16 = self::assetUrl('favicon_16')) !== null) {
            $tags[] = '<link rel="icon" type="image/png" sizes="16x16" href="' . e($png16) . '">';
        }
        if (($png32 = self::assetUrl('favicon_32')) !== null) {
            $tags[] = '<link rel="icon" type="image/png" sizes="32x32" href="' . e($png32) . '">';
        }
        if (($apple = self::assetUrl('apple_touch_icon')) !== null) {
            $tags[] = '<link rel="apple-touch-icon" sizes="180x180" href="' . e($apple) . '">';
        }

        if ($tags === []) {
            return '';
        }

        $tags[] = '<link rel="manifest" href="' . e(url('site.webmanifest')) . '">';

        return implode("\n    ", $tags);
    }

    /**
     * Contenido del `site.webmanifest` según los iconos disponibles.
     *
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        // El nombre que verá el móvil al añadir el sitio a la pantalla de
        // inicio es el configurado en General, no el del archivo de config.
        $name = SiteSettings::name();

        $icons = [];
        foreach (self::FAVICON_SLOTS as $slot => $meta) {
            $url = self::assetUrl($slot);
            if ($url === null) {
                continue;
            }
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            $icons[] = [
                // Rutas relativas a la raíz: el manifiesto se sirve con caché
                // pública y no debe arrastrar el host de la petición (si un
                // proxy comparte caché entre hosts, se envenenaría).
                'src'     => parse_url($url, PHP_URL_PATH) ?: '/',
                'sizes'   => $meta['sizes'],
                'type'    => match ($extension) {
                    'svg'   => 'image/svg+xml',
                    'ico'   => 'image/x-icon',
                    default => 'image/png',
                },
                'purpose' => 'any',
            ];
        }

        return [
            'name'             => $name,
            'short_name'       => mb_substr($name, 0, 12),
            'start_url'        => parse_url(url('/'), PHP_URL_PATH) ?: '/',
            'display'          => 'standalone',
            'background_color' => self::get('color_background'),
            'theme_color'      => self::get('color_primary'),
            'icons'            => $icons,
        ];
    }

    /**
     * Huella de la configuración actual, para `ETag` del CSS dinámico.
     */
    public static function version(): string
    {
        $payload = self::css() . "\n" . self::customCss();
        return substr(sha1($payload), 0, 16);
    }

    /**
     * Guarda la configuración enviada por el formulario.
     *
     * Valida antes de escribir: si algo no cuadra no se persiste **nada** y se
     * devuelven los errores, para que la interfaz no deje el estado a medias.
     *
     * @param array<string, mixed> $input Datos ya extraídos del POST.
     * @return array{ok: bool, errors: array<int, string>}
     */
    public static function save(array $input): array
    {
        $errors = [];
        $rows   = [];

        // ── Tema ────────────────────────────────────────────────────
        // Sólo se toca si viene en la petición: un envío parcial (por ejemplo
        // guardar únicamente el texto alternativo) no debe renombrar el tema
        // a «personalizado» por omisión.
        if (array_key_exists('theme', $input)) {
            $theme = (string) $input['theme'];
            if ($theme !== 'personalizado' && !array_key_exists($theme, self::THEME_PRESETS)) {
                $errors[] = 'Tema desconocido.';
            } else {
                $rows['theme'] = ['value' => $theme, 'type' => 'string'];
            }
        }

        // ── Colores ─────────────────────────────────────────────────
        $colors = is_array($input['colors'] ?? null) ? $input['colors'] : [];
        foreach (self::COLOR_LABELS as $key => $label) {
            $raw = (string) ($colors[$key] ?? '');
            if ($raw === '') {
                continue; // no enviado: se conserva el valor actual
            }
            $hex = self::normalizeHex($raw);
            if ($hex === null) {
                $errors[] = sprintf('%s: «%s» no es un color HEX válido (#rgb o #rrggbb).', $label, mb_substr($raw, 0, 20));
                continue;
            }
            $rows[$key] = ['value' => $hex, 'type' => 'string'];
        }

        // ── Texto alternativo ───────────────────────────────────────
        if (array_key_exists('logo_alt', $input)) {
            $alt = trim(strip_tags((string) $input['logo_alt']));
            if (mb_strlen($alt) > 200) {
                $errors[] = 'El texto alternativo no puede superar 200 caracteres.';
            } else {
                $rows['logo_alt'] = ['value' => $alt, 'type' => 'string'];
            }
        }

        // ── CSS personalizado ───────────────────────────────────────
        if (array_key_exists('custom_css', $input)) {
            [$clean, $cssErrors] = self::validateCustomCss((string) $input['custom_css']);
            if ($cssErrors !== []) {
                foreach ($cssErrors as $cssError) {
                    $errors[] = 'CSS personalizado: ' . $cssError;
                }
            } else {
                $rows['custom_css'] = ['value' => $clean, 'type' => 'string'];
            }
        }
        if (array_key_exists('custom_css_enabled', $input)) {
            $enabled = in_array((string) $input['custom_css_enabled'], ['1', 'true', 'on'], true);
            $rows['custom_css_enabled'] = ['value' => $enabled ? '1' : '0', 'type' => 'bool'];
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }
        if ($rows === []) {
            return ['ok' => false, 'errors' => ['No se recibió ningún valor que guardar.']];
        }

        Setting::updateBatch([self::GROUP => $rows]);
        self::flush();

        return ['ok' => true, 'errors' => []];
    }

    /**
     * Restablece los 16 colores al preset indicado y guarda.
     *
     * Es la acción del botón «Restablecer configuración»: reversible, porque
     * los valores anteriores son los del preset y no se toca ningún archivo.
     *
     * @return array{ok: bool, errors: array<int, string>}
     */
    public static function resetColors(string $theme = 'oscuro'): array
    {
        if (!array_key_exists($theme, self::THEME_PRESETS)) {
            return ['ok' => false, 'errors' => ['Tema desconocido.']];
        }

        $rows = [];
        foreach (self::THEME_PRESETS[$theme]['colors'] as $key => $hex) {
            $rows[$key] = ['value' => $hex, 'type' => 'string'];
        }
        $rows['theme'] = ['value' => $theme, 'type' => 'string'];

        Setting::updateBatch([self::GROUP => $rows]);
        self::flush();

        return ['ok' => true, 'errors' => []];
    }

    // ────────────────────────────────────────────────────────────────
    //  Utilidades de color
    // ────────────────────────────────────────────────────────────────

    /**
     * Normaliza un color HEX a `#rrggbb` en minúsculas, o null si no es válido.
     */
    public static function normalizeHex(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if ($value[0] !== '#') {
            $value = '#' . $value;
        }
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) !== 1) {
            return null;
        }
        $hex = strtolower(ltrim($value, '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . $hex;
    }

    /**
     * Luminancia relativa (0 = negro, 1 = blanco) según WCAG 2.1.
     */
    public static function luminance(string $hex): float
    {
        $normalized = self::normalizeHex($hex);
        if ($normalized === null) {
            return 0.0;
        }
        [$r, $g, $b] = self::toRgb($normalized);
        $channel = static function (int $value): float {
            $s = $value / 255;
            return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    /**
     * Aclara (porcentaje positivo) u oscurece (negativo) un color HEX.
     */
    public static function shade(string $hex, int $percent): string
    {
        $normalized = self::normalizeHex($hex);
        if ($normalized === null) {
            return '#000000';
        }
        [$r, $g, $b] = self::toRgb($normalized);
        $factor = max(-100, min(100, $percent)) / 100;
        $adjust = static function (int $value) use ($factor): int {
            $target = $factor >= 0 ? 255 : 0;
            $result = (int) round($value + ($target - $value) * abs($factor));
            return max(0, min(255, $result));
        };
        return sprintf('#%02x%02x%02x', $adjust($r), $adjust($g), $adjust($b));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function toRgb(string $hex): array
    {
        $normalized = self::normalizeHex($hex) ?? '#000000';
        $value = ltrim($normalized, '#');
        return [
            (int) hexdec(substr($value, 0, 2)),
            (int) hexdec(substr($value, 2, 2)),
            (int) hexdec(substr($value, 4, 2)),
        ];
    }
}
