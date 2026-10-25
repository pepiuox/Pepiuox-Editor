-- ═══════════════════════════════════════════════════════════════════════
-- 2026_10_25_menu_system.up.sql
--
-- Conecta los menús (`menus`) con sus ítems (`page_menu`) y añade lo mínimo
-- para el menú predeterminado automático y los menús personalizados.
--
-- Antes: `menus` era un registro de menús que nadie consultaba y `page_menu`
-- una lista de ítems sin vínculo con él (agrupados por la cadena menu_group).
-- `page_menu.slug` era UNIQUE global, así que una página no podía estar en dos
-- menús, y no había forma de asignar un menú a una página concreta.
--
-- Todo es aditivo salvo el índice único global de `slug`, que se sustituye por
-- uno por menú. No se borra ninguna fila.
--
-- Aplicar: php db/migrations/apply.php --admin 2026_10_25_menu_system.up.sql
-- Reversión: 2026_10_25_menu_system.down.sql
-- ═══════════════════════════════════════════════════════════════════════

-- 1) Marcas en el catálogo de menús.
--    is_default: el menú automático de una ubicación (se sincroniza con las
--                páginas publicadas).
--    is_primary: menú personalizado que sustituye al predeterminado en su
--                ubicación.
ALTER TABLE `menus` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `menus` ADD COLUMN `is_primary` TINYINT(1) NOT NULL DEFAULT 0;

-- 2) Vínculo ítem → menú. Se añade anulable para poder rellenarlo.
ALTER TABLE `page_menu` ADD COLUMN `menu_id` INT NULL AFTER `id`;

-- 3) Relleno: todo lo existente pertenece al menú 'main' (el que ya pintaba el
--    sitio), y ese menú pasa a ser el predeterminado.
UPDATE `page_menu` SET `menu_id` = (SELECT `id` FROM `menus` WHERE `slug` = 'main' LIMIT 1);
UPDATE `menus` SET `is_default` = 1 WHERE `slug` = 'main';

-- 4) Ya se puede exigir.
ALTER TABLE `page_menu` MODIFY COLUMN `menu_id` INT NOT NULL;

-- 5) Una página puede repetirse en menús distintos, pero no dentro del mismo.
ALTER TABLE `page_menu` DROP INDEX `slug`;
ALTER TABLE `page_menu` ADD UNIQUE KEY `uniq_menu_slug` (`menu_id`, `slug`);
ALTER TABLE `page_menu` ADD UNIQUE KEY `uniq_menu_page` (`menu_id`, `page_id`);

-- 6) Integridad referencial: borrar un menú borra sus ítems; borrar un ítem
--    padre borra su rama.
ALTER TABLE `page_menu`
    ADD CONSTRAINT `fk_page_menu_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;
ALTER TABLE `page_menu`
    ADD CONSTRAINT `fk_page_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `page_menu` (`id`) ON DELETE CASCADE;

-- 7) Asignación de un menú a una página concreta (prioridad máxima al elegir
--    qué menú mostrar).
ALTER TABLE `pages` ADD COLUMN `menu_id` INT NULL AFTER `template_id`;
ALTER TABLE `pages`
    ADD CONSTRAINT `fk_pages_menu_override` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL;
