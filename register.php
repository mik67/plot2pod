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
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password']  ?? '';
        $pass2 = $_POST['password2'] ?? '';

        if (empty($name) || empty($email) || empty($pass)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass !== $pass2) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)"
                );
                $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);

                $userId = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin']   = 0;

                header('Location: /dashboard.php');
                exit;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $error = 'This email is already registered. <a href="/login.php">Log in instead?</a>';
                } else {
                    error_log('Registration error: ' . $e->getMessage());
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Register – plot2pod</title>
    <?php $metaNoindex = true; include __DIR__ . '/partials/meta.php'; ?>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <h1>Create account</h1>
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="/register.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label>
                Name
                <input type="text" name="name"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required autocomplete="name">
            </label>
            <label>
                Email
                <input type="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </label>
            <label>
                Password
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>
            <label>
                Confirm password
                <input type="password" name="password2" required autocomplete="new-password">
            </label>
            <button type="submit" class="btn-primary">Create account</button>
        </form>
        <p class="auth-footer">Already have an account? <a href="/login.php">Log in</a></p>
    </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
