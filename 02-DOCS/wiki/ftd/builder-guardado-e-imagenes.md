---
author: Pepiuox
branch: main
status: in-progress
---
# Builder: las imágenes subidas no se veían y el guardado respondía con error

> Corrección quirúrgica sobre el builder existente. No se reescribió nada: cinco archivos, ningún
> cambio de esquema, ninguna migración ejecutada y ningún dato de usuario movido de sitio.

## Intent

Dos síntomas en `http://localhost:170/public/admin/pages/4/builder`:

1. Las imágenes recién subidas no aparecen en el visor de recursos (y, por tanto, tampoco en el lienzo).
2. «No guarda lo editado».

La investigación encontró **cuatro defectos independientes**; los dos primeros explican el síntoma 2,
los otros dos el síntoma 1.

### Defecto 1 — `AuditService::log()` con los argumentos desordenados (el 500 del guardado)

`AuditService::log()` está declarado como `log(?int $userId, string $action, ?string $details, ?string $context)`.
`PagesController::update()` lo llamaba con la acción en primer lugar:

```php
AuditService::log('page_updated', "Página actualizada: …", 'pages', $id);
```

PHP no convierte un `string` a `?int` en un parámetro tipado: lanza `TypeError`. El `Router` captura la
excepción y responde **500** `{"success":false,"error":"Error interno del servidor"}`.

Lo importante: el `TypeError` ocurría **después** de `PageService::save()`, que ya había hecho commit.
El contenido sí se estaba guardando; lo que fallaba era la respuesta. Prueba: al momento del
diagnóstico existían dos versiones `page_versions` de la página 4 con nota «Edición desde builder
visual» creadas minutos antes (`2026-10-25 01:26:02` y `01:26:29`) — el usuario había pulsado Guardar y
el builder le había contestado con un error. Mismo error en `store()` y `delete()`.

### Defecto 2 — el autoguardado insertaba una columna inexistente

`PagesController::autosave()` hacía `INSERT INTO page_autosave (…, grapesjs_data)`. La tabla real
(`SHOW COLUMNS FROM page_autosave` contra `web_editor_db`) no tiene `grapesjs_data`: el `INSERT` fallaba
siempre con `SQLSTATE[42S22] Unknown column 'grapesjs_data'` y el autoguardado nunca guardó una sola
fila (`SELECT COUNT(*) FROM page_autosave` → 0).

### Defecto 3 — las URLs de las imágenes no las servía nadie

`uploads/` vive en la raíz del proyecto, **fuera del document root**, pero `url('uploads/…')` genera
`<BASE_URL>/uploads/…` y `BASE_URL` es `http://localhost:170/public` (se deriva de `SCRIPT_NAME`, que
es `/public/index.php`). Comprobado con `curl`:

```
404  http://localhost:170/public/uploads/1f8af182bf1a5b5621058447ccbe6c2f.png
404  http://localhost:170/uploads/1f8af182bf1a5b5621058447ccbe6c2f.png
```

No existía ni carpeta `public/uploads/` ni regla que resolviera esa ruta. Todas las miniaturas del
gestor de recursos y todos los `<img>` insertados en el lienzo apuntaban a un 404.

### Defecto 4 — ruta relativa mal calculada en Windows

`getExistingAssets()` recortaba el prefijo del directorio así:

```php
str_replace($uploadDir . '/', '', $file->getPathname());
```

