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
<script src="<?= e(asset('js/theme-boot.js')) ?>"></script>
