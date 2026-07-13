// Admin default-layout builder: drag reorder, width toggle, add/remove.
(function () {
    'use strict';
    var list = document.getElementById('layout-builder');
    if (!list) return;
    var dragging = null;

    list.addEventListener('dragstart', function (e) { dragging = e.target.closest('.layout-item'); });
    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var over = e.target.closest('.layout-item');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        list.insertBefore(dragging, (e.clientY - rect.top) < rect.height / 2 ? over : over.nextSibling);
    });
    list.addEventListener('dragend', function () { dragging = null; });

    list.addEventListener('click', function (e) {
        var widthBtn = e.target.closest('.width-toggle');
        if (widthBtn) {
            var item = widthBtn.closest('.layout-item');
            var next = item.dataset.width === 'full' ? 'half' : 'full';
            item.dataset.width = next;
            widthBtn.textContent = next;
            return;
        }
        var removeBtn = e.target.closest('.remove-item');
        if (removeBtn) removeBtn.closest('.layout-item').remove();
    });

    document.getElementById('add-widget-btn').addEventListener('click', function () {
        var select = document.getElementById('add-widget-select');
        var slug = select.value;
        var name = select.options[select.selectedIndex].dataset.name;
        if (list.querySelector('[data-slug="' + slug + '"]')) return;
        var li = document.createElement('li');
        li.className = 'layout-item';
        li.dataset.slug = slug;
        li.dataset.width = 'full';
        li.draggable = true;
        li.innerHTML = '<span class="drag-handle">⠿</span><span style="flex:1;">' + name +
            '</span><button type="button" class="btn btn-secondary btn-sm width-toggle">full</button>' +
            '<button type="button" class="btn btn-danger btn-sm remove-item">&times;</button>';
        list.appendChild(li);
    });

    document.getElementById('save-layout-btn').addEventListener('click', function () {
        var items = Array.prototype.map.call(list.querySelectorAll('.layout-item'), function (li) {
            return { slug: li.dataset.slug, width: li.dataset.width };
        });
        document.getElementById('layout-items-json').value = JSON.stringify(items);
        document.getElementById('layout-form').submit();
    });
})();
