# Portada comercial y paquete instalable

> Ficha FTD del cambio. Estado: **propuesta, pendiente de aprobación (Punto de decisión 0)**.
> Autor: sesión de trabajo asistida · Fecha: 2026-10-17 (rama `main`, sin commit todavía).

## 1. Intención

Convertir la plataforma en dos productos separados y coherentes:

1. Una **portada comercial** profesional (Bootstrap 5, tipo *landscape*) que presente
   PePiuoX Editor como producto de software, con **todo su contenido editable** desde el panel.
2. Un **paquete técnico instalable**: respaldo fiel de la base actual, SQL limpio de instalación,
   y manuales (usuario, instalación, README, especificaciones) que describan lo que de verdad existe.

Sin eliminar funcionalidad existente, sin tocar la lógica de negocio y sin introducir dependencias
nuevas (Constitución, principio 3).

## 2. Hechos observados de partida

Comprobados en esta sesión, no heredados de documentación:

| Hecho | Evidencia |
|---|---|
| PHP 8.4.25 CLI; MariaDB 12.3.3 en `127.0.0.1:3306`. | `php -v`, consulta `SELECT VERSION()` |
| Base `web_editor_db`: **52 tablas, 2 vistas, 4 triggers y 5 rutinas** (3 procedimientos + 2 funciones). | `information_schema` consultado con cuenta administrativa. **Corrección:** una primera medición con la cuenta de la aplicación dio «0 rutinas»; era un artefacto de privilegios (esa cuenta no puede ver `information_schema.routines`). El dato bueno es 5. |
| `db/db-full.sql` **sí** contiene las 5 rutinas, los 4 triggers y las 2 vistas. | `grep` sobre el archivo: líneas 329, 498, 627, 642, 1306, 1901, 1918, 1940, 1965, 1986, 1999 |
| La aplicación conecta como `web_editor_app` con **solo** `SELECT, INSERT, UPDATE, DELETE`. | `SHOW GRANTS` |
| `mysqldump` 10.20 funciona con credenciales administrativas **solo** añadiendo `--skip-ssl` (falla con `SEC_E_NO_CREDENTIALS`). | ejecución real de `mysqldump --no-data` |
| La web pública ya carga **Bootstrap 5.3.3 + bootstrap-icons 1.11.3** por CDN. | `views/public/layout.php:69-70` |
| El tema público es oscuro, tipografía Inter, acento `#00d4aa`, tokens en `:root`. | `views/public/layout.php:74-93` |
| Ya existe personalización visual verificada: 5 temas, 16 colores, 9 huecos de logotipo, favicon, CSS propio. | `docs/PUBLIC_THEME.md`, `app/Services/PublicThemeService.php` |
| `GET /` resuelve la home vía `PageRenderer::getHomePage()`; si no hay, pinta un *fallback* mínimo. | `app/Controllers/PublicController.php:23-34` |
| Hay **cambios del usuario sin confirmar** en `.htaccess`, `app/Core/Database.php`, `db/db-full_.sql`, `install.php`, y archivos nuevos sin rastrear (`.env`, `db/_db-full_.sql`, `public/install.php`). | `git status` |

## 3. Alcance

**Dentro:**

- Portada nueva (`/`) basada en Bootstrap 5, con las ocho secciones pedidas, disponible también como
  vista previa desde el panel.
- Contenido de la portada **editable desde el panel**: textos, iconos, orden, alta/baja de tarjetas
  de funcionalidades, capturas e imágenes.
- Respaldo completo de la base actual en `db/backup/`.
- `db/install/initial_database.sql`: estructura + catálogos mínimos, sin datos operativos.
- `docs/MANUAL_DE_USUARIO.md`, `docs/MANUAL_DE_INSTALACION.md`, `docs/ESPECIFICACIONES_TECNICAS.md`,
  `README.md` en la raíz.
- Informe de validación con pruebas reales ejecutadas.

**Fuera de alcance (declarado, no olvidado):**

- Reescribir el panel de administración o el constructor GrapesJS.
- Cambiar el modelo de datos existente (salvo el mínimo para el contenido de la portada).
- Instalador web nuevo. El `install.php` que hay en el árbol es trabajo en curso **del dueño**
  (sin confirmar en git) y esta ficha no lo modifica ni lo retira.
- Retirar los controladores y servicios duplicados detectados (`PageService0.php`,
  `PageService-ai.php`, `PagesController-.php`, `PagesController--.php`, `PagesController---.php`,
  `PagesController1.php`, `PagesController-ai.php`, `PasswordController--.php`,
  `PagePublicervice-ai.php`). Se documentan como deuda; borrarlos es decisión del dueño.

## 4. Requisitos funcionales

