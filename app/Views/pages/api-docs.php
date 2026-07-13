<div class="page-head">
    <h1>API Documentation</h1>
    <p class="text-muted">REST API v1 — create a personal access token under <a href="<?= e(url('profile.security')) ?>">Profile → Security</a>, then send it as <code>Authorization: Bearer selector.secret</code>.</p>
</div>

<div class="card">
    <h2>Quick example</h2>
    <pre class="wiki-diff">curl -H "Authorization: Bearer &lt;your-token&gt;" <?= e(base_url('api/v1/me')) ?></pre>
    <p>Every response follows the same envelope: <code>{"data": …, "meta": {…}, "error": null}</code>. On failure, <code>data</code> is null and <code>error</code> holds <code>{code, message}</code>.</p>
</div>

<div class="card">
    <h2>Endpoints</h2>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Method</th><th>Path</th><th>Scope</th><th>Description</th></tr></thead>
        <tbody>
        <tr><td>GET</td><td><code>/api/v1/me</code></td><td>read</td><td>Current authenticated user</td></tr>
        <tr><td>GET</td><td><code>/api/v1/users</code></td><td>read</td><td>List active users (paginated)</td></tr>
        <tr><td>GET</td><td><code>/api/v1/directory?q=</code></td><td>read</td><td>Search the employee directory</td></tr>
        <tr><td>GET</td><td><code>/api/v1/news</code></td><td>read</td><td>List published news</td></tr>
        <tr><td>GET</td><td><code>/api/v1/news/{slug}</code></td><td>read</td><td>Get one article</td></tr>
        <tr><td>GET</td><td><code>/api/v1/documents</code></td><td>read</td><td>List documents (metadata + permission-checked download links)</td></tr>
        <tr><td>GET</td><td><code>/api/v1/events</code></td><td>read</td><td>List upcoming events</td></tr>
        <tr><td>GET</td><td><code>/api/v1/quick-links</code></td><td>read</td><td>The token owner's quick links</td></tr>
        </tbody>
    </table>
    </div>
    <p><a class="btn btn-secondary btn-sm" href="<?= e(url('api.docs.spec')) ?>" target="_blank" rel="noopener">Raw OpenAPI 3 spec (openapi.json)</a></p>
</div>

<div class="card">
    <h2>Rate limits &amp; pagination</h2>
    <p>Each token is limited to 120 requests/minute. List endpoints accept <code>?page=</code> and <code>?per_page=</code> (max 100) and return a <code>meta</code> block with <code>page, per_page, total, total_pages</code>.</p>
</div>
