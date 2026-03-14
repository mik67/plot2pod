# Admin Enhancements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add edit podcast, reject request with email, soft-delete request, and soft-delete podcast to the admin panel.

**Architecture:** All changes are in `admin.php` (new POST actions + UI sections) with DB migrations for new columns/enum values. Soft deletes use a `deleted` flag on podcasts and a `deleted` status on requests. All queries that serve public content are updated to exclude deleted records.

**Tech Stack:** PHP, MySQL, Apache

---

### Task 1: DB migration — add deleted/rejected fields

**Files:**
- Modify: `db/schema.sql`
- Create: `db/migrate_admin_enhancements.php`

**Step 1: Update `db/schema.sql`**

In the `podcasts` table, add `deleted` column after `published`:
```sql
    deleted     TINYINT(1)   NOT NULL DEFAULT 0
```

In the `requests` table, extend the status ENUM and add `reject_reason`:
```sql
    status       ENUM('pending','processing','done','rejected','deleted') NOT NULL DEFAULT 'pending',
    reject_reason TEXT NULL DEFAULT NULL,
```
Add `reject_reason` after `notified_at`.

**Step 2: Create `db/migrate_admin_enhancements.php`**

```php
<?php
/**
 * Migration: add deleted flag to podcasts, add rejected/deleted status and reject_reason to requests.
 * Run once on server: php db/migrate_admin_enhancements.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Add deleted column to podcasts
$pdo->exec("
    ALTER TABLE podcasts
    ADD COLUMN IF NOT EXISTS deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER published
");
echo "podcasts.deleted column added (or already exists).\n";

// Extend requests status enum
$pdo->exec("
    ALTER TABLE requests
    MODIFY COLUMN status ENUM('pending','processing','done','rejected','deleted') NOT NULL DEFAULT 'pending'
");
echo "requests.status enum extended.\n";

// Add reject_reason column to requests
$pdo->exec("
    ALTER TABLE requests
    ADD COLUMN IF NOT EXISTS reject_reason TEXT NULL DEFAULT NULL AFTER notified_at
");
echo "requests.reject_reason column added (or already exists).\n";

echo "\nMigration complete.\n";
```

**Step 3: Commit**
```bash
git add db/schema.sql db/migrate_admin_enhancements.php
git commit -m "feat: add migration for podcast deleted flag and request rejected/deleted status"
```

---

### Task 2: Add sendRejectNotification() to mailer.php

**Files:**
- Modify: `mailer.php`

**Step 1: Add function**

Append to `mailer.php`:

```php
function sendRejectNotification(string $userEmail, string $userName, string $reason): void {
    $subject = '[plot2pod] Your podcast request was not accepted';
    $body    = sprintf(
        "Hi %s,\n\nUnfortunately your podcast request was not accepted.\n\nReason: %s\n\nFeel free to submit a new request at %s\n\nThe plot2pod team",
        $userName,
        $reason,
        rtrim(SITE_URL, '/')
    );

    sendMail($userEmail, $subject, $body);
}
```

**Step 2: Commit**
```bash
git add mailer.php
git commit -m "feat: add sendRejectNotification() to mailer"
```

---

### Task 3: Add admin actions — reject, delete request, delete podcast, edit podcast

**Files:**
- Modify: `admin.php`

**Step 1: Read `admin.php`**

Find the POST handler block (the large `if ($_SERVER['REQUEST_METHOD'] === 'POST')` block). Add four new actions inside it, after the existing `toggle_published` action.

**Step 2: Add `reject_request` action**

```php
// ── Reject request ───────────────────────────────────────────
} elseif ($action === 'reject_request') {
    $reqId  = (int)($_POST['request_id'] ?? 0);
    $reason = trim($_POST['reject_reason'] ?? '');

    if (!$reqId || empty($reason)) {
        $message = 'Request ID and reason are required.';
        $msgType = 'error';
    } else {
        $req = $pdo->prepare(
            "SELECT r.*, u.email AS user_email, u.name AS user_name
             FROM requests r JOIN users u ON u.id = r.user_id
             WHERE r.id = ?"
        );
        $req->execute([$reqId]);
        $reqRow = $req->fetch();

        $pdo->prepare(
            "UPDATE requests SET status = 'rejected', reject_reason = ? WHERE id = ?"
        )->execute([$reason, $reqId]);

        if ($reqRow) {
            sendRejectNotification($reqRow['user_email'], $reqRow['user_name'], $reason);
        }

        $message = 'Request #' . $reqId . ' rejected.';
    }

// ── Soft delete request ──────────────────────────────────────
} elseif ($action === 'delete_request') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    if ($reqId) {
        $pdo->prepare(
            "UPDATE requests SET status = 'deleted' WHERE id = ?"
        )->execute([$reqId]);
        $message = 'Request #' . $reqId . ' deleted.';
    }

// ── Soft delete podcast ──────────────────────────────────────
} elseif ($action === 'delete_podcast') {
    $podId = (int)($_POST['podcast_id'] ?? 0);
    if ($podId) {
        $pdo->prepare(
            "UPDATE podcasts SET deleted = 1 WHERE id = ?"
        )->execute([$podId]);
        $message = 'Podcast deleted.';
    }

// ── Edit podcast ─────────────────────────────────────────────
} elseif ($action === 'edit_podcast') {
    $podId    = (int)($_POST['podcast_id']   ?? 0);
    $title    = trim($_POST['title']         ?? '');
    $desc     = trim($_POST['description']   ?? '');
    $mp3path  = trim($_POST['mp3_path']      ?? '');
    $duration = (int)($_POST['duration']     ?? 0);

    if (!$podId || empty($title) || empty($mp3path)) {
        $message = 'Podcast ID, title and MP3 path are required.';
        $msgType = 'error';
    } else {
        $pdo->prepare(
            "UPDATE podcasts SET title = ?, description = ?, mp3_path = ?, duration = ? WHERE id = ?"
        )->execute([$title, $desc, $mp3path, $duration, $podId]);
        $message = 'Podcast updated.';
    }
```

