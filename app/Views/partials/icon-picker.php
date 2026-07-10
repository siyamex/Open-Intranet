<?php
/**
 * Reusable icon picker modal. Expects $icons (string[] of icon names).
 * Open it from JS: IconPicker.open(callback) or via [data-icon-picker="inputId"].
 */
?>
<dialog id="icon-picker-modal" class="modal">
    <h2>Choose an icon</h2>
    <input type="search" class="form-control" id="icon-picker-search" placeholder="Search icons…" autocomplete="off">
    <div class="icon-grid" id="icon-picker-grid">
        <?php foreach ($icons as $iconName): ?>
        <button type="button" class="icon-cell" data-icon-name="<?= e($iconName) ?>" title="<?= e($iconName) ?>">
            <?= icon($iconName) ?>
            <span><?= e($iconName) ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <div style="display:flex; justify-content:flex-end; margin-top:0.75rem;">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
    </div>
</dialog>
