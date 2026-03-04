(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var notice = e.target.closest('.imn-w2-low-credits-notice .notice-dismiss');
        if (!notice) return;

        var container = notice.closest('.imn-w2-low-credits-notice');
        var nonce = container ? container.dataset.nonce : '';

        var data = new FormData();
        data.append('action', 'imn_w2_dismiss_low_credits');
        data.append('nonce', nonce);

        fetch(imn_w2_low_credits.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        });
    });
})();
