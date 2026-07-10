<h2 class="auth-heading">Reset your password</h2>
<p class="text-muted">Enter your email address and we'll send you a reset link, valid for 1 hour.</p>

<form method="post" action="<?= e(url('password.email')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    <p class="auth-links"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
</form>
