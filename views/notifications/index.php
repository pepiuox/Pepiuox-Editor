<?php $pageTitle = t('notifications'); ?>
<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-bell"></i> <?= t('notifications') ?></h3>
    <div class="d-flex gap-2">
      <form method="post" action="<?= url('admin/notifications/mark-all') ?>" class="d-inline"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm"><i class="bi bi-check2-all"></i> <?= t('mark_all_read') ?></button></form>
      <form method="post" action="<?= url('admin/notifications/clear') ?>" class="d-inline" onsubmit="return confirm('<?= t('confirm_delete_all') ?>')"><?= CSRF::field() ?><button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> <?= t('clear_all') ?></button></form>
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (!$notifications): ?>
      <div class="text-center text-muted p-5">
        <i class="bi bi-bell-slash" style="font-size:3rem"></i>
        <p class="mt-2"><?= t('no_notifications') ?></p>
      </div>
    <?php else: ?>
      <?php foreach ($notifications as $n): ?>
        <div class="notif-item <?= $n['is_read']?'':'unread' ?> <?= e($n['type']) ?>" style="border-bottom:1px solid var(--border)">
          <div class="icon"><i class="bi bi-<?= e($n['icon']) ?>"></i></div>
          <div class="content flex-grow-1">
            <div class="title">
              <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>"><?= e($n['title']) ?></a><?php else: ?><?= e($n['title']) ?><?php endif; ?>
            </div>
            <?php if ($n['message']): ?><div class="msg"><?= e($n['message']) ?></div><?php endif; ?>
            <div class="time"><?= e($n['created_at']) ?></div>
          </div>
          <div class="d-flex gap-1">
            <?php if (!$n['is_read']): ?>
              <form method="post" action="<?= url("admin/notifications/{$n['id']}/read") ?>"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm" title="<?= t('mark_read') ?>"><i class="bi bi-check"></i></button></form>
            <?php endif; ?>
            <form method="post" action="<?= url("admin/notifications/{$n['id']}/delete") ?>" onsubmit="return confirm('<?= t('confirm_delete') ?>')"><?= CSRF::field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button></form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>