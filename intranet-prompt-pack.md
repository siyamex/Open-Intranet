# OpenIntranet — AI Prompt Pack
### Build an open-source company intranet portal in vanilla PHP

This is a complete, ordered set of copy-paste prompts for building the app with an AI coding assistant (Claude Code, etc.). Work through them **in order** — each prompt assumes the previous ones are done.

**How to use**
1. Paste **Prompt 0** first and keep it as permanent project context. In Claude Code, save it as `CLAUDE.md` in the project root.
2. Run one prompt at a time. After each: run the app, test the checkpoints, commit to git, then continue.
3. Replace "OpenIntranet" everywhere with your own product name (find & replace).
4. If something breaks, don't re-run the prompt — reply with the exact error message + file name and ask for a fix.

**Baked-in assumptions** (edit Prompt 0 if you want different): PHP 8.2+, MySQL/MariaDB, Apache or Nginx, strictly no frameworks, no Composer, no build tools — everything hand-rolled with PHP's standard library.

---

## Prompt 0 — Master Context (paste first, keep forever)

```
You are helping me build "OpenIntranet", an open-source company intranet portal in pure vanilla PHP. Keep this context for every task.

STACK & RULES
- PHP 8.2+ only. No frameworks (no Laravel/Symfony/Slim). No Composer packages — hand-roll everything using PHP's standard library (PDO, openssl, sodium, curl, DOMDocument, finfo, GD, ZipArchive).
- MySQL/MariaDB via PDO with prepared statements ONLY. Never concatenate user input into SQL.
- Frontend: plain PHP templates, vanilla JavaScript (ES6+, no build step), hand-written CSS. No jQuery, no CDNs — every asset self-hosted.
- Icons: self-hosted MIT-licensed SVG set (e.g., Tabler Icons) in /public/assets/icons.

FOLDER STRUCTURE (create and always follow)
intranet/
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

CONVENTIONS
- declare(strict_types=1); PSR-12 style; simple PSR-4-like autoloader mapping App\ to /app.
- All HTML output escaped with helper e($str) (htmlspecialchars, ENT_QUOTES).
- Every state-changing request (POST/PUT/DELETE) carries a CSRF token, verified by middleware.
- Sessions: httponly, samesite=Lax, secure on HTTPS, ID regenerated on login/privilege change, files in storage/sessions.
- Passwords: password_hash(PASSWORD_ARGON2ID).
- Secrets at rest (SSO client secrets, SMTP password) encrypted with sodium_crypto_secretbox using APP_KEY from .env (app/Core/Crypto.php).
- Uploads: stored in storage/uploads with random names, NEVER web-accessible directly; served via a controller route /files/{uuid} that checks login + permissions first.
- Every admin action is written to an audit_logs table.
- Deliver complete, runnable files (no "..." placeholders). End every answer with: list of new/changed routes + new migrations + how to test.
```

---

## Phase 1 — Foundation

### Prompt 1 — Skeleton, router, core classes

```
Following the OpenIntranet master context, build the project foundation:

1. public/index.php front controller: register autoloader, load .env + config, start hardened session, dispatch the Router.
2. app/Core/Autoloader.php — maps the App\ namespace to /app.
3. app/Core/Config.php — tiny .env parser + loads config/*.php; usage Config::get('app.name').
4. app/Core/Router.php — GET/POST/PUT/DELETE (method spoofing via _method for forms), static + parameterized routes (/news/{slug}), route groups with middleware stacks, named routes with url('route.name', params), clean 404/405 pages. Routes declared in config/routes.php.
5. app/Core/DB.php — PDO singleton + helpers: run($sql, $params), fetch, fetchAll, insert (returns id), update, delete, transaction(callable).
6. app/Core/View.php — View::render('pages/home', $data) with layouts (layouts/app.php, layouts/admin.php, layouts/auth.php), title/styles/scripts sections, and partials.
7. Global helpers: e(), redirect(), old(), asset($path) with filemtime cache-busting, csrf_field(); flash messages via app/Core/Flash.php.
8. app/Core/Migrator.php + cli.php: `php cli.php migrate` runs pending files from database/migrations in order and records them in a migrations table; `php cli.php make:admin email password` creates a super admin.
9. .env.example: APP_NAME, APP_URL, APP_ENV, APP_KEY, DB_HOST/PORT/NAME/USER/PASS, SMTP_HOST/PORT/USER/PASS/FROM.
10. Apache .htaccess (route all to index.php, deny dotfiles) + equivalent nginx.conf snippet, plus a root .htaccess denying everything outside /public for misconfigured shared hosts.

Deliver every file complete, then the exact commands to run it locally.
```

