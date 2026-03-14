<?php
require_once __DIR__ . '/config.php';

/**
 * Convert a string to a URL-safe slug.
 */
function slugify(string $title): string {
    $slug = function_exists('mb_strtolower')
        ? mb_strtolower($title, 'UTF-8')
        : strtolower($title);
    $slug = preg_replace('/[^\w\s-]/u', '', $slug);
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    $slug = preg_replace('/-{2,}/', '-', $slug);
    $slug = trim($slug, '-');
    return function_exists('mb_substr')
        ? mb_substr($slug, 0, 200)
        : substr($slug, 0, 200);
}

/**
 * Generate a slug from title, appending -2, -3 etc. if it already exists in the DB.
 */
function generateUniqueSlug(PDO $pdo, string $title): string {
    $base = slugify($title);
    if ($base === '') {
        $base = 'podcast-' . time();
    }
    $slug = $base;
    $i    = 2;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcasts WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            break;
        }
        if ($i > 9999) {
            $slug = $base . '-' . time();
            break;
        }
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

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
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die('Database connection error. Please try again later.');
    }
}

