<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('admin.sso')) ?>">&larr; Back to providers</a></p>
</div>

<div class="card" style="max-width:760px;">
    <form method="post"
          action="<?= $provider === null ? e(url('admin.sso.store')) : e(url('admin.sso.update', ['id' => $provider['id']])) ?>">
        <?= csrf_field() ?>
        <?php if ($provider !== null): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="type">Provider type</label>
            <select class="form-control" id="type" name="type">
                <?php $type = (string) old('type', $provider['type'] ?? 'google'); ?>
                <option value="google" <?= $type === 'google' ? 'selected' : '' ?>>Google Workspace / Gmail</option>
                <option value="microsoft" <?= $type === 'microsoft' ? 'selected' : '' ?>>Microsoft Entra ID (Azure AD)</option>
                <option value="oidc" <?= $type === 'oidc' ? 'selected' : '' ?>>Generic OpenID Connect</option>
            </select>
            <p class="form-hint" id="type-hint"></p>
        </div>

        <div class="form-group">
            <label class="form-label" for="name">Display name</label>
            <input class="form-control" id="name" name="name" value="<?= e((string) old('name', $provider['name'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="slug">Slug <span class="text-muted">(used in URLs; leave blank to derive from the name)</span></label>
            <input class="form-control" id="slug" name="slug" value="<?= e((string) old('slug', $provider['slug'] ?? '')) ?>" pattern="[a-z0-9-]*">
        </div>

        <?php if ($provider !== null): ?>
        <div class="form-group">
            <label class="form-label">Redirect URI (paste into your IdP app registration)</label>
            <input class="form-control" readonly value="<?= e(base_url('auth/' . $provider['slug'] . '/callback')) ?>" onclick="this.select()">
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="client_id">Client ID</label>
            <input class="form-control" id="client_id" name="client_id" value="<?= e((string) old('client_id', $provider['client_id'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="client_secret">Client secret</label>
            <?php if ($provider !== null && !empty($provider['client_secret_encrypted'])): ?>
                <input class="form-control" id="client_secret" name="client_secret" type="password" placeholder="•••• set — type to replace" autocomplete="new-password">
            <?php else: ?>
                <input class="form-control" id="client_secret" name="client_secret" type="password" autocomplete="new-password">
            <?php endif; ?>
            <p class="form-hint">Stored encrypted (sodium secretbox) and never redisplayed.</p>
        </div>

        <div class="form-group" id="group-tenant">
            <label class="form-label" for="tenant_or_issuer">Tenant / Issuer</label>
            <input class="form-control" id="tenant_or_issuer" name="tenant_or_issuer" value="<?= e((string) old('tenant_or_issuer', $provider['tenant_or_issuer'] ?? '')) ?>">
            <p class="form-hint" id="tenant-hint">Microsoft: 'common', 'organizations' or your tenant ID. Generic OIDC: the issuer URL.</p>
        </div>

        <div class="form-group" id="group-discovery">
            <label class="form-label" for="discovery_url">Discovery URL</label>
            <input class="form-control" id="discovery_url" name="discovery_url" value="<?= e((string) old('discovery_url', $provider['discovery_url'] ?? '')) ?>" placeholder="https://idp.example.com/.well-known/openid-configuration">
        </div>

        <div class="form-group">
            <label class="form-label" for="scopes">Scopes</label>
            <input class="form-control" id="scopes" name="scopes" value="<?= e((string) old('scopes', $provider['scopes'] ?? 'openid profile email')) ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="button_color">Button color <span class="text-muted">(hex, optional)</span></label>
            <input class="form-control" id="button_color" name="button_color" value="<?= e((string) old('button_color', $provider['button_color'] ?? '')) ?>" placeholder="#4285f4" style="max-width:180px;">
        </div>

        <div class="form-group">
            <label class="form-label" for="allowed_domains">Allowed email domains <span class="text-muted">(comma separated; empty = allow all)</span></label>
            <input class="form-control" id="allowed_domains" name="allowed_domains" value="<?= e((string) old('allowed_domains', $provider['allowed_domains'] ?? '')) ?>" placeholder="example.com, example.org">
        </div>

        <div class="form-group form-check">
            <input type="checkbox" id="auto_provision" name="auto_provision" value="1" <?= (int) old('auto_provision', $provider['auto_provision'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label for="auto_provision">Auto-provision new users on first sign-in</label>
        </div>

        <div class="form-group" style="max-width:320px;">
            <label class="form-label" for="default_role_id">Default role for provisioned users</label>
            <select class="form-control" id="default_role_id" name="default_role_id">
                <option value="">Employee (default)</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= (int) old('default_role_id', $provider['default_role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e((string) $role['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group form-check">
            <input type="checkbox" id="enabled" name="enabled" value="1" <?= (int) old('enabled', $provider['enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label for="enabled">Enabled (show on the login page)</label>
        </div>

        <div style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn btn-primary"><?= $provider === null ? 'Create provider' : 'Save changes' ?></button>
            <?php if ($provider !== null): ?>
            <button type="button" class="btn btn-secondary" id="test-config" data-url="<?= e(url('admin.sso.test', ['id' => $provider['id']])) ?>">Test configuration</button>
            <?php endif; ?>
        </div>
    </form>

    <div id="test-results" style="margin-top:1rem;"></div>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/admin-sso.js')) ?>"></script>
<?php View::end(); ?>
