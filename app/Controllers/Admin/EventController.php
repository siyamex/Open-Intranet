<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;

final class EventController
{
    public function index(): void
    {
        View::render('admin/events/index', [
            'title' => 'Events',
            'events' => DB::fetchAll(
                'SELECT e.*, u.name AS creator_name,
                        (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id AND r.response = "going") AS going
                 FROM events e LEFT JOIN users u ON u.id = e.created_by
                 WHERE e.series_id IS NULL
                 ORDER BY e.starts_at DESC LIMIT 200'
            ),
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function store(): void
    {
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/events');
        }
        $data['created_by'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('events', $data);
        $this->materializeRecurrence($id, $data);
        Audit::log('event.created', 'event', $id, ['title' => $data['title']]);
        flash('success', 'Event created.');
        redirect('admin/events');
    }

    public function update(string $id): void
    {
        $event = $this->find($id);
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/events');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('events', $data, 'id = ?', [(int) $id]);
        // recurrence changes: rebuild the series
        DB::delete('events', 'series_id = ?', [(int) $id]);
        $this->materializeRecurrence((int) $id, $data);
        Audit::log('event.updated', 'event', (int) $id, ['title' => $data['title']]);
        flash('success', 'Event updated.');
        redirect('admin/events');
    }

    public function destroy(string $id): void
    {
        $event = $this->find($id);
        DB::delete('events', 'id = ?', [(int) $id]); // series cascades
        Audit::log('event.deleted', 'event', (int) $id, ['title' => $event['title']]);
        flash('success', 'Event deleted (including recurrences).');
        redirect('admin/events');
    }

    /**
     * Weekly/monthly recurrence, materialized 12 months ahead.
     */
    private function materializeRecurrence(int $parentId, array $data): void
    {
        if (($data['recurrence'] ?? 'none') === 'none') {
            return;
        }
        $interval = $data['recurrence'] === 'weekly' ? '+1 week' : '+1 month';
        $horizon = strtotime('+12 months');
        $start = strtotime((string) $data['starts_at']);
        $duration = strtotime((string) $data['ends_at']) - $start;
        $occurrence = strtotime($interval, $start);
        $rows = 0;
        while ($occurrence !== false && $occurrence <= $horizon && $rows < 60) {
            DB::insert('events', [
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'starts_at' => date('Y-m-d H:i:s', $occurrence),
                'ends_at' => date('Y-m-d H:i:s', $occurrence + $duration),
                'all_day' => $data['all_day'],
                'color' => $data['color'],
                'created_by' => Auth::id(),
                'visible_to' => $data['visible_to'],
                'rsvp_enabled' => $data['rsvp_enabled'],
                'recurrence' => 'none',
                'series_id' => $parentId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $occurrence = strtotime($interval, $occurrence);
            $rows++;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $v = new Validator($_POST, [
            'title' => 'required|max:255',
            'location' => 'max:255',
            'starts_at' => 'required',
            'ends_at' => 'required',
            'color' => ['regex:/^$|^#[0-9a-fA-F]{3,8}$/'], // array form: the regex contains '|'
            'recurrence' => 'in:none,weekly,monthly',
        ]);
        $start = strtotime((string) ($_POST['starts_at'] ?? ''));
        $end = strtotime((string) ($_POST['ends_at'] ?? ''));
        if ($v->fails() || $start === false || $end === false || $end < $start) {
            flash('error', $v->firstError() ?? 'End must be after start.');
            Flash::keepInput();
            return null;
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        return [
            'title' => trim((string) $_POST['title']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'location' => trim((string) ($_POST['location'] ?? '')) ?: null,
            'starts_at' => date('Y-m-d H:i:s', $start),
            'ends_at' => date('Y-m-d H:i:s', $end),
            'all_day' => !empty($_POST['all_day']) ? 1 : 0,
            'color' => trim((string) ($_POST['color'] ?? '')) ?: null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'rsvp_enabled' => !empty($_POST['rsvp_enabled']) ? 1 : 0,
            'recurrence' => (string) ($_POST['recurrence'] ?? 'none'),
        ];
    }

    private function find(string $id): array
    {
        $event = DB::fetch('SELECT * FROM events WHERE id = ? AND series_id IS NULL', [(int) $id]);
        if ($event === null) {
            flash('error', 'Event not found.');
            redirect('admin/events');
        }
        return $event;
    }
}
