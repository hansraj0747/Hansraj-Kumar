<?php
require_once __DIR__ . '/includes/bootstrap.php';

$search = trim((string) ($_GET['q'] ?? ''));
$departmentId = (int) ($_GET['department'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$conditions = ["e.status = 'approved'", 'TIMESTAMP(e.event_date, e.start_time) >= NOW()'];
$params = [];

if ($search !== '') {
    $conditions[] = '(e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($departmentId > 0) {
    $conditions[] = 'e.department_id = ?';
    $params[] = $departmentId;
}
$where = implode(' AND ', $conditions);

$countStatement = $pdo->prepare(
    "SELECT COUNT(*) FROM events e WHERE $where"
);
$countStatement->execute($params);
$totalEvents = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalEvents / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT e.*, d.name AS department
     FROM events e
     LEFT JOIN departments d ON d.id = e.department_id
     WHERE $where
     ORDER BY e.event_date ASC, e.start_time ASC
     LIMIT $perPage OFFSET $offset"
);
$statement->execute($params);
$events = $statement->fetchAll();

app_header('Events');
?>
<div class="section-head">
    <div>
        <span class="eyebrow">CAMPUS CALENDAR</span>
        <h1>Explore events</h1>
        <p class="muted">Find something to learn, create or celebrate.</p>
    </div>
</div>
<form method="get" class="inline" role="search">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search events, venues..." aria-label="Search events">
    <select name="department" aria-label="Filter by department">
        <option value="0">All departments</option>
        <?php foreach ($pdo->query('SELECT id, name FROM departments ORDER BY name') as $department): ?>
            <option value="<?= (int) $department['id'] ?>" <?= $departmentId === (int) $department['id'] ? 'selected' : '' ?>>
                <?= e($department['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-small">Search</button>
    <?php if ($search !== '' || $departmentId > 0): ?><a class="btn btn-small btn-light" href="events.php">Clear</a><?php endif; ?>
</form>
<div class="grid">
    <?php foreach ($events as $event): ?>
        <article class="card">
            <span class="tag"><?= e($event['department'] ?? 'Campus') ?></span>
            <h3><?= e($event['title']) ?></h3>
            <p><?= e($event['description']) ?></p>
            <div class="meta">📅 <?= e($event['event_date']) ?> · 📍 <?= e($event['venue']) ?></div>
            <p class="meta">Fee: ₹<?= number_format((float) $event['fee'], 2) ?></p>
            <a class="btn btn-small" href="event.php?id=<?= (int) $event['id'] ?>">Details &amp; register</a>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$events): ?>
    <div class="empty">No upcoming events match your search.</div>
<?php endif; ?>
<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Event pages">
        <?php for ($number = 1; $number <= $totalPages; $number++): ?>
            <a class="btn btn-small <?= $number === $page ? '' : 'btn-light' ?>"
               href="?<?= e(http_build_query(['q' => $search, 'department' => $departmentId, 'page' => $number])) ?>"
               <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php app_footer(); ?>
