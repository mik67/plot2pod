<?php
// Copy this file to config.php and fill in your values
define('DB_HOST',    'localhost');
define('DB_NAME',    'plot2pod');
define('DB_USER',    'your_db_user');
define('DB_PASS',    'your_db_password');
define('DB_CHARSET', 'utf8mb4');

define('ADMIN_EMAIL', 'milos@yourdomain.com');
define('FROM_EMAIL',  'noreply@plot2pod.com');
define('FROM_NAME',   'plot2pod');
define('SITE_URL',    'https://plot2pod.com');

define('UPLOAD_DIR',   __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTS', ['pdf', 'txt', 'docx']);

define('SESSION_NAME', 'plot2pod_session');

define('SMTP_HOST',   'mail.yourdomain.com');
define('SMTP_USER',   'noreply@plot2pod.com');
define('SMTP_PASS',   'your_smtp_password');
define('SMTP_SECURE', 'tls');
define('SMTP_PORT',   587);
