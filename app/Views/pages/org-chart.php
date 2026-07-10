<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Org Chart</h1>
</div>

<?php if ($cycles !== []): ?>
<div class="card" style="border-left:4px solid var(--color-danger);">
    <strong>Data problem:</strong> reporting-line cycle detected
    (<?php foreach ($cycles as $i => $cycle): ?><?= $i > 0 ? '; ' : '' ?><?= e(implode(' → ', array_column($cycle, 'name'))) ?><?php endforeach; ?>).
    These people are shown as roots until an admin fixes their manager.
</div>
<?php endif; ?>

<div class="filter-bar oc-toolbar">
    <div class="tabs" style="margin:0; border:none;">
        <button type="button" class="tab active" data-oc-view="hierarchy">Hierarchy</button>
        <button type="button" class="tab" data-oc-view="departments">By department</button>
        <button type="button" class="tab" data-oc-view="flat">Flat list</button>
    </div>
    <span style="flex:1;"></span>
    <input class="form-control" type="search" id="oc-search" placeholder="Find a person…" style="max-width:220px;">
    <select class="form-control" id="oc-dept-filter" style="max-width:200px;">
        <option value="">All departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= (int) $d['id'] ?>"><?= e((string) $d['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div id="oc-hierarchy" class="oc-view">
    <div class="oc-canvas-wrap card" id="oc-canvas-wrap">
        <div class="oc-controls">
            <button type="button" class="btn btn-secondary btn-sm" id="oc-zoom-in">+</button>
            <button type="button" class="btn btn-secondary btn-sm" id="oc-zoom-out">−</button>
            <button type="button" class="btn btn-secondary btn-sm" id="oc-fit">Fit</button>
            <button type="button" class="btn btn-secondary btn-sm" id="oc-export">PNG</button>
        </div>
        <svg id="oc-svg" role="img" aria-label="Organization chart"></svg>
        <noscript><p class="text-muted">JavaScript is required for the interactive chart — use the flat list below.</p></noscript>
    </div>
    <aside class="oc-panel card" id="oc-panel" hidden>
        <button type="button" class="toast-close" id="oc-panel-close" aria-label="Close">&times;</button>
        <div id="oc-panel-body"></div>
    </aside>
</div>

<div id="oc-departments" class="oc-view" hidden>
    <?php foreach ($byDepartment as $deptName => $members): ?>
    <div class="card">
        <h2><?= e((string) $deptName) ?> <span class="text-muted">(<?= count($members) ?>)</span></h2>
        <div class="dir-grid">
            <?php foreach ($members as $m): ?>
            <div class="card dir-card" style="margin:0;">
                <a href="<?= e(url('people.show', ['id' => $m['id']])) ?>"><?php partial('partials/avatar', ['person' => $m, 'size' => 48]); ?></a>
                <a class="dir-name" href="<?= e(url('people.show', ['id' => $m['id']])) ?>"><?= e((string) $m['name']) ?></a>
                <span class="text-muted dir-title"><?= e((string) ($m['job_title'] ?? '')) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="oc-flat" class="oc-view oc-print" hidden>
    <div class="card">
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Title</th><th>Department</th><th>Manager</th></tr></thead>
            <tbody>
            <?php foreach ($flat as $row): ?>
            <tr>
                <td><a href="<?= e(url('people.show', ['id' => $row['id']])) ?>"><?= e((string) $row['name']) ?></a></td>
                <td><?= e((string) ($row['job_title'] ?? '')) ?></td>
                <td><?= e((string) ($row['department_name'] ?? '—')) ?></td>
                <td><?= e((string) ($row['manager_name'] ?? '—')) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script type="application/json" id="oc-data"><?= json_encode(['tree' => $tree], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/org-chart.js')) ?>"></script>
<?php View::end(); ?>
