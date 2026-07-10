<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Visibility;
use App\Core\View;

final class DocumentController
{
    public function index(): void
    {
        // Visible category tree
        $categories = array_values(array_filter(
            DB::fetchAll('SELECT * FROM doc_categories ORDER BY name'),
            static fn (array $c): bool => Visibility::allowed($c['visible_to'])
        ));
        $visibleCategoryIds = array_map('intval', array_column($categories, 'id'));

        $categoryId = (int) ($_GET['category'] ?? 0);
        if ($categoryId !== 0 && !in_array($categoryId, $visibleCategoryIds, true)) {
            $categoryId = 0;
        }

        $where = ['d.parent_doc_id IS NULL'];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'd.title LIKE ?';
            $params[] = '%' . $q . '%';
        }
        if ($categoryId > 0) {
            $where[] = 'd.category_id = ?';
            $params[] = $categoryId;
        }
        $docs = DB::fetchAll(
            'SELECT d.*, c.name AS category_name, c.visible_to AS category_visible_to, u.name AS uploader_name
             FROM documents d
             LEFT JOIN doc_categories c ON c.id = d.category_id
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY d.published_at DESC, d.created_at DESC
             LIMIT 300',
            $params
        );
        $docs = array_values(array_filter($docs, static function (array $d) use ($visibleCategoryIds): bool {
            if ($d['category_id'] !== null && !in_array((int) $d['category_id'], $visibleCategoryIds, true)) {
                return false;
            }
            return Visibility::allowed($d['visible_to']);
        }));

        // Build tree (one level of nesting is rendered; deeper still works via parent chain)
        $tree = [];
        $byId = [];
        foreach ($categories as $c) {
            $c['children'] = [];
            $byId[(int) $c['id']] = $c;
        }
        foreach ($byId as $id => &$c) {
            $parent = $c['parent_id'] !== null ? (int) $c['parent_id'] : null;
            if ($parent !== null && isset($byId[$parent])) {
                $byId[$parent]['children'][] = &$c;
            } else {
                $tree[] = &$c;
            }
        }
        unset($c);

        View::render('pages/documents/index', [
            'title' => 'Documents',
            'docs' => $docs,
            'tree' => $tree,
            'categoryId' => $categoryId,
            'q' => $q,
        ]);
    }
}
