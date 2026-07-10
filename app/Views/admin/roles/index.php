<div class="page-head">
    <h1>Roles &amp; Permissions</h1>
    <p class="text-muted">Tick what each role may do, then save. Super Admin always has every permission.</p>
</div>

<div class="card">
    <h2>Create a custom role</h2>
    <form method="post" action="<?= e(url('admin.roles.store')) ?>" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="Role name (e.g. HR Manager)" value="<?= e((string) old('name')) ?>" required style="max-width:260px;">
        <input class="form-control" name="description" placeholder="Description (optional)" value="<?= e((string) old('description')) ?>" style="max-width:340px;">
        <button type="submit" class="btn btn-primary">Create role</button>
    </form>
</div>

<div class="card">
    <form method="post" action="<?= e(url('admin.roles.matrix')) ?>">
        <?= csrf_field() ?>
        <div class="table-wrap">
        <table class="table matrix-table">
            <thead>
            <tr>
                <th>Permission</th>
                <?php foreach ($roles as $role): ?>
                <th class="matrix-role">
                    <?= e((string) $role['name']) ?><br>
                    <span class="text-muted" style="font-weight:normal;"><?= (int) $role['user_count'] ?> user(s)</span>
                    <?php if ((int) $role['is_system'] === 0): ?>
                    <br><button type="submit" class="btn btn-danger btn-sm" style="margin-top:4px;"
                        formaction="<?= e(url('admin.roles.destroy', ['id' => $role['id']])) ?>"
                        formmethod="post" name="_method" value="DELETE"
                        data-confirm="Delete role <?= e((string) $role['name']) ?>?">Delete</button>
                    <?php endif; ?>
                </th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $groupName => $permissions): ?>
            <tr><td colspan="<?= count($roles) + 1 ?>" style="background:var(--color-surface-2); font-weight:600;"><?= e((string) $groupName) ?></td></tr>
                <?php foreach ($permissions as $permission): ?>
                <tr>
                    <td>
                        <?= e((string) $permission['label']) ?><br>
                        <code class="text-muted" style="font-size:0.78rem;"><?= e((string) $permission['slug']) ?></code>
                    </td>
                    <?php foreach ($roles as $role): ?>
                    <td style="text-align:center;">
                        <?php if ($role['slug'] === 'super_admin'): ?>
                            <span title="Super Admin always has every permission">✓</span>
                        <?php else: ?>
                            <input type="checkbox" name="perm[<?= (int) $role['id'] ?>][<?= (int) $permission['id'] ?>]" value="1"
                                <?= isset($matrix[(int) $role['id']][(int) $permission['id']]) ? 'checked' : '' ?>>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save permission matrix</button>
    </form>
</div>
