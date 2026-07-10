<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Settings;
use App\Core\View;
use App\Models\QuickLink;

final class HomeController
{
    public function index(): void
    {
        $sections = Settings::get('homepage_sections', ['quick_links', 'news', 'gazette']);
        if (!is_array($sections)) {
            $sections = ['quick_links', 'news', 'gazette'];
        }
        $data = ['title' => 'Home', 'sections' => $sections];
        foreach ($sections as $section) {
            if ($section === 'quick_links') {
                $data['quickLinks'] = QuickLink::forUser((int) Auth::id());
            } elseif ($section === 'news') {
                $count = (int) Settings::get('news_dashboard_count', 6);
                $data['pinnedPosts'] = $this->newsQuery('n.is_pinned = 1', 3);
                $pinnedIds = array_map('intval', array_column($data['pinnedPosts'], 'id'));
                $data['newsPosts'] = array_values(array_filter(
                    $this->newsQuery('1=1', $count + 3),
                    static fn (array $p): bool => !in_array((int) $p['id'], $pinnedIds, true)
                ));
                $data['newsPosts'] = array_slice($data['newsPosts'], 0, $count);
            }
            // gazette section attaches its data once the documents module exists
        }
        View::render('pages/home', $data);
    }

    private function newsQuery(string $extraWhere, int $limit): array
    {
        return DB::fetchAll(
            "SELECT n.*, c.name AS category_name, c.color AS category_color,
                    u.name AS author_name, u.avatar_path AS author_avatar
             FROM news n
             LEFT JOIN news_categories c ON c.id = n.category_id
             LEFT JOIN users u ON u.id = n.author_id
             WHERE n.status = 'published' AND n.published_at <= NOW() AND {$extraWhere}
             ORDER BY n.published_at DESC
             LIMIT {$limit}"
        );
    }
}
