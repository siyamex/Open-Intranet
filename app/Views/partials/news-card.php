<article class="news-card">
    <a class="news-card-media" href="<?= e(url('news.show', ['slug' => $post['slug']])) ?>">
        <?php if (!empty($post['cover_path'])): ?>
            <img src="<?= e(url('news.media', ['file' => basename((string) $post['cover_path'])])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="news-card-placeholder"><?= icon('news', 'icon') ?></span>
        <?php endif; ?>
        <?php if ((int) $post['is_pinned'] === 1): ?><span class="badge news-pin">Pinned</span><?php endif; ?>
    </a>
    <div class="news-card-body">
        <?php if (!empty($post['category_name'])): ?>
        <span class="chip" style="background: <?= e((string) ($post['category_color'] ?? '#4f46e5')) ?>;"><?= e((string) $post['category_name']) ?></span>
        <?php endif; ?>
        <h3><a href="<?= e(url('news.show', ['slug' => $post['slug']])) ?>"><?= e((string) $post['title']) ?></a></h3>
        <?php if (!empty($post['excerpt'])): ?><p class="text-muted news-excerpt"><?= e((string) $post['excerpt']) ?></p><?php endif; ?>
        <div class="news-meta">
            <?php partial('partials/avatar', ['person' => ['name' => $post['author_name'] ?? 'Unknown', 'avatar_path' => $post['author_avatar'] ?? null], 'size' => 24]); ?>
            <span><?= e((string) ($post['author_name'] ?? 'Unknown')) ?></span>
            <span class="text-muted">· <?= e(date('j M Y', strtotime((string) $post['published_at']))) ?> · <?= (int) $post['views'] ?> views</span>
        </div>
    </div>
</article>
