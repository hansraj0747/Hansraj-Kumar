<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$registrationId = (int) ($_GET['id'] ?? 0);
$user = current_user();
$sql = "SELECT r.*, e.title, e.event_date, e.start_time, e.venue,
               e.fee, u.name, u.email
        FROM event_registrations r
        JOIN events e ON e.id = r.event_id
        JOIN users u ON u.id = r.user_id
        WHERE r.id = ?";
$parameters = [$registrationId];

if ($user['role'] !== 'admin') {
    $sql .= ' AND r.user_id = ?';
    $parameters[] = $user['id'];
}

$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$registration = $statement->fetch();

if (!$registration) {
    http_response_code(404);
    exit('Registration not found.');
}

$paymentIsComplete = $registration['payment_status'] === 'verified'
    || $registration['payment_status'] === 'not_required';
$passIsActive = $paymentIsComplete && $registration['payment_status'] !== 'rejected';

app_header('Event pass');
?>
<div class="pass-card">
    <span class="eyebrow">CAMPUS EVENT REGISTRATION</span>
    <h1><?= e($registration['title']) ?></h1>
    <p class="muted"><?= e($registration['name']) ?> · <?= e($registration['email']) ?></p>
    <div class="pass-info">
        <span>DATE<strong><?= e($registration['event_date']) ?></strong></span>
        <span>TIME<strong><?= e($registration['start_time']) ?></strong></span>
        <span>VENUE<strong><?= e($registration['venue']) ?></strong></span>
    </div>

    <?php if ($passIsActive): ?>
        <div id="qrcode" aria-label="QR entry pass"></div>
        <p class="muted">Registration ID: <?= (int) $registration['id'] ?></p>
        <button class="btn" onclick="window.print()">Print pass</button>
    <?php else: ?>
        <div class="alert error">
            <?php if ($registration['payment_status'] === 'rejected'): ?>
                This payment was rejected. Please contact the event administrator.
            <?php else: ?>
                Your payment is awaiting admin verification. The QR entry pass will appear after verification.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p>Payment: <b><?= e($registration['payment_status']) ?></b> · Attendance: <b><?= e($registration['attendance_status']) ?></b></p>
    <?php if ($registration['attendance_status'] === 'attended'): ?>
        <a class="btn btn-light" href="certificate.php?id=<?= (int) $registration['id'] ?>">View certificate</a>
    <?php else: ?>
        <p class="muted">Certificate becomes available after an admin confirms attendance.</p>
    <?php endif; ?>
</div>
<?php if ($passIsActive): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const qrContainer = document.getElementById('qrcode');
        if (qrContainer && window.QRCode) {
            new QRCode(qrContainer, {
                text: <?= json_encode($registration['pass_token'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                width: 180,
                height: 180
            });
        }
    </script>
<?php endif; ?>
<?php app_footer(); ?>
