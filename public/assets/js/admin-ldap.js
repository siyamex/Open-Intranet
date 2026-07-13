// LDAP admin: add group mappings, live "test connection" button.
(function () {
    'use strict';
    var addBtn = document.getElementById('add-group-map');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'filter-bar';
            row.innerHTML = document.querySelector('#group-map .filter-bar')
                ? document.querySelector('#group-map .filter-bar').innerHTML
                : '<input class="form-control" name="group_dn[]" placeholder="cn=Group,ou=groups,dc=example,dc=com" style="flex:1;">';
            row.querySelectorAll('input, select').forEach(function (el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
            });
            document.getElementById('group-map').appendChild(row);
        });
    }

    var testBtn = document.getElementById('test-ldap');
    if (testBtn) {
        testBtn.addEventListener('click', function () {
            var box = document.getElementById('ldap-test-results');
            box.innerHTML = '<p class="text-muted">Testing…</p>';
            fetch(testBtn.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var html = '';
                    if (!data.ok) {
                        html += '<p class="form-error">' + (data.error || (data.errors || []).join('; ')) + '</p>';
                    } else {
                        html += '<p style="color:var(--color-success);">Connected successfully.</p>';
                    }
                    if (data.preview && data.preview.length) {
                        html += '<div class="table-wrap"><table class="table"><thead><tr><th>Action</th><th>Name</th><th>Email</th></tr></thead><tbody>';
                        data.preview.forEach(function (row) {
                            html += '<tr><td>' + row.action + '</td><td>' + row.name + '</td><td>' + row.email + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                    }
                    box.innerHTML = html;
                })
                .catch(function (err) {
                    box.innerHTML = '<p class="form-error">Test failed: ' + err + '</p>';
                });
        });
    }
})();
