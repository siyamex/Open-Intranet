<?php
use App\Core\Settings;
use App\Core\View;
?>
<div class="page-head">
    <h1>Settings</h1>
</div>

<div class="tabs">
    <?php foreach ($tabs as $t): ?>
    <a class="tab <?= $t === $tab ? 'active' : '' ?>" href="<?= e(url('admin.settings') . '?tab=' . $t) ?>"><?= e(ucfirst($t)) ?></a>
    <?php endforeach; ?>
</div>

<div class="card" style="max-width:760px;">
<?php if ($tab === 'general'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="tab" value="general">
        <div class="form-group">
            <label class="form-label" for="site_name">Site name</label>
            <input class="form-control" id="site_name" name="site_name" value="<?= e((string) Settings::get('site_name', 'OpenIntranet')) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="site_tagline">Tagline</label>
            <input class="form-control" id="site_tagline" name="site_tagline" value="<?= e((string) Settings::get('site_tagline', '')) ?>">
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="logo">Logo <span class="text-muted">(PNG/JPG/WebP, max 2 MB)</span></label>
                <?php $logo = Settings::get('logo_path'); ?>
                <?php if (is_string($logo) && $logo !== ''): ?><img src="<?= e(base_url($logo)) ?>" alt="" style="max-height:40px; display:block; margin-bottom:0.4rem;"><?php endif; ?>
                <input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
            </div>
            <div class="form-group">
                <label class="form-label" for="favicon">Favicon</label>
                <?php $fav = Settings::get('favicon_path'); ?>
                <?php if (is_string($fav) && $fav !== ''): ?><img src="<?= e(base_url($fav)) ?>" alt="" style="height:24px; display:block; margin-bottom:0.4rem;"><?php endif; ?>
                <input class="form-control" type="file" id="favicon" name="favicon" accept="image/png,image/jpeg,image/webp">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="timezone">Timezone</label>
                <select class="form-control searchable" id="timezone" name="timezone">
                    <?php foreach ($timezones as $tz): ?>
                    <option value="<?= e($tz) ?>" <?= (string) Settings::get('timezone', 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="date_format">Date format <span class="text-muted">(PHP format, e.g. j M Y)</span></label>
                <input class="form-control" id="date_format" name="date_format" value="<?= e((string) Settings::get('date_format', 'j M Y')) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save general settings</button>
    </form>

<?php elseif ($tab === 'homepage'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="homepage">
        <p class="text-muted">Drag to reorder the dashboard sections; untick to hide.</p>
        <?php
        $active = (array) Settings::get('homepage_sections', ['quick_links', 'news', 'gazette']);
        $labels = ['quick_links' => 'Apps / quick links', 'news' => 'News', 'gazette' => 'Gazette documents'];
        $ordered = array_merge($active, array_diff(array_keys($labels), $active));
        ?>
        <ul class="section-sort" id="section-sort">
            <?php foreach ($ordered as $sec): ?>
            <li draggable="true" data-section="<?= e($sec) ?>">
                <span class="drag-handle"><?= icon('grip-vertical') ?></span>
                <label class="form-check" style="flex:1;">
                    <input type="checkbox" name="sections_enabled[]" value="<?= e($sec) ?>" <?= in_array($sec, $active, true) ? 'checked' : '' ?>>
                    <?= e($labels[$sec] ?? $sec) ?>
                </label>
            </li>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" name="sections_order" id="sections-order" value="">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="news_dashboard_count">News cards on dashboard</label>
                <input class="form-control" type="number" min="1" max="12" id="news_dashboard_count" name="news_dashboard_count" value="<?= (int) Settings::get('news_dashboard_count', 6) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="gazette_dashboard_count">Gazette items on dashboard</label>
                <input class="form-control" type="number" min="1" max="12" id="gazette_dashboard_count" name="gazette_dashboard_count" value="<?= (int) Settings::get('gazette_dashboard_count', 5) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save homepage settings</button>
    </form>

<?php elseif ($tab === 'authentication'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="authentication">
        <label class="form-check" style="margin-bottom:1rem;">
            <input type="checkbox" name="allow_local_login" value="1" <?= Settings::get('allow_local_login', true) ? 'checked' : '' ?>>
            Allow local email + password sign-in <span class="text-muted">(super admins always can)</span>
        </label>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="session_lifetime_minutes">Session lifetime (minutes of inactivity)</label>
                <input class="form-control" type="number" min="10" max="43200" id="session_lifetime_minutes" name="session_lifetime_minutes" value="<?= (int) Settings::get('session_lifetime_minutes', 120) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="password_min_length">Minimum password length</label>
                <input class="form-control" type="number" min="8" max="64" id="password_min_length" name="password_min_length" value="<?= (int) Settings::get('password_min_length', 10) ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:320px;">
            <label class="form-label" for="sso_auto_redirect">Auto-redirect login to SSO provider</label>
            <select class="form-control" id="sso_auto_redirect" name="sso_auto_redirect">
                <option value="">— disabled —</option>
                <?php foreach ($providers as $p): ?>
                <option value="<?= e((string) $p['slug']) ?>" <?= (string) Settings::get('sso_auto_redirect', '') === $p['slug'] ? 'selected' : '' ?>><?= e((string) $p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save authentication settings</button>
    </form>

<?php elseif ($tab === 'mail'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="mail">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="smtp_host">SMTP host</label>
                <input class="form-control" id="smtp_host" name="smtp_host" value="<?= e((string) Settings::get('smtp_host', '')) ?>" placeholder="smtp.example.com">
            </div>
            <div class="form-group">
                <label class="form-label" for="smtp_port">Port <span class="text-muted">(587 STARTTLS / 465 TLS)</span></label>
                <input class="form-control" type="number" id="smtp_port" name="smtp_port" value="<?= (int) Settings::get('smtp_port', 587) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="smtp_user">Username</label>
                <input class="form-control" id="smtp_user" name="smtp_user" value="<?= e((string) Settings::get('smtp_user', '')) ?>" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label" for="smtp_pass">Password</label>
                <input class="form-control" type="password" id="smtp_pass" name="smtp_pass" autocomplete="new-password"
                       placeholder="<?= Settings::get('smtp_pass_encrypted') ? '•••• set — type to replace' : '' ?>">
                <p class="form-hint">Stored encrypted; never redisplayed.</p>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="smtp_from">From address</label>
            <input class="form-control" id="smtp_from" name="smtp_from" value="<?= e((string) Settings::get('smtp_from', '')) ?>" placeholder="OpenIntranet &lt;no-reply@example.com&gt;">
        </div>
        <button type="submit" class="btn btn-primary">Save mail settings</button>
    </form>
    <form method="post" action="<?= e(url('admin.settings.test-mail')) ?>" style="margin-top:0.75rem;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary">Send test email to me</button>
    </form>

<?php elseif ($tab === 'uploads'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="uploads">
        <div class="form-group" style="max-width:240px;">
            <label class="form-label" for="upload_max_mb">Max upload size (MB)</label>
            <input class="form-control" type="number" min="1" max="512" id="upload_max_mb" name="upload_max_mb" value="<?= (int) Settings::get('upload_max_mb', 20) ?>">
            <p class="form-hint">Also constrained by php.ini upload_max_filesize (<?= e(ini_get('upload_max_filesize') ?: '?') ?>).</p>
        </div>
        <div class="form-group">
            <label class="form-label">Allowed document types</label>
            <?php $allowed = (array) Settings::get('allowed_doc_types', ['pdf', 'docx', 'xlsx', 'pptx', 'png', 'jpg', 'zip']); ?>
            <div class="checkbox-row">
                <?php foreach (['pdf', 'docx', 'xlsx', 'pptx', 'png', 'jpg', 'zip'] as $type): ?>
                <label class="form-check"><input type="checkbox" name="allowed_doc_types[]" value="<?= $type ?>" <?= in_array($type, $allowed, true) ? 'checked' : '' ?>> .<?= $type ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save upload settings</button>
    </form>

<?php elseif ($tab === 'modules'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="modules">
        <p class="text-muted">Disabled modules disappear from menus, dashboards and their routes return 404.</p>
        <?php $moduleLabels = ['news' => 'News', 'documents' => 'Documents & gazette', 'directory' => 'Employee directory', 'org_chart' => 'Org chart', 'comments' => 'News comments', 'reactions' => 'Emoji reactions']; ?>
        <?php foreach ($modules as $slug => $enabled): ?>
        <label class="form-check" style="margin-bottom:0.6rem;">
            <input type="checkbox" name="modules[<?= e($slug) ?>]" value="1" <?= $enabled ? 'checked' : '' ?>>
            <?= e($moduleLabels[$slug] ?? $slug) ?>
        </label>
        <?php endforeach; ?>
        <div style="margin-top:0.75rem;"><button type="submit" class="btn btn-primary">Save modules</button></div>
    </form>

<?php elseif ($tab === 'advanced'): ?>
    <form method="post" action="<?= e(url('admin.settings.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="tab" value="advanced">
        <label class="form-check" style="margin-bottom:0.6rem;">
            <input type="checkbox" name="maintenance_mode" value="1" <?= Settings::get('maintenance_mode', false) ? 'checked' : '' ?>>
            <strong>Maintenance mode</strong> <span class="text-muted">(admins bypass; everyone else sees the message below)</span>
        </label>
        <div class="form-group">
            <label class="form-label" for="maintenance_message">Maintenance message</label>
            <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="2"><?= e((string) Settings::get('maintenance_message', '')) ?></textarea>
        </div>
        <div class="form-group" style="max-width:240px;">
            <label class="form-label" for="audit_retention_days">Audit log retention (days)</label>
            <input class="form-control" type="number" min="7" max="3650" id="audit_retention_days" name="audit_retention_days" value="<?= (int) Settings::get('audit_retention_days', 365) ?>">
            <p class="form-hint">Applied by <code>php cli.php audit:prune</code> (cron).</p>
        </div>
        <div class="checkbox-row" style="margin-bottom:1rem;">
            <label class="form-check"><input type="checkbox" name="comments_enabled" value="1" <?= Settings::get('comments_enabled', true) ? 'checked' : '' ?>> News comments</label>
            <label class="form-check"><input type="checkbox" name="reactions_enabled" value="1" <?= Settings::get('reactions_enabled', true) ? 'checked' : '' ?>> Emoji reactions</label>
        </div>
        <button type="submit" class="btn btn-primary">Save advanced settings</button>
    </form>
<?php endif; ?>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-settings.js')) ?>"></script>
<?php View::end(); ?>
