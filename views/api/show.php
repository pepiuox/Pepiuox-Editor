<?php
$pageTitle = $app['app_name'];
$newSecret = \App\Core\Session::get('new_api_secret');
$newToken = \App\Core\Session::get('new_api_token');
$newRefresh = \App\Core\Session::get('new_api_refresh');
\App\Core\Session::delete('new_api_secret');
\App\Core\Session::delete('new_api_token');
\App\Core\Session::delete('new_api_refresh');
?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-plug"></i> <?= e($app['app_name']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/api') ?>"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <?php if ($newSecret || $newToken): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <strong>Save these credentials now. They won't be shown again.</strong>
      </div>
      <?php if ($newSecret): ?>
        <div class="code-block mb-3">
          <div class="code-header"><span>API Secret</span><button class="btn btn-ghost btn-sm" onclick="copyText('apiSecret')"><i class="bi bi-clipboard"></i></button></div>
          <pre id="apiSecret"><?= e($newSecret) ?></pre>
        </div>
      <?php endif; ?>
      <?php if ($newToken): ?>
        <div class="code-block mb-3">
          <div class="code-header"><span>Bearer Token</span><button class="btn btn-ghost btn-sm" onclick="copyText('apiToken')"><i class="bi bi-clipboard"></i></button></div>
          <pre id="apiToken"><?= e($newToken) ?></pre>
        </div>
        <div class="code-block mb-3">
          <div class="code-header"><span>Refresh Token</span><button class="btn btn-ghost btn-sm" onclick="copyText('apiRefresh')"><i class="bi bi-clipboard"></i></button></div>
          <pre id="apiRefresh"><?= e($newRefresh) ?></pre>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="<?= url("admin/api/{$app['id']}") ?>">
      <?= CSRF::field() ?>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">App name</label><input type="text" name="app_name" class="form-control" value="<?= e($app['app_name']) ?>" required></div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php foreach (['active','suspended','revoked'] as $s): ?><option value="<?= $s ?>" <?= $app['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">API Key (public)</label><input type="text" class="form-control" value="<?= e($app['api_key']) ?>" readonly></div>
        <div class="form-group"><label class="form-label">Rate limit</label><input type="number" name="rate_limit" class="form-control" value="<?= (int)$app['rate_limit'] ?>"></div>
        <div class="form-group"><label class="form-label">Allowed IPs</label><input type="text" name="allowed_ips" class="form-control" value="<?= e($app['allowed_ips'] ? implode(',', json_decode($app['allowed_ips'], true) ?? []) : '') ?>"></div>
        <div class="form-group"><label class="form-label">Expires at</label><input type="datetime-local" name="expires_at" class="form-control" value="<?= e($app['expires_at'] ? date('Y-m-d\TH:i', strtotime($app['expires_at'])) : '') ?>"></div>
      </div>
      <button class="btn btn-primary"><i class="bi bi-check"></i> Save</button>
      <a class="btn btn-warning" href="<?= url("admin/api/{$app['id']}/regenerate") ?>" onclick="return confirm('Regenerate keys? Old tokens will stop working.')"><i class="bi bi-arrow-repeat"></i> Regenerate keys</a>
    </form>

    <hr>
    <h4><i class="bi bi-key"></i> Tokens</h4>
    <form method="post" action="<?= url("admin/api/{$app['id']}/tokens") ?>" class="form-grid mb-3">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label">Scopes</label>
        <input type="text" name="scopes[]" class="form-control" value="*" placeholder="* or pages:read,menus:read">
      </div>
      <div class="form-group">
        <label class="form-label">Expires in (seconds)</label>
        <input type="number" name="expires_in" class="form-control" value="3600">
      </div>
      <div class="form-group full-width"><button class="btn btn-primary"><i class="bi bi-plus"></i> Generate token</button></div>
    </form>

    <table class="table">
      <thead><tr><th>Token</th><th>Type</th><th>Expires</th><th>Revoked</th><th>Created</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tokens as $t): ?>
        <tr>
          <td><code><?= e(substr($t['token'], 0, 20)) ?>...</code></td>
          <td><?= e($t['token_type']) ?></td>
          <td><?= e($t['expires_at']) ?></td>
          <td><?= $t['revoked'] ? '<span class="badge badge-archived">Revoked</span>' : '<span class="badge badge-published">Active</span>' ?></td>
          <td><?= e($t['created_at']) ?></td>
          <td><?php if (!$t['revoked']): ?><form method="post" action="<?= url("admin/api/{$app['id']}/tokens/{$t['id']}/revoke") ?>" style="display:inline"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-x-circle"></i></button></form><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <hr>
    <h4><i class="bi bi-code-slash"></i> Usage example</h4>
    <div class="code-block">
      <div class="code-header"><span>cURL</span></div>
      <pre>curl -H "Authorization: Bearer YOUR_TOKEN" \
     <?= e(rtrim((require dirname(__DIR__,2).'/config/config.php')['app_url'],'/')) ?>/api/v1/pages</pre>
    </div>
  </div>
</div>
<script>
function copyText(id) {
  const t = document.getElementById(id).textContent;
  navigator.clipboard.writeText(t).then(() => toast('Copied'));
}
</script>