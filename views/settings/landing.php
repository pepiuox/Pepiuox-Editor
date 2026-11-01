<?php
/**
 * Editor del contenido de la portada pública.
 *
 * Pantalla propia dentro de Configuración. El formulario se agrupa en bloques
 * cerrados (hero, botones, textos de sección, listas) y las listas admiten alta
 * y baja de filas sin recargar.
 *
 * Decisiones que conviene no deshacer:
 *  - El interruptor «Publicar la portada como inicio» va separado y explicado:
 *    guardar textos no debe cambiar la página de inicio de un sitio publicado.
 *  - Al vaciar una lista no se «borra» nada del sitio que no se vea: la portada
 *    muestra su estado vacío. El botón de guardar lo dice.
 *  - Los índices de las filas se reasignan al guardar en el servidor, así que
 *    añadir y quitar filas nunca deja huecos que rompan el JSON.
 *
 * Recibe:
 *   $landing array  Contenido resuelto (LandingContentService::all())
 *   $limites array  Topes por lista
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

$fila = static function (string $clave, int $i, array $dato, bool $conIcono = true): string {
    ob_start();
    ?>
    <div class="landing-row border rounded p-3 mb-2" data-row>
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Icono</label>
                <input class="form-control form-control-sm" name="<?= $clave ?>[<?= $i ?>][icon]"
                       value="<?= e((string) ($dato['icon'] ?? '')) ?>" placeholder="bi-star">
            </div>
            <div class="col-md-9 d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>
                    <i class="bi bi-trash"></i> Quitar
                </button>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Título</label>
                <input class="form-control form-control-sm" name="<?= $clave ?>[<?= $i ?>][title]"
                       value="<?= e((string) ($dato['title'] ?? '')) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label small">Descripción</label>
                <input class="form-control form-control-sm" name="<?= $clave ?>[<?= $i ?>][description]"
                       value="<?= e((string) ($dato['description'] ?? '')) ?>">
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>

<style>
    /* Filas repetibles: sin estilos en línea, convención del proyecto. */
    .landing-row { background: rgba(255, 255, 255, .02); }
    .landing-row:hover { border-color: var(--accent) !important; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-window-sidebar me-2"></i>Portada</h1>
        <p class="text-secondary mb-0">
            Contenido de la página de presentación. Se ve en
            <a href="<?= url('admin/settings/landing/preview') ?>" target="_blank" rel="noopener">vista previa</a>.
        </p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings') ?>">
        <i class="bi bi-arrow-left"></i> Volver a Configuración
    </a>
</div>

<form method="post" action="<?= url('admin/settings/landing') ?>">
    <?= csrf_field() ?>

    <!-- Interruptor -->
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="enabled"
                           name="enabled" value="1" <?= !empty($landing['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="enabled">
                        Publicar la portada como página de inicio
                    </label>
                </div>
                <div class="form-text mb-0">
                    Desactivado, la web sigue mostrando la página marcada como inicio en
                    <a href="<?= url('admin/pages') ?>">Páginas</a>. Guardar los textos no cambia esto por sí solo.
                </div>
            </div>
            <?php if (!empty($landing['enabled'])): ?>
                <span class="badge text-bg-success">Publicada</span>
            <?php else: ?>
                <span class="badge text-bg-secondary">No publicada</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Cabecera de la portada</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="hero_kicker">Antetítulo</label>
                    <input class="form-control" id="hero_kicker" name="hero_kicker" maxlength="180"
                           value="<?= e((string) $landing['hero_kicker']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="hero_title">Titular</label>
                    <input class="form-control" id="hero_title" name="hero_title" maxlength="180"
                           value="<?= e((string) $landing['hero_title']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="hero_subtitle">Eslogan</label>
                    <input class="form-control" id="hero_subtitle" name="hero_subtitle" maxlength="400"
                           value="<?= e((string) $landing['hero_subtitle']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="hero_description">Descripción</label>
                    <textarea class="form-control" id="hero_description" name="hero_description" rows="3"
                              maxlength="1200"><?= e((string) $landing['hero_description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="hero_image">Imagen o maqueta</label>
                    <input class="form-control" id="hero_image" name="hero_image"
                           value="<?= e((string) $landing['hero_image']) ?>"
                           placeholder="/uploads/portada.png — vacío dibuja una maqueta del panel">
                    <div class="form-text">Vacío: se dibuja una representación del panel, no una captura falsa.</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="hero_cta_primary_label">Botón principal · texto</label>
                    <input class="form-control" id="hero_cta_primary_label" name="hero_cta_primary_label"
                           value="<?= e((string) ($landing['hero_cta_primary']['label'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="hero_cta_primary_href">Botón principal · destino</label>
                    <input class="form-control" id="hero_cta_primary_href" name="hero_cta_primary_href"
                           value="<?= e((string) ($landing['hero_cta_primary']['href'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="hero_cta_secondary_label">Botón secundario · texto</label>
                    <input class="form-control" id="hero_cta_secondary_label" name="hero_cta_secondary_label"
                           value="<?= e((string) ($landing['hero_cta_secondary']['label'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="hero_cta_secondary_href">Botón secundario · destino</label>
                    <input class="form-control" id="hero_cta_secondary_href" name="hero_cta_secondary_href"
                           value="<?= e((string) ($landing['hero_cta_secondary']['href'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <div class="form-text">
                        Destinos admitidos: ruta del sitio (<code>/login</code>), ancla (<code>#caracteristicas</code>)
                        o URL <code>http(s)</code>. Nada de <code>javascript:</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Textos de sección -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Textos de las secciones</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="sections_presentation_title">Presentación · título</label>
                    <input class="form-control" id="sections_presentation_title"
                           name="sections[presentation][title]"
                           value="<?= e((string) ($landing['sections']['presentation']['title'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="sections_features_title">Funciones · título</label>
                    <input class="form-control" id="sections_features_title"
                           name="sections[features][title]"
                           value="<?= e((string) ($landing['sections']['features']['title'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Presentación · problema</label>
                    <textarea class="form-control" name="sections[presentation][problem]" rows="3"><?= e((string) ($landing['sections']['presentation']['problem'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Presentación · para quién</label>
                    <textarea class="form-control" name="sections[presentation][audience]" rows="3"><?= e((string) ($landing['sections']['presentation']['audience'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Presentación · qué aporta</label>
                    <textarea class="form-control" name="sections[presentation][advantages]" rows="3"><?= e((string) ($landing['sections']['presentation']['advantages'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Funciones · introducción</label>
                    <textarea class="form-control" name="sections[features][intro]" rows="2"><?= e((string) ($landing['sections']['features']['intro'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Especificaciones · título e introducción</label>
                    <input class="form-control mb-2" name="sections[specs][title]"
                           value="<?= e((string) ($landing['sections']['specs']['title'] ?? '')) ?>">
                    <textarea class="form-control" name="sections[specs][intro]" rows="1"><?= e((string) ($landing['sections']['specs']['intro'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Beneficios · título e introducción</label>
                    <input class="form-control mb-2" name="sections[benefits][title]"
                           value="<?= e((string) ($landing['sections']['benefits']['title'] ?? '')) ?>">
                    <textarea class="form-control" name="sections[benefits][intro]" rows="1"><?= e((string) ($landing['sections']['benefits']['intro'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Galería · título e introducción</label>
                    <input class="form-control mb-2" name="sections[gallery][title]"
                           value="<?= e((string) ($landing['sections']['gallery']['title'] ?? '')) ?>">
                    <textarea class="form-control" name="sections[gallery][intro]" rows="1"><?= e((string) ($landing['sections']['gallery']['intro'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="footer_note">Nota del pie</label>
                    <input class="form-control" id="footer_note" name="footer_note" maxlength="400"
                           value="<?= e((string) $landing['footer_note']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de funciones -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Tarjetas de funciones
                <span class="text-secondary small">(máx. <?= (int) $limites['features'] ?>)</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="features">
                <i class="bi bi-plus-lg"></i> Añadir
            </button>
        </div>
        <div class="card-body" data-list="features">
            <?php $i = 0; foreach ($landing['features'] as $dato): ?>
                <?= $fila('features', $i++, $dato) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Especificaciones -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Especificaciones técnicas
                <span class="text-secondary small">(máx. <?= (int) $limites['specs'] ?>)</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="specs">
                <i class="bi bi-plus-lg"></i> Añadir
            </button>
        </div>
        <div class="card-body" data-list="specs">
            <?php $i = 0; foreach ($landing['specs'] as $dato): ?>
                <div class="landing-row border rounded p-3 mb-2" data-row>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">Etiqueta</label>
                            <input class="form-control form-control-sm" name="specs[<?= $i ?>][label]"
                                   value="<?= e((string) ($dato['label'] ?? '')) ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Valor</label>
                            <input class="form-control form-control-sm" name="specs[<?= $i ?>][value]"
                                   value="<?= e((string) ($dato['value'] ?? '')) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove-row>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php $i++; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Beneficios -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Beneficios
                <span class="text-secondary small">(máx. <?= (int) $limites['benefits'] ?>)</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="benefits">
                <i class="bi bi-plus-lg"></i> Añadir
            </button>
        </div>
        <div class="card-body" data-list="benefits">
            <?php $i = 0; foreach ($landing['benefits'] as $dato): ?>
                <?= $fila('benefits', $i++, $dato) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Galería -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Galería de capturas
                <span class="text-secondary small">(máx. <?= (int) $limites['gallery'] ?>)</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="gallery">
                <i class="bi bi-plus-lg"></i> Añadir
            </button>
        </div>
        <div class="card-body" data-list="gallery">
            <?php $i = 0; foreach ($landing['gallery'] as $dato): ?>
                <div class="landing-row border rounded p-3 mb-2" data-row>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">Imagen (ruta o URL)</label>
                            <input class="form-control form-control-sm" name="gallery[<?= $i ?>][image]"
                                   value="<?= e((string) ($dato['image'] ?? '')) ?>" placeholder="/uploads/captura.png">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Texto alternativo</label>
                            <input class="form-control form-control-sm" name="gallery[<?= $i ?>][alt]"
                                   value="<?= e((string) ($dato['alt'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Pie</label>
                            <input class="form-control form-control-sm" name="gallery[<?= $i ?>][caption]"
                                   value="<?= e((string) ($dato['caption'] ?? '')) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove-row>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php $i++; ?>
            <?php endforeach; ?>
            <?php if ($landing['gallery'] === []): ?>
                <p class="text-secondary mb-0 small">
                    No hay capturas. Mientras no haya, la portada muestra su estado vacío en esa sección.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Guardar contenido
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/settings/landing/preview') ?>" target="_blank" rel="noopener">
            <i class="bi bi-eye me-1"></i>Ver cómo queda
        </a>
    </div>
</form>

<script>
(() => {
    'use strict';

    // Plantillas de fila. Los índices se reasignan al guardar: aquí solo hace
    // falta un número único y creciente para que los campos no choquen.
    const plantillas = {
        features: (i) => `
            <div class="landing-row border rounded p-3 mb-2" data-row>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Icono</label>
                        <input class="form-control form-control-sm" name="features[${i}][icon]" placeholder="bi-star">
                    </div>
                    <div class="col-md-9 d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>
                            <i class="bi bi-trash"></i> Quitar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Título</label>
                        <input class="form-control form-control-sm" name="features[${i}][title]">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Descripción</label>
                        <input class="form-control form-control-sm" name="features[${i}][description]">
                    </div>
                </div>
            </div>`,
        benefits: (i) => `
            <div class="landing-row border rounded p-3 mb-2" data-row>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Icono</label>
                        <input class="form-control form-control-sm" name="benefits[${i}][icon]" placeholder="bi-star">
                    </div>
                    <div class="col-md-9 d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>
                            <i class="bi bi-trash"></i> Quitar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Título</label>
                        <input class="form-control form-control-sm" name="benefits[${i}][title]">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Descripción</label>
                        <input class="form-control form-control-sm" name="benefits[${i}][description]">
                    </div>
                </div>
            </div>`,
        specs: (i) => `
            <div class="landing-row border rounded p-3 mb-2" data-row>
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Etiqueta</label>
                        <input class="form-control form-control-sm" name="specs[${i}][label]">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small">Valor</label>
                        <input class="form-control form-control-sm" name="specs[${i}][value]">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove-row>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`,
        gallery: (i) => `
            <div class="landing-row border rounded p-3 mb-2" data-row>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">Imagen (ruta o URL)</label>
                        <input class="form-control form-control-sm" name="gallery[${i}][image]" placeholder="/uploads/captura.png">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Texto alternativo</label>
                        <input class="form-control form-control-sm" name="gallery[${i}][alt]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Pie</label>
                        <input class="form-control form-control-sm" name="gallery[${i}][caption]">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove-row>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`,
    };

    const contadores = {};

    // Delegación de eventos: sirve para las filas que ya existen y para las
    // que se añadan después, sin volver a enganchar nada.
    document.addEventListener('click', (evento) => {
        const quitar = evento.target.closest('[data-remove-row]');
        if (quitar) {
            quitar.closest('[data-row]')?.remove();
            return;
        }

        const anadir = evento.target.closest('[data-add-row]');
        if (!anadir) {
            return;
        }

        const lista = anadir.dataset.addRow;
        const contenedor = document.querySelector(`[data-list="${lista}"]`);
        const plantilla = plantillas[lista];
        if (!contenedor || !plantilla) {
            return;
        }

        // Índice único: parte del número de filas presentes más un contador que
        // nunca retrocede, para que quitar y volver a añadir no repita claves.
        contadores[lista] = (contadores[lista] ?? contenedor.querySelectorAll('[data-row]').length) + 1;
        contenedor.insertAdjacentHTML('beforeend', plantilla(contadores[lista]));
    });
})();
</script>
