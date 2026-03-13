# plot2pod MVP Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a PHP/MariaDB web platform where users submit podcast topics and track their request status, with Miloš manually processing and publishing podcasts.

**Architecture:** Static-feeling HTML/CSS/JS frontend backed by vanilla PHP and MariaDB. No framework. Shared cPanel hosting. PHPMailer for email notifications. Files stored on server filesystem.

**Tech Stack:** PHP 8+, MariaDB, PDO, PHPMailer (manual install), HTML5 audio player, CSS/JS via Frontend Design plugin.

---

## Task 1: Project scaffolding

**Files:**
- Create: `config.example.php`
- Create: `config.php` (gitignored)
- Create: `.gitignore`
- Create: `uploads/.gitkeep`
- Create: `tests/.gitkeep`

**Step 1: Create .gitignore**

```
config.php
uploads/
vendor/
*.log
.DS_Store
```

**Step 2: Create config.example.php**

```php
<?php
// Copy this file to config.php and fill in your values
define('DB_HOST', 'localhost');
define('DB_NAME', 'plot2pod');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

define('ADMIN_EMAIL', 'milos@yourdomain.com');
define('FROM_EMAIL',  'noreply@plot2pod.com');
define('FROM_NAME',   'plot2pod');
define('SITE_URL',    'https://plot2pod.com');

define('UPLOAD_DIR',  __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTS', ['pdf', 'txt', 'docx']);

define('SESSION_NAME', 'plot2pod_session');
```

**Step 3: Create config.php** (copy from example, fill in real values for your environment)

**Step 4: Create directory structure**

```
mkdir -p uploads css js lib tests
```

**Step 5: Commit**

```bash
git add .gitignore config.example.php uploads/.gitkeep tests/.gitkeep
git commit -m "chore: project scaffolding and config template"
```

---

## Task 2: Database schema

**Files:**
- Create: `db/schema.sql`
- Create: `db/seed.sql`

**Step 1: Create db/schema.sql**

```sql
CREATE DATABASE IF NOT EXISTS plot2pod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plot2pod;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    is_admin      TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT NOW()
) ENGINE=InnoDB;

CREATE TABLE podcasts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT         NOT NULL,
    mp3_path    VARCHAR(500) NOT NULL,
    duration    INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT NOW(),
    published   TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    type         ENUM('topic','links','files') NOT NULL,
    content      TEXT,
    file_paths   TEXT,
    status       ENUM('pending','processing','done') NOT NULL DEFAULT 'pending',
    podcast_id   INT          NULL DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT NOW(),
    notified_at  DATETIME     NULL DEFAULT NULL,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (podcast_id) REFERENCES podcasts(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

**Step 2: Create db/seed.sql** (first admin user — change email/password before running)

```sql
USE plot2pod;

-- Insert admin user (password: changeme123 — update immediately after first login)
INSERT INTO users (name, email, password_hash, is_admin)
VALUES ('Milos', 'milos@plot2pod.com',
        '$2y$12$examplehashreplacethis', 1);
-- IMPORTANT: generate real hash with: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
```

**Step 3: Run schema against MariaDB**

```bash
mysql -u root -p < db/schema.sql
```

Expected: no errors, database and tables created.

**Step 4: Verify tables exist**

```bash
mysql -u root -p plot2pod -e "SHOW TABLES;"
```

Expected output:
```
+--------------------+
| Tables_in_plot2pod |
+--------------------+
| podcasts           |
| requests           |
| users              |
+--------------------+
```

**Step 5: Commit**

```bash
git add db/
git commit -m "feat: database schema with users, podcasts, requests tables"
```

---

## Task 3: Database connection (db.php)

**Files:**
- Create: `db.php`
- Create: `tests/test_db.php`

**Step 1: Write the test first**

Create `tests/test_db.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Test 1: connection returns PDO instance
assert($pdo instanceof PDO, 'FAIL: $pdo is not a PDO instance');
echo "PASS: DB connection established\n";

// Test 2: can query users table
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
assert($stmt !== false, 'FAIL: could not query users table');
echo "PASS: users table accessible\n";

echo "\nAll DB tests passed.\n";
```

**Step 2: Run test to verify it fails**

```bash
php tests/test_db.php
```

Expected: Fatal error — `db.php` not found.

**Step 3: Create db.php**

```php
<?php
require_once __DIR__ . '/config.php';

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection error. Please try again later.');
}
```

**Step 4: Run test to verify it passes**

```bash
php tests/test_db.php
```

Expected:
```
PASS: DB connection established
PASS: users table accessible

