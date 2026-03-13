<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

requireAuth();
requireAdmin();

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid form submission.';
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // ── Add new podcast ──────────────────────────────────────────
        if ($action === 'add_podcast') {
            $title    = trim($_POST['title']       ?? '');
            $desc     = trim($_POST['description'] ?? '');
            $mp3path  = trim($_POST['mp3_path']    ?? '');
            $duration = (int)($_POST['duration']   ?? 0);

            if (empty($title) || empty($mp3path)) {
                $message = 'Title and MP3 path are required.';
                $msgType = 'error';
            } else {
                $pdo->prepare(
                    "INSERT INTO podcasts (title, description, mp3_path, duration) VALUES (?, ?, ?, ?)"
                )->execute([$title, $desc, $mp3path, $duration]);
                $message = 'Podcast added successfully.';
            }

        // ── Update request status ────────────────────────────────────
        } elseif ($action === 'update_status') {
            $reqId     = (int)($_POST['request_id'] ?? 0);
            $newStatus = $_POST['status']            ?? '';
            $podcastId = !empty($_POST['podcast_id']) ? (int)$_POST['podcast_id'] : null;

            if (!in_array($newStatus, ['pending', 'processing', 'done'], true)) {
                $message = 'Invalid status.';
                $msgType = 'error';
            } elseif (!$reqId) {
                $message = 'Invalid request ID.';
                $msgType = 'error';
            } else {
                $pdo->prepare(
                    "UPDATE requests SET status = ?, podcast_id = ? WHERE id = ?"
                )->execute([$newStatus, $podcastId, $reqId]);

                // Send done notification — only once
                if ($newStatus === 'done' && $podcastId) {
                    $req = $pdo->query(
                        "SELECT r.*, u.email AS user_email, u.name AS user_name
                         FROM requests r
                         JOIN users u ON u.id = r.user_id
                         WHERE r.id = $reqId"
                    )->fetch();

                    if ($req && empty($req['notified_at'])) {
                        sendDoneNotification($req['user_email'], $req['user_name'], $podcastId);
                        $pdo->prepare("UPDATE requests SET notified_at = NOW() WHERE id = ?")
                            ->execute([$reqId]);
                    }
                }

                $message = 'Request #' . $reqId . ' updated.';
            }

        // ── Toggle podcast visibility ────────────────────────────────
        } elseif ($action === 'toggle_published') {
            $podId = (int)($_POST['podcast_id'] ?? 0);
            if ($podId) {
                $pdo->prepare(
                    "UPDATE podcasts SET published = 1 - published WHERE id = ?"
                )->execute([$podId]);
                $message = 'Podcast visibility toggled.';
            }
        }
    }
}

// Fetch all requests newest first
$requests = $pdo->query(
    "SELECT r.*, u.name AS user_name, u.email AS user_email, p.title AS podcast_title
     FROM requests r
     JOIN  users u    ON u.id = r.user_id
     LEFT JOIN podcasts p ON p.id = r.podcast_id
     ORDER BY r.created_at DESC"
)->fetchAll();

// Fetch all podcasts for dropdown and list
$podcasts = $pdo->query(
    "SELECT * FROM podcasts ORDER BY created_at DESC"
)->fetchAll();

$csrf = generateCsrfToken();

