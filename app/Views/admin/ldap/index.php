<?php use App\Core\View; ?>
<div class="page-head">
    <h1>LDAP / Active Directory</h1>
</div>

<?php if (!$available): ?>
<div class="card" style="border-left:4px solid var(--color-danger);">
    <strong>The PHP <code>ldap</code> extension is not enabled</strong> on this server. Enable it in php.ini
    (<code>extension=ldap</code>) and restart the web server to use this feature.
</div>
<?php endif; ?>

<div class="card">
    <h2>Connection</h2>
    <form method="post" action="<?= e(url('admin.ldap.save')) ?>">
        <?= csrf_field() ?>
        <label class="form-check" style="margin-bottom:1rem;">
            <input type="checkbox" name="enabled" value="1" <?= (int) ($config['enabled'] ?? 0) === 1 ? 'checked' : '' ?>> Enabled
        </label>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Host</label>
                <input class="form-control" name="host" value="<?= e((string) ($config['host'] ?? '')) ?>" placeholder="dc1.example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Port</label>
                <input class="form-control" type="number" name="port" value="<?= e((string) ($config['port'] ?? 389)) ?>">
            </div>
            <div class="form-group form-check" style="align-self:end;">
                <input type="checkbox" name="use_tls" value="1" <?= (int) ($config['use_tls'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label>Use STARTTLS</label>
            </div>
            <div class="form-group">
                <label class="form-label">Bind DN <span class="text-muted">(empty = anonymous)</span></label>
                <input class="form-control" name="bind_dn" value="<?= e((string) ($config['bind_dn'] ?? '')) ?>" placeholder="cn=svc-intranet,ou=service,dc=example,dc=com">
            </div>
            <div class="form-group">
                <label class="form-label">Bind password</label>
                <input class="form-control" type="password" name="bind_password" autocomplete="new-password"
                       placeholder="<?= !empty($config['bind_password_encrypted']) ? '•••• set — type to replace' : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Base DN</label>
                <input class="form-control" name="base_dn" value="<?= e((string) ($config['base_dn'] ?? '')) ?>" placeholder="ou=people,dc=example,dc=com">
            </div>
        </div>

        <h3>Attribute mapping</h3>
        <div class="form-grid">
            <?php foreach (['user_filter' => 'User filter', 'attr_uid' => 'Unique ID attr', 'attr_name' => 'Name attr', 'attr_email' => 'Email attr', 'attr_title' => 'Title attr', 'attr_department' => 'Department attr', 'attr_phone' => 'Phone attr', 'attr_manager' => 'Manager attr'] as $field => $label): ?>
            <div class="form-group">
                <label class="form-label"><?= e($label) ?></label>
                <input class="form-control" name="<?= e($field) ?>" value="<?= e((string) ($config[$field] ?? '')) ?>">
            </div>
            <?php endforeach; ?>
        </div>

        <h3>Group &rarr; role mapping</h3>
        <?php $groupMap = is_array($config['group_role_map'] ?? null) ? $config['group_role_map'] : (json_decode((string) ($config['group_role_map'] ?? '{}'), true) ?: []); ?>
        <div id="group-map">
            <?php $i = 0; foreach ($groupMap as $dn => $roleId): ?>
            <div class="filter-bar">
                <input class="form-control" name="group_dn[]" value="<?= e((string) $dn) ?>" placeholder="cn=IT-Staff,ou=groups,dc=example,dc=com" style="flex:1;">
                <select class="form-control" name="group_role[]" style="max-width:200px;">
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= (int) $roleId === (int) $role['id'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php $i++; endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" id="add-group-map">+ Add group mapping</button>

        <label class="form-check" style="margin:1rem 0;">
            <input type="checkbox" name="allow_ldap_bind_login" value="1" <?= (int) ($config['allow_ldap_bind_login'] ?? 0) === 1 ? 'checked' : '' ?>>
            Allow LDAP-bind password as an extra local login method (for synced users)
        </label>

        <button type="submit" class="btn btn-primary">Save configuration</button>
        <button type="button" class="btn btn-secondary" id="test-ldap" data-url="<?= e(url('admin.ldap.test')) ?>">Test connection &amp; preview 10 users</button>
    </form>
    <div id="ldap-test-results" style="margin-top:1rem;"></div>
</div>

<div class="card">
    <h2>Sync</h2>
    <form method="post" action="<?= e(url('admin.ldap.sync')) ?>" class="filter-bar">
        <?= csrf_field() ?>
        <button type="submit" name="dry_run" value="1" class="btn btn-secondary">Dry run</button>
        <button type="submit" class="btn btn-primary" data-confirm="Run the real sync now? This creates/updates/deactivates users.">Sync now</button>
    </form>
    <?php $report = $_SESSION['ldap_sync_report'] ?? null; unset($_SESSION['ldap_sync_report']); ?>
    <?php if (is_array($report)): ?>
    <div class="table-wrap" style="margin-top:0.75rem;">
    <table class="table">
        <thead><tr><th>Action</th><th>Name</th><th>Email</th><th>DN</th></tr></thead>
        <tbody>
        <?php foreach ($report['changes'] as $change): ?>
        <tr>
            <td><span class="badge status-<?= $change['action'] === 'create' ? 'approved' : ($change['action'] === 'deactivate' ? 'rejected' : 'in_review') ?>"><?= e((string) $change['action']) ?></span></td>
            <td><?= e((string) $change['name']) ?></td>
            <td><?= e((string) $change['email']) ?></td>
            <td class="text-muted" style="font-size:0.8rem;"><?= e((string) $change['dn']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($report['changes'] === []): ?><tr><td colspan="4" class="text-muted">No changes.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-ldap.js')) ?>"></script>
<?php View::end(); ?>
