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
        'bootstrap-btn-lg', 'bootstrap-btn-sm', 'bootstrap-btn-group',
        'bootstrap-img-thumbnail',
        'btn-social-twitter', 'btn-social-facebook',
        'divider', 'spacer',
        'navbar-dark', 'navbar-bs'
    ];

    var installed = false;

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

    function applyCanonical(block, silent) {
        if (!block || typeof block.get !== 'function') return;

        var category = readCategory(block.get('category'));
        var key = resolveCategory(block.id, plainLabel(block.get('label')), category.id, category.label);
        var meta = CATEGORIES[key] || CATEGORIES['px-other'];

        block.set('category', {
            id: key,
            label: meta.label,
            order: meta.order,
            open: key === 'px-quick' || key === 'px-layout'
        }, { silent: silent !== false });

        var attributes = Object.assign({}, block.get('attributes') || {});
        var label = plainLabel(block.get('label')) || block.id;
        var description = plainLabel(block.get('description') || '');
        attributes.title = description ? label + ' — ' + description : label;
        block.set('attributes', attributes, { silent: true });
    }

    /**
     * Engancha la normalización al registro de bloques.
     *
     * Se llama antes de inyectar los ficheros de bloques: cada `add` dispara el
     * evento y la categoría queda puesta antes del primer pintado.
     */
    function install(editor) {
        if (!editor || !editor.BlockManager) return;

        var manager = editor.BlockManager;

        if (!installed) {
            installed = true;

            manager.on('add', function (block) {
                try {
                    applyCanonical(block, true);
                } catch (err) {
                    console.warn('[BlocksLibrary] No se pudo normalizar ' + (block && block.id), err);
                }
            });
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
