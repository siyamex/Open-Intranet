// Settings: drag-reorder homepage sections.
(function () {
    'use strict';
    var list = document.getElementById('section-sort');
    if (!list) return;
    var orderField = document.getElementById('sections-order');
    var dragging = null;

    function syncOrder() {
        var order = Array.prototype.map.call(list.querySelectorAll('li'), function (li) {
            return li.dataset.section;
        });
        orderField.value = JSON.stringify(order);
    }
    syncOrder();

    list.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('li');
    });
    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var over = e.target.closest('li');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        var before = (e.clientY - rect.top) < rect.height / 2;
        list.insertBefore(dragging, before ? over : over.nextSibling);
    });
    list.addEventListener('drop', function (e) { e.preventDefault(); syncOrder(); });
    list.addEventListener('dragend', function () { dragging = null; syncOrder(); });

    var form = list.closest('form');
    if (form) form.addEventListener('submit', syncOrder);
})();
