---
author: Pepiuox
branch: main
status: in-progress
---
# La página editada daba 404 en el sitio público, y las URLs llevaban `/public/`

> Continuación de [builder-guardado-e-imagenes.md](builder-guardado-e-imagenes.md). Con el builder ya
> guardando e mostrando imágenes, faltaba que la página se pudiera ver fuera del panel.

## Intent

Dos cosas pedidas:

1. La página editada en el builder aparece como **404 / no existente** en la vista pública.
2. «Que todo sea visto **sin el `public/`**, que se vea directamente».

### Causa del 404: dos columnas para una sola verdad

La tabla `pages` guarda el estado editorial dos veces: `status enum('draft','published','archived')` y
`is_published tinyint(1)`. El frontend exige **las dos**:

```php
// app/Services/PageRenderer.php
"SELECT * FROM pages WHERE slug = ? AND is_published = ? AND status = ?"
```

El builder tiene un único control de publicación (el select «Estado») y solo enviaba `status`. El
`is_published` se quedaba con el valor anterior (`0`), así que la página quedaba en un estado
contradictorio: `status=published`, `is_published=0`. Estado real de la base antes del arreglo:

```
id=4 slug=portafolio status=published is_published=0   ← la que el usuario estaba editando
id=6 slug=vistas     status=published is_published=0   ← también enlazada desde el menú público
```

Las demás (1, 2, 3, 5, 7) tenían `is_published=1` y por eso el resto del sitio sí se veía.

### Causa de las URLs con `/public/`: `BASE_URL` incluía un segmento interno

`config/paths.php` deriva `BASE_URL` de `dirname($_SERVER['SCRIPT_NAME'])`. Con el `.htaccess` de la
raíz reescribiendo todo hacia `public/`, `SCRIPT_NAME` es `/public/index.php` y el resultado era
`http://localhost:170/public`. Ese segmento no lo escribe nadie en el navegador: es un detalle interno
del despliegue, pero se filtraba a todos los enlaces, assets, formularios y al sitemap.

## Scope

Dentro:

- `views/pages/builder.php`: campo oculto `is_published` en el formulario de guardado, derivado del
  select «Estado» (publicado → `1`, resto → `0`). El builder sigue teniendo un único control de
  publicación; ahora lo traduce a las dos columnas que exige el frontend.
- `config/paths.php`: si el subdirectorio base termina en `/public`, se recorta. `BASE_URL` pasa a ser
  la raíz del sitio. Un despliegue con document root en `public/` no se ve afectado (allí el base ya
  es vacío).
- `config/config.php`: `app_url` → `http://localhost:170` (lo usa el sitemap y el SEO; con el valor
  viejo seguía publicando `/public/`).
- Reconciliación de datos: `UPDATE pages SET is_published = 1 WHERE status = 'published' AND is_published = 0`
  → 2 filas (ids 4 y 6). Reversible con el valor contrario; no se tocó nada más.
- `public/sitemap.xml` regenerado con `SitemapService::generate()` (el fichero estático tiene prioridad
  sobre la ruta dinámica, así que quedaba con las URLs viejas).

Fuera (documentado, no tocado):

- Contenido guardado que aún referencia `http://localhost:170/public/uploads/…`: **2 `<img>`** dentro de
  la página 4. Siguen resolviendo (200) gracias a la regla de compatibilidad de `public/.htaccess`, pero
  el texto de esas URLs conserva `/public/`. Normalizarlas es editar contenido del usuario:
  `UPDATE pages SET content_html = REPLACE(content_html, '/public/uploads/', '/uploads/')`.
- `views/profile/index.php:58` comprueba avatares en `ROOT_PATH . '/public/uploads/'`, una carpeta que no
  existe: ese `is_file()` siempre es falso y el avatar del perfil cae siempre al fallback.
- `PageRenderer::optimizeImages()` emite `<img … /> loading="lazy">` (la barra del cierre se cuela antes
  de los atributos añadidos). Los navegadores lo toleran; es cosmético.

## Checklist

- [x] `/portafolio` responde 200 con el contenido del builder — comprobado con `curl` (28 632 B, contiene
      el bloque `id="ibhfh"` y el `<title>Portafolio</title>`).
