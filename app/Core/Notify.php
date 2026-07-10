<?php

declare(strict_types=1);

namespace App\Core;

final class Notify
{
    public static function send(int $userId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        DB::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
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
