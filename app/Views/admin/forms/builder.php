<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('admin.forms')) ?>">&larr; Back to forms</a></p>
</div>

<form method="post" id="form-builder-form"
      action="<?= $form === null ? e(url('admin.forms.store')) : e(url('admin.forms.update', ['id' => $form['id']])) ?>">
    <?= csrf_field() ?>
    <?php if ($form !== null): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <input type="hidden" name="fields" id="fields-json">

    <div class="editor-layout">
        <div class="card">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Form title</label>
                    <input class="form-control" name="title" required maxlength="150" value="<?= e((string) ($form['title'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input class="form-control" name="description" maxlength="500" value="<?= e((string) ($form['description'] ?? '')) ?>">
                </div>
            </div>

            <label class="form-label">Fields <span class="text-muted">(drag ⠿ to reorder)</span></label>
            <ul id="fb-fields" class="fb-fields" data-fields='<?= e(json_encode($fields, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'></ul>
            <div class="checkbox-row" style="margin-top:0.5rem;">
                <?php foreach (['text' => 'Text', 'textarea' => 'Long text', 'select' => 'Dropdown', 'date' => 'Date', 'file' => 'File', 'checkbox' => 'Checkbox', 'section' => 'Section header'] as $type => $label): ?>
                <button type="button" class="btn btn-secondary btn-sm" data-add-field="<?= $type ?>">+ <?= $label ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="editor-side">
            <div class="card">
                <h3>Approval</h3>
                <div class="form-group">
                    <label class="form-label">Approver</label>
                    <select class="form-control" name="approver_type" id="approver-type">
                        <?php $at = (string) ($form['approver_type'] ?? 'manager'); ?>
                        <option value="manager" <?= $at === 'manager' ? 'selected' : '' ?>>Submitter's manager</option>
                        <option value="user" <?= $at === 'user' ? 'selected' : '' ?>>Specific person</option>
                        <option value="role" <?= $at === 'role' ? 'selected' : '' ?>>Anyone with a role</option>
                    </select>
                </div>
                <div class="form-group" id="approver-user-row">
                    <label class="form-label">Person</label>
                    <select class="form-control searchable" name="approver_user_id">
                        <option value="">— pick —</option>
                        <?php foreach ($people as $person): ?>
                        <option value="<?= (int) $person['id'] ?>" <?= (int) ($form['approver_user_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= e((string) $person['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="approver-role-row">
                    <label class="form-label">Role</label>
                    <select class="form-control" name="approver_role_id">
                        <option value="">— pick —</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (int) ($form['approver_role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card">
                <h3>Publishing</h3>
                <label class="form-check" style="margin-bottom:0.75rem;">
                    <input type="checkbox" name="is_published" value="1" <?= (int) ($form['is_published'] ?? 0) === 1 ? 'checked' : '' ?>>
                    Published (visible in the request catalog)
                </label>
                <div class="form-group">
                    <label class="form-label">Retention <span class="text-muted">(days; decided requests are deleted after — empty = keep forever)</span></label>
                    <input class="form-control" type="number" min="7" max="3650" name="retention_days" value="<?= e((string) ($form['retention_days'] ?? '')) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save form</button>
            </div>
        </aside>
    </div>
</form>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/form-builder.js')) ?>"></script>
<?php View::end(); ?>
