# Clean URLs (Phase 2) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace `/podcast.php?id=123` with `/podcast/my-topic-slug` across the entire site.

**Architecture:** Add a `slug` column to the `podcasts` table, generate it from the title at creation time, add an `.htaccess` rewrite rule, update `podcast.php` to look up by slug, then update all internal links. The `?id=` parameter is kept as a silent fallback.

**Tech Stack:** PHP, MySQL, Apache mod_rewrite

---

### Task 1: Add slugify() and generateUniqueSlug() to db.php

**Files:**
- Modify: `db.php`
- Test: `tests/test_slugify.php` (create new)

**Step 1: Write the failing test**

Create `tests/test_slugify.php`:

```php
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
```

**Step 2: Run to verify it fails**

```bash
php tests/test_slugify.php
```
Expected: FAIL with "Call to undefined function slugify()"

**Step 3: Implement slugify() in db.php**

Add these two functions at the bottom of `db.php`, after the PDO setup:

```php
/**
 * Convert a string to a URL-safe slug.
 */
function slugify(string $title): string {
    $slug = mb_strtolower($title, 'UTF-8');
    $slug = preg_replace('/[^\w\s-]/u', '', $slug);   // strip special chars (keep word chars, spaces, hyphens)
    $slug = preg_replace('/[\s_]+/', '-', $slug);      // spaces/underscores → hyphens
    $slug = preg_replace('/-{2,}/', '-', $slug);       // collapse multiple hyphens
    $slug = trim($slug, '-');
    return mb_substr($slug, 0, 200);
}

/**
 * Generate a slug from title, appending -2, -3 etc. if it already exists in the DB.
 */
function generateUniqueSlug(PDO $pdo, string $title): string {
    $base = slugify($title);
    $slug = $base;
    $i    = 2;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcasts WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            break;
        }
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
```

**Step 4: Run tests to verify they pass**

```bash
php tests/test_slugify.php
```
Expected:
```
PASS: basic slug
PASS: special chars stripped
PASS: multiple spaces collapsed
PASS: leading/trailing hyphens trimmed
PASS: slug truncated to 200 chars

All slugify tests passed.
```

**Step 5: Commit**

```bash
git add db.php tests/test_slugify.php
git commit -m "feat: add slugify() and generateUniqueSlug() helpers"
```

---

### Task 2: DB migration — add slug column and backfill

**Files:**
- Create: `db/migrate_slugs.php` (run-once migration script)
- Modify: `db/schema.sql`

**Step 1: Update schema.sql**

In `db/schema.sql`, add `slug` column to the podcasts table definition after `title`:

```sql
    slug        VARCHAR(200) NOT NULL DEFAULT '',
```

And add the unique key after the table definition:

```sql
ALTER TABLE podcasts ADD UNIQUE KEY uq_podcast_slug (slug);
```

Or add it inline in the CREATE TABLE if preferred. The exact position in the file is after `title VARCHAR(200) NOT NULL,`.

**Step 2: Create db/migrate_slugs.php**

```php
<?php
/**
 * One-time migration: add slug column to podcasts and backfill.
 * Run once: php db/migrate_slugs.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Add column if not exists
$pdo->exec("
    ALTER TABLE podcasts
    ADD COLUMN IF NOT EXISTS slug VARCHAR(200) NOT NULL DEFAULT '' AFTER title
");
echo "Column added (or already exists).\n";

// Add unique index if not exists
try {
    $pdo->exec("ALTER TABLE podcasts ADD UNIQUE KEY uq_podcast_slug (slug)");
    echo "Unique index added.\n";
} catch (PDOException $e) {
    echo "Unique index already exists (skipped).\n";
}

// Backfill empty slugs
$podcasts = $pdo->query("SELECT id, title FROM podcasts WHERE slug = ''")->fetchAll();
echo "Backfilling " . count($podcasts) . " podcasts...\n";

$update = $pdo->prepare("UPDATE podcasts SET slug = ? WHERE id = ?");
foreach ($podcasts as $p) {
    $slug = generateUniqueSlug($pdo, $p['title']);
    $update->execute([$slug, $p['id']]);
    echo "  #{$p['id']}: {$p['title']} → {$slug}\n";
}

echo "\nMigration complete.\n";
```

**Step 3: Run the migration**

```bash
php db/migrate_slugs.php
```
Expected output: lists each podcast with its new slug, ends with "Migration complete."

**Step 4: Verify in DB**

```bash
php -r "require 'config.php'; require 'db.php'; \$r = \$pdo->query('SELECT id, title, slug FROM podcasts')->fetchAll(PDO::FETCH_ASSOC); print_r(\$r);"
```
Expected: all rows have non-empty slug values.

