-- ═══════════════════════════════════════════════════════════════════
-- Reversión del catálogo de diseños del pie.
--
-- Qué hace: elimina `footer_designs`.
--
-- ATENCIÓN: es destructiva por naturaleza. Al revertir se pierden TODOS los
-- diseños guardados con el constructor. Antes de aplicarla, guarda un respaldo
-- (`mysqldump --skip-ssl ... web_editor_db > db/backup/...`).
--
-- El pie por campos (grupo `footer` de `settings`) no se toca: sigue intacto y
-- es lo que se pintará cuando no haya ningún diseño.
-- ═══════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `footer_designs`;
