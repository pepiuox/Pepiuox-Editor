# Biblioteca visual de bloques (BlockManager)

Rediseño del `BlockManager` de GrapesJS del editor de páginas y plantillas.
Estado: **implementado y verificado a nivel de código y de carga** (2026-10-30).
Sin probar en navegador real desde esta sesión (ver §6).

---

## 1. Qué había

- **188 bloques** repartidos en 11 ficheros de `public/assets/js/blocks/`, cada
  uno registrando con `gjsEditor.BlockManager.add(id, { label, content, category })`.
- **+35 categorías** distintas, muchas de un solo bloque, con emojis mezclados
  (`📐 Layouts Bootstrap`, `Advanced Widgets`, `Basic`, `Plantillas`…).
- Varios **duplicados literales** (`bs-navbar`, `bs-hero`, `bs-footer`,
  `bs-accordion`, `bs-alert` en dos ficheros) y sinónimos redundantes
  (`navbar`, `navbar-bs`, `navbar-dark`, `bootstrap-navbar`, `bs-navbar`).
- Cada bloque se pintaba como **un botón con un icono**: sin miniatura, sin
  descripción visible y sin búsqueda.
- El panel de GrapesJS caía dentro del contenedor del lienzo, sin integración
  con el panel izquierdo propio del builder.

## 2. Qué hay ahora

| Pieza | Archivo |
| --- | --- |
| Categorías canónicas, normalización y retirada de duplicados | `public/assets/js/blocks-library.js` |
| Estilos del panel (tarjetas, buscador, categorías) | `public/assets/css/blocks-library.css` |
| Bloques nuevos (plantillas rápidas, hero, navbar, footer) | `public/assets/js/blocks/pro-templates.js` |
| Enganche automático tras cargar los bloques | `EditorKit.loadBlockScripts()` en `grapesjs-shared.js` |
| Pestaña «Bloques» del panel izquierdo | `views/pages/builder.php` |
| Auditoría de clasificación (Node) | `tests/blocks-library/classify.js` |

### 11 categorías, todas con icono y orden

`Plantillas rápidas · Estructura y layout · Navegación · Encabezados y hero ·
Contenido · Botones y acciones · Tarjetas · Formularios · Componentes Bootstrap ·
Tablas y datos · Pie de página`

La clasificación se hace **por API**: a cada bloque se le asigna
`block.set('category', { id, label, icon, order })`. No se toca el DOM de
GrapesJS para reagrupar, así que las categorías siguen siendo las nativas (se
abren y cierran con su propio comportamiento).

Resultado real de la auditoría sobre los ficheros del proyecto:

```
188 bloques en 12 ficheros
 16  Plantillas rápidas     28  Estructura y layout
  9  Navegación              8  Encabezados y hero
 38  Contenido              18  Botones y acciones
 20  Tarjetas               15  Formularios
 24  Componentes Bootstrap  10  Tablas y datos
  2  Pie de página           0  Otros
```

### Tarjetas con miniatura real

Cada `.gjs-block` se reescribe con:

1. **Miniatura**: el HTML del propio bloque dentro de un `<iframe sandbox>`
   escalado al 33 %, con Bootstrap 5.3 e iconos cargados. No son iconos
   decorativos: es el componente que se va a insertar.
   - Contenido que no es HTML (bloques de plugin) cae al icono `media`.
   - `loading="lazy"`, `sandbox` vacío (sin scripts) y `pointer-events: none`.
2. **Nombre** limpio (se quita el HTML del `label` heredado) con elipsis a dos
   líneas.
3. **Estrella de favorito** (aparece al pasar el ratón).
4. `title` con `nombre — descripción` para el tooltip nativo.
5. Badge **Plantilla** / **Página** en los bloques nuevos.

### Buscador, filtros y memoria

- Buscador por nombre, descripción, id y palabras clave (`pxKeywords`).
- Chips: **Todo · Recientes · Favoritos**, con contador de bloques visibles.
- Las categorías sin resultados se ocultan y su contador refleja lo filtrado.
- Favoritos y últimos 12 usados en `localStorage` (`px.blocks.favorites`,
  `px.blocks.recent`); si el navegador lo bloquea, siguen funcionando en memoria.
