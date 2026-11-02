<?php
require_once __DIR__ . '/includes/bootstrap.php';

app_header('Home');
?>

<section class="hero">
    <div>
        <span class="eyebrow">CAMPUS • COMMUNITY • CREATIVITY</span>
        <h1>Every great campus moment starts with an event.</h1>
        <p>
            Discover college events, register online, receive QR entry passes
            and celebrate your participation.
        </p>
        <a class="btn" href="events.php">Explore Events</a>
        <a class="btn btn-light" href="register.php">Join Campus</a>
    </div>

    <div class="hero-card">
        <div class="hero-icon">✦</div>
        <h3>One campus. Many experiences.</h3>
        <p>Workshops · Festivals · Sports · Seminars · Competitions</p>
        <div class="stats">
            <span><b>01</b> Discover</span>
            <span><b>02</b> Register</span>
            <span><b>03</b> Participate</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">WHAT'S HAPPENING</span>
            <h2>Upcoming events</h2>
        </div>
        <a href="events.php">View all →</a>
    </div>

    <div class="grid">
        <?php
        $events = $pdo->query(
            "SELECT e.*, d.name AS department
            FROM events e
            LEFT JOIN departments d ON d.id = e.department_id
            WHERE e.status = 'approved'
                AND TIMESTAMP(e.event_date, e.start_time) >= NOW()
            ORDER BY e.event_date
            LIMIT 3"
        );

        foreach ($events as $event):
        ?>
            <article class="card">
                <span class="tag"><?= e($event['department'] ?? 'Campus') ?></span>
                <h3><?= e($event['title']) ?></h3>
                <p><?= e(text_excerpt($event['description'], 120)) ?></p>
                <div class="meta">
                    📅 <?= e($event['event_date']) ?> · 📍 <?= e($event['venue']) ?>
                </div>
                <a class="text-link" href="event.php?id=<?= (int) $event['id'] ?>">
                    View event →
                </a>
            </article>
        <?php endforeach; ?>

        <?php if (!$events->rowCount()): ?>
            <p class="muted">No upcoming events published yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php app_footer(); ?>
