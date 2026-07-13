<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;

final class FormsPruneCommand
{
    public const DESCRIPTION = 'Apply per-form retention: delete old decided submissions (cron)';

    public static function run(array $args): int
    {
        $forms = DB::fetchAll('SELECT id, title, retention_days FROM forms WHERE retention_days IS NOT NULL');
        $total = 0;
        foreach ($forms as $form) {
            $cutoff = date('Y-m-d H:i:s', time() - ((int) $form['retention_days']) * 86400);
            // remove attached files first
            $old = DB::fetchAll(
                "SELECT data FROM form_submissions
                 WHERE form_id = ? AND status IN ('approved', 'rejected') AND updated_at < ?",
                [(int) $form['id'], $cutoff]
            );
            foreach ($old as $row) {
                foreach ((array) json_decode((string) $row['data'], true) as $value) {
                    if (is_array($value) && isset($value['file'])) {
                        @unlink(BASE_PATH . '/storage/uploads/forms/' . basename((string) $value['file']));
                    }
                }
            }
            $deleted = DB::delete(
                'form_submissions',
                "form_id = ? AND status IN ('approved', 'rejected') AND updated_at < ?",
                [(int) $form['id'], $cutoff]
            );
            if ($deleted > 0) {
                echo "{$form['title']}: pruned {$deleted} submission(s) older than {$form['retention_days']} days.\n";
            }
            $total += $deleted;
        }
        echo "Total pruned: {$total}\n";
        return 0;
    }
}
