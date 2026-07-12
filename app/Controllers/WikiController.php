<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Diff;
use App\Core\Markdown;
use App\Core\Notify;
use App\Core\Visibility;
use App\Core\View;

final class WikiController
{
    public function index(): void
    {
        $spaces = array_values(array_filter(
            DB::fetchAll(
                'SELECT s.*, (SELECT COUNT(*) FROM wiki_pages p WHERE p.space_id = s.id) AS page_count
                 FROM wiki_spaces s ORDER BY s.name'
            ),
            static fn (array $s): bool => Visibility::allowed($s['visible_to'])
        ));
        View::render('pages/wiki/index', [
            'title' => 'Wiki',
            'spaces' => $spaces,
            'canManage' => Auth::can('wiki.manage'),
        ]);
    }

    public function storeSpace(): void
    {
        if (!Auth::can('wiki.manage')) {
            $this->forbidden();
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            flash('error', 'Space name is required.');
            redirect('wiki');
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'space';
        while (DB::fetch('SELECT id FROM wiki_spaces WHERE slug = ?', [$slug]) !== null) {
            $slug .= '-2';
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $id = DB::insert('wiki_spaces', [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('wiki.space_created', 'wiki_space', $id, ['name' => $name]);
        redirect('wiki/' . $slug);
    }

    public function space(string $slug): void
    {
        $space = $this->findSpace($slug);
        $first = DB::fetch(
            'SELECT slug FROM wiki_pages WHERE space_id = ? ORDER BY parent_id IS NULL DESC, sort_order, id LIMIT 1',
            [(int) $space['id']]
        );
        if ($first !== null) {
            redirect('wiki/' . $slug . '/' . $first['slug']);
        }
        View::render('pages/wiki/space', [
            'title' => (string) $space['name'],
            'space' => $space,
            'tree' => [],
            'page' => null,
            'rendered' => null,
            'toc' => [],
            'canEdit' => Auth::can('wiki.edit'),
            'breadcrumbs' => [['Wiki', url('wiki.index')], [(string) $space['name'], null]],
        ]);
    }

    public function page(string $slug, string $pageSlug): void
    {
        $space = $this->findSpace($slug);
        $page = $this->findPage($space, $pageSlug);
        $result = Markdown::render((string) ($page['body_md'] ?? ''));
        View::render('pages/wiki/space', [
            'title' => (string) $page['title'],
            'space' => $space,
            'tree' => $this->tree((int) $space['id']),
            'page' => $page,
            'rendered' => $result['html'],
            'toc' => $result['toc'],
            'canEdit' => Auth::can('wiki.edit'),
            'breadcrumbs' => [['Wiki', url('wiki.index')], [(string) $space['name'], url('wiki.space', ['slug' => $slug])], [(string) $page['title'], null]],
        ]);
    }

    public function edit(string $slug, string $pageSlug): void
    {
        $this->requireEdit();
        $space = $this->findSpace($slug);
        $page = $pageSlug === '_new' ? null : $this->findPage($space, $pageSlug);
        View::render('pages/wiki/edit', [
            'title' => $page === null ? 'New page' : 'Edit — ' . $page['title'],
            'space' => $space,
            'page' => $page,
            'pages' => DB::fetchAll('SELECT id, title FROM wiki_pages WHERE space_id = ? ORDER BY title', [(int) $space['id']]),
            'people' => DB::fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name"),
        ]);
    }

    public function save(string $slug): void
    {
        $this->requireEdit();
        $space = $this->findSpace($slug);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 255) {
            flash('error', 'Page title is required.');
            redirect('wiki/' . $slug);
        }
        $body = (string) ($_POST['body_md'] ?? '');
        $pageId = (int) ($_POST['page_id'] ?? 0);
        $now = date('Y-m-d H:i:s');
        $reviewDue = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['review_due'] ?? '')) ? (string) $_POST['review_due'] : null;
        $ownerId = !empty($_POST['owner_id']) ? (int) $_POST['owner_id'] : Auth::id();
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        if ($pageId > 0) {
            $page = DB::fetch('SELECT * FROM wiki_pages WHERE id = ? AND space_id = ?', [$pageId, (int) $space['id']]);
            if ($page === null) {
                flash('error', 'Page not found.');
                redirect('wiki/' . $slug);
            }
            // snapshot the previous state
            DB::insert('wiki_versions', [
                'page_id' => $pageId,
                'title' => $page['title'],
                'body_md' => $page['body_md'],
                'edited_by' => $page['updated_by'] ?? $page['owner_id'],
                'created_at' => $page['updated_at'],
            ]);
            DB::update('wiki_pages', [
                'title' => $title,
                'body_md' => $body,
                'owner_id' => $ownerId,
                'review_due' => $reviewDue,
                'parent_id' => $parentId === $pageId ? null : $parentId,
                'updated_by' => Auth::id(),
                'updated_at' => $now,
            ], 'id = ?', [$pageId]);
            $pageSlug = (string) $page['slug'];
            Audit::log('wiki.page_updated', 'wiki_page', $pageId, ['title' => $title]);
        } else {
            $pageSlug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'page';
            while (DB::fetch('SELECT id FROM wiki_pages WHERE space_id = ? AND slug = ?', [(int) $space['id'], $pageSlug]) !== null) {
                $pageSlug .= '-2';
            }
            $pageId = DB::insert('wiki_pages', [
                'space_id' => (int) $space['id'],
                'parent_id' => $parentId,
                'title' => $title,
                'slug' => $pageSlug,
                'body_md' => $body,
                'owner_id' => $ownerId,
                'review_due' => $reviewDue,
                'updated_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            Audit::log('wiki.page_created', 'wiki_page', $pageId, ['title' => $title]);
        }
        flash('success', 'Page saved.');
        redirect('wiki/' . $slug . '/' . $pageSlug);
    }

    /**
     * Live preview endpoint for the side-by-side editor.
     */
    public function preview(): void
    {
        $result = Markdown::render((string) ($_POST['body_md'] ?? ''));
        header('Content-Type: application/json');
        echo json_encode(['html' => $result['html']]);
        exit;
    }

    public function versions(string $slug, string $pageSlug): void
    {
        $space = $this->findSpace($slug);
        $page = $this->findPage($space, $pageSlug);
        View::render('pages/wiki/versions', [
            'title' => 'History — ' . $page['title'],
            'space' => $space,
            'page' => $page,
            'versions' => DB::fetchAll(
                'SELECT v.*, u.name AS editor_name FROM wiki_versions v
                 LEFT JOIN users u ON u.id = v.edited_by
                 WHERE v.page_id = ? ORDER BY v.id DESC LIMIT 50',
                [(int) $page['id']]
            ),
            'breadcrumbs' => [['Wiki', url('wiki.index')], [(string) $page['title'], url('wiki.page', ['slug' => $slug, 'pageSlug' => $pageSlug])], ['History', null]],
        ]);
    }

    public function diff(string $slug, string $pageSlug, string $versionId): void
    {
        $space = $this->findSpace($slug);
        $page = $this->findPage($space, $pageSlug);
        $version = DB::fetch('SELECT * FROM wiki_versions WHERE id = ? AND page_id = ?', [(int) $versionId, (int) $page['id']]);
        if ($version === null) {
            flash('error', 'Version not found.');
            redirect('wiki/' . $slug . '/' . $pageSlug);
        }
        View::render('pages/wiki/diff', [
            'title' => 'Diff — ' . $page['title'],
            'space' => $space,
            'page' => $page,
            'version' => $version,
            'diff' => Diff::words((string) ($version['body_md'] ?? ''), (string) ($page['body_md'] ?? '')),
        ]);
    }

    public function restore(string $slug, string $pageSlug, string $versionId): void
    {
        $this->requireEdit();
        $space = $this->findSpace($slug);
        $page = $this->findPage($space, $pageSlug);
        $version = DB::fetch('SELECT * FROM wiki_versions WHERE id = ? AND page_id = ?', [(int) $versionId, (int) $page['id']]);
        if ($version === null) {
            flash('error', 'Version not found.');
            redirect('wiki/' . $slug . '/' . $pageSlug);
        }
        DB::insert('wiki_versions', [
            'page_id' => (int) $page['id'],
            'title' => $page['title'],
            'body_md' => $page['body_md'],
            'edited_by' => $page['updated_by'],
            'created_at' => $page['updated_at'],
        ]);
        DB::update('wiki_pages', [
            'title' => $version['title'],
            'body_md' => $version['body_md'],
            'updated_by' => Auth::id(),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $page['id']]);
        Audit::log('wiki.page_restored', 'wiki_page', (int) $page['id'], ['version' => (int) $versionId]);
        flash('success', 'Version restored.');
        redirect('wiki/' . $slug . '/' . $pageSlug);
    }

    // ---- helpers ----------------------------------------------------------

    /**
     * @return array<int, array> root pages with nested children
     */
    private function tree(int $spaceId): array
    {
        $rows = DB::fetchAll(
            'SELECT id, parent_id, title, slug FROM wiki_pages WHERE space_id = ? ORDER BY sort_order, title',
            [$spaceId]
        );
        $byId = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $byId[(int) $row['id']] = $row;
        }
        $tree = [];
        foreach ($byId as $id => &$row) {
            $parent = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            if ($parent !== null && isset($byId[$parent])) {
                $byId[$parent]['children'][] = &$row;
            } else {
                $tree[] = &$row;
            }
        }
        unset($row);
        return $tree;
    }

    private function findSpace(string $slug): array
    {
        $space = DB::fetch('SELECT * FROM wiki_spaces WHERE slug = ?', [$slug]);
        if ($space === null || !Visibility::allowed($space['visible_to'])) {
            http_response_code(404);
            View::render('errors/404', [], null);
            exit;
        }
        return $space;
    }

    private function findPage(array $space, string $pageSlug): array
    {
        $page = DB::fetch(
            'SELECT p.*, o.name AS owner_name FROM wiki_pages p
             LEFT JOIN users o ON o.id = p.owner_id
             WHERE p.space_id = ? AND p.slug = ?',
            [(int) $space['id'], $pageSlug]
        );
        if ($page === null) {
            http_response_code(404);
            View::render('errors/404', [], null);
            exit;
        }
        return $page;
    }

    private function requireEdit(): void
    {
        if (!Auth::can('wiki.edit')) {
            $this->forbidden();
        }
    }

    private function forbidden(): never
    {
        http_response_code(403);
        View::render('errors/403', [], null);
        exit;
    }
}
