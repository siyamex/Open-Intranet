<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Polls</h1>
</div>

<div class="card">
    <h2>Create a poll</h2>
    <form method="post" action="<?= e(url('admin.polls.store')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Question</label>
            <input class="form-control" name="question" required maxlength="255" value="<?= e((string) old('question')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Options <span class="text-muted">(drag ⠿ to reorder)</span></label>
            <ul class="poll-option-builder" id="poll-options">
                <li draggable="true"><span class="drag-handle"><?= icon('grip-vertical') ?></span><input class="form-control" name="options[]" placeholder="Option 1" required></li>
                <li draggable="true"><span class="drag-handle"><?= icon('grip-vertical') ?></span><input class="form-control" name="options[]" placeholder="Option 2" required></li>
            </ul>
            <button type="button" class="btn btn-secondary btn-sm" id="add-poll-option">Add option</button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select class="form-control" name="type">
                    <option value="single">Single choice</option>
                    <option value="multiple">Multiple choice</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Target department <span class="text-muted">(optional)</span></label>
                <select class="form-control" name="department_id">
                    <option value="">Everyone</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= e((string) $d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Opens <span class="text-muted">(empty = now)</span></label>
                <input class="form-control" type="datetime-local" name="opens_at">
            </div>
            <div class="form-group">
                <label class="form-label">Closes <span class="text-muted">(empty = manual)</span></label>
                <input class="form-control" type="datetime-local" name="closes_at">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Visible to roles <span class="text-muted">(none = everyone)</span></label>
            <div class="checkbox-row">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <label class="form-check" style="margin-bottom:1rem;">
            <input type="checkbox" name="is_anonymous" value="1"> Anonymous voting <span class="text-muted">(who-voted is never stored)</span>
        </label>
        <div><button type="submit" class="btn btn-primary">Create poll</button></div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Question</th><th>Type</th><th>Window</th><th>Voters</th><th>By</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($polls as $poll): ?>
        <?php $closed = $poll['closes_at'] !== null && strtotime((string) $poll['closes_at']) <= time(); ?>
        <tr>
            <td>
                <strong><?= e((string) $poll['question']) ?></strong>
                <?php if ((int) $poll['is_anonymous'] === 1): ?><span class="badge">anon</span><?php endif; ?>
                <?php if ($closed): ?><span class="badge" style="background:var(--color-text-muted);">closed</span><?php endif; ?>
            </td>
            <td><?= e((string) $poll['type']) ?></td>
            <td class="text-muted" style="font-size:0.85rem;">
                <?= $poll['opens_at'] !== null ? e(date('j M H:i', strtotime((string) $poll['opens_at']))) : 'now' ?> →
                <?= $poll['closes_at'] !== null ? e(date('j M H:i', strtotime((string) $poll['closes_at']))) : 'open' ?>
            </td>
            <td><?= (int) $poll['voters'] ?></td>
            <td><?= e((string) ($poll['creator_name'] ?? '—')) ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.polls.results', ['id' => $poll['id']])) ?>">Results</a>
                <?php if (!$closed): ?>
                <form method="post" action="<?= e(url('admin.polls.close', ['id' => $poll['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Close</button>
                </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('admin.polls.destroy', ['id' => $poll['id']])) ?>" style="display:inline;"
                      data-confirm="Delete this poll and all its votes?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($polls === []): ?>
        <tr><td colspan="6" class="text-muted">No polls yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-polls.js')) ?>"></script>
<?php View::end(); ?>
