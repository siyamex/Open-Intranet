<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class User
{
    public const SORTABLE = ['name', 'email', 'status', 'last_login_at', 'created_at'];

    /**
     * Server-side searched, filtered, sorted and paginated user list.
     *
     * @return array{rows: array, total: int, page: int, pages: int}
     */
    public static function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.job_title LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'u.department_id = ?';
            $params[] = (int) $filters['department_id'];
        }
        if (!empty($filters['role_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM user_role ur WHERE ur.user_id = u.id AND ur.role_id = ?)';
            $params[] = (int) $filters['role_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'u.status = ?';
            $params[] = (string) $filters['status'];
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $sort = in_array($filters['sort'] ?? '', self::SORTABLE, true) ? $filters['sort'] : 'name';
        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $total = (int) DB::scalar('SELECT COUNT(*) FROM users u' . $whereSql, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $rows = DB::fetchAll(
            "SELECT u.*, d.name AS department_name,
                    (SELECT GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ')
                     FROM user_role ur JOIN roles r ON r.id = ur.role_id
                     WHERE ur.user_id = u.id) AS role_names
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             {$whereSql}
             ORDER BY u.{$sort} {$dir}, u.id
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function find(int $id): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /**
     * @return int[] role ids
     */
    public static function roleIds(int $userId): array
    {
        return array_map('intval', array_column(
            DB::fetchAll('SELECT role_id FROM user_role WHERE user_id = ?', [$userId]),
            'role_id'
        ));
    }

    public static function syncRoles(int $userId, array $roleIds): void
    {
        DB::delete('user_role', 'user_id = ?', [$userId]);
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            if ($roleId > 0) {
                DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, $roleId]);
            }
        }
    }

    /**
     * Counts of content that would lose its author on delete.
     *
     * @return array<string, int>
     */
    public static function contentCounts(int $userId): array
    {
        return [
            'news posts' => (int) DB::scalar('SELECT COUNT(*) FROM news WHERE author_id = ?', [$userId]),
            'documents' => (int) DB::scalar('SELECT COUNT(*) FROM documents WHERE uploaded_by = ?', [$userId]),
            'direct reports' => (int) DB::scalar('SELECT COUNT(*) FROM users WHERE manager_id = ?', [$userId]),
        ];
    }
}
