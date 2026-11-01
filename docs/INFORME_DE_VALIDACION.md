# Informe de validación y pruebas

**Proyecto:** PePiuoX Editor · **Fecha:** 31 de octubre de 2026 · **Alcance:** portada comercial, contenido
editable, paquete de instalación, sitemap, documentación y seguridad de lo nuevo.

Este informe dice **qué se ejecutó, qué salió y qué no se pudo comprobar**. Nada de lo que aparece como
superado se declara por inspección visual: cada línea tiene detrás una orden ejecutada o una consulta
medida. Lo que quedó fuera se dice al final, en `Limitaciones`.

---

## 1. Resumen

| Área | Estado | Evidencia |
|---|---|---|
| Respaldo de la base de datos | **Verificado** | Restaurado en base aislada; 52/52 tablas con checksum idéntico |
| SQL de instalación limpia | **Verificado** | Importado sin errores; 52 tablas, 2 vistas, 4 triggers, 5 rutinas, 0 datos personales |
| Primer administrador | **Verificado** | Cuenta creada en base aislada; `password_verify` correcto; 4 guardas probadas |
| Portada comercial | **Verificado** | 46 comprobaciones; `/` sin regresión; copia estática generada |
| Contenido editable desde el panel | **Verificado** | 15 comprobaciones de guardado, rechazo y persistencia |
| Sitemap por ámbito | **Verificado** | 17 comprobaciones; archivo regenerado con 9 URLs |
| Documentación | **Entregada** | 4 documentos, 3 890 líneas, contrastados contra el código |
| Seguridad de lo nuevo | **Revisado** | Sin hallazgos nuevos; 6 hallazgos preexistentes registrados como deuda 14–17 |

**Total de comprobaciones automatizadas ejecutadas: 78, con 0 fallos.**

---

## 2. Entorno real de la prueba

| Componente | Valor medido |
|---|---|
| Sistema | Windows, PowerShell |
| PHP | 8.4.25 CLI (requisito del proyecto: 8.3+) |
| Base de datos | MariaDB 12.3.3 en `127.0.0.1:3306` (requisito: 11+) |
| Cliente MySQL | `mysql` 15.2 · `mysqldump` 10.20 |
| Node.js | v26.5.0 (solo para `tests/blocks-library`) |
| Servidor de prueba | `php -S 127.0.0.1:<puerto> -t public` |
| Base de trabajo | `web_editor_db` (real, solo lectura en las pruebas) |

---

## 3. Base de datos

### 3.1 Respaldo completo

```
db/backup/database_backup_20261031.sql — 1 221 355 bytes
mysqldump --skip-ssl --single-transaction --skip-lock-tables
          --routines --triggers --events --hex-blob --complete-insert
```

| Comprobación | Resultado |
|---|---|
| El archivo termina correctamente | `-- Dump completed on 2026-10-31 17:10:13` |
| Restauración en base aislada | exit 0, sin errores |
| `CHECKSUM TABLE` de las 52 tablas | **0 diferencias** entre origen y restaurada |
| Objetos restaurados | 52 tablas · 2 vistas · 4 triggers · 5 rutinas |
| Vistas consultables | `daily_summary` 2 filas · `user_activity_summary` 3 filas |
| Base de verificación eliminada | `SHOW DATABASES LIKE 'zz_%'` vacío |

**Hallazgo:** `mysqldump` falla contra este MariaDB en Windows con `SEC_E_NO_CREDENTIALS` si no se pasa
`--skip-ssl`. Está fijado en el procedimiento y en el manual.

**Aviso de restauración:** el volcado conserva `DEFINER=`root`@`localhost`` en 14 sitios. Restaurar con
otra cuenta exige privilegios altos o limpiar esas cláusulas.

### 3.2 Instalación limpia

`db/install/initial_database.sql` — 143 335 bytes. Construido **por lista blanca**: estructura completa
+ siete tablas de catálogo. 529 filas en total.