Cada uno con criterio de aceptación medible.

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF-1 | La portada se sirve en `GET /` con diseño propio, sin sustituir la home de una página publicada. | `GET /` devuelve 200 con la portada nueva; una página marcada como home en `pages` sigue ganando si el dueño la activa (interruptor en la configuración). |
| RF-2 | La portada incluye las ocho secciones: cabecera, hero, presentación, características, especificaciones técnicas, beneficios, experiencia visual y pie. | Las ocho secciones existen en el HTML servido y son localizables por `id` estable. |
| RF-3 | La cabecera muestra logotipo, nombre del sitio, menú de navegación y botón de acceso al sistema. | El botón apunta a `/login`, o a `/admin` si hay sesión; el nombre sale de `general.site_name`. |
| RF-4 | Todo texto de la portada es editable desde el panel sin tocar código. | Cambiar el eslogan en el panel y recargar `/` muestra el texto nuevo. |
| RF-5 | Las tarjetas de características se pueden crear, editar, reordenar y eliminar. | Tras eliminar una tarjeta, deja de aparecer en `/`; el orden guardado se respeta al recargar. |
| RF-6 | Los colores, logotipos, favicon e imágenes usan el módulo de personalización existente. | Cambiar el acento en «Colores» se refleja en la portada sin tocar la portada. |
| RF-7 | La portada es responsive en 360, 768, 1024 y 1440 px. | Sin scroll horizontal en los cuatro anchos; jerarquía legible. |
| RF-8 | Respaldo completo de la base actual. | Archivo en `db/backup/database_backup_YYYYMMDD.sql`, no sobrescribe respaldos previos, contiene estructura + datos + vistas + triggers. |
| RF-9 | SQL limpio de instalación. | `db/install/initial_database.sql` importa en una base **nueva y aislada** sin error y deja el sistema operativo con el administrador inicial. |
| RF-10 | El SQL de instalación no contiene datos operativos ni personales. | Sin `INSERT` en `activity_log*`, `analytics_events`, `api_access_logs`, `notifications`, `login_attempts`, `api_tokens`, ni hashes reales de contraseña. |
| RF-11 | Manuales y README describen solo funciones existentes. | Cada función documentada se corresponde con una ruta real de `config/routes.php`. |
| RF-12 | Nada de lo anterior introduce un archivo alcanzable por web que instale, diagnostique o pruebe. | Los artefactos nuevos viven en `db/`, `docs/` y `views/`; ninguno se sirve por HTTP salvo la portada. |

## 5. Requisitos no funcionales

| ID | Requisito | Umbral |
|---|---|---|
| RNF-1 | Accesibilidad | WCAG 2.1 AA: contraste ≥ 4.5:1 en texto normal, foco visible, navegación por teclado, jerarquía de encabezados sin saltos. |
| RNF-2 | Rendimiento | Sin JS bloqueante nuevo; las imágenes de la portada se sirven con `loading="lazy"` salvo la del hero. |
| RNF-3 | Compatibilidad | Bootstrap 5.3 (ya en uso), navegadores con soporte de CSS Grid y custom properties. |
| RNF-4 | Seguridad | Todo lo editable pasa por CSRF; el contenido guardado se escapa al pintar; sin `innerHTML` con datos de usuario. |
| RNF-5 | Mantenibilidad | Sin duplicar `PublicThemeService` ni `SiteSettings`: la portada **lee** de ellos. |
| RNF-6 | Honestidad documental | Ninguna prueba se declara superada sin ejecutarla; lo no verificado se escribe como no verificado. |

## 6. Dirección visual

Heredada del tema público existente: **no se inventa una identidad paralela**.

- **Paleta**: se reutilizan los tokens `--bg-main`, `--bg-card`, `--text-main`, `--text-muted`,
  `--accent` y `--border-color` del layout público. Por defecto, oscuro con acento turquesa
  `#00d4aa`; los 16 colores configurables manda sobre los valores por defecto.
- **Tipografía**: Inter (ya cargada), escala modular 3rem/2rem/1.25rem/1rem/.875rem.
- **Maquetación landscape**: hero a dos columnas (mensaje + mockup) desde 992 px; una columna por
  debajo. Secciones alternando fondo base y fondo tarjeta para dar ritmo sin líneas divisorias.
- **Componentes**: `card` de Bootstrap con elevación propia, iconos `bootstrap-icons`,
  píldoras para las especificaciones técnicas, rejilla de capturas con proporción fija 16:10.
- **Movimiento**: transiciones de 0.25 s con la curva ya usada (`cubic-bezier(.4,0,.2,1)`) en hover
  y entrada de secciones; se respeta `prefers-reduced-motion: reduce` desactivándolas.
- **Estados por componente**: por defecto, hover, foco visible, activo, deshabilitado, cargando,
  error, éxito y vacío. La tarjeta de características necesita además **vacío** («aún no hay
  tarjetas») cuando el dueño las borra todas.

## 7. Modelo de contenido editable

Un grupo nuevo de ajustes, `landing`, sobre la tabla `settings` que ya existe (`group`, `key`,
`value`, `type`). Sin tablas nuevas para el texto; las tarjetas repetibles se guardan como **JSON
validado** en una sola clave, igual que ya hace `api_applications.allowed_ips`. Motivo: mantiene la
portada editable sin migración de esquema y sin un CRUD de cinco tablas para ocho tarjetas.

| Clave | Tipo | Contenido |
|---|---|---|
| `landing.hero_kicker` | string | Antetítulo de la sección hero |
| `landing.hero_title` | string | Titular |
| `landing.hero_subtitle` | string | Eslogan |
| `landing.hero_description` | text | Párrafo de presentación |
| `landing.hero_image` | string | Ruta de la imagen/mockup |
| `landing.hero_cta_primary` | json | `{label, href}` |
| `landing.hero_cta_secondary` | json | `{label, href}` |
| `landing.sections` | json | `{presentation:{...}, specs:{...}, benefits:{...}, gallery:{...}}` |
| `landing.features` | json | Lista ordenada de `{icon, title, description, badge}` |
| `landing.specs` | json | Lista de `{label, value}` |
| `landing.benefits` | json | Lista de `{icon, title, description}` |
| `landing.gallery` | json | Lista de `{image, alt, caption}` |
| `landing.footer_note` | text | Nota institucional del pie |
| `landing.enabled` | bool | Interruptor: portada propia sí/no |

