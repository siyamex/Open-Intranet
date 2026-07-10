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
        ]);
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
