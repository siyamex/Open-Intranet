<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Settings;
use App\Core\View;

final class DirectoryController
{
    private const PER_PAGE = 24;

    public function index(): void
    {
        View::render('pages/directory/index', [
            'title' => 'Directory',
            'departments' => DB::fetchAll('SELECT id, name, parent_id FROM departments ORDER BY name'),
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
            'locations' => array_column(DB::fetchAll(
                "SELECT DISTINCT location FROM users WHERE status = 'active' AND location IS NOT NULL AND location != '' ORDER BY location"
            ), 'location'),
            'visibleFields' => self::visibleFields(),
        ]);
    }

    /**
     * JSON endpoint behind the search-as-you-type UI.
     */
    public function api(): void
    {
        $visible = self::visibleFields();
        $searchable = self::searchableFields();

        $where = ["u.status = 'active'"];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $clauses = ['u.name LIKE ?'];
            $params[] = $like;
            foreach (['job_title' => 'title', 'email' => 'email', 'phone' => 'phone'] as $column => $field) {
                if (in_array($field, $searchable, true)) {
                    $clauses[] = "u.{$column} LIKE ?";
                    $params[] = $like;
                }
            }
            if (in_array('skills', $searchable, true)) {
                $clauses[] = 'EXISTS (SELECT 1 FROM user_skills s WHERE s.user_id = u.id AND s.skill LIKE ?)';
                $params[] = $like;
            }
            $where[] = '(' . implode(' OR ', $clauses) . ')';
        }
        if (!empty($_GET['department_id'])) {
            // include sub-departments of the picked one
            $deptId = (int) $_GET['department_id'];
            $where[] = '(u.department_id = ? OR u.department_id IN (SELECT id FROM departments WHERE parent_id = ?))';
            array_push($params, $deptId, $deptId);
        }
        if (!empty($_GET['location'])) {
            $where[] = 'u.location = ?';
            $params[] = (string) $_GET['location'];
        }
        if (!empty($_GET['role_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM user_role ur WHERE ur.user_id = u.id AND ur.role_id = ?)';
            $params[] = (int) $_GET['role_id'];
        }
        $letter = strtoupper((string) ($_GET['letter'] ?? ''));
        if (preg_match('/^[A-Z]$/', $letter)) {
            $where[] = 'u.name LIKE ?';
            $params[] = $letter . '%';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) DB::scalar("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min((int) ($_GET['page'] ?? 1), $pages));
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = DB::fetchAll(
            "SELECT u.id, u.name, u.email, u.phone, u.job_title, u.location, u.timezone, u.avatar_path,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE {$whereSql}
             ORDER BY u.name
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );
        $skillMap = [];
        if ($rows !== [] && in_array('skills', $visible, true)) {
            $ids = implode(',', array_map('intval', array_column($rows, 'id')));
            foreach (DB::fetchAll("SELECT user_id, skill FROM user_skills WHERE user_id IN ({$ids}) ORDER BY skill") as $s) {
                $skillMap[(int) $s['user_id']][] = $s['skill'];
            }
        }
        $chatTemplate = (string) Settings::get('directory_chat_template', '');

        $items = array_map(function (array $u) use ($visible, $skillMap, $chatTemplate): array {
            $item = [
                'id' => (int) $u['id'],
                'name' => $u['name'],
                'title' => $u['job_title'],
                'avatar_url' => $u['avatar_path'] !== null ? url('avatar', ['file' => basename((string) $u['avatar_path'])]) : null,
                'profile_url' => url('people.show', ['id' => $u['id']]),
                'vcard_url' => url('people.vcard', ['id' => $u['id']]),
            ];
            if (in_array('department', $visible, true)) {
                $item['department'] = $u['department_name'];
            }
            if (in_array('email', $visible, true)) {
                $item['email'] = $u['email'];
                if ($chatTemplate !== '' && str_contains($chatTemplate, '{email}')) {
                    $item['chat_url'] = str_replace('{email}', rawurlencode((string) $u['email']), $chatTemplate);
                }
            }
            if (in_array('phone', $visible, true)) {
                $item['phone'] = $u['phone'];
            }
            if (in_array('location', $visible, true)) {
                $item['location'] = $u['location'];
            }
            if (in_array('skills', $visible, true)) {
                $item['skills'] = $skillMap[(int) $u['id']] ?? [];
            }
            if (in_array('local_time', $visible, true) && !empty($u['timezone'])) {
                try {
                    $item['local_time'] = (new \DateTime('now', new \DateTimeZone((string) $u['timezone'])))->format('H:i');
                } catch (\Throwable) {
                }
            }
            return $item;
        }, $rows);

        header('Content-Type: application/json');
        echo json_encode(['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages]);
        exit;
    }

    public function department(string $id): void
    {
        $department = DB::fetch(
            'SELECT d.*, h.id AS head_id, h.name AS head_name, h.job_title AS head_title, h.avatar_path AS head_avatar
             FROM departments d
             LEFT JOIN users h ON h.id = d.head_user_id
             WHERE d.id = ?',
            [(int) $id]
        );
        if ($department === null) {
            http_response_code(404);
            View::render('errors/404', [], null);
            return;
        }
        View::render('pages/directory/department', [
            'title' => (string) $department['name'],
            'department' => $department,
            'members' => DB::fetchAll(
                "SELECT id, name, job_title, avatar_path FROM users
                 WHERE department_id = ? AND status = 'active' ORDER BY name",
                [(int) $id]
            ),
            'children' => DB::fetchAll(
                'SELECT d.*, (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.status = "active") AS member_count
                 FROM departments d WHERE d.parent_id = ? ORDER BY d.name',
                [(int) $id]
            ),
            'breadcrumbs' => [['Directory', url('directory.index')], [(string) $department['name'], null]],
        ]);
    }

    public function vcard(string $id): void
    {
        $person = DB::fetch(
            "SELECT u.*, d.name AS department_name FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.id = ? AND u.status = 'active'",
            [(int) $id]
        );
        if ($person === null) {
            http_response_code(404);
            View::render('errors/404', [], null);
            return;
        }
        $escape = static fn (string $v): string => str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $v);
        $nameParts = preg_split('/\s+/', trim((string) $person['name'])) ?: [];
        $last = count($nameParts) > 1 ? array_pop($nameParts) : '';
        $first = implode(' ', $nameParts);

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:' . $escape((string) $person['name']),
            'N:' . $escape($last) . ';' . $escape($first) . ';;;',
            'EMAIL;TYPE=WORK:' . $escape((string) $person['email']),
        ];
        if (!empty($person['phone'])) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . $escape((string) $person['phone']);
        }
        if (!empty($person['job_title'])) {
            $lines[] = 'TITLE:' . $escape((string) $person['job_title']);
        }
        if (!empty($person['department_name'])) {
            $lines[] = 'ORG:' . $escape((string) Settings::get('site_name', 'OpenIntranet')) . ';' . $escape((string) $person['department_name']);
        }
        $lines[] = 'REV:' . date('Ymd\THis\Z');
        $lines[] = 'END:VCARD';

        header('Content-Type: text/vcard; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9-]/i', '-', (string) $person['name']) . '.vcf"');
        echo implode("\r\n", $lines) . "\r\n";
        exit;
    }

    /**
     * @return string[]
     */
    public static function visibleFields(): array
    {
        $fields = Settings::get('directory_visible_fields', ['email', 'phone', 'department', 'location', 'skills', 'local_time']);
        return is_array($fields) ? $fields : ['email', 'phone', 'department', 'location', 'skills', 'local_time'];
    }

    /**
     * @return string[]
     */
    public static function searchableFields(): array
    {
        $fields = Settings::get('directory_searchable_fields', ['title', 'email', 'phone', 'skills']);
        return is_array($fields) ? $fields : ['title', 'email', 'phone', 'skills'];
    }
}
