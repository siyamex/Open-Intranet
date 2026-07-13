<div class="page-head">
    <h1>Dashboard Widgets</h1>
</div>

<div class="card">
    <h2>Personalization</h2>
    <form method="post" action="<?= e(url('admin.widgets.settings')) ?>">
        <?= csrf_field() ?>
        <label class="form-check" style="margin-bottom:0.75rem;">
            <input type="checkbox" name="allow_widget_personalization" value="1" <?= $allowPersonalization ? 'checked' : '' ?>>
            Let employees add/remove/reorder their own dashboard widgets
        </label>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<div class="card">
    <h2>Default layouts</h2>
    <p class="text-muted">Design the default dashboard per role — this is what employees see until they personalize (if allowed).</p>
    <div class="checkbox-row">
        <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.widgets.layout') . '?role=default') ?>">Global default</a>
        <?php foreach ($roles as $role): ?>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.widgets.layout') . '?role=' . (int) $role['id']) ?>"><?= e((string) $role['name']) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Add a custom widget</h2>
    <form method="post" action="<?= e(url('admin.widgets.custom.store')) ?>" id="custom-widget-form">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" required maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select class="form-control" name="widget_type" id="custom-widget-type">
                    <option value="html">Custom HTML</option>
                    <option value="rss">RSS feed</option>
                </select>
            </div>
        </div>
        <div class="form-group" id="html-field">
            <label class="form-label">HTML <span class="text-muted">(sanitized on save — same allowlist as news)</span></label>
            <textarea class="form-control" name="html" rows="4"></textarea>
        </div>
        <div class="form-grid" id="rss-fields" hidden>
            <div class="form-group">
                <label class="form-label">Feed URL (https://)</label>
                <input class="form-control" name="rss_url" placeholder="https://example.com/feed.xml">
            </div>
            <div class="form-group">
                <label class="form-label">Item limit</label>
                <input class="form-control" type="number" name="rss_limit" value="5" min="1" max="15">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create widget</button>
    </form>
</div>

<div class="card">
    <h2>All widgets</h2>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Type</th><th>Module</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($widgets as $widget): ?>
        <tr>
            <td><?= e((string) $widget['name']) ?></td>
            <td><?= e((string) $widget['type']) ?></td>
            <td><?= e((string) ($widget['module'] ?? '—')) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin.widgets.toggle', ['slug' => $widget['slug']])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= (int) $widget['is_active'] === 1 ? 'btn-primary' : 'btn-secondary' ?>"><?= (int) $widget['is_active'] === 1 ? 'Active' : 'Inactive' ?></button>
                </form>
            </td>
            <td>
                <?php if ($widget['type'] !== 'builtin'): ?>
                <form method="post" action="<?= e(url('admin.widgets.destroy', ['slug' => $widget['slug']])) ?>" data-confirm="Delete this widget?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
document.getElementById('custom-widget-type').addEventListener('change', function () {
    var isRss = this.value === 'rss';
    document.getElementById('html-field').hidden = isRss;
    document.getElementById('rss-fields').hidden = !isRss;
});
</script>
