# bootstrap — arranque de una instalación nueva

Herramientas de terminal que dejan una base recién importada en un estado usable. Viven aquí, fuera
de `public/`, porque la constitución (principio 16) prohíbe que algo que instala sea alcanzable por
web. No es `01-TOOLS/diagnostics/`: esa carpeta es un cementerio de scripts retirados y no se
reutiliza para herramientas nuevas.

## `create-admin.php`

Crea la cuenta Super Admin. Existe porque `db/install/initial_database.sql` **no incluye ninguna
cuenta ni ninguna contraseña**: un hash versionado se puede atacar sin prisa, y un hash de ejemplo
compartido por todas las instalaciones sería peor.

```powershell
# 1. Importa la estructura y los catálogos
mysql -u <admin> -p < db/install/initial_database.sql

# 2. Crea la cuenta (pregunta o genera la contraseña)
php 01-TOOLS/bootstrap/create-admin.php --username=admin --email=tu@dominio.com --generate

# 3. Abre /login
```

| Indicador | Para qué |
|---|---|
| `--username=<nombre>` | Obligatorio. 3–50 caracteres: letras, números, `.` `_` `-` |
| `--email=<correo>` | Obligatorio. Correo válido |
| `--generate` | Genera una contraseña de 20 caracteres y la muestra **una sola vez** |
| `--force` | Si ya hay un Super Admin, reescribe su contraseña en vez de negarse |
| `--yes` | Confirma el destino sin preguntar (para automatizar) |

**Contraseña.** En este orden: la variable de entorno `ADMIN_PASSWORD` (mínimo 12 caracteres), o una
generada. No se acepta por línea de comandos a propósito: quedaría en el historial del shell y en la
lista de procesos.

**El destino se confirma.** El script imprime la base de datos contra la que va a escribir y pide
escribir su nombre. Sin `--yes` y sin terminal interactiva, aborta.

> Esta parada existe por un incidente real del 2026-10-31. El script se ejecutó en una prueba sin
> `DB_NAME` en el entorno, cayó al `.env` y reescribió la contraseña de un Super Admin en la base de
> producción en lugar de la de pruebas. Se reparó con el respaldo del mismo día, verificando después
> que los tres usuarios coincidían bit a bit con él. El detalle está en
> `02-DOCS/wiki/ftd/portada-comercial-y-paquete-installable.md`, sección «Incidente».

**Lo que comprueba antes de escribir:** que el rol `Super Admin` exista en `users_roles` (si no, la
base no se importó), que no haya ya un Super Admin sin `--force`, y que el correo sea válido.
