---
author: Pepiuox
branch: main
status: in-progress
---
# Sistema de menús: diagnóstico y diseño

> Estado encontrado: **el módulo de administración de menús no funciona** (apunta a la tabla equivocada) y el menú
> público es 100 % manual, así que las páginas publicadas nuevas no aparecen. Este documento es el diagnóstico
> y la decisión de diseño; la implementación va detrás, en commits separados.

## 1. Qué existe hoy

| Pieza | Dónde | Estado |
|---|---|---|
| Tabla `menus` | id, name, slug(unique), location, is_active, created_at — **2 filas**: `main`/header, `footer`/footer | **Nadie la usa**: ninguna consulta en `app/` ni en `views/` la lee o escribe |
| Tabla `page_menu` | id, page_id(FK→pages), title, slug(**UNIQUE global**), url, icon, `order`, `sort`, parent_id(auto-referencia), level, is_published, `menu_group`, status(enum), target, timestamps — **6 filas**, todas `menu_group='main'` | Es lo único que se lee, y solo desde `PageRenderer` |
| `MenuService` | `$table = 'menus'` | Consulta columnas que **`menus` no tiene** (`menu_group`, `user_id`, `title`, `order`, `page_id`…) → toda la API del servicio falla |
| `MenuController` + `/admin/menus` | `views/menus/index.php` (única vista) | CRUD sobre el servicio roto; `menus.create` / `menus.edit` **no existen** como vistas |
| `/api/v1/menus/{slug}` | `ApiController::menusShow()` llama `MenuService::byGroup()` | **El método no existe** → 500 |
| Menú público | `PageRenderer::getMenu()` + `getMenuChildren()` (SQL propio sobre `page_menu`) | Funciona, pero con datos mantenidos a mano |

## 2. Diagnóstico

### 2.1 Dos modelos desconectados para el mismo concepto

`menus` es un registro de menús (nombre, slug, ubicación, activo). `page_menu` son los ítems de menú, pero **no
tienen ninguna relación**: no hay `menu_id` ni FK. Lo que hace de "menú" es la cadena `page_menu.menu_group`
('main', por defecto). Consecuencias:

- **No se puede saber qué menús existen** a partir de los ítems ni al revés.
- Un menú creado en `menus` (por ejemplo `footer`) no puede tener ítems: no hay vínculo.
- Borrar un menú no borra ni protege sus ítems.

### 2.2 El servicio apunta a la tabla equivocada

`MenuService` usa `$table = 'menus'` para todo, y consulta columnas que solo existen en `page_menu`:

```
SELECT * FROM menus WHERE menu_group = ?            → Unknown column 'menu_group'   → [] (silenciado)
SELECT * FROM menus WHERE user_id = ?               → Unknown column 'user_id'      → [] (silenciado)
INSERT INTO menus (user_id, title, slug, url, …)    → Unknown column 'user_id'      → error devuelto
UPDATE menus SET title = ?, order = ? …             → Unknown column 'title'        → error devuelto
```

Todo va envuelto en `try/catch` que devuelve `[]` o `['success' => false]`, así que la interfaz **parece** vacía
en vez de rota. Este es el mismo patrón que ya apareció con `page_autosave`, `template_versions` y `audit_logs`.

### 2.3 El menú público es manual

`PageRenderer::getMenu()` selecciona de `page_menu` los ítems con `status='published' AND is_published = 1`, sin
mirar nunca la tabla `pages`. Publicar una página **no** la añade al menú:

| Página | Estado | ¿En `page_menu`? |
|---|---|---|
| 1 inicio, 2 quienes-somos, 3 proyectos, 4 portafolio, 5 demo-web, 6 vistas | publicadas | sí |
| **7 desarrollo**, **12 viajes** | publicadas | **no** |

Además, un ítem puede quedar visible con la página despublicada (nada lo impide) y viceversa.

### 2.4 Restricciones que impiden los menús personalizados

- `page_menu.slug` es **UNIQUE global**: una página solo puede existir una vez en toda la tabla, así que
  **no puede estar en dos menús**. incumple el requisito de menús múltiples.
- `menu_group` es texto libre sin catálogo: no hay validación de que el grupo exista.
- No hay forma de asignar un menú a una página concreta (ni columna en `pages` ni tabla puente).
- No hay marca de "menú predeterminado" ni de "menú que reemplaza al principal".
- `order` y `sort` duplican el mismo concepto y nadie mantiene `level` (las filas hijas actuales tienen `level=0`).
- `status` + `is_published` vuelven a duplicar la misma verdad (como en `pages`).

### 2.5 Jerarquía: ya son dos cosas distintas (bien)

La jerarquía **del menú** (`page_menu.parent_id`) ya es independiente de la jerarquía **de las páginas**
(`pages.parent_id`): hoy `portafolio` cuelga de "Inicio" en el menú y es raíz en las páginas. Eso es lo deseado y
se conserva: el menú guarda su propio padre y orden.

## 3. Decisiones de diseño

### 3.1 Reutilizar lo que ya existe (sin tablas nuevas)

- `menus` **es** la tabla de menús: se queda y se le añaden dos marcas.
- `page_menu` **es** la tabla de ítems: se queda y se le añade el vínculo al menú.
- No se crean tablas nuevas.

### 3.2 Cambios de esquema (mínimos y aditivos)

