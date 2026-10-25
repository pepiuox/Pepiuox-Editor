---
author: Pepiuox
branch: main
status: in-progress
---
# Sistema de menús: implementación y pruebas

> Continúa [menus-diagnostico-y-diseno.md](menus-diagnostico-y-diseno.md). Aquí está lo que se cambió, cómo se
> aplica la migración, los resultados de las pruebas y **un cambio visible que conviene revisar** (sección 6).

## 1. Cambios de esquema (migración `2026_10_25_menu_system`)

| Tabla | Cambio |
|---|---|
| `menus` | `+ is_default` (menú automático), `+ is_primary` (sustituye al automático en su `location`) |
| `page_menu` | `+ menu_id` (FK → `menus.id`, ON DELETE CASCADE), `UNIQUE (menu_id, slug)` y `UNIQUE (menu_id, page_id)`, FK de `parent_id` → `page_menu.id` (ON DELETE CASCADE); se retira el `UNIQUE` global de `slug` |
| `pages` | `+ menu_id` (FK → `menus.id`, ON DELETE SET NULL) |

Backfill sin pérdida: todo lo que había en `page_menu` pasa al menú `main`, que queda marcado `is_default = 1`.
Respaldo previo en `storage/backups/menus_<fecha>.json` (2 menús + 6 ítems).

```bash
php db/migrations/apply.php --admin 2026_10_25_menu_system.up.sql     # aplicar
php db/migrations/apply.php --admin 2026_10_25_menu_system.down.sql   # revertir
```

`--admin` es obligatorio: el usuario de la aplicación (`web_editor_app`) no tiene `ALTER`, y no debe tenerlo.
La migración es idempotente (relanzarla deja 10 sentencias como OMITIDAS porque ya existen).

## 2. Qué se reescribió

- **`MenuService`** (antes apuntaba a la tabla `menus` como si fuera la de ítems): ahora gestiona dos niveles.
  - Menús: `listMenus`, `findMenu`, `findMenuBySlug`, `defaultMenu`, `primaryMenu`, `createMenu`,
    `updateMenu`, `deleteMenu`, `toggleMenu`, `setDefault`.
  - Ítems: `getItems`, `getTree`, `findItem`, `addItem`, `updateItem`, `deleteItem`, `moveItem`, `reorderItems`.
  - Sincronización: `syncDefaultMenu()` (idempotente, en transacción).
  - Resolución: `resolveForPage($page, $location)`, `navigationFor($page, $location)`, `byGroup($slug)` (API).
  - Mantenimiento: `repair()` (un solo predeterminado/principal por ubicación, sin padres cruzados).
- **`MenuController`**: mismas rutas y nuevas para ítems, sincronización, reparación y menú por defecto.
- **`views/menus/index.php`** (reescrita) y **`views/menus/edit.php`** (nueva; sirve para crear y editar, con
  formulario de menú, tabla de ítems y formulario de alta).
- **`PageRenderer`**: `menuForPage($page, $location)` resuelve el menú y lo sanea; `getMenu()` pasa a ser un
  envoltorio (404 y respaldos). `PublicController` usa `menuForPage($page)`.
- **`PagesController` + `PageModel` + `views/pages/edit.php`**: selector «Menú de navegación» por página
  (`pages.menu_id`) y sincronización del menú automático tras `store`, `update` y `delete`.

## 3. Jerarquía: dos cosas distintas

- `pages.parent_id` = estructura del CMS (migas de pan, jerarquía real).
- `page_menu.parent_id` = jerarquía **de cada menú**, con su propio padre y orden por ítem.

El menú automático **copia** la jerarquía de páginas (si cambia el padre de una página, se mueve su ítem). Los
menús personalizados no la tocan nunca: si se despublica una página, su ítem deja de mostrarse pero no se borra.

## 4. Cómo se decide el menú que ve el usuario

`MenuService::resolveForPage($page, $location)` — orden estricto:

| # | Condición | Resultado |
|---|---|---|
| 1 | La página tiene `menu_id` y ese menú está **activo** | ese menú (aunque no sea el principal) |
| 2 | Hay un menú **activo** con `is_primary = 1` en esa ubicación | ese menú |
| 3 | Hay un menú **activo** con `is_default = 1` en esa ubicación | menú automático |
| 4 | Nada de lo anterior | menú vacío (no se pinta nada) |

Casos concretos:

- **A. Sin configuración personalizada** → escenario 3 (automático con las páginas publicadas).
- **B. Menú asignado a una página** → escenario 1, solo en esa página.
- **C. Menú que reemplaza al general** → escenario 2, en todas las páginas sin asignación propia.
- **D. Varios menús** → solo se pinta uno: el que gana por prioridad. Nunca se suman.
- **E. Menú inactivo, inexistente o borrado** → se salta y cae al siguiente escalón (probado: 10a, 10b).

Filtro de visibilidad de cada ítem (`getTree($menuId, true)`): el ítem debe estar `status='published'` con
`is_published=1` y, si apunta a una página, la página debe estar publicada. Un ítem cuyo padre no está visible
sube al primer nivel en lugar de desaparecer.

## 5. Pruebas ejecutadas

Arnés en CLI contra la base real, con páginas y menús temporales (`zz-…`) borrados al final:

```
PASS  1. página sin padre entra en el automático en primer nivel
PASS  2. página hija cuelga del ítem de su padre
PASS  3. página nieta conserva los tres niveles                      [level=2]
PASS  4. la página despublicada desaparece del menú automático
PASS  4b. la nieta de una página oculta sube al primer nivel
PASS  9a. al republicar vuelve a su sitio y sin duplicados
PASS  9b. cambiar el padre de una página mueve su ítem
PASS  11. ningún duplicado (menu_id, page_id) tras sincronizar varias veces
PASS  5. menú personalizado con páginas y orden propio               [orden=19,17]
PASS  5b. la misma página no se puede repetir en el mismo menú
PASS  8a. no se puede añadir una página inexistente
PASS  8b. no se permite un ciclo (elemento padre de sí mismo)
PASS  6. la página con menú asignado usa ese menú                    [menu=5]
PASS  7. el menú principal sustituye al automático en su ubicación
PASS  8c. con varios menús se muestra uno solo (el principal)
PASS  10a. con el menú desactivado se usa el automático
PASS  10b. override a un menú inactivo cae al respaldo
PASS  10c. borrar un menú borra solo sus ítems
PASS  8d. el menú automático rechaza ítems manuales
PASS  12a/b/c. las páginas reales siguen en el menú, la API resuelve por slug y devuelve vacío si no existe
=== RESULTADO: 22 PASS / 0 FAIL ===
```

Y el flujo de administración de extremo a extremo por HTTP (usuario temporal, sesión y CSRF reales):

```
POST /admin/menus                       302 → /admin/menus/7/edit   (menú creado)
POST /admin/menus/7/items               302   (ítem añadido: página "Viajes")
POST /admin/menus/7 (is_primary=1)      302
GET  /inicio                            2 ítems en la nav → "Viajes", "Prueba HTTP"
POST /admin/menus/7 (is_active=0)       302 → GET /inicio → 8 ítems (volvió el automático)
POST /admin/menus/7/delete              302 → GET /inicio → 8 ítems (el automático intacto)
```

Estado final: 2 menús (`main` automático con 8 ítems, `footer` vacío), sin restos de prueba y sin páginas con
menú asignado.

Dos fallos reales aparecieron durante las pruebas y se corrigieron:

1. `addItem()` leía `$data['status']` dentro de un `in_array` cuyo valor por defecto siempre coincidía: al no
   venir el campo se generaba un aviso y `status` acababa vacío (`Data truncated`). Ahora usa una variable
   normalizada.
2. `syncDefaultMenu()` construía el mapa página→ítem **antes** de borrar los ítems ocultos. Como la FK de
   `parent_id` es `ON DELETE CASCADE`, borrar el ítem de una página oculta se llevaba el de su hija; el mapa
   viejo hacía que se intentara actualizar una fila inexistente y la nieta desaparecía. Ahora el mapa se relee
   después de borrar.

