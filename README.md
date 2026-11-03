# PePiuoX Editor

Editor y CMS web en **PHP 8** y **MariaDB** con panel de administración y sitio público:
páginas, versiones, plantillas, menús, usuarios, roles, SEO, archivos, analítica y API.

> **Versión:** 1.1.1 (`config/config.php` → `app_version`) · **Estado:** en desarrollo activo,
> desplegable en un servidor propio con las precauciones de la sección
> [Limitaciones conocidas](#limitaciones-conocidas).
>
> El detalle de cada entrega está en [`CHANGELOG.md`](CHANGELOG.md). Cada publicación del
> código lleva su versión escrita en el archivo `VERSION` del paquete.

---

## Índice

- [Qué es](#qué-es) · [Uso](#uso) · [Funcionalidades](#características-principales)
- [Módulos](#módulos-disponibles) · [Tecnologías](#tecnologías) · [Arquitectura](#arquitectura)
- [Instalación](#instalación-rápida) · [Desarrollo](#desarrollo) · [Publicación](#publicación-de-una-versión)
- [Seguridad](#seguridad) · [Limitaciones](#limitaciones-conocidas) · [Licencia](#licencia)

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
| Presentación | **36 temas** listos, 16 colores por tema, 10 huecos de logotipo por contexto, favicon, CSS propio, contraste AA calculado |
| Identidad y ajustes | Nombre del sitio, descripción, idioma, zona horaria, correo saliente, SEO por defecto |
| Personas | Usuarios, 5 roles, 64 permisos, baneos con caducidad y dispositivos de confianza |
| Seguridad | 2FA (TOTP con códigos de recuperación), CSRF en formularios, limitador de peticiones, historial de accesos fallidos, **permisos por acción en todo el panel** |
| Medición externa | 9 proveedores gratuitos por identificador: GA4, Tag Manager, Yandex Metrica, Clarity, Plausible, Umami y las verificaciones de Google y Bing |
| Consentimiento | Aviso de cookies que **bloquea la medición hasta que el visitante acepta**; los scripts con cookies no se ejecutan antes |
| Observabilidad | Auditoría, registro de actividad, analítica de visitas, estadísticas diarias |
| Operación | Copias de seguridad programables, restauración, caché de páginas y de aplicación |
| Publicación | Generador del paquete de producción con verificación por reimportación, archivo `VERSION` y registro de cambios dentro |
| Integración | API `/api/v1` con aplicaciones, claves y tokens; internacionalización con traducciones |
| Portada comercial | Página de presentación editable desde el panel, con hero, tarjetas de funciones, especificaciones, beneficios y galería |
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
| Analítica | `/admin/analytics` · `/admin/analytics/proveedores` |
| Auditoría | `/admin/audit` |
| Seguridad y 2FA | `/admin/security/dashboard` · `/admin/2fa` |
| Sitio público | `/` y `/{slug}` |

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
├── README.md                 Este documento
├── CHANGELOG.md              Qué cambió en cada versión
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
│   ├── despliegue/           build-backup.php: genera y verifica el paquete de producción
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

## Uso

El recorrido habitual, de cero a un sitio publicado.

### 1. Primer acceso

Entra en `/login` con el superadministrador que creaste al instalar y **cambia la contraseña**
en `Perfil → Contraseña`. Después activa el segundo factor en `/admin/2fa`: te da un código QR y
**diez códigos de recuperación** que puedes descargar o copiar. Guárdalos fuera del servidor: si
pierdes el móvil y los códigos, no hay forma de entrar.

### 2. Contenido

| Paso | Dónde |
|---|---|
| Crear la página | `/admin/pages` → **Nueva página** |
| Escribir el contenido | Constructor visual: arrastras bloques de Bootstrap 5 y editas en el sitio |
| Guardar sin publicar | Queda como borrador; puedes verla en vista previa |
| Publicar | Cambia el estado y la página aparece en `/{slug}` |
| Volver atrás | `/admin/pages/{id}/versions`: cada guardado deja una versión restaurable |
| Reutilizar estructura | `/admin/templates`: guardas una página como plantilla y la usas de base |

El código (`<script>`) dentro del contenido se guarda tal cual y se publica sin filtrar: **la
confianza en quien edita es total**. Si el panel va a tener más de un editor, eso hay que cambiarlo
antes.

### 3. Navegación

`/admin/menus` monta el árbol de navegación. Los ítems se ordenan arrastrando. El menú marcado
como *en uso* es el que pinta el sitio público, y al cambiarlo se regenera el sitemap.

### 4. Identidad visual

`/admin/settings` agrupa la identidad del sitio, por pestañas:

- **General**: nombre, descripción, idioma y zona horaria.
- **Temas**: 36 paletas listas. Al aplicar una se cargan sus 16 colores y los textos sobre
  superficies vuelven a «automático». Puedes partir de un tema y ajustar después color a color.
- **Colores**: los 16 colores con su contraste calculado. El panel **avisa si un par baja de
  4,5:1**, que es el mínimo AA que exige el proyecto.
- **Logotipos y favicon**: diez huecos por contexto (cabecera, pie, correo, redes…).

### 5. Medición

`/admin/analytics/proveedores`. Se configura con el **identificador** de cada proveedor, nunca
pegando su código:

| Proveedor | Qué se pega |
|---|---|
| Google Analytics 4 | `G-XXXXXXXXXX` |
| Google Tag Manager | `GTM-XXXXXXX` |
| Yandex Metrica | el número de contador |
| Microsoft Clarity | el identificador del proyecto |
| Plausible · Umami | el dominio · el identificador y la URL del servidor |
| Search Console · Bing | la cadena de verificación de propiedad |

Un valor con el formato equivocado **se descarta al guardar** y el panel lo dice; en la lista
aparece marcado como *descartado* en vez de publicarse. Un identificador mal escrito no puede
romper la página ni colar código en ella.

Si activas Tag Manager, configura GA4 **dentro de GTM** y deja vacío el campo de Analytics: si no,
se cargaría dos veces.

### 6. Consentimiento de cookies

No hay nada que activar: **el aviso aparece solo** cuando hay algún proveedor configurado que
instale cookies (GA4, GTM, Yandex, Clarity). Hasta que el visitante acepta, esos scripts llegan
al navegador en cuarentena y **no se ejecutan**. Plausible y Umami no usan cookies, así que se
emiten siempre y no disparan el aviso.

La decisión se guarda un año. El enlace a la política de cookies se configura en la misma pantalla;
si lo dejas vacío, el aviso sale sin enlace. **El aviso cubre el consentimiento, no el contenido
legal**: el texto de la política es responsabilidad del titular.

### 7. Personas y permisos

`/admin/users` para las cuentas, `/admin/roles` para roles y sus permisos, y
`/admin/users/banned` para los baneos con caducidad. Una cuenta nueva no puede hacer nada hasta
que su rol tenga permisos.

### 8. Mantenimiento

| Tarea | Dónde |
|---|---|
| Copia de seguridad | `/admin/backups` (o `mysqldump`, ver más abajo) |
| Vaciar caché | `/admin/cache` tras cambiar plantillas, menús o ajustes |
| Ver qué pasó | `/admin/audit` para auditoría, `/admin/analytics` para visitas propias |
| Revisar el sitemap | `/admin/seo/sitemap` |

---

## Desarrollo

Cómo se trabaja en este repositorio.

### Entorno local

```powershell
php -S 127.0.0.1:8000 -t public      # sirve el sitio
php 01-TOOLS\mysql\test_connection.php   # comprueba la conexión a MariaDB
```

No hay Composer ni `node_modules` obligatorio: el autoloader PSR-4 es propio
(`app/` → `App\`). Las baterías de `tests/` son scripts PHP sueltos, no PHPUnit.

### Dónde va cada cosa

Antes de escribir, decide en qué capa cae:

| Si tu código… | Va en |
|---|---|
| Lee la petición, valida y elige la vista | `app/Controllers` |
| Aplica reglas de negocio, transacciones, integraciones | `app/Services` |
| Consulta o escribe la base | `app/Models` |
| Es infraestructura reutilizable | `app/Core` |
| Corre antes del controlador | `app/Middleware` |
| Es una función global de plantilla | `app/Helpers` |

Un controlador **no** hace SQL: delega en el modelo. Un modelo **no** decide permisos: eso es del
middleware y del mapa de `config/permissions.php`.

### Añadir una pantalla

1. **Ruta** en `config/routes.php`, con su middleware (`$auth` para el panel).
2. **Permiso** en `config/permissions.php` para esa acción concreta. El mapa es la autoridad:
   `tests/seguridad/check.php` **falla si aparece una acción sin clasificar**, así que no se puede
   olvidar.
3. **Método** en el controlador: valida, delega, elige la vista.
4. **Vista** en `views/`, y la lógica de datos en el servicio o modelo.
5. **Comprobación real**: pinta la vista y mira el HTML. Ver más abajo.

### Convenciones que no se negocian

- `declare(strict_types=1);` en todo archivo nuevo.
- **PDO preparado siempre**. Cero concatenación de entrada en SQL.
- **Escapar al imprimir** con `e()`, y con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` cuando no
  haya helper a mano.
- **En las vistas, las clases del núcleo van con espacio de nombres completo**:
  `\App\Core\CSRF::field()`, no `CSRF::field()`. Las vistas no declaran `namespace`, así que el
  nombre corto se resuelve en el espacio global y PHP falla con `Class "CSRF" not found`. Este
  fallo se coló en ocho vistas y se corrigió en la 1.1.0.
- **CSRF en todo formulario que cambia estado**.
- Contraseñas con `password_hash()`; nunca `md5` ni `sha1`.
- El HTML que viaja a la base no se confía: se escapa al pintarlo.

### Añadir un tema

Los temas viven en `app/Services/PublicThemeService.php`, constante `THEME_PRESETS`. Cada uno es:

```php
'mi-tema' => [
    'label'       => 'Mi tema',
    'description' => 'Una frase que lo describa.',
    'colors'      => [
        'color_primary'    => '#1f6feb',
        // … hasta 16 claves, las mismas en todos los temas
    ],
],
```

Reglas: **las 16 claves exactas** (el servicio las compara con `COLOR_LABELS`), HEX de seis
dígitos, y **contraste AA 4,5:1** entre texto y fondo. Un tema que no cumpla se ve mal y rompe la
constitución del proyecto, así que antes de darlo por bueno mide los pares de contraste.

### Cómo se comprueba

No hay CI. La comprobación es disciplina y se hace así:

```powershell
# 1. Sintaxis de todo el proyecto
Get-ChildItem app,views,config,01-TOOLS -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# 2. Baterías del proyecto
php tests\seguridad\check.php
php tests\rutas\check.php
```

Y para lo que las baterías no cubren, **se pinta la vista y se mira el HTML**: se incluye el archivo
en un script con los datos que espera y se comprueba que no lanza excepción y que contiene lo que
debe. Para lo que toca base de datos, se importa `db/install/initial_database.sql` en una base de
usos múltiples, se ejecuta el cambio y **se borra la base al terminar**. Nunca sobre una base con
datos que importen.

> **Un error de sintaxis no es un error lógico.** Que `php -l` pase solo significa que el archivo se
> puede leer. La 1.1.0 arregló cinco fallos que llevaban tiempo ahí y todos pasaban el análisis de
> sintaxis.

### Git

Mensajes en **Conventional Commits con gitmoji**, en español:

```
🐛 fix(2fa): activar deja de fallar por una transacción anidada
✨ feat(analytics): view de proveedores de medición externa
📄 docs(licencias): corrige el nombre del titular en la LAMC
```

Un commit, un motivo. Si el mensaje necesita un «y», probablemente son dos.

## Publicación de una versión

Cada entrega es un paquete de producción versionado en `backup/`.

```powershell
# 1. Sube la versión en config/config.php → app_version
# 2. Confirma ese cambio  ← hazlo ANTES de compilar
# 3. Anota el cambio en CHANGELOG.md
# 4. Genera el paquete
php 01-TOOLS\despliegue\build-backup.php --yes --desde-base=<base>
```

El generador **verifica lo que produce**: importa el SQL en una base aislada y comprueba que salen
las tablas, páginas y menús esperados; si no cuadra, falla en lugar de dejar un paquete roto.

Qué deja en el paquete:

| Archivo | Qué contiene |
|---|---|
| `VERSION` | Versión, fecha de compilación, commit, origen de datos y contenido |
| `INSTALAR.md` | Pasos de instalación y la contraseña temporal del superadmin |
| `CHANGELOG.md` | El registro de cambios de esa publicación |
| `db/install/initial_database.sql` | Estructura y catálogos, sin `DEFINER` |

Opciones: `--desde-base=<base>` se lleva el sitio ya montado, `--destino=<carpeta>` cambia el
destino, `--sin-db` omite la base y `--dry-run` no escribe nada.

**El orden importa**: confirmar la subida de versión y compilar después. Si compilas antes, el
`VERSION` apunta a un commit que todavía no dice esa versión.

**La contraseña del superadmin cambia en cada compilación.** Anótala antes de cada prueba y no
vuelvas a compilar con un paquete que ya estés usando.

---

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
- **Los identificadores de medición se validan contra un patrón** antes de publicarse y se escapan
  al imprimirse. Un valor con comillas o etiquetas no se guarda: se descarta con aviso. Es lo que
  impide que la pantalla de proveedores sirva para inyectar código en todas las páginas.
- **La medición con cookies no arranca sin consentimiento.** Los scripts salen como
  `type="text/plain"` y el navegador no los ejecuta hasta que el visitante acepta.
- La pantalla de proveedores exige el permiso `settings.manage`: configurar la medición es
  configuración del sitio, no una consulta de métricas.

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
| [`CHANGELOG.md`](CHANGELOG.md) | Qué cambió en cada versión y qué queda pendiente |
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
| [`docs/LICENCIA DE USO LIBRE Y ATRIBUCIÓN (LULA) v1.0.md`](docs/LICENCIA%20DE%20USO%20LIBRE%20Y%20ATRIBUCI%C3%93N%20%28LULA%29%20v1.0.md) | Licencia de uso y atribución |
| [`docs/LAMC PEPIUOX v1.0.md`](docs/LAMC%20PEPIUOX%20v1.0.md) | Licencia de atribución, mejora continua y responsabilidad |

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
| 2026-11-03 | 1.1.1 | El paquete de producción viaja con su registro de cambios dentro |
| 2026-11-03 | 1.1.0 | 36 temas (25 paletas de Bootswatch ajustadas a AA) · proveedores de medición externa · aviso de cookies con bloqueo real · generador del paquete con verificación y versionado · 2FA, CSRF y enlaces de notificación corregidos |
| 2026-10-31 | — | Paquete de instalación (`db/install/initial_database.sql`), respaldo verificado por restauración y `01-TOOLS/bootstrap/create-admin.php` |
| 2026-10-31 | — | Inventario técnico verificado de la plataforma |
| 2026-10-17 | 1.0.0 | Constitución ratificada; credenciales fuera de git; scripts de diagnóstico fuera de la raíz web; guía de instalación reescrita |
| anterior | — | Constructor visual de bloques Bootstrap 5, personalización visual del sitio público, sistema de menús |

El historial completo y las decisiones están en [`02-DOCS/wiki/`](02-DOCS/wiki/index.md).

## Licencia

El proyecto se publica bajo **dos textos propios**, ambos en `docs/`:

| Documento | Qué regula |
|---|---|
| [Licencia de Uso Libre y Atribución (LULA) v1.0](docs/LICENCIA%20DE%20USO%20LIBRE%20Y%20ATRIBUCI%C3%93N%20%28LULA%29%20v1.0.md) | Uso, copia, modificación y distribución, con la atribución que exige |
| [Licencia de Atribución, Mejora Continua y Exención de Responsabilidad (LAMC) PePiuoX v1.0](docs/LAMC%20PEPIUOX%20v1.0.md) | Atribución, aportación de mejoras y límites de responsabilidad |

Ambas exigen **atribución al titular**: `Jose Ricardo Mantilla Mantilla (PePiuoX)`.

Si vas a redistribuir el código o publicar una versión modificada, lee los dos textos antes. Y si
el proyecto va a usarse de forma comercial, conviene que un abogado los revise: son textos propios,
no licencias estándar reconocidas como MIT o GPL.

Las dependencias de terceros conservan su licencia. Las paletas de color de los temas proceden de
[Bootswatch](https://bootswatch.com) (licencia MIT); de ellas se usan solo los valores HEX, sin
copiar su CSS ni su código.