### Prompt 2 — Full database schema + seeder

```
Create ordered migrations for the complete core schema (InnoDB, utf8mb4_unicode_ci, created_at/updated_at, FK constraints):

- users (id, name, email UNIQUE, password_hash NULL for SSO-only accounts, avatar_path, job_title, phone, department_id FK, manager_id FK->users, location, timezone, bio, status ENUM active/inactive/suspended, must_change_password TINYINT, last_login_at, email_verified_at)
- roles (id, name, slug UNIQUE, description, is_system) — seed: super_admin, admin, editor, employee
- permissions (id, slug UNIQUE, label, group_name) — seed granular slugs: users.manage, roles.manage, news.create, news.publish, docs.upload, docs.manage, themes.manage, settings.manage, menus.manage, sso.manage, links.manage, audit.view
- role_permission (role_id, permission_id) and user_role (user_id, role_id)
- departments (id, name, parent_id NULL, head_user_id NULL)
- sso_providers (id, name, slug UNIQUE, type ENUM google/microsoft/oidc, client_id, client_secret_encrypted, tenant_or_issuer, discovery_url, scopes, icon, button_color, allowed_domains TEXT, auto_provision TINYINT, default_role_id, enabled, sort_order)
- user_identities (id, user_id FK, provider_id FK, provider_subject, email, raw_profile JSON, UNIQUE(provider_id, provider_subject))
- login_attempts (id, email, ip, succeeded, created_at)
- password_resets (email, token_hash, expires_at) and remember_tokens (id, user_id, selector, validator_hash, expires_at)
- menu_items (id, location ENUM sidebar/navbar/footer, label, icon, url, route_name NULL, parent_id NULL, sort_order, target ENUM _self/_blank, visible_to JSON NULL=everyone, enabled)
- quick_links (id, title, url, description, icon_type ENUM library/upload, icon_value, bg_color, sort_order, visible_to JSON, open_new_tab, is_active, click_count)
- news_categories (id, name, slug, color) ; news (id, title, slug UNIQUE, excerpt, body MEDIUMTEXT, cover_path, category_id, author_id, status ENUM draft/scheduled/published/archived, is_pinned, allow_comments, published_at, views) ; news_comments (id, news_id, user_id, body)
- doc_categories (id, name, slug, parent_id NULL, visible_to JSON) ; documents (id, title, description, category_id, file_path, original_name, mime, size_bytes, version INT, parent_doc_id NULL, visible_to JSON, uploaded_by, download_count, is_gazette TINYINT, published_at)
- settings (key PRIMARY, value TEXT, type)
- themes (id, name, slug UNIQUE, source ENUM builtin/editor/uploaded, variables JSON, dark_variables JSON NULL, custom_css MEDIUMTEXT NULL, dir_path NULL, preview_path NULL, supports_dark, is_active)
- audit_logs (id, user_id NULL, action, entity_type, entity_id, meta JSON, ip, user_agent, created_at)
- notifications (id, user_id, type, title, body, url, read_at, created_at)

Then write `php cli.php seed`: roles + permission matrix, a sample department tree, default sidebar menu_items (Home, Profile, Directory, Org Chart, News, Documents), 6 sample quick_links, 2 news categories + 3 posts, a default theme row, and settings defaults (site_name, logo_path, homepage_sections order JSON).
```

---

## Phase 2 — Authentication & SSO

### Prompt 3 — Local authentication

```
Implement local authentication for OpenIntranet:

- GET/POST /login: email + password, Argon2id verify, one generic error message, rate limiting (max 5 failures per email+IP per 15 min via login_attempts, with a clear lockout message), session_regenerate_id, redirect to intended URL. Update last_login_at + audit log.
- "Remember me": selector/validator split-token cookie (30 days), validator stored hashed, rotated on every use, cleared on logout.
- POST /logout (CSRF) destroys session + remember token.
- Password reset: request form -> email a 1-hour token link (app/Core/Mailer.php sending via SMTP from .env with STARTTLS, fallback mail()); reset form enforces policy (min 10 chars, reject top-100 common passwords list bundled in config); consume token + invalidate all remember tokens.
- must_change_password flow that forces a new password before anything else.
- AuthMiddleware (guests -> /login), GuestMiddleware, Auth::user() cached per request, Auth::can('permission.slug') resolving role permissions, Auth::hasRole().
- Login page on layouts/auth.php: centered card, logo + site name from settings, and below a divider ("or continue with") dynamically render buttons for every enabled sso_providers row linking to /auth/{slug}/redirect (flow built next prompt).

No public registration — accounts come from admins or SSO auto-provisioning.
```

