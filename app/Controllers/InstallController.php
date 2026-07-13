<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\Migrator;
use App\Core\Settings;
use App\Core\View;

/**
 * First-run web installer. Auto-disabled once storage/installed.lock exists.
 */
final class InstallController
{
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'curl', 'openssl', 'sodium', 'gd', 'zip', 'fileinfo'];

    public function step(): void
    {
        if (is_file(BASE_PATH . '/storage/installed.lock')) {
            http_response_code(404);
            View::render('errors/404', [], null);
            return;
        }
        $step = max(1, min(5, (int) ($_GET['step'] ?? 1)));
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            match ($step) {
                2 => $this->saveDatabase(),
                3 => $this->runMigrations(),
                4 => $this->createAdmin(),
                5 => $this->finish(),
                default => null,
            };
            return;
        }

        $data = ['step' => $step, 'base' => self::base()];
        if ($step === 1) {
            $data['checks'] = $this->requirementChecks();
            $data['allOk'] = !in_array(false, array_column($data['checks'], 'ok'), true);
        }
        if ($step >= 3 && !$this->dbReady()) {
            self::goto('/install?step=2');
        }
        if ($step === 3) {
            $data['pending'] = count(array_filter(Migrator::status(), static fn ($m) => $m['status'] === 'pending'));
        }
        View::render('pages/install', $data, null);
    }

    private function saveDatabase(): void
    {
        $host = trim((string) ($_POST['db_host'] ?? '127.0.0.1')) ?: '127.0.0.1';
        $port = trim((string) ($_POST['db_port'] ?? '3306')) ?: '3306';
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');
        if ($name === '' || $user === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $this->fail(2, 'Database name (letters/numbers/underscores) and user are required.');
        }
        try {
            $pdo = new \PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");
        } catch (\PDOException $e) {
            $this->fail(2, 'Connection failed: ' . $e->getMessage());
        }

        $appUrl = $this->detectAppUrl();
        $env = "APP_NAME=OpenIntranet\n"
            . "APP_URL={$appUrl}\n"
            . "APP_ENV=production\n"
            . 'APP_KEY=' . Crypto::generateKey() . "\n"
            . "APP_TIMEZONE=UTC\n\n"
            . "DB_HOST={$host}\nDB_PORT={$port}\nDB_NAME={$name}\nDB_USER={$user}\nDB_PASS={$pass}\n\n"
            . "SMTP_HOST=\nSMTP_PORT=587\nSMTP_USER=\nSMTP_PASS=\nSMTP_FROM=\"OpenIntranet <no-reply@example.com>\"\n";
        if (file_put_contents(BASE_PATH . '/.env', $env, LOCK_EX) === false) {
            $this->fail(2, 'Could not write .env — check directory permissions.');
        }
        Config::boot();
        self::goto('/install?step=3');
    }

    private function runMigrations(): void
    {
        try {
            Migrator::migrate();
            \App\Console\SeedCommand::run([]);
        } catch (\Throwable $e) {
            $this->fail(3, 'Migration failed: ' . $e->getMessage());
        }
        self::goto('/install?step=4');
    }

    private function createAdmin(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10
            || $password !== (string) ($_POST['password_confirmation'] ?? '')) {
            $this->fail(4, 'Provide a name, valid email and matching password of at least 10 characters.');
        }
        $now = date('Y-m-d H:i:s');
        $existing = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        $userId = $existing !== null ? (int) $existing['id'] : DB::insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'status' => 'active',
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if ($existing !== null) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_ARGON2ID)], 'id = ?', [$userId]);
        }
        $roleId = (int) (DB::scalar("SELECT id FROM roles WHERE slug = 'super_admin'") ?? 0);
        if ($roleId > 0) {
            DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, $roleId]);
        }
        self::goto('/install?step=5');
    }

    private function finish(): void
    {
        $siteName = trim((string) ($_POST['site_name'] ?? 'OpenIntranet')) ?: 'OpenIntranet';
        Settings::set('site_name', $siteName);
        $tz = (string) ($_POST['timezone'] ?? 'UTC');
        if (in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            Settings::set('timezone', $tz);
        }
        file_put_contents(BASE_PATH . '/storage/installed.lock', date('c'), LOCK_EX);
        self::goto('/login');
    }

    // ---- helpers -------------------------------------------------------------

    /**
     * @return array<int, array{label: string, ok: bool, detail: string}>
     */
    private function requirementChecks(): array
    {
        $checks = [[
            'label' => 'PHP >= 8.2',
            'ok' => PHP_VERSION_ID >= 80200,
            'detail' => 'running ' . PHP_VERSION,
        ]];
        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks[] = [
                'label' => "ext-{$extension}",
                'ok' => extension_loaded($extension),
                'detail' => extension_loaded($extension) ? 'loaded' : 'missing — enable it in php.ini',
            ];
        }
        foreach (['storage', 'storage/cache', 'storage/logs', 'storage/sessions', 'storage/uploads', 'public/assets', '.'] as $dir) {
            $path = BASE_PATH . '/' . $dir;
            $writable = is_dir($path) ? is_writable($path) : @mkdir($path, 0775, true);
            $checks[] = [
                'label' => 'writable: ' . ($dir === '.' ? 'project root (.env)' : $dir),
                'ok' => (bool) $writable,
                'detail' => $writable ? 'ok' : 'grant the web server write access',
            ];
        }
        return $checks;
    }

    private function dbReady(): bool
    {
        if (!is_file(BASE_PATH . '/.env')) {
            return false;
        }
        try {
            DB::run('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function detectAppUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return ($https ? 'https' : 'http') . '://' . $host . self::base();
    }

    /**
     * URL base path of the app (works before APP_URL exists), e.g. "/intra".
     */
    public static function base(): string
    {
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        $base = $scriptDir === '/' ? '' : (string) preg_replace('#/public$#', '', $scriptDir);
        return rtrim($base, '/');
    }

    /**
     * Raw redirect using self::base() directly — the global redirect()
     * helper also prepends base_url() (built from APP_URL), which would
     * double the app path once .env exists from step 2 onward.
     */
    private static function goto(string $path): never
    {
        header('Location: ' . self::base() . $path, true, 302);
        exit;
    }

    private function fail(int $step, string $message): never
    {
        flash('error', $message);
        self::goto('/install?step=' . $step);
    }
}
