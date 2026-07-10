<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('news') ?> News</h2>
        <a href="<?= e(url('news.index')) ?>">All news &rarr;</a>
    </div>
    <?php $all = array_merge($pinnedPosts, $newsPosts); ?>
    <?php if ($all === []): ?>
        <p class="text-muted">Nothing published yet.</p>
    <?php else: ?>
    <div class="news-grid">
        <?php foreach ($all as $post): ?>
        <?php partial('partials/news-card', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