**Step 5: Commit**

```bash
git add db/schema.sql db/migrate_slugs.php
git commit -m "feat: add slug column migration and backfill script"
```

---

### Task 3: Update admin.php to generate slug on add_podcast

**Files:**
- Modify: `admin.php:21-35`

**Step 1: Update the add_podcast block**

Find the `add_podcast` action in `admin.php` (around line 21). The current INSERT is:

```php
$pdo->prepare(
    "INSERT INTO podcasts (title, description, mp3_path, duration) VALUES (?, ?, ?, ?)"
)->execute([$title, $desc, $mp3path, $duration]);
```

Replace with:

```php
$slug = generateUniqueSlug($pdo, $title);
$pdo->prepare(
    "INSERT INTO podcasts (title, slug, description, mp3_path, duration) VALUES (?, ?, ?, ?, ?)"
)->execute([$title, $slug, $desc, $mp3path, $duration]);
```

**Step 2: Verify manually**

In admin panel, add a test podcast with title "Test Podcast One". Confirm a row appears in the DB with `slug = 'test-podcast-one'`. Then add another with the same title and confirm it gets `slug = 'test-podcast-one-2'`.

**Step 3: Commit**

```bash
git add admin.php
git commit -m "feat: generate slug when adding podcast in admin"
```

---

### Task 4: Add .htaccess rewrite rule for clean URLs

**Files:**
- Modify: `.htaccess`

**Step 1: Add rewrite rule**

In `.htaccess`, inside the existing `<IfModule mod_rewrite.c>` block, add the podcast rewrite rule **before** the existing block rules:

```apache
    RewriteRule ^podcast/([^/]+)/?$ /podcast.php?slug=$1 [L,QSA]
```

The full block should look like:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^podcast/([^/]+)/?$ /podcast.php?slug=$1 [L,QSA]
    RewriteRule ^(tests|docs|\.git|\.claude)(/|$) - [F,L]
</IfModule>
```

**Step 2: Test the rewrite**

Visit `/podcast/any-slug` in browser. Should reach `podcast.php` (404 page is fine for now — slug lookup comes in Task 5).

**Step 3: Commit**

```bash
git add .htaccess
git commit -m "feat: add mod_rewrite rule for clean podcast URLs"
```

---

### Task 5: Update podcast.php to look up by slug

**Files:**
- Modify: `podcast.php`

**Step 1: Update the lookup logic**

Replace the current lookup (lines 6-22) which uses `?id=`:

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    include __DIR__ . '/partials/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id = ? AND published = 1");
$stmt->execute([$id]);
$podcast = $stmt->fetch();
```

Replace with:

```php
$slug = trim($_GET['slug'] ?? '');
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM podcasts WHERE slug = ? AND published = 1");
    $stmt->execute([$slug]);
    $podcast = $stmt->fetch();
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id = ? AND published = 1");
    $stmt->execute([$id]);
    $podcast = $stmt->fetch();
} else {
    $podcast = false;
}

if (!$podcast) {
    http_response_code(404);
    include __DIR__ . '/partials/404.php';
    exit;
}
```

**Step 2: Update canonical URL and JSON-LD**

Find `$metaCanonical` and JSON-LD URLs. Replace all occurrences of:
```php
rtrim(SITE_URL, '/') . '/podcast.php?id=' . (int)$podcast['id']
```
With:
```php
rtrim(SITE_URL, '/') . '/podcast/' . $podcast['slug']
```

Also update the breadcrumb `item` URL in the BreadcrumbList JSON-LD block.

**Step 3: Verify**

Visit `/podcast/your-slug` in browser. Confirm the correct podcast loads, canonical URL in source shows the clean URL.

**Step 4: Commit**

```bash
git add podcast.php
git commit -m "feat: update podcast.php to look up by slug with id fallback"
```

---

### Task 6: Update podcast-card.php links

**Files:**
- Modify: `partials/podcast-card.php`

**Step 1: Update the link href**

Current (line 2):
```php
<a href="/podcast.php?id=<?= $p['id'] ?>" class="podcast-card">
```

Replace with:
```php
<a href="/podcast/<?= htmlspecialchars($p['slug']) ?>" class="podcast-card">
```

**Step 2: Verify**

Visit homepage, click a podcast card. Confirm URL is `/podcast/the-slug` and podcast page loads correctly.

**Step 3: Commit**

```bash
git add partials/podcast-card.php
git commit -m "feat: update podcast card links to use clean URLs"
```

