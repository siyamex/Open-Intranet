# Scheduled tasks

Two commands should run from cron (or Windows Task Scheduler):

| Command | Purpose | Suggested schedule |
|---|---|---|
| `php cli.php publish:due` | Takes scheduled news live and notifies employees | every 5 minutes |
| `php cli.php backup` | SQL dump + uploads archive into `storage/backups` | daily, off-peak |
| `php cli.php audit:prune` | Applies the audit-log retention setting | weekly |

## Linux crontab example

```
*/5 * * * *  cd /var/www/openintranet && php cli.php publish:due >> storage/logs/cron.log 2>&1
30 2 * * *   cd /var/www/openintranet && php cli.php backup      >> storage/logs/cron.log 2>&1
0 3 * * 0    cd /var/www/openintranet && php cli.php audit:prune >> storage/logs/cron.log 2>&1
```

## Windows Task Scheduler example

```
schtasks /Create /SC MINUTE /MO 5 /TN "OpenIntranet publish" ^
  /TR "C:\xampp\php\php.exe C:\xampp\htdocs\intra\cli.php publish:due"
schtasks /Create /SC DAILY /ST 02:30 /TN "OpenIntranet backup" ^
  /TR "C:\xampp\php\php.exe C:\xampp\htdocs\intra\cli.php backup"
```

## Docker

```
docker compose exec app php cli.php publish:due
```
