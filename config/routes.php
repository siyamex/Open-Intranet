<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [\App\Controllers\HomeController::class, 'index'], 'home');
};
