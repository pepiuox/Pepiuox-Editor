---
author: Pepiuox
branch: main
status: in-progress
---
# Auditoría: de tabla huérfana a registro que funciona

> Era el pendiente con más riesgo: `AuditService` no podía escribir y **43 llamadas** de 20 archivos
> habrían reventado con un 500 en cuanto se ejecutaran. Ahora escribe, se lee y la interfaz de
> auditoría filtra por contexto y detalles.

## Lo que estaba mal (dos defectos independientes)

### 1. La tabla no tenía las columnas que el código usa

`AuditService::logInstance()` insertaba `(user_id, action, details, context, ip_address, user_agent, created_at)`
y la interfaz (`views/audit/index.php`, `AuditController`) muestra y filtra por `details` y `context`. La
tabla real tenía otra forma:

```
ANTES                                          DESPUÉS
id, table_name, record_id,                     id, table_name, record_id,
action ENUM('INSERT','UPDATE','DELETE','SELECT'),  action VARCHAR(100),
old_data, new_data, user_id,                   details TEXT, context VARCHAR(50),
ip_address, user_agent, created_at             old_data, new_data, user_id, ip_address, user_agent, created_at
```

Con ese esquema: el `INSERT` fallaba siempre (`Unknown column 'details'`) y el `try/catch` del servicio
lo tragaba devolviendo `false`; el filtro por contexto y la búsqueda por detalles devolvían 0 filas.
`audit_logs` solo tenía 3 filas antiguas.

### 2. 43 llamadas pasaban la acción donde va el usuario

Firma real: `log(?int $userId, string $action, ?string $details = null, ?string $context = null, ?int $recordId = null)`.

```php
// Incorrecto (43 sitios en 20 archivos)
AuditService::log('role_created', "Rol creado: X", 'roles', $id);
```

PHP no convierte `'role_created'` a `?int`: lanza `TypeError` **antes** de entrar. En los flujos que ya
habían guardado el dato, el usuario veía un 500 después del commit (el caso del builder, corregido en
`28d0569`). El resto de llamadas estaban esperando su turno.

## Qué se hizo

1. **Migración aditiva** (`db/migrations/2026_10_25_audit_logs_columns.up.sql`): `action` pasa a
   `VARCHAR(100)` y se añaden `details TEXT` y `context VARCHAR(50)`. Nada se borra; las columnas de
   auditoría de cambios de fila (`table_name`, `record_id`, `old_data`, `new_data`) se quedan.
   Reversión en `...down.sql` (las dos columnas se quitan; `action` **no** vuelve al ENUM a propósito,
   porque un ENUM no admite los valores que la aplicación guarda).
2. **Aplicador de migraciones** (`db/migrations/apply.php`): el proyecto no tenía. Ejecuta un `.sql`
   sentencia a sentencia, tolera «duplicate column» para poder relanzarlo y **exige `--admin`** para
   tocar esquema: el usuario de la aplicación (`web_editor_app`) no tiene permiso `ALTER` y así sigue.
   El `ALTER` con la cuenta de la app falla con `1142 ALTER command denied`, que es justo lo que debe
   pasar.
3. **`recordId` opcional** en `AuditService::log()` / `logInstance()`: las llamadas que ya pasaban el id
   del registro como cuarto argumento ahora lo guardan en `record_id` en lugar de perderlo.
4. **43 llamadas corregidas** en 20 archivos (16 controladores + 4 servicios), insertando el usuario
   como primer argumento. `Auth::id()` en la web; `null` sigue valiendo para acciones anónimas.
5. Import de `Auth` añadido en los tres archivos que no lo tenían (`SettingsController`,
   `VersionsController`, `TranslationsController`).

## Verificación

Escritura y lectura reales contra la base (fila de prueba borrada al final):

```
log() -> true
fila guardada: {"user_id":1,"action":"zz_audit_probe","details":"Prueba de auditoria (ID 42)",
                "context":"pages","record_id":42,"ip_address":"127.0.0.1","user_agent":"CLI"}
getLogs(filtros) -> 1 fila(s)          ← antes 0: el filtro usaba columnas inexistentes
getTotalCount(filtros) -> 1
getStatistics -> {"total":6,"today":3,"this_week":3,"unique_users":1}
getDistinctActions incluye la accion -> si
getActiveUsers -> 1 usuario(s)
find(id) -> encontrado
getLogsByUser(1) -> 3 · getLogsByIp(127.0.0.1) -> 5
limpieza: 1 fila(s) de prueba borradas
```

Interfaz:

```
GET /admin/audit                          → 48 954 B, layout admin, tabla con «Contexto», sin error
GET /admin/audit?context=pages&action=page → 48 954 B, sin error   ← el filtro por contexto fallaba antes
```

Prueba de fuego: la tabla tenía 3 filas al empezar y tiene **5** ahora, con acciones reales
(`page_updated`) generadas por los guardados que se hicieron durante la sesión de trabajo.

Estático: `php -l` en los 21 `.php` de `app/` sin errores; **0** llamadas con la acción como primer
argumento (107 llamadas totales, 67 con usuario explícito, el resto `$this->auditService->log(...)` de la
instancia del controlador, que ya usaba la firma correcta); 0 archivos PHP con BOM.

## Lo que se rompió por el camino (y cómo se detectó)

Al aplicar la corrección masiva usé `Set-Content -Encoding UTF8` de PowerShell 5.1, que escribe **BOM**.
Un BOM antes de `<?php` hace que PHP emita esos tres bytes como HTML y el `namespace` deje de ser la
primera sentencia: 13 archivos quedaron inválidos. Lo detectó el `php -l` en bucle, se restauraron desde
git y se rehizo la transformación escribiendo UTF-8 sin BOM
(`[System.IO.File]::WriteAllText($p, $s, (New-Object System.Text.UTF8Encoding($false)))`).
También apareció un BOM en `views/templates/edit.php` del commit anterior: ya no está.

## Lessons (autoaprendizaje)

1. **Un `catch` que devuelve `false`/`[]` convierte un fallo de esquema en «no hay datos».** Dos veces en
   este proyecto (`page_autosave.grapesjs_data`, `template_versions.created_by`, ahora `audit_logs`).
   Antes de creer que un registro no se guarda, mirar el esquema real con `SHOW COLUMNS`.
2. **Un `TypeError` de firma ocurre antes del cuerpo**: si el registro ya se escribió, el usuario ve un
   error de algo que sí pasó. Vale la pena comprobar si la fila cambió antes de tocar la persistencia.
3. **`Set-Content -Encoding UTF8` en PowerShell 5.1 añade BOM.** Para PHP, siempre
   `[System.IO.File]::WriteAllText(..., UTF8Encoding($false))`. Y `php -l` en bucle tras cualquier
   transformación masiva.
4. **Los cambios de esquema van con credenciales de administración y un aplicador explícito**, nunca con
   el usuario de la aplicación: que `ALTER` falle con `web_editor_app` es la señal de que el mínimo
   privilegio está bien puesto.
5. **Corregir la clase, no el síntoma**: había 43 sitios, no 3. Buscar el patrón completo
   (`AuditService::log(\s*['"]`) evita volver a verlo más tarde.

## Next

- Nada pendiente aquí. Las acciones ya se registran; la interfaz de auditoría filtra y exporta.
- Si se quiere auditoría de cambios de fila (old_data/new_data), hay que escribirla explícitamente: hoy
  esas columnas quedan nulas.
