# Checklist de publicación

Lo que hay que hacer para poner la plataforma en un servidor, en orden, con la comprobación de cada paso.
Marca cada casilla solo cuando la comprobación dé el resultado esperado.

> **Convención:** ✅ ya verificado en este equipo · ⬜ pendiente en el servidor.
> Los comandos suponen la raíz del proyecto como directorio actual.

---

## 1. Requisitos del servidor

| Requisito | Mínimo | Comprobación | Estado aquí |
|---|---|---|---|
| PHP | 8.2 | `php -v` | ✅ 8.4.25 |
| MariaDB / MySQL | 10.6 / 8.0 | `mysql --version` | ✅ MariaDB 12.3.3 |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`, `zip`, `gd`, `curl` | `php -m` | ✅ las ocho cargadas |
| Apache con `mod_rewrite` | — | `apache2ctl -M \| grep rewrite` | ⬜ |

```bash
php -r "foreach(['pdo_mysql','mbstring','json','openssl','fileinfo','zip','gd','curl'] as $e) printf(\"%-10s %s\n\", $e, extension_loaded($e)?'ok':'FALTA');"
```

---

## 2. Variables de entorno

Van en `01-TOOLS/mysql/.env` (ignorado por git) o en el entorno del proceso. Plantilla:
`01-TOOLS/mysql/.env.example`.

| Variable | Producción | Para qué |
|---|---|---|
| `DB_NAME` `DB_USER` `DB_PASSWORD` | datos reales | Conexión de la aplicación |
| `APP_URL` | `https://tu-dominio.com` | Base del sitemap y de los enlaces absolutos. **Sin barra final y sin `/public`** |
| `APP_DEBUG` | `false` | Con `true` se muestran trazas al visitante |
| `SESSION_SECURE` | `true` | Cookie de sesión solo por HTTPS (si HTTPS está en la petición se activa sola) |
| `CRON_TOKEN` | 64 caracteres hex | Autenticación de `/cron/*` |

```bash
# Generar el token si aún no existe
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Comprobación:

```bash
php -r "
\$_SERVER['HTTP_HOST']='localhost';
require 'config/paths.php';
\$c = require CONFIG_PATH.'/config.php';
printf(\"app_url=%s\ndebug=%s\nsecure=%s\ncron_token=%s\n\",
  \$c['app_url'], var_export(\$c['debug'],true), var_export(\$c['session']['secure'],true),
  \$c['cron_token']==='' ? 'VACIO' : strlen(\$c['cron_token']).' caracteres');
