<h2 class="auth-heading">Account not found</h2>
<p>Your <?= e((string) $provider['name']) ?> sign-in worked<?= $email !== '' ? ' (' . e($email) . ')' : '' ?>, but there is no
matching account on this portal yet.</p>
<p class="text-muted">Please contact your administrator to have an account created or linked for you.</p>
<a class="btn btn-primary btn-block" href="<?= e(url('login')) ?>">Back to sign in</a>
