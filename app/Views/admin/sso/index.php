<?php use App\Core\View; ?>
<div class="page-head">
    <h1>SSO Providers</h1>
    <p class="text-muted">Single sign-on for Google, Microsoft and any OpenID Connect provider. Drag rows to set the button order on the login page.</p>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
        <h2 style="margin:0;">Providers</h2>
        <a class="btn btn-primary" href="<?= e(url('admin.sso.create')) ?>">Add provider</a>
    </div>
    <?php if ($providers === []): ?>
        <p class="text-muted">No providers configured yet.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table" id="sso-table" data-order-url="<?= e(url('admin.sso.order')) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <thead><tr><th></th><th>Name</th><th>Type</th><th>Status</th><th>Redirect URI</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($providers as $p): ?>
            <tr draggable="true" data-id="<?= (int) $p['id'] ?>">
                <td class="drag-handle" title="Drag to reorder">⠿</td>
                <td><strong><?= e((string) $p['name']) ?></strong><br><span class="text-muted"><?= e((string) $p['slug']) ?></span></td>
                <td><?= e((string) $p['type']) ?></td>
                <td>
                    <form method="post" action="<?= e(url('admin.sso.toggle', ['id' => $p['id']])) ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm <?= (int) $p['enabled'] === 1 ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= (int) $p['enabled'] === 1 ? 'Enabled' : 'Disabled' ?>
                        </button>
                    </form>
                </td>
                <td>
                    <code class="copy-uri" style="cursor:pointer;" title="Click to copy"><?= e(base_url('auth/' . $p['slug'] . '/callback')) ?></code>
                </td>
                <td style="white-space:nowrap;">
                    <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.sso.edit', ['id' => $p['id']])) ?>">Edit</a>
                    <form method="post" action="<?= e(url('admin.sso.destroy', ['id' => $p['id']])) ?>" style="display:inline;"
                          onsubmit="return confirm('Delete this provider? Linked identities will stop working.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Authentication settings</h2>
    <form method="post" action="<?= e(url('admin.sso.settings')) ?>">
        <?= csrf_field() ?>
        <div class="form-group form-check">
            <input type="checkbox" id="allow_local_login" name="allow_local_login" value="1" <?= $allowLocal ? 'checked' : '' ?>>
            <label for="allow_local_login">Allow local email + password sign-in <span class="text-muted">(super admins can always use passwords)</span></label>
        </div>
        <div class="form-group" style="max-width:360px;">
            <label class="form-label" for="sso_auto_redirect">Auto-redirect straight to a provider</label>
            <select class="form-control" id="sso_auto_redirect" name="sso_auto_redirect">
                <option value="">— disabled —</option>
                <?php foreach ($providers as $p): ?>
                    <?php if ((int) $p['enabled'] === 1): ?>
                    <option value="<?= e((string) $p['slug']) ?>" <?= $autoRedirect === $p['slug'] ? 'selected' : '' ?>><?= e((string) $p['name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <p class="form-hint">When set, visiting the login page immediately redirects to this provider. Append <code>?local=1</code> to reach the local form.</p>
        </div>
        <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-sso.js')) ?>"></script>
<?php View::end(); ?>