- Estado vacío explícito cuando nada coincide.

### Bloques nuevos (13)

Plantillas rápidas: `Hero con CTA`, `Sección de características`,
`Sección de testimonios`, `Sección de precios`, `Sección de preguntas
frecuentes`, `Sección de estadísticas`, `Sección de contacto`, `Llamada a la
acción`, `Landing corporativa`, `Página de servicios`.
Hero: `Hero centrado`. Navegación: `Navbar con logotipo`. Pie: `Footer
corporativo`.

Todo es HTML de Bootstrap 5.3.3 (la versión que carga el lienzo) con clases
nativas —`container`, `row`, `col-*`, `g-*`, `card`, `btn`, `accordion`,
`badge`, `d-*`— y componentes GrapesJS independientes: al insertarlos se pueden
editar, mover y estilizar como cualquier otro. **No** son imágenes ni HTML
bloqueado.

Los huecos de imagen usan un marcador con degradado e icono (sin dependencias
externas): se sustituye arrastrando una imagen encima.

### Duplicados retirados

`bs-block-advanced-bs-pricing-section`, `bootstrap-btn-lg`, `bootstrap-btn-sm`,
`bootstrap-btn-group`, `bootstrap-img-thumbnail`, `btn-social-twitter`,
`btn-social-facebook`, `divider`, `spacer`, `navbar-dark`, `navbar-bs`,
`second-blocks-features-3col`.

Son definiciones de biblioteca: quitarlas **no afecta a ninguna página ya
guardada**, porque el contenido vive en el proyecto y no en el bloque.

## 3. Compatibilidad

- El arrastre lo sigue gestionando **GrapesJS**: las tarjetas son el mismo
  `.gjs-block` de siempre, solo con otro contenido interno. No se reimplementa
  el DnD.
- La biblioteca se aplica al terminar de cargar los bloques
  (`EditorKit.loadBlockScripts`), igual que antes, así que el orden de registro
  no cambia.
- `pro-templates.js` se carga solo: `views/pages/builder.php` lista el directorio
  `public/assets/js/blocks/*.js` con `glob`.
- El editor de plantillas (`views/templates/edit.php`) recibe el CSS y el JS por
  `views/partials/editor-assets-{head,body}.php`; si no tiene un host
  `#gjsBlocks`, la mejora no se aplica y todo sigue como estaba.
- No se toca ningún otro panel, el lienzo ni el guardado.

## 4. Cómo verificarlo

```bash
# sintaxis
node --check public/assets/js/blocks-library.js
node --check public/assets/js/blocks/pro-templates.js
php -l views/pages/builder.php

# clasificación real de los bloques del proyecto
node tests/blocks-library/classify.js
```

En el navegador: abrir el builder de cualquier página → pestaña **Bloques** →
comprobar miniatura, buscador, chips, categorías y arrastrar una plantilla.

## 5. Riesgos y límites conocidos

1. **Miniaturas**: son iframes; con 188 bloques la primera carga del panel
   descarga el CSS de Bootstrap en cada uno (queda en caché del navegador). Si
   molesta, se puede limitar a la categoría abierta.
2. **Categorías con pocos bloques**: `Pie de página` (2) y `Encabezados y hero`
   (8) tienen menos variedad que el resto; quedan huecos para seguir creciendo.
3. **Favoritos en `localStorage`**: por navegador, no por usuario.
4. **Bloques de plugin** (formularios de `grapesjs-plugin-forms`, tabs, export):
   su miniatura cae al icono porque su `content` es una función, no HTML.
5. La retirada de duplicados usa una lista explícita (`HIDE`): si se añaden
   ficheros de bloques nuevos con ids repetidos, hay que ampliarla.

## 6. Qué NO se ha podido comprobar aquí

No hay navegador en esta sesión, así que **no** he visto el panel renderizado ni
he arrastrado un bloque. Lo comprobado es: sintaxis de todos los ficheros, la
página del builder respondiendo `200` con el host, la pestaña, el CSS, el JS y
`pro-templates.js` en la lista de bloques, y la clasificación de los 188 bloques
ejecutando la lógica real de la biblioteca en Node. El arrastre y las miniaturas
necesitan una pasada manual en el navegador.
