<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\ImageTool;
use App\Core\Mailer;
use App\Core\RememberToken;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\View;
use App\Models\User;

final class UserController
{
    public function index(): void
    {
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'department_id' => (string) ($_GET['department_id'] ?? ''),
            'role_id' => (string) ($_GET['role_id'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'sort' => (string) ($_GET['sort'] ?? 'name'),
            'dir' => (string) ($_GET['dir'] ?? 'asc'),
        ];
        $result = User::search($filters, max(1, (int) ($_GET['page'] ?? 1)));
        View::render('admin/users/index', [
            'title' => 'Users',
            'result' => $result,
            'filters' => $filters,
            'departments' => DB::fetchAll('SELECT id, name FROM departments ORDER BY name'),
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect('admin/users');
        }
        $this->form($user);
    }

    public function store(): void
    {
        $data = $this->validated(null);
        if ($data === null) {
            redirect('admin/users/create');
        }
        $now = date('Y-m-d H:i:s');
        $sendInvite = !empty($_POST['send_invite']);
        $password = (string) ($_POST['password'] ?? '');

        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['email_verified_at'] = $now;
        $data['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_ARGON2ID) : null;
        $data['must_change_password'] = ($sendInvite || ($password !== '' && !empty($_POST['must_change_password']))) ? 1 : 0;

        $userId = DB::insert('users', $data);
        User::syncRoles($userId, (array) ($_POST['roles'] ?? []));
        $this->applyAvatar($userId);
        Audit::log('user.created', 'user', $userId, ['email' => $data['email'], 'invited' => $sendInvite]);

        if ($sendInvite) {
            $this->sendInvite($userId, $data['email'], $data['name']);
        }
        flash('success', 'User created' . ($sendInvite ? ' and invite email sent.' : '.'));
        redirect('admin/users');
    }

    public function update(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect('admin/users');
        }
        $data = $this->validated((int) $id);
        if ($data === null) {
            redirect('admin/users/' . $id . '/edit');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_ARGON2ID);
            $data['must_change_password'] = !empty($_POST['must_change_password']) ? 1 : 0;
            RememberToken::clearAllFor((int) $id);
        }
        DB::update('users', $data, 'id = ?', [(int) $id]);
        User::syncRoles((int) $id, (array) ($_POST['roles'] ?? []));
        $this->applyAvatar((int) $id);
        Audit::log('user.updated', 'user', (int) $id, ['email' => $data['email']]);
        flash('success', 'User updated.');
        redirect('admin/users');
    }

    public function toggleStatus(string $id): void
    {
        $user = $this->findOr404($id);
        if ((int) $user['id'] === Auth::id()) {
            flash('error', 'You cannot deactivate your own account.');
            redirect('admin/users');
        }
        $new = $user['status'] === 'active' ? 'inactive' : 'active';
        DB::update('users', ['status' => $new], 'id = ?', [(int) $id]);
        if ($new !== 'active') {
            RememberToken::clearAllFor((int) $id);
        }
        Audit::log('user.status_changed', 'user', (int) $id, ['from' => $user['status'], 'to' => $new]);
        flash('success', $user['name'] . ' is now ' . $new . '.');
        redirect('admin/users');
    }

    public function destroy(string $id): void
    {
        $user = $this->findOr404($id);
        if ((int) $user['id'] === Auth::id()) {
            flash('error', 'You cannot delete your own account.');
            redirect('admin/users');
        }
        DB::delete('users', 'id = ?', [(int) $id]);
        Audit::log('user.deleted', 'user', (int) $id, ['email' => $user['email'], 'name' => $user['name']]);
        flash('success', 'User deleted. Their content is now unattributed.');
        redirect('admin/users');
    }

    public function forceReset(string $id): void
    {
        $user = $this->findOr404($id);
        DB::update('users', ['must_change_password' => 1], 'id = ?', [(int) $id]);
        RememberToken::clearAllFor((int) $id);
        $this->sendInvite((int) $id, (string) $user['email'], (string) $user['name'], true);
        Audit::log('user.force_reset', 'user', (int) $id);
        flash('success', 'Password reset forced — a reset link was emailed to ' . $user['email'] . '.');
        redirect('admin/users');
    }

    public function impersonate(string $id): void
    {
        if (!Auth::hasRole('super_admin')) {
            flash('error', 'Only super admins can impersonate users.');
            redirect('admin/users');
        }
        $user = $this->findOr404($id);
        if ((int) $user['id'] === Auth::id() || $user['status'] !== 'active') {
            flash('error', 'Cannot impersonate that account.');
            redirect('admin/users');
        }
        $_SESSION['impersonator_id'] = Auth::id();
        $_SESSION['user_id'] = (int) $user['id'];
        Audit::log('user.impersonation_started', 'user', (int) $user['id']);
        Auth::refresh();
        flash('success', 'You are now viewing the portal as ' . $user['name'] . '.');
        redirect('/');
    }

    public function stopImpersonate(): void
    {
        $impersonatorId = (int) ($_SESSION['impersonator_id'] ?? 0);
        if ($impersonatorId > 0) {
            Audit::log('user.impersonation_ended', 'user', Auth::id(), [], $impersonatorId);
            $_SESSION['user_id'] = $impersonatorId;
            unset($_SESSION['impersonator_id']);
            Auth::refresh();
            flash('success', 'Returned to your own account.');
        }
        redirect('/');
    }

    // ---- helpers -----------------------------------------------------------

    private function form(?array $user): void
    {
        View::render('admin/users/form', [
            'title' => $user === null ? 'Create user' : 'Edit user',
            'user' => $user,
            'departments' => DB::fetchAll('SELECT id, name FROM departments ORDER BY name'),
            'managers' => DB::fetchAll("SELECT id, name, email FROM users WHERE status = 'active' ORDER BY name"),
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
            'userRoleIds' => $user === null ? [] : User::roleIds((int) $user['id']),
            'contentCounts' => $user === null ? [] : User::contentCounts((int) $user['id']),
        ], 'admin');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(?int $ignoreId): ?array
    {
        $v = new Validator($_POST, [
            'name' => 'required|max:150',
            'email' => 'required|email|max:190',
            'job_title' => 'max:150',
            'phone' => 'max:50',
            'location' => 'max:150',
            'status' => 'in:active,inactive,suspended',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            return null;
        }
        $email = strtolower(trim((string) $_POST['email']));
        $existing = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing !== null && (int) $existing['id'] !== $ignoreId) {
            flash('error', 'That email address is already in use.');
            Flash::keepInput();
            return null;
        }
        $managerId = !empty($_POST['manager_id']) ? (int) $_POST['manager_id'] : null;
        if ($managerId !== null && $managerId === $ignoreId) {
            $managerId = null; // no self-management
        }
        return [
            'name' => trim((string) $_POST['name']),
            'email' => $email,
            'job_title' => trim((string) ($_POST['job_title'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            'manager_id' => $managerId,
            'location' => trim((string) ($_POST['location'] ?? '')) ?: null,
            'timezone' => trim((string) ($_POST['timezone'] ?? '')) ?: null,
            'bio' => trim((string) ($_POST['bio'] ?? '')) ?: null,
            'status' => (string) ($_POST['status'] ?? 'active'),
        ];
    }

    private function applyAvatar(int $userId): void
    {
        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > 2 * 1024 * 1024) {
            flash('warning', 'Avatar skipped: upload failed or exceeds 2 MB.');
            return;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            flash('warning', 'Avatar skipped: only JPG, PNG or WebP images are allowed.');
            return;
        }
        $path = ImageTool::storeUpload((string) $file['tmp_name'], 'avatars', 256, 'jpeg');
        if ($path === null) {
            flash('warning', 'Avatar skipped: the image could not be processed.');
            return;
        }
        DB::update('users', ['avatar_path' => $path], 'id = ?', [$userId]);
    }

    private function sendInvite(int $userId, string $email, string $name, bool $isReset = false): void
    {
        $token = bin2hex(random_bytes(32));
        DB::run(
            'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP',
            [$email, hash('sha256', $token), date('Y-m-d H:i:s', time() + 48 * 3600)]
        );
        $link = base_url('password/reset/' . $token . '?email=' . rawurlencode($email));
        $site = (string) Settings::get('site_name', 'OpenIntranet');
        $subject = $isReset ? "Reset your {$site} password" : "You've been invited to {$site}";
        Mailer::send(
            $email,
            $subject,
            '<p>Hello ' . e($name) . ',</p>'
            . ($isReset
                ? '<p>An administrator has asked you to choose a new password.</p>'
                : "<p>An account has been created for you on <strong>" . e($site) . '</strong>.</p>')
            . '<p>Use this link to set your password (valid for 48 hours):</p>'
            . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
        );
    }

    private function findOr404(string $id): array
    {
        $user = User::find((int) $id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect('admin/users');
        }
        return $user;
    }
}
