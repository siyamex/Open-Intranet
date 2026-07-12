<div class="page-head">
    <h1><?= icon('book') ?> <?= e((string) $space['name']) ?></h1>
</div>

<div class="docs-layout">
    <aside class="docs-sidebar card">
        <?php if ($canEdit): ?>
        <a class="btn btn-primary btn-sm btn-block" style="margin-bottom:0.75rem;"
           href="<?= e(url('wiki.edit', ['slug' => $space['slug'], 'pageSlug' => '_new'])) ?>">New page</a>
        <?php endif; ?>
        <h3>Pages</h3>
        <ul class="doc-cat-tree">
            <?php
            $renderTree = function (array $nodes) use (&$renderTree, $space, $page): void {
                foreach ($nodes as $node) {
                    $active = $page !== null && (int) $node['id'] === (int) $page['id'];
                    echo '<li class="' . ($active ? 'active' : '') . '">';
                    echo '<a href="' . e(url('wiki.page', ['slug' => $space['slug'], 'pageSlug' => $node['slug']])) . '">' . e((string) $node['title']) . '</a>';
                    if ($node['children'] !== []) {
                        echo '<ul>';
                        $renderTree($node['children']);
                        echo '</ul>';
                    }
                    echo '</li>';
                }
            };
            $renderTree($tree);
            ?>
        </ul>
        <?php if ($tree === []): ?><p class="text-muted">No pages yet.</p><?php endif; ?>
    </aside>

    <div class="docs-main">
        <?php if ($page === null): ?>
        <div class="card"><p class="text-muted" style="margin:0;">This space is empty<?= $canEdit ? ' — create the first page.' : '.' ?></p></div>
        <?php else: ?>
        <div class="card">
            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                <h1 style="margin:0; flex:1;"><?= e((string) $page['title']) ?></h1>
                <?php if ($canEdit): ?>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('wiki.edit', ['slug' => $space['slug'], 'pageSlug' => $page['slug']])) ?>">Edit</a>
                <?php endif; ?>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('wiki.versions', ['slug' => $space['slug'], 'pageSlug' => $page['slug']])) ?>">History</a>
            </div>
            <p class="text-muted" style="font-size:0.85rem;">
                Owner: <?= e((string) ($page['owner_name'] ?? '—')) ?> · updated <?= e(date('j M Y H:i', strtotime((string) $page['updated_at']))) ?>
                <?php if ($page['review_due'] !== null): ?>
                    · review due <?= e(date('j M Y', strtotime((string) $page['review_due']))) ?>
                    <?= strtotime((string) $page['review_due']) < time() ? '<span class="badge" style="background:var(--color-danger);">overdue</span>' : '' ?>
                <?php endif; ?>
            </p>

            <?php if (count($toc) >= 2): ?>
            <nav class="wiki-toc">
                <strong>On this page</strong>
                <ul>
                    <?php foreach ($toc as $item): ?>
                    <li class="toc-l<?= (int) $item['level'] ?>"><a href="#<?= e((string) $item['id']) ?>"><?= e((string) $item['text']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <div class="article-body wiki-body"><?= $rendered /* Markdown output: fixed tag set, input escaped */ ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
