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

// BlockManager FIEL a GrapesJS 0.21.10:
//   - el módulo NO expone `.on` (extiende ItemManagerModule → Module)
//   - los eventos se emiten en el EDITOR (`em.trigger('block:add', block)`)
// La versión anterior de esta prueba le daba `.on` al gestor y por eso daba por
// buena una llamada que en el navegador lanza TypeError.
function makeEditor() {
    const listeners = {};
    return {
        on(event, cb) { (listeners[event] = listeners[event] || []).push(cb); },
        trigger(event, arg) { (listeners[event] || []).forEach((cb) => cb(arg)); }
    };
}

function makeManager(editor) {
    const blocks = new Map();
    return {
        getAll() { return Array.from(blocks.values()); },
        get(id) { return blocks.get(id) || null; },
        remove(id) { blocks.delete(id); },
        add(id, def) {
            const block = makeBlock(id, def);
            blocks.set(id, block);
            // Como en GrapesJS: el módulo avisa al editor, no a sí mismo.
            editor.trigger('block:add', block);
            return block;
        }
    };
}

const editor = makeEditor();
const manager = makeManager(editor);
editor.BlockManager = manager;

check('el BlockManager simulado NO tiene .on (como el real)', typeof manager.on === 'undefined');

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

console.log('\n== Categorías compartidas (el desplegable del panel) ==');
// El panel agrupa por categoría. Si cada bloque trae su propio objeto, GrapesJS
// crea una categoría por bloque: aparecen grupos repetidos y abrir o cerrar deja
// de comportarse. Todos los bloques de una misma clave deben compartir instancia.
const catNav1 = manager.get('bs-navbar').get('category');
manager.add('nav-extra', { label: 'Navbar extra', category: 'bootstrap-navigation' });
const catNav2 = manager.get('nav-extra').get('category');
check('dos bloques de Navegación comparten la MISMA instancia', catNav1 === catNav2,
    'son objetos distintos: el panel los trata como categorías separadas');

const catHero = manager.get('hero').get('category');
manager.add('hero-dos', { label: 'Hero dos', category: { id: 'Layout', label: 'Layout' } });
check('dos bloques de Encabezados comparten instancia', catHero === manager.get('hero-dos').get('category'));

// Abrir/cerrar a mano no puede revertirse al registrar otro bloque.
catNav1.open = false;
manager.add('nav-tercero', { label: 'Navbar tres', category: 'bootstrap-navigation' });
check('cerrar una categoría se mantiene al añadir otro bloque',
    manager.get('nav-tercero').get('category').open === false,
    'el bloque nuevo reabrió la categoría: el cierre manual se revertiría');

// Estado inicial: solo «Plantillas rápidas» y «Estructura y layout» abren; el
// resto arranca cerrado para que el panel no sea un muro de tarjetas.
check('Plantillas rápidas abre por defecto', manager.get('px-faq').get('category').open === true);
check('Estructura y layout abre por defecto', manager.get('plugin-columns').get('category').open === true);
check('Navegación arranca cerrada', catNav1.open === false);
check('Encabezados arranca cerrada', manager.get('hero').get('category').open === false);
check('Tarjetas arranca cerrada', manager.get('bootstrap-card-basic').get('category').open === false);

// Ninguna categoría se duplica por identidad.
const instancias = new Set();
manager.getAll().forEach((b) => {
    const c = b.get('category');
    if (c && typeof c === 'object') instancias.add(c);
});
const claves = new Set(Array.from(instancias).map((c) => c.id));
check('hay una sola instancia por clave de categoría', instancias.size === claves.size,
    instancias.size + ' instancias para ' + claves.size + ' claves');
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