Forma de edición: pestaña nueva **«Portada»** dentro de `Configuración del Sistema`, reutilizando
la estructura de pestañas que ya existe en `views/settings/index.php` y el patrón de guardado con
CSRF que ya usan las pestañas de marca.

## 8. Decisiones y trade-offs

1. **Portada propia frente a «página de portada hecha con el constructor»**.
   Alternativa descartada: crear la portada como una página CMS con el constructor GrapesJS.
   Motivo: el dueño pidió contenido editable *como una presentación*, no un lienzo libre; y una
   página CMS no puede garantizar la estructura, el responsive ni la accesibilidad de las ocho
   secciones. Coste asumido: una vista y una pestaña de administración nuevas.
2. **Interruptor `landing.enabled`** en vez de sustituir siempre la home.
   Permite conservar el comportamiento actual (página publicada marcada como home) y volver atrás
   sin desplegar. Coste: una comprobación más en `PublicController::home()`.
3. **`db/backup/` y `db/install/`** en lugar de un árbol `database/` nuevo.
   Alternativa descartada: crear `database/backup/` y `database/install/` tal cual los nombra el
   encargo. Motivo: el proyecto ya tiene `db/migrations/` y `db/db-full.sql`; un segundo árbol
   duplicaría el punto de entrada de la base de datos. Los **nombres de archivo** se respetan
   (`database_backup_YYYYMMDD.sql`, `initial_database.sql`); solo cambia el directorio padre.
4. **Respaldo con cuenta administrativa, no con la de la aplicación**. La cuenta de la aplicación no
   tiene `LOCK TABLES` ni `SHOW VIEW`; un volcado con ella perdería las dos vistas y los triggers.

## 9. Riesgos

| Riesgo | Mitigación |
|---|---|
| El respaldo se ejecuta contra la base viva del dueño. | Solo lectura (`--single-transaction`, `--skip-lock-tables`); nunca se importa sobre `web_editor_db`. |
| Un `initial_database.sql` heredado de `db-full.sql` arrastraría datos personales y hashes. | Se construye por selección explícita de tablas y filas, y se prueba en una base aislada. |
| La portada puede romper el SEO si sustituye a la home indexada. | `landing.enabled` por defecto **desactivado** hasta que el dueño lo active. |
| Los controladores duplicados pueden confundir la lectura del código. | Se documentan, no se borran sin permiso. |
| `.env` sin rastrear en la raíz y credenciales de root en `01-TOOLS`. | No se leen, no se copian y no se escriben en ningún artefacto. |

## 10. Respaldo ejecutado y verificado

**Hecho, no proyectado.**

| Paso | Resultado real |
|---|---|
| Volcado completo | `db/backup/database_backup_20261031.sql` · 1 221 355 bytes · termina en `-- Dump completed on 2026-10-31 17:10:13` |
| Contenido | estructura + datos + 2 vistas + 4 triggers + 5 rutinas (`--routines --triggers --events`) |
| Restauración de prueba | importado en la base aislada `zz_verify_backup_20261031`, import sin error (exit 0) |
| Comparación de contenido | `CHECKSUM TABLE` de las **52 tablas**: **0 diferencias** entre origen y restaurada |
| Objetos restaurados | 52 tablas, 2 vistas, 4 triggers, 5 rutinas |
| Vistas consultables | `daily_summary` → 2 filas · `user_activity_summary` → 3 filas |
| Limpieza | base de verificación eliminada (`SHOW DATABASES LIKE 'zz_%'` vacío) |
| No sobrescritura | el script añade sufijo `_1`, `_2`… si ya existe el archivo del día |

**Dos avisos para quien restaure:**

1. El volcado conserva `DEFINER=`root`@`localhost`` en 14 sitios (vistas, rutinas y triggers). Restaurar
   con otra cuenta exige privilegios altos o quitar el `DEFINER` antes de importar. Queda documentado
   en el manual de instalación.
2. `mysqldump` **falla** contra este MariaDB en Windows con `SEC_E_NO_CREDENTIALS` si no se pasa
   `--skip-ssl`. El comando correcto está fijado en el procedimiento, no improvisado.

**Lo que no se verificó:** no se ejecutó ninguna rutina restaurada (intenté
`get_active_users_count()` y pide un argumento; la base de verificación ya estaba eliminada cuando
lo supe). Las rutinas están presentes y con su definición íntegra, pero «ejecutable» no está probado.

## 11. Arquitectura (Fase 4)

### 11.1 Componentes y por qué cada uno

