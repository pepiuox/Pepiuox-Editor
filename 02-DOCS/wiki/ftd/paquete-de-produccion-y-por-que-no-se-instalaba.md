---
author: Pepiuox
branch: main
status: done
---
# Paquete de producción y por qué no se instalaba

## Intent

Subir el sitio a un servidor limpio. El paquete tenía que salir de una orden, no de copiar carpetas a
mano; llevar solo lo que hace falta en producción; y **traer el sitio ya montado**, no una instalación
en blanco: Lab eMotion, sus cuatro páginas y sus menús.

De paso salió el motivo real por el que la instalación no funcionaba en un servidor: el volcado se
cortaba a la mitad.

## Scope

**Dentro:**

- `01-TOOLS/despliegue/build-backup.php`: genera el paquete en `backup/` y **verifica lo que produce**
  importándolo en una base aislada.
- Opción `--desde-base=<base>`: construye el SQL a partir de una base existente, con su contenido.
- Los tres defectos del volcado que rompían la importación (abajo).
- `INSTALAR.md` del repositorio, reescrito; el paquete lleva el suyo con los datos concretos.
- El `.env` del paquete: dominio, `APP_DEBUG=false`, `SESSION_SECURE=true` y `CRON_TOKEN` puestos.

**Fuera, y se dice:**

- Los 14 ajustes `landing.*` no están en la base de origen. No es un fallo:
  `LandingContentService` completa sus valores con `defaults()` y la portada comercial queda apagada.

**Corregido después, en el mismo día:** `db/install/initial_database.sql` del repositorio también se
regeneró con la misma limpieza. Tenía los tres defectos y 52 tablas; ahora tiene 53, no lleva
`DEFINER` ni `USE`, y se importa con una cuenta sin privilegios. El paquete no lo usa —genera el
suyo— pero quien instale desde el repositorio ya no choca con la pared.

## Los tres defectos del volcado

1. **`CREATE DATABASE` y `USE` dentro del archivo.** Importado en una base nueva, el `USE` manda: los
   datos se van a `web_editor_db` y la base recién creada se queda vacía.
2. **`DEFINER=root@localhost` en disparadores, vistas y rutinas.** Con una cuenta de hosting da
   `ERROR 1227: Access denied; you need (at least one of) the SET USER privilege(s)` y la importación
   se detiene. Medido: de 53 tablas se creaban 35, y ninguna vista, disparador ni rutina; las 18
   tablas que faltaban son las que van detrás del primer disparador.
3. **Disparadores envueltos en un comentario de versión con otro comentario dentro.** Si el cuerpo
   lleva un `/* … */`, el cliente cierra el comentario antes de tiempo y falla con `ERROR 1064`.

## Checklist

- [x] El paquete se genera con una orden y no arrastra nada de desarrollo.
      Prueba: 274 archivos, 3,65 MB. Sin `tests/`, `02-DOCS/`, `db/backup/`, volcados con datos,
      `uploads/` de ejemplo ni el `.env` de desarrollo. 207 archivos PHP, 0 errores de sintaxis.
- [x] El SQL se importa con una cuenta **sin** privilegios de administrador.
      Prueba: usuario con solo `GRANT ALL` sobre su base, sin `SUPER` ni `SET USER` → código 0,
      53 tablas, 2 vistas, 4 disparadores, 5 rutinas.
- [x] Los objetos recién creados funcionan, no solo existen.
      Prueba: se consultan las dos vistas, se ejecuta `get_active_users_count()` y un `INSERT` de
      usuario deja su fila en `activity_log` a través del disparador.
- [x] El paquete arranca el sitio con su contenido.
      Prueba: importado en base limpia, la aplicación del paquete sirve `GET /` con 46.261 bytes,
      la portada de Lab eMotion, 4 páginas, 2 menús y 15 plantillas.
- [x] La contraseña que anuncia el `INSTALAR.md` valida contra el hash del `.sql`.
      Prueba: `password_verify()`, y el hash se sustituye en la **copia**, no en la base de origen.
- [x] Nada de historial operativo viaja al servidor.
      Prueba: las 30 tablas de registro se vacían en el volcado; importado, todas a 0.
- [x] El paquete supera su propio comprobador.
      Prueba: `php 01-TOOLS/despliegue/verificar.php` en verde en entorno, permisos, `.gitignore`,
      URL, debug, cookie segura y `CRON_TOKEN`. Quedan en rojo, a propósito, la conexión a la base
      (faltan credenciales) y el endpoint de cron (el dominio aún no existe).

## Lo que se aprendió

Un volcado de `mysqldump` **no es portable de serie**. Tres cosas lo atan a la máquina donde se
generó: el `USE` incrustado, el `DEFINER` y la envoltura de versión de los disparadores. Las tres se
resuelven post-procesando el archivo, y las tres hay que comprobarlas antes de dar un paquete por
bueno: importarlo con una cuenta restringida es la única prueba que vale.
