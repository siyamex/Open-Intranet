<div class="page-head">
    <h1>Security</h1>
    <p class="text-muted">Manage how you sign in to your account.</p>
</div>

<div class="card">
    <h2>Password</h2>
    <?php if ($hasPassword): ?>
        <p>You have a password set for local sign-in.</p>
        <a class="btn btn-secondary" href="<?= e(url('password.change')) ?>">Change password</a>
    <?php else: ?>
        <p class="text-muted">Your account uses single sign-on only — no local password is set.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Connected accounts</h2>
    <?php if ($identities === []): ?>
        <p class="text-muted">No single sign-on accounts connected yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Provider</th><th>Account</th><th>Connected</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($identities as $identity): ?>
                <tr>
                    <td><?= e((string) $identity['provider_name']) ?></td>
                    <td><?= e((string) ($identity['email'] ?? '—')) ?></td>
                    <td><?= e(date('j M Y', strtotime((string) $identity['created_at']))) ?></td>
                    <td>
                        <form method="post" action="<?= e(url('profile.security.unlink', ['id' => $identity['id']])) ?>"
                              data-confirm="Disconnect this account?">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger btn-sm">Disconnect</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

    <?php if ($available !== []): ?>
        <h3 style="margin-top:1rem;">Connect another account</h3>
        <div class="sso-buttons" style="max-width:320px;">
            <?php foreach ($available as $p): ?>
                <a class="btn btn-secondary" href="<?= e(base_url('auth/' . $p['slug'] . '/redirect?link=1')) ?>">Connect <?= e((string) $p['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
