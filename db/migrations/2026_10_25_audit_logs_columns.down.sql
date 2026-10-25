-- ═══════════════════════════════════════════════════════════════════════
-- 2026_10_25_audit_logs_columns.down.sql
--
-- Revierte 2026_10_25_audit_logs_columns.up.sql.
--
-- Se pueden quitar `details` y `context` sin problema: solo contienen lo que
-- escribió la auditoría desde la migración.
--
-- `action` NO se devuelve al ENUM a propósito: un ENUM('INSERT','UPDATE',
-- 'DELETE','SELECT') no admite los valores que la aplicación guarda
-- ('page_updated', 'ip_blocked', …) y el ALTER fallaría o truncaría las filas.
-- Si de verdad se necesita el ENUM original, hay que vaciar antes esas filas
-- (`DELETE FROM audit_logs`) y hacerlo a mano, asumiendo la pérdida de datos.
-- ═══════════════════════════════════════════════════════════════════════

ALTER TABLE `audit_logs` DROP COLUMN `context`;
ALTER TABLE `audit_logs` DROP COLUMN `details`;
