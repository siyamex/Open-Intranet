<?php
use App\Core\Settings;
use App\Core\View;

$siteName = (string) Settings::get('site_name', config('app.name'));
$logoPath = Settings::get('logo_path');
?>
<!doctype html>
<html lang="<?= e(\App\Core\Lang::locale()) ?>" dir="<?= e(\App\Core\Lang::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' . $siteName : $siteName) ?></title>
<?php partial('partials/head-assets'); ?>
<?= View::section('styles') ?>
</head>
<body class="auth-body" data-csrf="<?= e(csrf_token()) ?>">
<?php partial('partials/banners'); ?>
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <?php if (is_string($logoPath) && $logoPath !== ''): ?>
                <img class="auth-logo-img" src="<?= e(base_url($logoPath)) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <div class="auth-logo" aria-hidden="true"><?= e(mb_substr($siteName, 0, 1)) ?></div>
            <?php endif; ?>
            <h1 class="auth-title"><?= e($siteName) ?></h1>
        </div>
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/banners.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
