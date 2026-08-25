<?php foreach (get_flash() as $type => $msg): ?>
  <div class="alert alert-<?= $type==='error'?'danger':$type ?> alert-dismissible">
    <i class="bi bi-<?= $type==='success'?'check-circle':($type==='error'?'exclamation-triangle':'info-circle') ?>"></i>
    <span><?= $msg ?></span>
    <button class="btn-close" onclick="this.parentElement.remove()">×</button>
  </div>
<?php endforeach; ?>