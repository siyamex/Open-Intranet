<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Default dashboard layout</h1>
    <p><a href="<?= e(url('admin.widgets')) ?>">&larr; Back to widgets</a></p>
</div>

<div class="tabs">
    <a class="tab <?= $roleId === null ? 'active' : '' ?>" href="<?= e(url('admin.widgets.layout') . '?role=default') ?>">Global default</a>
    <?php foreach ($roles as $role): ?>
    <a class="tab <?= $roleId === (int) $role['id'] ? 'active' : '' ?>" href="<?= e(url('admin.widgets.layout') . '?role=' . (int) $role['id']) ?>"><?= e((string) $role['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <p class="text-muted">Drag to reorder. Click a tile's width badge to toggle full/half. Widgets not listed here won't appear for this layout.</p>
    <ul class="poll-option-builder" id="layout-builder" style="max-width:none;">
        <?php foreach ($current as $item): ?>
        <li class="layout-item" data-slug="<?= e((string) $item['widget_slug']) ?>" data-width="<?= e((string) $item['width']) ?>" draggable="true">
            <span class="drag-handle"><?= icon('grip-vertical') ?></span>
            <span style="flex:1;"><?= e((string) $item['name']) ?></span>
            <button type="button" class="btn btn-secondary btn-sm width-toggle"><?= e((string) $item['width']) ?></button>
            <button type="button" class="btn btn-danger btn-sm remove-item">&times;</button>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="filter-bar">
        <select class="form-control" id="add-widget-select" style="max-width:260px;">
            <?php foreach ($widgets as $widget): ?>
            <option value="<?= e((string) $widget['slug']) ?>" data-name="<?= e((string) $widget['name']) ?>"><?= e((string) $widget['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-secondary btn-sm" id="add-widget-btn">Add to layout</button>
        <span style="flex:1;"></span>
        <button type="button" class="btn btn-primary" id="save-layout-btn">Save layout</button>
    </div>
</div>

<form method="post" action="<?= e(url('admin.widgets.layout.save')) ?>" id="layout-form" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="role_id" value="<?= $roleId !== null ? (int) $roleId : '' ?>">
    <input type="hidden" name="items" id="layout-items-json">
</form>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-widget-layout.js')) ?>"></script>
<?php View::end(); ?>
