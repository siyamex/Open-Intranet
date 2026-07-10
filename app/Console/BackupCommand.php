<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Config;
use App\Core\DB;

final class BackupCommand
{
    public const DESCRIPTION = 'Timestamped SQL dump + uploads archive into storage/backups';

    public static function run(array $args): int
    {
        $dir = BASE_PATH . '/storage/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stamp = date('Ymd-His');

        // ---- database dump -------------------------------------------------
        $sqlFile = $dir . '/db-' . $stamp . '.sql';
        if (!self::tryMysqldump($sqlFile)) {
            echo "mysqldump not found — using PHP dump fallback.\n";
            self::phpDump($sqlFile);
        }
        echo 'Database dump: ' . basename($sqlFile) . ' (' . format_bytes((int) filesize($sqlFile)) . ")\n";

        // ---- uploads archive ------------------------------------------------
        $zipFile = $dir . '/uploads-' . $stamp . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $base = BASE_PATH . '/storage/uploads';
        $count = 0;
        if (is_dir($base)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), 'uploads/' . str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1)));
                    $count++;
                }
            }
        }
        $zip->close();
        echo 'Uploads archive: ' . basename($zipFile) . " ({$count} files)\n";
        echo "Restore steps are documented in docs/BACKUP.md\n";
        return 0;
    }

    private static function tryMysqldump(string $outFile): bool
    {
        $c = \App\Core\Config::get('database');
        $candidates = ['mysqldump'];
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        }
        foreach ($candidates as $bin) {
            $cmd = escapeshellarg($bin)
                . ' --host=' . escapeshellarg($c['host'])
                . ' --port=' . escapeshellarg($c['port'])
                . ' --user=' . escapeshellarg($c['user'])
                . ($c['pass'] !== '' ? ' --password=' . escapeshellarg($c['pass']) : '')
                . ' --single-transaction --routines ' . escapeshellarg($c['name'])
                . ' > ' . escapeshellarg($outFile) . ' 2>&1';
            exec($cmd, $output, $code);
            if ($code === 0 && is_file($outFile) && filesize($outFile) > 0) {
                return true;
            }
            @unlink($outFile);
        }
        return false;
    }

    /**
     * Plain-PHP dump: CREATE TABLE + INSERTs for every table.
     */
    private static function phpDump(string $outFile): void
    {
        $handle = fopen($outFile, 'w');
        fwrite($handle, "-- OpenIntranet PHP dump " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $tables = array_map('current', DB::fetchAll('SHOW TABLES'));
        foreach ($tables as $table) {
            $create = DB::fetch("SHOW CREATE TABLE `{$table}`");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n" . ($create['Create Table'] ?? '') . ";\n\n");
            $stmt = DB::run("SELECT * FROM `{$table}`");
            while (($row = $stmt->fetch()) !== false) {
                $values = array_map(static function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . addslashes((string) $value) . "'";
                }, array_values($row));
                $columns = '`' . implode('`, `', array_keys($row)) . '`';
                fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n");
            }
            fwrite($handle, "\n");
        }
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }
}
