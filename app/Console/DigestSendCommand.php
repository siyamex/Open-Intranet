<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Mailer;
use App\Core\Settings;
use App\Core\ThemeService;

/**
 * Sends daily/weekly HTML digest emails (unread notifications summary),
 * styled with the active theme's colors. Weekly digests go out on Mondays.
 * Also purges read notifications older than 90 days.
 */
final class DigestSendCommand
{
    public const DESCRIPTION = 'Send daily/weekly notification digests + purge old read notifications';

    public static function run(array $args): int
    {
        $frequencies = ['daily'];
        if (in_array('weekly', $args, true) || date('N') === '1') {
            $frequencies[] = 'weekly';
        }
        if (in_array('daily-off', $args, true)) {
            $frequencies = array_diff($frequencies, ['daily']);
        }

        // theme colors for the email
        $theme = ThemeService::activeTheme();
        $vars = $theme !== null ? (array) json_decode((string) $theme['variables'], true) : [];
        $primary = (string) ($vars['color-primary'] ?? '#4f46e5');
        $site = (string) Settings::get('site_name', 'OpenIntranet');

        $sent = 0;
        foreach ($frequencies as $frequency) {
            $since = date('Y-m-d H:i:s', time() - ($frequency === 'weekly' ? 7 : 1) * 86400);
            $subscribers = DB::fetchAll(
                "SELECT u.id, u.name, u.email FROM users u
                 JOIN user_prefs p ON p.user_id = u.id AND p.`key` = 'digest_frequency' AND p.`value` = ?
                 WHERE u.status = 'active'",
                [$frequency]
            );
            foreach ($subscribers as $user) {
                $items = DB::fetchAll(
                    'SELECT type, title, url, created_at FROM notifications
                     WHERE user_id = ? AND read_at IS NULL AND created_at >= ?
                     ORDER BY created_at DESC LIMIT 30',
                    [(int) $user['id'], $since]
                );
                if ($items === []) {
                    continue;
                }
                $rows = '';
                foreach ($items as $item) {
                    $link = $item['url'] !== null
                        ? '<a href="' . e((string) $item['url']) . '" style="color:' . e($primary) . ';">' . e((string) $item['title']) . '</a>'
                        : e((string) $item['title']);
                    $rows .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;">' . $link
                        . ' <span style="color:#888;font-size:12px;">(' . e((string) $item['type']) . ')</span></td></tr>';
                }
                $html = '<div style="font-family:sans-serif;max-width:560px;">'
                    . '<h2 style="background:' . e($primary) . ';color:#fff;padding:14px 16px;border-radius:8px;">'
                    . e($site) . ' — your ' . $frequency . ' digest</h2>'
                    . '<p>Hi ' . e((string) $user['name']) . ', here is what you missed:</p>'
                    . '<table style="width:100%;border-collapse:collapse;">' . $rows . '</table>'
                    . '<p style="color:#888;font-size:12px;">Change your digest settings under Profile → Notifications.</p>'
                    . '</div>';
                Mailer::send((string) $user['email'], "{$site} {$frequency} digest — " . count($items) . ' update(s)', $html);
                $sent++;
            }
        }

        $purged = DB::delete(
            'notifications',
            'read_at IS NOT NULL AND read_at < ?',
            [date('Y-m-d H:i:s', time() - 90 * 86400)]
        );
        echo "Digests sent: {$sent} (" . implode('+', $frequencies) . "). Purged {$purged} old read notification(s).\n";
        return 0;
    }
}
