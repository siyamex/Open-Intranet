<?php
use App\Core\Settings;
use App\Core\View;

$siteName = (string) Settings::get('site_name', config('app.name'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' . $siteName : $siteName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script src="<?= e(asset('js/theme-boot.js')) ?>"></script>
<?= View::section('styles') ?>
</head>
<body class="has-shell">
<?php partial('partials/impersonation'); ?>
<?php partial('partials/navbar'); ?>
<div class="shell">
    <?php partial('partials/sidebar'); ?>
    <main class="main" id="main">
        <?php partial('partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs ?? null, 'title' => $title ?? null]); ?>
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/components.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
