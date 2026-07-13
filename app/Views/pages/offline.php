<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">📡</p>
    <h1>You're offline</h1>
    <p class="text-muted">Check your connection — this page will reload once you're back online.</p>
    <button class="btn btn-primary" onclick="location.reload()">Retry</button>
</main>
</body>
</html>
