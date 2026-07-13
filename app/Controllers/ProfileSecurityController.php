<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\View;

final class ProfileSecurityController
{
    public function index(): void
    {
        $userId = Auth::id();
        $identities = DB::fetchAll(
            'SELECT ui.id, ui.email, ui.created_at, p.name AS provider_name, p.slug AS provider_slug
             FROM user_identities ui
             JOIN sso_providers p ON p.id = ui.provider_id
             WHERE ui.user_id = ?
             ORDER BY ui.created_at',
            [$userId]
        );
        $linkedSlugs = array_column($identities, 'provider_slug');
        $available = array_values(array_filter(
            DB::fetchAll('SELECT name, slug FROM sso_providers WHERE enabled = 1 ORDER BY sort_order, id'),
            static fn (array $p): bool => !in_array($p['slug'], $linkedSlugs, true)
        ));
        View::render('profile/security', [
            'title' => 'Security',
            'identities' => $identities,
            'available' => $available,
            'hasPassword' => Auth::user()['password_hash'] !== null,
            'tokens' => DB::fetchAll(
                'SELECT id, name, scopes, last_used_at, last_used_ip, expires_at, created_at
                 FROM api_tokens WHERE user_id = ? AND revoked_at IS NULL ORDER BY created_at DESC',
                [Auth::id()]
            ),
        ]);
    }

    public function createToken(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            flash('error', 'Give the token a name.');
            redirect('profile/security');
        }
        $scopes = array_values(array_intersect((array) ($_POST['scopes'] ?? []), ['read', 'write', 'admin']));
        if ($scopes === []) {
            $scopes = ['read'];
        }
        $expiresDays = (int) ($_POST['expires_days'] ?? 0);
        $generated = \App\Core\ApiAuth::generate();
        DB::insert('api_tokens', [
            'user_id' => Auth::id(),
            'name' => $name,
            'token_hash' => $generated['hash'],
            'selector' => $generated['selector'],
            'scopes' => json_encode($scopes),
            'expires_at' => $expiresDays > 0 ? date('Y-m-d H:i:s', time() + $expiresDays * 86400) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('api_token.created', 'api_token', null, ['name' => $name, 'scopes' => $scopes]);
        $_SESSION['new_api_token'] = $generated['plain'];
        flash('success', 'Token created — copy it now, it will not be shown again.');
        redirect('profile/security');
    }

    public function revokeToken(string $id): void
    {
        DB::update('api_tokens', ['revoked_at' => date('Y-m-d H:i:s')], 'id = ? AND user_id = ?', [(int) $id, Auth::id()]);
        Audit::log('api_token.revoked', 'api_token', (int) $id);
        flash('success', 'Token revoked.');
        redirect('profile/security');
    }

    public function unlink(string $id): void
    {
        $identity = DB::fetch(
            'SELECT ui.*, p.name AS provider_name FROM user_identities ui
             JOIN sso_providers p ON p.id = ui.provider_id
             WHERE ui.id = ? AND ui.user_id = ?',
            [(int) $id, Auth::id()]
        );
        if ($identity === null) {
            flash('error', 'Linked account not found.');
            redirect('profile/security');
        }
        $identityCount = (int) DB::scalar('SELECT COUNT(*) FROM user_identities WHERE user_id = ?', [Auth::id()]);
        $hasPassword = Auth::user()['password_hash'] !== null;
        if (!$hasPassword && $identityCount <= 1) {
            flash('error', 'You cannot unlink your only sign-in method. Set a password first.');
            redirect('profile/security');
        }
        DB::delete('user_identities', 'id = ?', [(int) $identity['id']]);
        Audit::log('sso.unlinked', 'user_identity', (int) $identity['id'], ['provider' => $identity['provider_name']]);
        flash('success', $identity['provider_name'] . ' account disconnected.');
        redirect('profile/security');
    }
}
