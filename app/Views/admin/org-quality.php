<div class="page-head">
    <h1>Org chart data quality</h1>
    <p class="text-muted">Issues that make the org chart incomplete or wrong.</p>
</div>

<div class="card">
    <h2>Reporting-line cycles (<?= count($cycles) ?>)</h2>
    <?php if ($cycles === []): ?>
    <p style="color:var(--color-success);">✓ No cycles detected.</p>
    <?php else: ?>
    <ul>
        <?php foreach ($cycles as $cycle): ?>
        <li style="color:var(--color-danger);">
            <?= e(implode(' → ', array_column($cycle, 'name'))) ?> — fix the manager of one of these users.
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Users with an inactive manager (<?= count($broken) ?>)</h2>
    <?php if ($broken === []): ?>
    <p style="color:var(--color-success);">✓ Every manager is active.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>User</th><th>Manager</th><th>Manager status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($broken as $row): ?>
        <tr>
            <td><?= e((string) $row['name']) ?></td>
            <td><?= e((string) $row['manager_name']) ?></td>
            <td><span class="badge" style="background:var(--color-warning);"><?= e((string) $row['manager_status']) ?></span></td>
            <td><a class="btn btn-secondary btn-sm" href="<?= e(url('admin.users.edit', ['id' => $row['id']])) ?>">Fix</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>People with no manager and no reports (<?= count($orphans) ?>)</h2>
    <p class="text-muted">Intended roots (e.g. the CEO) have reports; these accounts float outside the tree.</p>
    <?php if ($orphans === []): ?>
    <p style="color:var(--color-success);">✓ Everyone is connected.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>User</th><th>Title</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orphans as $row): ?>
        <tr>
            <td><?= e((string) $row['name']) ?></td>
            <td><?= e((string) ($row['job_title'] ?? '—')) ?></td>
            <td><a class="btn btn-secondary btn-sm" href="<?= e(url('admin.users.edit', ['id' => $row['id']])) ?>">Assign manager</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
