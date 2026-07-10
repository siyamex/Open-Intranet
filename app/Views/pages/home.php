<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Welcome back, <?= e(explode(' ', trim((string) \App\Core\Auth::user()['name']))[0]) ?> 👋</h1>
</div>

<?php foreach ($sections as $section): ?>
    <?php if ($section === 'quick_links' && isset($quickLinks)): ?>
        <?php partial('partials/home/quick-links', ['quickLinks' => $quickLinks]); ?>
    <?php elseif ($section === 'news' && isset($newsPosts)): ?>
        <?php partial('partials/home/news', ['newsPosts' => $newsPosts, 'pinnedPosts' => $pinnedPosts ?? []]); ?>
    <?php elseif ($section === 'gazette' && isset($gazetteDocs)): ?>
        <?php partial('partials/home/gazette', ['gazetteDocs' => $gazetteDocs]); ?>
    <?php endif; ?>
<?php endforeach; ?>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/quick-links.js')) ?>"></script>
<?php View::end(); ?>
