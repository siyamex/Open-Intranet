<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('heart') ?> Latest kudos</h2>
        <a href="<?= e(url('kudos.index')) ?>">Kudos wall &rarr;</a>
    </div>
    <?php if ($latestKudos === []): ?>
        <p class="text-muted">No kudos yet — <a href="<?= e(url('kudos.index')) ?>">send the first one</a>!</p>
    <?php else: ?>
    <div class="card" style="padding:0.5rem 1rem;">
        <ul class="gazette-list">
            <?php foreach ($latestKudos as $kudos): ?>
            <li>
                <?php partial('partials/avatar', ['person' => ['name' => $kudos['recipient_name'], 'avatar_path' => $kudos['recipient_avatar']], 'size' => 34]); ?>
                <span style="flex:1; min-width:0;">
                    <strong><?= e((string) ($kudos['sender_name'] ?? 'Someone')) ?></strong> →
                    <strong><?= e((string) $kudos['recipient_name']) ?></strong>
                    <?php if (!empty($kudos['value_label'])): ?><span class="badge"><?= e((string) ($kudos['value_emoji'] ?? '')) ?> <?= e((string) $kudos['value_label']) ?></span><?php endif; ?>
                    <br><span class="text-muted" style="font-size:0.85rem;"><?= e(mb_strimwidth((string) $kudos['message'], 0, 90, '…')) ?></span>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</section>
