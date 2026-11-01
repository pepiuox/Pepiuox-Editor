# Especificaciones técnicas — PePiuoX Editor

> **Documento:** `docs/ESPECIFICACIONES_TECNICAS.md`
> **Producto:** PePiuoX Editor — CMS/editor web en PHP 8 sin Composer con MariaDB
> **Alcance:** describe lo que el sistema **es hoy** (código y base de datos observados), no lo que se pretendía.
> **Base documental:** `02-DOCS/wiki/ftd/inventario-tecnico-plataforma.md`, `02-DOCS/wiki/sdd/constitution.md`,
> `02-DOCS/wiki/ftd/portada-comercial-y-paquete-instalable.md`, `docs/INSTALL.md`, `docs/ROUTES.md` y lectura
> directa de `config/routes.php`, `config/config.php`, `index.php`, `public/index.php`, `app/Core/*`,
> `app/Middleware/*`, `app/bootstrap.php`, `db/db-full.sql`, `db/install/initial_database.sql`.
> **Sin secretos:** este documento no contiene contraseñas, tokens, hashes ni datos personales.

## Cómo leer este documento

Cada afirmación relevante lleva su procedencia:

| Etiqueta | Significado |
|---|---|
| **HECHO OBSERVADO** | Comprobado leyendo el archivo citado, contando con herramienta real o ejecutando una comprobación en esta sesión. |
| **INFERENCIA** | Conclusión razonada a partir de código o estructura, **pendiente de confirmación en ejecución**. |
| **NO VERIFICADO** | No se pudo o no se debió comprobar (por ejemplo, requiere DML, escritura en `settings` o una base viva). |
| **RECOMENDACIÓN** | Propuesta técnica con su motivo; no es estado actual. |

Donde el inventario y la ficha de portada discrepan (el recuento de rutinas), se toma el dato corregido y se explica.

---

## Índice

