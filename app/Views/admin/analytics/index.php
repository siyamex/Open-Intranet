<div class="page-head">
    <h1>Analytics</h1>
    <p class="text-muted">Privacy-friendly: no third-party trackers, IPs are never stored.</p>
</div>

<form method="get" action="<?= e(url('admin.analytics')) ?>" class="filter-bar">
    <label class="form-label" style="margin:0;">From</label>
    <input class="form-control" type="date" name="from" value="<?= e($from) ?>">
    <label class="form-label" style="margin:0;">To</label>
    <input class="form-control" type="date" name="to" value="<?= e($to) ?>">
    <button type="submit" class="btn btn-secondary">Apply</button>
</form>

<div class="admin-two-col">
    <div class="card">
        <h2>Daily active users (DAU)</h2>
        <canvas id="chart-dau" height="180" data-series='<?= e(json_encode($dauSeries)) ?>' style="width:100%;"></canvas>
    </div>
    <div class="card">
        <h2>Weekly active users (WAU, rolling 7d)</h2>
        <canvas id="chart-wau" height="180" data-series='<?= e(json_encode($wauSeries)) ?>' style="width:100%;"></canvas>
    </div>
</div>

<div class="card">
    <h2>Page views</h2>
    <canvas id="chart-views" height="200" data-series='<?= e(json_encode($viewsSeries)) ?>' style="width:100%;"></canvas>
</div>

<div class="card">
    <h2>Peak hours (views by hour of day)</h2>
    <div class="heatmap-row" id="heatmap" data-values='<?= e(json_encode($heatmap)) ?>'></div>
</div>

<div class="admin-two-col">
    <div class="card">
        <h2>Top pages</h2>
        <?php if ($topPages === []): ?><p class="text-muted">No data yet — run <code>php cli.php analytics:rollup</code>.</p><?php endif; ?>
        <ul class="rank-list">
            <?php foreach ($topPages as $row): ?>
            <li><span><?= e($row['label']) ?></span><strong><?= (int) $row['value'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Top quick links (all-time)</h2>
        <ul class="rank-list">
            <?php foreach ($topLinks as $row): ?>
            <li><span><?= e((string) $row['title']) ?></span><strong><?= (int) $row['click_count'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Top news (all-time views)</h2>
        <ul class="rank-list">
            <?php foreach ($topNews as $row): ?>
            <li><span><?= e((string) $row['title']) ?></span><strong><?= (int) $row['views'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Document downloads (all-time)</h2>
        <ul class="rank-list">
            <?php foreach ($topDownloads as $row): ?>
            <li><span><?= e((string) $row['title']) ?></span><strong><?= (int) $row['download_count'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Top search terms</h2>
        <ul class="rank-list">
            <?php foreach ($topSearches as $row): ?>
            <li><span><?= e($row['label']) ?></span><strong><?= (int) $row['value'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Zero-result search terms</h2>
        <ul class="rank-list">
            <?php foreach ($zeroResultSearches as $row): ?>
            <li><span><?= e($row['label']) ?></span><strong style="color:var(--color-danger);"><?= (int) $row['value'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($zeroResultSearches === []): ?><p class="text-muted">None — good sign.</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Settings</h2>
    <form method="post" action="<?= e(url('admin.analytics.settings')) ?>">
        <?= csrf_field() ?>
        <label class="form-check" style="margin-bottom:0.75rem;">
            <input type="checkbox" name="anonymize" value="1" <?= $anonymize ? 'checked' : '' ?>>
            Anonymize (hash user IDs instead of storing them — top-page/search stats still work)
        </label>
        <div class="form-group" style="max-width:240px;">
            <label class="form-label">Raw event retention (days)</label>
            <input class="form-control" type="number" min="30" max="1825" name="retention_days" value="<?= (int) $retentionDays ?>">
            <p class="form-hint">Applied by <code>php cli.php analytics:rollup</code> (cron). Daily rollups are kept forever.</p>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<?php \App\Core\View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-analytics.js')) ?>"></script>
<?php \App\Core\View::end(); ?>
