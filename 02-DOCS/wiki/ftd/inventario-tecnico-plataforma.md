# Inventario técnico y funcional real — PepiuoX Editor

> **Alcance:** solo lectura de código y `SELECT` sobre la base de datos. No se modificó ningún archivo del proyecto salvo este documento.
> **Fecha del inventario:** sesión actual. Todo lo marcado **HECHO OBSERVADO** se comprobó leyendo el archivo citado o ejecutando una consulta; lo marcado **INFERENCIA** es conclusión razonada sin ejecución; lo marcado **RECOMENDACIÓN** es propuesta.
> **Entorno comprobado:** PHP 8.4 CLI, Node v26.5.0, MariaDB en `127.0.0.1:3306`, base `web_editor_db`.

---

## 1. Stack y arquitectura real

### 1.1 Stack

| Capa | Realidad observada | Evidencia |
|---|---|---|
| Lenguaje | PHP sin Composer (no hay `composer.json` ni `vendor/` utilizable), autoloader propio con `spl_autoload_register` | `index.php:16-24`, `public/index.php:21-42` |
| Tipado estricto | **Inconsistente**: `index.php`, `SettingsController`, `SiteSettings`, `PublicThemeService`, `PublicThemeController` llevan `declare(strict_types=1)`; `PublicController`, `PagesController`, `PageRenderer`, `Router` **no** | lectura directa de cada cabecera |
| Capas | `app/{Controllers(43),Models(24),Services(41),Core(12),Middleware(4),Helpers(2)}`, `views/` (70 `.php`, ~760 KB), `config/*.php`, `db/migrations` | conteo de archivos |
| BD | MariaDB `web_editor_db`: **52 tablas base + 2 vistas**, 0 rutinas, 4 triggers, 54 objetos en `SHOW TABLES` | `information_schema` + `SHOW TABLES` (SELECT) |
| Rutas | 224 declaraciones `$router->get/post/...` en `config/routes.php` (461 líneas) | conteo con `Select-String` |
| Frontend | Bootstrap 5 por CDN, sin build step, sin bundler, CSS/JS propios en `public/assets` | ver §5 |

### 1.2 Dos puntos de entrada (HECHO OBSERVADO)

