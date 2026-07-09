<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — Admin — ' . config('app.name') : 'Admin — ' . config('app.name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= View::section('styles') ?>
</head>
<body>
<header class="topbar topbar-admin">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(url('home')) ?>"><?= e(config('app.name')) ?></a>
        <span class="badge">Admin</span>
    </div>
</header>
<main class="container">
    <?php partial('partials/flash'); ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
