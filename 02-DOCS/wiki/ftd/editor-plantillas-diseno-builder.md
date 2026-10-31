---
author: Pepiuox
branch: main
status: in-progress
---
# Editor de plantillas con el diseño del builder

## Intent

`views/templates/edit.php` usaba una tarjeta clara de Bootstrap, mientras el builder de páginas usa
un tema oscuro propio. Dos editores para la misma tarea se veían como dos productos distintos.

Se unificó el lenguaje visual del editor de plantillas con el del builder: misma paleta, misma barra
superior, mismas pestañas y mismos botones. **Solo diseño**: GrapesJS y CodeMirror ya funcionaban y
no se tocó su lógica.

## Scope

Dentro:
- `views/templates/edit.php`: bloque `<style>` reescrito con la paleta del builder (`#0d1117`,
  `#161b22`, acento `#00d4aa`, tipografía Inter, mono JetBrains Mono); barra superior con logo,
  título, insignia de id y acciones; panel oscuro; pestañas tipo píldora; pie con opciones y
  acciones; avisos con el lenguaje de los toasts del builder.
- `data-bs-theme="dark"` en `<html>` para que los controles de Bootstrap hereden el tema.

Fuera:
- Toda la lógica JavaScript: GrapesJS, CodeMirror, pestañas, guardado y versiones.
- Los `id`, `name` y `data-tab` que el JavaScript consulta.
- `TemplatesController` y el backend de plantillas.

Contratos conservados a propósito: `#editorTabs .nav-link[data-tab]`, `.tab-pane` / `#pane-<tab>`
con la clase `active`, `#gjs-editor`, `#cm-html` / `#cm-css` / `#cm-js` / `#cm-php`,
`#grapesjs-data`, `#templateForm`, `#btnSave` (único, en la barra superior, asociado al formulario
con `form="templateForm"`) y `#btnSaveVersion`.

## Checklist

- [x] Documento FTD escrito antes del primer cambio — comprobado: este archivo existe.
- [x] Mismo lenguaje visual que el builder — comprobado: paleta y componentes idénticos en el CSS.
- [x] Los ids y clases que usa el JavaScript siguen presentes — comprobado: render con Auth simulado.
- [x] Un solo botón de envío y un solo `#btnSave` — comprobado sobre el HTML emitido.
- [x] El JavaScript no se modificó — comprobado: el bloque de scripts es idéntico al de `HEAD`
      tras normalizar saltos de línea.
- [x] Documento único, sin tarjetas claras antiguas — comprobado: sin `card-header` ni `card-body`.
- [x] Sintaxis válida — comprobado: `php -l` sin errores y llaves CSS equilibradas (63/63).

## Evidence

Render con Auth simulado, 17 comprobaciones, todas PASS:

```
PASS documento único <!DOCTYPE / <html / <body>   PASS campo oculto grapesjs_data
PASS modo oscuro de Bootstrap                     PASS formulario con id correcto
PASS barra superior del builder                   PASS btnSave único y asociado al form
PASS panel contenedor                             PASS un solo botón de envío
PASS sin tarjeta clara antigua                    PASS acento del builder presente
PASS pestañas con data-tab                        PASS tres checkbox de opciones
PASS contenedor #editorTabs                       PASS lienzo GrapesJS
PASS textareas de CodeMirror (4)                  TODO CORRECTO
```

Comparación del bloque de scripts contra `HEAD`: diferencia única de un salto de línea final, sin
cambios de contenido.

Evidencia que **no** se pudo obtener: ver el resultado en un navegador real. El navegador headless no
arranca bajo el sandbox de la sesión.

## Next

Abrir `/admin/templates/{id}/edit` y comprobar que el marco oscuro se ve coherente con el builder y
que las pestañas y el lienzo siguen funcionando.
