<?php $pageTitle = 'Menús'; ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-list-nested"></i> Menús</h3>
    <div class="d-flex gap-2">
      <?php foreach ($groups as $g): ?>
        <a class="btn btn-sm <?= $group===$g['slug']?'btn-primary':'btn-ghost' ?>" href="<?= url('admin/menus?group='.$g['slug']) ?>"><?= e($g['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-body">
    <!-- Añadir item -->
    <form method="post" action="<?= url('admin/menus') ?>" class="mb-4 p-3 bg-card-2 rounded">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="menu_group" value="<?= e($group) ?>">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Título</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">URL (opcional)</label><input type="text" name="url" class="form-control" placeholder="/pagina o https://..."></div>
        <div class="form-group"><label class="form-label">Página enlazada</label>
          <select name="page_id" class="form-select">
            <option value="">— Ninguna —</option>
            <?php foreach (\App\Models\Page::all() as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Target</label>
          <select name="target" class="form-select">
            <option value="_self">Misma ventana</option>
            <option value="_blank">Nueva ventana</option>
          </select>
        </div>
      </div>
      <button class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Añadir</button>
    </form>

    <!-- Lista drag&drop -->
    <div id="menuTree" class="menu-tree">
      <?php foreach ($items as $it): ?>
        <div class="menu-item" data-id="<?= (int)$it['id'] ?>" data-level="<?= (int)$it['level'] ?>" style="margin-left:<?= (int)$it['level']*24 ?>px">
          <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
          <strong><?= e($it['title']) ?></strong>
          <small class="text-muted ms-2"><?= e($it['url'] ?? '/'.$it['slug']) ?></small>
          <span class="ms-auto d-flex gap-1">
            <?php if ($it['is_published']): ?><span class="badge badge-published">Publicado</span><?php else: ?><span class="badge badge-draft">Borrador</span><?php endif; ?>
            <form method="post" action="<?= url("admin/menus/{$it['id']}/delete") ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<style>
.menu-item{display:flex;align-items:center;gap:.5rem;padding:.6rem .8rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:.35rem}
.menu-item:hover{border-color:var(--accent)}
.drag-handle{cursor:grab;color:var(--text-muted)}
.drag-handle:active{cursor:grabbing}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tree = document.getElementById('menuTree');
  if (!tree) return;
  new Sortable(tree, {
    handle: '.drag-handle',
    animation: 150,
    onEnd: () => {
      const items = [...tree.querySelectorAll('.menu-item')].map((el, i) => ({
        id: el.dataset.id,
        parent_id: null, // simplificado; en producción calcular por nivel
        level: el.dataset.level,
        order: i
      }));
      const fd = new FormData();
      fd.append('items', JSON.stringify(items));
      fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
      fetch('<?= url("admin/menus/reorder") ?>', { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r=>r.json()).then(d => { if (d.success) toast('Menú actualizado'); });
    }
  });
});
function toast(m){const t=document.createElement('div');t.className='toast success';t.innerHTML='<i class="bi bi-check-circle"></i> '+m;document.body.appendChild(t);setTimeout(()=>t.remove(),2500);}
</script>