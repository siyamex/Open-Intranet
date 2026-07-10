<div class="page-head">
    <h1>Search</h1>
</div>

<div class="card">
    <form method="get" action="<?= e(url('search')) ?>" class="filter-bar">
        <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search news, documents, people…" autofocus style="min-width:280px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($q === ''): ?>
        <p class="text-muted">Type something to search across the portal.</p>
    <?php elseif ($results === null): ?>
        <p class="text-muted">Unified search is being rolled out — soon this will find news, documents, people and links matching “<?= e($q) ?>”.</p>
    <?php endif; ?>
</div>
