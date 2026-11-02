# Instalación · PePiuoX Editor

Cómo se construye el paquete de producción y cómo se pone en un servidor real.

> Cada paquete lleva **su propio `INSTALAR.md`** con los datos concretos de esa
> compilación: dominio, credenciales de la cuenta inicial y cuántas páginas y
> menús viajan. Este documento es la versión del repositorio. Si hay
> diferencias, manda el del paquete.

---

## 1. Construir el paquete

El paquete **no se copia a mano**: se genera. Vive en `backup/` y está ignorado
por git, porque se reconstruye con una orden.

```bash
# Sitio nuevo, en blanco: lo que instala el repositorio
php 01-TOOLS/despliegue/build-backup.php --yes

# Sitio ya montado: se lleva el contenido de una base existente
php 01-TOOLS/despliegue/build-backup.php --yes --desde-base=pepiuox_editor
```

| Opción | Para qué |
|---|---|
| `--yes` | Confirma. Sin esto no se escribe nada: `backup/` se borra y se reconstruye entero |
| `--dry-run` | Enseña qué se copiaría y qué se descarta, sin tocar nada |
| `--desde-base=<nombre>` | Construye el SQL a partir de esa base: contenido, menús, ajustes y cuenta incluidos |
| `--sin-db` | Solo archivos, sin SQL |
| `--dominio=` | Dominio de destino (por defecto `https://pepiuox.net`) |

El generador **verifica lo que produce**: importa el SQL en una base aislada,
comprueba esquema, catálogos, cuenta y contenido, y borra las bases de trabajo
al terminar.

---

## 2. Requisitos del servidor

| | Mínimo |
|---|---|
| PHP | 8.2 o superior |
| Extensiones | `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`, `zip`, `gd`, `curl` |
| Base de datos | **MariaDB 11 o superior** |
| Servidor web | Apache con `mod_rewrite`, o nginx con la reescritura equivalente |
| Document root | la carpeta **`public/`**, nunca la raíz del paquete |

> **MySQL 8 no sirve.** El esquema usa la colación `utf8mb4_uca1400_ai_ci`, que
> solo existe en MariaDB. En MySQL la importación falla en la primera tabla.

Comprobación, desde la raíz del paquete:

```bash
php 01-TOOLS/despliegue/verificar.php
```

---

## 3. Subir y preparar

```bash
# 1. Descomprimir fuera del document root
unzip backup.zip -d /var/www/pepiuox
cd /var/www/pepiuox

# 2. Permisos de escritura para el usuario del servidor web
sudo chown -R www-data:www-data storage uploads
sudo chmod -R 775 storage uploads
```

El document root del dominio debe apuntar a **`public/`**.

---

## 4. Base de datos e importación

Crea la base **vacía** y un usuario para la aplicación:

```sql
CREATE DATABASE pepiuox_net CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
CREATE USER 'pepiuox_app'@'localhost' IDENTIFIED BY '<contraseña nueva>';
GRANT ALL PRIVILEGES ON pepiuox_net.* TO 'pepiuox_app'@'localhost';
```

El `GRANT ALL` es **solo para poder importar**: el volcado crea tablas, índices,
disparadores, vistas y rutinas. Importa:

```bash
mysql -u pepiuox_app -p pepiuox_net < db/install/initial_database.sql
```

O por phpMyAdmin: selecciona la base → **Importar** → elige el archivo.

**No hace falta la cuenta `root` ni privilegios de administrador.** El archivo no
lleva cláusulas `DEFINER`, así que los disparadores, vistas y rutinas quedan a
nombre de quien importa. Tampoco lleva `CREATE DATABASE` ni `USE`: rellena
exactamente la base que le indiques, con el nombre que tenga.

Cuando termine la importación, reduce los permisos. La aplicación nunca modifica
el esquema:

```sql
REVOKE ALL PRIVILEGES ON pepiuox_net.* FROM 'pepiuox_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON pepiuox_net.* TO 'pepiuox_app'@'localhost';
FLUSH PRIVILEGES;
```

Comprueba que entró todo: **53 tablas**, 2 vistas, 4 disparadores y 5 rutinas.

```sql
SELECT COUNT(*) FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = 'pepiuox_net' AND TABLE_TYPE = 'BASE TABLE';
```

---

## 5. Editar el `.env`