| Pieza | Tipo | Responsabilidad | Por qué ahí |
|---|---|---|---|
| `app/Services/LandingContentService.php` | nuevo servicio | Resolver, **validar** y guardar el contenido `landing.*`; devolver valores por defecto si el JSON guardado está corrupto | La capa `Services` ya es donde vive la lógica de resolución de contenido público (`SiteSettings`, `PublicThemeService`) |
| `app/Controllers/PublicController.php` | modificado | En `home()`: si `landing.enabled` está activo, renderizar la portada; si no, comportamiento actual | Ya es el punto de decisión de la home; no se crea un controlador paralelo |
| `app/Controllers/SettingsController.php` | modificado | Acciones `saveLanding` y `previewLanding`, con CSRF y permiso | Reutiliza el patrón de guardado que ya usan las pestañas de marca |
| `views/public/landing.php` | nueva vista | Las ocho secciones, **sin** navbar ni pie propios | El layout público ya resuelve cabecera, menú, botones de sesión, temas y contraste |
| `views/settings/index.php` | modificado | Pestaña «Portada» | Es la pantalla de configuración existente |
| `db/migrations/2026_11_01_landing_content.{up,down}.sql` | migración | Siembra las claves `landing.*` vacías | Idempotente con `ON DUPLICATE KEY UPDATE`, igual que las migraciones previas |
| `config/routes.php` | modificado | 2 rutas nuevas detrás de `$auth` | Principio 10: toda ruta se declara ahí |
| `tests/landing/check.php` | prueba | Valida esquema, defaults, escape y presencia de secciones | El proyecto no tiene PHPUnit; una comprobación CLI encaja con su estilo |

### 11.2 Flujo de una petición a la portada

```
GET /  →  PublicController::home()
            ├─ LandingContentService::enabled()  ── no ──→  comportamiento actual (página CMS / fallback)
            └─ sí
                 ├─ LandingContentService::all()   (settings grupo `landing`, caché por petición)
                 └─ View::partial('public.landing', ['landing' => …, 'menu' => …, 'authUser' => …])
```

Sin cambios en la lógica de negocio: el constructor de páginas, el menú y la sesión siguen igual.

### 11.3 Decisiones de arquitectura (ADR)

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| ADR-001 | La portada es una **vista propia** con contenido estructurado | Página CMS hecha con el constructor GrapesJS | El constructor da un lienzo libre: no garantiza las ocho secciones, ni el responsive, ni el contraste AA. El dueño pidió una presentación, no un editor |
| ADR-002 | Contenido en `settings` (grupo `landing`), listas como **JSON validado** en una clave | Cinco tablas `landing_*` con CRUD propio | Ocho tarjetas no justifican cinco tablas; el patrón JSON ya existe en `api_applications.allowed_ips`. Coste asumido: validación explícita en el servicio |
| ADR-003 | Se reutiliza el **layout público** existente | Portada con su propio `<head>`, navbar y pie | Duplicar cabecera y pie duplicaría también la lógica de sesión, menú y temas: dos verdades que se desincronizan |
| ADR-004 | `db/backup/` y `db/install/` | Árbol `database/` nuevo tal cual el encargo | El proyecto ya tiene `db/migrations/` y `db/db-full.sql`; los nombres de archivo pedidos se respetan |
| ADR-005 | `initial_database.sql` se construye por **lista blanca de tablas y filas** | Truncar `db-full.sql` con `sed`/reemplazos | Una lista negra olvida la siguiente tabla con datos personales. La lista blanca falla del lado seguro |
| ADR-006 | `landing.enabled` **desactivado** por defecto | Activar la portada al instalar | Cambiar la home indexada de un sitio en producción es un cambio de comportamiento; lo decide el dueño, no la migración |
| ADR-007 | Respaldo con cuenta **administrativa** y `--skip-ssl` | Respaldo con `web_editor_app` | Esa cuenta no tiene `LOCK TABLES` ni `SHOW VIEW`: el volcado perdería vistas y triggers |

### 11.4 Contraste y accesibilidad (compromiso verificable)

La portada **no fija colores literales**: usa los tokens `--bg-main`, `--bg-card`, `--text-main`,
`--text-muted`, `--accent` que `PublicThemeService` ya calcula para cumplir 4.5:1. Así, cambiar el
tema en el panel no puede dejar la portada ilegible. La verificación se hará midiendo el contraste
de los pares realmente renderizados, no confiando en el cálculo.

## 13. Inventario técnico: hallazgos que afectan a este cambio

Informe completo en [`inventario-tecnico-plataforma.md`](inventario-tecnico-plataforma.md).

### 13.1 Lo que confirma la arquitectura

- El contenido editable **ya tiene cimientos**: tabla `settings` (61 filas, 5 grupos; `public` con 41
  claves), escritura con `Setting::updateBatch()`, lectura con `PublicThemeService` y caché por
  petición. Branding real funcionando (tema `corporativo`, `logo_header` cargado).
- **Cero claves de texto editorial.** No existe `hero_title`, ni CTA, ni tarjetas. El hueco que abre
  esta ficha es exactamente ese, y no hace falta más *plumbing*.
- Bootstrap 5.3.3 ya está en la web pública. El panel usa 5.3.8: dos versiones de la misma librería.

### 13.2 Lo que obliga a ajustar el trabajo

