<h2 class="auth-heading">Choose a new password</h2>

<form method="post" action="<?= e(url('password.update')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input type="hidden" name="email" value="<?= e($email) ?>">
    <div class="form-group">
        <label class="form-label" for="password">New password</label>
        <input class="form-control" type="password" id="password" name="password" required autofocus autocomplete="new-password">
        <p class="form-hint">At least <?= e((string) \App\Core\Settings::get('password_min_length', 10)) ?> characters. Avoid common passwords.</p>
    </div>
    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm new password</label>
        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Update password</button>
</form>
