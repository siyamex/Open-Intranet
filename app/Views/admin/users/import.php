<div class="page-head">
    <h1>Import users</h1>
    <p><a href="<?= e(url('admin.users')) ?>">&larr; Back to users</a></p>
</div>

<?php if ($preview === null): ?>
<div class="card" style="max-width:640px;">
    <p>Upload a CSV of users. You'll get a <strong>dry-run preview</strong> with per-row validation before anything is written.
       Missing departments are created automatically. Imported users receive no password — use
       <em>Force password reset</em> (or invite emails) to onboard them.</p>
    <p><a class="btn btn-secondary btn-sm" href="<?= e(url('admin.users.import.template')) ?>">Download CSV template</a></p>
    <form method="post" action="<?= e(url('admin.users.import.preview')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="csv">CSV file (max 2 MB, up to 1000 rows)</label>
            <input class="form-control" type="file" id="csv" name="csv" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload &amp; preview</button>
    </form>
</div>
<?php else: ?>
<?php
$valid = count(array_filter($preview, static fn ($r) => $r['errors'] === []));
$invalid = count($preview) - $valid;
?>
<div class="card">
    <h2>Dry-run preview</h2>
    <p><strong><?= $valid ?></strong> row(s) ready to import<?= $invalid > 0 ? ", <strong>{$invalid}</strong> row(s) with errors will be skipped" : '' ?>.</p>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Line</th><th>Name</th><th>Email</th><th>Department</th><th>Roles</th><th>Manager</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($preview as $row): ?>
        <tr>
            <td><?= (int) $row['line'] ?></td>
            <td><?= e((string) $row['data']['name']) ?></td>
            <td><?= e((string) $row['data']['email']) ?></td>
            <td>
                <?= e((string) $row['data']['department'] ?: '—') ?>
                <?php if (!empty($row['data']['department_new'])): ?><span class="badge">new</span><?php endif; ?>
            </td>
            <td><?= e((string) $row['data']['roles'] ?: 'employee') ?></td>
            <td><?= e((string) $row['data']['manager_email'] ?: '—') ?></td>
            <td>
                <?php if ($row['errors'] === []): ?>
                    <span style="color:var(--color-success);">✓ OK</span>
                <?php else: ?>
                    <span style="color:var(--color-danger);"><?= e(implode(' ', $row['errors'])) ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div style="display:flex; gap:0.5rem; margin-top:1rem;">
        <?php if ($valid > 0): ?>
        <form method="post" action="<?= e(url('admin.users.import.commit')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Import <?= $valid ?> user(s)</button>
        </form>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url('admin.users.import')) ?>">Start over</a>
    </div>
</div>
<?php endif; ?>
