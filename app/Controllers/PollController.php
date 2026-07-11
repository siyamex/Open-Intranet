<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Visibility;
use App\Models\Poll;

final class PollController
{
    public function vote(string $id): void
    {
        $poll = DB::fetch('SELECT * FROM polls WHERE id = ?', [(int) $id]);
        $back = (string) ($_POST['back'] ?? '/');
        if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }
        if ($poll === null || !Visibility::allowed($poll['visible_to'])) {
            flash('error', 'Poll not found.');
            redirect($back);
        }
        $open = ($poll['opens_at'] === null || strtotime((string) $poll['opens_at']) <= time())
            && ($poll['closes_at'] === null || strtotime((string) $poll['closes_at']) > time());
        if (!$open) {
            flash('error', 'This poll is closed.');
            redirect($back);
        }
        $anonymous = (int) $poll['is_anonymous'] === 1;
        if (Poll::hasVoted((int) $poll['id'], (int) Auth::id(), $anonymous)) {
            flash('warning', 'You already voted in this poll.');
            redirect($back);
        }

        $optionIds = array_map('intval', (array) ($_POST['options'] ?? []));
        if ($poll['type'] === 'single') {
            $optionIds = array_slice($optionIds, 0, 1);
        }
        $validIds = array_map('intval', array_column(DB::fetchAll(
            'SELECT id FROM poll_options WHERE poll_id = ?',
            [(int) $poll['id']]
        ), 'id'));
        $optionIds = array_values(array_intersect(array_unique($optionIds), $validIds));
        if ($optionIds === []) {
            flash('error', 'Pick at least one option.');
            redirect($back);
        }
        foreach ($optionIds as $optionId) {
            DB::run(
                'INSERT IGNORE INTO poll_votes (poll_id, option_id, user_id, voter_hash) VALUES (?, ?, ?, ?)',
                [
                    (int) $poll['id'],
                    $optionId,
                    $anonymous ? null : Auth::id(),
                    $anonymous ? Poll::voterHash((int) $poll['id'], (int) Auth::id()) : null,
                ]
            );
        }
        flash('success', 'Vote recorded' . ($anonymous ? ' (anonymously).' : '.'));
        redirect($back);
    }
}