### Prompt 4 — SSO engine (Google, Microsoft, any OIDC — admin-configurable)

```
Build the SSO system in pure PHP (curl + openssl, no libraries):

CORE (app/Core/Sso/)
- OidcClient.php implementing Authorization Code flow + PKCE + state + nonce against any OIDC provider: fetch and cache {issuer}/.well-known/openid-configuration and its JWKS (storage/cache, 12h TTL); build the authorization URL; exchange the code via curl POST; validate the ID token fully: RS256 signature using openssl_verify with a public key reconstructed from the JWKS n/e values (write the base64url + ASN.1/DER helpers to convert RSA modulus/exponent into PEM), then verify iss, aud, exp, iat, nonce.
- Provider presets: google (discovery https://accounts.google.com/.well-known/openid-configuration), microsoft (https://login.microsoftonline.com/{tenant}/v2.0/.well-known/openid-configuration, supporting 'common', 'organizations', or a specific tenant ID), and generic oidc (admin supplies the discovery URL).

FLOW
- GET /auth/{slug}/redirect: store state, nonce, PKCE verifier + intended URL in session; redirect to provider.
- GET /auth/{slug}/callback: validate everything; extract sub, email, email_verified, name, picture. Then:
  1. Identity exists in user_identities -> log in.
  2. Else user with the same email exists AND provider says email_verified -> link identity, log in.
  3. Else provider.auto_provision is on AND email domain is in allowed_domains (comma list; empty = allow all) -> create user (active, default_role_id, download avatar into storage), log in.
  4. Else render a friendly "account not found — contact your administrator" page.
- Handle denied consent / invalid state / expired code with clear flash messages. Audit-log every SSO login, link, and provision. Never log tokens.

ACCOUNT LINKING (self-service)
- /profile/security lists linked identities with "Connect Google / Microsoft / ..." buttons (same flow with a link_mode session flag). Unlinking allowed only if the user still has a password OR another identity.

ADMIN /admin/sso (permission sso.manage)
- Full CRUD: type preset dropdown auto-fills discovery URL + scopes; all schema fields; client_secret encrypted via Crypto and never redisplayed (show "•••• set — replace?"); enable/disable toggle; drag-sort; each provider shows its copyable Redirect URI (APP_URL/auth/{slug}/callback); a "Test configuration" button that fetches discovery + JWKS live and reports exactly what's wrong.
- Settings toggles: allow local password login on/off (super_admins can never be locked out), optional auto-redirect straight to a chosen provider.
```

---

## Phase 3 — Users, Roles & Permissions

### Prompt 5 — User management + RBAC admin

```
Build user management under /admin (permission users.manage):

- Users list: server-side search (name/email/department/role/status), sortable columns, pagination (20/page), avatar thumbnails, inline status toggle.
- Create/edit: all profile fields, searchable department + manager pickers (vanilla JS select component), multi-role assignment, "send invite email" option (sets must_change_password + emails a temporary login link), avatar upload (jpg/png/webp, max 2MB, re-encoded and resized to 256px with GD).
- Bulk CSV import: downloadable template; DRY-RUN preview screen showing per-row validation errors before committing; auto-creates missing departments.
- Actions: deactivate/suspend (blocks login, keeps content), delete with content-reassignment warning, force password reset, and "impersonate" for super_admin (persistent banner + audit trail + one-click return).
- Roles & permissions screen (roles.manage): create custom roles, checkbox matrix grouped by permission group_name, system roles protected from deletion.
- Profile pages: /profile (self-edit, admin controls which fields are self-editable via settings) and /people/{id} public card (photo, title, department, contacts, manager, direct reports, recent news authored).

All mutations CSRF-protected and audit-logged.
```

---

## Phase 4 — Layout & Navigation

### Prompt 6 — App shell: navbar + sidebar + admin menu manager

