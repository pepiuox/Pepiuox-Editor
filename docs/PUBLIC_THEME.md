# Personalización visual de la web pública

Módulo que permite al administrador cambiar la identidad visual del **sitio
público** (logos, favicon, tema, colores y CSS adicional) sin tocar código, y
sin que el **panel de administración** cambie un píxel.

Estado: **implementado y verificado** (2026-10-26). 74 comprobaciones de servicio
y 45 de extremo a extremo en verde. Ver [tests/public-theme/README.md](../tests/public-theme/README.md).

---

## 1. Cómo se usa

`Configuración del Sistema` → pestañas nuevas:

| Pestaña | Qué hace |
| --- | --- |
| Identidad visual | Texto alternativo de los logotipos (accesibilidad) |
| Logotipos e iconos | 10 huecos: subir, previsualizar, reemplazar, eliminar |
| Favicon | 4 huecos (ICO, 16, 32, apple-touch) + `site.webmanifest` |
| Temas | 5 tarjetas: claro, oscuro, corporativo, minimalista, personalizado |
| Colores | 16 colores con selector y HEX, con indicador de contraste AA |
| CSS personalizado | Editor, activar/desactivar, restaurar, validación previa |
| Vista previa | Muestra en vivo + el sitio real en un iframe |

Permiso necesario: **`settings.manage`** (Super admin lo tiene por bypass).
Las cuatro pestañas anteriores (General, SEO, Cache, Correo) siguen ahí, con sus
mismos campos y nombres; ahora cada grupo tiene su propio botón de guardado.

---

## 2. Arquitectura

```
panel  ──POST /admin/settings/public────────┐
       ──POST /admin/settings/public/asset──┤
       ──POST /admin/settings/public/reset──┤
                                            ▼
                                 SettingsController  (permiso + CSRF + flash)
                                            │
                          ┌─────────────────┴──────────────────┐
                          ▼                                    ▼
                PublicThemeService                    SettingsAssetService
                (validación, persistencia,            (subida/borrado de archivos)
                 CSS, favicon, manifiesto)                      │
                          │                                     ├─ ImageUploadValidator
                          ▼                                     └─ SvgSanitizer
                     tabla settings (grupo `public`)
                          │
                          ▼
        GET /public-theme.css  ←──── views/public/layout.php
        GET /site.webmanifest        (único consumidor)
```

| Archivo | Responsabilidad |
| --- | --- |
| `app/Services/PublicThemeService.php` | Fuente de verdad: una consulta por petición, defaults en código, presets, generación de CSS, favicons, manifiesto, validación y acotado del CSS libre |
| `app/Services/SettingsAssetService.php` | Subida y borrado de logos/favicons: nombres generados, sustitución sin ventana de pérdida |
| `app/Services/ImageUploadValidator.php` | Extensión, tamaño, `is_uploaded_file`, MIME real, dimensiones, cabecera ICO |
| `app/Services/SvgSanitizer.php` | Lista blanca de elementos y atributos sobre SVG |
| `app/Controllers/PublicThemeController.php` | `/public-theme.css` con ETag y `/site.webmanifest` |
| `app/Controllers/SettingsController.php` | Acciones del panel, autorización y mensajes |
| `app/Helpers/public_settings.php` | `getPublicSettings()`, `public_logo()`, `public_favicon_tags()`… |
| `views/settings/index.php` | Las 11 pestañas |
| `views/public/layout.php` | 4 inserciones: favicons, hoja dinámica, `body.public-site`, logo con `picture` |
| `public/assets/js/settings-public.js` | Sólo se carga en esta pantalla del panel |

### Persistencia

Se reutiliza la tabla `settings` (`group`, `key`, `value`, `type`, `updated_at`,
`UNIQUE(group,key)`). **No hay cambio de esquema**: 34 filas nuevas en el grupo
`public`. La migración sólo siembra valores por defecto con
`ON DUPLICATE KEY UPDATE id = id`, así que relanzarla nunca pisa lo configurado.

Los valores por defecto viven **también en código** (`PublicThemeService::defaults()`):
si falta una fila o la base de datos no responde, el sitio público sigue
renderizándose con la paleta original en vez de romperse.

---

## 3. Decisión de arquitectura: el aislamiento

**Regla:** la personalización pública no puede alcanzar al panel.

Tres barreras, cada una suficiente por sí sola:

1. **Archivos separados.** El panel carga `public/assets/css/maker.css` desde
   `views/layouts/admin.php`. El sitio público carga `/public-theme.css` desde
   `views/public/layout.php`. Ninguno referencia al otro.
2. **Ámbito de CSS.** Todo lo generado cuelga de `body.public-site`, clase que
   sólo se aplica en el layout público. El CSS libre del administrador se
   prefija automáticamente (ver §5).
3. **Comprobación ejecutable.** La prueba de extremo a extremo pide
   `/admin/settings` con sesión y verifica que el HTML **no** contiene
   `public-theme.css` ni ninguna variable `--public-*`, y que sí conserva
   `maker.css` (bloque M de `e2e.ps1`).

