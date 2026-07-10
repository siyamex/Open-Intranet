# Backup & Restore

## Creating backups

```
php cli.php backup
```

Writes two timestamped files into `storage/backups/`:

- `db-YYYYMMDD-HHMMSS.sql` — full database dump (mysqldump when available,
  otherwise a plain-PHP dump)
- `uploads-YYYYMMDD-HHMMSS.zip` — everything under `storage/uploads`
  (documents, avatars, news images, quick-link icons)

Schedule it daily via cron / Task Scheduler and ship the files off-server:

```
# Linux cron, 02:30 every night
30 2 * * * cd /var/www/openintranet && php cli.php backup
```

## Restoring

1. Deploy the application code and copy your `.env` (the **same APP_KEY** is
   required — encrypted SSO/SMTP secrets are unreadable without it).
2. Restore the database:
   ```
   mysql -u USER -p DATABASE < storage/backups/db-....sql
   ```
3. Unzip the uploads archive back into place:
   ```
   unzip uploads-....zip -d storage/
   ```
4. Clear compiled caches: delete `storage/cache/*` (they regenerate).
5. Log in and spot-check documents, avatars and news images.
