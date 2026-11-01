# 🚀 Guía de instalación — PePiuoX Editor

> **Versión:** 2.0.0
> **Última actualización:** 17 de octubre de 2026

Esta guía está escrita para el proyecto tal como está hoy. Si algo de aquí no coincide con lo que te
encuentras, es un error de la guía: anótalo en la deuda de la constitución
(`02-DOCS/wiki/sdd/constitution.md`) en vez de improvisar.

> ## ⚠️ Lee esto antes de seguir (revisión del 31 de octubre de 2026)
>
> Esta guía sigue siendo válida para instalar **por consola**, pero se queda corta en tres puntos y
> conviene que lo sepas antes de ejecutar nada:
>
> 1. **La guía completa es [`MANUAL_DE_INSTALACION.md`](MANUAL_DE_INSTALACION.md).** Cubre servidor
>    web, privilegios mínimos de base de datos, SSL, tareas programadas, restauración de copias y
>    veinte errores frecuentes. Este archivo se conserva como guía breve.
> 2. **Para una instalación nueva, el volcado es `db/install/initial_database.sql`**, no
>    `db/db-full.sql`. El segundo es el esquema de referencia del proyecto y **contiene datos de una
>    instalación real** —cuentas con sus hashes y secretos de aplicaciones de API—, así que no debe
>    usarse para montar un sistema limpio ni para distribuirlo. El primero trae estructura, catálogos
>    y nada más: 52 tablas, 2 vistas, 4 triggers, 5 rutinas y cero datos de personas.
> 3. **El primer administrador ya no viene sembrado.** `initial_database.sql` no incluye ninguna
>    cuenta ni contraseña. Se crea por consola:
>    `php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com --generate`
>
> **Sobre el instalador web.** El párrafo siguiente dice que está retirado y que no vuelve. Eso sigue
> siendo el estado **documentado y confirmado**: la ruta soportada es la de consola. En el árbol de
> trabajo existen `install.php` y `public/install.php` **sin confirmar en git**, producto de un
> trabajo en curso del dueño del proyecto. No forman parte de este procedimiento, no están probados
> aquí y su adopción chocaría de frente con el principio 16 de la constitución: si se decide
> adoptarlos, hay que enmendar la constitución a propósito y con su propia prueba, no por la puerta
> de atrás.

## Antes de empezar: no hay instalador web

Existió un `install.php` que instalaba desde el navegador. Está retirado y **no vuelve**: nada que
instale, diagnostique o pruebe puede ser alcanzable por HTTP (constitución, principio 16). Se
conserva, sin uso, en `01-TOOLS/diagnostics/`.

La instalación son cuatro pasos manuales: requisitos, base de datos, credenciales y volcado.

---

## 1. Requisitos

| Componente | Versión | Notas |
|------------|---------|-------|
| **PHP** | 8.3 o superior | Probado con 8.4.25 |
| **MariaDB** | 11 o superior | Probado con 12.3.3. **MySQL no sirve tal cual**: el volcado usa colaciones propias de MariaDB. Ver el apartado 10. |
| **Apache** | 2.4 con `mod_rewrite` | O nginx con la reescritura equivalente |
| **Composer** | **no hace falta** | El proyecto se auto-carga con PSR-4; no hay dependencias (principio 3) |

**Extensiones PHP necesarias.** Comprueba con `php -m`:

```
pdo_mysql   mbstring   json   openssl   curl   gd   zip   intl   fileinfo
```

---

## 2. Qué se ve por web y qué no

Aquí está la decisión que más importa para la seguridad. El proyecto se sirve de una de dos formas:

- **Recomendada:** el *document root* de Apache apunta a `<proyecto>/public`. Lo que está fuera de
  `public/` no es alcanzable por HTTP ni por accidente. `public/.htaccess` se encarga de las rutas.
