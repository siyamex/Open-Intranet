// Poll builder: add + drag-order options.
(function () {
    'use strict';
    var list = document.getElementById('poll-options');
    if (!list) return;
    var dragging = null;

    document.getElementById('add-poll-option').addEventListener('click', function () {
        var li = document.createElement('li');
        li.draggable = true;
        li.innerHTML = list.querySelector('li').innerHTML;
        var input = li.querySelector('input');
        input.value = '';
        input.required = false;
        input.placeholder = 'Option ' + (list.children.length + 1);
        list.appendChild(li);
        input.focus();
    });

    list.addEventListener('dragstart', function (e) { dragging = e.target.closest('li'); });
    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var over = e.target.closest('li');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        list.insertBefore(dragging, (e.clientY - rect.top) < rect.height / 2 ? over : over.nextSibling);
    });
    list.addEventListener('dragend', function () { dragging = null; });
})();
