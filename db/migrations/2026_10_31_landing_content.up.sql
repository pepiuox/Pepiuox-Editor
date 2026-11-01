-- ═══════════════════════════════════════════════════════════════════
-- Contenido editable de la portada comercial (grupo `landing`).
--
-- Qué hace: siembra las claves de texto, las tarjetas de funciones, las
-- especificaciones, los beneficios y el pie de la portada pública. Todo se
-- edita después desde Configuración → Portada, sin tocar código.
--
-- Qué NO hace: no activa la portada. `landing.enabled` queda en 0 a
-- propósito (ADR-006): cambiar la página de inicio de un sitio publicado es
-- una decisión del dueño, no un efecto de una migración.
--
-- Idempotente y no destructiva: `ON DUPLICATE KEY UPDATE id = id` no pisa lo
-- que el administrador ya haya escrito si la migración se relanza.
--
-- Sobre las imágenes: `hero_image` y `gallery` se siembran vacíos. No se
-- inventan rutas a capturas que no existen; se suben desde la pestaña
-- Portada y, mientras estén vacías, la vista muestra su estado vacío.
-- ═══════════════════════════════════════════════════════════════════

INSERT INTO `settings` (`group`, `key`, `value`, `type`) VALUES

('landing', 'enabled', '0', 'bool'),

-- ── Cabecera y hero ────────────────────────────────────────────────
('landing', 'hero_kicker', 'Editor y CMS web en PHP 8 y MariaDB', 'string'),
('landing', 'hero_title', 'Publica, versiona y controla tu web desde un solo panel', 'string'),
('landing', 'hero_subtitle', 'Contenido, diseño y seguridad en la misma herramienta, sin dependencias de terceros.', 'string'),
('landing', 'hero_description', 'PePiuoX Editor reúne constructor visual, control de versiones, plantillas, menús y personalización visual completa en una plataforma que se instala en tu propio servidor. Sin licencias por usuario y sin ataduras a un servicio externo.', 'string'),
('landing', 'hero_image', '', 'string'),
('landing', 'hero_cta_primary', '{"label":"Entrar al sistema","href":"/login"}', 'json'),
('landing', 'hero_cta_secondary', '{"label":"Ver funciones","href":"#caracteristicas"}', 'json'),

-- ── Textos de cada sección ─────────────────────────────────────────
('landing', 'sections', '{"presentation":{"title":"Qué es PePiuoX Editor","problem":"Mantener una web suele repartirse entre un gestor de contenidos, un maquetador y una carpeta de copias de seguridad. Cada pieza guarda su verdad y ninguna sabe de las otras.","audience":"Equipos que publican contenido con frecuencia y no quieren depender de un desarrollador para cada cambio, pero tampoco aceptan perder control sobre el código, los datos ni el diseño.","advantages":"Una sola base de datos, un solo panel y un solo historial. Lo que se edita se versiona, lo que se publica se puede revertir y lo que se rompe se restaura con una copia fechada."},"features":{"title":"Funciones","intro":"Los módulos que ya están en la plataforma, no una hoja de ruta."},"specs":{"title":"Especificaciones técnicas","intro":"Lo que hay que saber antes de instalarlo en un servidor."},"benefits":{"title":"Qué cambia con la plataforma","intro":"El efecto práctico de tener todo en el mismo sitio."},"gallery":{"title":"El sistema por dentro","intro":"Pantallas reales del panel. Las imágenes se cargan desde Configuración → Portada."}}', 'json'),

-- ── Tarjetas de funciones (8) ──────────────────────────────────────
('landing', 'features', '[{"icon":"bi-vector-pen","title":"Constructor visual de bloques","description":"Arrastra bloques ya diseñados sobre Bootstrap 5 y publica sin escribir HTML. La biblioteca de bloques es propia y coherente con el resto del panel."},{"icon":"bi-clock-history","title":"Versionado del contenido","description":"Cada guardado deja una versión recuperable. Puedes comparar, restaurar o descartar sin miedo a perder el trabajo posterior."},{"icon":"bi-layout-text-window-reverse","title":"Plantillas reutilizables","description":"Define una estructura una vez y utilízala en tantas páginas como necesites, con versiones propias de cada plantilla."},{"icon":"bi-diagram-3","title":"Menús jerárquicos","description":"Menús multinivel con orden arrastrable, asignación por página y caché que se regenera sola al cambiar la estructura."},{"icon":"bi-palette","title":"Identidad visual sin tocar código","description":"Cinco temas, dieciséis colores, diez huecos de logotipo según el contexto, favicon y CSS propio, con el contraste AA calculado en cada superficie."},{"icon":"bi-shield-lock","title":"Seguridad en la base","description":"Contraseñas con bcrypt de coste 12, protección CSRF en los formularios, límite de intentos de acceso y verificación en dos pasos con códigos de recuperación."},{"icon":"bi-graph-up-arrow","title":"Analítica y auditoría","description":"Visitas, estadísticas diarias y un registro de quién hizo qué, cuándo y desde dónde. Todo consultable desde el propio panel."},{"icon":"bi-plug","title":"API versionada","description":"Endpoints en /api/v1 con aplicaciones, claves y tokens para que otros sistemas lean tu contenido sin entrar al panel."}]', 'json'),

-- ── Especificaciones técnicas (pares etiqueta/valor) ───────────────
('landing', 'specs', '[{"label":"Lenguaje","value":"PHP 8.3 o superior, con tipado estricto"},{"label":"Base de datos","value":"MariaDB 11 o superior, mediante PDO con consultas preparadas"},{"label":"Servidor web","value":"Apache 2.4 con mod_rewrite, o nginx con la reescritura equivalente"},{"label":"Dependencias","value":"Ninguna en tiempo de ejecución: autocarga PSR-4 propia, sin Composer"},{"label":"Interfaz pública","value":"Bootstrap 5.3 e iconografía Bootstrap Icons"},{"label":"Constructor","value":"GrapesJS con biblioteca de bloques propia"},{"label":"Seguridad","value":"Bcrypt coste 12, CSRF, límite de peticiones, 2FA TOTP, sesiones HttpOnly y SameSite"},{"label":"Interoperabilidad","value":"API REST versionada en /api/v1 con autenticación por token"}]', 'json'),

-- ── Beneficios (4) ─────────────────────────────────────────────────
('landing', 'benefits', '[{"icon":"bi-lightning-charge","title":"Automatización","description":"La caché, los menús, el sitemap y las copias programadas se regeneran sin intervención manual."},{"icon":"bi-bezier2","title":"Procesos más cortos","description":"Editar, previsualizar y publicar ocurre en la misma pantalla, sin exportar ni subir archivos por FTP."},{"icon":"bi-hdd-stack","title":"Administración centralizada","description":"Páginas, plantillas, menús, archivos, usuarios y ajustes comparten panel, permisos e historial."},{"icon":"bi-clipboard-data","title":"Control de la información","description":"Cada acción queda auditada y cada contenido versionado: se puede saber qué cambió y volver atrás."}]', 'json'),

-- ── Galería de capturas: vacía a propósito ─────────────────────────
('landing', 'gallery', '[]', 'json'),

-- ── Pie ────────────────────────────────────────────────────────────
('landing', 'footer_note', 'PePiuoX Editor — editor y CMS web en PHP 8 y MariaDB. Instalable en servidor propio.', 'string')

ON DUPLICATE KEY UPDATE `id` = `id`;
