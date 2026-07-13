<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e((string) $form['title']) ?></h1>
    <?php if (!empty($form['description'])): ?><p class="text-muted"><?= e((string) $form['description']) ?></p><?php endif; ?>
</div>

<div class="card" style="max-width:680px;">
    <form method="post" action="<?= e(url('requests.submit', ['slug' => $form['slug']])) ?>" enctype="multipart/form-data" id="request-form">
        <?= csrf_field() ?>
        <?php foreach ($fields as $field): ?>
            <?php
            $condAttrs = $field['condition'] !== null
                ? 'data-cond-field="' . e($field['condition']['field']) . '" data-cond-value="' . e($field['condition']['value']) . '"'
                : '';
            ?>
            <?php if ($field['type'] === 'section'): ?>
            <h2 style="margin-top:1.25rem;" <?= $condAttrs ?>><?= e($field['label']) ?></h2>
            <?php else: ?>
            <div class="form-group" <?= $condAttrs ?>>
                <?php if ($field['type'] !== 'checkbox'): ?>
                <label class="form-label"><?= e($field['label']) ?><?= $field['required'] ? ' <span style="color:var(--color-danger);">*</span>' : '' ?></label>
                <?php endif; ?>
                <?php if ($field['type'] === 'text'): ?>
                    <input class="form-control" name="field[<?= e($field['id']) ?>]" data-field="<?= e($field['id']) ?>" <?= $field['required'] ? 'data-req="1"' : '' ?>>
                <?php elseif ($field['type'] === 'textarea'): ?>
                    <textarea class="form-control" rows="3" name="field[<?= e($field['id']) ?>]" data-field="<?= e($field['id']) ?>" <?= $field['required'] ? 'data-req="1"' : '' ?>></textarea>
                <?php elseif ($field['type'] === 'date'): ?>
                    <input class="form-control" type="date" name="field[<?= e($field['id']) ?>]" data-field="<?= e($field['id']) ?>" <?= $field['required'] ? 'data-req="1"' : '' ?>>
                <?php elseif ($field['type'] === 'file'): ?>
                    <input class="form-control" type="file" name="field[<?= e($field['id']) ?>]" data-field="<?= e($field['id']) ?>">
                <?php elseif ($field['type'] === 'select'): ?>
                    <select class="form-control" name="field[<?= e($field['id']) ?>]" data-field="<?= e($field['id']) ?>" <?= $field['required'] ? 'data-req="1"' : '' ?>>
                        <option value="">— select —</option>
                        <?php foreach ($field['options'] as $option): ?>
                        <option value="<?= e($option) ?>"><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field['type'] === 'checkbox'): ?>
                    <label class="form-check">
                        <input type="checkbox" name="field[<?= e($field['id']) ?>]" value="1" data-field="<?= e($field['id']) ?>">
                        <?= e($field['label']) ?><?= $field['required'] ? ' <span style="color:var(--color-danger);">*</span>' : '' ?>
                    </label>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Submit request</button>
    </form>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/form-conditions.js')) ?>"></script>
<?php View::end(); ?>
