# Changelog

All notable changes to OpenIntranet are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/) and the project uses
[Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-07-10

First open-source release. 🎉

### Added
- Front controller + hand-rolled router, views, migrations, CLI runner
- Local auth (Argon2id, throttling, remember-me, password reset) and
  admin-configurable SSO (Google / Microsoft / any OIDC, PKCE + RS256)
- Users, roles & permission matrix, CSV import with dry-run, impersonation
- App shell: navbar, notification center, collapsible sidebar, menu manager
- Quick links launcher with favorites, personal ordering, click analytics
- News module: WYSIWYG, scheduling, pinning, comments, reactions, previews
- Document center: versioning, gazette, permission-checked file delivery
- Employee directory + skills + vCards; interactive SVG org chart
- Theme engine: tokens, 4 built-in themes, visual editor with live preview,
  dark mode, ZIP theme installer with rollback
- Admin panel: dashboard, tabbed settings, module toggles, maintenance mode,
  audit viewer with CSV export
- Hardening: CSP, rate limiting, SSRF guard, storage quotas, backups,
  `routes:audit`
- Web installer, Dockerfile + compose + Makefile, CI workflow, demo seeder
