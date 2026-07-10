<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Models\QuickLink;

/**
 * User-side quick link interactions: click tracking, favorites, personal order.
 */
final class QuickLinkController
{
    public function click(string $id): void
    {
        $link = DB::fetch('SELECT id FROM quick_links WHERE id = ? AND is_active = 1', [(int) $id]);
        if ($link !== null) {
            QuickLink::recordClick((int) $id);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => $link !== null]);
        exit;
    }

    public function pin(string $id): void
    {
        $link = DB::fetch('SELECT id FROM quick_links WHERE id = ? AND is_active = 1', [(int) $id]);
        $pinned = false;
        if ($link !== null) {
            $existing = DB::fetch(
                'SELECT 1 FROM user_quick_link_pins WHERE user_id = ? AND quick_link_id = ?',
                [Auth::id(), (int) $id]
            );
            if ($existing !== null) {
                DB::delete('user_quick_link_pins', 'user_id = ? AND quick_link_id = ?', [Auth::id(), (int) $id]);
            } else {
                DB::run(
                    'INSERT IGNORE INTO user_quick_link_pins (user_id, quick_link_id) VALUES (?, ?)',
                    [Auth::id(), (int) $id]
                );
                $pinned = true;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => $link !== null, 'pinned' => $pinned]);
        exit;
    }

    public function order(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $order = array_values(array_map('intval', (array) ($payload['order'] ?? [])));
        DB::run(
            "INSERT INTO user_prefs (user_id, `key`, `value`) VALUES (?, 'quick_links_order', ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [Auth::id(), json_encode($order)]
        );
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