| Tabla | Cambio | Motivo |
|---|---|---|
| `menus` | `+ is_default TINYINT(1) DEFAULT 0` | marca el menú **automático** (se sincroniza con las páginas publicadas) |
| `menus` | `+ is_primary TINYINT(1) DEFAULT 0` | marca el menú que **reemplaza** al predeterminado en su `location` |
| `page_menu` | `+ menu_id INT NOT NULL` + FK → `menus(id)` ON DELETE CASCADE | vínculo real ítem ↔ menú; borrar el menú borra solo sus ítems |
| `page_menu` | `DROP UNIQUE slug` → `UNIQUE (menu_id, slug)` | una página puede estar en varios menús |
| `page_menu` | `+ UNIQUE (menu_id, page_id)` | evita duplicar la misma página en el mismo menú |
| `page_menu` | FK `parent_id` → `page_menu(id)` ON DELETE CASCADE | jerarquía íntegra; borrar un padre no deja huérfanos |
| `pages` | `+ menu_id INT NULL` + FK → `menus(id)` ON DELETE SET NULL | asignación de menú a una página concreta |

Backfill (sin pérdida): todo `page_menu` existente pasa a `menu_id` del menú `main`, que se marca `is_default=1`.
Respaldo previo: `storage/backups/menus_<fecha>.json` con ambas tablas.

### 3.3 Tres conceptos separados

1. **Menú predeterminado** (`menus.is_default=1`): se genera y sincroniza desde las páginas publicadas. El
   administrador no lo edita a mano.
2. **Menú personalizado** (`is_default=0`): páginas elegidas a mano, con su propio padre y orden por ítem.
3. **Asignación**: `menus.location` (ubicación del sitio), `menus.is_primary` (sustituye al predeterminado en esa
   ubicación) y `pages.menu_id` (menú específico de una página).

### 3.4 Reglas de selección (una sola función, documentada)

`MenuService::resolveForPage(?array $page, string $location = 'header')`:

| Prioridad | Condición | Menú usado |
|---|---|---|
| 1 | `pages.menu_id` apunta a un menú **activo** con esa `location` | ese menú |
| 2 | existe un menú **activo** con `location` y `is_primary=1` | ese menú (reemplaza al predeterminado) |
| 3 | existe el menú **activo** con `is_default=1` para esa ubicación | menú automático |
| 4 | nada de lo anterior | menú vacío (no se muestran enlaces) |

Desempates y conflictos: solo puede haber **un** `is_default=1` y **un** `is_primary=1` por `location`. Se valida
al guardar (si otro menú ya lo tenía, se le quita) y `POST /admin/menus/repair` deja la base coherente si alguna vez
se toca por SQL.

### 3.5 Sincronización del menú predeterminado

`MenuService::syncDefaultMenu()` (idempotente, dentro de transacción):

1. Asegura el menú `is_default=1` (lo crea si no existe: `slug='default'`, `location='header'`).
2. Para cada página con `status='published'` **y** `is_published=1`: crea el ítem si falta
   (`menu_id`, `page_id`, `slug` = slug de la página, `title` = título, `url` = null → el render resuelve el slug).
3. Traduce la jerarquía de páginas (`pages.parent_id`) a jerarquía de ítems (`page_menu.parent_id`), y `level`.
4. Orden: por `pages.id` ascendente (las páginas no tienen columna de orden propia; se documenta).
5. Ítems cuya página ya no está publicada, no existe o fue borrada: se eliminan **solo del menú predeterminado**
   (los personalizados no se tocan).
6. Nunca duplica: la restricción `(menu_id, page_id)` lo impide y el propio sync busca antes de insertar.

Se invoca desde: `PagesController::store/update/delete` (tras guardar), el botón «Sincronizar» del panel y
`php db/migrations/…` mediante el servicio.

### 3.6 Ítems en menús personalizados

- Se pueden añadir páginas (desplegable de páginas) o enlaces externos (`url` sin `page_id`).
- Padre y orden se guardan **en el ítem** (`parent_id`, `order`), no en la página.
- Validaciones al guardar: la página existe; no hay ciclo (se recorre la cadena de padres); no hay duplicado
  `(menu_id, page_id)`; el padre pertenece al mismo menú; `level` se recalcula.
- La publicación de la página no altera los ítems personalizados (solo el predeterminado). Si la página se
  despublica, el ítem personalizado **no se muestra** en público, pero no se borra: al republicar vuelve.

## 4. Qué se va a implementar

1. Migración `2026_10_25_menu_system` (up/down) con respaldo previo.
2. `MenuService`: menús (CRUD), ítems (CRUD + reordenar), `syncDefaultMenu()`, `resolveForPage()`,
   `getNavigationMenu($menuId)`, `byGroup()` estático para la API, `repair()`.
3. `MenuController` + rutas + vistas `menus/index.php`, `menus/edit.php` (sirve para crear y editar): menús
   listados con sus marcas, formulario de menú, tabla de ítems con añadir/ordenar/eliminar y vista previa del árbol.
4. `PagesController` + `PageModel`: `menu_id` en la edición de página y llamada al sync en store/update/delete.
5. `PageRenderer` + `PublicController`: resolver el menú por página/ubicación y pintar solo lo publicado.
6. Pruebas de los 12 escenarios pedidos (CLI + HTTP) y documentación de las decisiones.

Queda fuera (documentado, no pedido): editor visual de menús con arrastrar y soltar (la vista usará flechas
subir/bajar y un campo de orden, que es verificable sin JavaScript frágil).
