<div class="page-head">
    <h1>Wiki</h1>
</div>

<?php if ($canManage): ?>
<div class="card">
    <h2>Create a space</h2>
    <form method="post" action="<?= e(url('wiki.space.store')) ?>" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="Space name (e.g. HR Handbook)" required style="max-width:240px;">
        <input class="form-control" name="description" placeholder="Description" style="max-width:320px;">
        <button type="submit" class="btn btn-primary">Create</button>
    </form>
</div>
<?php endif; ?>

<div class="theme-gallery">
    <?php foreach ($spaces as $space): ?>
    <a class="card theme-card" href="<?= e(url('wiki.space', ['slug' => $space['slug']])) ?>" style="color:var(--color-text);">
        <h2 style="margin:0;"><?= icon('book') ?> <?= e((string) $space['name']) ?></h2>
        <?php if (!empty($space['description'])): ?><p class="text-muted" style="margin:0.4rem 0 0;"><?= e((string) $space['description']) ?></p><?php endif; ?>
        <p class="text-muted" style="margin:0.4rem 0 0; font-size:0.85rem;"><?= (int) $space['page_count'] ?> page(s)</p>
    </a>
    <?php endforeach; ?>
    <?php if ($spaces === []): ?>
    <div class="card"><p class="text-muted" style="margin:0;">No wiki spaces yet.</p></div>
    <?php endif; ?>
</div>
