-- ═══════════════════════════════════════════════════════════════════
-- Reversión de 2026_10_31_footer_content.up.sql
--
-- Borra únicamente las claves del grupo `footer`. Los demás grupos quedan como
-- estaban. Al revertir, el pie vuelve a los valores por defecto del código
-- (`FooterContentService::defaults()`), no a un pie vacío.
-- ═══════════════════════════════════════════════════════════════════

DELETE FROM `settings` WHERE `group` = 'footer';
