<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;

final class OrgChartController
{
    public function index(): void
    {
        $data = self::buildTree();
        View::render('pages/org-chart', [
            'title' => 'Org Chart',
            'tree' => $data['tree'],
            'cycles' => $data['cycles'],
            'departments' => DB::fetchAll('SELECT id, name FROM departments ORDER BY name'),
            'byDepartment' => $this->groupedByDepartment(),
            'flat' => DB::fetchAll(
                "SELECT u.id, u.name, u.job_title, m.name AS manager_name, d.name AS department_name
                 FROM users u
                 LEFT JOIN users m ON m.id = u.manager_id
                 LEFT JOIN departments d ON d.id = u.department_id
                 WHERE u.status = 'active' ORDER BY u.name"
            ),
        ]);
    }

    public function api(): void
    {
        header('Content-Type: application/json');
        echo json_encode(self::buildTree());
        exit;
    }

    /**
     * Cycle-safe tree from users.manager_id.
     *
     * @return array{tree: array, cycles: array}
     */
    public static function buildTree(): array
    {
        $users = DB::fetchAll(
            "SELECT u.id, u.name, u.job_title, u.avatar_path, u.manager_id, u.department_id, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.status = 'active'
             ORDER BY u.name"
        );
        $byId = [];
        foreach ($users as $user) {
            $byId[(int) $user['id']] = $user;
        }

        // Detect cycles by walking each manager chain
        $cycles = [];
        $inCycle = [];
        foreach ($byId as $id => $user) {
            $seen = [];
            $current = $id;
            while ($current !== 0 && isset($byId[$current])) {
                if (isset($seen[$current])) {
                    // found a loop — record its members once
                    $members = array_keys($seen, true, true);
                    $loop = array_slice(array_keys($seen), array_search($current, array_keys($seen), true));
                    $key = implode('-', $loop);
                    if (!isset($cycles[$key])) {
                        $cycles[$key] = array_map(
                            static fn (int $uid): array => ['id' => $uid, 'name' => $byId[$uid]['name'] ?? '?'],
                            $loop
                        );
                    }
                    foreach ($loop as $uid) {
                        $inCycle[$uid] = true;
                    }
                    break;
                }
                $seen[$current] = true;
                $current = (int) ($byId[$current]['manager_id'] ?? 0);
            }
        }

        // Build nodes; members of a cycle become roots (flagged) so the chart still renders
        $nodes = [];
        foreach ($byId as $id => $user) {
            $nodes[$id] = [
                'id' => $id,
                'name' => $user['name'],
                'title' => $user['job_title'],
                'avatar' => $user['avatar_path'] !== null ? url('avatar', ['file' => basename((string) $user['avatar_path'])]) : null,
                'dept' => $user['department_name'],
                'dept_id' => $user['department_id'] !== null ? (int) $user['department_id'] : null,
                'profile_url' => url('people.show', ['id' => $id]),
                'in_cycle' => isset($inCycle[$id]),
                'children' => [],
            ];
        }
        $tree = [];
        foreach ($byId as $id => $user) {
            $managerId = (int) ($user['manager_id'] ?? 0);
            if ($managerId !== 0 && isset($nodes[$managerId]) && !isset($inCycle[$id])) {
                $nodes[$managerId]['children'][] = &$nodes[$id];
            } else {
                $tree[] = &$nodes[$id];
            }
        }
        return ['tree' => $tree, 'cycles' => array_values($cycles)];
    }

    /**
     * @return array<string, array>
     */
    private function groupedByDepartment(): array
    {
        $rows = DB::fetchAll(
            "SELECT u.id, u.name, u.job_title, u.avatar_path, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.status = 'active'
             ORDER BY d.name, u.name"
        );
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['department_name'] ?? '— No department —'][] = $row;
        }
        ksort($groups);
        return $groups;
    }
}
