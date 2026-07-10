// Reusable vanilla JS components.
(function () {
    'use strict';

    // ---- Searchable <select class="searchable"> --------------------------
    // Wraps a native select: adds a filter box above the open list.
    document.querySelectorAll('select.searchable').forEach(function (select) {
        if (select.options.length <= 8) return; // small lists stay native

        var wrap = document.createElement('div');
        wrap.className = 'ss-wrap';
        select.parentNode.insertBefore(wrap, select);

        var display = document.createElement('button');
        display.type = 'button';
        display.className = 'form-control ss-display';
        display.textContent = select.selectedIndex >= 0 ? select.options[select.selectedIndex].text : '— select —';

        var panel = document.createElement('div');
        panel.className = 'ss-panel';
        panel.hidden = true;

        var filter = document.createElement('input');
        filter.type = 'search';
        filter.className = 'form-control ss-filter';
        filter.placeholder = 'Type to filter…';

        var list = document.createElement('ul');
        list.className = 'ss-list';

        var rebuild = function (term) {
            list.innerHTML = '';
            term = (term || '').toLowerCase();
            Array.prototype.forEach.call(select.options, function (opt) {
                if (term && opt.text.toLowerCase().indexOf(term) === -1) return;
                var li = document.createElement('li');
                li.textContent = opt.text;
                li.className = opt.selected ? 'active' : '';
                li.addEventListener('click', function () {
                    select.value = opt.value;
                    display.textContent = opt.text;
                    panel.hidden = true;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                list.appendChild(li);
            });
        };

        display.addEventListener('click', function () {
            panel.hidden = !panel.hidden;
            if (!panel.hidden) {
                filter.value = '';
                rebuild('');
                filter.focus();
            }
        });
        filter.addEventListener('input', function () { rebuild(filter.value); });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) panel.hidden = true;
        });

        panel.appendChild(filter);
        panel.appendChild(list);
        wrap.appendChild(display);
        wrap.appendChild(panel);
        select.hidden = true;
        wrap.appendChild(select);
    });
})();
