<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>429 — Slow down</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">429</p>
    <h1>Too many requests</h1>
    <p class="text-muted">You're doing that a little too often. Wait a minute and try again.</p>
    <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Back to home</a>
</main>
</body>
</html>
