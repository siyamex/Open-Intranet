<div class="page-head">
    <h1>Themes</h1>
    <p class="text-muted">Activate, edit or duplicate a theme — or install one from a .zip (format documented in THEMES.md).</p>
</div>

<?php $report = $_SESSION['theme_install_report'] ?? null; unset($_SESSION['theme_install_report']); ?>
<?php if (is_array($report) && $report['ok']): ?>
<div class="card" style="border-left:4px solid var(--color-success);">
    <h2>Install report — <?= e((string) $report['slug']) ?></h2>
    <p><strong>Accepted files:</strong> <?= e(implode(', ', $report['files'])) ?></p>
    <?php if ($report['warnings'] !== []): ?>
    <p><strong>Warnings:</strong></p>
    <ul><?php foreach ($report['warnings'] as $w): ?><li class="text-muted"><?= e((string) $w) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <a class="btn btn-secondary btn-sm" href="<?= e(url('theme.preview') . '?theme=' . (int) $report['theme_id']) ?>" target="_blank" rel="noopener">Preview before activating ↗</a>
</div>
<?php endif; ?>

<div class="card">
    <h2>Install theme from ZIP</h2>
    <form method="post" action="<?= e(url('admin.themes.install')) ?>" enctype="multipart/form-data" class="filter-bar">
        <?= csrf_field() ?>
        <input class="form-control" type="file" name="theme_zip" accept=".zip,application/zip" required style="max-width:320px;">
        <button type="submit" class="btn btn-primary">Upload &amp; install</button>
        <span class="form-hint" style="margin:0;">Max 10 MB · theme.json required · PHP/script files are rejected outright.</span>
    </form>
</div>

<div class="theme-gallery">
    <?php foreach ($themes as $theme): ?>
    <?php $vars = json_decode((string) ($theme['variables'] ?? '{}'), true) ?: []; ?>
    <div class="card theme-card">
        <div class="theme-swatchbar" style="background: <?= e((string) ($vars['color-bg'] ?? '#f3f4f6')) ?>;">
            <span style="background: <?= e((string) ($vars['color-primary'] ?? '#4f46e5')) ?>;"></span>
            <span style="background: <?= e((string) ($vars['color-accent'] ?? '#0ea5e9')) ?>;"></span>
            <span style="background: <?= e((string) ($vars['color-surface'] ?? '#ffffff')) ?>; border:1px solid <?= e((string) ($vars['color-border'] ?? '#e5e7eb')) ?>;"></span>
            <span style="background: <?= e((string) ($vars['color-text'] ?? '#111827')) ?>;"></span>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.6rem;">
            <strong><?= e((string) $theme['name']) ?></strong>
            <span class="badge" style="background:var(--color-text-muted);"><?= e((string) $theme['source']) ?></span>
            <?php if ((int) $theme['is_active'] === 1): ?><span class="badge" style="background:var(--color-success);">active</span><?php endif; ?>
        </div>
        <div class="checkbox-row" style="margin-top:0.6rem;">
            <?php if ((int) $theme['is_active'] !== 1): ?>
            <form method="post" action="<?= e(url('admin.themes.activate', ['id' => $theme['id']])) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm">Activate</button>
            </form>
            <?php endif; ?>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.themes.edit', ['id' => $theme['id']])) ?>">Edit</a>
            <form method="post" action="<?= e(url('admin.themes.store')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="base_id" value="<?= (int) $theme['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Duplicate</button>
            </form>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.themes.export', ['id' => $theme['id']])) ?>">Export</a>
            <?php if ($theme['source'] === 'uploaded'): ?>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('theme.preview') . '?theme=' . (int) $theme['id']) ?>" target="_blank" rel="noopener">Preview</a>
            <?php if (\App\Core\ThemeInstaller::hasRollback((string) $theme['slug'])): ?>
            <form method="post" action="<?= e(url('admin.themes.rollback', ['id' => $theme['id']])) ?>"
                  onsubmit="return confirm('Roll back to the previous uploaded version?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm">Rollback v<?= e((string) $theme['version']) ?></button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ((int) $theme['is_active'] !== 1): ?>
            <form method="post" action="<?= e(url('admin.themes.destroy', ['id' => $theme['id']])) ?>"
                  onsubmit="return confirm('Delete this theme?');">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
