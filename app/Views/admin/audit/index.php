<div class="page-head">
    <h1>Audit Log</h1>
    <p class="text-muted"><?= (int) $total ?> entries match.</p>
</div>

<div class="card">
    <form method="get" action="<?= e(url('admin.audit')) ?>" class="filter-bar">
        <select class="form-control" name="user_id">
            <option value="">Any user</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int) $u['id'] ?>" <?= $filters['user_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e((string) $u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" name="action">
            <option value="">Any action</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?= e((string) $a) ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= e((string) $a) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control" name="entity_type" placeholder="Entity type" value="<?= e($filters['entity_type']) ?>" style="max-width:140px;">
        <input class="form-control" type="date" name="from" value="<?= e($filters['from']) ?>">
        <input class="form-control" type="date" name="to" value="<?= e($filters['to']) ?>">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a class="btn btn-secondary" href="<?= e(url('admin.audit') . '?' . http_build_query(array_merge(array_filter($filters), ['export' => 'csv']))) ?>">Export CSV</a>
    </form>

    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td style="white-space:nowrap;"><?= e(date('j M Y H:i:s', strtotime((string) $row['created_at']))) ?></td>
            <td><?= e((string) ($row['user_name'] ?? 'system')) ?></td>
            <td><code><?= e((string) $row['action']) ?></code></td>
            <td><?= e(trim((string) ($row['entity_type'] ?? '') . ' #' . (string) ($row['entity_id'] ?? ''), ' #')) ?></td>
            <td><?= e((string) ($row['ip'] ?? '')) ?></td>
            <td>
                <?php if (!empty($row['meta'])): ?>
                <details class="audit-details">
                    <summary>meta</summary>
                    <pre><?= e(json_encode(json_decode((string) $row['meta'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                </details>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
        <tr><td colspan="6" class="text-muted">No entries match your filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php for ($p = max(1, $page - 5); $p <= min($pages, $page + 5); $p++): ?>
        <a class="page-link <?= $p === $page ? 'active' : '' ?>"
           href="<?= e(url('admin.audit') . '?' . http_build_query(array_merge(array_filter($filters), ['page' => $p]))) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
