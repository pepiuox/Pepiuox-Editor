---
author: Pepiuox
branch: main
status: done
---
# La instalación documentada y los puntos de entrada

## Intent

Tres deudas de una vez, porque son la misma cosa vista desde tres sitios: la documentación de
instalación no existe, y los puntos de entrada cuentan mentiras sobre ella.

- **Deuda 9.** `docs/INSTALL.md` tiene 41 líneas desde el commit inicial: promete un índice de 11
  secciones y existen 2, y no menciona el `.env`, que es donde viven las credenciales desde el
  cambio anterior. `docs/README.md` son 10 líneas cortadas a mitad de un bloque `sql`. Y el mensaje
  500 de los puntos de entrada remitía a `INSTALL.md` «para configurar la base de datos», cosa que
  ese documento no explicaba.
- **Deuda 3.** La tabla de requisitos de esa misma guía declaraba PHP 8.1 mínimo y 8.2+
  recomendado, contra el principio 1 (8.3+).
- **Deuda 10.** `private/index.php` redirige a `install.php`, que nunca existió bajo `private/`.

Y al verificarlo apareció algo peor que las tres.

## Scope

**Dentro:**

- Reescribir `docs/INSTALL.md` como guía de verdad, con datos comprobados.
- Arreglar `docs/README.md`.
- PHP 8.3+ en los dos.
- Corregir el mensaje del guard de `index.php` y `public/index.php` para que diga la verdad.
- **Quitar el volcado del árbol de archivos del `index.php` de la raíz.**

**Fuera, con motivo:**

- `private/index.php`. Repararlo o retirarlo es una decisión del dueño, y la evidencia nueva cambia
  la respuesta: bajo el `.htaccess` de la raíz no es alcanzable, y además falla al arrancar. Deuda 10,
  ampliada.
- La mezcla de colaciones de la base. Otra decisión. Deuda 11.
- La configuración de nginx: no hay ninguna probada, así que la guía lo dice en vez de inventarla.

## Checklist

- [x] `docs/INSTALL.md` explica la instalación completa y no promete secciones que no tiene.
- [x] `docs/README.md` está entero.
- [x] Los dos dicen PHP 8.3+.
- [x] El mensaje del guard dice qué falta de verdad, y se dispara.
- [x] El árbol de archivos del proyecto ya no se publica en ninguna configuración.

## Evidence

**1. Los requisitos, medidos en esta máquina** (`php -v`, `php -m`): PHP 8.4.25, y las nueve
extensiones presentes (`pdo_mysql, mbstring, json, openssl, curl, gd, zip, intl, fileinfo`).
Motor: MariaDB 12.3.3.

**2. El hallazgo grave: la portada publicaba el árbol de archivos.**

El `index.php` de la raíz terminaba así:

```php
function listarCarpeta($ruta, $nivel = 0) { /* scandir recursivo, con 📁 y 📄 */ }

listarCarpeta(__DIR__);
```

Sin condición: se ejecutaba **al final de cada petición** y volcaba el árbol recursivo de `__DIR__`
dentro de la respuesta. Comparado midiendo la misma portada:

| Configuración | Antes | Después |
|---|---|---|
| *document root* = raíz del proyecto | HTTP 200, **429 050 bytes** | HTTP 200, **5 972 bytes** |
| *document root* = `public/` | HTTP 200, 5 972 bytes | HTTP 200, 5 972 bytes |

Con el *document root* en la raíz, la página incluía `.dsh/`, `skills/`, `README.md`, `SKILL.md` y,
en general, `config/`, `db/` y `01-TOOLS/` con sus nombres de archivo. Dos comprobaciones después del
arreglo:

```
sigue filtrando el arbol? False
aparece .dsh o 01-TOOLS?  False
```

Nada más llamaba a `listarCarpeta`: el grep sobre todo el proyecto deja solo la definición y su
única llamada, ambas en ese archivo. Era una ayuda de desarrollo olvidada en un punto de entrada.

Que las dos configuraciones den **el mismo tamaño** (5 972 bytes) después del arreglo es, de paso, la
prueba de que el `index.php` de la raíz y el de `public/` sirven la misma portada.

**3. El mensaje del guard, disparado a propósito** (apartando `config/database.php` y restituyéndolo
después):

```
HTTP 500 | Falta config/database.php. Ese archivo lee las credenciales; no las lleva. Copia
01-TOOLS/mysql/.env.example a 01-TOOLS/mysql/.env y rellena DB_NAME, DB_USER y DB_PASSWORD.
Guía: docs/INSTALL.md
```

**4. Lo que ve quien clona sin `.env`,** que es el caso que la deuda 9 decía mal contado. Ya estaba
bien resuelto por la excepción de `config/database.php`, y ahora está documentado:

```
HTTP 500 | 500 - Error interno del servidor — Faltan credenciales de MySQL. Copia
01-TOOLS/mysql/.env.example a 01-TOOLS/mysql/.env y rellena DB_NAME, DB_USER y DB_PASSWORD, o
defínelas como variables de entorno.
```

**5. Dato que la guía necesitaba y que destapó la deuda 11.** La base reparte sus tablas en tres
colaciones: `utf8mb4_uca1400_ai_ci` en 45, `utf8mb4_unicode_ci` en 4 y `utf8mb3_uca1400_ai_ci` en 3
(`users_permissions`, `users_roles`, `users_role_permissions`). Las de `uca1400` son propias de
MariaDB: la tabla de requisitos decía «MySQL 8.0 / MariaDB 10.5» y no era cierto.

**6. `private/` no es alcanzable, y además está roto.** Con el `.env` en su sitio:

```
/                    -> HTTP 200 | <title>Inicio</title>
/private/index.php   -> HTTP 200 | <br /><b>Fatal error</b>: Uncaught ArgumentCountError:
                        Too few arguments to function {closure:config\routes.php:44}(), 1 passed…
/public/index.php    -> HTTP 404   (normal si el document root no es public/)
```

Es decir: el tercer punto de entrada devuelve **200 con un error fatal y la traza a la vista**. El
`.htaccess` de la raíz reescribe todo hacia `public/`, así que no se sirve; pero el archivo está ahí
y miente. Deuda 10 ampliada con esto.

**7. `php -l` limpio** en `index.php` y `public/index.php`.

## Next

Cerradas la deuda 3 y la 9. Quedan: la 5 (el índice del wiki), la 7 (los hashes sembrados), la 10
(`private/index.php`: reparar o retirar) y la 11 (las colaciones). La 4 sigue como riesgo aceptado.
