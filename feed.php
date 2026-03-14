<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/rss+xml; charset=utf-8');

try {
    $podcasts = $pdo->query(
        "SELECT id, slug, title, description, mp3_path, duration, created_at
         FROM podcasts WHERE published = 1 ORDER BY created_at DESC"
    )->fetchAll();
} catch (Exception $e) {
    $podcasts = [];
}

$base = rtrim(SITE_URL, '/');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
    <title>plot2pod</title>
    <link><?= htmlspecialchars($base) ?>/</link>
    <description>AI-generated debate-format podcasts on any topic</description>
    <language>en-us</language>
    <?php foreach ($podcasts as $p):
        $url    = $base . '/podcast/' . $p['slug'];
        $mp3    = str_starts_with($p['mp3_path'], 'http') ? $p['mp3_path'] : $base . $p['mp3_path'];
        $pubDate = date(DATE_RSS, strtotime($p['created_at']));
    ?>
    <item>
        <title><?= htmlspecialchars($p['title']) ?></title>
        <link><?= htmlspecialchars($url) ?></link>
        <description><?= htmlspecialchars($p['description']) ?></description>
        <pubDate><?= $pubDate ?></pubDate>
        <guid isPermaLink="true"><?= htmlspecialchars($url) ?></guid>
        <enclosure url="<?= htmlspecialchars($mp3) ?>" type="audio/mpeg" length="0"/>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