```
Build the main application shell (layouts/app.php) — fully responsive, no CSS framework:

NAVBAR (fixed top): left = logo + site name from settings; center = global search input (form posts to /search — backend comes later, wire it now); right = notifications bell with unread badge + dropdown (mark as read), dark-mode toggle, avatar menu (My profile, Security, Admin panel if permitted, Logout).
SIDEBAR (left, collapsible; state in localStorage; becomes an off-canvas drawer with backdrop under 992px): renders menu_items where location=sidebar, filtered by visible_to roles, one nesting level with expand/collapse, active-state detection by current path, SVG icons. Sidebar footer: mini profile card.
MAIN: breadcrumbs partial + content. Flash messages render as dismissible toast notifications.

ADMIN MENU MANAGER /admin/menus (menus.manage): tabs per location (sidebar/navbar/footer); tree list with drag-and-drop reorder + one-level nesting (vanilla JS drag events POSTing the new order as JSON); add/edit modal: label, icon picker, URL or internal route dropdown, target, role-visibility multiselect, enabled toggle; delete with confirmation. Changes apply instantly for all users.

Also build the ICON PICKER as a reusable component: searchable modal listing the self-hosted SVG icon set — it will be reused by quick links, themes, and more.
```

---

## Phase 5 — Dashboard Modules

### Prompt 7 — Quick links / app launcher

```
Build the "Apps" launcher — the first section of the dashboard:

USER SIDE (/): responsive grid of app tiles (SVG icon on a colored rounded square + label + optional tooltip), filtered by visible_to and is_active, honoring open_new_tab. Clicking fires a non-blocking fetch that increments click_count. Users can star favorites (user_quick_link_pins table) — favorites float to the front — and drag to personally reorder (per-user order JSON).

ADMIN /admin/quick-links (links.manage): grid + table view toggle; CRUD modal: title, URL (validated), description, icon = library picker OR upload (SVG sanitized, or PNG resized), background color (palette + custom hex), role visibility, active + new-tab toggles; drag-drop global ordering; per-tile analytics (total clicks + 30-day sparkline from a quick_link_clicks daily rollup table).

Include app/Core/SvgSanitizer.php: DOMDocument-based allowlist that strips <script>, event handlers (on*), foreignObject, external hrefs, and CSS 'url(' — plus a cli test command demonstrating it neutralizes malicious samples.
```

### Prompt 8 — News module

```
Build the News module:

PUBLIC: dashboard "News" section (pinned posts first, then 6 latest cards: cover, colored category chip, title, excerpt, author avatar + name, date, views); /news index with category filter, search and pagination; /news/{slug} article page (cover hero, sanitized body, author box, prev/next links, view counter deduplicated per session, and — if enabled in settings — comments and emoji reactions).

EDITOR /admin/news (news.create for own drafts, news.publish to go live): list with status filters; editor page with: title (auto-slug, editable), category select with quick-add, excerpt, cover upload with 16:9 crop preview (canvas), and a lightweight custom WYSIWYG built on contenteditable — toolbar: H2/H3, bold, italic, underline, lists, blockquote, link (URL validated), image upload (inserted via /files/{uuid}), table, code, undo/redo. 

Server-side HTML sanitizer (DOMDocument allowlist: p, h2, h3, ul, ol, li, blockquote, a[href, forced rel="noopener"], img[src only from own /files/], table/thead/tbody/tr/td/th, pre, code, strong, em, u, br — strip all style and on* attributes and everything else).

WORKFLOW: draft -> publish now OR schedule (future published_at; `php cli.php publish:due` for cron), archive, pin/unpin (max 3 pinned — oldest auto-unpins), and a "preview as employee" link using a signed token. On publish, create notifications for all users, respecting a per-user "news notifications" preference.
```

### Prompt 9 — Documents & gazette center

```
Build the Document Center ("Gazette & Docs"):

PUBLIC: dashboard "Gazette" section (5 latest documents where is_gazette=1: file-type icon, title, date, download); /documents browser with a category tree sidebar, list showing type icon, title, version badge, size, date, uploader; title search; PDFs open in an inline preview page using the browser's native viewer via /files/{uuid} (Content-Disposition: inline) — everything else downloads; download_count increments.

PERMISSIONS: visible_to JSON on both categories and documents, enforced server-side on browsing AND on the /files serve route.

ADMIN /admin/documents (docs.upload / docs.manage): upload (pdf, docx, xlsx, pptx, png, jpg, zip; max size from settings; finfo MIME must match the extension; stored as random uuid in storage/uploads/docs), set title/description/category/visibility/is_gazette; "Upload new version" keeps history (version+1 via parent_doc_id) with restore + changelog note; bulk move/delete; category CRUD with nesting.

Build /files/{uuid}: auth check -> permission check -> readfile with correct Content-Type, Content-Disposition, X-Content-Type-Options: nosniff. No direct storage URLs anywhere in the app.
```

