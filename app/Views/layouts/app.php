<?php
use App\Core\Settings;
use App\Core\View;

use App\Core\Lang;

$siteName = (string) Settings::get('site_name', config('app.name'));
$themeMode = (string) user_pref('theme_mode', 'auto');
?>
<!doctype html>
<html lang="<?= e(Lang::locale()) ?>" dir="<?= e(Lang::dir()) ?>" data-theme-mode="<?= e(in_array($themeMode, ['auto', 'light', 'dark'], true) ? $themeMode : 'auto') ?>"
      data-sw-url="<?= e(url('pwa.sw')) ?>" data-app-base="<?= e(rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' . $siteName : $siteName) ?></title>
<?php partial('partials/head-assets'); ?>
<?= View::section('styles') ?>
</head>
<body class="has-shell" data-theme-pref-url="<?= e(url('prefs.theme-mode')) ?>" data-csrf="<?= e(csrf_token()) ?>">
<?php partial('partials/banners'); ?>
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
<?php partial('partials/search-overlay'); ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/components.js')) ?>"></script>
<script src="<?= e(asset('js/search-overlay.js')) ?>"></script>
<script src="<?= e(asset('js/banners.js')) ?>"></script>
<script src="<?= e(asset('js/lang-switch.js')) ?>"></script>
<script src="<?= e(asset('js/pwa.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
