<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('admin.users')) ?>">&larr; Back to users</a></p>
</div>

<div class="card" style="max-width:760px;">
    <form method="post" enctype="multipart/form-data"
          action="<?= $user === null ? e(url('admin.users.store')) : e(url('admin.users.update', ['id' => $user['id']])) ?>">
        <?= csrf_field() ?>
        <?php if ($user !== null): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" value="<?= e((string) old('name', $user['name'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= e((string) old('email', $user['email'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="job_title">Job title</label>
                <input class="form-control" id="job_title" name="job_title" value="<?= e((string) old('job_title', $user['job_title'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="<?= e((string) old('phone', $user['phone'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="department_id">Department</label>
                <select class="form-control searchable" id="department_id" name="department_id">
                    <option value="">— none —</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (int) old('department_id', $user['department_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= e((string) $d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="manager_id">Manager</label>
                <select class="form-control searchable" id="manager_id" name="manager_id">
                    <option value="">— none —</option>
                    <?php foreach ($managers as $m): ?>
                        <?php if ($user !== null && (int) $m['id'] === (int) $user['id']) continue; ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (int) old('manager_id', $user['manager_id'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= e((string) $m['name']) ?> (<?= e((string) $m['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="location">Location</label>
                <input class="form-control" id="location" name="location" value="<?= e((string) old('location', $user['location'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="timezone">Timezone</label>
                <select class="form-control searchable" id="timezone" name="timezone">
                    <option value="">— default —</option>
                    <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= (string) old('timezone', $user['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="bio">Bio</label>
            <textarea class="form-control" id="bio" name="bio" rows="3"><?= e((string) old('bio', $user['bio'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Roles</label>
            <div class="checkbox-row">
                <?php foreach ($roles as $r): ?>
                <label class="form-check">
                    <input type="checkbox" name="roles[]" value="<?= (int) $r['id'] ?>" <?= in_array((int) $r['id'], $userRoleIds, true) ? 'checked' : '' ?>>
                    <?= e((string) $r['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (['active', 'inactive', 'suspended'] as $st): ?>
                        <option value="<?= $st ?>" <?= (string) old('status', $user['status'] ?? 'active') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="avatar">Avatar <span class="text-muted">(JPG/PNG/WebP, max 2 MB)</span></label>
                <input class="form-control" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="password"><?= $user === null ? 'Password (optional if sending an invite)' : 'New password (leave blank to keep)' ?></label>
                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password">
            </div>
            <div class="form-group" style="align-self:end;">
                <?php if ($user === null): ?>
                <label class="form-check"><input type="checkbox" name="send_invite" value="1" checked> Send invite email (user sets their own password)</label>
                <?php endif; ?>
                <label class="form-check"><input type="checkbox" name="must_change_password" value="1"> Require password change at next sign-in</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= $user === null ? 'Create user' : 'Save changes' ?></button>
    </form>

    <?php if ($user !== null): ?>
    <hr style="margin:1.5rem 0; border:none; border-top:1px solid var(--color-border);">
    <h3>Actions</h3>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <form method="post" action="<?= e(url('admin.users.force-reset', ['id' => $user['id']])) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary">Force password reset</button>
        </form>
        <form method="post" action="<?= e(url('admin.users.destroy', ['id' => $user['id']])) ?>"
              onsubmit="return confirm('Delete <?= e((string) $user['name']) ?>?\n\nTheir content stays but becomes unattributed:\n<?php foreach ($contentCounts as $label => $count): ?><?= $count ?> <?= e($label) ?>\n<?php endforeach; ?>');">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger">Delete user</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/components.js')) ?>"></script>
<?php View::end(); ?>
