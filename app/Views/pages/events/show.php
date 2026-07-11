<div class="card" style="max-width:720px;">
    <span class="chip" style="background: <?= e((string) ($event['color'] ?: '#4f46e5')) ?>;">Event</span>
    <h1 style="margin-top:0.5rem;"><?= e((string) $event['title']) ?></h1>
    <p>
        📅 <?= (int) $event['all_day'] === 1
            ? e(date('l, j F Y', strtotime((string) $event['starts_at']))) . ' (all day)'
            : e(date('l, j F Y H:i', strtotime((string) $event['starts_at']))) . ' – ' . e(date('H:i', strtotime((string) $event['ends_at']))) ?>
        <?php if (!empty($event['location'])): ?><br>📍 <?= e((string) $event['location']) ?><?php endif; ?>
    </p>
    <?php if (!empty($event['description'])): ?>
    <p><?= nl2br(e((string) $event['description'])) ?></p>
    <?php endif; ?>
    <p><a class="btn btn-secondary btn-sm" href="<?= e(url('events.ics', ['id' => $event['id']])) ?>">Add to calendar (.ics)</a></p>
</div>

<?php if ((int) $event['rsvp_enabled'] === 1): ?>
<div class="card" style="max-width:720px;">
    <h2>Are you coming?</h2>
    <form method="post" action="<?= e(url('events.rsvp', ['id' => $event['id']])) ?>" class="filter-bar" style="margin:0 0 1rem;">
        <?= csrf_field() ?>
        <?php foreach (['going' => '✅ Going', 'maybe' => '🤔 Maybe', 'no' => '❌ Can\'t make it'] as $value => $label): ?>
        <button type="submit" name="response" value="<?= $value ?>"
                class="btn <?= $mine === $value ? 'btn-primary' : 'btn-secondary' ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </form>
    <?php foreach (['going' => 'Going', 'maybe' => 'Maybe', 'no' => 'Not coming'] as $key => $label): ?>
        <?php if ($rsvps[$key] !== []): ?>
        <p style="margin:0.4rem 0 0.2rem;"><strong><?= $label ?> (<?= count($rsvps[$key]) ?>)</strong></p>
        <div style="display:flex; gap:0.35rem; flex-wrap:wrap;">
            <?php foreach ($rsvps[$key] as $person): ?>
            <a href="<?= e(url('people.show', ['id' => $person['id']])) ?>" title="<?= e((string) $person['name']) ?>">
                <?php partial('partials/avatar', ['person' => $person, 'size' => 32]); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
