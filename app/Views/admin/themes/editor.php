<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('admin.themes')) ?>">&larr; Back to themes</a> · <span class="text-muted" id="dirty-hint" hidden>Unsaved changes</span></p>
</div>

<form method="post" action="<?= e(url('admin.themes.update', ['id' => $theme['id']])) ?>" id="theme-form">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="variables" id="field-variables">
    <input type="hidden" name="dark_variables" id="field-dark-variables">

    <div class="theme-editor"
         data-vars='<?= e(json_encode($variables, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
         data-dark='<?= e(json_encode($darkVariables, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'
         data-upload-url="<?= e(url('admin.themes.upload-login-bg')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         id="theme-editor">

        <div class="theme-controls">
            <div class="tabs" style="margin-bottom:0.75rem;">
                <button type="button" class="tab active" data-variant="light">Light</button>
                <button type="button" class="tab" data-variant="dark">Dark variant</button>
            </div>

            <div id="dark-tools" class="card" hidden>
                <label class="form-label">Auto-derive dark palette from light</label>
                <div style="display:flex; gap:0.6rem; align-items:center;">
                    <input type="range" id="derive-curve" min="10" max="35" value="18" style="flex:1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="derive-btn">Derive</button>
                </div>
                <p class="form-hint">Slide for darker/lighter base, then fine-tune tokens below.</p>
            </div>

            <details class="accordion" open>
                <summary>Colors</summary>
                <div class="accordion-body" id="color-controls"></div>
                <div id="contrast-warnings"></div>
                <div class="palette-suggestions">
                    <span class="text-muted" style="font-size:0.8rem;">Suggested palettes:</span>
                    <button type="button" class="swatch" data-palette='{"color-primary":"#4f46e5","color-accent":"#0ea5e9"}' style="background:#4f46e5;" title="Indigo"></button>
                    <button type="button" class="swatch" data-palette='{"color-primary":"#0f766e","color-accent":"#f59e0b"}' style="background:#0f766e;" title="Teal"></button>
                    <button type="button" class="swatch" data-palette='{"color-primary":"#b91c1c","color-accent":"#f97316"}' style="background:#b91c1c;" title="Crimson"></button>
                    <button type="button" class="swatch" data-palette='{"color-primary":"#1d4ed8","color-accent":"#059669"}' style="background:#1d4ed8;" title="Blue"></button>
                    <button type="button" class="swatch" data-palette='{"color-primary":"#7c3aed","color-accent":"#db2777"}' style="background:#7c3aed;" title="Violet"></button>
                </div>
            </details>

            <details class="accordion">
                <summary>Typography</summary>
                <div class="accordion-body">
                    <div class="form-group">
                        <label class="form-label">Font family</label>
                        <select class="form-control" id="ctl-font-family">
                            <option value='system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'>System UI</option>
                            <option value='Georgia, "Times New Roman", serif'>Serif (Georgia)</option>
                            <option value='"Segoe UI", Tahoma, Geneva, Verdana, sans-serif'>Segoe stack</option>
                            <option value='Verdana, Geneva, sans-serif'>Verdana</option>
                            <option value='"Trebuchet MS", Helvetica, sans-serif'>Trebuchet</option>
                            <option value='ui-monospace, "Cascadia Mono", Consolas, monospace'>Monospace</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base size: <span id="font-size-value"></span></label>
                        <input type="range" id="ctl-font-size" min="14" max="18" step="0.5" style="width:100%;">
                    </div>
                </div>
            </details>

            <details class="accordion">
                <summary>Shape &amp; feel</summary>
                <div class="accordion-body">
                    <div class="form-group">
                        <label class="form-label">Corner radius: <span id="radius-value"></span></label>
                        <input type="range" id="ctl-radius" min="0" max="20" style="width:100%;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Density</label>
                        <select class="form-control" id="ctl-density">
                            <option value="0.85rem">Compact</option>
                            <option value="1rem" selected>Comfortable</option>
                            <option value="1.15rem">Spacious</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Shadows</label>
                        <select class="form-control" id="ctl-shadow">
                            <option value="none">None</option>
                            <option value="soft" selected>Soft</option>
                            <option value="strong">Strong</option>
                        </select>
                    </div>
                </div>
            </details>

            <details class="accordion">
                <summary>Layout</summary>
                <div class="accordion-body">
                    <div class="form-group">
                        <label class="form-label">Navbar style</label>
                        <select class="form-control" id="ctl-navbar">
                            <option value="solid">Solid (surface)</option>
                            <option value="gradient">Gradient (primary → accent)</option>
                            <option value="transparent">Transparent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sidebar mode</label>
                        <select class="form-control" id="ctl-sidebar">
                            <option value="light">Light (surface)</option>
                            <option value="dark">Dark</option>
                            <option value="brand">Brand (primary)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link style</label>
                        <select class="form-control" id="ctl-links">
                            <option value="none">Plain</option>
                            <option value="underline">Underlined</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login page background</label>
                        <select class="form-control" id="ctl-login-bg-mode">
                            <option value="color">Theme background color</option>
                            <option value="gradient">Gradient (primary → accent)</option>
                            <option value="image">Uploaded image</option>
                        </select>
                        <div id="login-image-row" style="margin-top:0.5rem;" hidden>
                            <input class="form-control" type="file" id="ctl-login-image" accept="image/jpeg,image/png,image/webp">
                            <label class="form-label" style="margin-top:0.5rem;">Overlay opacity: <span id="overlay-value"></span></label>
                            <input type="range" id="ctl-login-overlay" min="0" max="80" value="30" style="width:100%;">
                        </div>
                    </div>
                </div>
            </details>

            <details class="accordion">
                <summary>Branding</summary>
                <div class="accordion-body">
                    <p class="form-hint">Logo and favicon are managed in <a href="<?= e(url('admin.settings') . '?tab=general') ?>">Settings → General</a> so they apply across every theme.</p>
                </div>
            </details>

            <details class="accordion">
                <summary>Custom CSS</summary>
                <div class="accordion-body">
                    <textarea class="form-control" name="custom_css" id="ctl-custom-css" rows="8"
                              placeholder=".navbar { border-bottom-width: 2px; }"><?= e((string) ($theme['custom_css'] ?? '')) ?></textarea>
                    <p class="form-error" id="css-lint" hidden></p>
                    <p class="form-hint">Tokens are available as var(--color-primary) etc. @import and expression() are banned.</p>
                </div>
            </details>

            <div class="editor-actions">
                <button type="submit" class="btn btn-primary" id="save-btn">Save theme</button>
                <button type="submit" class="btn btn-secondary" id="save-as-btn"
                        formaction="<?= e(url('admin.themes.store')) ?>" formmethod="post">Save as new…</button>
                <button type="button" class="btn btn-secondary" id="reset-btn">Reset to saved</button>
                <a class="btn btn-secondary" href="<?= e(url('admin.themes.export', ['id' => $theme['id']])) ?>">Export .zip</a>
            </div>
            <input type="hidden" name="base_id" value="<?= (int) $theme['id'] ?>">
            <input type="hidden" name="name" id="save-as-name" disabled>
        </div>

        <div class="theme-preview-pane">
            <div class="preview-toolbar">
                <button type="button" class="btn btn-secondary btn-sm device-btn active" data-width="100%">Desktop</button>
                <button type="button" class="btn btn-secondary btn-sm device-btn" data-width="768px">Tablet</button>
                <button type="button" class="btn btn-secondary btn-sm device-btn" data-width="390px">Mobile</button>
                <span style="flex:1;"></span>
                <label class="form-check" style="font-size:0.85rem;"><input type="checkbox" id="preview-dark"> Preview dark</label>
            </div>
            <div class="preview-frame-wrap">
                <iframe id="preview-frame" src="<?= e(url('theme.preview')) ?>" title="Theme preview"></iframe>
            </div>
        </div>
    </div>
</form>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/theme-editor.js')) ?>"></script>
<?php View::end(); ?>