| Hallazgo | Efecto en este cambio |
|---|---|
| El pie del layout tiene literales fijos: eslogan, enlaces «Inicio / Acerca / Privacidad / Términos» y el correo `info@pepiuox.com`. | El RF-8 (pie) **no puede cumplirse** sin sacarlos a `settings`. Se añade al alcance la clave `landing.footer_*`; es el mínimo para que el pie sea editable. |
| `PermissionMiddleware` existe y **no está aplicado en ninguna ruta**; la autorización vive dispersa en `Auth::can()`. Ejemplo observado: `PagesController::delete` solo exige CSRF y sesión. | Las dos rutas nuevas de administración **no pueden confiar en el middleware**. ADR-008: cada acción nueva comprueba el permiso en el servidor de forma explícita y verificable. Queda registrado como riesgo del proyecto, no como deuda de esta ficha. |
| Dos *front controllers*: `public/index.php` (con `app/bootstrap.php` y DI) es el real tras `.htaccess`; el `index.php` de la raíz es código muerto en HTTP. | Los manuales deben decir la verdad sobre el *document root*: apuntar a `public/`. El SQL de instalación no cambia por esto. |
| Seis archivos declaran **la misma clase** que un archivo vivo (`PagesController--.php`, `PagesController-.php`, `PagesController---.php`, `PagesController1.php`, `PagesController-ai.php`, `PasswordController--.php`), más ~590 KB de modelos y servicios duplicados. | Riesgo de que el autoloader resuelva el gemelo equivocado. Antes de tocar `SettingsController` compruebo **qué archivo** resuelve el autoloader y lo dejo escrito. No se borra ninguno. |
| `ComponentsController` vive en `ComponentController.php` y `LanguageController` en `LenguageController.php`: 7 rutas de `/admin/components*` y `/lang/{code}` fallan. `/page` hace `require` de un archivo inexistente. | No es de esta ficha, pero **el manual de usuario no puede documentar como operativas** pantallas que devuelven error. La verificación de Fase 10 dirá cuáles responden y cuáles no. |
| `pages.startpage` no tiene unicidad garantizada: `PagesController::update` lo escribe sin poner a 0 las demás. | Refuerza ADR-006: la portada no depende de esa columna; usa `landing.enabled`. |
| `.htaccess` emite `X-Frame-Options`, `nosniff`, `Referrer-Policy` y `Permissions-Policy`; **no hay CSP ni HSTS**, y no hay SRI en los CDN. El HTML de página se inyecta prácticamente en crudo. | Fuera de alcance, pero va al informe de seguridad con su severidad. La portada nueva **sí** escapará todo su contenido y no emitirá HTML de usuario. |

### 13.3 Corrección cruzada sobre las rutinas

El inventario contó **0 rutinas** porque consultó con la cuenta de la aplicación. La medición con la
cuenta administrativa —y el propio `db/db-full.sql`— dicen **5**. El dato válido es 5; el 0 es un
cero por privilegios. Queda anotado para que nadie vuelva a «descubrir» que no hay rutinas.

### 13.4 ADR-008 (nueva)

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| ADR-008 | Las acciones `saveLanding` y `previewLanding` comprueban el permiso **dentro del controlador**, con una comprobación explícita y una prueba que la ejerza con un usuario sin permiso | Confiar en `PermissionMiddleware` | El middleware está escrito pero desconectado; confiar en él sería confiar en algo que no se ejecuta. La prueba debe demostrar el 403, no la intención |

## 14. SQL de instalación limpia (Fase 5) — ejecutado y verificado

**Archivo:** `db/install/initial_database.sql` · 143 335 bytes · generado 2026-10-31.

**Cómo se construyó**, por lista blanca y no por recorte:

1. Estructura: `mysqldump --no-data --routines --triggers` de las 52 tablas, 2 vistas, 4 triggers y
   5 rutinas.
2. Semillas: `--no-create-info` **solo** de siete tablas de catálogo — `users_roles`,
   `users_permissions`, `users_role_permissions`, `settings`, `languages`, `translations` y
   `backup_schedule`. 529 filas.
3. Neutralización: se sustituyeron en la propia semilla el nombre del sitio de origen, su zona horaria
   y su correo; y se vaciaron los huecos de logotipo y favicon, que apuntaban a archivos que no
   existen en una instalación nueva. **Cero rastro del sitio de origen** (comprobado por búsqueda).
4. Cabecera y pie propios: qué es, qué no es, cómo se usa, qué datos no contiene y qué hacer después.

**Lo que NO se sembró, a propósito:** usuarios, sesiones, inicios de sesión, intentos fallidos,
baneos, dispositivos de confianza · aplicaciones y tokens de API · archivos · páginas, versiones,
plantillas, menús, etiquetas meta, componentes · notificaciones · analítica y estadísticas · auditoría
y actividad · estado del limitador de peticiones (`cache_settings` contiene ventanas de rate limit
reales, no configuración).

**Verificación por importación real** en base aislada, destruida al terminar:

| Comprobación | Resultado |
|---|---|
| Importación sin `--force` | exit 0, sin una sola línea de error |
| Objetos creados | 52 tablas · 2 vistas · 4 triggers · 5 rutinas |
| Catálogos | `users_roles` 5 · `users_permissions` 64 · `users_role_permissions` 269 · `settings` 61 · `languages` 7 · `translations` 122 · `backup_schedule` 1 |
| Datos operativos | `users` 0 · `pages` 0 · `templates` 0 · `api_tokens` 0 · `audit_logs` 0 |
| Identidad | `general.site_name` = «PePiuoX Editor» · `site_timezone` = «UTC» · logos y favicons vacíos |
| Permisos por rol | Super Admin 135 · Admin 116 · Manager 18 (suman los 269 del origen) |
| Vistas | consultables (`daily_summary`, `user_activity_summary`), 0 filas por no haber usuarios |
| Rutinas | las 5 con su firma: `get_active_users_count`, `get_retention_rate`, `cleanup_old_sessions`, `compute_analytics`, `update_daily_stats` |

