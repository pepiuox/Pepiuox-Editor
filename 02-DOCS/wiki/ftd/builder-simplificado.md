---
author: Pepiuox
branch: main
status: in-progress
---
# Builder de páginas: correcciones de carga y guardado

> **Nota de rumbo.** Hubo un intento de reescritura completa de `views/pages/builder.php` que
> resultó menos funcional que el original: no cargaba datos. Se revirtió a `HEAD` y todo se corrigió
> de forma quirúrgica sobre el archivo original (+206 / −113 líneas en la vista). El diseño y la
> estructura originales se conservan: barra lateral de Herramientas, panel derecho con Configuración
> y Comentarios, pestañas Visual/HTML/CSS/JS/PHP.

## Intent

El builder no cargaba contenido ni guardaba. Tres defectos independientes lo explicaban:

1. **Documento anidado.** `PagesController::builder()` renderizaba la vista con
   `View::render('pages.builder', …)`, cuyo layout por defecto es `layouts.admin`. Ese layout mete el
   contenido dentro de `<main class="main-content">`, así que el documento del builder —con su propio
   `<!DOCTYPE>`, `<html>`, `<head>` y `<body class="builder-body">`— quedaba dentro de otro documento.
   El navegador descarta un segundo `<body>`: la clase `builder-body` nunca se aplicaba al body real,
   `.builder-main` quedaba sin altura y con ello el lienzo, CodeMirror y los paneles se veían a 0 px.

2. **Plugin inexistente.** El builder cargaba
   `https://cdn.jsdelivr.net/npm/grapesjs-parser-postcss@1.0.0/dist/index.min.js`.
   **Esa versión no existe** (el paquete publicó `1.0.3`, `1.0.1` y `0.1.1`). El `<script>` daba 404,
   pero el nombre seguía en `plugins`, así que `grapesjs.init()` lanzaba *«Plugin not found»* y
   abortaba. Como todo el arranque vivía en un único `try`, arrastraba también `bindEvents()` y
   `startAutoSave()`.

3. **Guardado roto, por dos causas encadenadas.**
   - `PageService` es `final` y tiene constructor de dos dependencias (`Database`, `PageModel`);
     `save()` es un método de instancia. `PagesController` lo llamaba como
     `PageService::save(...)` → `Error: Non-static method ... cannot be called statically`. El
     bootstrap además construía `new PageService($db)` con un argumento de dos, dejando
     `pageService` en `null` en el contenedor.
   - Ya instanciado el servicio, `PageService::save()` invoca `CacheService::invalidatePage($id)`,
     método que **no existía** → `Call to undefined method`. La excepción provocaba el rollback y
     `save()` devolvía `false`, que es la alerta «Error al guardar».

## Scope

Dentro:
- `app/Core/View.php`: nuevo `renderStandalone()` para vistas que ya son un documento HTML completo.
- `app/Controllers/PagesController.php` y `EditorController`/`TemplatesController`: renderizan sus
  vistas completas sin layout. `update()` persiste `content_js` y `content_php` también en modo
  `visual`; conserva `h1`, `parent_id` e `is_published` cuando el builder no los envía; valida la
  longitud de `title`/`description` contra el `varchar(255)`; `uploadAsset()` conserva el nombre
  original del archivo con sufijo `-1`, `-2` ante colisión.
- `app/Services/CacheService.php`: `invalidatePage()`, `invalidateTemplatePages()` y `putPage()`
  como métodos estáticos que delegan en la instancia, siguiendo el patrón de `invalidateAllPages()`.
- `app/bootstrap.php`: construye `PageService` con sus dos dependencias.
- `views/pages/builder.php`: arranque aislado por subsistema, `preview()` implementada,
  `comments.js` cargado, indicador de autoguardado con el id correcto, `isDirty` al editar el
  lienzo y restauración de `grapesjs_data`.

Fuera:
- El motor GrapesJS y el diseño del builder: se conservan.
- Esquema de datos (no se amplía `description` a TEXT; se valida en el controlador).
- `PagePublicService`, versiones y el backend de comentarios.

## Checklist

- [x] Documento FTD escrito antes del primer cambio — comprobado: este archivo existe.
- [x] El builder se sirve como documento único — comprobado: una sola aparición de `<!DOCTYPE`,
      `<html`, `<head>` y `<body`, y ninguna de `main-content`.
- [x] Ningún `<script>` apunta a una versión inexistente — comprobado: las 7 versiones usadas se
      verificaron publicadas contra la API de jsDelivr; `grapesjs-parser-postcss@1.0.0` se retiró
      del `<script>` y de `plugins`.
- [x] Cada subsistema arranca aislado — comprobado: 4 llamadas a `safeInit(...)`.
- [x] `preview()` existe — comprobado: la función está declarada en el HTML emitido.
- [x] `comments.js` se carga y `CommentsManager` se instancia — comprobado en el HTML emitido.
- [x] El indicador de autoguardado usa un id existente — comprobado: `autoSaveStatus`.
- [x] El lienzo restaura el proyecto guardado — comprobado: `loadProjectData` y `pageData.grapes`.
- [x] El guardado funciona de extremo a extremo — comprobado: `PageService::save()` devuelve `true`
      contra la base real y persiste contenido, `grapesjs_data` y versión.
- [x] Sintaxis válida — comprobado: `php -l` sin errores en todos los archivos tocados.

## Evidence

Versiones de CDN verificadas contra la API de jsDelivr:

| Paquete | Versión usada | ¿Publicada? |
|---|---|---|
| grapesjs | 0.21.10 | sí |
| grapesjs-blocks-basic | 1.0.2 | sí |
| grapesjs-plugin-forms | 2.0.6 | sí |
| grapesjs-tabs | 1.0.6 | sí |
| grapesjs-custom-code | 1.0.2 | sí |
| grapesjs-touch | 0.1.1 | sí |
| grapesjs-plugin-export | 1.0.12 | sí |
| codemirror | 5.65.16 | sí |
| **grapesjs-parser-postcss** | **1.0.0** | **NO (404)** |

Diagnóstico del guardado, antes y después:

```
// Antes
LANZA Error: Non-static method App\Services\PageService::save() cannot be called statically
[PageService] Error al guardar: Call to undefined method App\Services\CacheService::invalidatePage()

// Después: ejecución real contra la base, con la fila restaurada al terminar
save() devuelve: true
content_html actualizado: SÍ
grapesjs_data guardado:   SÍ
versión creada:           SÍ (1)
content_html restaurado:  SÍ
```

Estructura del documento y arranque, sobre el HTML emitido:

```
PASS un solo <!DOCTYPE / <html / <head> / <body>   PASS carga comments.js
PASS body con clase builder-body                    PASS pageData.grapes inyectado
PASS sin wrapper de admin                           PASS preview() definida
PASS sin parser-postcss como script o plugin        PASS indicador apunta a autoSaveStatus
```

Evidencia que **no** se pudo obtener: interacción en navegador real. El navegador headless no arranca
bajo el sandbox de la sesión (falla al abrir el canal `mojo` con *Access is denied*).

## Next

Abrir `/admin/pages/{id}/builder`, editar en el lienzo, guardar y confirmar que no aparece la alerta
de error y que al recargar se conserva lo editado. Después: subir una imagen y comprobar que
mantiene su nombre en `uploads/`.
