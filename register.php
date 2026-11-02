<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $password = (string) ($_POST['password'] ?? '');

    if (text_length($name) < 2 || text_length($name) > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 10 || strlen($password) > 4096) {
        flash('Enter a valid name and email. Your password must contain at least 10 characters.', 'error');
    } else {
        try {
            if ($departmentId > 0) {
                $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
                $departmentCheck->execute([$departmentId]);
                if (!$departmentCheck->fetch()) {
                    throw new InvalidArgumentException('Please choose a valid department.');
                }
            }

            $statement = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, department_id, role)
                 VALUES (?, ?, ?, ?, 'student')"
            );
            $statement->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $departmentId > 0 ? $departmentId : null,
            ]);

            try {
                send_notice($pdo, $email, 'Welcome to CampusEvents',
                    "Hello $name, your account has been created.");
            } catch (Throwable $exception) {
                error_log('Welcome notification failed: ' . $exception->getMessage());
            }

            flash('Registration successful. Please sign in.');
            redirect('login.php');
        } catch (InvalidArgumentException $exception) {
            flash($exception->getMessage(), 'error');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('An account with that email address already exists.', 'error');
            } else {
                error_log('Registration failed: ' . $exception->getMessage());
                flash('We could not create your account. Please try again.', 'error');
            }
        }
    }
}

app_header('Register');
?>
<div class="auth-wrap">
    <form method="post" class="form-card">
        <span class="eyebrow">CREATE ACCOUNT</span>
        <h1>Join your campus</h1>
        <p class="muted">Register as a student to discover and propose events.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="name">Full name</label>
        <input id="name" name="name" required maxlength="100" autocomplete="name">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required maxlength="190" autocomplete="email">
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id">
            <option value="">Select department</option>
            <?php foreach ($pdo->query('SELECT id, name FROM departments ORDER BY name') as $department): ?>
                <option value="<?= (int) $department['id'] ?>"><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="password">Password (minimum 10 characters)</label>
        <input id="password" type="password" name="password" minlength="10" maxlength="4096" required autocomplete="new-password">
        <button class="btn full">Create account</button>
        <p class="muted center">Already registered? <a href="login.php">Sign in</a></p>
    </form>
</div>
<?php app_footer(); ?>
