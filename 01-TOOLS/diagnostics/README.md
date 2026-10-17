# diagnostics — scripts retirados de la raíz web

Aquí viven los cinco scripts que respondían por HTTP desde la raíz web. Se retiraron el 2026-10-17
para cumplir el principio 16 de la constitución: nada que instale, diagnostique o pruebe puede ser
alcanzable desde la raíz web. Cuatro estaban en `public/`; el quinto era el punto de entrada de
`private/`.

| Script | Qué hacía | Estado |
|---|---|---|
| `install.php` | Instalador web en dos pasos. | Roto: lee `db/db.sql` y `db/migrations.sql`, que no existen. Su propio mensaje de éxito pedía borrarlo. |
| `diagnose.php` | Diagnóstico del sistema por navegador. | No se ejecuta: sus rutas relativas apuntan a `public/`. |
| `test-files.php` | Prueba del sistema de archivos. | No se ejecuta: mismas rutas. |
| `test-trusted.php` | Prueba de proxies de confianza. | No se ejecuta: mismas rutas. |
| `private-index.php` | Tercer punto de entrada, el de `private/`, duplicado del de la raíz y del de `public/`. | Roto: bajo el `.htaccess` de la raíz no se sirve, y cuando se alcanzaba respondía **HTTP 200** con `Uncaught ArgumentCountError` en `config/routes.php:44` y la traza a la vista. Nada lo referenciaba. |

**No se reparan.** Se conservan por historial. Si algún día hace falta algo de aquí, el camino es
escribirlo de nuevo como herramienta de terminal dentro de `01-TOOLS/<nombre>/`, con su
`test_connection` y sin exponerlo por web.

Instalar el proyecto: `docs/INSTALL.md`.
Comprobar la base de datos: `php 01-TOOLS/mysql/test_connection.php`.
