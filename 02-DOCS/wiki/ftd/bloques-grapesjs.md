---
author: Pepiuox
branch: main
status: in-progress
---
# Bloques propios de GrapesJS en el builder (y el plugin Bootstrap 5)

> Estado: los 164 bloques de los ficheros cargan y registran **verificado en simulación** (no en
> navegador). Falta la comprobación visual del panel.

## Intent

Tres cosas pedidas:

1. Que los `.js` de la carpeta de bloques carguen en el builder visual y funcionen.
2. Revisar si son correctos y arreglar lo que impida usarlos.
3. Añadir el plugin **grapesjs-blocks-bootstrap5** y que todo quede funcional.

## Lo que estaba mal (con evidencia)

### 1. Los 11 ficheros morían al ejecutarse: el editor todavía no existía

Los ficheros son scripts sueltos que empiezan directamente con `gjsEditor.BlockManager.add(...)`.
En `builder.php`, `let gjsEditor = null` y la asignación ocurre dentro de `initGrapesJS()`, que se llama
desde el listener `DOMContentLoaded`. Los `<script src="blocks/…">` estaban al final del `<body>`, así
que se ejecutaban **antes** y reventaban:

```
THROW advance.js  TypeError: Cannot read properties of null (reading 'BlockManager')
… 11 de 11 (simulación con `vm` del orden de carga real)
```

### 2. Las rutas no existían o no eran accesibles

```html
<script src="blocks/blocks.js"></script>          <!-- relativa: /admin/pages/4/blocks/blocks.js -->
<script src="blocks/bs-blocks-advanced.js"></script>  <!-- el fichero real es blocks-bs-advanced.js -->
```

Y los ficheros estaban en `views/pages/blocks/`: `views/` no se sirve por web (el `.htaccess` lo bloquea
y está fuera de `public/`). Resultado: 8 peticiones 404 por carga de página.

### 3. `blocks-advanced.js` usaba una variable que no existe

16 de sus 17 bloques llamaban a `editor.BlockManager.add(...)` en lugar de `gjsEditor.…`:

```
blocks-advanced.js :: ReferenceError: editor is not defined
```

El fichero registraba **1** bloque y abortaba los otros 16.

### 4. Un id de bloque duplicado

`contact-form` estaba en `blocks.js` y en `second-blocks.js` con contenidos distintos. Ganaba el último
en cargarse y el otro desaparecía del panel sin avisar.

### 5. Placeholders PHP dentro del contenido

`advance.js` (4) y `uiblocks.js` (12) llevaban `src="<?php echo SITE_PATH; ?>assets/images/…"`. `SITE_PATH`
no está definido en ningún `.php` del repo y las imágenes no existen: los bloques se insertaban con la
imagen rota.

### 6. Riesgos en la lista de plugins

- `grapesjs` se cargaba desde `https://unpkg.com/grapesjs` **sin versión** (hoy sirve 0.23.8) mientras el
  CSS estaba fijado a 0.21.10: JS y CSS de versiones distintas.
- `pluginsOpts` usaba la clave `'gjs-plugin-export'`, que no es el nombre del plugin
  (`grapesjs-plugin-export`): esas opciones se ignoraban en silencio.
- Se habían añadido 7 plugins sin comprobar (preset-webpage, tui-image-editor, navbar, countdown, typed,
  style-bg, tooltip). Un solo nombre no registrado hacía que `grapesjs.init()` abortara con
  «Plugin not found», que es exactamente el fallo que ya había dejado el builder en blanco una vez.

## Lo que se hizo

| Cambio | Detalle |
|---|---|
| Ubicación | Los 11 ficheros pasan a `public/assets/js/blocks/` (junto al resto de assets, servible por web). `views/pages/blocks/` queda vacía y se elimina. |
| Carga diferida | `loadBlockScripts()` inyecta los ficheros **después** de `grapesjs.init()`, en serie y en orden alfabético (orden reproducible cuando dos ficheros tocan el mismo id). Un fichero que falla no detiene a los siguientes. |
| Lista automática | PHP hace `glob(ASSETS_PATH.'/js/blocks/*.js')`, así que **añadir un fichero no obliga a tocar la vista**. Las exclusiones están en `$blockExcluded` con su motivo. |
| `blocks-advanced.js` | `editor.` → `gjsEditor.` (16 llamadas). |
| `second-blocks.js` | Su `contact-form` pasa a `contact-form-card`: conviven los dos bloques. |
| `advance.js`, `uiblocks.js` | `src="<?php echo SITE_PATH; ?>…"` → `https://placehold.co/600x400?text=Imagen`. |
| Plugins | Versiones fijadas (grapesjs 0.21.10 = el mismo que el CSS) y lista reducida a lo verificado: `gjs-blocks-basic`, `grapesjs-plugin-forms`, `grapesjs-tabs`, `grapesjs-custom-code`, `grapesjs-touch`, `grapesjs-plugin-export` y el nuevo `grapesjs-blocks-bootstrap5@0.2.31`. Se corrige la clave `'grapesjs-plugin-export'`. |
| Bootstrap 5 | `grapesjs-blocks-bootstrap5` con categorías propias (`Bootstrap 5 · Layout`, `· Componentes`, …). El lienzo ya carga Bootstrap 5.3.3 CSS+JS, que es lo que el plugin necesita. |
| Red de seguridad | `filterPlugins()` descarta los nombres que no estén en el registro real de GrapesJS (`grapesjs.plugins.getAll()`) y avisa por consola y con toast. Un CDN caído degrada una función, no el builder entero. |
| Iconos | Se añade Font Awesome 6.5.2 (página y lienzo): 18 bloques y muchas etiquetas usan `fas fa-*` y sin la hoja salían en blanco. |

