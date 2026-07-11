<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('calendar') ?> Upcoming events</h2>
        <a href="<?= e(url('events.index')) ?>">Calendar &rarr;</a>
    </div>
    <?php if ($upcomingEvents === []): ?>
        <p class="text-muted">Nothing scheduled.</p>
    <?php else: ?>
    <div class="card" style="padding:0.5rem 1rem;">
        <ul class="gazette-list">
            <?php foreach ($upcomingEvents as $event): ?>
            <li>
                <span class="cal-date-badge" style="background: <?= e((string) ($event['color'] ?: '#4f46e5')) ?>;">
                    <span><?= e(date('j', strtotime((string) $event['starts_at']))) ?></span>
                    <small><?= e(date('M', strtotime((string) $event['starts_at']))) ?></small>
                </span>
                <a class="gazette-title" href="<?= e(url('events.show', ['id' => $event['id']])) ?>"><?= e((string) $event['title']) ?></a>
                <span class="text-muted">
                    <?= (int) $event['all_day'] === 1 ? 'All day' : e(date('H:i', strtotime((string) $event['starts_at']))) ?>
                    <?= !empty($event['location']) ? ' · ' . e((string) $event['location']) : '' ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</section>