Note: slug is intentionally NOT updated on edit.

**Step 3: Update the podcasts query to exclude deleted**

Find the query that fetches all podcasts (around line 96):
```php
$podcasts = $pdo->query(
    "SELECT * FROM podcasts ORDER BY created_at DESC"
)->fetchAll();
```
Replace with:
```php
$podcasts = $pdo->query(
    "SELECT * FROM podcasts WHERE deleted = 0 ORDER BY created_at DESC"
)->fetchAll();
```

**Step 4: Update the requests query to exclude deleted**

Find the query that fetches all requests (around line 87):
```php
$requests = $pdo->query(
    "SELECT r.*, u.name AS user_name, u.email AS user_email, p.title AS podcast_title
     FROM requests r
     JOIN  users u    ON u.id = r.user_id
     LEFT JOIN podcasts p ON p.id = r.podcast_id
     ORDER BY r.created_at DESC"
)->fetchAll();
```
Replace with:
```php
$requests = $pdo->query(
    "SELECT r.*, u.name AS user_name, u.email AS user_email, p.title AS podcast_title, p.slug AS podcast_slug
     FROM requests r
     JOIN  users u    ON u.id = r.user_id
     LEFT JOIN podcasts p ON p.id = r.podcast_id
     WHERE r.status != 'deleted'
     ORDER BY r.created_at DESC"
)->fetchAll();
```

**Step 5: Commit**
```bash
git add admin.php
git commit -m "feat: add reject, delete request, delete podcast, edit podcast actions"
```

---

### Task 4: Add admin UI — edit podcast form

**Files:**
- Modify: `admin.php` (HTML section)

**Step 1: Replace the podcasts list section**

Find the podcasts list section (around line 157). Currently each podcast row shows title + publish toggle. Add an expand/inline edit form and a delete button.

Replace the inner `foreach` loop body:

```php
<div class="admin-podcast-row <?= $p['published'] ? '' : 'unpublished' ?>">
    <div class="admin-podcast-info">
        <strong><?= htmlspecialchars($p['title']) ?></strong>
        <span class="status-badge <?= $p['published'] ? 'status-done' : 'status-pending' ?>">
            <?= $p['published'] ? 'published' : 'hidden' ?>
        </span>
        <small><?= date('M j, Y', strtotime($p['created_at'])) ?></small>
        <small class="text-muted">/podcast/<?= htmlspecialchars($p['slug']) ?></small>
    </div>
    <div class="admin-podcast-actions">
        <!-- Toggle published -->
        <form method="POST" action="/admin.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"     value="toggle_published">
            <input type="hidden" name="podcast_id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn-small">
                <?= $p['published'] ? 'Hide' : 'Publish' ?>
            </button>
        </form>
        <!-- Edit toggle -->
        <button type="button" class="btn-small"
            onclick="document.getElementById('edit-<?= $p['id'] ?>').classList.toggle('hidden')">
            Edit
        </button>
        <!-- Delete -->
        <form method="POST" action="/admin.php" style="display:inline"
              onsubmit="return confirm('Delete this podcast? This cannot be undone.')">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"     value="delete_podcast">
            <input type="hidden" name="podcast_id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn-small btn-danger">Delete</button>
        </form>
    </div>
    <!-- Inline edit form -->
    <form method="POST" action="/admin.php" class="admin-form edit-form hidden" id="edit-<?= $p['id'] ?>">
        <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
        <input type="hidden" name="action"      value="edit_podcast">
        <input type="hidden" name="podcast_id"  value="<?= $p['id'] ?>">
        <label>Title
            <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>" required>
        </label>
        <label>Description
            <textarea name="description" rows="2"><?= htmlspecialchars($p['description']) ?></textarea>
        </label>
        <label>MP3 path
            <input type="text" name="mp3_path" value="<?= htmlspecialchars($p['mp3_path']) ?>" required>
        </label>
        <label>Duration <small>(seconds)</small>
            <input type="number" name="duration" value="<?= $p['duration'] ?>" min="0">
        </label>
        <button type="submit">Save changes</button>
        <button type="button"
            onclick="document.getElementById('edit-<?= $p['id'] ?>').classList.add('hidden')">
            Cancel
        </button>
    </form>
</div>
```