---

### Task 7: Update sitemap.php

**Files:**
- Modify: `sitemap.php`

**Step 1: Update the query to include slug**

The query already selects `id` and `created_at`. Add `slug`:

```php
$podcasts = $pdo->query(
    "SELECT id, slug, created_at FROM podcasts WHERE published = 1 ORDER BY created_at DESC"
)->fetchAll();
```

**Step 2: Update the `<loc>` URL**

Replace:
```php
<loc><?= $base ?>/podcast.php?id=<?= $p['id'] ?></loc>
```
With:
```php
<loc><?= $base ?>/podcast/<?= htmlspecialchars($p['slug']) ?></loc>
```

**Step 3: Verify**

Visit `/sitemap.php`, confirm podcast URLs use slug format.

**Step 4: Commit**

```bash
git add sitemap.php
git commit -m "feat: update sitemap to use slug URLs"
```

---

### Task 8: Update feed.php

**Files:**
- Modify: `feed.php`

**Step 1: Update the query to include slug**

Add `slug` to the SELECT:

```php
$podcasts = $pdo->query(
    "SELECT id, slug, title, description, mp3_path, duration, created_at
     FROM podcasts WHERE published = 1 ORDER BY created_at DESC"
)->fetchAll();
```

**Step 2: Update item URLs**

Inside the foreach, replace the `$url` assignment:

```php
$url = $base . '/podcast.php?id=' . (int)$p['id'];
```
With:
```php
$url = $base . '/podcast/' . $p['slug'];
```

**Step 3: Verify**

Visit `/feed.php`, confirm `<link>` and `<guid>` use slug URLs.

**Step 4: Commit**

```bash
git add feed.php
git commit -m "feat: update RSS feed to use slug URLs"
```

---

### Task 9: Update mailer.php

**Files:**
- Modify: `mailer.php`
- Modify: `admin.php` (caller)

**Step 1: Update sendDoneNotification signature**

In `mailer.php`, change the function signature from accepting `int $podcastId` to `string $podcastSlug`:

```php
function sendDoneNotification(string $userEmail, string $userName, string $podcastSlug): void {
    $subject = '[plot2pod] Your podcast is ready!';
    $body    = sprintf(
        "Hi %s,\n\nGreat news — your podcast is ready to listen!\n\n%s/podcast/%s\n\nEnjoy,\nThe plot2pod team",
        $userName,
        rtrim(SITE_URL, '/'),
        $podcastSlug
    );

    sendMail($userEmail, $subject, $body);
}
```

**Step 2: Update the caller in admin.php**

Find the `sendDoneNotification` call in `admin.php` (around line 64). The current call is:

```php
sendDoneNotification($req['user_email'], $req['user_name'], $podcastId);
```

We need the slug. Look up the podcast to get the slug before calling:

```php
$pod = $pdo->prepare("SELECT slug FROM podcasts WHERE id = ?");
$pod->execute([$podcastId]);
$podSlug = $pod->fetchColumn();
if ($podSlug) {
    sendDoneNotification($req['user_email'], $req['user_name'], $podSlug);
}
```

**Step 3: Verify**

Mark a request as "done" in admin panel with a podcast linked. Confirm the notification email body contains the slug URL (check mail logs or use a test mail catcher).

**Step 4: Commit**

```bash
git add mailer.php admin.php
git commit -m "feat: update done notification email to use slug URL"
```

---

### Task 10: Update homepage ItemList JSON-LD

**Files:**
- Modify: `index.php`

**Step 1: Update the JSON-LD URL**

In the ItemList JSON-LD block (around line 30), update the item URL:

```php
'url' => rtrim(SITE_URL, '/') . '/podcast.php?id=' . (int)$p['id'],
```
Replace with:
```php
'url' => rtrim(SITE_URL, '/') . '/podcast/' . $p['slug'],
```

**Step 2: Verify**

View homepage source, confirm JSON-LD item URLs use slug format.

**Step 3: Commit**

```bash
git add index.php
git commit -m "feat: update homepage ItemList JSON-LD to use slug URLs"
```

---

## Done

All 10 tasks complete. Every podcast URL now uses `/podcast/slug` format. The `?id=` fallback in `podcast.php` remains for any bookmarked old links.

**Files to upload to server after completion:**
- `db.php`
- `admin.php`
- `mailer.php`
- `podcast.php`
- `partials/podcast-card.php`
- `sitemap.php`
- `feed.php`
- `index.php`
- `.htaccess`

**Run on server before uploading PHP files:**
```bash
php db/migrate_slugs.php
```
