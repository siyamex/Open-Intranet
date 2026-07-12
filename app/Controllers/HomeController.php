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
            } elseif ($section === 'news' && \App\Core\Modules::enabled('news')) {
                $count = (int) Settings::get('news_dashboard_count', 6);
                $data['pinnedPosts'] = $this->newsQuery('n.is_pinned = 1', 3);
                $pinnedIds = array_map('intval', array_column($data['pinnedPosts'], 'id'));
                $data['newsPosts'] = array_values(array_filter(
                    $this->newsQuery('1=1', $count + 3),
                    static fn (array $p): bool => !in_array((int) $p['id'], $pinnedIds, true)
                ));
                $data['newsPosts'] = array_slice($data['newsPosts'], 0, $count);
            }
            elseif ($section === 'kudos' && \App\Core\Modules::enabled('kudos')) {
                $data['latestKudos'] = \App\Controllers\KudosController::feed(4);
            } elseif ($section === 'poll' && \App\Core\Modules::enabled('polls')) {
                $data['activePoll'] = \App\Models\Poll::activeForUser();
            } elseif ($section === 'events' && \App\Core\Modules::enabled('events')) {
                $events = DB::fetchAll(
                    'SELECT id, title, location, starts_at, all_day, color, visible_to
                     FROM events WHERE ends_at >= NOW() ORDER BY starts_at LIMIT 15'
                );
                $data['upcomingEvents'] = array_slice(array_values(array_filter(
                    $events,
                    static fn (array $e): bool => \App\Core\Visibility::allowed($e['visible_to'])
                )), 0, 5);
            } elseif ($section === 'gazette' && \App\Core\Modules::enabled('documents')) {
                $count = (int) Settings::get('gazette_dashboard_count', 5);
                $docs = DB::fetchAll(
                    'SELECT d.*, c.visible_to AS category_visible_to
                     FROM documents d
                     LEFT JOIN doc_categories c ON c.id = d.category_id
                     WHERE d.is_gazette = 1 AND d.parent_doc_id IS NULL
                     ORDER BY d.published_at DESC, d.created_at DESC
                     LIMIT ' . ($count + 10)
                );
                $docs = array_values(array_filter($docs, static function (array $d): bool {
                    return \App\Core\Visibility::allowed($d['visible_to'])
                        && \App\Core\Visibility::allowed($d['category_visible_to']);
                }));
                $data['gazetteDocs'] = array_slice($docs, 0, $count);
            }
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
