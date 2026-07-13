<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Router;
use App\Core\View;

/**
 * Escape a string for HTML output.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Translate a lang key: __('nav.home'), __('dashboard.welcome', ['name' => 'Eva']).
 */
function __(string $key, array $params = []): string
{
    return \App\Core\Lang::get($key, $params);
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function env(string $key, mixed $default = null): mixed
{
    return Config::env($key, $default);
}

/**
 * Absolute URL for an app path, based on APP_URL.
 */
function base_url(string $path = ''): string
{
    $base = rtrim((string) Config::env('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Absolute URL for a named route.
 */
function url(string $name, array $params = []): string
{
    return Router::instance()->route($name, $params);
}

/**
 * Asset URL with filemtime cache busting.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return base_url('assets/' . $path) . '?v=' . $version;
}

function redirect(string $to, int $status = 302): never
{
    if (!preg_match('#^https?://#i', $to)) {
        $to = base_url($to);
    }
    header('Location: ' . $to, true, $status);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return Flash::old($key, $default);
}

function flash(string $type, string $message): void
{
    Flash::set($type, $message);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return Csrf::field();
}

/**
 * Render and echo a view partial.
 */
function partial(string $view, array $data = []): void
{
    echo View::fetch($view, $data);
}

/**
 * Per-user preference from the user_prefs table.
 */
function user_pref(string $key, mixed $default = null): mixed
{
    if (!\App\Core\Auth::check()) {
        return $default;
    }
    $value = \App\Core\DB::scalar(
        'SELECT `value` FROM user_prefs WHERE user_id = ? AND `key` = ?',
        [\App\Core\Auth::id(), $key]
    );
    return $value ?? $default;
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Inline a self-hosted SVG icon (colors follow currentColor).
 */
function icon(string $name, string $class = 'icon'): string
{
    static $cache = [];
    $name = (string) preg_replace('/[^a-z0-9-]/', '', strtolower($name));
    if ($name === '') {
        return '';
    }
    if (!isset($cache[$name])) {
        $file = BASE_PATH . '/public/assets/icons/' . $name . '.svg';
        $cache[$name] = is_file($file) ? trim((string) file_get_contents($file)) : '';
    }
    if ($cache[$name] === '') {
        return '';
    }
    return str_replace('<svg ', '<svg class="' . e($class) . '" aria-hidden="true" ', $cache[$name]);
}
