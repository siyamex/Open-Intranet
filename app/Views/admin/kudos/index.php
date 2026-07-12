<div class="page-head">
    <h1>Kudos moderation</h1>
</div>

<div class="card">
    <h2>Settings</h2>
    <div class="form-group">
        <label class="form-label">Value tags <span class="text-muted">(click to enable/disable)</span></label>
        <div class="checkbox-row">
            <?php foreach ($values as $value): ?>
            <form method="post" action="<?= e(url('admin.kudos.value.toggle', ['id' => $value['id']])) ?>" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm <?= (int) $value['is_active'] === 1 ? 'btn-secondary' : 'btn-danger' ?>">
                    <?= e((string) ($value['emoji'] ?? '')) ?> <?= e((string) $value['label']) ?><?= (int) $value['is_active'] === 0 ? ' (off)' : '' ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
    <form method="post" action="<?= e(url('admin.kudos.settings')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Banned words <span class="text-muted">(comma separated — messages containing them are rejected)</span></label>
            <input class="form-control" name="banned_words" value="<?= e($bannedWords) ?>" placeholder="word1, word2">
        </div>
        <div class="filter-bar" style="margin-bottom:1rem;">
            <input class="form-control" name="new_value" placeholder="New value tag" maxlength="50" style="max-width:220px;">
            <input class="form-control" name="new_value_emoji" placeholder="Emoji" maxlength="8" style="max-width:90px;">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>When</th><th>From → To</th><th>Value</th><th>Message</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $kudos): ?>
        <tr <?= (int) $kudos['is_hidden'] === 1 ? 'style="opacity:0.5;"' : '' ?>>
            <td style="white-space:nowrap;"><?= e(date('j M H:i', strtotime((string) $kudos['created_at']))) ?></td>
            <td><?= e((string) ($kudos['sender_name'] ?? '—')) ?> → <?= e((string) $kudos['recipient_name']) ?></td>
            <td><?= e((string) ($kudos['value_label'] ?? '—')) ?></td>
            <td><?= e((string) $kudos['message']) ?></td>
            <td style="white-space:nowrap;">
                <form method="post" action="<?= e(url('admin.kudos.hide', ['id' => $kudos['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm"><?= (int) $kudos['is_hidden'] === 1 ? 'Unhide' : 'Hide' ?></button>
                </form>
                <form method="post" action="<?= e(url('admin.kudos.destroy', ['id' => $kudos['id']])) ?>" style="display:inline;"
                      data-confirm="Delete this kudos permanently?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
        <tr><td colspan="5" class="text-muted">No kudos yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
