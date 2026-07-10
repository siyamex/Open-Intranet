<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Notify;

final class PublishDueCommand
{
    public const DESCRIPTION = 'Publish scheduled news whose time has come (run via cron)';

    public static function run(array $args): int
    {
        $due = DB::fetchAll(
            "SELECT id, title, slug, author_id FROM news WHERE status = 'scheduled' AND published_at <= NOW()"
        );
        foreach ($due as $post) {
            DB::update('news', ['status' => 'published'], 'id = ?', [(int) $post['id']]);
            $userIds = array_map('intval', array_column(DB::fetchAll(
                "SELECT u.id FROM users u
                 WHERE u.status = 'active' AND u.id != ?
                   AND NOT EXISTS (
                       SELECT 1 FROM user_prefs p
                       WHERE p.user_id = u.id AND p.`key` = 'notif_news' AND p.`value` = '0'
                   )",
                [(int) ($post['author_id'] ?? 0)]
            ), 'id'));
            Notify::sendMany($userIds, 'news', 'News: ' . $post['title'], null, base_url('news/' . $post['slug']));
            Audit::log('news.auto_published', 'news', (int) $post['id'], ['title' => $post['title']], null);
            echo "Published: {$post['title']}\n";
        }
        echo count($due) . " post(s) published.\n";
        return 0;
    }
}
