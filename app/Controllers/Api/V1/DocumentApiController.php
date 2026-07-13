<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\ApiAuth;
use App\Core\DB;
use App\Core\Visibility;

final class DocumentApiController extends BaseApiController
{
    public function index(): void
    {
        ['page' => $page, 'per_page' => $perPage, 'offset' => $offset] = $this->pagination();
        $rows = DB::fetchAll(
            'SELECT d.*, c.visible_to AS category_visible_to, c.name AS category
             FROM documents d LEFT JOIN doc_categories c ON c.id = d.category_id
             WHERE d.parent_doc_id IS NULL ORDER BY d.created_at DESC LIMIT 500'
        );
        $user = ApiAuth::user();
        $canManage = false; // token-based requests are treated as the owning user's normal permissions
        if ($user !== null) {
            $canManage = DB::fetch(
                "SELECT 1 FROM user_role ur JOIN role_permission rp ON rp.role_id = ur.role_id
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE ur.user_id = ? AND p.slug = 'docs.manage'",
                [(int) $user['id']]
            ) !== null;
        }
        $rows = array_values(array_filter($rows, static function (array $d) use ($canManage, $user): bool {
            if ($canManage || (int) ($d['uploaded_by'] ?? 0) === (int) ($user['id'] ?? 0)) {
                return true;
            }
            return Visibility::allowed($d['visible_to']) && Visibility::allowed($d['category_visible_to']);
        }));
        $total = count($rows);
        $slice = array_slice($rows, $offset, $perPage);
        $items = array_map(static function (array $d): array {
            return [
                'id' => (int) $d['id'],
                'uuid' => $d['uuid'],
                'title' => $d['title'],
                'category' => $d['category'],
                'version' => (int) $d['version'],
                'size_bytes' => (int) $d['size_bytes'],
                'download_url' => url('files.serve', ['uuid' => $d['uuid']]),
            ];
        }, $slice);
        $this->ok($items, $this->meta($page, $perPage, $total));
    }
}
