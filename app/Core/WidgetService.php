<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\KudosController;
use App\Models\Celebrations;
use App\Models\Poll;
use App\Models\QuickLink;

/**
 * Widget registry + per-user layout resolution: personal layout (if the
 * user has customized and personalization is allowed) > their highest
 * role's default layout > the global fallback (role_id NULL) > built-in
 * defaults if nothing is configured yet.
 */
final class WidgetService
{
    /**
     * @return array<int, array{slug: string, name: string, type: string, width: string, module: ?string}>
     */
    public static function layoutForUser(int $userId): array
    {
        $personalizeAllowed = (bool) Settings::get('allow_widget_personalization', true);
        if ($personalizeAllowed) {
            $rows = DB::fetchAll(
                'SELECT ul.widget_slug, ul.sort_order, ul.width, w.name, w.type, w.module, w.is_active
                 FROM user_layouts ul JOIN widgets w ON w.slug = ul.widget_slug
                 WHERE ul.user_id = ? ORDER BY ul.sort_order',
                [$userId]
            );
            if ($rows !== []) {
                return self::filterVisible($rows, $userId);
            }
        }

        $roleIds = array_map('intval', array_column(DB::fetchAll(
            'SELECT role_id FROM user_role WHERE user_id = ?',
            [$userId]
        ), 'role_id'));

        if ($roleIds !== []) {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $rows = DB::fetchAll(
                "SELECT rl.widget_slug, MIN(rl.sort_order) AS sort_order, rl.width, w.name, w.type, w.module, w.is_active
                 FROM role_layouts rl JOIN widgets w ON w.slug = rl.widget_slug
                 WHERE rl.role_id IN ({$placeholders})
                 GROUP BY rl.widget_slug, rl.width, w.name, w.type, w.module, w.is_active
                 ORDER BY sort_order",
                $roleIds
            );
            if ($rows !== []) {
                return self::filterVisible($rows, $userId);
            }
        }

        $rows = DB::fetchAll(
            'SELECT rl.widget_slug, rl.sort_order, rl.width, w.name, w.type, w.module, w.is_active
             FROM role_layouts rl JOIN widgets w ON w.slug = rl.widget_slug
             WHERE rl.role_id IS NULL ORDER BY rl.sort_order'
        );
        if ($rows !== []) {
            return self::filterVisible($rows, $userId);
        }

        // Absolute fallback: every active builtin widget, in registry order.
        $rows = DB::fetchAll("SELECT slug AS widget_slug, name, type, module, is_active, 0 AS sort_order, 'full' AS width FROM widgets WHERE is_active = 1");
        return self::filterVisible($rows, $userId);
    }

