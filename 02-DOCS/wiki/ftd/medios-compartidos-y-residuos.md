---
author: Pepiuox
branch: main
status: in-progress
---
# Cierre de pendientes: medios compartidos, URLs antiguas y el avatar del perfil

> Cuatro cosas que quedaban apuntadas. Por el camino, la prueba de extremo a extremo destapó una
> regresión que yo mismo había introducido al extraer el servicio de subida.

## 1. Subir imágenes desde el editor de plantillas (paridad con el builder)

Antes: la subida vivía dentro de `PagesController::uploadAsset()` con dos helpers privados
(`uniqueAssetName`, `assetDimensions`), atada a una página. El editor de plantillas solo podía **ver** la
biblioteca.

Ahora:

- `app/Services/AssetUploadService.php` (**nuevo**): validación y guardado en un solo sitio. Extensiones
  de la lista blanca, máximo 5 MB, MIME real con `finfo`, nombre normalizado con sufijo ante colisión,
  guardado en `UPLOADS_PATH/<año>/<mes>`, dimensiones (incluye SVG por `viewBox`).
- `PagesController::uploadAsset()` queda en permiso + forma de la respuesta; `getExistingAssets()` usa
  `dimensionsOf()` del servicio para lo que ya está en disco.
- `TemplatesController::uploadAsset()` + ruta `POST /admin/templates/{id}/builder/upload` (permiso
  `templates.edit`, 404 si la plantilla no existe).
- `views/templates/edit.php`: el gestor de assets del lienzo ya sube. En una plantilla nueva la subida se
  desactiva (no hay id en la ruta) y la ayuda del panel lo dice.

## 2. URLs antiguas `/public/uploads/` dentro del contenido

Había 20 referencias en el contenido guardado de dos páginas (`content_html` y `grapesjs_data`). Se
normalizaron a `/uploads/…`, con respaldo previo en `storage/backups/urls_public_uploads_<fecha>.json`:

```
ANTES:   pages.content_html 2 filas / 3 ocurrencias · pages.grapesjs_data 2 filas / 17 ocurrencias
         templates.*: 0
DESPUÉS: 0 filas con /public/uploads/ (páginas y plantillas), 4 filas actualizadas
```

`page_versions` no se tocó: es histórico y no debe reescribirse.

## 3. El avatar del perfil nunca se veía

`views/profile/index.php` comprobaba el archivo en `ROOT_PATH . '/public/uploads/' . $avatar` — una
carpeta que no existe. Resultado: `is_file()` siempre falso y el perfil mostraba la inicial aunque el
usuario tuviera foto. `handleAvatar()` guarda en `UPLOADS_PATH/avatars/…`.

Arreglado: la URL se calcula solo si el archivo existe de verdad bajo `UPLOADS_PATH`, y la plantilla
comprueba `$avatarUrl`. Probado con un PNG real: el `<img>` aparece con
`http://localhost:170/uploads/avatars/zz_test_avatar.png` y ese URL responde `200 image/png`.

## 4. La regresión que encontró la prueba

Al mover los helpers al servicio quité `assetDimensions()` de `PagesController`, pero
`getExistingAssets()` seguía llamándolo:

```
GET /admin/pages/13/builder → 500
Call to undefined method App\Controllers\PagesController::assetDimensions()
```

`php -l` no lo detecta (es un error en tiempo de ejecución) y el render por CLI anterior sí funcionaba
porque ese camino no pasa por el listado de assets. Lo destapó el `curl` real al builder de una página
nueva. Corregido usando `AssetUploadService::dimensionsOf()` (ahora público).

## Verificación de extremo a extremo (HTTP real, no simulada)

Se creó un usuario temporal con contraseña conocida, se hizo login por HTTP con CSRF y se usó esa sesión:

```
POST /admin/templates/4/builder/upload   {"success":true,"data":[{src: …/uploads/2026/10/zz_upload_test.png,
                                          name: zz_upload_test.png, width: 8, height: 8, size: 70}]}
POST /admin/pages/4/builder/upload      {"success":true,"data":[{src: …/uploads/2026/10/zz_upload_test-1.png}]}
                                          ← el sufijo -1 demuestra la unicidad de nombre
POST …/upload (sin archivo)             {"success":false,"errors":["No se recibieron archivos"]}
POST …/upload (un .txt)                 {"success":true,"data":[],"errors":["Tipo no permitido: zz_nota.txt"]}
GET  /uploads/2026/10/…png              200 image/png
POST /admin/pages/13 (payload builder)  {"success":true,"message":"Guardado correctamente"} → GET /zz-e2e-http → 200 con el contenido
POST /admin/templates/16               {"success":true,"message":"Plantilla guardada"}
POST /admin/templates/16/versions      {"success":true,"version_id":5}
GET  /admin/templates/16/versions      200 con la tabla de versiones
```

Datos de prueba limpiados después: 1 página, 1 plantilla, 2 versiones, 1 usuario, 2 imágenes y 2
registros de auditoría. Las páginas y plantillas reales del usuario no se tocaron.

## Lessons (autoaprendizaje)

1. **Refactorizar un helper compartido exige buscar también las llamadas**: `php -l` no ve métodos que ya
   no existen; solo la ejecución real del camino afectado lo hace. Tras extraer lógica, `grep` del nombre
   del método en todo el repo.
2. **`curl -F` interpreta `<` y `@` al principio del valor** como «leer de archivo». Para mandar HTML
   literal hay que usar `--form-string`. Perdí dos intentos por eso.
3. **`$pid` es una variable automática de solo lectura en PowerShell**: renombrar antes de asignar.
4. **`chmod($file, 0644)` en Windows deja el archivo en solo lectura** y luego no se puede borrar sin
   quitar el atributo. Con el sandbox de la sesión, además, hubo que escalar el permiso para eliminarlo.
5. **Una migración de datos necesita respaldo del contenido afectado**, no del esquema: el JSON previo con
   las filas originales permitió comprobar el antes/después.

## Next

- Pendiente por decisión, no por error: **autoguardado en plantillas** (necesita tabla propia; hoy el
  builder de páginas lo tiene vía `page_autosave`).
- `views/pages/editor.php` sigue siendo una copia antigua del builder y ninguna ruta la usa.
