<?php
require_once __DIR__ . '/includes/bootstrap.php';

$eventId = (int) ($_GET['id'] ?? 0);
$statement = $pdo->prepare(
    "SELECT e.*, d.name AS department
     FROM events e
     LEFT JOIN departments d ON d.id = e.department_id
     WHERE e.id = ? AND e.status = 'approved' AND TIMESTAMP(e.event_date, e.start_time) >= NOW()"
);
$statement->execute([$eventId]);
$event = $statement->fetch();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $user = current_user();
    $paymentReference = trim((string) ($_POST['payment_reference'] ?? ''));
    $isPaidEvent = (float) $event['fee'] > 0;

    if ($isPaidEvent && ($paymentReference === '' || text_length($paymentReference) > 100)) {
        flash('Enter a valid payment reference (maximum 100 characters).', 'error');
    } else {
        $passToken = bin2hex(random_bytes(24));

        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO event_registrations
                 (event_id, user_id, pass_token, payment_reference, payment_status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $eventId,
                $user['id'],
                $passToken,
                $isPaidEvent ? $paymentReference : null,
                $isPaidEvent ? 'pending' : 'not_required',
            ]);
            $registrationId = (int) $pdo->lastInsertId();
            $pdo->commit();

            try {
                send_notice(
                    $pdo,
                    $user['email'],
                    'Event registration received',
                    "Your registration for {$event['title']} was received. Registration ID: $registrationId. "
                    . 'Payment status: ' . ($isPaidEvent ? 'pending verification' : 'not required')
                );
            } catch (Throwable $exception) {
                error_log('Registration notification failed: ' . $exception->getMessage());
            }

            flash('Registration submitted. Your pass will become active when any required payment is verified.');
            redirect('pass.php?id=' . $registrationId);
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($exception->getCode() === '23000') {
                flash('You are already registered for this event.', 'error');
            } else {
                error_log('Event registration failed: ' . $exception->getMessage());
                flash('Registration could not be completed. Please try again.', 'error');
            }
        }
    }
}

app_header($event['title']);
?>
<div class="detail card">
    <span class="tag"><?= e($event['department'] ?? 'Campus') ?></span>
    <h1><?= e($event['title']) ?></h1>
    <p><?= nl2br(e($event['description'])) ?></p>
    <div class="meta">📅 <?= e($event['event_date']) ?> · <?= e($event['start_time']) ?> · 📍 <?= e($event['venue']) ?></div>
    <h2>₹<?= number_format((float) $event['fee'], 2) ?></h2>
    <?php if (current_user()): ?>
        <form method="post" class="stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ((float) $event['fee'] > 0): ?>
                <label for="payment_reference">Payment reference / UPI transaction ID</label>
                <input id="payment_reference" name="payment_reference" maxlength="100" required placeholder="Enter transaction reference">
                <p class="muted">Payment is subject to admin verification. Do not enter card or bank passwords.</p>
            <?php endif; ?>
            <button class="btn">Register for event</button>
        </form>
    <?php else: ?>
        <a class="btn" href="login.php">Sign in to register</a>
    <?php endif; ?>
</div>
<?php app_footer(); ?>
