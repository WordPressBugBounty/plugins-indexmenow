(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var link = e.target.closest('.imn-w2-row-push');
        if (!link) return;

        e.preventDefault();

        if (link.classList.contains('imn-w2-pushing')) return;

        var postId = link.dataset.postId;
        var nonce = link.dataset.nonce;
        var originalText = link.textContent;

        if (!postId || !nonce) return;

        link.classList.add('imn-w2-pushing');
        link.textContent = imn_w2_posts.i18n.pushing;

        var data = new FormData();
        data.append('action', 'imn_w2_bulk_push');
        data.append('post_id', postId);
        data.append('nonce', nonce);

        fetch(imn_w2_posts.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                link.classList.remove('imn-w2-pushing');

                if (res.success) {
                    link.classList.add('imn-w2-pushed');
                    link.textContent = imn_w2_posts.i18n.pushed;

                    setTimeout(function () {
                        link.classList.remove('imn-w2-pushed');
                        link.textContent = originalText;
                    }, 3000);
                } else {
                    link.classList.add('imn-w2-error');
                    link.textContent = imn_w2_posts.i18n.error;
                    console.error('IndexMeNow:', res.data.message || imn_w2_posts.i18n.error_generic);

                    setTimeout(function () {
                        link.classList.remove('imn-w2-error');
                        link.textContent = originalText;
                    }, 3000);
                }
            })
            .catch(function () {
                link.classList.remove('imn-w2-pushing');
                link.classList.add('imn-w2-error');
                link.textContent = imn_w2_posts.i18n.error;

                setTimeout(function () {
                    link.classList.remove('imn-w2-error');
                    link.textContent = originalText;
                }, 3000);
            });
    });
})();
