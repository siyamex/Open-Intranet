<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>403 — Access denied</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-body">
<main class="error-wrap">
    <p class="error-code">403</p>
    <h1>Access denied</h1>
    <p class="text-muted">You don't have permission to view this page. If you believe you should, contact your administrator.</p>
    <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Back to home</a>
</main>
</body>
</html>
