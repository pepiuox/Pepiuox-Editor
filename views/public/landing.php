<?php
/**
 * Portada comercial de la plataforma.
 *
 * Se renderiza dentro de `views/public/layout.php`, que ya aporta la cabecera
 * (logotipo, nombre del sitio, menú y botón de acceso) y el pie. Esta vista
 * aporta solo las secciones centrales: hero, presentación, funciones,
 * especificaciones, beneficios y galería.
 *
 * Contrato de entrada:
 *   $landing array  Contenido resuelto por App\Services\LandingContentService::all()
 *
 * Reglas que cumple esta vista:
 *  - No fija colores literales: usa los tokens `--bg-main`, `--bg-card`,
 *    `--text-main`, `--text-muted`, `--accent` y `--border-color` que
 *    `PublicThemeService` calcula para cumplir contraste AA. Cambiar el tema en
 *    el panel no puede dejar la portada ilegible.
 *  - Todo texto se escapa con `e()`. El contenido es editable desde el panel y
 *    la portada no debe convertirse en un inyector de HTML.
 *  - Respeta `prefers-reduced-motion`: sin animaciones de entrada si el sistema
 *    lo pide.
 *  - Los identificadores de sección son estables (`#inicio`,
 *    `#presentacion`, `#caracteristicas`, `#especificaciones`, `#beneficios`,
 *    `#experiencia`) porque los enlaces de los botones apuntan a ellos.
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

$sections = $landing['sections'] ?? [];
$hero     = $landing['hero_cta_primary'] ?? ['label' => 'Entrar al sistema', 'href' => '/login'];
$heroAlt  = $landing['hero_cta_secondary'] ?? ['label' => 'Ver funciones', 'href' => '#caracteristicas'];
?>

<style>
    /* Portada. Prefijo `landing-` para no colisionar con el layout público. */
    .landing-hero {
        padding: clamp(2.5rem, 5vw, 4.5rem) 0 clamp(2rem, 4vw, 3rem);
        position: relative;
        overflow: hidden;
    }
    .landing-hero::before {
        content: "";
        position: absolute;
        inset: -25% 45% 45% -10%;
        background: radial-gradient(circle at 30% 30%, var(--accent), transparent 62%);
        opacity: .16;
        pointer-events: none;
    }
    /* Segundo foco de luz: da profundidad sin competir con el texto. */
    .landing-hero::after {
        content: "";
        position: absolute;
        inset: 20% -15% -20% 55%;
        background: radial-gradient(circle at 70% 40%, var(--accent), transparent 65%);
        opacity: .07;
        pointer-events: none;
    }
    .landing-hero > .container { position: relative; }

    .landing-kicker {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-size: .8125rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-pill, 999px);
        padding: .35rem .85rem;
        margin-bottom: 1.25rem;
    }
    .landing-kicker i { color: var(--accent); }

    .landing-title {
        font-size: clamp(2rem, 4.5vw, 3.25rem);
        line-height: 1.12;
        letter-spacing: -.02em;
        margin: 0 0 1rem;
        color: var(--text-main);
    }
    .landing-subtitle {
        font-size: clamp(1.05rem, 1.6vw, 1.3rem);
        color: var(--text-main);
        opacity: .88;
        margin: 0 0 1rem;
    }
    .landing-description {
        color: var(--text-muted);
        max-width: 62ch;
        line-height: 1.66;
        margin: 0 0 1.5rem;
    }

    .landing-actions { display: flex; flex-wrap: wrap; gap: .75rem; }

    .landing-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: var(--radius-pill, 999px);
        padding: .7rem 1.4rem;
        font-weight: 600;
        border: 1px solid transparent;
        transition: transform var(--transition, .25s ease), background-color var(--transition, .25s ease),
                    border-color var(--transition, .25s ease), color var(--transition, .25s ease);
    }
    .landing-btn-primary { background: var(--accent); color: var(--bg-main); }
    .landing-btn-primary:hover { background: var(--accent-hover, var(--accent)); transform: translateY(-2px); color: var(--bg-main); }
    .landing-btn-ghost { border-color: var(--border-color); color: var(--text-main); background: transparent; }
    .landing-btn-ghost:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }
    .landing-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

    /* Maqueta decorativa del panel: no es una captura, es dibujo con divs. */
    .landing-mockup {
        border: 1px solid var(--border-color);
        border-radius: var(--radius, 12px);
        background: var(--bg-card);
        box-shadow: var(--shadow-lg, 0 16px 40px rgba(0,0,0,.45));
        overflow: hidden;
    }
    .landing-mockup-bar {
        display: flex;
        align-items: center;
        gap: .4rem;
        padding: .6rem .85rem;
        border-bottom: 1px solid var(--border-color);
    }
    .landing-mockup-dot { width: .55rem; height: .55rem; border-radius: 50%; background: var(--border-color); }
    .landing-mockup-dot:first-child { background: var(--accent); opacity: .8; }
    .landing-mockup-body { display: grid; grid-template-columns: 34% 1fr; min-height: 230px; }
    .landing-mockup-side { border-right: 1px solid var(--border-color); padding: .85rem; display: grid; gap: .5rem; align-content: start; }
    .landing-mockup-line { height: .45rem; border-radius: 999px; background: var(--border-color); opacity: .8; }
    .landing-mockup-line.is-accent { background: var(--accent); opacity: .55; }
    /* Anchos escalonados sin estilos en línea: el proyecto los evita. */
    .landing-mockup-side .landing-mockup-line:nth-child(1) { width: 70%; }
    .landing-mockup-side .landing-mockup-line:nth-child(2) { width: 85%; }
    .landing-mockup-side .landing-mockup-line:nth-child(3) { width: 60%; }
    .landing-mockup-side .landing-mockup-line:nth-child(4) { width: 75%; }
    .landing-mockup-side .landing-mockup-line:nth-child(5) { width: 50%; }
    .landing-mockup-main { padding: .85rem; display: grid; gap: .6rem; grid-template-columns: 1fr 1fr; align-content: start; }
    .landing-mockup-card { border: 1px solid var(--border-color); border-radius: 10px; min-height: 66px; background: color-mix(in srgb, var(--bg-card) 65%, transparent); }
    .landing-mockup-card.is-wide { grid-column: span 2; min-height: 78px; }

    .landing-section { padding: clamp(1.9rem, 3.4vw, 3rem) 0; }
    .landing-section + .landing-section { border-top: 1px solid var(--border-soft, rgba(255,255,255,.07)); }
    /* Ritmo de color: una sección de cada dos lleva fondo de tarjeta. Separa sin
       dibujar líneas duras y da densidad sin apretar el contenido. */
    .landing-section:nth-of-type(even) {
        background: color-mix(in srgb, var(--bg-card) 42%, transparent);
    }
    .landing-section-title {
        font-size: clamp(1.3rem, 2.2vw, 1.75rem);
        margin: 0 0 .45rem;
        color: var(--text-main);
        letter-spacing: -.015em;
        line-height: 1.2;
    }
    /* Marca de acento bajo el título: jerarquía sin ocupar espacio. */
    .landing-section-title::after {
        content: "";
        display: block;
        width: 2.25rem;
        height: 2px;
        margin-top: .6rem;
        border-radius: 2px;
        background: var(--accent);
        opacity: .8;
    }
    .landing-section-intro {
        color: var(--text-muted);
        max-width: 66ch;
        line-height: 1.62;
        margin: 0 0 1.5rem;
    }

    .landing-card {
        height: 100%;
        border: 1px solid var(--border-color);
        border-radius: var(--radius, 12px);
        background: var(--bg-card);
        padding: 1.1rem 1.15rem;
        transition: transform var(--transition, .25s ease), border-color var(--transition, .25s ease),
                    box-shadow var(--transition, .25s ease);
    }
    .landing-card:hover,
    .landing-card:focus-within {
        transform: translateY(-3px);
        border-color: var(--accent);
        box-shadow: var(--shadow-md, 0 8px 24px rgba(0,0,0,.35));
    }
    .landing-card-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 9px;
        background: color-mix(in srgb, var(--accent) 16%, transparent);
        color: var(--accent);
        font-size: 1.1rem;
        margin-bottom: .75rem;
    }
    .landing-card-title { font-size: .97rem; font-weight: 650; margin: 0 0 .35rem; color: var(--text-main); line-height: 1.3; }
    .landing-card-text { color: var(--text-muted); font-size: .885rem; margin: 0; line-height: 1.56; }

    .landing-spec { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .landing-spec-item {
        border: 1px solid var(--border-color);
        border-radius: 9px;
        padding: .7rem .9rem;
        background: color-mix(in srgb, var(--bg-card) 70%, transparent);
    }
    .landing-spec-label {
        display: block;
        font-size: .68rem;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: .25rem;
    }
    .landing-spec-value { color: var(--text-main); font-size: .89rem; line-height: 1.5; }

    .landing-empty {
        border: 1px dashed var(--border-color);
        border-radius: var(--radius, 12px);
        padding: 2.25rem 1.5rem;
        text-align: center;
        color: var(--text-muted);
    }
    .landing-empty i { font-size: 1.6rem; display: block; margin-bottom: .6rem; color: var(--accent); opacity: .8; }

    .landing-gallery { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .landing-shot { margin: 0; overflow: hidden; border-radius: var(--radius, 12px); border: 1px solid var(--border-color); background: var(--bg-card); }
    .landing-shot img { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
    .landing-shot figcaption { padding: .7rem .9rem; font-size: .85rem; color: var(--text-muted); }

    @media (prefers-reduced-motion: reduce) {
        .landing-btn, .landing-card { transition: none; }
        .landing-btn:hover, .landing-card:hover { transform: none; }
    }
</style>

<!-- ── 1. Hero ─────────────────────────────────────────────────────── -->
<section class="landing-hero" id="inicio">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="landing-kicker">
                    <i class="bi bi-stars" aria-hidden="true"></i>
                    <?= e($landing['hero_kicker']) ?>
                </p>
                <h1 class="landing-title"><?= e($landing['hero_title']) ?></h1>
                <p class="landing-subtitle"><?= e($landing['hero_subtitle']) ?></p>
                <p class="landing-description"><?= e($landing['hero_description']) ?></p>
                <div class="landing-actions">
                    <a class="landing-btn landing-btn-primary" href="<?= e($hero['href']) ?>">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <?= e($hero['label']) ?>
                    </a>
                    <a class="landing-btn landing-btn-ghost" href="<?= e($heroAlt['href']) ?>">
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                        <?= e($heroAlt['label']) ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <?php if ($landing['hero_image'] !== ''): ?>
                    <img class="landing-mockup"
                         src="<?= e($landing['hero_image']) ?>"
                         alt="Captura del panel de administración de <?= e($appName) ?>"
                         loading="eager" decoding="async">
                <?php else: ?>
                    <div class="landing-mockup" role="img"
                         aria-label="Representación del panel de administración: barra superior, menú lateral y tarjetas de contenido">
                        <div class="landing-mockup-bar">
                            <span class="landing-mockup-dot"></span>
                            <span class="landing-mockup-dot"></span>
                            <span class="landing-mockup-dot"></span>
                        </div>
                        <div class="landing-mockup-body">
                            <div class="landing-mockup-side">
                                <span class="landing-mockup-line is-accent"></span>
                                <span class="landing-mockup-line"></span>
                                <span class="landing-mockup-line"></span>
                                <span class="landing-mockup-line"></span>
                                <span class="landing-mockup-line"></span>
                            </div>
                            <div class="landing-mockup-main">
                                <div class="landing-mockup-card is-wide"></div>
                                <div class="landing-mockup-card"></div>
                                <div class="landing-mockup-card"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ── 2. Presentación ────────────────────────────────────────────── -->
<section class="landing-section" id="presentacion">
    <div class="container">
        <h2 class="landing-section-title"><?= e($sections['presentation']['title'] ?? 'Qué es') ?></h2>
        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <div class="landing-card">
                    <span class="landing-card-icon"><i class="bi bi-exclamation-diamond" aria-hidden="true"></i></span>
                    <h3 class="landing-card-title">El problema</h3>
                    <p class="landing-card-text"><?= e($sections['presentation']['problem'] ?? '') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="landing-card">
                    <span class="landing-card-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <h3 class="landing-card-title">Para quién es</h3>
                    <p class="landing-card-text"><?= e($sections['presentation']['audience'] ?? '') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="landing-card">
                    <span class="landing-card-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <h3 class="landing-card-title">Qué aporta</h3>
                    <p class="landing-card-text"><?= e($sections['presentation']['advantages'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── 3. Características ─────────────────────────────────────────── -->
<section class="landing-section" id="caracteristicas">
    <div class="container">
        <h2 class="landing-section-title"><?= e($sections['features']['title'] ?? 'Funciones') ?></h2>
        <p class="landing-section-intro"><?= e($sections['features']['intro'] ?? '') ?></p>

        <?php if ($landing['features'] === []): ?>
            <div class="landing-empty">
                <i class="bi bi-card-list" aria-hidden="true"></i>
                Todavía no hay tarjetas de funciones. Se añaden desde Configuración → Portada.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($landing['features'] as $feature): ?>
                    <div class="col-sm-6 col-lg-3">
                        <article class="landing-card">
                            <span class="landing-card-icon">
                                <i class="bi <?= e($feature['icon']) ?>" aria-hidden="true"></i>
                            </span>
                            <h3 class="landing-card-title"><?= e($feature['title']) ?></h3>
                            <p class="landing-card-text"><?= e($feature['description']) ?></p>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── 4. Beneficios ──────────────────────────────────────────────────
     Va antes que las especificaciones a propósito: primero qué cambia para
     quien lo usa, después cómo está hecho por dentro. ─────────────────── -->
<section class="landing-section" id="beneficios">
    <div class="container">
        <h2 class="landing-section-title"><?= e($sections['benefits']['title'] ?? 'Beneficios') ?></h2>
        <p class="landing-section-intro"><?= e($sections['benefits']['intro'] ?? '') ?></p>

        <?php if ($landing['benefits'] === []): ?>
            <div class="landing-empty">
                <i class="bi bi-stars" aria-hidden="true"></i>
                Sin beneficios publicados.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($landing['benefits'] as $benefit): ?>
                    <div class="col-sm-6 col-lg-3">
                        <article class="landing-card">
                            <span class="landing-card-icon">
                                <i class="bi <?= e($benefit['icon']) ?>" aria-hidden="true"></i>
                            </span>
                            <h3 class="landing-card-title"><?= e($benefit['title']) ?></h3>
                            <p class="landing-card-text"><?= e($benefit['description']) ?></p>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── 5. Especificaciones técnicas ───────────────────────────────── -->
<section class="landing-section" id="especificaciones">
    <div class="container">
        <h2 class="landing-section-title"><?= e($sections['specs']['title'] ?? 'Especificaciones técnicas') ?></h2>
        <p class="landing-section-intro"><?= e($sections['specs']['intro'] ?? '') ?></p>

        <?php if ($landing['specs'] === []): ?>
            <div class="landing-empty">
                <i class="bi bi-cpu" aria-hidden="true"></i>
                Sin especificaciones publicadas.
            </div>
        <?php else: ?>
            <div class="landing-spec">
                <?php foreach ($landing['specs'] as $spec): ?>
                    <div class="landing-spec-item">
                        <span class="landing-spec-label"><?= e($spec['label']) ?></span>
                        <span class="landing-spec-value"><?= e($spec['value']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── 6. Experiencia visual ──────────────────────────────────────── -->
<section class="landing-section" id="experiencia">
    <div class="container">
        <h2 class="landing-section-title"><?= e($sections['gallery']['title'] ?? 'El sistema por dentro') ?></h2>
        <p class="landing-section-intro"><?= e($sections['gallery']['intro'] ?? '') ?></p>

        <?php if ($landing['gallery'] === []): ?>
            <div class="landing-empty">
                <i class="bi bi-images" aria-hidden="true"></i>
                Aún no hay capturas publicadas. Se añaden desde Configuración → Portada.
            </div>
        <?php else: ?>
            <div class="landing-gallery">
                <?php foreach ($landing['gallery'] as $shot): ?>
                    <figure class="landing-shot">
                        <img src="<?= e($shot['image']) ?>" alt="<?= e($shot['alt']) ?>" loading="lazy" decoding="async">
                        <?php if ($shot['caption'] !== ''): ?>
                            <figcaption><?= e($shot['caption']) ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
