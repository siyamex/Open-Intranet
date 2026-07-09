<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>419 — Session expired</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">419</p>
    <h1>Session expired</h1>
    <p class="text-muted">Your session or security token expired. Go back, refresh the page and try again.</p>
    <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Back to home</a>
</main>
</body>
</html>
