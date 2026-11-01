<?php
/**
 * Editor visual del pie de página.
 *
 * Misma receta que el editor de la portada (`views/settings/landing.php`), para
 * que el panel se comporte igual en los dos sitios:
 *  - Un formulario por pantalla con CSRF y permiso `settings.manage`.
 *  - Columnas de enlaces como filas repetibles, con alta y baja sin recargar.
 *  - Todo lo que se pinta pasa luego por `e()` en el layout público.
 *
 * Decisiones que conviene no deshacer:
 *  - El copyright admite `{year}` y `{site}`; se explican en la propia pantalla
 *    para que nadie tenga que leer el código.
 *  - Los destinos se validan en el servidor (`App\Core\LinkGuard`): ruta del
 *    sitio, o URL http(s). Un `javascript:` no se guarda.
 *  - Si una columna se queda sin enlaces válidos, desaparece del pie en lugar de
 *    dibujar un título huérfano.
 *
 * Recibe:
 *   $footer   array  Contenido resuelto (FooterContentService::all())
 *   $maxCols  int    Columnas máximas
 *   $maxLinks int    Enlaces máximos por columna
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

/** Una fila de enlace dentro de una columna. */
$filaEnlace = static function (int $c, int $l, array $dato): string {
    ob_start();
    ?>
    <div class="d-flex gap-2 mb-2 align-items-center" data-link-row>
        <input class="form-control form-control-sm" name="columns[<?= $c ?>][links][<?= $l ?>][label]"
               value="<?= e((string) ($dato['label'] ?? '')) ?>" placeholder="Texto del enlace" aria-label="Texto del enlace">
        <input class="form-control form-control-sm" name="columns[<?= $c ?>][links][<?= $l ?>][href]"
               value="<?= e((string) ($dato['href'] ?? '')) ?>" placeholder="/pagina o https://…" aria-label="Destino del enlace">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-link aria-label="Quitar enlace">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>

<style>
    /* Filas repetibles: sin estilos en línea, convención del proyecto. */
    .footer-column { background: rgba(255, 255, 255, .02); }
    .footer-column:hover { border-color: var(--accent) !important; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-layout-three-columns me-2"></i>Pie de página</h1>
        <p class="text-secondary mb-0">
            Contenido del pie que aparece en <strong>todas</strong> las páginas públicas.
            <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver el sitio</a>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="<?= url('admin/settings/footer/builder') ?>">
            <i class="bi bi-magic"></i> Editar con el constructor
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/settings') ?>">
            <i class="bi bi-arrow-left"></i> Volver a Configuración
        </a>
    </div>
</div>

<form method="post" action="<?= url('admin/settings/footer') ?>">
    <?= csrf_field() ?>

    <!-- Bloque de marca -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Bloque de marca</div>
        <div class="card-body">
            <label class="form-label" for="about">Texto bajo el logotipo</label>
            <textarea class="form-control" id="about" name="about" rows="2" maxlength="400"><?= e((string) $footer['about']) ?></textarea>
            <div class="form-text">
                Se pinta debajo del logotipo o del nombre del sitio. El logotipo en sí se cambia en
                <a href="<?= url('admin/settings') ?>?tab=logos">Logotipos e iconos</a>.
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Línea de copyright</div>
        <div class="card-body">
            <label class="form-label" for="copyright">Texto</label>
            <input class="form-control" id="copyright" name="copyright" maxlength="180"
                   value="<?= e((string) $footer['copyright']) ?>">
            <div class="form-text">
                Marcadores disponibles: <code>{year}</code> se sustituye por el año en curso y
                <code>{site}</code> por el nombre del sitio. Vacío = no se pinta la línea.
            </div>
        </div>
    </div>

    <!-- Contacto -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Columna de contacto</div>
        <div class="card-body">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="show_contact"
                       name="show_contact" value="1" <?= !empty($footer['show_contact']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="show_contact">Mostrar la columna de contacto en el pie</label>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="contact_title">Título</label>
                    <input class="form-control" id="contact_title" name="contact_title" maxlength="60"
                           value="<?= e((string) $footer['contact_title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact_email">Correo</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                           value="<?= e((string) $footer['contact_email']) ?>" placeholder="tu@dominio.com">
                    <div class="form-text">Si no es un correo válido, se guarda vacío.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact_note">Nota</label>
                    <input class="form-control" id="contact_note" name="contact_note" maxlength="400"
                           value="<?= e((string) $footer['contact_note']) ?>" placeholder="Horario, dirección, teléfono…">
                </div>
            </div>
        </div>
    </div>

    <!-- Columnas de enlaces -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Columnas de enlaces
                <span class="text-secondary small">(máx. <?= (int) $maxCols ?> columnas · <?= (int) $maxLinks ?> enlaces cada una)</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-column>
                <i class="bi bi-plus-lg"></i> Añadir columna
            </button>
        </div>
        <div class="card-body" data-columns>
            <?php $c = 0; foreach ($footer['columns'] as $columna): ?>
                <div class="footer-column border rounded p-3 mb-3" data-column>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Título de la columna</label>
                            <input class="form-control form-control-sm" name="columns[<?= $c ?>][title]"
                                   value="<?= e((string) $columna['title']) ?>">
                        </div>
                        <div class="col-md-6 d-flex justify-content-md-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-column>
                                <i class="bi bi-trash"></i> Quitar columna
                            </button>
                        </div>
                    </div>
                    <div data-links>
                        <?php $l = 0; foreach ($columna['links'] as $enlace): ?>
                            <?= $filaEnlace($c, $l++, $enlace) ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-add-link>
                        <i class="bi bi-plus-lg"></i> Añadir enlace
                    </button>
                </div>
                <?php $c++; ?>
            <?php endforeach; ?>

            <?php if ($footer['columns'] === []): ?>
                <p class="text-secondary small mb-0">
                    No hay columnas. Sin columnas, el pie solo muestra el bloque de marca y el copyright.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Guardar pie de página
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('/') ?>" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ver el sitio
        </a>
    </div>
</form>

<script>
(() => {
    'use strict';

    const contenedor = document.querySelector('[data-columns]');
    let contadorColumnas = contenedor ? contenedor.querySelectorAll('[data-column]').length : 0;

    /** Fila de enlace nueva, con índices únicos. */
    const filaEnlace = (c, l) => `
        <div class="d-flex gap-2 mb-2 align-items-center" data-link-row>
            <input class="form-control form-control-sm" name="columns[${c}][links][${l}][label]"
                   placeholder="Texto del enlace" aria-label="Texto del enlace">
            <input class="form-control form-control-sm" name="columns[${c}][links][${l}][href]"
                   placeholder="/pagina o https://…" aria-label="Destino del enlace">
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-link aria-label="Quitar enlace">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`;

    const columnaNueva = (c) => `
        <div class="footer-column border rounded p-3 mb-3" data-column>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Título de la columna</label>
                    <input class="form-control form-control-sm" name="columns[${c}][title]">
                </div>
                <div class="col-md-6 d-flex justify-content-md-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-column>
                        <i class="bi bi-trash"></i> Quitar columna
                    </button>
                </div>
            </div>
            <div data-links>${filaEnlace(c, 0)}</div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-add-link>
                <i class="bi bi-plus-lg"></i> Añadir enlace
            </button>
        </div>`;

    // Delegación de eventos: vale para lo que ya existe y para lo que se añade.
    document.addEventListener('click', (evento) => {
        if (evento.target.closest('[data-remove-link]')) {
            evento.target.closest('[data-link-row]')?.remove();
            return;
        }
        if (evento.target.closest('[data-remove-column]')) {
            evento.target.closest('[data-column]')?.remove();
            return;
        }

        const anadirColumna = evento.target.closest('[data-add-column]');
        if (anadirColumna) {
            contenedor?.insertAdjacentHTML('beforeend', columnaNueva(contadorColumnas++));
            return;
        }

        const anadirEnlace = evento.target.closest('[data-add-link]');
        if (anadirEnlace) {
            const columna = anadirEnlace.closest('[data-column]');
            const cajas = columna?.querySelector('[data-links]');
            if (!columna || !cajas) return;

            // El índice de la columna se lee del propio campo de título, así no
            // hace falta mantener un mapa aparte cuando se quitan columnas.
            const campo = columna.querySelector('input[name^="columns["]');
            const coincidencia = campo?.name.match(/^columns\[(\d+)\]/);
            const c = coincidencia ? coincidencia[1] : '0';
            const l = cajas.querySelectorAll('[data-link-row]').length;

            cajas.insertAdjacentHTML('beforeend', filaEnlace(c, l));
        }
    });
})();
</script>
