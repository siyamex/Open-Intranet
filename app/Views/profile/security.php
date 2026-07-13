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

<div class="card">
    <h2>Personal access tokens</h2>
    <p class="text-muted">Use these to call the <a href="<?= e(url('api.docs')) ?>">REST API</a>. Treat them like passwords.</p>

    <?php if (!empty($_SESSION['new_api_token'])): ?>
    <div class="card" style="border-left:4px solid var(--color-warning); background:var(--color-surface-2);">
        <strong>Copy this token now — it won't be shown again:</strong>
        <input class="form-control" readonly value="<?= e((string) $_SESSION['new_api_token']) ?>" data-select-onclick style="margin-top:0.5rem; font-family:monospace;">
    </div>
    <?php unset($_SESSION['new_api_token']); ?>
    <?php endif; ?>

    <?php if ($tokens !== []): ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Scopes</th><th>Last used</th><th>Expires</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tokens as $token): ?>
        <tr>
            <td><?= e((string) $token['name']) ?></td>
            <td><?= e(implode(', ', (array) json_decode((string) $token['scopes'], true))) ?></td>
            <td><?= $token['last_used_at'] !== null ? e(date('j M Y H:i', strtotime((string) $token['last_used_at']))) . ' from ' . e((string) $token['last_used_ip']) : '<span class="text-muted">never</span>' ?></td>
            <td><?= $token['expires_at'] !== null ? e(date('j M Y', strtotime((string) $token['expires_at']))) : 'never' ?></td>
            <td>
                <form method="post" action="<?= e(url('profile.security.token.revoke', ['id' => $token['id']])) ?>" data-confirm="Revoke this token? Any app using it will stop working.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <p class="text-muted">No tokens yet.</p>
    <?php endif; ?>

    <h3 style="margin-top:1rem;">Create a token</h3>
    <form method="post" action="<?= e(url('profile.security.token.create')) ?>" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="e.g. My script" required maxlength="100" style="max-width:200px;">
        <label class="form-check"><input type="checkbox" name="scopes[]" value="read" checked> read</label>
        <label class="form-check"><input type="checkbox" name="scopes[]" value="write"> write</label>
        <label class="form-check"><input type="checkbox" name="scopes[]" value="admin"> admin</label>
        <select class="form-control" name="expires_days" style="max-width:160px;">
            <option value="0">Never expires</option>
            <option value="30">30 days</option>
            <option value="90" selected>90 days</option>
            <option value="365">1 year</option>
        </select>
        <button type="submit" class="btn btn-primary">Create token</button>
    </form>
</div>