All DB tests passed.
```

**Step 5: Commit**

```bash
git add db.php tests/test_db.php
git commit -m "feat: PDO database connection with error handling"
```

---

## Task 4: Auth helpers (auth.php)

**Files:**
- Create: `auth.php`
- Create: `tests/test_auth.php`

**Step 1: Write the test**

Create `tests/test_auth.php`:

```php
<?php
session_name('plot2pod_test');
session_start();

require_once __DIR__ . '/../auth.php';

// Test 1: isLoggedIn returns false when no session
$_SESSION = [];
assert(!isLoggedIn(), 'FAIL: isLoggedIn should be false with empty session');
echo "PASS: isLoggedIn returns false when not logged in\n";

// Test 2: isLoggedIn returns true when session has user_id
$_SESSION['user_id'] = 1;
assert(isLoggedIn(), 'FAIL: isLoggedIn should be true with user_id in session');
echo "PASS: isLoggedIn returns true when logged in\n";

// Test 3: isAdmin returns false when not admin
$_SESSION['is_admin'] = 0;
assert(!isAdmin(), 'FAIL: isAdmin should return false');
echo "PASS: isAdmin returns false for regular user\n";

// Test 4: CSRF token generation returns non-empty string
$_SESSION = [];
$token = generateCsrfToken();
assert(!empty($token), 'FAIL: CSRF token should not be empty');
assert(strlen($token) === 64, 'FAIL: CSRF token should be 64 chars');
echo "PASS: CSRF token generated correctly\n";

// Test 5: CSRF token validation
assert(validateCsrfToken($token), 'FAIL: CSRF token should validate');
assert(!validateCsrfToken('badtoken'), 'FAIL: bad token should not validate');
echo "PASS: CSRF token validation works\n";

echo "\nAll auth tests passed.\n";
```

**Step 2: Run test — expect failure**

```bash
php tests/test_auth.php
```

Expected: Fatal error — `auth.php` not found.

**Step 3: Create auth.php**

```php
<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['is_admin']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        die('Access denied.');
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'],
        'email'    => $_SESSION['user_email'],
        'is_admin' => $_SESSION['is_admin'],
    ];
}
```

**Step 4: Run test — expect pass**

```bash
php tests/test_auth.php
```

Expected:
```
PASS: isLoggedIn returns false when not logged in
PASS: isLoggedIn returns true when logged in
PASS: isAdmin returns false for regular user
PASS: CSRF token generated correctly
PASS: CSRF token validation works

All auth tests passed.
```

**Step 5: Commit**

```bash
git add auth.php tests/test_auth.php
git commit -m "feat: auth helpers with session management and CSRF protection"
```

---

## Task 5: PHPMailer setup + email helpers

**Files:**
- Create: `lib/PHPMailer/` (download PHPMailer)
- Create: `mailer.php`
- Create: `tests/test_mailer.php`

**Step 1: Download PHPMailer (no Composer needed)**

```bash
# Download the three required files manually
mkdir -p lib/PHPMailer
curl -o lib/PHPMailer/PHPMailer.php \
  https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php
curl -o lib/PHPMailer/SMTP.php \
  https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php
curl -o lib/PHPMailer/Exception.php \
  https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php
```

Or download from https://github.com/PHPMailer/PHPMailer/releases and copy `src/` files into `lib/PHPMailer/`.

**Step 2: Write test (dry run — no real email sent)**

Create `tests/test_mailer.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';

// Test: createMailer returns configured PHPMailer instance
$mail = createMailer();
assert($mail instanceof PHPMailer\PHPMailer\PHPMailer, 'FAIL: should return PHPMailer instance');
echo "PASS: createMailer returns PHPMailer instance\n";

