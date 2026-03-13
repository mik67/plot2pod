<?php
// Run: php tests/test_request.php (requires live DB)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Setup: test user
$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")
    ->execute(['Req Tester', 'reqtest_' . time() . '@example.com', password_hash('x', PASSWORD_DEFAULT)]);
$userId = $pdo->lastInsertId();

// Test 1: topic request inserts correctly
$stmt = $pdo->prepare("INSERT INTO requests (user_id, type, content) VALUES (?, 'topic', ?)");
$stmt->execute([$userId, 'The history of jazz music']);
$reqId = $pdo->lastInsertId();
assert($reqId > 0, 'FAIL: topic request insert failed');
echo "PASS: topic request inserted (id=$reqId)\n";

// Test 2: default status is pending
$row = $pdo->query("SELECT status FROM requests WHERE id=$reqId")->fetch();
assert($row['status'] === 'pending', 'FAIL: default status should be pending, got: ' . $row['status']);
echo "PASS: default status is 'pending'\n";

// Test 3: links request inserts correctly
$stmt2 = $pdo->prepare("INSERT INTO requests (user_id, type, content) VALUES (?, 'links', ?)");
$stmt2->execute([$userId, "https://example.com\nhttps://example.org"]);
$reqId2 = $pdo->lastInsertId();
assert($reqId2 > 0, 'FAIL: links request insert failed');
echo "PASS: links request inserted (id=$reqId2)\n";

// Test 4: files request with JSON file_paths
$filePaths = json_encode(['abc123.pdf', 'def456.docx']);
$stmt3 = $pdo->prepare("INSERT INTO requests (user_id, type, file_paths) VALUES (?, 'files', ?)");
$stmt3->execute([$userId, $filePaths]);
$reqId3 = $pdo->lastInsertId();
$row3 = $pdo->query("SELECT file_paths FROM requests WHERE id=$reqId3")->fetch();
$decoded = json_decode($row3['file_paths'], true);
assert(is_array($decoded) && count($decoded) === 2, 'FAIL: file_paths JSON decode failed');
echo "PASS: files request inserted with JSON file_paths\n";

// Test 5: ENUM rejects invalid status
try {
    $pdo->prepare("UPDATE requests SET status='invalid' WHERE id=?")->execute([$reqId]);
    // MariaDB may warn but not throw — check the stored value
    $stored = $pdo->query("SELECT status FROM requests WHERE id=$reqId")->fetch();
    assert($stored['status'] !== 'invalid', 'FAIL: invalid status should not be stored');
    echo "PASS: invalid status value rejected or ignored by ENUM\n";
} catch (PDOException $e) {
    echo "PASS: invalid status value rejected by DB exception\n";
}

// Cleanup
$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
echo "\nAll request tests passed.\n";
