<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

function csv_safe_value(mixed $value): string
{
    $value = (string) ($value ?? '');

    // Spreadsheet programs may execute cells that begin with formula characters.
    if ($value !== '' && preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $value)) {
        return "'" . $value;
    }

    return $value;
}

if (($_GET['export'] ?? '') === 'registrations') {
    $statement = $pdo->query(
        "SELECT r.id, u.name, u.email, e.title, e.event_date,
                r.payment_status, r.payment_reference, r.attendance_status
         FROM event_registrations r
         JOIN users u ON u.id = r.user_id
         JOIN events e ON e.id = r.event_id
         ORDER BY r.created_at DESC"
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event-registrations.csv"');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM helps spreadsheet software display names correctly.
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Registration ID', 'Student', 'Email', 'Event', 'Event Date', 'Payment Status', 'Payment Reference', 'Attendance']);

    while ($row = $statement->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, array_map('csv_safe_value', $row));
    }

    fclose($output);
    exit;
}

app_header('Reports');
?>
<div class="section-head">
    <div>
        <span class="eyebrow">REPORTING</span>
        <h1>Event reports</h1>
        <p class="muted">Export registration and payment tracking data for college records.</p>
    </div>
</div>
<div class="grid">
    <div class="card">
        <h3>Registration report</h3>
        <p>Student, event, date, payment reference and attendance status.</p>
        <a class="btn" href="reports.php?export=registrations">Download CSV</a>
    </div>
    <div class="card">
        <h3>Event summary</h3>
        <p><b><?= (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn() ?></b> total events</p>
        <p><b><?= (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn() ?></b> approved events</p>
    </div>
    <div class="card">
        <h3>Payment summary</h3>
        <p><b><?= (int) $pdo->query("SELECT COUNT(*) FROM event_registrations WHERE payment_status = 'verified'")->fetchColumn() ?></b> verified payments</p>
        <p><b><?= (int) $pdo->query("SELECT COUNT(*) FROM event_registrations WHERE payment_status = 'pending'")->fetchColumn() ?></b> pending verification</p>
    </div>
</div>
<?php app_footer(); ?>
