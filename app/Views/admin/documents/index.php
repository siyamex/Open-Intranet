<div class="page-head">
    <h1>Documents</h1>
    <p class="text-muted">Allowed: <?= e(implode(', ', $allowedTypes)) ?> · max <?= (int) $maxMb ?> MB · content is MIME-verified against the extension.</p>
</div>

<div class="card">
    <h2>Upload document</h2>
    <form method="post" action="<?= e(url('admin.documents.store')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="doc-title">Title</label>
                <input class="form-control" id="doc-title" name="title" required maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="doc-file">File</label>
                <input class="form-control" id="doc-file" name="file" type="file" required
                       accept="<?= e(implode(',', array_map(static fn ($t) => '.' . $t, $allowedTypes))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="doc-category">Category</label>
                <select class="form-control" id="doc-category" name="category_id">
                    <option value="">— none —</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= $c['parent_id'] !== null ? '&nbsp;&nbsp;— ' : '' ?><?= e((string) $c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="doc-description">Description</label>
                <input class="form-control" id="doc-description" name="description" maxlength="2000">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Visible to <span class="text-muted">(none checked = everyone)</span></label>
            <div class="checkbox-row">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <label class="form-check" style="margin-bottom:1rem;"><input type="checkbox" name="is_gazette" value="1"> Show in the Gazette section on the dashboard</label>
        <div><button type="submit" class="btn btn-primary">Upload</button></div>
    </form>
</div>

<div class="card">
    <h2>All documents</h2>
    <form method="post" action="<?= e(url('admin.documents.bulk')) ?>" id="bulk-form">
        <?= csrf_field() ?>
        <?php if ($canManage): ?>
        <div class="filter-bar">
            <select class="form-control" name="bulk_action">
                <option value="move">Move selected to…</option>
                <option value="delete">Delete selected</option>
            </select>
            <select class="form-control" name="move_category_id">
                <option value="">— no category —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Apply the bulk action to the selected documents?');">Apply</button>
        </div>
        <?php endif; ?>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><?php if ($canManage): ?><th></th><?php endif; ?><th>Type</th><th>Title</th><th>Category</th><th>Version</th><th>Size</th><th>Downloads</th><th>Gazette</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($docs as $doc): ?>
            <tr>
                <?php if ($canManage): ?><td><input type="checkbox" name="ids[]" value="<?= (int) $doc['id'] ?>" form="bulk-form"></td><?php endif; ?>
                <td><?php partial('partials/doc-type-badge', ['doc' => $doc]); ?></td>
                <td><strong><?= e((string) $doc['title']) ?></strong><br><span class="text-muted" style="font-size:0.8rem;"><?= e((string) $doc['original_name']) ?></span></td>
                <td><?= e((string) ($doc['category_name'] ?? '—')) ?></td>
                <td><span class="badge">v<?= (int) $doc['version'] ?></span><?php if ((int) $doc['old_versions'] > 0): ?> <span class="text-muted">(<?= (int) $doc['old_versions'] ?> old)</span><?php endif; ?></td>
                <td><?= e(format_bytes((int) $doc['size_bytes'])) ?></td>
                <td><?= (int) $doc['download_count'] ?></td>
                <td><?= (int) $doc['is_gazette'] === 1 ? '✓' : '—' ?></td>
                <td style="white-space:nowrap;">
                    <a class="btn btn-secondary btn-sm" href="<?= e(url('files.serve', ['uuid' => $doc['uuid']])) ?>">Get</a>
                    <?php if ($canManage): ?>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.documents.versions', ['id' => $doc['id']])) ?>">Versions</a>
                    <button type="button" class="btn btn-secondary btn-sm" data-doc-edit='<?= e(json_encode($doc, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>Edit</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if ($docs === []): ?>
            <tr><td colspan="9" class="text-muted">No documents uploaded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </form>
</div>

<?php if ($canManage): ?>
<div class="card">
    <h2>Categories</h2>
    <form method="post" action="<?= e(url('admin.doc-categories.store')) ?>" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" name="name" placeholder="Category name" required style="max-width:220px;">
        <select class="form-control" name="parent_id" style="max-width:220px;">
            <option value="">Top level</option>
            <?php foreach ($categories as $c): ?>
                <?php if ($c['parent_id'] === null): ?>
                <option value="<?= (int) $c['id'] ?>">Under: <?= e((string) $c['name']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Add category</button>
    </form>
    <?php if ($categories !== []): ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Parent</th><th>Visibility</th><th></th></tr></thead>
        <tbody>
        <?php $catNames = array_column($categories, 'name', 'id'); ?>
        <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= e((string) $c['name']) ?></td>
            <td><?= $c['parent_id'] !== null ? e((string) ($catNames[$c['parent_id']] ?? '—')) : '—' ?></td>
            <td><?= $c['visible_to'] === null ? 'Everyone' : e(implode(', ', (array) json_decode((string) $c['visible_to'], true))) ?></td>
            <td>
                <form method="post" action="<?= e(url('admin.doc-categories.destroy', ['id' => $c['id']])) ?>" style="display:inline;"
                      onsubmit="return confirm('Delete this category? Documents inside are kept without a category.');">
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

<dialog id="doc-modal" class="modal">
    <form method="post" id="doc-form" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">
        <h2>Edit document</h2>
        <div class="form-group">
            <label class="form-label" for="edit-title">Title</label>
            <input class="form-control" id="edit-title" name="title" required maxlength="255">
        </div>
        <div class="form-group">
            <label class="form-label" for="edit-description">Description</label>
            <input class="form-control" id="edit-description" name="description" maxlength="2000">
        </div>
        <div class="form-group">
            <label class="form-label" for="edit-category">Category</label>
            <select class="form-control" id="edit-category" name="category_id">
                <option value="">— none —</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Visible to</label>
            <div class="checkbox-row" id="edit-roles">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <label class="form-check" style="margin-bottom:1rem;"><input type="checkbox" name="is_gazette" id="edit-gazette" value="1"> Show in the Gazette</label>
        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</dialog>

<?php \App\Core\View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-documents.js')) ?>"></script>
<?php \App\Core\View::end(); ?>
<?php endif; ?>
