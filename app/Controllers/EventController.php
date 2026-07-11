<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Visibility;
use App\Core\View;

final class EventController
{
    public function index(): void
    {
        View::render('pages/events/index', ['title' => 'Events']);
    }

    /**
     * JSON events between ?from and ?to (Y-m-d), visibility-filtered.
     */
    public function api(): void
    {
        $from = self::dateOr((string) ($_GET['from'] ?? ''), date('Y-m-01'));
        $to = self::dateOr((string) ($_GET['to'] ?? ''), date('Y-m-t'));
        $rows = DB::fetchAll(
            'SELECT id, title, location, starts_at, ends_at, all_day, color, visible_to, rsvp_enabled
             FROM events WHERE starts_at <= ? AND ends_at >= ? ORDER BY starts_at',
            [$to . ' 23:59:59', $from . ' 00:00:00']
        );
        $items = [];
        foreach ($rows as $row) {
            if (!Visibility::allowed($row['visible_to'])) {
                continue;
            }
            $items[] = [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'location' => $row['location'],
                'starts_at' => $row['starts_at'],
                'ends_at' => $row['ends_at'],
                'all_day' => (int) $row['all_day'] === 1,
                'color' => $row['color'] ?: '#4f46e5',
                'url' => url('events.show', ['id' => $row['id']]),
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
        exit;
    }

    public function show(string $id): void
    {
        $event = $this->findVisible($id);
        $rsvps = DB::fetchAll(
            'SELECT r.response, u.id, u.name, u.avatar_path
             FROM event_rsvps r JOIN users u ON u.id = r.user_id
             WHERE r.event_id = ? ORDER BY r.response, u.name',
            [(int) $event['id']]
        );
        $grouped = ['going' => [], 'maybe' => [], 'no' => []];
        $mine = null;
        foreach ($rsvps as $rsvp) {
            $grouped[$rsvp['response']][] = $rsvp;
            if ((int) $rsvp['id'] === Auth::id()) {
                $mine = $rsvp['response'];
            }
        }
        View::render('pages/events/show', [
            'title' => (string) $event['title'],
            'event' => $event,
            'rsvps' => $grouped,
            'mine' => $mine,
            'breadcrumbs' => [['Events', url('events.index')], [(string) $event['title'], null]],
        ]);
    }

    public function rsvp(string $id): void
    {
        $event = $this->findVisible($id);
        $response = (string) ($_POST['response'] ?? '');
        if ((int) $event['rsvp_enabled'] !== 1 || !in_array($response, ['going', 'maybe', 'no'], true)) {
            flash('error', 'RSVP is not available for this event.');
            redirect('events/' . $id);
        }
        DB::run(
            'INSERT INTO event_rsvps (event_id, user_id, response) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE response = VALUES(response)',
            [(int) $event['id'], Auth::id(), $response]
        );
        flash('success', 'RSVP saved — ' . $response . '.');
        redirect('events/' . $id);
    }

    public function ics(string $id): void
    {
        $event = $this->findVisible($id);
        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: attachment; filename="event-' . (int) $event['id'] . '.ics"');
        echo self::icsDocument([$event]);
        exit;
    }

    /**
     * Personal calendar feed — signed token, no session (for calendar apps).
     */
    public function feed(string $userId, string $token): void
    {
        $expected = self::feedToken((int) $userId);
        if (!hash_equals($expected, $token)
            || DB::fetch("SELECT id FROM users WHERE id = ? AND status = 'active'", [(int) $userId]) === null) {
            http_response_code(404);
            exit('Not found');
        }
        $events = DB::fetchAll(
            'SELECT * FROM events WHERE ends_at >= ? ORDER BY starts_at LIMIT 500',
            [date('Y-m-d H:i:s', time() - 30 * 86400)]
        );
        // Feed uses the owner's roles for visibility
        $roles = array_column(DB::fetchAll(
            'SELECT r.slug FROM roles r JOIN user_role ur ON ur.role_id = r.id WHERE ur.user_id = ?',
            [(int) $userId]
        ), 'slug');
        $isSuper = in_array('super_admin', $roles, true);
        $events = array_values(array_filter($events, static function (array $e) use ($roles, $isSuper): bool {
            if ($e['visible_to'] === null || $isSuper) {
                return true;
            }
            $allowed = json_decode((string) $e['visible_to'], true);
            return !is_array($allowed) || $allowed === [] || array_intersect($allowed, $roles) !== [];
        }));
        header('Content-Type: text/calendar; charset=UTF-8');
        echo self::icsDocument($events);
        exit;
    }

    public static function feedToken(int $userId): string
    {
        return hash_hmac('sha256', 'calendar-feed:' . $userId, (string) Config::env('APP_KEY', ''));
    }

    private static function icsDocument(array $events): string
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OpenIntranet//Calendar//EN'];
        foreach ($events as $event) {
            $escape = static fn (string $v): string => str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $v);
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:event-' . (int) $event['id'] . '@openintranet';
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            if ((int) $event['all_day'] === 1) {
                $lines[] = 'DTSTART;VALUE=DATE:' . date('Ymd', strtotime((string) $event['starts_at']));
                $lines[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime((string) $event['ends_at']) + 86400);
            } else {
                $lines[] = 'DTSTART:' . date('Ymd\THis', strtotime((string) $event['starts_at']));
                $lines[] = 'DTEND:' . date('Ymd\THis', strtotime((string) $event['ends_at']));
            }
            $lines[] = 'SUMMARY:' . $escape((string) $event['title']);
            if (!empty($event['location'])) {
                $lines[] = 'LOCATION:' . $escape((string) $event['location']);
            }
            if (!empty($event['description'])) {
                $lines[] = 'DESCRIPTION:' . $escape((string) $event['description']);
            }
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }

    private function findVisible(string $id): array
    {
        $event = DB::fetch('SELECT * FROM events WHERE id = ?', [(int) $id]);
        if ($event === null || !Visibility::allowed($event['visible_to'])) {
            http_response_code(404);
            View::render('errors/404', [], null);
            exit;
        }
        return $event;
    }

    private static function dateOr(string $value, string $default): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
    }
}
