<h2 class="auth-heading"><?= $forced ? 'Set a new password' : 'Change password' ?></h2>
<?php if ($forced): ?>
<p class="text-muted">For security you must choose a new password before continuing.</p>
<?php endif; ?>

<form method="post" action="<?= e(url('password.change.post')) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($hasPassword): ?>
    <div class="form-group">
        <label class="form-label" for="current_password">Current password</label>
        <input class="form-control" type="password" id="current_password" name="current_password" required autofocus autocomplete="current-password">
    </div>
    <?php endif; ?>
    <div class="form-group">
        <label class="form-label" for="password">New password</label>
        <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
        <p class="form-hint">At least <?= e((string) \App\Core\Settings::get('password_min_length', 10)) ?> characters. Avoid common passwords.</p>
    </div>
    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm new password</label>
        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Save password</button>
</form>

<form method="post" action="<?= e(url('logout')) ?>" class="auth-links">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-secondary btn-block">Sign out</button>
</form>
