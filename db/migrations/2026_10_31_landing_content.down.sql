-- ═══════════════════════════════════════════════════════════════════
-- Reversión de 2026_10_31_landing_content.up.sql
--
-- Borra únicamente las claves del grupo `landing`. No toca ningún otro
-- ajuste: los grupos `general`, `seo`, `mail`, `cache` y `public` quedan
-- como estaban.
-- ═══════════════════════════════════════════════════════════════════

DELETE FROM `settings` WHERE `group` = 'landing';
