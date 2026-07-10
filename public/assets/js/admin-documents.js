// Admin documents: edit modal.
(function () {
    'use strict';
    var modal = document.getElementById('doc-modal');
    var form = document.getElementById('doc-form');
    if (!modal || !form) return;

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });

    var base = document.querySelector('[data-doc-edit]');
    document.querySelectorAll('[data-doc-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var doc = JSON.parse(btn.dataset.docEdit);
            form.action = window.location.pathname.replace(/\/$/, '') + '/' + doc.id;
            document.getElementById('edit-title').value = doc.title || '';
            document.getElementById('edit-description').value = doc.description || '';
            document.getElementById('edit-category').value = doc.category_id || '';
            document.getElementById('edit-gazette').checked = String(doc.is_gazette) === '1';
            var visible = [];
            try { visible = JSON.parse(doc.visible_to || '[]') || []; } catch (e) { visible = []; }
            document.querySelectorAll('#edit-roles input').forEach(function (cb) {
                cb.checked = visible.indexOf(cb.value) !== -1;
            });
            modal.showModal();
        });
    });
})();
