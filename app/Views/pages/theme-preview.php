<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Theme preview</title>
<?php partial('partials/head-assets'); ?>
<style>
    body { padding: 0; }
    .tp-section { padding: 1rem; }
    .tp-navbar-mock {
        height: var(--navbar-height);
        background: var(--navbar-bg);
        border-bottom: 1px solid var(--color-border);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0 1rem;
        font-weight: 700;
    }
    .tp-login-mock {
        background: var(--login-bg, var(--color-bg));
        background-size: cover;
        background-position: center;
        padding: 2rem 1rem;
        display: flex;
        justify-content: center;
        position: relative;
    }
    .tp-login-mock::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, var(--login-overlay, 0));
    }
    .tp-login-mock .auth-card { max-width: 320px; position: relative; }
</style>
</head>
<body>
<div class="tp-navbar-mock">
    <span class="brand-mark" style="width:28px;height:28px;border-radius:var(--radius-sm);background:var(--color-primary);color:var(--color-primary-contrast);display:inline-flex;align-items:center;justify-content:center;">O</span>
    Navbar
    <span style="flex:1;"></span>
    <span class="badge">3</span>
</div>

<div class="tp-section">
    <h1>Dashboard sample</h1>
    <p class="text-muted">Body text with a <a href="#">link</a>, <strong>bold</strong> and <em>emphasis</em>.</p>

    <div class="stat-grid">
        <div class="card stat-card"><span class="stat-value">128</span><span class="stat-label">Active users</span></div>
        <div class="card stat-card"><span class="stat-value">42</span><span class="stat-label">Logins today</span></div>
        <div class="card stat-card"><span class="stat-value">7</span><span class="stat-label">News this month</span></div>
    </div>

    <div class="card">
        <h2>Card heading</h2>
        <p>Cards use the surface token with border and shadow tokens.</p>
        <div class="checkbox-row">
            <button class="btn btn-primary">Primary</button>
            <button class="btn btn-secondary">Secondary</button>
            <button class="btn btn-danger">Danger</button>
        </div>
        <div class="form-group" style="max-width:280px; margin-top:0.9rem;">
            <label class="form-label">Input field</label>
            <input class="form-control" placeholder="Type here…">
        </div>
        <span class="chip" style="background:var(--color-primary);">Category chip</span>
        <p><span style="color:var(--color-success);">Success</span> · <span style="color:var(--color-warning);">Warning</span> · <span style="color:var(--color-danger);">Danger</span></p>
    </div>

    <div class="news-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
        <article class="news-card">
            <span class="news-card-media"><span class="news-card-placeholder">📰</span></span>
            <div class="news-card-body">
                <span class="chip" style="background:var(--color-accent);">News</span>
                <h3><a href="#">A sample news headline</a></h3>
                <p class="text-muted news-excerpt">Short excerpt of an article to preview typography and spacing.</p>
            </div>
        </article>
    </div>
</div>

<div class="tp-login-mock">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo">O</div>
            <h1 class="auth-title">Login sample</h1>
        </div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-control"></div>
        <button class="btn btn-primary btn-block">Sign in</button>
    </div>
</div>

<script src="<?= e(asset('js/theme-preview.js')) ?>"></script>
</body>
</html>
