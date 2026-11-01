# PePiuoX Editor

Editor y CMS web en **PHP 8** y **MariaDB** con panel de administración y sitio público:
páginas, versiones, plantillas, menús, usuarios, roles, SEO, archivos y API.

> **Versión:** 1.0.0 (`config/config.php` → `app_version`) · **Estado:** en desarrollo activo,
> desplegable en un servidor propio con las precauciones de la sección
> [Limitaciones conocidas](#limitaciones-conocidas).

---

## Qué es

PePiuoX Editor es una aplicación web monolítica, sin framework y sin Composer, que permite crear y
mantener un sitio web desde un panel de administración:

- Se escribe el contenido con un **constructor visual** (GrapesJS) y una biblioteca de bloques
  propios basada en Bootstrap 5.
- Cada página guarda **versiones** restaurables.
- La **identidad visual del sitio público** (logotipos, favicon, tema, 16 colores y CSS propio) se
  cambia desde el panel, sin tocar código.
- Usuarios, roles, permisos, 2FA, auditoría y un registro de actividad completan el gobierno del
  sistema.
- Una **API pública versionada** (`/api/v1`) expone páginas y menús a integraciones externas.

## Objetivos

1. Publicar y versionar contenido sin depender de un CMS de terceros ni de un servidor de
   aplicaciones.
2. Mantener una única base de datos MariaDB como fuente de verdad.
3. Poder instalarse en un servidor modesto: PHP + MariaDB + Apache, sin más.

## Características principales

| Área | Qué incluye |
|---|---|
| Contenido | Páginas con constructor visual, bloques reutilizables, plantillas, versiones con restauración, autoguardado, borradores y publicación |
| Navegación | Menús jerárquicos con ítems ordenables, caché de menú y sincronización automática |
| Presentación | Temas, 16 colores, 10 huecos de logotipo por contexto, favicon, CSS propio, contraste AA calculado |
| Identidad y ajustes | Nombre del sitio, descripción, idioma, zona horaria, correo saliente, SEO por defecto |
| Personas | Usuarios, 5 roles, 64 permisos, baneos con caducidad y dispositivos de confianza |
| Seguridad | 2FA (TOTP con códigos de recuperación), CSRF en formularios, limitador de peticiones, historial de accesos fallidos, **permisos por acción en todo el panel** |
| Observabilidad | Auditoría, registro de actividad, analítica de visitas, estadísticas diarias |
| Operación | Copias de seguridad programables, restauración, caché de páginas y de aplicación |
| Integración | API `/api/v1` con aplicaciones, claves y tokens; internacionalización con traducciones |
| Portada comercial | Página de presentación editable desde el panel, con hero, tarjetas de funciones, especificaciones, beneficios y galería
| Pie de página | Editor visual del pie: texto de marca, columnas de enlaces, contacto y copyright con marcadores |
| Sitemap | Generado desde el panel y regenerado al guardar páginas o cambiar el menú en uso |

## Módulos disponibles

Cada módulo tiene su ruta en `config/routes.php` y su manual de uso en
[`docs/MANUAL_DE_USUARIO.md`](docs/MANUAL_DE_USUARIO.md).

| Módulo | Ruta base |
|---|---|
| Panel principal | `/admin` |
| Perfil | `/admin/profile` |
| Usuarios · Baneados · Roles | `/admin/users` · `/admin/users/banned` · `/admin/roles` |
| Páginas · Constructor · Versiones | `/admin/pages` · `/admin/pages/{id}/builder` · `/admin/pages/{id}/versions` |
| Plantillas | `/admin/templates` |
| Menús | `/admin/menus` |
| SEO | `/admin/seo` |
| Archivos | `/admin/files` |
| Notificaciones | `/admin/notifications` |
| Caché | `/admin/cache` |
| Configuración e identidad visual | `/admin/settings` |
| Portada comercial | `/admin/settings/landing` · menú lateral **Web pública → Portada** · vista previa en `/admin/settings/landing/preview` |
| Pie de página | `/admin/settings/footer` · menú lateral **Web pública → Pie de página** |
| Sitemap | `/admin/seo/sitemap` |
| Copias de seguridad | `/admin/backups` |
| API | `/admin/api` |
| Traducciones | `/admin/translations` |
| Analítica y auditoría | `/admin/analytics` · `/admin/audit` |
| Seguridad y 2FA | `/admin/security/dashboard` · `/admin/2fa` |
| Sitio público | `/` y `/{slug}` |

> **No operativos hoy** (desajuste entre archivo y clase, en corrección): `/admin/components*`,
> `/lang/{code}` y `/page`, `/page/{slug}`. Ver [Limitaciones conocidas](#limitaciones-conocidas).

## Tecnologías

| Capa | Tecnología real |
|---|---|
| Lenguaje | PHP 8.3+ (verificado en 8.4.25) · `declare(strict_types=1)` en el código nuevo |
| Base de datos | MariaDB 11+ (verificado en 12.3.3), PDO con `ERRMODE_EXCEPTION` y `EMULATE_PREPARES => false` |
| Servidor web | Apache 2.4 con `mod_rewrite` (el `.htaccess` de la raíz resuelve todo), o nginx con la reescritura equivalente |
| Dependencias | **Ninguna en tiempo de ejecución**: autoloader PSR-4 propio. No hay Composer |
| Interfaz pública | Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 (CDN) |
| Interfaz del panel | Bootstrap 5.3.8, GrapesJS 0.21.10, CodeMirror 5.65.16, Chart.js 4.4.0 |
| Tipografías | Inter y JetBrains Mono |

## Arquitectura

Monolito por capas, sin framework. Una petición recorre siempre el mismo camino:

```
  Navegador
      │
      ▼
  .htaccess (raíz)  ──reescribe──▶  public/index.php      ← front controller REAL
                                          │
                                          ├─ app/bootstrap.php   (inyección de dependencias)
                                          ├─ config/paths.php · config/config.php
                                          ├─ App\Core\Session → App\Core\App::boot()
                                          ├─ App\Core\Router + App\Core\Request
                                          ├─ config/routes.php  (224 rutas, con middleware)
                                          │
                                          ├─ App\Middleware\{Auth,ApiAuth,Permission,RateLimit}
                                          ▼
                                    App\Controller
                                          │
                                          ├─ App\Service   (reglas, transacciones)
                                          ├─ App\Model     (acceso a datos)
                                          └─ App\Core\Database (PDO preparado)
                                          │
                                          ▼
                                     views/… (HTML)
```

> El `index.php` de la raíz existe y arranca la aplicación, pero bajo el `.htaccess` de la raíz
> **no se ejecuta**: todo se reescribe hacia `public/`. Es código muerto por HTTP.

Capas fijas, por convención del proyecto:

| Carpeta | Responsabilidad |
|---|---|
| `app/Controllers` | Reciben la petición, validan, delegan y eligen la vista |
| `app/Services` | Reglas de negocio, transacciones, integraciones |
| `app/Models` | Consultas y persistencia |
| `app/Core` | Router, Request, Response, Session, Auth, CSRF, Validator, View, Database |
| `app/Middleware` | Autenticación, autorización, límite de peticiones |
| `app/Helpers` | Funciones globales (`e()`, `url()`, `asset()`, …) |

## Estructura de directorios

```
PePiuoX-Editor/
├── index.php                 Front controller de la raíz (no recibe tráfico bajo el .htaccess)
├── .htaccess                 Reescribe todo hacia public/ y emite cabeceras de seguridad
├── public/                   Document root recomendado
│   ├── index.php             Front controller real
│   ├── page.php · uploads.php · sitemap.xml
│   └── assets/{css,js,js/blocks}
├── app/                      Código de la aplicación (ver tabla de capas)
├── config/                   config.php · database.php · env.php · paths.php · routes.php · permissions.php · services.php
├── views/                    Plantillas por módulo + layouts/ + partials/ + public/
├── db/
│   ├── db-full.sql           Esquema de referencia (incluye rutinas, vistas y triggers)
│   ├── migrations/           Migraciones up/down + apply.php
│   ├── install/
│   │   └── initial_database.sql   Instalación limpia: estructura + catálogos (sin datos de nadie)
│   └── backup/
│       └── database_backup_YYYYMMDD.sql
├── 01-TOOLS/                 Fuera de la raíz web — ver 01-TOOLS/*/README.md
│   ├── bootstrap/            create-admin.php (primer Super Admin) · seed-pages.php (páginas base)
│   ├── mysql/                .env (ignorado por git) · .env.example · test_connection.php
│   └── diagnostics/          Scripts retirados; se conservan por historial
├── docs/                     Manuales, rutas, estructura y tema público
├── 02-DOCS/wiki/             Memoria del proyecto: constitución, decisiones y ficha por cambio
├── storage/                  cache/ · logs/ · backups/ · versions/  (escribible)
├── uploads/                  Medios subidos (escribible)
└── tests/                    blocks-library (Node) · public-theme (requiere BD)
```

## Requisitos técnicos

- **PHP 8.3+** con `pdo_mysql`, `mbstring`, `json`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo`
- **MariaDB 11+** — el esquema usa colaciones `utf8mb4_uca1400_ai_ci`, propias de MariaDB.
  **MySQL no sirve tal cual.**
- **Apache 2.4** con `mod_rewrite`, o nginx con la reescritura equivalente
- Carpetas escribibles: `storage/`, `uploads/`, `db/backup/`

## Instalación rápida

```powershell
# 1. Credenciales (el .env no se versiona)
Copy-Item 01-TOOLS\mysql\.env.example 01-TOOLS\mysql\.env
#    edita DB_NAME, DB_USER y DB_PASSWORD

# 2. Base de datos: estructura + catálogos, sin datos de nadie
mysql -u <admin> -p < db\install\initial_database.sql

# 3. Primer administrador (imprime la base destino y pide confirmarla)
php 01-TOOLS\bootstrap\create-admin.php --username=admin --email=tu@dominio.com --generate

# 4. Comprobar la conexión
php 01-TOOLS\mysql\test_connection.php

# 5. Servir
php -S 127.0.0.1:8000 -t public
```

Y abre `http://127.0.0.1:8000/login`.

La guía completa —servidor web, privilegios mínimos de base de datos, SSL, tareas programadas,
restauración y solución de errores— está en
[`docs/MANUAL_DE_INSTALACION.md`](docs/MANUAL_DE_INSTALACION.md).

## Configuración inicial

| Qué | Dónde |
|---|---|
| Credenciales de base de datos | `01-TOOLS/mysql/.env` (ignorado por git) o variables de entorno del proceso |
| Token de las tareas programadas | `01-TOOLS/mysql/.env` → `CRON_TOKEN`. **Sin él, todos los `/cron/*` responden 401** |
| Nombre del sitio, descripción, idioma, zona horaria | `Configuración → General`, o `config/config.php` como valor por defecto |
| Identidad visual del sitio público | `Configuración → Identidad visual / Logotipos / Favicon / Temas / Colores / CSS` |
| Depuración y URL base | `config/config.php` → `debug`, `app_url` |
| Sesiones (Secure cuando haya HTTPS) | `config/config.php` → `session` |

## Acceso al sistema

- Sitio público: `/`
- Inicio de sesión: `/login`
- Panel: `/admin`

## Seguridad

Implementado y comprobable en el código:

- Consultas con **PDO preparado** en toda la capa de datos.
- **Permisos por acción en todo el panel.** Un mapa único (`config/permissions.php`) asocia cada acción a su permiso y el router lo aplica antes de ejecutar el controlador: 184 de 220 acciones están protegidas y las 36 restantes están justificadas (rutas públicas, pantallas propias, cron por token). `tests/seguridad/check.php` falla si aparece una acción nueva sin clasificar.
- **CSRF** en los formularios que cambian estado (97 formularios, 135 comprobaciones en controladores).
- Contraseñas con `password_hash()` (`PASSWORD_DEFAULT`, coste **12**); nunca MD5 ni SHA1.
- Sesiones con `httponly` y `samesite`; `secure` se activa al servir por HTTPS.
- `AuthMiddleware` en todo `/admin/*`, `ApiAuthMiddleware` en `/api/v1/*`, limitador de peticiones en login, registro, recuperación y 2FA.
- **El limitador falla en cerrado**: si no se puede construir, las rutas sensibles responden 503 en lugar de aceptar intentos sin contar.
- **Tareas programadas por token**: `CRON_TOKEN` en el entorno, comparado con `hash_equals`. Sin token configurado, todos los `/cron/*` rechazan la petición.
- Cabeceras `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` y `Permissions-Policy`
  emitidas por `.htaccess`.
- Credenciales fuera del repositorio; la aplicación usa una cuenta de base de datos con solo
  `SELECT, INSERT, UPDATE, DELETE`.

**Pendiente y conocido:** no hay `Content-Security-Policy` ni `Strict-Transport-Security`, y los CDN
no llevan `integrity` (ver [Limitaciones conocidas](#limitaciones-conocidas)).

## Copias de seguridad y restauración

```powershell
# Respaldo completo, con vistas, rutinas y triggers
# --skip-ssl es obligatorio en Windows con MariaDB: sin él, mysqldump falla
mysqldump --user=<admin> --password --host=127.0.0.1 --port=3306 --skip-ssl `
  --single-transaction --skip-lock-tables --routines --triggers --events `
  --default-character-set=utf8mb4 --hex-blob --complete-insert `
  --result-file=db/backup/database_backup_$(Get-Date -f yyyyMMdd).sql web_editor_db

# Restauración en una base NUEVA (nunca sobre la de producción sin querer)
mysql -u <admin> -p -e "CREATE DATABASE restauracion CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci"
mysql -u <admin> -p restauracion < db/backup/database_backup_20261031.sql
```

`db/install/initial_database.sql` **no es un respaldo**: instala limpio. `db/backup/` guarda copias
fieles. El detalle —incluido el aviso sobre `DEFINER=root@localhost` conservado en el volcado— está
en el manual de instalación.

## Documentación relacionada

| Documento | Qué es |
|---|---|
| [`docs/MANUAL_DE_INSTALACION.md`](docs/MANUAL_DE_INSTALACION.md) | Instalación, despliegue, respaldo y restauración |
| [`docs/MANUAL_DE_USUARIO.md`](docs/MANUAL_DE_USUARIO.md) | Uso del panel, módulo a módulo, con procedimientos |
| [`docs/ESPECIFICACIONES_TECNICAS.md`](docs/ESPECIFICACIONES_TECNICAS.md) | Arquitectura, modelo de datos, contratos, deuda y riesgos |
| [`docs/INFORME_DE_VALIDACION.md`](docs/INFORME_DE_VALIDACION.md) | Qué se ejecutó, qué salió y qué no se pudo comprobar |
| [`02-DOCS/wiki/sdd/constitution.md`](02-DOCS/wiki/sdd/constitution.md) | Las 26 reglas no negociables del proyecto |
| [`02-DOCS/wiki/index.md`](02-DOCS/wiki/index.md) | La memoria del proyecto: por dónde empezar a leerla |
| [`02-DOCS/wiki/ftd/inventario-tecnico-plataforma.md`](02-DOCS/wiki/ftd/inventario-tecnico-plataforma.md) | Inventario verificado de módulos, contenido editable y residuos |
| [`docs/ROUTES.md`](docs/ROUTES.md) | Rutas de la aplicación |
| [`docs/PUBLIC_THEME.md`](docs/PUBLIC_THEME.md) | Personalización visual del sitio público |
| [`docs/BLOCK_LIBRARY.md`](docs/BLOCK_LIBRARY.md) | Biblioteca de bloques del constructor |
| [`docs/INSTALL.md`](docs/INSTALL.md) | Guía breve de instalación y endurecimiento |

## Limitaciones conocidas

Se declaran en voz alta porque afectan a cómo se opera la plataforma, no porque estén resueltas:

1. **Sin CSP ni HSTS**, y todas las dependencias vienen de CDN sin `integrity`. El HTML de página se
   inyecta prácticamente en crudo en el sitio público.
2. **Ningún fallo funcional conocido de rutas.** Las que fallaban por desajuste archivo↔clase
   (`/admin/components*`, `/lang/{code}`, `/page*`) y la pantalla de actividad de auditoría, que quedaba
   tapada por `/admin/audit/{id}`, **ya funcionan** y están cubiertas por `tests/rutas/check.php`.
3. **No hay CI ni PHPUnit.** Lo automatizado son las baterías del propio proyecto:
   `tests/landing`, `tests/sitemap`, `tests/seguridad` y `tests/blocks-library`.
4. **Residuos:** 10 controladores sin uso y ~590 KB de modelos y servicios duplicados.
5. `pages.startpage` no tiene unicidad garantizada: guardar una nueva portada no desmarca la anterior.
6. **El historial de git conserva secretos antiguos.** Los volcados versionados ya no llevan hashes ni
   filas de aplicaciones de API, y los tokens filtrados se rotaron en la base. Pero los commits
   anteriores siguen conteniéndolos: borrarlos del historial exige reescribirlo. Los **respaldos**
   (db/backup/) ya no se versionan.
7. **El cron necesita `CRON_TOKEN`.** Sin ese valor los seis endpoints responden 401 a propósito.

Resueltas el 2026-10-31 y verificadas: ① el middleware de permisos ya está en vigor en todo el panel
(deuda 14), ② el token del cron es estable y `/cron/process-expired-bans` exige credencial (deuda 15),
③ `VersionsController` comprueba permisos y el limitador falla en cerrado, ④ las tres rutas que daban 500 por desajuste archivo↔clase y la pantalla de actividad de auditoría funcionan (deudas 17 y 18), ⑤ los volcados versionados ya no llevan hashes ni secretos de API y los tokens filtrados están rotados (deudas 7 y 16). El detalle está en
[`02-DOCS/wiki/sdd/constitution.md`](02-DOCS/wiki/sdd/constitution.md).

## Versiones e historial de cambios

| Fecha | Versión | Cambio |
|---|---|---|
| 2026-10-31 | — | Paquete de instalación (`db/install/initial_database.sql`), respaldo verificado por restauración y `01-TOOLS/bootstrap/create-admin.php` |
| 2026-10-31 | — | Inventario técnico verificado de la plataforma |
| 2026-10-17 | 1.0.0 | Constitución ratificada; credenciales fuera de git; scripts de diagnóstico fuera de la raíz web; guía de instalación reescrita |
| anterior | — | Constructor visual de bloques Bootstrap 5, personalización visual del sitio público, sistema de menús |

El historial completo y las decisiones están en [`02-DOCS/wiki/`](02-DOCS/wiki/index.md).

## Licencia

El repositorio **no incluye ningún archivo de licencia**, así que no hay licencia declarada. Hasta que
se añada una, los derechos quedan reservados por quien es titular del código. Si se va a distribuir o
publicar, hay que elegir y añadir una licencia explícitamente.
