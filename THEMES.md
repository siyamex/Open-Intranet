# OpenIntranet Theme Format

Themes are ZIP archives installed via **Admin → Themes → Install theme from ZIP**
(permission `themes.manage`, max 10 MB).

## Archive layout

```
mytheme.zip
├── theme.json      (required)
├── style.css       (optional extra CSS; design tokens are available)
├── preview.png     (optional gallery thumbnail, ~800x500)
└── assets/         (optional images/fonts, referenced as theme://path)
```

## theme.json

```json
{
    "name": "My Theme",
    "slug": "my-theme",
    "version": "1.0.0",
    "author": "Jane Doe",
    "homepage": "https://example.com",
    "supports_dark": true,
    "variables": {
        "color-primary": "#0f766e",
        "color-primary-contrast": "#ffffff",
        "color-accent": "#f59e0b",
        "color-bg": "#f6f8f7",
        "color-surface": "#ffffff",
        "color-surface-2": "#f1f5f3",
        "color-text": "#111827",
        "color-text-muted": "#6b7280",
        "color-border": "#e2e8f0",
        "color-success": "#16a34a",
        "color-warning": "#d97706",
        "color-danger": "#dc2626",
        "font-family": "Georgia, serif",
        "font-size-base": "16px",
        "radius-sm": "4px",
        "radius-md": "8px",
        "radius-lg": "12px",
        "navbar-bg": "linear-gradient(90deg, var(--color-primary), var(--color-accent))",
        "sidebar-bg": "var(--color-surface)",
        "login-bg": "url(theme://login.jpg) center / cover"
    },
    "dark_variables": {
        "color-bg": "#0e1513",
        "color-surface": "#16211d",
        "color-text": "#e2e8f0"
    }
}
```

- **variables** become CSS custom properties on `:root` (key `color-primary`
  → `--color-primary`).
- **dark_variables** are emitted under `[data-theme="dark"]`.
- Unknown token names are installed with a warning (they simply have no
  effect unless your `style.css` uses them).
- `theme://path` inside token values and `style.css` is rewritten at install
  time to the theme's real asset URL.

## style.css

Optional extra CSS appended after the token blocks. All tokens are available
as `var(--token-name)`. Sanitized on install: `@import`, `expression(` and
`</style` are stripped; `url()` may only point to relative paths (your own
assets) or `data:image/` URIs.

## assets/

Allowed types: `png jpg jpeg webp gif svg woff woff2 ttf otf css`.
Every file is content-verified with `finfo`; bitmaps are re-encoded with GD
(stripping EXIF), SVGs pass the allowlist sanitizer, CSS files are sanitized
like `style.css`. Anything else is skipped with a warning.

## Hard rejections (the whole archive is refused)

- more than **200 entries** or **50 MB uncompressed** (zip-bomb guard)
- any entry with `..`, an absolute path, or a symlink
- any file with a scripting/server extension anywhere in its name:
  `php phtml phar phps cgi pl sh htaccess ini exe bat cmd com js`
- hidden/dot files
- missing or invalid `theme.json`

## Versions & rollback

Re-uploading a ZIP whose slug matches an existing **uploaded** theme updates
it in place: the version is taken from `theme.json` (auto-bumped if
unchanged) and the previous directory is kept as `themes/uploaded/{slug}@{oldversion}`
for a one-click **Rollback** in the gallery. Slug collisions with built-in or
editor themes get `-2` appended instead.
