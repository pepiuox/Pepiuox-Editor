<?php

/**
 * Catálogo de temas de diseño para las plantillas.
 *
 * ── Qué son y qué no son ────────────────────────────────────────────
 * Son **patrones**: ritmo vertical, jerarquía tipográfica, densidad, familia de
 * color y forma de los componentes. Están inspirados en familias de sitios muy
 * visitados, pero **no copian** su identidad: ni logotipos, ni marcas, ni textos,
 * ni código, ni hojas de estilo de terceros. Cada tema dice de qué familia toma
 * el patrón para que la referencia quede declarada.
 *
 * ── Cómo están escritos ────────────────────────────────────────────
 * HTML con la rejilla de Bootstrap 5.3 y clases propias con prefijo, y CSS en
 * `content_css` con variables acotadas a una clase contenedora. Ni un `<style>`
 * dentro del HTML: el saneador lo elimina al publicar. Ni un `<script>`: el
 * layout público ya carga el bundle de Bootstrap.
 *
 * ── Cómo se añade un tema ──────────────────────────────────────────
 * Una entrada más en el array: `name`, `description`, `inspiration`, `html`,
 * `css`. El sembrador no necesita cambios.
 *
 * @package PePiuoX\Editor\Tools
 * @since   2026-11-01
 */

declare(strict_types=1);

