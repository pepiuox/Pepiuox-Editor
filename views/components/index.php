<?php $pageTitle = 'Componentes'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-puzzle"></i> Componentes reutilizables</h3>
    <a class="btn btn-primary btn-sm" href="<?= url('admin/components/create') ?>"><i class="bi bi-plus"></i> Nuevo</a>
  </div>
  <div class="card-body">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <a class="btn btn-sm <?= !$cat?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/components') ?>">Todos</a>
      <?php foreach ($categories as $c): ?>
        <a class="btn btn-sm <?= $cat===$c?'btn-primary':'btn-ghost' ?>" href="<?= url("admin/components?category=$c") ?>"><?= e(ucfirst($c)) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="row g-3">
      <?php foreach ($components as $c): ?>
        <div class="col-md-4">
          <div class="card card-accent">
            <div class="card-body">
              <div class="d-flex justify-between align-start mb-2">
                <h5 class="mb-0"><?= e($c['name']) ?></h5>
                <span class="badge badge-info"><?= e($c['category']) ?></span>
              </div>
              <div class="component-preview" style="height:120px;background:var(--bg-principal);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;margin-bottom:.75rem">
                <iframe srcdoc="<?= e($c['html']) ?>" style="width:200%;height:200%;border:0;transform:scale(0.5);transform-origin:top left;pointer-events:none"></iframe>
              </div>
              <div class="d-flex gap-1">
                <a class="btn btn-ghost btn-sm" href="<?= url("admin/components/{$c['id']}/edit") ?>"><i class="bi bi-pencil"></i> Editar</a>
                <form method="post" action="<?= url("admin/components/{$c['id']}/delete") ?>" style="display:inline" onsubmit="return confirm('¿Eliminar?')"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>