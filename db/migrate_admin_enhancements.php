<?php
/**
 * Migration: add deleted flag to podcasts, add rejected/deleted status and reject_reason to requests.
 * Run once on server: php db/migrate_admin_enhancements.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Add deleted column to podcasts
$pdo->exec("
    ALTER TABLE podcasts
    ADD COLUMN IF NOT EXISTS deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER published
");
echo "podcasts.deleted column added (or already exists).\n";

// Extend requests status enum
$pdo->exec("
    ALTER TABLE requests
    MODIFY COLUMN status ENUM('pending','processing','done','rejected','deleted') NOT NULL DEFAULT 'pending'
");
echo "requests.status enum extended.\n";

// Add reject_reason column to requests
$pdo->exec("
    ALTER TABLE requests
    ADD COLUMN IF NOT EXISTS reject_reason TEXT NULL DEFAULT NULL AFTER notified_at
");
echo "requests.reject_reason column added (or already exists).\n";

echo "\nMigration complete.\n";
