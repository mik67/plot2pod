<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/rss+xml; charset=utf-8');

try {
    $podcasts = $pdo->query(
        "SELECT id, slug, title, description, mp3_path, duration, created_at
         FROM podcasts WHERE published = 1 AND deleted = 0 ORDER BY created_at DESC"
    )->fetchAll();
} catch (Exception $e) {
    $podcasts = [];
}

$base = rtrim(SITE_URL, '/');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
<channel>
    <title>plot2pod</title>
    <link><?= htmlspecialchars($base) ?>/</link>
    <description>AI-generated debate-format podcasts on any topic</description>
    <language>en-us</language>
    <itunes:title>plot2pod</itunes:title>
    <itunes:author>plot2pod.com</itunes:author>
    <itunes:summary>AI-generated debate-format podcasts on any topic — submitted by real people, free to listen.</itunes:summary>
    <itunes:image href="<?= htmlspecialchars($base) ?>/img/plot2pod-cover.jpg"/>
    <itunes:category text="Science"/>
    <itunes:explicit>false</itunes:explicit>
    <itunes:owner>
        <itunes:name>plot2pod.com</itunes:name>
        <itunes:email>milos.mikulasek@gmail.com</itunes:email>
    </itunes:owner>
    <?php foreach ($podcasts as $p):
        $url     = $base . '/podcast/' . $p['slug'];
        $mp3     = str_starts_with($p['mp3_path'], 'http') ? $p['mp3_path'] : $base . $p['mp3_path'];
        $pubDate = date(DATE_RSS, strtotime($p['created_at']));
        $duration = gmdate('H:i:s', (int)$p['duration']);
    ?>
    <item>
        <title><?= htmlspecialchars($p['title']) ?></title>
        <link><?= htmlspecialchars($url) ?></link>
        <description><?= htmlspecialchars($p['description']) ?></description>
        <pubDate><?= $pubDate ?></pubDate>
        <guid isPermaLink="true"><?= htmlspecialchars($url) ?></guid>
        <enclosure url="<?= htmlspecialchars($mp3) ?>" type="audio/mpeg" length="0"/>
        <itunes:title><?= htmlspecialchars($p['title']) ?></itunes:title>
        <itunes:summary><?= htmlspecialchars($p['description']) ?></itunes:summary>
        <itunes:duration><?= $duration ?></itunes:duration>
        <itunes:explicit>false</itunes:explicit>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
