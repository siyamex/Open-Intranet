<div class="page-head">
    <h1>Emergency Banners</h1>
    <p class="text-muted">Shown above the navbar on every page, checked once per request (30s cache).</p>
</div>

<div class="card">
    <h2>New banner</h2>
    <form method="post" action="<?= e(url('admin.banners.store')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Message</label>
            <input class="form-control" name="message" required maxlength="500">
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Severity</label>
                <select class="form-control" name="severity">
                    <option value="info">Info</option>
                    <option value="warning" selected>Warning</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Link URL <span class="text-muted">(optional)</span></label>
                <input class="form-control" name="link_url" placeholder="/documents or https://…">
            </div>
            <div class="form-group">
                <label class="form-label">Link label</label>
                <input class="form-control" name="link_label" placeholder="Learn more">
            </div>
            <div class="form-group">
                <label class="form-label">Starts <span class="text-muted">(empty = now)</span></label>
                <input class="form-control" type="datetime-local" name="starts_at">
            </div>
            <div class="form-group">
                <label class="form-label">Ends <span class="text-muted">(empty = manual)</span></label>
                <input class="form-control" type="datetime-local" name="ends_at">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Visible to <span class="text-muted">(none = everyone)</span></label>
            <div class="checkbox-row">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="checkbox-row" style="margin-bottom:1rem;">
            <label class="form-check"><input type="checkbox" name="dismissible" value="1" checked> Dismissible (per browser)</label>
            <label class="form-check"><input type="checkbox" name="require_ack" value="1"> Require "I understand" acknowledgement</label>
        </div>
        <button type="submit" class="btn btn-primary">Publish banner</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Message</th><th>Severity</th><th>Window</th><th>Ack</th><th>By</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $banner): ?>
        <?php $ended = $banner['ends_at'] !== null && strtotime((string) $banner['ends_at']) <= time(); ?>
        <tr <?= $ended ? 'style="opacity:0.5;"' : '' ?>>
            <td><?= e((string) $banner['message']) ?></td>
            <td><span class="badge status-<?= $banner['severity'] === 'critical' ? 'rejected' : ($banner['severity'] === 'warning' ? 'in_review' : 'submitted') ?>"><?= e((string) $banner['severity']) ?></span></td>
            <td class="text-muted" style="font-size:0.85rem;">
                <?= $banner['starts_at'] !== null ? e(date('j M H:i', strtotime((string) $banner['starts_at']))) : 'now' ?> →
                <?= $banner['ends_at'] !== null ? e(date('j M H:i', strtotime((string) $banner['ends_at']))) : 'manual' ?>
            </td>
            <td><?= (int) $banner['require_ack'] === 1 ? ((int) $banner['ack_count'] . ' acked') : '—' ?></td>
            <td><?= e((string) ($banner['creator_name'] ?? '—')) ?></td>
            <td style="white-space:nowrap;">
                <?php if ((int) $banner['require_ack'] === 1): ?>
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.banners.report', ['id' => $banner['id']])) ?>">Report</a>
                <?php endif; ?>
                <?php if (!$ended): ?>
                <form method="post" action="<?= e(url('admin.banners.end', ['id' => $banner['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">End now</button>
                </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('admin.banners.destroy', ['id' => $banner['id']])) ?>" style="display:inline;" data-confirm="Delete this banner?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($banners === []): ?>
        <tr><td colspan="6" class="text-muted">No banners yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
