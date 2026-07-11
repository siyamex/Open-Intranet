<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Poll;

final class PollController
{
    public function index(): void
    {
        View::render('admin/polls/index', [
            'title' => 'Polls',
            'polls' => DB::fetchAll(
                'SELECT p.*, u.name AS creator_name,
                        (SELECT COUNT(DISTINCT COALESCE(v.user_id, CRC32(v.voter_hash))) FROM poll_votes v WHERE v.poll_id = p.id) AS voters
                 FROM polls p LEFT JOIN users u ON u.id = p.created_by
                 ORDER BY p.created_at DESC LIMIT 100'
            ),
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
            'departments' => DB::fetchAll('SELECT id, name FROM departments ORDER BY name'),
        ], 'admin');
    }

    public function store(): void
    {
        $v = new Validator($_POST, ['question' => 'required|max:255', 'type' => 'in:single,multiple']);
        $options = array_values(array_filter(array_map('trim', (array) ($_POST['options'] ?? [])), 'strlen'));
        if ($v->fails() || count($options) < 2) {
            flash('error', $v->firstError() ?? 'A poll needs at least two options.');
            Flash::keepInput();
            redirect('admin/polls');
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $opensAt = strtotime((string) ($_POST['opens_at'] ?? ''));
        $closesAt = strtotime((string) ($_POST['closes_at'] ?? ''));
        $pollId = DB::insert('polls', [
            'question' => trim((string) $_POST['question']),
            'type' => (string) ($_POST['type'] ?? 'single'),
            'is_anonymous' => !empty($_POST['is_anonymous']) ? 1 : 0,
            'opens_at' => $opensAt !== false ? date('Y-m-d H:i:s', $opensAt) : null,
            'closes_at' => $closesAt !== false ? date('Y-m-d H:i:s', $closesAt) : null,
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($options as $i => $label) {
            DB::insert('poll_options', [
                'poll_id' => $pollId,
                'label' => mb_substr($label, 0, 255),
                'sort_order' => ($i + 1) * 10,
            ]);
        }
        Audit::log('poll.created', 'poll', $pollId, ['question' => $_POST['question']]);
        flash('success', 'Poll created.');
        redirect('admin/polls');
    }

    public function close(string $id): void
    {
        $poll = $this->find($id);
        DB::update('polls', ['closes_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $id]);
        Audit::log('poll.closed', 'poll', (int) $id, ['question' => $poll['question']]);
        flash('success', 'Poll closed.');
        redirect('admin/polls');
    }

    public function destroy(string $id): void
    {
        $poll = $this->find($id);
        DB::delete('polls', 'id = ?', [(int) $id]);
        Audit::log('poll.deleted', 'poll', (int) $id, ['question' => $poll['question']]);
        flash('success', 'Poll deleted.');
        redirect('admin/polls');
    }

    public function results(string $id): void
    {
        $poll = Poll::withDetails($this->find($id));
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="poll-' . (int) $poll['id'] . '-results.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['option', 'votes', 'percent']);
            foreach ($poll['options'] as $option) {
                $pct = $poll['total_votes'] > 0 ? round(100 * (int) $option['votes'] / $poll['total_votes'], 1) : 0;
                fputcsv($out, [$option['label'], $option['votes'], $pct]);
            }
            if ((int) $poll['is_anonymous'] !== 1) {
                fputcsv($out, []);
                fputcsv($out, ['voter', 'option', 'voted_at']);
                foreach (DB::fetchAll(
                    'SELECT u.name, o.label, v.created_at
                     FROM poll_votes v JOIN users u ON u.id = v.user_id JOIN poll_options o ON o.id = v.option_id
                     WHERE v.poll_id = ? ORDER BY v.created_at',
                    [(int) $poll['id']]
                ) as $row) {
                    fputcsv($out, [$row['name'], $row['label'], $row['created_at']]);
                }
            }
            fclose($out);
            exit;
        }
        View::render('admin/polls/results', [
            'title' => 'Results — ' . $poll['question'],
            'poll' => $poll,
        ], 'admin');
    }

    private function find(string $id): array
    {
        $poll = DB::fetch('SELECT * FROM polls WHERE id = ?', [(int) $id]);
        if ($poll === null) {
            flash('error', 'Poll not found.');
            redirect('admin/polls');
        }
        return $poll;
    }
}
