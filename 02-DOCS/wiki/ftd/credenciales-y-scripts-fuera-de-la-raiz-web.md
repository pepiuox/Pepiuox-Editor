---
author: Pepiuox
branch: main
status: in-progress
---
# Credenciales fuera de git y scripts fuera de la raíz web

## Intent

La constitución v1.0.0 dice dos cosas que el repo incumplía:

- **Principio 15:** ningún secreto se confirma en git. `config/database.php` estaba rastreado con el
  usuario y la contraseña de MySQL en claro.
- **Principio 16:** nada que instale, diagnostique o pruebe es alcanzable desde la raíz web.
  `public/install.php`, `public/diagnose.php`, `public/test-files.php` y `public/test-trusted.php`
  respondían por HTTP.

Deuda 1 y deuda 2 de la constitución. Se cerraron juntas porque comparten archivos: el instalador es
quien escribía la contraseña en claro en `config/database.php`.

## Scope

**Dentro:**

- Las credenciales de MySQL pasaron a `01-TOOLS/mysql/.env`, que git ignora.
- `config/database.php` ya no contiene el secreto y lee del entorno, con una carga mínima en
  `config/env.php`, sin dependencias (principio 3).
- Los cuatro scripts salieron de `public/` hacia `01-TOOLS/diagnostics/`.
- Los dos puntos de entrada dejaron de redirigir a un instalador web inexistente.
- `docs/PARTIAL_ESTRUCTURE.md` refleja la estructura real.

**Fuera, y por qué:**

- **Rotar la contraseña.** Es el arreglo de verdad y necesita tu MySQL. Sigue pendiente. Sacarla del
  archivo no la borra del historial de git.
- **Deuda 3** (los docs declaran PHP 8.1+ y el principio 1 fija 8.3+). Son `docs/README.md` y
  `docs/INSTALL.md`.
- **`docs/INSTALL.md` lista Composer como requisito**, y el principio 3 dice que no hay Composer.
  Se anotó como deuda 6.
- Reparar el instalador retirado. Está roto (lee `db/db.sql` y `db/migrations.sql`, que no existen)
  y su propio mensaje de éxito pedía borrarlo. Se retiró, no se reparó.

## Checklist

- [x] `01-TOOLS/mysql/.env` con las credenciales reales, y git lo ignora.
      `01-TOOLS/.gitignore:2:*/.env  01-TOOLS/mysql/.env`
- [x] `01-TOOLS/mysql/.env.example` sin secretos, y sí confirmado.
      `git status` lo lista como `?? 01-TOOLS/mysql/.env.example`; `.env` no aparece.
- [x] `config/database.php` no contiene ningún secreto y lee del entorno.
      Buscar `Tru3L` y `pepiuox` en el archivo no devuelve nada; `php -l` limpio.
- [x] La aplicación sigue conectando a MySQL.
      `OK: conecta a pepiuox@127.0.0.1:3306/web_editor_db. Tablas visibles: 54`
- [x] Sin `.env`, el fallo explica qué hacer.
      Renombrado a `.env.bak`, mensaje leído, restaurado y reconectado.
- [x] Los cuatro scripts ya no están en `public/` y sí en `01-TOOLS/diagnostics/`.
      `FUERA public/install.php` (y los tres restantes); 5 archivos en el destino.
- [x] El arranque no queda mudo sin instalador web.
      `php -l` limpio en los dos puntos de entrada; `GET /` → `HTTP 200`, 5.976 bytes;
      `GET /install.php` → `HTTP 404`.
- [x] `docs/PARTIAL_ESTRUCTURE.md` refleja la estructura real.
      El árbol ya no nombra `install.php` en `public/` ni los inexistentes `db/db.sql`.

## Evidence

```
== git ignora el .env? ==
01-TOOLS/.gitignore:2:*/.env	01-TOOLS/mysql/.env

== php -l ==
No syntax errors detected in config\env.php
No syntax errors detected in config\database.php
No syntax errors detected in index.php
No syntax errors detected in public\index.php
No syntax errors detected in 01-TOOLS\mysql\test_connection.php
No syntax errors detected in 01-TOOLS\diagnostics\{install,diagnose,test-files,test-trusted}.php

== el secreto sigue en el archivo? ==
NO: ni la contraseña ni el usuario aparecen en config/database.php

== prueba real de conexion via .env ==
OK: conecta a pepiuox@127.0.0.1:3306/web_editor_db. Tablas visibles: 54

== el proceso gana sobre el .env? ==
$env:DB_USER='otro_usuario'
FALLO: SQLSTATE[HY000] [1045] Access denied for user 'otro_usuario'@'localhost'

== sin .env = ==
Configuración incompleta: Faltan credenciales de MySQL. Copia 01-TOOLS/mysql/.env.example a
01-TOOLS/mysql/.env y rellena DB_NAME, DB_USER y DB_PASSWORD, o defínelas como variables de entorno.

== la app arranca ==
HTTP 200 | bytes: 5976 | <title>Inicio</title>

== /install.php ahora ==
HTTP 404

== referencias restantes a los scripts retirados en el codigo ==
ninguna
```

**Un fallo propio, encontrado por la prueba y corregido.** La primera versión de `config/env.php`
declaraba `load_env_file()` pero `config/database.php` no la llamaba: el test de conexión falló con
"Faltan credenciales". La prueba lo atrapó antes de dar nada por hecho. Es exactamente para lo que
existe el `test_connection` de `01-TOOLS`.

**Lo que NO se probó.** La conexión se probó por CLI. No se probó el arranque bajo Apache con
`.htaccess`, ni la instalación desde cero, ni que el historial de git deje de contener la
contraseña: la contiene todavía.

## Next

Rotar la contraseña de MySQL y actualizar `DB_PASSWORD` en `01-TOOLS/mysql/.env`. Pasos en
`01-TOOLS/mysql/CREDENTIALS.md`. Después, deuda 3 y 6: alinear `docs/README.md` y `docs/INSTALL.md`.
