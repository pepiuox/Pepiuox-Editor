-- ═══════════════════════════════════════════════════════════════════════
-- 2026_10_25_audit_logs_columns.up.sql
--
-- Alinea la tabla `audit_logs` con el código que la usa.
--
-- Motivo: `App\Services\AuditService` escribe y lee
-- (user_id, action, details, context, ip_address, user_agent, created_at) y la
-- interfaz de auditoría (views/audit/index.php, AuditController) muestra y
-- filtra por `details` y `context`. La tabla real no tenía esas dos columnas y
-- `action` era un ENUM('INSERT','UPDATE','DELETE','SELECT'), así que:
--   · el INSERT de AuditService::logInstance() fallaba siempre (columna
--     inexistente) y la auditoría no guardaba nada;
--   · el filtro por contexto y la búsqueda por detalles devolvían 0 filas.
--
-- Solo es aditivo: se añaden dos columnas anulables y se amplía `action` a
-- VARCHAR conservando los valores existentes. Las columnas de auditoría de
-- cambios de fila (table_name, record_id, old_data, new_data) se quedan como
-- están: no se tocan ni se borran.
--
-- Aplicar: ejecutar cada bloque en orden (o usar
-- `php 01-TOOLS/diagnostics/migrate-audit-logs.php`, que comprueba antes cada
-- paso y es idempotente).
-- Reversión: 2026_10_25_audit_logs_columns.down.sql
-- ═══════════════════════════════════════════════════════════════════════

-- 1) `action` deja de ser ENUM: la auditoría registra acciones con nombre
--    ('page_updated', 'login_failed', …), no operaciones de fila.
ALTER TABLE `audit_logs`
    MODIFY COLUMN `action` VARCHAR(100) NULL;

-- 2) Detalle legible de la acción (lo que la vista llama "Detalles").
ALTER TABLE `audit_logs`
    ADD COLUMN `details` TEXT NULL AFTER `action`;

-- 3) Contexto/módulo de la acción ('pages', 'security', 'backups', …).
ALTER TABLE `audit_logs`
    ADD COLUMN `context` VARCHAR(50) NULL AFTER `details`;
