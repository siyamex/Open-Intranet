<div class="card" style="max-width:760px;">
    <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
        <?php partial('partials/avatar', ['person' => $person, 'size' => 96]); ?>
        <div>
            <h1 style="margin:0;"><?= e((string) $person['name']) ?></h1>
            <p class="text-muted" style="margin:0.2rem 0;">
                <?= e((string) ($person['job_title'] ?? '')) ?>
                <?= !empty($person['department_name']) ? ' · ' . e((string) $person['department_name']) : '' ?>
                <?= !empty($person['location']) ? ' · ' . e((string) $person['location']) : '' ?>
            </p>
            <p style="margin:0.2rem 0;">
                <a href="mailto:<?= e((string) $person['email']) ?>"><?= e((string) $person['email']) ?></a>
                <?php if (!empty($person['phone'])): ?> · <a href="tel:<?= e((string) $person['phone']) ?>"><?= e((string) $person['phone']) ?></a><?php endif; ?>
            </p>
        </div>
    </div>
    <?php if (!empty($person['bio'])): ?>
    <p style="margin-top:1rem;"><?= nl2br(e((string) $person['bio'])) ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($person['mgr_id'])): ?>
<div class="card" style="max-width:760px;">
    <h2>Manager</h2>
    <p><a href="<?= e(url('people.show', ['id' => $person['mgr_id']])) ?>"><?= e((string) $person['mgr_name']) ?></a>
       <span class="text-muted"><?= !empty($person['mgr_title']) ? '— ' . e((string) $person['mgr_title']) : '' ?></span></p>
</div>
<?php endif; ?>

<?php if ($reports !== []): ?>
<div class="card" style="max-width:760px;">
    <h2>Direct reports (<?= count($reports) ?>)</h2>
    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem;">
        <?php foreach ($reports as $r): ?>
        <li style="display:flex; align-items:center; gap:0.6rem;">
            <?php partial('partials/avatar', ['person' => $r, 'size' => 28]); ?>
            <a href="<?= e(url('people.show', ['id' => $r['id']])) ?>"><?= e((string) $r['name']) ?></a>
            <span class="text-muted"><?= e((string) ($r['job_title'] ?? '')) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($recentNews !== []): ?>
<div class="card" style="max-width:760px;">
    <h2>Recent posts</h2>
    <ul style="list-style:none; padding:0; margin:0;">
        <?php foreach ($recentNews as $n): ?>
        <li style="margin-bottom:0.4rem;">
            <a href="<?= e(base_url('news/' . $n['slug'])) ?>"><?= e((string) $n['title']) ?></a>
            <span class="text-muted">— <?= e(date('j M Y', strtotime((string) $n['published_at']))) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
