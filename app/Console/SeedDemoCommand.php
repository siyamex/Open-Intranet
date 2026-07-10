<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;

/**
 * Seeds ~30 fake employees with a realistic org structure plus extra news,
 * documents and quick links — for screenshots and trying the org chart.
 */
final class SeedDemoCommand
{
    public const DESCRIPTION = 'Seed ~30 demo users w/ org structure, news, documents, links';

    public static function run(array $args): int
    {
        $now = date('Y-m-d H:i:s');

        $departments = [];
        foreach (['Executive', 'Human Resources', 'Finance', 'Information Technology', 'Operations', 'Marketing'] as $name) {
            $existing = DB::fetch('SELECT id FROM departments WHERE name = ?', [$name]);
            $departments[$name] = $existing !== null
                ? (int) $existing['id']
                : DB::insert('departments', ['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }

        // [name, title, department, manager name|null]
        $people = [
            ['Aminath Waheeda', 'Chief Executive Officer', 'Executive', null],
            ['Hassan Zareer', 'Chief Operating Officer', 'Executive', 'Aminath Waheeda'],
            ['Mariyam Shifa', 'Chief Financial Officer', 'Executive', 'Aminath Waheeda'],
            ['Ibrahim Nashid', 'Chief Technology Officer', 'Executive', 'Aminath Waheeda'],
            ['Fathimath Reesha', 'HR Director', 'Human Resources', 'Hassan Zareer'],
            ['Ahmed Maajid', 'HR Officer', 'Human Resources', 'Fathimath Reesha'],
            ['Aishath Loona', 'Recruiter', 'Human Resources', 'Fathimath Reesha'],
            ['Mohamed Thoriq', 'Payroll Specialist', 'Human Resources', 'Fathimath Reesha'],
            ['Ali Rasheed', 'Finance Manager', 'Finance', 'Mariyam Shifa'],
            ['Hawwa Zeyna', 'Senior Accountant', 'Finance', 'Ali Rasheed'],
            ['Ismail Naseem', 'Accountant', 'Finance', 'Ali Rasheed'],
            ['Khadeeja Rifqa', 'Financial Analyst', 'Finance', 'Ali Rasheed'],
            ['Yoosuf Siraj', 'IT Manager', 'Information Technology', 'Ibrahim Nashid'],
            ['Zulaikha Manike', 'Senior Developer', 'Information Technology', 'Yoosuf Siraj'],
            ['Hussain Fayaz', 'Developer', 'Information Technology', 'Zulaikha Manike'],
            ['Aminath Eela', 'Developer', 'Information Technology', 'Zulaikha Manike'],
            ['Abdulla Shan', 'QA Engineer', 'Information Technology', 'Yoosuf Siraj'],
            ['Mariyam Nadhwa', 'SysAdmin', 'Information Technology', 'Yoosuf Siraj'],
            ['Adam Naail', 'Helpdesk Technician', 'Information Technology', 'Mariyam Nadhwa'],
            ['Sanfa Rasheedha', 'Operations Manager', 'Operations', 'Hassan Zareer'],
            ['Moosa Waseem', 'Logistics Coordinator', 'Operations', 'Sanfa Rasheedha'],
            ['Aishath Shaila', 'Facilities Officer', 'Operations', 'Sanfa Rasheedha'],
            ['Ibrahim Muaz', 'Procurement Officer', 'Operations', 'Sanfa Rasheedha'],
            ['Fathimath Inaya', 'Marketing Manager', 'Marketing', 'Hassan Zareer'],
            ['Ahmed Yaish', 'Content Writer', 'Marketing', 'Fathimath Inaya'],
            ['Mariyam Aroosha', 'Graphic Designer', 'Marketing', 'Fathimath Inaya'],
            ['Ali Nihan', 'Social Media Officer', 'Marketing', 'Fathimath Inaya'],
            ['Hassan Iyad', 'Business Analyst', 'Operations', 'Sanfa Rasheedha'],
            ['Aminath Raufa', 'Legal Counsel', 'Executive', 'Aminath Waheeda'],
            ['Mohamed Azhaan', 'Internal Auditor', 'Finance', 'Mariyam Shifa'],
        ];
        $skillPool = ['Excel', 'Project Management', 'PHP', 'MySQL', 'Photoshop', 'Dhivehi', 'English', 'First Aid', 'Public Speaking', 'Accounting', 'Networking', 'Copywriting'];
        $locations = ['HQ — Malé', 'Hulhumalé Office', 'Remote'];
        $employeeRole = (int) (DB::scalar("SELECT id FROM roles WHERE slug = 'employee'") ?? 0);
        $editorRole = (int) (DB::scalar("SELECT id FROM roles WHERE slug = 'editor'") ?? 0);

        $created = 0;
        $idByName = [];
        foreach ($people as $i => [$name, $title, $dept, $managerName]) {
            $email = strtolower(str_replace(' ', '.', self::ascii($name))) . '@demo.example';
            $existing = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing !== null) {
                $idByName[$name] = (int) $existing['id'];
                continue;
            }
            $userId = DB::insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => null, // demo users cannot log in
                'job_title' => $title,
                'phone' => '+960 7' . str_pad((string) (100000 + $i * 137), 6, '0'),
                'department_id' => $departments[$dept],
                'location' => $locations[$i % 3],
                'timezone' => 'Indian/Maldives',
                'status' => 'active',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $idByName[$name] = $userId;
            $role = str_contains($title, 'Writer') || str_contains($title, 'Manager') ? $editorRole : $employeeRole;
            if ($role > 0) {
                DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, $role]);
            }
            foreach (array_slice($skillPool, $i % 8, 3) as $skill) {
                DB::run('INSERT IGNORE INTO user_skills (user_id, skill) VALUES (?, ?)', [$userId, $skill]);
            }
            $created++;
        }
        // second pass: managers
        foreach ($people as [$name, , , $managerName]) {
            if ($managerName !== null && isset($idByName[$name], $idByName[$managerName])) {
                DB::update('users', ['manager_id' => $idByName[$managerName]], 'id = ?', [$idByName[$name]]);
            }
        }
        // department heads
        foreach ([['Human Resources', 'Fathimath Reesha'], ['Finance', 'Ali Rasheed'], ['Information Technology', 'Yoosuf Siraj'], ['Operations', 'Sanfa Rasheedha'], ['Marketing', 'Fathimath Inaya']] as [$dept, $head]) {
            if (isset($idByName[$head])) {
                DB::update('departments', ['head_user_id' => $idByName[$head]], 'id = ?', [$departments[$dept]]);
            }
        }
        echo "Demo users: {$created} created (" . count($people) . " total in roster).\n";

        // ---- extra news ------------------------------------------------------
        $catId = (int) (DB::scalar("SELECT id FROM news_categories WHERE slug = 'announcements'") ?? 0) ?: null;
        $authorId = $idByName['Fathimath Inaya'] ?? (int) DB::scalar('SELECT id FROM users ORDER BY id LIMIT 1');
        $demoNews = [
            ['New health insurance plan for all staff', 'The upgraded plan covers dependents and dental from next month.'],
            ['Office renovation — 3rd floor', 'The 3rd floor will be closed next week; teams relocate to the annex.'],
            ['Annual staff retreat announced', 'Three days in Baa Atoll — registration closes Friday!'],
            ['IT maintenance window Saturday night', 'Core systems will be unavailable between 23:00 and 02:00.'],
            ['Welcome our new colleagues', 'Five new team members joined this month across three departments.'],
        ];
        $added = 0;
        foreach ($demoNews as $i => [$title, $excerpt]) {
            $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
            if (DB::fetch('SELECT id FROM news WHERE slug = ?', [$slug]) === null) {
                DB::insert('news', [
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt,
                    'body' => '<p>' . e($excerpt) . '</p><p>Contact the relevant department for details.</p>',
                    'category_id' => $catId,
                    'author_id' => $authorId,
                    'status' => 'published',
                    'allow_comments' => 1,
                    'published_at' => date('Y-m-d H:i:s', time() - ($i + 1) * 43200),
                    'views' => random_int(8, 160),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $added++;
            }
        }
        echo "Demo news: {$added} added.\n";

        // ---- demo gazette documents ------------------------------------------
        $docsDir = BASE_PATH . '/storage/uploads/docs';
        if (!is_dir($docsDir)) {
            mkdir($docsDir, 0775, true);
        }
        $demoDocs = ['Staff Handbook 2026', 'Leave Application Form', 'Travel Policy'];
        $added = 0;
        foreach ($demoDocs as $title) {
            if (DB::fetch('SELECT id FROM documents WHERE title = ?', [$title]) !== null) {
                continue;
            }
            $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
                . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\ntrailer<</Size 4/Root 1 0 R>>\n%%EOF";
            $storedName = bin2hex(random_bytes(20)) . '.bin';
            file_put_contents($docsDir . '/' . $storedName, $pdf, LOCK_EX);
            $bytes = random_bytes(16);
            $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
            $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
            DB::insert('documents', [
                'uuid' => vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)),
                'title' => $title,
                'description' => 'Demo document seeded by seed:demo.',
                'file_path' => 'docs/' . $storedName,
                'original_name' => strtolower(str_replace(' ', '-', $title)) . '.pdf',
                'mime' => 'application/pdf',
                'size_bytes' => strlen($pdf),
                'version' => 1,
                'uploaded_by' => $idByName['Fathimath Reesha'] ?? null,
                'is_gazette' => 1,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $added++;
        }
        echo "Demo documents: {$added} added.\n";
        echo "Done — open the org chart to see the demo structure.\n";
        return 0;
    }

    private static function ascii(string $value): string
    {
        return (string) preg_replace('/[^a-z ]/i', '', $value);
    }
}
