<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Admin Dashboard</h1>
</div>

<div class="stat-grid">
    <?php foreach ($stats as $label => $value): ?>
    <div class="card stat-card">
        <span class="stat-value"><?= e((string) $value) ?></span>
        <span class="stat-label"><?= e((string) $label) ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2>Logins — last 30 days</h2>
    <canvas id="logins-chart" height="220" data-series="<?= e(json_encode($loginSeries)) ?>" style="width:100%;"></canvas>
</div>

<div class="admin-two-col">
    <div class="card">
        <h2>Quick actions</h2>
        <div class="checkbox-row">
            <a class="btn btn-primary" href="<?= e(url('admin.users.create')) ?>">Add user</a>
            <a class="btn btn-secondary" href="<?= e(url('admin.news.create')) ?>">Write news</a>
            <a class="btn btn-secondary" href="<?= e(url('admin.documents')) ?>">Upload document</a>
            <a class="btn btn-secondary" href="<?= e(base_url('admin/settings')) ?>">Settings</a>
        </div>
    </div>

    <div class="card">
        <h2>Latest activity</h2>
        <?php if ($latestAudit === []): ?>
        <p class="text-muted">Nothing yet.</p>
        <?php else: ?>
        <ul class="audit-mini">
            <?php foreach ($latestAudit as $row): ?>
            <li>
                <code><?= e((string) $row['action']) ?></code>
                <span><?= e((string) ($row['user_name'] ?? 'system')) ?></span>
                <span class="text-muted"><?= e(date('j M H:i', strtotime((string) $row['created_at']))) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($canAudit): ?><p style="margin:0.5rem 0 0;"><a href="<?= e(base_url('admin/audit')) ?>">Full audit log &rarr;</a></p><?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-dashboard.js')) ?>"></script>
<?php View::end(); ?>
