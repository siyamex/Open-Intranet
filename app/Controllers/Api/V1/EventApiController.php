<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\DB;
use App\Core\Visibility;

final class EventApiController extends BaseApiController
{
    public function index(): void
    {
        ['page' => $page, 'per_page' => $perPage, 'offset' => $offset] = $this->pagination();
        $rows = DB::fetchAll('SELECT * FROM events WHERE ends_at >= NOW() ORDER BY starts_at LIMIT 500');
        $rows = array_values(array_filter($rows, static fn (array $e): bool => Visibility::allowed($e['visible_to'])));
        $total = count($rows);
        $items = array_map(static function (array $e): array {
            return [
                'id' => (int) $e['id'],
                'title' => $e['title'],
                'location' => $e['location'],
                'starts_at' => $e['starts_at'],
                'ends_at' => $e['ends_at'],
                'all_day' => (int) $e['all_day'] === 1,
                'url' => url('events.show', ['id' => $e['id']]),
            ];
        }, array_slice($rows, $offset, $perPage));
        $this->ok($items, $this->meta($page, $perPage, $total));
    }
}
