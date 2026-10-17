---
author: Pepiuox
branch: main
status: done
---
# Retirar el tercer punto de entrada

## Intent

Cerrar la deuda 10. `private/index.php` era un tercer punto de entrada, hermano de `index.php` y de
`public/index.php`, con dos defectos: redirigía a un `install.php` que nunca existió bajo `private/`,
y —esto se midió en el cambio anterior— no es alcanzable bajo el `.htaccess` de la raíz, que
reescribe todo hacia `public/`; alcanzado de otra forma, devuelve **HTTP 200** con
`Uncaught ArgumentCountError` en `config/routes.php:44` y la traza a la vista.

El dueño eligió entre repararlo y retirarlo, con la evidencia delante. Retirarlo.

## Scope

**Dentro:**

- Sacar `private/index.php` de la raíz web, a `01-TOOLS/diagnostics/private-index.php`, con `git mv`
  para no perder el historial.
- Cabecera de retirada, como la de los otros cuatro scripts de esa carpeta.
- Una fila más en `01-TOOLS/diagnostics/README.md`.
- Actualizar lo que la documentación decía de `private/`.

**Fuera, con motivo:**

- `private/assets/`. Son ~440 KB de una copia antigua de los assets. El dueño autorizó retirar el
  punto de entrada, no borrar assets. Deuda 12.
- Reparar el `ArgumentCountError` de `config/routes.php:44`. Si algún día se quiere ese punto de
  entrada, se escribe de nuevo: no se resucita un duplicado roto.

## Checklist

- [x] `private/index.php` ya no está en `private/`.
- [x] El archivo vive en `01-TOOLS/diagnostics/` con su cabecera de retirada.
- [x] El README de `diagnostics/` lo lista, y ya no dice «los cuatro scripts».
- [x] La guía de instalación no dice cosas falsas sobre `private/`.
- [x] La aplicación sigue arrancando igual.

## Evidence

**1. Antes de mover, que no arrastraba trabajo ajeno.** El árbol de trabajo tiene cambios sin
confirmar que no son de este cambio; `private/index.php` estaba idéntico a `HEAD`:

```
git diff --stat -- private/index.php   -> (vacío)
git status --porcelain -- private/     -> (vacío)
```

**2. El traslado, que git reconoce como movimiento y no como borrado más creación:**

```
R  private/index.php -> 01-TOOLS/diagnostics/private-index.php
```

**3. Que nadie lo referenciaba.** Búsqueda de `private/index.php` y `private/` en todo el proyecto
(excluyendo `.git/`, `.rsc/`, `.dsh/`, `.opencode/`): ninguna referencia en código, vistas ni
`.htaccess` de la raíz.

**4. Que los assets de `private/` no son los que se sirven.** Las vistas cargan `asset('css/maker.css')`
y `asset('js/app.js')`; el comentario del propio layout lo dice: `asset()` resuelve a
`public/assets/...`. Búsqueda de `private/assets` en todo el proyecto: **cero resultados**. Lo que hay
en `private/assets/` es una copia vieja: `maker.css` son 82 774 bytes ahí y 63 833 en `public/assets/`
(la copia antigua es la que además quedó guardada como `maker0.css`, 82 776 bytes).

**5. Que los dos `.htaccess` no son el mismo archivo** (por si alguien pensaba que `private/.htaccess`
era una copia de seguridad del de la raíz):

```
raiz    : 00FB4D9D9454CA98F96879EC0BBE9AFB61F2E5CCB5C333549A453A45E45DDEF6
private : FFF8CEDD9688FA164409FD396238A9E35D47F5076BAF93998510A271F8B64230
identicos: False
```

**6. Los tres puntos de entrada, que ahora son dos.** Repetido el sondeo del cambio anterior para
comprobar que una petición a `private/` ya no responde nada.

**7. `php -l` limpio** en el archivo movido: sigue siendo PHP válido con la cabecera nueva delante.

## Next

Cerrada la deuda 10. Quedan la 5 (el índice del wiki), la 7 (los hashes sembrados), la 11 (las
colaciones) y la 12 (los assets de `private/`). La 4 sigue como riesgo aceptado.