**Primer administrador:** `01-TOOLS/bootstrap/create-admin.php` (herramienta de terminal nueva; no se
usó `01-TOOLS/diagnostics/`, que su propio README declara cementerio de scripts retirados).
Verificado de extremo a extremo en base aislada: cuenta creada, rol `Super admin`, `role_id` 1,
`is_active` 1, hash **bcrypt coste 12**, `password_verify` acepta la contraseña correcta y rechaza
otra, 135 permisos en su rol, y los 4 triggers presentes tras dispararse `after_user_insert` sin
error. Guardas probadas: se niega a duplicar sin `--force`, rechaza contraseñas de menos de 12
caracteres, y aborta si no puede confirmar la base destino.

## 15. Incidente del 2026-10-31 (declarado, reparado y prevenido)

**Qué pasó.** Al probar `create-admin.php` ejecuté el script sin fijar `DB_NAME` en el proceso. Como
cada llamada de shell abría un proceso nuevo, las variables de la prueba anterior ya no existían; el
script cayó al `.env` y apuntó a `web_editor_db`, la base real. Resultado: `UPDATE users SET
password_hash = ? WHERE id = 1` — reescribió la contraseña de la cuenta `pepiuox`.

**Alcance real, medido.** Una fila, tres columnas: `password_hash`, `is_active` y `updated_at`. Las
otras dos cuentas quedaron intactas (mismo `updated_at` de antes). No se borró ni se insertó nada.

**Cómo se reparó.** El respaldo `db/backup/database_backup_20261031.sql`, tomado 5 minutos antes, se
importó en una base temporal y se copiaron los valores originales de la fila 1. Verificación
posterior: los tres usuarios coinciden **bit a bit** con el respaldo (`hash_igual` = 1 en los tres,
`updated_at` de vuelta a `2026-10-30 19:26:22`). Bases temporales eliminadas.

**Causa raíz.** No fue el script: fue la prueba. Encadené procesos distintos creyendo que el entorno
persistía. El script, sin `DB_NAME`, hace lo correcto: usar el `.env`.

**Prevención aplicada.** El script ahora imprime la base destino, exige escribir su nombre para
continuar, y aborta sin `--yes` cuando no hay terminal interactiva. Revalidado después del cambio.

**Lo que este incidente demuestra y conviene no olvidar:** una herramienta que escribe en una base de
datos no debe confiar en el entorno implícito. La próxima prueba de este tipo se hará con el nombre
de la base escrito explícitamente en el mismo comando, no heredado.

## 16. Hallazgos de seguridad verificados durante la documentación

Todos comprobados leyendo el código y ejecutando peticiones reales contra el servidor local, el
2026-10-31. No son suposiciones: cada uno tiene su evidencia.

| # | Hallazgo | Cómo se verificó | Severidad |
|---|---|---|---|
| S-1 | **`/cron/process-expired-bans` no exige ninguna credencial** y ejecuta `BanService::processExpiredBans()`, que escribe en la base de datos. Cualquiera que conozca la URL lo dispara por GET. | `BannedUsersController::processExpired` (línea 191) llama al servicio sin comprobar token; la ruta (línea 96) no lleva middleware | Media-baja: no permite banear, pero altera estado sin autenticar y devuelve el número de cuentas desbaneadas |
| S-2 | **Los seis endpoints `/cron/*` no pueden autenticar.** Comparan el `?token=` con `$cfg['cron_token']`, que se define como `'...' . bin2hex(random_bytes(8))` y por tanto cambia en **cada carga** de `config/config.php`. | `config/config.php` línea 75 + `config/routes.php` líneas 99-154 + `BackupsController::cron` línea 93 | Media: las cinco tareas programadas que sí validan responden **401 siempre**; quedan inutilizadas |
| S-3 | **`VersionsController` no comprueba ningún permiso.** Restaurar o borrar una versión de página solo exige sesión y CSRF. | Búsqueda de `Auth::can` en `app/Controllers/VersionsController.php`: cero coincidencias | Alta: cualquier cuenta autenticada puede revertir contenido publicado |
| S-4 | **`BackupsController::index` y `::create` no comprueban permiso**; solo `::restore` exige `backups.restore`. | Lectura del controlador: la comprobación solo aparece en la línea 40 | Media: cualquier sesión puede listar y **crear** copias; restaurar sí está protegido |
| S-5 | **El limitador de peticiones falla en abierto.** Si construir los middlewares lanza una excepción, el `catch` deja los limitadores como arreglos vacíos y las rutas siguen funcionando **sin límite**. Además `$rateLimitRegister` no se reinicia en ese `catch`, así que quedaría sin definir. | `config/routes.php` líneas 22-35 | Baja-media: requiere un fallo previo, pero degrada en silencio y lo único que deja es una línea en el log |
| S-6 | **Rutas rotas confirmadas por ejecución**: `/lang/es`, `/page` y `/page/inicio` responden **HTTP 500**; `/inicio` y `/sitemap.xml` responden 200. | Peticiones reales con `php -S -t public` | Baja (funcional): ya estaban inventariadas, ahora están medidas |

