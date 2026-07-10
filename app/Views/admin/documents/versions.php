<div class="page-head">
    <h1>Versions — <?= e((string) $doc['title']) ?></h1>
    <p><a href="<?= e(url('admin.documents')) ?>">&larr; Back to documents</a></p>
</div>

<div class="card">
    <h2>Upload new version</h2>
    <form method="post" action="<?= e(url('admin.documents.version', ['id' => $doc['id']])) ?>" enctype="multipart/form-data" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" type="file" name="file" required style="max-width:300px;">
        <input class="form-control" name="version_note" placeholder="Changelog note (optional)" maxlength="255" style="max-width:300px;">
        <button type="submit" class="btn btn-primary">Upload v<?= (int) $doc['version'] + 1 ?></button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Version</th><th>File</th><th>Size</th><th>Note</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <tr style="background:var(--color-surface-2);">
            <td><span class="badge">v<?= (int) $doc['version'] ?> (current)</span></td>
            <td><?= e((string) $doc['original_name']) ?></td>
            <td><?= e(format_bytes((int) $doc['size_bytes'])) ?></td>
            <td><?= e((string) ($doc['version_note'] ?? '—')) ?></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $doc['updated_at']))) ?></td>
            <td><a class="btn btn-secondary btn-sm" href="<?= e(url('files.serve', ['uuid' => $doc['uuid']])) ?>">Get</a></td>
        </tr>
        <?php foreach ($versions as $v): ?>
        <tr>
            <td>v<?= (int) $v['version'] ?></td>
            <td><?= e((string) $v['original_name']) ?></td>
            <td><?= e(format_bytes((int) $v['size_bytes'])) ?></td>
            <td><?= e((string) ($v['version_note'] ?? '—')) ?></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $v['updated_at']))) ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('files.serve', ['uuid' => $v['uuid']])) ?>">Get</a>
                <form method="post" action="<?= e(url('admin.documents.restore', ['id' => $v['id']])) ?>" style="display:inline;"
                      onsubmit="return confirm('Restore v<?= (int) $v['version'] ?> as the current version?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Restore</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($versions === []): ?>
        <tr><td colspan="6" class="text-muted">No previous versions.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