- **Alternativa:** el *document root* es la raíz del proyecto y el `.htaccess` de la raíz hace el
  trabajo: bloquea `config/`, `app/`, `views/`, `storage/`, `db/`, `vendor/`, `.git/` y `.env/`, y
  reescribe todo internamente hacia `public/`.

En las dos formas, esto **nunca** debe ser alcanzable: `config/`, `app/`, `views/`, `storage/`,
`db/`, `01-TOOLS/`, `02-DOCS/` y ningún `.env`.

> `private/` ya no tiene punto de entrada: el suyo se retiró el 2026-10-17 y está en
> `01-TOOLS/diagnostics/`. Conserva una copia antigua de los assets, que el código no referencia:
> los vivos son `public/assets/` (`asset('css/maker.css')` resuelve ahí). Anotado como deuda 12.

---

## 3. Crear la base de datos y el usuario

El usuario de la aplicación **no** debe ser un superusuario: solo necesita leer y escribir.

```sql
CREATE DATABASE web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

CREATE USER 'web_editor_app'@'localhost' IDENTIFIED BY 'una-contraseña-larga-y-aleatoria';
GRANT SELECT, INSERT, UPDATE, DELETE ON `web_editor_db`.* TO 'web_editor_app'@'localhost';
FLUSH PRIVILEGES;
```

Las migraciones o los cambios de esquema los corres aparte, con tu cuenta de administración. El
usuario de la aplicación no puede crear ni borrar tablas a propósito.

Detalle completo en `01-TOOLS/mysql/CREDENTIALS.md`.

---

## 4. Poner las credenciales

Las credenciales **no** se guardan en el repositorio.

```powershell
Copy-Item 01-TOOLS\mysql\.env.example 01-TOOLS\mysql\.env
```

Rellena en `01-TOOLS/mysql/.env`: `DB_NAME`, `DB_USER` y `DB_PASSWORD`. El `.env` está ignorado por
git; compruébalo tú mismo con `git check-ignore -v 01-TOOLS/mysql/.env`.

Si vas a administrar el servidor —importar volcados, correr migraciones, rotar la contraseña de
`root`— guarda también ahí `DB_ROOT_USER` y `DB_ROOT_PASSWORD`, en el mismo archivo ignorado. El
detalle está en `01-TOOLS/mysql/CREDENTIALS.md`; la aplicación no lee esas dos.

**Precedencia:** las variables del proceso ganan sobre el archivo. Si defines `DB_NAME`, `DB_USER` y
`DB_PASSWORD` como variables de entorno reales (`SetEnv` de Apache, `systemd`, Docker), el `.env` ni
se mira: en producción puedes no tener `.env`.

Si no hay credenciales, la aplicación no arranca a medias: responde **HTTP 500** con el mensaje
«Faltan credenciales de MySQL», diciendo qué copiar y qué rellenar.

---

## 5. Importar el esquema y los datos

El volcado es `db/db-full.sql` (848 KB): 54 sentencias `CREATE TABLE` y 30 `INSERT`. Se generó desde
MariaDB 12.3.2, y su propia cabecera lo dice.

```powershell
# En esta máquina el cliente de MariaDB no está en el PATH: se llama por su ruta.
# Si el tuyo sí está en el PATH, vale el corto: `mysql -u pepiuox -p web_editor_db < db\db-full.sql`.
C:\ZampGUI\mariadb\bin\mysql.exe -u pepiuox -p web_editor_db < db\db-full.sql
```

> No existen `db/db.sql` ni `db/migrations.sql`. El instalador retirado los buscaba y por eso estaba
> roto: `db/db-full.sql` es el único volcado del proyecto.

Al terminar, la base tiene 52 tablas y 2 vistas (`daily_summary` y `user_activity_summary`).

---

## 6. Verificar antes de seguir

```powershell
php 01-TOOLS\mysql\test_connection.php
```

Debe responder algo así:

```
OK: conecta a web_editor_app@127.0.0.1:3306/web_editor_db. Tablas visibles: 54
```

