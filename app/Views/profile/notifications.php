<div class="page-head">
    <h1>Notification preferences</h1>
    <p class="text-muted">Choose what reaches you in-app and by email.</p>
</div>

<div class="card" style="max-width:640px;">
    <form method="post" action="<?= e(url('profile.notifications.save')) ?>">
        <?= csrf_field() ?>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Event</th><th style="text-align:center;">In-app</th><th style="text-align:center;">Email</th></tr></thead>
            <tbody>
            <?php foreach ($types as $type => $label): ?>
            <tr>
                <td><?= e($label) ?></td>
                <td style="text-align:center;">
                    <input type="checkbox" name="inapp[<?= e($type) ?>]" value="1"
                        <?= ($prefs['notif_' . $type] ?? '1') !== '0' ? 'checked' : '' ?>>
                </td>
                <td style="text-align:center;">
                    <input type="checkbox" name="email[<?= e($type) ?>]" value="1"
                        <?= ($prefs['notif_email_' . $type] ?? '0') === '1' ? 'checked' : '' ?>>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="form-group" style="max-width:280px; margin-top:1rem;">
            <label class="form-label" for="digest_frequency">Email digest of unread notifications</label>
            <select class="form-control" id="digest_frequency" name="digest_frequency">
                <?php $freq = (string) ($prefs['digest_frequency'] ?? 'none'); ?>
                <option value="none" <?= $freq === 'none' ? 'selected' : '' ?>>Off</option>
                <option value="daily" <?= $freq === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= $freq === 'weekly' ? 'selected' : '' ?>>Weekly (Mondays)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save preferences</button>
    </form>
</div>
