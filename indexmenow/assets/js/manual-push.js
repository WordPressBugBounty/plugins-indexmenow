(function () {
    'use strict';

    var RATE_LIMIT_MS = 5000; // 5 seconds cooldown after successful push.

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.imn-w2-push-btn');
        if (!btn) return;

        var postId = btn.dataset.postId;
        var nonce = btn.dataset.nonce;
        var statusEl = btn.parentNode.querySelector('.imn-w2-push-status');
        var spinner = btn.parentNode.querySelector('.imn-w2-push-spinner');

        btn.disabled = true;
        statusEl.style.display = 'none';
        statusEl.className = 'imn-w2-push-status';
        spinner.classList.add('is-active');

        var data = new FormData();
        data.append('action', 'imn_w2_manual_push');
        data.append('post_id', postId);
        data.append('nonce', nonce);

        fetch(imn_w2.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                spinner.classList.remove('is-active');
                statusEl.style.display = 'inline-block';

                if (res.success) {
                    statusEl.classList.add('imn-w2-push-status--success');
                    statusEl.textContent = res.data.message;
                    // Rate limit: keep button disabled for a few seconds after success.
                    setTimeout(function () {
                        btn.disabled = false;
                    }, RATE_LIMIT_MS);
                } else {
                    statusEl.classList.add('imn-w2-push-status--error');
                    statusEl.textContent = res.data.message || imn_w2.i18n.error_generic;
                    btn.disabled = false;
                }
            })
            .catch(function () {
                spinner.classList.remove('is-active');
                statusEl.style.display = 'inline-block';
                statusEl.classList.add('imn-w2-push-status--error');
                statusEl.textContent = imn_w2.i18n.error_network;
                btn.disabled = false;
            });
    });
})();
