<div class="page-head">
    <h1>Documents</h1>
</div>

<div class="docs-layout">
    <aside class="docs-sidebar card">
        <h3>Categories</h3>
        <ul class="doc-cat-tree">
            <li class="<?= $categoryId === 0 ? 'active' : '' ?>"><a href="<?= e(url('documents.index')) ?>">All documents</a></li>
            <?php foreach ($tree as $cat): ?>
            <li class="<?= $categoryId === (int) $cat['id'] ? 'active' : '' ?>">
                <a href="<?= e(url('documents.index') . '?category=' . (int) $cat['id']) ?>"><?= icon('folder', 'icon icon-sm') ?> <?= e((string) $cat['name']) ?></a>
                <?php if ($cat['children'] !== []): ?>
                <ul>
                    <?php foreach ($cat['children'] as $child): ?>
                    <li class="<?= $categoryId === (int) $child['id'] ? 'active' : '' ?>">
                        <a href="<?= e(url('documents.index') . '?category=' . (int) $child['id']) ?>"><?= e((string) $child['name']) ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="docs-main">
        <form method="get" action="<?= e(url('documents.index')) ?>" class="filter-bar">
            <?php if ($categoryId > 0): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
            <input class="form-control" type="search" name="q" placeholder="Search by title…" value="<?= e($q) ?>" style="min-width:240px;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>

        <div class="card">
            <?php if ($docs === []): ?>
            <p class="text-muted" style="margin:0;">No documents here yet.</p>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Type</th><th>Title</th><th>Version</th><th>Size</th><th>Date</th><th>Uploaded by</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($docs as $doc): ?>
                <tr>
                    <td><?php partial('partials/doc-type-badge', ['doc' => $doc]); ?></td>
                    <td>
                        <strong><?= e((string) $doc['title']) ?></strong>
                        <?php if (!empty($doc['description'])): ?><br><span class="text-muted" style="font-size:0.85rem;"><?= e(mb_strimwidth((string) $doc['description'], 0, 90, '…')) ?></span><?php endif; ?>
                    </td>
                    <td><span class="badge">v<?= (int) $doc['version'] ?></span></td>
                    <td><?= e(format_bytes((int) $doc['size_bytes'])) ?></td>
                    <td><?= e(date('j M Y', strtotime((string) ($doc['published_at'] ?? $doc['created_at'])))) ?></td>
                    <td><?= e((string) ($doc['uploader_name'] ?? '—')) ?></td>
                    <td style="white-space:nowrap;">
                        <a class="btn btn-secondary btn-sm" href="<?= e(url('files.serve', ['uuid' => $doc['uuid']])) ?>"
                           <?= $doc['mime'] === 'application/pdf' ? 'target="_blank" rel="noopener"' : '' ?>>
                            <?= $doc['mime'] === 'application/pdf' ? 'Open' : 'Download' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
