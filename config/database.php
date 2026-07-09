<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'host' => (string) Config::env('DB_HOST', '127.0.0.1'),
    'port' => (string) Config::env('DB_PORT', '3306'),
    'name' => (string) Config::env('DB_NAME', 'openintranet'),
    'user' => (string) Config::env('DB_USER', 'root'),
    'pass' => (string) Config::env('DB_PASS', ''),
];
