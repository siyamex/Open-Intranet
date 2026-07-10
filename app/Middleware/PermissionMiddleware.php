<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\View;

/**
 * Route middleware with a parameter: PermissionMiddleware::class . ':news.publish'
 */
final class PermissionMiddleware
{
    public function handle(?string $permission = null): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        if ($permission !== null && !Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403', [], null);
            exit;
        }
    }
}
