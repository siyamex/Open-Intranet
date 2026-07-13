// Emergency banner: dismiss / acknowledge.
(function () {
    'use strict';
    var csrf = document.body.dataset.csrf;

    document.addEventListener('click', function (e) {
        var dismiss = e.target.closest('.eb-dismiss');
        if (dismiss) {
            var banner = dismiss.closest('.emergency-banner');
            fetch(banner.dataset.dismissUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf }
            }).finally(function () { banner.remove(); });
            return;
        }
        var ack = e.target.closest('.eb-ack');
        if (ack) {
            var banner2 = ack.closest('.emergency-banner');
            fetch(banner2.dataset.ackUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf }
            }).finally(function () { banner2.remove(); });
        }
    });
})();
