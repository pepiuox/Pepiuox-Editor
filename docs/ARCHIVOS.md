# Mapa de archivos y del desarrollo

Inventario de la plataforma: qué hay, qué hace cada pieza y dónde tocar. Escrito para que alguien
nuevo —o tú dentro de tres meses— sepa en qué archivo está cada cosa sin abrir cuarenta pestañas.

> **Medido el 31 de octubre de 2026:** 413 archivos fuera de `storage/`, `.git` y las skills del
> arnés. 240 son PHP · 49 Markdown · 27 CSV · 24 JS · 19 SQL · 8 CSS.
> `app/` ocupa 1 302 KB · `views/` 830 KB · `public/assets/` 503 KB.

---

## 1. Cómo se sirve una petición

```
Navegador
   │
   ▼
.htaccess (raíz) ──reescribe──▶ public/index.php          ← único punto de entrada real
                                     │
                                     ├─ config/paths.php      constantes (ROOT_PATH, APP_PATH…)
                                     ├─ autocarga PSR-4        App\X → app/X.php
                                     ├─ app/Helpers/functions.php
                                     ├─ config/config.php      (+ carga config/env.php)
                                     ├─ App\Core\Session::start()
                                     ├─ App\Core\App::boot()   (+ services.php)
                                     ├─ App\Core\Router + Request
                                     ├─ config/routes.php      220 acciones declaradas
                                     │     └─ setPermissionMap(config/permissions.php)
                                     ▼
                     middleware de ruta ─▶ permiso por acción ─▶ controlador
                                                                     │
                                              servicio ─▶ modelo ─▶ PDO
                                                                     │
                                                                  views/
```

`index.php` de la raíz existe pero **no recibe tráfico**: el `.htaccess` reescribe todo a `public/`.

---

## 2. `app/` — el código (132 archivos, 1 302 KB)

### 2.1 `app/Core/` — el motor (14 clases)

| Archivo | Qué hace |
|---|---|
| `Router.php` | Resuelve rutas por primera coincidencia, ejecuta middlewares, aplica el **mapa de permisos** e inyecta dependencias. 18,7 KB, el archivo más delicado del proyecto |
| `Request.php` | Normaliza URI, método, parámetros y cabeceras |
| `Response.php` | `render`, `json`, `redirect`, `abort`. Todos terminan en `exit` |
| `Database.php` | Único acceso a PDO. `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false` |
| `Auth.php` | Sesión → usuario. `can()` con atajo para `Super admin` |
| `Session.php` | Arranque y `flash` |
| `CSRF.php` | Token por sesión y `field()` para los formularios |
| `View.php` | `render` (con layout), `renderStandalone` (documento completo), `partial` |
| `Validator.php` | Validaciones de entrada |
| `App.php` | Arranque: zona horaria, servicios |
| `CacheManager.php` | Caché de páginas en disco |
| `Security.php` | Cifrado AES-256-GCM. **Clave efímera por instancia** (hallazgo R-13) |
| `CronToken.php` | Autorización de `/cron/*` con `hash_equals` |
| `LinkGuard.php` | Única regla de destinos válidos (rutas internas, anclas, `http(s)`) |

### 2.2 `app/Middleware/` — 5 filtros

`AuthMiddleware` (sesión) · `PermissionMiddleware` (permiso, invocado desde el mapa) ·
`RateLimitMiddleware` (límite por ventana) · `RateLimitUnavailableMiddleware` (falla en cerrado) ·
`ApiAuthMiddleware` (Bearer para `/api/v1`).

### 2.3 `app/Helpers/`

`functions.php` (12,4 KB): `e()`, `url()`, `asset()`, `csrf_field()`, `is_active()`,
`is_active_exact()`, `can()`, `flash()`, `public_logo()`. `public_settings.php`: atajos del tema público.

### 2.4 `app/Controllers/` — 43 archivos, 37 clases distintas

