<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $token = trim((string) ($_POST['pass_token'] ?? ''));

    if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
        $message = 'That QR code or pass token is not valid.';
        $messageType = 'error';
    } else {
        $statement = $pdo->prepare(
            "SELECT r.id, r.attendance_status, r.payment_status, e.title,
                    e.event_date, e.start_time
             FROM event_registrations r
             JOIN events e ON e.id = r.event_id
             WHERE r.pass_token = ? LIMIT 1"
        );
        $statement->execute([$token]);
        $registration = $statement->fetch();

        if (!$registration) {
            $message = 'No registration was found for this pass.';
            $messageType = 'error';
        } elseif (!in_array($registration['payment_status'], ['verified', 'not_required'], true)) {
            $message = 'Check-in denied: payment has not been verified.';
            $messageType = 'error';
        } elseif (strtotime($registration['event_date'] . ' ' . $registration['start_time']) > time()) {
            $message = 'Check-in is not open yet for this event.';
            $messageType = 'error';
        } elseif ($registration['attendance_status'] === 'attended') {
            $message = 'This pass has already been checked in.';
            $messageType = 'error';
        } else {
            $update = $pdo->prepare(
                "UPDATE event_registrations
                 SET attendance_status = 'attended'
                 WHERE id = ? AND attendance_status = 'registered'"
            );
            $update->execute([$registration['id']]);
            $message = $update->rowCount()
                ? 'Check-in successful for ' . $registration['title'] . '.'
                : 'This registration was already checked in.';
            $messageType = $update->rowCount() ? 'success' : 'error';
        }
    }
}

app_header('Event check-in');
?>
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">EVENT ENTRY</span>
            <h1>QR pass check-in</h1>
            <p class="muted">Scan a pass with a QR reader or paste its token below.</p>
        </div>
    </div>
    <?php if ($message !== ''): ?>
        <div class="alert <?= e($messageType) ?>"><?= e($message) ?></div>
    <?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="pass_token">Pass token</label>
        <input id="pass_token" name="pass_token" required maxlength="48" minlength="48" pattern="[a-fA-F0-9]{48}" autocomplete="off" autofocus>
        <button class="btn">Validate pass and check in</button>
    </form>
</section>
<?php app_footer(); ?>
