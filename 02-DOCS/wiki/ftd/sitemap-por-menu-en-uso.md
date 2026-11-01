# Sitemap generado desde el panel, según el menú en uso

> Ficha FTD del cambio. Estado: **implementado y verificado el 2026-10-31**, pendiente de revisión
> del dueño sobre el criterio elegido.

## 1. Intención

Que el `sitemap.xml` se genere desde el panel y contenga **solo lo que el sitio pone en uso público**:
lo que el menú en uso expone, actualizándose cuando se guarda una página y cuando se cambia el menú.

Hasta ahora el sitemap listaba *todas* las páginas publicadas, incluidas las que no aparecen en
ningún menú y que el visitante no puede alcanzar navegando.

## 2. Lo que había (medido, no supuesto)

- `SitemapService` existía y escribía `public/sitemap.xml`; se invocaba al crear, actualizar y borrar
  páginas y desde el botón de `/admin/seo`.
- **El archivo estático tapaba la ruta dinámica** `GET /sitemap.xml` de `config/routes.php:55`: con
  Apache (o el servidor de PHP) un archivo real gana al router. La ruta estaba muerta de hecho.
- El archivo generado usaba `config.app_url` = `http://localhost:170`, así que **todas sus URLs
  apuntaban a localhost**: inservibles para cualquier buscador.
- La regla de menú no se aplicaba: `publishedForSitemap()` devolvía todas las publicadas.

## 3. La regla, y por qué es esa

No se inventó un criterio: se copió el que el sitio público ya usa para decidir qué menú pinta en
cada ubicación (`MenuService`, documentado en su cabecera y aplicado en `resolveForPage`):

```
menú asignado a la página → menú `is_primary` de la ubicación → menú `is_default` de la ubicación
```

Un menú con `is_primary = 1` **sustituye** al predeterminado en su ubicación. Si el sitemap usara
otra regla, anunciaría URLs que el visitante no alcanza.

**Consecuencia medida en los datos reales:** el sitio tiene tres menús. En `header` convive
`Menú principal` (`is_default = 1`, 9 ítems) con `Menu top` (`is_primary = 1`, 4 ítems); gana
`Menu top`. `Menú footer` (`footer`) no es ni predeterminado ni principal, así que **no está en uso**:
hoy nada lo pinta (el pie del layout usa enlaces fijos). Resultado: el sitemap pasa de **9 URLs a 5**
(inicio + `desarrollo`, `portafolio`, `proyectos`, `quienes-somos`). Las que caen —`abancay`,
`vistas`, `viajes`, `demo-web`— siguen publicadas y accesibles por URL, pero dejan de anunciarse.

Eso es exactamente lo pedido, y por eso la pantalla del panel lo muestra con el motivo de cada URL en
vez de dejarlo invisible.

## 4. Qué se implementó

| Pieza | Qué hace |
|---|---|
| `app/Services/SitemapService.php` | Reescrito: `menusInUse()`, `baseUrl()` con aviso de localhost, `generate(bool $escribir)` con previsualización, `refresh()` que nunca lanza, `status()`, escritura **atómica** (temporal + `rename`) |
| `GET /admin/seo/sitemap` | Pantalla: estado del archivo, URL base y su origen, avisos, menús en uso con su motivo, tabla de URLs incluidas con su procedencia, XML actual y formulario |
| `POST /admin/seo/sitemap/settings` | Guarda `seo.sitemap_source` (`menu` \| `published`), `seo.sitemap_base_url` y `seo.sitemap_include_home`, y regenera |
| `POST /admin/seo/sitemap/regenerate` | Regenera bajo demanda (ahora con permiso y CSRF) |
| `MenuController::touch()` | Regenera el sitemap tras cualquier cambio de menú: crear, editar, borrar, reordenar, asignar ítems, marcar predeterminado |
| `SeoController::guardSitemap()` | Exige `seo.manage` en las tres acciones: **no** se delega en `PermissionMiddleware`, que no está aplicado en ninguna ruta (deuda 14) |
| `views/seo/sitemap.php` | La pantalla. Enlace añadido en `/admin/seo` |
| `tests/sitemap/check.php` | 15 comprobaciones, en seco por defecto y con `--write` para generar de verdad |

**Dos fuentes, elegibles en el panel.** `menu` (por defecto) es lo pedido; `published` existe porque
excluir una página publicada puede ser deliberado o un accidente, y esa diferencia la juzga el dueño,
no el código.

## 5. Verificación

`php tests/sitemap/check.php` → **15 comprobaciones, 0 fallos**. `--write` genera el archivo.

