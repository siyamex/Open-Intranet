<?php
/** Standalone installer page — no APP_URL/theme assumptions. */
$assetBase = $base . '/public';
$steps = [1 => 'Requirements', 2 => 'Database', 3 => 'Migrate', 4 => 'Admin', 5 => 'Finish'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install OpenIntranet — step <?= (int) $step ?></title>
<link rel="stylesheet" href="<?= e($assetBase) ?>/assets/css/app.css">
</head>
<body class="auth-body">
<main class="auth-wrap" style="max-width:560px;">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo">O</div>
            <h1 class="auth-title">Install OpenIntranet</h1>
        </div>

        <div class="tabs" style="margin-bottom:1rem;">
            <?php foreach ($steps as $n => $label): ?>
            <span class="tab <?= $n === $step ? 'active' : '' ?>"><?= $n ?>. <?= e($label) ?></span>
            <?php endforeach; ?>
        </div>

        <?php partial('partials/flash'); ?>

        <?php if ($step === 1): ?>
            <table class="table">
                <tbody>
                <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?= $check['ok'] ? '✅' : '❌' ?></td>
                    <td><?= e((string) $check['label']) ?></td>
                    <td class="text-muted"><?= e((string) $check['detail']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($allOk): ?>
                <a class="btn btn-primary btn-block" href="<?= e($base) ?>/install?step=2">Continue</a>
            <?php else: ?>
                <p class="form-error">Fix the items above, then refresh this page.</p>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <p class="text-muted">Enter your MySQL/MariaDB credentials. The database is created if it doesn't exist, then <code>.env</code> is written with a fresh APP_KEY.</p>
            <form method="post" action="<?= e($base) ?>/install?step=2">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Host</label><input class="form-control" name="db_host" value="127.0.0.1"></div>
                    <div class="form-group"><label class="form-label">Port</label><input class="form-control" name="db_port" value="3306"></div>
                </div>
                <div class="form-group"><label class="form-label">Database name</label><input class="form-control" name="db_name" value="openintranet" required></div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">User</label><input class="form-control" name="db_user" required></div>
                    <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" name="db_pass"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Test connection &amp; save</button>
            </form>

        <?php elseif ($step === 3): ?>
            <p><?= (int) $pending ?> migration(s) pending. This creates all tables and seeds roles, permissions, menus and defaults.</p>
            <form method="post" action="<?= e($base) ?>/install?step=3">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-block">Run migrations &amp; seed</button>
            </form>

        <?php elseif ($step === 4): ?>
            <p class="text-muted">Create the super administrator account.</p>
            <form method="post" action="<?= e($base) ?>/install?step=4">
                <?= csrf_field() ?>
                <div class="form-group"><label class="form-label">Full name</label><input class="form-control" name="name" required></div>
                <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Password (min 10)</label><input class="form-control" type="password" name="password" required></div>
                    <div class="form-group"><label class="form-label">Confirm</label><input class="form-control" type="password" name="password_confirmation" required></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create admin</button>
            </form>

        <?php else: ?>
            <p class="text-muted">Last step — basic site settings. You can change everything later in Admin → Settings.</p>
            <form method="post" action="<?= e($base) ?>/install?step=5">
                <?= csrf_field() ?>
                <div class="form-group"><label class="form-label">Site name</label><input class="form-control" name="site_name" value="OpenIntranet" required></div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select class="form-control" name="timezone">
                        <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= $tz === 'UTC' ? 'selected' : '' ?>><?= e($tz) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Finish installation</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
