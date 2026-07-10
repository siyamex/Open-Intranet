<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\View;

final class PeopleController
{
    public function show(string $id): void
    {
        $person = DB::fetch(
            "SELECT u.*, d.name AS department_name, m.id AS mgr_id, m.name AS mgr_name, m.job_title AS mgr_title
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN users m ON m.id = u.manager_id
             WHERE u.id = ? AND u.status = 'active'",
            [(int) $id]
        );
        if ($person === null) {
            http_response_code(404);
            View::render('errors/404', [], null);
            return;
        }
        $reports = DB::fetchAll(
            "SELECT id, name, job_title, avatar_path FROM users WHERE manager_id = ? AND status = 'active' ORDER BY name",
            [(int) $id]
        );
        $recentNews = DB::fetchAll(
            "SELECT id, title, slug, published_at FROM news
             WHERE author_id = ? AND status = 'published' AND published_at <= NOW()
             ORDER BY published_at DESC LIMIT 5",
            [(int) $id]
        );
        $localTime = null;
        if (!empty($person['timezone'])) {
            try {
                $localTime = (new \DateTime('now', new \DateTimeZone((string) $person['timezone'])))->format('H:i');
            } catch (\Throwable) {
            }
        }
        View::render('people/show', [
            'title' => (string) $person['name'],
            'person' => $person,
            'reports' => $reports,
            'recentNews' => $recentNews,
            'skills' => array_column(DB::fetchAll(
                'SELECT skill FROM user_skills WHERE user_id = ? ORDER BY skill',
                [(int) $id]
            ), 'skill'),
            'localTime' => $localTime,
        ]);
    }
}
