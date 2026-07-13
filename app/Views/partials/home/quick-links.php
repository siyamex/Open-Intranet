<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('bolt') ?> <?= e(__('dashboard.apps')) ?></h2>
        <span class="text-muted" style="font-size:0.85rem;">Star favorites · drag to arrange</span>
    </div>
    <?php if ($quickLinks === []): ?>
        <p class="text-muted">No apps have been added yet.</p>
    <?php else: ?>
    <div class="ql-grid" id="ql-grid"
         data-click-url="<?= e(base_url('quick-links')) ?>"
         data-order-url="<?= e(url('quick-links.order')) ?>"
         data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach ($quickLinks as $link): ?>
        <div class="ql-tile" draggable="true" data-id="<?= (int) $link['id'] ?>" <?= $link['description'] ? 'title="' . e((string) $link['description']) . '"' : '' ?>>
            <button type="button" class="ql-pin <?= $link['pinned'] ? 'pinned' : '' ?>" aria-label="Toggle favorite"><?= icon('star') ?></button>
            <a class="ql-link" href="<?= e((string) $link['url']) ?>" <?= (int) $link['open_new_tab'] === 1 ? 'target="_blank" rel="noopener"' : '' ?>>
                <span class="ql-icon" style="background: <?= e((string) ($link['bg_color'] ?? '#4f46e5')) ?>;">
                    <?php if ($link['icon_type'] === 'upload' && $link['icon_value'] !== null): ?>
                        <img src="<?= e(url('qlicon', ['file' => $link['icon_value']])) ?>" alt="">
                    <?php else: ?>
                        <?= icon((string) ($link['icon_value'] ?? 'link'), 'icon ql-svg') ?>
                    <?php endif; ?>
                </span>
                <span class="ql-label"><?= e((string) $link['title']) ?></span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
