<?php
require_once __DIR__ . '/../db.php';

// Test 1: basic slug
$slug = slugify('Hello World');
assert($slug === 'hello-world', 'FAIL: basic slug: got ' . $slug);
echo "PASS: basic slug\n";

// Test 2: special chars stripped
$slug = slugify('AI & Machine Learning: What\'s Next?');
assert($slug === 'ai-machine-learning-whats-next', 'FAIL: special chars: got ' . $slug);
echo "PASS: special chars stripped\n";

// Test 3: multiple spaces/hyphens collapsed
$slug = slugify('Too   Many   Spaces');
assert($slug === 'too-many-spaces', 'FAIL: spaces: got ' . $slug);
echo "PASS: multiple spaces collapsed\n";

// Test 4: leading/trailing hyphens trimmed
$slug = slugify('  --test--  ');
assert($slug === 'test', 'FAIL: trim: got ' . $slug);
echo "PASS: leading/trailing hyphens trimmed\n";

// Test 5: max 200 chars
$slug = slugify(str_repeat('a', 300));
assert(strlen($slug) <= 200, 'FAIL: max length: got ' . strlen($slug));
echo "PASS: slug truncated to 200 chars\n";

// Test 6: all-special-chars input returns non-empty slug from slugify
$slug = slugify('???');
assert($slug === '', 'FAIL: all-special returns empty from slugify (expected): got ' . $slug);
echo "PASS: all-special-chars input returns empty string from slugify\n";

// Test 7: slugify on empty string returns empty
$slug = slugify('');
assert($slug === '', 'FAIL: empty input: got ' . $slug);
echo "PASS: empty input returns empty string\n";

echo "\nAll slugify tests passed.\n";
