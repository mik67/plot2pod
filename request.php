<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

requireAuth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $type = $_POST['type'] ?? '';

        if (!in_array($type, ['topic', 'links', 'files'], true)) {
            $error = 'Please select a submission type.';

        } elseif ($type === 'files') {
            $filePaths = [];
            $uploaded  = $_FILES['files'] ?? [];

            if (empty($uploaded['name'][0])) {
                $error = 'Please upload at least one file.';
            } else {
                foreach ($uploaded['name'] as $i => $name) {
                    if ($uploaded['error'][$i] !== UPLOAD_ERR_OK) {
                        $error = 'Upload error. Please try again.';
                        break;
                    }
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_EXTS, true)) {
                        $error = "File type .$ext is not allowed. Please use PDF, TXT, or DOCX.";
                        break;
                    }
                    if ($uploaded['size'][$i] > MAX_FILE_SIZE) {
                        $error = htmlspecialchars($name) . ' exceeds the 10 MB limit.';
                        break;
                    }
                    $safeName = uniqid('', true) . '.' . $ext;
                    $dest     = UPLOAD_DIR . $safeName;
                    if (move_uploaded_file($uploaded['tmp_name'][$i], $dest)) {
                        $filePaths[] = $safeName;
                    } else {
                        $error = 'Could not save uploaded file. Please try again.';
                        break;
                    }
                }
            }

            if (!$error) {
                if (empty($filePaths)) {
                    $error = 'File upload failed. Please try again.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO requests (user_id, type, file_paths) VALUES (?, 'files', ?)"
                    );
                    $stmt->execute([currentUser()['id'], json_encode($filePaths)]);
                    $reqId = $pdo->lastInsertId();
                    $req   = $pdo->query("SELECT * FROM requests WHERE id=$reqId")->fetch();
                    sendNewRequestNotification($req, currentUser()['name']);
                    header('Location: /dashboard.php?submitted=1');
                    exit;
                }
            }

        } else {
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                $label = $type === 'topic' ? 'topic' : 'source links';
                $error = "Please fill in the $label field.";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO requests (user_id, type, content) VALUES (?, ?, ?)"
                );
                $stmt->execute([currentUser()['id'], $type, $content]);
                $reqId = $pdo->lastInsertId();
                $req   = $pdo->query("SELECT * FROM requests WHERE id=$reqId")->fetch();
                sendNewRequestNotification($req, currentUser()['name']);
                header('Location: /dashboard.php?submitted=1');
                exit;
            }
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a topic – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main class="request-page">
    <div class="section-inner">
        <h1>Submit a podcast topic</h1>
        <p class="page-sub">We'll turn it into a debate-format podcast, usually within a few days.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/request.php" enctype="multipart/form-data" class="request-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <fieldset class="type-selector">
                <legend>How would you like to submit?</legend>
                <label class="type-option">
                    <input type="radio" name="type" value="topic" checked>
                    <span class="type-label">
                        <span class="type-icon">📝</span>
                        <strong>Give me a topic</strong>
                        <small>Describe what you want to learn about</small>
                    </span>
                </label>
                <label class="type-option">
                    <input type="radio" name="type" value="links">
                    <span class="type-label">
                        <span class="type-icon">🔗</span>
                        <strong>I have source links</strong>
                        <small>Paste URLs to articles or resources</small>
                    </span>
                </label>
                <label class="type-option">
                    <input type="radio" name="type" value="files">
                    <span class="type-label">
                        <span class="type-icon">📁</span>
                        <strong>Upload files</strong>
                        <small>PDF, TXT, or DOCX — max 10 MB each</small>
                    </span>
                </label>
            </fieldset>

            <div class="field-topic">
                <label>
                    What should the podcast be about?
                    <textarea name="content" rows="5"
                        placeholder="e.g. The impact of artificial intelligence on healthcare and medical diagnosis"
                    ><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="field-links" style="display:none">
                <label>
                    Paste your source URLs (one per line):
                    <textarea name="content" rows="5"
                        placeholder="https://example.com/article&#10;https://example.com/research"
                    ><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="field-files" style="display:none">
                <label>
                    Upload your files:
                    <input type="file" name="files[]" multiple
                           accept=".pdf,.txt,.docx">
                </label>
                <p class="field-hint">Accepted: PDF, TXT, DOCX · Max 10 MB per file · Up to 3 files</p>
            </div>

            <button type="submit" class="btn-primary btn-large">Submit request</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="/js/app.js"></script>
</body>
</html>
