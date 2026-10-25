---
author: Pepiuox
branch: main
status: in-progress
---
# Editor de plantillas: por qué no cargaba contenido y por qué no se veía como el builder

> Segunda pasada sobre `views/templates/edit.php` (la primera está en
> [editor-plantillas-coherente.md](editor-plantillas-coherente.md)). El motor ya era el del builder, pero el
> armazón de la página no: el contenido estaba en el HTML y aun así no se veía nada.

## Los dos síntomas y su causa común

1. **No carga contenido.**
2. **El aspecto no está definido** (zonas vacías, sin proporciones).

Ambos venían del mismo defecto estructural: **la vista envolvía todo el layout en un `<form>` visible**.

```
body.tpl-body { height: 100vh; display: flex; flex-direction: column }   ← alturas repartidas aquí
  <header> …            flex: 0 0 auto                                    ← bien
  <form id="templateForm">                                               ← ¡bloque normal, altura auto!
    .tpl-meta           (grid, alto)
    .tpl-layout         flex: 1 1 auto  → sin padre flex: no hace nada
      .tpl-panes        flex: 1 1 auto; min-height: 0
        .tab-pane       position: absolute; inset: 0                     ← absolutos: no dan altura
          #gjs          height: 100%  → 100% de 0 px = 0 px              ← lienzo invisible
```

Sin altura, GrapesJS crea un canvas de 0 px y CodeMirror queda con 0 px de alto: el contenido estaba
cargado en el `templateData` (14 670 bytes del HTML de la plantilla #4) pero **no había dónde dibujarse**.

## La solución: el armazón del builder, tal cual

En lugar de arreglar el flex a mano, se copia la estructura de `views/pages/builder.php`, que ya está
probada en producción:

| Pieza | Regla |
|---|---|
| `body.builder-body` | `height: 100vh; overflow: hidden` |
| `.builder-topbar` | `height: var(--builder-topbar-h)` (56 px) |
| `.builder-main` | `display: flex; height: calc(100vh - var(--builder-topbar-h))` |
| `.builder-left` / `.right-panel` | anchos fijos de 280 / 340 px con `flex-shrink: 0` |
| `.editor-panel` | `flex: 1; display: flex; flex-direction: column; min-width: 0` |
| `.editor-content` | `flex: 1; position: relative; min-height: 0` |
| `.editor-pane` | `position: absolute; inset: 0` (solo el activo con `display: block`) |
| `#gjs` | `height: 100%; width: 100%` |

Y el formulario deja de envolver nada: pasa a ser **oculto** (`#saveForm`), igual que en el builder. Los
campos de metadatos viven en el panel derecho y `saveTemplate()` los copia al formulario oculto antes de
enviar.

Además se replicó el resto del armazón del builder, para que las dos pantallas se lean igual:

- topbar: logo de volver, título con `#id`, chip «sistema», estado de guardado, botones *Versiones* y
  *Guardar*, toggle del panel derecho, chip de usuario.
- panel izquierdo con pestañas **Acciones** (guardar, guardar versión, ir a HTML/CSS, dispositivos,
  enlaces) y **Capas** (lista real del lienzo; clic para seleccionar el componente).
- pestañas *Visual / HTML / CSS / JS / PHP*, barra de estado (punto de estado, modo, línea/col, tamaño,
  id de plantilla) y toasts idénticos.
- mismos tokens CSS (`--builder-*`), misma tipografía y mismos estados hover/focus.

## Verificación

Como no hay navegador en la sesión, se montó un **arnés de ejecución real del JS** con Node: DOM
simulado (elementos por id, `classList`, `dataset`, `innerHTML`), `CodeMirror` y `grapesjs.init`
simulados, `fetch` interceptado, y el `EditorKit` **de verdad** cargado desde `public/assets/js/`.

Salida contra `GET /admin/templates/4/edit`:

```
PASS  script de la vista cargado
✅ Editor de plantillas inicializado
PASS  DOMContentLoaded sin excepciones
CodeMirror: editor-html, editor-css, editor-js, editor-php
HTML cargado en el editor de código: 11640 bytes      ← el contenido SÍ carga
CSS cargado en el editor de código: 252551 bytes
capas renderizadas: true
estado: Todo guardado
scripts de bloques inyectados en <body>: 1
PASS  saveTemplate() sin excepciones
  form name: Plantilla de prueba
  form html: 11640 bytes · form css: 252551 bytes
  grapesjs_data: {"pages":[{"frames":[]}]}…
  is_active: 1 | create_version: 1
peticiones fetch: /admin/pages/assets, /admin/templates/4
PASS  switchTab / setDevice
```

Y contra `GET /admin/templates/create` (variante de plantilla nueva): arranca igual, el contenido llega
vacío (0 bytes, correcto) y el guardado toma la ruta de `form.submit()`.

Comprobaciones estáticas sobre el HTML emitido:

```
body height: 100vh                     .editor-content flex:1 position:relative
.builder-main calc(100vh - 56px)       .editor-pane absolute inset:0
#gjs height:100%                       form envolviendo el layout: NO
5 panes + 1 panel                      BLOCK_SCRIPTS: 9 · EditorKit: 1
JS inline node --check: OK             php -l: OK
```

## Fuera de alcance

- Subir imágenes desde el editor de plantillas: no hay endpoint (el gestor usa la biblioteca existente).
- Autoguardado: no existe tabla equivalente a `page_autosave` para plantillas.
- `.tab-pane`/`.editor-pane` conviven: el builder usa `.editor-pane`; aquí se copia ese nombre.

## Lessons (autoaprendizaje)

1. **Un `<form>` en medio de una cadena de flex rompe las alturas.** Si un layout depende de `flex: 1`
   con hijos absolutos, ningún ancestro intermedio puede ser un bloque de altura automática.
2. **«No carga contenido» puede ser «no hay dónde dibujarlo».** Antes de tocar la carga de datos, medir
   la altura del contenedor: 0 px explica el síntoma completo.
3. **Copiar el armazón que ya funciona es más rápido y seguro que inventar otro**: misma topbar, mismo
   `calc()`, mismos panes absolutos.
4. **Un arnés de ejecución en Node con DOM simulado** detecta errores de arranque (funciones inexistentes,
   orden de inicialización, campos que no se rellenan) que `node --check` no ve.

## Next

1. Abrir `Editar plantilla` y comprobar: lienzo con el contenido, panel de bloques a la derecha del
   lienzo, pestañas HTML/CSS con el código y guardado sin recargar.
2. Si se quiere paridad total con el builder: subida de imágenes (endpoint compartido) y autoguardado.