**Cómo se verificó de verdad:** ver bloque M. No es una afirmación de diseño: es
una comprobación sobre el HTML servido por la aplicación.

---

## 4. Decisiones y alternativas descartadas (ADR resumido)

| Decisión | Alternativa descartada | Por qué |
| --- | --- | --- |
| Hoja de estilos dinámica en `/public-theme.css` con ETag | `<style>` embebido en el layout | Cacheable, 304 sin cuerpo, HTML más pequeño, y separable del panel |
| Reutilizar `settings` en lugar de una tabla nueva | `public_theme_settings` | El esquema ya daba exactamente lo necesario; una tabla nueva duplicaba el mecanismo |
| Sin `created_at` | Añadir la columna | Ningún consumidor la usa; YAGNI |
| Los 16 colores son la única fuente de verdad; el tema es sólo una etiqueta que rellena la interfaz | Guardar colores «virtuales» del preset y resolver en servidor | Un solo origen: lo que se guarda es lo que se aplica |
| `down` de la migración es un **no-op documentado** | Borrar las filas del grupo `public` | Borrar configuración de un administrador es destructivo; el botón «restablecer» es la vía explícita |
| `ImageUploadValidator` nuevo, sin refactorizar `AssetUploadService` | Extraer un validador común | `AssetUploadService` sirve al editor visual y funciona; refactorizarlo ponía en riesgo esa ruta. Duplicación de ~40 líneas, documentada |
| Sustitución de archivos en 3 pasos (escribir nuevo → guardar fila → borrar viejo) | Borrar y luego escribir | Evita la ventana en la que el sitio apunta a un archivo inexistente |
| Actualizar el archivo de favicon sólo si existe en disco | Emitir siempre las 5 etiquetas | Borrar un favicon lo quita del `<head>` sin dejar referencias rotas |
| Manifest con rutas relativas a la raíz | URLs absolutas con el host de la petición | Evita envenenar la caché compartida entre hosts (CWE-644) |

---

## 5. CSS personalizado: acotado automático

El administrador escribe CSS sin prefijo. Antes de publicarlo,
`PublicThemeService::scopeCss()` antepone `body.public-site` a cada selector de
primer nivel y desciende por `@media`, `@supports`, `@layer` y `@container`.
`:root`, `html`, `body` y `*` se sustituyen por el propio ámbito.
`@keyframes`, `@font-face` y `@page` se dejan intactos porque no admiten prefijo.

```css
/* el administrador escribe */      /* el navegador recibe */
.hero { color: red }                 body.public-site .hero { color: red }
@media (max-width:600px){.hero{}}    @media (max-width:600px){body.public-site .hero{}}
:root{--x:1}                         body.public-site{--x:1}
```

Es un transformador **de mejor esfuerzo, no un parser de CSS**: ante una
construcción que no entiende, la regla se publica como está y el navegador la
ignora. El modo de fallo es «no se aplica», nunca «se aplica global».

Antes de guardar se rechaza cualquier CSS que contenga `<` (podría cerrar la
etiqueta `style`), `@import`, `expression(`, `javascript:`, `vbscript:`,
`behavior:` o `-moz-binding`, y cualquier texto de más de 100 KB.

---

## 6. Seguridad

| Vector | Medida |
| --- | --- |
| Inyección SQL | 100 % consultas preparadas vía `Database`/`Setting::updateBatch` |
| Subida de archivo malicioso | Extensión en lista blanca por hueco, tamaño máximo (2 MB logo / 512 KB favicon), `is_uploaded_file`, MIME real con `finfo`, `getimagesize`, cabecera ICO |
| Nombre de archivo del usuario | Se ignora: `slot-<12 hex>.<ext>`, generado por el servidor |
| Path traversal | Patrón anclado `^settings/(logos|favicons)/[A-Za-z0-9][\w.-]{0,120}$` y comprobación de `..` antes de leer o borrar |
| Ejecución en el directorio de subidas | Los archivos viven fuera del document root y se sirven por `public/uploads.php`; además `uploads/.htaccess` desactiva intérpretes y deniega extensiones ejecutables |
| XSS vía SVG | Lista blanca de elementos/atributos; fuera `<script>`, `<style>`, `foreignObject`, `on*`, `javascript:`, `vbscript:`, `data:text/html`, `url(` en `style`, referencias externas; `DOCTYPE`/`ENTITY` rechazados (sin XXE) |
| XSS vía CSS | Rechazo de `<`, `@import`, `expression(`, `javascript:` + acotado bajo un ámbito |
| CSRF | `CSRF::guard()` en las 4 acciones de escritura, incluidas las que responden JSON |
| Autorización | `settings.manage` en lectura **y** escritura del módulo; lista blanca de grupos en `update()` |
| Divulgación | Respuestas genéricas al usuario; el detalle va a `error_log` |