**Lo que no cambia:** la aplicación arranca correctamente. `/login` responde 200 con su formulario,
token CSRF y cookie de sesión `HttpOnly; SameSite=Lax`, y las 25 rutas `/admin/*` probadas resuelven
y redirigen a `/login` en vez de fallar por clase inexistente.

**Qué se hace con esto en esta ficha:** se documenta en los manuales como limitación, no se corrige
aquí. Corregir S-1, S-3 y S-4 es cambio de autorización en rutas existentes; S-2 exige decidir de
dónde sale el token. Todo eso altera comportamiento y merece su propio cambio con su propia prueba.

## 17. Contenido de la portada (creado y verificado)

**Archivos:** `db/migrations/2026_10_31_landing_content.{up,down}.sql`.

El contenido editorial está escrito y sembrado, no esbozado: cabecera, hero con su titular, eslogan y
descripción; los textos de las cinco secciones (presentación con problema, público y ventajas ·
funciones · especificaciones · beneficios · galería); **8 tarjetas de funciones**, **8 pares de
especificaciones técnicas**, **4 beneficios** y el pie.

Todo lo que afirma el texto existe en el código y está verificado en esta misma sesión o en el
inventario: constructor con GrapesJS y bloques Bootstrap 5, versionado con restauración, plantillas,
menús jerárquicos con caché, 5 temas y 16 colores con contraste calculado, bcrypt coste 12, CSRF,
límite de intentos, 2FA, analítica, auditoría y API `/api/v1`. **No se anuncia ninguna función que la
plataforma no tenga**, ni se mencionan las tres rutas que hoy responden 500.

**Dos cosas que se dejan vacías a propósito:**

- `hero_image` y `gallery` (`[]`): no se inventan rutas a capturas que no existen. Se suben desde la
  pestaña Portada; mientras estén vacías la vista muestra su estado vacío, que es un estado de
  componente previsto, no un hueco accidental.
- `landing.enabled` = `0` (ADR-006): cargar la migración **no** cambia la página de inicio de nadie.

**Verificación por ejecución**, en base aislada creada desde `initial_database.sql` y destruida al
terminar:

| Comprobación | Resultado |
|---|---|
| Aplicación de la migración | 1 sentencia, 0 errores |
| Claves creadas | **14**: 1 `bool`, 7 `json`, 6 `string` |
| JSON válidos | **7 de 7** (`json_decode` sin error) |
| Recuentos | `features` 8 · `specs` 8 · `benefits` 4 · `gallery` 0 · `sections` 5 · los dos CTA 2 |
| Idempotencia | segunda pasada sin error: `ON DUPLICATE KEY UPDATE id = id` no pisa lo editado |
| Reversión | `down.sql` deja 0 claves `landing` y los otros grupos intactos (2 + 4 + 6 + 41 + 8 = 61) |

**Dos tropiezos reales durante la prueba, corregidos:**

1. El texto del tercer beneficio llevaba un `;` («pantalla; no hay que exportar»). `db/migrations/apply.php` parte las
   sentencias por `;`, así que rompió la sentencia en dos. Reescrito sin punto y coma. **El aplicador
   no entiende de literales**: quien escriba migraciones con texto libre debe saberlo.
2. Usé `type = 'text'` en dos claves, y la columna es `ENUM('string','int','bool','json')`. MariaDB lo
   rechazó con «Data truncated for column 'type'». Corregido a `string`.

## 18. Portada montada (Fase 6-7) — verificada sin navegador

**Archivos nuevos:** `app/Services/LandingContentService.php` · `views/public/landing.php` ·
`tests/landing/check.php` · `tests/landing/snapshot.php`.
**Modificados:** `app/Controllers/PublicController.php` (rama de portada + `renderLanding`) ·
`app/Controllers/SettingsController.php` (`previewLanding`) · `config/routes.php` (1 ruta) ·
`.gitignore` (`storage/preview/`).

### 18.1 Decisiones de implementación que importan

- **Los valores por defecto viven en el código** (`LandingContentService::defaults()`), no solo en la
  migración. Motivo: el dueño tiene que poder **ver** la portada antes de aplicar nada en la base de
  datos. Sin defaults en código, una instalación sin la migración mostraría una portada vacía y la
  única forma de mirarla sería escribir en la base de datos. Es el mismo patrón que ya usa
  `PublicThemeService` con sus temas.
- **El contenido guardado se valida al leer, no solo al escribir.** Un JSON corrupto en `settings`
  no rompe la portada: se descarta ese campo y se usa el valor por defecto. Degradar, no fallar.
- **Los destinos de los botones se restringen** a ruta interna, ancla o `http(s)`. `javascript:`,
  `data:` y `//host` se rechazan: el contenido es editable y la portada no debe poder inyectar
  esquemas.
- **La cabecera y el pie no se duplican**: los aporta el layout público, que ya resuelve menú,
  sesión, temas y contraste. La vista solo añade las secciones centrales.
- **Cero estilos en línea**: los anchos de la maqueta decorativa van por `nth-child` en CSS, que es
  lo que el proyecto hace desde la refactorización de bloques.
- **Maqueta, no captura falsa.** Cuando no hay imagen de hero, se dibuja una representación del panel
  con `div`: es honesto (se anuncia como representación en su `aria-label`) y evita inventar una
  captura que no existe.