| Comprobación | Resultado |
|---|---|
| Regla de menú | por `header` solo entra `Menu top` (primary); ninguna ubicación con dos menús |
| Aviso de localhost | se emite: `app_url` = `http://localhost:170` |
| XML bien formado | `simplexml_load_string` sin error; se recorren **todos** los `<loc>` por xpath |
| Sin rutas privadas | `/admin`, `/admin/pages`, `/login`, `/sitemap.xml` excluidas y verificadas por reflexión |
| Escritura en seco | el archivo **no** cambia (comparado por `LastWriteTime`) |
| Escritura real | `public/sitemap.xml` regenerado: 5 URLs, XML válido, sin `changefreq` (opcional según sitemaps.org y Google lo ignora) |
| Rutas del panel | `/admin/seo/sitemap` y `/admin/seo` responden **302** a `/login` sin sesión; `/sitemap.xml` responde **200** |
| `php -l` | limpio en los 6 archivos tocados |

**Un fallo propio detectado y corregido durante la prueba:** la primera versión del test contaba 1
`<loc>` de 9, porque `$doc->url->loc` en SimpleXML devuelve solo el primer `<url>`. Daba una falsa
sensación de cobertura; se cambió a `xpath`. Se deja anotado porque el patrón se repite en cualquier
prueba de XML con SimpleXML.

## 6. Lo que queda abierto

1. **La URL base sigue siendo localhost.** El archivo generado no servirá hasta que se configure un
   dominio real en la pantalla «Sitemap» o en `config.app_url`. El panel lo avisa, pero no lo arregla
   solo: es un dato del despliegue.
2. **`Menú footer` no está en uso.** O se marca como predeterminado de `footer`, o el pie sigue con
   sus enlaces fijos. Decisión del dueño.
3. **La ruta `GET /sitemap.xml` del router sigue muerta** por el archivo estático. Se deja así a
   propósito: servir el archivo es más rápido y ya está generado. Convendría retirar la ruta para que
   no confunda, pero toca `config/routes.php` y no se hizo en este cambio.
4. **Cuatro páginas publicadas salieron del sitemap** al pasar a la fuente `menu`. Si deben
   anunciarse, o se añaden al menú en uso o se cambia la fuente a `published`.

## 7. Ajuste posterior: el ámbito por defecto lo fija el dueño

Tras ver el primer resultado, el dueño corrigió el criterio. La sección 3 de esta ficha describe el
primer diseño (el sitemap seguía lo que el header muestra); **el comportamiento vigente es el de
aquí**.

### 7.1 Nuevo criterio

| Ámbito (`seo.sitemap_scope`) | Qué lee | Resultado con los datos reales |
|---|---|---|
| `default_menus` · **por defecto** | el menú `is_default = 1` de cada ubicación | **9 URLs** — «Menú principal» |
| `header_menu` | el menú que el header muestra (`is_primary` → `is_default`) | 5 URLs — «Menu top» |
| `specific` | el menú elegido en `seo.sitemap_menu_id` | el que se elija |
| `all_published` | los menús que se pintan, más **todas** las publicadas | 13 URLs |

«Menú principal» (`is_default = 1`, 9 ítems) es el que el dueño llama *el menú principal*, y ese
manda salvo que se elija otra cosa. El menú del header queda como opción explícita, no como
comportamiento implícito: puede mostrar solo una parte del sitio, y eso no debe decidir el sitemap
sin que nadie lo pida.

Se conserva la clave antigua `seo.sitemap_source` como compatibilidad: si existe y vale `published`,
se interpreta como `all_published`.

### 7.2 Defecto encontrado y corregido en la misma pasada

Con el nuevo ámbito el archivo anunciaba **`/` y `/inicio` a la vez**: la misma página dos veces,
porque el menú enlaza la portada por su slug y el generador añadía además la raíz. Contenido
duplicado a ojos de un buscador. Corregido en `urlsFromMenu()`: si la página del ítem tiene
`startpage = 1`, su ruta se normaliza a `/` y la añade una sola vez el bloque de inicio, que respeta
`include_home`.

### 7.3 Verificación

`php tests/sitemap/check.php` → **17 comprobaciones, 0 fallos**. Las dos nuevas comprueban que el
ámbito por defecto existe en el catálogo y que los dos ámbitos resuelven a menús distintos
(`predeterminados: Menú principal` frente a `header: Menu top`), que es lo que hace útil al selector.

| Comprobación | Resultado |
|---|---|
| Archivo regenerado | **9 URLs**, XML válido, sin `/inicio` duplicando a `/` |
| `GET /sitemap.xml` | **200**, 1 317 bytes, 9 `<loc>` |
| `/admin/seo/sitemap` sin sesión | redirige a `/login`; siguiendo la redirección se sirve la página de acceso (comprobado: el 200 es el formulario de login, no el panel) |
| `php -l` | limpio en los 5 archivos tocados |

