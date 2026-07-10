<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\ImageTool;
use App\Core\ThemeService;
use App\Core\View;

final class ThemeController
{
    public function index(): void
    {
        $themes = DB::fetchAll('SELECT * FROM themes ORDER BY is_active DESC, name');
        View::render('admin/themes/index', ['title' => 'Themes', 'themes' => $themes], 'admin');
    }

    public function edit(string $id): void
    {
        $theme = $this->find($id);
        View::render('admin/themes/editor', [
            'title' => 'Edit theme — ' . $theme['name'],
            'theme' => $theme,
            'variables' => json_decode((string) ($theme['variables'] ?? '{}'), true) ?: [],
            'darkVariables' => json_decode((string) ($theme['dark_variables'] ?? '{}'), true) ?: [],
        ], 'admin');
    }

    public function update(string $id): void
    {
        $theme = $this->find($id);
        $data = $this->payload();
        if ($data === null) {
            redirect('admin/themes/' . $id . '/edit');
        }
        $data['source'] = $theme['source'] === 'builtin' ? 'editor' : $theme['source'];
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['name'], $data['slug']); // name/slug fixed on save-in-place
        DB::update('themes', $data, 'id = ?', [(int) $id]);
        ThemeService::compiledPath(); // recompile if this is the active theme
        Audit::log('theme.updated', 'theme', (int) $id, ['name' => $theme['name']]);
        flash('success', 'Theme saved' . ((int) $theme['is_active'] === 1 ? ' and recompiled.' : '.'));
        redirect('admin/themes/' . $id . '/edit');
    }

    /**
     * Save as a new theme (from the editor) or duplicate (from the gallery).
     */
    public function store(): void
    {
        $baseId = (int) ($_POST['base_id'] ?? 0);
        $base = $baseId > 0 ? DB::fetch('SELECT * FROM themes WHERE id = ?', [$baseId]) : null;
        $data = $this->payload();
        if ($data === null && $base === null) {
            redirect('admin/themes');
        }
        $name = trim((string) ($_POST['name'] ?? '')) ?: (($base['name'] ?? 'Theme') . ' copy');
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'theme';
        while (DB::fetch('SELECT id FROM themes WHERE slug = ?', [$slug]) !== null) {
            $slug .= '-2';
        }
        $now = date('Y-m-d H:i:s');
        $id = DB::insert('themes', [
            'name' => $name,
            'slug' => $slug,
            'source' => 'editor',
            'variables' => $data['variables'] ?? ($base['variables'] ?? '{}'),
            'dark_variables' => $data['dark_variables'] ?? ($base['dark_variables'] ?? null),
            'custom_css' => $data['custom_css'] ?? ($base['custom_css'] ?? null),
            'supports_dark' => 1,
            'is_active' => 0,
            'author' => \App\Core\Auth::user()['name'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Audit::log('theme.created', 'theme', $id, ['name' => $name]);
        flash('success', "Theme '{$name}' created.");
        redirect('admin/themes/' . $id . '/edit');
    }

    public function activate(string $id): void
    {
        $theme = $this->find($id);
        ThemeService::activate((int) $id);
        Audit::log('theme.activated', 'theme', (int) $id, ['name' => $theme['name']]);
        flash('success', "Theme '{$theme['name']}' is now active.");
        redirect('admin/themes');
    }

    public function destroy(string $id): void
    {
        $theme = $this->find($id);
        if ((int) $theme['is_active'] === 1) {
            flash('error', 'The active theme cannot be deleted — activate another one first.');
            redirect('admin/themes');
        }
        DB::delete('themes', 'id = ?', [(int) $id]);
        Audit::log('theme.deleted', 'theme', (int) $id, ['name' => $theme['name']]);
        flash('success', "Theme '{$theme['name']}' deleted.");
        redirect('admin/themes');
    }

    /**
     * Export as .zip matching the upload spec: theme.json + style.css.
     */
    public function export(string $id): void
    {
        $theme = $this->find($id);
        $zipPath = tempnam(sys_get_temp_dir(), 'theme');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', (string) json_encode([
            'name' => $theme['name'],
            'slug' => $theme['slug'],
            'version' => $theme['version'] ?? '1.0.0',
            'author' => $theme['author'] ?? '',
            'homepage' => '',
            'supports_dark' => (bool) $theme['supports_dark'],
            'variables' => json_decode((string) ($theme['variables'] ?? '{}'), true) ?: new \stdClass(),
            'dark_variables' => json_decode((string) ($theme['dark_variables'] ?? '{}'), true) ?: new \stdClass(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (!empty($theme['custom_css'])) {
            $zip->addFromString('style.css', (string) $theme['custom_css']);
        }
        $zip->close();
        Audit::log('theme.exported', 'theme', (int) $id, ['name' => $theme['name']]);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $theme['slug'] . '.zip"');
        header('Content-Length: ' . (string) filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    /**
     * Sample page rendered inside the editor's live-preview iframe.
     * ?theme={id} previews a non-active theme (draft link before activating).
     */
    public function preview(): void
    {
        $draftCss = null;
        if (!empty($_GET['theme'])) {
            $draft = DB::fetch('SELECT * FROM themes WHERE id = ?', [(int) $_GET['theme']]);
            if ($draft !== null) {
                $draftCss = ThemeService::compile($draft);
            }
        }
        View::render('pages/theme-preview', ['title' => 'Theme preview', 'draftCss' => $draftCss], null);
    }

    /**
     * ZIP theme install (spec in THEMES.md).
     */
    public function install(): void
    {
        $file = $_FILES['theme_zip'] ?? null;
        if (!is_array($file) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a theme .zip file.');
            redirect('admin/themes');
        }
        if ((int) $file['size'] > \App\Core\ThemeInstaller::MAX_ZIP_BYTES) {
            flash('error', 'Theme archives may be at most 10 MB.');
            redirect('admin/themes');
        }
        $report = \App\Core\ThemeInstaller::install((string) $file['tmp_name']);
        $_SESSION['theme_install_report'] = $report;
        if ($report['ok']) {
            Audit::log('theme.installed', 'theme', $report['theme_id'], ['slug' => $report['slug'], 'warnings' => count($report['warnings'])]);
            flash('success', 'Theme installed — review the install report below, then preview or activate it.');
        } else {
            Audit::log('theme.install_rejected', 'theme', null, ['errors' => $report['errors']]);
            flash('error', 'Theme rejected: ' . implode(' ', $report['errors']));
        }
        redirect('admin/themes');
    }

    public function rollback(string $id): void
    {
        $error = \App\Core\ThemeInstaller::rollback((int) $id);
        if ($error !== null) {
            flash('error', $error);
        } else {
            Audit::log('theme.rolled_back', 'theme', (int) $id);
            flash('success', 'Theme rolled back to the previous version.');
        }
        redirect('admin/themes');
    }

    /**
     * Login background image upload (used by the Layout accordion).
     */
    public function uploadLoginBg(): void
    {
        header('Content-Type: application/json');
        $file = $_FILES['image'] ?? null;
        if (!is_array($file) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK || (int) $file['size'] > 4 * 1024 * 1024) {
            echo json_encode(['error' => 'Upload failed (max 4 MB).']);
            exit;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if (!in_array((string) $finfo->file((string) $file['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            echo json_encode(['error' => 'JPG, PNG or WebP only.']);
            exit;
        }
        $encoded = ImageTool::resizeEncode((string) file_get_contents((string) $file['tmp_name']), 1920, 'jpeg');
        if ($encoded === null) {
            echo json_encode(['error' => 'Image could not be processed.']);
            exit;
        }
        $dir = BASE_PATH . '/public/assets/branding';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = 'login-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
        file_put_contents($dir . '/' . $name, $encoded, LOCK_EX);
        echo json_encode(['url' => base_url('assets/branding/' . $name)]);
        exit;
    }

    /**
     * @return array{variables: string, dark_variables: ?string, custom_css: ?string}|null
     */
    private function payload(): ?array
    {
        $variables = json_decode((string) ($_POST['variables'] ?? ''), true);
        if (!is_array($variables)) {
            flash('error', 'Invalid token data.');
            return null;
        }
        $dark = json_decode((string) ($_POST['dark_variables'] ?? 'null'), true);
        $css = trim((string) ($_POST['custom_css'] ?? ''));
        if ($css !== '') {
            if (substr_count($css, '{') !== substr_count($css, '}')) {
                flash('error', 'Custom CSS has unbalanced braces.');
                return null;
            }
            if (preg_match('/@import|expression\s*\(/i', $css)) {
                flash('error', 'Custom CSS may not contain @import or expression().');
                return null;
            }
            $css = ThemeService::sanitizeCss($css);
        }
        return [
            'variables' => (string) json_encode($variables, JSON_UNESCAPED_SLASHES),
            'dark_variables' => is_array($dark) && $dark !== [] ? (string) json_encode($dark, JSON_UNESCAPED_SLASHES) : null,
            'custom_css' => $css !== '' ? $css : null,
        ];
    }

    private function find(string $id): array
    {
        $theme = DB::fetch('SELECT * FROM themes WHERE id = ?', [(int) $id]);
        if ($theme === null) {
            flash('error', 'Theme not found.');
            redirect('admin/themes');
        }
        return $theme;
    }
}
