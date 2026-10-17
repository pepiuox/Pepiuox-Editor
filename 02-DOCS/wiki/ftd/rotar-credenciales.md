---
author: Pepiuox
branch: main
status: done
---
# Rotar las credenciales

## Intent

La deuda 1 de la constitución quedó a medias: las credenciales salieron de `config/database.php`, pero
la contraseña vieja seguía viva en el servidor y en el historial de git, así que cualquiera que
clonara el repositorio podía conectarse a la base de datos. Rotarla es lo que cierra el agujero de
verdad.

## Scope

**Dentro:**

- La contraseña del usuario de MySQL de la aplicación se cambia en el servidor y en
  `01-TOOLS/mysql/.env`.
- Se prueba que la contraseña vieja ya no entra y que la nueva sí, y que la aplicación arranca.

**Fuera, y se decide aparte:**

- La contraseña de `root` de MySQL. No está en git, pero es `root`. Cambiarla puede romper otras
  herramientas locales que la tengan guardada. Necesita decisión del dueño.
- Las contraseñas de las cuentas de la aplicación: `db/db-full.sql` (que el dueño tiene modificado
  desde antes) trae los hashes bcrypt de `pepiuox`, `manager` y `admin` confirmados en git.
  Cambiarlas afecta al inicio de sesión de la persona, así que también necesitan su decisión.
- `db/db-full.sql` no se toca en este cambio.

## Checklist

- [x] La contraseña del usuario de la aplicación cambia en MySQL.
      Prueba: la contraseña vieja ya no conecta y la nueva sí.
- [x] `01-TOOLS/mysql/.env` queda con la nueva.
      Prueba: `git check-ignore -v` sigue ignorándolo y no aparece en `git status`.
- [x] La aplicación sigue arrancando.
      Prueba: `GET /` responde 200 con HTML real.
- [x] No queda ningún archivo confirmado con la contraseña vieja.
      Prueba: búsqueda de la contraseña vieja en el repo fuera de `.git`.

## Evidence

Salida observada, no supuesta.

**1. La cuenta real es `pepiuox@%`, no `pepiuox@localhost`.** MariaDB 12.3.3 lista:
`pepiuox@%`, `root@localhost`, `root@127.0.0.1`, `root@::1`, `root@desktop-8olhnhr`. El cliente entra
desde `localhost`, que empareja con el comodín `%`. Rotar `@localhost` no habría hecho nada.

**2. Rotación aplicada:**

```
ALTER USER 'pepiuox'@'%' IDENTIFIED BY '<32 caracteres aleatorios>'
FLUSH PRIVILEGES
1. ALTER USER pepiuox@% : ok
2. .env actualizado: ok
```

La contraseña nueva son 32 caracteres de `A-Z a-z 0-9 - _`, generados con `random_bytes`/`random_int`
unos 190 bits. No lleva comillas, `=`, `#` ni espacios, que son los caracteres que rompen un `.env` o
una consola.

**3. La contraseña vieja está muerta** (proceso nuevo):

```
NO CONECTA: la vieja esta muerta (BIEN) -> SQLSTATE[HY000] [1045] Access denied for user 'pepiuox'@'localhost' (using password: YES)
```

**4. La nueva conecta** (proceso nuevo):

```
OK: conecta a pepiuox@127.0.0.1:3306/web_editor_db. Tablas visibles: 54
```

**5. La aplicación arranca** (servidor embebido de PHP 8.4.25):

```
[200]: GET /
HTTP 200 / longitud 423501
titulo presente: True
```

**6. El `.env` sigue fuera de git:** `git check-ignore -v 01-TOOLS/mysql/.env` lo marca como ignorado
y no aparece en `git status`.

**7. La búsqueda de la contraseña vieja fuera de `.git` solo la encontraba en el `.env`**, y ese
archivo ya se actualizó. Los otros dos aciertos eran documentos de este mismo cambio
(`CREDENTIALS.md` y la ficha anterior), que citaban la contraseña vieja en texto claro: se
reescribieron para no nombrarla.

**Lección del propio test.** La primera comprobación dio falso negativo: mi script releía
`config/database.php` dentro del mismo proceso, y `require_once` impide volver a ejecutar
`config/env.php`, así que siguió usando la contraseña vieja cacheada en el entorno del proceso. La
verificación válida es siempre en un **proceso PHP nuevo**. Corolario operativo: tras rotar hay que
reiniciar cualquier proceso PHP de larga vida (un worker en cola, un daemon); Apache con `mod_php` o
PHP-FPM leen la configuración en cada petición y no lo necesitan.

## Next

Cerrado para la cuenta de la aplicación. De lo que quedó abierto aquí, los privilegios de la cuenta
de base de datos se resolvieron en el cambio siguiente, `usuario-de-bd-con-permisos-minimos.md`
(deuda 8). Siguen pendientes de decisión del dueño la contraseña de `root` (es `root`) y los tres
hashes bcrypt confirmados en `db/db-full.sql` (deuda 7).
