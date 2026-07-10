<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Settings;
use App\Core\View;

final class NewsController
{
    private const PER_PAGE = 9;

    public function index(): void
    {
        $where = ["n.status = 'published'", 'n.published_at <= NOW()'];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(n.title LIKE ? OR n.excerpt LIKE ?)';
            array_push($params, '%' . $q . '%', '%' . $q . '%');
        }
        $category = (int) ($_GET['category'] ?? 0);
        if ($category > 0) {
            $where[] = 'n.category_id = ?';
            $params[] = $category;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) DB::scalar("SELECT COUNT(*) FROM news n WHERE {$whereSql}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $pages));
        $offset = ($page - 1) * self::PER_PAGE;

        $posts = DB::fetchAll(
            "SELECT n.*, c.name AS category_name, c.color AS category_color,
                    u.name AS author_name, u.avatar_path AS author_avatar
             FROM news n
             LEFT JOIN news_categories c ON c.id = n.category_id
             LEFT JOIN users u ON u.id = n.author_id
             WHERE {$whereSql}
             ORDER BY n.is_pinned DESC, n.published_at DESC
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );
        View::render('pages/news/index', [
            'title' => 'News',
            'posts' => $posts,
            'categories' => DB::fetchAll('SELECT * FROM news_categories ORDER BY name'),
            'q' => $q,
            'category' => $category,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function show(string $slug): void
    {
        $post = DB::fetch(
            'SELECT n.*, c.name AS category_name, c.color AS category_color,
                    u.id AS author_uid, u.name AS author_name, u.avatar_path AS author_avatar, u.job_title AS author_title
             FROM news n
             LEFT JOIN news_categories c ON c.id = n.category_id
             LEFT JOIN users u ON u.id = n.author_id
             WHERE n.slug = ?',
            [$slug]
        );
        if ($post === null) {
            $this->notFound();
        }

        $isLive = $post['status'] === 'published' && strtotime((string) $post['published_at']) <= time();
        if (!$isLive) {
            // Draft/scheduled: author, publishers, or a valid signed preview token.
            $token = (string) ($_GET['preview'] ?? '');
            $validToken = $token !== '' && hash_equals(self::previewToken($slug), $token);
            $mayPreview = $validToken || Auth::can('news.publish') || (int) $post['author_id'] === Auth::id();
            if (!$mayPreview) {
                $this->notFound();
            }
        } else {
            // View counter, deduplicated per session
            $viewed = (array) ($_SESSION['viewed_news'] ?? []);
            if (!in_array((int) $post['id'], $viewed, true)) {
                DB::run('UPDATE news SET views = views + 1 WHERE id = ?', [(int) $post['id']]);
                $viewed[] = (int) $post['id'];
                $_SESSION['viewed_news'] = $viewed;
                $post['views'] = (int) $post['views'] + 1;
            }
        }

        $prev = DB::fetch(
            "SELECT title, slug FROM news WHERE status = 'published' AND published_at < ? AND published_at <= NOW()
             ORDER BY published_at DESC LIMIT 1",
            [$post['published_at']]
        );
        $next = DB::fetch(
            "SELECT title, slug FROM news WHERE status = 'published' AND published_at > ? AND published_at <= NOW()
             ORDER BY published_at ASC LIMIT 1",
            [$post['published_at']]
        );

        $commentsEnabled = (bool) Settings::get('comments_enabled', true) && (int) $post['allow_comments'] === 1;
        $comments = $commentsEnabled ? DB::fetchAll(
            'SELECT nc.*, u.name AS user_name, u.avatar_path AS user_avatar
             FROM news_comments nc JOIN users u ON u.id = nc.user_id
             WHERE nc.news_id = ? ORDER BY nc.created_at',
            [(int) $post['id']]
        ) : [];

        $reactionsEnabled = (bool) Settings::get('reactions_enabled', true);
        $reactions = [];
        $myReactions = [];
        if ($reactionsEnabled) {
            foreach (DB::fetchAll(
                'SELECT emoji, COUNT(*) AS n, MAX(user_id = ?) AS mine
                 FROM news_reactions WHERE news_id = ? GROUP BY emoji',
                [Auth::id(), (int) $post['id']]
            ) as $row) {
                $reactions[$row['emoji']] = (int) $row['n'];
                if ((int) $row['mine'] === 1) {
                    $myReactions[] = $row['emoji'];
                }
            }
        }

        View::render('pages/news/show', [
            'title' => (string) $post['title'],
            'post' => $post,
            'isLive' => $isLive,
            'prev' => $prev,
            'next' => $next,
            'commentsEnabled' => $commentsEnabled,
            'comments' => $comments,
            'reactionsEnabled' => $reactionsEnabled,
            'reactions' => $reactions,
            'myReactions' => $myReactions,
            'breadcrumbs' => [['News', url('news.index')], [(string) $post['title'], null]],
        ]);
    }

    public function comment(string $slug): void
    {
        $post = DB::fetch("SELECT * FROM news WHERE slug = ? AND status = 'published'", [$slug]);
        if ($post === null || !(bool) Settings::get('comments_enabled', true) || (int) $post['allow_comments'] !== 1) {
            $this->notFound();
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 2000) {
            flash('error', 'Comments must be between 1 and 2000 characters.');
            redirect('news/' . rawurlencode($slug));
        }
        DB::insert('news_comments', [
            'news_id' => (int) $post['id'],
            'user_id' => Auth::id(),
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        flash('success', 'Comment posted.');
        redirect('news/' . rawurlencode($slug) . '#comments');
    }

    public function react(string $slug): void
    {
        $post = DB::fetch("SELECT id FROM news WHERE slug = ? AND status = 'published'", [$slug]);
        $allowed = ['👍', '🎉', '❤️', '💡', '😄'];
        $emoji = (string) ($_POST['emoji'] ?? '');
        if ($post === null || !(bool) Settings::get('reactions_enabled', true) || !in_array($emoji, $allowed, true)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false]);
            exit;
        }
        $existing = DB::fetch(
            'SELECT id FROM news_reactions WHERE news_id = ? AND user_id = ? AND emoji = ?',
            [(int) $post['id'], Auth::id(), $emoji]
        );
        if ($existing !== null) {
            DB::delete('news_reactions', 'id = ?', [(int) $existing['id']]);
            $mine = false;
        } else {
            DB::run(
                'INSERT IGNORE INTO news_reactions (news_id, user_id, emoji) VALUES (?, ?, ?)',
                [(int) $post['id'], Auth::id(), $emoji]
            );
            $mine = true;
        }
        $count = (int) DB::scalar(
            'SELECT COUNT(*) FROM news_reactions WHERE news_id = ? AND emoji = ?',
            [(int) $post['id'], $emoji]
        );
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'count' => $count, 'mine' => $mine]);
        exit;
    }

    public static function previewToken(string $slug): string
    {
        return hash_hmac('sha256', 'news-preview:' . $slug, (string) Config::env('APP_KEY', ''));
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], null);
        exit;
    }
}
