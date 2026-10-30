/**
 * Interactividad de la pestaña «Configuración → personalización de la web pública».
 *
 * Se carga ÚNICAMENTE desde `views/settings/index.php`, así que nada de lo que
 * hace aquí llega al resto del panel:
 *
 *  - Pestañas (sustituye al script en línea que tenía la vista).
 *  - Sincronía selector de color ↔ campo HEX.
 *  - Indicador de contraste WCAG sobre el fondo principal.
 *  - Presets de tema: rellenan los 16 colores.
 *  - Muestra en vivo que refleja los colores sin guardar.
 *  - Validación previa del CSS personalizado y botón de restauración.
 *  - Confirmación de acciones destructivas mediante `data-confirm`.
 *
 * ES2022, sin dependencias, sin `var`, sin `eval`.
 *
 * @since 2026-10-26
 */
(() => {
    'use strict';

    // ──────────────────────────────────────────────────────────────
    //  Pestañas
    // ──────────────────────────────────────────────────────────────
    const tabButtons = Array.from(document.querySelectorAll('.nav-tabs .nav-link[data-tab]'));
    const panes = Array.from(document.querySelectorAll('.tab-content[data-tab]'));

    const activateTab = (id, updateHash = true) => {
        if (!id || !panes.some((pane) => pane.dataset.tab === id)) {
            id = 'general';
        }

        tabButtons.forEach((button) => {
            const isActive = button.dataset.tab === id;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panes.forEach((pane) => {
            pane.classList.toggle('active', pane.dataset.tab === id);
        });

        // Cada formulario recuerda en qué pestaña estaba el usuario, para que
        // el redirect posterior lo devuelva aquí y no a la primera.
        document.querySelectorAll('[data-return-tab]').forEach((input) => {
            input.value = id;
        });

        if (updateHash) {
            history.replaceState(null, '', `#tab-${id}`);
        }
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.tab));
    });

    // Pestaña inicial: la del hash, o la que decidió el controlador por ?tab=.
    const initial = (location.hash.match(/^#tab-([a-z]+)$/) || [])[1]
        || document.querySelector('.nav-tabs .nav-link.active')?.dataset.tab;
    activateTab(initial, false);

    // Atajos dentro de un panel: botones que llevan a otra pestaña
    // (por ejemplo, del aviso de «Identidad visual» a «General»).
    document.querySelectorAll('[data-goto-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            activateTab(button.dataset.gotoTab);
            document.querySelector(`.nav-tabs .nav-link[data-tab="${button.dataset.gotoTab}"]`)?.focus();
        });
    });

    // ──────────────────────────────────────────────────────────────
    //  Colores: selector ↔ HEX ↔ muestra en vivo
    // ──────────────────────────────────────────────────────────────
    const NORMALIZE = (value) => {
        let hex = String(value || '').trim().toLowerCase();
        if (hex === '') return null;
        if (hex[0] !== '#') hex = `#${hex}`;
        if (!/^#([0-9a-f]{3}|[0-9a-f]{6})$/.test(hex)) return null;
        if (hex.length === 4) {
            hex = `#${hex[1]}${hex[1]}${hex[2]}${hex[2]}${hex[3]}${hex[3]}`;
        }
        return hex;
    };

    const toRgb = (hex) => {
        const value = NORMALIZE(hex) || '#000000';
        return [
            parseInt(value.slice(1, 3), 16),
            parseInt(value.slice(3, 5), 16),
            parseInt(value.slice(5, 7), 16),
        ];
    };

    const luminance = (hex) => {
        const [r, g, b] = toRgb(hex).map((channel) => {
            const s = channel / 255;
            return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    const contrastRatio = (a, b) => {
        const la = luminance(a);
        const lb = luminance(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    };

    const pickers = Array.from(document.querySelectorAll('input[type="color"][data-hex-target]'));
    const hexInputs = Array.from(document.querySelectorAll('.ppf-hex[data-picker-target]'));

    const backgroundHex = () => {
        const background = document.querySelector('#picker_color_background');
        return background ? background.value : '#ffffff';
    };

    const refreshContrast = () => {
        const background = backgroundHex();
        document.querySelectorAll('[data-contrast]').forEach((badge) => {
            const key = badge.dataset.contrast;
            const picker = document.querySelector(`#picker_${key}`);
            if (!picker) return;

            // El propio fondo contra sí mismo siempre da 1.0:1 y no informa de
            // nada. Para esa fila se mide el texto, que es lo que se lee encima.
            const reference = key === 'color_background'
                ? (document.querySelector('#picker_color_text')?.value || '#000000')
                : background;
            const ratio = contrastRatio(picker.value, reference);
            const label = ratio >= 7 ? 'AAA' : (ratio >= 4.5 ? 'AA' : 'Bajo');
            badge.textContent = `${ratio.toFixed(1)}:1 ${label}`;
            badge.classList.toggle('is-pass', ratio >= 4.5);
            badge.classList.toggle('is-fail', ratio < 4.5);
            badge.title = key === 'color_background'
                ? `Contraste del texto principal sobre este fondo: ${ratio.toFixed(2)}:1`
                : `Contraste ${ratio.toFixed(2)}:1 sobre el fondo principal`;
        });
    };

    const refreshMock = () => {
        const mock = document.querySelector('#ppfMock');
        if (!mock) return;

        const read = (key, fallback) => {
            const picker = document.querySelector(`#picker_${key}`);
            return picker ? picker.value : fallback;
        };

        mock.style.setProperty('--ppf-bg-header', read('color_header_bg', '#0d1117'));
        mock.style.setProperty('--ppf-bg-section', read('color_section_bg', '#161b22'));
        mock.style.setProperty('--ppf-bg-footer', read('color_footer_bg', '#161b22'));
        mock.style.setProperty('--ppf-text', read('color_text', '#e6edf3'));
        mock.style.setProperty('--ppf-text-muted', read('color_text_muted', '#8b949e'));
        mock.style.setProperty('--ppf-button', read('color_button', '#00d4aa'));
    };

    pickers.forEach((picker) => {
        picker.addEventListener('input', () => {
            const target = document.getElementById(picker.dataset.hexTarget);
            if (target) target.value = picker.value;
            refreshContrast();
            refreshMock();
        });
    });

    hexInputs.forEach((input) => {
        const sync = () => {
            const normalized = NORMALIZE(input.value);
            if (!normalized) return;
            input.value = normalized;
            const picker = document.getElementById(input.dataset.pickerTarget);
            if (picker) picker.value = normalized;
            refreshContrast();
            refreshMock();
        };
        input.addEventListener('input', () => {
            if (NORMALIZE(input.value)) sync();
        });
        input.addEventListener('blur', sync);
    });

    refreshContrast();
    refreshMock();

    // ──────────────────────────────────────────────────────────────
    //  Presets de tema → rellenan los 16 colores
    // ──────────────────────────────────────────────────────────────
    const resetThemeInput = document.getElementById('ppfResetTheme');

    document.querySelectorAll('.ppf-theme-input').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;

            // «Restablecer» debe devolver la paleta del tema marcado ahora, no la
            // que estaba guardada al cargar la página.
            if (resetThemeInput) {
                resetThemeInput.value = radio.dataset.preset ? radio.value : 'oscuro';
            }

            const raw = radio.dataset.preset;
            if (!raw) return; // «Personalizado» no toca nada

            let colors = {};
            try {
                colors = JSON.parse(raw);
            } catch {
                return;
            }

            Object.entries(colors).forEach(([key, hex]) => {
                const picker = document.querySelector(`#picker_${key}`);
                const input = document.getElementById(`hex_${key}`);
                const normalized = NORMALIZE(hex);
                if (picker && normalized) picker.value = normalized;
                if (input && normalized) input.value = normalized;
            });

            refreshContrast();
            refreshMock();
        });
    });

    // ──────────────────────────────────────────────────────────────
    //  CSS personalizado: aviso previo y restauración
    // ──────────────────────────────────────────────────────────────
    const cssArea = document.getElementById('custom_css');
    const cssStatus = document.getElementById('ppfCssStatus');

    const CSS_RULES = [
        ['<', 'El carácter «<» podría cerrar la etiqueta style.'],
        ['@import', '@import enlaza hojas externas y no está permitido.'],
        ['expression(', 'expression() no está permitido.'],
        ['javascript:', 'Las URL javascript: no están permitidas.'],
        ['vbscript:', 'Las URL vbscript: no están permitidas.'],
        ['behavior:', 'behavior: no está permitido.'],
        ['-moz-binding', '-moz-binding no está permitido.'],
    ];

    const validateCss = (value) => {
        const errors = [];
        const lowered = String(value || '').toLowerCase();

        CSS_RULES.forEach(([needle, message]) => {
            if (lowered.includes(needle)) errors.push(message);
        });
        if (new Blob([value || '']).size > 102400) {
            errors.push('Supera el máximo de 100 KB.');
        }
        return errors;
    };

    const reportCss = () => {
        if (!cssArea || !cssStatus) return;

        if (cssArea.value.trim() === '') {
            cssStatus.textContent = '';
            cssStatus.className = 'ppf-css-status';
            return;
        }

        const errors = validateCss(cssArea.value);
        if (errors.length === 0) {
            cssStatus.textContent = 'Sintaxis aceptable: se guardará tal cual.';
            cssStatus.className = 'ppf-css-status is-ok';
        } else {
            cssStatus.textContent = errors.join(' ');
            cssStatus.className = 'ppf-css-status is-error';
        }
    };

    cssArea?.addEventListener('input', reportCss);
    reportCss();

    document.getElementById('ppfCssRestore')?.addEventListener('click', () => {
        if (!cssArea || !cssStatus) return;
        if (!window.confirm('¿Vaciar el CSS personalizado? El tema seguirá aplicándose.')) return;
        cssArea.value = '';
        reportCss();
        cssStatus.textContent = 'CSS vaciado. Pulsa «Guardar personalización» para hacerlo efectivo.';
    });

    // ──────────────────────────────────────────────────────────────
    //  Vista previa
    // ──────────────────────────────────────────────────────────────
    document.getElementById('ppfReloadPreview')?.addEventListener('click', () => {
        const frame = document.getElementById('ppfPreviewFrame');
        if (!frame) return;
        // Se recarga añadiendo la marca de tiempo para saltar la caché.
        const base = frame.dataset.baseUrl || frame.src.split('?')[0];
        frame.dataset.baseUrl = base;
        frame.src = `${base}?_ppf=${Date.now()}`;
    });

    // ──────────────────────────────────────────────────────────────
    //  Confirmación de acciones destructivas
    // ──────────────────────────────────────────────────────────────
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    //  Aviso al salir con cambios sin guardar
    // ──────────────────────────────────────────────────────────────
    const publicForm = document.getElementById('publicSettingsForm');
    if (publicForm) {
        // `return_tab` cambia al cambiar de pestaña y no es un dato del usuario:
        // si entrara en la instantánea, navegar entre pestañas dispararía un
        // aviso de «cambios sin guardar» que no existe.
        const snapshot = () => JSON.stringify(
            Array.from(new FormData(publicForm).entries())
                .filter(([name]) => name !== 'return_tab')
        );

        let saved = snapshot();
        let submitting = false;

        publicForm.addEventListener('submit', () => { submitting = true; });
        // Volver a la página con el formulario ya guardado no debe avisar.
        window.addEventListener('pageshow', () => { saved = snapshot(); });

        window.addEventListener('beforeunload', (event) => {
            if (submitting || snapshot() === saved) return;
            event.preventDefault();
            event.returnValue = '';
        });
    }
})();
