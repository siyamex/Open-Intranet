<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Modules;
use App\Core\View;

/**
 * Route middleware with a parameter: ModuleMiddleware::class . ':news'
 * — 404s the route when the module is toggled off.
 */
final class ModuleMiddleware
{
    public function handle(?string $module = null): void
    {
        if ($module !== null && !Modules::enabled($module)) {
            http_response_code(404);
            View::render('errors/404', [], null);
            exit;
        }
    }
}
