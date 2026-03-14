<?php require_once __DIR__ . '/../auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not found – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <h1>404</h1>
        <p>Page not found.</p>
        <a href="/" class="btn-primary">Back to homepage</a>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
