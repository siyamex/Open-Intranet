<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Settings;
use App\Core\Sso\OidcClient;
use App\Core\Validator;
use App\Core\View;

final class SsoProviderController
{
    public function index(): void
    {
        $providers = DB::fetchAll('SELECT * FROM sso_providers ORDER BY sort_order, id');
        View::render('admin/sso/index', [
            'title' => 'SSO Providers',
            'providers' => $providers,
            'allowLocal' => (bool) Settings::get('allow_local_login', true),
            'autoRedirect' => (string) (Settings::get('sso_auto_redirect') ?? ''),
        ], 'admin');
    }

    public function create(): void
    {
        View::render('admin/sso/form', [
            'title' => 'Add SSO Provider',
            'provider' => null,
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function store(): void
    {
        $data = $this->validated(null);
        if ($data === null) {
            redirect('admin/sso/create');
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['sort_order'] = (int) (DB::scalar('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM sso_providers') ?? 10);
        $id = DB::insert('sso_providers', $data);
        Audit::log('sso_provider.created', 'sso_provider', $id, ['name' => $data['name']]);
        flash('success', 'Provider created. Copy the Redirect URI into your IdP app registration, then test it.');
        redirect('admin/sso/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $provider = $this->find($id);
        View::render('admin/sso/form', [
            'title' => 'Edit SSO Provider',
            'provider' => $provider,
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function update(string $id): void
    {
        $provider = $this->find($id);
        $data = $this->validated((int) $id);
        if ($data === null) {
            redirect('admin/sso/' . $id . '/edit');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($data['client_secret_encrypted'] === null) {
            unset($data['client_secret_encrypted']); // blank input keeps the stored secret
        }
        DB::update('sso_providers', $data, 'id = ?', [(int) $id]);
        Audit::log('sso_provider.updated', 'sso_provider', (int) $id, ['name' => $data['name']]);
        flash('success', 'Provider updated.');
        redirect('admin/sso/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $provider = $this->find($id);
        DB::delete('sso_providers', 'id = ?', [(int) $id]);
        Audit::log('sso_provider.deleted', 'sso_provider', (int) $id, ['name' => $provider['name']]);
        flash('success', 'Provider deleted.');
        redirect('admin/sso');
    }

    public function toggle(string $id): void
    {
        $provider = $this->find($id);
        $enabled = (int) $provider['enabled'] === 1 ? 0 : 1;
        DB::update('sso_providers', ['enabled' => $enabled], 'id = ?', [(int) $id]);
        Audit::log('sso_provider.' . ($enabled ? 'enabled' : 'disabled'), 'sso_provider', (int) $id, ['name' => $provider['name']]);
        flash('success', $provider['name'] . ($enabled ? ' enabled.' : ' disabled.'));
        redirect('admin/sso');
    }

    public function order(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $ids = is_array($payload['order'] ?? null) ? $payload['order'] : [];
        $sort = 10;
        foreach ($ids as $id) {
            DB::update('sso_providers', ['sort_order' => $sort], 'id = ?', [(int) $id]);
            $sort += 10;
        }
        Audit::log('sso_provider.reordered', 'sso_provider', null, ['order' => array_map('intval', $ids)]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function test(string $id): void
    {
        $provider = $this->find($id);
        header('Content-Type: application/json');
        echo json_encode(['results' => (new OidcClient($provider))->testConfiguration()]);
        exit;
    }

    public function saveSettings(): void
    {
        Settings::set('allow_local_login', !empty($_POST['allow_local_login']), 'bool');
        $auto = trim((string) ($_POST['sso_auto_redirect'] ?? ''));
        Settings::set('sso_auto_redirect', $auto, 'string');
        Audit::log('settings.updated', 'settings', 'auth', [
            'allow_local_login' => !empty($_POST['allow_local_login']),
            'sso_auto_redirect' => $auto,
        ]);
        flash('success', 'Authentication settings saved.');
        redirect('admin/sso');
    }

    private function find(string $id): array
    {
        $provider = DB::fetch('SELECT * FROM sso_providers WHERE id = ?', [(int) $id]);
        if ($provider === null) {
            flash('error', 'Provider not found.');
            redirect('admin/sso');
        }
        return $provider;
    }

    /**
     * @return array<string, mixed>|null null when validation failed (errors flashed)
     */
    private function validated(?int $ignoreId): ?array
    {
        $v = new Validator($_POST, [
            'name' => 'required|max:100',
            'type' => 'required|in:google,microsoft,oidc',
            'client_id' => 'required|max:255',
            'scopes' => 'max:255',
            'button_color' => 'max:20',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            return null;
        }
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        if ($slug === '') {
            $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $_POST['name']));
            $slug = trim($slug, '-') ?: 'provider';
        }
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            flash('error', 'Slug may only contain lowercase letters, numbers and dashes.');
            Flash::keepInput();
            return null;
        }
        $existing = DB::fetch('SELECT id FROM sso_providers WHERE slug = ?', [$slug]);
        if ($existing !== null && (int) $existing['id'] !== $ignoreId) {
            flash('error', "The slug '{$slug}' is already in use.");
            Flash::keepInput();
            return null;
        }
        if (($_POST['type'] ?? '') === 'oidc') {
            $discovery = trim((string) ($_POST['discovery_url'] ?? ''));
            $issuer = trim((string) ($_POST['tenant_or_issuer'] ?? ''));
            if ($discovery === '' && $issuer === '') {
                flash('error', 'Generic OIDC providers need a discovery URL or an issuer.');
                Flash::keepInput();
                return null;
            }
        }
        $secret = (string) ($_POST['client_secret'] ?? '');
        return [
            'name' => trim((string) $_POST['name']),
            'slug' => $slug,
            'type' => (string) $_POST['type'],
            'client_id' => trim((string) $_POST['client_id']),
            'client_secret_encrypted' => $secret !== '' ? Crypto::encrypt($secret) : null,
            'tenant_or_issuer' => trim((string) ($_POST['tenant_or_issuer'] ?? '')) ?: null,
            'discovery_url' => trim((string) ($_POST['discovery_url'] ?? '')) ?: null,
            'scopes' => trim((string) ($_POST['scopes'] ?? '')) ?: 'openid profile email',
            'icon' => trim((string) ($_POST['icon'] ?? '')) ?: null,
            'button_color' => trim((string) ($_POST['button_color'] ?? '')) ?: null,
            'allowed_domains' => trim((string) ($_POST['allowed_domains'] ?? '')) ?: null,
            'auto_provision' => !empty($_POST['auto_provision']) ? 1 : 0,
            'default_role_id' => !empty($_POST['default_role_id']) ? (int) $_POST['default_role_id'] : null,
            'enabled' => !empty($_POST['enabled']) ? 1 : 0,
        ];
    }
}
