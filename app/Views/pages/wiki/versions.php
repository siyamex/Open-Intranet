<div class="page-head">
    <h1>History — <?= e((string) $page['title']) ?></h1>
    <p><a href="<?= e(url('wiki.page', ['slug' => $space['slug'], 'pageSlug' => $page['slug']])) ?>">&larr; Back to page</a></p>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Saved</th><th>Title then</th><th>Edited by</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($versions as $version): ?>
        <tr>
            <td><?= e(date('j M Y H:i', strtotime((string) $version['created_at']))) ?></td>
            <td><?= e((string) $version['title']) ?></td>
            <td><?= e((string) ($version['editor_name'] ?? '—')) ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('wiki.diff', ['slug' => $space['slug'], 'pageSlug' => $page['slug'], 'versionId' => $version['id']])) ?>">Diff vs current</a>
                <form method="post" action="<?= e(url('wiki.restore', ['slug' => $space['slug'], 'pageSlug' => $page['slug'], 'versionId' => $version['id']])) ?>" style="display:inline;"
                      data-confirm="Restore this version? The current content is kept in history.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Restore</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($versions === []): ?>
        <tr><td colspan="4" class="text-muted">No previous versions — this page has only been saved once.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
