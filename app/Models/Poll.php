<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Visibility;

final class Poll
{
    /**
     * The newest open poll the current user may see, with options,
     * result counts, and whether they voted.
     *
     * @return array|null
     */
    public static function activeForUser(): ?array
    {
        $polls = DB::fetchAll(
            'SELECT * FROM polls
             WHERE (opens_at IS NULL OR opens_at <= NOW())
               AND (closes_at IS NULL OR closes_at > NOW())
             ORDER BY created_at DESC LIMIT 10'
        );
        foreach ($polls as $poll) {
            if (!Visibility::allowed($poll['visible_to'])) {
                continue;
            }
            if ($poll['department_id'] !== null
                && (int) (Auth::user()['department_id'] ?? 0) !== (int) $poll['department_id']
                && !Auth::hasRole('super_admin')) {
                continue;
            }
            return self::withDetails($poll);
        }
        return null;
    }

    public static function withDetails(array $poll): array
    {
        $pollId = (int) $poll['id'];
        $poll['options'] = DB::fetchAll(
            'SELECT o.id, o.label,
                    (SELECT COUNT(*) FROM poll_votes v WHERE v.option_id = o.id) AS votes
             FROM poll_options o WHERE o.poll_id = ? ORDER BY o.sort_order, o.id',
            [$pollId]
        );
        $poll['total_votes'] = array_sum(array_map('intval', array_column($poll['options'], 'votes')));
        $poll['voters'] = (int) DB::scalar(
            'SELECT COUNT(DISTINCT COALESCE(user_id, CRC32(voter_hash))) FROM poll_votes WHERE poll_id = ?',
            [$pollId]
        );
        $poll['has_voted'] = self::hasVoted($pollId, (int) Auth::id(), (int) $poll['is_anonymous'] === 1);
        $poll['is_closed'] = $poll['closes_at'] !== null && strtotime((string) $poll['closes_at']) <= time();
        return $poll;
    }

    public static function hasVoted(int $pollId, int $userId, bool $anonymous): bool
    {
        if ($anonymous) {
            return DB::fetch(
                'SELECT 1 FROM poll_votes WHERE poll_id = ? AND voter_hash = ? LIMIT 1',
                [$pollId, self::voterHash($pollId, $userId)]
            ) !== null;
        }
        return DB::fetch(
            'SELECT 1 FROM poll_votes WHERE poll_id = ? AND user_id = ? LIMIT 1',
            [$pollId, $userId]
        ) !== null;
    }

    /**
     * Deterministic per-poll hash so anonymous votes dedupe without
     * storing the user id.
     */
    public static function voterHash(int $pollId, int $userId): string
    {
        return hash_hmac('sha256', 'poll:' . $pollId . ':voter:' . $userId, (string) Config::env('APP_KEY', ''));
    }
}
