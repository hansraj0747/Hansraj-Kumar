<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id < 1) {
        flash('Choose a valid record.', 'error');
        redirect('admin.php');
    }

    if ($action === 'approve' || $action === 'reject') {
        $statement = $pdo->prepare(
            "UPDATE events SET status = ? WHERE id = ? AND status = 'pending'"
        );
        $statement->execute([$action === 'approve' ? 'approved' : 'rejected', $id]);
    } elseif ($action === 'payment' || $action === 'reject_payment') {
        $newStatus = $action === 'payment' ? 'verified' : 'rejected';
        $statement = $pdo->prepare(
            "UPDATE event_registrations r
             JOIN events e ON e.id = r.event_id
             SET r.payment_status = ?
             WHERE r.id = ? AND e.fee > 0 AND r.payment_status = 'pending'"
        );
        $statement->execute([$newStatus, $id]);
    } elseif ($action === 'attendance') {
        $statement = $pdo->prepare(
            "UPDATE event_registrations r
             JOIN events e ON e.id = r.event_id
             SET r.attendance_status = 'attended'
             WHERE r.id = ?
               AND r.attendance_status = 'registered'
               AND r.payment_status IN ('verified', 'not_required')
               AND TIMESTAMP(e.event_date, e.start_time) <= NOW()"
        );
        $statement->execute([$id]);
    } else {
        flash('Unknown admin action.', 'error');
        redirect('admin.php');
    }

    flash($statement->rowCount() > 0
        ? 'The requested change has been saved.'
        : 'No change was made. The record may already have been updated or may not meet the required conditions.',
        $statement->rowCount() > 0 ? 'success' : 'error');
    redirect('admin.php');
}

app_header('Admin panel');
?>
<div class="section-head">
    <div>
        <span class="eyebrow">CONTROL CENTER</span>
        <h1>Admin dashboard</h1>
        <p class="muted">Review proposals, verify payments and manage attendance.</p>
    </div>
    <a class="btn" href="checkin.php">Open QR check-in</a>
</div>
<div class="grid">
    <div class="card metric"><span>Students</span><strong><?= (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?></strong></div>
    <div class="card metric"><span>Approved events</span><strong><?= (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn() ?></strong></div>
    <div class="card metric"><span>Registrations</span><strong><?= (int) $pdo->query('SELECT COUNT(*) FROM event_registrations')->fetchColumn() ?></strong></div>
</div>

<section class="section">
    <div class="section-head">
        <h2>Pending event proposals</h2>
        <a class="btn btn-small" href="reports.php">Download reports (CSV)</a>
    </div>
    <div class="table-wrap">
        <table>
            <tr><th>Event</th><th>Department</th><th>Student</th><th>Action</th></tr>
            <?php
            $proposals = $pdo->query(
                "SELECT e.id, e.title, e.event_date, d.name AS department, u.name AS student
                 FROM events e
                 LEFT JOIN departments d ON d.id = e.department_id
                 JOIN users u ON u.id = e.created_by
                 WHERE e.status = 'pending'
                 ORDER BY e.created_at ASC"
            );
            foreach ($proposals as $proposal):
            ?>
                <tr>
                    <td><?= e($proposal['title']) ?><small><?= e($proposal['event_date']) ?></small></td>
                    <td><?= e($proposal['department'] ?? '—') ?></td>
                    <td><?= e($proposal['student']) ?></td>
                    <td>
                        <form method="post" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $proposal['id'] ?>">
                            <button name="action" value="approve" class="btn btn-small">Approve</button>
                            <button name="action" value="reject" class="btn btn-small btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>

<section class="section">
    <h2>Payment verification &amp; attendance</h2>
    <div class="table-wrap">
        <table>
            <tr><th>Student</th><th>Event</th><th>Payment reference</th><th>Status / actions</th></tr>
            <?php
            $registrations = $pdo->query(
                "SELECT r.id, r.payment_reference, r.payment_status, r.attendance_status,
                        u.name AS student, e.title AS event_title
                 FROM event_registrations r
                 JOIN users u ON u.id = r.user_id
                 JOIN events e ON e.id = r.event_id
                 ORDER BY r.created_at DESC"
            );
            foreach ($registrations as $registration):
            ?>
                <tr>
                    <td><?= e($registration['student']) ?></td>
                    <td><?= e($registration['event_title']) ?></td>
                    <td><?= e($registration['payment_reference'] ?? '—') ?></td>
                    <td>
                        <?= e($registration['payment_status']) ?> / <?= e($registration['attendance_status']) ?>
                        <form method="post" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $registration['id'] ?>">
                            <?php if ($registration['payment_status'] === 'pending'): ?>
                                <button class="btn btn-small" name="action" value="payment">Verify payment</button>
                                <button class="btn btn-small btn-danger" name="action" value="reject_payment">Reject payment</button>
                            <?php endif; ?>
                            <?php if ($registration['attendance_status'] !== 'attended'
                                && in_array($registration['payment_status'], ['verified', 'not_required'], true)): ?>
                                <button class="btn btn-small btn-light" name="action" value="attendance">Mark attended</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>

<section class="section">
    <h2>Departments</h2>
    <form method="post" action="add_department.php" class="inline">
        <input name="name" placeholder="New department name" maxlength="120" required>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="btn btn-small">Add department</button>
    </form>
</section>
<?php app_footer(); ?>
