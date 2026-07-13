<div class="page-head">
    <h1>Acknowledgement report</h1>
    <p><a href="<?= e(url('admin.banners')) ?>">&larr; Back to banners</a></p>
    <p class="text-muted"><?= e((string) $banner['message']) ?></p>
</div>

<div class="admin-two-col">
    <div class="card">
        <h2>Acknowledged (<?= count($acked) ?>)</h2>
        <ul style="list-style:none; margin:0; padding:0;">
            <?php foreach ($acked as $person): ?>
            <li style="padding:0.3rem 0; border-bottom:1px solid var(--color-border);">
                <?= e((string) $person['name']) ?> <span class="text-muted">— <?= e(date('j M H:i', strtotime((string) $person['created_at']))) ?></span>
            </li>
            <?php endforeach; ?>
            <?php if ($acked === []): ?><li class="text-muted">Nobody yet.</li><?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Not yet acknowledged (<?= count($notAcked) ?>)</h2>
        <ul style="list-style:none; margin:0; padding:0;">
            <?php foreach ($notAcked as $person): ?>
            <li style="padding:0.3rem 0; border-bottom:1px solid var(--color-border);"><?= e((string) $person['name']) ?> <span class="text-muted">(<?= e((string) $person['email']) ?>)</span></li>
            <?php endforeach; ?>
            <?php if ($notAcked === []): ?><li class="text-muted">Everyone has acknowledged. ✓</li><?php endif; ?>
        </ul>
    </div>
</div>