Si falla aquí, no sigas: el problema está en los pasos 3, 4 o 5.

---

## 7. Primer acceso

Levanta el sitio (Apache con el *document root* del paso 2, o para probar:

```powershell
php -S 127.0.0.1:8000 -t public
```

) y entra en `/login`.

El volcado siembra tres cuentas:

| # | Usuario | Correo | Rol |
|---|---------|--------|-----|
| 1 | `pepiuox` | pepiuox@yandex.com | Super admin |
| 2 | `manager` | admin@webeditor.com | Manager |
| 3 | `admin` | admin@local.test | Super admin |

**No se documentan sus contraseñas**, y hacen falta dos avisos:

1. Los hashes de esas tres cuentas están confirmados en git (deuda 7), así que se pueden atacar
   fuera de línea sin prisa. **Cambia las tres contraseñas** antes de publicar nada.
2. La cuenta `admin` (`admin@local.test`) es un resto del instalador retirado, que creaba esa cuenta
   por defecto. Si no la usas, desactívala o bórrala.

Para poner una contraseña conocida, genera el hash con el mismo algoritmo que usa la aplicación
(`PASSWORD_DEFAULT`, coste 12) y guárdalo:

```powershell
php -r "echo password_hash('TuContraseñaNueva', PASSWORD_DEFAULT, ['cost' => 12]);"
```

```sql
UPDATE users SET password_hash = '<el-hash-que-te-devolvió>' WHERE username = 'pepiuox';
```

---

## 8. Problemas frecuentes

| Síntoma | Qué pasa |
|---------|----------|
| `500 — Faltan credenciales de MySQL` | No hay `.env` ni variables de entorno. Paso 4. |
| `500 — Falta config/database.php` | Alguien borró ese archivo. Restáuralo desde el repositorio. |
| `1045 Access denied for user` | Usuario o contraseña mal en el `.env`, o el usuario no existe. Paso 3. |
| `1044 Access denied ... to database` | El usuario existe pero no tiene permisos sobre esa base. Paso 3. |
| `Unknown collation: 'utf8mb4_uca1400_ai_ci'` | Estás en MySQL, no en MariaDB. Apartado 10. |
| La portada responde 404 o sirve PHP en crudo | El *document root* no es `public/` y falta el `.htaccess`. Paso 2. |
| `Only variables should be passed by reference` | Estás en PHP 8.3 o superior y algo quedó sin actualizar. Anótalo como deuda. |

---

## 9. Endurecer antes de publicar

- `config/config.php` → `'debug' => false`. Con `true`, la aplicación enseña trazas y mensajes de
  excepción por pantalla, y una traza contiene rutas absolutas de tu servidor.
- HTTPS, y entonces activa `secure` en la cookie de sesión (principio 20).
- Ningún `.env` dentro del *document root*.
- Las tres contraseñas del paso 7, cambiadas.

---

## 10. Lo que esta guía no cubre

Se dice en vez de callarse:

- **nginx.** No hay configuración probada. Necesita el equivalente a las reglas del `.htaccess`:
  servir `public/`, bloquear `config/`, `app/`, `views/`, `storage/`, `db/`, `01-TOOLS/`, `02-DOCS/`,
  y mandar todo lo demás a `public/index.php`.
- **MySQL.** El volcado usa colaciones propias de MariaDB (`utf8mb4_uca1400_ai_ci`, y tres tablas en
  `utf8mb3_uca1400_ai_ci`), así que importarlo en MySQL falla. La tabla de requisitos de esta guía
  decía «MySQL 8.0 / MariaDB 10.5» y eso no era cierto para el volcado: está corregido, y el fondo
  del problema —que la base mezcle tres colaciones y tres tablas no admitan 4 bytes— queda como
  deuda 11.
- **Despliegue** (servidor, dominio, copias de seguridad). No hay documento todavía.