// Stats
$total   = count($requests);
$pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$done    = count(array_filter($requests, fn($r) => $r['status'] === 'done'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="admin">
    <div class="section-inner">
        <h1>Admin panel</h1>

        <?php if ($message): ?>
            <div class="<?= $msgType === 'error' ? 'error-msg' : 'success-msg' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="admin-stats">
            <div class="stat-card"><strong><?= $total ?></strong><span>Total requests</span></div>
            <div class="stat-card"><strong><?= $pending ?></strong><span>Pending</span></div>
            <div class="stat-card"><strong><?= $done ?></strong><span>Done</span></div>
            <div class="stat-card"><strong><?= count($podcasts) ?></strong><span>Podcasts</span></div>
        </div>

        <!-- Add podcast -->
        <section class="admin-section">
            <h2>Add podcast</h2>
            <form method="POST" action="/admin.php" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     value="add_podcast">
                <label>Title <input type="text" name="title" required></label>
                <label>Description <textarea name="description" rows="2"></textarea></label>
                <label>
                    MP3 path <small>(relative to webroot, e.g. /uploads/episode-1.mp3)</small>
                    <input type="text" name="mp3_path" required placeholder="/uploads/episode-1.mp3">
                </label>
                <label>Duration <small>(seconds)</small>
                    <input type="number" name="duration" min="0" value="0">
                </label>
                <button type="submit">Add podcast</button>
            </form>
        </section>

        <!-- Podcasts list -->
        <?php if (!empty($podcasts)): ?>
        <section class="admin-section">
            <h2>Podcasts (<?= count($podcasts) ?>)</h2>
            <div class="admin-podcasts">
                <?php foreach ($podcasts as $p): ?>
                <div class="admin-podcast-row <?= $p['published'] ? '' : 'unpublished' ?>">
                    <div>
                        <strong><?= htmlspecialchars($p['title']) ?></strong>
                        <span class="status-badge <?= $p['published'] ? 'status-done' : 'status-pending' ?>">
                            <?= $p['published'] ? 'published' : 'hidden' ?>
                        </span>
                        <small><?= date('M j, Y', strtotime($p['created_at'])) ?></small>
                    </div>
                    <form method="POST" action="/admin.php" style="display:inline">
                        <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                        <input type="hidden" name="action"      value="toggle_published">
                        <input type="hidden" name="podcast_id"  value="<?= $p['id'] ?>">
                        <button type="submit" class="btn-small">
                            <?= $p['published'] ? 'Hide' : 'Publish' ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Requests list -->
        <section class="admin-section">
            <h2>Requests (<?= $total ?>)</h2>
            <?php if (empty($requests)): ?>
                <p class="empty-state">No requests yet.</p>
            <?php else: ?>
                <div class="admin-requests">
                    <?php foreach ($requests as $r): ?>
                    <div class="admin-request-item status-<?= $r['status'] ?>">
                        <div class="admin-request-header">
                            <span class="request-id">#<?= $r['id'] ?></span>
                            <span class="request-date"><?= date('M j Y, H:i', strtotime($r['created_at'])) ?></span>
                            <span class="request-user">
                                <?= htmlspecialchars($r['user_name']) ?>
                                <small>(<?= htmlspecialchars($r['user_email']) ?>)</small>
                            </span>
                            <span class="request-type-badge"><?= $r['type'] ?></span>
                            <span class="status-badge status-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                            <?php if ($r['notified_at']): ?>
                                <span class="notified-badge" title="Notified <?= date('M j H:i', strtotime($r['notified_at'])) ?>">✉ sent</span>
                            <?php endif; ?>
                        </div>

                        <div class="admin-request-content">
                            <?php if ($r['type'] === 'files'): ?>
                                <?php $files = json_decode($r['file_paths'], true) ?? []; ?>
                                <em>Files: <?= implode(', ', array_map('htmlspecialchars', $files)) ?></em>
                            <?php else: ?>
                                <?= htmlspecialchars($r['content']) ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="/admin.php" class="admin-request-form">
                            <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                            <input type="hidden" name="action"      value="update_status">
                            <input type="hidden" name="request_id"  value="<?= $r['id'] ?>">

                            <select name="status">
                                <?php foreach (['pending', 'processing', 'done'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>>
                                        <?= $s ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="podcast_id">
                                <option value="">— no podcast —</option>
                                <?php foreach ($podcasts as $p): ?>
                                    <option value="<?= $p['id'] ?>"
                                        <?= $r['podcast_id'] == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit">Update</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