| Módulo | Controlador |
|---|---|
| Tablero · Perfil · Notificaciones | `DashboardController` · `ProfileController` · `NotificationsController` |
| Usuarios · Baneos · Roles | `UsersController` · `BannedUsersController` · `RolesController` |
| Páginas · Constructor · Versiones | `PagesController` · `VersionsController` |
| Plantillas · Menús | `TemplatesController` · `MenuController` |
| SEO · Sitemap | `SeoController` |
| Archivos · Componentes | `FilesController` · `ComponentsController` |
| Configuración · Portada · Pie | `SettingsController` |
| Copias · API · Traducciones | `BackupsController` · `ApiManageController` · `TranslationsController` |
| Analítica · Auditoría | `AnalyticsController` · `AuditController` · `ActivityLogsController` |
| Seguridad · 2FA | `SecurityDashboardController` · `LoginAttemptsController` · `TrustedDevicesController` · `TwoFactorController` |
| Colaboración · Comentarios | `CollaborationController` · `CommentsController` |
| Acceso · Contraseña | `AuthController` · `PasswordController` |
| Público · Tema público · API v1 | `PublicController` · `PublicThemeController` · `ApiController` · `LanguageController` |

### 2.5 `app/Services/` — 41 archivos · `app/Models/` — 24 archivos

Los servicios con más peso: `MenuService` (50 KB), `PublicThemeService` (54 KB), `FileManagerService`
(50 KB), `SitemapService`, `LandingContentService`, `FooterContentService`, `PageRenderer`,
`SettingsAssetService`, `TrustedDeviceService`, `EmailNotificationService`.

### 2.6 Peso muerto: 15 archivos duplicados (~280 KB)

No los referencia nada. Declaran la misma clase que un archivo vivo o tienen sufijos de copia:

```
Controllers: PagesController--.php  PagesController---.php  PagesController-ai.php
             PagesController1.php   PasswordController--.php
Models:      Page0.php  Page1.php  PageModel0.php  PageModel1.php  PageModel2.php
             PageModel-ai.php  PageVersion0.php
Services:    PageService0.php  PageService-ai.php  PagePublicervice-ai.php
```

**No se borraron**: son del dueño y borrarlos es su decisión. Si un `class_exists` falla alguna vez,
sospecha de aquí.

---

## 3. `views/` — 74 archivos, 830 KB

| Carpeta | Qué contiene |
|---|---|
| `layouts/` | `admin.php` (panel) · `auth.php` (acceso) |
| `partials/` | `sidebar.php` (menú del panel) · `topbar.php` · `flash.php` · `editor-assets-head/body.php` (GrapesJS + CodeMirror) |
| `public/` | `layout.php` (documento completo del sitio) · `landing.php` (portada) |
| `pages/` | Listado, edición, versiones y **`builder.php` (99 KB, el constructor)** |
| `settings/` | `index.php` (622 líneas, 11 pestañas) · `landing.php` · `footer.php` (campos) · `footer-builder.php` (GrapesJS) |
| `seo/` | `index`, `global`, `redirects`, `analyze`, `sitemap`, `generator`, `competitor` |
| Resto | Una carpeta por módulo: `users`, `roles`, `menus`, `templates`, `files`, `backups`, `api`, `analytics`, `audit`, `security`, `2fa`, `auth`, `profile`, `notifications`, `translations`, `components`, `cache`, `private` |

---

## 4. `config/` — 7 archivos

| Archivo | Qué es |
|---|---|
| `paths.php` | Constantes de rutas y `BASE_URL` |
| `env.php` | Cargador de `.env` y `env()` |
| `config.php` | Ajustes generales, sesión, contraseña, correo, `cron_token` |
| `database.php` | Lee credenciales del entorno. **No las contiene** |
| `routes.php` | 39 KB: las 220 acciones y los middlewares |
| `permissions.php` | 18,5 KB: **mapa acción → permiso** |
| `services.php` | Registro de servicios del contenedor |

---

## 5. `public/` — el document root

`index.php` (entrada real) · `.htaccess` (reescritura y cabeceras) · `page.php` (render público
histórico, 33 KB) · `sitemap.xml` (generado) · `uploads.php` · `install.php` (**trabajo en curso del
dueño, sin documentar como ruta soportada**) · `assets/{css,js,js/blocks}`.

---

## 6. `db/` — 20 archivos

| Ruta | Qué es |
|---|---|
| `db-full.sql` | Volcado de referencia. **Empieza con `CREATE DATABASE` + `USE web_editor_db`**: importarlo escribe en esa base, no en la que tengas seleccionada |
| `db-full_.sql`, `_db-full_.sql` | Duplicados del anterior. Tres volcados para el mismo esquema |
| `install/initial_database.sql` | **Instalación limpia**: estructura + catálogos, sin datos de nadie |
| `migrations/` | 6 pares `up`/`down` + `apply.php` |
| `backup/` | Respaldos reales. **Ignorado por git** a propósito |

