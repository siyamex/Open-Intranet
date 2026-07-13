<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\ApiAuth;
use App\Core\DB;

final class UserApiController extends BaseApiController
{
    public function me(): void
    {
        $user = ApiAuth::user();
        $this->ok($this->present($user));
    }

    public function index(): void
    {
        ['page' => $page, 'per_page' => $perPage, 'offset' => $offset] = $this->pagination();
        $total = (int) DB::scalar("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $rows = DB::fetchAll(
            "SELECT id, name, email, job_title, department_id, avatar_path FROM users
             WHERE status = 'active' ORDER BY name LIMIT {$perPage} OFFSET {$offset}"
        );
        $this->ok(array_map([$this, 'present'], $rows), $this->meta($page, $perPage, $total));
    }

    private function present(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'job_title' => $user['job_title'] ?? null,
            'avatar_url' => $user['avatar_path'] !== null ? url('avatar', ['file' => basename((string) $user['avatar_path'])]) : null,
        ];
    }
}
