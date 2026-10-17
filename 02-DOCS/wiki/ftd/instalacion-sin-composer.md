---
author: Pepiuox
branch: main
status: done
---
# Los requisitos de instalación sin Composer

## Intent

Es la deuda 6. `docs/INSTALL.md` declara Composer 2 como requisito, y el principio 3 dice que el
proyecto se instala sin Composer. Una de las dos cosas es falsa, y comprobarlo es el primer paso:
si el proyecto realmente lo necesitara, el que sobra es el principio.

## Scope

**Dentro:**

- Quitar la fila de Composer de la tabla de requisitos de `docs/INSTALL.md` si el proyecto no lo usa.

**Fuera, con motivo:**

- La fila de PHP de esa misma tabla (dice 8.1 mínimo / 8.2+ recomendado y el principio 1 fija 8.3+).
  Es la deuda 3 y es otra decisión.
- `docs/INSTALL.md` está cortado: su índice promete 11 secciones y solo existen 2, y no menciona el
  `.env`. Reescribirlo es trabajo de verdad y no cabe aquí. Anotado como deuda 9.
- `private/index.php` redirige a un `install.php` que nunca existió en `private/`. Anotado como
  deuda 10.

## Checklist

- [x] La fila de Composer desaparece de `docs/INSTALL.md`.
      Prueba: buscar `Composer` en `docs/` no devuelve nada.
- [x] El proyecto no lo usa por ningún lado.
      Prueba: no existen `composer.json`, `composer.lock` ni `vendor/`, y la búsqueda global no
      encuentra más menciones.
- [x] El cambio es solo esa fila.
      Prueba: `git diff --stat` de una línea borrada.

## Evidence

**1. El proyecto no usa Composer.** Comprobado antes de borrar nada, porque el orden importa: si el
proyecto lo usara, lo que sobraría es el principio 3.

```
composer.json : False
composer.lock : False
vendor        : False
```

Búsqueda de `vendor/autoload`, `Composer\` y `composer.json` en todo el código: **ninguna
coincidencia**. La aplicación se auto-carga con su propio `spl_autoload_register` PSR-4, repetido en
sus puntos de entrada. Es decir: el proyecto se instala sin Composer de verdad, y la fila de la tabla
era la que mentía.

**2. Menciones antes del cambio:** una, y solo una, en todo el proyecto:

```
docs/INSTALL.md  35  | **Composer** | 2.0 | 2.5+ |
```

**3. El cambio es exactamente esa fila:**

```
docs/INSTALL.md | 1 -
1 file changed, 1 deletion(-)
```

```
 | **Nginx** | 1.18 | 1.20+ |
-| **Composer** | 2.0 | 2.5+ |
```

**4. Menciones después:** cero, ni en `docs/` ni en el resto del proyecto.

## Next

Deuda 6 cerrada. La tabla de requisitos sigue declarando PHP 8.1 mínimo / 8.2+ recomendado y el
principio 1 fija 8.3+ (deuda 3), en la fila justo encima de la que se quitó.

Lo que esta comprobación destapó, y que vale más que el arreglo:

- `docs/INSTALL.md` tiene 41 líneas desde el commit inicial. Su índice promete 11 secciones, existen
  2, y el documento es anterior al `.env`, así que no explica cómo se configuran las credenciales.
  El mensaje de error de los puntos de entrada remite a él «para configurar la base de datos», y ahí
  no hay nada que leer. Deuda 9.
- `private/index.php` es un tercer punto de entrada que redirige a `install.php`, archivo que nunca
  existió bajo `private/` y que ya no existe en ningún sitio. Deuda 10.
