// Comprobación del normalizador de contenido heredado (Node, sin navegador).
'use strict';
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..', '..');
const sb = { window: {}, document: { addEventListener() {}, querySelector() { return null; }, querySelectorAll() { return []; } }, console };
vm.createContext(sb);
vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js/blocks-library.js'), 'utf8'), sb);
const lib = sb.window.BlocksLibrary;

const attrs = {
    label: '<i class="fas fa-card"></i> Card',
    content: '<div style="padding: 20px; border: 1px dashed #ddd; background: #f8f9fa; color: #6c757d">Hola</div>'
};
const block = {
    id: 'uiblocks-card',
    get(key) { return key === 'category' ? 'Cards' : attrs[key]; },
    set(key, value) { attrs[key] = value; }
};
lib.install({ BlockManager: { on() {}, getAll() { return [block]; }, get() { return null; }, remove() {} } });

let failed = 0;
function check(name, condition, detail) {
    console.log((condition ? '  PASA  ' : '  FALLA ') + name + (condition ? '' : ' -> ' + detail));
    if (!condition) failed++;
}

check('icono incrustado fuera de la etiqueta', attrs.label === 'Card', attrs.label);
check('asigna icono propio', /class="bi bi-card-text"/.test(attrs.media || ''), attrs.media);
check('padding fijo -> p-4', /class="p-4/.test(attrs.content), attrs.content);
check('borde de guia fuera', attrs.content.indexOf('dashed') === -1, attrs.content);
check('sin colores fijos', !/#[0-9a-f]{3,6}/i.test(attrs.content), attrs.content);
check('categoria canonica', attrs.category && attrs.category.id === 'px-cards', JSON.stringify(attrs.category));

console.log('\nContenido resultante:\n' + attrs.content);
console.log('\n' + (failed === 0 ? 'Resultado: todas las comprobaciones pasan' : 'Resultado: ' + failed + ' fallan'));
process.exit(failed === 0 ? 0 : 1);
