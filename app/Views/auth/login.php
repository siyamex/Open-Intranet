<h2 class="auth-heading">Sign in</h2>

<?php if ($allowLocal): ?>
<form method="post" action="<?= e(url('login.post')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required autofocus autocomplete="username">
    </div>
    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <div class="form-group form-check">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Remember me for 30 days</label>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    <p class="auth-links"><a href="<?= e(url('password.forgot')) ?>">Forgot your password?</a></p>
</form>
<?php endif; ?>

<?php if ($providers !== []): ?>
    <?php if ($allowLocal): ?><div class="auth-divider">or continue with</div><?php endif; ?>
    <div class="sso-buttons">
        <?php foreach ($providers as $p): ?>
        <a class="btn btn-secondary btn-block sso-btn" href="<?= e(base_url('auth/' . $p['slug'] . '/redirect')) ?>"
           <?php if (!empty($p['button_color'])): ?>style="border-color: <?= e($p['button_color']) ?>; color: <?= e($p['button_color']) ?>;"<?php endif; ?>>
            Continue with <?= e($p['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
<?php elseif (!$allowLocal): ?>
    <p class="text-muted">Sign-in is currently disabled. Please contact your administrator.</p>
<?php endif; ?>
