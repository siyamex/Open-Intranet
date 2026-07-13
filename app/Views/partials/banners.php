<?php
use App\Core\Auth;
use App\Core\BannerService;
use App\Core\DB;

$dismissed = (array) ($_SESSION['dismissed_banners'] ?? []);
$acked = Auth::check() ? array_map('intval', array_column(DB::fetchAll(
    'SELECT banner_id FROM banner_acknowledgements WHERE user_id = ?',
    [Auth::id()]
), 'banner_id')) : [];
$banners = array_values(array_filter(
    BannerService::active(),
    static function (array $b) use ($dismissed, $acked): bool {
        if ((int) $b['require_ack'] === 1) {
            return !in_array((int) $b['id'], $acked, true);
        }
        if ((int) $b['dismissible'] === 1) {
            return !in_array((int) $b['id'], $dismissed, true);
        }
        return true;
    }
));
?>
<?php foreach ($banners as $banner): ?>
<?php $needsAck = (int) $banner['require_ack'] === 1 && !in_array((int) $banner['id'], $acked, true); ?>
<div class="emergency-banner sev-<?= e((string) $banner['severity']) ?>" data-banner-id="<?= (int) $banner['id'] ?>"
     data-dismiss-url="<?= e(url('banners.dismiss', ['id' => $banner['id']])) ?>"
     data-ack-url="<?= e(url('banners.ack', ['id' => $banner['id']])) ?>">
    <span class="eb-icon"><?= $banner['severity'] === 'critical' ? '🚨' : ($banner['severity'] === 'warning' ? '⚠️' : 'ℹ️') ?></span>
    <span class="eb-message">
        <?= e((string) $banner['message']) ?>
        <?php if (!empty($banner['link_url'])): ?>
        <a href="<?= e((string) $banner['link_url']) ?>" class="eb-link"><?= e((string) ($banner['link_label'] ?: 'Learn more')) ?></a>
        <?php endif; ?>
    </span>
    <?php if ($needsAck): ?>
    <button type="button" class="btn btn-sm eb-ack" data-id="<?= (int) $banner['id'] ?>">I understand</button>
    <?php elseif ((int) $banner['dismissible'] === 1): ?>
    <button type="button" class="eb-dismiss" data-id="<?= (int) $banner['id'] ?>" aria-label="Dismiss">&times;</button>
    <?php endif; ?>
</div>
<?php endforeach; ?>
