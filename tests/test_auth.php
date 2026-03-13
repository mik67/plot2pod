<?php
// Run: php tests/test_auth.php
session_name('plot2pod_test');
session_start();

require_once __DIR__ . '/../auth.php';

// Test 1: isLoggedIn returns false when no session
$_SESSION = [];
assert(!isLoggedIn(), 'FAIL: isLoggedIn should be false with empty session');
echo "PASS: isLoggedIn returns false when not logged in\n";

// Test 2: isLoggedIn returns true when session has user_id
$_SESSION['user_id'] = 1;
assert(isLoggedIn(), 'FAIL: isLoggedIn should be true with user_id in session');
echo "PASS: isLoggedIn returns true when logged in\n";

// Test 3: isAdmin returns false when not admin
$_SESSION['is_admin'] = 0;
assert(!isAdmin(), 'FAIL: isAdmin should return false for regular user');
echo "PASS: isAdmin returns false for regular user\n";

// Test 4: isAdmin returns true for admin
$_SESSION['is_admin'] = 1;
assert(isAdmin(), 'FAIL: isAdmin should return true for admin');
echo "PASS: isAdmin returns true for admin\n";

// Test 5: CSRF token generation returns 64-char hex string
$_SESSION = [];
$token = generateCsrfToken();
assert(!empty($token), 'FAIL: CSRF token should not be empty');
assert(strlen($token) === 64, 'FAIL: CSRF token should be 64 chars, got ' . strlen($token));
assert(ctype_xdigit($token), 'FAIL: CSRF token should be hexadecimal');
echo "PASS: CSRF token generated correctly (64-char hex)\n";

// Test 6: same call returns same token (not regenerated each time)
$token2 = generateCsrfToken();
assert($token === $token2, 'FAIL: token should be stable within session');
echo "PASS: CSRF token stable within session\n";

// Test 7: valid token validates
assert(validateCsrfToken($token), 'FAIL: valid token should pass validation');
echo "PASS: valid CSRF token validates\n";

// Test 8: wrong token fails
assert(!validateCsrfToken('badtoken'), 'FAIL: bad token should not validate');
assert(!validateCsrfToken(''), 'FAIL: empty token should not validate');
echo "PASS: invalid CSRF tokens rejected\n";

// Test 9: currentUser returns null when not logged in
$_SESSION = [];
assert(currentUser() === null, 'FAIL: currentUser should return null when not logged in');
echo "PASS: currentUser returns null when not logged in\n";

// Test 10: currentUser returns array when logged in
$_SESSION['user_id']    = 42;
$_SESSION['user_name']  = 'Milos';
$_SESSION['user_email'] = 'milos@example.com';
$_SESSION['is_admin']   = 1;
$user = currentUser();
assert($user['id'] === 42, 'FAIL: user id mismatch');
assert($user['name'] === 'Milos', 'FAIL: user name mismatch');
assert($user['is_admin'] === 1, 'FAIL: is_admin mismatch');
echo "PASS: currentUser returns correct session data\n";

echo "\nAll auth tests passed.\n";
