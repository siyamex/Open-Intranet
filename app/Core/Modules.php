<?php

declare(strict_types=1);

namespace App\Core;

final class Modules
{
    /** Maps app path prefixes to module slugs (menus + routes). */
    public const PATH_MAP = [
        '/news' => 'news',
        '/documents' => 'documents',
        '/files' => 'documents',
        '/directory' => 'directory',
        '/org-chart' => 'org_chart',
    ];

    /** @var array<string, bool>|null */
    private static ?array $cache = null;

    public static function enabled(string $slug): bool
    {
        self::load();
        return self::$cache[$slug] ?? true; // unknown slugs default to enabled
    }

    /**
     * @return array<string, bool>
     */
    public static function all(): array
    {
        self::load();
        return self::$cache ?? [];
    }

    public static function set(string $slug, bool $enabled): void
    {
        DB::run(
            'INSERT INTO modules (slug, enabled) VALUES (?, ?) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)',
            [$slug, $enabled ? 1 : 0]
        );
        self::$cache = null;
    }

    /**
     * Module slug governing an app path, or null when always available.
     */
    public static function forPath(string $path): ?string
    {
        foreach (self::PATH_MAP as $prefix => $slug) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $slug;
            }
        }
        return null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        try {
            foreach (DB::fetchAll('SELECT slug, enabled FROM modules') as $row) {
                self::$cache[$row['slug']] = (int) $row['enabled'] === 1;
            }
        } catch (\PDOException) {
            // table not migrated yet — everything enabled
        }
    }
}