    private static function filterVisible(array $rows, int $userId): array
    {
        $out = [];
        foreach ($rows as $row) {
            if ((int) ($row['is_active'] ?? 1) !== 1) {
                continue;
            }
            if (!empty($row['module']) && !Modules::enabled((string) $row['module'])) {
                continue;
            }
            $out[] = [
                'slug' => (string) $row['widget_slug'],
                'name' => (string) $row['name'],
                'type' => (string) $row['type'],
                'width' => (string) ($row['width'] ?? 'full'),
                'module' => $row['module'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * @return string[] widget slugs an admin/user is allowed to add
     */
    public static function availableSlugs(): array
    {
        return array_column(DB::fetchAll('SELECT slug FROM widgets WHERE is_active = 1'), 'slug');
    }

    /**
     * Render one widget's inner HTML (used by the lazy-load endpoint).
     * Returns null if the widget has nothing to show for this user.
     */
    public static function render(string $slug, int $userId): ?string
    {
        $widget = DB::fetch('SELECT * FROM widgets WHERE slug = ? AND is_active = 1', [$slug]);
        if ($widget === null) {
            return null;
        }
        if (!empty($widget['module']) && !Modules::enabled((string) $widget['module'])) {
            return null;
        }

        if ($widget['type'] === 'html') {
            $config = json_decode((string) ($widget['config'] ?? '{}'), true) ?: [];
            $html = (string) ($config['html'] ?? '');
            return '<section class="home-section"><div class="card">' . HtmlSanitizer::sanitize($html) . '</div></section>';
        }
        if ($widget['type'] === 'rss') {
            return self::renderRss($widget);
        }

        return match ($slug) {
            'quick_links' => View::fetch('partials/home/quick-links', ['quickLinks' => QuickLink::forUser($userId)]),
            'news' => self::renderNews(),
            'gazette' => self::renderGazette(),
            'events' => self::renderEvents(),
            'poll' => self::renderPoll(),
            'kudos' => self::renderKudos(),
            'celebrations' => self::renderCelebrations(),
            default => null,
        };
    }

    private static function renderNews(): string
    {
        $count = (int) Settings::get('news_dashboard_count', 6);
        $base = "SELECT n.*, c.name AS category_name, c.color AS category_color, u.name AS author_name, u.avatar_path AS author_avatar
                  FROM news n LEFT JOIN news_categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id
                  WHERE n.status = 'published' AND n.published_at <= NOW()";
        $pinned = DB::fetchAll($base . ' AND n.is_pinned = 1 ORDER BY n.published_at DESC LIMIT 3');
        $pinnedIds = array_map('intval', array_column($pinned, 'id'));
        $rest = array_values(array_filter(
            DB::fetchAll($base . ' ORDER BY n.published_at DESC LIMIT ' . ($count + 3)),
            static fn (array $p): bool => !in_array((int) $p['id'], $pinnedIds, true)
        ));
        return View::fetch('partials/home/news', ['pinnedPosts' => $pinned, 'newsPosts' => array_slice($rest, 0, $count)]);
    }

    private static function renderGazette(): string
    {
        $count = (int) Settings::get('gazette_dashboard_count', 5);
        $docs = DB::fetchAll(
            'SELECT d.*, c.visible_to AS category_visible_to FROM documents d
             LEFT JOIN doc_categories c ON c.id = d.category_id
             WHERE d.is_gazette = 1 AND d.parent_doc_id IS NULL
             ORDER BY d.published_at DESC, d.created_at DESC LIMIT ' . ($count + 10)
        );
        $docs = array_values(array_filter($docs, static fn (array $d): bool =>
            Visibility::allowed($d['visible_to']) && Visibility::allowed($d['category_visible_to'])));
        return View::fetch('partials/home/gazette', ['gazetteDocs' => array_slice($docs, 0, $count)]);
    }

    private static function renderEvents(): string
    {
        $events = DB::fetchAll(
            'SELECT id, title, location, starts_at, all_day, color, visible_to
             FROM events WHERE ends_at >= NOW() ORDER BY starts_at LIMIT 15'
        );
        $events = array_slice(array_values(array_filter(
            $events,
            static fn (array $e): bool => Visibility::allowed($e['visible_to'])
        )), 0, 5);
        return View::fetch('partials/home/events', ['upcomingEvents' => $events]);
    }

    private static function renderPoll(): ?string
    {
        $poll = Poll::activeForUser();
        return $poll !== null ? View::fetch('partials/home/poll', ['activePoll' => $poll]) : null;
    }

    private static function renderKudos(): string
    {
        return View::fetch('partials/home/kudos', ['latestKudos' => KudosController::feed(4)]);
    }

    private static function renderCelebrations(): ?string
    {
        $celebrations = Celebrations::upcoming(7);
        if ($celebrations['birthdays'] === [] && $celebrations['anniversaries'] === []) {
            return null;
        }
        return View::fetch('partials/home/celebrations', ['celebrations' => $celebrations]);
    }

    private static function renderRss(array $widget): string
    {
        $config = json_decode((string) ($widget['config'] ?? '{}'), true) ?: [];
        $url = (string) ($config['url'] ?? '');
        $limit = max(1, min(15, (int) ($config['limit'] ?? 5)));
        if ($url === '') {
            return '';
        }
        $cacheFile = BASE_PATH . '/storage/cache/rss-' . sha1($url) . '.json';
        $items = null;
        if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < 900) {
            $items = json_decode((string) file_get_contents($cacheFile), true);
        }
        if (!is_array($items)) {
            try {
                Http::assertAllowedUrl($url);
                $xml = Http::get($url, [], 6)['body'];
                $items = self::parseRss($xml, $limit);
                @file_put_contents($cacheFile, json_encode($items), LOCK_EX);
            } catch (\Throwable) {
                $items = [];
            }
        }
        $html = '<section class="home-section"><div class="home-section-head"><h2>' . e((string) $widget['name']) . '</h2></div><div class="card" style="padding:0.5rem 1rem;"><ul class="gazette-list">';
        foreach ($items as $item) {
            $html .= '<li><span class="gazette-title"><a href="' . e((string) $item['link']) . '" target="_blank" rel="noopener noreferrer">' . e((string) $item['title']) . '</a></span></li>';
        }
        if ($items === []) {
            $html .= '<li class="text-muted">No items available.</li>';
        }
        $html .= '</ul></div></section>';
        return $html;
    }

    /**
     * @return array<int, array{title: string, link: string}>
     */
    private static function parseRss(string $xml, int $limit): array
    {
        $items = [];
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        if ($doc->loadXML($xml, LIBXML_NONET)) {
            foreach ($doc->getElementsByTagName('item') as $node) {
                if (count($items) >= $limit) {
                    break;
                }
                $title = $node->getElementsByTagName('title')->item(0)?->textContent ?? '';
                $link = $node->getElementsByTagName('link')->item(0)?->textContent ?? '';
                if ($title !== '' && preg_match('#^https?://#i', $link)) {
                    $items[] = ['title' => $title, 'link' => $link];
                }
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $items;
    }
}
