<?php
use App\Core\Auth;
use App\Core\Settings;
use App\Core\View;

$siteName = (string) Settings::get('site_name', config('app.name'));
$authUser = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' — ' . $siteName : $siteName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= View::section('styles') ?>
</head>
<body>
<?php partial('partials/impersonation'); ?>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(url('home')) ?>"><?= e($siteName) ?></a>
        <div class="topbar-spacer"></div>
        <?php if ($authUser !== null): ?>
        <span class="topbar-user"><?= e((string) $authUser['name']) ?></span>
        <form method="post" action="<?= e(url('logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm">Sign out</button>
        </form>
        <?php endif; ?>
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
