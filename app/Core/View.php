<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @var array<string, string> */
    private static array $sections = [];

    /** @var string[] */
    private static array $sectionStack = [];

    /** @var array<string, mixed> data shared with every view and layout */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Render a view inside a layout and echo it. Pass $layout = null for a bare view.
     * Internal variables are __-prefixed so view data keys (e.g. 'view',
     * 'data', 'layout') are never shadowed by extract(EXTR_SKIP).
     */
    public static function render(string $__view, array $__data = [], ?string $__layout = 'app'): void
    {
        $__data = array_merge(self::$shared, $__data);
        $content = self::fetch($__view, $__data);
        if ($__layout === null) {
            echo $content;
            return;
        }
        $__layoutFile = BASE_PATH . '/app/Views/layouts/' . $__layout . '.php';
        if (!is_file($__layoutFile)) {
            throw new \RuntimeException("Layout not found: {$__layout}");
        }
        extract($__data, EXTR_SKIP);
        include $__layoutFile;
    }

    /**
     * Render a view file to a string (no layout).
     */
    public static function fetch(string $__view, array $__data = []): string
    {
        $__file = BASE_PATH . '/app/Views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("View not found: {$__view}");
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        include $__file;
        return (string) ob_get_clean();
    }

    public static function start(string $name): void
    {
        self::$sectionStack[] = $name;
        ob_start();
    }

    public static function end(): void
    {
        $name = array_pop(self::$sectionStack);
        if ($name === null) {
            throw new \RuntimeException('View::end() called without View::start().');
        }
        self::$sections[$name] = (self::$sections[$name] ?? '') . (string) ob_get_clean();
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? '';
    }
}