"
```

Esperado: `app_url` con el dominio real, `debug=false`, `secure=true`, token de 64 caracteres.

**Estado aquí:** ✅ las cuatro variables se leen del entorno y se comportan como corresponde
(`debug` y `secure` verificados en ambos modos).

---

## 3. Base de datos

⬜ **3.1 Crear la base y el usuario de la aplicación.**

El usuario de la aplicación **no debe poder crear ni alterar tablas**: el esquema se aplica con la cuenta
administrativa y la aplicación solo lee y escribe datos.

```sql
CREATE DATABASE web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
CREATE USER 'web_editor_app'@'localhost' IDENTIFIED BY '<contraseña>';
GRANT SELECT, INSERT, UPDATE, DELETE ON web_editor_db.* TO 'web_editor_app'@'localhost';
```

✅ Verificado aquí: la cuenta de la aplicación **no** puede `CREATE` ni `SHOW VIEW`. Es lo correcto y no
hay que "arreglarlo".

⬜ **3.2 Importar la instalación limpia.**

```bash
mysql -u root -p web_editor_db < db/install/initial_database.sql
```

Sin datos de nadie: estructura + catálogos. **No** importes `db/db-full.sql` en producción: es un volcado de
referencia y empieza con `CREATE DATABASE`/`USE`, así que escribe en la base que diga el archivo.

⬜ **3.3 Aplicar migraciones pendientes** (si vienes de una versión anterior):

```bash
php db/migrations/apply.php --admin 2026_11_01_footer_designs.up.sql
```

`--admin` es obligatorio para cambios de esquema: el runner usa la cuenta de la aplicación por defecto.

⬜ **3.4 Crear el primer Super Admin.**

```bash
php 01-TOOLS/bootstrap/create-admin.php --generate
```

Genera una contraseña aleatoria y la imprime **una sola vez**. El script pide confirmar la base de datos
destino: escribe su nombre. (Existe por un incidente real: una ejecución sin `DB_NAME` escribió en la base
equivocada.)

⬜ **3.5 Sembrar las páginas del sistema** (opcional, solo en una instalación nueva):

```bash
php 01-TOOLS/bootstrap/seed-pages.php
```

---

## 4. Sistema de archivos

```bash
sudo chown -R www-data:www-data storage uploads
sudo chmod -R 775 storage uploads
```

| Directorio | Para qué |
|---|---|
| `storage/cache` | Caché de páginas |
| `storage/logs` | Errores y correo |
| `storage/backups` | Copias de seguridad |
| `storage/versions` | Versiones de páginas |
| `uploads/` | Medios subidos (fuera del código) |

✅ Verificado aquí: `storage/backups` existe y admite escritura.

⬜ **4.1 El `.env` nunca se versiona.** Comprobación:

```bash
git check-ignore -v 01-TOOLS/mysql/.env     # debe imprimir la regla que lo ignora
git ls-files | grep -c '\.env$'             # debe imprimir 0
```

✅ Verificado aquí: ignorado por `01-TOOLS/.gitignore`.

⬜ **4.2 Los respaldos tampoco.** `db/backup/` está en `.gitignore`; un volcado lleva datos personales,
hashes de contraseñas y secretos de API.

---

## 5. Copias de seguridad

⬜ **5.1 Programar la copia diaria.** El endpoint hace una copia completa (base de datos + archivos) y la
registra como no manual.

```cron
# Copia diaria a las 03:15
15 3 * * * curl -fsS "https://tu-dominio.com/cron/backup?token=<CRON_TOKEN>" > /dev/null
```

⬜ **5.2 Programar las tareas de limpieza.**

```cron
*/30 * * * * curl -fsS "https://tu-dominio.com/cron/cleanup-rate-limits?token=<CRON_TOKEN>" > /dev/null
0 4 * * *   curl -fsS "https://tu-dominio.com/cron/cleanup-trusted-devices?token=<CRON_TOKEN>" > /dev/null
0 5 * * *   curl -fsS "https://tu-dominio.com/cron/process-expired-bans?token=<CRON_TOKEN>" > /dev/null
30 6 * * *  curl -fsS "https://tu-dominio.com/cron/security-digest?token=<CRON_TOKEN>" > /dev/null
0 7 * * *   curl -fsS "https://tu-dominio.com/cron/cleanup-collaboration?token=<CRON_TOKEN>" > /dev/null
```

Comprobación de los seis (sin token deben dar **401**, con token **200**):

```bash
for r in backup process-expired-bans cleanup-rate-limits security-digest cleanup-trusted-devices cleanup-collaboration; do
  printf '%-30s sin=%s con=%s\n' "$r" \
    "$(curl -s -o /dev/null -w '%{http_code}' https://tu-dominio.com/cron/$r)" \
    "$(curl -s -o /dev/null -w '%{http_code}' "https://tu-dominio.com/cron/$r?token=<CRON_TOKEN>")"
