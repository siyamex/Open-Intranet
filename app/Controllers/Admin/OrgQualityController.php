<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\OrgChartController;
use App\Core\DB;
use App\Core\View;

/**
 * Data-quality report for the org chart: orphans, broken chains, cycles.
 */
final class OrgQualityController
{
    public function index(): void
    {
        // No manager AND no direct reports (intended roots usually have reports)
        $orphans = DB::fetchAll(
            "SELECT u.id, u.name, u.job_title FROM users u
             WHERE u.status = 'active' AND u.manager_id IS NULL
               AND NOT EXISTS (SELECT 1 FROM users r WHERE r.manager_id = u.id AND r.status = 'active')
             ORDER BY u.name"
        );
        // Manager exists but is inactive/suspended — the chain is broken
        $broken = DB::fetchAll(
            "SELECT u.id, u.name, m.name AS manager_name, m.status AS manager_status
             FROM users u JOIN users m ON m.id = u.manager_id
             WHERE u.status = 'active' AND m.status != 'active'
             ORDER BY u.name"
        );
        $cycles = OrgChartController::buildTree()['cycles'];

        View::render('admin/org-quality', [
            'title' => 'Org chart data quality',
            'orphans' => $orphans,
            'broken' => $broken,
            'cycles' => $cycles,
        ], 'admin');
    }
}
