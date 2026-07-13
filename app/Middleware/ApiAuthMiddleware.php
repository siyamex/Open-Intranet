<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;

/**
 * Route middleware with an optional required scope:
 * ApiAuthMiddleware::class . ':read' or ':write'
 */
final class ApiAuthMiddleware
{
    public function handle(?string $scope = null): void
    {
        if (!ApiAuth::attempt()) {
            \App\Controllers\Api\V1\BaseApiController::fail(401, 'unauthorized', 'Missing or invalid API token.');
        }
        if ($scope !== null && !ApiAuth::can($scope)) {
            \App\Controllers\Api\V1\BaseApiController::fail(403, 'forbidden', "Token is missing the '{$scope}' scope.");
        }
    }
}
