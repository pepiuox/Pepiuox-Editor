# Manual de usuario — PePiuoX Editor

**Versión del documento:** 1.0
**Ámbito:** panel de administración y web pública de PePiuoX Editor (CMS/editor web en PHP 8 + MariaDB)
**Destinatarios:** administradores, editores de contenido y responsables técnicos del sitio
**Base de conocimiento de este manual:** lectura directa de `config/routes.php`, de los controladores de `app/Controllers/` y de las vistas de `views/`. No se ha ejecutado la aplicación para escribir este documento.

---

## Índice

1. [Introducción al sistema](#1-introducción-al-sistema)
2. [Objetivo y alcance](#2-objetivo-y-alcance)
3. [Acceso a la plataforma](#3-acceso-a-la-plataforma)
4. [Inicio de sesión](#4-inicio-de-sesión)
5. [El panel principal](#5-el-panel-principal)
6. [Cada módulo explicado](#6-cada-módulo-explicado)
7. [Procedimientos paso a paso](#7-procedimientos-paso-a-paso)
8. [Administración de usuarios](#8-administración-de-usuarios)
9. [Gestión de registros](#9-gestión-de-registros)
10. [Consultas y reportes](#10-consultas-y-reportes)
11. [Configuraciones disponibles](#11-configuraciones-disponibles)
12. [Recomendaciones de seguridad](#12-recomendaciones-de-seguridad)
13. [Preguntas frecuentes](#13-preguntas-frecuentes)
14. [Solución de problemas habituales](#14-solución-de-problemas-habituales)
15. [Limitaciones conocidas](#15-limitaciones-conocidas)
16. [Glosario](#16-glosario)
17. [Trazabilidad](#17-trazabilidad)

---

## Cómo leer este manual (convenciones de veracidad)

Este manual distingue tres tipos de afirmación:

| Marca | Significado |
|---|---|
| **[Comprobado]** | Verificado leyendo el código de este repositorio: la ruta, el controlador, el campo o el botón existen tal como se describe. |
| **[Inferido]** | Deducido por el contexto del código, pero no confirmado ejecutando la aplicación. |
| **[No verificado]** | No se ha podido comprobar; se indica expresamente para que no se dé por cierto. |

Reglas aplicadas al escribir:

- Los nombres de ruta, los nombres de campo y los textos de botón citados provienen de `config/routes.php` o de las vistas.
- Ninguna afirmación de este manual significa «probado ejecutando la aplicación». Lo que no se ha ejecutado no se presenta como probado.
- No hay capturas de pantalla disponibles. En su lugar aparece una indicación entre corchetes: `[Captura sugerida: …]`, que describe qué imagen correspondería a ese punto.
- No se documenta ningún procedimiento para las pantallas que hoy no funcionan (véase la sección 15).

**Datos técnicos de partida [Comprobado en el repositorio]:**

| Elemento | Valor |
|---|---|
| Rutas declaradas en `config/routes.php` | 224 |
| Rutas `/admin/*` | Todas pasan por `AuthMiddleware` |
| Base de datos | `web_editor_db` — 52 tablas, 2 vistas, 4 triggers, 5 rutinas |
| Roles del sistema | `Super Admin`, `Admin`, `Manager`, `Stantard User`, `Guest` (el nombre `Stantard User` está escrito así en el sistema) |
| Prefijo del panel | `/admin` |
| Punto de acceso | `/login` |

---

## 1. Introducción al sistema

PePiuoX Editor es un **CMS** (*Content Management System*, sistema de gestión de contenidos): una aplicación web que te permite crear, editar y publicar el contenido de un sitio sin escribir HTML a mano. Está construida en **PHP 8** sobre una base de datos **MariaDB** y se organiza en dos mitades que comparten los mismos datos:

- **El panel de administración** (`/admin`): el área privada donde tú y tu equipo trabajáis. Requiere iniciar sesión. Aquí se crean páginas, plantillas, menús, usuarios, copias de seguridad y configuración.
- **La web pública** (`/` y `/{slug}`): lo que ve el visitante. Muestra las páginas publicadas con la plantilla y el menú que les hayas asignado.

El sistema incluye además:

- Un **constructor visual** de páginas (editor por bloques, con arrastrar y soltar) en `/admin/pages/{id}/builder`.
- **Versionado**: cada guardado relevante puede generar una versión recuperable de la página.
- **Colaboración y comentarios** por página, con hilos, respuestas y resolución.
- **Personalización visual de la web pública** sin tocar código: logotipos, favicon, temas, colores y CSS propio.
- **Seguridad operativa**: autenticación en dos pasos (2FA), registro de auditoría, historial de intentos de acceso, dispositivos de confianza y baneo de usuarios.
- **API REST** con tokens para consumir el contenido desde otros sistemas.
- **Copias de seguridad** manuales y programadas.

> **Aviso importante antes de empezar:** el sistema tiene una limitación de autorización documentada en la sección 15. No des acceso al panel a personas de poca confianza solo por asignarles un rol: no todas las acciones del panel están filtradas por permiso.

---

## 2. Objetivo y alcance

### 2.1. Objetivo

Este manual te permite operar el sistema sin ayuda técnica: entrar, crear y publicar contenido, gestionar personas y permisos, personalizar la imagen del sitio público, hacer y restaurar copias de seguridad, y diagnosticar los problemas más habituales.

### 2.2. Qué cubre este manual

| Cubierto | Detalle |
|---|---|
| Acceso y sesión | `/login`, `/register`, `/forgot`, `/reset`, `/2fa/challenge`, cierre de sesión |
| Contenido | Páginas, constructor visual, versiones, plantillas, menús, archivos, SEO |
| Personas | Usuarios, roles, baneos, perfil propio, 2FA |
| Operación | Caché, copias de seguridad, API, traducciones, analítica, auditoría, seguridad, notificaciones |
| Imagen del sitio | Logotipos, favicon, temas, colores, CSS propio, vista previa |

### 2.3. Qué NO cubre

- Instalación del servidor, del PHP, de MariaDB o del certificado TLS.
- Desarrollo de nuevas funcionalidades o cambios en el esquema de la base de datos.
- Administración del servidor de correo (solo se documenta qué campos existen en Configuración).
- Las pantallas que hoy no responden por un defecto conocido (sección 15).

### 2.4. Perfiles de usuario del sistema

Los cinco roles del sistema son:

| Rol | Uso previsto [Inferido del nombre y del juego de permisos] |
|---|---|
| `Super Admin` | Control total, incluida la configuración y las copias de seguridad |
| `Admin` | Administración general del sitio y de las personas |
| `Manager` | Gestión de contenido y menús sin llegar a la configuración crítica |
| `Stantard User` | Usuario estándar con acceso limitado (nombre tal como figura en el sistema) |
| `Guest` | Acceso mínimo o de solo lectura |

Los permisos concretos de cada rol se asignan en la base de datos; el formulario de rol del panel edita **nombre**, **descripción** y la marca de **rol por defecto**, pero no se observa en esa vista una matriz de permisos [Comprobado en `views/roles/create.php` y `views/roles/edit.php`].

---

## 3. Acceso a la plataforma

### 3.1. Direcciones

| Qué | Ruta | Notas |
|---|---|---|
| Web pública | `/` | Portada del sitio |
| Página pública por slug | `/{slug}` | Por ejemplo `/quienes-somos` |
| Acceso al panel | `/login` | Formulario de entrada |
| Panel de administración | `/admin` | Requiere sesión iniciada |
| Hoja de estilos pública generada | `/public-theme.css` | Generada desde tu configuración; no requiere sesión |
| Manifiesto de iconos | `/site.webmanifest` | No requiere sesión |
| `sitemap.xml` | `/sitemap.xml` | Generado por el sistema |
| `robots.txt` | `/robots.txt` | Generado por el sistema |

[Captura sugerida: `/login` — formulario de acceso con los campos «Usuario», «Contraseña», la casilla «Recordarme» y el botón «Entrar»]

### 3.2. Requisitos del navegador

- Un navegador actualizado con JavaScript activado: el constructor visual y varias acciones del panel dependen de JavaScript.
- Cookies y almacenamiento local permitidos para el dominio del sitio: la sesión se guarda en una cookie.
- Recomendado: resolución de 1366 px de ancho o superior para el constructor visual y las tablas de datos.

### 3.3. Cuentas

Hay dos vías de alta:

- **Alta por un administrador** desde `/admin/users/create` (recomendado en instalaciones privadas).
- **Autorregistro** desde `/register` **[Comprobado: la ruta existe y está limitada por tasa]**. [Inferido] Si no quieres registros abiertos, revisa la configuración del sitio; el manual no puede confirmar desde el código si el registro está o no abierto al público en tu instalación.

---

## 4. Inicio de sesión

### 4.1. Formulario de acceso — `/login`

Campos del formulario [Comprobado en `views/auth/login.php`]:

| Campo | Nombre técnico | Obligatorio | Notas |
|---|---|---|---|
| Usuario | `username` | Sí | No es el correo: es el nombre de usuario |
| Contraseña | `password` | Sí | |
| Recordarme | `remember` | No | Mantiene la sesión iniciada en ese navegador |
| Botón | — | — | «Entrar» |

En la misma pantalla hay dos enlaces: **«¿Olvidaste tu contraseña?»** (`/forgot`) y **«¿Registrate y se un usuario?»** (`/register`).

**Límite de intentos [Comprobado en `config/routes.php`]:** el envío del formulario está limitado a **5 intentos por minuto** por origen. Si los superas, el sistema rechaza temporalmente el intento. Recuperación de contraseña: 3 solicitudes por hora. Restablecimiento: 5 por hora. Alta: 3 por hora. Verificación 2FA: 3 por minuto.

### 4.2. Verificación en dos pasos — `/2fa/challenge`

Si tu cuenta tiene 2FA activo, tras escribir usuario y contraseña el sistema te lleva a la pantalla de verificación, con dos pestañas [Comprobado en `views/2fa/challenge.php`]:

- **Código de la app autenticadora** (TOTP, 6 dígitos): campo `code`, con la casilla **«Confiar en este dispositivo»** (`trust_device`).
- **Código de recuperación**: campo `recovery_code`. Aviso mostrado por el propio sistema: al usar un código de recuperación recibirás una notificación por correo y **ese código quedará invalidado**.

### 4.3. Cierre de sesión

- `GET /logout` cierra la sesión actual [Comprobado en `config/routes.php`].
- Desde tu perfil (`/admin/profile`) puedes cerrar **todas las demás sesiones** abiertas: `POST /admin/profile/sessions/close`.

### 4.4. Si no puedes entrar

1. Comprueba que estás usando el **nombre de usuario**, no el correo.
2. Comprueba mayúsculas y minúsculas del nombre de usuario.
3. Espera un minuto si has fallado varias veces: el límite de intentos es temporal.
4. Usa **«¿Olvidaste tu contraseña?»**.
5. Si tu cuenta está baneada, el acceso se bloquea aunque la contraseña sea correcta; solo un administrador puede desbanearte (sección 8.4).
6. Si perdiste el acceso a la app autenticadora y no tienes códigos de recuperación, necesitas que un administrador intervenga; el panel no ofrece autoservicio para ese caso [Inferido de las rutas existentes].

---

## 5. El panel principal

### 5.1. Dashboard — `/admin`

Es la pantalla de bienvenida y el resumen del estado del sitio. Muestra [Comprobado en `app/Controllers/DashboardController.php` y `views/dashboard/index.php`]:

| Bloque | Contenido |
|---|---|
| Contadores | Total de páginas, páginas **publicadas**, **borradores** y cifras de apoyo |
| «Páginas recientes» | Últimas páginas modificadas, con su estado, fecha y autor |
| «Actividad reciente» | Últimos movimientos registrados por el sistema |

Las cifras de publicadas y borradores salen directamente del estado de las páginas en la base de datos [Comprobado: `SELECT COUNT(*) … WHERE status='published'` y `status='draft'`].

[Captura sugerida: `/admin` — dashboard con los contadores arriba, «Páginas recientes» y «Actividad reciente»]

### 5.2. Navegación lateral

El menú lateral agrupa los módulos y **oculta los enlaces para los que no tienes permiso** [Comprobado en `views/partials/sidebar.php`]. Permisos asociados a cada enlace:

| Enlace del menú | Permiso que lo controla |
|---|---|
| Páginas | `pages.view` |
| Plantillas | `templates.view` |
| Menús | `menus.manage` |
| Archivos | `files.manage` |
| SEO | `seo.manage` |
| Usuarios | `users.view` |
| Usuarios baneados | `users.ban` |
| Configuración | `settings.manage` |
| Caché | `cache.manage` |
| Backups | `backups.manage` |
| Analítica | `analytics.view` |
| Auditoría y sus enlaces de seguridad | `audit.view` |
| Roles | Sin comprobación `can()` en la vista del menú [Comprobado en `views/partials/sidebar.php`] |
| 2FA (pie del menú) | Sin comprobación `can()`: es tu propia seguridad |

> **Advertencia:** que un enlace esté oculto **no** equivale a que la acción esté protegida en el servidor. La sección 15 explica el alcance real de esta protección.

### 5.3. Barra superior y notificaciones

La barra superior contiene tu ficha de usuario (enlace a `/admin/profile`) y el acceso a **Notificaciones**, con acciones rápidas «marcar todas como leídas» y el listado completo en `/admin/notifications` [Comprobado en `views/partials/topbar.php`].

### 5.4. Módulos sin enlace en el menú

Dos módulos existen y funcionan por URL directa, pero **no aparecen enlazados** en el menú lateral ni en la barra superior [Comprobado: no hay referencia a ellos en `views/partials/` ni en `views/layouts/`]:

| Módulo | URL |
|---|---|
| API | `/admin/api` |
| Traducciones | `/admin/translations` |

Si los usas con frecuencia, guárdalos en marcadores.

---

## 6. Cada módulo explicado

En cada apartado: qué es, para qué sirve, cómo se llega, qué se puede hacer y los avisos que importan.

### 6.1. Dashboard — `/admin`

- **Qué es:** resumen del sitio.
- **Para qué sirve:** saber cuántas páginas hay publicadas y en borrador, qué se ha tocado últimamente y qué ha pasado en el sistema.
- **Qué se puede hacer:** consultar; desde «Páginas recientes» se salta a la edición de esa página.
- **Avisos:** es solo lectura.

### 6.2. Perfil — `/admin/profile`

- **Qué es:** tus datos personales y de seguridad.
- **Para qué sirve:** mantener tus datos al día y proteger tu cuenta.
- **Qué se puede hacer [Comprobado en `views/profile/index.php`]:** editar los datos personales; cambiar tu contraseña en el bloque «Cambiar contraseña»; cerrar las demás sesiones abiertas; acceder al bloque de seguridad (2FA).
- **Avisos:** «cerrar las demás sesiones» es irreversible en el sentido de que esas sesiones deberán volver a autenticarse.

[Captura sugerida: `/admin/profile` — datos personales, cambio de contraseña y bloque de seguridad]

### 6.3. Usuarios — `/admin/users`

- **Qué es:** el listado de personas con cuenta.
- **Para qué sirve:** dar de alta, editar, cambiar contraseña, activar/desactivar y eliminar cuentas.
- **Cómo se llega:** menú lateral → Usuarios, o `/admin/users`.
- **Listado:** columnas ID, Usuario, Email, Rol, Estado, Último acceso y acciones (editar, ver baneo) [Comprobado en `views/users/index.php`]. Botón **«Nuevo»** en la cabecera.
- **Permisos exigidos en el controlador [Comprobado]:** `users.view` para listar, `users.create` para alta, `users.edit` para editar y cambiar contraseña, `users.delete` para eliminar.
- **Avisos:** la eliminación de una cuenta es **destructiva**. El formulario de edición incluye una **«Zona peligrosa»** con el botón de eliminación [Comprobado en `views/users/edit.php`]. No elimines la cuenta con la que estás trabajando.

[Captura sugerida: `/admin/users` — listado con el botón «Nuevo» y las acciones por fila]

### 6.4. Usuarios baneados — `/admin/users/banned`

- **Qué es:** el registro de bloqueos de cuenta.
- **Para qué sirve:** impedir el acceso de una cuenta durante un tiempo o de forma permanente, y consultar el historial.
- **Qué se puede hacer [Comprobado en `views/users/banned.php` y en las rutas]:** buscar usuarios (`search`), filtrar el listado (`filter`), **banear** (con motivo obligatorio, duración y aviso por correo opcional), **banear en lote**, **desbanear**, **desbanear en lote**, **extender** un baneo indicando días y notas, y consultar el historial por usuario y el historial global.
- **Duraciones del formulario de baneo:** 1 día, 7 días (por defecto), 30 días, 90 días, 1 año y **permanente**.
- **Avisos:** el baneo permanente bloquea el acceso hasta que alguien lo levante. El motivo (`reason`) es obligatorio. Existe un proceso automático de expiración (`/cron/process-expired-bans`) que levanta los baneos temporales vencidos.

[Captura sugerida: `/admin/users/banned` — panel «Banear usuario» y tabla de baneos activos con «Desbanear»]

### 6.5. Roles — `/admin/roles`

- **Qué es:** el catálogo de roles del sistema.
- **Para qué sirve:** agrupar permisos y asignar un rol a cada persona.
- **Qué se puede hacer [Comprobado en `views/roles/index.php`]:** listar (nombre, descripción, si es rol de sistema, si es el rol por defecto, número de usuarios), crear (`/admin/roles/create`), editar y eliminar (`POST /admin/roles/{id}/delete`).
- **Campos del formulario de rol:** `name`, `description`, casilla `default_role` («Marcar como rol por defecto»). En la edición, el nombre de un **rol de sistema** es de solo lectura.
- **Avisos:** eliminar un rol es **destructivo**; comprueba antes cuántos usuarios lo tienen asignado (columna «Usuarios»). El formulario no muestra una matriz de permisos: los permisos finos se gestionan fuera de esta pantalla.

### 6.6. Páginas — `/admin/pages`

- **Qué es:** el corazón del CMS. Cada página es un documento con contenido, plantilla, estado y SEO.
- **Para qué sirve:** crear y mantener el contenido del sitio público.
- **Listado [Comprobado en `views/pages/index.php`]:** filtros **Todas / Publicadas / Borradores**, botón **«Nueva»**, y por fila: ID, Título, Slug, Plantilla, Estado, Autor, Actualizada, con acciones **constructor visual**, **editar metadatos**, **ver** (abre la página pública) y **eliminar**.
- **Rutas:** listado `/admin/pages`; alta `/admin/pages/create`; metadatos `/admin/pages/{id}/edit`; constructor `/admin/pages/{id}/builder`; versiones `/admin/pages/{id}/versions`; comentarios `/admin/pages/{id}/comments`.
- **Permisos observados:** el constructor y la subida de recursos exigen `pages.edit`; el bloque de metadatos SEO exige `seo.manage` [Comprobado en `app/Controllers/PagesController.php`].
- **Avisos:** «eliminar» es **destructivo**. En el constructor, la edición del bloque PHP solo se guarda si tienes `pages.edit` [Comprobado en el controlador].

[Captura sugerida: `/admin/pages` — listado con los filtros «Todas / Publicadas / Borradores» y el botón «Nueva»]

### 6.7. Constructor visual — `/admin/pages/{id}/builder`

- **Qué es:** un editor visual por bloques sobre un lienzo.
- **Para qué sirve:** montar el diseño de la página arrastrando bloques y editando su contenido y estilos sin escribir HTML.
- **Qué se puede hacer [Comprobado en `views/pages/builder.php`]:** mostrar u ocultar el panel izquierdo (bloques y recursos), recargar los recursos disponibles, mostrar u ocultar el panel derecho (configuración y comentarios), añadir **comentarios sobre el lienzo**, y guardar la página con su contenido y sus estilos.
- **Qué se guarda:** los campos ocultos del formulario del constructor son `title`, `slug`, `description`, `template_id`, `content_html`, `content_css`, `content_php`, `content_js`, `grapesjs_data`, `status`, `is_published`, `startpage`, `seo_title`, `seo_description` y `version_note`.
- **Aviso:** `content_php` solo se conserva si tienes `pages.edit`. El autoguardado existe (`/admin/pages/{id}/autosave`), pero **no sustituye** a pulsar guardar: [Inferido] el autoguardado conserva un estado intermedio recuperable, no la publicación.

[Captura sugerida: `/admin/pages/{id}/builder` — lienzo central con panel de bloques a la izquierda y panel de configuración a la derecha]

### 6.8. Versiones de página — `/admin/pages/{id}/versions`

- **Qué es:** el historial de versiones de una página.
- **Para qué sirve:** comparar, restaurar o descartar estados anteriores.
- **Qué se puede hacer:** listar versiones, **previsualizar** una versión (`/admin/pages/{id}/versions/{vid}/preview`), **restaurar** (`POST …/restore`) y **eliminar** una versión (`POST …/delete`).
- **Avisos:** restaurar sobrescribe el contenido actual de la página con el de la versión elegida; eliminar una versión es **irreversible**. En esta pantalla no se observa comprobación de permiso dentro del controlador; el único filtro es tener sesión iniciada [Comprobado en `app/Controllers/VersionsController.php`].

### 6.9. Comentarios y colaboración

- **Qué es:** revisión del contenido en equipo, dentro de la página.
- **Comentarios por página [Comprobado en las rutas]:** listar (`/admin/pages/{id}/comments`), crear, **responder**, **resolver**, **reabrir**, **eliminar** y **mover** un comentario. Tus menciones se consultan en `/admin/profile/mentions`.
- **Colaboración en tiempo real:** el sistema expone un conjunto de rutas bajo `/admin/collaboration/{id}/…` (unirse, salir, latido, sondeo, publicar cambios y cursor, bloquear y liberar, mensajes y estadísticas). Estas rutas las consume la interfaz del constructor; **no son pantallas que se naveguen a mano**.
- **Aviso:** eliminar un comentario es destructivo. La limpieza de sesiones y bloqueos de colaboración vencidos la hace un proceso programado (`/cron/cleanup-collaboration`).

### 6.10. Plantillas — `/admin/templates`

- **Qué es:** estructuras reutilizables (cabecera, pie, maquetas) que aplican las páginas.
- **Para qué sirve:** no repetir el mismo diseño en cada página.
- **Listado [Comprobado en `views/templates/index.php`]:** columnas ID, Nombre, Sistema, Activo, Por defecto; botón **«Nuevo»**; acciones editar y (por ruta) duplicar, eliminar y gestionar versiones.
- **Qué se puede hacer:** crear (`/admin/templates/create`), editar el diseño (`/admin/templates/{id}/edit`), **versiones** de plantilla (`/admin/templates/{id}/versions`, con restaurar y eliminar), **duplicar** y **eliminar**.
- **Permisos observados:** `templates.view`, `templates.create`, `templates.edit`, `templates.delete` y `templates.edit_php` para el bloque PHP [Comprobado en `app/Controllers/TemplatesController.php`].
- **Avisos:** eliminar una plantilla en uso afecta a las páginas que la usan; revisa antes la columna «Por defecto» y el listado de páginas.

[Captura sugerida: `/admin/templates` — listado con columnas «Activo» y «Por defecto»]

### 6.11. Menús — `/admin/menus`

- **Qué es:** la estructura de navegación del sitio público.
- **Para qué sirve:** decidir qué enlaces aparecen, en qué orden, con qué anidamiento y en qué ubicación.
- **Listado [Comprobado en `views/menus/index.php`]:** crear menú, **regenerar** el menú automático desde las páginas publicadas, **reparar** (dejar un solo predeterminado y un solo principal por ubicación), duplicar, marcar como predeterminado, activar/desactivar, eliminar.
- **Editor de menú (`/admin/menus/{id}/edit`) [Comprobado en `views/menus/edit.php`]:** campos del menú `name`, `slug`, `location`, `is_active`, `is_primary`; lista de elementos con arrastre para reordenar y anidar, más botones de subir/bajar y quitar; formulario «Añadir elemento» con `page_id`, `title`, `url`, `parent_id`, `order`, `target` e `icon`.
- **Otros datos del módulo:** estadísticas (`/admin/menus/stats`), exportación JSON (`/admin/menus/json`), y un menú JSON público en `/api/menu` con migas en `/api/menu/breadcrumb/{id}`.
- **Permisos observados:** `menus.view`, `menus.create`, `menus.edit`, `menus.delete`.
- **Avisos:** eliminar un menú es destructivo; si es el menú principal o el predeterminado, la navegación pública puede quedarse sin enlaces hasta que asignes otro.

[Captura sugerida: `/admin/menus/{id}/edit` — ajustes del menú y lista de elementos con asas de arrastre]

### 6.12. SEO — `/admin/seo`

- **Qué es:** posicionamiento en buscadores.
- **Para qué sirve:** controlar títulos y descripciones, redirecciones, `sitemap.xml` y `robots.txt`, y analizar páginas concretas.
- **Pantallas [Comprobado]:** panel principal `/admin/seo`; **SEO global** `/admin/seo/global` (`POST /admin/seo/global/save`); **redirecciones** `/admin/seo/redirects` (crear y eliminar); **regenerar sitemap** (`POST /admin/seo/sitemap/regenerate`); por página: **análisis** (`/admin/seo/{id}/analyze`), **competidor** (`/admin/seo/{id}/competitor`) y **generador de metadatos** (`/admin/seo/{id}/meta-generator`, con generar y aplicar).
- **Campos SEO por página [Comprobado en `views/pages/edit.php`]:** `seo_title`, `seo_description`, `seo_keywords`, `seo_canonical`, `seo_robots`, `og_title`, `og_description`, `og_image` y tipo de tarjeta.
- **Permiso observado:** `seo.manage`.
- **Avisos:** una redirección mal configurada puede dejar URLs públicas sin destino. El generador de metadatos propone texto; revísalo antes de aplicarlo.

### 6.13. Archivos — `/admin/files`

- **Qué es:** el gestor de archivos del sitio (imágenes, documentos).
- **Para qué sirve:** subir y reutilizar recursos en páginas y plantillas.
- **Qué se puede hacer [Comprobado]:** subir varios archivos a la vez (campo `files[]`), **sincronizar** el gestor con lo que ya existe en `uploads/`, buscar y filtrar por categoría, **búsqueda avanzada** (`/admin/files/search/advanced`), descargar, **compartir** (crear enlace: `/admin/files/{id}/share`), editar **metadatos** (`POST /admin/files/{id}/metadata`), **exportar** el listado (`/admin/files/export`) y **eliminar**.
- **Límites [Comprobado en `app/Controllers/FilesController.php`]:** tamaño máximo por archivo **10 MB**; extensiones permitidas según la constante `ALLOWED_EXTENSIONS` (por defecto `jpg, jpeg, png, gif, webp, svg`). Si el archivo supera el tamaño o la extensión no está permitida, se rechaza y se informa del motivo.
- **Permiso observado:** `files.manage` habilita la descarga de archivos ajenos; sin él, solo descargas los que te pertenecen.
- **Avisos:** eliminar un archivo es destructivo y puede romper imágenes de páginas ya publicadas.

[Captura sugerida: `/admin/files` — gestor con el selector múltiple de archivos y el botón «Subir»]

### 6.14. Configuración — `/admin/settings`

- **Qué es:** los ajustes del sistema y la personalización visual del sitio público, en **una sola pantalla con pestañas**.
- **Pestañas [Comprobado en `app/Controllers/SettingsController.php`]:** `general`, `seo`, `cache`, `mail`, `identity` (identidad), `logos` (logotipos), `favicon`, `themes` (temas), `colors` (colores), `css` (CSS propio) y `preview` (vista previa).
- **Qué se puede hacer:** guardar ajustes generales; configurar el correo saliente (la pestaña muestra la contraseña SMTP, por eso el acceso está restringido); gestionar los huecos de logotipo y favicon (subir y borrar); elegir tema; ajustar colores; escribir CSS propio; ver una **muestra en vivo** y el **sitio público real** en la pestaña de vista previa.
- **Permiso:** `settings.manage`, exigido **también para ver la pantalla** [Comprobado: `guardManage()` en `index`].
- **Avisos:** los cambios de esta pantalla afectan de inmediato a la web pública. La pestaña de correo contiene datos sensibles: no compartas su contenido ni hagas capturas de ella.

[Captura sugerida: `/admin/settings` — pestañas «Identidad», «Logotipos», «Temas», «Colores» y «Vista previa»]

### 6.15. Caché — `/admin/cache`

- **Qué es:** el almacenamiento temporal que acelera el sitio.
- **Para qué sirve:** vaciarla cuando un cambio no se refleja, o regenerarla tras una actualización grande.
- **Qué se puede hacer [Comprobado en `views/cache/index.php`]:** botón **«Limpiar cache»** (`POST /admin/cache/clear`) y botón **«Regenerar todo»** (`POST /admin/cache/regenerate`).
- **Permiso:** `cache.manage` (controla el enlace del menú).
- **Avisos:** tras limpiar la caché, las primeras visitas pueden ir más lentas mientras se regenera.

### 6.16. Copias de seguridad — `/admin/backups`

- **Qué es:** el módulo de backup (copia de seguridad).
- **Para qué sirve:** guardar una foto del sitio y poder volver atrás.
- **Qué se puede hacer [Comprobado en `views/backups/index.php` y en las rutas]:** crear una copia eligiendo tipo (**Completo (BD + archivos)**, **Solo BD**, **Solo archivos**), descargarla, **restaurarla** y eliminarla; además, una tarjeta de **Programación** para automatizar la creación.
- **Tabla del listado:** Archivo, Tipo, Tamaño, Estado, Creado por, Fecha y acciones.
- **Permiso observado:** restaurar exige `backups.restore` [Comprobado]. Crear/listar no muestra comprobación de permiso dentro del controlador.
- **Avisos:** **restaurar es la acción más peligrosa del panel**: sustituye datos actuales por los de la copia. No la ejecutes sobre producción sin haber descargado antes una copia del estado actual y sin autorización.

[Captura sugerida: `/admin/backups` — selector de tipo de copia, listado de copias y tarjeta «Programación»]

### 6.17. API — `/admin/api`

- **Qué es:** la gestión de aplicaciones y tokens para consumir el contenido por API.
- **Para qué sirve:** dar acceso programático al contenido (por ejemplo, a una app móvil o a otro sistema).
- **Qué se puede hacer [Comprobado]:** listar aplicaciones, crear (`/admin/api/create`), ver el detalle (`/admin/api/{id}`), actualizar, eliminar, **regenerar**, crear **tokens** (`POST /admin/api/{id}/tokens`) y **revocar** un token (`POST /admin/api/{id}/tokens/{tid}/revoke`).
- **Campos de la aplicación [Comprobado en `views/api/create.php`]:** `app_name`, `description`, `allowed_ips` (lista separada por comas), `rate_limit` (por defecto 100) y `expires_at`.
- **Tokens [Comprobado en `views/api/show.php`]:** se crean indicando una caducidad en segundos (`expires_in`, por defecto 3600). La pantalla incluye un bloque de **ejemplo de uso**.
- **Endpoints públicos de la API [Comprobado]:** `/api/v1/pages`, `/api/v1/pages/{slug}`, `/api/v1/menus/{slug}`, `/api/v1/me`, todos con autenticación por **token Bearer**. Además `/api/menu` y `/api/menu/breadcrumb/{id}` son públicos sin token.
- **Avisos:** el token es una credencial: trátalo como una contraseña, no lo pegues en documentación compartida ni en repositorios. Revócalo si se filtra. En esta pantalla no se observa comprobación de permiso dentro del controlador; el único filtro es tener sesión iniciada.

[Captura sugerida: `/admin/api/{id}` — bloque «Tokens» con el botón de creación y el ejemplo de uso]

### 6.18. Traducciones — `/admin/translations`

- **Qué es:** el gestor de las cadenas de texto traducidas, organizadas por idioma y por grupo.
- **Para qué sirve:** traducir la interfaz y el contenido etiquetado.
- **Qué se puede hacer [Comprobado en `views/translations/index.php`]:** elegir idioma (`lang`) y grupo (`group`), buscar (`search`), editar valores y añadir una clave nueva (`key`, `value`). Rutas: `POST /admin/translations/save` y `POST /admin/translations/add`.
- **Avisos:** este módulo **guarda** traducciones, pero el **selector público de idioma no funciona** en la versión actual (sección 15.2). No prometas cambio de idioma en la web pública hasta que se corrija.

### 6.19. Analítica — `/admin/analytics`

- **Qué es:** métricas de uso del sitio.
- **Para qué sirve:** ver qué se visita, quién entra y desde dónde.
- **Qué se puede hacer [Comprobado en `views/analytics/index.php`]:** seleccionar el periodo en días, exportar **Visitas**, **Logins** y **Páginas** (CSV/descarga), consultar **Top páginas**, **IPs sospechosas** (con enlace para bloquear) y **Usuarios más activos**.
- **Datos disponibles también por JSON:** `/admin/analytics/api` (resumen).
- **Permiso:** `analytics.view` (controla el enlace del menú).

### 6.20. Auditoría — `/admin/audit`

- **Qué es:** el registro de lo que ocurre en el sistema, separado en dos vistas.
- **Para qué sirve:** investigar quién hizo qué y cuándo.
- **Auditoría (`/admin/audit`) [Comprobado en `views/audit/index.php`]:** listado con filtros y paginación, **exportación** (CSV y JSON), **estadísticas** (`/admin/audit/stats`), detalle por registro (`/admin/audit/{id}`), por usuario (`/admin/audit/user/{id}`) y por IP (`/admin/audit/ip/{ip}`), y **limpieza** de registros (`POST /admin/audit/clean`).
- **Actividad (`/admin/audit/activity`):** misma estructura (listado, exportación, estadísticas, detalle, limpieza).
- **Permiso:** `audit.view` (controla el enlace del menú).
- **Avisos:** la limpieza de registros es **irreversible** y borra evidencia. Exporta antes si el registro puede hacer falta.

### 6.21. Seguridad — `/admin/security/*`

Tres pantallas complementarias:

| Pantalla | Ruta | Contenido [Comprobado] |
|---|---|---|
| Dashboard de seguridad | `/admin/security/dashboard` | Rate limiting activo, top IPs atacantes, usuarios más atacados, IPs bloqueadas, usuarios baneados, sesiones activas (últimos 30 min) y actividad sospechosa (24 h). Datos en vivo por `/admin/security/dashboard/realtime` |
| Intentos de acceso | `/admin/security/login-attempts` | IPs con múltiples fallos (7 días), IPs bloqueadas, historial de intentos con filtros Todos/Fallidos/Correctos, paginación, formulario **«Bloquear IP»**, desbloqueo por IP y limpieza de intentos |
| Dispositivos de confianza | `/admin/security/trusted-devices` | Lista de dispositivos marcados como de confianza, revocación individual y **revocación de todos** |

- **Avisos:** bloquear una IP puede dejar fuera a personas legítimas que compartan salida a internet (oficina, VPN, operador móvil). Revisa la IP antes de bloquear.

[Captura sugerida: `/admin/security/dashboard` — tarjetas de rate limiting, IPs atacantes y sesiones activas]

### 6.22. Autenticación en dos pasos (2FA) — `/admin/2fa`

- **Qué es:** el segundo factor de tu cuenta.
- **Para qué sirve:** que una contraseña robada no baste para entrar.
- **Qué se puede hacer [Comprobado]:** configurar (`/admin/2fa`), verificar el primer código (`POST /admin/2fa/verify`), **desactivar** (`POST /admin/2fa/disable`), **regenerar códigos de recuperación** (`POST /admin/2fa/regenerate-codes`), **descargar los códigos** (`/admin/2fa/download-codes`) y ver la confirmación de recuperación (`/admin/2fa/recovery-success`).
- **Avisos:** guarda los códigos de recuperación fuera del sitio (por ejemplo, en tu gestor de contraseñas). Cada código de recuperación se invalida al usarse.

### 6.23. Notificaciones — `/admin/notifications`

- **Qué es:** el buzón interno de avisos.
- **Para qué sirve:** enterarte de menciones, comentarios y eventos del sistema.
- **Qué se puede hacer [Comprobado]:** ver el listado, consultar el resumen (`/admin/notifications/list`), **marcar todas como leídas** (`POST …/mark-all`), marcar una como leída, eliminar una (`POST …/{id}/delete`) y **vaciar** el buzón (`POST …/clear`).
- **Avisos:** vaciar el buzón es destructivo.

### 6.24. Web pública — `/` y `/{slug}`

- **Qué es:** el sitio que ven los visitantes.
- **Cómo funciona [Comprobado]:** la portada la sirve `PublicController::home`; el resto de páginas, la ruta comodín `/{slug}` → `PublicController::show`. Los recursos visuales se generan desde tu configuración: `/public-theme.css` y `/site.webmanifest`.
- **Qué puedes controlar sin tocar código:** logotipos, favicon, tema, colores y CSS propio, desde la pestaña correspondiente de Configuración (sección 11).

### 6.25. Módulos que hoy no funcionan

Estos tres grupos existen en el archivo de rutas pero **responden con error** porque la clase o el archivo al que apuntan no coincide con lo declarado [Comprobado por inspección del repositorio]:

| Ruta | Qué falla |
|---|---|
| `/admin/components`, `/admin/components/api`, `/admin/components/create`, `POST /admin/components`, `/admin/components/{id}/edit`, `POST /admin/components/{id}`, `POST /admin/components/{id}/delete` | La ruta apunta a la clase `App\Controllers\ComponentsController`; esa clase existe, pero está declarada dentro del archivo `app/Controllers/ComponentController.php`. El cargador automático busca `ComponentsController.php` y no lo encuentra. |
| `/lang/{code}` | La ruta apunta a `App\Controllers\LanguageController`; esa clase existe dentro de `app/Controllers/LenguageController.php`. Mismo desajuste de nombre de archivo. |
| `/page` y `/page/{slug}` | Las rutas hacen `require` de `page.php` en la raíz del proyecto; ese archivo no existe. |

**Qué significa para ti:** no hay procedimiento posible para estas pantallas. No las uses, no las documentes a tu equipo como disponibles y no prometas su uso. La corrección es una tarea técnica pendiente, no un problema de permisos ni de configuración.

> **Ojo con las URLs públicas:** las páginas publicadas se sirven correctamente por `/{slug}` (ruta comodín `PublicController::show`). Lo que falla es la ruta `/page` y `/page/{slug}`, no la publicación de páginas.

---

## 7. Procedimientos paso a paso

Convención: los pasos van en imperativo. Cuando un paso exige un permiso, se indica. Todos los formularios del panel incluyen un campo de seguridad **CSRF** (`_csrf_token` o el helper `csrf_field()`) [Comprobado en las vistas]; si ese campo falta o caduca, el formulario se rechaza.

### 7.1. Crear una página

1. Entra en `/admin/pages`.
2. Pulsa **«Nueva»** (arriba a la derecha).
3. Rellena **Título** (obligatorio).
4. Rellena **Slug** si quieres una URL concreta; si lo dejas vacío, se genera automáticamente a partir del título.
5. Elige **Plantilla** en el desplegable.
6. Elige **Página padre** si la página cuelga de otra.
7. Añade una **Descripción** corta (máximo 255 caracteres) y el **H1**.
8. Elige el **Estado**: borrador o publicado.
9. Si quieres programar la publicación, rellena **Publicar el** (fecha y hora).
10. Marca **Publicada** si debe quedar visible, y **Página de inicio** si debe ser la portada.
11. Pulsa **«Crear y editar»**.
12. El sistema crea la página y te lleva a su edición, desde donde puedes abrir el constructor visual.

[Captura sugerida: `/admin/pages/create` — formulario con Título, Slug, Plantilla y Estado]

### 7.2. Usar el constructor visual

1. Entra en `/admin/pages` y localiza la página.
2. Pulsa el botón del **constructor visual** de esa fila (o abre `/admin/pages/{id}/builder`).
3. Arrastra los bloques que necesites desde el panel izquierdo al lienzo central.
4. Selecciona un bloque en el lienzo y ajusta sus opciones en el panel derecho.
5. Usa el botón del panel izquierdo para **mostrarlo u ocultarlo** cuando necesites más espacio de trabajo.
6. Usa el botón del panel derecho para abrir la **configuración y los comentarios**.
7. Si quieres dejar una nota al equipo, activa el modo comentario y pulsa sobre el punto del lienzo donde quieras anotar.
8. Rellena la **nota de versión** si quieres identificar este guardado en el historial.
9. Guarda la página.
10. Comprueba el resultado abriendo la página pública con el botón **ver**.

> **Aviso:** si no tienes permiso `pages.edit`, el bloque PHP no se guarda aunque escribas en él.

### 7.3. Publicar o despublicar una página

1. Entra en `/admin/pages/{id}/edit`.
2. Para **publicar**: marca **Publicada** y pon el **Estado** en publicado; si quieres que aparezca más tarde, usa **Publicar el**.
3. Para **despublicar**: desmarca **Publicada** y deja el **Estado** en borrador.
4. Pulsa guardar.
5. Comprueba el cambio en `/admin/pages` (filtros **Publicadas** y **Borradores**) y en la web pública.

[Captura sugerida: `/admin/pages/{id}/edit` — bloque de estado con «Publicada», «Publicar el» y «Página de inicio»]

### 7.4. Restaurar una versión de una página

1. Entra en `/admin/pages/{id}/versions`.
2. Localiza la versión que quieres recuperar.
3. Pulsa **previsualizar** para comprobar que es la correcta.
4. Pulsa **restaurar** y confirma.
5. Vuelve a la página y revisa el contenido.

> **Aviso:** restaurar sustituye el contenido actual. Si el estado actual es valioso, guarda primero (genera una versión nueva) antes de restaurar.

### 7.5. Crear una plantilla

1. Entra en `/admin/templates`.
2. Pulsa **«Nuevo»**.
3. Indica el **nombre** y una **descripción**.
4. Monta el diseño en el editor de la plantilla.
5. Marca **activa** si debe poder asignarse a páginas.
6. Marca **por defecto** solo si debe ser la plantilla que se propone al crear páginas.
7. Guarda.
8. Para guardar un estado concreto de la plantilla, crea una **versión** desde `/admin/templates/{id}/versions`.

[Captura sugerida: `/admin/templates/{id}/edit` — editor de plantilla con el panel «Configuración»]

### 7.6. Crear un menú y asignarlo

1. Entra en `/admin/menus`.
2. Pulsa el botón de **crear menú**.
3. Rellena **nombre** y **slug**.
4. Elige la **ubicación** (`location`) donde se mostrará.
5. Marca **activo** y, si corresponde, **principal**.
6. Guarda; el sistema te lleva al editor del menú.
7. En **«Añadir elemento»**, selecciona la **página** (`page_id`) o escribe una **URL** externa, y ajusta **título**, **padre**, **orden**, **destino** (`target`) e **icono**.
8. Pulsa el botón de añadir.
9. Reordena y anida arrastrando los elementos, o usa los botones de subir y bajar.
10. Para **asignar el menú a una página**, entra en `/admin/pages/{id}/edit`, elige el menú en el desplegable **menú** y guarda.
11. Si necesitas dejar un único menú predeterminado y uno principal por ubicación, usa la acción **reparar** del listado.
12. Si el menú no se refleja en la web pública, usa **regenerar** y luego limpia la caché en `/admin/cache`.

[Captura sugerida: `/admin/menus/{id}/edit` — formulario «Añadir elemento» con página, título, padre y orden]

### 7.7. Subir un archivo

1. Entra en `/admin/files`.
2. En el formulario de subida, pulsa el selector y elige uno o varios archivos.
3. Comprueba que cada archivo cumple: **máximo 10 MB** y extensión permitida (`jpg`, `jpeg`, `png`, `gif`, `webp`, `svg` por defecto).
4. Pulsa **«Subir»**.
5. Revisa el listado: si un archivo se ha rechazado, el sistema indica el motivo (tamaño o extensión).
6. Si el archivo ya estaba en `uploads/` pero no aparece en el gestor, pulsa el botón de **sincronizar**.
7. Opcional: completa los **metadatos** del archivo y crea un enlace de **compartir** si lo necesitas.

[Captura sugerida: `/admin/files` — selector de archivos múltiple y botón «Subir»]

### 7.8. Crear un usuario

1. Entra en `/admin/users` (requiere `users.view`).
2. Pulsa **«Nuevo»**.
3. Rellena **usuario** (`username`), **correo** (`email`), **nombre completo** (`full_name`).
4. Escribe la **contraseña** y repítela en **confirmación**.
5. Elige el **rol**.
6. Elige el **estado** (activo o inactivo).
7. Guarda. Requiere `users.create`.

[Captura sugerida: `/admin/users/create` — formulario «Crear nuevo usuario»]

### 7.9. Asignar un rol a una persona

1. Entra en `/admin/users/{id}/edit` (requiere `users.edit`).
2. En el bloque de información básica, cambia el desplegable **rol**.
3. Guarda.
4. Si el rol que necesitas no existe, créalo antes en `/admin/roles/create`.

> **Aviso de seguridad:** asignar un rol **no** garantiza que todas las acciones de ese rol queden filtradas (sección 15.1). Para personas de poca confianza, prefiere no dar acceso al panel.

### 7.10. Banear y desbanear

**Banear**

1. Entra en `/admin/users/banned` (requiere `users.ban`).
2. Busca a la persona en el buscador del panel «Banear usuario».
3. Selecciona la cuenta.
4. Escribe el **motivo** (obligatorio).
5. Elige la **duración**: 1 día, 7 días, 30 días, 90 días, 1 año o permanente.
6. Marca **notificar por correo** si quieres avisar a la persona.
7. Pulsa **«Banear usuario»**.

**Desbanear**

1. Entra en `/admin/users/banned`.
2. Localiza el baneo en el listado.
3. Pulsa el botón de **desbanear** de esa fila y confirma.
4. Para varios a la vez, usa el baneo/desbaneo en lote del listado.

**Extender un baneo**

1. En `/admin/users/banned`, abre la acción **«Extender baneo»** del registro.
2. Indica los **días** (1 a 3650) y, si quieres, unas **notas**.
3. Pulsa **«Extender»**.

> **Avisos:** el baneo permanente no expira solo. Los baneos temporales se levantan automáticamente cuando vence el plazo, siempre que el proceso programado `/cron/process-expired-bans` esté ejecutándose. El historial de cada persona está en `/admin/users/banned/{id}/history` y el global en `/admin/users/banned/history`.

[Captura sugerida: `/admin/users/banned` — panel «Banear usuario» con motivo, duración y casilla de aviso por correo]

### 7.11. Activar la autenticación en dos pasos (2FA)

1. Entra en `/admin/2fa` (también desde el pie del menú lateral o desde tu perfil).
2. Escanea el código que muestra la pantalla con tu aplicación autenticadora, o introduce la clave manualmente.
3. Escribe el **código de 6 dígitos** en el campo `code`.
4. Pulsa el botón de verificación.
5. Guarda los **códigos de recuperación**; puedes descargarlos desde `/admin/2fa/download-codes`.
6. A partir de ahora, al entrar se te pedirá el código (salvo que marques **«Confiar en este dispositivo»**).

**Para desactivarlo:** en `/admin/2fa`, usa la acción de desactivar (`POST /admin/2fa/disable`).

**Si cambias de móvil:** regenera los códigos de recuperación desde `/admin/2fa/regenerate-codes` y guarda los nuevos.

> **Aviso:** sin la app y sin códigos de recuperación, la cuenta queda inutilizable hasta que un administrador intervenga.

[Captura sugerida: `/admin/2fa` — código QR, campo de 6 dígitos y enlace para descargar los códigos de recuperación]

### 7.12. Crear una copia de seguridad

1. Entra en `/admin/backups`.
2. En la tarjeta de creación, elige el **tipo**: Completo (BD + archivos), Solo BD o Solo archivos.
3. Envía el formulario de creación.
4. Espera a que el listado muestre la copia con su estado.
5. Descarga la copia con el botón de descarga y guárdala **fuera del servidor**.

Opcional: configura la **Programación** en la tarjeta correspondiente para que el sistema cree copias de forma automática (el proceso lo dispara `/cron/backup`).

[Captura sugerida: `/admin/backups` — selector de tipo y listado con la nueva copia y su acción de descarga]

### 7.13. Restaurar una copia de seguridad

> **Detente aquí si no tienes autorización.** Restaurar sustituye los datos actuales.

1. **Antes de nada**, crea y descarga una copia del estado actual (procedimiento 7.12).
2. Entra en `/admin/backups` (necesitas permiso `backups.restore` para restaurar).
3. Localiza la copia que quieres recuperar.
4. Pulsa el botón de **restaurar** de esa fila.
5. Confirma la operación.
6. Cuando termine, cierra la sesión y vuelve a entrar.
7. Verifica: página de inicio, una página interna, un menú y el listado de usuarios.

> **Avisos:** los cambios hechos después de la fecha de la copia se pierden. Si la restauración falla a medias, no repitas a ciegas: conserva el mensaje de error y avisa al responsable técnico. No ejecutes restauraciones sobre producción sin ventana de mantenimiento acordada.

### 7.14. Cambiar el logotipo y los colores del sitio público

**Cambiar un logotipo**

1. Entra en `/admin/settings` (requiere `settings.manage`).
2. Abre la pestaña **«Logotipos»**.
3. Localiza el hueco que quieres cambiar. El sistema define diez ranuras: logo del encabezado, logo de escritorio, logo de tablet, logo de móvil, logo del menú principal, logo de menús multinivel, logo del pie, logo de barra lateral, logo alternativo para fondos oscuros y logo alternativo para fondos claros. La ranura «logo de barra lateral» está oculta en la interfaz [Comprobado], por lo que verás **nueve**.
4. Pulsa el selector de archivo del hueco y elige la imagen. Respeta la medida orientativa que muestra cada hueco (por ejemplo, 240–320 px de ancho con transparencia para el encabezado).
5. Pulsa el botón de subida del hueco.
6. Comprueba el resultado en la pestaña **«Vista previa»** (muestra en vivo y sitio público real).

**Cambiar el favicon**

1. En `/admin/settings`, abre la pestaña **«Favicon»**.
2. Sube los archivos en sus huecos: `favicon.ico`, PNG de 16×16, PNG de 32×32 y `apple-touch-icon` de 180×180.
3. Recarga la web pública con caché del navegador limpia para verlo.

**Cambiar tema y colores**

1. En `/admin/settings`, abre la pestaña **«Temas»**.
2. Elige uno de los temas predefinidos: **Claro**, **Oscuro**, **Corporativo** o **Minimalista**; o marca **personalizado** para definir tu propia paleta.
3. Abre la pestaña **«Colores»** y ajusta los colores base (fondos y textos) y los colores de texto sobre cada superficie (header, menú, menú al pasar, desplegables, pie, barra lateral y botones).
4. Guarda.
5. Revisa **«Vista previa»**.
6. Si quieres volver al punto de partida, usa el botón de restablecer colores de la pestaña de colores (restablece la paleta al tema indicado).

**Añadir CSS propio**

1. En `/admin/settings`, abre la pestaña **«CSS propio»**.
2. Activa la casilla de habilitación (`custom_css_enabled`) — si está desactivada, el CSS no se aplica.
3. Escribe el CSS en el área de texto (límite aproximado de 100 KB, [Comprobado: constante de 102400 bytes]).
4. Guarda y revisa la vista previa.

> **Avisos:** estos cambios afectan de inmediato al sitio público. Si algo se rompe visualmente, restablece colores y desactiva el CSS propio antes de seguir. La pestaña «Correo» contiene datos sensibles (incluida la contraseña SMTP): no hagas capturas de esa pestaña.

[Captura sugerida: `/admin/settings` — pestaña «Logotipos» con los huecos y su botón de subida]

### 7.15. Crear una aplicación de API y su token

1. Entra en `/admin/api` (por URL directa: no está en el menú lateral).
2. Pulsa el botón de creación.
3. Rellena **App name** (`app_name`, obligatorio).
4. Añade una **descripción** para recordar para qué sirve.
5. Opcional: limita las **IPs permitidas** (`allowed_ips`), separadas por comas.
6. Ajusta el **Rate limit** (peticiones permitidas; valor por defecto 100).
7. Opcional: fija una **caducidad** (`expires_at`).
8. Guarda la aplicación.
9. Abre su detalle (`/admin/api/{id}`) y ve al bloque **«Tokens»**.
10. Indica la caducidad del token en segundos (`expires_in`, por defecto 3600) y crea el token.
11. **Copia el token en ese momento** y guárdalo en un gestor de contraseñas. [Inferido] Los tokens no se suelen volver a mostrar completos; si lo pierdes, revócalo y crea otro.
12. Prueba la llamada siguiendo el bloque **«Usage example»** de la misma pantalla, enviando el token como `Authorization: Bearer <token>`.

```bash
# Ejemplo de comprobación de la API (sustituye el dominio y el token)
curl -H "Authorization: Bearer TU_TOKEN" https://tu-dominio.example/api/v1/pages
```

> **Avisos:** revoca (`POST /admin/api/{id}/tokens/{tid}/revoke`) cualquier token que se haya filtrado. Regenerar la aplicación invalida sus credenciales anteriores; comprueba qué sistemas la usan antes.

[Captura sugerida: `/admin/api/{id}` — bloque «Tokens» con el campo de caducidad y el ejemplo de uso]

### 7.16. Limpiar la caché cuando un cambio no se ve

1. Entra en `/admin/cache` (requiere `cache.manage`).
2. Pulsa **«Limpiar cache»**.
3. Si el problema continúa, pulsa **«Regenerar todo»**.
4. Recarga la web pública forzando la recarga (Ctrl+F5 o equivalente) para descartar la caché del navegador.
5. Si sigue sin verse, comprueba que la página está **publicada** y que la versión restaurada o el borrador guardado son los correctos.

---

## 8. Administración de usuarios

### 8.1. Modelo de personas

| Concepto | Dónde se gestiona | Notas |
|---|---|---|
| Cuenta de usuario | `/admin/users` | Usuario, correo, nombre, rol, estado |
| Rol | `/admin/roles` | Agrupación de permisos |
| Baneo | `/admin/users/banned` | Bloqueo temporal o permanente |
| 2FA | `/admin/2fa` | Segundo factor por cuenta |
| Dispositivos de confianza | `/admin/security/trusted-devices` | Equipos exentos de pedir 2FA |
| Sesiones | `/admin/profile` | Cierre de las demás sesiones |

### 8.2. Buenas prácticas de alta

1. Una cuenta por persona, nunca cuentas compartidas.
2. Rol mínimo suficiente.
3. Cuenta inactiva en lugar de eliminar cuando la persona puede volver.
4. 2FA obligatorio para cualquier cuenta con acceso a configuración, usuarios o copias de seguridad [Recomendación].
5. Revisar periódicamente el listado de usuarios y el estado de los baneos.

### 8.3. Cambio de contraseña

- **Un administrador** puede cambiarla desde `/admin/users/{id}/edit`, bloque «Cambiar contraseña» (requiere `users.edit`).
- **Cada persona** puede cambiarla en `/admin/profile`, bloque «Cambiar contraseña».

### 8.4. Baneos: qué ocurre exactamente

- El baneo impide el acceso a la cuenta mientras está activo.
- El listado permite filtrar, buscar, banear en lote, desbanear en lote y extender.
- El historial conserva los baneos anteriores por usuario y de forma global.
- Los baneos con duración expiran mediante un proceso programado; si ese proceso no se ejecuta, un baneo temporal puede seguir bloqueando más tiempo del previsto [Inferido del diseño de la ruta `/cron/process-expired-bans`].

### 8.5. Roles: lo que puedes y no puedes hacer aquí

- Puedes crear, renombrar (salvo roles de sistema), describir, marcar como predeterminado y eliminar roles.
- La asignación de permisos concretos a cada rol se hace a nivel de base de datos; el formulario del panel no expone esa matriz [Comprobado].
- Antes de eliminar un rol, mira la columna **Usuarios**: si tiene usuarios asignados, el borrado puede dejarlos sin rol válido [Inferido].

---

## 9. Gestión de registros

Esta sección resume el ciclo de vida de los cuatro tipos de registro que producen contenido.

### 9.1. Páginas

| Aspecto | Detalle |
|---|---|
| Campos principales | `title`, `slug`, `description`, `h1`, `template_id`, `parent_id`, `menu_id`, `status`, `publish_at`, `is_published`, `startpage` |
| Contenido | `content_html`, `content_css`, `content_js`, `content_php`, `grapesjs_data` |
| SEO | `seo_title`, `seo_description`, `seo_keywords`, `seo_canonical`, `seo_robots`, `og_title`, `og_description`, `og_image`, tipo de tarjeta |
| Historial | Versiones con nota (`version_note`), previsualización, restauración y borrado |
| Estados | Publicada / borrador, con posibilidad de programar la fecha de publicación |
| Riesgo | Eliminar es destructivo; restaurar una versión sobrescribe el contenido actual |

### 9.2. Plantillas

| Aspecto | Detalle |
|---|---|
| Campos | `name`, `description`, `content_html`, `content_css`, `content_js`, `content_php`, `grapesjs_data`, `is_active`, `is_default` |
| Historial | Versiones propias con restaurar y eliminar |
| Riesgo | Eliminar una plantilla en uso; el bloque PHP solo se guarda con `templates.edit_php` |

### 9.3. Menús

| Aspecto | Detalle |
|---|---|
| Menú | `name`, `slug`, `location`, `is_active`, `is_primary`, predeterminado |
| Elementos | `page_id` o `url`, `title`, `parent_id`, `order`, `target`, `icon` |
| Acciones globales | Regenerar desde páginas publicadas, reparar (un predeterminado y un principal por ubicación), duplicar |
| Riesgo | Dejar el sitio sin navegación si se elimina o desactiva el menú principal sin sustituto |

### 9.4. Archivos

| Aspecto | Detalle |
|---|---|
| Subida | Múltiple (`files[]`), 10 MB por archivo, extensiones permitidas por configuración |
| Organización | Categorías, búsqueda simple y avanzada, metadatos |
| Distribución | Descarga, enlace de compartir, exportación del listado |
| Sincronización | Botón para registrar en el gestor lo que ya está en `uploads/` |
| Riesgo | Eliminar un archivo rompe las referencias existentes en páginas publicadas |

---

## 10. Consultas y reportes

No hay un módulo llamado «reportes»; los reportes se obtienen de tres sitios:

### 10.1. Analítica — `/admin/analytics`

| Reporte | Cómo obtenerlo |
|---|---|
| Visitas por periodo | Elegir días y exportar **Visitas** |
| Inicios de sesión | Exportar **Logins** |
| Páginas más vistas | Tarjeta **Top páginas** y exportación **Páginas** |
| IPs sospechosas | Tarjeta correspondiente, con enlace para bloquear |
| Usuarios más activos | Tarjeta correspondiente |
| Datos para integración | `/admin/analytics/api` (JSON) |

### 10.2. Auditoría — `/admin/audit` y `/admin/audit/activity`

| Reporte | Cómo obtenerlo |
|---|---|
| Registro de auditoría filtrado | `/admin/audit` con filtros y paginación |
| Exportación CSV / JSON | `/admin/audit/export` y `/admin/audit/export-json` |
| Estadísticas | `/admin/audit/stats` |
| Historial por usuario | `/admin/audit/user/{id}` |
| Historial por IP | `/admin/audit/ip/{ip}` |
| Registro de actividad | `/admin/audit/activity` (con su propia exportación y estadísticas) |

### 10.3. Seguridad — `/admin/security/*`

| Reporte | Dónde |
|---|---|
| Intentos de acceso fallidos y correctos | `/admin/security/login-attempts` (filtros y paginación) |
| IPs bloqueadas | En la misma pantalla |
| Sesiones activas y actividad sospechosa | `/admin/security/dashboard` |
| Dispositivos de confianza | `/admin/security/trusted-devices` |

### 10.4. Buenas prácticas al reportar

1. Fija el periodo y anótalo junto al informe.
2. Exporta antes de limpiar registros (la limpieza es irreversible).
3. No incluyas en un informe correos reales ni datos personales innecesarios.
4. Para incidencias, adjunta la hora exacta y la IP: permiten localizar el evento en auditoría.

---

## 11. Configuraciones disponibles

### 11.1. Pestañas de `/admin/settings`

| Pestaña | Para qué sirve | Aviso |
|---|---|---|
| `general` | Ajustes generales del sistema | Afecta a todo el sitio |
| `seo` | Valores SEO por defecto | Coherente con `/admin/seo/global` |
| `cache` | Parámetros de caché | Equivale a `/admin/cache` en la operación |
| `mail` | Correo saliente (incluye contraseña SMTP) | **Datos sensibles**: no hagas capturas |
| `identity` | Identidad del sitio: `logo_alt` (texto alternativo del logo) y datos de marca | Afecta a accesibilidad: escribe un texto alternativo útil |
| `logos` | Nueve huecos visibles de logotipo | Respeta las medidas indicadas |
| `favicon` | Cuatro huecos de icono | Recarga con caché limpia para verlo |
| `themes` | Cuatro temas predefinidos y la opción «personalizado» | Cambia la imagen completa del sitio |
| `colors` | Colores base (16 etiquetas) y textos sobre cada superficie (7 etiquetas) | Comprueba contraste antes de publicar |
| `css` | CSS propio con interruptor de habilitación | Un CSS inválido puede romper la web pública |
| `preview` | Muestra en vivo y sitio público real | Úsala siempre antes de dar por bueno un cambio |

[Captura sugerida: `/admin/settings` — fila de pestañas completa]

### 11.2. Otras configuraciones fuera de `/admin/settings`

| Qué | Dónde | Notas |
|---|---|---|
| SEO global y redirecciones | `/admin/seo/global`, `/admin/seo/redirects` | Con efecto en la web pública |
| Copias programadas | `/admin/backups` (tarjeta «Programación») | Se ejecuta por `/cron/backup` |
| Aplicaciones y tokens de API | `/admin/api` | Credenciales |
| Traducciones | `/admin/translations` | El selector público de idioma no funciona (15.2) |
| Caché | `/admin/cache` | Limpiar y regenerar |
| Cuenta y seguridad personal | `/admin/profile`, `/admin/2fa` | |

### 11.3. Procesos automáticos (cron)

El sistema expone rutas de proceso que deben invocarse de forma programada y que están protegidas por un **token** de configuración [Comprobado en `config/routes.php`]:

| Ruta | Función |
|---|---|
| `/cron/backup` | Crea la copia programada |
| `/cron/process-expired-bans` | Levanta baneos vencidos |
| `/cron/cleanup-rate-limits` | Limpia el contador de límites de intentos |
| `/cron/security-digest` | Envía el resumen de seguridad (`?period=daily` u otro) |
| `/cron/cleanup-trusted-devices` | Elimina dispositivos de confianza caducados |
| `/cron/cleanup-collaboration` | Limpia sesiones, bloqueos y eventos de colaboración |

> **Aviso:** si estos procesos no se ejecutan, verás efectos visibles: baneos temporales que no expiran, copias que no se generan y resúmenes de seguridad que no llegan. Su programación es tarea del responsable técnico.

---

## 12. Recomendaciones de seguridad

1. **No des acceso al panel a personas de poca confianza solo por asignarles un rol.** La autorización por permiso no está aplicada de forma uniforme (sección 15.1). Es la recomendación más importante de este manual.
2. **Activa 2FA** en todas las cuentas con acceso a Configuración, Usuarios, Copias de seguridad y API.
3. **Contraseñas largas y únicas**, gestionadas con un gestor de contraseñas. El sistema aplica un límite de intentos de acceso, pero eso no sustituye una contraseña fuerte.
4. **Revisa el historial de intentos** (`/admin/security/login-attempts`) al menos una vez por semana y bloquea IPs solo tras comprobar que el patrón es realmente malicioso.
5. **Banea en lugar de eliminar** cuando el problema es de conducta o riesgo temporal: conserva el historial.
6. **Descarga copias fuera del servidor.** Una copia que solo existe en el mismo servidor no protege frente a la pérdida del servidor.
7. **Prueba la restauración** en un entorno de pruebas antes de necesitarla de verdad [Recomendación].
8. **Trata los tokens de API como contraseñas**: no los pegues en chats, documentos compartidos ni repositorios; revócalos al rotar personal.
9. **No hagas capturas de la pestaña «Correo»** de Configuración ni de los códigos de recuperación 2FA.
10. **Registra los cambios críticos**: quién restauró una copia, quién cambió colores, quién creó un token. La auditoría lo guarda, pero conviene un registro humano del motivo.
11. **Ten en cuenta las carencias conocidas de cabeceras de seguridad** (sección 15.4) al decidir qué publicas en el sitio público.
12. **Revisa los permisos efectivos** de cada rol en la base de datos cuando incorpores a alguien nuevo, porque la interfaz no muestra esa matriz.

---

## 13. Preguntas frecuentes

**¿Cómo entro al panel?**
Con `/login`, usando tu nombre de usuario (no el correo) y tu contraseña. Si tienes 2FA, después se te pedirá el código.

**¿Por qué no veo un módulo en el menú?**
Porque tu rol no tiene el permiso asociado a ese enlace. El menú oculta lo que no puedes ver. Si crees que es un error, pídelo a un administrador.

**¿Por qué el menú no muestra un módulo que sí debería existir (API, Traducciones)?**
Porque esos dos módulos existen pero no están enlazados en el menú; se accede escribiendo la URL (`/admin/api`, `/admin/translations`).

**He guardado y la web pública no cambia. ¿Qué hago?**
Comprueba en este orden: que la página esté publicada; que el menú asignado sea el correcto; limpia la caché en `/admin/cache`; recarga con caché del navegador limpia.

**He perdido el contenido de una página.**
Mira en `/admin/pages/{id}/versions`. Si hay una versión previa, previsualízala y restaura. Si no la hay, recurre a una copia de seguridad.

**Puedo cambiar el idioma del sitio público?**
Hoy no. El gestor de traducciones funciona, pero el selector público de idioma (`/lang/{code}`) está roto (sección 15.2).

**¿Dónde están los componentes reutilizables?**
La pantalla de componentes (`/admin/components`) no funciona en esta versión. No hay procedimiento disponible (sección 15.2).

**¿Puedo cambiar el logo de la web pública sin tocar código?**
Sí: Configuración → pestaña «Logotipos» (y «Favicon» para el icono). Hay nueve huecos visibles.

**¿Cuántos temas hay?**
Cuatro predefinidos —Claro, Oscuro, Corporativo y Minimalista— más la opción «personalizado» para definir tu propia paleta.

**¿Qué pasa si borro un rol que tiene usuarios?**
El rol se elimina; los usuarios que lo tenían quedan sin ese rol. Compruébalo en la columna «Usuarios» antes de borrar.

**¿Un baneo temporal se levanta solo?**
Sí, cuando vence el plazo, siempre que el proceso programado `/cron/process-expired-bans` se esté ejecutando.

**¿Cuánto puede pesar un archivo que subo?**
Hasta 10 MB, con las extensiones permitidas (por defecto `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`).

**¿Necesito algo especial para restaurar una copia?**
El permiso `backups.restore` y mucha prudencia: la restauración sustituye los datos actuales.

---

## 14. Solución de problemas habituales

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| «Credenciales incorrectas» al entrar | Usuario mal escrito (has puesto el correo) o contraseña errónea | Usa el nombre de usuario; si no, «¿Olvidaste tu contraseña?» |
| El acceso se rechaza y la contraseña es correcta | Límite de intentos activo o cuenta baneada | Espera un minuto; si persiste, consulta `/admin/users/banned` |
| Tras la contraseña me pide un código y no lo tengo | 2FA activo | Usa un código de recuperación; si no tienes, pide ayuda a un administrador |
| Un módulo del menú no aparece | Falta el permiso de ese enlace | Pide el permiso o el rol adecuado |
| `/admin/components` da error | Ruta rota por desajuste archivo/clase (15.2) | No hay solución de usuario: es un defecto pendiente de corrección |
| `/lang/xx` da error | Ruta rota (15.2) | Usa el sitio en el idioma por defecto |
| `/page` o `/page/{slug}` da error | Ruta rota (15.2) | Usa la URL pública `/{slug}` |
| Los cambios de color o logo no se ven | Caché del sitio o del navegador | `/admin/cache` → Limpiar; recarga forzada |
| El CSS propio no se aplica | La casilla de habilitación está desmarcada | Actívala y guarda |
| Un archivo no sube | Supera 10 MB o la extensión no está permitida | Reduce el tamaño o convierte el formato |
| Un archivo está en `uploads/` pero no aparece en el gestor | No está registrado en el gestor | Usa el botón de sincronizar en `/admin/files` |
| La página está publicada pero no se encuentra en la web | Slug cambiado, menú desactualizado o caché | Revisa el slug, regenera el menú, limpia la caché |
| El menú público perdió enlaces | Se borró o desactivó el menú principal | Reasigna el menú en la página o marca otro como principal; usa «reparar» |
| El editor visual no responde | JavaScript bloqueado o fallo de carga de un recurso | Recarga; prueba otro navegador; informa al responsable técnico |
| No puedo restaurar una copia | Falta el permiso `backups.restore` | Solicítalo a un administrador |
| Un baneo temporal no expira | El proceso programado no se ejecuta | Avisa al responsable técnico (`/cron/process-expired-bans`) |
| La exportación de auditoría sale vacía | Filtros demasiado restrictivos o registros limpiados | Amplía el filtro o revisa si se ejecutó una limpieza |
| No recibo notificaciones | Buzón vaciado o sin eventos recientes | Revisa `/admin/notifications` |
| El correo del sistema no sale | Configuración SMTP incorrecta | Revisa la pestaña «Correo» con el responsable técnico (sin compartir la contraseña) |

---

## 15. Limitaciones conocidas

Esta sección es deliberadamente explícita. Son defectos y carencias reales de la versión documentada; no son errores de uso ni problemas de configuración.

### 15.1. La autorización por permiso no está aplicada de forma uniforme

- `PermissionMiddleware` existe en el proyecto, pero **no está aplicado en ninguna ruta**.
- Todas las rutas `/admin/*` pasan por `AuthMiddleware`, que comprueba **que hay sesión**, no **qué puede hacer** esa sesión.
- La autorización real se revalida **dentro de los controladores**, y de forma desigual. Ejemplos comprobados en el código:
  - **Sí comprueban permiso:** `UsersController` (`users.view`, `users.create`, `users.edit`, `users.delete`), `MenuController` (`menus.view/create/edit/delete`), `TemplatesController` (`templates.*`, incluido `templates.edit_php`), `SettingsController` (`settings.manage`, también en la lectura), `BackupsController` (solo en la restauración: `backups.restore`), `PagesController` (`pages.edit` en constructor y subida de recursos; `seo.manage` en metadatos SEO).
  - **No se observa comprobación de permiso dentro del controlador:** `VersionsController`, `ApiManageController` y las acciones de listado y creación de `BackupsController`.
- **Traducción para el usuario:** **no des acceso al panel a personas de poca confianza solo por asignarles un rol.** Que un enlace esté oculto en el menú no demuestra que la acción esté bloqueada en el servidor.
- **Mitigación operativa:** acceso al panel restringido a personas de confianza; 2FA obligatorio; revisión periódica de la auditoría.

### 15.2. Tres grupos de rutas no funcionan

| Ruta | Estado | Detalle |
|---|---|---|
| `/admin/components` y todas sus derivadas | **No funciona** | La ruta apunta a `App\Controllers\ComponentsController`; la clase existe, pero está declarada en `app/Controllers/ComponentController.php`. El cargador automático no la encuentra. |
| `/lang/{code}` | **No funciona** | La ruta apunta a `App\Controllers\LanguageController`; la clase existe en `app/Controllers/LenguageController.php`. |
| `/page` y `/page/{slug}` | **No funciona** | Las rutas hacen `require` de `page.php` en la raíz del proyecto, y ese archivo no existe. |

Consecuencias prácticas:

- **Componentes:** no se puede gestionar ningún componente reutilizable desde el panel. No hay procedimiento de usuario.
- **Cambio de idioma:** el módulo de traducciones (`/admin/translations`) sí permite **gestionar** las traducciones, pero **el visitante no puede cambiar de idioma** porque el selector público es una de las rutas roas.
- **Publicación de páginas:** las páginas publicadas se sirven por `/{slug}` y no se ven afectadas. Lo roto es la vía `/page…`, no la publicación.

> No inventes procedimientos alternativos para estas pantallas: no existen.

### 15.3. Textos y estados inconsistentes de la interfaz

- El nombre del rol `Stantard User` está escrito así en el sistema (probable errata). No lo cambies a la ligera: otros elementos pueden referirse a ese nombre literal.
- Los módulos de API y Traducciones no tienen enlace en el menú (sección 5.4).
- El listado de plantillas se titula «Templates» (en inglés) mientras el resto de la interfaz está en español.

### 15.4. Cabeceras de seguridad y dependencias externas

- **No hay CSP** (Content Security Policy, política de seguridad de contenido) configurada: el navegador no recibe una lista de orígenes permitidos para scripts y estilos.
- **No hay HSTS** (HTTP Strict Transport Security): el navegador no recibe la orden de usar siempre HTTPS.
- **Las dependencias se cargan desde CDN sin SRI** (Subresource Integrity): el navegador no puede verificar que el archivo servido por la CDN sea exactamente el esperado.
- **Qué implica para ti:** ante un compromiso de la CDN o un fallo de la red, el panel y la web pública dependen de recursos que no controlas. Como usuario, evita introducir scripts de terceros y no pegues contenido no confiable en el CSS propio.
- **Nota importante:** la ausencia de CSP no equivale a ausencia de otras protecciones. Los formularios incluyen protección CSRF y el acceso está limitado por tasa [Comprobado]. Lo que falta es la capa de cabeceras.

### 15.5. Otras limitaciones observadas

- El formulario de rol no expone la matriz de permisos: los permisos se asignan fuera del panel.
- El listado y la creación de copias de seguridad no muestran comprobación de permiso en el controlador; un usuario con sesión podría intentarlo aunque el enlace esté oculto [Comprobado en el controlador, no verificado ejecutando].
- La restauración de copias no se ha probado en esta sesión: **no existe evidencia de que funcione** en esta instalación, solo evidencia de que la ruta y el servicio existen.
- Ninguna de las afirmaciones de este manual sobre el comportamiento en ejecución ha sido verificada ejecutando la aplicación; todas proceden de la lectura del código.

---

## 16. Glosario

| Término | Definición |
|---|---|
| **API** | Interfaz de programación: un conjunto de direcciones web que devuelven datos en formato reutilizable (aquí, JSON) para que otros sistemas los consuman. |
| **Backup / copia de seguridad** | Copia de los datos del sitio (base de datos y/o archivos) para poder recuperarlos. |
| **Bearer token** | Credencial que se envía en la cabecera `Authorization` para identificarse ante una API. Trátala como una contraseña. |
| **Borrador** | Página guardada pero no visible en la web pública. |
| **CDN** | Red de servidores que entrega archivos estáticos (scripts, estilos) desde ubicaciones cercanas al visitante. |
| **CMS** | Sistema de gestión de contenidos: aplicación para crear y publicar contenido web sin programar. |
| **Constructor visual** | Editor por bloques que permite montar una página arrastrando elementos y viendo el resultado. |
| **CSRF** | Ataque que fuerza a un usuario autenticado a enviar una petición que no deseaba. Se combate con un campo de seguridad (token) en cada formulario. |
| **CSP** | Cabecera HTTP que indica al navegador qué orígenes pueden cargar scripts, estilos e imágenes. |
| **CSS** | Lenguaje de estilos que define la apariencia de una página. |
| **Cron** | Mecanismo del sistema operativo para ejecutar tareas automáticas de forma periódica. |
| **Favicon** | Icono pequeño que identifica el sitio en la pestaña del navegador. |
| **HSTS** | Cabecera que obliga al navegador a usar siempre HTTPS con ese dominio. |
| **JSON** | Formato de texto estructurado usado para intercambiar datos. |
| **Middleware** | Capa intermedia que procesa una petición antes de llegar al controlador (por ejemplo, comprobar la sesión). |
| **Migaja (breadcrumb)** | Ruta de navegación que muestra en qué punto del sitio estás. |
| **Plantilla** | Estructura de diseño reutilizable que aplican las páginas. |
| **Permiso** | Autorización concreta (por ejemplo `pages.edit`) que habilita una acción. |
| **Rol** | Conjunto de permisos que se asigna a una persona. |
| **Ruta** | Dirección dentro del sitio (`/admin/pages`) que el sistema asocia a una acción. |
| **Sesión** | Periodo durante el cual el sistema recuerda que has iniciado sesión. |
| **SEO** | Optimización para buscadores: prácticas que mejoran cómo apareces en los resultados de búsqueda. |
| **Sitemap** | Archivo que lista las páginas del sitio para los buscadores. |
| **Slug** | Parte final de una URL que identifica la página (`/quienes-somos`). |
| **SRI** | Integridad de subrecurso: atributo que permite al navegador verificar que un archivo externo no ha sido alterado. |
| **TOTP** | Código de 6 dígitos que cambia cada 30 segundos en una app autenticadora; es el segundo factor de 2FA. |
| **2FA** | Autenticación en dos pasos: además de la contraseña, un segundo código. |
| **Versión** | Estado guardado de una página o plantilla, recuperable después. |

---

## 17. Trazabilidad

Tabla de comprobación: cada ruta documentada en este manual, con su módulo y el controlador real que la atiende. Fuente: `config/routes.php` [Comprobado].

### 17.1. Rutas públicas y de acceso

| Ruta | Módulo | Controlador |
|---|---|---|
| `GET /` | Web pública (portada) | `PublicController::home` |
| `GET /{slug}` | Web pública (página) | `PublicController::show` |
| `GET /sitemap.xml` | SEO público | `PublicController::sitemap` |
| `GET /robots.txt` | SEO público | `SeoController::robots` |
| `GET /public-theme.css` | Tema público | `PublicThemeController::css` |
| `GET /site.webmanifest` | Tema público | `PublicThemeController::manifest` |
| `GET /login` | Acceso | `AuthController::showLogin` |
| `POST /login` | Acceso | `AuthController::login` |
| `GET /logout` | Acceso | `AuthController::logout` |
| `GET/POST /register` | Alta | `PasswordController::registerForm` / `registerSend` |
| `GET/POST /forgot` | Recuperación | `PasswordController::forgotForm` / `forgotSend` |
| `GET/POST /reset` | Recuperación | `PasswordController::resetForm` / `resetSave` |
| `GET /2fa/challenge` | 2FA | `TwoFactorController::challenge` |
| `POST /2fa/challenge/verify` | 2FA | `AuthController::verify2FA` |
| `GET /api/menu`, `/api/menu/breadcrumb/{id}` | API pública de menús | `MenuController::getMenuJson`, `getBreadcrumb` |
| `GET /api/v1/pages`, `/pages/{slug}`, `/menus/{slug}`, `/me` | API con token | `ApiController::pagesIndex`, `pagesShow`, `menusShow`, `me` |
| `GET /cron/*` | Procesos automáticos | `BackupsController::cron`, `BannedUsersController::processExpired` y cierres del router |
| `GET /lang/{code}` | **Roto** | `LanguageController::switch` (clase en `LenguageController.php`) |
| `GET /page`, `/page/{slug}` | **Roto** | Requiere `page.php` (no existe) |

### 17.2. Rutas del panel

| Ruta | Módulo | Controlador |
|---|---|---|
| `/admin` | Dashboard | `DashboardController::index` |
| `/admin/profile`, `POST /admin/profile`, `POST /admin/profile/password` | Perfil | `ProfileController::index`, `update`, `changePassword` |
| `POST /admin/profile/sessions/close` | Perfil / sesiones | `AuthController::closeOtherSessions` |
| `/admin/profile/mentions` | Comentarios (menciones) | `CommentsController::mentions` |
| `/admin/users`, `/create`, `POST /admin/users`, `/search`, `/{id}/edit`, `POST /{id}`, `POST /{id}/delete`, `POST /{id}/password` | Usuarios | `UsersController` |
| `/admin/users/banned`, `/history`, `/search`, `/ban`, `/bulk-ban`, `/bulk-unban`, `/{id}/unban`, `/{id}/extend`, `/{id}/history` | Baneos | `BannedUsersController` |
| `/admin/roles`, `/create`, `POST /admin/roles`, `/{id}/edit`, `POST /{id}`, `POST /{id}/delete` | Roles | `RolesController` |
| `/admin/pages`, `/create`, `POST /admin/pages`, `/assets`, `/{id}/builder`, `/{id}/builder/upload`, `/{id}/edit`, `POST /{id}`, `POST /{id}/delete`, `/{id}/autosave`, `POST /{id}/autosave`, `POST /{id}/autosave/clear` | Páginas | `PagesController` |
| `/admin/pages/{id}/comments` y derivadas (reply, resolve, reopen, delete, move) | Comentarios | `CommentsController` |
| `/admin/pages/{id}/versions`, `/{vid}/preview`, `POST /{vid}/restore`, `POST /{vid}/delete` | Versiones | `VersionsController` |
| `/admin/templates` y derivadas (`/create`, `/{id}/edit`, `/{id}/builder/upload`, `/{id}/versions`, `POST /{id}`, `POST /{id}/versions`, `POST /{id}/versions/{vid}/restore`, `POST /{id}/versions/{vid}/delete`, `POST /{id}/delete`, `POST /{id}/duplicate`) | Plantillas | `TemplatesController` |
| `/admin/menus` y derivadas (`/create`, `/sync`, `/repair`, `/regenerate`, `/json`, `/stats`, `/{id}/edit`, `/{id}/breadcrumb`, `POST /{id}`, `POST /{id}/delete`, `POST /{id}/duplicate`, `/{id}/toggle`, `/{id}/default`, `/{id}/items`, `/{id}/items/reorder`, `/{id}/items/{item}/delete`, `/{id}/items/{item}/move`) | Menús | `MenuController` |
| `/admin/seo`, `/global`, `POST /global/save`, `/redirects`, `POST /redirects/store`, `POST /redirects/{id}/delete`, `POST /sitemap/regenerate`, `/{id}/analyze`, `/{id}/competitor`, `/{id}/meta-generator`, `POST /{id}/generate-meta`, `POST /{id}/apply-meta` | SEO | `SeoController` |
| `/admin/files`, `POST /upload`, `POST /wysiwyg-upload`, `POST /sync`, `/{id}/download`, `/{id}/share`, `POST /{id}/share`, `POST /{id}/delete`, `/search/advanced`, `POST /{id}/metadata`, `/export` | Archivos | `FilesController` |
| `/admin/settings`, `POST /update` | Configuración | `SettingsController::index`, `update` |
| `POST /admin/settings/brand`, `/brand/asset`, `/brand/asset/delete`, `/brand/reset` | Configuración (web pública) | `SettingsController::updatePublic`, `uploadAsset`, `deleteAsset`, `resetPublic` |
| `/admin/cache`, `POST /clear`, `POST /regenerate` | Caché | `CacheController` |
| `/admin/backups`, `POST /create`, `POST /schedule`, `/{id}/download`, `POST /{id}/restore`, `POST /{id}/delete` | Copias de seguridad | `BackupsController` |
| `/admin/api`, `/create`, `POST /admin/api`, `/{id}`, `POST /{id}`, `POST /{id}/delete`, `/{id}/regenerate`, `POST /{id}/tokens`, `POST /{id}/tokens/{tid}/revoke` | API | `ApiManageController` |
| `/admin/translations`, `POST /save`, `POST /add` | Traducciones | `TranslationsController` |
| `/admin/analytics`, `/export`, `/api` | Analítica | `AnalyticsController` |
| `/admin/audit`, `/export`, `/export-json`, `/stats`, `POST /clean`, `/user/{id}`, `/ip/{ip}`, `/{id}` | Auditoría | `AuditController` |
| `/admin/audit/activity`, `/export`, `/export-json`, `/stats`, `POST /clean`, `/user/{id}`, `/ip/{ip}`, `/{id}` | Actividad | `ActivityLogsController` |
| `/admin/security/dashboard`, `/realtime` | Seguridad | `SecurityDashboardController` |
| `/admin/security/login-attempts`, `POST /block`, `POST /clean`, `POST /{id}/unblock` | Seguridad | `LoginAttemptsController` |
| `/admin/security/trusted-devices`, `POST /revoke-all`, `POST /{id}/revoke` | Seguridad | `TrustedDevicesController` |
| `/admin/2fa`, `POST /verify`, `POST /disable`, `/recovery-success`, `POST /regenerate-codes`, `/download-codes` | 2FA | `TwoFactorController` |
| `/admin/notifications`, `/list`, `POST /mark-all`, `POST /clear`, `POST /{id}/read`, `POST /{id}/delete` | Notificaciones | `NotificationsController` |
| `/admin/collaboration/{id}/*` | Colaboración | `CollaborationController` |
| `/admin/components…` | **Roto** | `ComponentsController` (clase declarada en `ComponentController.php`) |

### 17.3. Evidencias utilizadas

| Evidencia | Qué se comprobó |
|---|---|
| `config/routes.php` | 224 rutas declaradas; middlewares (`AuthMiddleware` en `/admin/*`, `ApiAuthMiddleware` en `/api/v1/*`, límites por tasa en login/registro/recuperación/2FA); rutas rotas de `components`, `lang` y `page` |
| `app/Controllers/SettingsController.php` | Pestañas válidas, permiso `settings.manage` también en lectura, acciones de la web pública |
| `app/Services/PublicThemeService.php` | Diez ranuras de logotipo (una oculta), cuatro de favicon, cuatro temas predefinidos más «personalizado», 16 colores base y 7 de texto sobre superficie, límite de CSS |
| `app/Controllers/PagesController.php` | Permisos `pages.edit` y `seo.manage`, constructor y subida de recursos |
| `app/Controllers/UsersController.php`, `MenuController.php`, `TemplatesController.php`, `BackupsController.php`, `VersionsController.php`, `ApiManageController.php` | Qué controladores comprueban permiso y cuáles no |
| `app/Controllers/FilesController.php` | Límite de 10 MB y extensiones permitidas |
| `app/Controllers/DashboardController.php` | Contadores de páginas publicadas y borradores |
| `views/*` | Campos, botones y textos citados en cada procedimiento |
| `views/partials/sidebar.php` y `topbar.php` | Permiso que controla cada enlace del menú y ausencia de enlaces a API y Traducciones |

### 17.4. Qué no se pudo verificar

- **No se ejecutó la aplicación** en ningún momento: no hay ninguna prueba de comportamiento real (login, guardado, restauración, subida) en este documento.
- **La restauración de copias de seguridad** existe como ruta y servicio, pero no se ha probado su funcionamiento.
- **El envío de correo** (SMTP, digest de seguridad, aviso de baneo) no se ha verificado.
- **La ejecución real de los procesos cron** no se ha verificado.
- **El contenido exacto de los formularios de programación de copias** no se detalla porque no se inspeccionaron sus campos uno a uno.
- **Las rutas que exigen permiso** se han comprobado leyendo el controlador; no se ha verificado qué permisos tiene cada rol concreto en la base de datos.

---

*Fin del manual. Versión 1.0 — documento generado por lectura directa del código; toda afirmación de comportamiento en ejecución debe validarse en un entorno de pruebas antes de darla por buena.*

---

## Actualización del 31 de octubre de 2026

Este manual se escribió antes de la ronda de cambios de esa fecha. Lo que sigue es lo que hay
**además** de lo ya descrito; donde contradiga algún apartado anterior, manda este bloque.

### A. Editor de la portada — `Configuración → Editar la portada`

Pantalla propia en `/admin/settings/landing`. Permite editar sin tocar código:

- El **hero**: antetítulo, titular, eslogan, descripción e imagen (si se deja vacía, la portada dibuja
  una representación del panel en lugar de una captura).
- Los **dos botones** del hero: texto y destino.
- Los **textos de las cinco secciones**: presentación (problema, para quién, qué aporta), funciones,
  especificaciones, beneficios y galería.
- Las **listas**, con botones de añadir y quitar fila: tarjetas de funciones, especificaciones
  técnicas, beneficios y capturas de la galería.
- La **nota del pie**.

Dos comportamientos que conviene conocer:

1. **Guardar no publica.** El interruptor «Publicar la portada como página de inicio» está separado del
   resto: guardar los textos no cambia lo que ve el visitante en `/`. Mientras esté desactivado, la web
   sigue mostrando la página marcada como inicio en `Páginas`.
2. **Un dato inválido no borra el anterior.** Si un botón lleva un destino que no es una ruta del sitio,
   un ancla o una URL `http(s)`, se rechaza y **se conserva el que había**; la pantalla lo avisa. Lo
   mismo con las imágenes.

Para verla antes de publicarla: **`Configuración → Editar la portada → Ver cómo queda`**.

### B. Sitemap — `/admin/seo/sitemap`

Pantalla nueva dentro de SEO. Muestra el estado del archivo, la URL base y de dónde sale, los menús que
se van a leer con el motivo de cada uno, y la lista de URLs que se publicarían.

- **Se regenera solo** al guardar, crear o borrar una página, y al cambiar cualquier cosa de un menú.
- **Qué incluye, por defecto:** los menús marcados como **predeterminados** de cada ubicación — el
  «Menú principal». Hay otras tres opciones: el menú que el header está mostrando, un menú concreto, o
  todas las páginas publicadas.
- La **URL base** es lo que decide si el archivo sirve. Si apunta a `localhost`, la pantalla lo avisa:
  un sitemap con direcciones locales no lo indexa ningún buscador. Se configura ahí mismo.
- La página de inicio se publica como `/`, nunca como `/su-slug`, para no anunciar la misma página dos
  veces.

### C. Permisos: qué cambia para ti

Desde esta actualización **cada acción del panel exige su permiso**. Antes bastaba con tener sesión; ahora
un `Manager` puede ver las versiones de una página pero no restaurarlas ni borrarlas.

Consecuencia práctica: si creas un rol nuevo, **asígnale los permisos de los módulos que vaya a usar**.
Si no lo haces, verá el menú pero recibirá un 403 al entrar en los módulos sin permiso. El `Super Admin`
no necesita permisos: los tiene todos por definición.

Quedan sin permiso a propósito, porque son de cada persona sobre lo suyo: el tablero, el perfil propio y
las notificaciones propias.

### D. Tareas programadas

Los seis endpoints `/cron/*` exigen ahora un token estable. Si tu instalación no tiene `CRON_TOKEN`
definido, **responden 401 a propósito**. Ver el manual de instalación.

### E. Páginas de presentación del sistema

`01-TOOLS/bootstrap/seed-pages.php` crea tres páginas con contenido coherente con lo que la plataforma
hace (`/funciones`, `/seguridad`, `/como-funciona`) y las enlaza en el menú predeterminado. Es una
herramienta de terminal, no un instalador web, y no borra páginas existentes.

### F. Deuda 17 corregida — las tres rutas rotas ya funcionan

**Esto anula cualquier afirmación anterior de este manual que diga que `/admin/components*`,
`/lang/{code}`, `/page` o `/page/{slug}` no funcionan.** Se corrigieron el 31 de octubre de 2026.

| Ruta | Antes | Ahora |
|---|---|---|
| `/admin/components` y sus seis derivadas | Error 500 al entrar (clase no encontrada) | Funciona; exige el permiso `components.manage` |
| `/lang/{code}` | Error 500 | Cambia el idioma y redirige (302) |
| `/page` y `/page/{slug}` | Error 500 | Alias de las rutas públicas: `/page/funciones` es `/funciones` |
| `/admin/audit/activity` | Inalcanzable: `/admin/audit/{id}` la interceptaba | **También corregida**: el orden de rutas se rehizo y `tests/rutas/check.php` lo vigila |

La causa era que dos archivos se llamaban distinto que la clase que contenían, y el cargador automático
busca el nombre exacto. Se renombraron los archivos; el código de las rutas no cambió.

### G. Editor del pie de página — `Configuración → Editar el pie`

El pie aparece en **todas** las páginas públicas y antes tenía su texto escrito en el código. Ahora se
edita desde el menú lateral, en **Web pública → Pie de página** (o en `/admin/settings/footer`), igual
que la portada, que está en **Web pública → Portada**.

> El grupo **Web pública** del menú solo aparece a quien tiene el permiso `settings.manage`. Si no lo ves,
> es que tu rol no lo incluye.

- **Bloque de marca:** el texto que va bajo el logotipo o el nombre del sitio. El logotipo se cambia en
  `Logotipos e iconos`.
- **Columnas de enlaces:** se añaden, editan y quitan columnas, y dentro de cada una sus enlaces (texto y
  destino). Máximo 4 columnas y 6 enlaces por columna. Una columna sin título o sin enlaces válidos
  desaparece del pie en vez de dibujar un título huérfano.
- **Columna de contacto:** interruptor para mostrarla, título, correo y una nota libre. Un correo con
  formato inválido **se guarda vacío**, no se pinta roto.
- **Línea de copyright:** admite dos marcadores, `{year}` (año en curso) y `{site}` (nombre del sitio).
  Vacío = la línea no se pinta.

**Destinos aceptados en un enlace:** ruta del sitio (`/funciones`), o URL `http(s)`. Se rechazan
`javascript:`, `data:` y las direcciones protocolo-relativas (`//otro-dominio`).

**Cambio de contenido respecto a antes:** los enlaces antiguos del pie apuntaban a `about`, `privacy` y
`terms`, **tres páginas que nunca se crearon** y que llevaban a un 404. Los valores por defecto ahora
apuntan a páginas reales (`/funciones`, `/seguridad`, `/como-funciona`, `/quienes-somos`).

### H. El pie también se edita con el constructor visual

Además de los campos, el pie tiene un **constructor igual al de las páginas**: `Configuración → Editar el
pie → Editar con el constructor` (o `/admin/settings/footer/builder`).

- Se arrastran los mismos bloques que en una página y se compone el pie a mano.
- Al guardar, el sitio pasa a mostrar **ese** pie.
- La casilla **«Usar este pie»** decide cuál se ve. Desmarcarla y guardar devuelve el pie por campos, con
  todo lo que tuviera: probar el constructor no destruye nada.
- El constructor pinta su contenido con el mismo saneado que una página: un `<script>` pegado dentro del
  contenido no se ejecuta.

Los textos dinámicos del copyright (`{year}`, `{site}`) son del pie por campos. Si usas el constructor,
escribe el año a mano o vuelve a los campos.

### I. Guardar varios diseños de pie y elegir cuál se usa

Un diseño es una composición del pie hecha con el constructor. Puedes tener varios y cambiar de uno a otro
sin volver a componer nada.

**Crear un diseño** — `Configuración → Editar el pie → Catálogo de diseños`, escribe un nombre (por ejemplo
«Pie oscuro») y pulsa **Crear y componer**. Se abre el constructor vacío.

**Ponerlo en el sitio** — al terminar de componer, marca **«Usar este diseño en el sitio»** y guarda. También
puedes hacerlo desde el catálogo con el botón **Usar**.

**Copiar un diseño** — **Duplicar** crea una copia con otro nombre. Sirve para probar un cambio sin tocar el
que ya funciona.

**Quitar un diseño** — solo los que no están en uso. El que está activo no se puede borrar: activa otro o
pulsa **Volver al pie por campos** y entonces bórralo.

**Volver al pie por campos** — desactiva el diseño y el sitio muestra otra vez el pie de columnas, con todo
lo que tuviera. Nada se pierde.