| Comprobación | Resultado |
|---|---|
| Importación sin `--force` | exit 0, sin una línea de error |
| Objetos | 52 tablas · 2 vistas · 4 triggers · 5 rutinas |
| Catálogos | roles 5 · permisos 64 · rol-permiso 269 · ajustes 61 · idiomas 7 · traducciones 122 · programación de copias 1 |
| Datos operativos | `users` 0 · `pages` 0 · `templates` 0 · `api_tokens` 0 · `audit_logs` 0 |
| Identidad neutralizada | `site_name` = «PePiuoX Editor» · `site_timezone` = «UTC» · logos y favicons vacíos |
| Sin rastro del sitio de origen | búsqueda de nombre, zona horaria y correo originales: 0 coincidencias |
| Sin hashes ni secretos de API | 0 coincidencias de `$2y$`, `$2a$`, `app_secret`, `refresh_token` en datos |

### 3.3 Primer administrador

`01-TOOLS/bootstrap/create-admin.php` (herramienta de terminal, fuera de la raíz web).

| Comprobación | Resultado |
|---|---|
| Creación en base aislada | cuenta creada, rol `Super admin`, `role_id` 1, activa |
| Algoritmo de la contraseña | **bcrypt, coste 12** |
| `password_verify` con la correcta | **sí** |
| `password_verify` con otra | **no** |
| Permisos del rol | 135 |
| Trigger `after_user_insert` | se disparó sin error; 4 triggers presentes |
| Se niega a duplicar sin `--force` | exit 1 con mensaje claro |
| Rechaza contraseña de menos de 12 | exit 2 |
| Confirma la base destino antes de escribir | exit 2 sin terminal interactiva y sin `--yes` |

---

## 4. Portada comercial

`php tests/landing/check.php` → **46 comprobaciones, 0 fallos**.

Cubre: forma del contenido por defecto (14 claves), las seis secciones con identificador estable, un solo
`<h1>` y cinco `<h2>`, que el ancla del botón apunte a una sección que existe, escape de `<script>` y de
atributos inyectados en los textos editables, ausencia de atributos `style=`, comportamiento con listas
vacías y su estado vacío, y el rechazo de `javascript:`, `data:`, `//host` y etiquetas vacías como
destino de un botón.

| Comprobación de integración | Resultado |
|---|---|
| `GET /` con la portada **desactivada** | **200**, título «Inicio · Lab eMotion», **sin** `landing-hero`: no hay regresión |
| Copia estática de la página completa | 52 900 bytes · 1 `<!DOCTYPE>` · Bootstrap 5.3.3 · 1 hero + 5 secciones · 15 tarjetas · 1 `<h1>` y 5 `<h2>` |
| Errores de PHP filtrados al HTML | 0 (`Warning`, `Fatal`, `Notice`, `Deprecated`, `Uncaught`) |
| `GET /admin/settings/landing/preview` sin sesión | redirige a `/login` |

---

## 5. Contenido editable desde el panel

`php tests/landing/save.php` → **15 comprobaciones, 0 fallos**, contra una base aislada.

| Comprobación | Resultado |
|---|---|
| Guardado válido persiste y se relee | sí |
| Fila de lista incompleta | se descarta, avisa, y no arrastra a las demás (2 de 3 guardadas) |
| Destino `javascript:` en un botón | se rechaza y **conserva** el anterior |
| Guardar sin el interruptor | **no** desactiva la portada |
| Activar / desactivar | funciona |

`php -l` limpio en los cinco archivos tocados.

---

## 6. Sitemap

`php tests/sitemap/check.php` → **17 comprobaciones, 0 fallos**. En seco no modifica el archivo
(comparado por fecha de escritura); con `--write` lo regenera.

| Comprobación | Resultado |
|---|---|
| Ámbito por defecto | `default_menus` → «Menú principal» |
| Los dos ámbitos resuelven a menús distintos | `predeterminados: Menú principal` / `header: Menu top` |
| XML | bien formado; todos los `<loc>` recorridos por `xpath`, absolutos |
| Rutas privadas | `/admin`, `/admin/pages`, `/login`, `/sitemap.xml` excluidas |
| Archivo regenerado | **9 URLs**, sin `/inicio` duplicando a `/` |
| `GET /sitemap.xml` | **200**, 1 317 bytes, 9 `<loc>` |
| `/admin/seo/sitemap` sin sesión | redirige a `/login` |

---

## 7. Pruebas preexistentes del proyecto

| Suite | Resultado |
|---|---|
| `tests/blocks-library` (Node, sin base de datos) | **pasan** (exit 0) |
| `tests/public-theme` | **no se ejecutaron**: escriben en `settings` y en `uploads`, y su README pide una base de copia. No se corrieron contra la base real |

