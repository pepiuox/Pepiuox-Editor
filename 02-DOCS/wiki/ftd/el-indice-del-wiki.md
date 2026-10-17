---
author: Pepiuox
branch: main
status: done
---
# El índice del wiki

## Intent

Cerrar la deuda 5: «No existe `02-DOCS/wiki/index.md`, así que nada apunta al lector a este
archivo». El wiki ya tenía la constitución, el registro de decisiones y seis fichas de cambios, pero
no tenía puerta. Quien llegaba veía tres carpetas y tenía que adivinar por dónde empezar.

El principio 26 dice lo que está en juego: «el wiki es la memoria que lee la sesión siguiente».

## Scope

**Dentro:**

- Crear `02-DOCS/wiki/index.md` como mapa de lo que el wiki tiene de verdad.
- Que `docs/README.md` apunte al índice, no a la carpeta.

**Fuera, con motivo:**

- Montar el motor OKF del skill `harness` (`inbox/`, `raw/`, `log.md`, `gaps.md`, `scores.json`,
  `.base`). Es un cambio de comportamiento con su propio ciclo —bandeja de entrada, ingestas,
  registro, sincronización— que nadie ha pedido. Decisión registrada.
- Un `## Knowledge map` en `AGENTS.md`. Ese archivo lo gestiona rsc (está en `governedPaths` con su
  digest), así que escribir ahí a mano es pedir un aviso de deriva o perderlo en la próxima
  actualización.

## Checklist

- [x] `02-DOCS/wiki/index.md` existe y empieza por `# Knowledge Base Index`.
- [x] Lista **todos** los documentos del wiki: ninguno se queda sin fila.
- [x] Dice el orden de lectura, no solo la lista.
- [x] `docs/README.md` apunta al índice.
- [x] Dice lo que **no** es, para que nadie busque aquí un motor OKF que no está.

## Evidence

**1. Lo que había, comprobado antes de escribir.** `02-DOCS/` contiene una sola carpeta, `wiki/`, con
tres subcarpetas: `ftd/` (6 fichas), `harness/` (3 documentos) y `sdd/` (la constitución). Nada más:

```
02-DOCS/
  wiki/
    ftd/      6 fichas de cambio
    harness/  decisions.md, installation-plan.md, user-profile.md
    sdd/      constitution.md
```

**2. Por qué no vale la plantilla del arnés «verbatim».** `.dsh/skills/harness/references/`
contiene `wiki-index-template.md`, pero su propio encabezado habla de que «la navegación humana vive
en las vistas `.base`» y su tabla lleva una columna `Score` «regenerada en cada pase de
mantenimiento». Aquí no hay `.base` ni hay pase de mantenimiento: copiarla habría sido describir un
sistema que no existe. El protocolo del wiki (`wiki-protocol.md`, Initialization) confirma que ese
motor crea doce cosas más, ninguna presente.

**3. Que no se ha inventado ninguna fecha.** La columna «Actualizado» sale de
`git log -1 --format=%cs` de cada archivo, no de una estimación. La única excepción es
`user-profile.md`, que está excluido de git: su fila dice «—» y avisa de que es personal.

**4. Que el índice no miente por omisión.** Se comprobó que aparecen los 10 documentos del wiki
(3 de `harness/`, 1 de `sdd/`, 6 de `ftd/`), contando los que están sin confirmar.

**5. El puntero, antes y después.** `docs/README.md` decía `| \`../02-DOCS/wiki/\` | Memoria del
proyecto: … |` — una carpeta, que no dice por dónde empezar. Ahora apunta a `index.md`.

## Next

Cerrada la deuda 5. Quedan la 7 (los hashes sembrados), la 11 (las colaciones) y la 12 (los assets
de `private/`). La 4 sigue como riesgo aceptado.
