# Manual de instalación — PePiuoX Editor

> **Ámbito:** instalación de PePiuoX Editor en un servidor propio (Apache o nginx) con PHP 8 y MariaDB.
> **Estado de este documento:** escrito contra el árbol de trabajo actual del proyecto. Cada dato lleva
> su etiqueta cuando importa:
>
> - **HECHO OBSERVADO** — comprobado leyendo un archivo del repositorio o ejecutando un comando real.
> - **INFERENCIA** — conclusión razonada, pendiente de confirmación en tu servidor.
> - **RECOMENDACIÓN** — propuesta con su motivo, no una obligación del código.
>
> Las partes que se ejecutaron de verdad en la máquina donde se redactó esto están en
> [Comprobación de este manual](#comprobación-de-este-manual).

---

## Índice

1. [Requisitos mínimos](#1-requisitos-mínimos)
2. [Requisitos recomendados](#2-requisitos-recomendados)
3. [Versiones y dependencias](#3-versiones-y-dependencias)
4. [Configuración del servidor web](#4-configuración-del-servidor-web)
5. [Preparación de la base de datos](#5-preparación-de-la-base-de-datos)
6. [Importación de `initial_database.sql`](#6-importación-de-initial_databasesql)
7. [Configuración del `.env`](#7-configuración-del-env)
8. [Usuario de base de datos con privilegios mínimos](#8-usuario-de-base-de-datos-con-privilegios-mínimos)
9. [Permisos de carpetas](#9-permisos-de-carpetas)
10. [Instalación de dependencias](#10-instalación-de-dependencias)
11. [Dominio](#11-dominio)
12. [SSL/HTTPS y qué cambia en la sesión](#12-sslhttps-y-qué-cambia-en-la-sesión)
13. [Tareas programadas](#13-tareas-programadas)
14. [Primer acceso y creación del administrador](#14-primer-acceso-y-creación-del-administrador)
15. [Verificación del funcionamiento](#15-verificación-del-funcionamiento)
16. [Actualización del sistema](#16-actualización-del-sistema)
17. [Restauración de un respaldo](#17-restauración-de-un-respaldo)
18. [Solución de errores frecuentes](#18-solución-de-errores-frecuentes)
19. [Limitaciones conocidas y endurecimiento pendiente](#19-limitaciones-conocidas-y-endurecimiento-pendiente)
20. [Comprobación de este manual](#comprobación-de-este-manual)

---

## 1. Requisitos mínimos

**Requisito declarado por el proyecto** (`docs/README.md`, cabecera de `db/install/initial_database.sql`):

| Componente | Mínimo | Por qué |
|---|---|---|
| **PHP** | **8.3+** | El código usa `declare(strict_types=1)`, tipos de retorno, `readonly`, `str_starts_with` y `match` |
| **MariaDB** | **11+** | El esquema usa colaciones `utf8mb4_uca1400_ai_ci`, que **solo existen en MariaDB**. **MySQL no sirve tal cual.** |
| **Apache** | **2.4** con `mod_rewrite` | El `.htaccess` de la raíz reescribe todo hacia `public/` |
| **Composer** | **no se necesita** | No hay `composer.json`, ni `vendor/`, ni referencias a su autoloader |
| **Cliente MySQL/MariaDB** | cualquiera de la misma versión mayor | Solo para importar, respaldar y restaurar por consola |

**Extensiones PHP obligatorias** (declaradas en `docs/README.md`):

```
pdo_mysql  mbstring  json  openssl  curl  gd  zip  intl  fileinfo
```

Comprueba que están todas antes de seguir:

```powershell
# Windows PowerShell
php --version
php -m | Select-String -Pattern 'pdo_mysql|mbstring|json|openssl|curl|gd|zip|intl|fileinfo'
```

```bash
# Linux / bash
php --version
php -m | grep -E 'pdo_mysql|mbstring|json|openssl|curl|gd|zip|intl|fileinfo'
```

> **HECHO OBSERVADO:** de esas nueve extensiones, el código de `app/` usa de verdad `pdo_mysql`,
> `mbstring`, `json`, `openssl` (`openssl_encrypt` en cifrado de secretos de API), `gd`
> (redimensionado de imágenes), `zip` (`ZipArchive` en la caché y en la librería de bloques),
> `fileinfo` (validación de MIME en subidas) e **`intl`** (`transliterator_transliterate` en
> `app/Models/Page.php:155`, que convierte el título en un slug sin acentos).
>
> **`curl` es la única que no se usa** (0 coincidencias de `curl_*` en `app/`). Sigue instalándola:
> está declarada como requisito y quitarla rompería el contrato del proyecto.
>
> **Corrección de una revisión anterior de este mismo manual:** una primera pasada contó `intl` como
> «0 usos» porque el grep buscaba `IntlDateFormatter`, `NumberFormatter`, `Collator` y `Locale::`, y
> el proyecto la usa por otra vía. Sin `intl`, la generación de slugs falla. No es opcional.

---

## 2. Requisitos recomendados

**Entorno de referencia donde se verificó el proyecto** (HECHO OBSERVADO en la máquina de desarrollo):

| Componente | Versión observada |
|---|---|
| PHP | **8.4.25** (CLI, NTS, Visual C++ 2022 x64) |
| MariaDB | **12.3.3** |
| Cliente `mysql` | 15.2 (MariaDB 12.3.3) |
| `mysqldump` | 10.20 (MariaDB 12.3.3) |
| Apache | **2.4.68** (Win64) |
| Node.js (solo pruebas) | 26.5.0 |

Recomendaciones de dimensionado (RECOMENDACIÓN, no medidas en este proyecto):

| Recurso | Recomendado | Motivo |
|---|---|---|
| PHP | 8.4 o superior, con OPcache activo | El proyecto se validó en 8.4.25 |
| Memoria PHP (`memory_limit`) | 256 MB o más | Editar páginas grandes con GrapesJS y procesar imágenes |
| `upload_max_filesize` / `post_max_size` | 12 MB o más | `config/config.php` limita las subidas a **10 MB** (`uploads.max_size`) |
| `max_execution_time` | 60 s | Generar un respaldo o un sitemap puede tardar |
| Disco | 2 × el tamaño de la base + `uploads/` | Respaldo y espacio de trabajo |
| Backend de PHP | `mod_php` o PHP-FPM | Ambos leen configuración en cada petición, así que no requieren reinicio al rotar contraseñas |

---

## 3. Versiones y dependencias

### Dependencias de PHP

**Ninguna.** HECHO OBSERVADO: no hay `composer.json`, no hay `vendor/`, no hay `vendor/autoload.php` en
el repositorio. El autoloader es propio, está en `public/index.php` y mapea los prefijos `App\Core\`,
`App\Models\`, `App\Controllers\`, `App\Services\`, `App\Middleware\` y `App\` contra `app/`.

### Dependencias de frontend

HECHO OBSERVADO: las librerías de navegador se cargan desde CDN, no desde disco:

| Librería | Versión fijada | Dónde |
|---|---|---|
| Bootstrap | 5.3.8 (panel) y 5.3.3 (builder) | `views/*/admin.php`, `views/*/builder.php` |
| Bootstrap Icons | 1.11.3 | `views/` |
| CodeMirror | 5.65.16 | editor de código |
| GrapesJS | 0.21.10 | editor visual |
| Chart.js | 4.4.0 | dashboard |

Ninguna lleva atributo `integrity` (SRI). Ver [limitación 2](#19-limitaciones-conocidas-y-endurecimiento-pendiente).

### Dependencia de desarrollo

Node.js **solo** para las pruebas de `tests/blocks-library` (`node:test`). No hace falta en el servidor
de producción.

---

## 4. Configuración del servidor web

### 4.1. Qué se sirve y qué no

| Forma de servir | Document root | Mecanismo |
|---|---|---|
| **Recomendada** | `<proyecto>/public` | Lo que está fuera de `public/` no es alcanzable por HTTP. `public/.htaccess` resuelve las rutas |
| Alternativa | `<proyecto>` (raíz) | El `.htaccess` de la raíz bloquea `config/`, `app/`, `views/`, `storage/`, `db/`, `vendor/`, `.git/`, `.env` y reescribe internamente hacia `public/` |

**Nunca** deben ser alcanzables: `config/`, `app/`, `views/`, `storage/`, `db/`, `01-TOOLS/`,
`02-DOCS/` ni ningún `.env`.

**Punto de entrada real:** `public/index.php`. El `index.php` de la raíz es **código muerto cuando se
sirve por web**: la regla 6 del `.htaccess` reescribe `/index.php` a `public/index.php`, así que esa
copia nunca se ejecuta por HTTP (HECHO OBSERVADO leyendo `.htaccess` y ambos `index.php`).

### 4.2. Apache con el `.htaccess` del proyecto

El `.htaccess` de la raíz (141 líneas, HECHO OBSERVADO) hace, en este orden: pasa `Authorization` a PHP,
bloquea carpetas sensibles, bloquea extensiones sensibles, filtra inyecciones en la query string, corta
métodos HTTP no usados, resuelve `/uploads/...` hacia `public/uploads.php`, elimina `/public/` de las
URLs con un 301, deja pasar `install.php` y reescribe todo lo demás hacia `public/`. Después aplica
headers de seguridad, compresión y caché.

**Virtual host mínimo (document root recomendado):**

```apache
<VirtualHost *:80>
    ServerName ejemplo.com
    ServerAlias www.ejemplo.com
    DocumentRoot /var/www/pepiuox-editor/public

    <Directory /var/www/pepiuox-editor/public>
        Options -Indexes -ExecCGI +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # El .env y las carpetas de trabajo no se sirven aunque alguien suba un nivel
    <Directory /var/www/pepiuox-editor>
        Require all denied
    </Directory>
    <Directory /var/www/pepiuox-editor/public>
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/pepiuox-error.log
    CustomLog ${APACHE_LOG_DIR}/pepiuox-access.log combined
</VirtualHost>
```

Activa los módulos (Linux):

```bash
sudo a2enmod rewrite headers deflate expires
sudo systemctl reload apache2
```

En Windows con Apache Lounge, `AllowOverride All` va en `conf/httpd.conf` dentro del
`<Directory "...">` que corresponda, y los módulos se activan quitando el `#` de
`LoadModule rewrite_module modules/mod_rewrite.so`, `headers_module`, `deflate_module` y `expires_module`.

**Comprobación de que `mod_rewrite` y `.htaccess` están vivos:**

```bash
curl -I https://ejemplo.com/
# Debe responder 200 y traer X-Frame-Options: SAMEORIGIN
curl -I https://ejemplo.com/config/config.php
# Debe responder 403
```

> Si `curl -I .../config/config.php` devuelve 200, `AllowOverride` está en `None` y **el servidor está
> publicando tu configuración**. Para y corrígelo antes de continuar.

### 4.3. Bloque equivalente de nginx

nginx **ignora `.htaccess`**. Este bloque reproduce lo que hace el proyecto (RECOMENDACIÓN: escrito
para este manual, no probado en esta sesión):

```nginx
server {
    listen 80;
    server_name ejemplo.com www.ejemplo.com;

    # Document root en public/: el equivalente más seguro al .htaccess del proyecto
    root /var/www/pepiuox-editor/public;
    index index.php;

    client_max_body_size 12m;

    # 1. Carpetas y archivos que nunca se sirven
    location ~ ^/(config|app|views|storage|db|01-TOOLS|02-DOCS|vendor|private)/ { deny all; return 403; }
    location ~ /\.(env|git|htaccess|htpasswd) { deny all; return 403; }
    location ~* \.(sql|log|md|ini|conf|bak|backup|yml|yaml|lock|sh|bat)$ { deny all; return 403; }

    # 2. Subidas: viven fuera del document root y las sirve public/uploads.php
    location ~ ^/uploads/(.+)$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/pepiuox-editor/public/uploads.php;
        fastcgi_param QUERY_STRING    $query_string;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    # 3. Instalador (solo si lo usas; ver apartado 6.3)
    location = /install.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/pepiuox-editor/install.php;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    # 4. Estáticos: se sirven tal cual
    location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|woff2|ico)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    # 5. Todo lo demás al front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    # 6. Cabeceras que sí emite el .htaccess del proyecto
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
}
```

> **Falta en nginx lo que el `.htaccess` tampoco tiene:** CSP y HSTS. Ver
> [limitación 2](#19-limitaciones-conocidas-y-endurecimiento-pendiente). Añádelas aquí si sirves con
> nginx y terminas el TLS antes de nginx.

---

## 5. Preparación de la base de datos

### 5.1. Crear la base

El esquema exige la colación `utf8mb4_uca1400_ai_ci`. Créala explícitamente para que las tablas
nuevas hereden la misma:

```sql
CREATE DATABASE web_editor_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_uca1400_ai_ci;
```

Por consola:

```powershell
# Windows PowerShell (cliente MariaDB fuera del PATH en la máquina de referencia)
& 'C:\ZampGUI\mariadb\bin\mysql.exe' -u root -p -e "CREATE DATABASE IF NOT EXISTS web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;"
```

```bash
# Linux / bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;"
```

> `db/install/initial_database.sql` **también crea la base si no existe**, así que este paso es
> opcional si vas a importar ese archivo. Hacerlo aquí te permite fijar la colación a mano.

### 5.2. Comprobar que el motor es MariaDB y no MySQL

```sql
SELECT VERSION();
SHOW COLLATION LIKE 'utf8mb4_uca1400%';
```

Debe devolver al menos una fila. Si `SHOW COLLATION` vuelve vacío, **estás en MySQL** y la importación
fallará con `Unknown collation: 'utf8mb4_uca1400_ai_ci'`.

### 5.3. Antes de importar: la base debe estar vacía

HECHO OBSERVADO (cabecera del propio archivo): `initial_database.sql` crea 52 tablas con `CREATE TABLE`
y **se detiene con error si las tablas ya existen**. La única excepción son las dos vistas, que se
recrean con `DROP VIEW IF EXISTS`.

```sql
-- Debe devolver 0
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'web_editor_db';
```

Si no es 0, **no importes**: o estás apuntando a una base que ya tiene datos, o repetiste la instalación.
Ver [apartado 17](#17-restauración-de-un-respaldo) si lo que quieres es restaurar.

---

## 6. Importación de `initial_database.sql`

### 6.1. Qué contiene y qué no

| Contenido | Cantidad (HECHO OBSERVADO, contado sobre el archivo) |
|---|---|
| Tamaño del archivo | 143 331 bytes |
| `CREATE TABLE` | **52** |
| Vistas | **2** (`daily_summary`, `user_activity_summary`) |
| Triggers | **4** (`after_page_update`, `after_user_insert`, `after_user_update`, `before_user_delete`) |
| Rutinas | **5** (`get_active_users_count`, `get_retention_rate`, `cleanup_old_sessions`, `compute_analytics`, `update_daily_stats`) |
| Sentencias `INSERT` | 529, **solo catálogos** (roles, permisos, ajustes, idiomas, traducciones) |
| `DEFINER=` | 11, todos `root@localhost` |

**No contiene:** usuarios, contraseñas, hashes de contraseña, sesiones, inicios de sesión, intentos
fallidos, baneos, dispositivos de confianza, tokens de API, archivos, páginas, versiones, plantillas,
menús, componentes, notificaciones, analítica, auditoría, colas de caché ni estado del limitador de
peticiones.

### 6.2. Importar

```powershell
# Windows PowerShell — cliente fuera del PATH en la máquina de referencia
& 'C:\ZampGUI\mariadb\bin\mysql.exe' -u root -p --default-character-set=utf8mb4 < db\install\initial_database.sql
```

```bash
# Linux / bash
mysql -u root -p --default-character-set=utf8mb4 < db/install/initial_database.sql
```

Si usas un usuario administrador concreto en lugar de `root`, cámbialo. **No uses el usuario de la
aplicación para importar**: solo tiene `SELECT, INSERT, UPDATE, DELETE`, y el archivo necesita crear
tablas, vistas, triggers y rutinas.

### 6.3. El instalador web (`install.php`), si lo prefieres

HECHO OBSERVADO: existe un asistente web en `install.php` (raíz) con un puente en `public/install.php`.
Está **sin confirmar en git** en el momento de escribir esto, y `docs/INSTALL.md` aún dice que no hay
instalador web. Si decides usarlo, sus garantías (leídas en el código) son:

| Guarda | Comportamiento |
|---|---|
| Token de un solo uso | Se genera **solo por consola** (`php install.php --make-token`), vive en `storage/.install-token`, caduca en 1 hora, permisos `0600` |
| Solo local | Solo acepta `127.0.0.1` / `::1`, salvo `php install.php --allow-remote` o `INSTALL_ALLOW_REMOTE=1` |
| Límite de intentos | 20; el contador vive en `storage/.install-attempts` |
| Autodestrucción | Al terminar escribe `storage/.installed` y responde **410 Gone** para siempre |

```powershell
php install.php --make-token   # imprime el token de un solo uso
```

> **Aviso:** ese asistente importa **`db/db-full.sql`**, no `db/install/initial_database.sql`. Son
> archivos distintos: `db/db-full.sql` (868 KB) es un volcado con datos operativos y cuentas sembradas.
> El camino documentado en este manual es **consola + `initial_database.sql` + `create-admin.php`**.

### 6.4. Verificar la importación

```sql
USE web_editor_db;

SELECT COUNT(*) AS tablas   FROM information_schema.tables  WHERE table_schema='web_editor_db' AND table_type='BASE TABLE';  -- 52
SELECT COUNT(*) AS vistas   FROM information_schema.views   WHERE table_schema='web_editor_db';                              -- 2
SELECT COUNT(*) AS triggers FROM information_schema.triggers WHERE trigger_schema='web_editor_db';                            -- 4
SELECT COUNT(*) AS rutinas  FROM information_schema.routines WHERE routine_schema='web_editor_db';                            -- 5
SELECT COUNT(*) AS usuarios FROM users;                                                                                       -- 0
SELECT name FROM users_roles ORDER BY idRol;                                                                                  -- debe incluir 'Super Admin'
```

Si `users_roles` no contiene `Super Admin`, `create-admin.php` abortará en el paso del rol.

---

## 7. Configuración del `.env`

### 7.1. Dónde viven las credenciales

Las credenciales **no** están en el repositorio. Viven en `01-TOOLS/mysql/.env`, que git ignora
(HECHO OBSERVADO: `01-TOOLS/.gitignore` contiene `*/.env` y `*/.env.*`, con excepción de
`!*/.env.example`).

| Clave | Obligatoria | Para qué |
|---|---|---|
| `DB_DRIVER` | no (`mysql` por defecto) | Driver PDO |
| `DB_HOST` | no (`127.0.0.1` por defecto) | Servidor |
| `DB_PORT` | no (`3306` por defecto) | Puerto |
| `DB_NAME` | **sí** | Base de datos |
| `DB_USER` | **sí** | Usuario de la aplicación |
| `DB_PASSWORD` | **sí** | Contraseña de ese usuario |
| `DB_CHARSET` | no (`utf8mb4` por defecto) | Juego de caracteres |
| `DB_COLLATION` | no (`utf8mb4_uca1400_ai_ci` por defecto) | Colación |
| `DB_ROOT_USER` | solo para tareas de administración | Cuenta administrativa; la aplicación **no** la lee |
| `DB_ROOT_PASSWORD` | solo para tareas de administración | Ídem |

HECHO OBSERVADO: `config/database.php` lanza una `RuntimeException` con instrucciones si falta
`DB_NAME`, `DB_USER` o `DB_PASSWORD`. La aplicación no arranca a medias.

### 7.2. Crear el archivo

```powershell
# Windows PowerShell
Copy-Item 01-TOOLS\mysql\.env.example 01-TOOLS\mysql\.env
notepad 01-TOOLS\mysql\.env
```

```bash
# Linux / bash
cp 01-TOOLS/mysql/.env.example 01-TOOLS/mysql/.env
chmod 600 01-TOOLS/mysql/.env
$EDITOR 01-TOOLS/mysql/.env
```

Contenido a rellenar (los valores de ejemplo son **marcadores**, no contraseñas reales):

```dotenv
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=web_editor_db
DB_USER=web_editor_app
DB_PASSWORD=cambia_esta_contraseña
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_uca1400_ai_ci

# Cuenta de administración del servidor. La aplicación NO la usa.
DB_ROOT_USER=root
DB_ROOT_PASSWORD=cambia_esta_contraseña
```

Reglas de la contraseña (RECOMENDACIÓN tomada de `01-TOOLS/mysql/CREDENTIALS.md`): larga y aleatoria,
**sin comillas, sin `=`, sin `#` y sin espacios**. Esos cuatro caracteres rompen un `.env` o una consola.

### 7.3. Precedencia: el entorno gana al archivo

HECHO OBSERVADO en `config/env.php`:

1. Primero se leen las variables ya presentes en el proceso (`getenv` / `$_ENV`).
2. Después el `.env`, y **una clave que ya existe en el proceso no se sobrescribe**.

Es decir: en producción puedes no tener `.env` y definir las credenciales con `SetEnv` de Apache,
`Environment=` de systemd o variables de Docker. Un `.env` con valores distintos **no** gana.

```apache
# Apache: credenciales por entorno, sin .env
SetEnv DB_NAME web_editor_db
SetEnv DB_USER web_editor_app
SetEnv DB_PASSWORD <contraseña-segura>
```

### 7.4. Proteger el archivo

```bash
# Linux
chown www-data:www-data 01-TOOLS/mysql/.env
chmod 600 01-TOOLS/mysql/.env
```

```powershell
# Windows: quitar herencia y dejar solo al usuario del servicio web y al administrador
icacls "01-TOOLS\mysql\.env" /inheritance:r
icacls "01-TOOLS\mysql\.env" /grant:r "$($env:USERNAME):(R,W)"
```

Comprueba que git lo ignora:

```bash
git check-ignore -v 01-TOOLS/mysql/.env
# Debe imprimir la regla que lo ignora
```

### 7.5. Probar la conexión

```powershell
php 01-TOOLS\mysql\test_connection.php
```

```bash
php 01-TOOLS/mysql/test_connection.php
```

Si falla aquí, no sigas: el problema está en el apartado 5, 7 o 8.

---

## 8. Usuario de base de datos con privilegios mínimos

La aplicación **debe** conectarse con un usuario que tenga **solo** `SELECT, INSERT, UPDATE, DELETE`
sobre su base. En la instalación de referencia ese usuario es `web_editor_app`.

### 8.1. Crear el usuario y dar los permisos exactos

```sql
-- Dos hosts del mismo usuario: que no dependa de cómo se resuelva el nombre.
CREATE USER 'web_editor_app'@'localhost' IDENTIFIED BY '<contraseña-segura>';
CREATE USER 'web_editor_app'@'127.0.0.1' IDENTIFIED BY '<contraseña-segura>';

GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO 'web_editor_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO 'web_editor_app'@'127.0.0.1';

FLUSH PRIVILEGES;
```

Eso es **todo** lo que necesita. No le des `CREATE`, `DROP`, `ALTER`, `INDEX`, `TRIGGER`, `EXECUTE`,
`GRANT` ni permisos sobre `mysql.*`. Las migraciones y los respaldos los corres tú con la cuenta
administrativa (`DB_ROOT_USER` / `DB_ROOT_PASSWORD`).

> Las **vistas y rutinas con `DEFINER=root@localhost`** que trae el esquema se ejecutan con los
> privilegios de su definidor, no con los del usuario de la aplicación. Por eso puede leer
> `daily_summary` y `user_activity_summary` sin tener permisos sobre `mysql.*`. Si borras la cuenta
> `root@localhost`, esas vistas y rutinas dejan de funcionar (INFERENCIA razonada desde el
> comportamiento documentado de `DEFINER`; no ejecutada aquí).

### 8.2. Verificar los privilegios

```sql
SHOW GRANTS FOR 'web_editor_app'@'localhost';
```

Debe imprimir exactamente una línea `GRANT USAGE ON *.*` y una línea
`GRANT SELECT, INSERT, UPDATE, DELETE ON \`web_editor_db\`.*`. Si aparece `ALL PRIVILEGES`, **quítalos**:

```sql
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'web_editor_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO 'web_editor_app'@'localhost';
FLUSH PRIVILEGES;
```

### 8.3. Rotar la contraseña sin romper nada

```sql
ALTER USER 'web_editor_app'@'localhost' IDENTIFIED BY '<contraseña-nueva-y-larga>';
ALTER USER 'web_editor_app'@'127.0.0.1' IDENTIFIED BY '<contraseña-nueva-y-larga>';
FLUSH PRIVILEGES;
```

Después actualiza `DB_PASSWORD` en el `.env` y prueba **en un proceso nuevo**:

```bash
php 01-TOOLS/mysql/test_connection.php
```

> Comprobar la rotación dentro del mismo proceso da un falso negativo: `require_once` impide recargar
> `config/env.php` y el proceso seguiría usando la contraseña vieja. Si tienes workers de larga vida,
> reinícialos; Apache con `mod_php` y PHP-FPM no lo necesitan.

---

## 9. Permisos de carpetas

El usuario con el que corre PHP (normalmente `www-data` en Debian/Ubuntu, `apache` en RHEL,
`NT AUTHORITY\SYSTEM` o el usuario del servicio en Windows) necesita **escritura** en:

| Ruta | Para qué | Notas |
|---|---|---|
| `storage/` | Estado general | `config/paths.php` crea `storage/cache`, `storage/cache/pages`, `storage/cache/data`, `storage/logs` y `uploads/` si no existen |
| `storage/cache/` | Caché de páginas y datos | `config.php` → `cache.path` |
| `storage/logs/` | `log_errors` | `config.php` → `logs.path` |
| `storage/backups/` | Respaldos JSON previos a migraciones | Ya existen tres archivos `*.json` |
| `storage/versions/` | Versiones de páginas | |
| `uploads/` | Subidas e imágenes de marca | Fuera del document root; se sirven por `public/uploads.php` |
| `db/backup/` | Volcados `database_backup_YYYYMMDD.sql` | Ya contiene `database_backup_20261031.sql` (1,2 MB) |

El resto del proyecto debe ser **de solo lectura** para ese usuario.

```bash
# Linux (Debian/Ubuntu)
sudo chown -R www-data:www-data /var/www/pepiuox-editor/storage \
                                /var/www/pepiuox-editor/uploads \
                                /var/www/pepiuox-editor/db/backup
sudo chmod -R 775 /var/www/pepiuox-editor/storage \
                    /var/www/pepiuox-editor/uploads \
                    /var/www/pepiuox-editor/db/backup
sudo find /var/www/pepiuox-editor -type f -exec chmod 640 {} +
sudo find /var/www/pepiuox-editor -type d -exec chmod 750 {} +
sudo chmod 600 /var/www/pepiuox-editor/01-TOOLS/mysql/.env
```

```powershell
# Windows PowerShell (ajusta la cuenta del servicio web a la tuya)
$svc = 'NT AUTHORITY\SYSTEM'
foreach ($p in @('storage','uploads','db\backup')) {
    icacls $p /grant:r "${svc}:(OI)(CI)M" /T
}
```

**Comprobación real desde PHP** (es la misma que hace el instalador web al empezar):

```powershell
php -r "foreach (['storage','uploads','db/backup'] as $d) { echo $d, ': ', (is_writable($d) ? 'ESCRIBIBLE' : 'NO ESCRIBIBLE'), PHP_EOL; }"
```

```bash
php -r 'foreach (["storage","uploads","db/backup"] as $d) { echo $d, ": ", (is_writable($d) ? "ESCRIBIBLE" : "NO ESCRIBIBLE"), PHP_EOL; }'
```

Si `storage/` o `uploads/` no son escribibles, los síntomas son: no se guardan ajustes de marca, no
suben imágenes, no se escribe el log y la caché falla en silencio.

---

## 10. Instalación de dependencias

### 10.1. Por qué no hay Composer

HECHO OBSERVADO: no existe `composer.json`, ni `vendor/`, ni `vendor/autoload.php`, ni una sola
referencia al autoloader de Composer en el código. El proyecto carga sus clases con un
`spl_autoload_register` propio en `public/index.php`, con este mapa de prefijos:

| Prefijo | Carpeta |
|---|---|
| `App\Core\` | `app/Core/` |
| `App\Models\` | `app/Models/` |
| `App\Controllers\` | `app/Controllers/` |
| `App\Services\` | `app/Services/` |
| `App\Middleware\` | `app/Middleware/` |
| `App\` | `app/` |

**No ejecutes `composer install`.** No instalaría nada y una carpeta `vendor/` vacía solo confundiría
al siguiente que lea el proyecto.

### 10.2. Lo que sí hay que hacer

| Paso | Comando |
|---|---|
| Extensiones PHP | Instalarlas en el sistema (apartado 1) y reiniciar PHP/Apache |
| Librerías de navegador | Se cargan por CDN. **No** hay paso de build; no hay `package.json` |
| Node.js | Opcional, solo si vas a ejecutar las pruebas de `tests/blocks-library` |

### 10.3. Pruebas (opcional, no bloquean la instalación)

```bash
# Windows y Linux: cada archivo se ejecuta directo con Node
node tests/blocks-library/normalize.test.js
node tests/blocks-library/classify.js
node tests/blocks-library/install.test.js
```

> `node --test tests/blocks-library` **no** es fiable en entornos confinados: el *runner* lanza un
> subproceso y falla con `spawn EPERM`. Ejecutando cada archivo directamente no hay subproceso.

También hay una prueba de integración del tema público en `tests/public-theme/`
(`e2e.ps1`, `service-test.php`, `support.php`), que **sí requiere base de datos**.

---

## 11. Dominio

### 11.1. DNS

| Registro | Tipo | Valor | Nota |
|---|---|---|---|
| `ejemplo.com` | `A` | IP del servidor | El apex no admite `CNAME` |
| `ejemplo.com` | `AAAA` | IPv6, si la tienes | Opcional |
| `www.ejemplo.com` | `CNAME` | `ejemplo.com` | |

Espera a que propague antes de pedir el certificado:

```bash
dig +short ejemplo.com
nslookup ejemplo.com
```

### 11.2. `config/config.php`: `app_url`

HECHO OBSERVADO: `app_url` viene con `'http://localhost:170'`, un valor de desarrollo. **No** se usa
para generar los enlaces de navegación —eso lo hace `BASE_URL`, calculado en `config/paths.php` a
partir de `$_SERVER`— pero **sí** se usa en:

| Dónde | Consecuencia si está mal |
|---|---|
| `app/Services/EmailNotificationService.php` (7 usos) | Los enlaces de los correos llevan a `localhost` |
| `app/Controllers/PasswordController.php:244` | **El enlace de recuperación de contraseña es inservible** |
| `app/Services/SitemapService.php` y `SeoService.php` | `sitemap.xml`, canonical y Open Graph con dominio equivocado |
| `app/Services/EmailTemplate.php`, `BanService.php`, `TwoFactorRecoveryService.php` | Ídem |

Cámbialo antes de publicar:

```php
'app_url' => 'https://ejemplo.com',   // sin barra final y sin /public
```

### 11.3. Otros tres valores que hay que cambiar antes de publicar

HECHO OBSERVADO en `config/config.php`:

```php
'app_url' => 'http://localhost:170',   // → tu dominio real
'debug'   => true,                     // → false
'session' => ['secure' => false, ...],  // → true (con HTTPS)
```

Con `debug => true`, `public/index.php` activa `display_errors` y las excepciones se imprimen con
**traza completa**, que incluye rutas absolutas del servidor. No publiques con `debug` en `true`.

---

## 12. SSL/HTTPS y qué cambia en la configuración de sesión

### 12.1. Certificado

```bash
# Linux con Certbot (Apache)
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d ejemplo.com -d www.ejemplo.com

# Linux con Certbot (nginx)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d ejemplo.com -d www.ejemplo.com
```

```powershell
# Windows: win-acme o Certify The Web, o certificado propio si es una intranet
# https://www.win-acme.com/
```

Renovación automática de Let's Encrypt: `certbot` instala un temporizador de systemd/servicio. Compruébalo:

```bash
systemctl list-timers | grep certbot
sudo certbot renew --dry-run
```

### 12.2. Qué cambia en la configuración de sesión

HECHO OBSERVADO: `app/Core/Session.php` pasa `config/config.php` → `session.*` a
`session_set_cookie_params()`. Al pasar a HTTPS hay que cambiar **una** clave:

```php
'session' => [
    'name'     => 'PEP_SESSION',
    'lifetime' => 7200,
    'path'     => '/',
    'domain'   => '',        // déjalo vacío salvo que sirvas varias subdomainas desde la misma sesión
    'secure'   => true,      // ← ESTE. false en desarrollo, true con HTTPS
    'httponly' => true,      // ya viene en true; no lo toques
    'samesite' => 'Lax',     // correcto para este panel
],
```

| Clave | Antes (HTTP) | Después (HTTPS) | Efecto |
|---|---|---|---|
| `secure` | `false` | **`true`** | La cookie `PEP_SESSION` solo viaja por TLS. Sin esto, el `PHPSESSID` puede viajar en claro |
| `httponly` | `true` | `true` | Ya viene bien: la cookie no es legible por JavaScript |
| `samesite` | `Lax` | `Lax` | Suficiente como mitigación CSRF de transporte; el CSRF real lo pone la aplicación con token |
| `domain` | `''` | `''` | Vacío = solo el host actual. No lo abras sin motivo |
| `lifetime` | `7200` | `7200` | 2 h |

Si sirves detrás de un proxy que termina el TLS, Apache/nginx debe reenviar
`X-Forwarded-Proto` y la aplicación debe ver `$_SERVER['HTTPS']`; `config/paths.php` decide `http` o
`https` mirando `$_SERVER['HTTPS']` y `$_SERVER['SERVER_PORT'] == 443`. Si el proxy no lo propaga,
`BASE_URL` saldrá en `http://` y los enlaces y assets serán mixtos (INFERENCIA: no lo probé con proxy).

### 12.3. Redirigir HTTP a HTTPS

```apache
<VirtualHost *:80>
    ServerName ejemplo.com
    ServerAlias www.ejemplo.com
    RewriteEngine On
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>
```

```nginx
server {
    listen 80;
    server_name ejemplo.com www.ejemplo.com;
    return 301 https://$host$request_uri;
}
```

> **HSTS:** el proyecto **no** emite `Strict-Transport-Security` (HECHO OBSERVADO: no aparece en
> `.htaccess` ni en `public/.htaccess` ni en `uploads/.htaccess`). Añádelo **solo** cuando tengas
> HTTPS estable en todos los subdominios; HSTS es difícil de deshacer:
>
> ```apache
> Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
> ```

### 12.4. Después de activar HTTPS, comprobar

```bash
curl -sI https://ejemplo.com/login | grep -i -E 'strict-transport|set-cookie'
# La cookie PEP_SESSION debe llevar «Secure» y «HttpOnly»
```

---

## 13. Tareas programadas

### 13.1. Los seis endpoints, tal como están en `config/routes.php`

HECHO OBSERVADO (rutas y controladores leídos):

| Endpoint | Método | Qué hace | Autenticación real |
|---|---|---|---|
| `/cron/backup` | GET | `BackupService::runScheduled()` — ejecuta los respaldos programados | `?token=` comparado con `config.php` → `cron_token` |
| `/cron/process-expired-bans` | GET | `BanService::processExpiredBans()` — levanta baneos vencidos | **NINGUNA.** No comprueba token (HECHO OBSERVADO en `BannedUsersController::processExpired`) |
| `/cron/cleanup-rate-limits` | GET | `RateLimiter::cleanup()` | `?token=` |
| `/cron/security-digest` | GET | `EmailNotificationService::sendSecurityDigest($period)` | `?token=`; acepta `&period=daily` o `weekly` |
| `/cron/cleanup-trusted-devices` | GET | `TrustedDeviceService::cleanupExpired()` | `?token=` |
| `/cron/cleanup-collaboration` | GET | Cierra sesiones de colaboración, borra bloqueos vencidos y eventos de más de 24 h | `?token=` |

El mecanismo de token es, literalmente, esto (repetido en las cinco rutas y en
`BackupsController::cron`):

```php
$cfg = require CONFIG_PATH . '/config.php';
$token = $request->get('token');          // ?token=... en la URL
$expected = $cfg['cron_token'] ?? '';
if (!$expected || $token !== $expected) {
    Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
}
```

### 13.2. Dos defectos que tienes que conocer antes de programar nada

**1. El token cambia en cada carga.** HECHO OBSERVADO en `config/config.php`:

```php
'cron_token' => 'cambia-este-token-por-uno-seguro-' . bin2hex(random_bytes(8)),
```

Esa expresión se evalúa **cada vez que alguien hace `require` del archivo**. Lo comprobé ejecutando dos
cargas seguidas:

```
t1=cambia-este-token-por-uno-seguro-dfedf883531350f2
t2=cambia-este-token-por-uno-seguro-c8b9937bc84fcf51
iguales=NO
```

Consecuencia práctica: **nadie puede conocer el token**, así que los cinco endpoints protegidos
responden siempre **401**. No es un fallo de tu instalación: es el código tal como está.

**2. `/cron/process-expired-bans` no tiene ninguna comprobación.** Cualquiera que alcance la URL la
ejecuta. Escribe en base de datos (levanta baneos), así que no es un GET inocuo.

### 13.3. Arreglo mínimo antes de usar cron (RECOMENDACIÓN)

Sustituye la línea del token por un valor fijo y largo, fuera de git:

```php
// config/config.php
'cron_token' => getenv('CRON_TOKEN') ?: 'pon-aqui-un-token-largo-y-aleatorio-de-32-o-mas-caracteres',
```

Y envuelve `/cron/process-expired-bans` con la misma comprobación que las otras cinco. Mientras no
hagas esto, **no expongas `/cron/*` a Internet**: limita el acceso a `127.0.0.1` en el servidor web.

```apache
# Apache: solo desde el propio servidor
<LocationMatch "^/cron/">
    Require ip 127.0.0.1 ::1
</LocationMatch>
```

```nginx
# nginx
location ^~ /cron/ {
    allow 127.0.0.1;
    allow ::1;
    deny all;
    try_files $uri /index.php?$query_string;
}
```

### 13.4. Programación con el token ya arreglado

**Linux (crontab -e):**

```cron
# Respaldo diario a las 03:15
15 3 * * *  curl -fsS "https://ejemplo.com/cron/backup?token=<token-fijo>" >/dev/null

# Baneos vencidos cada 10 minutos
*/10 * * * * curl -fsS "https://ejemplo.com/cron/process-expired-bans?token=<token-fijo>" >/dev/null

# Limpieza del limitador cada 15 minutos
*/15 * * * * curl -fsS "https://ejemplo.com/cron/cleanup-rate-limits?token=<token-fijo>" >/dev/null

# Dispositivos de confianza vencidos, cada hora
0 * * * *   curl -fsS "https://ejemplo.com/cron/cleanup-trusted-devices?token=<token-fijo>" >/dev/null

# Sesiones de colaboración zombis, cada 5 minutos
*/5 * * * * curl -fsS "https://ejemplo.com/cron/cleanup-collaboration?token=<token-fijo>" >/dev/null

# Resumen de seguridad diario a las 08:00
0 8 * * *   curl -fsS "https://ejemplo.com/cron/security-digest?token=<token-fijo>&period=daily" >/dev/null
```

Si el cron corre **en el mismo servidor**, usa `http://127.0.0.1/...` y evitarás depender del DNS y del
proxy.

**Windows (Programador de tareas, PowerShell):**

```powershell
$token  = '<token-fijo>'
$accion = New-ScheduledTaskAction -Execute 'curl.exe' `
          -Argument "-fsS `"http://127.0.0.1/cron/backup?token=$token`""
$disparo = New-ScheduledTaskTrigger -Daily -At 3:15am
Register-ScheduledTask -TaskName 'PePiuoX respaldo diario' -Action $accion -Trigger $disparo `
                       -Description 'Respaldo de web_editor_db' -RunLevel Highest
```

Repite con `schtasks` si prefieres la consola:

```powershell
schtasks /create /tn "PePiuoX cleanup rate limits" /tr "curl.exe -fsS \"http://127.0.0.1/cron/cleanup-rate-limits?token=<token-fijo>\"" /sc minute /mo 15 /f
```

Comprueba a mano que el endpoint responde antes de programarlo:

```bash
curl -i "http://127.0.0.1/cron/cleanup-rate-limits?token=<token-fijo>"
# 200 con {"success":true,"cleaned":N}   ← con el token arreglado
# 401 {"success":false,"message":"Unauthorized"}  ← token sin arreglar
```

---

## 14. Primer acceso y creación del administrador

### 14.1. Crear la cuenta Super Admin

`db/install/initial_database.sql` **no trae ninguna cuenta ni ninguna contraseña**. La crea este script,
fuera de `public/`, que además aborta si alguien lo alcanza por HTTP (HECHO OBSERVADO: comprueba
`PHP_SAPI !== 'cli'` y responde 404).

```powershell
# Windows PowerShell — contraseña generada, se muestra una sola vez
php 01-TOOLS\bootstrap\create-admin.php --username=admin --email=tu@dominio.com --generate
```

```bash
# Linux — contraseña que tú eliges, por variable de entorno (nunca por argumento)
ADMIN_PASSWORD='<contraseña-segura>' php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com
```

| Indicador | Efecto |
|---|---|
| `--username=<nombre>` | **Obligatorio.** 3 a 50 caracteres: letras, números, `.`, `_`, `-` |
| `--email=<correo>` | **Obligatorio.** Se valida con `FILTER_VALIDATE_EMAIL` |
| `--generate` | Genera una contraseña de 20 caracteres y la imprime una sola vez |
| `--force` | Si ya existe un Super Admin, reescribe su contraseña |
| `--yes` | No pide confirmar el destino (para automatizar) |
| `--help` | Ayuda |

**Comportamiento que debes esperar:**

1. Imprime `Base de datos destino: «web_editor_db» en 127.0.0.1`.
2. Si no pasas `--yes`, **pide escribir el nombre exacto de la base**. Esto existe por un incidente
   real: el script escribió en producción en lugar de en pruebas por no confirmar el destino.
3. Sin terminal interactiva y sin `--yes`, aborta: `Sin terminal interactiva no se puede confirmar el destino.`
4. La contraseña va por `ADMIN_PASSWORD` (mínimo **12** caracteres) o se genera. **No se acepta por
   línea de comandos**: quedaría en el historial del shell y en la lista de procesos.
5. Si ya hay un Super Admin y no pasas `--force`, aborta sin tocar nada.

También comprueba que `users_roles` tenga el rol `Super Admin` y, si no lo encuentra, te dice que
importes `db/install/initial_database.sql`. Es la comprobación que confirma que la importación fue bien.

**Verificado de extremo a extremo en una base aislada** según los datos del proyecto: el script crea la
cuenta, y `password_hash` + `password_verify` funcionan con el login.

### 14.2. Entrar

1. Abre `https://ejemplo.com/login`.
2. Entra con el usuario y la contraseña del paso anterior.
3. **Cambia la contraseña en el primer acceso** (`/admin/profile`). La generada no queda guardada en
   ningún archivo.
4. La portada pública aún no tiene contenido: crea tu primera página en `/admin/pages`.

### 14.3. Aviso sobre `db/db-full.sql`

Si en lugar de `initial_database.sql` importaste `db/db-full.sql` (el volcado con datos), **la base trae
tres cuentas sembradas** con hashes confirmados en git. Eso es un riesgo real: se pueden atacar fuera
de línea sin prisa. Con `initial_database.sql` ese problema no existe porque no hay ninguna cuenta.
Si ya importaste el volcado completo, cambia las tres contraseñas o desactiva esas cuentas antes de
publicar.

---

## 15. Verificación del funcionamiento

Ejecuta esta lista en orden. Cada fila tiene su comprobación concreta y su resultado esperado.

| # | Comprobación | Cómo | Esperado |
|---|---|---|---|
| 1 | Versión de PHP | `php --version` | 8.3 o superior (referencia: 8.4.25) |
| 2 | Extensiones | `php -m` y buscar las nueve | Todas presentes |
| 3 | Motor de base de datos | `SELECT VERSION();` | MariaDB 11+ (referencia: 12.3.3) |
| 4 | Colación disponible | `SHOW COLLATION LIKE 'utf8mb4_uca1400%';` | Al menos una fila |
| 5 | Credenciales | `php 01-TOOLS/mysql/test_connection.php` | Conecta y enumera tablas |
| 6 | Conteo de objetos | Las 5 consultas del apartado 6.4 | 52 / 2 / 4 / 5 / 0 usuarios |
| 7 | Privilegios | `SHOW GRANTS FOR 'web_editor_app'@'localhost';` | Solo `SELECT, INSERT, UPDATE, DELETE` |
| 8 | Escritura | El `php -r` del apartado 9 | `storage`, `uploads`, `db/backup` escribibles |
| 9 | Front controller | `curl -I https://ejemplo.com/` | `200` |
| 10 | `.htaccess` / `AllowOverride` | `curl -I https://ejemplo.com/config/config.php` | `403` |
| 11 | Archivos sensibles | `curl -I https://ejemplo.com/01-TOOLS/mysql/.env` | `403` o `404`, **nunca** el contenido |
| 12 | Cabeceras de seguridad | `curl -sI https://ejemplo.com/ \| grep -i -E 'x-frame\|x-content-type\|referrer\|permissions'` | Las cuatro presentes |
| 13 | Login | `https://ejemplo.com/login` con la cuenta creada | Entra al panel |
| 14 | Panel | `/admin` | Dashboard con datos |
| 15 | Portada | `/` | Renderiza (vacía si no hay páginas) |
| 16 | Primera página | Crear en `/admin/pages` y publicar | Visible en `/page/<slug>` |
| 17 | Subida de imagen | Subir un PNG desde ajustes de marca | Se guarda en `uploads/settings/logos/` y se sirve |
| 18 | Respaldo manual | `/admin/backups` → crear | Aparece un archivo nuevo en `db/backup/` |
| 19 | sitemap | `curl -s https://ejemplo.com/sitemap.xml \| head` | XML con tu dominio real (no `localhost`) |
| 20 | robots | `curl -s https://ejemplo.com/robots.txt` | Texto con `Sitemap:` |
| 21 | Correo | `/forgot` con tu email | Llega el correo y **el enlace apunta a tu dominio** |
| 22 | Errores ocultos | Provocar un 404 | Página de error, sin traza ni rutas del servidor (`debug => false`) |

**Pruebas automáticas disponibles** (HECHO OBSERVADO: no hay PHPUnit ni CI):

```bash
node tests/blocks-library/normalize.test.js   # 6 comprobaciones, todas PASA
node tests/blocks-library/classify.js         # 0 bloques en «Otros»
node tests/blocks-library/install.test.js     # 11 comprobaciones, todas PASA
```

La prueba `tests/public-theme/e2e.ps1` es de integración y **necesita base de datos**.

---

## 16. Actualización del sistema

### 16.1. Antes de tocar nada

1. **Respalda**. Un respaldo que no se ha restaurado nunca no es un respaldo:

```powershell
# Windows PowerShell
& 'C:\ZampGUI\mariadb\bin\mysqldump.exe' --user=<admin> --password `
  --host=127.0.0.1 --port=3306 --skip-ssl --single-transaction --skip-lock-tables `
  --routines --triggers --events --default-character-set=utf8mb4 --hex-blob `
  --complete-insert --result-file=db\backup\database_backup_20261031.sql web_editor_db
```

```bash
# Linux / bash (aquí --skip-ssl no es necesario; en Windows con MariaDB sí, ver nota)
mysqldump --user=<admin> --password \
  --host=127.0.0.1 --port=3306 --single-transaction --skip-lock-tables \
  --routines --triggers --events --default-character-set=utf8mb4 --hex-blob \
  --complete-insert --result-file=db/backup/database_backup_$(date +%Y%m%d).sql web_editor_db
```

> **`--skip-ssl` en Windows es obligatorio.** HECHO OBSERVADO: `mysqldump` en Windows con MariaDB falla
> con `SEC_E_NO_CREDENTIALS` si no se pasa. No es un problema de credenciales: es la negociación TLS
> del cliente en Windows. En Linux normalmente no hace falta.

2. Anota la versión actual: `config/config.php` → `app_version` (`1.0.0` hoy).

### 16.2. Actualizar el código

```bash
git status                # que no haya cambios tuyos sin confirmar
git pull --ff-only
```

### 16.3. Aplicar migraciones

HECHO OBSERVADO: hay un aplicador propio, `db/migrations/apply.php`, y cuatro migraciones versionadas
con `up`/`down`:

| Migración |
|---|
| `2026_10_25_audit_logs_columns.up.sql` / `.down.sql` |
| `2026_10_25_menu_system.up.sql` / `.down.sql` |
| `2026_10_26_public_visual_settings.up.sql` / `.down.sql` |
| `2026_10_30_public_surface_texts.up.sql` / `.down.sql` |

```powershell
# Solo sentencias dentro de db/migrations; --admin usa DB_ROOT_USER / DB_ROOT_PASSWORD
php db\migrations\apply.php --admin 2026_10_30_public_surface_texts.up.sql
```

```bash
php db/migrations/apply.php --admin 2026_10_30_public_surface_texts.up.sql
```

El aplicador imprime `APLICADA` u `OMITIDA` por sentencia y es idempotente: los errores `1060`
(columna duplicada), `1061` (índice duplicado), `1022`, `1091` y `1826` se tratan como «ya existía» en
lugar de abortar. Cualquier otro error detiene el proceso.

> El usuario de la aplicación **no** puede aplicar esquema a propósito (`SELECT, INSERT, UPDATE,
> DELETE`). Sin `--admin` y sin `DB_ROOT_USER`/`DB_ROOT_PASSWORD` en el entorno, el script sale con
> `Faltan DB_ROOT_USER / DB_ROOT_PASSWORD en el entorno`.

### 16.4. Revisar `config/config.php`

Cada actualización puede reintroducir los valores de desarrollo. Verifica tras cada `git pull`:

```php
'app_url'        => 'https://ejemplo.com',  // no localhost
'debug'          => false,                  // no true
'session.secure' => true,                   // no false
```

### 16.5. Volver atrás

- Código: `git checkout <commit-anterior>` o `git revert`.
- Esquema: aplica el `.down.sql` correspondiente con `--admin`.
- Datos: restaura el respaldo del apartado 17.

---

## 17. Restauración de un respaldo

### 17.1. Aviso que hay que leer antes

HECHO OBSERVADO sobre `db/backup/database_backup_20261031.sql` (1 221 355 bytes, generado por
`mysqldump` 10.20 de MariaDB 12.3.3): contiene **14 apariciones de `DEFINER=root@localhost`**
(vistas, rutinas y triggers) y **52 `CREATE TABLE`**, con `DROP TABLE IF EXISTS` por tabla.

Consecuencia: **restaurar con una cuenta que no sea `root@localhost` puede fallar** con
`The user specified as a definer ('root'@'localhost') does not exist` o con un error de privilegios.
Tienes dos caminos:

| Camino | Cuándo |
|---|---|
| Restaurar con una cuenta de privilegios altos que pueda crear objetos con `DEFINER` | Lo habitual en un servidor propio |
| Quitar las cláusulas `DEFINER` **antes** de importar | Si restauras con una cuenta limitada |

Quitar los `DEFINER` sobre una **copia**, nunca sobre el respaldo original:

```bash
# Linux: copia de trabajo sin DEFINER
sed -E 's/DEFINER=`[^`]+`@`[^`]+`//g' db/backup/database_backup_20261031.sql > /tmp/restore.sql
```

```powershell
# Windows PowerShell
(Get-Content db\backup\database_backup_20261031.sql -Raw) `
  -replace 'DEFINER=`[^`]+`@`[^`]+`', '' `
  | Set-Content C:\Temp\restore.sql -Encoding UTF8
```

### 17.2. Restaurar

**Nunca sobre la base buena sin haber probado antes.** El orden correcto es: base aislada, verificar,
y solo después la base real.

```sql
-- 1. Base aislada para verificar
CREATE DATABASE web_editor_check CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
```

```powershell
# 2. Importar en la base aislada (Windows)
& 'C:\ZampGUI\mariadb\bin\mysql.exe' -u <admin> -p --default-character-set=utf8mb4 web_editor_check < db\backup\database_backup_20261031.sql
```

```bash
# 2. Importar en la base aislada (Linux)
mysql -u <admin> -p --default-character-set=utf8mb4 web_editor_check < db/backup/database_backup_20261031.sql
```

### 17.3. Verificar el respaldo

Compara el `CHECKSUM TABLE` de las 52 tablas entre la base de origen y la restaurada.

```sql
-- En la base de origen
SELECT CONCAT('CHECKSUM TABLE `', table_name, '`;')
FROM information_schema.tables
WHERE table_schema = 'web_editor_db' AND table_type = 'BASE TABLE'
ORDER BY table_name;
```

Ejecuta la lista resultante en **ambas** bases y compara. Resultado documentado del proyecto para este
respaldo: **52 de 52 tablas idénticas**, 2 vistas consultables, 4 triggers y 5 rutinas presentes.

Comprobación rápida de objetos:

```sql
SELECT COUNT(*) FROM information_schema.tables  WHERE table_schema='web_editor_check' AND table_type='BASE TABLE'; -- 52
SELECT COUNT(*) FROM information_schema.views   WHERE table_schema='web_editor_check';                              -- 2
SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema='web_editor_check';                            -- 4
SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='web_editor_check';                            -- 5
SELECT * FROM daily_summary LIMIT 5;      -- la vista responde
```

### 17.4. Pasar a la base real

Solo si la verificación salió bien:

```bash
# 3. Parar la aplicación (mantenimiento) y restaurar
mysql -u <admin> -p --default-character-set=utf8mb4 web_editor_db < db/backup/database_backup_20261031.sql
```

> Es una operación **destructiva**: el volcado trae `DROP TABLE IF EXISTS` por tabla. Si el respaldo está
> incompleto o es de otra fecha, pierdes los datos posteriores. Ten el respaldo **actual** a mano antes
> de empezar, y no lo hagas sobre producción sin autorización explícita.

Al terminar, borra la base de verificación:

```sql
DROP DATABASE web_editor_check;
```

---

## 18. Solución de errores frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| `500` con «Faltan credenciales de MySQL» | No hay `.env` ni variables de entorno, o falta `DB_NAME`/`DB_USER`/`DB_PASSWORD` | Apartado 7. La excepción la lanza `config/database.php` |
| `500` con «Falta config/database.php» | Alguien borró el archivo | Restáuralo desde el repositorio (`git checkout config/database.php`) |
| `SQLSTATE[HY000] [1045] Access denied for user` | Usuario o contraseña mal, o el usuario no existe en ese host | Apartado 8. Recuerda crear el usuario para `localhost` **y** `127.0.0.1` |
| `SQLSTATE[HY000] [1044] Access denied ... to database` | El usuario existe pero no tiene permisos sobre esa base | `GRANT SELECT, INSERT, UPDATE, DELETE ON web_editor_db.* TO ...` |
| `Unknown collation: 'utf8mb4_uca1400_ai_ci'` | Estás en **MySQL**, no en MariaDB | Instala MariaDB 11+. Este esquema no se importa en MySQL |
| `Table 'users_roles' doesn't exist` al crear el admin | No importaste el esquema, o importaste en otra base | Apartado 6 y comprueba `DB_NAME` |
| `CREATE TABLE ... already exists` al importar | La base no estaba vacía | Apartado 5.3: no importes sobre una base con tablas |
| `mysqldump: Got error ... SEC_E_NO_CREDENTIALS` (Windows) | Falta `--skip-ssl` en Windows con MariaDB | Añade `--skip-ssl` al comando |
| `The user specified as a definer ('root'@'localhost') does not exist` | El respaldo trae `DEFINER=root@localhost` y esa cuenta no existe | Apartado 17.1: restaura con privilegios altos o quita los `DEFINER` |
| 404 en `/` o el navegador descarga PHP en crudo | El document root no es `public/` y `AllowOverride` está en `None` | Apartado 4 |
| `/config/config.php` responde `200` | `AllowOverride None`, el `.htaccess` se ignora | Apartado 4.2. **Estás publicando tu configuración** |
| `/admin/components` responde 500 | Desajuste archivo↔clase: la ruta apunta a `ComponentsController` y el archivo es `ComponentController` (sin «s») | Limitación conocida, ver apartado 19 |
| `/lang/es` responde 500 | Igual: la ruta apunta a `LanguageController` y el archivo es `LenguageController` | Limitación conocida |
| `/page` y `/page/<slug>` responden 500 | La ruta hace `require __DIR__.'/../page.php'` desde `config/`, es decir `page.php` en la raíz, que no existe (el archivo real es `public/page.php`) | Limitación conocida |
| `/cron/...?token=...` responde siempre `401` | `cron_token` se regenera en cada carga de `config.php` | Apartado 13.2 y 13.3 |
| No se guardan imágenes ni ajustes de marca | `uploads/` no es escribible | Apartado 9 |
| Enlaces y correos apuntan a `localhost:170` | `app_url` sigue en el valor de desarrollo | Apartado 11.2 |
| Aparecen trazas con rutas del servidor | `debug => true` | Apartado 11.3. Ponlo en `false` |
| La sesión se pierde en cada petición | `session.secure => true` sobre HTTP, o `domain` mal puesto | Apartado 12.2 |
| `node --test` falla con `spawn EPERM` | El runner lanza un subproceso y el entorno lo bloquea | Ejecuta cada archivo directo: `node tests/blocks-library/install.test.js` |

---

## 19. Limitaciones conocidas y endurecimiento pendiente

Esto no es una lista de tareas opcionales: son cosas que **no** están resueltas hoy y que condicionan
si la instalación es apta para producción.

### 19.1. `PermissionMiddleware` no está aplicado en ninguna ruta

HECHO OBSERVADO: `app/Middleware/PermissionMiddleware.php` existe, pero no aparece en
`config/routes.php` (0 coincidencias). La autorización fina depende de llamadas `Auth::can()` dispersas
dentro de los controladores, de forma desigual. Ejemplo observado del inventario técnico del proyecto:
`PagesController::delete()` solo exige sesión y CSRF.

> **No operes una instalación multiusuario sin revisar esto antes.** Cualquier sesión con acceso a
> `/admin` puede llegar a acciones que deberían exigir un permiso concreto. Aplicar el middleware ya
> escrito, con permisos como `pages.manage` o `settings.manage`, es el arreglo pendiente.

### 19.2. Sin CSP ni HSTS; CDN sin SRI

| Qué falta | Estado |
|---|---|
| `Content-Security-Policy` global | **No hay.** Solo `uploads/.htaccess` pone una CSP a los `.svg` servidos directamente |
| `Strict-Transport-Security` | **No hay** en ninguno de los tres `.htaccess` |
| SRI (`integrity=`) en los CDN | **No hay** (0 coincidencias en `views/`); se cargan 5 librerías desde jsDelivr sin verificación de integridad |

Lo que **sí** emite el `.htaccess`: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`,
`Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
Además quita `Server` y `X-Powered-By`.

### 19.3. Rutas que hoy responden con error

Cuatro familias de rutas fallan por desajuste entre el archivo y la clase que la ruta nombra:

| Ruta | Archivo real | Clase que pide la ruta |
|---|---|---|
| `/admin/components`, `/admin/components/api`, `/create`, `/{id}/edit`, `/{id}`, `/{id}/delete` | `ComponentController.php` | `ComponentsController` |
| `/lang/{code}` | `LenguageController.php` | `LanguageController` |
| `/page` | `public/page.php` | `require config/../page.php` (no existe) |
| `/page/{slug}` | `public/page.php` | ídem |

### 19.4. Sin PHPUnit ni CI

HECHO OBSERVADO: no hay `phpunit.xml`, ni `composer.json`, ni `.github/workflows/`. Las únicas pruebas
automáticas son las de `tests/blocks-library` (Node, `node:test`) y la de integración de
`tests/public-theme` (PowerShell + PHP, requiere base de datos). No hay puerta automática que impida
desplegar código roto.

### 19.5. `/cron/process-expired-bans` sin autenticación

Ya descrito en 13.2. Escribe en base de datos y no comprueba token.

### 19.6. El token de cron no es estable

Ya descrito en 13.2. Mientras no se corrija, los otros cinco endpoints de cron son inutilizables.

### 19.7. `docs/INSTALL.md` está desactualizado respecto a este manual

Diferencias observadas:

- Afirma que **no hay instalador web**; existe `install.php` (sin confirmar en git) y `public/install.php`.
- Indica importar `db/db-full.sql`; el camino limpio es `db/install/initial_database.sql`.
- Describe tres cuentas sembradas como si fueran la norma; con `initial_database.sql` no hay ninguna.
- Su tabla de requisitos dice PHP 8.3+ (correcto) pero el mensaje del instalador web comprueba 8.2.

Mientras no se unifiquen, **este manual manda** para una instalación nueva.

### 19.8. Otros puntos de endurecimiento

| Riesgo | Recomendación |
|---|---|
| Colaciones mezcladas en la base de origen (`utf8mb4_uca1400_ai_ci` y `utf8mb3_uca1400_ai_ci`) | Revisar tabla por tabla si necesitas caracteres de 4 bytes en todas |
| Sin límite de intentos en el propio servidor web | Añadir `mod_ratelimit`/`limit_req` delante de `/login`, `/forgot` y `/reset` |
| Sin WAF ni bloqueo por IP | Valorar `fail2ban` o equivalente sobre el log de acceso |
| Sin `backup` verificado por calendario | Automatizar la verificación del apartado 17.3 y alertar si falla |
| Sin sistema de ficheros de solo lectura para el código | Montar `app/`, `config/`, `views/` en solo lectura una vez instalado |
| Sin prueba de humo tras desplegar | El listado del apartado 15 es manual; convertirlo en un script, aunque no haya CI |
| Respaldo en el mismo disco que la base (`db/backup/`) | Copiar los volcados fuera del servidor (3-2-1) |

---

## Comprobación de este manual

### Lo que se ejecutó realmente en esta máquina

| Comprobación | Herramienta | Resultado real |
|---|---|---|
| Versión de PHP | `php --version` | PHP **8.4.25** (cli), NTS, Visual C++ 2022 x64 |
| Extensiones presentes | `php -m` | `pdo_mysql`, `mbstring`, `json`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo`: **las nueve** |
| Uso real de extensiones en el código | `grep` sobre `app/` | `ZipArchive` 12, `gd` 13, `fileinfo` 6, `openssl_encrypt` 5, `mb_*` 41, `transliterator_transliterate` 7 (`intl`); **`curl` 0** |
| Versión del cliente `mysql` | `mysql --version` | 15.2 (MariaDB 12.3.3) |
| Versión de `mysqldump` | `mysqldump --version` | 10.20 (MariaDB 12.3.3) |
| Versión de Apache | `httpd -v` | Apache/2.4.68 (Win64) |
| Versión de Node | `node --version` | v26.5.0 |
| Ausencia de Composer | `Test-Path composer.json`, `vendor/`, `vendor/autoload.php` | **False** en los tres |
| Objetos de `initial_database.sql` | regex sobre el archivo | 52 `CREATE TABLE`, 2 vistas, 4 triggers, 5 rutinas, 529 `INSERT`, 11 `DEFINER`, 143 331 bytes |
| `DEFINER` del respaldo | regex sobre `db/backup/database_backup_20261031.sql` | **14**, y 52 `CREATE TABLE`, 1 221 355 bytes |
| Inestabilidad de `cron_token` | `php` cargando `config/config.php` dos veces | `...-dfedf883531350f2` vs `...-c8b9937bc84fcf51` → **iguales = NO** |
| `PermissionMiddleware` en rutas | `grep` sobre el repositorio | 0 coincidencias en `config/routes.php`; solo definición y menciones en documentación |
| Desajustes archivo↔clase | `grep` + listado de `app/Controllers` | `ComponentsController`/`LanguageController` no existen; `ComponentController`/`LenguageController` sí; `page.php` solo en `public/` |
| CSP y HSTS | `grep` en los tres `.htaccess` | 0 `Strict-Transport-Security`; la única CSP es la de `.svg` en `uploads/.htaccess` |
| SRI en CDN | `grep` en `views/` | 0 `integrity=`; 5 librerías desde jsDelivr |
| Claves del `.env` | listado de nombres de clave (sin valores) | Las 10 claves del apartado 7.1 presentes |
| Pruebas Node | `node <archivo>` en los tres de `tests/blocks-library` | Las tres terminan con código de salida **0**; todas las comprobaciones `PASA`. La traza en `stderr` de `install.test.js` es esperada: corresponde al caso «un bloque roto no detiene al resto» |
| Legibilidad de la documentación citada | lectura de `docs/INSTALL.md`, `docs/README.md`, `01-TOOLS/mysql/CREDENTIALS.md` | Leídos; las divergencias están anotadas en 19.7 |

### Lo que **no** se ejecutó (procedimiento, no hecho probado)

Estas partes son correctas según la lectura del código y la documentación, pero **no se ejecutaron en
esta sesión**. No las des por verificadas hasta probarlas en tu servidor:

| Parte | Por qué no se ejecutó |
|---|---|
| Crear la base, importar `initial_database.sql`, ejecutar `create-admin.php` | Prohibido explícitamente en el encargo: no tocar bases de datos reales ni importar nada |
| Comandos de `mysqldump` de los apartados 16 y 17 | Requieren conexión a una base real |
| Restauración en `web_editor_check` y comparación de `CHECKSUM TABLE` | Ídem. El resultado «52/52 idénticas» es dato del proyecto, **no** re-medido aquí |
| `GRANT` / `SHOW GRANTS` | Sin acceso al servidor MariaDB en esta sesión |
| Configuración de Apache y el bloque de nginx | El bloque de nginx **está escrito para este manual y no se probó**; el de Apache tampoco se aplicó a un vhost real |
| Certbot, DNS, HSTS | Requieren dominio y servidor propios |
| Tareas programadas (crontab y Programador de tareas) | No se registró ninguna tarea; y con el `cron_token` actual responderían 401 |
| `db/migrations/apply.php --admin` | Escribe en base de datos |
| Apartado 15 (lista de verificación) | Es el procedimiento a ejecutar **después** de instalar |
| `tests/public-theme/e2e.ps1` | Requiere base de datos |
| El instalador web `install.php` | No se ejecutó; su comportamiento está descrito leyendo el código |

### Conclusión

- **Hecho observado y ejecutado**: versiones del entorno, extensiones, estructura y contenido de
  `initial_database.sql` y del respaldo, ausencia de Composer, inestabilidad del `cron_token`,
  ausencia de `PermissionMiddleware` en rutas, desajustes de rutas, ausencia de CSP/HSTS/SRI, pruebas
  Node en verde, y todas las afirmaciones sobre archivos del repositorio.
- **Leído pero no ejecutado**: creación del administrador, importación, respaldo, restauración,
  privilegios, configuración de servidor web, TLS, cron.
- **Escrito para este manual y sin probar**: el bloque de nginx y las líneas de crontab/`schtasks`.

**Estado de la entrega: VALIDADO CON LIMITACIONES.** El manual describe con precisión el proyecto tal
como está, incluidos sus cuatro defectos declarados y dos hallazgos adicionales de esta sesión (token de
cron inestable y endpoint de baneos sin autenticación). No se declara probada la instalación completa:
eso hay que hacerlo, y verificarlo, en el servidor de destino.

---

## Actualización del 31 de octubre de 2026

Cambios posteriores a la redacción de este manual. **El punto 1 es obligatorio para que las tareas
programadas funcionen.**

### 1. `CRON_TOKEN` — obligatorio para el cron

Añade esta línea a `01-TOOLS/mysql/.env`:

```bash
# Genera el valor con:
#   php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
CRON_TOKEN=<el-valor-generado>
```

Sin esa clave, **los seis endpoints `/cron/*` responden 401**. Es intencionado: uno de ellos escribe en
la base de datos y se prefiere cerrar a dejar una tarea sin autenticar. Hasta esta fecha el token se
generaba en cada carga de `config/config.php`, así que no había forma de acertar: respondían 401 siempre.

Comprobación:

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1/cron/cleanup-rate-limits?token=<el-valor>"
# 200 con el token correcto · 401 sin token o con uno incorrecto
```

Crontab de ejemplo (una vez definido el token):

```bash
15 3 * * *   curl -fsS "https://TU-DOMINIO/cron/backup?token=TOKEN" >/dev/null
*/10 * * * * curl -fsS "https://TU-DOMINIO/cron/process-expired-bans?token=TOKEN" >/dev/null
*/15 * * * * curl -fsS "https://TU-DOMINIO/cron/cleanup-rate-limits?token=TOKEN" >/dev/null
0 * * * *    curl -fsS "https://TU-DOMINIO/cron/cleanup-trusted-devices?token=TOKEN" >/dev/null
*/5 * * * *  curl -fsS "https://TU-DOMINIO/cron/cleanup-collaboration?token=TOKEN" >/dev/null
0 8 * * *    curl -fsS "https://TU-DOMINIO/cron/security-digest?token=TOKEN&period=daily" >/dev/null
```

### 2. Permisos por acción

El panel ya no funciona «con solo tener sesión»: cada acción exige su permiso a través de
`config/permissions.php`, aplicado por el router. Al crear un rol, **asigna los permisos de los módulos
que vaya a usar** o sus usuarios recibirán 403.

`tests/seguridad/check.php` comprueba que no quede ninguna acción sin clasificar. Ejecútalo después de
añadir rutas nuevas.

### 3. Sitemap

`/admin/seo/sitemap` permite fijar la **URL base** del sitemap. Sin un dominio real, el archivo generado
no sirve para buscadores. Se puede fijar ahí o en `config/config.php → app_url`.

### 4. Páginas de presentación

```bash
php 01-TOOLS/bootstrap/seed-pages.php --dry-run   # ver qué haría
php 01-TOOLS/bootstrap/seed-pages.php --yes       # crear /funciones, /seguridad, /como-funciona
```

No borra páginas existentes; si un slug ya existe, lo salta salvo que se pase `--update`.

### 5. Comprobaciones que puedes ejecutar tras instalar

```bash
php tests/seguridad/check.php     # permisos y cron
php tests/landing/check.php       # portada
php tests/sitemap/check.php       # sitemap
```

### 6. Las tres rutas rotas, corregidas

Anula lo que digan los apartados anteriores sobre `/admin/components*`, `/lang/{code}`, `/page` y
`/page/{slug}`: ya no responden 500.

- `app/Controllers/ComponentController.php` → `ComponentsController.php`
- `app/Controllers/LenguageController.php` → `LanguageController.php`

Se renombraron con `git mv` porque era el archivo el que no coincidía con su clase; ninguna otra parte
del código usaba los nombres viejos. Las rutas `/page` y `/page/{slug}` dejaron de hacer `require` de un
`page.php` inexistente y son ahora alias de `PublicController::home` y `::show`.

Comprobado: `/lang/es` y `/lang/en` responden 302, `/page` y `/page/funciones` responden 200, y
`class_exists` resuelve ambas clases. `tests/seguridad/check.php` lo vigila con cuatro comprobaciones
permanentes.

Si aplicaste la limitación anterior en una instalación tuya, no hay nada que deshacer: era un fallo del
código, no de tu configuración.

### 7. Respaldos y secretos: dos reglas

**Los respaldos no se versionan.** Un volcado completo lleva los datos de las personas, sus hashes de
contraseña y los secretos de las aplicaciones de API. `db/backup/` está en `.gitignore` por eso. Si
alguna vez estuvo rastreado, se quita del índice sin borrarlo del disco:

```bash
git rm --cached -r db/backup    # deja de rastrearse; los archivos siguen ahí
```

**Los `.sql` que sí se versionan tienen que estar limpios.** Para comprobar y limpiar un volcado:

```bash
php 01-TOOLS/mantenimiento/sanitize-dump.php --dry-run db/db-full.sql
php 01-TOOLS/mantenimiento/sanitize-dump.php --yes     db/db-full.sql
```

Sustituye los hashes bcrypt por un centinela inválido y retira las filas de `api_applications` y
`api_tokens` (la estructura se conserva). Guarda el original en `storage/preview/` y **aborta si el
número de tablas, vistas, disparadores o rutinas cambia**.

**Retirar un secreto de un archivo no lo invalida.** Si estuvo publicado, hay que rotarlo en el servidor:

```bash
php 01-TOOLS/mantenimiento/rotate-api-tokens.php --dry-run
php 01-TOOLS/mantenimiento/rotate-api-tokens.php --yes
```

Rota `token` y `refresh_token` de cada aplicación. Quien estuviera usando uno tendrá que generar otro en
`/admin/api`. No rota `app_secret` ni `api_secret`: `App\Core\Security` usa una clave distinta por
instancia, así que esos valores son texto cifrado que ya no se puede descifrar (hallazgo R-13).

`tests/seguridad/check.php` falla si un `.sql` versionado vuelve a llevar hashes o filas de API.

**Aviso al importar `db/db-full.sql`:** empieza con `CREATE DATABASE IF NOT EXISTS web_editor_db` y
`USE web_editor_db`, así que **importarlo escribe en esa base**, no en la que tengas seleccionada. Para
probarlo en una base aislada, quita esas dos líneas de una copia:

```bash
grep -v -E '^(CREATE DATABASE|USE `)' db/db-full.sql > /tmp/prueba.sql
mysql -u <admin> -p <base_de_pruebas> < /tmp/prueba.sql
```