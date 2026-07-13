<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\BannerService;
use App\Core\DB;
use App\Core\Validator;
use App\Core\View;

final class BannerController
{
    public function index(): void
    {
        View::render('admin/banners/index', [
            'title' => 'Emergency Banners',
            'banners' => DB::fetchAll(
                'SELECT b.*, u.name AS creator_name,
                        (SELECT COUNT(*) FROM banner_acknowledgements a WHERE a.banner_id = b.id) AS ack_count
                 FROM banners b LEFT JOIN users u ON u.id = b.created_by
                 ORDER BY b.created_at DESC'
            ),
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function store(): void
    {
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/banners');
        }
        $data['created_by'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('banners', $data);
        BannerService::invalidate();
        Audit::log('banner.created', 'banner', $id, ['message' => $data['message']]);
        flash('success', 'Banner published.');
        redirect('admin/banners');
    }

    public function destroy(string $id): void
    {
        $banner = DB::fetch('SELECT * FROM banners WHERE id = ?', [(int) $id]);
        if ($banner !== null) {
            DB::delete('banners', 'id = ?', [(int) $id]);
            BannerService::invalidate();
            Audit::log('banner.deleted', 'banner', (int) $id, ['message' => $banner['message']]);
            flash('success', 'Banner removed.');
        }
        redirect('admin/banners');
    }

    public function end(string $id): void
    {
        $banner = DB::fetch('SELECT * FROM banners WHERE id = ?', [(int) $id]);
        if ($banner !== null) {
            DB::update('banners', ['ends_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $id]);
            BannerService::invalidate();
            Audit::log('banner.ended', 'banner', (int) $id);
            flash('success', 'Banner ended.');
        }
        redirect('admin/banners');
    }

    public function report(string $id): void
    {
        $banner = DB::fetch('SELECT * FROM banners WHERE id = ?', [(int) $id]);
        if ($banner === null) {
            flash('error', 'Banner not found.');
            redirect('admin/banners');
        }
        $acked = DB::fetchAll(
            'SELECT u.id, u.name, u.email, a.created_at FROM banner_acknowledgements a
             JOIN users u ON u.id = a.user_id WHERE a.banner_id = ? ORDER BY a.created_at',
            [(int) $id]
        );
        $ackedIds = array_map('intval', array_column($acked, 'id'));
        $notAcked = DB::fetchAll(
            "SELECT id, name, email FROM users WHERE status = 'active'" .
            ($ackedIds !== [] ? ' AND id NOT IN (' . implode(',', $ackedIds) . ')' : '') .
            ' ORDER BY name'
        );
        View::render('admin/banners/report', [
            'title' => 'Acknowledgement report',
            'banner' => $banner,
            'acked' => $acked,
            'notAcked' => $notAcked,
        ], 'admin');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $v = new Validator($_POST, [
            'message' => 'required|max:500',
            'severity' => 'in:info,warning,critical',
            'link_url' => 'max:500',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            return null;
        }
        $linkUrl = trim((string) ($_POST['link_url'] ?? ''));
        if ($linkUrl !== '' && !preg_match('#^https?://#i', $linkUrl) && !str_starts_with($linkUrl, '/')) {
            flash('error', 'Link must be an absolute URL or start with /.');
            return null;
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $starts = strtotime((string) ($_POST['starts_at'] ?? ''));
        $ends = strtotime((string) ($_POST['ends_at'] ?? ''));
        return [
            'message' => trim((string) $_POST['message']),
            'severity' => (string) ($_POST['severity'] ?? 'warning'),
            'link_url' => $linkUrl ?: null,
            'link_label' => trim((string) ($_POST['link_label'] ?? '')) ?: null,
            'dismissible' => !empty($_POST['dismissible']) ? 1 : 0,
            'require_ack' => !empty($_POST['require_ack']) ? 1 : 0,
            'starts_at' => $starts !== false ? date('Y-m-d H:i:s', $starts) : null,
            'ends_at' => $ends !== false ? date('Y-m-d H:i:s', $ends) : null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
        ];
    }
}
