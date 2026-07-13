// Conditional field visibility on request forms: show X when Y = value.
(function () {
    'use strict';
    var form = document.getElementById('request-form');
    if (!form) return;

    function valueOf(fieldId) {
        var input = form.querySelector('[data-field="' + fieldId + '"]');
        if (!input) return '';
        if (input.type === 'checkbox') return input.checked ? 'yes' : 'no';
        return input.value;
    }

    function sync() {
        form.querySelectorAll('[data-cond-field]').forEach(function (el) {
            var show = valueOf(el.dataset.condField) === el.dataset.condValue;
            el.style.display = show ? '' : 'none';
            // hidden fields shouldn't block native validation
            el.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = !show;
            });
        });
    }
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    sync();
})();
