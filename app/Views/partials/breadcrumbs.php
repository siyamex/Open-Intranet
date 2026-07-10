<?php
/**
 * Optional $breadcrumbs: array of [label, url|null]. Falls back to Home / $title.
 */
$crumbs = $breadcrumbs ?? (isset($title) && $title !== 'Home' ? [[$title, null]] : []);
?>
<?php if ($crumbs !== []): ?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(url('home')) ?>">Home</a>
    <?php foreach ($crumbs as [$label, $link]): ?>
        <span class="crumb-sep">/</span>
        <?php if ($link !== null): ?>
            <a href="<?= e((string) $link) ?>"><?= e((string) $label) ?></a>
        <?php else: ?>
            <span aria-current="page"><?= e((string) $label) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
