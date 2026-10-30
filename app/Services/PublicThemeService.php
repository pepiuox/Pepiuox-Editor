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
     * Colores de texto **sobre cada superficie**, editables.
     *
     * Los 16 colores de `COLOR_LABELS` son fondos y textos base; estos son el
     * texto de los elementos que se pintan encima de un fondo propio (header,
     * menú, desplegables, pie, barra lateral) y el de los botones. Sin ellos, el
     * color del texto lo decidía el layout base y un tema con fondo oscuro en el
     * header dejaba el menú ilegible.
     *
     * `surface` dice contra qué fondo se mide el contraste.
     * Vacío significa **automático**: se calcula el color que cumple AA sobre esa
     * superficie. Con un valor, manda el administrador.
     *
     * @var array<string, array{label: string, surface: string, hint: string}>
     */
    public const SURFACE_TEXT_LABELS = [
        'color_header_text'     => ['label' => 'Texto del header',            'surface' => 'color_header_bg',     'hint' => 'Marca y contenido sobre la cabecera'],
        'color_menu_text'       => ['label' => 'Texto del menú principal',    'surface' => 'color_menu_bg',       'hint' => 'Enlaces del menú, en reposo'],
        'color_menu_hover_text' => ['label' => 'Texto del menú al pasar',     'surface' => 'color_menu_hover',    'hint' => 'Hover, foco y elemento activo'],
        'color_multinivel_text' => ['label' => 'Texto de los desplegables',   'surface' => 'color_multinivel_bg', 'hint' => 'Items de menús multinivel'],
        'color_footer_text'     => ['label' => 'Texto del pie',               'surface' => 'color_footer_bg',     'hint' => 'Contenido del pie de página'],
        'color_sidebar_text'    => ['label' => 'Texto de la barra lateral',   'surface' => 'color_sidebar_bg',    'hint' => 'Cuando exista barra lateral'],
        'color_button_text'     => ['label' => 'Texto de los botones',        'surface' => 'color_button',        'hint' => 'Botones de acción del menú y del contenido'],
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
                // Azules más oscuros que el estándar de Bootstrap: #0d6efd sobre
                // blanco se queda en 4.0:1 y no llega a AA para texto normal.
                'color_primary'       => '#0a58ca',
                'color_secondary'     => '#565e64',
                'color_accent'        => '#ffc107',
                'color_background'    => '#ffffff',
                'color_section_bg'    => '#f8f9fa',
                'color_text'          => '#1a1d21',
                'color_text_muted'    => '#565e64',
                'color_link'          => '#0a58ca',
                'color_button'        => '#0a58ca',
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
        // Texto sobre superficies: vacío = automático (contraste calculado).
        foreach (array_keys(self::SURFACE_TEXT_LABELS) as $key) {
            $defaults[$key] = '';
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
        // defecto. Estas claves sí admiten vacío a propósito, porque vacío
        // significa «automático»:
        //   - custom_css y logo_alt: texto opcional
        //   - los textos sobre superficies: contraste calculado
        $admitenVacio = array_merge(
            ['custom_css', 'logo_alt'],
            array_keys(self::SURFACE_TEXT_LABELS)
        );
        $defaults = self::defaults();
        foreach ($values as $key => $value) {
            if ($value === '' && !in_array($key, $admitenVacio, true)) {
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
     * Color de texto efectivo de cada superficie.
     *
     * Automático (vacío) → se calcula el que cumple AA sobre su fondo. Con valor
     * → manda el administrador, que ve el ratio medido en la interfaz.
     *
     * @return array<string, string>
     */
    public static function resolvedSurfaceTexts(): array
    {
        $values     = self::all();
        $background = self::normalizeHex($values['color_background'] ?? '') ?? '#0d1117';
        $resolved   = [];

        foreach (self::SURFACE_TEXT_LABELS as $key => $meta) {
            $explicit  = self::normalizeHex($values[$key] ?? '');
            $surfaceBg = self::normalizeHex($values[$meta['surface']] ?? '') ?? $background;

            $resolved[$key] = $explicit ?? ($key === 'color_button_text'
                ? self::contrastColor($surfaceBg)
                : self::ensureContrast($values['color_text'] ?? '', $surfaceBg, 4.5));
        }

        return $resolved;
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

        // ── Legibilidad garantizada ─────────────────────────────────
        // Los colores los elige una persona, y una combinación válida en HEX
        // puede dejar el texto ilegible (texto oscuro sobre botón azul, gris
        // claro sobre blanco…). Antes de publicar, cada color de texto se ajusta
        // lo justo para alcanzar el mínimo AA sobre su fondo real. El valor
        // guardado no se toca: el ajuste se calcula al generar el CSS.
        $background = self::normalizeHex($values['color_background']) ?? '#0d1117';
        $text       = self::ensureContrast($values['color_text'] ?? '', $background, 4.5);
        $textMuted  = self::ensureContrast($values['color_text_muted'] ?? '', $background, 4.5);
        $link       = self::ensureContrast($values['color_link'] ?? '', $background, 4.5);
        $buttonBg   = self::normalizeHex($values['color_button'] ?? '') ?? '#00d4aa';
        $primaryBg  = self::normalizeHex($values['color_primary'] ?? '') ?? '#00d4aa';
        $buttonText  = self::contrastColor($buttonBg);
        $primaryText = self::contrastColor($primaryBg);

        // ── Texto sobre cada superficie ─────────────────────────────
        $surfaceText = self::resolvedSurfaceTexts();

        $headerText     = $surfaceText['color_header_text'];
        $menuText       = $surfaceText['color_menu_text'];
        $menuHoverText  = $surfaceText['color_menu_hover_text'];
        $multinivelText = $surfaceText['color_multinivel_text'];
        $footerText     = $surfaceText['color_footer_text'];
        $sidebarText    = $surfaceText['color_sidebar_text'];
        $buttonText     = $surfaceText['color_button_text'];

        // Atenuado sobre cada superficie, recalculado: el del documento no
        // vale sobre un pie oscuro ni sobre un header claro.
        $footerMuted  = self::ensureContrast($values['color_text_muted'] ?? '', $values['color_footer_bg'] ?? $background, 4.5);
        $headerMuted  = self::ensureContrast($values['color_text_muted'] ?? '', $values['color_header_bg'] ?? $background, 4.5);
        $menuMuted    = self::ensureContrast($values['color_text_muted'] ?? '', $values['color_menu_bg'] ?? $background, 4.5);

        $declarations = '';
        foreach (self::COLOR_LABELS as $key => $label) {
            $hex = self::normalizeHex($values[$key] ?? '') ?? self::defaults()[$key];
            // El valor publicado es el ajustado, para que las variables que
            // consume el sitio ya sean legibles.
            $hex = match ($key) {
                'color_text'       => $text,
                'color_text_muted' => $textMuted,
                'color_link'       => $link,
                default            => $hex,
            };
            $var = str_replace('color_', '', $key);
            $var = str_replace('_bg', '-bg', $var);
            $declarations .= '    --public-' . str_replace('_', '-', $var) . ': ' . $hex . ";\n";
        }
        foreach ($surfaceText as $key => $hex) {
            $var = str_replace(['color_', '_'], ['', '-'], $key);
            $declarations .= '    --public-' . $var . ': ' . $hex . ";\n";
        }

        $accent      = self::normalizeHex($values['color_accent']) ?? '#00d4aa';
        $border      = self::normalizeHex($values['color_border']) ?? '#30363d';
        $accentHover = self::shade($accent, self::luminance($accent) < 0.5 ? 14 : -14);
        $buttonHover = self::shade($buttonBg, self::luminance($buttonBg) < 0.5 ? 14 : -14);
        $primaryHover = self::shade($primaryBg, self::luminance($primaryBg) < 0.5 ? 14 : -14);
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
        // Los componentes de Bootstrap que se usen dentro de las páginas
        // heredan los tokens del tema en lugar de su propia paleta: así tablas,
        // formularios, alertas y tarjetas se ven coherentes con el resto.
        $css .= "    --bs-body-color: var(--public-text);\n";
        $css .= "    --bs-body-bg: var(--public-background);\n";
        $css .= "    --bs-secondary-color: var(--public-text-muted);\n";
        $css .= "    --bs-border-color: var(--public-border);\n";
        $css .= "    --bs-link-color: var(--public-link);\n";
        $css .= "    --bs-link-hover-color: var(--accent-hover);\n";
        $css .= "    background-color: var(--public-background);\n";
        $css .= "    color: var(--public-text);\n";
        $css .= "}\n";

        // ── Cada superficie con su texto ─────────────────────────────
        $css .= <<<CSS
body.public-site a { color: var(--public-link); }
body.public-site a:hover,
body.public-site a:focus-visible { color: var(--accent-hover); }

/* Cabecera */
body.public-site .navbar-main { background-color: var(--public-header-bg); border-bottom-color: var(--public-border); color: var(--public-header-text); }
body.public-site .navbar-main .text-muted,
body.public-site .navbar-main small { color: {$headerMuted}; }

/* Menú principal: el texto es configurable, no heredado */
body.public-site .navbar .nav-link,
body.public-site .navbar .nav-link .split-link,
body.public-site .navbar .nav-link-parent { color: var(--public-menu-text); }
body.public-site .navbar .nav-link:hover,
body.public-site .navbar .nav-link:focus-visible,
body.public-site .navbar .nav-link.active,
body.public-site .navbar .nav-link:hover .split-link,
body.public-site .navbar .nav-link-parent:hover .split-link { color: var(--public-menu-hover-text); }
body.public-site .navbar .nav-link:hover,
body.public-site .navbar .nav-link:focus-visible,
body.public-site .navbar .nav-link.active { background-color: var(--public-menu-hover); }
body.public-site .navbar .nav-link .menu-caret { color: inherit; }
body.public-site .navbar-collapse { background-color: var(--public-menu-bg); }
body.public-site .navbar-collapse .text-muted,
body.public-site .navbar-collapse small { color: {$menuMuted}; }

/* Desplegables y multinivel */
body.public-site .navbar .dropdown-menu,
body.public-site .navbar .dropdown-submenu > .dropdown-menu { background-color: var(--public-multinivel-bg); border-color: var(--public-border); color: var(--public-multinivel-text); }
body.public-site .navbar .dropdown-menu .dropdown-item,
body.public-site .navbar .dropdown-menu a,
body.public-site .navbar .dropdown-item-parent { color: var(--public-multinivel-text); }
body.public-site .navbar .dropdown-menu .dropdown-item:hover,
body.public-site .navbar .dropdown-menu a:hover,
body.public-site .navbar .dropdown-item-parent:hover { background-color: var(--public-menu-hover); color: var(--public-menu-hover-text); }

/* Pie y barra lateral */
body.public-site .footer { background-color: var(--public-footer-bg); border-top-color: var(--public-border); color: var(--public-footer-text); }
body.public-site .footer h5,
body.public-site .footer h6,
body.public-site .footer a { color: var(--public-footer-text); }
body.public-site .footer .text-muted,
body.public-site .footer small { color: {$footerMuted} !important; }
body.public-site .sidebar,
body.public-site .public-sidebar { background-color: var(--public-sidebar-bg); color: var(--public-sidebar-text); }
CSS;

        // ── Botones: el texto es configurable y nunca se hereda ─────
        // El fallo reportado («no se ve el texto del botón de ir al admin»)
        // venía de que el color del texto estaba fijado en el layout base
        // (#04120f) mientras el fondo lo ponía el tema: con un tema de botón
        // azul, quedaba texto casi negro sobre azul.
        $css .= "body.public-site .btn-accent,\n"
            . "body.public-site .btn-accent i,\n"
            . "body.public-site .btn-accent span { color: var(--public-button-text); }\n"
            . "body.public-site .btn-accent { background-color: var(--public-button); border-color: var(--public-button); }\n"
            . "body.public-site .btn-accent:hover,\n"
            . "body.public-site .btn-accent:focus-visible { background-color: {$buttonHover}; border-color: {$buttonHover}; color: " . self::contrastColor($buttonHover) . "; }\n"
            . "body.public-site .btn-primary,\n"
            . "body.public-site .btn-primary i,\n"
            . "body.public-site .btn-primary span { color: {$primaryText}; }\n"
            . "body.public-site .btn-primary { background-color: var(--public-primary); border-color: var(--public-primary); }\n"
            . "body.public-site .btn-primary:hover,\n"
            . "body.public-site .btn-primary:focus-visible { background-color: {$primaryHover}; border-color: {$primaryHover}; color: " . self::contrastColor($primaryHover) . "; }\n"
            . "body.public-site .btn-nav-ghost { color: var(--public-menu-text); border-color: var(--public-border); }\n"
            . "body.public-site .btn-nav-ghost:hover,\n"
            . "body.public-site .btn-nav-ghost:focus-visible { color: var(--public-menu-hover-text); border-color: var(--public-accent); }\n"
            . "body.public-site .btn-nav-icon { color: var(--public-menu-text); border-color: var(--public-border); }\n"
            . "body.public-site .user-chip { color: var(--public-header-text); }\n"
            . "body.public-site .user-chip .user-role { color: {$headerMuted}; }\n";

        $css .= <<<CSS
/* El atenuado usa el color secundario, pero sólo si es legible: si no, manda
   el texto atenuado ya ajustado. */
body.public-site .text-muted,
body.public-site .form-text,
body.public-site small.text-muted { color: var(--public-text-muted) !important; }
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

        // ── Texto sobre superficies ─────────────────────────────────
        // Vacío es un valor legítimo: significa «calcúlalo tú para que cumpla
        // contraste». Sólo se escribe si la clave llega en la petición, así un
        // guardado parcial no borra lo que el administrador ya eligió.
        foreach (self::SURFACE_TEXT_LABELS as $key => $meta) {
            if (!array_key_exists($key, $colors)) {
                continue;
            }
            $raw = trim((string) $colors[$key]);
            if ($raw === '') {
                $rows[$key] = ['value' => '', 'type' => 'string'];
                continue;
            }
            $hex = self::normalizeHex($raw);
            if ($hex === null) {
                $errors[] = sprintf('%s: «%s» no es un color HEX válido.', $meta['label'], mb_substr($raw, 0, 20));
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

        // Los textos sobre superficies vuelven a «automático»: el preset ya
        // trae fondos pensados para que el cálculo dé un contraste holgado.
        foreach (array_keys(self::SURFACE_TEXT_LABELS) as $key) {
            $rows[$key] = ['value' => '', 'type' => 'string'];
        }

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

    /**
     * Razón de contraste WCAG 2.1 entre dos colores (1:1 a 21:1).
     */
    public static function contrastRatio(string $foreground, string $background): float
    {
        $a = self::luminance($foreground);
        $b = self::luminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Color de texto legible sobre el fondo dado (claro u oscuro).
     *
     * Se usa para los botones: el fondo lo elige el administrador, el texto se
     * calcula. Así ningún tema deja un botón con texto invisible.
     */
    public static function contrastColor(string $background): string
    {
        // Blanco sobre fondos oscuros, casi negro sobre fondos claros. Se
        // comparan las dos opciones y se queda la que más contraste da.
        $dark  = '#06110e';
        $light = '#ffffff';

        return self::contrastRatio($dark, $background) >= self::contrastRatio($light, $background)
            ? $dark
            : $light;
    }

    /**
     * Ajusta un color de texto lo mínimo para alcanzar el contraste pedido
     * sobre el fondo indicado. Si ya cumple, lo devuelve igual.
     *
     * @param float $minimum Mínimo WCAG (4.5 = AA para texto normal).
     */
    public static function ensureContrast(string $foreground, string $background, float $minimum = 4.5): string
    {
        $foreground = self::normalizeHex($foreground) ?? '#000000';
        $background = self::normalizeHex($background) ?? '#ffffff';

        if (self::contrastRatio($foreground, $background) >= $minimum) {
            return $foreground;
        }

        // Se avanza hacia el extremo que MÁS contraste da sobre este fondo (no
        // siempre es el blanco: sobre un gris medio, el negro da más), en pasos,
        // y se para en cuanto se alcanza el mínimo: no se pierde la identidad
        // del color más de lo imprescindible.
        $white = self::contrastRatio('#ffffff', $background);
        $black = self::contrastRatio('#000000', $background);
        $target = $white >= $black ? '#ffffff' : '#000000';

        if (max($white, $black) < $minimum) {
            // Ningún extremo llega: se devuelve el mejor disponible.
            return $target;
        }

        for ($step = 1; $step <= 20; $step++) {
            $candidate = self::mix($foreground, $target, $step / 20);
            if (self::contrastRatio($candidate, $background) >= $minimum) {
                return $candidate;
            }
        }

        return $target;
    }

    /**
     * Mezcla lineal de dos colores: `$ratio` 0 devuelve `$a`, 1 devuelve `$b`.
     */
    public static function mix(string $a, string $b, float $ratio): string
    {
        $ratio = max(0.0, min(1.0, $ratio));
        [$ar, $ag, $ab] = self::toRgb($a);
        [$br, $bg, $bb] = self::toRgb($b);

        return sprintf(
            '#%02x%02x%02x',
            (int) round($ar + ($br - $ar) * $ratio),
            (int) round($ag + ($bg - $ag) * $ratio),
            (int) round($ab + ($bb - $ab) * $ratio)
        );
    }
}
