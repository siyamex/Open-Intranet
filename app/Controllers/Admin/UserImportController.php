<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\View;

/**
 * Bulk CSV user import with a mandatory dry-run preview step.
 * Parsed rows are cached in storage/cache between preview and commit.
 */
final class UserImportController
{
    private const COLUMNS = ['name', 'email', 'job_title', 'phone', 'department', 'location', 'timezone', 'manager_email', 'roles'];

    public function form(): void
    {
        View::render('admin/users/import', ['title' => 'Import users', 'preview' => null], 'admin');
    }

    public function template(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="users-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, self::COLUMNS);
        fputcsv($out, ['Jane Doe', 'jane.doe@example.com', 'HR Officer', '+1 555 0100', 'Human Resources', 'HQ', 'UTC', 'manager@example.com', 'employee']);
        fclose($out);
        exit;
    }

    public function preview(): void
    {
        $file = $_FILES['csv'] ?? null;
        if (!is_array($file) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a CSV file to upload.');
            redirect('admin/users/import');
        }
        if ((int) $file['size'] > 2 * 1024 * 1024) {
            flash('error', 'CSV exceeds the 2 MB limit.');
            redirect('admin/users/import');
        }

        $rows = $this->parse((string) $file['tmp_name']);
        if ($rows === null) {
            flash('error', 'The CSV header is invalid. Download the template and keep its column names.');
            redirect('admin/users/import');
        }

        $validated = $this->validateRows($rows);
        file_put_contents($this->cacheFile(), json_encode($validated), LOCK_EX);

        View::render('admin/users/import', [
            'title' => 'Import users — preview',
            'preview' => $validated,
        ], 'admin');
    }

    public function commit(): void
    {
        $cache = $this->cacheFile();
        $validated = is_file($cache) ? json_decode((string) file_get_contents($cache), true) : null;
        if (!is_array($validated)) {
            flash('error', 'No pending import found — upload the CSV again.');
            redirect('admin/users/import');
        }
        @unlink($cache);

        $created = 0;
        $now = date('Y-m-d H:i:s');
        $pendingManagers = [];

        foreach ($validated as $row) {
            if ($row['errors'] !== []) {
                continue;
            }
            $data = $row['data'];
            $departmentId = null;
            if ($data['department'] !== '') {
                $dept = DB::fetch('SELECT id FROM departments WHERE name = ?', [$data['department']]);
                $departmentId = $dept !== null
                    ? (int) $dept['id']
                    : DB::insert('departments', ['name' => $data['department'], 'created_at' => $now, 'updated_at' => $now]);
            }
            $userId = DB::insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => null,
                'job_title' => $data['job_title'] ?: null,
                'phone' => $data['phone'] ?: null,
                'department_id' => $departmentId,
                'location' => $data['location'] ?: null,
                'timezone' => $data['timezone'] ?: null,
                'status' => 'active',
                'must_change_password' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($data['role_ids'] as $roleId) {
                DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, $roleId]);
            }
            if ($data['manager_email'] !== '') {
                $pendingManagers[$userId] = $data['manager_email'];
            }
            $created++;
        }

        // Second pass: managers may themselves be part of this import.
        foreach ($pendingManagers as $userId => $managerEmail) {
            $manager = DB::fetch('SELECT id FROM users WHERE email = ?', [$managerEmail]);
            if ($manager !== null && (int) $manager['id'] !== $userId) {
                DB::update('users', ['manager_id' => (int) $manager['id']], 'id = ?', [$userId]);
            }
        }

        Audit::log('user.bulk_imported', 'user', null, ['created' => $created]);
        flash('success', "Import complete — {$created} user(s) created (invite them via Force password reset when ready).");
        redirect('admin/users');
    }

    /**
     * @return array<int, array<string, string>>|null null on bad header
     */
    private function parse(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }
        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return null;
        }
        $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), $header);
        if (array_slice($header, 0, 2) !== ['name', 'email']) {
            fclose($handle);
            return null;
        }
        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }
            $row = [];
            foreach (self::COLUMNS as $i => $column) {
                $index = array_search($column, $header, true);
                $row[$column] = $index === false ? '' : trim((string) ($line[$index] ?? ''));
            }
            if (implode('', $row) !== '') {
                $rows[] = $row;
            }
            if (count($rows) >= 1000) {
                break; // sanity cap
            }
        }
        fclose($handle);
        return $rows;
    }

    /**
     * @return array<int, array{line: int, data: array, errors: string[]}>
     */
    private function validateRows(array $rows): array
    {
        $roleMap = [];
        foreach (DB::fetchAll('SELECT id, slug FROM roles') as $role) {
            $roleMap[$role['slug']] = (int) $role['id'];
        }
        $seenEmails = [];
        $result = [];
        foreach ($rows as $i => $row) {
            $errors = [];
            $email = strtolower($row['email']);
            if ($row['name'] === '') {
                $errors[] = 'Name is required.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address.';
            } elseif (isset($seenEmails[$email])) {
                $errors[] = 'Duplicate email within the file.';
            } elseif (DB::fetch('SELECT id FROM users WHERE email = ?', [$email]) !== null) {
                $errors[] = 'A user with this email already exists.';
            }
            $seenEmails[$email] = true;

            $roleIds = [];
            if ($row['roles'] !== '') {
                foreach (explode('|', $row['roles']) as $slug) {
                    $slug = strtolower(trim($slug));
                    if ($slug === '') {
                        continue;
                    }
                    if (!isset($roleMap[$slug])) {
                        $errors[] = "Unknown role '{$slug}'.";
                    } else {
                        $roleIds[] = $roleMap[$slug];
                    }
                }
            }
            if ($roleIds === [] && isset($roleMap['employee'])) {
                $roleIds[] = $roleMap['employee'];
            }
            $managerEmail = strtolower($row['manager_email']);
            if ($managerEmail !== '' && !filter_var($managerEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid manager email.';
            }
            $departmentNew = $row['department'] !== ''
                && DB::fetch('SELECT id FROM departments WHERE name = ?', [$row['department']]) === null;

            $result[] = [
                'line' => $i + 2,
                'data' => [
                    'name' => $row['name'],
                    'email' => $email,
                    'job_title' => $row['job_title'],
                    'phone' => $row['phone'],
                    'department' => $row['department'],
                    'department_new' => $departmentNew,
                    'location' => $row['location'],
                    'timezone' => $row['timezone'],
                    'manager_email' => $managerEmail,
                    'roles' => $row['roles'],
                    'role_ids' => $roleIds,
                ],
                'errors' => $errors,
            ];
        }
        return $result;
    }

    private function cacheFile(): string
    {
        return BASE_PATH . '/storage/cache/user-import-' . (int) Auth::id() . '.json';
    }
}
