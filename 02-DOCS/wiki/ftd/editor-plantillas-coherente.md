---
author: Pepiuox
branch: main
status: in-progress
---
# El editor de plantillas usa el mismo sistema que el builder de páginas

> Antes: `views/templates/edit.php` era un editor aparte — otro GrapesJS (solo `gjs-blocks-basic`), sin
> los bloques del proyecto, sin Bootstrap en el lienzo, con jQuery y un guardado a base de recargar la
> página. Todo eso queda alineado con `views/pages/builder.php`.

## Qué estaba incoherente (con evidencia)

| # | Problema | Comprobación |
|---|---|---|
| 1 | Cargaba **1 plugin** (`gjs-blocks-basic`); el builder 7 | HTML servido del editor de plantillas |
| 2 | **No cargaba** los bloques de `public/assets/js/blocks` (164 bloques) | no había `<script>` de bloques |
| 3 | El **lienzo no tenía Bootstrap** (`canvas.styles: []`): cualquier bloque Bootstrap se veía roto | `canvas: { styles: [], scripts: [] }` |
| 4 | **jQuery** y Bootstrap 5.3.0 (la página) frente a Bootstrap 5.3.3 en el lienzo del builder | `<script src="…jquery@3.6.0…">` |
| 5 | Guardado con **recarga completa** (`<form>` POST) y sin manejo de errores del servidor | `$('#templateForm').on('submit', …)` |
| 6 | **«Guardar versión» no guardaba nada**: enviaba JSON con `X-CSRF-TOKEN`, pero `CSRF::verify()` lee `$_POST` | `contentType: 'application/json'` + `headers: {'X-CSRF-TOKEN': …}` |
| 7 | **Las versiones no existían**: `TemplatesService` insertaba en `template_versions` una columna `created_by` que la tabla no tiene, y `getVersions()` hacía `JOIN users ON u.id = tv.created_by` | `SHOW COLUMNS FROM template_versions` (id, template_id, content_html, content_css, content_php, content_js, created_at) |
| 8 | El enlace «Versiones» llevaba a una vista **que no existía** (`views/templates/versions.php`) | `TemplatesController::versions()` renderiza `templates.versions` |

## Qué se hizo

**Fuente única de activos** (`views/partials/`):
- `editor-assets-head.php`: fuentes, iconos (Bootstrap Icons + Font Awesome), GrapesJS CSS, CodeMirror
  CSS. Bootstrap 5 solo si `$editorIncludeBootstrap` (el builder no lo necesita).
- `editor-assets-body.php`: CodeMirror (librería → modos → addons), GrapesJS + los 7 plugins (incluido
  `grapesjs-blocks-bootstrap5@0.2.31`) y el script de utilidades compartidas.

**Fuente única de comportamiento** (`public/assets/js/grapesjs-shared.js` → `window.EditorKit`):
lista de plugins, sus opciones, estilos/scripts del lienzo, filtro contra el registro real de GrapesJS y
la carga secuencial de los ficheros de bloques. El builder ya no tiene copias locales de esa lógica.

**`views/templates/edit.php` reescrito** con el modelo del builder: mismos activos, mismos plugins,
mismos bloques, mismo lienzo (Bootstrap 5 + iconos + Font Awesome), CodeMirror con las mismas opciones
(tema drácula, plegado, `Ctrl+S`), sincronización en los dos sentidos lienzo ↔ código con la misma guarda
`isSyncing`, restauración del proyecto con `loadProjectData`, guardado por `fetch` con el mismo criterio
de errores (no dar por bueno un 4xx/5xx) y toasts iguales. Sin jQuery.

**Controlador**: `TemplatesController::update()` responde JSON cuando la petición es AJAX (igual que
`PagesController::update()`), manteniendo el redirect para el POST normal y para la creación.

**Versiones arregladas**: `TemplatesService::createVersion()` ya no escribe `created_by` y
`getVersions()` ya no hace el JOIN inexistente. Se creó la vista `views/templates/versions.php`
(listado + restaurar + eliminar) que faltaba.

## Fuera de alcance (documentado)

- **Subir imágenes desde el editor de plantillas**: no existe endpoint de subida para plantillas. El
  gestor de assets muestra la biblioteca de medios (la de `uploads/`), pero el botón de subida del
  plugin no tiene destino. Se resuelve creando un endpoint propio o extrayendo la subida de
  `PagesController::uploadAsset()` a un servicio compartido.
- **Autoguardado**: el builder lo tiene porque existe `page_autosave`. Para plantillas no hay tabla ni
  endpoint; añadirlo requiere esquema nuevo.
