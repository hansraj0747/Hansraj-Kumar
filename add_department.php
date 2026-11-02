<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
$name = trim((string) ($_POST['name'] ?? ''));

if (text_length($name) < 2 || text_length($name) > 120) {
    flash('Department names must contain between 2 and 120 characters.', 'error');
} else {
    $statement = $pdo->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
    $statement->execute([$name]);
    flash($statement->rowCount() > 0 ? 'Department added.' : 'That department already exists.',
        $statement->rowCount() > 0 ? 'success' : 'error');
}

redirect('admin.php');