done
```

✅ Verificado aquí: los seis dan **401** sin token, **401** con token incorrecto y **200** con el correcto.

> **Aviso de seguridad:** el token viaja en la URL, así que queda en los registros del servidor web. Usa
> HTTPS, no compartas los logs y rota el token si sospechas que se ha filtrado (cambia `CRON_TOKEN` en el
> `.env` y en el crontab).

---

## 6. Publicación y SEO

⬜ **6.1 El dominio.** Tres formas, en orden de prioridad:

1. `APP_URL` en el `.env` ← recomendado, un solo sitio.
2. Ajuste guardado desde el panel (`Configuración → Sitemap`).
3. `BASE_URL` derivada de la petición (último recurso).

La aplicación **avisa** cuando la URL base apunta a `localhost`, porque un sitemap con direcciones locales
no lo indexa ningún buscador.

```bash
curl -s https://tu-dominio.com/sitemap.xml | head -5
# debe mostrar <loc>https://tu-dominio.com/...</loc>
```

✅ Verificado aquí: la URL base sigue el orden panel → `APP_URL` → petición, y avisa con localhost.

⬜ **6.2 Comprobar el sitemap:** que `robots.txt` apunte a él y que las URLs sean las públicas (sin
`/public`, sin `localhost`).

⬜ **6.3 Favicon y logotipos** cargados desde `Configuración → Logotipos`.

---

## 7. Seguridad del despliegue

| Punto | Comprobación | Esperado |
|---|---|---|
| HTTPS obligatorio | `curl -I http://tu-dominio.com` | 301 a `https://` |
| HSTS | `curl -I https://tu-dominio.com` | `Strict-Transport-Security` |
| Cabeceras | ídem | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` |
| Sin trazas al público | provocar un error 500 | página genérica, sin rutas ni SQL |
| Directorio `public/` es la raíz | `curl https://tu-dominio.com/../config/config.php` | 403 / 404 |
| `.env` inaccesible | `curl https://tu-dominio.com/01-TOOLS/mysql/.env` | 403 / 404 |

✅ Verificado aquí: `public/.htaccess` emite `X-Frame-Options`, `X-Content-Type-Options` y
`Referrer-Policy`.

⬜ **7.1 Las cabeceras HSTS y la redirección a HTTPS son del servidor** (Apache/nginx o el proxy). La
aplicación no las emite porque no siempre está detrás de TLS.

⬜ **7.2 Rotar la contraseña del panel** si el hash estuvo publicado alguna vez en el repositorio (deuda 7).

---

## 8. Verificación funcional tras publicar

En este orden, con una sesión de Super Admin:

- ⬜ `/login` entra y la sesión sobrevive a una recarga.
- ⬜ `Páginas → Nueva página` con plantilla; el constructor carga **bloques** y **categorías que se abren y cierran**.
- ⬜ Guardar la página y verla en el sitio público.
- ⬜ `Configuración → Editar el pie → Catálogo de diseños`: crear uno, componerlo, guardarlo y activarlo.
- ⬜ El pie aparece en una página pública y **no** aparece código raro en pantalla.
- ⬜ `Páginas → subir imagen`: llega a `uploads/` y se ve en el editor.
- ⬜ `/cron/backup?token=…` responde 200 y aparece una copia en `Copias de seguridad`.

---

## 9. Lo que este checklist no cubre

- **Certificado TLS**: se emite en el servidor o en el proxy (Let's Encrypt, Cloudflare…).
- **Copias fuera del servidor**: `storage/backups` está en el mismo disco. Lleva los ZIP a otro sitio.
- **Monitorización externa**: que alguien avise si el sitio cae.
- **Rotación del `CRON_TOKEN`** y de las contraseñas: calendario de la organización.
- **El historial de git sigue conteniendo los secretos antiguos** (deudas 7 y 16): solo se eliminan
  reescribiendo el historial, y eso es una decisión del dueño.

---

## Estado resumido

| Bloque | Estado |
|---|---|
| Requisitos del servidor | ✅ verificados aquí |
| Variables de entorno | ✅ código listo; ⬜ valores reales en el servidor |
| Base de datos | ✅ privilegios correctos; ⬜ importar y crear admin |
| Cron | ✅ seis endpoints verificados; ⬜ programar en el servidor |
| SEO / dominio | ✅ configurable; ⬜ fijar el dominio real |
| Seguridad | ✅ cabeceras de la app; ⬜ TLS y HSTS del servidor |
| Verificación funcional | ⬜ tras publicar |
