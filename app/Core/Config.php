<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, string> */
    private static array $env = [];

    /** @var array<string, array> */
    private static array $items = [];

    public static function boot(): void
    {
        self::$env = [];
        self::$items = [];
        $file = BASE_PATH . '/.env';
        if (!is_file($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '') {
                self::$env[$key] = $value;
            }
        }
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }
        $fromServer = getenv($key);
        return $fromServer === false ? $default : $fromServer;
    }

    /**
     * Dot notation over config/*.php files: Config::get('app.name').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);
        if (!array_key_exists($file, self::$items)) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            self::$items[$file] = is_file($path) ? (array) require $path : [];
        }
        $value = self::$items[$file];
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
