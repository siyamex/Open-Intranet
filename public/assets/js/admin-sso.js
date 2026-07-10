// Admin SSO: type presets, drag-sort, copy redirect URI, live config test.
(function () {
    'use strict';

    // ---- Type presets on the provider form ----
    var typeSelect = document.getElementById('type');
    if (typeSelect) {
        var hints = {
            google: 'Create an OAuth 2.0 Web client in Google Cloud Console. Discovery is preset.',
            microsoft: 'Create an App Registration in Microsoft Entra ID. Set the tenant below.',
            oidc: 'Any OpenID Connect IdP (Keycloak, Okta, Auth0, ...). Provide the discovery URL or issuer.'
        };
        var apply = function () {
            var t = typeSelect.value;
            document.getElementById('type-hint').textContent = hints[t] || '';
            document.getElementById('group-discovery').style.display = (t === 'oidc') ? '' : 'none';
            document.getElementById('group-tenant').style.display = (t === 'google') ? 'none' : '';
            var scopes = document.getElementById('scopes');
            if (scopes && !scopes.value) scopes.value = 'openid profile email';
        };
        typeSelect.addEventListener('change', apply);
        apply();
    }

    // ---- Test configuration ----
    var testBtn = document.getElementById('test-config');
    if (testBtn) {
        testBtn.addEventListener('click', function () {
            var box = document.getElementById('test-results');
            box.innerHTML = '<p class="text-muted">Testing…</p>';
            fetch(testBtn.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var html = '<div class="table-wrap"><table class="table"><tbody>';
                    (data.results || []).forEach(function (row) {
                        html += '<tr><td>' + (row.ok ? '✅' : '❌') + '</td><td><strong>' +
                            escapeHtml(row.label) + '</strong></td><td>' + escapeHtml(row.detail) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    box.innerHTML = html;
                })
                .catch(function (err) {
                    box.innerHTML = '<p class="form-error">Test failed: ' + escapeHtml(String(err)) + '</p>';
                });
        });
    }

    // ---- Copy redirect URI ----
    document.querySelectorAll('.copy-uri').forEach(function (el) {
        el.addEventListener('click', function () {
            navigator.clipboard.writeText(el.textContent.trim()).then(function () {
                var original = el.textContent;
                el.textContent = 'Copied!';
                setTimeout(function () { el.textContent = original; }, 1200);
            });
        });
    });

    // ---- Drag-sort provider rows ----
    var table = document.getElementById('sso-table');
    if (table) {
        var tbody = table.querySelector('tbody');
        var dragging = null;
        tbody.addEventListener('dragstart', function (e) {
            dragging = e.target.closest('tr');
            e.dataTransfer.effectAllowed = 'move';
        });
        tbody.addEventListener('dragover', function (e) {
            e.preventDefault();
            var row = e.target.closest('tr');
            if (!row || row === dragging) return;
            var rect = row.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            tbody.insertBefore(dragging, after ? row.nextSibling : row);
        });
        tbody.addEventListener('drop', function (e) { e.preventDefault(); saveOrder(); });
        tbody.addEventListener('dragend', function () { dragging = null; });

        function saveOrder() {
            var order = Array.prototype.map.call(tbody.querySelectorAll('tr'), function (tr) {
                return parseInt(tr.dataset.id, 10);
            });
            fetch(table.dataset.orderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': table.dataset.csrf
                },
                body: JSON.stringify({ order: order })
            });
        }
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
})();
