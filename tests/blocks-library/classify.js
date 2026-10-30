/**
 * Comprobación de la clasificación de bloques (Node, sin navegador).
 *
 * Lee los ficheros reales de `public/assets/js/blocks/*.js`, extrae cada bloque
 * registrado y aplica `BlocksLibrary.resolveCategory()` para ver en qué
 * categoría cae. Sirve para detectar bloques huérfanos en «Otros» y categorías
 * vacías sin tener que abrir el editor.
 *
 * Uso: node tests/blocks-library/classify.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..', '..');
const blocksDir = path.join(root, 'public', 'assets', 'js', 'blocks');

// Se carga la biblioteca con un `window` mínimo para usar su propia lógica.
const sandbox = { window: {}, document: { addEventListener() {}, querySelector() { return null; }, querySelectorAll() { return []; } }, console, localStorage: null };
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(root, 'public', 'assets', 'js', 'blocks-library.js'), 'utf8'), sandbox);
const lib = sandbox.window.BlocksLibrary;

if (!lib) {
    console.error('No se pudo cargar BlocksLibrary.');
    process.exit(1);
}

const files = fs.readdirSync(blocksDir).filter((f) => f.endsWith('.js')).sort();
const blocks = [];

// pro-templates.js declara la categoría con constantes (QUICK, HERO, NAV, FOOTER).
const CONSTANTS = { QUICK: 'px-quick', HERO: 'px-hero', NAV: 'px-nav', FOOTER: 'px-footer' };

files.forEach((file) => {
    const source = fs.readFileSync(path.join(blocksDir, file), 'utf8');
    // Captura: BlockManager.add('id', { ... category ... label ... })
    const re = /BlockManager\.add\(\s*'([^']+)'\s*,\s*\{([\s\S]*?)\n\s*\}\);/g;
    let match;
    while ((match = re.exec(source)) !== null) {
        const id = match[1];
        const body = match[2];
        const labelMatch = body.match(/label:\s*'([^']*)'/);
        const label = labelMatch ? labelMatch[1] : id;
        const catObjMatch = body.match(/category:\s*\{[\s\S]*?label:\s*'([^']*)'/);
        const catStrMatch = body.match(/category:\s*'([^']+)'/);
        const catObjIdMatch = body.match(/category:\s*\{[\s\S]*?id:\s*'([^']+)'/);
        const catConstMatch = body.match(/category:\s*([A-Z]+)\s*,/);
        const catId = catObjIdMatch
            ? catObjIdMatch[1]
            : (catStrMatch ? catStrMatch[1] : (catConstMatch && CONSTANTS[catConstMatch[1]] ? CONSTANTS[catConstMatch[1]] : ''));
        blocks.push({
            file,
            id,
            label,
            catId,
            catLabel: catObjMatch ? catObjMatch[1] : ''
        });
    }
});

const byCategory = {};
const unknown = [];
blocks.forEach((block) => {
    const key = lib.resolveCategory(block.id, block.label, block.catId, block.catLabel);
    byCategory[key] = byCategory[key] || [];
    byCategory[key].push(block);
    if (key === 'px-other') unknown.push(block);
});

const order = Object.keys(lib.categories).sort((a, b) => lib.categories[a].order - lib.categories[b].order);

console.log('Bloques detectados: ' + blocks.length + ' en ' + files.length + ' ficheros\n');
order.forEach((key) => {
    const list = byCategory[key] || [];
    const meta = lib.categories[key];
    console.log(String(list.length).padStart(3) + '  ' + meta.label + '  [' + key + ']');
});
console.log('');
if (unknown.length) {
    console.log('SIN CATEGORÍA CLARA (' + unknown.length + '):');
    unknown.forEach((b) => console.log('   - ' + b.id + '  (' + b.label + ')  cat=' + b.catId));
} else {
    console.log('Ningún bloque cae en «Otros».');
}
