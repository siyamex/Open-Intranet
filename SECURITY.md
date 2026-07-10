# Security Policy

## Reporting a vulnerability

Please **do not open a public GitHub issue** for security problems.

Email **security@your-org.example** (replace with your address before
publishing) with:

- a description of the issue and its impact,
- steps to reproduce (a proof of concept helps a lot),
- the version/commit you tested against.

You will get an acknowledgement within **72 hours**. We aim to ship a fix for
confirmed high-severity issues within **14 days** and will credit you in the
release notes unless you prefer otherwise. Please give us reasonable time to
fix the issue before public disclosure.

## Scope

- OpenIntranet application code in this repository.
- The theme ZIP installer, upload pipeline, SSO/OIDC client and REST API are
  the most security-sensitive areas — reports there are especially welcome.

Out of scope: vulnerabilities requiring an already-compromised super admin
account, issues in PHP/MySQL/Apache themselves, and social engineering.

## Hardening checklist for operators

- Serve over **HTTPS** and enable HSTS at the web server.
- Point the document root at `public/` (the bundled root `.htaccess` is only
  a safety net for shared hosts).
- Keep `APP_ENV=production` in `.env` (hides stack traces).
- Keep `APP_KEY` secret — it encrypts SSO client secrets and SMTP passwords.
- Run `php cli.php backup` and `php cli.php audit:prune` from cron.
- Review `php cli.php routes:audit` after adding custom code.
