<?php
/**
 * Pantalla del sitemap.
 *
 * Para qué existe esta pantalla y no solo un botón:
 *
 *  1. El sitemap ya no lista «todas las páginas publicadas». Lista lo que el
 *     menú en uso expone, y esa decisión tiene consecuencias visibles: una
 *     página publicada que no está en ningún menú deja de anunciarse. El dueño
 *     tiene que poder verlo antes de que le pase a su buscador.
 *  2. La URL base sale de la configuración y, si apunta a `localhost`, el
 *     archivo generado no sirve para nada. Aquí se avisa en vez de publicar un
 *     sitemap que parece correcto.
 *
 * Recibe:
 *   $informe array  Resultado de SitemapService::generate()
 *   $estado  array  SitemapService::status()
 *   $base    array  SitemapService::baseUrl()
 *   $xml     string Contenido actual del archivo
 *
 * @package PePiuoX\Editor\Views
 * @since   2026-10-31
 */

$etiquetaOrigen = static function (string $origen): string {
    if ($origen === 'inicio') {
        return 'Página de inicio';
    }
    if ($origen === 'publicada') {
        return 'Publicada (fuente: todas)';
    }
    if (str_starts_with($origen, 'menu:')) {
        return 'Menú «' . substr($origen, 5) . '»';
    }

    return $origen;
};
?>

<style>
    /* Vista previa del XML sin estilos en línea (convención del proyecto). */
    .sitemap-xml { max-height: 22rem; overflow: auto; }
</style>

<h1 class="h3 mb-1"><i class="bi bi-diagram-2 me-2"></i>Sitemap</h1>
<p class="text-secondary mb-4">
    El sitemap se escribe en <code>public/sitemap.xml</code> y se regenera solo cuando
    guardas una página o cambias el menú en uso.
</p>

<?php if (!empty($informe['warnings'])): ?>
    <div class="alert alert-warning" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Revisa esto antes de publicar</div>
        <ul class="mb-0 ps-3">
            <?php foreach ($informe['warnings'] as $aviso): ?>
                <li><?= e((string) $aviso) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$estado['exists']): ?>
    <div class="alert alert-secondary" role="alert">
        Todavía no existe <code>public/sitemap.xml</code>. Pulsa «Regenerar» para crearlo.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">URLs en el sitemap</div>
            <div class="fs-4 fw-bold"><?= (int) $estado['urls'] ?></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Última generación</div>
            <div class="fs-6 fw-semibold"><?= $estado['generated_at'] !== null ? e((string) $estado['generated_at']) : '—' ?></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Tamaño del archivo</div>
            <div class="fs-6 fw-semibold"><?= $estado['exists'] ? number_format((int) $estado['size']) . ' bytes' : '—' ?></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Ámbito</div>
            <div class="fs-6 fw-semibold"><?= e($scopes[$informe['scope']]['label'] ?? (string) $informe['scope']) ?></div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Configuración</span>
        <form method="post" action="<?= url('admin/seo/sitemap/regenerate') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i>Regenerar ahora
            </button>
        </form>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('admin/seo/sitemap/settings') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="scope" class="form-label">Qué se publica</label>
                    <select class="form-select" id="scope" name="scope">
                        <?php foreach ($scopes as $clave => $info): ?>
                            <option value="<?= e((string) $clave) ?>" <?= $ajustes['scope'] === $clave ? 'selected' : '' ?>>
                                <?= e($info['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Por defecto, los menús predeterminados: el «Menú principal».
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="menu_id" class="form-label">Menú concreto</label>
                    <select class="form-select" id="menu_id" name="menu_id">
                        <option value="0">— ninguno —</option>
                        <?php foreach ($menus as $menu): ?>
                            <option value="<?= (int) $menu['id'] ?>" <?= (int) $ajustes['menu_id'] === (int) $menu['id'] ? 'selected' : '' ?>>
                                <?= e((string) $menu['name']) ?> · <?= e((string) $menu['location']) ?>
                                (<?= (int) $menu['items'] ?> ítems)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Solo se usa si eliges «Un menú concreto».</div>
                </div>
                <div class="col-md-3">
                    <label for="base_url" class="form-label">URL base del sitio</label>
                    <input type="url" class="form-control" id="base_url" name="base_url"
                           value="<?= e($informe['base_url']) ?>"
                           placeholder="https://tu-dominio.com">
                    <div class="form-text">
                        Origen actual: <strong><?= e((string) $base['origen']) ?></strong>.
                        Vacío = usar <code>config.app_url</code>.
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include_home"
                               name="include_home" value="1" <?= !empty($ajustes['include_home']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="include_home">Incluir inicio</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3">
                <i class="bi bi-save me-1"></i>Guardar y regenerar
            </button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Menús que se van a leer</div>
    <div class="card-body p-0">
        <?php if ($informe['menus'] === []): ?>
            <div class="p-3 text-secondary">
                Con el ámbito elegido no hay ningún menú que leer. Revisa
                <a href="<?= url('admin/menus') ?>">Menús</a>: sin menú no hay rutas que publicar.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Ubicación</th>
                            <th>Menú</th>
                            <th>Por qué</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($informe['menus'] as $menu): ?>
                            <tr>
                                <td><code><?= e((string) $menu['location']) ?></code></td>
                                <td><?= e((string) $menu['name']) ?></td>
                                <td>
                                    <?php if ($menu['motivo'] === 'primary'): ?>
                                        <span class="badge text-bg-primary">El header lo muestra</span>
                                    <?php elseif ($menu['motivo'] === 'elegido'): ?>
                                        <span class="badge text-bg-info">Elegido a mano</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Predeterminado de la ubicación</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">
        URLs que se publicarían ahora (<?= (int) $informe['total'] ?>)
    </div>
    <div class="card-body p-0">
        <?php if ($informe['urls'] === []): ?>
            <div class="p-3 text-secondary">No hay ninguna URL que publicar con la configuración actual.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>lastmod</th>
                            <th>prioridad</th>
                            <th>De dónde sale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($informe['urls'] as $url): ?>
                            <tr>
                                <td class="text-break"><code><?= e((string) $url['loc']) ?></code></td>
                                <td><?= e((string) $url['lastmod']) ?></td>
                                <td><?= e((string) $url['priority']) ?></td>
                                <td class="small text-secondary"><?= e($etiquetaOrigen((string) $url['origen'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Contenido actual del archivo</div>
    <div class="card-body">
        <?php if ($xml === ''): ?>
            <p class="text-secondary mb-0">El archivo no existe todavía.</p>
        <?php else: ?>
            <pre class="mb-0 small sitemap-xml"><code><?= e($xml) ?></code></pre>
        <?php endif; ?>
    </div>
</div>
