# PePiuoX Editor

Editor/CMS web en PHP 8 y MariaDB con panel de administración: páginas, versiones, plantillas,
menús, usuarios y roles.

## Requisitos

- **PHP 8.3+** con `pdo_mysql`, `mbstring`, `json`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo`
- **MariaDB 11+** (el volcado usa colaciones propias de MariaDB; MySQL no sirve tal cual)
- **Apache 2.4** con `mod_rewrite`, o nginx con la reescritura equivalente
- **Composer no hace falta**: el proyecto se auto-carga con PSR-4 y no tiene dependencias

## Instalación mínima

1. Apunta el *document root* a `public/` (o usa el `.htaccess` de la raíz).
2. Crea la base y un usuario con `SELECT, INSERT, UPDATE, DELETE` sobre ella.
3. Copia `01-TOOLS/mysql/.env.example` a `01-TOOLS/mysql/.env` y rellena las credenciales.
4. Importa `db/db-full.sql`.

```powershell
Copy-Item 01-TOOLS\mysql\.env.example 01-TOOLS\mysql\.env
mysql -u pepiuox -p web_editor_db < db\db-full.sql
php 01-TOOLS\mysql\test_connection.php
```

**La guía completa, con los problemas frecuentes y lo que hay que endurecer antes de publicar, está
en [`INSTALL.md`](INSTALL.md).** No hay instalador web a propósito: se retiró de la raíz por
seguridad.

## Documentación

| Documento | Qué es |
|-----------|--------|
| [`INSTALL.md`](INSTALL.md) | La guía de instalación de verdad |
| [`ROUTES.md`](ROUTES.md) | Rutas de la aplicación |
| [`PARTIAL_ESTRUCTURE.md`](PARTIAL_ESTRUCTURE.md) | Mapa de carpetas |
| [`../02-DOCS/wiki/index.md`](../02-DOCS/wiki/index.md) | La memoria del proyecto: por dónde empezar a leerla |