return [

    // ═══════════════════════════════════════════════════════════════
    'claridad' => [
        'name' => 'Claridad (producto SaaS)',
        'description' => 'Fondo claro, mucho aire, tipografía grande y un acento violeta. '
            . 'Para presentar un producto o servicio con una propuesta de valor única.',
        'inspiration' => 'patrón de las páginas de producto de herramientas SaaS: hero centrado, '
            . 'prueba de confianza debajo y tres tarjetas con borde fino.',
        'html' => <<<'HTML'
<div class="th-claridad">
  <section class="th-hero">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-9">
          <span class="th-pill">Nuevo · disponible ahora</span>
          <h1 class="th-title">Publica tu web sin pelearte con el servidor</h1>
          <p class="th-lead">
            Un panel, un constructor visual y tu contenido. Sin plantillas rígidas ni dependencias que
            decidan por ti.
          </p>
          <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
            <a class="th-btn-primary" href="/funciones">Empezar</a>
            <a class="th-btn-quiet" href="/como-funciona">Ver cómo funciona</a>
          </div>
          <p class="th-note">Sin tarjeta · sin límites de páginas · en tu propio servidor</p>
        </div>
      </div>
    </div>
  </section>

  <section class="th-section">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <article class="th-card">
            <span class="th-card-icon"><i class="bi bi-layers"></i></span>
            <h2 class="th-card-title">Composición por bloques</h2>
            <p class="th-card-text">Elige una sección, arrástrala y ajusta el texto. La maqueta se mantiene coherente sola.</p>
          </article>
        </div>
        <div class="col-md-4">
          <article class="th-card">
            <span class="th-card-icon"><i class="bi bi-bounding-box"></i></span>
            <h2 class="th-card-title">Diseño adaptable</h2>
            <p class="th-card-text">Cada bloque se ve bien en móvil y escritorio sin retoques a mano.</p>
          </article>
        </div>
        <div class="col-md-4">
          <article class="th-card">
            <span class="th-card-icon"><i class="bi bi-clock"></i></span>
            <h2 class="th-card-title">Historial de cambios</h2>
            <p class="th-card-text">Vuelve a cualquier versión anterior de una página con un clic.</p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="th-cta">
    <div class="container text-center">
      <h2 class="th-cta-title">Listo para tu primera página</h2>
      <p class="th-cta-text">Se tarda menos en publicarla que en leer esta frase.</p>
      <a class="th-btn-primary" href="/admin">Entrar al panel</a>
    </div>
  </section>
</div>
HTML,
        'css' => <<<'CSS'
.th-claridad {
  --th-ink: #0B1020;
  --th-body: #47506B;
  --th-accent: #635BFF;
  --th-accent-soft: #EEECFF;
  --th-line: #E6E9F2;
  --th-bg: #FFFFFF;
  --th-bg-soft: #F6F8FC;
  background: var(--th-bg);
  color: var(--th-ink);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
.th-claridad h1, .th-claridad h2 { letter-spacing: -.03em; }
.th-hero { padding: 6rem 0 4.5rem; background:
  radial-gradient(900px 460px at 50% -20%, var(--th-accent-soft), transparent 65%), var(--th-bg); }
.th-pill {
  display: inline-block; margin-bottom: 1.25rem;
  padding: .35rem .85rem; border-radius: 999px;
  background: var(--th-accent-soft); color: var(--th-accent);
  font-size: .8rem; font-weight: 600;
}
.th-title { font-size: clamp(2.2rem, 5vw, 3.6rem); font-weight: 700; line-height: 1.08; margin-bottom: 1.1rem; }
.th-lead { font-size: 1.15rem; line-height: 1.6; color: var(--th-body); max-width: 34em; margin: 0 auto; }
.th-note { margin: 1.5rem 0 0; font-size: .85rem; color: var(--th-body); }
.th-btn-primary {
  display: inline-block; padding: .8rem 1.6rem; border-radius: 10px;
  background: var(--th-accent); color: #fff; font-weight: 600; text-decoration: none;
  box-shadow: 0 8px 24px rgba(99,91,255,.28);
}
.th-btn-primary:hover { background: #4f47e0; color: #fff; }
.th-btn-quiet { display: inline-block; padding: .8rem 1.35rem; border-radius: 10px; color: var(--th-ink); font-weight: 600; text-decoration: none; border: 1px solid var(--th-line); }
.th-btn-quiet:hover { border-color: var(--th-accent); color: var(--th-accent); }
.th-section { padding: 4rem 0; background: var(--th-bg-soft); }
.th-card { height: 100%; background: #fff; border: 1px solid var(--th-line); border-radius: 14px; padding: 1.75rem 1.5rem; }
.th-card-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 42px; height: 42px; border-radius: 11px;
  background: var(--th-accent-soft); color: var(--th-accent); font-size: 1.15rem; margin-bottom: 1rem;
}
.th-card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: .5rem; }
.th-card-text { color: var(--th-body); margin: 0; font-size: .95rem; line-height: 1.6; }
.th-cta { padding: 4.5rem 0; }
.th-cta-title { font-size: clamp(1.6rem, 3vw, 2.3rem); font-weight: 700; margin-bottom: .6rem; }
.th-cta-text { color: var(--th-body); margin-bottom: 1.75rem; }
CSS,
    ],

    // ═══════════════════════════════════════════════════════════════
    'editorial' => [
        'name' => 'Editorial (publicación)',
        'description' => 'Titulares con serif, fondo crema y líneas finas. Para un blog, '
            . 'una revista o una web de contenidos donde lo importante es leer.',
        'inspiration' => 'patrón de las publicaciones digitales de referencia: columna ancha, '
            . 'jerarquía por tamaño y peso, y reglas horizontales en lugar de tarjetas.',
        'html' => <<<'HTML'
<div class="th-editorial">
  <section class="th-masthead">
    <div class="container">
      <div class="row">
        <div class="col-lg-9">
          <p class="th-kicker">Edición de apertura</p>
          <h1 class="th-headline">Escribir bien sigue siendo la mejor tecnología</h1>
          <p class="th-deck">
            Una plataforma para publicar textos largos con la tipografía y el espacio que se merecen,
            sin que el diseño se interponga en la lectura.
          </p>
          <p class="th-byline">Por la redacción · 5 minutos de lectura</p>
        </div>
      </div>
    </div>
  </section>

  <section class="th-body-section">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-8">
          <article class="th-article">
            <h2 class="th-subhead">Un diseño que no compite con el texto</h2>
            <p>
              El contenido manda. La columna mantiene una medida de lectura cómoda, los interlineados
              dejan respirar y los enlaces se distinguen sin gritar.
            </p>
            <p>
              Este texto es de ejemplo: sirve para ver cómo respira la plantilla. Cámbialo por el tuyo y
              la estructura se adapta.
            </p>
            <blockquote class="th-pull">
              La buena tipografía es invisible: solo notas cuando falta.
            </blockquote>
          </article>
        </div>
        <div class="col-lg-4">
          <aside class="th-aside">
            <h2 class="th-aside-title">En este número</h2>
            <ul class="th-aside-list">
              <li>Tipografía y jerarquía</li>
              <li>Medida de lectura</li>
              <li>Reglas frente a tarjetas</li>
              <li>Imágenes con pie</li>
            </ul>
          </aside>
        </div>
      </div>
    </div>
  </section>
</div>
HTML,
        'css' => <<<'CSS'
.th-editorial {
  --th-paper: #FAF7F2;
  --th-ink: #14110F;
  --th-quiet: #5B544D;
  --th-rule: #DED6CA;
  --th-accent: #B3261E;
  background: var(--th-paper);
  color: var(--th-ink);
  font-family: Georgia, "Iowan Old Style", "Times New Roman", serif;
}
.th-editorial .container { max-width: 1080px; }
.th-masthead { padding: 5rem 0 2.5rem; border-bottom: 1px solid var(--th-rule); }
.th-kicker {
  text-transform: uppercase; letter-spacing: .18em; font-size: .72rem;
  color: var(--th-accent); margin-bottom: 1rem;
  font-family: system-ui, sans-serif; font-weight: 700;
}
.th-headline { font-size: clamp(2.1rem, 5vw, 3.4rem); line-height: 1.12; margin-bottom: 1.1rem; }
.th-deck { font-size: 1.2rem; line-height: 1.62; color: var(--th-quiet); max-width: 36em; }
.th-byline { margin-top: 1.6rem; font-size: .82rem; color: var(--th-quiet); font-family: system-ui, sans-serif; }
.th-body-section { padding: 3rem 0 5rem; }
.th-subhead { font-size: 1.6rem; margin-bottom: 1rem; }
.th-article p { font-size: 1.08rem; line-height: 1.78; margin-bottom: 1.15rem; }
.th-article p:first-of-type::first-letter {
  float: left; font-size: 3.4rem; line-height: .82; padding: .1rem .55rem .1rem 0; color: var(--th-accent);
}
.th-pull {
  margin: 2rem 0; padding: 1.1rem 0 1.1rem 1.4rem;
  border-left: 3px solid var(--th-accent);
  font-size: 1.3rem; line-height: 1.45; color: var(--th-ink);
}
.th-aside { border-top: 2px solid var(--th-ink); padding-top: 1rem; }
.th-aside-title { font-size: .78rem; text-transform: uppercase; letter-spacing: .14em; font-family: system-ui, sans-serif; margin-bottom: .9rem; }
.th-aside-list { list-style: none; margin: 0; padding: 0; }
.th-aside-list li { padding: .6rem 0; border-bottom: 1px solid var(--th-rule); font-size: .98rem; }
CSS,
    ],

    // ═══════════════════════════════════════════════════════════════
    'tecnico-oscuro' => [
        'name' => 'Técnico oscuro (desarrolladores)',
        'description' => 'Fondo casi negro, monoespaciada para los detalles y acentos fríos. '
            . 'Para documentación, herramientas y productos dirigidos a quien programa.',
        'inspiration' => 'patrón de las webs de herramientas para desarrolladores: hero de una línea, '
            . 'bloque de código visible y rejilla de características con bordes de 1 px.',
        'html' => <<<'HTML'
<div class="th-tecnico">
  <section class="th-hero-tech">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <p class="th-tag">v1.0 · estable</p>
          <h1 class="th-tech-title">Instala, publica, olvídate del resto</h1>
          <p class="th-tech-lead">
            Un CMS que se lee como el código que ejecuta: consultas preparadas, permisos por acción y
            migraciones reversibles.
          </p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <a class="th-tech-btn" href="/funciones">Ver funciones</a>
            <a class="th-tech-link" href="/seguridad">Leer la política</a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="th-terminal">
            <div class="th-terminal-bar"><span></span><span></span><span></span></div>
            <pre class="th-terminal-body"><code>$ php 01-TOOLS/despliegue/verificar.php

✔ PHP              8.4
✔ Extensiones      pdo_mysql, zip, gd
✔ Base de datos    mínimo privilegio
✘ URL pública      apunta a localhost</code></pre>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="th-grid-tech">
    <div class="container">
      <div class="row g-0">
        <div class="col-md-4 th-cell">
          <h2 class="th-cell-title">Consultas preparadas</h2>
          <p class="th-cell-text">PDO en todas partes, sin concatenar nunca una entrada del usuario.</p>
        </div>
        <div class="col-md-4 th-cell">
          <h2 class="th-cell-title">Permisos por acción</h2>
          <p class="th-cell-text">Un mapa único decide qué puede hacer cada rol en cada ruta.</p>
        </div>
        <div class="col-md-4 th-cell">
          <h2 class="th-cell-title">Migraciones reversibles</h2>
          <p class="th-cell-text">Cada cambio de esquema tiene su vuelta atrás y su comprobación de idempotencia.</p>
        </div>
      </div>
    </div>
  </section>
</div>
HTML,
        'css' => <<<'CSS'
.th-tecnico {
  --th-black: #0A0A0A;
  --th-panel: #121212;
  --th-line: #232323;
  --th-text: #EDEDED;
  --th-dim: #A1A1A1;
  --th-cyan: #22D3EE;
  --th-lime: #A3E635;
  background: var(--th-black);
  color: var(--th-text);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
.th-hero-tech { padding: 5.5rem 0; border-bottom: 1px solid var(--th-line); }
.th-tag {
  display: inline-block; margin-bottom: 1.1rem; padding: .25rem .6rem;
  border: 1px solid var(--th-line); border-radius: 6px;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: .74rem; color: var(--th-cyan);
}
.th-tech-title { font-size: clamp(2rem, 4.6vw, 3.1rem); line-height: 1.1; letter-spacing: -.02em; margin-bottom: 1rem; color: #fff; }
.th-tech-lead { font-size: 1.1rem; line-height: 1.65; color: var(--th-dim); max-width: 32em; }
.th-tech-btn {
  display: inline-block; padding: .75rem 1.4rem; border-radius: 8px;
  background: var(--th-text); color: var(--th-black); font-weight: 600; text-decoration: none;
}
.th-tech-btn:hover { background: var(--th-lime); color: var(--th-black); }
.th-tech-link { display: inline-block; padding: .75rem 0; color: var(--th-cyan); text-decoration: none; font-weight: 500; }
.th-tech-link:hover { text-decoration: underline; }
.th-terminal { background: var(--th-panel); border: 1px solid var(--th-line); border-radius: 12px; overflow: hidden; }
.th-terminal-bar { display: flex; gap: .4rem; padding: .7rem .9rem; border-bottom: 1px solid var(--th-line); }
.th-terminal-bar span { width: .6rem; height: .6rem; border-radius: 50%; background: #3a3a3a; display: inline-block; }
.th-terminal-bar span:first-child { background: #F87171; }
.th-terminal-bar span:nth-child(2) { background: #FBBF24; }
.th-terminal-bar span:nth-child(3) { background: var(--th-lime); }
.th-terminal-body {
  margin: 0; padding: 1.1rem 1.15rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: .84rem; line-height: 1.75; color: var(--th-dim); overflow-x: auto;
}
.th-terminal-body code { color: inherit; background: none; padding: 0; }
.th-grid-tech { padding: 0; }
.th-grid-tech .container { max-width: 100%; padding: 0; }
.th-cell { padding: 2.5rem 2rem; border-right: 1px solid var(--th-line); border-bottom: 1px solid var(--th-line); }
.th-cell-title { font-size: 1rem; font-weight: 600; margin-bottom: .6rem; color: #fff; }
.th-cell-text { margin: 0; font-size: .93rem; line-height: 1.65; color: var(--th-dim); }
@media (max-width: 767.98px) { .th-cell { border-right: none; } }
CSS,
    ],

    // ═══════════════════════════════════════════════════════════════
    'marca' => [
        'name' => 'Marca (naranja y verde)',
        'description' => 'La paleta de la marca: naranjas cálidos sobre verdes profundos. '
            . 'Es el mismo sistema visual de la portada que se sembró con la instalación.',
        'inspiration' => 'sistema propio de la plataforma, con la paleta declarada en '
            . 'docs/ESPECIFICACIONES_TECNICAS.md.',
        'html' => <<<'HTML'
<div class="th-marca">
  <section class="th-marca-hero">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-7">
          <span class="th-marca-badge">Hecho para durar</span>
          <h1 class="th-marca-title">Contenido propio, en servidor propio</h1>
          <p class="th-marca-lead">
            Sin dependencias que cambien de precio ni plataformas que decidan qué puedes publicar.
          </p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <a class="th-marca-btn" href="/funciones">Ver funciones</a>
            <a class="th-marca-ghost" href="/como-funciona">Cómo funciona</a>
          </div>
        </div>
        <div class="col-lg-5">
          <ul class="th-marca-list">
            <li><i class="bi bi-check2-circle"></i>Constructor visual con bloques de Bootstrap 5</li>
            <li><i class="bi bi-check2-circle"></i>Portada y pie editables</li>
            <li><i class="bi bi-check2-circle"></i>Copias de seguridad y versiones</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section class="th-marca-band">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="th-marca-card">
            <h2 class="th-marca-card-title">Sin sorpresas</h2>
            <p class="th-marca-card-text">Todo el código está en tu servidor y se puede auditar.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="th-marca-card">
            <h2 class="th-marca-card-title">Sin ataduras</h2>
            <p class="th-marca-card-text">Exporta tus datos y tus copias cuando quieras.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="th-marca-card">
            <h2 class="th-marca-card-title">Sin ruido</h2>
            <p class="th-marca-card-text">Solo lo necesario para publicar y mantener el sitio.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
HTML,
        'css' => <<<'CSS'
.th-marca {
  --m-orange: #C46A00; --m-amber: #F0B429; --m-yellow: #FDE047;
  --m-yellow-soft: #FEF08A; --m-cream: #FFFFF0; --m-green: #4B8B5A;
  --m-green-dark: #1B5E20; --m-green-deep: #0D3B0D; --m-ink: #041A04;
  background: var(--m-cream); color: var(--m-ink);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
}
.th-marca-hero {
  padding: 5.5rem 0;
  background: linear-gradient(155deg, var(--m-green-deep), var(--m-green-dark) 60%, var(--m-green));
  color: var(--m-cream);
}
.th-marca-badge {
  display: inline-block; margin-bottom: 1.1rem; padding: .3rem .8rem; border-radius: 999px;
  background: rgba(253,224,71,.16); border: 1px solid rgba(253,224,71,.45); color: var(--m-yellow);
  font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
}
.th-marca-title { font-size: clamp(2.1rem, 4.8vw, 3.4rem); line-height: 1.1; color: #fff; margin-bottom: 1rem; }
.th-marca-lead { font-size: 1.15rem; line-height: 1.65; color: var(--m-yellow-soft); max-width: 32em; }
.th-marca-btn {
  display: inline-block; padding: .8rem 1.5rem; border-radius: 999px;
  background: var(--m-amber); color: var(--m-ink); font-weight: 600; text-decoration: none;
}
.th-marca-btn:hover { background: var(--m-yellow); color: var(--m-ink); }
.th-marca-ghost {
  display: inline-block; padding: .8rem 1.4rem; border-radius: 999px;
  border: 1px solid rgba(255,255,255,.45); color: var(--m-cream); text-decoration: none;
}
.th-marca-ghost:hover { background: rgba(255,255,255,.12); color: #fff; }
.th-marca-list { list-style: none; margin: 0; padding: 1.25rem 1.35rem; border-radius: 16px;
  background: rgba(4,26,4,.55); border: 1px solid rgba(254,240,138,.22); }
.th-marca-list li { display: flex; align-items: center; gap: .6rem; padding: .45rem 0; color: var(--m-cream); font-size: .95rem; }
.th-marca-list i { color: var(--m-yellow); }
.th-marca-band { padding: 4rem 0; }
.th-marca-card {
  height: 100%; background: #fff; border: 1px solid rgba(27,94,32,.14);
  border-radius: 16px; padding: 1.6rem 1.4rem;
  border-top: 4px solid var(--m-amber);
}
.th-marca-card-title { font-size: 1.05rem; font-weight: 700; color: var(--m-green-deep); margin-bottom: .5rem; }
.th-marca-card-text { margin: 0; color: #2c3d2e; font-size: .95rem; line-height: 1.6; }
CSS,
    ],

];
