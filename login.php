<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $statement = $pdo->prepare(
        'SELECT id, name, email, password_hash, role, department_id
         FROM users WHERE email = ? AND is_active = 1 LIMIT 1'
    );
    $statement->execute([$email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect('dashboard.php');
    }

    flash('Invalid email or password.', 'error');
}

app_header('Login');
?>
<div class="auth-wrap">
    <form method="post" class="form-card">
        <span class="eyebrow">WELCOME BACK</span>
        <h1>Sign in</h1>
        <p class="muted">Access your campus events dashboard.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required autocomplete="username">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <button class="btn full">Sign in</button>
        <p class="muted center">New here? <a href="register.php">Create an account</a></p>
    </form>
</div>
<?php app_footer(); ?>
