<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e(__('dashboard.welcome', ['name' => explode(' ', trim((string) \App\Core\Auth::user()['name']))[0]])) ?> 👋</h1>
    <?php if ($personalizable): ?>
    <div class="checkbox-row">
        <button type="button" class="btn btn-secondary btn-sm" id="widget-customize">Customize dashboard</button>
        <button type="button" class="btn btn-secondary btn-sm" id="widget-add" hidden>+ Add widget</button>
        <button type="button" class="btn btn-secondary btn-sm" id="widget-reset" hidden>Reset to default</button>
        <button type="button" class="btn btn-primary btn-sm" id="widget-done" hidden>Done</button>
    </div>
    <?php endif; ?>
</div>

<div class="widget-grid" id="widget-grid"
     data-widget-url="<?= e(base_url('widgets')) ?>"
     data-catalog-url="<?= e(url('widgets.catalog')) ?>"
     data-save-url="<?= e(url('widgets.layout.save')) ?>"
     data-reset-url="<?= e(url('widgets.layout.reset')) ?>"
     data-csrf="<?= e(csrf_token()) ?>">
    <?php foreach ($layout as $widget): ?>
    <div class="widget-slot widget-<?= e($widget['width']) ?>" data-slug="<?= e($widget['slug']) ?>" data-width="<?= e($widget['width']) ?>" draggable="false">
        <div class="widget-remove-handle" hidden>
            <span class="drag-handle"><?= icon('grip-vertical') ?></span>
            <span class="widget-name"><?= e($widget['name']) ?></span>
            <button type="button" class="widget-remove" aria-label="Remove widget">&times;</button>
        </div>
        <div class="widget-body">
            <div class="widget-skeleton">
                <div class="skel-line" style="width:40%;"></div>
                <div class="skel-block"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<dialog id="widget-picker-modal" class="modal">
    <h2>Add a widget</h2>
    <ul class="widget-picker-list" id="widget-picker-list"></ul>
    <div style="display:flex; justify-content:flex-end; margin-top:0.75rem;">
        <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
    </div>
</dialog>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/quick-links.js')) ?>"></script>
<script src="<?= e(asset('js/dashboard-widgets.js')) ?>"></script>
<?php View::end(); ?>
