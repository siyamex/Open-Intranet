<?php
use App\Core\View;

$sortLink = function (string $column, string $label) use ($filters): string {
    $dir = ($filters['sort'] === $column && $filters['dir'] === 'asc') ? 'desc' : 'asc';
    $params = array_merge($filters, ['sort' => $column, 'dir' => $dir]);
    $arrow = $filters['sort'] === $column ? ($filters['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . e(url('admin.users') . '?' . http_build_query(array_filter($params))) . '">' . e($label) . $arrow . '</a>';
};
?>
<div class="page-head">
    <h1>Users</h1>
    <p class="text-muted"><?= (int) $result['total'] ?> user(s)</p>
</div>

<div class="card">
    <form method="get" action="<?= e(url('admin.users')) ?>" class="filter-bar">
        <input class="form-control" type="search" name="q" placeholder="Search name, email, title…" value="<?= e($filters['q']) ?>">
        <select class="form-control" name="department_id">
            <option value="">All departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (string) $d['id'] === $filters['department_id'] ? 'selected' : '' ?>><?= e((string) $d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" name="role_id">
            <option value="">All roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= (string) $r['id'] === $filters['role_id'] ? 'selected' : '' ?>><?= e((string) $r['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" name="status">
            <option value="">Any status</option>
            <?php foreach (['active', 'inactive', 'suspended'] as $st): ?>
                <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <span style="flex:1;"></span>
        <a class="btn btn-secondary" href="<?= e(url('admin.users.import')) ?>">Import CSV</a>
        <a class="btn btn-primary" href="<?= e(url('admin.users.create')) ?>">Add user</a>
    </form>

    <div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th></th>
            <th><?= $sortLink('name', 'Name') ?></th>
            <th><?= $sortLink('email', 'Email') ?></th>
            <th>Department</th>
            <th>Roles</th>
            <th><?= $sortLink('status', 'Status') ?></th>
            <th><?= $sortLink('last_login_at', 'Last login') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['rows'] as $u): ?>
        <tr>
            <td><?php partial('partials/avatar', ['person' => $u, 'size' => 32]); ?></td>
            <td>
                <strong><?= e((string) $u['name']) ?></strong>
                <?php if (!empty($u['job_title'])): ?><br><span class="text-muted"><?= e((string) $u['job_title']) ?></span><?php endif; ?>
            </td>
            <td><?= e((string) $u['email']) ?></td>
            <td><?= e((string) ($u['department_name'] ?? '—')) ?></td>
            <td><?= e((string) ($u['role_names'] ?? '—')) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin.users.toggle', ['id' => $u['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-primary' : 'btn-secondary' ?>"
                            title="Click to toggle active/inactive"><?= e(ucfirst((string) $u['status'])) ?></button>
                </form>
            </td>
            <td><?= $u['last_login_at'] !== null ? e(date('j M Y H:i', strtotime((string) $u['last_login_at']))) : '<span class="text-muted">never</span>' ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.users.edit', ['id' => $u['id']])) ?>">Edit</a>
                <form method="post" action="<?= e(url('admin.users.impersonate', ['id' => $u['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm" title="View the portal as this user (super admin only)">Impersonate</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?>
        <tr><td colspan="8" class="text-muted">No users match your filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
    <nav class="pagination">
        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <?php $params = array_merge(array_filter($filters), ['page' => $p]); ?>
            <a class="page-link <?= $p === $result['page'] ? 'active' : '' ?>"
               href="<?= e(url('admin.users') . '?' . http_build_query($params)) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
