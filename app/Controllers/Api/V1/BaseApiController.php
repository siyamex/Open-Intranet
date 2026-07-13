<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

/**
 * Consistent {data, meta, error} envelope + pagination for the whole API.
 */
abstract class BaseApiController
{
    protected function ok(mixed $data, array $meta = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['data' => $data, 'meta' => $meta, 'error' => null], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{page: int, per_page: int, offset: int}
     */
    protected function pagination(int $default = 20, int $max = 100): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min($max, (int) ($_GET['per_page'] ?? $default)));
        return ['page' => $page, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage];
    }

    protected function meta(int $page, int $perPage, int $total): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function fail(int $status, string $code, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['data' => null, 'meta' => [], 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