**Step 2: Commit**
```bash
git add admin.php
git commit -m "feat: add inline edit form and delete button to podcast list"
```

---

### Task 5: Add admin UI — reject and delete buttons on requests

**Files:**
- Modify: `admin.php` (HTML section)

**Step 1: Add reject form to each request row**

Find the request item form (around line 216). After the existing `<form>` with status update, add a reject section:

```php
<?php if (!in_array($r['status'], ['rejected', 'done', 'deleted'])): ?>
<form method="POST" action="/admin.php" class="admin-reject-form">
    <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
    <input type="hidden" name="action"      value="reject_request">
    <input type="hidden" name="request_id"  value="<?= $r['id'] ?>">
    <input type="text" name="reject_reason" placeholder="Rejection reason…" required>
    <button type="submit" class="btn-small btn-danger">Reject</button>
</form>
<?php endif; ?>
```

**Step 2: Add delete button to each request row**

After the reject form:

```php
<?php if ($r['status'] !== 'deleted'): ?>
<form method="POST" action="/admin.php" style="display:inline"
      onsubmit="return confirm('Remove this request?')">
    <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
    <input type="hidden" name="action"      value="delete_request">
    <input type="hidden" name="request_id"  value="<?= $r['id'] ?>">
    <button type="submit" class="btn-small btn-danger">Remove</button>
</form>
<?php endif; ?>
```

**Step 3: Show rejection reason in request content area**

In the `admin-request-content` div, after the existing content display, add:

```php
<?php if ($r['status'] === 'rejected' && $r['reject_reason']): ?>
    <em class="reject-reason">Rejected: <?= htmlspecialchars($r['reject_reason']) ?></em>
<?php endif; ?>
```

**Step 4: Commit**
```bash
git add admin.php
git commit -m "feat: add reject and delete buttons to request list"
```

---

### Task 6: Update public queries to exclude deleted podcasts

**Files:**
- Modify: `podcast.php`
- Modify: `sitemap.php`
- Modify: `feed.php`
- Modify: `index.php`
- Modify: `partials/podcast-card.php` (no change needed — uses `$p` from parent query)

**Step 1: `podcast.php`** — slug lookup already has `AND published = 1`, add `AND deleted = 0`:

```php
$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE slug = ? AND published = 1 AND deleted = 0");
```
And the id fallback:
```php
$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id = ? AND published = 1 AND deleted = 0");
```

**Step 2: `sitemap.php`** — add `AND deleted = 0`:
```php
"SELECT id, slug, created_at FROM podcasts WHERE published = 1 AND deleted = 0 ORDER BY created_at DESC"
```

**Step 3: `feed.php`** — add `AND deleted = 0`:
```php
"SELECT id, slug, title, description, mp3_path, duration, created_at
 FROM podcasts WHERE published = 1 AND deleted = 0 ORDER BY created_at DESC"
```

**Step 4: `index.php`** — add `AND deleted = 0`:
```php
"SELECT * FROM podcasts WHERE published = 1 AND deleted = 0 ORDER BY created_at DESC"
```

**Step 5: Commit**
```bash
git add podcast.php sitemap.php feed.php index.php
git commit -m "feat: exclude deleted podcasts from all public queries"
```

---

### Task 7: Add CSS for new admin UI elements

**Files:**
- Modify: `css/style.css`

**Step 1: Add styles**

Find the admin section in `css/style.css` (search for `.admin-section`). Add these styles:

```css
.btn-danger {
  background: var(--red);
  color: #fff;
  border-color: var(--red);
}
.btn-danger:hover { background: #dc2626; border-color: #dc2626; }

.edit-form { margin-top: 1rem; }
.hidden    { display: none !important; }

.admin-podcast-info  { flex: 1; }
.admin-podcast-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }

.admin-reject-form {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
  align-items: center;
}
.admin-reject-form input[type="text"] {
  flex: 1;
  padding: 0.35rem 0.6rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 0.875rem;
}

.reject-reason {
  display: block;
  margin-top: 0.35rem;
  color: var(--red);
  font-size: 0.85rem;
}

.text-muted { color: var(--text-muted); font-size: 0.8rem; }
```

**Step 2: Commit**
```bash
git add css/style.css
git commit -m "feat: add admin UI styles for edit, delete, reject"
```

---

## Done

All 7 tasks complete.

**Run on server before uploading PHP files:**
```bash
php db/migrate_admin_enhancements.php
```

**Files to upload:**
- `db/schema.sql`
- `db/migrate_admin_enhancements.php`
- `mailer.php`
- `admin.php`
- `podcast.php`
- `sitemap.php`
- `feed.php`
- `index.php`
- `css/style.css`
