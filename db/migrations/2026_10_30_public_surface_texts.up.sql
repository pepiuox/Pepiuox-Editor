-- ═══════════════════════════════════════════════════════════════════
-- Colores de texto sobre cada superficie (grupo `public`).
--
-- Hasta ahora el color del texto de la cabecera, del menú, de los
-- desplegables, del pie y de los botones lo decidía el layout base, y el
-- fondo lo ponía el tema. Resultado: un tema con fondo oscuro dejaba el
-- menú ilegible y un botón azul con texto oscuro.
--
-- Estas siete claves lo hacen configurable. **Valor vacío = automático**:
-- PublicThemeService calcula el color que cumple AA (4.5:1) sobre el fondo
-- de esa superficie. Guardar un HEX manda sobre el cálculo.
--
-- Idempotente y no destructiva: `ON DUPLICATE KEY UPDATE id = id` no pisa
-- nada de lo que el administrador haya elegido.
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO `settings` (`group`, `key`, `value`, `type`) VALUES
    ('public', 'color_header_text',     '', 'string'),
    ('public', 'color_menu_text',       '', 'string'),
    ('public', 'color_menu_hover_text', '', 'string'),
    ('public', 'color_multinivel_text', '', 'string'),
    ('public', 'color_footer_text',     '', 'string'),
    ('public', 'color_sidebar_text',    '', 'string'),
    ('public', 'color_button_text',     '', 'string')
ON DUPLICATE KEY UPDATE `id` = `id`;
