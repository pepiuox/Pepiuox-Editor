<?php
/**
 * Vista: Versiones de Template
 */
$pageTitle = 'Versiones: ' . ($template['name'] ?? '');
$versions  = $versions ?? [];
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-0"><i class="bi bi-clock-history"></i> <?= e($pageTitle) ?></h3>
            <small class="text-muted"><?= e($template['description'] ?? '') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin/templates/' . (int)$template['id'] . '/edit') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> Editar template
            </a>
            <form method="post" action="<?= url('admin/templates/' . (int)$template['id'] . '/versions') ?>" class="d-inline">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="action" value="create">
                <button type="submit" class="btn btn-info btn-sm">
                    <i class="bi bi-plus-circle"></i> Crear versión manual
                </button>
            </form>
            <a href="<?= url('admin/templates') ?>" class="btn btn-ghost btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($versions)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history display-1"></i>
                <p class="mt-3">No hay versiones guardadas</p>
                <small>Las versiones se crean automáticamente al actualizar el template</small>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Se muestran <?= count($versions) ?> versión(es).
                Al restaurar una versión, el estado actual se guarda automáticamente como nueva versión.
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Fecha</th>
                            <th>Creado por</th>
                            <th>HTML</th>
                            <th>CSS</th>
                            <th>JS</th>
                            <th>PHP</th>
                            <th style="width:200px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versions as $v): ?>
                            <tr>
                                <td><strong>#<?= (int)$v['id'] ?></strong></td>
                                <td>
                                    <small>
                                        <i class="bi bi-calendar3"></i>
                                        <?= e(date('d/m/Y H:i:s', strtotime($v['created_at']))) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($v['username'])): ?>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?= e($v['username']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sistema</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?= strlen((string)$v['content_html']) ?> chars</span></td>
                                <td><span class="badge bg-success"><?= strlen((string)$v['content_css']) ?> chars</span></td>
                                <td><span class="badge bg-warning"><?= strlen((string)$v['content_js']) ?> chars</span></td>
                                <td><span class="badge bg-secondary"><?= strlen((string)$v['content_php']) ?> chars</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-info btn-sm"
                                                onclick="previewVersion(<?= (int)$v['id'] ?>)"
                                                title="Preview">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form method="post" action="<?= url("admin/templates/{$template['id']}/versions/{$v['id']}/restore") ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('¿Restaurar esta versión? El estado actual se guardará como nueva versión.')">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Restaurar">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="<?= url("admin/templates/{$template['id']}/versions/{$v['id']}/delete") ?>"
                                              style="display:inline"
                                              onsubmit="return confirm('¿Eliminar esta versión?')">
                                            <?= \App\Core\CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Preview de versión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-tabs" class="mb-3">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" data-preview="html" href="#">HTML</a></li>
                        <li class="nav-item"><a class="nav-link" data-preview="css" href="#">CSS</a></li>
                        <li class="nav-item"><a class="nav-link" data-preview="js" href="#">JS</a></li>
                        <li class="nav-item"><a class="nav-link" data-preview="render" href="#">Render</a></li>
                    </ul>
                </div>
                <div id="preview-content" style="min-height:400px; border:1px solid #dee2e6; border-radius:8px; padding:1rem; background:#f8f9fa;">
                    <pre id="preview-code" style="margin:0; white-space:pre-wrap;"></pre>
                    <iframe id="preview-frame" style="width:100%; height:500px; border:0; display:none; background:#fff;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const versionsData = <?= json_encode(array_column($versions, null, 'id')) ?>;
let currentVersion = null;

function previewVersion(id) {
    currentVersion = versionsData[id];
    if (!currentVersion) return;

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    showPreview('html');
    modal.show();
}

function showPreview(type) {
    const code = document.getElementById('preview-code');
    const frame = document.getElementById('preview-frame');

    if (type === 'render') {
        code.style.display = 'none';
        frame.style.display = 'block';
        const doc = frame.contentDocument || frame.contentWindow.document;
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html><head><style>${currentVersion.content_css || ''}</style></head>
            <body>${currentVersion.content_html || ''}
            <script>${currentVersion.content_js || ''}<\/script>
            </body></html>
        `);
        doc.close();
    } else {
        frame.style.display = 'none';
        code.style.display = 'block';
        const field = 'content_' + type;
        code.textContent = currentVersion[field] || '(vacío)';
    }
}

document.querySelectorAll('#preview-tabs .nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('#preview-tabs .nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        showPreview(link.dataset.preview);
    });
});
</script>