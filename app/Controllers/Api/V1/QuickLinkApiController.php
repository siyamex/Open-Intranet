<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\ApiAuth;
use App\Models\QuickLink;

final class QuickLinkApiController extends BaseApiController
{
    public function index(): void
    {
        $userId = ApiAuth::userId();
        $links = $userId !== null ? QuickLink::forUser($userId) : [];
        $items = array_map(static function (array $l): array {
            return [
                'id' => (int) $l['id'],
                'title' => $l['title'],
                'url' => $l['url'],
                'pinned' => (bool) $l['pinned'],
            ];
        }, $links);
        $this->ok($items);
    }
}
