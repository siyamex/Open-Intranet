# OpenIntranet — Master Context

You are helping me build "OpenIntranet", an open-source company intranet portal in pure vanilla PHP. Keep this context for every task.

## STACK & RULES
- PHP 8.2+ only. No frameworks (no Laravel/Symfony/Slim). No Composer packages — hand-roll everything using PHP's standard library (PDO, openssl, sodium, curl, DOMDocument, finfo, GD, ZipArchive).
- MySQL/MariaDB via PDO with prepared statements ONLY. Never concatenate user input into SQL.
- Frontend: plain PHP templates, vanilla JavaScript (ES6+, no build step), hand-written CSS. No jQuery, no CDNs — every asset self-hosted.
- Icons: self-hosted MIT-licensed SVG set (Tabler Icons subset) in /public/assets/icons.

## FOLDER STRUCTURE
```
intra/
├── public/                 # the ONLY web-accessible directory (document root)
│   ├── index.php           # front controller
│   └── assets/             # css, js, icons, fonts, images
├── app/
│   ├── Core/               # Router, DB, Auth, View, Csrf, Config, Crypto, Validator, Flash, Mailer, Migrator, Autoloader
│   ├── Controllers/        # e.g. Admin/UserController.php
│   ├── Models/             # thin classes wrapping SQL per table
│   ├── Middleware/         # AuthMiddleware, AdminMiddleware, CsrfMiddleware, RateLimit
│   └── Views/              # layouts/, partials/, pages/, admin/
├── config/                 # app.php, routes.php, database.php (read from .env)
├── database/migrations/    # 001_xxx.sql ... ordered SQL files
├── storage/                # uploads/, cache/, logs/, sessions/ — NOT web accessible
├── themes/                 # built-in + uploaded themes
├── cli.php                 # command runner (migrate, seed, make:admin, ...)
└── .env.example
```

## CONVENTIONS
- declare(strict_types=1); PSR-12 style; simple PSR-4-like autoloader mapping App\ to /app.
- All HTML output escaped with helper e($str) (htmlspecialchars, ENT_QUOTES).
- Every state-changing request (POST/PUT/DELETE) carries a CSRF token, verified by middleware.
- Sessions: httponly, samesite=Lax, secure on HTTPS, ID regenerated on login/privilege change, files in storage/sessions.
- Passwords: password_hash(PASSWORD_ARGON2ID).
- Secrets at rest (SSO client secrets, SMTP password) encrypted with sodium_crypto_secretbox using APP_KEY from .env (app/Core/Crypto.php).
- Uploads: stored in storage/uploads with random names, NEVER web-accessible directly; served via a controller route /files/{uuid} that checks login + permissions first.
- Every admin action is written to an audit_logs table.
- Deliver complete, runnable files (no "..." placeholders). End every answer with: list of new/changed routes + new migrations + how to test.

## LOCAL DEV ENVIRONMENT (this machine)
- Windows 11 + XAMPP. PHP 8.3 CLI: `C:\xampp\php\php.exe`. MariaDB 10.4, user `root`, empty password.
- App lives at `c:\xampp\htdocs\intra`; served at `http://localhost/intra/public/` (root .htaccess rewrites `/intra/` → `public/`).
- Database: `openintranet`.
- Run CLI as: `C:\xampp\php\php.exe cli.php <command>`.
