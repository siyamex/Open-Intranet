<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\View;

final class DocumentController
{
    /** Extension => acceptable finfo MIME types (OOXML files also sniff as zip). */
    private const TYPES = [
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'zip' => ['application/zip'],
    ];

    /** Canonical MIME stored + served per extension. */
    private const SERVE_MIME = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'zip' => 'application/zip',
    ];

    public function index(): void
    {
        $docs = DB::fetchAll(
            'SELECT d.*, c.name AS category_name, u.name AS uploader_name,
                    (SELECT COUNT(*) FROM documents v WHERE v.parent_doc_id = d.id) AS old_versions
             FROM documents d
             LEFT JOIN doc_categories c ON c.id = d.category_id
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE d.parent_doc_id IS NULL
             ORDER BY d.created_at DESC LIMIT 300'
        );
        View::render('admin/documents/index', [
            'title' => 'Documents',
            'docs' => $docs,
            'categories' => DB::fetchAll('SELECT * FROM doc_categories ORDER BY name'),
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
            'allowedTypes' => $this->allowedExtensions(),
            'maxMb' => (int) Settings::get('upload_max_mb', 20),
            'canManage' => Auth::can('docs.manage'),
        ], 'admin');
    }

    public function store(): void
    {
        $v = new Validator($_POST, ['title' => 'required|max:255', 'description' => 'max:2000']);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            redirect('admin/documents');
        }
        $stored = $this->storeUpload();
        if ($stored === null) {
            redirect('admin/documents');
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $now = date('Y-m-d H:i:s');
        $id = DB::insert('documents', [
            'uuid' => $this->uuid(),
            'title' => trim((string) $_POST['title']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'file_path' => $stored['path'],
            'original_name' => $stored['original'],
            'mime' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'version' => 1,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'uploaded_by' => Auth::id(),
            'is_gazette' => !empty($_POST['is_gazette']) ? 1 : 0,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::log('document.uploaded', 'document', $id, ['title' => $_POST['title'], 'name' => $stored['original']]);
        if (!empty($_POST['is_gazette'])) {
            $userIds = array_map('intval', array_column(DB::fetchAll(
                "SELECT id FROM users WHERE status = 'active' AND id != ?",
                [Auth::id()]
            ), 'id'));
            foreach ($userIds as $userId) {
                \App\Core\Notify::send($userId, 'documents', 'New gazette document: ' . trim((string) $_POST['title']), null, base_url('documents'));
            }
        }
        flash('success', 'Document uploaded.');
        redirect('admin/documents');
    }

    public function update(string $id): void
    {
        $doc = $this->findHead($id);
        $v = new Validator($_POST, ['title' => 'required|max:255', 'description' => 'max:2000']);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            redirect('admin/documents');
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        DB::update('documents', [
            'title' => trim((string) $_POST['title']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'is_gazette' => !empty($_POST['is_gazette']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $id]);
        Audit::log('document.updated', 'document', (int) $id, ['title' => $_POST['title']]);
        flash('success', 'Document updated.');
        redirect('admin/documents');
    }

    /**
     * Upload a new version: the current file is archived as a version row,
     * the head row gets the new file and version + 1.
     */
    public function newVersion(string $id): void
    {
        $doc = $this->findHead($id);
        $stored = $this->storeUpload();
        if ($stored === null) {
            redirect('admin/documents');
        }
        $note = trim((string) ($_POST['version_note'] ?? '')) ?: null;
        $now = date('Y-m-d H:i:s');
        // Archive the current file as a version row
        DB::insert('documents', [
            'uuid' => $this->uuid(),
            'title' => $doc['title'],
            'description' => $doc['description'],
            'category_id' => $doc['category_id'],
            'file_path' => $doc['file_path'],
            'original_name' => $doc['original_name'],
            'mime' => $doc['mime'],
            'size_bytes' => $doc['size_bytes'],
            'version' => (int) $doc['version'],
            'version_note' => $doc['version_note'],
            'parent_doc_id' => (int) $doc['id'],
            'visible_to' => $doc['visible_to'],
            'uploaded_by' => $doc['uploaded_by'],
            'is_gazette' => 0,
            'created_at' => $doc['created_at'],
            'updated_at' => $now,
        ]);
        DB::update('documents', [
            'file_path' => $stored['path'],
            'original_name' => $stored['original'],
            'mime' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'version' => (int) $doc['version'] + 1,
            'version_note' => $note,
            'uploaded_by' => Auth::id(),
            'updated_at' => $now,
        ], 'id = ?', [(int) $doc['id']]);
        Audit::log('document.new_version', 'document', (int) $doc['id'], ['version' => (int) $doc['version'] + 1, 'note' => $note]);
        flash('success', 'New version uploaded (v' . ((int) $doc['version'] + 1) . ').');
        redirect('admin/documents/' . $doc['id'] . '/versions');
    }

    public function versions(string $id): void
    {
        $doc = $this->findHead($id);
        $versions = DB::fetchAll(
            'SELECT * FROM documents WHERE parent_doc_id = ? ORDER BY version DESC',
            [(int) $doc['id']]
        );
        View::render('admin/documents/versions', [
            'title' => 'Versions — ' . $doc['title'],
            'doc' => $doc,
            'versions' => $versions,
        ], 'admin');
    }

    public function restore(string $versionId): void
    {
        $version = DB::fetch('SELECT * FROM documents WHERE id = ? AND parent_doc_id IS NOT NULL', [(int) $versionId]);
        if ($version === null) {
            flash('error', 'Version not found.');
            redirect('admin/documents');
        }
        $head = DB::fetch('SELECT * FROM documents WHERE id = ?', [(int) $version['parent_doc_id']]);
        if ($head === null) {
            flash('error', 'Original document not found.');
            redirect('admin/documents');
        }
        $now = date('Y-m-d H:i:s');
        // Archive the current head file, then copy the old version's file up
        DB::insert('documents', [
            'uuid' => $this->uuid(),
            'title' => $head['title'],
            'description' => $head['description'],
            'category_id' => $head['category_id'],
            'file_path' => $head['file_path'],
            'original_name' => $head['original_name'],
            'mime' => $head['mime'],
            'size_bytes' => $head['size_bytes'],
            'version' => (int) $head['version'],
            'version_note' => $head['version_note'],
            'parent_doc_id' => (int) $head['id'],
            'visible_to' => $head['visible_to'],
            'uploaded_by' => $head['uploaded_by'],
            'is_gazette' => 0,
            'created_at' => $head['created_at'],
            'updated_at' => $now,
        ]);
        DB::update('documents', [
            'file_path' => $version['file_path'],
            'original_name' => $version['original_name'],
            'mime' => $version['mime'],
            'size_bytes' => $version['size_bytes'],
            'version' => (int) $head['version'] + 1,
            'version_note' => 'Restored v' . $version['version'],
            'updated_at' => $now,
        ], 'id = ?', [(int) $head['id']]);
        Audit::log('document.version_restored', 'document', (int) $head['id'], ['restored_version' => (int) $version['version']]);
        flash('success', 'Version v' . $version['version'] . ' restored as v' . ((int) $head['version'] + 1) . '.');
        redirect('admin/documents/' . $head['id'] . '/versions');
    }

    public function destroy(string $id): void
    {
        $doc = $this->findHead($id);
        $files = array_column(DB::fetchAll(
            'SELECT file_path FROM documents WHERE id = ? OR parent_doc_id = ?',
            [(int) $doc['id'], (int) $doc['id']]
        ), 'file_path');
        DB::delete('documents', 'id = ?', [(int) $doc['id']]); // versions cascade
        foreach (array_unique($files) as $file) {
            @unlink(BASE_PATH . '/storage/uploads/' . $file);
        }
        Audit::log('document.deleted', 'document', (int) $doc['id'], ['title' => $doc['title']]);
        flash('success', 'Document and its versions deleted.');
        redirect('admin/documents');
    }

    public function bulk(): void
    {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $action = (string) ($_POST['bulk_action'] ?? '');
        if ($ids === []) {
            flash('error', 'Select at least one document.');
            redirect('admin/documents');
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            $files = array_column(DB::fetchAll(
                "SELECT file_path FROM documents WHERE id IN ({$placeholders}) OR parent_doc_id IN ({$placeholders})",
                array_merge($ids, $ids)
            ), 'file_path');
            DB::run("DELETE FROM documents WHERE id IN ({$placeholders}) AND parent_doc_id IS NULL", $ids);
            foreach (array_unique($files) as $file) {
                @unlink(BASE_PATH . '/storage/uploads/' . $file);
            }
            Audit::log('document.bulk_deleted', 'document', null, ['ids' => $ids]);
            flash('success', count($ids) . ' document(s) deleted.');
        } elseif ($action === 'move') {
            $categoryId = !empty($_POST['move_category_id']) ? (int) $_POST['move_category_id'] : null;
            DB::run(
                "UPDATE documents SET category_id = ? WHERE id IN ({$placeholders}) AND parent_doc_id IS NULL",
                array_merge([$categoryId], $ids)
            );
            Audit::log('document.bulk_moved', 'document', null, ['ids' => $ids, 'category_id' => $categoryId]);
            flash('success', count($ids) . ' document(s) moved.');
        }
        redirect('admin/documents');
    }

    // ---- Category CRUD -----------------------------------------------------

    public function storeCategory(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            flash('error', 'Category name is required.');
            redirect('admin/documents');
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'category';
        if (DB::fetch('SELECT id FROM doc_categories WHERE slug = ?', [$slug]) !== null) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $id = DB::insert('doc_categories', [
            'name' => $name,
            'slug' => $slug,
            'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('doc_category.created', 'doc_category', $id, ['name' => $name]);
        flash('success', 'Category created.');
        redirect('admin/documents');
    }

    public function destroyCategory(string $id): void
    {
        $category = DB::fetch('SELECT * FROM doc_categories WHERE id = ?', [(int) $id]);
        if ($category !== null) {
            DB::delete('doc_categories', 'id = ?', [(int) $id]); // docs keep, category_id set null
            Audit::log('doc_category.deleted', 'doc_category', (int) $id, ['name' => $category['name']]);
            flash('success', 'Category deleted (documents were kept).');
        }
        redirect('admin/documents');
    }

    // ---- helpers -------------------------------------------------------------

    /**
     * @return array{path: string, original: string, mime: string, size: int}|null
     */
    private function storeUpload(): ?array
    {
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a file to upload.');
            return null;
        }
        $maxBytes = ((int) Settings::get('upload_max_mb', 20)) * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            flash('error', 'File exceeds the ' . Settings::get('upload_max_mb', 20) . ' MB limit.');
            return null;
        }
        // Storage quotas (per user + global)
        $userQuota = ((int) Settings::get('storage_quota_user_mb', 500)) * 1024 * 1024;
        $globalQuota = ((int) Settings::get('storage_quota_global_mb', 10240)) * 1024 * 1024;
        $userUsed = (int) DB::scalar('SELECT COALESCE(SUM(size_bytes), 0) FROM documents WHERE uploaded_by = ?', [Auth::id()]);
        $globalUsed = (int) DB::scalar('SELECT COALESCE(SUM(size_bytes), 0) FROM documents');
        if ($userUsed + (int) $file['size'] > $userQuota) {
            flash('error', 'Your personal storage quota (' . Settings::get('storage_quota_user_mb', 500) . ' MB) would be exceeded.');
            return null;
        }
        if ($globalUsed + (int) $file['size'] > $globalQuota) {
            flash('error', 'The global storage quota is full — contact your administrator.');
            return null;
        }
        $original = (string) $file['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = $this->allowedExtensions();
        if (!in_array($ext, $allowed, true) || !isset(self::TYPES[$ext])) {
            flash('error', 'File type .' . $ext . ' is not allowed (' . implode(', ', $allowed) . ').');
            return null;
        }
        // Double-extension guard: no second suspicious extension in the name
        if (preg_match('/\.(php\d?|phtml|phar|cgi|pl|sh|exe|bat|js|html?)\./i', $original)) {
            flash('error', 'Suspicious file name rejected.');
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sniffed = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($sniffed, self::TYPES[$ext], true)) {
            flash('error', "The file's actual content ({$sniffed}) doesn't match its .{$ext} extension.");
            return null;
        }
        $dir = BASE_PATH . '/storage/uploads/docs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $storedName = bin2hex(random_bytes(20)) . '.bin';
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $storedName)) {
            flash('error', 'The upload could not be stored.');
            return null;
        }
        return [
            'path' => 'docs/' . $storedName,
            'original' => $original,
            'mime' => self::SERVE_MIME[$ext],
            'size' => (int) $file['size'],
        ];
    }

    /**
     * @return string[]
     */
    private function allowedExtensions(): array
    {
        $configured = Settings::get('allowed_doc_types', array_keys(self::TYPES));
        $configured = is_array($configured) ? $configured : array_keys(self::TYPES);
        return array_values(array_intersect(array_map('strtolower', $configured), array_keys(self::TYPES)));
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function findHead(string $id): array
    {
        $doc = DB::fetch('SELECT * FROM documents WHERE id = ? AND parent_doc_id IS NULL', [(int) $id]);
        if ($doc === null) {
            flash('error', 'Document not found.');
            redirect('admin/documents');
        }
        return $doc;
    }
}
