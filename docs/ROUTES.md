# 🛣️ Documentación de Rutas — PePiuoX Editor

> **Versión:** 1.0.0  
> **Última actualización:** 25 de agosto de 2026  
> **Total de rutas:** 141  
> **Autor:** PePiuoX Team

---

## 📋 Índice

1. [Convenciones](#convenciones)
2. [Rutas Públicas](#rutas-públicas)
3. [API REST Pública](#api-rest-pública)
4. [Panel Administrativo](#panel-administrativo)
5. [Cron Endpoints](#cron-endpoints)
6. [Catch-All](#catch-all)
7. [Middlewares](#middlewares)
8. [Códigos de Estado](#códigos-de-estado)

---

## 🎯 Convenciones

### Métodos HTTP

| Método | Uso |
|--------|-----|
| `GET` | Obtener datos o mostrar vistas |
| `POST` | Crear recursos o ejecutar acciones |
| `PUT` | Actualización completa (no usado actualmente) |
| `DELETE` | Eliminación (implementado como POST por seguridad CSRF) |

### Parámetros de Ruta

| Sintaxis | Descripción | Ejemplo |
|----------|-------------|---------|
| `{id}` | ID numérico de un recurso | `/admin/users/5/edit` |
| `{slug}` | Slug alfanumérico | `/blog/mi-articulo` |
| `{vid}` | ID de versión | `/admin/pages/5/versions/3/restore` |
| `{tid}` | ID de token | `/admin/api/2/tokens/15/revoke` |
| `{code}` | Código de idioma | `/lang/es` |

### Autenticación

| Símbolo | Descripción |
|---------|-------------|
| 🔓 | Ruta pública (sin autenticación) |
| 🔐 | Requiere sesión de usuario |
| 🛡️ | Requiere sesión + permisos específicos |
| 🔑 | Requiere Bearer Token (API) |
| ⏱️ | Protegida por Rate Limiting |

---

## 🌐 Rutas Públicas

Rutas accesibles sin autenticación.

### 1.1 Frontend y SEO

| # | Método | Ruta | Controlador | Descripción | Rate Limit |
|---|--------|------|-------------|-------------|------------|
| 1 | GET | `/` | `PublicController::home` | Página de inicio | — |
| 2 | GET | `/sitemap.xml` | `PublicController::sitemap` | Sitemap XML dinámico | — |
| 3 | GET | `/robots.txt` | `SeoController::robots` | Robots.txt dinámico | — |
| 4 | GET | `/{slug}` | `PublicController::show` | Página pública por slug | — |

### 1.2 Autenticación

| # | Método | Ruta | Controlador | Descripción | Rate Limit |
|---|--------|------|-------------|-------------|------------|
| 5 | GET | `/login` | `AuthController::showLogin` | Formulario de login | — |
| 6 | POST | `/login` | `AuthController::login` | Procesar login | **5/min** |
| 7 | GET | `/logout` | `AuthController::logout` | Cerrar sesión | — |

### 1.3 Recuperación de Contraseña

| # | Método | Ruta | Controlador | Descripción | Rate Limit |
|---|--------|------|-------------|-------------|------------|
| 8 | GET | `/forgot` | `PasswordController::forgotForm` | Formulario recuperación | — |
| 9 | POST | `/forgot` | `PasswordController::forgotSend` | Enviar email | **3/hora** |
| 10 | GET | `/reset` | `PasswordController::resetForm` | Nueva contraseña | — |
| 11 | POST | `/reset` | `PasswordController::resetSave` | Guardar contraseña | **5/hora** |

### 1.4 2FA Challenge

| # | Método | Ruta | Controlador | Descripción | Rate Limit |
|---|--------|------|-------------|-------------|------------|
| 12 | GET | `/2fa/challenge` | `TwoFactorController::challenge` | Pedir código 2FA | — |
| 13 | POST | `/2fa/challenge/verify` | `AuthController::verify2FA` | Verificar código | **5/min** |

### 1.5 Internacionalización

| # | Método | Ruta | Controlador | Descripción | Rate Limit |
|---|--------|------|-------------|-------------|------------|
| 14 | GET | `/lang/{code}` | `LanguageController::switch` | Cambiar idioma | — |

---

## 🔌 API REST Pública

Autenticación mediante Bearer Token en header `Authorization`.

| # | Método | Ruta | Controlador | Descripción | Scopes |
|---|--------|------|-------------|-------------|--------|
| 15 | GET | `/api/v1/pages` | `ApiController::pagesIndex` | Listar páginas | `pages:read` |
| 16 | GET | `/api/v1/pages/{slug}` | `ApiController::pagesShow` | Obtener página | `pages:read` |
| 17 | GET | `/api/v1/menus/{slug}` | `ApiController::menusShow` | Obtener menú | `menus:read` |
| 18 | GET | `/api/v1/me` | `ApiController::me` | Info del token | — |

### Ejemplo de uso

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://dominio.com/api/v1/pages