<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin']   = $user['is_admin'];

            $next = filter_var($_GET['next'] ?? '', FILTER_SANITIZE_URL);
            $next = $next && str_starts_with($next, '/') ? $next : '/dashboard.php';
            header('Location: ' . $next);
            exit;
        } else {
            // Same message for wrong email or wrong password (security best practice)
            $error = 'Invalid email or password.';
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
    <title>Log in – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <h1>Log in</h1>
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/login.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label>
                Email
                <input type="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </label>
            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn-primary">Log in</button>
        </form>
        <p class="auth-footer">No account yet? <a href="/register.php">Register free</a></p>
    </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
