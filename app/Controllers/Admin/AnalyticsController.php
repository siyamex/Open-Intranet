<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Settings;
use App\Core\View;

final class AnalyticsController
{
    public function index(): void
    {
        $from = self::dateOr((string) ($_GET['from'] ?? ''), date('Y-m-d', strtotime('-29 days')));
        $to = self::dateOr((string) ($_GET['to'] ?? ''), date('Y-m-d'));

        $dau = $this->series($from, $to, 'dau');
        $wau = $this->weeklyActive($from, $to);
        $pageViews = $this->series($from, $to, 'page_views_total');

        View::render('admin/analytics/index', [
            'title' => 'Analytics',
            'from' => $from,
            'to' => $to,
            'dauSeries' => $dau,
            'wauSeries' => $wau,
            'viewsSeries' => $pageViews,
            'topPages' => $this->topDimension($from, $to, 'top_page', 15),
            'topSearches' => $this->topDimension($from, $to, 'top_search', 10),
            'zeroResultSearches' => $this->topDimension($from, $to, 'zero_result_search', 10),
            'topLinks' => DB::fetchAll(
                'SELECT title, click_count FROM quick_links ORDER BY click_count DESC LIMIT 10'
            ),
            'topNews' => DB::fetchAll(
                "SELECT title, views FROM news WHERE status = 'published' ORDER BY views DESC LIMIT 10"
            ),
            'topDownloads' => DB::fetchAll(
                'SELECT title, download_count FROM documents WHERE parent_doc_id IS NULL ORDER BY download_count DESC LIMIT 10'
            ),
            'heatmap' => $this->heatmap($from, $to),
            'anonymize' => (bool) Settings::get('analytics_anonymize', false),
            'retentionDays' => (int) Settings::get('analytics_retention_days', 180),
        ], 'admin');
    }

    public function saveSettings(): void
    {
        Settings::set('analytics_anonymize', !empty($_POST['anonymize']), 'bool');
        Settings::set('analytics_retention_days', max(30, min(1825, (int) ($_POST['retention_days'] ?? 180))), 'int');
        flash('success', 'Analytics settings saved.');
        redirect('admin/analytics');
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function series(string $from, string $to, string $metric): array
    {
        $rows = DB::fetchAll(
            "SELECT day, value FROM analytics_daily WHERE metric = ? AND day BETWEEN ? AND ?",
            [$metric, $from, $to]
        );
        $byDay = array_column($rows, 'value', 'day');
        $series = [];
        $cursor = strtotime($from);
        $end = strtotime($to);
        while ($cursor <= $end) {
            $day = date('Y-m-d', $cursor);
            $series[] = ['label' => date('j M', $cursor), 'value' => (int) ($byDay[$day] ?? 0)];
            $cursor = strtotime('+1 day', $cursor);
        }
        return $series;
    }

    /**
     * WAU computed directly from raw page_views (7-day rolling distinct users).
     */
    private function weeklyActive(string $from, string $to): array
    {
        $series = [];
        $cursor = strtotime($from);
        $end = strtotime($to);
        while ($cursor <= $end) {
            $windowStart = date('Y-m-d 00:00:00', strtotime('-6 days', $cursor));
            $windowEnd = date('Y-m-d 23:59:59', $cursor);
            $count = (int) DB::scalar(
                'SELECT COUNT(DISTINCT COALESCE(user_id, user_hash)) FROM page_views WHERE created_at BETWEEN ? AND ?',
                [$windowStart, $windowEnd]
            );
            $series[] = ['label' => date('j M', $cursor), 'value' => $count];
            $cursor = strtotime('+1 day', $cursor);
        }
        return $series;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function topDimension(string $from, string $to, string $metric, int $limit): array
    {
        $rows = DB::fetchAll(
            'SELECT dimension, SUM(value) AS total FROM analytics_daily
             WHERE metric = ? AND day BETWEEN ? AND ? AND dimension != ""
             GROUP BY dimension ORDER BY total DESC LIMIT ?',
            [$metric, $from, $to, $limit]
        );
        return array_map(static fn (array $r): array => ['label' => (string) $r['dimension'], 'value' => (int) $r['total']], $rows);
    }

    /**
     * @return int[] 24 values, views per hour of day
     */
    private function heatmap(string $from, string $to): array
    {
        $rows = DB::fetchAll(
            'SELECT dimension AS hour, SUM(value) AS total FROM analytics_daily
             WHERE metric = "peak_hour" AND day BETWEEN ? AND ? GROUP BY dimension',
            [$from, $to]
        );
        $byHour = array_column($rows, 'total', 'hour');
        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = (int) ($byHour[(string) $h] ?? 0);
        }
        return $out;
    }

    private static function dateOr(string $value, string $default): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
    }
}