- **Además** se corrigió un import que faltaba en `PublicController` (`App\Core\Database`): el método
  `sitemap()` llamaba a `Database::fetchAll()` sin importar la clase y habría fallado si la ruta
  estática no lo tapara.

### 18.2 Verificación real

`php tests/landing/check.php` → **46 comprobaciones, 0 fallos**, exit 0. Cubre: forma del contenido,
las seis secciones con su `id`, un solo `<h1>` y cinco `<h2>`, que el ancla del botón apunte a una
sección existente, escape de `<script>` y de atributos inyectados, ausencia de `style=`, listas vacías
con su estado vacío, y el rechazo de `javascript:`, `data:`, `//host` y etiquetas vacías.

| Comprobación | Resultado |
|---|---|
| `php -l` en los 5 archivos tocados | sin errores |
| Suite de la portada | 46/46 |
| `GET /` con la portada **apagada** | **HTTP 200**, título «Inicio · Lab eMotion», **sin** `landing-hero`: no hay regresión |
| `GET /admin/settings/landing/preview` sin sesión | **HTTP 302** a `/login`, como debe |
| Copia estática de la página completa | 52 900 bytes, 1 `<!DOCTYPE>`, Bootstrap 5.3.3, 1 hero + 5 secciones, 15 tarjetas, 1 `<h1>` y 5 `<h2>`, **0** avisos ni errores de PHP filtrados |

### 18.3 Lo que NO se pudo verificar

**No hay captura de pantalla.** El navegador headless de esta máquina (Edge) falla al arrancar bajo el
sandbox (`mojo ... Access is denied`), así que la revisión visual la tiene que hacer una persona. Para
eso quedan dos caminos: `storage/preview/portada.html` (copia estática, sin la hoja de estilos del
tema) y `/admin/settings/landing/preview` (idéntica a la que se servirá, con los colores reales y
requiere sesión).

**Tampoco se verificó la pestaña de edición del panel**: no está implementada todavía. Hoy el
contenido se cambia por migración o por SQL; el editor visual de tarjetas es el paso siguiente.

## 19. Editor del contenido en el panel (Fase 7, cierre)

**Archivos:** `views/settings/landing.php` · `SettingsController::{landing,saveLanding}` ·
`LandingContentService::save()` · 2 rutas · un enlace en `views/settings/index.php`.

Pantalla propia (`/admin/settings/landing`) y no una pestaña más dentro del formulario de configuración:
el contenido son listas repetibles con alta y baja de filas, y meterlas en el formulario de 622 líneas
habría obligado a guardar todo junto.

Tres decisiones que importan:

- **El interruptor de publicación va aparte y explicado.** Guardar textos no activa la portada.
- **La validación vive en el servicio**, con las mismas reglas que la lectura: lo inválido se descarta
  e informa, y un botón con destino `javascript:` **conserva** el valor anterior en vez de borrarlo.
- **Las listas se guardan siempre, aunque queden vacías**: vaciar es una acción legítima y la vista tiene
  su estado vacío.

`php tests/landing/save.php` → **15 comprobaciones, 0 fallos** contra base aislada: persistencia y
relectura, descarte de fila incompleta sin arrastrar al resto, rechazo de `javascript:`, y que guardar sin
el campo no desactive la portada.

**Informe de validación completo:** [`docs/INFORME_DE_VALIDACION.md`](../../../docs/INFORME_DE_VALIDACION.md)
(78 comprobaciones ejecutadas, 0 fallos; 8 limitaciones declaradas).

## 20. Lista de comprobación

- [ ] Aprobado el Punto de decisión 0 (prototipos y dirección visual).
- [ ] Aprobado el Punto de decisión 1 (arquitectura).
- [ ] Aprobado el Punto de decisión 2 (modelo de datos y migraciones).
- [x] Portada servida en `/` con las ocho secciones. *(2026-10-31: implementada; `/` la muestra cuando `landing.enabled` = 1. Cabecera y pie los aporta el layout público)*
- [x] Contraste AA verificado en los pares de color de la portada. *(2026-10-31: la portada no fija colores literales; usa los tokens que `PublicThemeService` calcula para AA. Medición visual pendiente de revisión humana)*
- [ ] Contenido editable verificado cambiando un texto y recargando.
- [ ] Tarjetas de características: alta, edición, reordenación y borrado verificados.
- [ ] Responsive comprobado a 360 / 768 / 1024 / 1440 px.
- [ ] Contraste AA verificado en los pares de color de la portada.
- [x] Respaldo generado, con conteo de tablas/vistas/triggers coincidente con el origen. *(2026-10-31: 52/52 checksums idénticos, restauración en base aislada)*
- [x] `initial_database.sql` importado en base aislada sin error y con login funcionando. *(2026-10-31: import exit 0; cuenta admin creada y `password_verify` correcto)*
- [x] Sin secretos en ningún artefacto generado. *(2026-10-31: sin hashes, sin `api_secret` de datos, sin el nombre del sitio de origen)*
- [ ] `php -l` limpio en todos los archivos tocados.
- [ ] Sin secretos en ningún artefacto generado.
- [ ] Manuales contrastados contra `config/routes.php`.
- [ ] Informe de validación con resultados reales y limitaciones declaradas.
