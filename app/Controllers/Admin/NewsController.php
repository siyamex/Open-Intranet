<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\NewsController as PublicNews;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\HtmlSanitizer;
use App\Core\ImageTool;
use App\Core\Notify;
use App\Core\Validator;
use App\Core\View;

final class NewsController
{
    public function index(): void
    {
        $status = in_array($_GET['status'] ?? '', ['draft', 'scheduled', 'published', 'archived'], true)
            ? (string) $_GET['status'] : '';
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = 'n.status = ?';
            $params[] = $status;
        }
        if (!Auth::can('news.publish')) {
            $where[] = 'n.author_id = ?'; // creators only see their own posts
            $params[] = Auth::id();
        }
        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $posts = DB::fetchAll(
            "SELECT n.*, c.name AS category_name, u.name AS author_name
             FROM news n
             LEFT JOIN news_categories c ON c.id = n.category_id
             LEFT JOIN users u ON u.id = n.author_id
             {$whereSql}
             ORDER BY n.updated_at DESC LIMIT 200",
            $params
        );
        View::render('admin/news/index', [
            'title' => 'News',
            'posts' => $posts,
            'status' => $status,
            'canPublish' => Auth::can('news.publish'),
        ], 'admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $post = $this->findOwn($id);
        $this->form($post);
    }

