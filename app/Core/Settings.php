<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Key/value settings stored in the settings table, cached per request.
 * Types: string, int, bool, json.
 */
final class Settings
{
    /** @var array<string, array{value: ?string, type: string}>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        if (!isset(self::$cache[$key])) {
            return $default;
        }
        $row = self::$cache[$key];
        if ($row['value'] === null) {
            return $default;
        }
        return match ($row['type']) {
            'int' => (int) $row['value'],
            'bool' => in_array(strtolower($row['value']), ['1', 'true', 'on', 'yes'], true),
            'json' => json_decode($row['value'], true),
            default => $row['value'],
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'json' => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'bool' => $value ? '1' : '0',
            default => $value === null ? null : (string) $value,
        };
        DB::run(
            'INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`)',
            [$key, $stored, $type]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = ['value' => $stored, 'type' => $type];
        }
    }

    /**
     * @return array<string, mixed> every setting, decoded
     */
    public static function all(): array
    {
        self::load();
        $out = [];
        foreach (array_keys(self::$cache ?? []) as $key) {
            $out[$key] = self::get($key);
        }
        return $out;
    }

    public static function forget(): void
    {
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        try {
            foreach (DB::fetchAll('SELECT `key`, `value`, `type` FROM settings') as $row) {
                self::$cache[$row['key']] = ['value' => $row['value'], 'type' => $row['type']];
            }
        } catch (\PDOException) {
            // settings table not migrated yet — behave as empty
        }
    }
}
