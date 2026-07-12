<div class="page-head">
    <h1>My profile</h1>
    <p class="text-muted">Fields your administrator has enabled for self-service are editable below.</p>
</div>

<div class="card" style="max-width:680px;">
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem;">
        <?php partial('partials/avatar', ['person' => $user, 'size' => 72]); ?>
        <div>
            <h2 style="margin:0;"><?= e((string) $user['name']) ?></h2>
            <p class="text-muted" style="margin:0;">
                <?= e((string) ($user['job_title'] ?? '')) ?><?= $department !== null ? ' · ' . e($department) : '' ?>
            </p>
            <p class="text-muted" style="margin:0;"><?= e((string) $user['email']) ?></p>
        </div>
    </div>

    <form method="post" action="<?= e(url('profile.update')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if (in_array('name', $editable, true)): ?>
        <div class="form-group">
            <label class="form-label" for="name">Full name</label>
            <input class="form-control" id="name" name="name" value="<?= e((string) old('name', $user['name'])) ?>" required>
        </div>
        <?php endif; ?>
        <div class="form-grid">
            <?php if (in_array('phone', $editable, true)): ?>
            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="<?= e((string) old('phone', $user['phone'] ?? '')) ?>">
            </div>
            <?php endif; ?>
            <?php if (in_array('location', $editable, true)): ?>
            <div class="form-group">
                <label class="form-label" for="location">Location</label>
                <input class="form-control" id="location" name="location" value="<?= e((string) old('location', $user['location'] ?? '')) ?>">
            </div>
            <?php endif; ?>
            <?php if (in_array('timezone', $editable, true)): ?>
            <div class="form-group">
                <label class="form-label" for="timezone">Timezone</label>
                <select class="form-control" id="timezone" name="timezone">
                    <option value="">— default —</option>
                    <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= (string) old('timezone', $user['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (in_array('avatar', $editable, true)): ?>
            <div class="form-group">
                <label class="form-label" for="avatar">Photo <span class="text-muted">(JPG/PNG/WebP, max 2 MB)</span></label>
                <input class="form-control" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
            <?php endif; ?>
        </div>
        <?php if (in_array('bio', $editable, true)): ?>
        <div class="form-group">
            <label class="form-label" for="bio">About me</label>
            <textarea class="form-control" id="bio" name="bio" rows="3"><?= e((string) old('bio', $user['bio'] ?? '')) ?></textarea>
        </div>
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="birth_date">Birthday <span class="text-muted">(only day + month is ever shown)</span></label>
                <input class="form-control" type="date" id="birth_date" name="birth_date" value="<?= e((string) ($user['birth_date'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="hire_date">Joined the company</label>
                <input class="form-control" type="date" id="hire_date" name="hire_date" value="<?= e((string) ($user['hire_date'] ?? '')) ?>">
            </div>
        </div>
        <label class="form-check" style="margin-bottom:1rem;">
            <input type="checkbox" name="celebrations_opt_out" value="1" <?= (int) ($user['celebrations_opt_out'] ?? 0) === 1 ? 'checked' : '' ?>>
            Don't show my birthday/anniversary on the dashboard
        </label>
        <button type="submit" class="btn btn-primary">Save profile</button>
        <a class="btn btn-secondary" href="<?= e(url('profile.security')) ?>">Security settings</a>
    </form>
</div>

<div class="card" style="max-width:680px;">
    <h2>My skills</h2>
    <p class="text-muted">Shown on your profile and searchable in the directory.</p>
    <div class="dir-skills" style="margin-bottom:0.75rem;">
        <?php foreach ($skills as $skill): ?>
        <form method="post" action="<?= e(url('profile.skills.remove')) ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="skill" value="<?= e((string) $skill) ?>">
            <button type="submit" class="skill-chip skill-removable" title="Remove"><?= e((string) $skill) ?> ×</button>
        </form>
        <?php endforeach; ?>
        <?php if ($skills === []): ?><span class="text-muted">No skills added yet.</span><?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('profile.skills.add')) ?>" class="filter-bar" style="margin:0;">
        <?= csrf_field() ?>
        <input class="form-control" name="skill" placeholder="Add a skill (e.g. Excel, Dhivehi, First Aid)" maxlength="50" required style="max-width:300px;">
        <button type="submit" class="btn btn-secondary">Add skill</button>
    </form>
</div>
