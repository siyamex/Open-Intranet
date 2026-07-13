// Admin form builder: add/edit/reorder fields, conditional visibility rules,
// serialized to a JSON hidden input on save.
(function () {
    'use strict';
    var list = document.getElementById('fb-fields');
    var form = document.getElementById('form-builder-form');
    if (!list || !form) return;
    var seq = 0;
    var dragging = null;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function addField(field) {
        seq++;
        var li = document.createElement('li');
        li.className = 'fb-field';
        li.draggable = true;
        li.dataset.id = field.id || ('f' + Date.now().toString(36) + seq);
        li.dataset.type = field.type;
        li.innerHTML =
            '<div class="fb-row">' +
            '<span class="drag-handle">⠿</span>' +
            '<span class="badge">' + esc(field.type) + '</span>' +
            '<input class="form-control fb-label" placeholder="Label" value="' + esc(field.label || '') + '">' +
            (field.type !== 'section'
                ? '<label class="form-check"><input type="checkbox" class="fb-required"' + (field.required ? ' checked' : '') + '> req.</label>'
                : '') +
            '<button type="button" class="btn btn-danger btn-sm fb-remove">×</button>' +
            '</div>' +
            (field.type === 'select'
                ? '<input class="form-control fb-options" placeholder="Options, comma separated" value="' + esc((field.options || []).join(', ')) + '">'
                : '') +
            (field.type !== 'section'
                ? '<div class="fb-condition">Show only when <select class="form-control fb-cond-field"><option value="">— always —</option></select>' +
                  ' equals <input class="form-control fb-cond-value" placeholder="value" value="' + esc(field.condition ? field.condition.value : '') + '"' +
                  (field.condition ? '' : ' disabled') + '></div>'
                : '');
        li.querySelector('.fb-remove').addEventListener('click', function () {
            li.remove();
            refreshConditionOptions();
        });
        li.querySelector('.fb-label').addEventListener('input', refreshConditionOptions);
        var condField = li.querySelector('.fb-cond-field');
        if (condField) {
            condField.dataset.selected = field.condition ? field.condition.field : '';
            condField.addEventListener('change', function () {
                li.querySelector('.fb-cond-value').disabled = condField.value === '';
            });
        }
        list.appendChild(li);
        refreshConditionOptions();
    }

    function refreshConditionOptions() {
        var fields = Array.prototype.map.call(list.querySelectorAll('.fb-field'), function (li) {
            return { id: li.dataset.id, label: li.querySelector('.fb-label').value || li.dataset.type };
        });
        list.querySelectorAll('.fb-field').forEach(function (li) {
            var select = li.querySelector('.fb-cond-field');
            if (!select) return;
            var selected = select.value || select.dataset.selected || '';
            select.innerHTML = '<option value="">— always —</option>';
            fields.forEach(function (f) {
                if (f.id === li.dataset.id) return;
                var option = document.createElement('option');
                option.value = f.id;
                option.textContent = f.label;
                if (f.id === selected) option.selected = true;
                select.appendChild(option);
            });
            select.dataset.selected = select.value;
            var valueInput = li.querySelector('.fb-cond-value');
            if (valueInput) valueInput.disabled = select.value === '';
        });
    }

    document.querySelectorAll('[data-add-field]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            addField({ type: btn.dataset.addField, label: '', required: false, options: [] });
        });
    });

    list.addEventListener('dragstart', function (e) { dragging = e.target.closest('.fb-field'); });
    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var over = e.target.closest('.fb-field');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        list.insertBefore(dragging, (e.clientY - rect.top) < rect.height / 2 ? over : over.nextSibling);
    });
    list.addEventListener('dragend', function () { dragging = null; });

    form.addEventListener('submit', function () {
        var fields = Array.prototype.map.call(list.querySelectorAll('.fb-field'), function (li) {
            var condSelect = li.querySelector('.fb-cond-field');
            var condValue = li.querySelector('.fb-cond-value');
            var optionsInput = li.querySelector('.fb-options');
            return {
                id: li.dataset.id,
                type: li.dataset.type,
                label: li.querySelector('.fb-label').value || li.dataset.type,
                required: !!(li.querySelector('.fb-required') && li.querySelector('.fb-required').checked),
                options: optionsInput ? optionsInput.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [],
                condition: (condSelect && condSelect.value && condValue.value)
                    ? { field: condSelect.value, value: condValue.value }
                    : null
            };
        });
        document.getElementById('fields-json').value = JSON.stringify(fields);
    });

    // approver type visibility
    var approverType = document.getElementById('approver-type');
    function syncApprover() {
        document.getElementById('approver-user-row').style.display = approverType.value === 'user' ? '' : 'none';
        document.getElementById('approver-role-row').style.display = approverType.value === 'role' ? '' : 'none';
    }
    approverType.addEventListener('change', syncApprover);
    syncApprover();

    // load existing fields
    JSON.parse(list.dataset.fields || '[]').forEach(addField);
})();
