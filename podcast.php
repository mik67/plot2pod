<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    include __DIR__ . '/partials/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id = ? AND published = 1");
$stmt->execute([$id]);
$podcast = $stmt->fetch();

if (!$podcast) {
    http_response_code(404);
    include __DIR__ . '/partials/404.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(mb_substr($podcast['description'], 0, 160)) ?>">
    <title><?= htmlspecialchars($podcast['title']) ?> – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="podcast-detail">
    <div class="section-inner">
        <a href="/" class="back-link">← All podcasts</a>
        <h1><?= htmlspecialchars($podcast['title']) ?></h1>
        <p class="podcast-meta">Duration: <?= gmdate('G:i:s', $podcast['duration']) ?></p>
        <p class="podcast-description"><?= htmlspecialchars($podcast['description']) ?></p>

        <audio controls preload="metadata" class="main-player">
            <source src="<?= htmlspecialchars($podcast['mp3_path']) ?>" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>

        <div class="podcast-cta">
            <?php if (isLoggedIn()): ?>
                <a href="/request.php" class="btn-primary">Submit your own topic</a>
            <?php else: ?>
                <p>Want your own podcast? <a href="/register.php" class="btn-primary">Get started free</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
