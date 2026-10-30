---
author: Pepiuox
branch: main
status: in-progress
---
# Personalización visual de la web pública desde el módulo de configuración

## Intent

El sitio público tenía su identidad visual escrita a mano dentro de `views/public/layout.php`:
841 líneas con un `<style>` embebido, la marca textual `bi-layers-fill` fija y **ningún** favicon.
Cambiar colores, logo o favicon exigía editar código y volver a desplegar.

Cambio: el administrador gestiona desde `views/settings/index.php` los logotipos (10 áreas), el
favicon (4 resoluciones), el tema, 16 colores y CSS adicional de la web pública, sin tocar código
fuente. Todo se guarda en la tabla `settings` que el proyecto ya usaba y se aplica sólo al frontend
público.

Vale la pena porque la identidad visual deja de ser deuda de código y pasa a ser configuración
auditada, reversible y sin despliegue.

## Scope

Dentro:
- `app/Services/PublicThemeService.php`: lectura única del grupo `public` con caché de petición,
  valores por defecto en código, presets de tema, generación del CSS y del `<head>` de favicons.
- `app/Services/ImageUploadValidator.php`, `app/Services/SvgSanitizer.php`,
  `app/Services/SettingsAssetService.php`: subida segura de logos y favicons.
- `app/Controllers/PublicThemeController.php`: rutas públicas `/public-theme.css` y `/site.webmanifest`.
- `app/Controllers/SettingsController.php`: acciones `updatePublic`, `uploadAsset`, `deleteAsset`,
  `resetPublic`. Se corrige además `SettingService::clearCache()`, que no existía y hacía que
  guardar configuración mostrara error aunque los datos sí se guardaban.
- `app/Helpers/public_settings.php`: `getPublicSettings()`, expuesta desde `functions.php`.
- `views/settings/index.php`: 7 pestañas nuevas junto a las 4 existentes (General, SEO, Cache, Correo).
- `views/public/layout.php`: `body.public-site`, favicons, CSS dinámico, logo responsive con `picture`.
- `db/migrations/2026_10_26_public_visual_settings.*.sql`: valores por defecto del grupo `public`.
- `public/assets/js/settings-public.js` y `public/assets/css/settings-public.css`: sólo se cargan en
  la página de configuración.

Fuera:
- `public/assets/css/maker.css` y el layout del panel: **no se tocan**. El panel conserva su
  identidad visual; los widgets nuevos se dibujan con clases existentes y estilos con ámbito
  `.ppf-*` incluidos en la propia vista.
- `app/Services/AssetUploadService.php`: no se refactoriza. Su ruta es la del editor visual y
  funciona; `ImageUploadValidator` replica las reglas en vez de reescribir esa ruta (fail-safe
  sobre DRY, ver ADR en `docs/PUBLIC_THEME.md`).
- El `<style>` embebido del layout público se conserva como capa base; el CSS dinámico se carga
  **después** y gana por especificidad (`body.public-site`).
- Logo de sidebar y de menús multinivel: se persisten y se exponen por helper, pero la vista pública
  actual no tiene esos elementos (ver "Limitaciones" en `docs/PUBLIC_THEME.md`).

## Checklist

- [x] Documento FTD escrito antes del primer cambio — comprobado: este archivo existe.
- [x] `php -l` limpio en todos los archivos nuevos y modificados — comprobado: 13 archivos, ninguna salida de error.
- [x] Migración aplicada con `db/migrations/apply.php` e idempotente al relanzarla — comprobado: primera pasada «1 aplicada(s)», segunda «1 aplicada(s), 0 omitida(s)» sin error.
- [x] `GET /public-theme.css` devuelve las 16 variables `--public-*` y 304 con `If-None-Match` — comprobado: 200 con `Content-Type: text/css` y `ETag`; segundo GET con el ETag devuelve 304.
- [x] El HTML del panel no contiene `--public-` ni `/public-theme.css` — comprobado: bloque M de `tests/public-theme/e2e.ps1` sobre el HTML servido de `/admin/settings`.
- [x] Subida rechazada para archivo con MIME falsificado y para SVG con `<script>` — comprobado: bloques B y C de e2e (el SVG guardado no contiene `<script>` ni `onmouseover`).
- [x] Favicon eliminado desaparece del `<head>` público — comprobado: bloque D + K de e2e.
- [x] Permiso: POST sin `settings.manage` responde 403 — comprobado: bloque H de e2e con sesión de rol Manager.
- [x] Commit acotado a los archivos de esta funcionalidad — comprobado: commits `5e00390`, `04e6e95`, `f84dc22` y el de esta corrección, siempre con `git commit -- <rutas>` para no arrastrar cambios ajenos.

## Incidencia: 404 al subir imágenes (2026-10-30)

Síntoma reportado por el usuario: al subir un logo, «404 La página solicitada no
existe».

Diagnóstico contra el Apache real del proyecto (`localhost:170`, Apache 2.4.68):

```
POST /admin/settings/public/asset  →  301 → /asset  →  404
```

Dos causas independientes, cada una suficiente para romperlo:

1. `.htaccess` raíz: la regla que elimina `/public/` de la URL no estaba anclada
   al inicio, así que mordía cualquier ruta con un segmento `public` en medio.
   Corregido con `RewriteCond %{THE_REQUEST} ^[A-Z]+\s/+public/` +
   `RewriteRule ^public/?(.*)$ /$1`.
2. `Request::uri()`: recortaba el directorio base del script comparando texto,
   de modo que `/public-theme.css` se convertía en `/-theme.css` (el servidor
   embebido de las pruebas no lo reproducía porque su `SCRIPT_NAME` es otro).
   Corregido comparando por segmento. Afectaba también a la hoja de estilos
   pública, que en Apache devolvía 404.

Además se renombraron los endpoints de escritura a `/admin/settings/brand*` para
no usar `public` como segmento y esquivar los 301 que el navegador pudiera tener
cacheados, y la interfaz anuncia ahora el tope de subida real
(`min(propio, upload_max_filesize, post_max_size)`; en este entorno PHP limita a
2 MB).

Verificado en Apache tras el arreglo: `/public-theme.css` → 200 `text/css` con
las 16 variables; `/site.webmanifest` → 200; los cuatro POST de `brand` → 302 a
`/login` sin sesión (antes 301 → `/asset`).

## Resultado de las pruebas

| Suite | Comando | Resultado real |
| --- | --- | --- |
| Servicio | `php tests/public-theme/service-test.php` | 97 pasan, 0 fallan |
| Extremo a extremo | `./tests/public-theme/e2e.ps1` con servidor embebido | 65 pasan, 0 fallan |

Ambas suites son idempotentes y limpian lo que escriben: se ejecutaron dos veces
seguidas con el mismo resultado y sin dejar archivos ni claves de prueba.

## Estado

**VALIDADO CON LIMITACIONES.** Limitaciones en `docs/PUBLIC_THEME.md` §7
(logo de sidebar y multinivel sin consumidor en el layout actual, `<picture>` con
una sola `<source>` ganadora, `scopeCss()` de mejor esfuerzo, SVG sin `<style>`
interno). Pendientes fuera de alcance: `debug=true` en `config/config.php`, rate
limit en la subida y CSP del sitio público.
