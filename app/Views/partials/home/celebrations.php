<?php if ($celebrations['birthdays'] !== [] || $celebrations['anniversaries'] !== []): ?>
<section class="home-section">
    <div class="home-section-head">
        <h2>🎂 Celebrations</h2>
    </div>
    <div class="card" style="padding:0.5rem 1rem;">
        <ul class="gazette-list">
            <?php foreach ($celebrations['birthdays'] as $person): ?>
            <li>
                <?php partial('partials/avatar', ['person' => $person, 'size' => 34]); ?>
                <span style="flex:1;">
                    <a href="<?= e(url('people.show', ['id' => $person['id']])) ?>"><strong><?= e((string) $person['name']) ?></strong></a>
                    — birthday <?= $person['in_days'] === 0 ? '<strong>today</strong> 🎉' : 'on ' . e((string) $person['day_month']) ?>
                </span>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('kudos.index') . '?to=' . (int) $person['id'] . '&wish=1') ?>">Send wishes</a>
            </li>
            <?php endforeach; ?>
            <?php foreach ($celebrations['anniversaries'] as $person): ?>
            <li>
                <?php partial('partials/avatar', ['person' => $person, 'size' => 34]); ?>
                <span style="flex:1;">
                    <a href="<?= e(url('people.show', ['id' => $person['id']])) ?>"><strong><?= e((string) $person['name']) ?></strong></a>
                    — <?= (int) $person['years'] ?> year<?= (int) $person['years'] > 1 ? 's' : '' ?> with us
                    <?= $person['in_days'] === 0 ? '<strong>today</strong> 🥳' : 'on ' . e((string) $person['day_month']) ?>
                </span>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('kudos.index') . '?to=' . (int) $person['id'] . '&wish=1') ?>">Send wishes</a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>