---

## Phase 6 — Admin Panel Core

### Prompt 10 — Admin dashboard, settings, modules, audit viewer

```
Build the Admin Panel core at /admin:

DASHBOARD: stat cards (active users, logins today, news this month, total documents, storage used), a 30-day logins line chart drawn on <canvas> (no chart library), latest audit entries, quick-action buttons.

SETTINGS /admin/settings (settings.manage), tabbed:
- General: site name, tagline, logo + favicon upload, timezone, date format.
- Homepage: enable/disable + drag-reorder the dashboard sections (quick links, news, gazette, and future widgets) stored as JSON; per-section item counts.
- Authentication: local login on/off, session lifetime, password policy values, optional SSO auto-redirect provider.
- Mail: SMTP host/port/user/password (encrypted)/from + a "Send test email" button.
- Uploads: max sizes, allowed document types.
- Advanced: maintenance mode (custom message, admins bypass), audit log retention days.

MODULES: a modules(slug, enabled) registry with toggles that hide routes + menu items + dashboard sections for: news, documents, directory, org_chart, comments, reactions.

AUDIT LOG viewer (audit.view): filters by user/action/entity/date range, detail drawer rendering the meta JSON, CSV export.
```

---

## Phase 7 — Theme Engine (the advanced one)

### Prompt 11 — Theme engine core + dark mode

```
Build the theming engine:

- Define ALL design tokens as CSS custom properties and refactor every existing stylesheet to consume ONLY tokens for colors/fonts/radius (list each file you changed): colors (primary, primary-contrast, accent, bg, surface, surface-2, text, text-muted, border, success, warning, danger), typography (font family, base size, scale), radius (sm/md/lg), spacing density, shadow level, navbar (height, bg mode: solid/gradient), sidebar (bg, width), link style.
- ThemeService: takes the active themes row -> generates CSS: `:root{--tokens}` + `[data-theme="dark"]{...}` from dark_variables + appended custom_css -> writes a compiled file to storage/cache/theme-{id}-{hash}.css -> served through an asset route with far-future cache headers; recompiled automatically on save.
- Seed 4 built-in themes: "Aurora" (indigo light), "Slate" (neutral corporate), "Forest" (green), "Midnight" (dark-first).
- Dark mode: per-user preference auto/light/dark (navbar toggle -> sets data-theme attribute + localStorage + persists to user prefs); "auto" follows prefers-color-scheme.
```

### Prompt 12 — Visual theme editor with live preview

```
Build /admin/themes (themes.manage) — the advanced visual editor:

- GALLERY: theme cards (preview thumbnail, name, source badge, active badge) with actions: activate, edit, duplicate, export, delete (blocked for the active theme).
- EDITOR: two-pane layout.
  LEFT = controls in accordions:
  * Colors: native color inputs + hex fields + suggested palettes + a live WCAG AA contrast checker warning for text/background and primary/primary-contrast pairs.
  * Typography: bundled open-source font choices + system stacks, size scale.
  * Shape & feel: radius, density, shadows.
  * Layout: navbar style (solid/gradient/transparent), sidebar mode (light/dark/brand), login page background (color/gradient/uploaded image with overlay opacity slider).
  * Branding: logo light + dark variants, favicon.
  * Custom CSS: textarea with basic linting (balanced braces; '@import' and 'expression(' banned).
  * Dark variant tab: auto-derive dark palette from the light one with an adjustable curve, plus per-token manual overrides.
  RIGHT = LIVE PREVIEW: iframe of a /theme-preview route rendering a sample dashboard, login and news page; control changes postMessage token updates and the preview applies them to document.documentElement.style instantly without reload; device-width toggle (desktop/tablet/mobile).
- Actions: Save (recompile), Save as new theme, Reset to last saved, Export as .zip (theme.json + style.css + assets — matching the upload spec in the next prompt). Warn on unsaved changes before leaving.
```

### Prompt 13 — Custom theme upload (ZIP install)

