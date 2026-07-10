# Contributing to OpenIntranet

Thanks for helping! A few ground rules keep the project true to its goal:
**a hand-rolled, dependency-free PHP codebase anyone can read end to end.**

## Golden rules

1. **No frameworks, no Composer packages, no build steps.** Solutions use
   PHP's standard library, vanilla ES6 JavaScript and hand-written CSS.
2. **PDO prepared statements only** — never concatenate user input into SQL.
3. Escape every output with `e()`; sanitize every upload; audit-log every
   admin action.
4. `declare(strict_types=1);` and PSR-12 style in every PHP file.
5. All colors/typography/shape in CSS must consume the design tokens
   (`var(--color-primary)` etc.) so themes keep working.

## Workflow

1. Fork, create a feature branch.
2. Make your change; add a migration (`database/migrations/NNN_name.sql`)
   if the schema changes — never edit an existing migration.
3. Check yourself:
   ```
   php -l on changed files
   php cli.php migrate && php cli.php seed
   php cli.php routes:audit     # no unprotected state-changing routes
   php cli.php svg:test         # if you touched sanitizers
   ```
4. Open a PR describing **what** and **why**, with screenshots for UI work.

## Security issues

Never open a public issue — see [SECURITY.md](SECURITY.md).
