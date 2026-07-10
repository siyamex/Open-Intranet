<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;

final class QuickLink
{
    /**
     * Active links visible to the current user: favorites first, then the
     * user's personal order, then global sort order.
     *
     * @return array<int, array>
     */
    public static function forUser(int $userId): array
    {
        $rows = DB::fetchAll('SELECT * FROM quick_links WHERE is_active = 1 ORDER BY sort_order, id');
        $isSuperAdmin = Auth::hasRole('super_admin');
        $userRoles = Auth::roles();
        $rows = array_values(array_filter($rows, static function (array $row) use ($userRoles, $isSuperAdmin): bool {
            if ($row['visible_to'] === null || $row['visible_to'] === '' || $isSuperAdmin) {
                return true;
            }
            $allowed = json_decode((string) $row['visible_to'], true);
            return !is_array($allowed) || $allowed === [] || array_intersect($allowed, $userRoles) !== [];
        }));

        $pins = array_map('intval', array_column(
            DB::fetchAll('SELECT quick_link_id FROM user_quick_link_pins WHERE user_id = ?', [$userId]),
            'quick_link_id'
        ));
        $orderJson = DB::scalar("SELECT `value` FROM user_prefs WHERE user_id = ? AND `key` = 'quick_links_order'", [$userId]);
        $userOrder = is_string($orderJson) ? (array) json_decode($orderJson, true) : [];
        $orderIndex = array_flip(array_map('intval', $userOrder));

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['pinned'] = in_array($id, $pins, true);
            $row['user_order'] = $orderIndex[$id] ?? PHP_INT_MAX;
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            if ($a['pinned'] !== $b['pinned']) {
                return $a['pinned'] ? -1 : 1; // favorites float to the front
            }
            if ($a['user_order'] !== $b['user_order']) {
                return $a['user_order'] <=> $b['user_order'];
            }
            return (int) $a['sort_order'] <=> (int) $b['sort_order'];
        });
        return $rows;
    }

    public static function recordClick(int $id): void
    {
        DB::run('UPDATE quick_links SET click_count = click_count + 1 WHERE id = ?', [$id]);
        DB::run(
            'INSERT INTO quick_link_clicks (quick_link_id, day, clicks) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE clicks = clicks + 1',
            [$id, date('Y-m-d')]
        );
    }

    /**
     * Daily clicks for the sparkline, oldest first, zero-filled for $days.
     *
     * @return int[]
     */
    public static function sparkline(int $id, int $days = 30): array
    {
        $rows = DB::fetchAll(
            'SELECT day, clicks FROM quick_link_clicks WHERE quick_link_id = ? AND day > ?',
            [$id, date('Y-m-d', time() - ($days + 1) * 86400)]
        );
        $byDay = array_column($rows, 'clicks', 'day');
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', time() - $i * 86400);
            $series[] = (int) ($byDay[$day] ?? 0);
        }
        return $series;
    }
}