```
Implement custom theme upload + install, and document the format in THEMES.md at the repo root:

ZIP SPEC
mytheme.zip
├── theme.json    -> { "name", "slug", "version", "author", "homepage", "supports_dark": bool, "variables": {token: value}, "dark_variables": {...} }
├── style.css     (optional extra CSS; tokens available)
├── preview.png   (optional, 800x500)
└── assets/       (images/fonts, referenced as theme://path — rewritten to real URLs on install)

INSTALL FLOW /admin/themes/upload:
- Max 10MB. Open with ZipArchive and REJECT the whole archive if ANY entry has: path traversal ('..' or absolute paths), a symlink, an extension in [php, phtml, phar, cgi, pl, sh, htaccess, ini] anywhere, or the archive holds >200 files (zip-bomb guard: also cap total uncompressed size).
- Validate theme.json against the expected schema; unknown tokens are warn-listed, not fatal. Sanitize slug; on collision append -2.
- finfo-verify every asset is genuinely an image/font/css; re-encode images with GD (strips EXIF).
- Sanitize style.css: ban @import, expression(, '</style', and url() pointing anywhere except the theme's own assets.
- Extract to themes/uploaded/{slug}; insert a themes row (source=uploaded, inactive); show an install report (accepted files + warnings).
MANAGE: activate, "preview before activating" draft link, delete (blocked while active), and re-upload as update (same id, version bump, previous directory kept as {slug}@{oldversion} for one-click rollback).
```

---

## Phase 8 — Directory & Org Chart

### Prompt 14 — Employee directory

```
Build the Employee Directory at /directory:

- Search-as-you-type (debounced fetch to /api/directory?q=) across name, title, email, phone and skills; filters: department (tree select), location, role; an A–Z index bar; grid of profile cards (photo, name, title, dept, quick actions: mailto, tel, chat link from a settings template) with a table-view toggle; pagination. The endpoint returns JSON rendered by a small vanilla JS component with loading/empty/error states.
- Department page /directory/department/{id}: head, members, sub-departments.
- Profile extras: self-managed skills tags (user_skills chips), colleague's local time from their timezone, "Download vCard" (.vcf generator), and an org-context strip (manager <- me -> direct reports).
- Admin: directory settings — which fields are visible/searchable, and per-field self-edit toggles.
```

### Prompt 15 — Interactive org chart

```
Build an interactive Org Chart at /org-chart in pure vanilla JS + SVG (no libraries):

- Data: /api/org-chart builds the tree from users.manager_id (roots = no manager), returning {id, name, title, avatar, dept, children[]}; cycle-safe (detect cycles and surface them as data errors).
- Rendering: top-down tree with SVG connector lines; node cards (avatar, name, title, department color strip, reports-count badge); collapse/expand per node with children beyond depth 3 lazy-loaded; pan by dragging + zoom (wheel and +/-/fit buttons); search box that expands the path to and centers a match; department filter that dims non-matches; clicking a node opens a side panel with the profile summary + link to full profile.
- View toggle: hierarchy | grouped by department | flat list (the flat list is also the mobile/<noscript> fallback).
- Export: PNG (serialize SVG to canvas) and a print stylesheet.
- Admin data-quality page: users with no manager (besides intended roots), broken chains, detected cycles.
```

---

## Phase 9 — Hardening & Open-Source Release

### Prompt 16 — Security hardening pass

```
Do a full security hardening pass across OpenIntranet and give me a report of every change:

- Security headers middleware: CSP (self-only; move any inline JS into files rather than loosening it), X-Frame-Options SAMEORIGIN (theme-preview iframe is same-origin so it still works), X-Content-Type-Options, Referrer-Policy, Permissions-Policy; note HSTS for production.
- Central RateLimit middleware (token buckets in storage/cache) applied to: login, password reset, SSO callbacks, search, uploads.
- Audit EVERY state-changing route for auth + permission + CSRF and output the results as a routes table.
- Uploads re-audit: double extensions, MIME sniffing, GD re-encode of all images, theme-zip bomb guard, per-user and global storage quotas from settings.
- Session fixation check; IDOR sweep — every {id}/{uuid} route must verify ownership or permission; open-redirect guard on "intended URL" (same-origin only); SSRF guard on OIDC discovery/JWKS fetching (https only, resolve DNS and block private/link-local ranges).
- Error handling: friendly 403/404/500 pages; stack traces only when APP_ENV=local; logging to storage/logs with rotation.
- `php cli.php backup`: timestamped SQL dump + storage tarball; document restore steps.
- Add SECURITY.md with a vulnerability reporting policy for the open-source repo.
```

