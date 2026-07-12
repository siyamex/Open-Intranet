<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Notify;

final class WikiRemindersCommand
{
    public const DESCRIPTION = 'Notify wiki page owners about due/overdue reviews (run daily via cron)';

    public static function run(array $args): int
    {
        $due = DB::fetchAll(
            'SELECT p.id, p.title, p.slug, p.review_due, p.owner_id, s.slug AS space_slug
             FROM wiki_pages p JOIN wiki_spaces s ON s.id = p.space_id
             WHERE p.owner_id IS NOT NULL AND p.review_due IS NOT NULL AND p.review_due <= ?',
            [date('Y-m-d')]
        );
        foreach ($due as $page) {
            Notify::send(
                (int) $page['owner_id'],
                'mentions',
                'Wiki review due: ' . $page['title'],
                'This page was due for review on ' . date('j M Y', strtotime((string) $page['review_due'])) . '.',
                base_url('wiki/' . $page['space_slug'] . '/' . $page['slug'])
            );
            echo "Reminded owner of: {$page['title']}\n";
        }
        echo count($due) . " reminder(s) sent.\n";
        return 0;
    }
}
