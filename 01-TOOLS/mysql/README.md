# mysql — la conexión a la base de datos

Qué es: la carpeta del proveedor MySQL. Aquí viven las credenciales, no en el código.

## Quién lo lee

`config/database.php` llama a `config/env.php`, que lee `01-TOOLS/mysql/.env` y deja sus claves
disponibles para la aplicación. El código nunca contiene la contraseña.

## Precedencia

1. Variables de entorno del proceso (Apache `SetEnv`, systemd, Docker, `export`). Ganan.
2. Este `.env`. Solo para desarrollo local.

Por eso el mismo código sirve en local y en producción sin editarlo.

## Archivos

| Archivo | Se confirma en git | Para qué |
|---|---|---|
| `.env` | No | Tus credenciales reales de esta máquina. |
| `.env.example` | Sí | La plantilla, sin secretos. |
| `CREDENTIALS.md` | Sí | Cómo obtenerlas y cómo rotarlas. |
| `test_connection.php` | Sí | Prueba que la conexión funciona de verdad. |

## Probar la conexión

```
php 01-TOOLS/mysql/test_connection.php
```

Responde en una línea: si conecta, cuántas tablas ve. Si no, el motivo.