1. [Resumen ejecutivo técnico](#1-resumen-ejecutivo-técnico)
2. [Stack y versiones](#2-stack-y-versiones)
3. [Arquitectura por capas y flujo de una petición](#3-arquitectura-por-capas-y-flujo-de-una-petición)
4. [Estructura de directorios](#4-estructura-de-directorios)
5. [Modelo de datos](#5-modelo-de-datos)
6. [Contratos de la API pública `/api/v1`](#6-contratos-de-la-api-pública-apiv1)
7. [Sistema de contenido editable](#7-sistema-de-contenido-editable)
8. [Seguridad](#8-seguridad)
9. [Rendimiento y caché](#9-rendimiento-y-caché)
10. [Observabilidad y auditoría](#10-observabilidad-y-auditoría)
11. [Estrategia de pruebas y cobertura real](#11-estrategia-de-pruebas-y-cobertura-real)
12. [Paquete de instalación y respaldo](#12-paquete-de-instalación-y-respaldo)
13. [Decisiones de arquitectura (ADR-001 a ADR-008)](#13-decisiones-de-arquitectura-adr-001-a-adr-008)
14. [Deuda técnica y riesgos](#14-deuda-técnica-y-riesgos)
15. [Requisitos no funcionales: umbrales medibles](#15-requisitos-no-funcionales-umbrales-medibles)
16. [Trazabilidad requisito → componente → prueba](#16-trazabilidad-requisito--componente--prueba)

---

## 1. Resumen ejecutivo técnico

PePiuoX Editor es una aplicación **PHP 8 monolítica por capas**, sin Composer y sin *build step*, con un
autoloader PSR-4 propio, sobre **MariaDB**, que ofrece dos superficies en el mismo código:

- una **web pública** (portada resoluble por dato, páginas por slug, `robots.txt`, `sitemap.xml`, hoja de
  tema dinámica `/public-theme.css` y manifiesto `/site.webmanifest`);
- un **panel de administración** (`/admin/*`) con 224 rutas declaradas, 43 controladores, 41 servicios,
  24 modelos, 12 clases en `app/Core` y 70 vistas PHP.

El estado real, sin adornos (**HECHO OBSERVADO**):

| Dimensión | Estado |
|---|---|
| Alcance funcional | Amplio: páginas con constructor visual GrapesJS, plantillas, menús multinivel, versiones, comentarios anclados, colaboración en tiempo real, SEO, archivos, roles y permisos, 2FA, API v1 de solo lectura, backups, analítica, auditoría. |
| Madurez de ejecución | **Desigual.** `PermissionMiddleware` existe y **no se aplica en ninguna ruta**; tres familias de rutas no pueden resolverse por desajuste archivo↔clase; hay diez controladores muertos y ~590 KB de modelos/servicios duplicados. |
| Seguridad de cabeceras | Parcial: `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` vía `.htaccess`. **Sin CSP de documento y sin HSTS. Sin SRI en ningún CDN.** |
| Automatización de pruebas | Mínima: una suite Node sin base de datos que **pasa** (`tests/blocks-library`) y una suite que **requiere BD y escribe en `settings`** y no se ejecutó (`tests/public-theme`). Resto del código: sin pruebas. Sin CI. |
| Base de datos | 52 tablas, 2 vistas, 4 triggers, 5 rutinas; tres colaciones distintas; la aplicación entra con un usuario de solo `SELECT, INSERT, UPDATE, DELETE`. |
| Documentación | Buena en el nivel de proyecto (constitución de 26 principios con deuda trazada, inventario técnico, 22 fichas FTD), con deriva en los documentos derivados: `docs/ROUTES.md` declara 141 rutas frente a las 224 reales. |

**Veredicto técnico (INFERENCIA):** el sistema es **funcionalmente rico y operativamente frágil**. Lo que
falta no es funcionalidad nueva, es **gobierno de ejecución**: un punto único de autorización, disciplina de
nombres archivo↔clase, limpieza de residuos, CSP y pruebas de humo que detecten rutas que no resuelven.

---

## 2. Stack y versiones

### 2.1 Entorno de referencia y requisitos (**HECHO OBSERVADO**)

| Pieza | Requisito declarado | Entorno de referencia verificado | Fuente |
|---|---|---|---|
| PHP | **8.3 o superior** | **8.4.25** (CLI) | `docs/INSTALL.md` línea 24; ficha FTD §2 |
| MariaDB | **11 o superior** | **12.3.3** en `127.0.0.1:3306` | `docs/INSTALL.md` línea 25; ficha FTD §2 |
| Apache | **2.4 con `mod_rewrite`** | Apache 2.4 del *stack* local | `docs/INSTALL.md` línea 26 |
| Extensiones PHP | `pdo_mysql, mbstring, json, openssl, curl, gd, zip, intl, fileinfo` | no re-verificado en esta sesión | `docs/INSTALL.md` línea 32 |
| Composer | **no requerido** (decisión de arquitectura) | ausente: sin `composer.json`, sin `vendor/` utilizable | `docs/INSTALL.md`; ficha FTD §13.1 |

**MySQL no es sustituible sin migración**: el esquema usa colaciones `utf8mb4_uca1400_ai_ci`, propias de
MariaDB. Un `Unknown collation` es el síntoma esperado en MySQL (**HECHO OBSERVADO** en el volcado y en
`config/database.php`, que fija `utf8mb4_uca1400_ai_ci` por defecto).

### 2.2 Aplicación

| Componente | Versión / decisión | Evidencia |
|---|---|---|
| Versión de aplicación | `app_version = 1.0.0` | `config/config.php:7` |
| Zona horaria por archivo | `Europe/Madrid` | `config/config.php:9` |
| Zona horaria efectiva | la de `settings` (`SiteSettings::timezone()`) con caída a la del archivo | `app/Core/App.php:19-23` |
| `strict_types` | **inconsistente**: presente en `public/index.php`, `index.php`, `PublicThemeController`, `PublicThemeService`, `SettingsController`, `SiteSettings`; ausente en `PublicController`, `PagesController`, `PageRenderer`, `Router` | lectura de cabeceras |
| Modo depuración | `debug => true` en el archivo versionado | `config/config.php:11` |
| Sesión | nombre `PEP_SESSION`, 7200 s, `httponly: true`, `samesite: Lax`, `secure: false` | `config/config.php:14-22` |
| Subidas | 10 MB, extensiones `jpg,jpeg,png,gif,webp,svg,pdf,zip` (config) / lista amplia en `config/paths.php` | `config/config.php:38-42` |
| Hash de contraseña | `PASSWORD_DEFAULT`, `cost => 12` | `config/config.php:44-47`; constitución principio 18 |

### 2.3 Frontend (**HECHO OBSERVADO**)

| Recurso | Versión | Dónde | Nota |
|---|---|---|---|
| Bootstrap (web pública) | **5.3.3** | `views/public/layout.php` | CDN |
| Bootstrap (panel) | **5.3.8** | `views/layouts/admin.php`, `views/partials/*` | **Dos versiones del mismo framework en producción** |
| Bootstrap Icons | 1.11.3 | público y panel | CDN |
| Font Awesome Free | 6.5.2 | vistas de builder/editor | CDN |
| GrapesJS | 0.21.10 (+ 7 plugins) | builder de páginas y plantillas | CDN |
| CodeMirror | 5.65.16 | editor de código | CDN |
| Chart.js | 4.4.0 | analítica y dashboard de seguridad | CDN |
| Tipografías | Inter 400-800, JetBrains Mono 400-500 | Google Fonts | CDN |

**85 referencias a `cdn.jsdelivr.net` o `fonts.googleapis.com` en `views/` y cero atributos `integrity=`**
(recuento propio con búsqueda sobre `views/`). Toda la cadena de suministro frontend es externa y no
verificada por integridad.

---

## 3. Arquitectura por capas y flujo de una petición

### 3.1 Capas fijas (**HECHO OBSERVADO**)

| Capa | Ruta | Contenido |
|---|---|---|
| Entrada | `public/index.php` | autoloader, helpers, configuración, sesión, DI, rutas, despacho |
| Entrada (muerta en HTTP) | `index.php` (raíz) | mismo propósito, **sin contenedor DI** |
| Núcleo | `app/Core` | 12 clases: `App, Auth, CacheManager, CSRF, Database, Request, Response, Router, Security, Session, Validator, View` |
| Middleware | `app/Middleware` | 4 clases: `AuthMiddleware, ApiAuthMiddleware, PermissionMiddleware, RateLimitMiddleware` |
| Controladores | `app/Controllers` | 43 archivos |
| Servicios | `app/Services` | 41 archivos |
| Modelos | `app/Models` | 24 archivos |
| Helpers | `app/Helpers` | `functions.php`, `public_settings.php` |
| Vistas | `views/` | 70 archivos `.php`, 26 subdirectorios (incluye `public/` y `private/`) |
| Configuración | `config/*.php` | devuelven arrays: `config.php`, `database.php`, `paths.php`, `routes.php`, `env.php` |
| DI | `app/bootstrap.php` | `buildServiceContainer()` + `registerDependencies()` + `autoResolve()` |

### 3.2 Los dos puntos de entrada (**HECHO OBSERVADO**)

- `.htaccess` raíz reescribe todo lo que no empieza por `public/` hacia `public/`
  (`RewriteRule ^((?!public/).*)$ public/$1 [L,NC]`) y redirige `/public/...` a `/...` con 301.
- Por tanto **el front controller efectivo es `public/index.php`**. `index.php` de la raíz queda
  inalcanzable por HTTP en esa configuración; se conserva en el árbol y sigue registrando rutas por su
  cuenta. **Mantener dos arranques con dos autoloaders distintos es deuda activa**: un cambio en uno no se
  prueba en el otro.

### 3.3 Flujo de una petición (ASCII)

```
                       ┌──────────────────────────────────────────────┐
  Navegador  ──HTTP──▶ │ Apache 2.4 + mod_rewrite (.htaccess raíz)    │
                       │ · headers de seguridad (mod_headers)         │
                       │ · deniega config/ app/ views/ storage/ db/   │
                       │ · /uploads/*  → public/uploads.php           │
                       │ · resto       → public/$1                    │
                       └───────────────────┬──────────────────────────┘
                                           ▼
                       ┌──────────────────────────────────────────────┐
                       │ public/index.php  (front controller real)    │
                       │ 1 config/paths.php  → constantes y dirs      │
                       │ 2 spl_autoload_register  (App\ → app/)       │
                       │ 3 app/Helpers/functions.php  (e, url, ...)   │
                       │ 4 config/config.php  → array $cfg            │
                       │ 5 verifica config/database.php               │
                       └───────────────────┬──────────────────────────┘
                                           ▼
                       ┌──────────────────────────────────────────────┐
                       │ Session::start()  →  App::boot()             │
                       │  · zona horaria desde settings               │
                       │  · remember-me (AuthController::checkRemember)│
                       │  · locale de usuario + TranslationService    │
                       └───────────────────┬──────────────────────────┘
                                           ▼
                       ┌──────────────────────────────────────────────┐
                       │ app/bootstrap.php                            │
                       │ buildServiceContainer()  (singletons, tolerante│
                       │   a fallos: cada servicio puede quedar null)  │
                       │ registerDependencies()   (7 bindings + autoResolve)│
                       └───────────────────┬──────────────────────────┘
                                           ▼
                       ┌──────────────────────────────────────────────┐
                       │ Router::dispatch(Request)                    │
                       │  a) middlewares globales  (ninguno registrado)│
                       │  b) findRoute(method, uri)  → PRIMERA coincidencia│
                       │  c) middlewares de la ruta ($auth / $apiMW / rate)│
                       │  d) resolve(controller) + callMethod(...)    │
                       │     · inyecta Request, array $params,         │
                       │       y castea {id} a int si la firma lo pide │
                       └───────────────────┬──────────────────────────┘
                                           ▼
              ┌────────────────────────────┴─────────────────────────────┐
              ▼                                                          ▼
   ┌────────────────────────┐                          ┌──────────────────────────────┐
   │ Controlador            │                          │ Controlador                  │
   │ · CSRF::guard() si POST│                          │ · comprueba Auth::can(...)   │
   │ · Auth::can(...)       │                          │   (disperso, no sistemático) │
   └───────────┬────────────┘                          └──────────────┬───────────────┘
               ▼                                                      ▼
   ┌────────────────────────┐                          ┌──────────────────────────────┐
   │ Services / Models      │                          │ Response::json() / render()  │
   │ → Database (PDO)       │                          │ → exit                       │
   └───────────┬────────────┘                          └──────────────────────────────┘
               ▼
   ┌──────────────────────────────────────────────────────────────────────────────┐
   │ Database::connection()  singleton PDO                                        │
   │ ERRMODE_EXCEPTION · FETCH_ASSOC · EMULATE_PREPARES = false                   │
   │ credenciales desde el entorno o 01-TOOLS/mysql/.env (nunca en el repo)        │
   └──────────────────────────────────────────────────────────────────────────────┘
```

### 3.4 Inyección de dependencias: **parcial** (**HECHO OBSERVADO**)

`app/bootstrap.php` cablea explícitamente **siete** controladores (`PagesController`, `PageController`,
`UsersController`, `MenuController`, `TemplatesController`, `AuditController`, `ActivityLogsController`,
`CacheController`, `FilesController`). El resto entra por `autoResolve()` por reflexión, que puede devolver
`null` en parámetros anulables y **oculta** un fallo de construcción en lugar de propagarlo. Conclusión: la
DI **no es un patrón del sistema**, es una comodidad aplicada a un subconjunto.

### 3.5 Enrutado: primera coincidencia (**HECHO OBSERVADO**)

`Router::findRoute()` recorre `$this->routes` en orden de registro y devuelve la **primera** ruta cuyo patrón
case, sin ordenar por especificidad. Consecuencia directa: **el orden de `config/routes.php` es contrato**.
Las rutas literales deben ir antes de las parametrizadas; el archivo lo respeta casi siempre, pero **no siempre**
(ver riesgo R-09 en §14).

El *catch-all* `GET /{slug}` está declarado al final y absorbe cualquier slug no reservado.

---

## 4. Estructura de directorios

```
Pepiuox-Editor/
├── .htaccess                     # reescritura + cabeceras de seguridad
├── index.php                     # 2.º front controller: NO alcanzable por HTTP
├── install.php                   # residuo en la raíz (cambio sin confirmar del dueño)
├── README.md
├── app/
│   ├── bootstrap.php             # contenedor DI + resolver del Router
│   ├── Controllers/              # 43 archivos (incluye 10 muertos)
│   ├── Models/                   # 24 archivos
│   ├── Services/                 # 41 archivos
│   ├── Core/                     # 12 clases del núcleo
│   ├── Middleware/               # 4 middlewares
│   └── Helpers/                  # functions.php, public_settings.php
├── config/
│   ├── config.php                # configuración general (array)
│   ├── database.php              # lee credenciales del entorno, no las lleva
│   ├── env.php                   # cargador de .env
│   ├── paths.php                 # constantes de rutas y URLs
│   └── routes.php                # 224 rutas + endpoints cron
├── db/
│   ├── db-full.sql               # esquema + datos de referencia (VERSIONADO)
│   ├── db-full_.sql, _db-full_.sql # copias de trabajo
│   ├── backup/database_backup_20261031.sql
│   ├── install/initial_database.sql
│   └── migrations/               # 4 migraciones up/down + apply.php
├── docs/                         # INSTALL.md, PUBLIC_THEME.md, ROUTES.md, CSV por módulo
├── public/                       # document root recomendado
│   ├── index.php                 # FRONT CONTROLLER REAL
│   ├── page.php, uploads.php, .htaccess
│   └── assets/{css,js}
├── storage/{cache,logs}/         # creadas en tiempo de ejecución por config/paths.php
├── tests/{blocks-library,public-theme}/
├── uploads/                      # fuera del document root del código; servido por uploads.php
├── views/                        # 70 vistas en 26 subdirectorios
├── private/                      # copia obsoleta de assets + .htaccess (deuda)
├── 01-TOOLS/                     # bootstrap/ (create-admin.php), diagnostics/, mysql/, _TEMPLATE/
└── 02-DOCS/                      # constitution.md, inventario, fichas FTD
```

**Notas estructurales:**

- `views/` incluye un subdirectorio `private/` además de `public/`; conviene no confundirlo con la carpeta
  `private/` de la raíz.
- `config/paths.php` **crea directorios al cargarse** (`storage/cache`, `storage/logs`, `uploads`, etc.):
  importar la configuración tiene efecto secundario en el sistema de archivos.
- `01-TOOLS/` queda fuera del *document root* recomendado y `.htaccess` deniega `config|app|views|storage|db|vendor|.git|.env`.

---

## 5. Modelo de datos

Base `web_editor_db`. **HECHO OBSERVADO** (ficha FTD §2 y §14, corroborado por `grep` sobre `db/db-full.sql`):

| Objeto | Cantidad | Detalle |
|---|---:|---|
| Tablas base | **52** | ver agrupación por dominio abajo |
| Vistas | **2** | `daily_summary`, `user_activity_summary` |
| Triggers | **4** | `after_page_update`, `after_user_insert`, `after_user_update`, `before_user_delete` |
| Rutinas | **5** | procedimientos `cleanup_old_sessions`, `compute_analytics(IN p_days INT)`, `update_daily_stats`; funciones `get_active_users_count(days)`, `get_retention_rate(days)` |
| Claves foráneas | **51** en el volcado de referencia | mapa en §5.4 |
| Colaciones | **3 distintas** | `utf8mb4_uca1400_ai_ci` (45 tablas), `utf8mb4_unicode_ci` (4), `utf8mb3_uca1400_ai_ci` (3) |

**Corrección documentada sobre las rutinas:** un primer inventario midió «0 rutinas» porque consultó
`information_schema.routines` con la cuenta de la aplicación, que no puede verlas. La medición con cuenta
administrativa y el propio `db/db-full.sql` dan **5**. El dato válido es 5; el 0 era un artefacto de
privilegios.

**Eventos programados:** el volcado de referencia contiene además **3 `EVENT`** (`cleanup_old_backups`,
`cleanup_sessions_event`, `update_daily_stats_event`). Su presencia en la base viva **no se ha medido** en
esta sesión (**NO VERIFICADO**).

### 5.1 Las 52 tablas por dominio funcional

| Dominio | Tablas | Nº |
|---|---|---:|
| **Identidad y control de acceso** | `users`, `users_roles`, `users_permissions`, `users_role_permissions`, `user_sessions`, `user_logins`, `trusted_devices`, `two_factor_recovery_codes`, `login_attempts`, `blocked_ips`, `ban_history` | 11 |
| **Contenido y publicación** | `pages`, `page_versions`, `page_autosave`, `templates`, `template_versions`, `meta_tags`, `secure_pages` | 7 |
| **Navegación** | `menus`, `page_menu` | 2 |
| **SEO** | `redirects`, `seo_audit` | 2 |
| **Archivos** | `files`, `file_shares` | 2 |
| **Colaboración y comentarios** | `collaboration_sessions`, `collaboration_locks`, `collaboration_events`, `collaboration_messages`, `component_comments`, `comment_replies`, `comment_mentions` | 7 |
| **Componentes reutilizables** | `components` | 1 |
| **Configuración e i18n** | `settings`, `languages`, `translations`, `cache_settings` | 4 |
| **Analítica y métricas** | `analytics_events`, `visits`, `daily_stats`, `page_statistics`, `reports` | 5 |
| **Auditoría y actividad** | `audit_logs`, `activity_log`, `activity_logs`, `user_activities` | 4 |
| **API** | `api_applications`, `api_tokens`, `api_access_logs` | 3 |
| **Notificaciones** | `notifications` | 1 |
| **Respaldos** | `backups`, `backup_schedule` | 2 |
| **Caché de página en BD** | `page_cache` | 1 |
| **Total** | | **52** |

Observaciones de modelado que importan (**HECHO OBSERVADO**):

- **Tres tablas de log coexisten**: `audit_logs`, `activity_log` (singular, alimentada por los triggers de
  `users`) y `activity_logs` (plural), más `user_activities`. Es deuda de modelado: dos «auditorías» con
  propósitos solapados.
- `users` carga doble identidad de rol: la columna `role` (ENUM de texto) **y** `role_id` (FK a `users_roles`).
  `Auth::can()` resuelve el rol por **nombre** (`SELECT idRol FROM users_roles WHERE name = ?`), no por
  `role_id`: si el nombre del rol cambia, los permisos dejan de resolverse.
- `components` tiene 0 filas y su módulo no carga (§14, R-02): funcionalidad declarada, no operativa.
- `page_menu.slug` era UNIQUE global; la migración `2026_10_25_menu_system` lo sustituye por
  `UNIQUE(menu_id, slug)` y añade `UNIQUE(menu_id, page_id)`.

### 5.2 Vistas (**HECHO OBSERVADO**, definición en `db/db-full.sql`)

| Vista | Definición resumida | Uso previsto |
|---|---|---|
| `daily_summary` | Agrupa `users` por `DATE(created_at)` y cuenta altas totales y por rol (`Admin`, `Manager`, `Stantard User`, `Guest`). | Alta de usuarios por día. |
| `user_activity_summary` | `users` LEFT JOIN `user_logins` (solo `login_success = 1`): días activos, logins totales, primer y último login, duración media de sesión. | Actividad por usuario. |

Ambas son `ALGORITHM=UNDEFINED SQL SECURITY DEFINER`. Consecuencias: **se ejecutan con los privilegios del
`DEFINER`** (no del usuario que consulta) y el volcado conserva `DEFINER=`root`@`localhost`` en 14 sitios
(vistas, rutinas y triggers), lo que obliga a privilegios altos para restaurar con otra cuenta.

### 5.3 Triggers y rutinas

**Triggers (**HECHO OBSERVADO**):**

| Trigger | Momento / tabla | Efecto |
|---|---|---|
| `after_page_update` | AFTER UPDATE en `pages` | Si `updated_at` avanza, incrementa la versión mayor en `page_statistics` (`x.0 → x+1.0`) y refresca `updated_at`. |
| `after_user_insert` | AFTER INSERT en `users` | Inserta en `activity_log` (`user_created`) con `JSON_OBJECT(username, email, role)`. |
| `after_user_update` | AFTER UPDATE en `users` | Si cambian `username`, `email` o `role`, registra `user_updated` con `old_data` y `new_data`. |
| `before_user_delete` | BEFORE DELETE en `users` | Registra `user_deleted` con `old_data`. |

**Nota de privacidad:** los triggers escriben **email y rol** en `activity_log.new_data`. El principio 23 de la
constitución prohíbe registros con datos personales completos; aquí el email de la cuenta queda persistido en
la traza. Se documenta como hallazgo, no se corrige en este documento.

**Rutinas:**

| Rutina | Tipo | Qué hace |
|---|---|---|
| `cleanup_old_sessions()` | PROCEDURE | Borra `user_sessions` con `last_activity` anterior a 24 h. |
| `compute_analytics(IN p_days INT)` | PROCEDURE | Devuelve dos resultsets: eventos por día/tipo desde `analytics_events` y top 20 páginas por visitas/visitantes únicos. |
| `update_daily_stats()` | PROCEDURE | `INSERT ... ON DUPLICATE KEY UPDATE` en `daily_stats` con usuarios, altas, activos, logins correctos y fallidos del día. |
| `get_active_users_count(days INT)` | FUNCTION | Usuarios distintos con login correcto en los últimos *days*. |
| `get_retention_rate(days INT)` | FUNCTION | Cociente (usuarios con login en *days* / usuarios con alta anterior a *days*) × 100, 2 decimales. |

**Lo que no está probado:** la ficha FTD §10 declara expresamente que **no se ejecutó ninguna rutina
restaurada**. Están presentes con su definición íntegra; «ejecutable» **NO VERIFICADO**.

### 5.4 Mapa de claves foráneas (**HECHO OBSERVADO**, 51 restricciones en `db/db-full.sql`)

| Tabla origen | Columna | → Tabla destino | Borrado |
|---|---|---|---|
| `users` | `role_id` | `users_roles(idRol)` | SET NULL |
| `users` | `banned_by` | `users` | SET NULL |
| `users_role_permissions` | `role_id` | `users_roles(idRol)` | CASCADE |
| `users_role_permissions` | `permission_id` | `users_permissions(idPer)` | CASCADE |
| `user_sessions` | `user_id` | `users` | CASCADE |
| `user_logins` | `user_id` | `users` | CASCADE |
| `user_activities` | `user_id` | `users` | CASCADE |
| `trusted_devices` | `user_id` | `users` | CASCADE |
| `two_factor_recovery_codes` | `user_id` | `users` | CASCADE |
| `notifications` | `user_id` | `users` | CASCADE |
| `ban_history` | `user_id` / `performed_by` | `users` | CASCADE / SET NULL |
| `backups` | `created_by` | `users` | SET NULL |
| `reports` | `created_by` | `users` | SET NULL |
| `files` | `user_id` | `users` | CASCADE |
| `file_shares` | `file_id`, `shared_by`, `shared_with` | `files`, `users`, `users` | CASCADE |
| `pages` | `user_id` | `users` | CASCADE |
| `pages` | `template_id` | `templates` | SET NULL |
| `pages` | `parent_id` | `pages` | SET NULL |
| `page_versions` | `page_id` / `created_by` | `pages` / `users` | CASCADE / (sin acción) |
| `page_autosave` | `page_id`, `user_id` | `pages`, `users` | CASCADE |
| `page_cache`, `page_statistics`, `visits`, `collaboration_*`, `component_comments` | `page_id` | `pages` | CASCADE |
| `meta_tags` | `page_id` | `pages` | CASCADE |
| `page_menu` | `page_id` | `pages` | **NO ACTION** |
| `template_versions` | `template_id` | `templates` | CASCADE |
| `component_comments` | `user_id` / `resolved_by` | `users` | CASCADE / SET NULL |
| `comment_replies` | `comment_id` / `user_id` | `component_comments` / `users` | CASCADE |
| `comment_mentions` | `comment_id`, `reply_id`, `mentioned_user_id` | `component_comments`, `comment_replies`, `users` | CASCADE |
| `collaboration_events`, `_locks`, `_messages`, `_sessions` | `user_id` | `users` | CASCADE |
| `api_tokens` | `application_id` | `api_applications` | CASCADE |
| `api_access_logs` | `application_id` | `api_applications` | SET NULL |
| `activity_log`, `activity_logs` | `user_id` | `users` | SET NULL |

**Huecos de integridad observados (**INFERENCIA** a partir del volcado):**

1. `page_menu.menu_id` y `pages.menu_id` **no tienen FK en `db/db-full.sql`**; la migración
   `2026_10_25_menu_system` sí las declara (`fk_page_menu_menu`, `fk_page_menu_parent`, `fk_pages_menu_override`).
   Es decir, **el «esquema de referencia» versionado va por detrás de las migraciones** — contradice el
   principio 4 de la constitución («un cambio de esquema actualiza `db/db-full.sql` en el mismo cambio»).
2. `page_menu.page_id` usa `ON DELETE NO ACTION`, no `CASCADE`: borrar una página con ítem de menú falla
   en lugar de limpiar el ítem.

---

## 6. Contratos de la API pública `/api/v1`

### 6.1 Superficie (**HECHO OBSERVADO**, `config/routes.php:160-163`)

| Método | Ruta | Acción | Middleware |
|---|---|---|---|
| GET | `/api/v1/pages` | `ApiController::pagesIndex` | `ApiAuthMiddleware` |
| GET | `/api/v1/pages/{slug}` | `ApiController::pagesShow` | `ApiAuthMiddleware` |
| GET | `/api/v1/menus/{slug}` | `ApiController::menusShow` | `ApiAuthMiddleware` |
| GET | `/api/v1/me` | `ApiController::me` | `ApiAuthMiddleware` |

Además existen dos rutas JSON **sin autenticación** fuera de `/api/v1`: `GET /api/menu` y
`GET /api/menu/breadcrumb/{id}` (`MenuController::getMenuJson`, `getBreadcrumb`).

### 6.2 Autenticación (**HECHO OBSERVADO**, `ApiAuthMiddleware`, `ApiToken::validate`)

- Token **Bearer** en `Authorization`, con dos alternativas de compatibilidad: `?api_key=` y `?access_token=`.
- El token se guarda **hasheado con SHA-256** en `api_tokens.token`; se busca por `hash('sha256', $token)`.
  Bien: la base no permite recuperar el token en claro.
- Condiciones de validez en la misma consulta: `revoked = 0`, `expires_at > NOW()` y `api_applications.status = 'active'`.
- Lista blanca de IP opcional (`allowed_ips`, JSON) → 403 `IP_BLOCKED` si la IP no está.
- Límite por aplicación y por minuto, contado en `cache_settings` con clave `api:{application_id}:{Y-m-d H:i}`
  → 429 `RATE_LIMIT`.
- Registro de acceso en `api_access_logs` vía `register_shutdown_function` (endpoint, método, IP,
  user-agent, `request_data`, código y tiempo de ejecución). Si el registro falla, se ignora en silencio.

### 6.3 Envoltorio de respuesta (**HECHO OBSERVADO**)

Todas las respuestas de `/api/v1` usan el mismo sobre JSON:

```json
{ "success": true,  "data": ..., "total": 12 }
{ "success": false, "message": "…", "code": "AUTH_MISSING" }
```

Códigos `code` observados: `AUTH_MISSING` (401), `AUTH_INVALID` (401), `IP_BLOCKED` (403),
`RATE_LIMIT` (429), `Insufficient scope` (403, sin `code`). Códigos HTTP usados: 401, 403, 404, 429.

### 6.4 Detalle por endpoint

| Endpoint | Entrada | Salida | Scopes |
|---|---|---|---|
| `GET /api/v1/pages` | `?status=` (opcional) | Lista de hasta **100** páginas con `id, title, slug, description, status, is_published, created_at, updated_at`. `total` = longitud devuelta, no el total de la tabla. | `pages:read` o `*` |
| `GET /api/v1/pages/{slug}` | `{slug}` | `id, title, slug, description, content_html, content_css, status, created_at, updated_at`. 404 `Not found` si no existe o no está publicada. | **no comprobado en código** |
| `GET /api/v1/menus/{slug}` | `{slug}` de menú | Ítems resueltos por `MenuService::byGroup()` | **no comprobado en código** (el scope `menus:read` está documentado en `docs/ROUTES.md`, no aplicado) |
| `GET /api/v1/me` | — | `application`, `scopes` (array decodificado), `expires_at` | — |

**Hallazgo (HECHO OBSERVADO):** solo `pagesIndex()` verifica scopes. `pagesShow()`, `menusShow()` y `me()`
no comprueban el scope, de modo que un token con cualquier scope puede leer una página completa y un menú.
**RECOMENDACIÓN:** un único punto de comprobación de scopes en el middleware o en un método común.

**Fuera de contrato, pero observable:** `pagesIndex()` concatena `" WHERE status = ?"` a la consulta según la
presencia de `$status`. La parte variable es una constante del código, no entrada del usuario, y el valor va
parametrizado; **no es una inyección**, pero es el patrón que conviene no repetir (constitución, principio 17).

---

## 7. Sistema de contenido editable

### 7.1 Modelo (**HECHO OBSERVADO**)

Tabla `settings` — clave-valor plano, sin tabla por página ni JSON por página:

```sql
settings(id, `group` VARCHAR(50), `key` VARCHAR(100), value LONGTEXT,
         type ENUM('string','int','bool','json'), updated_at,
         UNIQUE KEY uniq_group_key (`group`,`key`))
```

**61 filas en 5 grupos** en la instalación de referencia: `general` (4), `seo` (8), `cache` (2), `mail` (6),
`public` (41).

| Pieza | Responsabilidad | Archivo |
|---|---|---|
| Escritura | `Setting::updateBatch()`: `INSERT ... ON DUPLICATE KEY UPDATE value, type` **dentro de transacción** | `app/Models/Setting.php` |
| Lectura agrupada | `Setting::all()`, `SettingService::all()` / `get()` / `set()` (con caché estático por petición) | `app/Models/Setting.php`, `app/Services/SettingService.php` |
| Lectura del tema | `PublicThemeService::all()`: una sola consulta `WHERE group = 'public'`, cacheada en estático, con degradación a `defaults()` si la BD falla | `app/Services/PublicThemeService.php` |
| Helpers de vista | `getPublicSettings()`, `public_setting()`, `public_logo()`, `public_logo_alt()`, `public_favicon_tags()`, `public_theme_css_url()`, `public_is_dark()` | `app/Helpers/public_settings.php` |
| Pantalla | `GET /admin/settings` con 11 pestañas (`general, seo, cache, mail, identity, logos, favicon, themes, colors, css, preview`) | `views/settings/index.php` |

**Tres capas de valor por defecto:** `PublicThemeService::defaults()` en código → filas de la migración → valor
guardado. Un valor vacío en claves que lo admiten significa «automático» (color de texto sobre superficie) o
«sin asset» (logos y favicons).

### 7.2 Grupos y escritura

| Grupo | Claves | Valores por defecto conocidos | Ruta de escritura | Validación |
|---|---:|---|---|---|
| `general` | 4 | `site_name`, zona horaria, idioma, descripción | `POST /admin/settings/update` | lista blanca `['general','seo','cache','mail']` |
| `seo` | 8 | títulos y descripciones por defecto, etc. | ídem | ídem |
| `cache` | 2 | TTL, activación | ídem | ídem |
| `mail` | 6 | remitente, SMTP | ídem | ídem |
| `public` | 41 | tema `corporativo`, 16 colores, 7 textos sobre superficie, 10 huecos de logo, 4 favicons, `custom_css`, `custom_css_enabled` | `POST /admin/settings/brand`, `/brand/asset`, `/brand/asset/delete`, `/brand/reset` | HEX normalizado, temas contra `THEME_PRESETS`, CSS por lista negra + acotado, contrastes AA calculados |

**Controles de escritura comprobados:**

- El grupo `public` **no** se puede escribir por `/admin/settings/update`: la lista blanca lo excluye
  explícitamente (evita *mass assignment* sobre el tema).
- Las cuatro rutas `/admin/settings/brand*` **vuelven a comprobar `settings.manage` dentro del controlador**,
  porque `$auth` solo garantiza sesión. Está comentado en `config/routes.php:357-359`.
- `custom_css` se valida con lista negra (sin `<`, `@import`, `expression(`, `javascript:`, `-moz-binding`),
  se limita a `MAX_CUSTOM_CSS_BYTES = 102400` y se acota a `body.public-site` con `scopeCss()`.
- `ensureContrast()` calcula el color de texto sobre superficie con mínimo 4.5:1 cuando la clave está vacía.

### 7.3 Superficie pública generada

| Recurso | Ruta | Caché |
|---|---|---|
| Hoja de estilos del tema | `GET /public-theme.css` (anónima) | `ETag` = `PublicThemeService::version()`, `Cache-Control: public, max-age=300, must-revalidate`, responde **304** si `If-None-Match` coincide |
| Manifiesto | `GET /site.webmanifest` (anónima) | `Cache-Control: public, max-age=300` |
| `robots.txt` | `GET /robots.txt` → `SeoController::robots` | — |
| `sitemap.xml` | `GET /sitemap.xml` → `PublicController::sitemap` | ver riesgo R-10 |

**Lo que NO existe (**HECHO OBSERVADO**):** ninguna clave de **texto editorial** para la web pública (no hay
`hero_title`, `cta_label`, `about_text`…). La migración `2026_10_30_public_surface_texts`, pese al nombre,
solo añade **colores de texto**. El pie del layout público tiene literales fijos en
`views/public/layout.php`: descripción, enlaces «Inicio / Acerca de / Privacidad / Términos» y el correo de
contacto. Consecuencia: cambiar el pie exige editar código.

---

## 8. Seguridad

### 8.1 Modelo de autorización (el punto más débil)

**Estado real (**HECHO OBSERVADO**):**

| Middleware | Existe | ¿Aplicado? | Cobertura |
|---|---|---|---|
| `AuthMiddleware` | Sí | **Sí** | variable `$auth` en todas las rutas `/admin/*` (sesión válida) |
| `ApiAuthMiddleware` | Sí | **Sí** | las 4 rutas `/api/v1/*` |
| `RateLimitMiddleware` | Sí | **Sí** | `login` 5/60 s, `register` 3/3600, `forgot` 3/3600, `reset` 5/3600, `2fa` 3/60 |
| `PermissionMiddleware` | Sí | **NO — cero apariciones en `config/routes.php`** | ninguna |

La autorización fina depende de **188 llamadas a `Auth::can()` dispersas** por controladores y vistas
(recuento propio sobre `app/`). No hay un punto único donde auditar autorización, y la cobertura es desigual:
hay acciones sensibles protegidas solo por **sesión + CSRF** — el caso citado en las fuentes es
`PagesController::delete()`, que solo llama a `CSRF::guard()`.

**Riesgo derivado (**INFERENCIA**):** cualquier usuario autenticado con acceso a la UI de páginas podría borrar
una página si conoce el `POST` y obtiene un token CSRF de su propia sesión. La UI no es un control de acceso.

`Auth::can()` resuelve el permiso con **dos consultas por llamada** (rol por nombre + permiso en
`users_role_permissions`), sin caché: en una vista con varias comprobaciones esto multiplica consultas, y el
resultado depende de que `users_roles.name` coincida con `users.role` (texto).

### 8.2 CSRF (**HECHO OBSERVADO**)

- `app/Core/CSRF.php`: `token()` (32 bytes aleatorios en sesión), `field()`, `verify()` con `hash_equals`,
  `guard()` que responde 403 JSON a peticiones AJAX y página 403 al resto.
- **135 llamadas a `CSRF::guard()`** en controladores según el inventario (recuento propio con búsqueda:
  136 coincidencias, que incluyen comentarios) y **97 formularios `method="post"`** en `views/`.
- `config/config.php` define `csrf.token_name = _csrf_token` y `lifetime = 7200`. El *lifetime* **no se
  aplica**: `CSRF::verify()` compara contra el token de sesión sin caducidad propia; el token vive lo que viva
  la sesión.
- **No hay verificación automática** de que cada ruta POST tenga guard: el ratio es alto, la garantía no existe.

### 8.3 Sesiones (**HECHO OBSERVADO**)

- `Session::start()` fija `name`, `lifetime`, `path`, `domain`, `secure`, `httponly`, `samesite` desde
  `config/config.php`. Hoy: `httponly = true`, `samesite = Lax`, **`secure = false`** (sitio servido por HTTP).
- `Session::regenerate()` llama a `session_regenerate_id(true)`. Su uso en el flujo de login **no se ha
  verificado en ejecución** (NO VERIFICADO).
- `Session::destroy()` vacía `$_SESSION`, borra la cookie y destruye la sesión.
- `Session::flash()` / `getFlash()` implementan mensajes de un solo uso.

### 8.4 Contraseñas y 2FA

| Control | Estado |
|---|---|
| Hash | `password_hash` / `password_verify` con `PASSWORD_DEFAULT` y `cost => 12` (`config/config.php:44-47`) |
| 2FA | `TotpService` + `two_factor_recovery_codes`; alta, verificación, códigos de recuperación, regeneración y descarga (`/admin/2fa*`) |
| Dispositivos de confianza | `TrustedDeviceService` + `trusted_devices`, con limpieza por cron |
| Intentos fallidos | `login_attempts` + `RateLimiter` (tabla `cache_settings`) + `blocked_ips` |
| Recordar sesión | `remember_token` (64) y `remember_expires` en `users`, autologin en `AuthController::checkRemember()` |
| Recuperación | `recovery_token` / `recovery_expires` con índice dedicado |

### 8.5 Cabeceras (**HECHO OBSERVADO**)

| Cabecera | ¿Presente? | Dónde |
|---|---|---|
| `X-Frame-Options: SAMEORIGIN` | Sí | `.htaccess` raíz (`mod_headers`) |
| `X-Content-Type-Options: nosniff` | Sí | `.htaccess`, `Response::render/json`, `PublicThemeController` |
| `X-XSS-Protection: 1; mode=block` | Sí | `.htaccess` (cabecera obsoleta; inocua en navegadores modernos) |
| `Referrer-Policy: strict-origin-when-cross-origin` | Sí | `.htaccess` |
| `Permissions-Policy: camera=(), microphone=(), geolocation=()` | Sí | `.htaccess` raíz (no en `public/.htaccess`) |
| `Header unset Server`, `unset X-Powered-By` | Sí | `.htaccess` |
| **`Content-Security-Policy` de documento** | **NO** | solo existe en `public/uploads.php` (para servir SVG) y en `uploads/.htaccess` |
| **`Strict-Transport-Security`** | **NO** | cero coincidencias en el repositorio |

Las cabeceras dependen de `mod_headers`; bajo `php -S` **no se emiten**. Esto significa que el servidor de
pruebas documentado en `docs/INSTALL.md` sirve la aplicación **sin** ninguna de estas protecciones.

### 8.6 Subidas y contenido inyectado

| Vector | Control observado | Valoración |
|---|---|---|
| Extensiones | `ALLOWED_EXTENSIONS` en `config/paths.php` (imágenes, documentos, comprimidos, audio, vídeo) y lista más corta en `config/config.php` | Dos listas distintas: cuál manda depende del punto que valide (deuda, R-12) |
| Tamaño | 10 MB en `config/config.php` | — |
| MIME / contenido | `ImageUploadValidator`, `AssetUploadService`, `FileService`, `SvgSanitizer` | Existe; cobertura por ruta **NO VERIFICADO** |
| Servido de `uploads/` | `public/uploads.php` con `nosniff` + `CSP: default-src 'none'; style-src 'unsafe-inline'; sandbox` para SVG | Correcto enfoque |
| `pages.content_html` | `PageRenderer::sanitizeHtml()`: lista negra + `strip_tags` | **Débil por diseño**: lista negra es derrotable |
| `pages.content_css` | Inyectado en `<style>` | Riesgo acotado |
| `pages.content_js` | Inyectado en `<script>` «prácticamente en crudo» (solo neutraliza `</script>`) | **XSS almacenado** para quien tenga permiso de edición |
| `pages.content_php` | **No se evalúa** en la superficie pública | Correcto |

### 8.7 Otros hallazgos de seguridad

- `app/Core/Security.php` implementa criptografía propia (`AES-256-GCM`, HMAC-SHA256 con sal, firmas) y, si
  no se le inyecta clave, **genera una aleatoria en el constructor** (`generateSecureKey()`): cualquier dato
  cifrado con esa instancia es indescifrable después. Sus métodos `encrypt`/`decrypt` solo se referencian desde
  `SecureEncryptionController` (controlador muerto) y `ApiAuthService` (servicio **sin referencias en el
  repositorio**). Riesgo latente, no explotado hoy.
- El mensaje de error de `Database::connection()` remite a `/install.php`, retirado y hoy 404: mensaje obsoleto
  que envía a un callejón sin salida.
- `Response::abort()` imprime el mensaje recibido escapado; `Router::handleException()` le pasa
  `$e->getMessage()`. Con `debug = true` (valor versionado), la aplicación muestra además trazas completas.

---

## 9. Rendimiento y caché

### 9.1 Capas de caché existentes (**HECHO OBSERVADO**)

| Capa | Implementación | Ámbito | Invalidación |
|---|---|---|---|
| En memoria (petición) | estáticos en `PageRenderer` (`$pageCache`, `$menuCache`), `PublicThemeService::all()`, `SettingService` | una petición | fin de petición |
| Archivos | `CacheService` + `CacheManager` (`storage/cache`, `storage/cache/pages`, `storage/cache/data`), con configuración desde `cache.*` de `settings` | entre peticiones | `clearPageCache`, `invalidateRelatedPages`, `invalidateTemplatePages`, `clearExpiredCache` |
| Base de datos | `page_cache` (contenido por página) y `cache_settings` (contadores y ventanas de rate limit) | persistente | por `page_id` / expiración |
| HTTP | `ETag` + `max-age=300` en `/public-theme.css` y `/site.webmanifest` | navegador / proxy | hash del contenido |
| Navegador (assets) | `asset()` sirve la ruta pelada | — | **sin** versionado por hash |

### 9.2 Lo medido y lo que no

- **Medido:** número de rutas (224), de controladores/servicios/modelos/vistas, tamaño de `views/` (~760 KB),
  tamaño de los assets propios (`maker.css` 63 833 B, `maker0.css` 82 776 B, `blocks-advanced.js` 38 520 B…),
  número de consultas por comprobación de permiso (`Auth::can` = 2).
- **No medido en esta sesión:** tiempos de respuesta, `EXPLAIN` de ninguna consulta, Lighthouse, tamaño de
  payload real de la portada, número de consultas por petición. **NO VERIFICADO.** No hay perfilado ni
  `EXPLAIN ANALYZE` en el repositorio.

### 9.3 Observaciones con impacto potencial (**INFERENCIA**, sin medición)

1. **`PageRenderer::getPageBySlug()`** usa `WHERE slug = ? AND ... OR slug LIKE ? AND ...` sin paréntesis:
   la precedencia de `OR` hace que el filtro `is_published = 1 AND status = 'published'` del primer bloque no
   aplique al segundo. Un slug que case por `LIKE` podría devolver una página **no publicada**. Requiere
   verificación con datos reales (riesgo funcional y de seguridad de contenido).
2. **`Auth::can()` sin caché**: dos consultas por llamada; con 188 llamadas potenciales repartidas por vistas,
   el coste crece con cada comprobación.
3. **`SELECT *` en `PageRenderer`** (trae `content_html`, `content_css`, `content_php`, `content_js`,
   `grapesjs_data` de golpe) para decidir la portada: lectura innecesariamente ancha.
4. **Sin minificación ni *fingerprint*** en assets propios: cada despliegue invalida (o no invalida) cachés sin
   control. `maker0.css` (82 KB) es casi con certeza una copia obsoleta de `maker.css` (63 KB).
5. **Una caché de archivos con escritura concurrente** en `storage/cache` sin bloqueo explícito observado:
   riesgo de carrera en escrituras simultáneas (NO VERIFICADO).

---

## 10. Observabilidad y auditoría

### 10.1 Qué se registra y dónde (**HECHO OBSERVADO**)

| Destino | Escritor | Contenido |
|---|---|---|
| `audit_logs` | `AuditService` (`log()`, `logInstance()`) | acción, usuario, IP, user-agent, datos anteriores/nuevos |
| `activity_log` (singular) | `ActivityLoggerService`, `RateLimitMiddleware` (bloqueos) y **los 4 triggers** de `users` | `user_created`, `user_updated`, `user_deleted`, `rate_limit_exceeded` |
| `activity_logs` (plural) | `ActivityLogsController` (lectura y limpieza) | misma familia, tabla distinta |
| `api_access_logs` | `ApiAuthMiddleware` | endpoint, método, IP, user-agent, payload, código, tiempo |
| `login_attempts` | flujo de autenticación | intento, éxito, IP |
| `user_logins`, `user_sessions` | autenticación y sesión | inicios y actividad de sesión |
| `analytics_events`, `visits`, `page_statistics`, `daily_stats` | servicios de analítica y rutina `update_daily_stats()` | métricas de uso |
| `storage/logs` | `config/config.php` (`logs.path`) y `error_log` de PHP | errores de aplicación |

### 10.2 Consultas y explotación

- Pantallas: `GET /admin/audit`, `/admin/audit/activity`, `/admin/analytics`, `/admin/security/dashboard`
  (+ `/realtime`), `/admin/security/login-attempts`, `/admin/security/trusted-devices`.
- Exportaciones: `GET /admin/audit/export`, `/admin/audit/export-json`, `/admin/audit/activity/export*`,
  `GET /admin/analytics/export`, `GET /admin/analytics/api` (JSON para Chart.js 4.4.0).
- Retención: `AuditController::clean` y `ActivityLogsController::clean` purgan por antigüedad
  (`AuditService::cleanOldLogs(int $days = 90)`); `update_daily_stats_event` consolida a diario.
- Vistas SQL de apoyo: `daily_summary`, `user_activity_summary`; funciones `get_active_users_count`,
  `get_retention_rate`.

### 10.3 Límites reales

- **No hay trazas distribuidas, métricas Prometheus/OpenTelemetry ni correlación de petición** (no existe
  `request_id` en el registro). La observabilidad es **de base de datos**, no de telemetría de servicio.
- **Duplicidad de auditorías:** `audit_logs` y las dos tablas `activity_log*` registran eventos solapados; no
  hay una fuente única de verdad.
- **Datos personales en la traza:** los triggers de `users` escriben `email` en `activity_log.new_data`
  (ver §5.3); los `api_access_logs` guardan el payload completo de la petición (`request_data`).
- `ApiAuthMiddleware` envuelve el registro en `try/catch` vacío: si el log falla, **no hay rastro del fallo**.

---

## 11. Estrategia de pruebas y cobertura real

### 11.1 Infraestructura (**HECHO OBSERVADO**)

No hay PHPUnit, ni `composer.json`, ni `phpunit.xml`, ni lanzador de pruebas, ni **CI**. Lo que existe:

| Suite | Tipo | Requiere BD | Ejecutada | Resultado |
|---|---|---|---|---|
| `tests/blocks-library/` (`install.test.js`, `normalize.test.js`, `classify.js`) | Node, sin navegador; simula `BlockManager` de GrapesJS en un `vm` | No | **Sí** (`node tests/…`) | **Pasa**: «todas las comprobaciones pasan», exit 0. Emite `[BlocksLibrary] No se pudo normalizar boom` por stderr, que es el error simulado a propósito |
| `tests/public-theme/` (`service-test.php`) | Servicio contra BD real | **Sí**, y escribe en `settings` (grupo `public`) | **No** | Salida esperada documentada: «118 pasan, 0 fallan». **No ejecutada**: modifica configuración real |
| `tests/public-theme/` (`e2e.ps1`) | HTTP extremo a extremo con `php -S` | Sí | **No** | Espera «73 pasan, 0 fallan». Requiere `public/_ppf_router.php`, **que no está en el repo** |

**Motivo de la no ejecución (declarado, no limitación técnica):** ambas suites escriben en `settings` y crean
o borran archivos en `uploads/`; su README pide ejecutarlas sobre una copia de la base.

### 11.2 Cobertura real

| Área | Cobertura |
|---|---|
| Biblioteca de bloques GrapesJS | Verificada (Node) |
| Servicio de tema público (defaults, 16 variables CSS, ámbito, validación HEX/CSS, SVG, subidas, favicon, manifiesto, helpers) | Escrita, **no ejecutada en esta sesión** |
| End-to-end del tema público | Escrita, **no ejecutada**; falta el router de pruebas |
| Controladores, modelos, rutas, autenticación, autorización, migraciones, cron, API, colaboración, backups | **Sin ninguna prueba automatizada** |

**Conclusión honesta:** «las pruebas pasan» solo es cierto para `tests/blocks-library`. Cualquier afirmación
sobre el resto del sistema es **verificación manual o lectura de código**, no prueba ejecutada.

### 11.3 Estrategia recomendada (**RECOMENDACIÓN**)

1. **Prueba de humo de rutas** (la de mayor relación valor/coste): recorrer las 224 rutas y comprobar que
   cada controlador declarado se instancia. Detecta de golpe R-02 (desajustes archivo↔clase) y R-03 (rutas
   con `require` a archivos inexistentes). Es CLI, no necesita navegador.
2. **Prueba de autorización** por permiso: matriz rol × acción esperando 403. Es la única forma de saber el
   estado real mientras `PermissionMiddleware` siga desconectado.
3. **Aislar la BD de pruebas**: las suites existentes deben correr contra una copia, no contra `web_editor_db`.
4. Añadir `php -l` en un script previo (principio 5 de la constitución) y fijarlo como puerta mínima.

---

## 12. Paquete de instalación y respaldo

### 12.1 Componentes (**HECHO OBSERVADO**)

| Artefacto | Ruta | Tamaño | Versionado en Git | Propósito |
|---|---|---:|---|---|
| Respaldo completo | `db/backup/database_backup_20261031.sql` | 1 221 355 B | **No** (sin rastrear) | Copia fiel de la base de origen |
| SQL de instalación limpia | `db/install/initial_database.sql` | 143 331 B | **No** (sin rastrear) | Esquema + catálogos, sin datos operativos |
| Esquema de referencia | `db/db-full.sql` | 868 157 B | **Sí** | Volcado histórico de referencia |
| Migraciones | `db/migrations/*.{up,down}.sql` + `apply.php` | 4 pares | — | Cambios versionados y reversibles |
| Primer administrador | `01-TOOLS/bootstrap/create-admin.php` | 11 645 B | **No** (sin rastrear) | Crea la cuenta inicial por consola |
| Guía | `docs/INSTALL.md` | 8 354 B | Sí | Requisitos, `.env`, BD, verificación, hardening |

### 12.2 Respaldo: lo que se ejecutó y verificó (**HECHO OBSERVADO**, ficha FTD §10)

| Paso | Resultado real |
|---|---|
| Volcado | `db/backup/database_backup_20261031.sql`, termina en `-- Dump completed on 2026-10-31 17:10:13` |
| Contenido | estructura + datos + 2 vistas + 4 triggers + 5 rutinas (`--routines --triggers --events`) |
| Restauración de prueba | importado en la base aislada `zz_verify_backup_20261031`, exit 0 |
| Comparación | `CHECKSUM TABLE` de las **52 tablas**: **0 diferencias** |
| Objetos restaurados | 52 tablas, 2 vistas, 4 triggers, 5 rutinas |
| Vistas consultables | `daily_summary` → 2 filas; `user_activity_summary` → 3 filas |
| Limpieza | base de verificación eliminada |
| No sobrescritura | el script añade sufijo `_1`, `_2`… si ya existe el archivo del día |

**Dos avisos operativos:** (1) el volcado conserva `DEFINER=`root`@`localhost`` en 14 objetos; restaurar con otra
cuenta exige privilegios altos o quitar el `DEFINER`. (2) `mysqldump` falla contra este MariaDB en Windows con
`SEC_E_NO_CREDENTIALS` si no se pasa `--skip-ssl`; el comando correcto está fijado en el procedimiento.

**Lo no verificado:** no se ejecutó ninguna rutina restaurada (§5.3).

### 12.3 SQL de instalación limpia

Construido **por lista blanca**, no por recorte: estructura con `mysqldump --no-data --routines --triggers` +
semillas `--no-create-info` de **siete tablas de catálogo** (`users_roles`, `users_permissions`,
`users_role_permissions`, `settings`, `languages`, `translations`, `backup_schedule`, 529 filas) +
neutralización de identidad del sitio de origen.

| Comprobación (ficha FTD §14) | Resultado |
|---|---|
| Importación sin `--force` | exit 0, sin errores |
| Objetos creados | 52 tablas · 2 vistas · 4 triggers · 5 rutinas |
| Catálogos | roles 5 · permisos 64 · matriz 269 · `settings` 61 · idiomas 7 · traducciones 122 · `backup_schedule` 1 |
| Datos operativos | `users` 0 · `pages` 0 · `templates` 0 · `api_tokens` 0 · `audit_logs` 0 |
| Identidad | `site_name` «PePiuoX Editor», zona `UTC`, logos y favicons vacíos |
| Contraseñas | **0 hashes** (recuento propio: cero coincidencias de `$2y$`) |

**Referencia documental (**HECHO OBSERVADO**, con matiz):** la cabecera del propio SQL remite a
`docs/MANUAL_DE_INSTALACION.md`. En la revisión inicial de este documento ese archivo **no existía**; en el
árbol de trabajo han aparecido después `docs/MANUAL_DE_INSTALACION.md` y `docs/MANUAL_DE_USUARIO.md`, ambos
**sin rastrear en git** (`git ls-files` vacío). Hoy existen; su contenido no forma parte de esta verificación.
La guía de instalación rastreada y contrastada es `docs/INSTALL.md`.

### 12.4 Primer administrador

`01-TOOLS/bootstrap/create-admin.php` (no `01-TOOLS/diagnostics/`, declarado cementerio de scripts retirados).
Verificado de extremo a extremo en base aislada: cuenta creada, rol `Super admin` (`role_id` 1), `is_active` 1,
hash **bcrypt coste 12**, `password_verify` acepta la correcta y rechaza otra, 135 permisos en su rol, y los
triggers se disparan sin error. Guardas: se niega a duplicar sin `--force`, rechaza contraseñas de menos de 12
caracteres, imprime la base destino y **exige escribir su nombre** para continuar (prevención del incidente del
2026-10-31, documentado en la ficha FTD §15).

---

## 13. Decisiones de arquitectura (ADR-001 a ADR-008)

Tomadas de `02-DOCS/wiki/ftd/portada-comercial-y-paquete-instalable.md` §11.3 y §13.4. Se reproducen resumidas;
la fuente manda.

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| **ADR-001** | La portada es una **vista propia** con contenido estructurado | Página CMS hecha con el constructor GrapesJS | El constructor es un lienzo libre: no garantiza secciones, responsive ni contraste AA. Se pidió una presentación, no un editor |
| **ADR-002** | El contenido vive en `settings` (grupo propio), con listas como **JSON validado** en una clave | Cinco tablas `landing_*` con CRUD propio | Ocho tarjetas no justifican cinco tablas; el patrón JSON ya existe en `api_applications.allowed_ips`. Coste: validación explícita en el servicio |
| **ADR-003** | Se reutiliza el **layout público** existente | Portada con su propio `<head>`, navbar y pie | Duplicar cabecera y pie duplicaría sesión, menú y temas: dos verdades que se desincronizan |
| **ADR-004** | `db/backup/` y `db/install/` | Árbol `database/` nuevo | Ya existen `db/migrations/` y `db/db-full.sql`; los nombres de archivo pedidos se respetan |
| **ADR-005** | `initial_database.sql` por **lista blanca** de tablas y filas | Truncar `db-full.sql` con `sed` | Una lista negra olvida la siguiente tabla con datos personales |
| **ADR-006** | Interruptor de portada **desactivado** por defecto | Activar la portada al instalar | Cambiar la home indexada de un sitio en producción es un cambio de comportamiento: lo decide el dueño |
| **ADR-007** | Respaldo con cuenta **administrativa** y `--skip-ssl` | Respaldo con `web_editor_app` | Esa cuenta no tiene `LOCK TABLES` ni `SHOW VIEW`: el volcado perdería vistas y triggers |
| **ADR-008** | Las acciones nuevas comprueban el permiso **dentro del controlador**, con prueba que ejerza el 403 | Confiar en `PermissionMiddleware` | El middleware está escrito pero desconectado: confiar en él es confiar en algo que no se ejecuta |

**Estado de estos ADR:** el documento de origen los marca como **propuesta pendiente de aprobación** (Punto de
decisión 0 sin firmar en su lista de comprobación). Se reproducen como **decisiones registradas**, no como
decisiones ya implementadas. En el código actual **no existe** `LandingContentService` ni `views/public/landing.php`
(**HECHO OBSERVADO**: no aparecen en el árbol), lo que confirma que ADR-001 a ADR-006 y ADR-008 describen trabajo
**no implementado**.

---

## 14. Deuda técnica y riesgos

Severidad: **Crítica** (explotable o pérdida de datos) · **Alta** (funcionalidad o control de seguridad roto) ·
**Media** (mantenibilidad, comportamiento inconsistente) · **Baja** (higiene).

### 14.1 Riesgos nombrados (los siete del encargo, sin suavizar)

| ID | Riesgo | Severidad | Evidencia | Recomendación |
|---|---|---|---|---|
| **R-01** | **`PermissionMiddleware` existe y no está aplicado en ninguna ruta.** La autorización por permiso se revalida de forma desigual en los controladores; hay acciones sensibles protegidas solo por sesión + CSRF (p. ej. `PagesController::delete()`). | **Alta** | 0 apariciones de `PermissionMiddleware` en `config/routes.php`; 188 llamadas `Auth::can()` dispersas | Aplicar el middleware ya escrito con `pages.manage`, `settings.manage`, `users.manage`… **empezando por las acciones destructivas**; es el cambio de mayor impacto y menor coste |
| **R-02** | **Rutas que fallan por desajuste archivo↔clase:** `/admin/components*` (clase `ComponentsController` en `ComponentController.php` — **7 rutas**), `/lang/{code}` (clase `LanguageController` en `LenguageController.php`), y `/page`, `/page/{slug}` (hacen `require` de `ROOT/page.php`, **inexistente**; el real es `public/page.php`). | **Alta** | Autoloader resuelve `App\Controllers\X` → `app/Controllers/X.php` por nombre; `Test-Path page.php` = falso | Renombrar los dos archivos; corregir el `require` o retirar las dos rutas legacy. Añadir la prueba de humo de §11.3 |
| **R-03** | **Diez controladores muertos** con clases duplicadas o sin rutas: `PagesController---.php`, `PagesController--.php`, `PagesController-.php`, `PagesController1.php`, `PagesController-ai.php`, `PasswordController--.php`, `PageController.php`, `EditorController.php`, `SecurityController.php` (clase `Security`), `SecureEncryptionController.php` (clase `SecureEncryption`). Más ~590 KB de modelos y servicios duplicados (`Page0/1`, `PageModel0/1/2/-ai`, `PageVersion0`, `PageService0/-ai`, `PagePublicervice-ai`, `PagePublic`). Nadie los referencia. | **Media** | Conteo propio: cada archivo declara la clase indicada; `EditorController` importa `App\Services\PagePublisher`, **clase que no existe** | Borrado controlado con `git rm` tras confirmar ausencia de referencias. Riesgo real: que el autoloader resuelva el gemelo equivocado y alguien edite el archivo muerto |
| **R-04** | **Sin CSP de documento y sin HSTS; todas las dependencias por CDN sin SRI.** El HTML de página se inyecta prácticamente en crudo en la web pública (`content_js` en `<script>`, `content_css` en `<style>`): **XSS almacenado** por quien tenga permiso de edición. | **Alta** | 0 coincidencias de `Content-Security-Policy` fuera de `uploads.php`/`uploads/.htaccess`; 0 de `Strict-Transport-Security`; 85 referencias CDN y **0** atributos `integrity=` | CSP con `default-src 'self'` + allowlist de `cdn.jsdelivr.net` y `fonts.googleapis.com`; SRI en cada `<link>`/`<script>`; HSTS cuando haya HTTPS; y decisión explícita sobre `content_js` (permiso dedicado o servido como archivo) |
| **R-05** | **`pages.startpage` no tiene unicidad garantizada:** guardar una nueva home **no desmarca la anterior**. Dos filas con `startpage = 1` hacen la portada indeterminada (gana la de menor `id` que devuelva el motor). | **Media** | `PagesController::update()` escribe `startpage` tal cual; el `handleStartpage()` solo existía en las variantes muertas; no hay índice único parcial | `UPDATE pages SET startpage = 0 WHERE id <> ?` en la misma transacción, o índice único sobre una columna generada. Verificar con dos filas a `1` |
| **R-06** | **`config/config.php` genera `cron_token` con `bin2hex(random_bytes(8))` en cada carga:** el token cambia en cada petición y **no sirve como secreto estable**. Los cinco endpoints `/cron/*` que lo comparan son inalcanzables de forma fiable. | **Media** | `config/config.php:75`; los endpoints recargan el archivo con `require` en cada petición | Leer `CRON_TOKEN` del entorno y abortar si falta. Documentar el cron real en `docs/INSTALL.md` |
| **R-07** | **`db/db-full.sql`, versionado, contiene los hashes bcrypt de las cuentas sembradas y secretos de aplicaciones de API.** Un hash confirmado se crackea offline sin prisa; un secreto de API confirmado permite firmar peticiones. | **Crítica** | `git ls-files db/db-full.sql` lo confirma rastreado; 3 coincidencias de hash bcrypt en el archivo; constitución, deuda nº 7 (abierta) | Sustituir los hashes por marcadores que fuercen cambio de contraseña y **rotar los secretos de API**; valorar sacar el archivo del árbol o limpiar el historial. Es cambio de esquema/semilla: **requiere decisión del dueño** |

### 14.2 Riesgos adicionales detectados en esta revisión

| ID | Riesgo | Severidad | Evidencia | Recomendación |
|---|---|---|---|---|
| **R-08** | **`GET /admin/audit/activity` no puede alcanzar su controlador:** `GET /admin/audit/{id}` está registrado **antes** (línea 414 vs 417) y `Router::findRoute()` devuelve la **primera** coincidencia; `{id}` castea `'activity'` a `int` → `0`. | **Alta** | Orden literal en `config/routes.php` + bucle de primera coincidencia en `Router::findRoute()` | Reordenar (literales antes que parametrizadas) y añadir la prueba de humo de rutas. **No verificado en ejecución**: la conclusión sale de la lectura del código |
| **R-09** | **El orden de `config/routes.php` es contrato no escrito.** Cualquier ruta literal añadida después de una parametrizada del mismo prefijo queda sombreada. | **Media** | Mismo mecanismo que R-08 | Comentario normativo en la cabecera del archivo + prueba que detecte rutas sombreadas |
| **R-10** | **`PublicController::sitemap()` usa `Database::fetchAll()` sin importar `App\Core\Database`**: si la ruta se alcanzara, sería error fatal. Hoy la tapa el archivo estático `public/sitemap.xml`. | **Media** | Lectura del controlador | Añadir el `use` o retirar la ruta; no dejar una ruta declarada que no puede ejecutarse |
| **R-11** | **`MediaModel` escribe en una tabla `media` que no existe** en las 52 tablas del esquema. Cualquier camino que lo invoque falla con error SQL. Está cableado en `bootstrap.php` dentro de `FileManagerService`. | **Media** | `app/Models/MediaModel.php` (`$table = 'media'`, `INSERT INTO media`); la tabla no aparece en `db/db-full.sql` ni en la lista de 52 | Crear la tabla, migrar a `files`, o retirar `MediaModel`. **Camino no ejecutado en esta sesión** (NO VERIFICADO) |
| **R-12** | **Dos listas de extensiones permitidas distintas**: `config/config.php` (8: imágenes, svg, pdf, zip) y `config/paths.php` (`ALLOWED_EXTENSIONS`, ~28 incluyendo `rar`, `7z`, `mov`, `avi`, `exe`-adyacentes). Cuál se aplica depende del punto que valide. | **Media** | Ambas constantes en el repo | Una sola fuente de verdad, consumida por todos los validadores |
| **R-13** | **Criptografía propia en `app/Core/Security.php`** con clave aleatoria generada en el constructor si no se inyecta: los datos cifrados son irrecuperables después. Solo se usa desde código muerto hoy. | **Media** | `Security::__construct()` → `generateSecureKey()`; `encrypt/decrypt` solo referenciados desde `SecureEncryptionController` y `ApiAuthService` (este último, **sin referencias**) | Eliminar la criptografía casera (constitución: sin criptografía propia) o inyectar clave desde el entorno |
| **R-14** | **`db/db-full.sql` va por detrás de las migraciones:** no contiene `menus.is_default/is_primary`, `page_menu.menu_id`, `pages.menu_id` ni sus tres FK. Contradice el principio 4. | **Media** | Comparación del volcado con `2026_10_25_menu_system.up.sql` | Regenerar el volcado tras aplicar migraciones, o declarar `db/install/initial_database.sql` como esquema de referencia |
| **R-15** | **`debug => true` versionado.** Con ese valor la aplicación muestra trazas, mensajes de excepción y trazas SQL al navegador. | **Alta** | `config/config.php:11`; `public/index.php:61-68` y `Router::handleException()` | Detectar el entorno; en producción `false` y respuestas genéricas |
| **R-16** | **Colaciones mezcladas** (45 `utf8mb4_uca1400_ai_ci`, 4 `utf8mb4_unicode_ci`, 3 `utf8mb3_uca1400_ai_ci`): las tres tablas `utf8mb3` (`users_permissions`, `users_roles`, `users_role_permissions`) **no pueden almacenar caracteres de 4 bytes**; comparar entre colaciones distintas puede fallar o impedir usar índices. Además fija MariaDB como único motor. | **Media** | Conteo propio sobre `db/db-full.sql`: 45/4/3 = 52 | Estandarizar en `utf8mb4_uca1400_ai_ci` con su propia migración (`ALTER TABLE … CONVERT TO`) y regenerar el volcado |
| **R-17** | **Sin CI, sin linter, sin PHPUnit**: el 90 % del código no tiene pruebas. | **Media** | Constitución, deuda nº 4 (riesgo aceptado) | Empezar por `php -l` + prueba de humo de rutas |
| **R-18** | **Documentación derivada desactualizada:** `docs/ROUTES.md` declara 141 rutas y 18 endpoints; `config/routes.php` tiene **224**. Los manuales de usuario e instalación existen pero **no están rastreados en git**, así que no forman parte de la entrega versionada. | **Baja** | Recuento propio: 224 declaraciones `$router->get/post/...`; `git ls-files` vacío para ambos manuales | Regenerar `ROUTES.md` desde el archivo de rutas; confirmar los manuales en git cuando se aprueben |
| **R-19** | **Dos versiones de Bootstrap** (5.3.3 público, 5.3.8 panel) y assets duplicados (`maker0.css` 82 KB obsoleto, `collabotarion.js` con errata en el nombre, `private/` con copia muerta). | **Baja** | Inventario §5.4-5.5 | Unificar versión y borrar copias muertas |
| **R-20** | **`config/database.php` documenta `/install.php` como vía de configuración** en el mensaje de error de `Database::connection()`, pero ese instalador está retirado y responde 404. | **Baja** | `app/Core/Database.php:27-29` | Corregir el mensaje a `01-TOOLS/mysql/.env` + `docs/INSTALL.md` |
| **R-21** | **`page_menu.page_id` con `ON DELETE NO ACTION`**: borrar una página con ítem de menú falla en vez de limpiar el ítem; el usuario ve un error de integridad. | **Baja** | FK `FK_pages_menu` en `db/db-full.sql` | Decidir el comportamiento (`CASCADE` o borrado lógico) y migrarlo |

### 14.3 Mapa de severidad (resumen)

```
CRÍTICA  ██  R-07 (hashes y secretos versionados)
ALTA     █████  R-01 (autorización) · R-02 (rutas rotas) · R-04 (CSP/SRI/XSS)
                R-08 (ruta sombreada) · R-15 (debug en producción)
MEDIA    ███████  R-03 · R-05 · R-06 · R-09 · R-10 · R-11 · R-12 · R-13 · R-14 · R-16 · R-17
BAJA     ████  R-18 · R-19 · R-20 · R-21
```

---

## 15. Requisitos no funcionales: umbrales medibles

Un requisito sin umbral no es verificable. Estos son los umbrales que este documento propone como criterio de
aceptación, con su método de comprobación. **Ninguno está medido todavía salvo donde se indica.**

| ID | Requisito | Umbral | Método de comprobación | Estado actual |
|---|---|---|---|---|
| RNF-01 | Compatibilidad PHP | Arranca y sirve `/` sin *warnings* en PHP ≥ 8.3 | `php -v` + portada en `php -S` | 8.4.25 en referencia |
| RNF-02 | Compatibilidad de motor | Importación completa sin error en **MariaDB ≥ 11** | importar `initial_database.sql` en base aislada | Verificado (exit 0) en 12.3.3 |
| RNF-03 | Integridad del esquema | 52 tablas, 2 vistas, 4 triggers, 5 rutinas tras importar | `information_schema` con cuenta administrativa | Verificado en la ficha FTD |
| RNF-04 | Autorización | **100 %** de las rutas `/admin/*` que mutan estado detrás de un permiso verificado en servidor | inventario ruta → `Auth::can`/middleware + prueba de 403 por rol | **0 % vía middleware** (R-01) |
| RNF-05 | CSRF | 100 % de los formularios `method="post"` con token válido y `guard()` en el controlador | recuento automatizado formulario ↔ controlador | 97 formularios / 135 guards (alto, sin garantía) |
| RNF-06 | Inyección SQL | **0** consultas con entrada de usuario concatenada | revisión de cada `Database::*` con interpolación | Sin hallazgos de inyección directa; `pagesIndex` concatena una constante |
| RNF-07 | Cabeceras | 100 % de respuestas HTML con `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y **CSP** | `curl -I` de `/`, `/login`, `/admin` | 4 de 5: **falta CSP** (y HSTS) |
| RNF-08 | Integridad de dependencias | 100 % de `<script>`/`<link>` de CDN con `integrity` y `crossorigin` | recuento de `integrity=` sobre `views/` | **0 de 85** |
| RNF-09 | Contraseñas | 100 % con `password_hash(PASSWORD_DEFAULT, cost 12)` | búsqueda de `md5`/`sha1` + revisión de altas y cambios | Cumple en código; `db-full.sql` conserva hashes sembrados (R-07) |
| RNF-10 | Sesiones | `httponly`, `samesite` y `secure` en producción | `Set-Cookie` observada | `httponly` y `samesite` sí; `secure` **false** |
| RNF-11 | Accesibilidad | WCAG 2.1 **AA**: contraste ≥ 4.5:1 en texto normal, foco visible, navegación por teclado, jerarquía de encabezados sin saltos | auditoría axe/Lighthouse en `/`, `/login`, `/admin` + medición de los pares de color realmente renderizados | Sin medir; `ensureContrast()` calcula el color pero **no se ha medido el resultado renderizado** |
| RNF-12 | Rendimiento de portada | Lighthouse ≥ 90 en las cuatro categorías; LCP < 2.5 s en 4G simulada | Lighthouse contra `/` | **No medido** |
| RNF-13 | Caché HTTP del tema | Segunda visita a `/public-theme.css` sin cambios → **304** | `curl` con `If-None-Match` | Implementado (ETag); no re-verificado en esta sesión |
| RNF-14 | Consultas por permiso | ≤ 0 consultas añadidas por comprobación repetida de un mismo permiso en la misma petición | instrumentar `Auth::can` | **2 consultas por llamada, sin caché** |
| RNF-15 | Pruebas | Cada ruta declarada instancia su controlador; 100 % de las rutas críticas con prueba de humo | suite CLI | 0 % |
| RNF-16 | Secretos | 0 secretos en el árbol versionado | búsqueda de patrones de hash/clave + `git log -p` sobre archivos de configuración | **Fallido**: `db/db-full.sql` (R-07) |
| RNF-17 | Documentación | 100 % de las rutas documentadas coinciden con `config/routes.php` | diff automatizado | 141 documentadas / 224 reales |
| RNF-18 | Migraciones | Cada migración con `up` y `down` idempotentes; esquema de referencia regenerado | aplicar y revertir en base aislada | 4 pares up/down; `db-full.sql` desactualizado (R-14) |

---

## 16. Trazabilidad requisito → componente → prueba

### 16.1 Requisitos funcionales de la plataforma (estado real)

| Req. | Requisito | Componente | Ruta | Prueba | Estado |
|---|---|---|---|---|---|
| RF-01 | Servir la portada desde una página publicada marcada como home | `PageRenderer::getHomePage()`, `PublicController::home()` | `GET /` | ninguna automatizada | Implementado; unicidad no garantizada (R-05) |
| RF-02 | Servir páginas por slug | `PageRenderer::getPageBySlug()`, `PublicController::show()` | `GET /{slug}` | ninguna | Implementado; **filtro `OR`/`LIKE` sospechoso** (§9.3) |
| RF-03 | Panel con CRUD de páginas y constructor visual | `PagesController`, `views/pages/{index,create,edit,builder,editor}.php`, GrapesJS 0.21.10 | `/admin/pages*` | ninguna | Implementado; borrado sin permiso (R-01) |
| RF-04 | Versionado de páginas con restauración | `VersionsController`, `page_versions` (55 filas) | `/admin/pages/{id}/versions*` | ninguna | Implementado |
| RF-05 | Plantillas con versiones y duplicado | `TemplatesController`, `TemplatesService`, `template_versions` (13 plantillas) | `/admin/templates*` | ninguna | Implementado |
| RF-06 | Menús multinivel gestionables | `MenuController`, `MenuService`, `menus`, `page_menu` | `/admin/menus*` | ninguna | Implementado |
| RF-07 | Usuarios, roles y matriz de permisos | `UsersController`, `RolesController`, `users_*` | `/admin/users*`, `/admin/roles*` | ninguna | Implementado |
| RF-08 | Autenticación con 2FA y remember-me | `AuthController`, `TwoFactorController`, `TotpService` | `/login`, `/admin/2fa*` | ninguna | Implementado |
| RF-09 | Ban y expiración automática | `BannedUsersController`, `BanService` | `/admin/users/banned*`, `/cron/process-expired-bans` | ninguna | Implementado; cron depende de R-06 |
| RF-10 | Archivos con subida validada y compartición | `FilesController`, `FileService`, `AssetUploadService`, `SvgSanitizer` | `/admin/files*` | ninguna | Implementado; dos listas de extensiones (R-12) |
| RF-11 | SEO: metas, redirecciones, sitemap, robots | `SeoController`, `SitemapService`, `MetaDescriptionGenerator` | `/admin/seo*`, `/robots.txt`, `/sitemap.xml` | ninguna | Implementado; `sitemap()` roto (R-10) |
| RF-12 | Comentarios anclados con menciones | `CommentsController`, `CommentService`, `component_comments` | `/admin/pages/{id}/comments*` | ninguna | Implementado |
| RF-13 | Colaboración en tiempo real (locks, cursores, mensajes) | `CollaborationController`, `CollaborationService` | `/admin/collaboration/{id}/*`, `/cron/cleanup-collaboration` | ninguna | Implementado |
| RF-14 | Notificaciones internas | `NotificationsController`, `NotificationService` | `/admin/notifications*` | ninguna | Implementado |
| RF-15 | Analítica con exportación | `AnalyticsController`, `AnalyticsService`, `Chart.js` | `/admin/analytics*` | ninguna | Implementado |
| RF-16 | Auditoría y log de actividad | `AuditController`, `ActivityLogsController` | `/admin/audit*` | ninguna | Implementado; `/admin/audit/activity` inalcanzable (R-08) |
| RF-17 | Respaldos manuales y programados | `BackupsController`, `BackupService` | `/admin/backups*`, `/cron/backup` | verificado en la ficha FTD (restauración 52/52 checksums) | Implementado y verificado |
| RF-18 | API v1 de solo lectura por token | `ApiController`, `ApiAuthMiddleware`, `ApiToken` | `/api/v1/*` | ninguna | Implementado; scopes incompletos (§6.4) |
| RF-19 | i18n y edición de traducciones | `TranslationsController`, `TranslationService`, `languages`, `translations` | `/admin/translations*`, `/lang/{code}` | ninguna | Implementado; `/lang/{code}` roto (R-02) |
| RF-20 | Personalización visual de la web pública | `PublicThemeService`, `SettingsController`, `PublicThemeController` | `/admin/settings*`, `GET /public-theme.css`, `GET /site.webmanifest` | `tests/public-theme/service-test.php` (**escrita, no ejecutada**), `e2e.ps1` (**no ejecutable: falta el router**) | Implementado; verificación automatizada pendiente |
| RF-21 | Componentes reutilizables | `ComponentController` (clase `ComponentsController`), `components` (0 filas) | `/admin/components*` | ninguna | **No operativo** (R-02 + 0 filas) |

### 16.2 Requisitos de la ficha de portada comercial (propuesta, no implementada)

| Req. FTD | Requisito | Componente previsto | Prueba prevista | Estado |
|---|---|---|---|---|
| RF-1 | Portada en `/` con interruptor | `PublicController::home()` + `landing.enabled` | manual | No implementado |
| RF-2 | Ocho secciones con `id` estable | `views/public/landing.php` | `tests/landing/check.php` | No implementado |
| RF-3 | Cabecera con logo, nombre y botón de acceso | layout público existente | manual | Parcial: el layout ya resuelve navbar y sesión |
| RF-4 | Todo texto editable desde el panel | `settings` grupo `landing` + pestaña «Portada» | manual: cambiar y recargar | No implementado |
| RF-5 | Tarjetas de características CRUD + orden | `landing.features` (JSON validado) | manual | No implementado |
| RF-6 | Colores y assets por el módulo existente | `PublicThemeService` (reutilizado) | `tests/public-theme` | **Ya existe** (módulo), verificado por código |
| RF-7 | Responsive a 360/768/1024/1440 | CSS con tokens `--public-*` | manual | No medido |
| RF-8 | Respaldo completo de la base | `db/backup/database_backup_YYYYMMDD.sql` | restauración + `CHECKSUM TABLE` | **Hecho y verificado** (52/52) |
| RF-9 | SQL limpio de instalación | `db/install/initial_database.sql` | importación en base aislada | **Hecho y verificado** (exit 0) |
| RF-10 | Sin datos operativos ni personales en el SQL | lista blanca de 7 tablas | recuento de filas + 0 hashes | **Verificado** |
| RF-11 | Manuales que describen solo lo existente | `docs/*` | contraste con `config/routes.php` | **Pendiente de contraste**: `docs/INSTALL.md` está rastreado y es la guía real; `docs/MANUAL_DE_USUARIO.md` y `docs/MANUAL_DE_INSTALACION.md` existen en el árbol pero **sin rastrear** y no se han contrastado aquí |
| RF-12 | Nada nuevo alcanzable que instale/diagnostique/pruebe | artefactos en `db/`, `docs/`, `views/` | revisión de rutas | Cumple por ubicación |

### 16.3 Requisitos no funcionales y su verificación

Ver §15: cada RNF lleva su umbral y su método. La trazabilidad completa queda así:

```
Constitución (26 principios)
   ├─ §1 Stack canon  ───────────────► RNF-01, RNF-02, RNF-03 (medido)
   ├─ §2 Calidad      ───────────────► RNF-15, RNF-18 (sin medir)
   ├─ §3 Convenciones  ──────────────► R-02, R-03, R-09 (incumplido)
   ├─ §5 Seguridad    ───────────────► RNF-04..RNF-10, RNF-16 (deuda abierta: R-01, R-04, R-07)
   ├─ §6 Accesibilidad ──────────────► RNF-11 (sin medir)
   └─ §7 Conocimiento  ──────────────► RNF-17 (desactualizado: 141 vs 224 rutas)
```

---

## Cierre: estado de este documento

- **Lo escrito aquí procede de** los tres documentos citados, siete archivos de código leídos completos
  (`config/routes.php`, `config/config.php`, `index.php`, `public/index.php`, `app/Core/Database.php`,
  `app/Core/Router.php`, `app/bootstrap.php`, entre otros), recuentos propios con herramienta y lectura de
  `db/db-full.sql` y `db/install/initial_database.sql`.
- **No se ejecutó** ninguna migración, ningún DML, ninguna rutina, ni ninguna de las dos suites que requieren
  base de datos. **No se modificó** ningún otro archivo del proyecto.
- **Lo que queda como «no verificado»:** ejecución de las 5 rutinas; comportamiento en runtime de R-02, R-08,
  R-10 y R-11; mediciones de rendimiento y accesibilidad; presencia de los 3 `EVENT` en la base viva; uso real
  de `session_regenerate_id()` en el login; y el resultado de las suites `tests/public-theme`.
- **Advertencia:** la ausencia de errores observados no demuestra ausencia de errores. Este documento describe
  el sistema tal como se leyó, no garantiza su corrección ni su seguridad.

---

## Actualización del 31 de octubre de 2026

Apartados nuevos o corregidos respecto al cuerpo de este documento.

### 1. Autorización: de «sesión» a «permiso por acción»

El cuerpo de este documento describe `PermissionMiddleware` como escrito pero **sin aplicar en ninguna
ruta**. Eso ya no es cierto.

**Diseño actual.** Un mapa único `config/permissions.php` asocia cada acción del panel
(`Controlador::método`) con el permiso que exige. `App\Core\Router`, después de resolver la ruta y
después de los middlewares de ruta (de modo que `AuthMiddleware` redirige antes al login), consulta el
mapa y ejecuta `PermissionMiddleware`. Una sola lista en lugar de 150 anotaciones que se desactualizan.

**Medición** (`tests/seguridad/check.php`):

| Concepto | Valor |
|---|---|
| Acciones declaradas en `config/routes.php` | 220 |
| Con permiso exigido | 184 |
| Justificadas sin permiso | 36 |
| Sin clasificar | **0** |

Las 36 se reparten así: 22 rutas públicas (portada, páginas, sitemap, `robots.txt`, acceso, registro,
recuperación, cambio de idioma y API v1 autenticada por token), 12 pantallas propias (tablero, perfil,
notificaciones, sesiones propias, segundo factor en el acceso) y 2 endpoints de cron autenticados por
token. El test **falla** si aparece una acción nueva sin clasificar.

**Por qué los endpoints de cron no están en el mapa:** se autentican por token, no por sesión. Se probó
a mapearlos y respondieron 403 — un usuario logueado no es un cron. Ese error se detectó al verificarlo
y está documentado en el propio archivo.

### 2. Token de las tareas programadas

`cron_token` ya no se genera con `random_bytes` en cada carga de `config/config.php` (lo que hacía
imposible acertar). Ahora se lee de `CRON_TOKEN` en el entorno y se compara con `hash_equals` a través de
`App\Core\CronToken`, usado por los seis endpoints. Sin token configurado, todos responden 401: cerrado
por defecto.

### 3. Limitador de peticiones: falla en cerrado

Si la construcción de los limitadores falla, `config/routes.php` ya **no** los deja vacíos (lo que
suponía aceptar intentos sin contar). Coloca `RateLimitUnavailableMiddleware`, que responde 503. En
tiempo de ejecución, un fallo del almacenamiento sí deja pasar la petición, y el log lo dice con esas
palabras: si la base de datos no responde, bloquear el acceso no protegería nada.

### 4. Portada comercial y sitemap

- `app/Services/LandingContentService.php`: resuelve y **valida** el contenido del grupo `landing` de
  `settings`, con valores por defecto en código para que la portada se pueda previsualizar antes de
  sembrar nada. Un JSON corrupto degrada al valor por defecto en vez de romper la página.
- `views/public/landing.php`: seis secciones con identificadores estables. No fija colores: usa los
  tokens de `PublicThemeService`.
- `POST /admin/settings/landing` guarda con validación por campo; un destino `javascript:` se rechaza y
  conserva el anterior.
- `app/Services/SitemapService.php` reescrito: resuelve los menús según el ámbito elegido, normaliza la
  página de inicio a `/` (antes anunciaba `/` y `/inicio`, contenido duplicado), escribe de forma
  atómica y devuelve un informe con el motivo de cada URL.

### 5. Contratos nuevos o modificados

| Método y ruta | Permiso | Notas |
|---|---|---|
| `GET /admin/settings/landing` | `settings.manage` | Editor del contenido |
| `POST /admin/settings/landing` | `settings.manage` | Guarda; CSRF obligatorio |
| `GET /admin/settings/landing/preview` | `settings.manage` | Pinta la portada aunque esté desactivada |
| `GET /admin/seo/sitemap` | `seo.manage` | Estado y configuración del sitemap |
| `POST /admin/seo/sitemap/settings` | `seo.manage` | Guarda ámbito y URL base, regenera |
| `GET /admin/seo/sitemap/regenerate` (POST) | `seo.manage` | Regeneración manual |

### 6. Deuda cerrada y deuda abierta

**Cerrada el 2026-10-31:** deuda 14 (middleware de permisos), deuda 15 (token del cron y endpoint de
baneos), más `VersionsController` sin permisos, el limitador que fallaba en abierto y `RateLimiter::cleanup()`,
que no existía y hacía que `/cron/cleanup-rate-limits` respondiera 500.

**Abierta:** sin CSP ni HSTS, CDN sin SRI, secretos de API en `db/db-full.sql` versionado (deuda 16) y
tres grupos de rutas que responden 500 por desajuste archivo↔clase (deuda 17).

### 7. Verificación

Tres baterías propias, ejecutadas: `tests/seguridad/check.php` (20), `tests/landing/check.php` (46) y
`tests/sitemap/check.php` (17). **83 comprobaciones, 0 fallos.** El detalle de lo ejecutado y de lo que
no se pudo comprobar está en `docs/INFORME_DE_VALIDACION.md`.

### 8. R-02 (rutas por desajuste archivo↔clase) — corregido

El apartado de riesgos describe R-02 como **Alta** y abierto. Ya no lo está.

- `app/Controllers/ComponentController.php` se renombró a `ComponentsController.php`.
- `app/Controllers/LenguageController.php` se renombró a `LanguageController.php`.
- Las rutas `/page` y `/page/{slug}` dejaron de hacer `require` de `public/page.php` desde la raíz: ahora
  son alias de `PublicController::home` y `::show`, sin duplicar el renderizador.

Efecto medido: `/lang/{code}` pasa de 500 a 302; `/page` y `/page/{slug}` de 500 a 200; las siete rutas
de `/admin/components*` resuelven su clase (y quedan detrás de `components.manage` por el mapa de
permisos). `tests/seguridad/check.php` incorpora cuatro comprobaciones que fallan si el desajuste vuelve.

**Sigue abierto en la misma familia:** `/admin/audit/activity` es inalcanzable porque `/admin/audit/{id}`
se registra antes y el router devuelve la primera coincidencia. No se tocó.