<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/xml; charset=utf-8');

$podcasts = $pdo->query(
    "SELECT id, slug, created_at FROM podcasts WHERE published = 1 AND deleted = 0 ORDER BY created_at DESC"
)->fetchAll();

$base = rtrim(SITE_URL, '/');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $base ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach ($podcasts as $p): ?>
    <url>
        <loc><?= $base ?>/podcast/<?= htmlspecialchars($p['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($p['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