---

## 8. Seguridad de lo nuevo

Revisión de los archivos creados y modificados en este trabajo:

| Control | Estado |
|---|---|
| Consultas preparadas | todas las consultas nuevas usan parámetros enlazados; los fragmentos dinámicos se limitan a `AND location = ?` |
| CSRF | presente en los cuatro POST nuevos (`/admin/settings/landing`, `/admin/sitemap/regenerate`, `/admin/seo/sitemap/settings`) |
| Autorización | `guardManage()` (`settings.manage`) y `guardSitemap()` (`seo.manage`) comprueban en el servidor; **no** se delega en `PermissionMiddleware`, que no está aplicado en ninguna ruta |
| Escape de salida | todo el contenido editable pasa por `e()`; verificado con inyección de `<script>` y de atributos |
| Esquemas peligrosos | `javascript:`, `data:` y `//host` rechazados en botones e imágenes |
| Escritura en disco | ruta constante (`public/sitemap.xml`), escritura atómica con archivo temporal y `rename`; sin entrada de usuario en la ruta |
| Secretos | no se escribe ninguno; el SQL de instalación no contiene hashes ni claves |
| Rutas nuevas | todas detrás de `$auth` |

**Sin hallazgos nuevos en el código de este trabajo.**

---

## 9. Hallazgos preexistentes, registrados y no corregidos

Corregirlos cambia autorización o comportamiento de rutas existentes, así que cada uno merece su propio
cambio con su propia prueba. Quedan anotados como deuda numerada en
`02-DOCS/wiki/sdd/constitution.md` (14–17).

| # | Hallazgo | Severidad | Por qué no se corrigió aquí |
|---|---|---|---|
| S-1 | `/cron/process-expired-bans` no exige credencial y escribe en la base | Media-baja | Cambia autorización de una ruta existente |
| S-2 | Los seis `/cron/*` no pueden autenticar: `cron_token` se regenera en cada carga | Media | Exige decidir de dónde sale el secreto |
| S-3 | `VersionsController` no comprueba ningún permiso | **Alta** | Restaurar versiones es contenido publicado |
| S-4 | `BackupsController::index` y `::create` no comprueban permiso | Media | Afecta a un módulo completo |
| S-5 | El limitador de peticiones degrada a «sin límite» si falla al construirse | Baja-media | Toca `config/routes.php` y el arranque |
| S-6 | `PermissionMiddleware` no está aplicado en ninguna ruta | **Alta** | Es un cambio transversal |

Además, de la revisión de las especificaciones: `/admin/audit/activity` está sombreada por
`/admin/audit/{id}` (el router devuelve la primera coincidencia), y `db/db-full.sql` versionado contiene
hashes y secretos de API de tres aplicaciones.

---

## 10. Limitaciones: lo que **no** se pudo verificar

Se dice sin adornos porque condiciona cuánto vale todo lo anterior.

1. **No hay revisión visual con navegador.** El navegador headless de esta máquina (Edge) no arranca bajo
   el sandbox (`mojo … Access is denied`). La portada se ha verificado por HTML generado, no mirándola.
   Quedan dos caminos para una persona: `storage/preview/portada.html` y `/admin/settings/landing/preview`.
2. **No se probó el guardado por HTTP con sesión.** Las pruebas de guardado llaman al servicio directamente
   contra una base aislada. El camino completo formulario → controlador → servicio no se ejecutó con un
   usuario real.
3. **No se verificó el contraste AA midiendo píxeles.** La portada no fija colores literales: usa los
   tokens que `PublicThemeService` calcula para AA. La medición visual del resultado final está pendiente.
4. **La URL base del sitemap es `http://localhost:170`.** El archivo generado no sirve para buscadores
   hasta que se configure el dominio real. Está avisado en el panel y aplazado por decisión del dueño.
5. **`tests/public-theme` no se ejecutó** (requiere base de datos y escribe en ella).
6. **No hay PHPUnit ni CI.** Las 78 comprobaciones son scripts CLI del propio proyecto.
7. **Las rutas `/lang/{code}`, `/page` y `/page/{slug}` responden 500** por desajuste entre archivo y
   clase. Medido, no corregido.
