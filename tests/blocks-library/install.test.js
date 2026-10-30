/**
 * Prueba del enganche de la biblioteca de bloques, sin navegador.
 *
 * Simula el BlockManager de GrapesJS (Backbone) con lo mínimo que usa
 * `BlocksLibrary.install()` y comprueba que:
 *   - no lanza ninguna excepción al instalar,
 *   - cada bloque recibe una categoría canónica,
 *   - los duplicados de la lista HIDE se retiran,
 *   - un fallo en un bloque no detiene al resto.
 *
 * Uso: node tests/blocks-library/install.test.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..', '..');
const sandbox = {
    window: {},
    document: { addEventListener() {}, querySelector() { return null; }, querySelectorAll() { return []; } },
    console
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(root, 'public', 'assets', 'js', 'blocks-library.js'), 'utf8'), sandbox);
const lib = sandbox.window.BlocksLibrary;

let failed = 0;
function check(name, condition, detail) {
    if (condition) {
        console.log('  PASA  ' + name);
    } else {
        failed++;
        console.log('  FALLA ' + name + (detail ? ' → ' + detail : ''));
    }
}

// ── BlockManager simulado ────────────────────────────────────────
function makeBlock(id, def) {
    const attrs = Object.assign({ id }, def);
    const block = {
        id,
        get(key) {
            if (key === 'category' && attrs.breakOnCategory) {
                throw new Error('categoría ilegible');
            }
            return attrs[key];
        },
        set(key, value, opts) {
            attrs[key] = value;
            return this;
        },
        attrs
    };
    return block;
}

function makeManager() {
    const blocks = new Map();
    const listeners = {};
    return {
        on(event, cb) { (listeners[event] = listeners[event] || []).push(cb); },
        getAll() { return Array.from(blocks.values()); },
        get(id) { return blocks.get(id) || null; },
        remove(id) { blocks.delete(id); },
        add(id, def) {
            const block = makeBlock(id, def);
            blocks.set(id, block);
            (listeners.add || []).forEach((cb) => cb(block));
            return block;
        }
    };
}

const manager = makeManager();
const editor = { BlockManager: manager };

// Bloques que ya existirían por los plugins antes de cargar los ficheros.
manager.add('plugin-text', { label: 'Text', category: 'Basic' });
manager.add('plugin-columns', { label: 'Columns', category: { id: 'layout', label: 'Layout' } });

console.log('== Instalación ==');
let threw = null;
try {
    lib.install(editor);
} catch (err) {
    threw = err;
}
check('install() no lanza', threw === null, threw && threw.message);

console.log('\n== Add posterior (como los ficheros de bloques) ==');
manager.add('bs-navbar', { label: 'Navbar', category: 'bootstrap-navigation' });
manager.add('hero', { label: 'Hero', category: { id: 'Layout', label: 'Layout' } });
manager.add('px-faq', { label: 'Sección de preguntas frecuentes', category: { id: 'px-quick', label: 'Plantillas rápidas' } });
manager.add('bootstrap-card-basic', { label: 'Card', category: 'bootstrap-cards' });

check('navbar → Navegación', manager.get('bs-navbar').get('category').id === 'px-nav', JSON.stringify(manager.get('bs-navbar').get('category')));
check('hero → Encabezados', manager.get('hero').get('category').id === 'px-hero', JSON.stringify(manager.get('hero').get('category')));
check('px-faq conserva Plantillas rápidas', manager.get('px-faq').get('category').id === 'px-quick', JSON.stringify(manager.get('px-faq').get('category')));
check('card → Tarjetas', manager.get('bootstrap-card-basic').get('category').id === 'px-cards', JSON.stringify(manager.get('bootstrap-card-basic').get('category')));
check('plugin "Basic" → Contenido', manager.get('plugin-text').get('category').id === 'px-content', JSON.stringify(manager.get('plugin-text').get('category')));
check('la categoría lleva label y order', (() => {
    const cat = manager.get('bs-navbar').get('category');
    return cat.label === 'Navegación' && typeof cat.order === 'number';
})(), JSON.stringify(manager.get('bs-navbar').get('category')));

console.log('\n== Atributos y duplicados ==');
check('se añade title para el tooltip', typeof manager.get('bs-navbar').get('attributes').title === 'string' && manager.get('bs-navbar').get('attributes').title.length > 0);

manager.add('divider', { label: 'Divider', category: 'utils' });
lib.install(editor);
check('los duplicados de HIDE se retiran', manager.get('divider') === null);

console.log('\n== Un bloque roto no detiene al resto ==');
manager.add('boom', { label: 'Roto', category: 'x', breakOnCategory: true });
const before = manager.get('px-faq').get('category').id;
manager.add('px-stats', { label: 'Stats', category: { id: 'px-quick' } });
check('el bloque siguiente sí se normaliza', manager.get('px-stats').get('category').id === 'px-quick', JSON.stringify(manager.get('px-stats').get('category')));
check('el anterior quedó intacto', manager.get('px-faq').get('category').id === before);

console.log('\n─────────────────────────────');
console.log(failed === 0 ? 'Resultado: todas las comprobaciones pasan' : 'Resultado: ' + failed + ' fallan');
process.exit(failed === 0 ? 0 : 1);
