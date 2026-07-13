<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;

/**
 * Per-token rate limit (falls back to per-IP if somehow unauthenticated,
 * though ApiAuthMiddleware runs first on every API route).
 */
final class ApiRateLimitMiddleware
{
    public function handle(?string $param = null): void
    {
        [$capacity, $window] = array_pad(explode(',', (string) ($param ?? '120,60')), 2, '60');
        $key = 'api:' . (ApiAuth::userId() ?? ('ip:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')));
        if (!RateLimitMiddleware::consume($key, (int) $capacity, (int) $window)) {
            \App\Controllers\Api\V1\BaseApiController::fail(429, 'rate_limited', 'Too many requests — slow down.');
        }
    }
}
