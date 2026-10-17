# Credenciales de MySQL

## Dónde viven

En `01-TOOLS/mysql/.env`, que git ignora. Nunca en `config/database.php`, que sí se confirma.

## Cómo obtenerlas

1. Crea la base de datos y el usuario en MySQL/MariaDB.
2. Copia la plantilla: `cp 01-TOOLS/mysql/.env.example 01-TOOLS/mysql/.env`.
3. Rellena `DB_NAME`, `DB_USER` y `DB_PASSWORD`.

El usuario necesita, como mínimo: `SELECT`, `INSERT`, `UPDATE`, `DELETE` sobre la base de datos del
proyecto. No le des `GRANT` ni permisos sobre `mysql.*`.

## Cómo rotarlas

La contraseña `Tru3L@ve` estuvo confirmada en el historial de git. Sacarla del archivo no la borra
del historial: cualquiera que clone el repo la puede leer en un commit antiguo. Rotar es el arreglo
de verdad, y está pendiente.

1. Cambia la contraseña en MySQL:
   ```sql
   ALTER USER 'pepiuox'@'localhost' IDENTIFIED BY 'una-contraseña-nueva-y-larga';
   FLUSH PRIVILEGES;
   ```
2. Actualiza `DB_PASSWORD` en `01-TOOLS/mysql/.env`.
3. Prueba: `php 01-TOOLS/mysql/test_connection.php`.

Si el repositorio estuvo público en algún momento, la contraseña antigua ya está comprometida: no
la reutilices en ningún sitio.
