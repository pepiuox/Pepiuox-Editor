---
author: Pepiuox
branch: main
status: done
---
# Usuario de base de datos con permisos mínimos

## Intent

Es la deuda 8. La aplicación se conectaba a MySQL como `pepiuox@%`, una cuenta con
`ALL PRIVILEGES ON *.* WITH GRANT OPTION`, o sea un superusuario del servidor entero, mientras
`CREDENTIALS.md` pedía justo lo contrario. Un fallo de inyección en cualquier consulta no daba acceso
a la base del proyecto: daba acceso a las ocho bases que hay en esa máquina. Se crea un usuario que
solo pueda hacer lo que la aplicación hace de verdad.

## Scope

**Dentro:**

- Crear el usuario de la aplicación con los permisos mínimos que necesita.
- Apuntar `01-TOOLS/mysql/.env` a él.
- Probar en las dos direcciones: que lo que necesita funciona, y que lo que no necesita está denegado.

**Fuera, con motivo:**

- Reducir los permisos de `pepiuox@%`. Sigue siendo superusuario, pero ya no lo usa la aplicación: es
  la cuenta de administración del dueño y sirve para importar volcados y correr migraciones.
- `EXECUTE` sobre las cinco rutinas (`definer=root@localhost`): la aplicación no las llama. Son para
  eventos y tareas del servidor.
- `EVENT`: la aplicación no programa eventos.
- La contraseña de `root` (`root`) sigue pendiente de decisión del dueño.

## Checklist

- [x] El usuario existe con los permisos mínimos.
      Prueba: `SHOW GRANTS` muestra solo `SELECT, INSERT, UPDATE, DELETE` sobre `web_editor_db`.
- [x] La aplicación lo usa.
      Prueba: `.env` y `php 01-TOOLS/mysql/test_connection.php` con el usuario nuevo.
- [x] Todo lo que la aplicación necesita sigue funcionando.
      Prueba: las consultas reales de la aplicación, incluido el `SHOW CREATE TABLE` del backup.
- [x] Lo que no necesita está denegado.
      Prueba: `mysql.user`, otra base de datos, `CREATE`, `DROP`, `ALTER` y `GRANT` fallan.
- [x] La aplicación arranca.
      Prueba: `GET /` responde 200 con HTML real.

## Evidence

Cómo se eligió el alcance: se buscó DDL en tiempo de ejecución en `app/` y no hay. Los aciertos
iniciales eran falsos positivos de la palabra `truncate`. `BackupService` usa `SHOW CREATE TABLE` y
`SELECT *`, que son de lectura. La base tiene 52 tablas, 2 vistas, 4 disparadores y 5 rutinas; los
disparadores y las rutinas son `definer=root@localhost`, así que corren con los privilegios de `root`
y la aplicación no necesita `TRIGGER` ni `EXECUTE` para ellos.

**1. Permisos exactos que quedaron** (`SHOW GRANTS` como el propio usuario):

```
GRANT USAGE ON *.* TO `web_editor_app`@`localhost` IDENTIFIED BY PASSWORD '*4FF7...'
GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO `web_editor_app`@`localhost`
```

`USAGE ON *.*` significa «puede conectarse y nada más»: es la fila vacía que MySQL crea con cada
usuario, no un permiso. Existe el mismo par de filas para `web_editor_app@127.0.0.1`.

**2. Lo que SÍ puede hacer** (probado, no supuesto):

```
OK  SELECT sobre users -> 3 filas
OK  SELECT sobre las 2 vistas -> daily_summary=2, user_activity_summary=3
OK  SHOW CREATE TABLE pages (lo usa BackupService) -> devuelve 1517 bytes de definicion
OK  SHOW TABLES -> 54 objetos
OK  INSERT en notifications (deshecho, 0 filas)
OK  UPDATE en notifications.id (0 filas)
OK  DELETE en notifications (0 filas)
```

Las tres escrituras van dentro de una transacción que se deshace, y con `WHERE 1=0`: MySQL comprueba
el privilegio antes de mirar si hay filas, así que la prueba es real y no deja rastro en los datos.

**3. Lo que NO puede hacer** (cada línea es un intento que falló):

```
DENEGADO leer mysql.user -> 1142 SELECT command denied ... for table `mysql`.`user`
DENEGADO leer la base de otro proyecto (finanzapp) -> 1044 Access denied ... to database 'finanzapp'
DENEGADO crear una tabla -> 1142 CREATE command denied
DENEGADO borrar la tabla users -> 1142 DROP command denied
DENEGADO cambiar la estructura de users -> 1142 ALTER command denied
DENEGADO darse permisos a si mismo -> 1045 Access denied
```

**4. Bases de datos que ve:** `information_schema, web_editor_db`. Antes esta cuenta habría visto
también `finanzapp`, `menu_saas`, `phpgrapesjs`, `plataforma_integral`, `qr_menu_project`,
`usermanager` y `volunteer_platform`.

**5. La aplicación arranca:**

```
OK: conecta a web_editor_app@127.0.0.1:3306/web_editor_db. Tablas visibles: 54
[200]: GET /
HTTP 200 / 423501 bytes / <title>: True
```

**Observación sobre el servidor, no sobre el proyecto.** El `SHOW GRANTS` de cualquier usuario
incluye dos líneas de una cuenta `PUBLIC` que dan `ALL` sobre las bases que se llamen `test` o
`test_%`. Es configuración del MySQL local, sale en todos los usuarios y no la toca este cambio;
queda anotada por si algún día se endurece el servidor.

## Next

Cerrado. Lo que queda pendiente de decisión es la contraseña de `root` (`root`) y los tres hashes
bcrypt de `db/db-full.sql` (deuda 7).
