<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $stats = [
            'Active users' => (int) DB::scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'Logins today' => (int) DB::scalar(
                'SELECT COUNT(DISTINCT email) FROM login_attempts WHERE succeeded = 1 AND created_at >= ?',
                [date('Y-m-d 00:00:00')]
            ),
            'News this month' => (int) DB::scalar(
                "SELECT COUNT(*) FROM news WHERE status = 'published' AND published_at >= ?",
                [date('Y-m-01 00:00:00')]
            ),
            'Documents' => (int) DB::scalar('SELECT COUNT(*) FROM documents WHERE parent_doc_id IS NULL'),
            'Storage used' => format_bytes($this->storageUsed()),
        ];

        // successful logins per day, last 30 days (zero-filled)
        $rows = DB::fetchAll(
            'SELECT DATE(created_at) AS day, COUNT(*) AS n FROM login_attempts
             WHERE succeeded = 1 AND created_at > ?
             GROUP BY DATE(created_at)',
            [date('Y-m-d H:i:s', time() - 31 * 86400)]
        );
        $byDay = array_column($rows, 'n', 'day');
        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', time() - $i * 86400);
            $series[] = ['label' => date('j M', strtotime($day)), 'value' => (int) ($byDay[$day] ?? 0)];
        }

        $latestAudit = DB::fetchAll(
            'SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 10'
        );

        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'loginSeries' => $series,
            'latestAudit' => $latestAudit,
            'canAudit' => Auth::can('audit.view'),
        ], 'admin');
    }

    private function storageUsed(): int
    {
        $total = 0;
        $dir = BASE_PATH . '/storage/uploads';
        if (!is_dir($dir)) {
            return 0;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $total += $file->getSize();
            }
        }
        return $total;
    }
}
