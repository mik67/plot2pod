<?php
// Run: php tests/test_mailer.php
// Note: does NOT send real email — only tests function signatures and logic.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';

// Test 1: sendMail function exists and is callable
assert(function_exists('sendMail'), 'FAIL: sendMail function not defined');
echo "PASS: sendMail function exists\n";

// Test 2: sendNewRequestNotification function exists
assert(function_exists('sendNewRequestNotification'), 'FAIL: sendNewRequestNotification not defined');
echo "PASS: sendNewRequestNotification function exists\n";

// Test 3: sendDoneNotification function exists
assert(function_exists('sendDoneNotification'), 'FAIL: sendDoneNotification not defined');
echo "PASS: sendDoneNotification function exists\n";

// Test 4: constants used by mailer are defined
assert(defined('FROM_EMAIL'),  'FAIL: FROM_EMAIL not defined');
assert(defined('FROM_NAME'),   'FAIL: FROM_NAME not defined');
assert(defined('ADMIN_EMAIL'), 'FAIL: ADMIN_EMAIL not defined');
assert(defined('SITE_URL'),    'FAIL: SITE_URL not defined');
echo "PASS: all required constants defined\n";

// Test 5: FROM_EMAIL looks like a valid email
assert(filter_var(FROM_EMAIL, FILTER_VALIDATE_EMAIL) !== false,
    'FAIL: FROM_EMAIL is not a valid email address');
echo "PASS: FROM_EMAIL is valid\n";

// Test 6: ADMIN_EMAIL looks like a valid email
assert(filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL) !== false,
    'FAIL: ADMIN_EMAIL is not a valid email address');
echo "PASS: ADMIN_EMAIL is valid\n";

echo "\nAll mailer tests passed.\n";
echo "Note: actual email delivery can only be verified on the live server.\n";
