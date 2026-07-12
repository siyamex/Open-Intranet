<div class="page-head">
    <h1>Search</h1>
</div>

<div class="card">
    <form method="get" action="<?= e(url('search')) ?>" class="filter-bar">
        <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search news, documents, people, links…" autofocus style="min-width:280px;">
        <button type="submit" class="btn btn-primary">Search</button>
        <span class="text-muted" style="font-size:0.85rem;">Tip: press <kbd>Ctrl</kbd>+<kbd>K</kbd> anywhere.</span>
    </form>

    <?php if ($q === '' && $recent !== []): ?>
        <p class="text-muted" style="margin-bottom:0.4rem;">Recent searches:</p>
        <div class="checkbox-row">
            <?php foreach ($recent as $term): ?>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('search') . '?q=' . rawurlencode((string) $term)) ?>"><?= e((string) $term) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($results !== null): ?>
    <?php if ($results === []): ?>
    <div class="card"><p class="text-muted" style="margin:0;">Nothing found for “<?= e($q) ?>”.</p></div>
    <?php else: ?>
        <?php foreach ($results as $group => $items): ?>
        <div class="card">
            <h2><?= e((string) $group) ?> <span class="text-muted">(<?= count($items) ?>)</span></h2>
            <ul class="search-results">
                <?php foreach ($items as $item): ?>
                <li>
                    <span class="badge search-badge"><?= e((string) $group) ?></span>
                    <span style="flex:1; min-width:0;">
                        <a href="<?= e((string) $item['url']) ?>"><strong><?= e((string) $item['title']) ?></strong></a>
                        <span class="text-muted">· <?= e((string) $item['meta']) ?></span>
                        <?php if ($item['snippet'] !== ''): ?><br><span class="search-snippet"><?= $item['snippet'] /* escaped + <mark> only */ ?></span><?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
