// Admin menu manager: modal add/edit, drag-drop reorder with one-level nesting.
(function () {
    'use strict';

    var modal = document.getElementById('menu-modal');
    var form = document.getElementById('menu-form');
    var storeUrl = form ? form.action : '';

    // ---- Icon picker (reusable) ----
    window.IconPicker = (function () {
        var picker = document.getElementById('icon-picker-modal');
        var callback = null;
        if (picker) {
            document.getElementById('icon-picker-search').addEventListener('input', function () {
                var term = this.value.toLowerCase();
                picker.querySelectorAll('.icon-cell').forEach(function (cell) {
                    cell.hidden = term !== '' && cell.dataset.iconName.indexOf(term) === -1;
                });
            });
            picker.querySelectorAll('.icon-cell').forEach(function (cell) {
                cell.addEventListener('click', function () {
                    if (callback) callback(cell.dataset.iconName);
                    picker.close();
                });
            });
        }
        return {
            open: function (cb) {
                callback = cb;
                if (picker) picker.showModal();
            }
        };
    })();

    document.querySelectorAll('[data-icon-picker]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.iconPicker);
            window.IconPicker.open(function (name) {
                input.value = name;
                var preview = document.getElementById(btn.dataset.iconPicker + '-preview');
                if (preview) preview.textContent = name;
            });
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('dialog').close();
        });
    });

    // ---- Add / edit ----
    var addBtn = document.querySelector('[data-menu-add]');
    if (addBtn && modal) {
        addBtn.addEventListener('click', function () {
            form.reset();
            form.action = storeUrl;
            document.getElementById('menu-form-method').value = 'POST';
            document.getElementById('menu-modal-title').textContent = 'Add menu item';
            document.getElementById('menu-parent-id').value = '';
            document.getElementById('menu-icon').value = '';
            document.getElementById('menu-icon-preview').textContent = '';
            modal.showModal();
        });
    }
    document.querySelectorAll('[data-menu-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = JSON.parse(btn.dataset.menuEdit);
            form.reset();
            form.action = storeUrl + '/' + item.id;
            document.getElementById('menu-form-method').value = 'PUT';
            document.getElementById('menu-modal-title').textContent = 'Edit menu item';
            document.getElementById('menu-label').value = item.label || '';
            document.getElementById('menu-icon').value = item.icon || '';
            document.getElementById('menu-icon-preview').textContent = item.icon || '';
            document.getElementById('menu-route').value = item.route_name || '';
            document.getElementById('menu-url').value = item.url || '';
            document.getElementById('menu-target').value = item.target || '_self';
            document.getElementById('menu-parent-id').value = item.parent_id || '';
            document.getElementById('menu-enabled').checked = String(item.enabled) === '1';
            var visible = [];
            try { visible = JSON.parse(item.visible_to || '[]') || []; } catch (e) { visible = []; }
            document.querySelectorAll('#menu-roles input').forEach(function (cb) {
                cb.checked = visible.indexOf(cb.value) !== -1;
            });
            modal.showModal();
        });
    });

    // PUT spoofing: rewrite the _method input before submit
    if (form) {
        form.addEventListener('submit', function () {
            var method = document.getElementById('menu-form-method');
            if (method.value === 'POST') method.disabled = true;
            else method.name = '_method';
        });
    }

    // ---- Drag & drop reorder with one-level nesting ----
    var tree = document.getElementById('menu-tree');
    if (!tree) return;
    var dragging = null;

    tree.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('.menu-node');
        if (dragging) e.dataTransfer.effectAllowed = 'move';
    });

    tree.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragging) return;
        var overRow = e.target.closest('.menu-row');
        if (!overRow) return;
        var overNode = overRow.parentElement;
        if (overNode === dragging || dragging.contains(overNode)) return;

        var rect = overRow.getBoundingClientRect();
        var y = e.clientY - rect.top;
        var isTopLevel = overNode.parentElement === tree;
        var draggingHasChildren = dragging.querySelector('.menu-children .menu-node') !== null;

        if (isTopLevel && !draggingHasChildren && y > rect.height * 0.25 && y < rect.height * 0.75) {
            // middle zone of a top-level row -> nest under it
            overNode.querySelector('.menu-children').appendChild(dragging);
        } else {
            var parentList = overNode.parentElement;
            if (!isTopLevel && draggingHasChildren) parentList = tree; // items with children stay top-level
            parentList.insertBefore(dragging, y < rect.height / 2 ? overNode : overNode.nextSibling);
        }
    });

    tree.addEventListener('drop', function (e) { e.preventDefault(); save(); });
    tree.addEventListener('dragend', function () { dragging = null; });

    function save() {
        var items = [];
        var sort = 10;
        tree.querySelectorAll(':scope > .menu-node').forEach(function (node) {
            items.push({ id: parseInt(node.dataset.id, 10), parent_id: null, sort: sort });
            sort += 10;
            node.querySelectorAll(':scope > .menu-children > .menu-node').forEach(function (child) {
                items.push({ id: parseInt(child.dataset.id, 10), parent_id: parseInt(node.dataset.id, 10), sort: sort });
                sort += 10;
            });
        });
        fetch(tree.dataset.reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': tree.dataset.csrf },
            body: JSON.stringify({ items: items })
        });
    }
})();
