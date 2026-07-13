<?php

declare(strict_types=1);

namespace App\Core;

final class Notify
{
    public static function send(int $userId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if (self::pref($userId, 'notif_' . $type) === '0') {
            return; // user turned this type off entirely
        }
        DB::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::pushIfSubscribed($userId, $title, $body, $url);
        if (self::pref($userId, 'notif_email_' . $type) === '1') {
            $email = DB::scalar("SELECT email FROM users WHERE id = ? AND status = 'active'", [$userId]);
            if (is_string($email) && $email !== '') {
                Mailer::send(
                    $email,
                    $title,
                    '<p>' . e($title) . '</p>'
                    . ($body !== null ? '<p>' . e($body) . '</p>' : '')
                    . ($url !== null ? '<p><a href="' . e($url) . '">Open in ' . e((string) Settings::get('site_name', 'OpenIntranet')) . '</a></p>' : '')
                );
            }
        }
    }

    private static function pushIfSubscribed(int $userId, string $title, ?string $body, ?string $url): void
    {
        $subs = DB::fetchAll('SELECT * FROM push_subscriptions WHERE user_id = ?', [$userId]);
        foreach ($subs as $sub) {
            WebPush::send($sub, ['title' => $title, 'body' => $body ?? '', 'url' => $url ?? base_url('/')]);
        }
    }

    private static function pref(int $userId, string $key): ?string
    {
        $value = DB::scalar('SELECT `value` FROM user_prefs WHERE user_id = ? AND `key` = ?', [$userId, $key]);
        return is_string($value) ? $value : null;
    }

    /**
     * Notify many users at once (single multi-row insert per chunk).
     *
     * @param int[] $userIds
     */
    public static function sendMany(array $userIds, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach (array_chunk($userIds, 200) as $chunk) {
            $placeholders = [];
            $params = [];
            foreach ($chunk as $id) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?)';
                array_push($params, $id, $type, $title, $body, $url, $now);
            }
            DB::run(
                'INSERT INTO notifications (user_id, type, title, body, url, created_at) VALUES '
                . implode(', ', $placeholders),
                $params
            );
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int) DB::scalar('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }
}