## Fuera de alcance (documentado)

- **`bs-blocks.js`** no se carga: duplica 5 ids de `blocks-bs.js` (`bs-navbar`, `bs-hero`, `bs-footer`,
  `bs-accordion`, `bs-alert`) con contenido distinto. Se queda `blocks-bs.js` como canónico.
- **`template.js`** no se carga: su único bloque es un layout de administración con un `<script>` dentro
  del `content`, y ese contenido se ejecuta en el lienzo del editor (mismo origen que el panel).
- **`blocks.js` sustituye 5 bloques de los plugins** (`text`, `image`, `video`, `map` de
  `gjs-blocks-basic` y `button` de `grapesjs-plugin-forms`) porque usa los mismos ids. Se deja así: es
  determinista (los ficheros de bloques cargan después de los plugins) y son versiones propias.
- **`advance.js` y `uiblocks.js`** usan clases CSS de una plantilla externa que no está en el repo
  (`fdb-icon`, `about-one`, `action-serenity`…): se insertan sin ese estilo. Los iconos y la estructura
  funcionan; el acabado depende de cargar ese CSS.
- Los 7 plugins retirados se pueden volver a añadir con una línea en `PLUGIN_NAMES` y su `<script>`: el
  filtro ya evita que un nombre inválido tumbe el builder.

## Checklist

- [x] Los 11 ficheros pasan `node --check` — comprobado uno por uno (exit 0).
- [x] Los 9 ficheros cargados registran bloques sin errores — comprobado con una simulación en Node que
      sustituye `gjsEditor` por un mock y ejecuta cada fichero en su propio contexto `vm`:
      **164 bloques, 164 ids únicos, 0 errores, 52 categorías**.
- [x] No quedan ids duplicados entre los ficheros cargados — comprobado: `contact-form` era el único.
- [x] No quedan placeholders PHP en el contenido — comprobado: 16 `src` sustituidos, `grep '<\?php'` en
      `public/assets/js/blocks/` sin resultados.
- [x] Las 11 URLs de bloques responden por HTTP — comprobado con `curl`: `200 text/javascript` en
      `/assets/js/blocks/*.js`.
- [x] Las URLs que inyecta el builder son absolutas y no hay rutas relativas — comprobado en el HTML
      emitido: 9 entradas en `BLOCK_SCRIPTS`, 0 `src="blocks/…"`.
- [x] El plugin Bootstrap 5 está pedido y configurado — comprobado en el HTML emitido:
      `'grapesjs-blocks-bootstrap5': {` en `pluginsOpts` y su `<script>` fijado a 0.2.31.
- [x] Nombre del plugin verificado en su documentación publicada (README del paquete:
      «Plugin name: `grapesjs-blocks-bootstrap5`») y fichero `dist/index.min.js` verificado en el listado
      de jsDelivr del paquete 0.2.31.
- [x] JS emitido por la vista válido — comprobado con `node --check` sobre el `<script>` renderizado.
- [x] `php -l` sin errores en `views/pages/builder.php`.
- [ ] Verificación en navegador (panel con los bloques, arrastrar uno al lienzo, abrir un bloque
      Bootstrap 5): **no ejecutada**, no hay navegador en esta sesión.

## Evidence

Simulación de carga (mock de `gjsEditor` + `vm`, mismo orden que genera el PHP):

```
ficheros cargados por el builder (9): advance.js, blocks-advanced.js, blocks-bootstrap.js,
  blocks-bs-advanced.js, blocks-bs.js, blocks.js, bs-block-advanced.js, second-blocks.js, uiblocks.js
bloques registrados: 164 | ids unicos: 164
errores en ejecucion: ninguno
ids duplicados entre ficheros cargados: ninguno
categorias: 52
excluidos: bs-blocks.js, template.js (11 bloques)
```

Antes de los arreglos la misma simulación daba:

```
errores en ejecucion:
  blocks-advanced.js :: ReferenceError: editor is not defined
ids duplicados entre ficheros cargados:
  contact-form -> blocks.js + second-blocks.js
bloques registrados: 148 | ids unicos: 147
```

Antes de cambiar el mecanismo de carga, los 11 ficheros lanzaban
`TypeError: Cannot read properties of null (reading 'BlockManager')`.

## Lessons (autoaprendizaje)

1. **Un `let` global no existe para un `<script>` que corre antes de asignarlo.** Los ficheros de bloques
   asumen `gjsEditor` listo: hay que inyectarlos después de `grapesjs.init()`, no como `<script>` fijos.
2. **`node --check` no prueba que un fichero funcione**: los 11 pasaban sintaxis y uno reventaba en la
   línea 52 por una variable inexistente. Ejecutar el código con un mock es lo que descubre el fallo real.
3. **`views/` no es una carpeta de assets.** Lo que el navegador tiene que descargar vive bajo `public/`.
4. **Una lista de plugins sin versión es una bomba de relojería** (y mezclar versiones de CSS y JS de
   GrapesJS también). Fijar versión y filtrar contra el registro convierte un fallo total en un aviso.
5. **Un id de bloque repetido no da error**: gana el último y el otro desaparece. Hay que comprobarlo.

## Next

1. Abrir el builder y confirmar en el panel: las categorías nuevas, un bloque Bootstrap 5 arrastrado al
   lienzo y su HTML resultante.
2. Decidir si se quiere cargar `template.js` sin su `<script>` (sería un bloque de layout, no de sección).
3. Revisar `views/pages/editor.php`: hoy es una copia de `builder.php` y arrastra los `<script src="blocks/…">`
   antiguos. No lo usa ninguna ruta de `config/routes.php`.
