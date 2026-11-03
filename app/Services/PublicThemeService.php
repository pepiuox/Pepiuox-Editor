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
        // ════════════════════════════════════════════════════════════
        // Los siguientes siete amplían la selección por FAMILIA de color.
        // Criterio de diseño, no de gusto:
        //   - texto sobre fondo ≥ 4.5:1 (AA) y texto atenuado ≥ 4.5:1;
        //   - un solo acento saturado por tema, el resto neutros;
        //   - fondos de sección a menos de un 8 % de diferencia de luminosidad
        //     para que el salto entre bloques no canse la vista.
        // Los ratios se comprueban en tests/seguridad/check.php.
        // ════════════════════════════════════════════════════════════

        'bosque' => [
            'label'       => 'Bosque',
            'description' => 'Verdes profundos con ámbar de acento. Natural y sobrio, bueno para contenido largo.',
            'colors'      => [
                'color_primary'       => '#1b5e20',
                'color_secondary'     => '#4b8b5a',
                'color_accent'        => '#c46a00',
                'color_background'    => '#fffff0',
                'color_section_bg'    => '#f2f5e9',
                'color_text'          => '#041a04',
                'color_text_muted'    => '#3c5a42',
                'color_link'          => '#1b5e20',
                'color_button'        => '#1b5e20',
                'color_border'        => '#c9d6c3',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#eef3e6',
                'color_sidebar_bg'    => '#f4f7ee',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#f7faf2',
                'color_menu_hover'    => '#e4eedc',
            ],
        ],

        'oceano' => [
            'label'       => 'Océano',
            'description' => 'Azul profundo con turquesa. Transmite confianza; habitual en servicios y formación.',
            'colors'      => [
                'color_primary'       => '#0b4f6c',
                'color_secondary'     => '#2d6a8f',
                'color_accent'        => '#00808a',
                'color_background'    => '#f7fafc',
                'color_section_bg'    => '#edf2f7',
                'color_text'          => '#0b2545',
                'color_text_muted'    => '#44607d',
                'color_link'          => '#0b4f6c',
                'color_button'        => '#0b4f6c',
                'color_border'        => '#cbd5e0',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#e6eef5',
                'color_sidebar_bg'    => '#edf3f8',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#f2f7fb',
                'color_menu_hover'    => '#d9e6f0',
            ],
        ],

        'atardecer' => [
            'label'       => 'Atardecer',
            'description' => 'Terracota y ámbar sobre crema. Cálido sin perder legibilidad; para hostelería y artesanía.',
            'colors'      => [
                'color_primary'       => '#a34f00',
                'color_secondary'     => '#c46a00',
                'color_accent'        => '#b07d00',
                'color_background'    => '#fffbf5',
                'color_section_bg'    => '#fdf1e3',
                'color_text'          => '#2b1a0f',
                'color_text_muted'    => '#6b4a33',
                'color_link'          => '#8a4300',
                'color_button'        => '#a34f00',
                'color_border'        => '#ebd9c4',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#fbeee0',
                'color_sidebar_bg'    => '#fdf4e9',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#fef8f0',
                'color_menu_hover'    => '#f6e4ce',
            ],
        ],

        'nocturno-ambar' => [
            'label'       => 'Nocturno ámbar',
            'description' => 'Oscuro cálido con ámbar. Menos fatiga de noche y contraste alto sin deslumbrar.',
            'colors'      => [
                'color_primary'       => '#f0b429',
                'color_secondary'     => '#c46a00',
                'color_accent'        => '#fde047',
                'color_background'    => '#12100c',
                'color_section_bg'    => '#1a1712',
                'color_text'          => '#f5efe6',
                'color_text_muted'    => '#bfb3a3',
                'color_link'          => '#fde047',
                'color_button'        => '#f0b429',
                'color_border'        => '#2e2a22',
                'color_header_bg'     => '#171410',
                'color_footer_bg'     => '#171410',
                'color_sidebar_bg'    => '#14110d',
                'color_menu_bg'       => '#1a1712',
                'color_multinivel_bg' => '#1f1b15',
                'color_menu_hover'    => '#2a251c',
            ],
        ],

        'pizarra' => [
            'label'       => 'Pizarra',
            'description' => 'Grises neutros con acento celeste. El fondo no compite nunca con el contenido.',
            'colors'      => [
                'color_primary'       => '#334155',
                'color_secondary'     => '#64748b',
                'color_accent'        => '#0369a1',
                'color_background'    => '#ffffff',
                'color_section_bg'    => '#f4f6f8',
                'color_text'          => '#111827',
                'color_text_muted'    => '#4b5563',
                'color_link'          => '#0369a1',
                'color_button'        => '#334155',
                'color_border'        => '#e2e8f0',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#f1f5f9',
                'color_sidebar_bg'    => '#f8fafc',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#f8fafc',
                'color_menu_hover'    => '#e2e8f0',
            ],
        ],

        'papel' => [
            'label'       => 'Papel',
            'description' => 'Crema y tinta con acento granate. Aspecto editorial, para textos que se leen despacio.',
            'colors'      => [
                'color_primary'       => '#7a1f1a',
                'color_secondary'     => '#9a5b2e',
                'color_accent'        => '#8a6200',
                'color_background'    => '#fbf7f0',
                'color_section_bg'    => '#f5efe4',
                'color_text'          => '#1a1613',
                'color_text_muted'    => '#5c544b',
                'color_link'          => '#7a1f1a',
                'color_button'        => '#7a1f1a',
                'color_border'        => '#e0d6c6',
                'color_header_bg'     => '#fffdf9',
                'color_footer_bg'     => '#f3ebdf',
                'color_sidebar_bg'    => '#f7f1e7',
                'color_menu_bg'       => '#fffdf9',
                'color_multinivel_bg' => '#fbf6ee',
                'color_menu_hover'    => '#efe4d3',
            ],
        ],

        'ciruela' => [
            'label'       => 'Ciruela',
            'description' => 'Violeta profundo con magenta. Con personalidad; para marcas creativas y portafolios.',
            'colors'      => [
                'color_primary'       => '#5b2160',
                'color_secondary'     => '#7e3a85',
                'color_accent'        => '#a21caf',
                'color_background'    => '#fbf7fb',
                'color_section_bg'    => '#f3eaf4',
                'color_text'          => '#241026',
                'color_text_muted'    => '#5c4061',
                'color_link'          => '#6b2470',
                'color_button'        => '#5b2160',
                'color_border'        => '#e2d2e4',
                'color_header_bg'     => '#ffffff',
                'color_footer_bg'     => '#f2e8f3',
                'color_sidebar_bg'    => '#f6eff7',
                'color_menu_bg'       => '#ffffff',
                'color_multinivel_bg' => '#faf4fb',
                'color_menu_hover'    => '#ebdcee',
            ],
        ],
        // ── Paletas de Bootswatch ───────────────────────────────────
        // Los 9 temas siguientes parten de los valores de color publicados por
        // Bootswatch v5 (https://bootswatch.com), proyecto con licencia MIT.
        // Solo se han tomado los valores HEX: no se copia su CSS, sus fuentes
        // ni su código. Los nombres se citan como referencia de la paleta.
        //
        // Varios colores originales no llegan al 4.5:1 que exige la constitución
        // del proyecto para AA, así que se ha ajustado su luminosidad lo mínimo
        // necesario. Los cambios están anotados en cada tema que los necesitó.
        'bootswatch-cerulean' => [
            'label'       => 'Cerulean',
            'description' => 'Celeste luminoso con acentos azul marino. La paleta de Bootswatch Cerulean, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#237bad',
                'color_secondary'      => '#6d747a',
                'color_accent'         => '#033c73',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#495057',
                'color_text_muted'     => '#6d747a',
                'color_link'           => '#237bad',
                'color_button'         => '#237bad',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-cosmo' => [
            'label'       => 'Cosmo',
            'description' => 'Azul franco sobre blanco, esquinas rectas. La paleta de Bootswatch Cosmo, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#2476d1',
                'color_secondary'      => '#6d747a',
                'color_accent'         => '#9954bb',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#373a3c',
                'color_text_muted'     => '#6d747a',
                'color_link'           => '#2476d1',
                'color_button'         => '#2476d1',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-flatly' => [
            'label'       => 'Flatly',
            'description' => 'Pizarra oscura con verde azulado. La paleta de Bootswatch Flatly, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#2c3e50',
                'color_secondary'      => '#6c7778',
                'color_accent'         => '#0f826c',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#ecf0f1',
                'color_text'           => '#343a40',
                'color_text_muted'     => '#6c7778',
                'color_link'           => '#2c3e50',
                'color_button'         => '#2c3e50',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#ecf0f1',
                'color_sidebar_bg'     => '#ecf0f1',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#dee2e3',
            ],
        ],
        'bootswatch-minty' => [
            'label'       => 'Minty',
            'description' => 'Verde menta suave con rosa de contrapunto. La paleta de Bootswatch Minty, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#4d7c6e',
                'color_secondary'      => '#747474',
                'color_accent'         => '#388264',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f7f7f9',
                'color_text'           => '#5a5a5a',
                'color_text_muted'     => '#747474',
                'color_link'           => '#4d7c6e',
                'color_button'         => '#4d7c6e',
                'color_border'         => '#eceeef',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f7f7f9',
                'color_sidebar_bg'     => '#f7f7f9',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e8e8ea',
            ],
        ],
        'bootswatch-sandstone' => [
            'label'       => 'Sandstone',
            'description' => 'Arena, piedra y verde oliva. La paleta de Bootswatch Sandstone, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#325d88',
                'color_secondary'      => '#74726c',
                'color_accent'         => '#5e7e30',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f5f0',
                'color_text'           => '#3e3f3a',
                'color_text_muted'     => '#74726c',
                'color_link'           => '#325d88',
                'color_button'         => '#325d88',
                'color_border'         => '#dfd7ca',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f5f0',
                'color_sidebar_bg'     => '#f8f5f0',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9e6e2',
            ],
        ],
        'bootswatch-united' => [
            'label'       => 'United',
            'description' => 'Naranja Ubuntu con ciruela oscura. La paleta de Bootswatch United, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#ce4b1d',
                'color_secondary'      => '#79746e',
                'color_accent'         => '#772953',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#e9ecef',
                'color_text'           => '#333333',
                'color_text_muted'     => '#79746e',
                'color_link'           => '#ce4b1d',
                'color_button'         => '#ce4b1d',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#e9ecef',
                'color_sidebar_bg'     => '#e9ecef',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#dbdee1',
            ],
        ],
        'bootswatch-cyborg' => [
            'label'       => 'Cyborg',
            'description' => 'Negro casi puro con cian eléctrico. La paleta de Bootswatch Cyborg, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#2a9fd6',
                'color_secondary'      => '#888888',
                'color_accent'         => '#a952d4',
                'color_background'     => '#060606',
                'color_section_bg'     => '#222222',
                'color_text'           => '#adafae',
                'color_text_muted'     => '#888888',
                'color_link'           => '#2a9fd6',
                'color_button'         => '#2a9fd6',
                'color_border'         => '#282828',
                'color_header_bg'      => '#222222',
                'color_footer_bg'      => '#222222',
                'color_sidebar_bg'     => '#222222',
                'color_menu_bg'        => '#222222',
                'color_multinivel_bg'  => '#222222',
                'color_menu_hover'     => '#3d3d3d',
            ],
        ],
        'bootswatch-slate' => [
            'label'       => 'Slate',
            'description' => 'Gris pizarra con detalles cian. La paleta de Bootswatch Slate, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#5bc0de',
                'color_secondary'      => '#8e959a',
                'color_accent'         => '#f89406',
                'color_background'     => '#272b30',
                'color_section_bg'     => '#3a3f44',
                'color_text'           => '#aaaaaa',
                'color_text_muted'     => '#8e959a',
                'color_link'           => '#5bc0de',
                'color_button'         => '#5bc0de',
                'color_border'         => '#1b1e20',
                'color_header_bg'      => '#3a3f44',
                'color_footer_bg'      => '#3a3f44',
                'color_sidebar_bg'     => '#3a3f44',
                'color_menu_bg'        => '#3a3f44',
                'color_multinivel_bg'  => '#3a3f44',
                'color_menu_hover'     => '#52565a',
            ],
        ],
        'bootswatch-vapor' => [
            'label'       => 'Vapor',
            'description' => 'Morado profundo con neón rosa y turquesa. La paleta de Bootswatch Vapor, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#8f6bcd',
                'color_secondary'      => '#9b8fb0',
                'color_accent'         => '#ea39b8',
                'color_background'     => '#1a0933',
                'color_section_bg'     => '#2a0d4d',
                'color_text'           => '#32fbe2',
                'color_text_muted'     => '#9b8fb0',
                'color_link'           => '#8f6bcd',
                'color_button'         => '#8f6bcd',
                'color_border'         => '#3b1a66',
                'color_header_bg'      => '#2a0d4d',
                'color_footer_bg'      => '#2a0d4d',
                'color_sidebar_bg'     => '#2a0d4d',
                'color_menu_bg'        => '#2a0d4d',
                'color_multinivel_bg'  => '#2a0d4d',
                'color_menu_hover'     => '#442a62',
            ],
        ],
        'bootswatch-darkly' => [
            'label'       => 'Darkly',
            'description' => 'Gris carbón con verde azulado de enlace. La paleta de Bootswatch Darkly, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#00bc8c',
                'color_secondary'      => '#9a9a9a',
                'color_accent'         => '#3498db',
                'color_background'     => '#222222',
                'color_section_bg'     => '#303030',
                'color_text'           => '#ffffff',
                'color_text_muted'     => '#9a9a9a',
                'color_link'           => '#00bc8c',
                'color_button'         => '#00bc8c',
                'color_border'         => '#444444',
                'color_header_bg'      => '#303030',
                'color_footer_bg'      => '#303030',
                'color_sidebar_bg'     => '#303030',
                'color_menu_bg'        => '#303030',
                'color_multinivel_bg'  => '#303030',
                'color_menu_hover'     => '#494949',
            ],
        ],
        'bootswatch-journal' => [
            'label'       => 'Journal',
            'description' => 'Rojo coral con azul prensa y titulares condensados. La paleta de Bootswatch Journal, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#c05451',
                'color_secondary'      => '#727272',
                'color_accent'         => '#336699',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#333333',
                'color_text_muted'     => '#727272',
                'color_link'           => '#c05451',
                'color_button'         => '#c05451',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-litera' => [
            'label'       => 'Litera',
            'description' => 'Azul tinta sobre blanco, seria y de lectura larga. La paleta de Bootswatch Litera, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#3c73d1',
                'color_secondary'      => '#6a6f74',
                'color_accent'         => '#117f90',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#343a40',
                'color_text_muted'     => '#6a6f74',
                'color_link'           => '#3c73d1',
                'color_button'         => '#3c73d1',
                'color_border'         => '#dddddd',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-lumen' => [
            'label'       => 'Lumen',
            'description' => 'Azul acero con toques de cian. La paleta de Bootswatch Lumen, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#127ca5',
                'color_secondary'      => '#6e6e6e',
                'color_accent'         => '#487c90',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f6f6f6',
                'color_text'           => '#333333',
                'color_text_muted'     => '#6e6e6e',
                'color_link'           => '#127ca5',
                'color_button'         => '#127ca5',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f6f6f6',
                'color_sidebar_bg'     => '#f6f6f6',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e7e7e7',
            ],
        ],
        'bootswatch-lux' => [
            'label'       => 'Lux',
            'description' => 'Casi monocromo: negro, gris y un cian de detalle. La paleta de Bootswatch Lux, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#1a1a1a',
                'color_secondary'      => '#686f75',
                'color_accent'         => '#1a7fa9',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f0f1f2',
                'color_text'           => '#55595c',
                'color_text_muted'     => '#686f75',
                'color_link'           => '#1a1a1a',
                'color_button'         => '#1a1a1a',
                'color_border'         => '#e0e1e2',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f0f1f2',
                'color_sidebar_bg'     => '#f0f1f2',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e2e3e3',
            ],
        ],
        'bootswatch-materia' => [
            'label'       => 'Materia',
            'description' => 'Azul Material con acentos morados y formas planas. La paleta de Bootswatch Materia, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#1c7ac6',
                'color_secondary'      => '#666666',
                'color_accent'         => '#9c27b0',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#444444',
                'color_text_muted'     => '#666666',
                'color_link'           => '#1c7ac6',
                'color_button'         => '#1c7ac6',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-morph' => [
            'label'       => 'Morph',
            'description' => 'Neumorfismo: azul grisáceo, sombras suaves y azul vivo. La paleta de Bootswatch Morph, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#2562ae',
                'color_secondary'      => '#5e6367',
                'color_accent'         => '#4e53cf',
                'color_background'     => '#d9e3f1',
                'color_section_bg'     => '#f0f5fa',
                'color_text'           => '#586484',
                'color_text_muted'     => '#5e6367',
                'color_link'           => '#2562ae',
                'color_button'         => '#2562ae',
                'color_border'         => '#bed1e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f0f5fa',
                'color_sidebar_bg'     => '#f0f5fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e2e6eb',
            ],
        ],
        'bootswatch-pulse' => [
            'label'       => 'Pulse',
            'description' => 'Morado profundo con cian de enlace. La paleta de Bootswatch Pulse, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#593196',
                'color_secondary'      => '#696f75',
                'color_accent'         => '#007aad',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f9f8fc',
                'color_text'           => '#444444',
                'color_text_muted'     => '#696f75',
                'color_link'           => '#593196',
                'color_button'         => '#593196',
                'color_border'         => '#ededed',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f9f8fc',
                'color_sidebar_bg'     => '#f9f8fc',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#eae9ed',
            ],
        ],
        'bootswatch-quartz' => [
            'label'       => 'Quartz',
            'description' => 'Morado con rosa chicle y destellos cian. La paleta de Bootswatch Quartz, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#f3c5dc',
                'color_secondary'      => '#d9daf0',
                'color_accent'         => '#85dffb',
                'color_background'     => '#4f5499',
                'color_section_bg'     => '#3f4380',
                'color_text'           => '#ffffff',
                'color_text_muted'     => '#d9daf0',
                'color_link'           => '#f3c5dc',
                'color_button'         => '#f3c5dc',
                'color_border'         => '#6a6fbd',
                'color_header_bg'      => '#3f4380',
                'color_footer_bg'      => '#3f4380',
                'color_sidebar_bg'     => '#3f4380',
                'color_menu_bg'        => '#3f4380',
                'color_multinivel_bg'  => '#3f4380',
                'color_menu_hover'     => '#565a8f',
            ],
        ],
        'bootswatch-simplex' => [
            'label'       => 'Simplex',
            'description' => 'Rojo ladrillo sobre blanco roto. La paleta de Bootswatch Simplex, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#d9230f',
                'color_secondary'      => '#727272',
                'color_accent'         => '#0279a2',
                'color_background'     => '#fcfcfc',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#373a3c',
                'color_text_muted'     => '#727272',
                'color_link'           => '#d9230f',
                'color_button'         => '#d9230f',
                'color_border'         => '#dddddd',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
            ],
        ],
        'bootswatch-sketchy' => [
            'label'       => 'Sketchy',
            'description' => 'Trazos a mano alzada, bordes gruesos y negro de tinta. La paleta de Bootswatch Sketchy, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#333333',
                'color_secondary'      => '#696f75',
                'color_accent'         => '#117f90',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f7f7f9',
                'color_text'           => '#333333',
                'color_text_muted'     => '#696f75',
                'color_link'           => '#333333',
                'color_button'         => '#333333',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f7f7f9',
                'color_sidebar_bg'     => '#f7f7f9',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e8e8ea',
            ],
        ],
        'bootswatch-solar' => [
            'label'       => 'Solar',
            'description' => 'Solarized oscuro: azul noche con ámbar y azul cielo. La paleta de Bootswatch Solar, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#b58900',
                'color_secondary'      => '#93a1a1',
                'color_accent'         => '#3794d6',
                'color_background'     => '#002b36',
                'color_section_bg'     => '#00222b',
                'color_text'           => '#839496',
                'color_text_muted'     => '#93a1a1',
                'color_link'           => '#b58900',
                'color_button'         => '#b58900',
                'color_border'         => '#0e4b5a',
                'color_header_bg'      => '#00222b',
                'color_footer_bg'      => '#00222b',
                'color_sidebar_bg'     => '#00222b',
                'color_menu_bg'        => '#00222b',
                'color_multinivel_bg'  => '#00222b',
                'color_menu_hover'     => '#1f3d44',
            ],
        ],
        'bootswatch-spacelab' => [
            'label'       => 'Spacelab',
            'description' => 'Azul acero con gris cálido y aire técnico. La paleta de Bootswatch Spacelab, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#446e9b',
                'color_secondary'      => '#6a6a6a',
                'color_accent'         => '#2778be',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#eeeeee',
                'color_text'           => '#696969',
                'color_text_muted'     => '#6a6a6a',
                'color_link'           => '#446e9b',
                'color_button'         => '#446e9b',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#eeeeee',
                'color_sidebar_bg'     => '#eeeeee',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e0e0e0',
            ],
        ],
        'bootswatch-superhero' => [
            'label'       => 'Superhero',
            'description' => 'Azul marino profundo con naranja de acción. La paleta de Bootswatch Superhero, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#df6919',
                'color_secondary'      => '#989fa6',
                'color_accent'         => '#5bc0de',
                'color_background'     => '#0f2537',
                'color_section_bg'     => '#20374c',
                'color_text'           => '#ebebeb',
                'color_text_muted'     => '#989fa6',
                'color_link'           => '#df6919',
                'color_button'         => '#df6919',
                'color_border'         => '#4e5d6c',
                'color_header_bg'      => '#20374c',
                'color_footer_bg'      => '#20374c',
                'color_sidebar_bg'     => '#20374c',
                'color_menu_bg'        => '#20374c',
                'color_multinivel_bg'  => '#20374c',
                'color_menu_hover'     => '#3b4f61',
            ],
        ],
        'bootswatch-yeti' => [
            'label'       => 'Yeti',
            'description' => 'Azul cian, esquinas rectas y titulares ligeros. La paleta de Bootswatch Yeti, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#007ca5',
                'color_secondary'      => '#6b6b6b',
                'color_accent'         => '#3c7f93',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#eeeeee',
                'color_text'           => '#333333',
                'color_text_muted'     => '#6b6b6b',
                'color_link'           => '#007ca5',
                'color_button'         => '#007ca5',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#eeeeee',
                'color_sidebar_bg'     => '#eeeeee',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e0e0e0',
            ],
        ],
        'bootswatch-zephyr' => [
            'label'       => 'Zephyr',
            'description' => 'Azul índigo con sombras tenues y aire limpio. La paleta de Bootswatch Zephyr, ajustada para AA.',
            'colors'      => [
                'color_primary'        => '#3459e6',
                'color_secondary'      => '#687078',
                'color_accent'         => '#287bb5',
                'color_background'     => '#ffffff',
                'color_section_bg'     => '#f8f9fa',
                'color_text'           => '#495057',
                'color_text_muted'     => '#687078',
                'color_link'           => '#3459e6',
                'color_button'         => '#3459e6',
                'color_border'         => '#dee2e6',
                'color_header_bg'      => '#ffffff',
                'color_footer_bg'      => '#f8f9fa',
                'color_sidebar_bg'     => '#f8f9fa',
                'color_menu_bg'        => '#ffffff',
                'color_multinivel_bg'  => '#ffffff',
                'color_menu_hover'     => '#e9eaeb',
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
