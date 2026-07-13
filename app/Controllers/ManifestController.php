<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Settings;
use App\Core\ThemeService;

final class ManifestController
{
    public function manifest(): void
    {
        $siteName = (string) Settings::get('site_name', 'OpenIntranet');
        $theme = ThemeService::activeTheme();
        $vars = $theme !== null ? (array) json_decode((string) $theme['variables'], true) : [];
        $themeColor = (string) ($vars['color-primary'] ?? '#4f46e5');
        $bg = (string) ($vars['color-bg'] ?? '#f3f4f6');

        $icons = [];
        $icon512 = Settings::get('pwa_icon_512');
        $icon192 = Settings::get('pwa_icon_192');
        if (is_string($icon192) && $icon192 !== '') {
            $icons[] = ['src' => base_url($icon192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'];
        }
        if (is_string($icon512) && $icon512 !== '') {
            $icons[] = ['src' => base_url($icon512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'];
        }
        if ($icons === []) {
            // fall back to the favicon if no PWA icon was generated yet
            $favicon = Settings::get('favicon_path');
            if (is_string($favicon) && $favicon !== '') {
                $icons[] = ['src' => base_url($favicon), 'sizes' => '64x64', 'type' => 'image/png'];
            }
        }

        $manifest = [
            'name' => $siteName,
            'short_name' => mb_substr($siteName, 0, 12),
            'description' => (string) Settings::get('site_tagline', 'Company intranet portal'),
            'start_url' => base_url('/'),
            'scope' => base_url('/'),
            'display' => 'standalone',
            'background_color' => $bg,
            'theme_color' => $themeColor,
            'icons' => $icons,
        ];
        header('Content-Type: application/manifest+json');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Service worker must be served from the app root to control the whole
     * scope, so this route is aliased to /sw.js.
     */
    public function serviceWorker(): void
    {
        header('Content-Type: application/javascript; charset=UTF-8');
        header('Cache-Control: no-cache');
        header('Service-Worker-Allowed: /');
        $version = ThemeService::version();
        $base = rtrim((string) parse_url((string) \App\Core\Config::env('APP_URL', ''), PHP_URL_PATH), '/');
        $js = (string) file_get_contents(BASE_PATH . '/public/assets/js/sw-template.js');
        $js = str_replace(['__CACHE_VERSION__', '__APP_BASE__'], [$version, $base], $js);
        echo $js;
        exit;
    }
}
