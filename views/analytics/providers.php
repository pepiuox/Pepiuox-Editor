<?php
/**
 * Integración de proveedores de medición externa.
 *
 * Variables que entrega AnalyticsController::providers():
 *
 * @var array<string,string> $ajustes  Valor guardado de cada proveedor.
 * @var array<string,string> $vigentes Valores que pasan su patrón y se emiten.
 * @var string               $politica Enlace a la política de cookies (puede ir vacío).
 *
 * Se configura con identificadores, no pegando código: un campo de texto que
 * acaba dentro de un `<script>` en todas las páginas es una puerta abierta.
 */

use App\Services\AnalyticsProvidersService;

$pageTitle = 'Analytics · Proveedores';
$ajustes = $ajustes ?? [];
$vigentes = $vigentes ?? [];
$politica = $politica ?? '';
$activos = count($vigentes);
?>

<div class="d-flex flex-wrap justify-between align-items-center gap-2 mb-4">
  <div>
    <h2 class="mb-0"><i class="bi bi-broadcast text-primary"></i> Proveedores de medición</h2>
    <p class="text-muted mb-0">
      Analítica externa del sitio público. Los identificadores se validan antes de publicarse.
    </p>
  </div>
  <a href="<?= url('admin/analytics') ?>" class="btn btn-ghost btn-sm">
    <i class="bi bi-arrow-left"></i> Volver a las métricas
  </a>
</div>

<div class="alert <?= $activos > 0 ? 'alert-success' : 'alert-info' ?> d-flex align-items-center gap-2">
  <i class="bi <?= $activos > 0 ? 'bi-check-circle' : 'bi-info-circle' ?>"></i>
  <span>
    <?php if ($activos === 0): ?>
      Ahora mismo <strong>no hay ningún proveedor activo</strong>: no se emite ninguna etiqueta de medición.
    <?php else: ?>
      <strong><?= $activos ?></strong> proveedor(es) activo(s):
      <?= e(implode(', ', array_map(
          static fn (string $k): string => AnalyticsProvidersService::FIELDS[$k]['label'] ?? $k,
          array_keys($vigentes)
      ))) ?>.
    <?php endif; ?>
  </span>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-plug"></i> Identificadores</h3>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/analytics/proveedores') ?>">
      <?= \App\Core\CSRF::field() ?>

      <div class="form-grid">
        <?php foreach (AnalyticsProvidersService::FIELDS as $clave => $prov): ?>
          <?php $valor = (string) ($ajustes[$clave] ?? ''); ?>
          <?php $emitido = isset($vigentes[$clave]); ?>
          <div class="form-group">
            <label class="form-label" for="prov-<?= e($clave) ?>">
              <?= e($prov['label']) ?>
              <?php if ($emitido): ?>
                <span class="badge" style="background:var(--success,#198754);color:#fff;font-size:.7rem">activo</span>
              <?php elseif ($valor !== ''): ?>
                <span class="badge" style="background:var(--danger,#dc3545);color:#fff;font-size:.7rem">descartado</span>
              <?php endif; ?>
            </label>
            <input type="text"
                   class="form-control"
                   id="prov-<?= e($clave) ?>"
                   name="<?= e($clave) ?>"
                   value="<?= e($valor) ?>"
                   placeholder="<?= e($prov['placeholder']) ?>"
                   autocomplete="off"
                   spellcheck="false">
            <small class="form-text"><?= e($prov['ayuda']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>

      <hr class="my-4">
      <h4 class="mb-2"><i class="bi bi-info-square"></i> Aviso de cookies</h4>
      <p class="form-text mb-3">
        El aviso aparece solo cuando hay algún proveedor con cookies configurado. Desde aquí puedes
        enlazar tu política de cookies en el propio aviso.
      </p>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="cookiesPolicy">Enlace a la política de cookies</label>
          <input type="text" class="form-control" id="cookiesPolicy"
                 name="cookies_policy_url"
                 value="<?= e($politica) ?>"
                 placeholder="/privacidad o https://tu-dominio.com/cookies"
                 autocomplete="off" spellcheck="false">
          <small class="form-text">
            Ruta del sitio (<code>/privacidad</code>) o URL completa con <code>https://</code>. Vacío = el
            aviso sale sin enlace.
          </small>
        </div>
      </div>

      <div class="d-flex justify-end gap-2 mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Guardar proveedores
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <h3><i class="bi bi-shield-check"></i> Cómo funciona</h3>
  </div>
  <div class="card-body">
    <ul class="mb-0" style="line-height:1.9">
      <li>
        Deja un campo <strong>vacío</strong> para desactivar ese proveedor. No hay interruptor aparte.
      </li>
      <li>
        Un identificador que no encaja con el formato de su proveedor
        <strong>se descarta al guardar</strong> y no se publica nada. Es lo que impide que un valor
        con comillas o etiquetas acabe dentro del HTML del sitio.
      </li>
      <li>
        Si activas <strong>Google Tag Manager</strong>, configura GA4 dentro de GTM y deja vacío el campo
        de Google Analytics: si no, se cargaría dos veces.
      </li>
      <li>
        <strong>Cookies y consentimiento.</strong> GA4, Tag Manager, Yandex Metrica y Clarity usan cookies;
        Plausible y Umami no. Si el sitio presta servicio en la Unión Europea, necesitas consentimiento
        previo del visitante antes de cargar los que usan cookies. Ese aviso no lo pone esta pantalla.
      </li>
    </ul>
  </div>
</div>
