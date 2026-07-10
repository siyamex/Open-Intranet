<?php
use App\Core\Settings;
use App\Core\View;

$siteName = (string) Settings::get('site_name', config('app.name'));
$themeMode = (string) user_pref('theme_mode', 'auto');
?>
<!doctype html>
<html lang="en" data-theme-mode="<?= e(in_array($themeMode, ['auto', 'light', 'dark'], true) ? $themeMode : 'auto') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — Admin — ' . $siteName : 'Admin — ' . $siteName) ?></title>
<?php partial('partials/head-assets'); ?>
<?= View::section('styles') ?>
</head>
<body class="has-shell" data-theme-pref-url="<?= e(url('prefs.theme-mode')) ?>" data-csrf="<?= e(csrf_token()) ?>">
<?php partial('partials/impersonation'); ?>
<?php partial('partials/navbar'); ?>
<div class="shell">
    <?php partial('partials/admin-sidebar'); ?>
    <main class="main" id="main">
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/components.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
