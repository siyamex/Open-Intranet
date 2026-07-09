<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' . config('app.name') : config('app.name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= View::section('styles') ?>
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo" aria-hidden="true"><?= e(mb_substr((string) config('app.name'), 0, 1)) ?></div>
            <h1 class="auth-title"><?= e(config('app.name')) ?></h1>
        </div>
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
