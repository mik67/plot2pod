<?php
// Run: php tests/test_db.php (requires DB to be running)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Test 1: connection returns PDO instance
assert($pdo instanceof PDO, 'FAIL: $pdo is not a PDO instance');
echo "PASS: DB connection established\n";

// Test 2: can query users table
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
assert($stmt !== false, 'FAIL: could not query users table');
echo "PASS: users table accessible\n";

// Test 3: can query podcasts table
$stmt = $pdo->query("SELECT COUNT(*) FROM podcasts");
assert($stmt !== false, 'FAIL: could not query podcasts table');
echo "PASS: podcasts table accessible\n";

// Test 4: can query requests table
$stmt = $pdo->query("SELECT COUNT(*) FROM requests");
assert($stmt !== false, 'FAIL: could not query requests table');
echo "PASS: requests table accessible\n";

echo "\nAll DB tests passed.\n";
