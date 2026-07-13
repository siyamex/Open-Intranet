<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Models\FormRequest;

final class FormController
{
    public function index(): void
    {
        View::render('admin/forms/index', [
            'title' => 'Request forms',
            'forms' => DB::fetchAll(
                'SELECT f.*, (SELECT COUNT(*) FROM form_submissions s WHERE s.form_id = f.id) AS submissions
                 FROM forms f ORDER BY f.title'
            ),
        ], 'admin');
    }

    public function create(): void
    {
        $this->builder(null);
    }

    public function edit(string $id): void
    {
        $this->builder($this->find($id));
    }

    public function store(): void
    {
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/forms');
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $data['title']), '-')) ?: 'form';
        while (DB::fetch('SELECT id FROM forms WHERE slug = ?', [$slug]) !== null) {
            $slug .= '-2';
        }
        $data['slug'] = $slug;
        $data['created_by'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('forms', $data);
        Audit::log('form.created', 'form', $id, ['title' => $data['title']]);
        flash('success', 'Form saved — publish it when it is ready.');
        redirect('admin/forms/' . $id . '/edit');
    }

    public function update(string $id): void
    {
        $this->find($id);
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/forms/' . $id . '/edit');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('forms', $data, 'id = ?', [(int) $id]);
        Audit::log('form.updated', 'form', (int) $id, ['title' => $data['title']]);
        flash('success', 'Form updated.');
        redirect('admin/forms/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $form = $this->find($id);
        DB::delete('forms', 'id = ?', [(int) $id]);
        Audit::log('form.deleted', 'form', (int) $id, ['title' => $form['title']]);
        flash('success', 'Form and its submissions deleted.');
        redirect('admin/forms');
    }

    public function submissions(string $id): void
    {
        $form = $this->find($id);
        $rows = DB::fetchAll(
            'SELECT s.*, u.name AS submitter_name FROM form_submissions s
             JOIN users u ON u.id = s.user_id
             WHERE s.form_id = ? ORDER BY s.created_at DESC LIMIT 500',
            [(int) $id]
        );
        if (($_GET['export'] ?? '') === 'csv') {
            $fields = FormRequest::fields($form);
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $form['slug'] . '-submissions.csv"');
            $out = fopen('php://output', 'w');
            $header = ['id', 'submitted_by', 'status', 'created_at'];
            foreach ($fields as $field) {
                if ($field['type'] !== 'section') {
                    $header[] = $field['label'];
                }
            }
            fputcsv($out, $header);
            foreach ($rows as $row) {
                $data = (array) json_decode((string) $row['data'], true);
                $line = [$row['id'], $row['submitter_name'], $row['status'], $row['created_at']];
                foreach ($fields as $field) {
                    if ($field['type'] === 'section') {
                        continue;
                    }
                    $value = $data[$field['id']] ?? '';
                    $line[] = is_array($value) ? ($value['name'] ?? 'file') : $value;
                }
                fputcsv($out, $line);
            }
            fclose($out);
            exit;
        }
        View::render('admin/forms/submissions', [
            'title' => 'Submissions — ' . $form['title'],
            'form' => $form,
            'rows' => $rows,
        ], 'admin');
    }

    private function builder(?array $form): void
    {
        View::render('admin/forms/builder', [
            'title' => $form === null ? 'New form' : 'Edit form — ' . $form['title'],
            'form' => $form,
            'fields' => $form !== null ? FormRequest::fields($form) : [],
            'people' => DB::fetchAll("SELECT id, name FROM users WHERE status = 'active' ORDER BY name"),
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $fieldsJson = (string) ($_POST['fields'] ?? '[]');
        $decoded = json_decode($fieldsJson, true);
        if ($title === '' || mb_strlen($title) > 150 || !is_array($decoded)) {
            flash('error', 'A form needs a title and valid fields.');
            return null;
        }
        // normalize through the same sanitizer the renderer uses
        $fields = FormRequest::fields(['fields' => $fieldsJson]);
        if (count(array_filter($fields, static fn (array $f): bool => $f['type'] !== 'section')) === 0) {
            flash('error', 'Add at least one input field.');
            return null;
        }
        $approverType = in_array($_POST['approver_type'] ?? '', ['user', 'manager', 'role'], true)
            ? (string) $_POST['approver_type'] : 'manager';
        return [
            'title' => $title,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
            'approver_type' => $approverType,
            'approver_user_id' => $approverType === 'user' && !empty($_POST['approver_user_id']) ? (int) $_POST['approver_user_id'] : null,
            'approver_role_id' => $approverType === 'role' && !empty($_POST['approver_role_id']) ? (int) $_POST['approver_role_id'] : null,
            'is_published' => !empty($_POST['is_published']) ? 1 : 0,
            'retention_days' => !empty($_POST['retention_days']) ? max(7, min(3650, (int) $_POST['retention_days'])) : null,
        ];
    }

    private function find(string $id): array
    {
        $form = DB::fetch('SELECT * FROM forms WHERE id = ?', [(int) $id]);
        if ($form === null) {
            flash('error', 'Form not found.');
            redirect('admin/forms');
        }
        return $form;
    }
}
