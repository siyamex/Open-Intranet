<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'name' => (string) Config::env('APP_NAME', 'OpenIntranet'),
    'url' => (string) Config::env('APP_URL', 'http://localhost'),
    'env' => (string) Config::env('APP_ENV', 'production'),
    'timezone' => (string) Config::env('APP_TIMEZONE', 'UTC'),
];