echo "\nAll mailer tests passed.\n";
```

**Step 3: Run test — expect failure**

```bash
php tests/test_mailer.php
```

Expected: Fatal error — `mailer.php` not found.

**Step 4: Create mailer.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
    $mail->SMTPAuth   = defined('SMTP_USER');
    $mail->Username   = defined('SMTP_USER')  ? SMTP_USER  : '';
    $mail->Password   = defined('SMTP_PASS')  ? SMTP_PASS  : '';
    $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = defined('SMTP_PORT')  ? SMTP_PORT  : 587;
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->isHTML(false);
    return $mail;
}

function sendNewRequestNotification(array $request, string $userName): void {
    try {
        $mail = createMailer();
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = '[plot2pod] New podcast request #' . $request['id'];
        $mail->Body    = sprintf(
            "New request from %s\n\nType: %s\n\nContent:\n%s\n\nReview: %s/admin.php",
            $userName,
            $request['type'],
            $request['content'] ?? '(file upload)',
            SITE_URL
        );
        $mail->send();
    } catch (Exception $e) {
        error_log('Email failed (new request): ' . $e->getMessage());
    }
}

function sendDoneNotification(string $userEmail, string $userName, int $podcastId): void {
    try {
        $mail = createMailer();
        $mail->addAddress($userEmail, $userName);
        $mail->Subject = '[plot2pod] Your podcast is ready!';
        $mail->Body    = sprintf(
            "Hi %s,\n\nYour podcast is ready to listen!\n\n%s/podcast.php?id=%d\n\nEnjoy,\nThe plot2pod team",
            $userName,
            SITE_URL,
            $podcastId
        );
        $mail->send();
    } catch (Exception $e) {
        error_log('Email failed (done notification): ' . $e->getMessage());
    }
}
```

**Step 5: Add SMTP constants to config.php** (add after existing defines):

```php
define('SMTP_HOST',   'mail.yourdomain.com');
define('SMTP_USER',   'noreply@plot2pod.com');
define('SMTP_PASS',   'your_smtp_password');
define('SMTP_SECURE', 'tls');
define('SMTP_PORT',   587);
```

**Step 6: Run test — expect pass**

```bash
php tests/test_mailer.php
```

Expected:
```
PASS: createMailer returns PHPMailer instance

All mailer tests passed.
```

**Step 7: Commit**

```bash
git add lib/PHPMailer/ mailer.php tests/test_mailer.php
git commit -m "feat: PHPMailer setup with admin and user notification functions"
```

---

## Task 6: Registration (register.php)

**Files:**
- Create: `register.php`
- Create: `tests/test_register.php`

**Step 1: Write test**

Create `tests/test_register.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Helper: clean up test users
function cleanTestUser(PDO $pdo, string $email): void {
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
}

$testEmail = 'testuser_' . time() . '@example.com';

// Test 1: can insert a user with hashed password
$hash = password_hash('testpass123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)"
);
$stmt->execute(['Test User', $testEmail, $hash]);
$id = $pdo->lastInsertId();
assert($id > 0, 'FAIL: user insert failed');
echo "PASS: user inserted with id=$id\n";

// Test 2: email uniqueness constraint works
try {
    $stmt->execute(['Duplicate', $testEmail, $hash]);
    assert(false, 'FAIL: duplicate email should throw');
} catch (PDOException $e) {
    assert(str_contains($e->getMessage(), 'Duplicate'), 'FAIL: wrong exception');
    echo "PASS: duplicate email rejected by DB\n";
}

// Test 3: password hash validates correctly
$row = $pdo->query("SELECT password_hash FROM users WHERE id=$id")->fetch();
assert(password_verify('testpass123', $row['password_hash']), 'FAIL: password verify failed');
echo "PASS: password hash/verify works\n";

cleanTestUser($pdo, $testEmail);
echo "\nAll registration tests passed.\n";
```

**Step 2: Run test — verify DB layer works**

```bash
php tests/test_register.php
```

Expected: All 3 PASS.

**Step 3: Create register.php**

```php
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
        $pass  = $_POST['password']   ?? '';
        $pass2 = $_POST['password2']  ?? '';

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
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin']   = 0;

                header('Location: /dashboard.php');
                exit;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $error = 'This email is already registered. Please log in.';
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
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <main class="auth-page">
        <div class="auth-card">
            <h1>Create account</h1>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="/register.php">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <label>Name <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></label>
                <label>Email <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></label>
                <label>Password <input type="password" name="password" required minlength="8"></label>
                <label>Confirm password <input type="password" name="password2" required></label>
                <button type="submit">Create account</button>
            </form>
            <p>Already have an account? <a href="/login.php">Log in</a></p>
        </div>
    </main>
</body>
</html>
```

**Step 4: Create partials/header.php**

