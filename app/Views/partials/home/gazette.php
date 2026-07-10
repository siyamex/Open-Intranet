<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('files') ?> Gazette</h2>
        <a href="<?= e(url('documents.index')) ?>">Document center &rarr;</a>
    </div>
    <?php if ($gazetteDocs === []): ?>
        <p class="text-muted">No gazette documents published yet.</p>
    <?php else: ?>
    <div class="card" style="padding:0.5rem 1rem;">
        <ul class="gazette-list">
            <?php foreach ($gazetteDocs as $doc): ?>
            <li>
                <?php partial('partials/doc-type-badge', ['doc' => $doc]); ?>
                <a class="gazette-title" href="<?= e(url('files.serve', ['uuid' => $doc['uuid']])) ?>"
                   <?= $doc['mime'] === 'application/pdf' ? 'target="_blank" rel="noopener"' : '' ?>><?= e((string) $doc['title']) ?></a>
                <span class="text-muted"><?= e(date('j M Y', strtotime((string) ($doc['published_at'] ?? $doc['created_at'])))) ?></span>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('files.serve', ['uuid' => $doc['uuid']])) ?>"><?= icon('download', 'icon icon-sm') ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</section>
