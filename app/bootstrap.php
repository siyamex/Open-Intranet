<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

require BASE_PATH . '/app/helpers.php';

\App\Core\Config::boot();

date_default_timezone_set((string) \App\Core\Config::get('app.timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
ini_set('display_errors', \App\Core\Config::env('APP_ENV', 'production') === 'local' ? '1' : '0');