    public function store(): void
    {
        $data = $this->validated(null);
        if ($data === null) {
            redirect('admin/news/create');
        }
        $data['author_id'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('news', $data);
        $this->applyCover($id);
        Audit::log('news.created', 'news', $id, ['title' => $data['title'], 'status' => $data['status']]);
        if ($data['status'] === 'published') {
            $this->notifyPublished($id, $data['title'], $data['slug']);
        }
        flash('success', 'Post saved.');
        redirect('admin/news/' . $id . '/edit');
    }

    public function update(string $id): void
    {
        $post = $this->findOwn($id);
        $data = $this->validated((int) $id);
        if ($data === null) {
            redirect('admin/news/' . $id . '/edit');
        }
        $wasLive = $post['status'] === 'published';
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('news', $data, 'id = ?', [(int) $id]);
        $this->applyCover((int) $id);
        Audit::log('news.updated', 'news', (int) $id, ['title' => $data['title'], 'status' => $data['status']]);
        if (!$wasLive && $data['status'] === 'published') {
            $this->notifyPublished((int) $id, $data['title'], $data['slug']);
        }
        flash('success', 'Post updated.');
        redirect('admin/news/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $post = $this->findOwn($id);
        DB::delete('news', 'id = ?', [(int) $id]);
        Audit::log('news.deleted', 'news', (int) $id, ['title' => $post['title']]);
        flash('success', 'Post deleted.');
        redirect('admin/news');
    }

    public function archive(string $id): void
    {
        $post = $this->findOwn($id);
        DB::update('news', ['status' => 'archived', 'is_pinned' => 0], 'id = ?', [(int) $id]);
        Audit::log('news.archived', 'news', (int) $id, ['title' => $post['title']]);
        flash('success', 'Post archived.');
        redirect('admin/news');
    }

    public function pin(string $id): void
    {
        $post = $this->findOwn($id);
        if ((int) $post['is_pinned'] === 1) {
            DB::update('news', ['is_pinned' => 0], 'id = ?', [(int) $id]);
            flash('success', 'Post unpinned.');
        } else {
            // max 3 pinned — oldest pin auto-unpins
            $pinned = DB::fetchAll("SELECT id FROM news WHERE is_pinned = 1 ORDER BY updated_at ASC");
            if (count($pinned) >= 3) {
                DB::update('news', ['is_pinned' => 0], 'id = ?', [(int) $pinned[0]['id']]);
            }
            DB::update('news', ['is_pinned' => 1], 'id = ?', [(int) $id]);
            flash('success', 'Post pinned to the top.');
        }
        Audit::log('news.pin_toggled', 'news', (int) $id);
        redirect('admin/news');
    }

    /**
     * WYSIWYG image upload -> JSON {url}.
     */
    public function uploadImage(): void
    {
        header('Content-Type: application/json');
        $file = $_FILES['image'] ?? null;
        if (!is_array($file) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK || (int) $file['size'] > 8 * 1024 * 1024) {
            echo json_encode(['error' => 'Upload failed (max 8 MB).']);
            exit;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            echo json_encode(['error' => 'Only JPG, PNG, WebP or GIF images are allowed.']);
            exit;
        }
        $encoded = ImageTool::resizeEncode((string) file_get_contents((string) $file['tmp_name']), 1600, 'jpeg');
        if ($encoded === null) {
            echo json_encode(['error' => 'The image could not be processed.']);
            exit;
        }
        $dir = BASE_PATH . '/storage/uploads/news';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.jpg';
        file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
        echo json_encode(['url' => url('news.media', ['file' => $name])]);
        exit;
    }

    public function storeCategory(): void
    {
        header('Content-Type: application/json');
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            echo json_encode(['error' => 'Invalid category name.']);
            exit;
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'category';
        $existing = DB::fetch('SELECT id, name FROM news_categories WHERE slug = ?', [$slug]);
        if ($existing !== null) {
            echo json_encode(['id' => (int) $existing['id'], 'name' => $existing['name']]);
            exit;
        }
        $id = DB::insert('news_categories', ['name' => $name, 'slug' => $slug, 'color' => '#4f46e5']);
        Audit::log('news_category.created', 'news_category', $id, ['name' => $name]);
        echo json_encode(['id' => $id, 'name' => $name]);
        exit;
    }

    // ---- helpers ---------------------------------------------------------

    private function form(?array $post): void
    {
        View::render('admin/news/form', [
            'title' => $post === null ? 'New post' : 'Edit post',
            'post' => $post,
            'categories' => DB::fetchAll('SELECT * FROM news_categories ORDER BY name'),
            'canPublish' => Auth::can('news.publish'),
            'previewToken' => $post !== null ? PublicNews::previewToken((string) $post['slug']) : null,
        ], 'admin');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(?int $ignoreId): ?array
    {
        $v = new Validator($_POST, [
            'title' => 'required|max:255',
            'excerpt' => 'max:500',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            return null;
        }
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        if ($slug === '') {
            $slug = (string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $_POST['title']));
        }
        $slug = trim((string) preg_replace('/-{2,}/', '-', (string) preg_replace('/[^a-z0-9-]/', '-', $slug)), '-') ?: 'post';
        $existing = DB::fetch('SELECT id FROM news WHERE slug = ?', [$slug]);
        if ($existing !== null && (int) $existing['id'] !== $ignoreId) {
            $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 4);
        }

        $requestedStatus = (string) ($_POST['status'] ?? 'draft');
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        $status = 'draft';
        $publishedAtSql = null;
        if ($requestedStatus === 'published' && Auth::can('news.publish')) {
            if ($publishedAt !== '' && strtotime($publishedAt) > time()) {
                $status = 'scheduled';
                $publishedAtSql = date('Y-m-d H:i:s', (int) strtotime($publishedAt));
            } else {
                $status = 'published';
                $publishedAtSql = date('Y-m-d H:i:s');
            }
        } elseif ($requestedStatus === 'archived') {
            $status = 'archived';
        }

        return [
            'title' => trim((string) $_POST['title']),
            'slug' => $slug,
            'excerpt' => trim((string) ($_POST['excerpt'] ?? '')) ?: null,
            'body' => HtmlSanitizer::sanitize((string) ($_POST['body'] ?? '')),
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'status' => $status,
            'published_at' => $publishedAtSql,
            'allow_comments' => !empty($_POST['allow_comments']) ? 1 : 0,
        ];
    }

    private function applyCover(int $id): void
    {
        $file = $_FILES['cover'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > 8 * 1024 * 1024) {
            flash('warning', 'Cover skipped: upload failed or exceeds 8 MB.');
            return;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            flash('warning', 'Cover skipped: only JPG, PNG or WebP allowed.');
            return;
        }
        $encoded = ImageTool::resizeEncode((string) file_get_contents((string) $file['tmp_name']), 1600, 'jpeg');
        if ($encoded === null) {
            flash('warning', 'Cover skipped: the image could not be processed.');
            return;
        }
        $dir = BASE_PATH . '/storage/uploads/news';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.jpg';
        file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
        DB::update('news', ['cover_path' => 'news/' . $name], 'id = ?', [$id]);
    }

    private function notifyPublished(int $id, string $title, string $slug): void
    {
        $userIds = array_map('intval', array_column(DB::fetchAll(
            "SELECT u.id FROM users u
             WHERE u.status = 'active' AND u.id != ?
               AND NOT EXISTS (
                   SELECT 1 FROM user_prefs p
                   WHERE p.user_id = u.id AND p.`key` = 'notif_news' AND p.`value` = '0'
               )",
            [Auth::id()]
        ), 'id'));
        Notify::sendMany($userIds, 'news', 'News: ' . $title, null, base_url('news/' . $slug));
    }

    private function findOwn(string $id): array
    {
        $post = DB::fetch('SELECT * FROM news WHERE id = ?', [(int) $id]);
        if ($post === null) {
            flash('error', 'Post not found.');
            redirect('admin/news');
        }
        if (!Auth::can('news.publish') && (int) $post['author_id'] !== Auth::id()) {
            flash('error', 'You can only manage your own posts.');
            redirect('admin/news');
        }
        return $post;
    }
}
