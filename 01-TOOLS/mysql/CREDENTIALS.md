# Credenciales de MySQL

## Dónde viven

En `01-TOOLS/mysql/.env`, que git ignora. Nunca en `config/database.php`, que sí se confirma.

## Hay dos usuarios, y no son el mismo

| Usuario | Para qué | Permisos |
|---------|----------|----------|
| `web_editor_app` | **El que usa la aplicación**: es el valor de `DB_USER`. | `SELECT, INSERT, UPDATE, DELETE` sobre `web_editor_db.*`. Nada más. |
| `pepiuox` | Tu cuenta de administración de MySQL: cliente de consola, importar volcados, correr migraciones. La aplicación **ya no la usa**. | `ALL PRIVILEGES ON *.* WITH GRANT OPTION`. Es un superusuario: no la pongas en `DB_USER`. |

`web_editor_app` existe en dos hosts, `@localhost` y `@127.0.0.1`, con la misma contraseña. Son dos
filas distintas del mismo usuario, para que no dependa de cómo el servidor resuelva los nombres.

## Cómo obtenerlas

1. Crea la base de datos.
2. Copia la plantilla: `cp 01-TOOLS/mysql/.env.example 01-TOOLS/mysql/.env`.
3. Rellena `DB_NAME`, `DB_USER` y `DB_PASSWORD` con el usuario de la aplicación.

El usuario de la aplicación necesita exactamente `SELECT, INSERT, UPDATE, DELETE` sobre la base de
datos del proyecto. No le des `CREATE`, `DROP`, `ALTER`, `GRANT` ni permisos sobre `mysql.*`: las
migraciones las corres tú con tu cuenta de administración.

```sql
CREATE USER 'web_editor_app'@'localhost' IDENTIFIED BY 'una-contraseña-larga-y-aleatoria';
GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO 'web_editor_app'@'localhost';
```

## Cómo rotarlas

La contraseña del usuario de la aplicación se rotó el 2026-10-17. La anterior estuvo confirmada en
el historial de git; sacarla del archivo no la borra del historial, así que se cambió de verdad en el
servidor. Git conserva el commit antiguo, pero esa contraseña ya no abre nada. No la reutilices en
ningún sitio.

Para rotar la de `web_editor_app`:

1. Genera una contraseña larga y aleatoria **sin comillas, `=`, `#` ni espacios**: son los caracteres
   que rompen un `.env` o una consola.
2. Cámbiala en MySQL y refresca los privilegios:
   ```sql
   ALTER USER 'web_editor_app'@'localhost' IDENTIFIED BY 'una-contraseña-nueva-y-larga';
   ALTER USER 'web_editor_app'@'127.0.0.1' IDENTIFIED BY 'una-contraseña-nueva-y-larga';
   FLUSH PRIVILEGES;
   ```
3. Actualiza `DB_PASSWORD` en `01-TOOLS/mysql/.env`.
4. Prueba en un **proceso nuevo**: `php 01-TOOLS/mysql/test_connection.php`. Comprobar la rotación
   dentro del mismo proceso da un falso negativo: `require_once` impide recargar `config/env.php` y
   el proceso seguiría usando la contraseña vieja que ya tenía cacheada.
5. Si hay procesos PHP de larga vida (un worker en cola, un daemon), reinícialos. Apache con
   `mod_php` y PHP-FPM leen la configuración en cada petición y no lo necesitan.

## La cuenta `root`

Es la cuenta de administración del **servidor**, no del proyecto: sirve a las 13 bases que hay en
esta máquina, no solo a `web_editor_db`.

Su contraseña vive en `01-TOOLS/mysql/.env`, en `DB_ROOT_PASSWORD` (con `DB_ROOT_USER` al lado).
**Nunca en este archivo**, que sí se confirma en git.

Se rotó el 2026-10-17. Antes era `root`, la primera contraseña que prueba cualquiera. El hash viejo
(`*81F5E21E…`) sigue en el historial de git, pero ya no abre nada. La rotación cubrió las cuatro
cuentas que existían: `root@localhost`, `root@127.0.0.1`, `root@::1` y `root@<nombre-del-equipo>`.

Qué la usa, y qué hay que tocar al rotarla:

- **phpMyAdmin** (`C:\ZampGUI\www\pma\config.inc.php`, con `auth_type = config`) guarda esta misma
  contraseña en su configuración. Al rotarla hay que actualizar también esa línea, o phpMyAdmin deja
  de entrar. Es el único consumidor vivo que se encontró.
- **Disparadores y rutinas.** Cuatro disparadores y cinco rutinas de `web_editor_db` tienen
  `definer=root@localhost`. El `definer` guarda el nombre, no la contraseña: mientras la cuenta
  exista, rotar la contraseña no los afecta.
- **Dos páginas de prueba** en `C:\ZampGUI\apache\htdocs` (`dbtest.php` y `test.php`) llevan
  `root`/`root` escrito y **son alcanzables por web**. Desde la rotación devuelven un error de acceso
  con la traza a la vista. No se les puso la contraseña nueva a propósito: sería dejar la credencial
  del superusuario en un archivo que sirve Apache. Sobran: deuda 13.

Para rotarla, las cuatro cuentas a la vez:

```sql
ALTER USER 'root'@'localhost'   IDENTIFIED BY 'nueva-contraseña-larga-y-aleatoria';
ALTER USER 'root'@'127.0.0.1'   IDENTIFIED BY 'nueva-contraseña-larga-y-aleatoria';
ALTER USER 'root'@'::1'         IDENTIFIED BY 'nueva-contraseña-larga-y-aleatoria';
ALTER USER 'root'@'nombre-equipo' IDENTIFIED BY 'nueva-contraseña-larga-y-aleatoria';
```

Y actualizar `DB_ROOT_PASSWORD` en el `.env` **y** phpMyAdmin, o te quedas fuera de los dos.
