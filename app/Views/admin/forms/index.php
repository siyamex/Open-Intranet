<div class="page-head">
    <h1>Request forms</h1>
    <p class="text-muted">Build HR/IT request forms with approval workflows.</p>
</div>

<div class="card">
    <div class="filter-bar">
        <span style="flex:1;"></span>
        <a class="btn btn-primary" href="<?= e(url('admin.forms.create')) ?>">New form</a>
    </div>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Form</th><th>Approver</th><th>Published</th><th>Submissions</th><th>Retention</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($forms as $form): ?>
        <tr>
            <td><strong><?= e((string) $form['title']) ?></strong><br><span class="text-muted"><?= e((string) ($form['description'] ?? '')) ?></span></td>
            <td><?= e((string) $form['approver_type']) ?></td>
            <td><?= (int) $form['is_published'] === 1 ? '✓' : '—' ?></td>
            <td><?= (int) $form['submissions'] ?></td>
            <td><?= $form['retention_days'] !== null ? (int) $form['retention_days'] . 'd' : '∞' ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.forms.edit', ['id' => $form['id']])) ?>">Edit</a>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.forms.submissions', ['id' => $form['id']])) ?>">Submissions</a>
                <form method="post" action="<?= e(url('admin.forms.destroy', ['id' => $form['id']])) ?>" style="display:inline;"
                      data-confirm="Delete this form AND all its submissions?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($forms === []): ?>
        <tr><td colspan="6" class="text-muted">No forms yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
