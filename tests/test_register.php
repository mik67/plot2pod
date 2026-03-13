<?php
// Run: php tests/test_register.php (requires live DB)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$testEmail = 'testregister_' . time() . '@example.com';

// Test 1: can insert a user with hashed password
$hash = password_hash('testpass123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute(['Test User', $testEmail, $hash]);
$id = $pdo->lastInsertId();
assert($id > 0, 'FAIL: user insert failed');
echo "PASS: user inserted with id=$id\n";

// Test 2: duplicate email is rejected
try {
    $stmt->execute(['Duplicate', $testEmail, $hash]);
    assert(false, 'FAIL: duplicate email should throw PDOException');
} catch (PDOException $e) {
    assert(str_contains($e->getMessage(), 'Duplicate'), 'FAIL: unexpected exception: ' . $e->getMessage());
    echo "PASS: duplicate email rejected by DB\n";
}

// Test 3: password_hash / password_verify round-trip
$row = $pdo->query("SELECT password_hash FROM users WHERE id=$id")->fetch();
assert(password_verify('testpass123', $row['password_hash']), 'FAIL: password_verify failed');
echo "PASS: password hash/verify round-trip works\n";

// Test 4: default is_admin is 0
$row = $pdo->query("SELECT is_admin FROM users WHERE id=$id")->fetch();
assert((int)$row['is_admin'] === 0, 'FAIL: new user should not be admin');
echo "PASS: new user is not admin by default\n";

// Cleanup
$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
echo "\nAll registration tests passed.\n";
