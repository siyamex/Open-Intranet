<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Notify;

final class NotificationController
{
    public function recent(): void
    {
        $items = DB::fetchAll(
            'SELECT id, type, title, body, url, read_at, created_at
             FROM notifications WHERE user_id = ?
             ORDER BY created_at DESC, id DESC LIMIT 10',
            [Auth::id()]
        );
        foreach ($items as &$item) {
            $item['time_ago'] = self::timeAgo((string) $item['created_at']);
        }
        unset($item);
        header('Content-Type: application/json');
        echo json_encode([
            'unread' => Notify::unreadCount((int) Auth::id()),
            'items' => $items,
        ]);
        exit;
    }

    public function markRead(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            DB::update('notifications', ['read_at' => date('Y-m-d H:i:s')], 'id = ? AND user_id = ?', [$id, Auth::id()]);
        } else {
            DB::update('notifications', ['read_at' => date('Y-m-d H:i:s')], 'user_id = ? AND read_at IS NULL', [Auth::id()]);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'unread' => Notify::unreadCount((int) Auth::id())]);
        exit;
    }

    private static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        return floor($diff / 86400) . 'd ago';
    }
}
