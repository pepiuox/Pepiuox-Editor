-- ═══════════════════════════════════════════════════════════════════════
-- 2026_10_25_menu_system.down.sql
--
-- Revierte 2026_10_25_menu_system.up.sql.
--
-- Ojo: al volver a poner el UNIQUE global de `slug`, si una misma página está
-- en dos menús (slug repetido) el ALTER fallará. Es a propósito: revertir no
-- debe borrar filas en silencio. En ese caso hay que decidir qué ítems sobran
-- y borrarlos a mano antes de revertir.
-- ═══════════════════════════════════════════════════════════════════════

-- 1) Quitar la asignación por página.
ALTER TABLE `pages` DROP FOREIGN KEY `fk_pages_menu_override`;
ALTER TABLE `pages` DROP COLUMN `menu_id`;

-- 2) Quitar la jerarquía y el vínculo con el menú.
ALTER TABLE `page_menu` DROP FOREIGN KEY `fk_page_menu_parent`;
ALTER TABLE `page_menu` DROP FOREIGN KEY `fk_page_menu_menu`;
ALTER TABLE `page_menu` DROP INDEX `uniq_menu_page`;
ALTER TABLE `page_menu` DROP INDEX `uniq_menu_slug`;
ALTER TABLE `page_menu` ADD UNIQUE KEY `slug` (`slug`);
ALTER TABLE `page_menu` DROP COLUMN `menu_id`;

-- 3) Quitar las marcas del catálogo de menús.
ALTER TABLE `menus` DROP COLUMN `is_primary`;
ALTER TABLE `menus` DROP COLUMN `is_default`;
