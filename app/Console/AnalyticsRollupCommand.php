<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Settings;

final class AnalyticsRollupCommand
{
    public const DESCRIPTION = 'Roll up daily analytics (DAU/WAU, top pages/links/news, downloads) + apply retention';

    public static function run(array $args): int
    {
        $day = date('Y-m-d', strtotime('-1 day'));
        $start = $day . ' 00:00:00';
        $end = $day . ' 23:59:59';

        self::store($day, 'dau', null, (int) DB::scalar(
            'SELECT COUNT(DISTINCT COALESCE(user_id, user_hash)) FROM page_views WHERE created_at BETWEEN ? AND ?',
            [$start, $end]
        ));
        self::store($day, 'page_views_total', null, (int) DB::scalar(
            'SELECT COUNT(*) FROM page_views WHERE created_at BETWEEN ? AND ?',
            [$start, $end]
        ));

        foreach (DB::fetchAll(
            'SELECT path, COUNT(*) AS n FROM page_views WHERE created_at BETWEEN ? AND ? GROUP BY path ORDER BY n DESC LIMIT 20',
            [$start, $end]
        ) as $row) {
            self::store($day, 'top_page', (string) $row['path'], (int) $row['n']);
        }

        foreach (DB::fetchAll(
            'SELECT query, COUNT(*) AS n, SUM(result_count = 0) AS zero FROM search_queries_log
             WHERE created_at BETWEEN ? AND ? GROUP BY query ORDER BY n DESC LIMIT 20',
            [$start, $end]
        ) as $row) {
            self::store($day, 'top_search', (string) $row['query'], (int) $row['n']);
            if ((int) $row['zero'] > 0) {
                self::store($day, 'zero_result_search', (string) $row['query'], (int) $row['zero']);
            }
        }

        foreach (DB::fetchAll(
            "SELECT HOUR(created_at) AS h, COUNT(*) AS n FROM page_views WHERE created_at BETWEEN ? AND ? GROUP BY h",
            [$start, $end]
        ) as $row) {
            self::store($day, 'peak_hour', (string) $row['h'], (int) $row['n']);
        }

        $retention = (int) Settings::get('analytics_retention_days', 180);
        $cutoff = date('Y-m-d H:i:s', time() - $retention * 86400);
        $deleted = DB::delete('page_views', 'created_at < ?', [$cutoff]);
        DB::delete('search_queries_log', 'created_at < ?', [$cutoff]);

        echo "Rolled up analytics for {$day}. Pruned {$deleted} raw page_views older than {$retention} days.\n";
        return 0;
    }

    private static function store(string $day, string $metric, ?string $dimension, int $value): void
    {
        DB::run(
            'INSERT INTO analytics_daily (day, metric, dimension, value) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$day, $metric, $dimension ?? '', $value]
        );
    }
}
