// OpenIntranet base JS — toast dismissal. Modules add their own files.
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var close = event.target.closest('.toast-close');
        if (close) {
            var toast = close.closest('.toast');
            if (toast) toast.remove();
        }
    });

    // Auto-dismiss toasts after 6 seconds
    document.querySelectorAll('.toast').forEach(function (toast) {
        setTimeout(function () {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 400);
        }, 6000);
    });
})();
