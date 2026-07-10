<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ThemeService;

/**
 * Serves the compiled theme CSS (no auth — the login page needs it).
 * Cache-busted by ?v={hash}, so far-future caching is safe.
 */
final class ThemeCssController
{
    public function css(): void
    {
        $path = ThemeService::compiledPath();
        header('Content-Type: text/css; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=31536000, immutable');
        if ($path === null || !is_file($path)) {
            echo "/* no active theme */";
            exit;
        }
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}
