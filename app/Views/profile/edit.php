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
        <button type="submit" class="btn btn-primary">Save profile</button>
        <a class="btn btn-secondary" href="<?= e(url('profile.security')) ?>">Security settings</a>
    </form>
</div>
