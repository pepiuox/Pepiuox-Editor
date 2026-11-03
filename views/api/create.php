<?php $pageTitle = 'New API Application'; ?>
<div class="card">
  <div class="card-header"><h3><i class="bi bi-plus-circle"></i> New API Application</h3></div>
  <div class="card-body">
    <form method="post" action="<?= url('admin/api') ?>">
      <?= \App\Core\CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Application name</label>
          <input type="text" name="app_name" class="form-control" required>
        </div>
        <div class="form-group full-width">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Allowed IPs (comma separated, empty = any)</label>
          <input type="text" name="allowed_ips" class="form-control" placeholder="1.2.3.4, 5.6.7.8">
        </div>
        <div class="form-group">
          <label class="form-label">Rate limit (req/min)</label>
          <input type="number" name="rate_limit" class="form-control" value="100" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Expires at</label>
          <input type="datetime-local" name="expires_at" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Scopes</label>
          <div>
            <?php foreach (['*','pages:read','pages:write','menus:read','files:read','users:read'] as $s): ?>
              <label class="form-check d-inline-block me-3"><input type="checkbox" name="permissions[]" value="<?= $s ?>" class="form-check-input" <?= $s==='*'?'checked':'' ?>> <code><?= $s ?></code></label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <button class="btn btn-primary"><i class="bi bi-check"></i> Create</button>
      <a class="btn btn-ghost" href="<?= url('admin/api') ?>">Cancel</a>
    </form>
  </div>
</div>