<?php
/**
 * Versiones de una plantilla.
 *
 * Ruta: GET /admin/templates/{id}/versions
 * Controlador: TemplatesController::versions() (la envuelve en layouts.admin).
 *
 * `template_versions` guarda el contenido completo (html/css/js/php) y la fecha;
 * no guarda autor ni nota, así que la tabla no los muestra.
 *
 * @package Views\Templates
 */

$templateName = $template['name'] ?? 'Plantilla';
$templateId   = (int) ($template['id'] ?? 0);
$pageTitle    = 'Versiones: ' . $templateName;
?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-clock-history"></i> Versiones de <?= e($templateName) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= url("admin/templates/{$templateId}/edit") ?>">
      <i class="bi bi-arrow-left"></i> Volver al editor
    </a>
  </div>

  <div class="card-body p-0">
    <?php if (empty($versions)): ?>
      <p style="padding:1.25rem;margin:0;color:#8b949e">
        Todavía no hay versiones guardadas. Usa «Guardar versión» en el editor antes de cambiar el contenido.
      </p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Versión</th>
            <th>Fecha</th>
            <th>Tamaño</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($versions as $v): ?>
          <?php
            $size = strlen((string) ($v['content_html'] ?? ''))
                  + strlen((string) ($v['content_css'] ?? ''))
                  + strlen((string) ($v['content_js'] ?? ''))
                  + strlen((string) ($v['content_php'] ?? ''));
          ?>
          <tr>
            <td><strong>#<?= (int) $v['id'] ?></strong></td>
            <td><?= e($v['created_at'] ?? '—') ?></td>
            <td><?= e(number_format($size / 1024, 1)) ?> KB</td>
            <td class="text-end">
              <form method="post"
                    action="<?= url("admin/templates/{$templateId}/versions/" . (int) $v['id'] . '/restore') ?>"
                    style="display:inline">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-ghost btn-sm" onclick="return confirm('¿Restaurar esta versión?')"
                        title="Restaurar">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              <form method="post"
                    action="<?= url("admin/templates/{$templateId}/versions/" . (int) $v['id'] . '/delete') ?>"
                    style="display:inline">
                <?= \App\Core\CSRF::field() ?>
                <button class="btn btn-ghost btn-sm" onclick="return confirm('¿Eliminar esta versión?')"
                        title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
