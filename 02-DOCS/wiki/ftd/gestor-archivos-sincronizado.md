---
author: Pepiuox
branch: main
status: in-progress
---
# Gestor de archivos: lo que se sube a `uploads/` ya se ve en `/admin/files`

## 1. Diagnóstico

El gestor (`views/files/index.php` + `FilesController` + `FileManagerService`) listaba **solo la tabla `files`**,
mientras el editor visual escribe **solo en disco** (`uploads/`). Eran dos inventarios distintos del mismo
almacén:

| Comprobación | Antes |
|---|---|
| Filas en `files` | **3** (y las 3 con `file_path` absoluto de Windows, ninguna con archivo real en disco) |
| Archivos reales en `uploads/` | **21** (`.jpg`, `.png`, `.jpeg`, en raíz y en `2026/10/`) |
| Visibles en `/admin/files` | **0 útiles**: 1 fila viva apuntando a un archivo inexistente y 2 ya borradas |

Consecuencias concretas:

1. **Nada de lo subido desde el builder aparecía** en el gestor.
2. **La vista previa se componía mal**: `url('uploads/' . ltrim($f['file_path'], '/'))` con
   `file_path = C:\…\uploads\images\foto.png` da una URL imposible (`/uploads/C:%5C…`).
3. **`download()` y `hardDelete()`** comprobaban `is_file($file['file_path'])` con esa ruta absoluta: nunca
   encontraban el archivo (404 al descargar, y el borrado físico no borraba nada).
4. **El propio `upload()`** guardaba la ruta **absoluta** en la base de datos: cada subida nueva generaba otra
   fila rota.
5. **El listado filtraba por propietario** (`user_id`): un administrador no veía los archivos de otro usuario,
   y los importados no tenían dueño claro.
6. **Siete rutas declaradas sin método** en el controlador (`/admin/files/search`, `/stats`, `/editor`,
   `/{id}/hard-delete`, `/{id}/metadata` GET, `/share/{id}/revoke`): un 500 esperando a que alguien las abriera.

## 2. Cambios

### Rutas de archivo con un solo criterio

`FileManagerService` gana cuatro métodos y todo el módulo pasa por ellos:

- `relativePath($stored)` — normaliza; acepta rutas heredadas absolutas (`C:\…\uploads\x.png`) y rechaza `..`.
- `absolutePath($stored)` — ruta real dentro de `uploads/`.
- `fileExists($stored)` / `urlFor($stored)` — existencia y URL pública (`public/uploads.php` sirve el archivo).

`upload()` y `uploadBase64()` **guardan la ruta relativa** (`images/foto.png`) y siguen usando la absoluta solo
para escribir en disco.

### Sincronización disco → tabla

`syncFromDisk()` (idempotente):

1. Normaliza las rutas heredadas a relativas.
2. Marca como borrados (`deleted_at`) los registros cuyo archivo ya no existe.
3. Registra los archivos que están en disco y no estaban en la tabla (MIME con `finfo`, categoría, tamaño,
   dimensiones de imagen).

Se lanza desde el botón **«Sincronizar con disco»** (`POST /admin/files/sync`, permiso `files.upload`).

### Gestor más útil

- Quien tiene `files.manage` (Super admin incluido) ve **todos** los archivos, puede descargarlos y borrarlos;
  el resto sigue viendo solo los suyos. `getUserFiles()` acepta `null` para «todos».
- **Buscador por nombre** (`?q=…`) junto a los filtros de categoría, más enlace a la búsqueda avanzada.
- **Contadores** en la cabecera: en el gestor, en `uploads/`, y cuántos registros no tienen archivo.
- Aviso «sin archivo» en la tarjeta cuando el registro apunta a un archivo que ya no está.
- Título con la ruta relativa (para saber de qué carpeta sale cada archivo).
- Rutas muertas eliminadas del router.

## 3. Pruebas (HTTP real con sesión y CSRF)

```
GET  /admin/files                       21 tarjetas · 42 <img> · 0 avisos «sin archivo»
GET  /admin/files?q=anticuchos          3 resultados
GET  /admin/files?q=zzzz                0 resultados
GET  /admin/files?category=image        21 resultados
POST /admin/files/sync                  «Sincronizado: 0 registrado(s), 0 ruta(s) normalizada(s), 2 sin archivo»
                                        (la primera vez: 20 registrados, 1 normalizada, 2 sin archivo)
GET  /admin/files/19/download           200 · 48 078 bytes · image/jpeg      (archivo de otro usuario)
POST /admin/files/upload (PNG)          200 · aparece como tarjeta 22
   file_path guardado: images/6ade476643479_…png   (relativo) · preview 200 image/png
POST /admin/files/24/delete             302 · la tarjeta desaparece · deleted_at con fecha
```

Datos de prueba borrados al terminar (2 filas y 2 PNG de 70 bytes); quedan las 21 activas + 2 registros
heredados marcados como borrados.

## 4. Lecciones (autoaprendizaje)

1. **Dos inventarios del mismo almacén siempre divergen.** El disco no es la base de datos: hace falta una
   operación de reconciliación explícita y una sola forma de resolver rutas.
2. **En la base de datos se guarda la ruta relativa**, nunca la absoluta: una ruta con `C:\…` deja de ser
   válida en cuanto cambia la máquina, el usuario o el sistema operativo.
3. **Un `is_file()` sobre un valor que no es una ruta fiable falla en silencio**: descarga 404, borrado que no
   borra, vista previa rota. Mejor un único `absolutePath()` con guarda anti-traversal.
4. **Rutas declaradas sin método** no dan error hasta que alguien las visita; comparar rutas y métodos del
   controlador es una revisión barata.
5. **Los permisos también son parte del inventario**: si el almacén es compartido, el listado no puede depender
   del propietario de cada fila.

## 5. Pendiente

- **Borrado definitivo desde la interfaz**: el servicio tiene `hardDelete()` (borra archivo y fila) pero no hay
  botón ni ruta. La papelera real necesitaría una vista de «borrados».
- Los 2 registros heredados sin archivo quedan como borrados (reversibles). Si se quieren eliminar del todo,
  hay que hacerlo con `hardDelete()`.
- El nombre original de lo subido desde el editor visual se perdió (el asset guarda un nombre normalizado);
  las filas importadas usan el nombre del archivo como `original_name`.
