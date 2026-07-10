<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = Config::get('database');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $c['host'],
                $c['port'],
                $c['name']
            );
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Keep MySQL's session clock aligned with PHP's timezone so
            // CURRENT_TIMESTAMP defaults and PHP-written datetimes agree.
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /**
     * @return int last insert id
     */
    public static function insert(string $table, array $data): int
    {
        self::assertName($table);
        $columns = array_keys($data);
        foreach ($columns as $column) {
            self::assertName((string) $column);
        }
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) '
            . 'VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @return int affected rows
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        self::assertName($table);
        $sets = [];
        foreach (array_keys($data) as $column) {
            self::assertName((string) $column);
            $sets[] = '`' . $column . '` = ?';
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        return self::run($sql, array_merge(array_values($data), $whereParams))->rowCount();
    }

    /**
     * @return int affected rows
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        self::assertName($table);
        return self::run('DELETE FROM `' . $table . '` WHERE ' . $where, $params)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function assertName(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid SQL identifier: {$name}");
        }
    }
}
