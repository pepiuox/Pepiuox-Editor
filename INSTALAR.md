# Instalación limpia · PePiuoX Editor

Paquete listo para un servidor real. **No incluye** tests, documentación interna del
desarrollo, respaldos, volcados con datos ni credenciales.

---

## 1. Requisitos

| | Mínimo |
|---|---|
| PHP | 8.2 (con `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`, `zip`, `gd`, `curl`) |
| MariaDB / MySQL | MariaDB 10.6 · MySQL 8.0 |
| Servidor web | Apache con `mod_rewrite` apuntando a `public/` |

Comprobación rápida:

```bash
php 01-TOOLS/despliegue/verificar.php
```

---

## 2. Subir y preparar

```bash
# 1. Descomprimir en el servidor, fuera del document root
unzip pepiuox-editor.zip -d /var/www/pepiuox
cd /var/www/pepiuox

# 2. Credenciales
cp 01-TOOLS/mysql/.env.example 01-TOOLS/mysql/.env
# Edita .env y rellena DB_* ; deja APP_URL para el paso 5

# 3. Permisos
sudo chown -R www-data:www-data storage uploads
sudo chmod -R 775 storage uploads
```

El document root del sitio debe ser **`public/`**, no la raíz del paquete.

---

## 3. Base de datos

Crea la base y un usuario **sin permisos de esquema**:

```sql
CREATE DATABASE web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
CREATE USER 'web_editor_app'@'localhost' IDENTIFIED BY '<contraseña>';
GRANT SELECT, INSERT, UPDATE, DELETE ON web_editor_db.* TO 'web_editor_app'@'localhost';
```

Importa la instalación limpia (estructura + catálogos, **sin datos de nadie**):

```bash
mysql -u root -p web_editor_db < db/install/initial_database.sql
```

---

## 4. Primer administrador y portada

```bash
# Crea el Super Admin. Genera una contraseña e imprime la base de destino: escríbela.
php 01-TOOLS/bootstrap/create-admin.php --generate

# Crea la portada (y las páginas del sistema)
php 01-TOOLS/bootstrap/seed-pages.php --yes --only-inicio
```

`seed-pages` deja **una página de inicio publicada** (`/inicio`, marcada como portada),
editable después con el constructor como cualquier otra.

---

## 5. Dominio y modo producción

```bash
php 01-TOOLS/despliegue/verificar.php --fix --yes --url=https://tu-dominio.com
```

Escribe en el `.env`: `APP_URL`, `APP_DEBUG=false` y `SESSION_SECURE=true`. Vuelve a
ejecutarlo sin argumentos hasta ver `Listo para publicar`.

---

## 6. Tareas programadas

Copia el token de `CRON_TOKEN` del `.env` y añádelo al crontab:

```cron
15 3 * * *  curl -fsS "https://tu-dominio.com/cron/backup?token=<CRON_TOKEN>" > /dev/null
*/30 * * * * curl -fsS "https://tu-dominio.com/cron/cleanup-rate-limits?token=<CRON_TOKEN>" > /dev/null
```

Los seis endpoints están en `docs/CHECKLIST_PUBLICACION.md` §5. Sin token responden 401.

---

## 7. Qué hay dentro

```
app/                 código (controladores, modelos, servicios, núcleo)
views/               plantillas del panel y del sitio público
config/              rutas, permisos, entorno
public/              document root: index.php, .htaccess, assets
db/install/          SQL de instalación limpia
db/migrations/       6 migraciones con su reversión
01-TOOLS/bootstrap/  crear el primer admin y sembrar páginas
01-TOOLS/despliegue/ verificación previa a publicar
01-TOOLS/mantenimiento/ rotar contraseñas y tokens de API, sanear volcados
docs/                manual de instalación y de usuario
```

**Fuera del paquete a propósito:** `tests/`, `02-DOCS/`, los volcados de referencia
(`db/db-full*.sql`, con datos reales), `db/backup/`, `uploads/` de ejemplo, el `.env`
y las maquetas de diseño.

---

## 8. Si algo no arranca

| Síntoma | Causa habitual |
|---|---|
| 500 en todas las rutas | El document root no es `public/`, o falta `mod_rewrite` |
| «No se puede conectar a la base» | `DB_*` mal en el `.env`, o el usuario no existe |
| Los assets dan 404 | `BASE_URL` mal derivada: revisa que el sitio se sirva desde la raíz |
| Los `/cron/*` dan 401 | Falta `CRON_TOKEN` o no coincide con el del crontab |
| No deja guardar en el panel | Permisos de `storage/` (debe escribir `www-data`) |
