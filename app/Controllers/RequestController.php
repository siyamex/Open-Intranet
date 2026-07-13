<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Notify;
use App\Core\View;
use App\Models\FormRequest;

final class RequestController
{
    public function catalog(): void
    {
        View::render('pages/requests/catalog', [
            'title' => 'Requests',
            'forms' => DB::fetchAll('SELECT * FROM forms WHERE is_published = 1 ORDER BY title'),
            'mine' => DB::fetchAll(
                'SELECT s.*, f.title AS form_title FROM form_submissions s
                 JOIN forms f ON f.id = s.form_id
                 WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 10',
                [Auth::id()]
            ),
            'pendingApprovals' => $this->pendingForMe(),
        ]);
    }

    public function show(string $slug): void
    {
        $form = $this->findPublished($slug);
        View::render('pages/requests/form', [
            'title' => (string) $form['title'],
            'form' => $form,
            'fields' => FormRequest::fields($form),
            'breadcrumbs' => [['Requests', url('requests.catalog')], [(string) $form['title'], null]],
        ]);
    }

    public function submit(string $slug): void
    {
        $form = $this->findPublished($slug);
        $fields = FormRequest::fields($form);
        $data = [];
        $answers = (array) ($_POST['field'] ?? []);

        // resolve which fields are visible given the submitted answers
        $visible = static function (array $field) use ($answers): bool {
            if ($field['condition'] === null) {
                return true;
            }
            return (string) ($answers[$field['condition']['field']] ?? '') === $field['condition']['value'];
        };

        foreach ($fields as $field) {
            if ($field['type'] === 'section') {
                continue;
            }
            if (!$visible($field)) {
                continue;
            }
            if ($field['type'] === 'file') {
                $file = $_FILES['field']['tmp_name'][$field['id']] ?? null;
                $error = $_FILES['field']['error'][$field['id']] ?? UPLOAD_ERR_NO_FILE;
                if ($error === UPLOAD_ERR_NO_FILE) {
                    if ($field['required']) {
                        flash('error', "'{$field['label']}' requires a file.");
                        redirect('requests/' . $slug);
                    }
                    continue;
                }
                $size = (int) ($_FILES['field']['size'][$field['id']] ?? 0);
                if ($error !== UPLOAD_ERR_OK || $size > 10 * 1024 * 1024) {
                    flash('error', "Upload for '{$field['label']}' failed (max 10 MB).");
                    redirect('requests/' . $slug);
                }
                $original = (string) ($_FILES['field']['name'][$field['id']] ?? 'file');
                if (preg_match('/\.(php\d?|phtml|phar|cgi|pl|sh|exe|bat|js|html?)(\.|$)/i', $original)) {
                    flash('error', 'That file type is not allowed.');
                    redirect('requests/' . $slug);
                }
                $dir = BASE_PATH . '/storage/uploads/forms';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $stored = bin2hex(random_bytes(18)) . '.bin';
                move_uploaded_file((string) $file, $dir . '/' . $stored);
                $data[$field['id']] = ['file' => $stored, 'name' => $original];
                continue;
            }
            $value = $field['type'] === 'checkbox'
                ? (!empty($answers[$field['id']]) ? 'yes' : 'no')
                : trim((string) ($answers[$field['id']] ?? ''));
            if ($field['type'] === 'select' && $value !== '' && !in_array($value, $field['options'], true)) {
                $value = '';
            }
            if ($field['required'] && ($value === '' || ($field['type'] === 'checkbox' && $value === 'no'))) {
                flash('error', "'{$field['label']}' is required.");
                redirect('requests/' . $slug);
            }
            $data[$field['id']] = mb_substr($value, 0, 4000);
        }

        $submissionId = DB::insert('form_submissions', [
            'form_id' => (int) $form['id'],
            'user_id' => Auth::id(),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'status' => 'submitted',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        FormRequest::logEvent($submissionId, 'submitted');
        foreach (FormRequest::approverIds($form, (int) Auth::id()) as $approverId) {
            Notify::send(
                $approverId,
                'forms',
                'Approval needed: ' . $form['title'],
                (string) (Auth::user()['name'] ?? '') . ' submitted a request.',
                url('requests.detail', ['id' => $submissionId])
            );
        }
        flash('success', 'Request submitted — you can follow its progress below.');
        redirect('requests/submission/' . $submissionId);
    }

    public function detail(string $id): void
    {
        $submission = DB::fetch(
            'SELECT s.*, f.title AS form_title, f.fields AS form_fields, f.approver_type, f.approver_user_id, f.approver_role_id,
                    u.name AS submitter_name
             FROM form_submissions s
             JOIN forms f ON f.id = s.form_id
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?',
            [(int) $id]
        );
        if ($submission === null) {
            $this->notFound();
        }
        $form = ['fields' => $submission['form_fields'], 'approver_type' => $submission['approver_type'],
            'approver_user_id' => $submission['approver_user_id'], 'approver_role_id' => $submission['approver_role_id']];
        $isSubmitter = (int) $submission['user_id'] === Auth::id();
        $isApprover = FormRequest::isApprover($form, $submission);
        if (!$isSubmitter && !$isApprover) {
            http_response_code(403);
            View::render('errors/403', [], null);
            exit;
        }
        View::render('pages/requests/detail', [
            'title' => 'Request #' . $submission['id'],
            'submission' => $submission,
            'fields' => FormRequest::fields($form),
            'data' => (array) json_decode((string) $submission['data'], true),
            'events' => DB::fetchAll(
                'SELECT e.*, u.name AS actor_name FROM form_submission_events e
                 LEFT JOIN users u ON u.id = e.actor_id
                 WHERE e.submission_id = ? ORDER BY e.id',
                [(int) $id]
            ),
            'isApprover' => $isApprover,
        ]);
    }

    public function act(string $id): void
    {
        $submission = DB::fetch(
            'SELECT s.*, f.title AS form_title, f.fields AS form_fields, f.approver_type, f.approver_user_id, f.approver_role_id
             FROM form_submissions s JOIN forms f ON f.id = s.form_id WHERE s.id = ?',
            [(int) $id]
        );
        if ($submission === null) {
            $this->notFound();
        }
        $form = ['fields' => $submission['form_fields'], 'approver_type' => $submission['approver_type'],
            'approver_user_id' => $submission['approver_user_id'], 'approver_role_id' => $submission['approver_role_id']];
        if (!FormRequest::isApprover($form, $submission)) {
            http_response_code(403);
            View::render('errors/403', [], null);
            exit;
        }
        $action = (string) ($_POST['action'] ?? '');
        $note = trim((string) ($_POST['note'] ?? '')) ?: null;
        $map = ['review' => 'in_review', 'approve' => 'approved', 'reject' => 'rejected'];
        if (!isset($map[$action]) || in_array($submission['status'], ['approved', 'rejected'], true)) {
            flash('error', 'This request has already been decided.');
            redirect('requests/submission/' . $id);
        }
        DB::update('form_submissions', [
            'status' => $map[$action],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $id]);
        FormRequest::logEvent((int) $id, $map[$action], $note);
        Notify::send(
            (int) $submission['user_id'],
            'forms',
            'Your request "' . $submission['form_title'] . '" is now ' . str_replace('_', ' ', $map[$action]),
            $note,
            url('requests.detail', ['id' => $id])
        );
        flash('success', 'Request marked as ' . str_replace('_', ' ', $map[$action]) . '.');
        redirect('requests/submission/' . $id);
    }

    public function file(string $file): void
    {
        if (!preg_match('/^[a-f0-9]{36}\.bin$/', $file)) {
            $this->notFound();
        }
        $submission = DB::fetch(
            "SELECT s.*, f.fields AS form_fields, f.approver_type, f.approver_user_id, f.approver_role_id
             FROM form_submissions s JOIN forms f ON f.id = s.form_id
             WHERE s.data LIKE ?",
            ['%' . $file . '%']
        );
        if ($submission === null) {
            $this->notFound();
        }
        $form = ['fields' => $submission['form_fields'], 'approver_type' => $submission['approver_type'],
            'approver_user_id' => $submission['approver_user_id'], 'approver_role_id' => $submission['approver_role_id']];
        if ((int) $submission['user_id'] !== Auth::id() && !FormRequest::isApprover($form, $submission)) {
            http_response_code(403);
            View::render('errors/403', [], null);
            exit;
        }
        $path = BASE_PATH . '/storage/uploads/forms/' . $file;
        if (!is_file($path)) {
            $this->notFound();
        }
        // find the original name in the submission data
        $original = 'attachment';
        foreach ((array) json_decode((string) $submission['data'], true) as $value) {
            if (is_array($value) && ($value['file'] ?? '') === $file) {
                $original = (string) ($value['name'] ?? 'attachment');
            }
        }
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $original) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    /**
     * Pending submissions the current user may approve.
     */
    private function pendingForMe(): array
    {
        $pending = DB::fetchAll(
            "SELECT s.*, f.title AS form_title, f.fields AS form_fields, f.approver_type, f.approver_user_id, f.approver_role_id,
                    u.name AS submitter_name
             FROM form_submissions s
             JOIN forms f ON f.id = s.form_id
             JOIN users u ON u.id = s.user_id
             WHERE s.status IN ('submitted', 'in_review')
             ORDER BY s.created_at LIMIT 100"
        );
        return array_values(array_filter($pending, static function (array $s): bool {
            $form = ['fields' => $s['form_fields'], 'approver_type' => $s['approver_type'],
                'approver_user_id' => $s['approver_user_id'], 'approver_role_id' => $s['approver_role_id']];
            return FormRequest::isApprover($form, $s);
        }));
    }

    private function findPublished(string $slug): array
    {
        $form = DB::fetch('SELECT * FROM forms WHERE slug = ? AND is_published = 1', [$slug]);
        if ($form === null) {
            $this->notFound();
        }
        return $form;
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], null);
        exit;
    }
}
