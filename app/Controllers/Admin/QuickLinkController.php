<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Flash;
use App\Core\ImageTool;
use App\Core\SvgSanitizer;
use App\Core\Validator;
use App\Core\View;
use App\Models\QuickLink;

final class QuickLinkController
{
    public function index(): void
    {
        $links = DB::fetchAll('SELECT * FROM quick_links ORDER BY sort_order, id');
        foreach ($links as &$link) {
            $link['spark'] = QuickLink::sparkline((int) $link['id']);
            $link['clicks_30d'] = array_sum($link['spark']);
        }
        unset($link);
        View::render('admin/quick-links/index', [
            'title' => 'Quick Links',
            'links' => $links,
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
            'icons' => array_map(static fn (string $f): string => basename($f, '.svg'), glob(BASE_PATH . '/public/assets/icons/*.svg') ?: []),
            'view' => ($_GET['view'] ?? 'grid') === 'table' ? 'table' : 'grid',
        ], 'admin');
    }

    public function store(): void
    {
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/quick-links');
        }
        $data['sort_order'] = (int) (DB::scalar('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM quick_links') ?? 10);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('quick_links', $data);
        Audit::log('quick_link.created', 'quick_link', $id, ['title' => $data['title']]);
        flash('success', 'Quick link added.');
        redirect('admin/quick-links');
    }

    public function update(string $id): void
    {
        $link = DB::fetch('SELECT * FROM quick_links WHERE id = ?', [(int) $id]);
        if ($link === null) {
            flash('error', 'Link not found.');
            redirect('admin/quick-links');
        }
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/quick-links');
        }
        if ($data['icon_type'] === 'upload' && $data['icon_value'] === null) {
            // no new upload — keep the existing uploaded icon
            unset($data['icon_type'], $data['icon_value']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('quick_links', $data, 'id = ?', [(int) $id]);
        Audit::log('quick_link.updated', 'quick_link', (int) $id, ['title' => $data['title']]);
        flash('success', 'Quick link updated.');
        redirect('admin/quick-links');
    }

    public function destroy(string $id): void
    {
        $link = DB::fetch('SELECT * FROM quick_links WHERE id = ?', [(int) $id]);
        if ($link !== null) {
            DB::delete('quick_links', 'id = ?', [(int) $id]);
            Audit::log('quick_link.deleted', 'quick_link', (int) $id, ['title' => $link['title']]);
            flash('success', 'Quick link deleted.');
        }
        redirect('admin/quick-links');
    }

    public function reorder(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $ids = array_map('intval', (array) ($payload['order'] ?? []));
        $sort = 10;
        foreach ($ids as $id) {
            DB::update('quick_links', ['sort_order' => $sort], 'id = ?', [$id]);
            $sort += 10;
        }
        Audit::log('quick_link.reordered', 'quick_link', null);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $v = new Validator($_POST, [
            'title' => 'required|max:150',
            'url' => 'required|url|max:500',
            'description' => 'max:255',
            'bg_color' => 'regex:/^$|^#[0-9a-fA-F]{3,8}$/',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            Flash::keepInput();
            return null;
        }
        $iconType = ($_POST['icon_type'] ?? 'library') === 'upload' ? 'upload' : 'library';
        $iconValue = null;
        if ($iconType === 'library') {
            $iconValue = trim((string) ($_POST['icon_library'] ?? '')) ?: null;
        } else {
            $iconValue = $this->storeIconUpload();
            if ($iconValue === false) {
                return null; // error already flashed
            }
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        return [
            'title' => trim((string) $_POST['title']),
            'url' => trim((string) $_POST['url']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'icon_type' => $iconType,
            'icon_value' => $iconValue,
            'bg_color' => trim((string) ($_POST['bg_color'] ?? '')) ?: null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'open_new_tab' => !empty($_POST['open_new_tab']) ? 1 : 0,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ];
    }

    /**
     * @return string|null|false path on success, null when nothing uploaded, false on error
     */
    private function storeIconUpload(): string|null|false
    {
        $file = $_FILES['icon_file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > 512 * 1024) {
            flash('error', 'Icon upload failed or exceeds 512 KB.');
            return false;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        $dir = BASE_PATH . '/storage/uploads/qlicons';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'], true)
            && str_contains(strtolower((string) $file['name']), '.svg')) {
            $clean = SvgSanitizer::sanitize((string) file_get_contents((string) $file['tmp_name']));
            if ($clean === null) {
                flash('error', 'That SVG could not be sanitized — please use a simple icon file.');
                return false;
            }
            $name = bin2hex(random_bytes(12)) . '.svg';
            file_put_contents($dir . '/' . $name, $clean, LOCK_EX);
            return $name;
        }
        if (in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            $encoded = ImageTool::resizeEncode((string) file_get_contents((string) $file['tmp_name']), 128, 'png');
            if ($encoded === null) {
                flash('error', 'The icon image could not be processed.');
                return false;
            }
            $name = bin2hex(random_bytes(12)) . '.png';
            file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
            return $name;
        }
        flash('error', 'Icons must be SVG or PNG/JPG/WebP images.');
        return false;
    }
}
