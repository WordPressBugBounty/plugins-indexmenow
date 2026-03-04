(function () {
    'use strict';

    var RATE_LIMIT_MS = 5000; // 5 seconds cooldown after successful push.
    var isProcessing = false;

    document.addEventListener('click', function (e) {
        var barItem = e.target.closest('#wp-admin-bar-imn-w2-push');
        if (!barItem) return;

        e.preventDefault();

        if (isProcessing) return;

        var postId = imn_w2_bar.post_id;
        var nonce = imn_w2_bar.nonce;
        var labelEl = barItem.querySelector('.ab-label');
        var originalLabel = labelEl ? labelEl.textContent : 'IndexMeNow';

        if (!postId || !nonce) {
            console.error('IndexMeNow: Missing post ID or nonce');
            return;
        }

        isProcessing = true;
        barItem.classList.remove('imn-w2-success', 'imn-w2-error');
        barItem.classList.add('imn-w2-pushing');
        if (labelEl) labelEl.textContent = imn_w2_bar.i18n.pushing;

        var data = new FormData();
        data.append('action', 'imn_w2_manual_push');
        data.append('post_id', postId);
        data.append('nonce', nonce);

        fetch(imn_w2_bar.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                barItem.classList.remove('imn-w2-pushing');

                if (res.success) {
                    barItem.classList.add('imn-w2-success');
                    if (labelEl) labelEl.textContent = imn_w2_bar.i18n.success;

                    // Reset after delay.
                    setTimeout(function () {
                        barItem.classList.remove('imn-w2-success');
                        if (labelEl) labelEl.textContent = originalLabel;
                        isProcessing = false;
                    }, RATE_LIMIT_MS);
                } else {
                    barItem.classList.add('imn-w2-error');
                    if (labelEl) labelEl.textContent = imn_w2_bar.i18n.error;

                    // Show error in console.
                    console.error('IndexMeNow:', res.data.message || imn_w2_bar.i18n.error_generic);

                    // Reset after delay.
                    setTimeout(function () {
                        barItem.classList.remove('imn-w2-error');
                        if (labelEl) labelEl.textContent = originalLabel;
                        isProcessing = false;
                    }, 3000);
                }
            })
            .catch(function () {
                barItem.classList.remove('imn-w2-pushing');
                barItem.classList.add('imn-w2-error');
                if (labelEl) labelEl.textContent = imn_w2_bar.i18n.error;

                console.error('IndexMeNow:', imn_w2_bar.i18n.error_network);

                // Reset after delay.
                setTimeout(function () {
                    barItem.classList.remove('imn-w2-error');
                    if (labelEl) labelEl.textContent = originalLabel;
                    isProcessing = false;
                }, 3000);
            });
    });
})();
