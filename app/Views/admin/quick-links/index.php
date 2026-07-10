<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Quick Links</h1>
    <p class="text-muted">The app launcher tiles on the dashboard. Drag to set the global order.</p>
</div>

<div class="card">
    <div class="filter-bar">
        <a class="btn <?= $view === 'grid' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" href="<?= e(url('admin.quick-links') . '?view=grid') ?>">Grid</a>
        <a class="btn <?= $view === 'table' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" href="<?= e(url('admin.quick-links') . '?view=table') ?>">Table</a>
        <span style="flex:1;"></span>
        <button type="button" class="btn btn-primary" data-ql-add>Add link</button>
    </div>

    <?php if ($links === []): ?>
        <p class="text-muted">No quick links yet.</p>
    <?php elseif ($view === 'grid'): ?>
    <div class="ql-grid admin" id="admin-ql-grid" data-reorder-url="<?= e(url('admin.quick-links.reorder')) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach ($links as $link): ?>
        <div class="ql-tile <?= (int) $link['is_active'] === 0 ? 'inactive' : '' ?>" draggable="true" data-id="<?= (int) $link['id'] ?>">
            <span class="ql-icon" style="background: <?= e((string) ($link['bg_color'] ?? '#4f46e5')) ?>;">
                <?php if ($link['icon_type'] === 'upload' && $link['icon_value'] !== null): ?>
                    <img src="<?= e(url('qlicon', ['file' => $link['icon_value']])) ?>" alt="">
                <?php else: ?>
                    <?= icon((string) ($link['icon_value'] ?? 'link'), 'icon ql-svg') ?>
                <?php endif; ?>
            </span>
            <span class="ql-label"><?= e((string) $link['title']) ?></span>
            <canvas class="ql-spark" width="120" height="26" data-series="<?= e(json_encode($link['spark'])) ?>"></canvas>
            <span class="text-muted" style="font-size:0.75rem;"><?= (int) $link['clicks_30d'] ?> clicks / 30d · <?= (int) $link['click_count'] ?> total</span>
            <div style="display:flex; gap:0.3rem;">
                <button type="button" class="btn btn-secondary btn-sm" data-ql-edit='<?= e(json_encode($link, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>Edit</button>
                <form method="post" action="<?= e(url('admin.quick-links.destroy', ['id' => $link['id']])) ?>" onsubmit="return confirm('Delete this link?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Title</th><th>URL</th><th>Visibility</th><th>Active</th><th>Clicks (30d / total)</th><th>Trend</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($links as $link): ?>
        <tr>
            <td><strong><?= e((string) $link['title']) ?></strong></td>
            <td><a href="<?= e((string) $link['url']) ?>" target="_blank" rel="noopener"><?= e(mb_strimwidth((string) $link['url'], 0, 40, '…')) ?></a></td>
            <td><?= $link['visible_to'] === null ? 'Everyone' : e(implode(', ', (array) json_decode((string) $link['visible_to'], true))) ?></td>
            <td><?= (int) $link['is_active'] === 1 ? '✓' : '—' ?></td>
            <td><?= (int) $link['clicks_30d'] ?> / <?= (int) $link['click_count'] ?></td>
            <td><canvas class="ql-spark" width="120" height="26" data-series="<?= e(json_encode($link['spark'])) ?>"></canvas></td>
            <td style="white-space:nowrap;">
                <button type="button" class="btn btn-secondary btn-sm" data-ql-edit='<?= e(json_encode($link, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>Edit</button>
                <form method="post" action="<?= e(url('admin.quick-links.destroy', ['id' => $link['id']])) ?>" style="display:inline;" onsubmit="return confirm('Delete this link?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<dialog id="ql-modal" class="modal">
    <form method="post" id="ql-form" action="<?= e(url('admin.quick-links.store')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="POST" id="ql-form-method">
        <h2 id="ql-modal-title">Add quick link</h2>
        <div class="form-group">
            <label class="form-label" for="ql-title">Title</label>
            <input class="form-control" id="ql-title" name="title" required maxlength="150">
        </div>
        <div class="form-group">
            <label class="form-label" for="ql-url">URL</label>
            <input class="form-control" id="ql-url" name="url" type="url" required maxlength="500" placeholder="https://…">
        </div>
        <div class="form-group">
            <label class="form-label" for="ql-description">Description <span class="text-muted">(tooltip)</span></label>
            <input class="form-control" id="ql-description" name="description" maxlength="255">
        </div>
        <div class="form-group">
            <label class="form-label">Icon</label>
            <div class="checkbox-row" style="margin-bottom:0.5rem;">
                <label class="form-check"><input type="radio" name="icon_type" value="library" id="ql-icon-lib" checked> Icon library</label>
                <label class="form-check"><input type="radio" name="icon_type" value="upload" id="ql-icon-up"> Upload (SVG/PNG)</label>
            </div>
            <div id="ql-icon-library-row" style="display:flex; gap:0.5rem; align-items:center;">
                <span id="ql-icon-library-preview" class="icon-preview"></span>
                <input type="hidden" name="icon_library" id="ql-icon-library">
                <button type="button" class="btn btn-secondary btn-sm" data-icon-picker="ql-icon-library">Choose icon</button>
            </div>
            <div id="ql-icon-upload-row" hidden>
                <input class="form-control" type="file" name="icon_file" accept=".svg,image/svg+xml,image/png,image/jpeg,image/webp">
                <p class="form-hint">SVGs are sanitized on upload; bitmaps are resized to 128 px.</p>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Background color</label>
            <div class="palette-row" id="ql-palette">
                <?php foreach (['#4f46e5', '#0ea5e9', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0f766e', '#334155'] as $swatch): ?>
                <button type="button" class="swatch" data-color="<?= $swatch ?>" style="background:<?= $swatch ?>;" aria-label="<?= $swatch ?>"></button>
                <?php endforeach; ?>
                <input type="color" id="ql-color-custom" value="#4f46e5" aria-label="Custom color">
            </div>
            <input type="hidden" name="bg_color" id="ql-bg-color" value="#4f46e5">
        </div>
        <div class="form-group">
            <label class="form-label">Visible to <span class="text-muted">(none checked = everyone)</span></label>
            <div class="checkbox-row" id="ql-roles">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="checkbox-row">
            <label class="form-check"><input type="checkbox" name="is_active" id="ql-active" value="1" checked> Active</label>
            <label class="form-check"><input type="checkbox" name="open_new_tab" id="ql-newtab" value="1" checked> Open in new tab</label>
        </div>
        <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</dialog>

<?php partial('partials/icon-picker', ['icons' => $icons]); ?>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-menus.js')) ?>"></script>
<script src="<?= e(asset('js/admin-quick-links.js')) ?>"></script>
<?php View::end(); ?>
