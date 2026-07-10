// Admin quick links: modal add/edit, palette, drag order, sparklines.
(function () {
    'use strict';

    // ---- Sparklines ----
    document.querySelectorAll('.ql-spark').forEach(function (canvas) {
        var series = JSON.parse(canvas.dataset.series || '[]');
        var ctx = canvas.getContext('2d');
        var w = canvas.width, h = canvas.height;
        var max = Math.max.apply(null, series.concat([1]));
        ctx.clearRect(0, 0, w, h);
        ctx.beginPath();
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#4f46e5';
        ctx.lineWidth = 1.5;
        series.forEach(function (v, i) {
            var x = series.length > 1 ? (i / (series.length - 1)) * (w - 4) + 2 : w / 2;
            var y = h - 3 - (v / max) * (h - 6);
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();
    });

    var modal = document.getElementById('ql-modal');
    var form = document.getElementById('ql-form');
    if (!modal || !form) return;
    var storeUrl = form.action;

    // ---- Palette ----
    var bgInput = document.getElementById('ql-bg-color');
    document.querySelectorAll('#ql-palette .swatch').forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            bgInput.value = swatch.dataset.color;
            document.getElementById('ql-color-custom').value = swatch.dataset.color;
        });
    });
    document.getElementById('ql-color-custom').addEventListener('input', function () {
        bgInput.value = this.value;
    });

    // ---- Icon type toggle ----
    function syncIconRows() {
        var upload = document.getElementById('ql-icon-up').checked;
        document.getElementById('ql-icon-library-row').style.display = upload ? 'none' : 'flex';
        document.getElementById('ql-icon-upload-row').hidden = !upload;
    }
    document.getElementById('ql-icon-lib').addEventListener('change', syncIconRows);
    document.getElementById('ql-icon-up').addEventListener('change', syncIconRows);

    // ---- Add / edit ----
    var addBtn = document.querySelector('[data-ql-add]');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            form.reset();
            form.action = storeUrl;
            document.getElementById('ql-form-method').value = 'POST';
            document.getElementById('ql-modal-title').textContent = 'Add quick link';
            document.getElementById('ql-icon-library').value = '';
            document.getElementById('ql-icon-library-preview').textContent = '';
            bgInput.value = '#4f46e5';
            syncIconRows();
            modal.showModal();
        });
    }
    document.querySelectorAll('[data-ql-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var link = JSON.parse(btn.dataset.qlEdit);
            form.reset();
            form.action = storeUrl + '/' + link.id;
            document.getElementById('ql-form-method').value = 'PUT';
            document.getElementById('ql-modal-title').textContent = 'Edit quick link';
            document.getElementById('ql-title').value = link.title || '';
            document.getElementById('ql-url').value = link.url || '';
            document.getElementById('ql-description').value = link.description || '';
            var isUpload = link.icon_type === 'upload';
            document.getElementById('ql-icon-up').checked = isUpload;
            document.getElementById('ql-icon-lib').checked = !isUpload;
            document.getElementById('ql-icon-library').value = isUpload ? '' : (link.icon_value || '');
            document.getElementById('ql-icon-library-preview').textContent = isUpload ? '' : (link.icon_value || '');
            bgInput.value = link.bg_color || '#4f46e5';
            document.getElementById('ql-color-custom').value = link.bg_color || '#4f46e5';
            document.getElementById('ql-active').checked = String(link.is_active) === '1';
            document.getElementById('ql-newtab').checked = String(link.open_new_tab) === '1';
            var visible = [];
            try { visible = JSON.parse(link.visible_to || '[]') || []; } catch (e) { visible = []; }
            document.querySelectorAll('#ql-roles input').forEach(function (cb) {
                cb.checked = visible.indexOf(cb.value) !== -1;
            });
            syncIconRows();
            modal.showModal();
        });
    });
    form.addEventListener('submit', function () {
        var method = document.getElementById('ql-form-method');
        if (method.value === 'POST') method.disabled = true;
    });

    // ---- Global drag order (grid view) ----
    var grid = document.getElementById('admin-ql-grid');
    if (!grid) return;
    var dragging = null;
    grid.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('.ql-tile');
    });
    grid.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragging) return;
        var over = e.target.closest('.ql-tile');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        var before = (e.clientX - rect.left) < rect.width / 2;
        grid.insertBefore(dragging, before ? over : over.nextSibling);
    });
    grid.addEventListener('drop', function (e) {
        e.preventDefault();
        var order = Array.prototype.map.call(grid.querySelectorAll('.ql-tile'), function (t) {
            return parseInt(t.dataset.id, 10);
        });
        fetch(grid.dataset.reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': grid.dataset.csrf },
            body: JSON.stringify({ order: order })
        });
    });
    grid.addEventListener('dragend', function () { dragging = null; });
})();
