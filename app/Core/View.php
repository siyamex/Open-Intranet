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
     */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $data = array_merge(self::$shared, $data);
        $content = self::fetch($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout}");
        }
        extract($data, EXTR_SKIP);
        include $layoutFile;
    }

    /**
     * Render a view file to a string (no layout).
     */
    public static function fetch(string $view, array $data = []): string
    {
        $__file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
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
