// News editor: auto-slug, WYSIWYG on contenteditable, cover 16:9 preview,
// category quick-add.
(function () {
    'use strict';

    // ---- Auto-slug ----
    var titleInput = document.getElementById('news-title');
    var slugInput = document.getElementById('news-slug');
    if (titleInput && slugInput) {
        slugInput.addEventListener('input', function () { slugInput.dataset.touched = '1'; });
        titleInput.addEventListener('input', function () {
            if (slugInput.dataset.touched === '1') return;
            slugInput.value = titleInput.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 200);
        });
    }

    // ---- WYSIWYG ----
    var wysiwyg = document.getElementById('wysiwyg');
    var area = document.getElementById('wysiwyg-area');
    var bodyField = document.getElementById('news-body');
    var form = document.getElementById('news-form');
    if (wysiwyg && area && form) {
        wysiwyg.querySelector('.wysiwyg-toolbar').addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            e.preventDefault();
            area.focus();
            if (btn.dataset.cmd) {
                document.execCommand(btn.dataset.cmd, false, btn.dataset.value || null);
                return;
            }
            switch (btn.dataset.action) {
                case 'link': insertLink(); break;
                case 'image': document.getElementById('wysiwyg-image-input').click(); break;
                case 'table': insertTable(); break;
            }
        });

        function insertLink() {
            var url = window.prompt('Link URL (https://…):');
            if (!url) return;
            if (!/^(https?:\/\/|mailto:|\/)/i.test(url)) {
                alert('Links must start with https://, http://, mailto: or /');
                return;
            }
            document.execCommand('createLink', false, url);
        }

        function insertTable() {
            var rows = parseInt(window.prompt('Rows:', '3'), 10) || 3;
            var cols = parseInt(window.prompt('Columns:', '3'), 10) || 3;
            rows = Math.min(rows, 20); cols = Math.min(cols, 8);
            var html = '<table><thead><tr>';
            for (var c = 0; c < cols; c++) html += '<th>Header</th>';
            html += '</tr></thead><tbody>';
            for (var r = 0; r < rows - 1; r++) {
                html += '<tr>';
                for (c = 0; c < cols; c++) html += '<td>&nbsp;</td>';
                html += '</tr>';
            }
            html += '</tbody></table><p><br></p>';
            document.execCommand('insertHTML', false, html);
        }

        // Image upload
        document.getElementById('wysiwyg-image-input').addEventListener('change', function () {
            var file = this.files[0];
            this.value = '';
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fd.append('_token', wysiwyg.dataset.csrf);
            fetch(wysiwyg.dataset.uploadUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); return; }
                    area.focus();
                    document.execCommand('insertHTML', false, '<img src="' + data.url + '" alt="">');
                })
                .catch(function () { alert('Image upload failed.'); });
        });

        // Copy content into the hidden field on submit
        form.addEventListener('submit', function () {
            bodyField.value = area.innerHTML;
        });
    }

    // ---- Cover 16:9 preview ----
    var coverInput = document.getElementById('news-cover');
    var coverPreview = document.getElementById('cover-preview');
    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function () {
            var file = coverInput.files[0];
            if (!file) return;
            var img = new Image();
            img.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width = 480; canvas.height = 270; // 16:9
                var ctx = canvas.getContext('2d');
                var scale = Math.max(canvas.width / img.width, canvas.height / img.height);
                var w = img.width * scale, h = img.height * scale;
                ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
                coverPreview.innerHTML = '';
                coverPreview.appendChild(canvas);
                URL.revokeObjectURL(img.src);
            };
            img.src = URL.createObjectURL(file);
        });
    }

    // ---- Category quick-add ----
    var addBtn = document.getElementById('add-category');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var nameInput = document.getElementById('new-category-name');
            var name = nameInput.value.trim();
            if (!name) return;
            var body = new URLSearchParams();
            body.set('name', name);
            body.set('_token', wysiwyg ? wysiwyg.dataset.csrf : '');
            fetch(addBtn.dataset.url, { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); return; }
                    var select = document.getElementById('news-category');
                    var option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.name;
                    option.selected = true;
                    select.appendChild(option);
                    nameInput.value = '';
                });
        });
    }
})();
