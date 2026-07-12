<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Settings;
use App\Core\View;

final class KudosController
{
    public function index(): void
    {
        View::render('admin/kudos/index', [
            'title' => 'Kudos moderation',
            'items' => DB::fetchAll(
                'SELECT k.*, s.name AS sender_name, r.name AS recipient_name, v.label AS value_label
                 FROM kudos k
                 LEFT JOIN users s ON s.id = k.sender_id
                 JOIN users r ON r.id = k.recipient_id
                 LEFT JOIN kudos_values v ON v.id = k.value_id
                 ORDER BY k.created_at DESC LIMIT 200'
            ),
            'values' => DB::fetchAll('SELECT * FROM kudos_values ORDER BY label'),
            'bannedWords' => (string) Settings::get('kudos_banned_words', ''),
        ], 'admin');
    }

    public function toggleHide(string $id): void
    {
        $kudos = DB::fetch('SELECT * FROM kudos WHERE id = ?', [(int) $id]);
        if ($kudos !== null) {
            $hidden = (int) $kudos['is_hidden'] === 1 ? 0 : 1;
            DB::update('kudos', ['is_hidden' => $hidden], 'id = ?', [(int) $id]);
            Audit::log($hidden ? 'kudos.hidden' : 'kudos.unhidden', 'kudos', (int) $id);
            flash('success', $hidden ? 'Kudos hidden.' : 'Kudos visible again.');
        }
        redirect('admin/kudos');
    }

    public function destroy(string $id): void
    {
        DB::delete('kudos', 'id = ?', [(int) $id]);
        Audit::log('kudos.deleted', 'kudos', (int) $id);
        flash('success', 'Kudos deleted.');
        redirect('admin/kudos');
    }

    public function saveSettings(): void
    {
        Settings::set('kudos_banned_words', trim((string) ($_POST['banned_words'] ?? '')));
        // value tags management
        $newValue = trim((string) ($_POST['new_value'] ?? ''));
        if ($newValue !== '' && mb_strlen($newValue) <= 50) {
            DB::run(
                'INSERT INTO kudos_values (label, emoji, is_active) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE is_active = 1',
                [$newValue, trim((string) ($_POST['new_value_emoji'] ?? '')) ?: null]
            );
        }
        Audit::log('kudos.settings_updated', 'settings', 'kudos');
        flash('success', 'Kudos settings saved.');
        redirect('admin/kudos');
    }

    public function toggleValue(string $id): void
    {
        $value = DB::fetch('SELECT * FROM kudos_values WHERE id = ?', [(int) $id]);
        if ($value !== null) {
            DB::update('kudos_values', ['is_active' => (int) $value['is_active'] === 1 ? 0 : 1], 'id = ?', [(int) $id]);
        }
        redirect('admin/kudos');
    }
}
