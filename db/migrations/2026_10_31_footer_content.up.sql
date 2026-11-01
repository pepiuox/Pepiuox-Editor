-- ═══════════════════════════════════════════════════════════════════
-- Contenido editable del pie de página (grupo `footer`).
--
-- Qué hace: siembra las claves del pie para que se pueda editar desde
-- Configuración → Pie de página. Hasta ahora el pie tenía todo su texto escrito
-- a mano en `views/public/layout.php`, y se pintaba en todas las páginas.
--
-- Los enlaces por defecto apuntan a páginas que EXISTEN. Los anteriores
-- (`about`, `privacy`, `terms`) no estaban creados y llevaban a un 404.
--
-- Idempotente y no destructiva: `ON DUPLICATE KEY UPDATE id = id` no pisa lo que
-- el administrador ya haya escrito si la migración se relanza.
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO `settings` (`group`, `key`, `value`, `type`) VALUES

('footer', 'about', 'Editor y gestor de contenidos en PHP y MariaDB. Instalado en tu propio servidor, sin dependencias de terceros.', 'string'),
('footer', 'copyright', '© {year} {site}. Todos los derechos reservados.', 'string'),
('footer', 'show_contact', '1', 'bool'),
('footer', 'contact_title', 'Contacto', 'string'),
('footer', 'contact_email', 'info@pepiuox.com', 'string'),
('footer', 'contact_note', '', 'string'),

-- Dos columnas de enlaces. Misma forma que las listas de la portada: una clave
-- JSON con la lista completa, validada al leer y al guardar.
('footer', 'columns', '[{"title":"Navegación","links":[{"label":"Inicio","href":"/"},{"label":"Funciones","href":"/funciones"},{"label":"Cómo funciona","href":"/como-funciona"}]},{"title":"Plataforma","links":[{"label":"Seguridad","href":"/seguridad"},{"label":"Quiénes somos","href":"/quienes-somos"}]}]', 'json')

ON DUPLICATE KEY UPDATE `id` = `id`;
