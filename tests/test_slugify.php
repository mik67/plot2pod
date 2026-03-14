<?php
require_once __DIR__ . '/../db.php';

// Test 1: basic slug
$slug = slugify('Hello World');
assert($slug === 'hello-world', 'FAIL: basic slug: got ' . $slug);
echo "PASS: basic slug\n";

// Test 2: special chars stripped
$slug = slugify('AI & Machine Learning: What\'s Next?');
assert($slug === 'ai-machine-learning-what-s-next', 'FAIL: special chars: got ' . $slug);
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

echo "\nAll slugify tests passed.\n";