- **`views/pages/editor.php`**: sigue siendo una copia antigua de `builder.php` (con los `<script>` de
  bloques relativos). Ninguna ruta de `config/routes.php` la usa.
- `template_versions` no guarda autor ni nota: la vista no los inventa.

## Checklist

- [x] Los dos editores cargan **exactamente** los mismos activos — comprobado en el HTML emitido: 1
      `bootstrap-icons`, 1 `fontawesome`, 1 `grapesjs@0.21.10` CSS, 1 `codemirror` CSS en cada uno, y
      1 `grapesjs-shared.js`.
- [x] El editor de plantillas carga los 7 plugins y los 9 ficheros de bloques — comprobado en su HTML:
      `grapesjs-blocks-bootstrap5@0.2.31` presente y 9 entradas en `BLOCK_SCRIPTS`.
- [x] El builder sigue igual tras la refactorización — comprobado: `node --check` del JS emitido y
      `EditorKit.filterPlugins` / `EditorKit.loadBlockScripts` / `CANVAS_STYLES` en uso.
- [x] `EditorKit` se comporta como debe — simulación en Node: con registro de plugins filtra
      (`skipped` correcto), sin registro deja pasar la lista completa, y la carga de bloques inyecta los
      ficheros en orden llamando a `onDone`.
- [x] Guardar por AJAX funciona — simulación del POST real por el router: `{"success":true,"message":"Plantilla guardada"}`,
      con `content_html`, `grapesjs_data`, `is_active`/`is_default` persistidos.
- [x] «Guardar versión» guarda — `createVersion()` devuelve `success` y `getVersions()` lista las 2
      versiones creadas en la prueba.
- [x] La página de versiones existe y renderiza — `GET /admin/templates/1/versions` devuelve 31 587 B
      dentro de `layouts.admin`, con el nombre de la plantilla.
- [x] Sintaxis: `php -l` en los 5 PHP tocados y `node --check` en `grapesjs-shared.js` y en el JS inline
      de los dos editores.
- [ ] Comprobación en navegador autenticado (arrastrar un bloque, guardar, ver la versión en el
      listado): **no ejecutada**, sin navegador en esta sesión.

## Evidence

```
templates/edit (plantilla real #4)   331 742 B · 1 <html> · 9 BLOCK_SCRIPTS · EditorKit · bootstrap5
templates/edit (nueva)                39 934 B · action=/admin/templates · isNew=true · sin botón versiones
templates/1/versions                  31 587 B · layout admin · «Versiones de Landing Page»
builder/pages/4/builder              105 221 B · JS inline node --check OK
POST /admin/templates/{id} (AJAX)    {"success":true,"message":"Plantilla guardada"} + fila persistida
```

Simulación de `EditorKit`:

```
registrados -> {"enabled":["gjs-blocks-basic","grapesjs-plugin-forms","grapesjs-blocks-bootstrap5"],
                "skipped":["grapesjs-tabs","grapesjs-custom-code","grapesjs-touch","grapesjs-plugin-export"]}
sin registro -> los 7 habilitados, 0 descartados
bloques inyectados -> 3 /a.js,/b.js,/c.js
```

## Lessons (autoaprendizaje)

1. **Dos editores con el mismo motor divergen si cada uno copia su lista de CDN.** Extraer los activos a
   un partial y la lógica a un script compartido convierte «coherencia» en algo que no depende de la
   memoria de nadie.
2. **Un `catch` que devuelve `[]` esconde que la consulta está rota**: `getVersions()` llevaba tiempo
   devolviendo vacío por un JOIN a una columna inexistente, sin un solo error visible.
3. **`CSRF::verify()` lee `$_POST`, no cabeceras.** Un cliente que manda `X-CSRF-TOKEN` con body JSON
   falla siempre el guardado; hay que enviar `_csrf_token` en el cuerpo.
4. **Una vista enlazada que no existe es un 500 silencioso** hasta que alguien pulsa el botón.
5. **Comprobar la coherencia contando elementos en el HTML emitido** (un `<html>`, un CSS, un script
   compartido) es rápido y detecta duplicados o ausencias que el ojo no ve.

## Next

1. Abrir `Editar plantilla`, arrastrar un bloque Bootstrap 5, guardar y comprobar el listado de versiones.
2. Decidir el endpoint de subida de imágenes para plantillas (extraer la subida a un servicio común).
3. Si se quiere autoguardado en plantillas, crear tabla y ruta propias — o reutilizar `page_autosave`
   con un `owner_type`.