`getPathname()` devuelve separadores nativos (`\` en Windows) y `$uploadDir` usa `/`, así que el
recorte no ocurría nunca para los ficheros en subcarpetas y la URL salía como
`uploads/C:\…\uploads\2026\10\foto.jpg`. Los ficheros de la raíz de `uploads/` se salvaban por
casualidad; los de `2026/10/…` no.

## Scope

Dentro:

- `app/Controllers/PagesController.php`: `store()`, `update()` y `delete()` llaman a
  `AuditService::log()` con la firma declarada; `autosave()` inserta solo las columnas que existen;
  `getAutosave()` usa `?? null` para la columna ausente; `getExistingAssets()` normaliza separadores
  antes de calcular la ruta relativa.
- `public/uploads.php` (**nuevo**): sirve los ficheros de `uploads/` validando extensión, rechazando
  `..`/bytes nulos/barras invertidas y comprobando con `realpath()` que el destino sigue dentro de
  `UPLOADS_PATH`. Añade `nosniff`, `ETag`, `Last-Modified`, caché de 24 h y, para SVG, CSP `sandbox`.
- `.htaccess` (raíz) y `public/.htaccess`: `RewriteRule ^uploads/(.+)$ … uploads.php`, que cubre tanto
  `/uploads/…` como `/public/uploads/…`.
- `views/pages/builder.php`: `savePage()` deja de convertir un 4xx/5xx en «guardado correcto» y
  `performAutoSave()` deja de tratar un 500 como respuesta válida.

Fuera (documentado, no tocado):

- `audit_logs` no tiene las columnas que `AuditService::logInstance()` inserta (`details`, `context`) y
  su `action` es `enum('INSERT','UPDATE','DELETE','SELECT')`. La llamada ya no rompe la petición, pero
  la fila de auditoría se pierde (el `try/catch` del servicio la registra en `error_log`). Arreglarlo
  exige decidir si el destino es `audit_logs` (auditoría de cambios de fila) o `activity_logs` (donde
  ya escribe el dueño canónico `ActivityLoggerService`): es una decisión de arquitectura, no un parche.
- Los otros controladores con la misma llamada desordenada (`EditorController`, `CollaborationController`,
  `ComponentController`, `BackupsController`, `LoginAttemptsController`, `ApiManageController`) tienen
  el mismo fallo latente. No se tocaron para no ampliar el alcance.
- Contenido histórico con `src="http://localhost:130/uploads/…"`: ese host/puerto ya no existe. Es dato
  guardado, no código; se corrige reedintando esas imágenes.
- No se ejecutó ninguna migración: no se añadió `page_autosave.grapesjs_data`.

## Checklist

- [x] Guardado responde éxito — comprobado: `POST /admin/pages/{id}` simulado por el router real devuelve
      `{"success":true,"message":"Guardado correctamente"}` (antes: 500).
- [x] El contenido y el proyecto del lienzo se persisten — comprobado: marcador en `content_html` = SÍ,
      `grapesjs_data` con el proyecto = SÍ, 1 versión creada.
- [x] El autoguardado guarda una fila — comprobado: `page_autosave` recibe `content_html` y `content_css`.
- [x] Las imágenes se sirven por HTTP — comprobado: `200 image/png` y `200 image/jpeg` en
      `/uploads/…` y en `/public/uploads/…` (plano y en subcarpeta).
- [x] La ruta inexistente da 404 y no filtra — comprobado: 404 en `…/nope.jpg`; 404 en traversal
      codificado (`%2e%2e%2f`); 403 de Apache en traversal literal.
- [x] La lista de assets del builder ya trae URLs limpias — comprobado: 19 assets, ninguna con `\` ni `C:`.
- [x] JS del builder válido — comprobado: `node --check` sobre el `<script>` emitido (49 388 bytes).
- [x] Sintaxis PHP válida — comprobado: `php -l` en los tres archivos PHP tocados.
- [ ] Interacción en navegador real (subir imagen y verla en el visor): no ejecutada, ver «Evidence».

## Evidence

Simulación de extremo a extremo del guardado por el router real (página temporal, borrada al final):

```
temp page = 9
{"success":true,"message":"Guardado correctamente"}
save: title=ZZ E2E marker=SI grapes=SI
versions=1
cleanup OK
```

Antes de la corrección, la misma simulación devolvía `{"success":false,"error":"Error interno del servidor"}`
con `[Router] App\Services\AuditService::log(): Argument #1 ($userId) must be of type ?int, string given`.

Autoguardado:

```
{"success":true,"message":"Autoguardado"}
autosave OK html=<p>AUTO-MARKER</p> css=.auto{}
```

Esquema real que desmintió el código:

```
page_autosave: id, page_id, user_id, content_html, content_css, content_js, content_php, created_at
   → no existe grapesjs_data
audit_logs:    id, table_name, record_id, action(enum INSERT/UPDATE/DELETE/SELECT), old_data,
               new_data, user_id, ip_address, user_agent, created_at
   → no existen details ni context
activity_logs: id, user_id, action(varchar), details, ip_address, user_agent, created_at
```

Imágenes por HTTP (`curl -o NUL -w '%{http_code} %{content_type}'`):

```
200 image/png   /uploads/img_6a72adaed150b7.31479953.png
200 image/png   /public/uploads/img_6a72adaed150b7.31479953.png
200 image/jpeg  /public/uploads/2026/10/anticuchos-de-corazon-1.jpg
200 image/jpeg  /uploads/2026/10/Causa-Limena-02-800x530.jpg
404 text/plain  /public/uploads/2026/10/nope.jpg
404             /uploads/%2e%2e%2fconfig%2fconfig.php
```

Evidencia que **no** se pudo obtener: sesión de navegador autenticada. El guardado se verificó
invocando el router real con la MISMA petición POST que arma `savePage()` (mismo CSRF, mismos campos,
sesión de `user_id = 1`), y la vista renderizada se comprobó por su HTML y su JS. Falta la pulsación
física del botón.

## Lessons (autoaprendizaje)

1. **Un 500 después del commit se lee como «no guarda».** Cuando el usuario dice «no guarda», lo primero
   que hay que mirar es si la fila cambió (versiones, `updated_at`). Aquí el dato estaba guardado y el
   mensaje de error era el que mentía. Pregunta útil: ¿existe una versión nueva en `page_versions`?
2. **Un `catch (Throwable)` que devuelve `false` esconde la causa.** `PageService::save()` hace eso y solo
   deja rastro en `error_log`. Para diagnosticar fue decisivo reproducir la transacción paso a paso en
   CLI en vez de leer el código.
3. **El esquema manda.** `SHOW COLUMNS` sobre la base real habría evitado los defectos 2 y 1: el código
   insertaba columnas que no existen y llamaba a un servicio con la tabla de otro.
4. **Windows y las rutas.** Cualquier `str_replace($dir . '/', …)` sobre `getPathname()` es un fallo
   esperando: normalizar `\` → `/` antes de comparar o recortar.
5. **`url()` arrastra `BASE_URL`, y `BASE_URL` arrastra `/public`.** Un fichero fuera del document root
   necesita una ruta de servicio explícita; si no, la URL que genera la aplicación no la resuelve nadie.
6. **Un `.htaccess` tiene contexto de directorio.** `^public/uploads/…` en la raíz no sirve para una
   petición que ya entra en `public/`; el patrón relativo (`^uploads/…` en `public/.htaccess`) sí. Se
   comprueba con `curl`, no leyendo la regla.

## Next

1. Abrir el builder, subir una imagen y confirmar que aparece la miniatura en el visor y que se puede
   arrastrar al lienzo.
2. Pulsar Guardar y verificar el toast de éxito y que al recargar se conserva lo editado.
3. Decidir el destino de la auditoría (`audit_logs` vs `activity_logs`) y, con esa decisión, corregir
   `AuditService::logInstance()` y los otros seis controladores con la llamada desordenada.
4. Si se quiere que el autoguardado restaure el lienzo, añadir `page_autosave.grapesjs_data` con una
   migración `up/down` y conectar `getAutosave()` en el arranque del builder.
