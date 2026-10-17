---
author: Pepiuox
branch: main
status: done
---
# Rotar la contraseña de `root`

## Intent

Cerrar el punto que quedaba abierto desde el primer cambio de credenciales: la cuenta `root` de
MySQL entraba con la contraseña `root`. Estaba anotado en `CREDENTIALS.md` y en dos fichas como
«pendiente de decisión del dueño, porque cambiarla puede romper otras herramientas locales». El
dueño decidió rotarla; esto es el inventario previo y la rotación.

No era una deuda numerada de la constitución: vivía fuera de la tabla. Ahora está resuelta.

## Scope

**Dentro:**

- Rotar las **cuatro** cuentas `root` a la vez.
- Guardar la contraseña nueva en `01-TOOLS/mysql/.env`, que git ignora.
- Dejar phpMyAdmin entrando, que es el único consumidor vivo que se encontró.
- Decir en la documentación dónde está la contraseña y qué se rompe si se rota.

**Fuera, con motivo:**

- Las dos páginas de prueba de `C:\ZampGUI\apache\htdocs`. Ponerles la contraseña nueva sería dejar
  la credencial del superusuario en un archivo que sirve Apache. Deuda 13.
- Los proyectos `opticas` y `pepiuox-maker`. Ya estaban rotos antes de tocar nada (ver evidencia).
- `finanzapp`. Su `MYSQL_ROOT_PASSWORD: root` es de contenedores de GitHub Actions, no de este
  servidor local.

## Checklist

- [x] Las cuatro cuentas `root` con la contraseña nueva.
- [x] La contraseña vieja ya no entra.
- [x] La contraseña nueva guardada en el `.env`, y su sitio documentado en `CREDENTIALS.md`.
- [x] phpMyAdmin entra y lista las bases.
- [x] La aplicación no se enteró: usa `web_editor_app` (comprobado).
- [x] La guía de instalación ya no manda un comando que falla.

## Evidence

**1. Lo que había, medido antes de tocar.** Cuatro cuentas `root`, todas con el **mismo** hash
`*81F5E21E35407D8…`, que es el conocido de la contraseña `root`:

```
root@127.0.0.1        *81F5E21E35407D8...
root@::1              *81F5E21E35407D8...
root@desktop-8olhnhr  *81F5E21E35407D8...
root@localhost        *81F5E21E35407D8...
```

**2. Lo que se rompía al rotarla.** El servidor sirve **13 bases** (`finanzapp`, `menu_saas`,
`phpgrapesjs`, `plataforma_integral`, `qr_menu_project`, `usermanager`, `volunteer_platform`,
`web_editor_db`…), así que la lista de consumidores importaba:

| Consumidor | Estado medido |
|---|---|
| `C:\ZampGUI\www\pma\config.inc.php` (phpMyAdmin) | **Vivo.** `auth_type = config`, `root`/`root`. Hay que actualizarlo. |
| `ZampGUI\apache\htdocs\dbtest.php` y `test.php` | Vivos y **alcanzables por web**. Ahora devuelven `HTTP 200` con `Fatal error: Uncaught mysqli_sql_exception: Access denied for user 'root'@'localhost'` y la traza. |
| `opticas\config.php` | Ya roto: entra como `root` con `Tru3L@ve`, que **no entra** (error 2054), y la base `opticas` **no existe**. |
| `pepiuox-maker` (2 configs) | Ya roto: igual, y la base `grapescms` **no existe**. |
| `finanzapp` (CI) | `MYSQL_ROOT_PASSWORD` en un contenedor de GitHub Actions: no es este servidor. |

Que las cuentas `root` son `definer` de 4 disparadores y 5 rutinas de `web_editor_db` **no** es un
problema: el `definer` guarda el nombre, no la contraseña.

**3. La rotación.** Cuatro `ALTER USER`, y los cuatro hashes pasan de `*81F5E21E…` a un valor nuevo
idéntico en las cuatro:

```
root@127.0.0.1        *DB727C001A4E468...  cambiada
root@::1              *DB727C001A4E468...  cambiada
root@desktop-8olhnhr  *DB727C001A4E468...  cambiada
root@localhost        *DB727C001A4E468...  cambiada
todas las cuentas con el mismo hash: SI
```

**4. La comprobación, en conexión nueva.** La vieja rechazada y la nueva dentro:

```
host=127.0.0.1 -> ENTRA como root@localhost
host=localhost -> ENTRA como root@localhost
con la vieja ('root'): rechazada (1045)
```

**5. phpMyAdmin, por HTTP y no solo por conexión.** Su `config.inc.php` tiene ya la contraseña del
`.env` (`php -l` limpio antes y después), y `http://127.0.0.1/pma/` responde `HTTP 200` con
`web_editor_db` y `finanzapp` en la página, sin `Access denied` ni `Cannot log in`.

**6. La aplicación sigue igual.** `php 01-TOOLS/mysql/test_connection.php` →
`OK: conecta a web_editor_app@127.0.0.1:3306/web_editor_db. Tablas visibles: 54`.

**7. Un error propio, dicho.** El primer intento de actualizar phpMyAdmin le dejó la contraseña
**vacía**: se leyó el `.env` con `parse_ini_file()`, que falla con un aviso en la línea 3 porque los
comentarios llevan paréntesis, así que la contraseña leída fue la cadena vacía; y el reemplazo en
`config.inc.php` se escribió con comillas dobles, de modo que PHP interpoló `$cfg` y `$i` en la
cadena de sustitución. Se detectó al comprobar (la contraseña guardada no coincidía), se restauró
desde la copia `.rscbak` que se había hecho antes, y se repitió con un lector propio del `.env` y con
`preg_replace_callback`. De ahí que la evidencia 5 compruebe la línea guardada y no solo el `php -l`.

**8. Un defecto de la guía de instalación, encontrado de paso.** `docs/INSTALL.md` mandaba
`mysql -u pepiuox -p …`, pero en esta máquina el cliente **no está en el PATH** (vive en
`C:\ZampGUI\mariadb\bin\`). El comando, tal cual, no funcionaba. Corregido con la ruta completa y la
alternativa corta.

## Lo que esta ficha deja obsoleto

Frases que eran ciertas y ya no lo son. No se reescriben los documentos: son registros con fecha.

- `02-DOCS/wiki/ftd/rotar-credenciales.md`, líneas 25 y 103: «La contraseña de `root` … es `root`».
- `02-DOCS/wiki/ftd/usuario-de-bd-con-permisos-minimos.md`, líneas 31 y 109: «sigue pendiente de
  decisión del dueño».
- `02-DOCS/wiki/harness/decisions.md`, línea 50: la entrada antigua, que se conserva por ser un
  registro cronológico.

## Next

Quedan la deuda 7 (los tres hashes sembrados), la 11 (las colaciones), la 12 (los assets de
`private/`) y la 13 (las dos páginas de prueba de Apache). La 4 sigue como riesgo aceptado.
