<?php if (!empty($_SESSION['impersonator_id'])): ?>
<div class="impersonation-banner">
    <span>👁 You are impersonating <strong><?= e((string) (\App\Core\Auth::user()['name'] ?? '')) ?></strong> — every action is audited.</span>
    <form method="post" action="<?= e(url('impersonate.stop')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm">Return to my account</button>
    </form>
</div>
<?php endif; ?>
