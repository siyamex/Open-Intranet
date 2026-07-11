<div class="page-head">
    <h1>Events</h1>
</div>

<div class="card">
    <h2 id="event-form-title">Create event</h2>
    <form method="post" action="<?= e(url('admin.events.store')) ?>" id="event-form">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="POST" id="event-method">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" id="ev-title" required maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input class="form-control" name="location" id="ev-location" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Starts</label>
                <input class="form-control" type="datetime-local" name="starts_at" id="ev-starts" required>
            </div>
            <div class="form-group">
                <label class="form-label">Ends</label>
                <input class="form-control" type="datetime-local" name="ends_at" id="ev-ends" required>
            </div>
            <div class="form-group">
                <label class="form-label">Color</label>
                <input class="form-control" type="color" name="color" id="ev-color" value="#4f46e5" style="height:40px;">
            </div>
            <div class="form-group">
                <label class="form-label">Repeat</label>
                <select class="form-control" name="recurrence" id="ev-recurrence">
                    <option value="none">Does not repeat</option>
                    <option value="weekly">Weekly (12 months)</option>
                    <option value="monthly">Monthly (12 months)</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="ev-description" rows="2"></textarea>
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
            <label class="form-check"><input type="checkbox" name="all_day" id="ev-allday" value="1"> All-day</label>
            <label class="form-check"><input type="checkbox" name="rsvp_enabled" id="ev-rsvp" value="1" checked> RSVP enabled</label>
        </div>
        <button type="submit" class="btn btn-primary">Save event</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Title</th><th>When</th><th>Repeat</th><th>Going</th><th>By</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
        <tr>
            <td><span class="doc-badge" style="background: <?= e((string) ($event['color'] ?: '#4f46e5')) ?>;">&nbsp;</span>
                <strong><?= e((string) $event['title']) ?></strong></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $event['starts_at']))) ?></td>
            <td><?= e((string) $event['recurrence']) ?></td>
            <td><?= (int) $event['going'] ?></td>
            <td><?= e((string) ($event['creator_name'] ?? '—')) ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('events.show', ['id' => $event['id']])) ?>">View</a>
                <form method="post" action="<?= e(url('admin.events.destroy', ['id' => $event['id']])) ?>" style="display:inline;"
                      data-confirm="Delete this event and its recurrences?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($events === []): ?>
        <tr><td colspan="6" class="text-muted">No events yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
