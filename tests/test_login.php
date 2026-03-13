<?php
// Run: php tests/test_login.php (requires live DB)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$testEmail = 'testlogin_' . time() . '@example.com';
$testPass  = 'correctpass99';

// Setup: insert test user
$hash = password_hash($testPass, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")
    ->execute(['Login Tester', $testEmail, $hash]);
$userId = $pdo->lastInsertId();

// Test 1: correct credentials — user found
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$testEmail]);
$user = $stmt->fetch();
assert($user !== false, 'FAIL: user not found by email');
echo "PASS: user found by email\n";

// Test 2: correct password verifies
assert(password_verify($testPass, $user['password_hash']), 'FAIL: correct password should verify');
echo "PASS: correct password verifies\n";

// Test 3: wrong password fails
assert(!password_verify('wrongpassword', $user['password_hash']), 'FAIL: wrong password should fail');
echo "PASS: wrong password rejected\n";

// Test 4: non-existent email returns no user
$stmt->execute(['nobody@nowhere.com']);
$noUser = $stmt->fetch();
assert($noUser === false, 'FAIL: non-existent email should return false');
echo "PASS: non-existent email returns false\n";

// Cleanup
$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
echo "\nAll login tests passed.\n";
