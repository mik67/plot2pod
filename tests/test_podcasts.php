<?php
// Run: php tests/test_podcasts.php (requires live DB)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Setup: insert two test podcasts
$stmt = $pdo->prepare(
    "INSERT INTO podcasts (title, description, mp3_path, duration, published) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute(['Test Podcast A', 'Description A', '/uploads/a.mp3', 1800, 1]);
$idA = $pdo->lastInsertId();

$stmt->execute(['Test Podcast B', 'Description B', '/uploads/b.mp3', 2400, 1]);
$idB = $pdo->lastInsertId();

$stmt->execute(['Hidden Podcast', 'Not published', '/uploads/c.mp3', 600, 0]);
$idC = $pdo->lastInsertId();

// Test 1: published podcasts are returned
$rows = $pdo->query(
    "SELECT * FROM podcasts WHERE published=1 ORDER BY created_at DESC"
)->fetchAll();
assert(count($rows) >= 2, 'FAIL: should have at least 2 published podcasts');
echo "PASS: published podcasts returned (" . count($rows) . " rows)\n";

// Test 2: newest podcast is first
$ids = array_column($rows, 'id');
assert(array_search($idB, $ids) < array_search($idA, $ids),
    'FAIL: newer podcast B should appear before older podcast A');
echo "PASS: podcasts ordered newest first\n";

// Test 3: unpublished podcast not in results
assert(!in_array($idC, $ids), 'FAIL: unpublished podcast should not appear');
echo "PASS: unpublished podcast excluded\n";

// Test 4: podcast fields are present
$first = $rows[0];
foreach (['id', 'title', 'description', 'mp3_path', 'duration', 'created_at'] as $field) {
    assert(array_key_exists($field, $first), "FAIL: missing field $field");
}
echo "PASS: all required fields present\n";

// Cleanup
$pdo->prepare("DELETE FROM podcasts WHERE id IN (?, ?, ?)")->execute([$idA, $idB, $idC]);
echo "\nAll podcast tests passed.\n";
