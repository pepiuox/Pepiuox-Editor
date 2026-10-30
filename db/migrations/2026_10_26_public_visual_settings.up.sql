-- ═══════════════════════════════════════════════════════════════════
-- Semilla del grupo de configuración `public` (web pública).
--
-- No hay cambio de esquema: la tabla `settings` ya aporta
-- id / group / key / value / type / updated_at con UNIQUE(group,key),
-- que es exactamente lo que la personalización visual necesita.
--
-- Idempotente: `ON DUPLICATE KEY UPDATE id = id` no sobrescribe nada.
-- Si el administrador ya cambió un valor, relanzar esta migración no lo
-- pisa. Esa es la razón de no usar REPLACE ni UPDATE.
--
-- Los valores por defecto reproducen la paleta que el layout público ya
-- tenía escrita a mano (tema oscuro), así que aplicar la migración no
-- cambia ni un píxel del sitio: sólo hace explícita la configuración.
--
-- @package Db\Migrations
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO `settings` (`group`, `key`, `value`, `type`) VALUES
    ('public', 'theme',              'oscuro',  'string'),
    ('public', 'logo_alt',           '',        'string'),
    ('public', 'logo_header',        '',        'string'),
    ('public', 'logo_desktop',       '',        'string'),
    ('public', 'logo_tablet',        '',        'string'),
    ('public', 'logo_mobile',        '',        'string'),
    ('public', 'logo_menu',          '',        'string'),
    ('public', 'logo_multinivel',    '',        'string'),
    ('public', 'logo_footer',        '',        'string'),
    ('public', 'logo_sidebar',       '',        'string'),
    ('public', 'logo_dark',          '',        'string'),
    ('public', 'logo_light',         '',        'string'),
    ('public', 'favicon_ico',        '',        'string'),
    ('public', 'favicon_16',         '',        'string'),
    ('public', 'favicon_32',         '',        'string'),
    ('public', 'apple_touch_icon',   '',        'string'),
    ('public', 'color_primary',      '#00d4aa', 'string'),
    ('public', 'color_secondary',    '#8b949e', 'string'),
    ('public', 'color_accent',       '#00d4aa', 'string'),
    ('public', 'color_background',   '#0d1117', 'string'),
    ('public', 'color_section_bg',   '#161b22', 'string'),
    ('public', 'color_text',         '#e6edf3', 'string'),
    ('public', 'color_text_muted',   '#8b949e', 'string'),
    ('public', 'color_link',         '#00d4aa', 'string'),
    ('public', 'color_button',       '#00d4aa', 'string'),
    ('public', 'color_border',       '#30363d', 'string'),
    ('public', 'color_header_bg',    '#0d1117', 'string'),
    ('public', 'color_footer_bg',    '#161b22', 'string'),
    ('public', 'color_sidebar_bg',   '#161b22', 'string'),
    ('public', 'color_menu_bg',      '#0d1117', 'string'),
    ('public', 'color_multinivel_bg','#161b22', 'string'),
    ('public', 'color_menu_hover',   '#1f6f5f', 'string'),
    ('public', 'custom_css',         '',        'string'),
    ('public', 'custom_css_enabled', '0',       'bool')
ON DUPLICATE KEY UPDATE `id` = `id`;
