<div class="page-head">
    <h1>Changes since <?= e(date('j M Y H:i', strtotime((string) $version['created_at']))) ?></h1>
    <p><a href="<?= e(url('wiki.versions', ['slug' => $space['slug'], 'pageSlug' => $page['slug']])) ?>">&larr; Back to history</a></p>
</div>

<div class="card">
    <p class="text-muted"><del style="background:#fee2e2;">removed</del> · <ins style="background:#dcfce7;">added</ins> (word-level, markdown source)</p>
    <pre class="wiki-diff"><?= $diff /* words are individually escaped by Diff::words */ ?></pre>
</div>
