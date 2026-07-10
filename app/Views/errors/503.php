<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Maintenance</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">🔧</p>
    <h1>Down for maintenance</h1>
    <p class="text-muted"><?= e($message ?? 'We are doing some maintenance — back soon.') ?></p>
</main>
</body>
</html>
