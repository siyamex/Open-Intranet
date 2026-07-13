<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Modules;
use App\Core\Visibility;
use App\Core\View;

/**
 * Unified search across news, documents, people and quick links — each
 * group filtered by its module's own visibility rules.
 */
final class SearchController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $results = null;
        if ($q !== '' && mb_strlen($q) >= 2) {
            $results = self::search($q);
            $this->remember($q, array_sum(array_map('count', $results)));
        }
        if (($_GET['format'] ?? '') === 'json') {
            header('Content-Type: application/json');
            echo json_encode(['q' => $q, 'groups' => $results !== null ? self::toJson($results) : []]);
            exit;
        }
        View::render('pages/search', [
            'title' => 'Search',
            'q' => $q,
            'results' => $results,
            'recent' => array_column(DB::fetchAll(
                'SELECT query FROM search_history WHERE user_id = ? GROUP BY query ORDER BY MAX(created_at) DESC LIMIT 8',
                [Auth::id()]
            ), 'query'),
        ]);
    }

    /**
     * @return array<string, array<int, array{title: string, snippet: string, url: string, meta: string}>>
     */
    public static function search(string $q): array
    {
        $groups = [];

        if (Modules::enabled('news')) {
            $rows = self::fulltext(
                "SELECT id, title, slug, excerpt, body, published_at FROM news
                 WHERE status = 'published' AND published_at <= NOW() AND %MATCH% LIMIT 8",
                'MATCH(title, body) AGAINST (? IN NATURAL LANGUAGE MODE)',
                '(title LIKE ? OR body LIKE ?)',
                $q,
                2
            );
            $groups['News'] = array_map(static fn (array $r): array => [
                'title' => (string) $r['title'],
                'snippet' => self::snippet((string) ($r['excerpt'] ?: strip_tags((string) $r['body'])), $q),
                'url' => url('news.show', ['slug' => $r['slug']]),
                'meta' => date('j M Y', strtotime((string) $r['published_at'])),
            ], $rows);
        }

        if (Modules::enabled('documents')) {
            $rows = self::fulltext(
                'SELECT d.*, c.visible_to AS category_visible_to FROM documents d
                 LEFT JOIN doc_categories c ON c.id = d.category_id
                 WHERE d.parent_doc_id IS NULL AND %MATCH% LIMIT 12',
                'MATCH(d.title, d.description) AGAINST (? IN NATURAL LANGUAGE MODE)',
                '(d.title LIKE ? OR d.description LIKE ?)',
                $q,
                2
            );
            $rows = array_values(array_filter($rows, static fn (array $d): bool =>
                Visibility::allowed($d['visible_to']) && Visibility::allowed($d['category_visible_to'])));
            $groups['Documents'] = array_map(static fn (array $r): array => [
                'title' => (string) $r['title'],
                'snippet' => self::snippet((string) ($r['description'] ?? ''), $q),
                'url' => url('files.serve', ['uuid' => $r['uuid']]),
                'meta' => strtoupper(pathinfo((string) $r['original_name'], PATHINFO_EXTENSION)) . ' · v' . $r['version'],
            ], array_slice($rows, 0, 8));
        }

        if (Modules::enabled('directory')) {
            $rows = self::fulltext(
                "SELECT id, name, job_title, email FROM users WHERE status = 'active' AND %MATCH% LIMIT 8",
                'MATCH(name, job_title) AGAINST (? IN NATURAL LANGUAGE MODE)',
                '(name LIKE ? OR job_title LIKE ? OR email LIKE ?)',
                $q,
                3
            );
            $groups['People'] = array_map(static fn (array $r): array => [
                'title' => (string) $r['name'],
                'snippet' => self::snippet((string) ($r['job_title'] ?? ''), $q),
                'url' => url('people.show', ['id' => $r['id']]),
                'meta' => (string) $r['email'],
            ], $rows);
        }

        if (Modules::enabled('wiki')) {
            $rows = self::fulltext(
                'SELECT p.id, p.title, p.slug, p.body_md, s.slug AS space_slug, s.name AS space_name, s.visible_to
                 FROM wiki_pages p JOIN wiki_spaces s ON s.id = p.space_id
                 WHERE %MATCH% LIMIT 8',
                'MATCH(p.title, p.body_md) AGAINST (? IN NATURAL LANGUAGE MODE)',
                '(p.title LIKE ? OR p.body_md LIKE ?)',
                $q,
                2
            );
            $rows = array_values(array_filter($rows, static fn (array $p): bool => Visibility::allowed($p['visible_to'])));
            $groups['Wiki'] = array_map(static fn (array $r): array => [
                'title' => (string) $r['title'],
                'snippet' => self::snippet(mb_substr((string) ($r['body_md'] ?? ''), 0, 400), $q),
                'url' => url('wiki.page', ['slug' => $r['space_slug'], 'pageSlug' => $r['slug']]),
                'meta' => (string) $r['space_name'],
            ], $rows);
        }

        $rows = self::fulltext(
            'SELECT * FROM quick_links WHERE is_active = 1 AND %MATCH% LIMIT 6',
            'MATCH(title) AGAINST (? IN NATURAL LANGUAGE MODE)',
            '(title LIKE ? OR description LIKE ?)',
            $q,
            2
        );
        $rows = array_values(array_filter($rows, static fn (array $l): bool => Visibility::allowed($l['visible_to'])));
        $groups['Links'] = array_map(static fn (array $r): array => [
            'title' => (string) $r['title'],
            'snippet' => self::snippet((string) ($r['description'] ?? ''), $q),
            'url' => (string) $r['url'],
            'meta' => 'quick link',
        ], $rows);

        return array_filter($groups, static fn (array $g): bool => $g !== []);
    }

    /**
     * FULLTEXT first; LIKE fallback when the term is too short or unmatched.
     */
    private static function fulltext(string $sqlTemplate, string $matchClause, string $likeClause, string $q, int $likeParams): array
    {
        if (mb_strlen($q) >= 3) {
            $rows = DB::fetchAll(str_replace('%MATCH%', $matchClause, $sqlTemplate), [$q]);
            if ($rows !== []) {
                return $rows;
            }
        }
        $like = '%' . $q . '%';
        return DB::fetchAll(str_replace('%MATCH%', $likeClause, $sqlTemplate), array_fill(0, $likeParams, $like));
    }

    /**
     * XSS-safe highlighted snippet: escape first, then mark the terms.
     */
    public static function snippet(string $text, string $q, int $length = 140): string
    {
        $text = trim($text);
        // center the snippet on the first match when possible
        $pos = mb_stripos($text, $q);
        if ($pos !== false && $pos > 40) {
            $text = '…' . mb_substr($text, $pos - 30);
        }
        $text = mb_strimwidth($text, 0, $length, '…');
        $escaped = e($text);
        foreach (array_slice(preg_split('/\s+/', $q) ?: [], 0, 5) as $term) {
            if (mb_strlen($term) < 2) {
                continue;
            }
            $escaped = (string) preg_replace(
                '/(' . preg_quote(e($term), '/') . ')/iu',
                '<mark>$1</mark>',
                $escaped
            );
        }
        return $escaped;
    }

    private function remember(string $q, int $resultCount = 0): void
    {
        DB::insert('search_history', [
            'user_id' => Auth::id(),
            'query' => mb_substr($q, 0, 190),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            DB::insert('search_queries_log', [
                'query' => mb_substr($q, 0, 190),
                'result_count' => $resultCount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // analytics table optional
        }
        // keep the history small
        DB::run(
            'DELETE FROM search_history WHERE user_id = ? AND id NOT IN (
                SELECT id FROM (SELECT id FROM search_history WHERE user_id = ? ORDER BY id DESC LIMIT 50) keep
            )',
            [Auth::id(), Auth::id()]
        );
    }

    /**
     * @return array<int, array{label: string, items: array}>
     */
    private static function toJson(array $results): array
    {
        $out = [];
        foreach ($results as $label => $items) {
            $out[] = ['label' => $label, 'items' => $items];
        }
        return $out;
    }
}