```php
<?php require_once __DIR__ . '/../auth.php'; ?>
<header class="site-header">
    <a href="/" class="logo">plot2pod</a>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="/dashboard.php">My requests</a>
            <a href="/request.php">Submit topic</a>
            <a href="/logout.php">Log out</a>
        <?php else: ?>
            <a href="/login.php">Log in</a>
            <a href="/register.php" class="btn-primary">Register</a>
        <?php endif; ?>
    </nav>
</header>
```

**Step 5: Verify in browser**

- Open `/register.php`
- Submit empty form → validation errors shown
- Submit valid data → redirected to `/dashboard.php`
- Try same email again → "already registered" error

**Step 6: Commit**

```bash
git add register.php partials/header.php
git commit -m "feat: user registration with CSRF protection and validation"
```

---

## Task 7: Login + logout

**Files:**
- Create: `login.php`
- Create: `logout.php`

**Step 1: Write login test**

Add to `tests/test_register.php` (or create `tests/test_login.php`):

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Insert test user
$email = 'logintest_' . time() . '@example.com';
$hash  = password_hash('correctpass', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?,?,?)")
    ->execute(['Login Tester', $email, $hash]);

// Test 1: correct credentials → user found and password matches
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();
assert($user !== false, 'FAIL: user not found');
assert(password_verify('correctpass', $user['password_hash']), 'FAIL: password mismatch');
echo "PASS: correct credentials verified\n";

// Test 2: wrong password → verify returns false
assert(!password_verify('wrongpass', $user['password_hash']), 'FAIL: wrong pass should fail');
echo "PASS: wrong password rejected\n";

