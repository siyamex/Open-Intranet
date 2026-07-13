<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\DB;

final class NewsApiController extends BaseApiController
{
    public function index(): void
    {
        ['page' => $page, 'per_page' => $perPage, 'offset' => $offset] = $this->pagination();
        $total = (int) DB::scalar("SELECT COUNT(*) FROM news WHERE status = 'published' AND published_at <= NOW()");
        $rows = DB::fetchAll(
            "SELECT n.id, n.title, n.slug, n.excerpt, n.published_at, n.views, c.name AS category, u.name AS author
             FROM news n LEFT JOIN news_categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id
             WHERE n.status = 'published' AND n.published_at <= NOW()
             ORDER BY n.published_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        foreach ($rows as &$row) {
            $row['url'] = url('news.show', ['slug' => $row['slug']]);
        }
        $this->ok($rows, $this->meta($page, $perPage, $total));
    }

    public function show(string $slug): void
    {
        $post = DB::fetch(
            "SELECT n.*, c.name AS category, u.name AS author FROM news n
             LEFT JOIN news_categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id
             WHERE n.slug = ? AND n.status = 'published' AND n.published_at <= NOW()",
            [$slug]
        );
        if ($post === null) {
            self::fail(404, 'not_found', 'Post not found.');
        }
        $this->ok([
            'id' => (int) $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'excerpt' => $post['excerpt'],
            'body' => $post['body'],
            'category' => $post['category'],
            'author' => $post['author'],
            'published_at' => $post['published_at'],
            'views' => (int) $post['views'],
        ]);
    }
}
