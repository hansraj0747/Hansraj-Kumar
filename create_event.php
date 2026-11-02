<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

if (current_user()['role'] !== 'student') {
    redirect('admin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $eventDate = (string) ($_POST['event_date'] ?? '');
    $startTime = (string) ($_POST['start_time'] ?? '');
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $feeInput = (string) ($_POST['fee'] ?? '0');

    $date = DateTime::createFromFormat('!Y-m-d', $eventDate);
    $validDate = $date && $date->format('Y-m-d') === $eventDate
        && $eventDate >= date('Y-m-d');
    $validTime = (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime);
    $validFee = is_numeric($feeInput) && (float) $feeInput >= 0
        && (float) $feeInput <= 99999999.99;

    $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
    $departmentCheck->execute([$departmentId]);
    $validDepartment = (bool) $departmentCheck->fetchColumn();

    if (text_length($title) < 3 || text_length($title) > 180
        || $description === '' || text_length($description) > 10000
        || $venue === '' || text_length($venue) > 180
        || !$validDate || !$validTime || !$validFee || !$validDepartment) {
        flash('Please check the event details. Use a future date, valid time, department and non-negative fee.', 'error');
    } else {
        $statement = $pdo->prepare(
            "INSERT INTO events
             (title, description, event_date, start_time, venue, department_id, fee, created_by, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $statement->execute([
            $title, $description, $eventDate, $startTime . ':00', $venue,
            $departmentId, number_format((float) $feeInput, 2, '.', ''), current_user()['id'],
        ]);

        flash('Event proposal submitted. An admin must approve it before it is published.');
        redirect('dashboard.php');
    }
}

app_header('Propose event');
?>
<div class="auth-wrap wide">
    <form method="post" class="form-card">
        <span class="eyebrow">STUDENT EVENT PROPOSAL</span>
        <h1>Launch an event</h1>
        <p class="muted">Your proposal will be reviewed by the admin before it becomes public.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="title">Event title</label>
        <input id="title" name="title" required maxlength="180" minlength="3">
        <label for="description">Description</label>
        <textarea id="description" name="description" required rows="5" maxlength="10000"></textarea>
        <div class="form-row">
            <div><label for="event_date">Date</label><input id="event_date" type="date" name="event_date" required min="<?= date('Y-m-d') ?>"></div>
            <div><label for="start_time">Start time</label><input id="start_time" type="time" name="start_time" required></div>
        </div>
        <label for="venue">Venue</label>
        <input id="venue" name="venue" required maxlength="180">
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id" required>
            <option value="">Select department</option>
            <?php foreach ($pdo->query('SELECT id, name FROM departments ORDER BY name') as $department): ?>
                <option value="<?= (int) $department['id'] ?>"><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="fee">Entry fee (₹; enter 0 for free)</label>
        <input id="fee" type="number" name="fee" min="0" max="99999999.99" step="0.01" value="0" required>
        <button class="btn full">Submit for approval</button>
    </form>
</div>
<?php app_footer(); ?>
