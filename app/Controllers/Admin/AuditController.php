<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\View;

final class AuditController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        [$whereSql, $params] = $this->filters();

        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($whereSql, $params);
        }

        $total = (int) DB::scalar("SELECT COUNT(*) FROM audit_logs a {$whereSql}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $pages));
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::fetchAll(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             {$whereSql}
             ORDER BY a.id DESC
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );
        View::render('admin/audit/index', [
            'title' => 'Audit Log',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'users' => DB::fetchAll('SELECT id, name FROM users ORDER BY name'),
            'actions' => array_column(DB::fetchAll('SELECT DISTINCT action FROM audit_logs ORDER BY action LIMIT 200'), 'action'),
            'filters' => [
                'user_id' => (string) ($_GET['user_id'] ?? ''),
                'action' => (string) ($_GET['action'] ?? ''),
                'entity_type' => (string) ($_GET['entity_type'] ?? ''),
                'from' => (string) ($_GET['from'] ?? ''),
                'to' => (string) ($_GET['to'] ?? ''),
            ],
        ], 'admin');
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function filters(): array
    {
        $where = [];
        $params = [];
        if (!empty($_GET['user_id'])) {
            $where[] = 'a.user_id = ?';
            $params[] = (int) $_GET['user_id'];
        }
        if (!empty($_GET['action'])) {
            $where[] = 'a.action = ?';
            $params[] = (string) $_GET['action'];
        }
        if (!empty($_GET['entity_type'])) {
            $where[] = 'a.entity_type = ?';
            $params[] = (string) $_GET['entity_type'];
        }
        if (!empty($_GET['from']) && strtotime((string) $_GET['from']) !== false) {
            $where[] = 'a.created_at >= ?';
            $params[] = date('Y-m-d 00:00:00', (int) strtotime((string) $_GET['from']));
        }
        if (!empty($_GET['to']) && strtotime((string) $_GET['to']) !== false) {
            $where[] = 'a.created_at <= ?';
            $params[] = date('Y-m-d 23:59:59', (int) strtotime((string) $_GET['to']));
        }
        return [$where === [] ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }

    private function exportCsv(string $whereSql, array $params): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="audit-log-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'created_at', 'user', 'action', 'entity_type', 'entity_id', 'ip', 'user_agent', 'meta']);
        $rows = DB::fetchAll(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             {$whereSql}
             ORDER BY a.id DESC LIMIT 20000",
            $params
        );
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'], $row['created_at'], $row['user_name'] ?? '', $row['action'],
                $row['entity_type'] ?? '', $row['entity_id'] ?? '', $row['ip'] ?? '',
                $row['user_agent'] ?? '', $row['meta'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
