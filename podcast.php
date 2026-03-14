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
    <title><?= htmlspecialchars($podcast['title']) ?> – plot2pod</title>
    <?php
    $metaTitle     = $podcast['title'];
    $metaDesc      = $podcast['description'];
    $metaCanonical = SITE_URL . '/podcast.php?id=' . $podcast['id'];
    include __DIR__ . '/partials/meta.php';
    ?>
    <link rel="stylesheet" href="/css/style.css">
    <script type="application/ld+json">
    <?= json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'PodcastEpisode',
        'name'            => $podcast['title'],
        'description'     => $podcast['description'],
        'url'             => SITE_URL . '/podcast.php?id=' . $podcast['id'],
        'associatedMedia' => [
            '@type'      => 'MediaObject',
            'contentUrl' => (str_starts_with($podcast['mp3_path'], 'http') ? '' : SITE_URL) . $podcast['mp3_path'],
            'encodingFormat' => 'audio/mpeg',
            'duration'   => 'PT' . gmdate('H\Hi\Ms\S', $podcast['duration']),
        ],
        'partOfSeries'    => [
            '@type' => 'PodcastSeries',
            'name'  => 'plot2pod',
            'url'   => SITE_URL,
        ],
        'datePublished'   => date('Y-m-d', strtotime($podcast['created_at'])),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Home',
                'item'     => rtrim(SITE_URL, '/') . '/',
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $podcast['title'],
                'item'     => SITE_URL . '/podcast.php?id=' . (int)$podcast['id'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="podcast-detail">
    <div class="section-inner">
        <a href="/" class="back-link">← All podcasts</a>
        <h1><?= htmlspecialchars($podcast['title']) ?></h1>
        <p class="podcast-meta">Duration: <?= gmdate('G:i:s', $podcast['duration']) ?></p>
        <p class="podcast-description"><?= htmlspecialchars($podcast['description']) ?></p>

        <div class="podcast-player-wrap">
            <audio controls preload="metadata">
                <source src="<?= htmlspecialchars($podcast['mp3_path']) ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>

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