// Clean up
$pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
echo "\nAll login tests passed.\n";
```

**Step 2: Run test**

```bash
php tests/test_login.php
```

Expected: Both PASS.

**Step 3: Create login.php**

```php
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
        $error = 'Invalid form submission.';
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

            $next = $_GET['next'] ?? '/dashboard.php';
            header('Location: ' . $next);
            exit;
        } else {
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
            <form method="POST" action="/login.php">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <label>Email <input type="email" name="email" required autocomplete="email"></label>
                <label>Password <input type="password" name="password" required></label>
                <button type="submit">Log in</button>
            </form>
            <p>No account? <a href="/register.php">Register</a></p>
        </div>
    </main>
</body>
</html>
```

**Step 4: Create logout.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

session_unset();
session_destroy();

header('Location: /');
exit;
```

**Step 5: Verify in browser**

- Login with wrong password → error shown
- Login with correct credentials → redirect to dashboard
- Visit `/logout.php` → session cleared, redirected to homepage

**Step 6: Commit**

```bash
git add login.php logout.php tests/test_login.php
git commit -m "feat: login and logout with session management"
```

---

## Task 8: Homepage – podcast showcase (index.php)

**Files:**
- Create: `index.php`
- Create: `partials/footer.php`
- Create: `partials/podcast-card.php`

**Step 1: Write test for podcast query**

Create `tests/test_podcasts.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Insert test podcast
$stmt = $pdo->prepare(
    "INSERT INTO podcasts (title, description, mp3_path, duration) VALUES (?,?,?,?)"
);
$stmt->execute(['Test Podcast', 'A test description', '/uploads/test.mp3', 1800]);
$id = $pdo->lastInsertId();

// Test: published podcasts are returned in correct order
$rows = $pdo->query(
    "SELECT * FROM podcasts WHERE published=1 ORDER BY created_at DESC"
)->fetchAll();

assert(count($rows) >= 1, 'FAIL: should have at least 1 podcast');
assert($rows[0]['id'] == $id, 'FAIL: newest podcast should be first');
echo "PASS: podcast listing query works\n";

// Clean up
$pdo->prepare("DELETE FROM podcasts WHERE id=?")->execute([$id]);
echo "\nAll podcast tests passed.\n";
```

**Step 2: Run test**

```bash
php tests/test_podcasts.php
```

Expected: PASS.

**Step 3: Create index.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$podcasts = $pdo->query(
    "SELECT * FROM podcasts WHERE published=1 ORDER BY created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>plot2pod – AI-generated podcasts</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <section class="hero">
        <h1>Turn any topic into a podcast</h1>
        <p>Submit a topic, upload your materials, or share your sources.<br>We'll create a debate-format podcast for you and everyone.</p>
        <?php if (isLoggedIn()): ?>
            <a href="/request.php" class="btn-primary">Submit a topic</a>
        <?php else: ?>
            <a href="/register.php" class="btn-primary">Get started free</a>
        <?php endif; ?>
    </section>

    <section class="podcasts">
        <h2>Latest podcasts</h2>
        <?php if (empty($podcasts)): ?>
            <p class="empty-state">Podcasts coming soon — be the first to submit a topic!</p>
        <?php else: ?>
            <div class="podcast-grid">
                <?php foreach ($podcasts as $p): ?>
                    <?php include __DIR__ . '/partials/podcast-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="/js/app.js"></script>
</body>
</html>
```

**Step 4: Create partials/podcast-card.php**

```php
<!-- $p must be in scope -->
<article class="podcast-card">
    <div class="podcast-info">
        <h3><a href="/podcast.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></h3>
        <p><?= htmlspecialchars($p['description']) ?></p>
        <span class="duration"><?= gmdate('i:s', $p['duration']) ?></span>
    </div>
    <audio controls preload="none">
        <source src="<?= htmlspecialchars($p['mp3_path']) ?>" type="audio/mpeg">
    </audio>
</article>
```

**Step 5: Create partials/footer.php**

```php
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> plot2pod</p>
</footer>
```

**Step 6: Verify in browser**

- `/` shows hero section and empty state (no podcasts yet)
- After inserting a test podcast in DB, card appears with audio player

**Step 7: Commit**

```bash
git add index.php partials/podcast-card.php partials/footer.php tests/test_podcasts.php
git commit -m "feat: homepage with podcast showcase and HTML5 audio player"
```

---

## Task 9: Podcast detail page (podcast.php)

**Files:**
- Create: `podcast.php`

**Step 1: Create podcast.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    die('Podcast not found.');
}

$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id=? AND published=1");
$stmt->execute([$id]);
$podcast = $stmt->fetch();

if (!$podcast) {
    http_response_code(404);
    die('Podcast not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($podcast['title']) ?> – plot2pod</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <main class="podcast-detail">
        <h1><?= htmlspecialchars($podcast['title']) ?></h1>
        <p class="description"><?= htmlspecialchars($podcast['description']) ?></p>
        <audio controls preload="metadata" class="main-player">
            <source src="<?= htmlspecialchars($podcast['mp3_path']) ?>" type="audio/mpeg">
        </audio>
        <p class="meta">Duration: <?= gmdate('i:s', $podcast['duration']) ?></p>
        <a href="/" class="back-link">← All podcasts</a>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

**Step 2: Verify in browser**

- `/podcast.php?id=999` → 404
- `/podcast.php?id=1` (valid podcast) → shows title, description, player

**Step 3: Commit**

```bash
git add podcast.php
git commit -m "feat: podcast detail page with HTML5 player"
```

---

## Task 10: Request submission form (request.php)

**Files:**
- Create: `request.php`
- Create: `tests/test_request.php`

**Step 1: Write test for request insertion**

Create `tests/test_request.php`:

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Insert a test user
$pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)")
    ->execute(['Req Tester', 'reqtest@example.com', password_hash('x', PASSWORD_DEFAULT)]);
$userId = $pdo->lastInsertId();

// Test 1: topic request inserts correctly
$stmt = $pdo->prepare(
    "INSERT INTO requests (user_id, type, content) VALUES (?, 'topic', ?)"
);
$stmt->execute([$userId, 'The history of jazz music']);
$reqId = $pdo->lastInsertId();
assert($reqId > 0, 'FAIL: request insert failed');
echo "PASS: topic request inserted\n";

// Test 2: request has status=pending by default
$row = $pdo->query("SELECT status FROM requests WHERE id=$reqId")->fetch();
assert($row['status'] === 'pending', 'FAIL: default status should be pending');
echo "PASS: default status is pending\n";

// Clean up
$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
echo "\nAll request tests passed.\n";
```

**Step 2: Run test**

```bash
php tests/test_request.php
```

Expected: Both PASS.

**Step 3: Create request.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

requireAuth();

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $type = $_POST['type'] ?? '';

        if (!in_array($type, ['topic', 'links', 'files'], true)) {
            $error = 'Please select a submission type.';
        } elseif ($type === 'files') {
            // File upload handling
            $filePaths = [];
            $uploaded  = $_FILES['files'] ?? [];

            if (empty($uploaded['name'][0])) {
                $error = 'Please upload at least one file.';
            } else {
                foreach ($uploaded['name'] as $i => $name) {
                    if ($uploaded['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_EXTS, true)) {
                        $error = "File type .$ext is not allowed. Use PDF, TXT, or DOCX.";
                        break;
                    }
                    if ($uploaded['size'][$i] > MAX_FILE_SIZE) {
                        $error = "File $name exceeds the 10 MB limit.";
                        break;
                    }

                    $safeName = uniqid() . '.' . $ext;
                    $dest     = UPLOAD_DIR . $safeName;
                    if (move_uploaded_file($uploaded['tmp_name'][$i], $dest)) {
                        $filePaths[] = $safeName;
                    }
                }

                if (!$error && empty($filePaths)) {
                    $error = 'File upload failed. Please try again.';
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare(
                    "INSERT INTO requests (user_id, type, file_paths) VALUES (?, 'files', ?)"
                );
                $stmt->execute([currentUser()['id'], json_encode($filePaths)]);
                $reqId   = $pdo->lastInsertId();
                $success = true;
            }
        } else {
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                $error = 'Please fill in the ' . ($type === 'topic' ? 'topic' : 'source links') . ' field.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO requests (user_id, type, content) VALUES (?, ?, ?)"
                );
                $stmt->execute([currentUser()['id'], $type, $content]);
                $reqId   = $pdo->lastInsertId();
                $success = true;
            }
        }

        if ($success) {
            $req = $pdo->query("SELECT * FROM requests WHERE id=$reqId")->fetch();
            sendNewRequestNotification($req, currentUser()['name']);
            header('Location: /dashboard.php?submitted=1');
            exit;
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
        <h1>Submit a podcast topic</h1>
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/request.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="type-selector">
                <label>
                    <input type="radio" name="type" value="topic" checked>
                    <span>📝 Give me a topic</span>
                </label>
                <label>
                    <input type="radio" name="type" value="links">
                    <span>🔗 I have source links</span>
                </label>
                <label>
                    <input type="radio" name="type" value="files">
                    <span>📁 I'll upload files</span>
                </label>
            </div>

            <div class="field-topic">
                <label>What should the podcast be about?
                    <textarea name="content" rows="4" placeholder="e.g. The impact of artificial intelligence on healthcare"></textarea>
                </label>
            </div>

            <div class="field-links" style="display:none">
                <label>Paste your source URLs (one per line):
                    <textarea name="content" rows="4" placeholder="https://..."></textarea>
                </label>
            </div>

            <div class="field-files" style="display:none">
                <label>Upload your files (PDF, TXT, DOCX – max 10 MB each):
                    <input type="file" name="files[]" multiple accept=".pdf,.txt,.docx">
                </label>
            </div>

            <button type="submit" class="btn-primary">Submit request</button>
        </form>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="/js/app.js"></script>
</body>
</html>
```

**Step 4: Add JS to app.js to toggle form fields**

Create/update `js/app.js`:

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="type"]');
    const fields = {
        topic: document.querySelector('.field-topic'),
        links: document.querySelector('.field-links'),
        files: document.querySelector('.field-files'),
    };

    function showField(type) {
        Object.entries(fields).forEach(([key, el]) => {
            if (el) el.style.display = key === type ? '' : 'none';
        });
    }

    radios.forEach(r => r.addEventListener('change', () => showField(r.value)));
});
```

**Step 5: Verify in browser**

- Visit `/request.php` without login → redirected to login
- Logged in: select Topic, submit empty → error
- Submit a topic → redirect to dashboard with `?submitted=1`
- Check DB: request row created with status=pending
- Check email: Miloš gets notification

**Step 6: Commit**

```bash
git add request.php js/app.js tests/test_request.php
git commit -m "feat: request submission form with topic/links/files modes and email notification"
```

---

## Task 11: User dashboard (dashboard.php)

**Files:**
- Create: `dashboard.php`

**Step 1: Create dashboard.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAuth();
$user = currentUser();

$stmt = $pdo->prepare(
    "SELECT r.*, p.title as podcast_title
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
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <main class="dashboard">
        <h1>My requests</h1>

        <?php if ($submitted): ?>
            <div class="success-msg">Your request was submitted! We'll notify you when it's ready.</div>
        <?php endif; ?>

        <a href="/request.php" class="btn-primary">+ Submit new topic</a>

        <?php if (empty($requests)): ?>
            <p class="empty-state">No requests yet. <a href="/request.php">Submit your first topic!</a></p>
        <?php else: ?>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Podcast</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td class="content-preview">
                            <?php if ($r['type'] === 'files'): ?>
                                <em>(files uploaded)</em>
                            <?php else: ?>
                                <?= htmlspecialchars(mb_substr($r['content'], 0, 60)) ?>…
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                        <td>
                            <?php if ($r['podcast_id']): ?>
                                <a href="/podcast.php?id=<?= $r['podcast_id'] ?>">
                                    <?= htmlspecialchars($r['podcast_title']) ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

**Step 2: Verify in browser**

- Login as test user with a request → table shows request with status badge
- `?submitted=1` → success message shown
- Done request → podcast link appears in table

**Step 3: Commit**

```bash
git add dashboard.php
git commit -m "feat: user dashboard showing request status and podcast links"
```

---

## Task 12: Admin panel (admin.php)

**Files:**
- Create: `admin.php`

**Step 1: Create admin.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

requireAuth();
requireAdmin();

$message = '';

// Handle status update + podcast assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $reqId     = (int)($_POST['request_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $podcastId = $_POST['podcast_id'] ? (int)$_POST['podcast_id'] : null;

        if (!in_array($newStatus, ['pending','processing','done'], true)) {
            $message = 'Invalid status.';
        } else {
            $pdo->prepare(
                "UPDATE requests SET status=?, podcast_id=? WHERE id=?"
            )->execute([$newStatus, $podcastId, $reqId]);

            // Send email when marked done
            if ($newStatus === 'done' && $podcastId) {
                $req = $pdo->query("SELECT r.*, u.email, u.name
                    FROM requests r JOIN users u ON u.id=r.user_id
                    WHERE r.id=$reqId")->fetch();

                if ($req && empty($req['notified_at'])) {
                    sendDoneNotification($req['email'], $req['name'], $podcastId);
                    $pdo->prepare("UPDATE requests SET notified_at=NOW() WHERE id=?")
                        ->execute([$reqId]);
                }
            }
            $message = 'Request updated.';
        }
    } elseif ($action === 'add_podcast') {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $mp3path  = trim($_POST['mp3_path'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);

        if ($title && $mp3path) {
            $pdo->prepare(
                "INSERT INTO podcasts (title, description, mp3_path, duration) VALUES (?,?,?,?)"
            )->execute([$title, $desc, $mp3path, $duration]);
            $message = 'Podcast added.';
        } else {
            $message = 'Title and MP3 path are required.';
        }
    }
}

// Fetch all requests newest first
$requests = $pdo->query(
    "SELECT r.*, u.name as user_name, u.email as user_email, p.title as podcast_title
     FROM requests r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN podcasts p ON p.id = r.podcast_id
     ORDER BY r.created_at DESC"
)->fetchAll();

// Fetch podcasts for dropdown
$podcasts = $pdo->query("SELECT id, title FROM podcasts ORDER BY created_at DESC")->fetchAll();

$csrf = generateCsrfToken();
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
        <h1>Admin panel</h1>

        <?php if ($message): ?>
            <div class="success-msg"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add podcast -->
        <section class="admin-section">
            <h2>Add podcast</h2>
            <form method="POST" action="/admin.php">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="add_podcast">
                <label>Title <input type="text" name="title" required></label>
                <label>Description <textarea name="description" rows="2"></textarea></label>
                <label>MP3 path (relative to webroot, e.g. /uploads/ep1.mp3)
                    <input type="text" name="mp3_path" required placeholder="/uploads/ep1.mp3">
                </label>
                <label>Duration (seconds) <input type="number" name="duration" min="0" value="0"></label>
                <button type="submit">Add podcast</button>
            </form>
        </section>

        <!-- Requests list -->
        <section class="admin-section">
            <h2>Requests (<?= count($requests) ?>)</h2>
            <?php foreach ($requests as $r): ?>
            <div class="request-item status-<?= $r['status'] ?>">
                <div class="request-meta">
                    <strong>#<?= $r['id'] ?></strong>
                    <?= date('M j Y H:i', strtotime($r['created_at'])) ?>
                    — <em><?= htmlspecialchars($r['user_name']) ?></em>
                    (<?= htmlspecialchars($r['user_email']) ?>)
                    — Type: <strong><?= $r['type'] ?></strong>
                    — Status: <span class="status-badge status-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                </div>

                <?php if ($r['type'] !== 'files'): ?>
                    <blockquote><?= htmlspecialchars($r['content']) ?></blockquote>
                <?php else: ?>
                    <p><em>Files: <?= htmlspecialchars($r['file_paths']) ?></em></p>
                <?php endif; ?>

                <form method="POST" action="/admin.php" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">

                    <select name="status">
                        <?php foreach (['pending','processing','done'] as $s): ?>
                            <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="podcast_id">
                        <option value="">— no podcast —</option>
                        <?php foreach ($podcasts as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $r['podcast_id'] == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update</button>
                </form>
            </div>
            <?php endforeach; ?>
        </section>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

**Step 2: Verify in browser**

- Login as admin → `/admin.php` loads
- Login as non-admin → 403
- Add a podcast → appears in podcast dropdown
- Change request status to `done`, assign podcast → user receives email notification
- `notified_at` timestamp set in DB (no double emails)

**Step 3: Commit**

```bash
git add admin.php
git commit -m "feat: admin panel with request management and podcast publishing"
```

---

## Task 13: Frontend styling via Frontend Design plugin

**Files:**
- Modify: `css/style.css`

**Step 1: Prompt the Frontend Design plugin**

In a new Claude session with the Frontend Design plugin active, provide this prompt:

```
Create the complete CSS for a podcast platform called plot2pod.

Pages: homepage (hero + podcast grid), auth pages (login/register centered cards),
request form (radio type selector + conditional fields), dashboard (table with
status badges), admin panel, podcast detail.

CSS classes to style:
- .site-header, .logo — sticky top nav
- .hero — large headline section with CTA
- .podcast-grid — responsive card grid
- .podcast-card — card with audio player
- .auth-page, .auth-card — centered login/register form
- .request-page, .type-selector — request form with radio pills
- .dashboard, .requests-table — table with status badges
- .status-badge.status-pending/processing/done — colored badges
- .admin, .admin-section, .request-item — admin panel
- .btn-primary — primary action button
- .error-msg, .success-msg — alert boxes
- .empty-state — placeholder text

Requirements: dark theme, distinctive typography (not Inter/Roboto),
mobile-first, HTML5 audio player styled. English interface.
```

**Step 2:** Paste generated CSS into `css/style.css`.

**Step 3: Verify all pages look correct in browser** (mobile and desktop widths).

**Step 4: Commit**

```bash
git add css/style.css
git commit -m "feat: complete frontend styling via Frontend Design plugin"
```

---

## Task 14: Deployment to cPanel

**Step 1: Create deployment checklist**

```
□ Set up database in cPanel → MySQL Databases → create DB + user
□ Run db/schema.sql via phpMyAdmin
□ Generate admin password hash:
    php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
□ Run db/seed.sql with real hash to create admin user
□ Upload all PHP/CSS/JS files via cPanel File Manager or FTP
□ Create config.php from config.example.php with real DB credentials + SMTP
□ Set uploads/ directory permissions to 755
□ Test: visit site URL, register a user, submit a topic, check admin email
□ Test: admin panel → update status to done → check user email notification
□ Upload 3-5 sample podcasts (MP3 files) via FTP → add via admin panel
```

**Step 2: Verify production checklist**

- Homepage shows 3–5 sample podcasts with working players
- Registration and login work
- Request submission sends email to Miloš
- Admin panel accessible only to admin user
- Status update to done sends email to user

**Step 3: Final commit**

```bash
git add .
git commit -m "chore: deployment configuration and final checklist"
```

---

## Summary

| Task | Component | Commit |
|------|-----------|--------|
| 1 | Project scaffolding | `chore: project scaffolding` |
| 2 | DB schema | `feat: database schema` |
| 3 | DB connection | `feat: PDO connection` |
| 4 | Auth helpers + CSRF | `feat: auth helpers` |
| 5 | PHPMailer setup | `feat: PHPMailer` |
| 6 | Registration | `feat: user registration` |
| 7 | Login + logout | `feat: login and logout` |
| 8 | Homepage + showcase | `feat: homepage` |
| 9 | Podcast detail | `feat: podcast detail` |
| 10 | Request form | `feat: request submission` |
| 11 | Dashboard | `feat: user dashboard` |
| 12 | Admin panel | `feat: admin panel` |
| 13 | CSS via Frontend Design | `feat: frontend styling` |
| 14 | Deploy to cPanel | `chore: deployment` |
