/**
 * grapesjs-shared.js — piezas comunes de los editores visuales.
 *
 * Lo cargan views/pages/builder.php (páginas) y views/templates/edit.php
 * (plantillas) a través de views/partials/editor-assets-body.php. Aquí vive lo
 * que debe comportarse igual en los dos: qué plugins se piden, qué estilos y
 * scripts tiene el lienzo, y cómo se cargan los ficheros de bloques.
 *
 * Sin dependencias: script clásico que expone `window.EditorKit`.
 *
 * @package Assets\Js
 */
window.EditorKit = (function () {
    'use strict';

    // Hojas y scripts del IFRAME del lienzo (no de la página del editor).
    var CANVAS_STYLES = [
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
        'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css'
    ];

    var CANVAS_SCRIPTS = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
    ];

    // Plugins solicitados. Se filtran contra el registro real antes de inicializar
    // (ver filterPlugins): un nombre inexistente hacía que grapesjs.init() lanzara
    // «Plugin not found» y el editor entero se quedara en blanco.
    var PLUGIN_NAMES = [
        'gjs-blocks-basic',
        'grapesjs-plugin-forms',
        'grapesjs-tabs',
        'grapesjs-custom-code',
        'grapesjs-touch',
        'grapesjs-plugin-export',
        'grapesjs-blocks-bootstrap5'
    ];

    // Opciones por plugin. Las claves son los nombres reales de plugin.
    var PLUGINS_OPTS = {
        'gjs-blocks-basic': {
            flexGrid: true,
            blocks: ['column1', 'column2', 'column3', 'column3-7', 'text', 'link', 'image', 'video', 'map']
        },
        'grapesjs-plugin-forms': {
            blocks: ['form', 'input', 'textarea', 'select', 'button', 'label', 'checkbox', 'radio']
        },
        'grapesjs-tabs': {
            tabsBlock: { category: 'Extra' }
        },
        'grapesjs-custom-code': {
            blockLabel: 'Código Personalizado',
            blockCategory: 'Avanzado',
            label: 'Código',
            placeholder: 'Pega tu código HTML/CSS/JS aquí'
        },
        'grapesjs-plugin-export': {
            command: 'export-template',
            root: '#gjs',
            label: 'Exportar',
            modalTitle: 'Exportar HTML'
        },
        'grapesjs-blocks-bootstrap5': {
            gridDevices: true,
            blockCategories: {
                layout: 'Bootstrap 5 · Layout',
                components: 'Bootstrap 5 · Componentes',
                typography: 'Bootstrap 5 · Tipografía',
                basic: 'Bootstrap 5 · Básicos',
                forms: 'Bootstrap 5 · Formularios'
            }
        }
    };

    /**
     * Deja pasar solo los plugins registrados en el navegador.
     *
     * @param {string[]} names Nombres solicitados
     * @returns {{enabled: string[], skipped: string[]}}
     */
    function filterPlugins(names) {
        var registry = null;
        try {
            if (window.grapesjs && grapesjs.plugins && typeof grapesjs.plugins.getAll === 'function') {
                registry = grapesjs.plugins.getAll();
            }
        } catch (err) {
            console.warn('[EditorKit] No se pudo leer el registro de plugins:', err);
        }

        // Sin registro accesible se confía en la lista tal cual.
        if (!registry) {
            return { enabled: names.slice(), skipped: [] };
        }

        var enabled = [];
        var skipped = [];
        names.forEach(function (name) {
            if (Object.prototype.hasOwnProperty.call(registry, name)) {
                enabled.push(name);
            } else {
                skipped.push(name);
            }
        });
        return { enabled: enabled, skipped: skipped };
    }

    /**
     * Inyecta los ficheros de bloques en orden, uno detrás de otro.
     *
     * En serie y no en paralelo: el orden decide quién gana cuando dos ficheros
     * registran el mismo id de bloque. Un fichero que falle no detiene al resto.
     *
     * @param {string[]} urls     URLs de los ficheros, en orden
     * @param {object}   editor   Instancia de GrapesJS ya creada (los ficheros
     *                            registran con editor.BlockManager)
     * @param {function} [onDone] Se llama al terminar (con el número de ficheros)
     * @returns {void}
     */
    function loadBlockScripts(urls, editor, onDone) {
        if (!editor || !urls || !urls.length) {
            if (typeof onDone === 'function') onDone(0);
            return;
        }

        var loadNext = function (index) {
            if (index >= urls.length) {
                console.log('✅ Bloques cargados: ' + urls.length + ' fichero(s)');
                // La biblioteca visual se engancha aquí: necesita todos los
                // bloques registrados para agrupar, retirar duplicados y pintar
                // las miniaturas una sola vez.
                if (window.BlocksLibrary && typeof window.BlocksLibrary.enhance === 'function') {
                    try {
                        window.BlocksLibrary.enhance(editor);
                    } catch (err) {
                        console.warn('[EditorKit] La biblioteca de bloques no se pudo aplicar:', err);
                    }
                }
                if (typeof onDone === 'function') onDone(urls.length);
                return;
            }
            var script = document.createElement('script');
            script.src = urls[index];
            script.onload = function () { loadNext(index + 1); };
            script.onerror = function () {
                console.error('✗ No se pudo cargar el fichero de bloques: ' + urls[index]);
                loadNext(index + 1);
            };
            document.body.appendChild(script);
        };

        loadNext(0);
    }

    return {
        CANVAS_STYLES: CANVAS_STYLES,
        CANVAS_SCRIPTS: CANVAS_SCRIPTS,
        PLUGIN_NAMES: PLUGIN_NAMES,
        PLUGINS_OPTS: PLUGINS_OPTS,
        filterPlugins: filterPlugins,
        loadBlockScripts: loadBlockScripts
    };
})();
