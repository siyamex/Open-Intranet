<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Menus</h1>
    <p class="text-muted">Drag items to reorder or nest them one level deep. Changes apply instantly for all users.</p>
</div>

<div class="tabs">
    <?php foreach ($locations as $loc): ?>
    <a class="tab <?= $loc === $location ? 'active' : '' ?>" href="<?= e(url('admin.menus') . '?location=' . $loc) ?>"><?= e(ucfirst($loc)) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <h2 style="margin:0;"><?= e(ucfirst($location)) ?> items</h2>
        <button type="button" class="btn btn-primary" data-menu-add>Add item</button>
    </div>

    <?php if ($tree === []): ?>
        <p class="text-muted">No items yet — add the first one.</p>
    <?php else: ?>
    <ul class="menu-tree" id="menu-tree"
        data-reorder-url="<?= e(url('admin.menus.reorder')) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach ($tree as $item): ?>
        <li class="menu-node" data-id="<?= (int) $item['id'] ?>" draggable="true">
            <div class="menu-row <?= (int) $item['enabled'] === 0 ? 'disabled' : '' ?>">
                <span class="drag-handle"><?= icon('grip-vertical') ?></span>
                <?= icon((string) ($item['icon'] ?? 'link')) ?>
                <strong><?= e((string) $item['label']) ?></strong>
                <span class="text-muted menu-url"><?= e((string) ($item['route_name'] ?? $item['url'] ?? '')) ?></span>
                <?php if ((int) $item['enabled'] === 0): ?><span class="badge" style="background:var(--color-text-muted);">disabled</span><?php endif; ?>
                <?php if ($item['visible_to'] !== null): ?><span class="badge">restricted</span><?php endif; ?>
                <span style="flex:1;"></span>
                <button type="button" class="btn btn-secondary btn-sm" data-menu-edit='<?= e(json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>Edit</button>
                <form method="post" action="<?= e(url('admin.menus.destroy', ['id' => $item['id']])) ?>"
                      onsubmit="return confirm('Delete this item<?= $item['children'] !== [] ? ' and its sub-items' : '' ?>?');" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
            <ul class="menu-children">
                <?php foreach ($item['children'] as $child): ?>
                <li class="menu-node" data-id="<?= (int) $child['id'] ?>" draggable="true">
                    <div class="menu-row <?= (int) $child['enabled'] === 0 ? 'disabled' : '' ?>">
                        <span class="drag-handle"><?= icon('grip-vertical') ?></span>
                        <?= icon((string) ($child['icon'] ?? 'link')) ?>
                        <strong><?= e((string) $child['label']) ?></strong>
                        <span class="text-muted menu-url"><?= e((string) ($child['route_name'] ?? $child['url'] ?? '')) ?></span>
                        <?php if ((int) $child['enabled'] === 0): ?><span class="badge" style="background:var(--color-text-muted);">disabled</span><?php endif; ?>
                        <span style="flex:1;"></span>
                        <button type="button" class="btn btn-secondary btn-sm" data-menu-edit='<?= e(json_encode($child, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>Edit</button>
                        <form method="post" action="<?= e(url('admin.menus.destroy', ['id' => $child['id']])) ?>"
                              onsubmit="return confirm('Delete this item?');" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                    <ul class="menu-children"></ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
    <p class="form-hint">Tip: drop an item onto another item's area to nest it; drop it between top-level rows to un-nest.</p>
    <?php endif; ?>
</div>

<!-- Add/edit modal -->
<dialog id="menu-modal" class="modal">
    <form method="post" id="menu-form" action="<?= e(url('admin.menus.store')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="POST" id="menu-form-method">
        <input type="hidden" name="location" value="<?= e($location) ?>">
        <input type="hidden" name="parent_id" id="menu-parent-id" value="">
        <h2 id="menu-modal-title">Add menu item</h2>
        <div class="form-group">
            <label class="form-label" for="menu-label">Label</label>
            <input class="form-control" id="menu-label" name="label" required maxlength="100">
        </div>
        <div class="form-group">
            <label class="form-label">Icon</label>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <span id="menu-icon-preview" class="icon-preview"></span>
                <input type="hidden" name="icon" id="menu-icon">
                <button type="button" class="btn btn-secondary btn-sm" data-icon-picker="menu-icon">Choose icon</button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="menu-route">Internal page</label>
            <select class="form-control" id="menu-route" name="route_name">
                <option value="">— custom URL below —</option>
                <?php foreach ($routeOptions as $name => $label): ?>
                <option value="<?= e($name) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="menu-url">URL <span class="text-muted">(path like /news or full https:// link)</span></label>
            <input class="form-control" id="menu-url" name="url" maxlength="500">
        </div>
        <div class="form-group">
            <label class="form-label" for="menu-target">Open in</label>
            <select class="form-control" id="menu-target" name="target">
                <option value="_self">Same tab</option>
                <option value="_blank">New tab</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Visible to <span class="text-muted">(none checked = everyone)</span></label>
            <div class="checkbox-row" id="menu-roles">
                <?php foreach ($roles as $role): ?>
                <label class="form-check"><input type="checkbox" name="visible_to[]" value="<?= e((string) $role['slug']) ?>"> <?= e((string) $role['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" name="enabled" id="menu-enabled" value="1" checked>
            <label for="menu-enabled">Enabled</label>
        </div>
        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</dialog>

<?php partial('partials/icon-picker', ['icons' => $icons]); ?>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-menus.js')) ?>"></script>
<?php View::end(); ?>
