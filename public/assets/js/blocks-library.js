/**
 * blocks-library.js — convierte el BlockManager de GrapesJS en una biblioteca
 * visual de componentes.
 *
 * No sustituye el BlockManager: lo **normaliza y lo decora**. Los bloques siguen
 * siendo los de `public/assets/js/blocks/*.js` y el arrastre lo sigue gestionando
 * GrapesJS, así que insertar, editar y guardar funcionan igual que antes.
 *
 * Qué hace, en orden:
 *   1. Reagrupa los ~160 bloques heredados en 11 categorías con icono y orden
 *      (usa `BlockManager` + `block.set('category', …)`, no toca el DOM a mano).
 *   2. Retira del gestor los bloques duplicados o redundantes (lista HIDE).
 *   3. Añade a cada tarjeta una **miniatura real**: el HTML del bloque
 *      renderizado en un iframe escalado y sin scripts.
 *   4. Inyecta buscador, filtros (Todo / Recientes / Favoritos) y contador.
 *   5. Recuerda favoritos y últimos usados en `localStorage`.
 *
 * Sin dependencias y sin tocar el resto del editor. Expone `window.BlocksLibrary`.
 *
 * @package Assets\Js
 * @since   2026-10-30
 */
window.BlocksLibrary = (function () {
    'use strict';

    // ═══════════════════════════════════════════════════════════════
    //  CATEGORÍAS CANÓNICAS
    //  `order` decide el orden en el panel; `icon` es una clase de
    //  Bootstrap Icons (ya cargada en el editor).
    // ═══════════════════════════════════════════════════════════════
    var CATEGORIES = {
        'px-quick':    { label: 'Plantillas rápidas', icon: 'bi-magic',            order: 10 },
        'px-layout':   { label: 'Estructura y layout', icon: 'bi-grid-3x3-gap',    order: 20 },
        'px-nav':      { label: 'Navegación',          icon: 'bi-signpost-split',  order: 30 },
        'px-hero':     { label: 'Encabezados y hero',  icon: 'bi-easel2',          order: 40 },
        'px-content':  { label: 'Contenido',           icon: 'bi-text-paragraph',  order: 50 },
        'px-actions':  { label: 'Botones y acciones',  icon: 'bi-hand-index-thumb', order: 60 },
        'px-cards':    { label: 'Tarjetas',            icon: 'bi-collection',      order: 70 },
        'px-forms':    { label: 'Formularios',         icon: 'bi-input-cursor-text', order: 80 },
        'px-bs':       { label: 'Componentes Bootstrap', icon: 'bi-bootstrap',     order: 90 },
        'px-data':     { label: 'Tablas y datos',      icon: 'bi-table',           order: 100 },
        'px-footer':   { label: 'Pie de página',       icon: 'bi-layout-text-window-reverse', order: 110 },
        'px-other':    { label: 'Otros',               icon: 'bi-three-dots',      order: 200 }
    };

    /**
     * Resuelve la categoría canónica de un bloque.
     *
     * Prioridad: id explícito → palabras del id de categoría → palabras del
     * nombre del bloque. Así los bloques heredados caen en su sitio sin
     * reescribir los 160 registros.
     */
    function resolveCategory(id, label, catId, catLabel) {
        // El nombre manda para los hero: los bloques heredados los declaran como
        // «Layout» o «Templates», pero un bloque llamado Hero es un hero.
        var nameProbe = (id + ' ' + label).toLowerCase();
        if (/hero|jumbotron/.test(nameProbe)) {
            return 'px-hero';
        }

        var explicit = {
            // Identidad: los bloques nuevos ya declaran una categoría canónica y
            // deben quedarse donde dicen (si no, «sección de…» los enviaría a
            // Estructura por la palabra «sección»).
            'px-quick': 'px-quick',
            'px-layout': 'px-layout',
            'px-nav': 'px-nav',
            'px-hero': 'px-hero',
            'px-content': 'px-content',
            'px-actions': 'px-actions',
            'px-cards': 'px-cards',
            'px-forms': 'px-forms',
            'px-bs': 'px-bs',
            'px-data': 'px-data',
            'px-footer': 'px-footer',
            'px-other': 'px-other',

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
        if (/layout|grid|estructura|column|section|seccion/.test(probe)) return 'px-layout';
        if (/navbar|nav|menu|breadcrumb|pagination|tabs/.test(probe)) return 'px-nav';
        if (/hero|header|jumbotron|banner/.test(probe)) return 'px-hero';
        if (/footer/.test(probe)) return 'px-footer';
        if (/table|tabla|data|dashboard|stats|pricing-table/.test(probe)) return 'px-data';
        if (/form|input|select|checkbox|radio|switch|search/.test(probe)) return 'px-forms';
        if (/card|tarjeta|team|equipo|pricing|precio|testimon|feature/.test(probe)) return 'px-cards';
        if (/btn|button|boton|action|accion|cta/.test(probe)) return 'px-actions';
        if (/alert|badge|accordion|collapse|modal|dropdown|progress|spinner|tooltip|toast|offcanvas|carousel/.test(probe)) return 'px-bs';
        if (/media|map|embed|text|texto|heading|titulo|paragraph|quote|list|image|img|video|gallery|icon|divider|content/.test(probe)) return 'px-content';

        return 'px-other';
    }

    /**
     * Bloques que se retiran del gestor por duplicar a otro mejor.
     *
     * Son definiciones de biblioteca: quitarlas no afecta a ninguna página ya
     * guardada (el contenido vive en el proyecto, no en el bloque).
     */
    var HIDE = [
        // Duplicados literales entre blocks-bootstrap.js y blocks-bs.js
        'bs-block-advanced-bs-pricing-section',
        // Variantes que repiten el mismo concepto con menos calidad
        'bootstrap-btn-lg', 'bootstrap-btn-sm', 'bootstrap-btn-group',
        'bootstrap-img-thumbnail',
        'btn-social-twitter', 'btn-social-facebook',
        'divider', 'spacer',
        'navbar-dark', 'navbar-bs',
        'second-blocks-features-3col'
    ];

    var FAV_KEY = 'px.blocks.favorites';
    var RECENT_KEY = 'px.blocks.recent';
    var RECENT_MAX = 12;

    var state = {
        editor: null,
        host: null,
        filter: 'all',
        query: '',
        favorites: [],
        recent: [],
        observer: null
    };

    // ───────────────────────────────────────────────────────────────
    //  Persistencia ligera
    // ───────────────────────────────────────────────────────────────
    function readList(key) {
        try {
            var raw = window.localStorage.getItem(key);
            var list = raw ? JSON.parse(raw) : [];
            return Array.isArray(list) ? list : [];
        } catch (err) {
            return [];
        }
    }

    function writeList(key, list) {
        try {
            window.localStorage.setItem(key, JSON.stringify(list));
        } catch (err) {
            /* modo privado o cuota llena: favoritos solo en memoria */
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  Utilidades de etiqueta
    // ───────────────────────────────────────────────────────────────
    function plainLabel(html) {
        var div = document.createElement('div');
        div.innerHTML = String(html || '');
        return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function blockLabel(block) {
        return plainLabel(block.get('label') || block.get('name') || block.id);
    }

    function blockDescription(block) {
        var description = block.get('description') || '';
        if (description) return plainLabel(description);
        // Los plugins no siempre declaran descripción: se usa la categoría.
        var category = block.get('category');
        var label = category && category.get ? category.get('label') : '';
        return label ? 'Componente de ' + plainLabel(label).toLowerCase() : 'Arrastra al lienzo para insertarlo';
    }

    function blockKeywords(block) {
        var extra = block.get('pxKeywords') || '';
        return (blockLabel(block) + ' ' + blockDescription(block) + ' ' + extra + ' ' + block.id).toLowerCase();
    }

    // ───────────────────────────────────────────────────────────────
    //  Miniatura real en iframe
    // ───────────────────────────────────────────────────────────────
    var PREVIEW_HEAD = [
        '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">',
        '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">',
        '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">',
        '<style>',
        'html,body{margin:0;padding:0;background:#fff;color:#212529;',
        'font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:14px;line-height:1.5}',
        'body{padding:14px}',
        'img{max-width:100%;height:auto}',
        '.container,.container-fluid{max-width:100%}',
        // Los bloques suelen traer bordes discontinuos de guía: se suavizan para
        // que la miniatura parezca producto final y no un boceto.
        '[style*="dashed"]{border-color:#dee2e6 !important}',
        '</style></head><body>'
    ].join('');

    function previewDocument(html) {
        return PREVIEW_HEAD + html + '</body></html>';
    }

    /**
     * Convierte el contenido de un bloque en HTML de previsualización.
     * Devuelve null si no es representable (contenido no textual).
     */
    function previewHtml(block) {
        var content = block.get('content');

        if (typeof content === 'string') {
            return content;
        }

        if (content && typeof content === 'object' && typeof content.html === 'string') {
            return content.html;
        }

        return null;
    }

    function buildPreview(block) {
        var html = previewHtml(block);
        var box = document.createElement('div');
        box.className = 'px-block-preview';

        if (html === null) {
            var fallback = document.createElement('div');
            fallback.className = 'px-block-fallback';
            fallback.innerHTML = String(block.get('media') || '<i class="bi bi-box"></i>');
            box.appendChild(fallback);
            return box;
        }

        var frame = document.createElement('iframe');
        frame.setAttribute('sandbox', '');
        frame.setAttribute('tabindex', '-1');
        frame.setAttribute('aria-hidden', 'true');
        frame.setAttribute('loading', 'lazy');
        frame.setAttribute('scrolling', 'no');
        frame.srcdoc = previewDocument(html);
        box.appendChild(frame);
        return box;
    }

    // ───────────────────────────────────────────────────────────────
    //  Normalización de categorías y retirada de duplicados
    // ───────────────────────────────────────────────────────────────
    function normalizeCategories() {
        var manager = state.editor.BlockManager;

        HIDE.forEach(function (id) {
            try {
                if (manager.get(id)) manager.remove(id);
            } catch (err) {
                console.warn('[BlocksLibrary] No se pudo retirar el bloque ' + id, err);
            }
        });

        var all = manager.getAll();
        if (!all || typeof all.forEach !== 'function') {
            return;
        }

        all.forEach(function (block) {
            var raw = block.get('category');
            var catId = raw && raw.get ? (raw.get('id') || '') : String(raw || '');
            var catLabel = raw && raw.get ? (raw.get('label') || '') : '';
            var key = resolveCategory(block.id, blockLabel(block), catId, catLabel);
            var canonical = CATEGORIES[key] || CATEGORIES['px-other'];

            block.set('category', {
                id: key,
                label: canonical.label,
                icon: canonical.icon,
                order: canonical.order,
                open: key === 'px-quick' || key === 'px-layout'
            });

            // Un atributo propio permite encontrar el modelo desde el DOM sin
            // depender de la estructura interna de las vistas de GrapesJS.
            var attributes = Object.assign({}, block.get('attributes') || {});
            attributes['data-px-block'] = block.id;
            attributes.title = blockLabel(block) + ' — ' + blockDescription(block);
            block.set('attributes', attributes);
        });

        try {
            state.editor.Blocks.render();
        } catch (err) {
            console.warn('[BlocksLibrary] No se pudo repintar el gestor de bloques', err);
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  Barra de búsqueda, filtros y estado vacío
    // ───────────────────────────────────────────────────────────────
    function buildToolbar() {
        var toolbar = document.createElement('div');
        toolbar.className = 'px-blocks-toolbar';

        var search = document.createElement('div');
        search.className = 'px-blocks-search';
        search.innerHTML = '<i class="bi bi-search"></i>';

        var input = document.createElement('input');
        input.type = 'search';
        input.placeholder = 'Buscar componentes…';
        input.setAttribute('aria-label', 'Buscar bloques por nombre');
        search.appendChild(input);

        var filters = document.createElement('div');
        filters.className = 'px-blocks-filters';

        var chips = [
            { id: 'all', label: 'Todo', icon: 'bi-grid' },
            { id: 'recent', label: 'Recientes', icon: 'bi-clock-history' },
            { id: 'fav', label: 'Favoritos', icon: 'bi-star' }
        ];

        chips.forEach(function (chip) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'px-chip' + (chip.id === 'all' ? ' is-active' : '');
            button.dataset.filter = chip.id;
            button.innerHTML = '<i class="bi ' + chip.icon + '"></i>' + chip.label;
            button.addEventListener('click', function () {
                state.filter = chip.id;
                filters.querySelectorAll('.px-chip').forEach(function (other) {
                    other.classList.toggle('is-active', other === button);
                });
                applyFilters();
            });
            filters.appendChild(button);
        });

        var counter = document.createElement('span');
        counter.className = 'px-blocks-count';

        filters.appendChild(counter);
        toolbar.appendChild(search);
        toolbar.appendChild(filters);

        input.addEventListener('input', function () {
            state.query = input.value.trim().toLowerCase();
            applyFilters();
        });

        var empty = document.createElement('div');
        empty.className = 'px-blocks-empty';
        empty.innerHTML = '<i class="bi bi-search"></i>Ningún bloque coincide.<br>Prueba con otra palabra o cambia el filtro.';

        toolbar.pxCounter = counter;
        toolbar.pxEmpty = empty;

        return toolbar;
    }

    function ensureToolbar() {
        var existing = state.host.querySelector('.px-blocks-toolbar');
        if (existing) return existing;

        var toolbar = buildToolbar();
        state.host.insertBefore(toolbar, state.host.firstChild);
        state.host.appendChild(toolbar.pxEmpty);
        return toolbar;
    }

    // ───────────────────────────────────────────────────────────────
    //  Decoración de tarjetas
    // ───────────────────────────────────────────────────────────────
    function decorateCard(element, block) {
        if (element.dataset.pxDecorated === '1') return;
        element.dataset.pxDecorated = '1';
        element.dataset.pxId = block.id;

        var label = blockLabel(block);
        var description = blockDescription(block);

        // Badge para plantillas completas (se insertan como sección entera).
        if (block.get('pxBadge')) {
            var badge = document.createElement('span');
            badge.className = 'px-block-badge';
            badge.textContent = String(block.get('pxBadge'));
            element.appendChild(badge);
        }

        // Se reemplaza el cuerpo por la miniatura y un pie propio. El elemento
        // sigue siendo el `.gjs-block` original: el arrastre no cambia.
        var legacyLabel = element.querySelector('.gjs-block-label');
        var legacyMedia = element.querySelector('.gjs-block-media-icon, .gjs-block-media, .fa, .bi');
        element.innerHTML = '';

        var preview = buildPreview(block);
        element.appendChild(preview);

        var meta = document.createElement('div');
        meta.className = 'px-block-meta';

        var text = document.createElement('span');
        text.className = 'px-block-label';
        text.textContent = label;
        meta.appendChild(text);

        var fav = document.createElement('button');
        fav.type = 'button';
        fav.className = 'px-block-fav';
        fav.title = 'Marcar como favorito';
        fav.setAttribute('aria-label', 'Marcar ' + label + ' como favorito');
        fav.innerHTML = '<i class="bi bi-star"></i>';
        fav.addEventListener('click', function (event) {
            // Sin esto, el clic iniciaría un arrastre del bloque.
            event.preventDefault();
            event.stopPropagation();
            toggleFavorite(block.id);
        });
        meta.appendChild(fav);

        element.appendChild(meta);

        element.setAttribute('title', label + ' — ' + description);
        element.classList.toggle('px-is-fav', state.favorites.indexOf(block.id) !== -1);
        void legacyLabel;
        void legacyMedia;
    }

    function decorateAll() {
        ensureToolbar();

        state.host.querySelectorAll('.gjs-block-category').forEach(function (categoryNode) {
            var title = categoryNode.querySelector('.gjs-title');
            var cards = categoryNode.querySelectorAll('.gjs-block');
            if (!title) return;

            // Cabecera con icono, nombre limpio y contador.
            if (title.dataset.pxDecorated !== '1') {
                var categoryId = null;
                var firstBlock = cards[0] && cards[0].dataset.pxId;
                if (firstBlock) {
                    var model = state.editor.BlockManager.get(firstBlock);
                    var category = model && model.get('category');
                    categoryId = category && category.get ? category.get('id') : null;
                }
                var meta = categoryId ? CATEGORIES[categoryId] : null;
                var label = plainLabel(title.textContent) || (meta ? meta.label : 'Bloques');

                title.innerHTML = '';
                var icon = document.createElement('span');
                icon.className = 'px-cat-icon';
                icon.innerHTML = '<i class="bi ' + ((meta && meta.icon) || 'bi-grid') + '"></i>';
                var name = document.createElement('span');
                name.className = 'px-cat-name';
                name.textContent = label;
                var count = document.createElement('span');
                count.className = 'px-cat-count';
                count.textContent = String(cards.length);
                var chevron = document.createElement('span');
                chevron.className = 'px-cat-chevron';
                chevron.innerHTML = '<i class="bi bi-chevron-down"></i>';

                title.appendChild(icon);
                title.appendChild(name);
                title.appendChild(count);
                title.appendChild(chevron);
                title.dataset.pxDecorated = '1';
            }

            cards.forEach(function (card) {
                var block = state.editor.BlockManager.get(card.dataset.pxBlock || '');
                if (block) decorateCard(card, block);
            });
        });

        applyFilters();
    }

    // ───────────────────────────────────────────────────────────────
    //  Filtrado
    // ───────────────────────────────────────────────────────────────
    function applyFilters() {
        if (!state.host) return;

        var toolbar = state.host.querySelector('.px-blocks-toolbar');
        var visible = 0;

        state.host.querySelectorAll('.gjs-block-category').forEach(function (categoryNode) {
            var shownInCategory = 0;

            categoryNode.querySelectorAll('.gjs-block').forEach(function (card) {
                var id = card.dataset.pxId || '';
                var block = state.editor.BlockManager.get(id);
                var matchesQuery = state.query === '' || (block && blockKeywords(block).indexOf(state.query) !== -1);
                var matchesFilter = true;

                if (state.filter === 'fav') {
                    matchesFilter = state.favorites.indexOf(id) !== -1;
                } else if (state.filter === 'recent') {
                    matchesFilter = state.recent.indexOf(id) !== -1;
                }

                var show = matchesQuery && matchesFilter;
                card.classList.toggle('px-hidden', !show);
                if (show) shownInCategory++;
            });

            // Una categoría sin resultados no debe ocupar espacio.
            categoryNode.classList.toggle('px-hidden', shownInCategory === 0);
            var count = categoryNode.querySelector('.px-cat-count');
            if (count) count.textContent = String(shownInCategory);
            visible += shownInCategory;
        });

        if (toolbar && toolbar.pxCounter) {
            toolbar.pxCounter.textContent = visible + (visible === 1 ? ' bloque' : ' bloques');
        }
        if (toolbar && toolbar.pxEmpty) {
            toolbar.pxEmpty.classList.toggle('is-visible', visible === 0);
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  Favoritos y recientes
    // ───────────────────────────────────────────────────────────────
    function toggleFavorite(id) {
        var index = state.favorites.indexOf(id);
        if (index === -1) {
            state.favorites.push(id);
        } else {
            state.favorites.splice(index, 1);
        }
        writeList(FAV_KEY, state.favorites);

        state.host.querySelectorAll('.gjs-block').forEach(function (card) {
            if (card.dataset.pxId === id) {
                card.classList.toggle('px-is-fav', state.favorites.indexOf(id) !== -1);
            }
        });

        applyFilters();
    }

    function rememberRecent(id) {
        state.recent = state.recent.filter(function (value) { return value !== id; });
        state.recent.unshift(id);
        state.recent = state.recent.slice(0, RECENT_MAX);
        writeList(RECENT_KEY, state.recent);
    }

    // ───────────────────────────────────────────────────────────────
    //  Arranque
    // ───────────────────────────────────────────────────────────────
    function enhance(editor, hostSelector) {
        if (!editor || !editor.BlockManager) return;

        state.editor = editor;
        state.favorites = readList(FAV_KEY);
        state.recent = readList(RECENT_KEY);

        state.host = document.querySelector(hostSelector || '#gjsBlocks');
        if (!state.host) return;

        normalizeCategories();
        decorateAll();

        // GrapesJS vuelve a pintar el panel al añadir o quitar bloques: se
        // redecorá lo nuevo sin repetir trabajo (cada tarjeta lleva su marca).
        var pending = null;
        var observer = new MutationObserver(function () {
            clearTimeout(pending);
            pending = setTimeout(decorateAll, 60);
        });
        observer.observe(state.host, { childList: true, subtree: true });
        state.observer = observer;

        editor.on('block:drag:stop', function (block) {
            if (block && block.id) rememberRecent(block.id);
        });

        document.querySelectorAll('.gjs-block').forEach(function (card) {
            card.addEventListener('dragstart', function () { card.classList.add('px-is-dragging'); });
            card.addEventListener('dragend', function () { card.classList.remove('px-is-dragging'); });
        });

        // Estado de arrastre global: los bloques se crean después de este punto.
        document.addEventListener('dragstart', function (event) {
            var card = event.target && event.target.closest ? event.target.closest('.gjs-block') : null;
            if (card) card.classList.add('px-is-dragging');
        }, true);
        document.addEventListener('dragend', function () {
            document.querySelectorAll('.gjs-block.px-is-dragging').forEach(function (card) {
                card.classList.remove('px-is-dragging');
            });
        }, true);
    }

    return {
        enhance: enhance,
        categories: CATEGORIES,
        resolveCategory: resolveCategory,
        refresh: applyFilters
    };
})();