### Prompt 17 — Web installer, Docker, and release files

```
Prepare OpenIntranet for open-source release:

- WEB INSTALLER at /install (auto-disabled afterward by writing storage/installed.lock): step 1 requirements check (PHP >= 8.2; extensions pdo_mysql, curl, openssl, sodium, gd, zip, fileinfo; writable dirs), step 2 DB credentials with live connection test -> writes .env and generates APP_KEY, step 3 run migrations + seed, step 4 create the super admin, step 5 site basics (name, logo, timezone). Styled with the default theme.
- Docker: Dockerfile (php:8.3-apache + extensions + sane php.ini) and docker-compose.yml (app, mariadb, optional mailpit for mail testing) + a Makefile (make up / make migrate / make seed).
- Cron documentation for publish:due and daily backup.
- README.md (feature list, screenshot placeholders, quick starts for Docker, classic LAMP, and shared hosting), CONTRIBUTING.md, MIT LICENSE, CHANGELOG.md, finalized .env.example, and a GitHub Actions workflow running php -l across the repo + a smoke test.
- `php cli.php seed:demo`: ~30 fake users with a realistic org structure, news, documents and links — for screenshots and trying the org chart.
```

---

## Advanced Feature Prompts (add-ons — run any, in any order, after Phase 9)

### A1 — Events & calendar

```
Add an Events module: events table (title, description, location, starts_at, ends_at, all_day, color, created_by, visible_to, rsvp_enabled). Build a month/week/list calendar in vanilla JS (no libraries), an "Upcoming events" dashboard widget, an event page with RSVP (going/maybe/no) showing attendee avatars, ICS export per event plus a personal signed-token calendar feed URL, and admin CRUD with simple recurrence (weekly/monthly, materialized 12 months ahead).
```

### A2 — Polls & surveys

```
Add Polls: single/multiple choice with optional anonymous mode; scheduled open/close; one vote per user enforced server-side (store a hash of user id when anonymous); a dashboard widget showing the active poll and animated result bars after voting; an admin builder with drag-ordered options, audience targeting by role/department, a results page, and CSV export.
```

### A3 — Kudos / recognition wall

```
Add a Kudos module: users publicly appreciate colleagues (recipient picker, message <= 300 chars, a value tag from an admin-managed list like Teamwork/Innovation); emoji reactions; a monthly leaderboard; a "latest kudos" dashboard widget; a notification to the recipient; admin moderation (hide/delete + a banned-words filter).
```

### A4 — Birthdays & anniversaries

```
Add Celebrations: optional birth_date (only day+month ever displayed) and hire_date on users (self-serve + admin); a dashboard widget for today/this week with a "Send wishes" button pre-filling a kudos; a per-user privacy opt-out; a module-level admin toggle.
```

### A5 — Global search (powers the navbar box)

```
Implement unified search at /search: MySQL FULLTEXT indexes on news(title, body), documents(title, description), users(name, job_title) and quick_links(title); grouped results with type badges and XSS-safe highlighted snippets; every result permission-filtered by its module's rules; keyboard navigation; per-user recent searches; and a Ctrl/Cmd+K overlay version of the search.
```

### A6 — Notification preferences + email digest

```
Extend notifications: a preferences page (in-app vs email, per event type: news, documents in followed categories, events, kudos, mentions); `php cli.php digest:send` producing daily/weekly HTML digest emails styled with the active theme's colors; mark-all-read; auto-purge of read notifications older than 90 days.
```

### A7 — Knowledge base / wiki

```
Add a Wiki: spaces containing a page tree; markdown editing with live side-by-side preview (small hand-written markdown parser with a strict HTML allowlist); page versioning with a word-level diff view and restore; per-space permissions; auto table of contents from headings; page owners with "review due" reminders; breadcrumbs + tree sidebar; results included in global search.
```

### A8 — Forms builder + approval workflows

```
Add a Form Builder for HR/IT requests: admin drags fields onto a form (text, textarea, select, date, file, checkbox, section header) with required rules and conditional visibility (show X when Y = value); published forms appear in a request catalog; each submission becomes a ticket with a status flow (submitted -> in review -> approved/rejected) and an approver chain (specific user, submitter's manager, or a role); email + in-app notifications at every step; a timeline view for the submitter; CSV export; per-form retention policy.
```

