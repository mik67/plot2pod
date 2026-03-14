<?php
/**
 * One-time migration: add slug column to podcasts and backfill.
 * Run once on the server: php db/migrate_slugs.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Add column if not exists
$pdo->exec("
    ALTER TABLE podcasts
    ADD COLUMN IF NOT EXISTS slug VARCHAR(200) NOT NULL DEFAULT '' AFTER title
");
echo "Column added (or already exists).\n";

// Add unique index if not exists
try {
    $pdo->exec("ALTER TABLE podcasts ADD UNIQUE KEY uq_podcast_slug (slug)");
    echo "Unique index added.\n";
} catch (PDOException $e) {
    echo "Unique index already exists (skipped).\n";
}

// Backfill empty slugs
$podcasts = $pdo->query("SELECT id, title FROM podcasts WHERE slug = ''")->fetchAll();
echo "Backfilling " . count($podcasts) . " podcasts...\n";

$update = $pdo->prepare("UPDATE podcasts SET slug = ? WHERE id = ?");
foreach ($podcasts as $p) {
    $slug = generateUniqueSlug($pdo, $p['title']);
    $update->execute([$slug, $p['id']]);
    echo "  #{$p['id']}: {$p['title']} → {$slug}\n";
}

echo "\nMigration complete.\n";
