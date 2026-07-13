<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Active-banner lookup, cached 30s in a static file (checked per request,
 * as required — file mtime gives us the cache window cheaply).
 */
final class BannerService
{
    private const CACHE_FILE = '/storage/cache/active-banners.json';
    private const TTL = 30;

    /**
     * @return array<int, array> active banners, newest first
     */
    public static function active(): array
    {
        $all = self::allCached();
        $now = time();
        $active = array_filter($all, static function (array $b) use ($now): bool {
            if ($b['starts_at'] !== null && strtotime((string) $b['starts_at']) > $now) {
                return false;
            }
            if ($b['ends_at'] !== null && strtotime((string) $b['ends_at']) <= $now) {
                return false;
            }
            return Visibility::allowed($b['visible_to']);
        });
        return array_values($active);
    }

    /**
     * @return array<int, array> all rows (any window), cached for 30s
     */
    private static function allCached(): array
    {
        $file = BASE_PATH . self::CACHE_FILE;
        if (is_file($file) && (time() - (int) filemtime($file)) < self::TTL) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached)) {
                return $cached;
            }
        }
        $rows = [];
        try {
            $rows = DB::fetchAll(
                'SELECT * FROM banners
                 WHERE (starts_at IS NULL OR starts_at <= NOW())
                   AND (ends_at IS NULL OR ends_at > NOW())
                 ORDER BY FIELD(severity, "critical", "warning", "info"), created_at DESC'
            );
        } catch (\Throwable) {
            // table not migrated yet
        }
        @file_put_contents($file, json_encode($rows), LOCK_EX);
        return $rows;
    }

    public static function invalidate(): void
    {
        @unlink(BASE_PATH . self::CACHE_FILE);
    }
}
