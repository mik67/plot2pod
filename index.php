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
    <title>plot2pod – AI-generated podcasts on any topic</title>
    <?php include __DIR__ . '/partials/meta.php'; ?>
    <link rel="stylesheet" href="/css/style.css">
    <?php if (!empty($podcasts)): ?>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        'name'     => 'Latest podcasts',
        'url'      => rtrim(SITE_URL, '/') . '/',
        'itemListElement' => array_map(function($p, $i) {
            return [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $p['title'],
                'url'      => rtrim(SITE_URL, '/') . '/podcast.php?id=' . (int)$p['id'],
            ];
        }, $podcasts, array_keys($podcasts)),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php endif; ?>
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
    <h2>Dive in — free podcasts<br>on science, politics, tech, and beyond</h2>
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

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="/js/app.js"></script>
</body>
</html>
