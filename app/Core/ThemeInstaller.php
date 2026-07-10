<?php

declare(strict_types=1);

namespace App\Core;

use ZipArchive;

/**
 * Validates and installs uploaded theme ZIPs (see THEMES.md for the format).
 * The whole archive is rejected on any traversal/symlink/banned-extension/
 * zip-bomb finding; every asset is finfo-verified and images are re-encoded.
 */
final class ThemeInstaller
{
    public const MAX_ZIP_BYTES = 10 * 1024 * 1024;
    private const MAX_FILES = 200;
    private const MAX_UNCOMPRESSED = 50 * 1024 * 1024;
    private const BANNED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'cgi', 'pl', 'sh', 'htaccess', 'ini', 'exe', 'bat', 'cmd', 'com', 'js'];
    private const ASSET_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'otf', 'css'];
    private const KNOWN_TOKENS = [
        'color-primary', 'color-primary-contrast', 'color-accent', 'color-bg', 'color-surface', 'color-surface-2',
        'color-text', 'color-text-muted', 'color-border', 'color-success', 'color-warning', 'color-danger',
        'font-family', 'font-size-base', 'radius-sm', 'radius-md', 'radius-lg', 'space-unit',
        'shadow-1', 'shadow-2', 'navbar-height', 'navbar-bg', 'sidebar-width', 'sidebar-bg',
        'link-decoration', 'login-bg', 'login-overlay',
    ];

    /**
     * @return array{ok: bool, errors: string[], warnings: string[], files: string[], theme_id: ?int, slug: ?string}
     */
    public static function install(string $zipPath): array
    {
        $report = ['ok' => false, 'errors' => [], 'warnings' => [], 'files' => [], 'theme_id' => null, 'slug' => null];

        if (!is_file($zipPath) || filesize($zipPath) > self::MAX_ZIP_BYTES) {
            $report['errors'][] = 'Archive missing or larger than 10 MB.';
            return $report;
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $report['errors'][] = 'The file is not a readable ZIP archive.';
            return $report;
        }

        // ---- Whole-archive safety pass -----------------------------------
        if ($zip->numFiles > self::MAX_FILES) {
            $report['errors'][] = 'Archive holds more than ' . self::MAX_FILES . ' entries (zip-bomb guard).';
            $zip->close();
            return $report;
        }
        $totalUncompressed = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                $report['errors'][] = 'Unreadable archive entry.';
                $zip->close();
                return $report;
            }
            $name = str_replace('\\', '/', (string) $stat['name']);
            $totalUncompressed += (int) $stat['size'];
            if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('#^[a-zA-Z]:#', $name)) {
                $report['errors'][] = "Path traversal rejected: {$name}";
                $zip->close();
                return $report;
            }
            $attrs = $zip->getExternalAttributesIndex($i, $opsys, $extAttr) ? $extAttr : 0;
            if ((($attrs >> 16) & 0xF000) === 0xA000) {
                $report['errors'][] = "Symlink rejected: {$name}";
                $zip->close();
                return $report;
            }
            if (str_ends_with($name, '/')) {
                continue; // directory entry
            }
            $basename = strtolower(basename($name));
            foreach (array_slice(explode('.', $basename), 1) as $extPart) {
                if (in_array($extPart, self::BANNED_EXTENSIONS, true)) {
                    $report['errors'][] = "Banned file type rejected: {$name}";
                    $zip->close();
                    return $report;
                }
            }
            if ($basename === '.htaccess' || str_starts_with($basename, '.')) {
                $report['errors'][] = "Hidden/control file rejected: {$name}";
                $zip->close();
                return $report;
            }
        }
        if ($totalUncompressed > self::MAX_UNCOMPRESSED) {
            $report['errors'][] = 'Uncompressed size exceeds 50 MB (zip-bomb guard).';
            $zip->close();
            return $report;
        }

        // ---- theme.json ----------------------------------------------------
        $manifestRaw = $zip->getFromName('theme.json');
        if ($manifestRaw === false) {
            $report['errors'][] = 'theme.json is missing from the archive root.';
            $zip->close();
            return $report;
        }
        $manifest = json_decode($manifestRaw, true);
        if (!is_array($manifest) || trim((string) ($manifest['name'] ?? '')) === '') {
            $report['errors'][] = 'theme.json is invalid or missing "name".';
            $zip->close();
            return $report;
        }
        $name = trim((string) $manifest['name']);
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) ($manifest['slug'] ?? $name)), '-')) ?: 'theme';
        $version = preg_match('/^\d+(\.\d+){0,2}$/', (string) ($manifest['version'] ?? '')) ? (string) $manifest['version'] : '1.0.0';

        $variables = is_array($manifest['variables'] ?? null) ? $manifest['variables'] : [];
        $darkVariables = is_array($manifest['dark_variables'] ?? null) ? $manifest['dark_variables'] : [];
        foreach (array_keys(array_merge($variables, $darkVariables)) as $token) {
            if (!in_array((string) $token, self::KNOWN_TOKENS, true)) {
                $report['warnings'][] = "Unknown token '{$token}' (kept — may have no effect).";
            }
        }

        // ---- Existing uploaded theme with the same slug = update -----------
        $existing = DB::fetch('SELECT * FROM themes WHERE slug = ?', [$slug]);
        $isUpdate = $existing !== null && $existing['source'] === 'uploaded';
        if ($existing !== null && !$isUpdate) {
            $slug .= '-2';
            while (DB::fetch('SELECT id FROM themes WHERE slug = ?', [$slug]) !== null) {
                $slug .= '-2';
            }
            $report['warnings'][] = "Slug collision — installed as '{$slug}'.";
            $isUpdate = false;
        }
        $report['slug'] = $slug;

        $targetDir = BASE_PATH . '/themes/uploaded/' . $slug;
        if ($isUpdate && is_dir($targetDir)) {
            // keep the previous version for one-click rollback
            $archiveDir = BASE_PATH . '/themes/uploaded/' . $slug . '@' . ($existing['version'] ?? '1.0.0');
            self::deleteDir($archiveDir);
            rename($targetDir, $archiveDir);
            $report['warnings'][] = 'Previous version kept as ' . basename($archiveDir) . ' (rollback available).';
        }
        mkdir($targetDir . '/assets', 0775, true);

        $assetUrlBase = self::assetUrlBase($slug);
        $rewrite = static fn (string $v): string => str_replace('theme://', $assetUrlBase, $v);

        // ---- Extract + verify files ------------------------------------------
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $previewPath = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entry = str_replace('\\', '/', (string) $stat['name']);
            if (str_ends_with($entry, '/') || $entry === 'theme.json' || $entry === 'style.css') {
                continue;
            }
            $isPreview = $entry === 'preview.png';
            $isAsset = str_starts_with($entry, 'assets/');
            if (!$isPreview && !$isAsset) {
                $report['warnings'][] = "Ignored unexpected file: {$entry}";
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ASSET_EXTENSIONS, true)) {
                $report['warnings'][] = "Skipped disallowed asset type: {$entry}";
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $report['warnings'][] = "Unreadable entry skipped: {$entry}";
                continue;
            }
            $mime = (string) $finfo->buffer($contents);
            $stored = null;
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                if (!str_starts_with($mime, 'image/')) {
                    $report['warnings'][] = "Skipped {$entry}: content is not an image ({$mime}).";
                    continue;
                }
                $stored = ImageTool::resizeEncode($contents, 2000, $ext === 'png' ? 'png' : 'jpeg');
                if ($stored === null) {
                    $report['warnings'][] = "Skipped {$entry}: image could not be re-encoded.";
                    continue;
                }
            } elseif ($ext === 'svg') {
                $stored = SvgSanitizer::sanitize($contents);
                if ($stored === null) {
                    $report['warnings'][] = "Skipped {$entry}: SVG failed sanitization.";
                    continue;
                }
            } elseif ($ext === 'css') {
                if (!str_starts_with($mime, 'text/')) {
                    $report['warnings'][] = "Skipped {$entry}: not a text file ({$mime}).";
                    continue;
                }
                $stored = ThemeService::sanitizeCss($rewrite($contents));
            } else { // fonts
                $fontMimes = ['font/woff', 'font/woff2', 'font/ttf', 'font/otf', 'application/font-woff', 'application/vnd.ms-opentype', 'application/octet-stream', 'application/font-sfnt'];
                if (!in_array($mime, $fontMimes, true)) {
                    $report['warnings'][] = "Skipped {$entry}: content is not a font ({$mime}).";
                    continue;
                }
                $stored = $contents;
            }
            $relative = $isPreview ? 'preview.png' : $entry; // assets/... keeps its subpath
            $destination = $targetDir . '/' . $relative;
            $destDir = dirname($destination);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0775, true);
            }
            file_put_contents($destination, $stored, LOCK_EX);
            $report['files'][] = $relative;
            if ($isPreview) {
                $previewPath = 'uploaded/' . $slug . '/preview.png';
            }
        }

        // ---- style.css ----------------------------------------------------------
        $customCss = null;
        $styleRaw = $zip->getFromName('style.css');
        if ($styleRaw !== false) {
            $customCss = ThemeService::sanitizeCss($rewrite($styleRaw));
            file_put_contents($targetDir . '/style.css', $customCss, LOCK_EX);
            $report['files'][] = 'style.css';
        }
        $zip->close();

        // token values may reference theme:// too
        $variables = array_map(static fn ($v) => is_string($v) ? $rewrite($v) : $v, $variables);
        $darkVariables = array_map(static fn ($v) => is_string($v) ? $rewrite($v) : $v, $darkVariables);

        $now = date('Y-m-d H:i:s');
        $row = [
            'name' => $name,
            'source' => 'uploaded',
            'variables' => json_encode($variables, JSON_UNESCAPED_SLASHES),
            'dark_variables' => $darkVariables !== [] ? json_encode($darkVariables, JSON_UNESCAPED_SLASHES) : null,
            'custom_css' => $customCss,
            'dir_path' => 'uploaded/' . $slug,
            'preview_path' => $previewPath,
            'supports_dark' => !empty($manifest['supports_dark']) || $darkVariables !== [] ? 1 : 0,
            'version' => $version,
            'author' => trim((string) ($manifest['author'] ?? '')) ?: null,
            'updated_at' => $now,
        ];
        if ($isUpdate) {
            if ($version === ($existing['version'] ?? '')) {
                $row['version'] = self::bumpVersion($version);
                $report['warnings'][] = 'Same version re-uploaded — bumped to ' . $row['version'] . '.';
            }
            DB::update('themes', $row, 'id = ?', [(int) $existing['id']]);
            $report['theme_id'] = (int) $existing['id'];
        } else {
            $row['slug'] = $slug;
            $row['is_active'] = 0;
            $row['created_at'] = $now;
            $report['theme_id'] = DB::insert('themes', $row);
        }
        ThemeService::compiledPath(); // recompile if the updated theme is active
        $report['ok'] = true;
        $report['files'][] = 'theme.json';
        return $report;
    }

    /**
     * One-click rollback to the archived previous version directory.
     */
    public static function rollback(int $themeId): ?string
    {
        $theme = DB::fetch("SELECT * FROM themes WHERE id = ? AND source = 'uploaded'", [$themeId]);
        if ($theme === null) {
            return 'Theme not found.';
        }
        $slug = (string) $theme['slug'];
        $archives = glob(BASE_PATH . '/themes/uploaded/' . $slug . '@*') ?: [];
        if ($archives === []) {
            return 'No archived version available.';
        }
        rsort($archives, SORT_NATURAL);
        $archive = $archives[0];
        $oldVersion = (string) substr(basename($archive), strlen($slug) + 1);
        $current = BASE_PATH . '/themes/uploaded/' . $slug;
        self::deleteDir($current);
        rename($archive, $current);
        // restore manifest data from the rolled-back directory
        $manifest = json_decode((string) @file_get_contents($current . '/theme.json'), true) ?: [];
        $css = @file_get_contents($current . '/style.css');
        DB::update('themes', [
            'variables' => json_encode(is_array($manifest['variables'] ?? null) ? $manifest['variables'] : [], JSON_UNESCAPED_SLASHES),
            'dark_variables' => is_array($manifest['dark_variables'] ?? null) && $manifest['dark_variables'] !== []
                ? json_encode($manifest['dark_variables'], JSON_UNESCAPED_SLASHES) : null,
            'custom_css' => $css !== false ? ThemeService::sanitizeCss((string) $css) : null,
            'version' => $oldVersion ?: '1.0.0',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$themeId]);
        ThemeService::compiledPath();
        return null;
    }

    public static function hasRollback(string $slug): bool
    {
        return (glob(BASE_PATH . '/themes/uploaded/' . $slug . '@*') ?: []) !== [];
    }

    /**
     * Relative URL prefix that theme:// rewrites to (kept relative so the
     * CSS/vars sanitizers accept it).
     */
    private static function assetUrlBase(string $slug): string
    {
        $basePath = rtrim((string) parse_url((string) Config::env('APP_URL', ''), PHP_URL_PATH), '/');
        return $basePath . '/theme-assets/' . $slug . '/';
    }

    private static function bumpVersion(string $version): string
    {
        $parts = array_map('intval', explode('.', $version));
        $parts = array_pad($parts, 3, 0);
        $parts[2]++;
        return implode('.', $parts);
    }

    private static function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
