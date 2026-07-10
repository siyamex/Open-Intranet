<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\ImageTool;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\View;

final class ProfileController
{
    private const ALL_EDITABLE = ['name', 'phone', 'location', 'timezone', 'bio', 'avatar'];

    public function edit(): void
    {
        View::render('profile/edit', [
            'title' => 'My profile',
            'user' => Auth::user(),
            'editable' => $this->editableFields(),
            'department' => $this->departmentName(),
        ]);
    }

    public function update(): void
    {
        $editable = $this->editableFields();
        $v = new Validator($_POST, [
            'name' => in_array('name', $editable, true) ? 'required|max:150' : 'max:150',
            'phone' => 'max:50',
            'location' => 'max:150',
            'timezone' => 'max:64',
            'bio' => 'max:2000',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            redirect('profile');
        }
        $data = [];
        foreach (['name', 'phone', 'location', 'timezone', 'bio'] as $field) {
            if (in_array($field, $editable, true) && array_key_exists($field, $_POST)) {
                $value = trim((string) $_POST[$field]);
                $data[$field] = $value === '' && $field !== 'name' ? null : $value;
            }
        }
        if ($data !== []) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            DB::update('users', $data, 'id = ?', [Auth::id()]);
        }
        if (in_array('avatar', $editable, true)) {
            $this->applyAvatar();
        }
        Auth::refresh();
        Audit::log('profile.updated', 'user', Auth::id());
        flash('success', 'Profile updated.');
        redirect('profile');
    }

    /**
     * Persist the navbar dark-mode preference (auto/light/dark).
     */
    public function saveThemeMode(): void
    {
        $mode = (string) ($_POST['mode'] ?? 'auto');
        if (!in_array($mode, ['auto', 'light', 'dark'], true)) {
            $mode = 'auto';
        }
        DB::run(
            "INSERT INTO user_prefs (user_id, `key`, `value`) VALUES (?, 'theme_mode', ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [Auth::id(), $mode]
        );
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'mode' => $mode]);
        exit;
    }

    private function applyAvatar(): void
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
        if ($path !== null) {
            DB::update('users', ['avatar_path' => $path], 'id = ?', [Auth::id()]);
        }
    }

    /**
     * @return string[]
     */
    private function editableFields(): array
    {
        $fields = Settings::get('profile_self_editable', self::ALL_EDITABLE);
        return is_array($fields) ? array_values(array_intersect($fields, self::ALL_EDITABLE)) : self::ALL_EDITABLE;
    }

    private function departmentName(): ?string
    {
        $deptId = Auth::user()['department_id'] ?? null;
        if ($deptId === null) {
            return null;
        }
        return DB::scalar('SELECT name FROM departments WHERE id = ?', [(int) $deptId]);
    }
}
