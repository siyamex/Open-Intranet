<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>405 — Method not allowed</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">405</p>
    <h1>Method not allowed</h1>
    <p class="text-muted">That request method isn't supported here<?= isset($allowed) && $allowed !== [] ? ' — allowed: ' . e(implode(', ', $allowed)) : '' ?>.</p>
    <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Back to home</a>
</main>
</body>
</html>