8. **La copia estática de la portada no carga la hoja de estilos del tema** al abrirla desde el disco,
   porque su ruta es absoluta al servidor. Los colores reales solo se ven en la vista previa con sesión.

---

## 11. Cómo reproducirlo

```powershell
# Pruebas que no necesitan base de datos
php tests/landing/check.php
php tests/sitemap/check.php
node --test tests/blocks-library

# Generar la copia estática de la portada
php tests/landing/snapshot.php

# Regenerar el sitemap de verdad
php tests/sitemap/check.php --write

# Prueba de guardado: SIEMPRE contra una base de pruebas
$env:DB_NAME = "una_base_de_pruebas"
$env:DB_USER = "<usuario>"; $env:DB_PASSWORD = "<contraseña>"
php tests/landing/save.php
```

---

## 12. Veredicto

**VALIDADO CON LIMITACIONES.**

Lo construido funciona y está medido: respaldo restaurable, instalación limpia reproducible, portada
servida sin romper el sitio, contenido editable con validación, sitemap coherente con el menú, y
documentación que se corresponde con el código. Las limitaciones de la sección 10 son reales y acotan la
confianza: sin revisión visual en navegador, sin el camino HTTP completo de guardado y con seis hallazgos
de seguridad preexistentes sin corregir.

**Recomendación antes de publicar:** configurar el dominio real del sitemap, activar la portada solo
después de verla, corregir S-3 y S-6 (autorización), y decidir qué se hace con `cron_token`.

---

## Actualización: segunda ronda de cambios (31 de octubre de 2026)

Lo anterior se refiere al primer paquete. Después se hizo esto, y esto es lo que vale.

### Trabajo añadido

| Qué | Estado | Evidencia |
|---|---|---|
| Contenido de páginas del sistema | **Verificado** | 3 páginas creadas (`/funciones`, `/seguridad`, `/como-funciona`), enlazadas en el menú, las 4 con **un solo `<h1>`**, HTTP 200 |
| Ajuste visual de la portada | **Verificado** | Orden rehecho en el DOM, densidad y color; 46 comprobaciones siguen en verde |
| S-3 · `VersionsController` sin permisos | **Corregido** | Exige `pages.view`, `pages.restore` y `pages.delete` |
| S-6 · `PermissionMiddleware` sin conectar | **Corregido** | Mapa `config/permissions.php`; 184 de 220 acciones protegidas, 36 justificadas, **0 sin clasificar** |
| S-1 · `/cron/process-expired-bans` sin credencial | **Corregido** | Ahora 401 sin token |
| S-2 · `cron_token` inestable | **Corregido** | `CRON_TOKEN` del entorno; `hash_equals` |
| S-4 · `BackupsController::index/create` sin permiso | **Corregido** | Cubierto por el mapa con `backups.manage` |
| S-5 · limitador que fallaba en abierto | **Corregido** | Falla en cerrado con 503 al construir; el fallo de ejecución se registra diciendo que permite la petición |

### Defectos encontrados al verificar (y corregidos en el acto)

1. **Mapear los endpoints de cron a permisos los rompió**: pasaron a exigir sesión y respondían **403**.
   Un cron se autentica por token. Se excluyeron del mapa, con el motivo escrito en el archivo.
2. **`/cron/cleanup-rate-limits` daba 500**: llamaba a `RateLimiter::cleanup()`, que **no existía**. Nunca
   se notó porque el endpoint no era alcanzable (token inestable). Método implementado.
3. **`SecurityDashboardController` estaba sin mapear**: el tablero de seguridad era accesible con solo
   tener sesión. Lo destapó el propio test de cobertura del mapa.
4. **Orden de middlewares**: la comprobación de permiso iba antes que `AuthMiddleware`, así que un
   visitante sin sesión recibía **403** en vez de ir a `/login`. Reordenado; ahora responde 302.
5. **Las páginas nuevas salieron sin `<h1>`**: la columna `h1` existe en la tabla pero el layout no la
   pinta. Corregido en el contenido de las cuatro páginas.
6. **El sitemap anunciaba `/` y `/inicio`**: la misma portada como dos URLs. Normalizado a `/`.
7. **Mi test del sitemap contaba 1 `<loc>` de 9**: `$doc->url->loc` en SimpleXML devuelve solo el primero.
   Cambiado a `xpath`.

### Verificación final