### Hallazgos de la auditoría de seguridad y su estado

| ID | Hallazgo | Estado |
| --- | --- | --- |
| A-01 | `SettingsController::update()` no comprobaba `settings.manage` (mass assignment sobre `settings`) | **Corregido**: `guardManage()` + lista blanca de grupos |
| A-02 | `index()` no exigía permiso y reimprimía la contraseña SMTP | **Corregido**: `guardManage()` en lectura |
| A-03 | `<style>` dentro del SVG sin inspeccionar | **Corregido**: el elemento `<style>` se elimina y el atributo `style` se filtra |
| A-04 | CSS personalizado no acotado | **Corregido**: `scopeCss()` + texto de la interfaz ajustado |
| A-05 | Manifest con host de la petición | **Corregido**: rutas relativas a la raíz |
| A-06 | `uploads/` sin `.htaccess` propio | **Corregido**: `uploads/.htaccess` |
| A-07 | Filtro MIME laxo para SVG en el validador | **Mitigado**: el saneador exige raíz `<svg>` real; el validador sigue aceptando variantes de `finfo` a propósito |
| A-08 | `debug=true` en `config/config.php` | **Pendiente, fuera de alcance**: es configuración de entorno, no de este módulo |
| A-09 | Rate limit en la subida | **Pendiente**: recomendado con `RateLimitMiddleware` |
| A-10 | CSP del sitio público | **Pendiente**: requiere decidir política (`unsafe-inline` para el `<style>` actual) |

---

## 7. Limitaciones conocidas (honestas)

1. **Logo de sidebar y de menús multinivel**: se guardan y se sirven por
   `public_logo('logo_sidebar')` / `public_logo('logo_multinivel')`, pero el
   layout público actual **no tiene** barra lateral ni un hueco de logo dentro de
   los submenús, así que hoy no se ven en ninguna página. El dato queda listo
   para cuando exista ese componente.
2. **Variantes por fondo**: `logo_dark` y `logo_light` se eligen con
   `prefers-color-scheme`, que manda sobre las variantes por tamaño si ambas
   están definidas. Es una limitación de `<picture>` (una sola `<source>` gana).
3. **`scopeCss()` no es un parser**: CSS muy exótico (comas dentro de `:is()`,
   llaves dentro de `content`) puede quedar sin acotar o ignorado por el
   navegador.
4. **El SVG pierde su `<style>` interno** al sanearse. Un logotipo con estilos
   embebidos puede cambiar de aspecto; la interfaz avisa de lo eliminado.
5. **`uploads/` es escribible pero `is_writable()` devuelve `false`** en este
   entorno Windows (ACL heredada). La subida funciona; no se usa `is_writable()`
   como criterio en ningún sitio.
6. **Los archivos huérfanos no se recogen**: si se edita la tabla a mano, el
   archivo anterior queda en disco.

---

## 8. Cómo verificarlo tú mismo

```bash
# 1. Sintaxis
php -l app/Services/PublicThemeService.php   # y el resto de archivos tocados

# 2. Migración (idempotente: se puede relanzar)
php db/migrations/apply.php 2026_10_26_public_visual_settings.up.sql

# 3. Prueba de servicio (74 comprobaciones)
php tests/public-theme/service-test.php

# 4. Prueba HTTP (45 comprobaciones) — ver tests/public-theme/README.md
```

---

## 9. Trazabilidad requisito → código → prueba

| Requisito | Código | Prueba |
| --- | --- | --- |
| RF-01 Logos por área | `SettingsAssetService`, `PublicThemeService::LOGO_SLOTS` | e2e A, C, I · servicio 7 |
| RF-02 Vista previa | `views/settings/index.php` (pestaña Logotipos) | inspección visual |
| RF-03 Favicon + manifest | `PublicThemeService::faviconTags/manifest` | e2e D · servicio 9 |
| RF-04 Temas | `THEME_PRESETS`, `resetColors()` | e2e J · servicio 8 |
| RF-05 16 colores | `COLOR_LABELS`, `save()` | servicio 4, 8 |
| RF-06 Variables `--public-*` | `PublicThemeService::css()` | servicio 2 · e2e F |
| RF-07 CSS personalizado | `validateCustomCss()`, `scopeCss()` | servicio 5, 5.b · e2e L |
| RF-08 Pestañas sin perder las 4 | `views/settings/index.php` | e2e M (11 pestañas) |
| RF-09 `getPublicSettings()` con caché | `public_settings.php` | servicio 11 |
| RF-10 Panel intacto | separación de archivos + `body.public-site` | servicio 3 · e2e M |
| RF-11 Permiso + CSRF | `guardManage()`, `CSRF::guard()` | e2e H |
| RF-12 PDO preparado | `Setting::updateBatch` | revisión de seguridad A-01… |
| RNF-01 Aislamiento | §3 | e2e M |
| RNF-06 ETag/304 | `PublicThemeController::css()` | manual (curl, documentado) |
