-- ═══════════════════════════════════════════════════════════════════
-- Catálogo de diseños del pie de página.
--
-- Qué hace: crea `footer_designs`, donde vive cada diseño compuesto con el
-- constructor visual. Antes solo se podía guardar uno (claves `builder_*` del
-- grupo `footer` de `settings`); con esta tabla se guardan varios, cada uno con
-- su nombre, y se elige cuál está activo.
--
-- Por qué una tabla y no más claves en `settings`:
--   - `settings` está pensada para pares clave/valor, no para N registros con
--     nombre, autor y fechas.
--   - Listar «los diseños» con claves exigiría recorrer y parsear el grupo
--     entero en cada lectura.
--   - El contenido de una página ya vive así (`pages.content_html` longtext):
--     un diseño de pie es lo mismo, un documento.
--
-- Solo un diseño activo a la vez: se garantiza en la aplicación con una
-- transacción, no con un índice único sobre un booleano (MySQL no lo permite de
-- forma portátil).
--
-- Idempotente: `CREATE TABLE IF NOT EXISTS`. Reversible: ver el .down.sql.
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `footer_designs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `grapesjs_data` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_footer_designs_name` (`name`),
  KEY `idx_footer_designs_active` (`is_active`),
  KEY `idx_footer_designs_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