| Batería | Comprobaciones |
|---|---|
| `tests/seguridad/check.php` | **20 · 0 fallos** |
| `tests/landing/check.php` | **46 · 0 fallos** |
| `tests/sitemap/check.php` | **17 · 0 fallos** |
| **Total** | **83 · 0 fallos** |

Smoke por HTTP: `/`, `/funciones`, `/seguridad`, `/como-funciona`, `/sitemap.xml`, `/login` y las rutas
del panel responden. `POST /login` devuelve `X-RateLimit-Limit: 5`, que demuestra que el limitador real
está activo y no el guardián del 503. Cron: sin token los seis responden 401; con el token correcto, los
seis responden 200; con uno incorrecto, 401.

### Estado revisado de los hallazgos de seguridad

| # | Hallazgo | Estado |
|---|---|---|
| S-1 | `/cron/process-expired-bans` sin credencial | **Cerrado** |
| S-2 | `cron_token` inestable | **Cerrado** |
| S-3 | `VersionsController` sin permisos | **Cerrado** |
| S-4 | `BackupsController` sin permisos | **Cerrado** (por el mapa) |
| S-5 | Limitador que fallaba en abierto | **Cerrado** |
| S-6 | Middleware de permisos sin conectar | **Cerrado** |

### Limitaciones que siguen en pie

Sin cambios respecto a la sección 10, y se repiten porque siguen siendo verdad:

1. **No hay revisión visual en navegador** (el headless no arranca bajo el sandbox). La portada está
   verificada por HTML generado.
2. **El 403 de una sesión con rol limitado no se ejecutó**: el mapa y el catálogo de roles están
   verificados, el rechazo real no, porque no hay credenciales de un rol no administrador.
3. **No se probó el guardado de la portada por HTTP**: el servicio está probado contra base aislada.
4. **`CRON_TOKEN` no está en el `.env` de esta máquina**, así que el cron sigue respondiendo 401 hasta
   que se añada. Es el comportamiento correcto, y está avisado en el README y en el manual.
5. Las tres rutas que responden 500 (**deuda 17**) y los secretos de API en `db/db-full.sql`
   (**deuda 16**) siguen abiertos: no se tocaron.

### Veredicto revisado

**VALIDADO CON LIMITACIONES.** Los seis hallazgos de seguridad que estaban abiertos al empezar esta
ronda están cerrados y medidos. Lo que acota la confianza ahora no es la autorización, sino la falta de
revisión visual en navegador, la ausencia de una prueba de rechazo con una sesión de rol limitado, y la
deuda 16 y 17, que no formaban parte del encargo de esta ronda.

---

## Cierre: deuda 17 y estado final (31 de octubre de 2026)

### Deuda 17 — cerrada

| Ruta | Antes | Ahora |
|---|---|---|
| `/admin/components*` (7 rutas) | **500** al entrar | Resuelve la clase; exige `components.manage` |
| `/lang/{code}` | **500** | Cambia el idioma y redirige |
| `/page`, `/page/{slug}` | **500** (`require` de un archivo inexistente) | **200**: alias de `PublicController::home` y `::show` |

Cómo: `git mv` de `ComponentController.php` → `ComponentsController.php` y `LenguageController.php` →
`LanguageController.php` (el cargador busca el nombre exacto de la clase). Ninguna otra parte del código
usaba los nombres viejos, así que no hubo que tocar referencias. Para `/page`, se sustituyó el `require`
roto por alias a las rutas públicas que ya funcionaban, sin duplicar el renderizador.

Cuatro comprobaciones permanentes en `tests/seguridad/check.php` impiden que el desajuste vuelva.

**Sigue abierto:** `/admin/audit/activity` es inalcanzable porque `/admin/audit/{id}` se registra antes y
el router devuelve la primera coincidencia. Es un conflicto de orden de rutas, no de nombres.

### Verificación final de todo el trabajo

| Batería | Comprobaciones |
|---|---|
| `tests/seguridad/check.php` | **24 · 0 fallos** |
| `tests/landing/check.php` | **46 · 0 fallos** |
| `tests/sitemap/check.php` | **17 · 0 fallos** |
| **Total** | **87 · 0 fallos** |

`php -l` sobre los **31 archivos** tocados: **0 errores**.

Smoke por HTTP con el servidor de desarrollo:

```
/ · /funciones · /seguridad · /como-funciona · /page · /page/funciones · /lang/es
/sitemap.xml · /login · /admin · /admin/components · /admin/settings/landing
/admin/seo/sitemap · /cron/process-expired-bans (con token)     → todas responden
```

### Estado de la deuda de la constitución (v1.3.0)

| # | Tema | Estado |
|---|---|---|
| 14 | `PermissionMiddleware` sin conectar | **Cerrada** |
| 15 | Token del cron + endpoint de baneos | **Cerrada** |
| 16 | Secretos de API en `db/db-full.sql` versionado | **Abierta** — rotar esas credenciales rompe integraciones vivas; es decisión del dueño |
| 17 | Rutas por desajuste archivo↔clase | **Cerrada** |
| 7 | Hashes de contraseñas sembradas en el volcado | **Abierta** — el SQL de instalación limpia no los lleva; el volcado versionado sí |

Además siguen abiertos, fuera de la numeración: sin CSP ni HSTS, CDN sin SRI, y
`/admin/audit/activity` inalcanzable.

### Qué significa «funcional y seguro» aquí, sin exagerar

**Funcional:** las rutas responden, la portada se edita desde el panel, el sitemap se genera solo, el
cron funciona con token, y las tres rutas que daban 500 ya no.

**Seguro:** cada acción del panel exige su permiso (184 de 220, con 36 justificadas y 0 sin clasificar);
el cron exige token y falla en cerrado si no está configurado; el limitador falla en cerrado; las
contraseñas son bcrypt coste 12; las consultas son preparadas.

**Lo que sigue sin poder afirmarse:** no hay revisión visual en navegador, el 403 con una sesión de rol
limitado no se ejecutó (no hay credenciales de un rol no administrador), y quedan los dos puntos de
deuda 16 y 7 — secretos en el volcado versionado — que solo el dueño puede cerrar rotando credenciales.

---

## Cierre definitivo: la pantalla de actividad de auditoría (deuda 18)

**El último fallo funcional conocido, corregido.**

`/admin/audit/activity` era inalcanzable. `Router::findRoute()` devuelve la primera coincidencia en orden
de registro, y `/admin/audit/{id}` se registraba antes que la ruta literal, así que la petición acababa en
`AuditController::show` con `id = "activity"` (casteado a 0). El archivo ya tenía un comentario que decía
«literales primero»; el bloque de actividad se había añadido después de las parametrizadas.

Corregido reordenando el bloque: primero todas las literales de `audit` y `activity`, después las
parametrizadas.

### Prueba nueva: `tests/rutas/check.php`

**23 comprobaciones, 0 fallos.** Hace dos cosas:

1. **Resuelve 21 URIs contra el router real** (con `ReflectionMethod` sobre `findRoute`) y comprueba a qué
   acción va cada una. No supone el orden: lo ejecuta.
2. **Recorre todo `config/routes.php`** buscando cualquier literal registrada después de una
   parametrizada que la capture. **No encontró ninguna otra** en el archivo completo, así que este fallo
   era el único de su clase.

### Verificación final acumulada

| Batería | Comprobaciones |
|---|---|
| `tests/rutas/check.php` | **23 · 0 fallos** |
| `tests/seguridad/check.php` | **24 · 0 fallos** |
| `tests/landing/check.php` | **46 · 0 fallos** |
| `tests/sitemap/check.php` | **17 · 0 fallos** |
| **Total** | **110 · 0 fallos** |

### Estado de la deuda (constitución v1.4.0)

| # | Tema | Estado |
|---|---|---|
| 14 | Middleware de permisos sin conectar | Cerrada |
| 15 | Token del cron + endpoint de baneos | Cerrada |
| 17 | Rutas por desajuste archivo↔clase | Cerrada |
| 18 | `/admin/audit/activity` tapada por `{id}` | Cerrada |
| 16 | Secretos de API en `db/db-full.sql` versionado | **Abierta** — rotar credenciales rompe integraciones vivas |
| 7 | Hashes de contraseñas sembradas en el volcado | **Abierta** — el SQL de instalación limpia no los lleva |

**No queda ningún fallo funcional conocido en las rutas.** Lo que sigue abierto son dos decisiones del
dueño sobre secretos en el volcado versionado, y tres endurecimientos de cabeceras (CSP, HSTS, SRI) que no
se tocaron.