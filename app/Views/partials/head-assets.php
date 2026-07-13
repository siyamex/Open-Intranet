<?php
use App\Core\Settings;
use App\Core\ThemeService;

$favicon = Settings::get('favicon_path');
?>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(base_url('theme.css') . '?v=' . ThemeService::version()) ?>">
<?php if (is_string($favicon) && $favicon !== ''): ?>
<link rel="icon" href="<?= e(base_url($favicon)) ?>">
<?php endif; ?>
<link rel="manifest" href="<?= e(url('pwa.manifest')) ?>">
<meta name="theme-color" content="<?= e((string) (json_decode((string) (\App\Core\ThemeService::activeTheme()['variables'] ?? '{}'), true)['color-primary'] ?? '#4f46e5')) ?>">
<script src="<?= e(asset('js/theme-boot.js')) ?>"></script>
