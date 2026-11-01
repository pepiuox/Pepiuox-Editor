/**
 * blocks-library.js — organización de la biblioteca de bloques.
 *
 * Alcance deliberadamente pequeño: **solo toca el modelo** (categorías y
 * atributos) a través de la API del BlockManager. No escribe en el DOM, no
 * reemplaza tarjetas, no observa mutaciones y no fuerza repintados: la primera
 * versión hacía eso y dejaba el panel en blanco.
 *
 * Así se usa:
 *   `install(editor)`  → se llama ANTES de cargar los ficheros de bloques.
 *                        Engancha el evento `add` del gestor y normaliza la
 *                        categoría de cada bloque en el mismo instante en que se
 *                        registra, antes de que GrapesJS lo pinte. Cero
 *                        repintados extra.
 *   `enhance(editor)`  → alias de `install` para los bloques que ya existían
 *                        (los que registran los plugins durante el init).
 *
 * Si algo aquí falla, se registra en consola y se sigue: el editor y los bloques
 * funcionan igual que sin este fichero.
 *
 * @package Assets\Js
 * @since   2026-10-30
 */
window.BlocksLibrary = (function () {
    'use strict';

    /**
     * Categorías canónicas. `order` decide el orden en el panel; `icon` no se
     * pinta (el DOM es de GrapesJS), se guarda para uso futuro y para la
     * auditoría de `tests/blocks-library/classify.js`.
     */
    var CATEGORIES = {
        'px-quick':  { label: 'Plantillas rápidas',     order: 10 },
        'px-layout': { label: 'Estructura y layout',    order: 20 },
        'px-nav':    { label: 'Navegación',             order: 30 },
        'px-hero':   { label: 'Encabezados y hero',     order: 40 },
        'px-content': { label: 'Contenido',             order: 50 },
        'px-actions': { label: 'Botones y acciones',    order: 60 },
        'px-cards':  { label: 'Tarjetas',               order: 70 },
        'px-forms':  { label: 'Formularios',            order: 80 },
        'px-bs':     { label: 'Componentes Bootstrap',  order: 90 },
        'px-data':   { label: 'Tablas y datos',         order: 100 },
        'px-footer': { label: 'Pie de página',          order: 110 },
        'px-other':  { label: 'Otros',                  order: 200 }
    };

    /**
     * Bloques que no se ofrecen por duplicar a otro mejor.
     *
     * Son definiciones de biblioteca: retirarlas no rompe ninguna página ya
     * guardada, porque el contenido vive en el proyecto y no en el bloque.
     */
    var HIDE = [
        // Fichero duplicado: blocks-bs.js repite lo que ya ofrece
        // blocks-bootstrap.js (reescrito y coherente con Bootstrap 5.3).
        'bs-navbar', 'bs-hero', 'bs-footer', 'bs-cards-row', 'bs-card', 'bs-alert',
        'bs-accordion', 'bs-tabs', 'bs-form', 'bs-grid-3', 'bs-progress', 'bs-badge',
        'bs-breadcrumb', 'bs-pagination', 'bs-pricing', 'bs-cta', 'bs-login', 'bs-search-bar',
        'bs-buttons', 'bs-modal',
        // second-blocks.js: mismo concepto con menos calidad
        'navbar-bs', 'hero-section', 'features-3col', 'footer-custom',
        'pricing-card', 'contact-form-card',
        // Redundantes sueltos
        'bootstrap-btn-lg', 'bootstrap-btn-sm', 'bootstrap-btn-group',
        'bootstrap-img-thumbnail', 'btn-social-twitter', 'btn-social-facebook',
        'divider', 'spacer', 'navbar-dark'
    ];

    /**
     * Icono de Bootstrap por bloque: se busca por palabra clave en id y nombre.
     * Se usa solo si el bloque no declara icono o si declara uno de Font Awesome
     * (el editor carga las dos familias y mezclarlas se ve inconsistente).
     */
    var ICONS = [
        [/hero|jumbotron/, 'bi-easel2'],
        [/navbar|nav\b|menu/, 'bi-menu-button-wide'],
        [/breadcrumb/, 'bi-chevron-right'],
        [/pagination/, 'bi-three-dots'],
        [/tabs|pestan/, 'bi-folder2-open'],
        [/accordion/, 'bi-chevron-expand'],
        [/dropdown/, 'bi-caret-down-square'],
        [/modal/, 'bi-window-stack'],
        [/offcanvas/, 'bi-layout-sidebar-inset'],
        [/toast|tooltip/, 'bi-chat-square-text'],
        [/carousel|slider/, 'bi-images'],
        [/gallery|galer/, 'bi-grid-3x3-gap'],
        [/video/, 'bi-play-btn'],
        [/map/, 'bi-geo-alt'],
        [/img|image|foto|figura|photo/, 'bi-image'],
        [/table|tabla/, 'bi-table'],
        [/dashboard|stats|chart|estadist/, 'bi-bar-chart'],
        [/form|contact|newsletter|login|registro|suscrip/, 'bi-input-cursor-text'],
        [/input|select|radio|check|switch|range/, 'bi-ui-checks'],
        [/pricing|precio|price/, 'bi-tags'],
        [/testimon|opinion|review/, 'bi-chat-quote'],
        [/team|equipo|perfil|profile/, 'bi-people'],
        [/feature|caracterist|beneficio/, 'bi-grid-1x2'],
        [/footer|pie/, 'bi-layout-text-window-reverse'],
        [/cta|banner|call/, 'bi-megaphone'],
        [/btn|button|boton/, 'bi-hand-index'],
        [/alert|aviso/, 'bi-exclamation-triangle'],
        [/badge|etiqueta|tag/, 'bi-tag'],
        [/progress/, 'bi-bar-chart-steps'],
        [/list|lista/, 'bi-list-ul'],
        [/heading|titulo|title|h1|h2|h3/, 'bi-type-h1'],
        [/text|texto|paragraph|parrafo|quote|cita/, 'bi-paragraph'],
        [/icon/, 'bi-star'],
        [/container|row|grid|column|col|layout|section|seccion/, 'bi-columns-gap'],
        [/divider|separator|hr/, 'bi-dash-lg'],
        [/faq|pregunta/, 'bi-question-circle'],
        [/card|tarjeta/, 'bi-card-text'],
        [/sidebar/, 'bi-layout-sidebar'],
        [/landing|page|pagina|plantilla|template/, 'bi-window-desktop']
    ];

    var CATEGORY_ICON = {
        'px-quick': 'bi-magic', 'px-layout': 'bi-columns-gap', 'px-nav': 'bi-menu-button-wide',
        'px-hero': 'bi-easel2', 'px-content': 'bi-paragraph', 'px-actions': 'bi-hand-index',
        'px-cards': 'bi-card-text', 'px-forms': 'bi-input-cursor-text', 'px-bs': 'bi-bootstrap',
        'px-data': 'bi-table', 'px-footer': 'bi-layout-text-window-reverse', 'px-other': 'bi-box'
    };

    /** Icono adecuado para un bloque, buscando en su id y su nombre. */
    function iconFor(id, label, categoryKey) {
        var probe = (id + ' ' + label).toLowerCase();
        for (var i = 0; i < ICONS.length; i++) {
            if (ICONS[i][0].test(probe)) {
                return ICONS[i][1];
            }
        }
        return CATEGORY_ICON[categoryKey] || 'bi-box';
    }

    var installed = false;

    /**
     * Una sola instancia de categoría por clave, compartida por todos los bloques.
     *
     * ── Por qué no crear un objeto nuevo por bloque ────────────────────
     * Antes, cada bloque recibía `{ id, label, order, open }` recién construido.
     * GrapesJS trata cada objeto de categoría como una entrada propia del panel,
     * así que acababa con **una categoría por bloque** aunque compartieran id y
     * etiqueta: el desplegable abría y cerraba grupos distintos y parecía que no
     * funcionaba.
     *
     * Compartiendo la instancia hay una categoría real por clave, y `open` se
     * decide una vez: si se reescribiera en cada bloque, abrir o cerrar a mano se
     * revertiría con el siguiente bloque que se registrara.
     */
    var CATEGORY_OBJECTS = {};

    /**
     * Editor activo, para poder pedirle las categorías reales.
     * @type {Object|null}
     */
    var activeEditor = null;

    /**
     * Devuelve el MODELO de categoría, no un objeto plano.
     *
     * ── Por qué un modelo y no un objeto (esto era el fallo de fondo) ───
     * En GrapesJS, `Block.category` es un getter:
     *
     *     get category() { const cat = this.get('category');
     *                      return cat instanceof Category ? cat : undefined; }
     *
     * Es decir: con un **objeto plano** devuelve `undefined`, y entonces
     * `BlocksView` cae en `initCategory()`, que crea la categoría y **reemplaza**
     * el valor del bloque por el modelo. Al asignar nosotros un objeto plano en
     * cada alta, estábamos peleando contra la librería 294 veces: el bloque volvía
     * a quedarse sin categoría válida y la categoría se recreaba.
     *
     * Pidiendo el modelo real a `getCategories()` —la API documentada— el bloque
     * queda enlazado a la categoría de verdad y su estado `open` sobrevive.
     */
    function categoryModel(key) {
        var meta = CATEGORIES[key] || CATEGORIES['px-other'];
        var categorias = activeEditor && activeEditor.Blocks && typeof activeEditor.Blocks.getCategories === 'function'
            ? activeEditor.Blocks.getCategories()
            : null;

        if (categorias && typeof categorias.get === 'function') {
            var existente = categorias.get(key);
            if (existente) {
                return existente;
            }
            if (typeof categorias.add === 'function') {
                // `open` se fija al CREAR la categoría, no en cada bloque: si se
                // reenviara en cada alta, el cierre manual se desharía.
                return categorias.add({
                    id: key,
                    label: meta.label,
                    order: meta.order,
                    open: key === 'px-quick' || key === 'px-layout'
                });
            }
        }

        // Respaldo: sin API de categorías, se usa un objeto compartido por clave.
        // Es lo mejor que se puede hacer, aunque GrapesJS lo tratará como nueva.
        if (!CATEGORY_OBJECTS[key]) {
            CATEGORY_OBJECTS[key] = { id: key, label: meta.label, order: meta.order };
        }
        return CATEGORY_OBJECTS[key];
    }

    /**
     * Categoría canónica de un bloque.
     *
     * Prioridad: el nombre manda para los hero y los quick templates; después el
     * id de categoría original; y si no hay coincidencia, palabras del nombre.
     */
    function resolveCategory(id, label, catId, catLabel) {
        var name = (id + ' ' + label).toLowerCase();

        if (/hero|jumbotron/.test(name)) {
            return 'px-hero';
        }

        var explicit = {
            // Bloques nuevos: ya declaran su categoría canónica.
            'px-quick': 'px-quick', 'px-layout': 'px-layout', 'px-nav': 'px-nav',
            'px-hero': 'px-hero', 'px-content': 'px-content', 'px-actions': 'px-actions',
            'px-cards': 'px-cards', 'px-forms': 'px-forms', 'px-bs': 'px-bs',
            'px-data': 'px-data', 'px-footer': 'px-footer', 'px-other': 'px-other',

            // Categorías heredadas de los ficheros de bloques.
            'bootstrap-layouts': 'px-layout',
            'bootstrap-navigation': 'px-nav',
            'bootstrap-cards': 'px-cards',
            'bootstrap-forms': 'px-forms',
            'bootstrap-buttons': 'px-actions',
            'bootstrap-tables': 'px-data',
            'bootstrap-content': 'px-content',
            'bootstrap-media': 'px-content',
            'bootstrap-special': 'px-bs',
            'Advanced Data': 'px-data',
            'Advanced Layouts': 'px-layout',
            'Advanced Pages': 'px-quick',
            'Advanced Sections': 'px-quick',
            'Advanced Widgets': 'px-bs',
            'advanced-pages': 'px-quick',
            'advanced-sections': 'px-quick',
            'Plantillas': 'px-quick',
            'Basic': 'px-content',
            'UI Components': 'px-bs',
            'About': 'px-content',
            'Accordions': 'px-bs',
            'Actions': 'px-actions',
            'Articles': 'px-content',
            'Buttons': 'px-actions',
            'Cards': 'px-cards',
            'Features': 'px-cards',
            'Footers': 'px-footer',
            'Forms': 'px-forms',
            'Headers': 'px-hero',
            'Media': 'px-content',
            'media': 'px-content',
            'multimedia': 'px-content',
            'Pricing': 'px-cards',
            'Team': 'px-cards',
            'utils': 'px-layout',
            'advanced-extra': 'px-bs',
            'advanced-gallery': 'px-content',
            'advanced-maps': 'px-content',
            'advanced-tables': 'px-data',
            'advanced-testimonials': 'px-cards',
            'Extra': 'px-bs',
            'Avanzado': 'px-other'
        };

        if (catId && explicit[catId]) {
            return explicit[catId];
        }

        var probe = ((catId || '') + ' ' + (catLabel || '') + ' ' + id + ' ' + label).toLowerCase();

        if (/plantilla|template|landing|dashboard/.test(probe)) return 'px-quick';
        if (/layout|grid|estructura|column|section|seccion|flex/.test(probe)) return 'px-layout';
        if (/navbar|nav|menu|breadcrumb|pagination|tabs/.test(probe)) return 'px-nav';
        if (/footer/.test(probe)) return 'px-footer';
        if (/table|tabla|data|stats|pricing-table/.test(probe)) return 'px-data';
        if (/form|input|select|checkbox|radio|switch|search/.test(probe)) return 'px-forms';
        if (/card|tarjeta|team|equipo|pricing|precio|testimon|feature/.test(probe)) return 'px-cards';
        if (/btn|button|boton|action|accion|cta/.test(probe)) return 'px-actions';
        if (/alert|badge|accordion|collapse|modal|dropdown|progress|spinner|tooltip|toast|offcanvas|carousel/.test(probe)) return 'px-bs';
        if (/media|map|embed|text|texto|heading|titulo|paragraph|quote|list|image|img|video|gallery|icon|content/.test(probe)) return 'px-content';

        return 'px-other';
    }

    /** Texto plano de una etiqueta que puede traer HTML. */
    function plainLabel(html) {
        return String(html || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Aplica la categoría canónica a un bloque.
     *
     * `silent` evita que cada bloque repinte el panel; cuando el bloque se
     * registra por primera vez el panel aún no lo ha pintado, así que la
     * categoría nueva es la que se ve desde el principio.
     */
    /**
     * Lee el id y la etiqueta de la categoría de un bloque.
     *
     * GrapesJS puede dar un modelo de Backbone (`cat.get('id')`) o un objeto
     * plano (`cat.id`), según si la categoría ya fue normalizada por el gestor.
     * Leer solo `.get` mandaba los bloques con objeto plano a «Otros».
     */
    function readCategory(raw) {
        if (raw && typeof raw === 'object') {
            var isModel = typeof raw.get === 'function';
            return {
                id: String((isModel ? raw.get('id') : raw.id) || ''),
                label: String((isModel ? raw.get('label') : raw.label) || '')
            };
        }
        return { id: String(raw || ''), label: '' };
    }

    /**
     * Convierte los estilos de guía en línea en utilidades de Bootstrap.
     *
     * Los ficheros heredados (`uiblocks.js`, `advance.js`, `blocks-advanced.js`,
     * `blocks-bs-advanced.js`, `bs-block-advanced.js`) marcan las zonas con
     * `style="padding:20px; border:1px dashed #ddd"` y grises fijos. Eso no
     * respeta el tema ni la grilla. Se sustituye por sus clases equivalentes:
     *
     *   padding:20px            → p-4
     *   border:1px dashed #ddd  → border border-2 border-secondary-subtle rounded-3
     *   background:#f8f9fa      → bg-body-tertiary
     *
     * Solo actúa sobre esos patrones conocidos: cualquier otro estilo del bloque
     * se deja intacto para no cambiar el diseño de nadie.
     */
    function normalizeContent(block, silent) {
        var content = block.get('content');
        if (typeof content !== 'string' || content.indexOf('style=') === -1) return;

        var normalized = content
            // Relleno fijo de guía
            .replace(/style="[^"]*padding:\s*(?:1[6-9]|[2-9]\d)px[^"]*"/g, 'class="p-4"')
            // Borde discontinuo de guía (con o sin relleno en el mismo style)
            .replace(/style="[^"]*border:\s*1px\s+dashed[^"]*"/g, 'class="p-4 border border-2 border-secondary-subtle rounded-3"')
            // Grises de Bootstrap antiguos escritos a mano
            .replace(/background:\s*#f8f9fa/gi, 'background:var(--bs-tertiary-bg)')
            .replace(/background:\s*#e9ecef/gi, 'background:var(--bs-secondary-bg)')
            .replace(/color:\s*#6c757d/gi, 'color:var(--bs-secondary-color)')
            // Colores primarios fijos
            .replace(/background:\s*#0d6efd/gi, 'background:var(--bs-primary)')
            .replace(/color:\s*#0d6efd/gi, 'color:var(--bs-primary)');

        if (normalized !== content) {
            block.set('content', normalized, { silent: silent !== false });
        }
    }

    function applyCanonical(block, silent) {
        if (!block || typeof block.get !== 'function') return;

        var category = readCategory(block.get('category'));
        var key = resolveCategory(block.id, plainLabel(block.get('label')), category.id, category.label);

        // Etiqueta limpia: varios ficheros meten el icono dentro del `label`
        // (`<i class="fas fa-…"></i> Sección`). El icono va en `media`, no en el
        // texto: si no, se ve duplicado y descuadra la tarjeta.
        var rawLabel = String(block.get('label') || '');
        var label = plainLabel(rawLabel) || block.id;
        if (rawLabel !== label) {
            block.set('label', label, { silent: silent !== false });
        }

        // Icono propio: se respeta el que ya sea de Bootstrap Icons; el de Font
        // Awesome se sustituye para que toda la biblioteca use una sola familia.
        var media = String(block.get('media') || '');
        if (media === '' || /fa[srlb]?\s+fa-/.test(media)) {
            block.set('media', '<i class="bi ' + iconFor(block.id, label, key) + '"></i>', { silent: silent !== false });
        }

        normalizeContent(block, silent);

        // Modelo real de categoría, no un objeto: ver categoryModel(). Así el
        // bloque queda enlazado a la categoría persistente y su estado se conserva.
        block.set('category', categoryModel(key), { silent: silent !== false });

        var attributes = Object.assign({}, block.get('attributes') || {});
        var description = plainLabel(block.get('description') || '');
        attributes.title = description ? label + ' — ' + description : label;
        block.set('attributes', attributes, { silent: true });
    }

    /**
     * Engancha la normalización al registro de bloques.
     *
     * ── Dónde va el listener (esto estaba mal) ──────────────────────────
     * GrapesJS **no** expone `.on` en el BlockManager: `BlockManager` extiende
     * `ItemManagerModule` → `Module`, y ninguno de los tres define `.on`. Los
     * eventos se emiten en el **editor**: `em.trigger('block:add', block)`.
     *
     * La versión anterior hacía `manager.on('add', …)`, que lanza
     * `TypeError: manager.on is not a function`. Como `EditorKit` envuelve la
     * llamada en try/catch, el error se registraba como aviso y la normalización
     * no ocurría nunca: cada fichero conservaba su propio objeto de categoría y el
     * panel mostraba varias categorías con el mismo nombre.
     *
     * Se intenta el editor primero y se cae al gestor solo si alguna versión
     * futura lo expone. Si no hay forma de enganchar, se avisa: un fallo silencioso
     * aquí se confunde con «todo bien».
     */
    function install(editor) {
        if (!editor || !editor.BlockManager) {
            console.warn('[BlocksLibrary] Sin editor o sin BlockManager: no se puede normalizar.');
            return;
        }

        var manager = editor.BlockManager;
        activeEditor = editor;

        var normalize = function (block) {
            try {
                applyCanonical(block, true);
            } catch (err) {
                console.warn('[BlocksLibrary] No se pudo normalizar ' + (block && block.id), err);
            }
        };

        if (!installed) {
            installed = true;

            var hooked = false;

            // Camino correcto: el editor emite `block:add`.
            if (typeof editor.on === 'function') {
                editor.on('block:add', normalize);
                hooked = true;
            }

            // Respaldo: algunas versiones antiguas o wrappers exponen `.on`.
            if (!hooked && typeof manager.on === 'function') {
                manager.on('add', normalize);
                hooked = true;
            }

            if (!hooked) {
                console.warn('[BlocksLibrary] No se encontró dónde escuchar los bloques nuevos: ' +
                    'las categorías no se normalizarán al registrarse.');
            }
        }

        // Los bloques de los plugins ya están registrados en este punto: se
        // normalizan de una vez (pocos, y es el único repintado que provocan).
        try {
            manager.getAll().forEach(function (block) {
                applyCanonical(block, false);
            });
            HIDE.forEach(function (id) {
                if (manager.get(id)) manager.remove(id);
            });
        } catch (err) {
            console.warn('[BlocksLibrary] Normalización inicial omitida:', err);
        }
    }

    /** Alias: engancha y normaliza lo que ya exista. */
    function enhance(editor) {
        install(editor);
    }

    return {
        install: install,
        enhance: enhance,
        categories: CATEGORIES,
        resolveCategory: resolveCategory
    };
})();
