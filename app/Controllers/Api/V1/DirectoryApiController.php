<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\DB;

final class DirectoryApiController extends BaseApiController
{
    public function index(): void
    {
        ['page' => $page, 'per_page' => $perPage, 'offset' => $offset] = $this->pagination();
        $where = ["u.status = 'active'"];
        $params = [];
        if (!empty($_GET['q'])) {
            $where[] = '(u.name LIKE ? OR u.job_title LIKE ?)';
            $like = '%' . $_GET['q'] . '%';
            array_push($params, $like, $like);
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) DB::scalar("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
        $rows = DB::fetchAll(
            "SELECT u.id, u.name, u.job_title, u.email, u.phone, d.name AS department
             FROM users u LEFT JOIN departments d ON d.id = u.department_id
             WHERE {$whereSql} ORDER BY u.name LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $this->ok($rows, $this->meta($page, $perPage, $total));
    }
}
