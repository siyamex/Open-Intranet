<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

final class Celebrations
{
    /**
     * Birthdays and hire anniversaries within the next $days days.
     * Only day+month of birth dates is ever exposed.
     *
     * @return array{birthdays: array, anniversaries: array}
     */
    public static function upcoming(int $days = 7): array
    {
        $users = DB::fetchAll(
            "SELECT id, name, avatar_path, birth_date, hire_date FROM users
             WHERE status = 'active' AND celebrations_opt_out = 0
               AND (birth_date IS NOT NULL OR hire_date IS NOT NULL)"
        );
        $birthdays = [];
        $anniversaries = [];
        $today = new \DateTimeImmutable('today');

        foreach ($users as $user) {
            foreach ([['birth_date', &$birthdays], ['hire_date', &$anniversaries]] as [$field, &$bucket]) {
                if ($user[$field] === null) {
                    continue;
                }
                $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $user[$field]);
                if ($date === false) {
                    continue;
                }
                $next = $date->setDate((int) $today->format('Y'), (int) $date->format('n'), (int) $date->format('j'));
                if ($next < $today) {
                    $next = $next->modify('+1 year');
                }
                $inDays = (int) $today->diff($next)->days;
                if ($inDays > $days) {
                    continue;
                }
                $entry = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'avatar_path' => $user['avatar_path'],
                    'when' => $next->format('Y-m-d'),
                    'in_days' => $inDays,
                    'day_month' => $next->format('j M'), // never the birth year
                ];
                if ($field === 'hire_date') {
                    $years = (int) $next->format('Y') - (int) $date->format('Y');
                    if ($years < 1) {
                        continue; // joined this year — not an anniversary yet
                    }
                    $entry['years'] = $years;
                }
                $bucket[] = $entry;
            }
            unset($bucket);
        }
        $sort = static fn (array $a, array $b): int => $a['in_days'] <=> $b['in_days'];
        usort($birthdays, $sort);
        usort($anniversaries, $sort);
        return ['birthdays' => $birthdays, 'anniversaries' => $anniversaries];
    }
}
