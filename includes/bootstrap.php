<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

// Keep authenticated sessions alive only while the user is active.
$sessionTimeout = 1800;
if (!empty($_SESSION['user'])) {
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if ($lastActivity > 0 && time() - $lastActivity > $sessionTimeout) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'] = [
            'message' => 'Your session expired. Please sign in again.',
            'type' => 'error',
        ];
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit(APP_DEBUG
        ? 'Database connection failed. Check the database configuration.'
        : 'Service temporarily unavailable. Please try again later.');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function text_excerpt(string $value, int $limit): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $limit, '…', 'UTF-8');
    }

    $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if ($characters === false) {
        return substr($value, 0, $limit);
    }

    return count($characters) > $limit
        ? implode('', array_slice($characters, 0, max(0, $limit - 1))) . '…'
        : $value;
}

function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf'] ?? '';

    if (!is_string($token) || !is_string($sessionToken)
        || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Your form has expired. Please go back, refresh the page and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();

    if ((current_user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert ' . e($message['type'] ?? 'success') . '">'
        . e($message['message'] ?? '') . '</div>';
}

function send_notice(PDO $pdo, string $to, string $subject, string $body): void
{
    $statement = $pdo->prepare(
        'INSERT INTO notifications (email, subject, body) VALUES (?, ?, ?)'
    );
    $statement->execute([$to, $subject, $body]);

    if (MAIL_MODE === 'smtp' && class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
        } catch (Throwable $exception) {
            error_log('Mail send failed: ' . $exception->getMessage());
        }
        return;
    }

    $directory = __DIR__ . '/../storage';
    if (!is_dir($directory)) {
        mkdir($directory, 0750, true);
    }

    $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
    $entry = date('c') . " | $to | $safeSubject\n$body\n---\n";
    file_put_contents($directory . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
}

function app_header(string $title = ''): void
{
    $user = current_user();
    $pageTitle = $title ? $title . ' | ' . APP_NAME : APP_NAME;

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($pageTitle) . '</title>'
        . '<link rel="stylesheet" href="' . APP_URL . '/assets/css/style.css"></head><body>';
    echo '<header class="topbar"><a class="brand" href="' . APP_URL . '/index.php">CampusEvents</a><nav>';
    echo '<a href="' . APP_URL . '/events.php">Events</a>';

    if ($user) {
        echo '<a href="' . APP_URL . '/dashboard.php">Dashboard</a>';
        if ($user['role'] === 'admin') {
            echo '<a href="' . APP_URL . '/admin.php">Admin</a>';
        }
        echo '<span class="nav-user">' . e($user['name']) . '</span>';
        echo '<form method="post" action="' . APP_URL . '/logout.php" class="inline nav-logout">'
            . '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="nav-link">Logout</button></form>';
    } else {
        echo '<a href="' . APP_URL . '/login.php">Login</a>';
        echo '<a class="nav-cta" href="' . APP_URL . '/register.php">Register</a>';
    }

    echo '</nav></header><main class="container">';
    show_flash();
}

function app_footer(): void
{
    echo '</main><footer>© ' . date('Y') . ' ' . e(APP_NAME)
        . ' · College events, made simple</footer></body></html>';
}