---

## 7. `01-TOOLS/` — herramientas de terminal (fuera de la raíz web)

| Ruta | Qué hace |
|---|---|
| `bootstrap/create-admin.php` | Crea el primer Super Admin con contraseña generada |
| `bootstrap/seed-pages.php` | Siembra /funciones, /seguridad, /como-funciona y la **portada** |
| `bootstrap/seed-themes.php` | Siembra los temas de diseño como plantillas (--list, --only) |
| `bootstrap/themes/catalogo.php` | Catálogo de temas: HTML y CSS de cada uno |
| `mantenimiento/sanitize-dump.php` | Quita hashes y filas de API de un volcado |
| `mantenimiento/rotate-api-tokens.php` | Rota los tokens de API filtrados |
| `despliegue/verificar.php` | Comprueba y prepara la publicación (entorno, permisos, configuración, base de datos, cron) |
| `mysql/` | `.env` (ignorado), `.env.example`, `test_connection.php` |
| `diagnostics/` | Cementerio de scripts retirados. **No se reutiliza** |

---

## 8. `tests/` — 6 baterías, 160 comprobaciones

| Ruta | Comprueba |
|---|---|
| `rutas/check.php` | 23: resuelve URIs reales y detecta literales tapadas por parametrizadas |
| `seguridad/check.php` | 34: mapa de permisos, cron, limitador, menú, volcados sin secretos |
| `footer/check.php` | 30: contenido del pie, validación de enlaces, topes |
| `landing/check.php` | 46: portada, escape, listas vacías, destinos |
| `sitemap/check.php` | 17: ámbitos, XML, rutas excluidas |
| `paneles/menu.php` | 10: pinta el menú con sesión real y verifica qué entrada se enciende |
| `landing/save.php` | Guardado contra base de pruebas |
| `public-theme/`, `blocks-library/` | Preexistentes; los primeros requieren base de datos |

---

## 9. Documentación

**`docs/`** (38): `MANUAL_DE_USUARIO` · `MANUAL_DE_INSTALACION` · `ESPECIFICACIONES_TECNICAS` ·
`INFORME_DE_VALIDACION` · `ARCHIVOS` (este) · `INSTALL` · `ROUTES` · `PUBLIC_THEME` · `BLOCK_LIBRARY` ·
`PARTIAL_ESTRUCTURE` · 27 CSV con el inventario funcional por pantalla.

**`02-DOCS/wiki/`** (30): `sdd/constitution.md` (la autoridad, v1.5.0) · `harness/decisions.md` ·
`ftd/` una ficha por cambio · `index.md` por dónde empezar.

---

## 10. Carpetas heredadas

| Ruta | Estado |
|---|---|
| `private/` | Copia muerta de assets y un `.htaccess`. Nada la referencia (deuda 12) |
| `uploads/` | Medios subidos. 25 archivos |
| `nbproject/` | Metadatos de NetBeans |
| `storage/` | `cache/`, `logs/`, `backups/`, `versions/`, `preview/` (este último ignorado) |

---

## 11. Por dónde empezar según lo que vayas a tocar

| Quiero… | Archivo |
|---|---|
| Añadir una ruta | `config/routes.php` **y** `config/permissions.php` (la batería de seguridad falla si falta el permiso) |
| Añadir una pantalla | `app/Controllers/` + `views/<módulo>/` + 2 rutas + entrada en el mapa |
| Cambiar el menú del panel | `views/partials/sidebar.php` |
| Cambiar el aspecto del sitio público | `Configuración → Temas / Colores`, o `PublicThemeService` |
| Cambiar la portada | `Configuración → Editar la portada` (`LandingContentService`) |
| Cambiar el pie | `Configuración → Editar el pie`: campos (ooter.php) o **constructor** (ooter-builder.php) |
| Reutilizar el editor visual | `public/assets/js/grapesjs-shared.js` (window.EditorKit): lienzo, plugins y carga de bloques |
| Tocar el constructor visual | `views/pages/builder.php` + `public/assets/js/grapesjs-shared.js` |
| Comparar los tres editores | `docs/ESPECIFICACIONES_TECNICAS.md` §12: piezas compartidas y qué cambia en cada uno |
| Cambiar el esquema | `db/migrations/<fecha>_<nombre>.{up,down}.sql` + `db/db-full.sql` |
| Entender una decisión | `02-DOCS/wiki/` (`ftd/` y `constitution.md`) |
