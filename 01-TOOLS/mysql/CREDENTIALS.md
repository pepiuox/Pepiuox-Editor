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

En esta máquina `root` de MySQL entra con la contraseña `root`. Es una credencial débil y sigue
pendiente de decisión: cambiarla puede romper otras herramientas locales que la tengan guardada.
