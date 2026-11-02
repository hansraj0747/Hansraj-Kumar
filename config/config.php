<?php
declare(strict_types=1);

define('APP_NAME', 'College Event Management System');
define('APP_URL', 'http://localhost/College-Event-Management-System'); // Update this on your server.
define('APP_ENV', 'development'); // Change to production when deploying.
define('APP_DEBUG', APP_ENV !== 'production');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'college_events');
define('DB_USER', 'root');
define('DB_PASS', '');

define('MAIL_MODE', 'log'); // Use smtp only after configuring the SMTP settings below.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', 'no-reply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

date_default_timezone_set('Asia/Kolkata');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
