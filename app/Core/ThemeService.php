<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Compiles the active theme row into a static CSS file:
 * :root{--tokens} + [data-theme="dark"]{--tokens} + custom_css,
 * written to storage/cache/theme-{id}-{hash}.css and served via /theme.css.
 */
final class ThemeService
{
    private static ?array $active = null;

    public static function activeTheme(): ?array
    {
        if (self::$active !== null) {
            return self::$active;
        }
        try {
            self::$active = DB::fetch('SELECT * FROM themes WHERE is_active = 1 ORDER BY id LIMIT 1');
        } catch (\PDOException) {
            self::$active = null;
        }
        return self::$active;
    }

    public static function activate(int $themeId): void
    {
        DB::run('UPDATE themes SET is_active = 0');
        DB::update('themes', ['is_active' => 1], 'id = ?', [$themeId]);
        self::$active = null;
        self::compiledPath(); // pre-compile
    }

    /**
     * Path of the compiled CSS for the active theme (compiling if needed),
     * or null when no theme is active.
     */
    public static function compiledPath(): ?string
    {
        $theme = self::activeTheme();
        if ($theme === null) {
            return null;
        }
        $hash = self::hash($theme);
        $file = BASE_PATH . '/storage/cache/theme-' . (int) $theme['id'] . '-' . $hash . '.css';
        if (!is_file($file)) {
            // clear stale compiles of this theme
            foreach (glob(BASE_PATH . '/storage/cache/theme-' . (int) $theme['id'] . '-*.css') ?: [] as $old) {
                @unlink($old);
            }
            file_put_contents($file, self::compile($theme), LOCK_EX);
        }
        return $file;
    }

    public static function version(): string
    {
        $theme = self::activeTheme();
        return $theme === null ? '0' : self::hash($theme);
    }

    /**
     * Build the CSS for a theme row (also used by the live editor preview).
     */
    public static function compile(array $theme): string
    {
        $light = self::decodeVars($theme['variables'] ?? null);
        $dark = self::decodeVars($theme['dark_variables'] ?? null);

        $css = "/* Theme: " . str_replace('*/', '', (string) $theme['name']) . " (compiled) */\n";
        if ($light !== []) {
            $css .= ":root {\n" . self::varsBlock($light) . "}\n";
        }
        if ($dark !== []) {
            $css .= "[data-theme=\"dark\"] {\n" . self::varsBlock($dark) . "}\n";
        }
        $custom = trim((string) ($theme['custom_css'] ?? ''));
        if ($custom !== '') {
            $css .= "\n/* custom css */\n" . self::sanitizeCss($custom) . "\n";
        }
        return $css;
    }

    /**
     * Strip constructs that could escape the stylesheet sandbox.
     */
    public static function sanitizeCss(string $css): string
    {
        $css = str_ireplace(['@import', 'expression(', '</style'], ['/*import*/', '/*expr*/(', ''], $css);
        // url() only for same-origin/relative or data:image
        $css = (string) preg_replace_callback('/url\s*\(([^)]*)\)/i', static function (array $m): string {
            $target = trim($m[1], " \t\"'");
            if (str_starts_with($target, 'data:image/')
                || str_starts_with($target, '/')
                || str_starts_with($target, '../')
                || preg_match('#^[a-z0-9_.-]+([/?].*)?$#i', $target)) {
                return $m[0];
            }
            return 'url()';
        }, $css);
        return $css;
    }

    /**
     * @return array<string, string>
     */
    private static function decodeVars(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $key => $value) {
            $key = (string) preg_replace('/[^a-z0-9-]/', '', strtolower((string) $key));
            if ($key === '' || !is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            // token values may not contain characters that break out of the declaration
            if ($value === '' || preg_match('/[{};]|@import|expression\s*\(|url\s*\(\s*[\'"]?\s*(https?:)?\/\//i', $value)) {
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    private static function varsBlock(array $vars): string
    {
        $out = '';
        foreach ($vars as $key => $value) {
            $out .= "    --{$key}: {$value};\n";
        }
        return $out;
    }

    private static function hash(array $theme): string
    {
        return substr(sha1(
            (string) $theme['id'] . '|' . (string) $theme['updated_at'] . '|'
            . (string) $theme['variables'] . '|' . (string) $theme['dark_variables'] . '|'
            . (string) $theme['custom_css']
        ), 0, 12);
    }
}