Un solo archivo, `01-TOOLS/mysql/.env`. El dominio y los indicadores de
producción ya vienen puestos; **rellena las claves de la base**:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=pepiuox_net
DB_USER=pepiuox_app
DB_PASSWORD=<la contraseña del paso 4>
```

Nada más es obligatorio. Ya vienen configurados:

| Clave | Valor |
|---|---|
| `APP_URL` | el dominio de destino, sin barra final |
| `APP_DEBUG` | `false` |
| `SESSION_SECURE` | `true` |
| `CRON_TOKEN` | generado en cada compilación |

Si cambias `CRON_TOKEN`, cámbialo también en el crontab del paso 7.

---

## 6. Primer acceso

Las credenciales están en el `INSTALAR.md` del paquete, en el apartado
«Credenciales de la cuenta inicial».

> **Cambia esa contraseña en el primer acceso** (Perfil → Contraseña). Está
> escrita en el paquete y dentro del `.sql`: cualquiera que lo tenga la conoce.

Entra en `https://<tu-dominio>/login` y revisa **Configuración → General** para
poner el nombre real del sitio.

---

## 7. Tareas programadas

Copia el `CRON_TOKEN` de `01-TOOLS/mysql/.env` y añade al crontab:

```cron
15 3 * * *  curl -fsS "https://<tu-dominio>/cron/backup?token=<CRON_TOKEN>" > /dev/null
*/30 * * * * curl -fsS "https://<tu-dominio>/cron/cleanup-rate-limits?token=<CRON_TOKEN>" > /dev/null
```

Los seis endpoints están en `docs/CHECKLIST_PUBLICACION.md` §5. Sin token
responden 401, a propósito.

---

## 8. Qué hay dentro del paquete

```
app/                    código: controladores, modelos, servicios, núcleo
views/                  plantillas del panel y del sitio público
config/                 rutas, permisos, configuración
public/                 document root: index.php, .htaccess, assets
db/install/             el SQL de instalación (el del paso 4)
db/migrations/          7 migraciones con su reversión
01-TOOLS/bootstrap/     crear cuentas y sembrar páginas
01-TOOLS/despliegue/    verificación previa a publicar
01-TOOLS/mantenimiento/ rotar contraseñas y tokens de API
01-TOOLS/mysql/         el .env (el del paso 5)
docs/                   manual de instalación y de usuario
storage/                caché, registros y copias (vacías, escribibles)
uploads/                imágenes subidas (vacía, escribible)
```

**Fuera a propósito:** `tests/`, `02-DOCS/`, `db/backup/`, los volcados de
referencia con datos reales, `uploads/` de ejemplo, el `.env` de desarrollo, las
maquetas de diseño y los controladores y modelos duplicados documentados como
residuo en `docs/ESPECIFICACIONES_TECNICAS.md` (riesgo R-03).

---

## 9. Si algo no arranca

| Síntoma | Causa habitual |
|---|---|
| 500 en todas las rutas | El document root no es `public/`, o falta `mod_rewrite` |
| «No se puede conectar a la base» | `DB_*` mal en el `.env`, o el usuario no existe |
| `Unknown collation: utf8mb4_uca1400_ai_ci` | El servidor es MySQL, no MariaDB |
| La importación se corta a la mitad y faltan tablas | Privilegios insuficientes, o un volcado con `DEFINER` |
| `ERROR 1227: you need the SET USER privilege` | El `.sql` lleva `DEFINER`. El del paquete no; si usas otro, quítalo |
| Los assets dan 404 | El sitio no se sirve desde la raíz del dominio |
| Los `/cron/*` dan 401 | Falta `CRON_TOKEN` o no coincide con el del crontab |
| No deja guardar en el panel | Permisos de `storage/` (debe escribir el usuario del servidor web) |
| No aparecen estadísticas de página | Faltan los disparadores: la importación no se completó |

### Errores que ya están resueltos en el generador

Estos tres rompían la instalación y están corregidos en `db/install/initial_database.sql`.
Se apuntan aquí porque siguen afectando a cualquier volcado que hagas por tu cuenta:

1. **`CREATE DATABASE` / `USE` dentro del volcado.** Importado en una base nueva,
   el `USE` manda y los datos se van a otra base. El paquete no las lleva.
2. **`DEFINER=usuario@host` en disparadores, vistas y rutinas.** Con una cuenta
   normal da `ERROR 1227` y la importación se corta en el primer disparador: las
   tablas que vienen detrás no se crean y las vistas y rutinas tampoco. El
   paquete los retira.
3. **Disparadores envueltos en comentarios de versión con comentarios dentro.**
   Si el cuerpo de un disparador lleva un `/* … */`, el cliente cierra el
   comentario antes de tiempo y falla con `ERROR 1064`. El paquete los escribe
   como `CREATE TRIGGER` normal.

---

## 10. Las dos formas de construir el paquete

| | `--desde-base=<base>` | sin opción |
|---|---|---|
| Esquema y catálogos | sí | sí |
| Contenido (páginas, menús, ajustes, plantillas) | **el de esa base** | una página de introducción |
| Cuenta inicial | la de esa base, con **contraseña nueva** | `admin` con contraseña nueva |
| Registros, sesiones y tokens | se vacían siempre | vacíos |

En los dos casos la contraseña documentada es nueva y sustituye a la que tuviera
la base de origen. **La base de origen no se modifica**: solo se lee.
