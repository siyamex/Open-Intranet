<?php $messages = \App\Core\Flash::pull(); ?>
<?php if ($messages !== []): ?>
<div class="toast-stack" role="status" aria-live="polite">
    <?php foreach ($messages as $m): ?>
    <div class="toast toast-<?= e($m['type']) ?>">
        <span><?= e($m['message']) ?></span>
        <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
