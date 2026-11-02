---
author: Pepiuox
branch: main
status: done
---
# 2FA: activar, descargar y copiar

## Intent

Tres fallos seguidos en el mismo módulo, los tres reportados desde el panel y los tres con la misma
forma: la interfaz dice que hace algo y el backend no lo hace.

1. **Activar 2FA fallaba siempre** con «Error al activar 2FA: There is already an active transaction».
2. **El botón de descargar los códigos no descargaba nada**: devolvía un 302.
3. **Copiar los códigos no copiaba**, y encima decía que sí.

Los tres se reproducen, se corrigen y se verifican por HTTP real con el servidor levantado, no leyendo
el código.

## Scope

**Dentro:**

- La transacción anidada de `TwoFactorController::verify()` y `TwoFactorRecoveryService::generateForUser()`.
- El ciclo de vida de los códigos recién generados entre la página que los muestra y la descarga.
- El copiado al portapapeles: permiso, contexto seguro, plan B y aviso honesto.

**Fuera, y se dice:**

- El aviso `[Permisos] Acción de panel sin permiso declarado: TwoFactorController::recoverySuccess`
  que aparece en el log. Es anterior a este cambio y afecta al catálogo de permisos, no a los tres
  fallos. Queda pendiente como deuda aparte.
- El copiado no se ha probado en un navegador real: no hay navegador en este entorno. Se ha
  verificado que el JavaScript que llega al cliente es válido (`node --check`) y que el fallo ahora
  **se ve** en vez de quedarse callado.

## Checklist

- [x] Activar 2FA funciona con la transacción abierta del controlador.
      Prueba: por HTTP, `POST /admin/2fa/verify` con un TOTP válido llega a `/admin/2fa/recovery-success`,
      deja `two_factor_enabled = 1` y persiste 10 códigos. Antes: `PDOException: There is already an
      active transaction`, 0 códigos y el flag a 0.
- [x] El servicio no cierra una transacción que no abrió.
      Prueba: con un fallo forzado dentro de la transacción del llamador, la excepción se propaga, la
      transacción del llamador **sigue abierta** y su `rollback` revierte el trabajo.
- [x] Registrar los códigos sin transacción del llamador sigue funcionando y no deja transacción
      colgada. Prueba: devuelve `[]` y `inTransaction()` queda en `false`.
- [x] La semántica de `Database` a un solo nivel no cambia.
      Prueba: 11 comprobaciones de `begin` / `commit` / `rollback` / `rollbackIfActive`, y los dos
      contratos de fallo (`begin` sobre otra transacción y `rollback` sin ninguna) siguen lanzando.
- [x] El enlace de descarga entrega el archivo.
      Prueba: `GET /admin/2fa/download-codes` responde **200** con
      `Content-Disposition: attachment; filename="codigos-recuperacion-2fa.txt"`,
      `Content-Type: text/plain` y los 10 códigos, idénticos a los de la pantalla. Antes: 302.
- [x] Los códigos caducan en la sesión en lugar de quedarse indefinidamente.
      Prueba: 10 minutos (`MINUTOS_CODIGOS`); la descarga los borra al entregar el archivo.
- [x] El copiado usa el portapapeles moderno con plan B y avisa de lo que pasa de verdad.
      Prueba: `copiarTexto()` cae a `document.execCommand` cuando `navigator.clipboard` no existe o
      rechaza, comprueba el resultado y, si tampoco puede, lo dice y selecciona la lista.
- [x] El aviso usa las clases que existen en `maker.css` (`.toast success`, con espacio).
      Prueba: el HTML servido contiene `'toast ' + (tipo`; antes construía `toast-success`, que no
      está definida en la hoja del panel.

## Lo que se aprendió

Un servicio no puede abrir una transacción a ciegas. Este ya había mordido antes en `MenuService`,
que se arregló con `rollbackIfActive()`. La regla que queda escrita en `Database`: quien abre una
transacción la cierra, y `inTransaction()` existe para que un servicio pueda sumarse a la del
llamador en vez de pisarla.