- [x] El guardado desde el builder deja la página visible — comprobado de extremo a extremo: payload del
      builder (`status=published`, `is_published=1`) → router real → `{"success":true}` → `GET /zz-pub-…`
      → **200** con el marcador del contenido y **sin** referencias a `/public`. Página temporal borrada.
- [x] Las URLs generadas ya no llevan `/public` — comprobado en el HTML del builder: `saveUrl`,
      `assetsUrl`, `uploadUrl`, `action` del formulario y `baseUrl` de comentarios.
- [x] El menú público enlaza limpio — comprobado: `http://localhost:170/inicio`, `/portafolio`,
      `/quienes-somos`, `/proyectos`, `/vistas`, `/demo-web`.
- [x] El sitemap publica URLs limpias — comprobado tras regenerarlo: `<loc>http://localhost:170/portafolio</loc>`.
- [x] Lo que antes funcionaba sigue funcionando — comprobado: `/` 200, `/assets/css/builder.css` 200,
      `/uploads/…png` 200, `/login` 200, `/admin/pages/4/builder` → 302 a `http://localhost:170/login`
      (sin `/public`), `/no-existe` → 404 real.
- [x] Sintaxis válida — `php -l` en `config/paths.php`, `config/config.php` y `views/pages/builder.php`;
      `node --check` sobre el `<script>` emitido por el builder.
- [ ] Pulsación real en el navegador: no ejecutada (sin sesión interactiva en esta sesión).

## Evidence

Antes / después, con `curl -o NUL -w '%{http_code}'`:

```
ANTES                                   DESPUÉS
404  /portafolio                        200  /portafolio
404  /public/portafolio                 200  /public/portafolio
404  /vistas                            200  /vistas
302  /admin/… → …/public/login          302  /admin/… → http://localhost:170/login
→  sitemap: …/public/portafolio         →  sitemap: …/portafolio
```

Reconciliación de datos (una sola vez, idempotente):

```
ANTES:   id=4 portafolio status=published is_published=0
         id=6 vistas     status=published is_published=0
filas actualizadas: 2
DESPUÉS: las 7 páginas quedan con is_published=1 (todas tienen status=published)
```

Guardado real y vista pública, de extremo a extremo:

```
{"success":true,"message":"Guardado correctamente"}
temp page: id=11 slug=zz-pub-4bc23e
GET /zz-pub-4bc23e -> 200 26958
contiene marcador: True
contiene /public: False
```

## Lessons (autoaprendizaje)

1. **Dos columnas, una verdad: la que no escribe el formulario acaba desincronizada.** `status` e
   `is_published` se leen con `AND`, así que cualquier escritor que solo actualice una rompe la lectura
   sin error visible: 404 silencioso. Cuando dos campos describen el mismo hecho, o hay dueño único o
   hay bug.
2. **Un 404 del frontend no siempre es una ruta rota.** Aquí la ruta y el slug eran correctos: fallaba
   el `WHERE`. Antes de tocar el router, mirar los flags de la fila.
3. **`BASE_URL` derivada de `SCRIPT_NAME` mete en las URLs la carpeta del punto de entrada.** Si el
   despliegue reescribe desde la raíz, ese segmento hay que recortarlo explícitamente.
4. **Los ficheros estáticos ganan a las rutas.** `public/sitemap.xml` tapaba a `PublicController::sitemap()`;
   cambiar el generador no cambia lo que se sirve hasta regenerar el archivo.
5. **Comprobar con `curl` después de cambiar un `.htaccess` o la configuración de rutas**: la página
   puede verse bien en una URL y seguir mal en la gemela (`/x` frente a `/public/x`).

## Next

1. Abrir `http://localhost:170/portafolio` en el navegador y confirmar la vista pública con sus imágenes.
2. Decidir si `is_published` se retira (dejando `status` como única verdad) o se mantiene: si se
   mantiene, el editor de metadatos y el builder ya escriben ambos campos, pero conviene un test que lo
   garantice.
3. Normalizar las URLs de contenido antiguas (`/public/uploads/` → `/uploads/`) si se quiere eliminar la
   regla de compatibilidad, y arreglar la comprobación de avatares de `views/profile/index.php:58`.
