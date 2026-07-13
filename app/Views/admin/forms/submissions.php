<div class="page-head">
    <h1>Submissions — <?= e((string) $form['title']) ?></h1>
    <p><a href="<?= e(url('admin.forms')) ?>">&larr; Back to forms</a>
       · <a href="<?= e(url('admin.forms.submissions', ['id' => $form['id']]) . '?export=csv') ?>">Export CSV</a></p>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>#</th><th>Submitted by</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td>#<?= (int) $row['id'] ?></td>
            <td><?= e((string) $row['submitter_name']) ?></td>
            <td><span class="badge status-<?= e((string) $row['status']) ?>"><?= e(str_replace('_', ' ', (string) $row['status'])) ?></span></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $row['created_at']))) ?></td>
            <td><a class="btn btn-secondary btn-sm" href="<?= e(url('requests.detail', ['id' => $row['id']])) ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
        <tr><td colspan="5" class="text-muted">No submissions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
