<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAuth();
$user = currentUser();

$stmt = $pdo->prepare(
    "SELECT r.*, p.title AS podcast_title
     FROM requests r
     LEFT JOIN podcasts p ON p.id = r.podcast_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();

$submitted = !empty($_GET['submitted']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My requests – plot2pod</title>
    <?php $metaNoindex = true; include __DIR__ . '/partials/meta.php'; ?>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="dashboard">
    <div class="section-inner">
        <div class="dashboard-header">
            <h1>My requests</h1>
            <a href="/request.php" class="btn-primary">+ Submit new topic</a>
        </div>

        <?php if ($submitted): ?>
            <div class="success-msg">
                Your request was submitted! We'll notify you by email when your podcast is ready.
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <p>You haven't submitted any requests yet.</p>
                <a href="/request.php" class="btn-primary">Submit your first topic</a>
            </div>
        <?php else: ?>
            <div class="requests-list">
                <?php foreach ($requests as $r): ?>
                <div class="request-row">
                    <div class="request-row-meta">
                        <span class="request-date"><?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                        <span class="request-type"><?= htmlspecialchars($r['type']) ?></span>
                        <span class="status-badge status-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                    </div>
                    <div class="request-row-content">
                        <?php if ($r['type'] === 'files'): ?>
                            <em class="text-muted">Files uploaded</em>
                        <?php else: ?>
                            <?= htmlspecialchars(mb_substr($r['content'], 0, 120)) ?>
                            <?= mb_strlen($r['content']) > 120 ? '…' : '' ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($r['podcast_id']): ?>
                    <div class="request-row-podcast">
                        🎙 <a href="/podcast.php?id=<?= $r['podcast_id'] ?>">
                            <?= htmlspecialchars($r['podcast_title']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
