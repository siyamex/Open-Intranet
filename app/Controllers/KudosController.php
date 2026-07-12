<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Notify;
use App\Core\Settings;
use App\Core\View;

final class KudosController
{
    public function index(): void
    {
        View::render('pages/kudos', [
            'title' => 'Kudos',
            'feed' => self::feed(30),
            'values' => DB::fetchAll('SELECT * FROM kudos_values WHERE is_active = 1 ORDER BY label'),
            'people' => DB::fetchAll(
                "SELECT id, name FROM users WHERE status = 'active' AND id != ? ORDER BY name",
                [Auth::id()]
            ),
            'leaderboard' => self::leaderboard(),
        ]);
    }

    public function store(): void
    {
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        $valueId = (int) ($_POST['value_id'] ?? 0);
        $prefill = isset($_GET['to']);

        if ($recipientId === Auth::id()) {
            flash('error', 'Nice try — you cannot send kudos to yourself. 😄');
            redirect('kudos');
        }
        $recipient = DB::fetch("SELECT id, name FROM users WHERE id = ? AND status = 'active'", [$recipientId]);
        if ($recipient === null || $message === '' || mb_strlen($message) > 300) {
            flash('error', 'Pick a colleague and write a message up to 300 characters.');
            redirect('kudos');
        }
        foreach (self::bannedWords() as $word) {
            if ($word !== '' && mb_stripos($message, $word) !== false) {
                flash('error', 'Your message contains a word that is not allowed.');
                redirect('kudos');
            }
        }
        $id = DB::insert('kudos', [
            'sender_id' => Auth::id(),
            'recipient_id' => $recipientId,
            'value_id' => $valueId > 0 && DB::fetch('SELECT id FROM kudos_values WHERE id = ? AND is_active = 1', [$valueId]) !== null
                ? $valueId : null,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Notify::send(
            $recipientId,
            'kudos',
            (string) (Auth::user()['name'] ?? 'Someone') . ' sent you kudos 🎉',
            mb_substr($message, 0, 120),
            base_url('kudos')
        );
        flash('success', 'Kudos sent to ' . $recipient['name'] . ' 🎉');
        redirect('kudos');
    }

    public function react(string $id): void
    {
        $allowed = ['👏', '❤️', '🎉', '💯'];
        $emoji = (string) ($_POST['emoji'] ?? '');
        $kudos = DB::fetch('SELECT id FROM kudos WHERE id = ? AND is_hidden = 0', [(int) $id]);
        header('Content-Type: application/json');
        if ($kudos === null || !in_array($emoji, $allowed, true)) {
            echo json_encode(['ok' => false]);
            exit;
        }
        $existing = DB::fetch(
            'SELECT 1 FROM kudos_reactions WHERE kudos_id = ? AND user_id = ? AND emoji = ?',
            [(int) $id, Auth::id(), $emoji]
        );
        if ($existing !== null) {
            DB::delete('kudos_reactions', 'kudos_id = ? AND user_id = ? AND emoji = ?', [(int) $id, Auth::id(), $emoji]);
        } else {
            DB::run('INSERT IGNORE INTO kudos_reactions (kudos_id, user_id, emoji) VALUES (?, ?, ?)', [(int) $id, Auth::id(), $emoji]);
        }
        $count = (int) DB::scalar('SELECT COUNT(*) FROM kudos_reactions WHERE kudos_id = ? AND emoji = ?', [(int) $id, $emoji]);
        echo json_encode(['ok' => true, 'count' => $count, 'mine' => $existing === null]);
        exit;
    }

    /**
     * @return array<int, array>
     */
    public static function feed(int $limit): array
    {
        $rows = DB::fetchAll(
            'SELECT k.*, v.label AS value_label, v.emoji AS value_emoji,
                    s.id AS sender_uid, s.name AS sender_name, s.avatar_path AS sender_avatar,
                    r.id AS recipient_uid, r.name AS recipient_name, r.avatar_path AS recipient_avatar
             FROM kudos k
             LEFT JOIN kudos_values v ON v.id = k.value_id
             LEFT JOIN users s ON s.id = k.sender_id
             JOIN users r ON r.id = k.recipient_id
             WHERE k.is_hidden = 0
             ORDER BY k.created_at DESC
             LIMIT ' . $limit
        );
        foreach ($rows as &$row) {
            $row['reactions'] = DB::fetchAll(
                'SELECT emoji, COUNT(*) AS n, MAX(user_id = ?) AS mine FROM kudos_reactions WHERE kudos_id = ? GROUP BY emoji',
                [\App\Core\Auth::id(), (int) $row['id']]
            );
        }
        unset($row);
        return $rows;
    }

    /**
     * Top recipients this month.
     */
    public static function leaderboard(): array
    {
        return DB::fetchAll(
            "SELECT u.id, u.name, u.avatar_path, COUNT(*) AS received
             FROM kudos k JOIN users u ON u.id = k.recipient_id
             WHERE k.is_hidden = 0 AND k.created_at >= ?
             GROUP BY u.id, u.name, u.avatar_path
             ORDER BY received DESC, u.name
             LIMIT 5",
            [date('Y-m-01 00:00:00')]
        );
    }

    /**
     * @return string[]
     */
    private static function bannedWords(): array
    {
        $raw = (string) Settings::get('kudos_banned_words', '');
        return array_filter(array_map(static fn (string $w): string => mb_strtolower(trim($w)), explode(',', $raw)));
    }
}
