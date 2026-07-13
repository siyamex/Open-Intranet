<?php

declare(strict_types=1);

namespace App\Core;

/**
 * lang/{code}.php key files with fallback to English. Usage: __('key', [:params]).
 */
final class Lang
{
    private static ?string $current = null;
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    public const DEFAULT_LOCALE = 'en';

    public static function setLocale(string $code): void
    {
        self::$current = preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $code) ? $code : self::DEFAULT_LOCALE;
    }

    public static function locale(): string
    {
        if (self::$current !== null) {
            return self::$current;
        }
        // 1. logged-in user's preference  2. settings default  3. 'en'
        $pref = null;
        if (Auth::check()) {
            $pref = DB::scalar(
                "SELECT `value` FROM user_prefs WHERE user_id = ? AND `key` = 'locale'",
                [Auth::id()]
            );
        }
        $locale = is_string($pref) && $pref !== '' ? $pref : (string) Settings::get('default_locale', self::DEFAULT_LOCALE);
        self::setLocale($locale);
        return self::$current;
    }

    public static function isRtl(): bool
    {
        static $rtl = ['ar', 'dv', 'he', 'fa', 'ur'];
        return in_array(self::locale(), $rtl, true);
    }

    public static function dir(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * @param array<string, string|int> $params
     */
    public static function get(string $key, array $params = []): string
    {
        $strings = self::load(self::locale());
        $value = $strings[$key] ?? self::load(self::DEFAULT_LOCALE)[$key] ?? $key;
        foreach ($params as $name => $paramValue) {
            $value = str_replace(':' . $name, (string) $paramValue, $value);
        }
        return $value;
    }

    /**
     * @return array<string, string>
     */
    private static function load(string $code): array
    {
        if (isset(self::$cache[$code])) {
            return self::$cache[$code];
        }
        $file = BASE_PATH . '/lang/' . $code . '.php';
        self::$cache[$code] = is_file($file) ? (array) require $file : [];
        return self::$cache[$code];
    }

    public static function formatDate(\DateTimeInterface $date, int $style = \IntlDateFormatter::LONG): string
    {
        if (class_exists(\IntlDateFormatter::class)) {
            $formatter = new \IntlDateFormatter(self::locale(), $style, \IntlDateFormatter::NONE);
            $formatted = $formatter->format($date);
            if ($formatted !== false) {
                return $formatted;
            }
        }
        return $date->format((string) Settings::get('date_format', 'j M Y'));
    }
}