## 6. Cambio visible que conviene revisar

El menú automático **ahora refleja la jerarquía real de las páginas**, y hoy todas las páginas tienen
`parent_id = NULL`. Por eso «Portafolio», «proyectos» y «vistas» han pasado de estar anidados a mano bajo otros
ítems a **primer nivel** (la estructura manual anterior no correspondía a ninguna relación de páginas).

- Si se quiere «Portafolio» dentro de «Inicio», hay que poner **Inicio como página padre** de Portafolio
  (`/admin/pages/{id}/edit` → «Página padre») y pulsar «Sincronizar automático».
- Si se quiere una navegación distinta de la estructura de páginas, ese es el caso de un **menú personalizado**
  (jerarquía y orden propios, sin tocar las páginas).

El estado anterior está en `storage/backups/menus_20261025_172704.json`.

## 7. Arrastrar y soltar (organización del menú)

En `views/menus/edit.php`, la tabla de elementos de un **menú personalizado** es arrastrable:

- Se arrastra por el asa `⠿` de cada fila (`draggable="true"`).
- **Tres zonas de destino** según la posición del puntero sobre la fila destino:
  - borde superior (30 % superior) → se coloca **antes**;
  - borde inferior (30 % inferior) → se coloca **después**;
  - centro → se **anida** dentro de ese elemento (pasa a ser hijo).
- Al soltar, el bloque movido arrastra a sus descendientes (se recoloca el subárbol completo) y se
  recalculan las profundidades en la vista.
- Se envía `POST /admin/menus/{id}/items/reorder` con `_csrf_token` e `items` =
  `[{"id":…,"order":…,"parent_id":…}, …]` (el orden es el índice visual, de 0 a n-1). El servidor
  valida padre, ciclos y pertenencia al menú, y guarda **`order` y `sort`** (se mantienen iguales; `order`
  es el canónico para pintar) además de `level`.
- Tras guardar, la página se recarga para mostrar el estado persistido.
- **Alternativa accesible sin JavaScript**: las flechas ↑ ↓ de cada fila siguen siendo formularios POST a
  `/admin/menus/{id}/items/{item}/move`.
- El **menú automático** no es arrastrable ni acepta reorden manual: su orden se deriva de las páginas
  (`pages.id`, jerarquía por `pages.parent_id`) y lo pisa la siguiente sincronización. La vista oculta el
  arrastre, y el servicio además lo rechaza con un mensaje claro.

Verificado por HTTP (con sesión y CSRF reales):

```
POST /admin/menus/8/items/reorder  items=[27,25,26]        → {"success":true}  → nav: Viajes, Inicio, proyectos
POST /admin/menus/8/items/reorder  items=[27,25(parent 27)] → {"success":true}  → DB: Inicio parent_id=27 level=1
                                                              nav: 1 nivel + 1 submenú (dropdown)
POST /admin/menus/8/items/reorder  ciclo 27↔25              → {"success":false,"error":"…crearía un ciclo"}
POST /admin/menus/1/items/reorder  (automático)             → {"success":false,"error":"El menú automático se ordena…"}
```

JS del arrastre extraído del HTML emitido: `node --check` sin errores; el bloque no se emite en el menú
automático (0 scripts de DnD en esa vista).

**Fallo encontrado al probarlo**: `reorderItems()` validaba antes de abrir la transacción y el `catch`
llamaba a `Database::rollback()` sin transacción activa → «There is no active transaction» (excepción que
tapaba el mensaje real). Se añadió `Database::rollbackIfActive()` y se usa en los cuatro `catch` del
servicio.

## 8. Límites conocidos

- El layout público pinta **solo** el menú de la ubicación `header`. El menú `footer` existe en el modelo y en la
  administración, pero el pie de página sigue teniendo enlaces estáticos: falta renderizarlo.
- El arrastre se ha validado con el endpoint real y la sintaxis del script, **no** con un navegador: la
  pulsación del ratón queda pendiente de tu comprobación.
- `order` y `sort` siguen duplicados en `page_menu` (se escriben igual); se conservan por compatibilidad.
