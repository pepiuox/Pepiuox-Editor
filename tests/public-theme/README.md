# Pruebas de la personalización visual de la web pública

Andamiaje de verificación del módulo de identidad visual. **No forma parte de la
aplicación** y no se sirve por HTTP: vive en `tests/`, fuera del document root.

## 1. Prueba de servicio (CLI, sin servidor web)

Comprueba la lógica pura contra la base de datos real: valores por defecto de la
migración, generación de las 16 variables CSS, ámbito `body.public-site`,
validación de colores HEX, validación del CSS personalizado, saneado de SVG,
reglas del validador de subidas, persistencia y restablecimiento, favicon,
manifiesto y los helpers.

```bash
php tests/public-theme/service-test.php
```

Salida esperada: `Resultado: 97 pasan, 0 fallan` y código de salida 0.
Escribe en la tabla `settings` (grupo `public`) y deja el tema en `oscuro` al
terminar, que es el valor por defecto.

## 2. Prueba de extremo a extremo (HTTP)

Verifica lo que sólo se puede comprobar por HTTP: subida real con sesión
autenticada, rechazo de un PNG falsificado, saneado del SVG en disco, emisión de
favicons y manifiesto, `<picture>` con el logo, ETag del CSS, CSRF, permisos por
rol y borrado de archivos.

Necesita tres cosas:

1. Un servidor con el mismo `session.save_path` que usa `support.php`:

   ```powershell
   php -S 127.0.0.1:8899 -t public `
       -d session.save_path="tests/public-theme/.runtime/sessions" `
       public/_ppf_router.php
   ```

   > `public/_ppf_router.php` es un router de pruebas que reproduce lo que hace
   > el `.htaccess` de Apache (estáticos servidos, resto a `index.php`). No se
   > despliega; si no existe, créalo con dos líneas: `return false` si el
   > archivo existe en `public/`, y `require __DIR__ . '/index.php'` si no.

2. La base de datos accesible con `01-TOOLS/mysql/.env`.

3. Windows PowerShell con permiso de ejecución para el proceso:

   ```powershell
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   ./tests/public-theme/e2e.ps1
   ```

Salida esperada: `Resultado E2E: 65 pasan, 0 fallan`.

La prueba **crea y luego borra** sus propios archivos en
`uploads/settings/logos` y `uploads/settings/favicons`, y deja las claves del
grupo `public` vacías o en su valor por defecto. Si se interrumpe a medias,
basta con repetirla o vaciar las claves con
`php tests/public-theme/support.php set public logo_header ""`.

> ⚠️ **Ejecútala en una copia de la base de datos, no sobre la configuración
> real.** Usa los huecos `logo_header`, `logo_tablet`, `logo_menu`,
> `logo_multinivel` y `favicon_32`, y al terminar los deja vacíos: si ya tenías
> imágenes ahí, las pierde. El bloque N también escribe y restaura el nombre del
> sitio y la meta descripción de SEO.

## Cobertura de los criterios de aceptación

| Criterio del encargo | Dónde se comprueba |
| --- | --- |
| Nombre, descripción, idioma y zona horaria desde Configuración | `service-test.php` bloque 12 · `e2e.ps1` bloque N |
| Huecos de logo con destino real y huecos ocultos | `service-test.php` bloque 9.b · `e2e.ps1` bloque O |
| Carga y actualización de logotipos | `e2e.ps1` bloques A, C, I |
| Imágenes distintas por dispositivo | `e2e.ps1` bloque E (`<picture>`) |
| Actualización del favicon | `e2e.ps1` bloque D |
| Persistencia de temas y colores | `service-test.php` bloque 8 · `e2e.ps1` bloque J |
| Aplicación del CSS personalizado | `service-test.php` bloques 5 y 2 |
| Vistas públicas correctas | `e2e.ps1` bloques D, E, F |
| El panel no cambia | `service-test.php` bloque 3 (todo el CSS bajo `body.public-site`) + `e2e.ps1` bloque M + revisión de `views/layouts/admin.php` |
| Operaciones de carga seguras | `service-test.php` bloques 6 y 7 · `e2e.ps1` bloques B, H |
