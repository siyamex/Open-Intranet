// Widgetized dashboard: lazy-load each widget via fetch with a skeleton
// placeholder, then (if personalization is enabled) drag-reorder,
// remove, add and reset the layout.
(function () {
    'use strict';
    var grid = document.getElementById('widget-grid');
    if (!grid) return;
    var csrf = grid.dataset.csrf;

    // ---- Lazy-load each widget ----
    function loadSlot(slot) {
        var slug = slot.dataset.slug;
        fetch(grid.dataset.widgetUrl + '/' + slug, { headers: { 'Accept': 'text/html' } })
            .then(function (r) { return r.ok ? r.text() : ''; })
            .then(function (html) {
                var body = slot.querySelector('.widget-body');
                if (!html.trim()) {
                    slot.remove();
                    return;
                }
                body.innerHTML = html;
                // re-run inline sparkline/poll-bar animations for widgets that need JS
                body.querySelectorAll('.poll-bar-fill').forEach(function (bar) {
                    requestAnimationFrame(function () { setTimeout(function () { bar.style.width = bar.dataset.width; }, 60); });
                });
            })
            .catch(function () { slot.remove(); });
    }
    grid.querySelectorAll('.widget-slot').forEach(loadSlot);

    // ---- Personalization mode ----
    var customizeBtn = document.getElementById('widget-customize');
    if (!customizeBtn) return; // personalization disabled by admin
    var addBtn = document.getElementById('widget-add');
    var resetBtn = document.getElementById('widget-reset');
    var doneBtn = document.getElementById('widget-done');
    var pickerModal = document.getElementById('widget-picker-modal');
    var pickerList = document.getElementById('widget-picker-list');
    var editing = false;
    var dragging = null;

    function setEditing(on) {
        editing = on;
        grid.classList.toggle('editing', on);
        [customizeBtn].forEach(function (b) { b.hidden = on; });
        [addBtn, resetBtn, doneBtn].forEach(function (b) { b.hidden = !on; });
        grid.querySelectorAll('.widget-slot').forEach(function (slot) {
            slot.draggable = on;
            slot.querySelector('.widget-remove-handle').hidden = !on;
        });
    }
    customizeBtn.addEventListener('click', function () { setEditing(true); });
    doneBtn.addEventListener('click', function () { setEditing(false); saveLayout(); });

    grid.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.widget-remove');
        if (removeBtn && editing) {
            removeBtn.closest('.widget-slot').remove();
        }
    });

    grid.addEventListener('dragstart', function (e) {
        if (!editing) return;
        dragging = e.target.closest('.widget-slot');
    });
    grid.addEventListener('dragover', function (e) {
        if (!editing || !dragging) return;
        e.preventDefault();
        var over = e.target.closest('.widget-slot');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        var before = (e.clientY - rect.top) < rect.height / 2;
        grid.insertBefore(dragging, before ? over : over.nextSibling);
    });
    grid.addEventListener('dragend', function () { dragging = null; });

    resetBtn.addEventListener('click', function () {
        fetch(grid.dataset.resetUrl, { method: 'POST', headers: { 'X-CSRF-Token': csrf } })
            .then(function () { window.location.reload(); });
    });

    addBtn.addEventListener('click', function () {
        var current = Array.prototype.map.call(grid.querySelectorAll('.widget-slot'), function (s) { return s.dataset.slug; });
        pickerList.innerHTML = '<li class="text-muted">Loading…</li>';
        pickerModal.showModal();
        fetch(grid.dataset.catalogUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                pickerList.innerHTML = '';
                data.widgets.forEach(function (w) {
                    if (current.indexOf(w.slug) !== -1) return;
                    var li = document.createElement('li');
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-secondary btn-block';
                    btn.textContent = w.name;
                    btn.addEventListener('click', function () {
                        var slot = document.createElement('div');
                        slot.className = 'widget-slot widget-full';
                        slot.dataset.slug = w.slug;
                        slot.dataset.width = 'full';
                        slot.draggable = true;
                        slot.innerHTML = '<div class="widget-remove-handle"><span class="drag-handle">⠿</span>' +
                            '<span class="widget-name">' + w.name + '</span><button type="button" class="widget-remove" aria-label="Remove widget">&times;</button></div>' +
                            '<div class="widget-body"><div class="widget-skeleton"><div class="skel-line" style="width:40%;"></div><div class="skel-block"></div></div></div>';
                        grid.appendChild(slot);
                        loadSlot(slot);
                        pickerModal.close();
                    });
                    li.appendChild(btn);
                    pickerList.appendChild(li);
                });
                if (!pickerList.children.length) pickerList.innerHTML = '<li class="text-muted">All available widgets are already on your dashboard.</li>';
            });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });

    function saveLayout() {
        var items = Array.prototype.map.call(grid.querySelectorAll('.widget-slot'), function (slot) {
            return { slug: slot.dataset.slug, width: slot.dataset.width };
        });
        fetch(grid.dataset.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ items: items })
        });
    }
})();
