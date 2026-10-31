---
author: Pepiuox
branch: main
status: in-progress
---
# Navbar público con acciones de sesión

## Intent

Las páginas públicas no mostraban la barra de navegación del sistema: `PublicController` generaba su
propio layout en línea, con la marca fija «Mi Sitio Web», tema claro y sin ninguna acción de sesión.
Existía un layout moderno en `views/public/layout.php`, pero nada lo incluía.

Cambio: conectar el layout real y rediseñar su navbar. Visitante ve «Iniciar sesión» y «Crear
cuenta». Sesión iniciada ve el acceso al panel de administración, y «Iniciar sesión» y «Crear
cuenta» desaparecen.

Vale la pena porque antes el sitio público no tenía forma de alcanzar `/login` ni `/register` desde
la navegación, y su diseño no coincidía con el del panel.

## Scope

Dentro:
- `app/Controllers/PublicController.php`: dejar de generar el layout en línea y renderizar
  `views/public/layout.php` con `page`, `menu`, `content`, `appName` y `authUser`.
- `views/public/layout.php`: navbar moderno, acciones condicionadas por `Auth::check()`, estados
  móvil/escritorio, foco visible y `prefers-reduced-motion`.
- Conservar íntegro el sistema de menú multinivel (`renderMenuHtml` + el JS de submenús).

Fuera:
- `PagePublicService` / `PagePublicService.php` / `PagePublicervice-ai.php`, que piden
  `layouts.public` (archivo inexistente) y no están enrutados.
- Las rutas `/page` y `/page/{slug}` de `config/routes.php`, que hacen `require` de un `page.php`
  raíz que no existe.
- `PublicController::sitemap()`, que usa `Database` sin importar el namespace.
- Cualquier cambio de esquema, de autenticación o de permisos.

## Checklist

- [x] Documento FTD escrito antes del primer cambio — comprobado: este archivo existe con checklist.
- [x] `PublicController` renderiza `views/public/layout.php` — comprobado: `php -l` limpio y lectura
      del archivo mostrando `View::partial('public.layout', [...])`.
- [x] La navbar muestra «Iniciar sesión» y «Crear cuenta» sin sesión — comprobado: render con
      `Auth::check()` falso produce `/login` y `/register` y ningún `/admin`.
- [x] Con sesión activa muestra el acceso al panel y oculta ingreso y registro — comprobado: render
      con sesión simulada produce `/admin` y `/admin/profile`, y no produce `/login` ni `/register`.
- [x] El menú multinivel sigue funcionando — comprobado: el HTML generado por `renderMenuHtml`
      conserva `li.has-submenu`, `.menu-caret`, `.dropdown-menu` y `.split-link`, que es lo que el JS
      consulta.
- [x] Sintaxis PHP válida en los archivos tocados — comprobado: `php -l` sin errores en
      `PublicController.php` y en `views/public/layout.php`.

## Evidence

Render real de la vista en dos estados, con el autoloader y los helpers del proyecto, sin base de
datos (menú de prueba con un submenú). 15 comprobaciones, todas PASS:

```
PASS visitante: muestra /login
PASS visitante: muestra /register
PASS visitante: NO muestra /admin
PASS sesión: muestra /admin
PASS sesión: NO muestra /login
PASS sesión: NO muestra /register
PASS sesión: muestra /admin/profile
PASS sesión: muestra /logout
PASS sesión: muestra nombre de usuario
PASS menú: conserva has-submenu
PASS menú: conserva menu-caret
PASS menú: conserva dropdown-menu
PASS menú: conserva split-link
PASS marca: dos tonos del appName
PASS botones: clases nav-btn en ambos
```

Salida HTML de la navbar como visitante (recortada):

```html
<nav class="navbar navbar-expand-lg navbar-main sticky-top" id="siteNav" aria-label="Navegación principal">
  <a class="navbar-brand" href=".../" aria-label="PePiuoX Editor · Inicio">
    <span class="brand-mark" aria-hidden="true"><i class="bi bi-layers-fill"></i></span>
    <span class="brand-word">PePiuoX<span class="brand-accent"> Editor</span></span>
  </a>
  ...
  <div class="nav-actions">
    <a class="btn btn-nav-ghost nav-btn" href=".../login"><i class="bi bi-box-arrow-in-right"></i><span>Iniciar sesión</span></a>
    <a class="btn btn-accent nav-btn" href=".../register"><i class="bi bi-person-plus"></i><span>Crear cuenta</span></a>
  </div>
</nav>
```

`php -l` en los dos archivos: «No syntax errors detected».

Evidencia que **no** se pudo obtener: captura visual en navegador. El navegador headless
(Edge) no arranca bajo el sandbox de la sesión — falla al abrir el canal `mojo` con *Access is
denied*. El diseño está verificado por estructura y por HTML generado, no por píxeles.

## Next

Revisar el resultado en el navegador con `/` y con una página publicada, y decidir si se unifica
`PagePublicService` sobre este mismo layout o se retira.
