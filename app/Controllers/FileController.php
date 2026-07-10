<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Visibility;
use App\Core\View;

/**
 * Permission-checked document delivery — the ONLY way document files are
 * served. PDFs and images render inline (browser viewer); all else downloads.
 */
final class FileController
{
    private const INLINE_MIMES = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    public function serve(string $uuid): void
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $uuid)) {
            $this->notFound();
        }
        $doc = DB::fetch(
            'SELECT d.*, c.visible_to AS category_visible_to
             FROM documents d
             LEFT JOIN doc_categories c ON c.id = d.category_id
             WHERE d.uuid = ?',
            [$uuid]
        );
        if ($doc === null) {
            $this->notFound();
        }
        if (!$this->mayAccess($doc)) {
            http_response_code(403);
            View::render('errors/403', [], null);
            exit;
        }
        $path = BASE_PATH . '/storage/uploads/' . $doc['file_path'];
        if (!is_file($path)) {
            $this->notFound();
        }

        DB::run('UPDATE documents SET download_count = download_count + 1 WHERE id = ?', [(int) $doc['id']]);

        $mime = (string) $doc['mime'];
        $disposition = in_array($mime, self::INLINE_MIMES, true) ? 'inline' : 'attachment';
        $filename = str_replace(['"', "\r", "\n"], '', (string) $doc['original_name']);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($path);
        exit;
    }

    private function mayAccess(array $doc): bool
    {
        if (Auth::can('docs.manage') || (int) ($doc['uploaded_by'] ?? 0) === Auth::id()) {
            return true;
        }
        return Visibility::allowed($doc['visible_to'] ?? null)
            && Visibility::allowed($doc['category_visible_to'] ?? null);
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], null);
        exit;
    }
}
