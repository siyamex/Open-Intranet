<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Http;
use App\Core\ImageTool;
use App\Core\Sso\OidcClient;
use App\Core\View;

final class SsoController
{
    public function redirect(string $slug): void
    {
        $provider = DB::fetch('SELECT * FROM sso_providers WHERE slug = ? AND enabled = 1', [$slug]);
        if ($provider === null) {
            http_response_code(404);
            View::render('errors/404', [], null);
            return;
        }
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $verifier = OidcClient::generateCodeVerifier();
        $_SESSION['sso_flow'] = [
            'slug' => $slug,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'link_mode' => !empty($_GET['link']) && Auth::check(),
            'link_user_id' => Auth::id(),
            'started_at' => time(),
        ];
        try {
            $url = (new OidcClient($provider))->authUrl($this->redirectUri($slug), $state, $nonce, $verifier);
        } catch (\Throwable $e) {
            Audit::log('sso.redirect_failed', 'sso_provider', (int) $provider['id'], ['error' => $e->getMessage()]);
            flash('error', 'Sign-in with ' . $provider['name'] . ' is unavailable right now: ' . $e->getMessage());
            redirect(Auth::check() ? 'profile/security' : 'login');
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    public function callback(string $slug): void
    {
        $flow = $_SESSION['sso_flow'] ?? null;
        unset($_SESSION['sso_flow']);

        if (!is_array($flow) || ($flow['slug'] ?? '') !== $slug || (time() - (int) ($flow['started_at'] ?? 0)) > 600) {
            $this->fail('Your sign-in session expired — please try again.');
        }
        if (isset($_GET['error'])) {
            $message = $_GET['error'] === 'access_denied'
                ? 'Sign-in was cancelled.'
                : 'The identity provider reported an error: ' . (string) ($_GET['error_description'] ?? $_GET['error']);
            $this->fail($message);
        }
        $state = (string) ($_GET['state'] ?? '');
        if ($state === '' || !hash_equals((string) $flow['state'], $state)) {
            $this->fail('Invalid sign-in state — please try again.');
        }
        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            $this->fail('The identity provider did not return an authorization code.');
        }
        $provider = DB::fetch('SELECT * FROM sso_providers WHERE slug = ? AND enabled = 1', [$slug]);
        if ($provider === null) {
            $this->fail('This sign-in method is no longer available.');
        }

        try {
            $client = new OidcClient($provider);
            $tokens = $client->exchangeCode($code, $this->redirectUri($slug), (string) $flow['verifier']);
            $claims = $client->validateIdToken((string) $tokens['id_token'], (string) $flow['nonce']);
        } catch (\Throwable $e) {
            Audit::log('sso.callback_failed', 'sso_provider', (int) $provider['id'], ['error' => $e->getMessage()]);
            $this->fail('Sign-in could not be completed: ' . $e->getMessage());
            return;
        }

        $providerId = (int) $provider['id'];
        $sub = (string) ($claims['sub'] ?? '');
        if ($sub === '') {
            $this->fail('The identity provider did not return a subject identifier.');
        }
        $email = strtolower(trim((string) ($claims['email'] ?? $claims['preferred_username'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }
        // Microsoft v2 tokens omit email_verified; org accounts are considered verified.
        $emailVerified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL)
            || ($provider['type'] === 'microsoft' && $email !== '');
        $name = trim((string) ($claims['name'] ?? '')) ?: ($email !== '' ? $email : 'User');
        $picture = (string) ($claims['picture'] ?? '');
        $rawProfile = json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $now = date('Y-m-d H:i:s');

        // ---- Self-service account linking --------------------------------
        if (!empty($flow['link_mode']) && Auth::check() && Auth::id() === ($flow['link_user_id'] ?? null)) {
            $existing = DB::fetch(
                'SELECT * FROM user_identities WHERE provider_id = ? AND provider_subject = ?',
                [$providerId, $sub]
            );
            if ($existing !== null && (int) $existing['user_id'] !== Auth::id()) {
                $this->fail('That ' . $provider['name'] . ' account is already linked to a different user.');
            }
            if ($existing === null) {
                DB::insert('user_identities', [
                    'user_id' => Auth::id(),
                    'provider_id' => $providerId,
                    'provider_subject' => $sub,
                    'email' => $email ?: null,
                    'raw_profile' => $rawProfile,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                Audit::log('sso.linked', 'sso_provider', $providerId, ['email' => $email]);
            }
            flash('success', $provider['name'] . ' account connected.');
            redirect('profile/security');
        }

        // ---- 1. Known identity -> log in -----------------------------------
        $identity = DB::fetch(
            'SELECT * FROM user_identities WHERE provider_id = ? AND provider_subject = ?',
            [$providerId, $sub]
        );
        if ($identity !== null) {
            $user = DB::fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [(int) $identity['user_id']]);
            if ($user === null) {
                $this->fail('Your account is inactive — please contact your administrator.');
            }
            DB::update('user_identities', ['email' => $email ?: null, 'raw_profile' => $rawProfile], 'id = ?', [(int) $identity['id']]);
            $this->completeLogin($user, $provider, 'sso.login');
        }

        // ---- 2. Same verified email -> link + log in ------------------------
        if ($email !== '' && $emailVerified) {
            $user = DB::fetch("SELECT * FROM users WHERE email = ?", [$email]);
            if ($user !== null) {
                if ($user['status'] !== 'active') {
                    $this->fail('Your account is inactive — please contact your administrator.');
                }
                DB::insert('user_identities', [
                    'user_id' => (int) $user['id'],
                    'provider_id' => $providerId,
                    'provider_subject' => $sub,
                    'email' => $email,
                    'raw_profile' => $rawProfile,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                Audit::log('sso.linked_by_email', 'user', (int) $user['id'], ['provider' => $provider['slug']], (int) $user['id']);
                $this->completeLogin($user, $provider, 'sso.login');
            }
        }

        // ---- 3. Auto-provision -----------------------------------------------
        if ((int) $provider['auto_provision'] === 1 && $email !== '' && $emailVerified
            && $this->domainAllowed($email, (string) ($provider['allowed_domains'] ?? ''))) {
            $avatarPath = $picture !== '' ? $this->downloadAvatar($picture) : null;
            $userId = DB::insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => null,
                'avatar_path' => $avatarPath,
                'status' => 'active',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) ($provider['default_role_id'] ?? 0);
            if ($roleId === 0) {
                $roleId = (int) (DB::scalar("SELECT id FROM roles WHERE slug = 'employee'") ?? 0);
            }
            if ($roleId > 0) {
                DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, $roleId]);
            }
            DB::insert('user_identities', [
                'user_id' => $userId,
                'provider_id' => $providerId,
                'provider_subject' => $sub,
                'email' => $email,
                'raw_profile' => $rawProfile,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            Audit::log('sso.provisioned', 'user', $userId, ['provider' => $provider['slug'], 'email' => $email], $userId);
            $user = DB::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
            $this->completeLogin($user, $provider, 'sso.login_first');
        }

        // ---- 4. No account -----------------------------------------------------
        Audit::log('sso.account_not_found', 'sso_provider', $providerId, ['email' => $email]);
        View::render('auth/sso-not-found', ['title' => 'Account not found', 'email' => $email, 'provider' => $provider], 'auth');
    }

    private function completeLogin(array $user, array $provider, string $auditAction): never
    {
        Auth::login($user);
        Audit::log($auditAction, 'user', (int) $user['id'], ['provider' => $provider['slug']]);
        if ((int) ($user['must_change_password'] ?? 0) === 1 && $user['password_hash'] !== null) {
            redirect('password/change');
        }
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            redirect($intended);
        }
        redirect('/');
    }

    private function domainAllowed(string $email, string $allowedDomains): bool
    {
        $allowed = array_filter(array_map(
            static fn (string $d): string => strtolower(trim($d)),
            explode(',', $allowedDomains)
        ));
        if ($allowed === []) {
            return true; // empty list = allow all
        }
        $domain = strtolower((string) substr((string) strrchr($email, '@'), 1));
        return in_array($domain, $allowed, true);
    }

    private function downloadAvatar(string $url): ?string
    {
        try {
            Http::assertAllowedUrl($url);
            $response = Http::get($url, [], 8);
            if ($response['status'] !== 200 || $response['body'] === '') {
                return null;
            }
            $encoded = ImageTool::resizeEncode($response['body'], 256, 'jpeg');
            if ($encoded === null) {
                return null;
            }
            $dir = BASE_PATH . '/storage/uploads/avatars';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = bin2hex(random_bytes(16)) . '.jpg';
            file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
            return 'avatars/' . $name;
        } catch (\Throwable) {
            return null;
        }
    }

    private function redirectUri(string $slug): string
    {
        return base_url('auth/' . $slug . '/callback');
    }

    private function fail(string $message): never
    {
        flash('error', $message);
        redirect(Auth::check() ? 'profile/security' : 'login');
    }
}
