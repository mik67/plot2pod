<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$podcasts = $pdo->query(
    "SELECT * FROM podcasts WHERE published = 1 ORDER BY created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Turn any topic into a debate-format podcast. Submit a topic, upload your materials, or share your sources.">
    <title>plot2pod – AI-generated podcasts on any topic</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="home">
<?php include __DIR__ . '/partials/header.php'; ?>

<section class="hero">
    <div class="hero-inner">
        <h1>Turn any topic into a podcast</h1>
        <p class="hero-sub">Submit a topic, upload your materials, or share your sources.<br>
        We'll create a debate-format podcast — for you and everyone.</p>
        <div class="hero-actions">
        <?php if (isLoggedIn()): ?>
            <a href="/request.php" class="btn-primary btn-large">Submit a topic</a>
        <?php else: ?>
            <a href="/register.php" class="btn-primary btn-large">Get started free</a>
            <a href="/login.php" class="btn-secondary">Log in</a>
        <?php endif; ?>
        </div>
    </div>
</section>

<div class="podcasts-section">
    <h2>Latest podcasts</h2>
        <?php if (empty($podcasts)): ?>
            <div class="empty-state">
                <p>Podcasts coming soon — be the first to <a href="/register.php">submit a topic</a>!</p>
            </div>
        <?php else: ?>
            <div class="podcast-grid">
                <?php foreach ($podcasts as $p): ?>
                    <?php include __DIR__ . '/partials/podcast-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="/js/app.js"></script>
</body>
</html>
