<div class="page-head">
    <h1>News</h1>
</div>

<div class="filter-bar">
    <form method="get" action="<?= e(url('news.index')) ?>" class="filter-bar" style="margin:0;">
        <input class="form-control" type="search" name="q" placeholder="Search news…" value="<?= e($q) ?>">
        <select class="form-control" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $category === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<?php if ($posts === []): ?>
<div class="card"><p class="text-muted" style="margin:0;">No news found.</p></div>
<?php else: ?>
<div class="news-grid">
    <?php foreach ($posts as $post): ?>
    <?php partial('partials/news-card', ['post' => $post]); ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($pages > 1): ?>
<nav class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="page-link <?= $p === $page ? 'active' : '' ?>"
           href="<?= e(url('news.index') . '?' . http_build_query(array_filter(['q' => $q, 'category' => $category ?: null, 'page' => $p]))) ?>"><?= $p ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
