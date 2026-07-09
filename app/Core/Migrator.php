<?php

declare(strict_types=1);

namespace App\Core;

final class Migrator
{
    /**
     * Run all pending migrations, in filename order.
     *
     * @return string[] the migrations that were applied
     */
    public static function migrate(): array
    {
        self::ensureTable();
        $ran = array_column(DB::fetchAll('SELECT migration FROM migrations'), 'migration');
        $batch = (int) DB::scalar('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations');
        $applied = [];
        foreach (self::files() as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }
            $sql = (string) file_get_contents($file);
            foreach (self::splitStatements($sql) as $statement) {
                DB::pdo()->exec($statement);
            }
            DB::insert('migrations', [
                'migration' => $name,
                'batch' => $batch,
                'ran_at' => date('Y-m-d H:i:s'),
            ]);
            $applied[] = $name;
        }
        return $applied;
    }

    /**
     * @return array<int, array{migration: string, status: string}>
     */
    public static function status(): array
    {
        self::ensureTable();
        $ran = array_column(DB::fetchAll('SELECT migration FROM migrations'), 'migration');
        $rows = [];
        foreach (self::files() as $file) {
            $name = basename($file);
            $rows[] = ['migration' => $name, 'status' => in_array($name, $ran, true) ? 'ran' : 'pending'];
        }
        return $rows;
    }

    /**
     * @return string[]
     */
    private static function files(): array
    {
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private static function ensureTable(): void
    {
        DB::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                ran_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * Split an SQL file into statements on semicolons, respecting quoted
     * strings, backticked identifiers and -- comments.
     *
     * @return string[]
     */
    public static function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = null;
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if ($inString !== null) {
                $current .= $char;
                if ($char === '\\' && $inString !== '`') {
                    $i++;
                    if ($i < $length) {
                        $current .= $sql[$i];
                    }
                    continue;
                }
                if ($char === $inString) {
                    $inString = null;
                }
                continue;
            }
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = $char;
                $current .= $char;
                continue;
            }
            if ($char === '-' && ($sql[$i + 1] ?? '') === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $current .= "\n";
                continue;
            }
            if ($char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $current .= "\n";
                continue;
            }
            if ($char === ';') {
                if (trim($current) !== '') {
                    $statements[] = trim($current);
                }
                $current = '';
                continue;
            }
            $current .= $char;
        }
        if (trim($current) !== '') {
            $statements[] = trim($current);
        }
        return $statements;
    }
}