- `index.php` (raíz, 135 líneas): autoloader PSR-4 estricto `App\` → `app/`, **sin** contenedor DI.
- `public/index.php` (156 líneas): mismo autoloader por prefijos + `require app/bootstrap.php` + `buildServiceContainer()` + `registerDependencies($router, $services)`.
- `.htaccess` de la raíz reescribe **todo** a `public/` (`RewriteRule ^((?!public/).*)$ public/$1 [L,NC]`, línea 79) y redirige `/public/...` a `/...` (línea 64-65).

**Conclusión (INFERENCIA fuerte):** el front controller efectivo en producción es **`public/index.php`**; `index.php` de la raíz queda inalcanzable por HTTP (salvo ejecución CLI o que el document root apunte a la raíz y Apache ignore el rewrite). Mantener dos entrypoints con dos autoloaders distintos es deuda activa: un cambio en uno no se prueba en el otro.

### 1.3 Cadena de una petición pública

`GET /` → `.htaccess` → `public/index.php` → `App::boot()` (sesión, autologin, i18n, zona horaria desde `settings`) → rutas → `PublicController::home` → `PageRenderer::getHomePage()` → `PageRenderer::buildPageHtml()` → `View::partial('public.layout')` → `views/public/layout.php`.

---

## 2. Módulos funcionales

**HECHO OBSERVADO:** 43 archivos en `app/Controllers` que declaran **37 clases distintas** (6 son variantes de `PagesController`, 2 de `PasswordController`). 33 clases están referenciadas por `config/routes.php`; **10 archivos son residuos muertos** (ver §8).

| Módulo | Qué hace | Controlador(es) | Modelo(s) / Servicio(s) | Vistas | Rutas (prefijo) |
|---|---|---|---|---|---|
| Dashboard | Panel de entrada | `DashboardController` | — | `dashboard/index.php` | `GET /admin` |
| Autenticación | Login, logout, remember-me, 2FA challenge | `AuthController` | `User`, `AuditService`, `NotificationService`, `TotpService` | `auth/login.php` | `/login`, `/logout`, `/2fa/challenge*` |
| Contraseña / registro | Alta, forgot, reset | `PasswordController` | `User`, `AuditService`, `NotificationService`, `MailService` | `auth/{register,forgot,reset}.php` | `/register`, `/forgot`, `/reset` |
| Perfil | Datos propios, cambio de clave, cierre de sesiones | `ProfileController` (+ `AuthController::closeOtherSessions`) | `User` | `profile/index.php` | `/admin/profile*` |
| Usuarios | CRUD, búsqueda, cambio de clave | `UsersController` | `User` | `users/{index,create,edit}.php` | `/admin/users*` |
| Usuarios baneados | Ban, bulk-ban, extensión, historial, cron de expiración | `BannedUsersController` | `BanService` | `users/{banned,ban_history,ban_global_history}.php` | `/admin/users/banned*`, `/cron/process-expired-bans` |
| Roles y permisos | CRUD de roles y matriz de permisos | `RolesController` | `Role`, `Permission` | `roles/{index,create,edit}.php` | `/admin/roles*` |
| Páginas | CRUD + builder visual (GrapesJS) + editor de código + assets + autosave | `PagesController` | `Page`, `PageModel`, `PageService`, `Template`, `MetaTag`, `AssetUploadService`, `MenuService` | `pages/{index,create,edit,builder,editor}.php` | `/admin/pages*` |
| Versiones de páginas | Historial, preview, restauración, borrado | `VersionsController` | `PageVersion`, `CacheService` | `pages/versions.php` | `/admin/pages/{id}/versions*` |
| Plantillas / builder | CRUD de plantillas + versiones + duplicado | `TemplatesController` | `TemplatesService`, `AssetUploadService` | `templates/*` | `/admin/templates*` |
| Menús | Menús, ítems, reordenado, sync automático, caché | `MenuController` | `MenuService`, `CacheService` | `menus/{index,edit}.php` | `/admin/menus*`, `/api/menu*` |
| SEO | Metas globales, redirecciones, sitemap, análisis, competidor, generador de meta | `SeoController` | `SeoService`, `SeoCompetitorService`, `MetaDescriptionGenerator`, `SitemapService`, `SettingService`, `Page`, `MetaTag` | `seo/*` (6 vistas) | `/admin/seo*`, `/robots.txt` |
| Archivos | Gestor, subida, WYSIWYG upload, sync, share, metadata, export, búsqueda avanzada | `FilesController` (35 KB, el mayor) | `FileService`, `FileManagerService`, `ImageUploadValidator` | `files/*` (4 vistas) | `/admin/files*` |
| Componentes | Bloques reutilizables + API interna | `ComponentController` (**clase `ComponentsController`**) | `Component` | `components/{index,edit}.php` | `/admin/components*` ⚠️ ver §8 |
| Comentarios / colaboración | Comentarios anclados con respuestas, menciones; sesiones colaborativas, locks, cursores, mensajes | `CommentsController`, `CollaborationController` | `ComponentComment`, `CommentService`, `CollaborationService` | `pages/edit.php` (panel), `collaboration.css/js` | `/admin/pages/{id}/comments*`, `/admin/profile/mentions`, `/admin/collaboration/{id}/*`, `/cron/cleanup-collaboration` |
| Notificaciones | Bandeja, marcar leídas, limpiar | `NotificationsController` | `NotificationService` | `notifications/index.php` | `/admin/notifications*` |
| Caché | Estado y purga | `CacheController` | `CacheService` | `cache/index.php` | `/admin/cache*` |
| Configuración | Ajustes de sistema (`general`,`seo`,`cache`,`mail`) **y** personalización visual pública (`public`) | `SettingsController` | `Setting`, `SettingService`, `PublicThemeService`, `SettingsAssetService`, `SiteSettings` | `settings/index.php` (37 KB, 11 pestañas) | `/admin/settings*`, `/admin/settings/brand*` |
| Backups | Crear, programar, descargar, restaurar, borrar, cron | `BackupsController` | `BackupService` | `backups/index.php` | `/admin/backups*`, `/cron/backup` |
| API | API v1 de solo lectura + gestión de apps/tokens | `ApiController`, `ApiManageController` | `Page`, `ApiApplication`, `ApiToken`, `ApiAuthService` | `api/*` | `/api/v1/*`, `/admin/api*` |
| i18n | Cambio de idioma y edición de traducciones | `LenguageController` (**clase `LanguageController`**), `TranslationsController` | `TranslationService` | `translations/index.php` | `/lang/{code}` ⚠️, `/admin/translations*` |
| Analytics | Resumen, exportación, endpoint JSON | `AnalyticsController` | `AnalyticsService` | `analytics/index.php` | `/admin/analytics*` |
| Auditoría | Log de auditoría y de actividad | `AuditController`, `ActivityLogsController` | `AuditService`, `ActivityLoggerService` | `audit/{index,detail,activity}.php` | `/admin/audit*`, `/admin/audit/activity*` |
| Seguridad | Dashboard de seguridad en tiempo real, intentos de login, IPs bloqueadas, dispositivos de confianza | `SecurityDashboardController`, `LoginAttemptsController`, `TrustedDevicesController` | `TrustedDeviceService`, `RateLimiter` | `security/*` | `/admin/security/*`, `/cron/security-digest`, `/cron/cleanup-trusted-devices` |
| 2FA | Alta, verificación, códigos de recuperación | `TwoFactorController` | `TotpService`, `TwoFactorRecoveryService` | `2fa/*` (4 vistas) | `/admin/2fa*` |
| Web pública | Portada, páginas por slug, sitemap, CSS del tema, manifiesto | `PublicController`, `PublicThemeController` | `PageRenderer`, `SiteSettings`, `PublicThemeService` | `public/layout.php` | `GET /`, `GET /{slug}`, `/sitemap.xml`, `/robots.txt`, `/public-theme.css`, `/site.webmanifest` |

**Notas del mapa:**
- `EditorController` (10 KB, 13 `Auth::can`) implementa un editor de páginas alternativo, pero **no tiene rutas** (HECHO OBSERVADO: no aparece en `config/routes.php`).
- `PageController` (12 KB) es la variante "pública" cableada en `app/bootstrap.php:181` pero **ninguna ruta la instancia**.
- `bootstrap.php` (HECHO OBSERVADO) construye un contenedor DI con bindings sólo para `PagesController`, `PageController`, `UsersController`, `MenuController`, `TemplatesController`, `AuditController`… El resto se instancia sin DI, así que la inyección de dependencias es **parcial**, no un patrón del sistema.

---

## 3. Contenido editable existente (CRÍTICO)

### 3.1 Cómo se guardan las claves

**HECHO OBSERVADO.** Tabla `settings` con `id / group / key / value / type / updated_at` y `UNIQUE(group,key)` (lo declara la cabecera de `db/migrations/2026_10_26_public_visual_settings.up.sql`). Hoy hay **61 filas en 5 grupos**: `general` (4), `seo` (8), `cache` (2), `mail` (6), `public` (41).

- Escritura: `App\Models\Setting::updateBatch()` (`app/Models/Setting.php:24-42`), `INSERT ... ON DUPLICATE KEY UPDATE value, type` dentro de transacción. Clave compuesta por grupo + key. No hay tabla por página ni JSON: es un key-value plano.
- Lectura: `App\Services\SettingService::all()` (agrupado) y `App\Services\PublicThemeService::all()` (una sola consulta `WHERE group = 'public'`, cacheada en estático por petición, con degradación a defaults si la BD falla).
- Tres capas de "default": `PublicThemeService::defaults()` en código → filas de la migración → valor configurado. Un valor vacío en claves que admiten vacío significa **"automático"** (texto de superficies) o "sin asset" (logos/favicons).

### 3.2 Pantallas de administración que ya existen

**HECHO OBSERVADO.** Todo vive en `GET /admin/settings` (`views/settings/index.php`, 11 pestañas: `general, seo, cache, mail, identity, logos, favicon, themes, colors, css, preview`). Rutas exactas:

| Ruta | Método | Qué hace |
|---|---|---|
| `/admin/settings` | GET | Pinta todas las pestañas (requiere `settings.manage`, comprobado en el controlador) |
| `/admin/settings/update` | POST | Guarda grupos de sistema; lista blanca `general, seo, cache, mail` |
| `/admin/settings/brand` | POST | Guarda tema, 16 colores, 7 colores de texto sobre superficie, `logo_alt`, `custom_css`, `custom_css_enabled` |
| `/admin/settings/brand/asset` | POST | Sube logo/favicon a `uploads/settings/{logos,favicons}` |
| `/admin/settings/brand/asset/delete` | POST | Borra un asset |
| `/admin/settings/brand/reset` | POST | Restablece los colores a un preset |
| `/public-theme.css` | GET (anónima) | Sirve el CSS generado + CSS personalizado, con `ETag` y `304` |
| `/site.webmanifest` | GET (anónima) | Manifiesto con los iconos existentes |

Backend de todo lo visual: `app/Services/PublicThemeService.php` (~1.100 líneas) + `app/Helpers/public_settings.php` (`getPublicSettings()`, `public_setting()`, `public_logo()`, `public_logo_alt()`, `public_favicon_tags()`, `public_theme_css_url()`, `public_is_dark()`).

### 3.3 Claves `public.*` reales (41, verificadas en BD y en código)

- **Tema:** `theme` (`oscuro|claro|corporativo|minimalista|personalizado`; hoy `corporativo`).
- **Logos (10):** `logo_header`, `logo_desktop`, `logo_tablet`, `logo_mobile`, `logo_menu`, `logo_multinivel`, `logo_footer`, `logo_sidebar` (oculto en la UI: `HIDDEN_SLOTS`), `logo_dark`, `logo_light`. Más `logo_alt`.
- **Favicons (4):** `favicon_ico`, `favicon_16`, `favicon_32`, `apple_touch_icon`.
- **Colores (16):** `color_primary`, `color_secondary`, `color_accent`, `color_background`, `color_section_bg`, `color_text`, `color_text_muted`, `color_link`, `color_button`, `color_border`, `color_header_bg`, `color_footer_bg`, `color_sidebar_bg`, `color_menu_bg`, `color_multinivel_bg`, `color_menu_hover`.
- **Texto sobre superficies (7):** `color_header_text`, `color_menu_text`, `color_menu_hover_text`, `color_multinivel_text`, `color_footer_text`, `color_sidebar_text`, `color_button_text` (vacío ⇒ contraste AA calculado con `ensureContrast()`).
- **CSS libre:** `custom_css`, `custom_css_enabled` (validado: sin `<`, `@import`, `expression(`, `javascript:`, `-moz-binding`; acotado a `body.public-site` por `scopeCss()`).

Estado real hoy: `logo_header` apunta a `settings/logos/logo_header-f05d18bd92a8.png`; el resto de slots vacíos; `custom_css_enabled = 0`.

### 3.4 ¿Existe ya un sistema de "branding"?

**SÍ, y es la base sobre la que colgar una landing.** Nombre del sitio, descripción, idioma, zona horaria, logo (multi-slot responsive con `<picture>`), favicon, manifiesto, tema de 16 colores + 7 textos, presets y CSS propio, con contraste AA calculado y aislamiento estricto (todo el CSS va bajo `body.public-site`; el panel no hereda nada).

**Lo que NO existe** (HECHO OBSERVADO, decide el diseño de la landing):
- No hay ni una clave de **texto de contenido** para la web pública: no existe `hero_title`, `hero_subtitle`, `cta_label`, `about_text`, etc. La migración `2026_10_30_public_surface_texts` sólo añade **colores de texto**, pese al nombre.
- Los textos del **footer** están **hardcodeados** en `views/public/layout.php`: "Plataforma de creación y gestión de contenido profesional.", enlaces "Inicio / Acerca de / Privacidad / Términos" y el correo `info@pepiuox.com`.
- No hay tabla de secciones/bloques de landing, ni campo `settings` por página, ni modelo de "bloques de portada".
- El contenido de la portada se edita hoy **como una página más**, con el builder GrapesJS (`content_html` + `content_css` + `content_js` + `grapesjs_data`).

---

## 4. Portada actual y datos vivos

### 4.1 Cómo se decide la home

**HECHO OBSERVADO** (`app/Services/PageRenderer.php:17-38`):

```sql
SELECT * FROM pages
WHERE startpage = ? AND is_published = ? AND status = ?
LIMIT 1
```

con `$startpage = 1`, `$is_published = 1`, `$status = 'published'` como propiedades privadas de la clase. Resultado cacheado en estático (`self::$pageCache['home_page']`).

Cadena: `GET /` → `PublicController::home()` → si no hay resultado, `showFallbackPage('Bienvenido', ...)`; si hay, `buildPageHtml()` + `menuForPage()` + layout.

Observaciones:
- La home **no** se busca por `slug = 'home'` ni por `id`. `PublicController::show()` sí trata `''` y `'home'` como portada.
- **No hay unicidad garantizada:** `PagesController::update()` escribe `startpage` tal cual (verificado en `PageModel::updateById()`, que lo incluye en su lista blanca), sin poner a 0 el resto; sólo las variantes muertas (`PagesController---`, `PageModel`, `PageModel1`…) implementaban `handleStartpage()`. Con dos filas en `startpage=1` la portada es la de menor `id` que devuelva el motor: no determinista por contrato.
- `status` y `is_published` deben cumplirse **ambos**; una página publicada con `status='draft'` no es portada.

### 4.2 Datos vivos (SELECT ejecutado, sin credenciales ni datos personales)

| Consulta | Resultado |
|---|---|
| `SELECT COUNT(*) FROM pages` | **9** |
| `SELECT COUNT(*) FROM templates` | **13** |
| `SELECT COUNT(*) FROM menus` | **3** (`main`/header – default, `footer`/footer, `menu-top`/header – primary) |
| `SELECT COUNT(*) FROM users` | **3** |
| `SELECT COUNT(*) FROM settings` | **61** (grupos: general 4, seo 8, cache 2, mail 6, public 41) |
| `SELECT COUNT(*) FROM page_versions` | 55 |
| `SELECT COUNT(*) FROM files` | 23 |
| `SELECT COUNT(*) FROM components` | 0 |
| Objetos de BD | 52 tablas base, 2 vistas, 0 rutinas, 4 triggers |

**Portada hoy:** `id=1`, `slug='inicio'`, `title='Inicio'`, `startpage=1`, `is_published=1`, `status='published'`. Es la **única** fila con `startpage <> 0`.

Resto de páginas (todas publicadas, `startpage=0`): `quienes-somos` (2), `proyectos` (3), `portafolio` (4), `demo-web` (5), `vistas` (6), `desarrollo` (7), `viajes` (12), `abancay` (20).

Columnas de `pages`: `id, user_id, template_id, menu_id, parent_id, title, slug, description, h1, content_html, content_css, content_php, content_js, grapesjs_data, startpage, is_published, publish_at, status, created_at, updated_at`.

Tabla de ítems de menú: **`page_menu`** (no `menu_items`), con `menu_id` añadido por la migración `2026_10_25_menu_system`. Roles y permisos viven en `users_roles`, `users_permissions`, `users_role_permissions` (no existen tablas `roles` ni `permissions`).

---

## 5. Layout público y stack frontend

### 5.1 `views/public/layout.php` (33,6 KB)

**HECHO OBSERVADO.** Estructura:

1. **Cabecera PHP** (líneas 1-45): resuelve metadatos con `SiteSettings::titleFor()/descriptionFor()/language()/name()`, calcula la marca en dos tonos (`$brandFirst` neutro + `$brandRest` en acento), lee `Auth::check()` para decidir acciones de sesión.
2. `<head>`: `charset`, `viewport`, `<title>`, `meta description`, `og:site_name`, `og:description`, favicons vía `PublicThemeService::faviconTags()` (sólo archivos existentes), **Bootstrap 5.3.3 CSS** y **Bootstrap Icons 1.11.3** por CDN, y `<link rel="stylesheet" href="<?= e(public_theme_css_url()) ?>">` (hoja dinámica con `?v=<hash>`).
3. **~530 líneas de CSS embebido** (`<style>`) con tokens propios (`--bg-main`, `--accent`, `--radius`, `--shadow-*`), navbar, dropdowns multinivel, botones, hero, footer y responsive. Es el bloque que la hoja dinámica *puentea* (`--public-*` → `--bg-main`, `--accent`, …).
4. `<body class="public-site">` + `<html data-bs-theme="dark|light">` según `PublicThemeService::isDark()` (luminancia del fondo configurado).
5. **Navbar** real (`.navbar.navbar-expand-lg.sticky-top#siteNav`) con `<picture>` responsive de logo (`logo_desktop/tablet/mobile/menu`), menú renderizado por `(new PageRenderer())->renderMenuHtml($menu)`, y acciones por sesión (login/registro o acceso al panel).
6. `<main><?= $content ?></main>` — el HTML ya construido por `PageRenderer::buildPageHtml()`.
7. **Footer** (`.footer`) con logo, descripción **hardcodeada**, columnas Navegación/Legal y contacto **hardcodeadas**, y `© año {site_name}`.
8. **Bootstrap 5.3.3 bundle JS** por CDN + ~100 líneas de JS inline (colapso de submenús, elevación de navbar al hacer scroll).

Helpers usados en el layout: `e()`, `url()`, `asset()`, `public_theme_css_url()`, `public_logo()`, `public_logo_alt()`, `SiteSettings::*`, `PublicThemeService::*`, `PageRenderer::renderMenuHtml()`.

### 5.2 `views/layouts/admin.php` (1,2 KB)

**HECHO OBSERVADO.** Estructura mínima: **Bootstrap 5.3.8** CSS+JS por CDN (versión distinta a la pública: **5.3.3**), Bootstrap Icons 1.11.3, `asset('css/maker.css')`, `asset('js/app.js')`, e incluye `views/partials/{sidebar,topbar,flash}.php`. **No** carga `--public-*`, ni la hoja dinámica, ni los logos configurables: el aislamiento del branding público respecto al panel es real. `views/layouts/auth.php` no carga Bootstrap (sólo `maker.css`).

### 5.3 ¿Bootstrap 5 está disponible en la web pública?

**SÍ, y de hecho es el framework de la portada**, no sólo del panel (HECHO OBSERVADO): `bootstrap@5.3.3` CSS en `views/public/layout.php:78` y `bootstrap.bundle.min.js` en la línea 840. Lo que **no** hace la web pública es usar el sistema de utilidades de forma sistemática: convive con ~530 líneas de CSS propio que redefinen navbar, dropdowns y botones, y el tema oscuro se resuelve por tokens propios + `data-bs-theme`.

### 5.4 Versiones realmente referenciadas (grep sobre `views/`)

| Recurso | Versión | Dónde |
|---|---|---|
| Bootstrap (público) | **5.3.3** | `views/public/layout.php` |
| Bootstrap (admin) | **5.3.8** | `views/layouts/admin.php`, `partials/*` |
| Bootstrap Icons | 1.11.3 | público y admin |
| Font Awesome Free | 6.5.2 | vistas de builder/editor |
| GrapesJS | 0.21.10 (+ `blocks-basic 1.0.2`, `blocks-bootstrap5 0.2.31`, `custom-code 1.0.2`, `plugin-export 1.0.12`, `forms 2.0.6`, `tabs 1.0.6`, `touch 0.1.1`) | builder de páginas/plantillas |
| CodeMirror | 5.65.16 (10 addons/modos) | editor de código |
| Chart.js | 4.4.0 | analytics / dashboard de seguridad |
| Tipografías | **Inter** 400-800 y **JetBrains Mono** 400-500 (Google Fonts) | layout público y admin |

**Riesgo de cadena de suministro (INFERENCIA):** todas las dependencias se cargan en caliente desde `cdn.jsdelivr.net` y `fonts.googleapis.com`, sin SRI (`integrity`) ni copia local. Si el CDN falla o se bloquea, la portada pierde el framework entero.

### 5.5 `public/assets` — inventario con tamaños

| CSS | Bytes | JS | Bytes |
|---|---:|---|---:|
| `css/maker.css` | 63.833 | `js/app.js` | 15.631 |
| `css/maker0.css` | 82.776 | `js/blocks-library.js` | 16.583 |
| `css/builder.css` | 30.369 | `js/comments.js` | 27.672 |
| `css/collaboration.css` | 5.628 | `js/collabotarion.js` | 26.554 |
| `css/blocks-library.css` | 4.841 | `js/grapesjs-shared.js` | 6.355 |
| | | `js/settings-public.js` | 17.083 |
| | | `js/blocks/advance.js` | 13.374 |
| | | `js/blocks/blocks-advanced.js` | 38.520 |
| | | `js/blocks/blocks-bootstrap.js` | 34.803 |
| | | `js/blocks/blocks-bs-advanced.js` | 16.501 |
| | | `js/blocks/blocks-bs.js` | 14.794 |
| | | `js/blocks/blocks.js` | 13.489 |
| | | `js/blocks/bs-block-advanced.js` | 10.230 |
| | | `js/blocks/bs-blocks.js` | 8.824 |
| | | `js/blocks/pro-templates.js` | 33.121 |
| | | `js/blocks/second-blocks.js` | 12.289 |
| | | `js/blocks/template.js` | 8.197 |
| | | `js/blocks/uiblocks.js` | 13.501 |

Notas: `maker0.css` (82 KB) es casi con certeza una copia obsoleta de `maker.css`; `collabotarion.js` está mal escrito en el propio nombre del archivo. No hay minificación ni versionado por hash de assets propios (`asset()` sirve la ruta pelada).

---

## 6. Seguridad observada

### 6.1 CSRF — presente y extendido

**HECHO OBSERVADO:** 111 referencias a token CSRF en vistas (64 `\App\Core\CSRF::field()`, 12 `csrf_field()`, 6 `CSRF::field()`, más usos JS con `_csrf_token`). `CSRF::guard()` aparece 135 veces en controladores. Formularios POST en vistas: 97. `app/Core/CSRF.php` expone `token()`, `field()`, `verify()`, `guard()`; `Setting`-based `SettingsController::update()` y `updatePublic()` llaman a `CSRF::guard()`.

**Excepción implícita (INFERENCIA a validar):** las llamadas AJAX que envían `X-CSRF-Token` sólo están cubiertas donde la vista lo añade; el ratio 111/97 es bueno pero no hay verificación automática de que **cada** ruta POST tenga guard.

### 6.2 Middlewares

| Middleware | Existe | ¿Se aplica? |
|---|---|---|
| `AuthMiddleware` | Sí (`app/Middleware/AuthMiddleware.php`) | **Sí**: variable `$auth` en todas las rutas `/admin/*` |
| `ApiAuthMiddleware` | Sí | **Sí**: `/api/v1/*` (Bearer) |
| `RateLimitMiddleware` | Sí (constructor `prefix, max, decay, identifier`) | **Sí**: `login` 5/60 s, `register` 3/3600, `forgot` 3/3600, `reset` 5/3600, `2fa` 3/60 |
| `PermissionMiddleware` | Sí (`app/Middleware/PermissionMiddleware.php`) | **NO**: no aparece en `config/routes.php` (HECHO OBSERVADO: grep de `PermissionMiddleware` en rutas = 0 resultados) |

**Consecuencia real (HECHO OBSERVADO + INFERENCIA):** la autorización fina depende de llamadas `Auth::can()` dispersas dentro de los controladores. Hay métodos sensibles sin comprobación por permiso, por ejemplo `PagesController::delete()` (sólo `CSRF::guard()` + sesión). Es decir: **cualquier usuario autenticado podría borrar páginas** si la UI le deja; el `PermissionMiddleware` que lo evitaría estructuralmente está escrito pero desconectado.

### 6.3 Cabeceras de seguridad

**HECHO OBSERVADO:**
- `.htaccess` raíz: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `Header unset Server`, `X-Powered-By` fuera.
- `public/.htaccess`: mismo bloque salvo `Permissions-Policy`.
- `app/Core/Response.php`: emite `X-Content-Type-Options` en respuestas JSON.
- `public/uploads.php`: `nosniff` + CSP `default-src 'none'; style-src 'unsafe-inline'; sandbox` para servir SVGs.
- **NO existe `Content-Security-Policy` a nivel de documento** (grep global: 0 coincidencias fuera de `uploads.php`) y **NO existe `Strict-Transport-Security`**. Tampoco hay política de CSP en PHP.
- Estas cabeceras dependen de que Apache tenga `mod_headers` activo; bajo `php -S` (servidor de pruebas) **no se emiten**.

### 6.4 Otros controles observados

- Contraseñas: `password_hash`/`password_verify` en `User`/`AuthController` (uso de `TotpService` para 2FA, `TrustedDeviceService` para dispositivos).
- Subida de archivos: `AssetUploadService`, `FileService`, `ImageUploadValidator`, `SvgSanitizer`; MIME y extensión con `ALLOWED_EXTENSIONS` en `config/paths.php`; los assets de marca van a `uploads/settings/{logos,favicons}`.
- CSS libre: validación por lista negra + acotado de ámbito (`PublicThemeService::validateCustomCss()`/`scopeCss()`).
- **Contenido de página:** `PageRenderer::buildPageHtml()` escapa el HTML con una **lista negra + `strip_tags`** (`sanitizeHtml()`) e inyecta `content_css` dentro de `<style>` y `content_js` dentro de `<script>` **prácticamente en crudo** (sólo neutraliza `</script>`). `content_php` **no** se evalúa en la superficie pública (bien).
- `PublicController::sitemap()` usa `Database::fetchAll()` **sin importar** `App\Core\Database` (HECHO OBSERVADO) ⇒ si esa ruta se alcanzara, sería error fatal. Hoy el archivo estático `public/sitemap.xml` la tapa.

---

## 7. Pruebas

**HECHO OBSERVADO.** No hay PHPUnit, ni `composer.json`, ni `phpunit.xml`, ni CI. Lo que existe:

### `tests/blocks-library/` (Node, sin navegador)
- `install.test.js` (5,4 KB): simula el `BlockManager` de GrapesJS en un `vm` sandbox y comprueba que `BlocksLibrary.install()` no lanza, que asigna categorías canónicas, retira duplicados y aísla fallos de un bloque.
- `normalize.test.js` (1,9 KB): normalización de bloques (bordes de guía, colores fijos, categoría canónica).
- `classify.js` (3,7 KB): helper compartido, no test.
- **Ejecución real:** `node tests/blocks-library/install.test.js` → `Resultado: todas las comprobaciones pasan`, **exit 0**. Emite un `[BlocksLibrary] No se pudo normalizar boom` por stderr que es **el error simulado a propósito** (bloque `boom`), no un fallo. `node tests/blocks-library/normalize.test.js` → `todas las comprobaciones pasan`.
- No tocan la base de datos.

### `tests/public-theme/` (PHP + PowerShell, contra BD real)
- `service-test.php` (21,8 KB): prueba de servicio **con la BD real** (defaults de migración, 16 variables CSS, ámbito `body.public-site`, validación de HEX y CSS, saneado de SVG, validador de subidas, persistencia y reset, favicon, manifiesto, helpers). Salida esperada documentada: `118 pasan, 0 fallan`.
- `e2e.ps1` (18,3 KB): prueba HTTP extremo a extremo (subida autenticada, PNG falsificado, SVG saneado en disco, `<picture>`, ETag, CSRF, permisos por rol, borrado). Espera `73 pasan, 0 fallan`. Requiere `php -S` con `session.save_path` propio y el router `public/_ppf_router.php`, que **no está en el repo** (el README explica cómo crearlo).
- `support.php` (4,9 KB): utilidades CLI para leer/pintar claves.
- **NO ejecutados en este inventario** (decisión deliberada, no limitación técnica): ambos **escriben** en `settings` (grupo `public`) y crean/borran archivos en `uploads/`; el README avisa explícitamente de que hay que ejecutarlos "en una copia de la base de datos, no sobre la configuración real". El encargo prohíbe DML.

**Cobertura real:** hay verificación sólida del módulo de tema público y de la biblioteca de bloques; **no hay ninguna prueba automatizada** de controladores, modelos, rutas, autenticación, autorización, migraciones ni del resto de módulos.

---

## 8. Residuos y deuda detectada

### 8.1 Archivos muertos (HECHO OBSERVADO: no aparecen en `config/routes.php`)

| Archivo | Tamaño | Clase declarada | Estado |
|---|---:|---|---|
| `app/Controllers/PagesController---.php` | 39,1 KB | `PagesController` | **Residuo**. Duplicado de clase: si el autoloader lo cargara, colisiona con `PagesController.php`. Nunca se carga. |
| `app/Controllers/PagesController--.php` | 35,9 KB | `PagesController` | Residuo (idem) |
| `app/Controllers/PagesController-.php` | 34,3 KB | `PagesController` | Residuo (idem) |
| `app/Controllers/PagesController-ai.php` | 36,1 KB | `PagesController` | Residuo (idem) |
| `app/Controllers/PagesController1.php` | 28,0 KB | `PagesController` | Residuo (idem) |
| `app/Controllers/PasswordController--.php` | 21,7 KB | `PasswordController` | Residuo (idem) |
| `app/Controllers/PageController.php` | 12,6 KB | `PageController` | Sin rutas; sólo aparece en el contenedor DI de `app/bootstrap.php:181` |
| `app/Controllers/EditorController.php` | 10,2 KB | `EditorController` | Sin rutas. Además importa `App\Services\PagePublisher`, **clase que no existe** |
| `app/Controllers/SecurityController.php` | 8,1 KB | `Security` | Sin rutas |
| `app/Controllers/SecureEncryptionController.php` | 15,7 KB | `SecureEncryption` | Sin rutas |

Modelos y servicios con el mismo patrón: `Page0.php`, `Page1.php`, `PageModel0.php`, `PageModel1.php`, `PageModel2.php`, `PageModel-ai.php`, `PageVersion0.php`, `PageService0.php`, `PageService-ai.php`, `PagePublicervice-ai.php`, `PagePublic.php` (versiones abandonadas del mismo dominio). ~590 KB de código PHP duplicado y sin cobertura.

### 8.2 Desajustes archivo ↔ clase que rompen rutas (HECHO OBSERVADO, alto impacto)

El autoloader (`public/index.php:21-42` e `index.php:16-24`) resuelve `App\Controllers\X` a `app/Controllers/X.php` **por nombre de clase**. Dos archivos declaran una clase con nombre distinto al del archivo:

| Clase referenciada en rutas | Archivo real | Efecto |
|---|---|---|
| `App\Controllers\ComponentsController` | `app/Controllers/ComponentController.php` | `is_file()` falso ⇒ `class_exists` falla ⇒ **error en las 7 rutas `/admin/components*`** |
| `App\Controllers\LanguageController` | `app/Controllers/LenguageController.php` (nombre mal escrito) | **error en `GET /lang/{code}`** |

No se ha ejecutado la petición; la conclusión sale del mapeo determinista del autoloader. **VERIFICAR en runtime antes de dar por supuesta la caída** (un `require` previo de otro archivo podría cargar la clase).

### 8.3 Otras rutas defectuosas

- `GET /page` y `GET /page/{slug}` hacen `require __DIR__ . '/../page.php'` desde `config/` ⇒ buscan `ROOT/page.php`, **que no existe** (el archivo real es `public/page.php`).
- `PublicController::sitemap()` sin `use App\Core\Database` (fatal si se alcanza; hoy tapada por `public/sitemap.xml`).
- `views/layouts/auth.php` termina con un `require config/config.php` después del HTML, con `$cfg` usada antes de definirse: `e($cfg['app_name'] ?? ...)` funciona por el `??`, pero es orden frágil.

### 8.4 Deuda de arquitectura y configuración

- **Dos entrypoints** con dos autoloaders distintos (`index.php` vs `public/index.php`).
- **DI parcial**: `app/bootstrap.php` cablea 6-7 controladores; el resto se construye a mano.
- **`PermissionMiddleware` nunca aplicado**; autorización repartida en `Auth::can()` irregulares.
- **Bootstrap 5.3.8 en admin vs 5.3.3 en público** (dos versiones del mismo framework).
- `PermissionMiddleware`/`Authorize` ausente ⇒ no hay un punto único donde auditar autorización.
- `security headers` dependen de Apache; sin CSP ni HSTS.
- Dependencias CDN sin SRI ni copia local.
- Sin pruebas para el 90 % del código; sin CI.
- `app/Services/MenuService.php` fija `itemsTable = 'page_menu'` (correcto) pero `components` tiene 0 filas y su módulo ni siquiera carga: funcionalidad declarada, no operativa.
- `EmailTemplate.php` es un servicio cuyo nombre no sigue el sufijo `Service`.

---

## 9. Qué falta para una landing editable

### 9.1 Lo que YA hay y se puede reutilizar (base real, no hay que inventarla)

1. **Persistencia key-value** `settings(group,key)` con escritura transaccional e idempotente (`Setting::updateBatch`).
2. **Servicio de tema público** con defaults en código, caché por petición, degradación segura y contraste AA calculado (`PublicThemeService`).
3. **Pantalla de administración completa** en `/admin/settings` (11 pestañas) con CSRF, permisos `settings.manage`, subida de assets validada y preview.
4. **Hoja dinámica** `/public-theme.css` con `ETag`/`304` y CSS personalizado acotado a `body.public-site`.
5. **Layout público** con navbar por menús reales (`menus` + `page_menu`), `<picture>` de logo, favicons, manifiesto y footer.
6. **Motor de páginas** con builder GrapesJS (`pages.content_html|content_css|content_js|grapesjs_data`), versiones (`page_versions`, 55 filas) y autosave (`page_autosave`).
7. **Portada resoluble por dato**: `pages.startpage = 1` + `status='published'` + `is_published=1`.

### 9.2 Lo que falta (brechas concretas)

| # | Brecha | Impacto | Enfoque mínimo |
|---|---|---|---|
| 1 | **Cero claves de texto editorial** en `settings` (hero, subtítulo, CTA, "sobre", contacto) | La landing no se puede reescribir sin tocar la página entera con el builder | Nuevo grupo `settings` (`landing`/`public_texts`) con `*_text` por sección + pestaña nueva; el patrón ya existe, sólo hay que añadir claves y campos |
| 2 | **Footer y navbar hardcodeados** en `views/public/layout.php` (descripción, 4 enlaces, email) | Cambiar el pie exige editar código | Mover a `settings` (`footer_tagline`, `footer_email`, `footer_legal_*`) y leerlos con `public_setting()` |
| 3 | **No hay modelo de secciones de landing** (hero, features, CTA, testimonios como estructura repetible) | Cada cambio de estructura = reeditar la página a mano | Reutilizar `page_menu`-style: tabla `landing_sections` o JSON en una clave `settings` + render parcial |
| 4 | **Unicidad de `startpage` no garantizada** | Dos "homes" ⇒ portada indeterminada | `handleStartpage()` (ya escrito en los residuos) o índice único parcial / UPDATE en transacción |
| 5 | **`/admin/components*` y `/lang/{code}` caen** por nombre de archivo | Funcionalidad rota silenciosamente | Renombrar `ComponentController.php`→`ComponentsController.php` y `LenguageController.php`→`LanguageController.php` |
| 6 | **`/page` y `/page/{slug}` rotos** | Rutas legacy muertas | Corregir el `require` a `public/page.php` o eliminar las rutas |
| 7 | **`PermissionMiddleware` sin conectar** | Cualquier sesión con acceso a `/admin/pages` puede borrar páginas | Aplicar el middleware ya existente con `pages.manage`, `settings.manage`, etc. |
| 8 | **Sin CSP ni HSTS**; CDNs sin SRI | Superficie XSS y dependencia externa | CSP con `default-src 'self'` + allowlist de jsdelivr/googleapis, SRI en los `<link>`/`<script>` |
| 9 | **`content_js` de página se inyecta en crudo** en la web pública | XSS almacenado por un editor comprometido | Sanear JS o servirlo como archivo con CSP, o restringirlo a un permiso explícito |
| 10 | **Sin pruebas de controladores/rutas** | Cualquier refactor es a ciegas | Añadir pruebas de humo: cada ruta declarada instancia su controlador (detectaría los puntos 5 y 6) |
| 11 | **~590 KB de controladores/modelos/servicios duplicados** | Mantenimiento insostenible, riesgo de editar el archivo equivocado | Borrado controlado (con `git rm`) tras confirmar ausencia de referencias |
| 12 | **Sin i18n en la landing**: los textos del layout público están en español fijo | Bloquea multi-idioma | Usar `t()` (ya existe) o claves de settings por locale |

### 9.3 Veredicto

La plataforma **ya tiene la infraestructura** de una landing editable (persistencia, servicio, pantalla de administración, CSS dinámico, assets, portada por dato). Lo que falta **no es plumbing, es contenido estructurado**: claves de texto, bloques repetibles y sacar del layout los literales del pie. El trabajo de una landing editable se puede apoyar íntegramente en `settings` + `PublicThemeService` sin tocar el modelo de datos existente.

---

*Informe generado por inspección directa de archivos y `SELECT` de solo lectura. No se ejecutaron migraciones, ni DDL/DML, ni se modificó ningún archivo del proyecto salvo este documento.*
