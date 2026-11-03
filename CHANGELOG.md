# Cambios · PePiuoX Editor

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
El número de versión vive en `config/config.php` (`app_version`) y lo estampa
`01-TOOLS/despliegue/build-backup.php` en el archivo `VERSION` de cada paquete.

---

## [1.1.0] · 2026-11-03

Primera publicación con registro de cambios. La 1.0.0 es el estado anterior.

### Añadido

- **Veinticinco temas nuevos** para la web pública, con las paletas de Bootswatch
  v5 (licencia MIT): 36 en total, contando los 11 que ya había. De cada paleta se
  toman solo los valores HEX; no se copia su CSS ni su código. Los colores que no
  llegaban al 4,5:1 que exige el proyecto se han ajustado en luminosidad, 33
  correcciones documentadas. En tres casos hubo que corregir el origen: Quartz
  tiene un fondo tan claro que ni el blanco da contraste suficiente.
- **Proveedores de medición externa**, en `/admin/analytics/proveedores`: Google
  Analytics 4, Google Tag Manager, Yandex Metrica, Microsoft Clarity, Plausible,
  Umami y las verificaciones de Google Search Console y Bing. Se configuran con
  un **identificador**, nunca pegando código: cada valor se valida contra un
  patrón y lo que no encaja se descarta al guardar en lugar de publicarse.
- **Aviso de cookies** que bloquea la medición de verdad. Los proveedores que
  instalan cookies llegan al navegador como `type="text/plain"` y no se ejecutan
  hasta que el visitante acepta. Aparece solo cuando hay algún proveedor con
  cookies configurado; Plausible y Umami no usan cookies y no lo disparan. El
  enlace a la política de cookies se configura en la misma pantalla.
- **Generador del paquete de producción** (`01-TOOLS/despliegue/build-backup.php`):
  construye `backup/` de una pasada y **verifica lo que produce** importándolo en
  una base aislada. Con `--desde-base=<base>` se lleva el sitio ya montado, con
  `--destino=` cambia la carpeta, y con `--dry-run` no escribe nada.
- **Versionado de las publicaciones**: cada compilación escribe un archivo
  `VERSION` con la versión, la fecha, el commit y lo que viaja dentro, y lo
  repite en la cabecera del `INSTALAR.md`.
- Licencias **LULA** y **LAMC** incorporadas al proyecto.

### Cambiado

- `db/install/initial_database.sql` regenerado: **53 tablas** (faltaba
  `footer_designs`), sin cláusulas `DEFINER` y sin `CREATE DATABASE` ni `USE`.
- Los campos de analítica salen de la página de SEO y pasan a su propia pantalla;
  en SEO queda un enlace, para que los mismos datos no tengan dos dueños.
- `.gitignore` excluye `backup/` y `nbproject/`.

### Corregido

- **Activar 2FA fallaba siempre.** El controlador abría una transacción y el
  servicio abría otra encima; PDO no las anida y respondía «There is already an
  active transaction». La activación se revertía entera.
- **Descargar los códigos de recuperación no descargaba**: la página los borraba
  de la sesión al pintarse y el enlace devolvía un 302.
- **Copiar los códigos no copiaba**, y el aviso decía que sí: se llamaba a
  `navigator.clipboard` sin comprobar el contexto seguro, sin `catch` y sin plan B.
- **`Class "CSRF" not found`** en ocho vistas: 14 llamadas sin cualificar al
  espacio de nombres global.
- **Los enlaces de las notificaciones no navegaban.** La URL se guardaba entera en
  la base, así que una notificación creada en desarrollo apuntaba a `localhost`
  para siempre.
- **La importación del SQL se cortaba a la mitad**: de 53 tablas se creaban 35 y
  ninguna vista, disparador ni rutina, por `DEFINER=root@localhost` sin
  privilegios de administrador. También se corrigieron los disparadores envueltos
  en un comentario de versión con otro comentario dentro.

### Eliminado

- `views/private/layout.php`: layout duplicado que nadie renderizaba y donde vivía
  la implementación vieja de Google Analytics. Por eso GA nunca se emitió.

### Seguridad

- La pantalla de proveedores exige el permiso `settings.manage`: integrar un
  proveedor es configuración del sitio, no consulta de métricas.
- Los identificadores de medición y el enlace de la política se validan antes de
  publicarse y se escapan al imprimirse. Probado con un intento de inyección que
  se descarta al guardar.
- La contraseña del superadministrador se genera nueva en cada publicación.

---

## Notas de la publicación 1.1.0

| | |
|---|---|
| Versión | 1.1.0 |
| Base de datos | 53 tablas · 4 páginas · 2 menús |
| Motor | MariaDB 11 o superior |
| Commits | `4850961` y anteriores de la misma tanda |

**Limitaciones conocidas**

- El copiado al portapapeles de los códigos 2FA no se ha probado en un navegador
  real: en el entorno de desarrollo no hay ninguno. Lo que sí está corregido es
  que el fallo ahora se ve en vez de quedarse callado.
- El aviso de cookies **no sustituye** a una revisión legal: cubre el
  consentimiento previo de los proveedores con cookies, pero el texto de la
  política y su contenido son responsabilidad del titular.