### A9 — Emergency broadcast banner

```
Add an Emergency Banner: admin composes severity-colored banners (message, optional link, dismissible or sticky, start/end time, audience roles) that render above the navbar on every page (checked per request, cached 30s); an acknowledgement mode requires a "I understand" click and gives admins a who-has/hasn't-acknowledged report.
```

### A10 — Multi-language (i18n)

```
Internationalize the app: lang/{code}.php key files with an __('key', [:params]) helper falling back to English; extract ALL user-facing strings file by file and give me a progress table; per-user language picker + admin default; RTL flag per language (dir="rtl" + audit CSS to logical properties); dates via IntlDateFormatter. Optional content translation: a news_translations table keyed by locale with language tabs in the editor.
```

### A11 — REST API + personal access tokens

```
Add REST API v1 under /api/v1 (JSON): personal access tokens generated in /profile/security (hashed at rest, scopes read/write/admin, last-used tracking, revocable); endpoints for users (read), directory, news, documents (metadata + permission-checked download links), events, quick-links; a consistent {data, meta, error} envelope; pagination; per-token rate limits; an OpenAPI 3 file at docs/openapi.json plus a simple /api/docs HTML viewer.
```

### A12 — LDAP / Active Directory sync

```
Add LDAP/AD integration (php-ldap extension): admin config (host, port, TLS, bind DN + encrypted password, base DN, user filter, attribute mapping to name/email/title/department/phone/manager, group->role mapping); a "test connection & preview 10 users" button; `php cli.php ldap:sync` (create/update/deactivate-missing) with a --dry-run flag and a change report; optional LDAP-bind password authentication as an extra login method.
```

### A13 — PWA (installable app)

```
Make it a PWA: manifest.json generated from settings (name, theme color pulled from the active theme, uploaded icon auto-resized to 192/512 + maskable); a vanilla service worker precaching the app shell with theme-aware cache busting, network-first pages, and an offline fallback page; web-push scaffolding (VAPID key generation via cli, a subscribe toggle in notification preferences, and an admin "send push" hooked into the notifications system).
```

### A14 — Internal analytics dashboard

```
Add privacy-friendly analytics at /admin/analytics: log page views (path, user_id, timestamp) with daily rollups via cli; canvas-drawn charts (no libraries) for DAU/WAU, top pages, top quick links, top news, top search terms including zero-result queries, document downloads, and a peak-hours heatmap; date-range picker; a retention setting and an anonymize option (hash user ids).
```

### A15 — Widgetized, personalizable homepage

```
Upgrade the dashboard into a widget system: a widget registry (quick links, news, gazette, events, birthdays, poll, kudos, sanitized custom-HTML widget for admins, and a server-fetched + cached RSS widget); admins design the default grid layout per role with a drag-drop builder; an optional "let users personalize" toggle so employees can add/remove/reorder their allowed widgets (stored per user, with reset-to-default); every widget lazy-loads via fetch with skeleton placeholders.
```

### More ideas when you're ready

Meeting-room booking with floor maps · helpdesk/ticketing with SLA timers · onboarding checklists per role · classifieds/marketplace board · cafeteria menu + shuttle schedule widgets · digital-signage display mode (rotating news/events fullscreen for lobby TVs) · Slack/Teams webhook notifications on publish · SCIM provisioning for enterprise IdPs · scheduled content expiry/archival policies · two-factor authentication (TOTP) for local accounts.

---

## Working Tips

- **One prompt per session task.** Finish, test, `git commit -m "Phase X: ..."`, then continue. Small steps beat one giant generation.
- **Test checkpoints:** after Prompt 4 log in with Google *and* Microsoft test apps; after Prompt 13 try uploading a deliberately malicious ZIP (a `.php` inside) and confirm rejection; after Prompt 16 re-run everything.
- **Fixing bugs:** paste the exact PHP error + the file name and say "fix this" — don't regenerate the whole prompt.
- **Getting SSO credentials:** Google -> Google Cloud Console OAuth client (Web); Microsoft -> Entra ID App Registration. Both need the Redirect URI the admin panel shows you.
- **If you later allow Composer:** tell the AI once — good swaps are `firebase/php-jwt` (token validation) and `phpmailer/phpmailer` (SMTP). Everything else stays the same.
