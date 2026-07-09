<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 — Page not found</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">404</p>
    <h1>Page not found</h1>
    <p class="text-muted">The page you are looking for doesn't exist or has moved.</p>
    <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Back to home</a>
</main>
</body>
</html>
