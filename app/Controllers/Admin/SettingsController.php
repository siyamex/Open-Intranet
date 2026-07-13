<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\ImageTool;
use App\Core\Mailer;
use App\Core\Modules;
use App\Core\Settings;
use App\Core\View;

final class SettingsController
{
    private const TABS = ['general', 'homepage', 'authentication', 'mail', 'uploads', 'directory', 'languages', 'modules', 'advanced'];

    public function index(): void
    {
        $tab = in_array($_GET['tab'] ?? '', self::TABS, true) ? (string) $_GET['tab'] : 'general';
        View::render('admin/settings/index', [
            'title' => 'Settings',
            'tab' => $tab,
            'tabs' => self::TABS,
            'providers' => DB::fetchAll('SELECT name, slug FROM sso_providers WHERE enabled = 1 ORDER BY sort_order'),
            'modules' => Modules::all(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ], 'admin');
    }

    public function save(): void
    {
        $tab = (string) ($_POST['tab'] ?? 'general');
        match ($tab) {
            'general' => $this->saveGeneral(),
            'homepage' => $this->saveHomepage(),
            'authentication' => $this->saveAuthentication(),
            'mail' => $this->saveMail(),
            'uploads' => $this->saveUploads(),
            'directory' => $this->saveDirectory(),
            'languages' => $this->saveLanguages(),
            'modules' => $this->saveModules(),
            'advanced' => $this->saveAdvanced(),
            default => null,
        };
        Audit::log('settings.updated', 'settings', $tab);
        flash('success', ucfirst($tab) . ' settings saved.');
        redirect('admin/settings?tab=' . $tab);
    }

    public function testMail(): void
    {
        $to = (string) (Auth::user()['email'] ?? '');
        $ok = Mailer::send(
            $to,
            'Test email from ' . Settings::get('site_name', 'OpenIntranet'),
            '<p>This is a test email — your mail settings work. 🎉</p>'
        );
        flash($ok ? 'success' : 'error', $ok
            ? "Test email sent to {$to} (check storage/logs/mail.log in local dev)."
            : 'Sending failed — check the SMTP settings and storage/logs.');
        redirect('admin/settings?tab=mail');
    }

    private function saveGeneral(): void
    {
        Settings::set('site_name', trim((string) ($_POST['site_name'] ?? 'OpenIntranet')) ?: 'OpenIntranet');
        Settings::set('site_tagline', trim((string) ($_POST['site_tagline'] ?? '')));
        $tz = (string) ($_POST['timezone'] ?? 'UTC');
        if (in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            Settings::set('timezone', $tz);
        }
        Settings::set('date_format', trim((string) ($_POST['date_format'] ?? 'j M Y')) ?: 'j M Y');
        $this->applyBrandingUpload('logo', 'logo_path', 400, 'png');
        $this->applyBrandingUpload('favicon', 'favicon_path', 64, 'png');
    }

    private function saveHomepage(): void
    {
        $known = ['quick_links', 'news', 'gazette', 'events', 'poll', 'kudos', 'celebrations'];
        $order = array_values(array_intersect(
            array_map('strval', (array) json_decode((string) ($_POST['sections_order'] ?? '[]'), true)),
            $known
        ));
        $enabled = array_values(array_filter((array) ($_POST['sections_enabled'] ?? []), 'is_string'));
        $sections = array_values(array_filter($order !== [] ? $order : $known,
            static fn (string $s): bool => in_array($s, $enabled, true)));
        Settings::set('homepage_sections', $sections, 'json');
        Settings::set('news_dashboard_count', max(1, min(12, (int) ($_POST['news_dashboard_count'] ?? 6))), 'int');
        Settings::set('gazette_dashboard_count', max(1, min(12, (int) ($_POST['gazette_dashboard_count'] ?? 5))), 'int');
    }

    private function saveAuthentication(): void
    {
        Settings::set('allow_local_login', !empty($_POST['allow_local_login']), 'bool');
        Settings::set('session_lifetime_minutes', max(10, min(43200, (int) ($_POST['session_lifetime_minutes'] ?? 120))), 'int');
        Settings::set('password_min_length', max(8, min(64, (int) ($_POST['password_min_length'] ?? 10))), 'int');
        Settings::set('sso_auto_redirect', trim((string) ($_POST['sso_auto_redirect'] ?? '')));
    }

    private function saveMail(): void
    {
        Settings::set('smtp_host', trim((string) ($_POST['smtp_host'] ?? '')));
        Settings::set('smtp_port', max(1, min(65535, (int) ($_POST['smtp_port'] ?? 587))), 'int');
        Settings::set('smtp_user', trim((string) ($_POST['smtp_user'] ?? '')));
        $pass = (string) ($_POST['smtp_pass'] ?? '');
        if ($pass !== '') {
            Settings::set('smtp_pass_encrypted', Crypto::encrypt($pass));
        }
        Settings::set('smtp_from', trim((string) ($_POST['smtp_from'] ?? '')));
    }

    private function saveUploads(): void
    {
        Settings::set('upload_max_mb', max(1, min(512, (int) ($_POST['upload_max_mb'] ?? 20))), 'int');
        Settings::set('storage_quota_user_mb', max(10, min(102400, (int) ($_POST['storage_quota_user_mb'] ?? 500))), 'int');
        Settings::set('storage_quota_global_mb', max(100, min(1048576, (int) ($_POST['storage_quota_global_mb'] ?? 10240))), 'int');
        $known = ['pdf', 'docx', 'xlsx', 'pptx', 'png', 'jpg', 'zip'];
        $types = array_values(array_intersect((array) ($_POST['allowed_doc_types'] ?? []), $known));
        Settings::set('allowed_doc_types', $types !== [] ? $types : $known, 'json');
    }

    private function saveDirectory(): void
    {
        $visibleKnown = ['email', 'phone', 'department', 'location', 'skills', 'local_time'];
        $searchKnown = ['title', 'email', 'phone', 'skills'];
        $editKnown = ['name', 'phone', 'location', 'timezone', 'bio', 'avatar'];
        Settings::set('directory_visible_fields', array_values(array_intersect((array) ($_POST['visible'] ?? []), $visibleKnown)), 'json');
        Settings::set('directory_searchable_fields', array_values(array_intersect((array) ($_POST['searchable'] ?? []), $searchKnown)), 'json');
        Settings::set('profile_self_editable', array_values(array_intersect((array) ($_POST['self_edit'] ?? []), $editKnown)), 'json');
        Settings::set('directory_chat_template', trim((string) ($_POST['chat_template'] ?? '')));
    }

    private function saveLanguages(): void
    {
        $active = array_map('strval', (array) ($_POST['active'] ?? []));
        foreach (DB::fetchAll('SELECT code FROM languages') as $lang) {
            DB::update('languages', ['is_active' => in_array($lang['code'], $active, true) ? 1 : 0], 'code = ?', [$lang['code']]);
        }
        $default = (string) ($_POST['default_locale'] ?? 'en');
        if (in_array($default, $active, true)) {
            Settings::set('default_locale', $default);
        }
    }

    private function saveModules(): void
    {
        $enabled = (array) ($_POST['modules'] ?? []);
        foreach (array_keys(Modules::all()) as $slug) {
            Modules::set($slug, array_key_exists($slug, $enabled));
        }
    }

    private function saveAdvanced(): void
    {
        Settings::set('maintenance_mode', !empty($_POST['maintenance_mode']), 'bool');
        Settings::set('maintenance_message', trim((string) ($_POST['maintenance_message'] ?? '')));
        Settings::set('audit_retention_days', max(7, min(3650, (int) ($_POST['audit_retention_days'] ?? 365))), 'int');
        Settings::set('comments_enabled', !empty($_POST['comments_enabled']), 'bool');
        Settings::set('reactions_enabled', !empty($_POST['reactions_enabled']), 'bool');
    }

    /**
     * Branding files live in public/assets/branding (they must be readable
     * on the login page, pre-auth). Always re-encoded with GD.
     */
    private function applyBrandingUpload(string $field, string $settingKey, int $max, string $format): void
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > 2 * 1024 * 1024) {
            flash('warning', ucfirst($field) . ' skipped: upload failed or exceeds 2 MB.');
            return;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            flash('warning', ucfirst($field) . ' skipped: PNG, JPG or WebP only.');
            return;
        }
        $encoded = ImageTool::resizeEncode((string) file_get_contents((string) $file['tmp_name']), $max, $format);
        if ($encoded === null) {
            flash('warning', ucfirst($field) . ' skipped: the image could not be processed.');
            return;
        }
        $dir = BASE_PATH . '/public/assets/branding';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = $field . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $format;
        file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
        // remove the previous file
        $old = Settings::get($settingKey);
        if (is_string($old) && $old !== '' && str_starts_with($old, 'assets/branding/')) {
            @unlink(BASE_PATH . '/public/' . $old);
        }
        Settings::set($settingKey, 'assets/branding/' . $name);
    }
}
