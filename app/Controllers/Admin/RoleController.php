<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;

final class RoleController
{
    public function index(): void
    {
        $roles = DB::fetchAll(
            'SELECT r.*, (SELECT COUNT(*) FROM user_role ur WHERE ur.role_id = r.id) AS user_count
             FROM roles r ORDER BY r.is_system DESC, r.name'
        );
        $permissions = DB::fetchAll('SELECT * FROM permissions ORDER BY group_name, slug');
        $groups = [];
        foreach ($permissions as $permission) {
            $groups[$permission['group_name']][] = $permission;
        }
        $matrix = [];
        foreach (DB::fetchAll('SELECT role_id, permission_id FROM role_permission') as $row) {
            $matrix[(int) $row['role_id']][(int) $row['permission_id']] = true;
        }
        View::render('admin/roles/index', [
            'title' => 'Roles & Permissions',
            'roles' => $roles,
            'groups' => $groups,
            'matrix' => $matrix,
        ], 'admin');
    }

    public function store(): void
    {
        $v = new Validator($_POST, ['name' => 'required|max:100']);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            redirect('admin/roles');
        }
        $name = trim((string) $_POST['name']);
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $name), '_'));
        if ($slug === '' || DB::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]) !== null) {
            flash('error', 'A role with that name already exists.');
            redirect('admin/roles');
        }
        $id = DB::insert('roles', [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'is_system' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('role.created', 'role', $id, ['name' => $name]);
        flash('success', "Role '{$name}' created — tick its permissions below and save.");
        redirect('admin/roles');
    }

    /**
     * Save the whole permission matrix in one POST.
     */
    public function saveMatrix(): void
    {
        $submitted = (array) ($_POST['perm'] ?? []); // perm[role_id][permission_id] = 1
        $roles = DB::fetchAll('SELECT id, slug FROM roles');
        $permissionIds = array_map('intval', array_column(DB::fetchAll('SELECT id FROM permissions'), 'id'));
        foreach ($roles as $role) {
            $roleId = (int) $role['id'];
            if ($role['slug'] === 'super_admin') {
                continue; // super_admin bypasses permissions; keep its rows untouched
            }
            $wanted = array_keys(array_filter((array) ($submitted[$roleId] ?? [])));
            $wanted = array_values(array_intersect(array_map('intval', $wanted), $permissionIds));
            DB::delete('role_permission', 'role_id = ?', [$roleId]);
            foreach ($wanted as $permissionId) {
                DB::run('INSERT IGNORE INTO role_permission (role_id, permission_id) VALUES (?, ?)', [$roleId, $permissionId]);
            }
        }
        Audit::log('role.matrix_updated', 'role', null);
        flash('success', 'Permission matrix saved.');
        redirect('admin/roles');
    }

    public function destroy(string $id): void
    {
        $role = DB::fetch('SELECT * FROM roles WHERE id = ?', [(int) $id]);
        if ($role === null) {
            flash('error', 'Role not found.');
            redirect('admin/roles');
        }
        if ((int) $role['is_system'] === 1) {
            flash('error', 'System roles cannot be deleted.');
            redirect('admin/roles');
        }
        DB::delete('roles', 'id = ?', [(int) $id]);
        Audit::log('role.deleted', 'role', (int) $id, ['name' => $role['name']]);
        flash('success', "Role '{$role['name']}' deleted.");
        redirect('admin/roles');
    }
}
