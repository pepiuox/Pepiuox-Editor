# Knowledge Base Index

Este wiki es la memoria del proyecto: la constitución, el registro de decisiones y una ficha por
cada cambio. Se lee de arriba abajo y en quince minutos alguien nuevo sabe qué se decidió y por qué.

**Orden de lectura:** empieza por los tres documentos de la primera tabla, en ese orden. Después, la
ficha del cambio que vayas a tocar.

> **Qué no es este wiki.** El skill `harness` describe un motor de conocimiento OKF (temas con
> artículos, `log.md`, `gaps.md`, `scores.json` y vistas `.base`). Aquí eso no está montado: lo que
> hay es la estructura de SDD y FTD. Por eso el índice no lleva columna `Score` (no hay pase de
> mantenimiento que la calcule) ni hay ficheros OKF. Si algún día se monta el motor, se añade sin
> tocar esto.

## Empezar por aquí

| Documento | Qué es | Actualizado |
|-----------|--------|-------------|
| [`harness/user-profile.md`](harness/user-profile.md) | Quién es el dueño, su nivel técnico y el objetivo del proyecto. Es personal y no se confirma en git. | — |
| [`sdd/constitution.md`](sdd/constitution.md) | Las reglas del proyecto (26 principios), la definición de «terminado» y la tabla de deuda abierta. Es la autoridad. | 2026-10-17 |
| [`harness/decisions.md`](harness/decisions.md) | Registro solo-añadir: qué se decidió, qué opciones había y por qué se eligió. | 2026-10-17 |

## sdd — la autoridad

La constitución. Si algo la contradice, lo que sobra es lo otro, no el principio.

| Documento | Qué es | Actualizado |
|-----------|--------|-------------|
| [`sdd/constitution.md`](sdd/constitution.md) | 26 principios, la definición de «terminado» y las deudas numeradas (cerradas y abiertas). | 2026-10-17 |

## harness — cómo funciona el arnés y por qué

| Documento | Qué es | Actualizado |
|-----------|--------|-------------|
| [`harness/installation-plan.md`](harness/installation-plan.md) | El plan aceptado al instalar el arnés: agentes, skills y capacidades elegidos, con el motivo de cada uno. | 2026-10-16 |
| [`harness/decisions.md`](harness/decisions.md) | El registro de decisiones, en orden inverso. | 2026-10-17 |
| [`harness/user-profile.md`](harness/user-profile.md) | El perfil del dueño. Personal; excluido de git. | — |

## ftd — una ficha por cambio

Cada ficha cuenta el mismo cambio: intención, alcance, lista de comprobación y la evidencia medida,
con lo que quedó sin cubrir dicho en voz alta. De la más nueva a la más vieja.

| Documento | Qué es | Actualizado |
|-----------|--------|-------------|
| [`ftd/el-indice-del-wiki.md`](ftd/el-indice-del-wiki.md) | Este índice existe, para que la sesión siguiente sepa por dónde entrar. Deuda 5. | 2026-10-17 |
| [`ftd/retirar-el-tercer-punto-de-entrada.md`](ftd/retirar-el-tercer-punto-de-entrada.md) | `private/index.php` sale de la raíz web: estaba roto y nadie lo usaba. Deuda 10. | 2026-10-17 |
| [`ftd/instalacion-documentada-y-puntos-de-entrada.md`](ftd/instalacion-documentada-y-puntos-de-entrada.md) | La guía de instalación pasa a ser real y los puntos de entrada dejan de mentir. Además: la portada publicaba el árbol de archivos del proyecto. Deudas 3 y 9. | 2026-10-17 |
| [`ftd/instalacion-sin-composer.md`](ftd/instalacion-sin-composer.md) | Composer sale de los requisitos: el proyecto no lo usa. Deuda 6. | 2026-10-17 |
| [`ftd/usuario-de-bd-con-permisos-minimos.md`](ftd/usuario-de-bd-con-permisos-minimos.md) | La aplicación deja de conectarse como superusuario. Deuda 8. | 2026-10-16 |
| [`ftd/rotar-credenciales.md`](ftd/rotar-credenciales.md) | La contraseña filtrada se rota y se verifica en un proceso PHP nuevo. | 2026-10-16 |
| [`ftd/credenciales-y-scripts-fuera-de-la-raiz-web.md`](ftd/credenciales-y-scripts-fuera-de-la-raiz-web.md) | Las credenciales salen de git y los scripts de diagnóstico salen de la raíz web. Deudas 1 y 2. | 2026-10-16 |

## Cómo se mantiene

- Un cambio que altera comportamiento añade o actualiza su ficha en `ftd/` **en el mismo cambio**
  (principio 26), y añade su fila aquí.
- Una decisión con opciones se anota en `harness/decisions.md`, con lo que se descartó y por qué.
- Un hallazgo que contradice un principio se registra como deuda numerada en `sdd/constitution.md`,
  se cierre o no en ese momento.
