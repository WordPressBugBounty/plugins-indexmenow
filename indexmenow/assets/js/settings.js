/* IMN Settings v1.2.1 */
(function () {
    'use strict';

    var verifyBtn = document.getElementById('imn-w2-verify-key');
    var apiKeyInput = document.getElementById('imn_w2_api_key');
    var toggleKeyBtn = document.getElementById('imn-w2-toggle-key');
    var projectRow = document.getElementById('imn-w2-project-row');
    var projectSelect = document.getElementById('imn_w2_project_id');
    var keySpinner = document.querySelector('.imn-w2-key-spinner');
    var keyStatus = document.getElementById('imn-w2-key-status');
    var creditsDisplay = document.getElementById('imn-w2-credits-display');
    var modeRadios = document.querySelectorAll('input[name="imn_w2_project_mode"]');

    // Toggle API key visibility.
    if (toggleKeyBtn && apiKeyInput) {
        toggleKeyBtn.addEventListener('click', function () {
            var icon = toggleKeyBtn.querySelector('.dashicons');
            if (apiKeyInput.type === 'password') {
                apiKeyInput.type = 'text';
                icon.classList.remove('dashicons-visibility');
                icon.classList.add('dashicons-hidden');
            } else {
                apiKeyInput.type = 'password';
                icon.classList.remove('dashicons-hidden');
                icon.classList.add('dashicons-visibility');
            }
        });
    }

    /**
     * Escape HTML entities to prevent XSS.
     */
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Toggle project select based on mode radio.
    function updateProjectSelectState() {
        var mode = document.querySelector('input[name="imn_w2_project_mode"]:checked');
        projectSelect.disabled = !mode || mode.value !== 'existing';
    }

    modeRadios.forEach(function (radio) {
        radio.addEventListener('change', updateProjectSelectState);
    });

    // Verify key & load projects via AJAX.
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function () {
            var apiKey = apiKeyInput.value.trim();
            if (!apiKey) return;

            verifyBtn.disabled = true;
            keySpinner.classList.add('is-active');
            keyStatus.style.display = 'none';

            var data = new FormData();
            data.append('action', 'imn_w2_fetch_projects');
            data.append('nonce', imn_w2_settings.nonce);
            data.append('api_key', apiKey);

            fetch(imn_w2_settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    keySpinner.classList.remove('is-active');
                    keyStatus.style.display = 'inline-block';
                    verifyBtn.disabled = false;

                    if (!res.success) {
                        keyStatus.className = 'imn-w2-push-status--error';
                        keyStatus.textContent = res.data.message || imn_w2_settings.i18n.error;
                        return;
                    }

                    keyStatus.className = 'imn-w2-push-status--success';
                    keyStatus.textContent = '\u2713';

                    // Update credits display.
                    if (creditsDisplay && res.data.credits !== undefined) {
                        creditsDisplay.textContent = res.data.credits;
                    }

                    // Show project row.
                    projectRow.style.display = '';

                    // Populate select safely.
                    var currentVal = projectSelect.value;
                    var projects = res.data.projects || [];

                    if (projects.length === 0) {
                        keyStatus.textContent = '\u2713 ' + imn_w2_settings.i18n.no_projects;
                    }

                    // Clear existing options.
                    projectSelect.innerHTML = '';

                    // Add default option.
                    var defaultOpt = document.createElement('option');
                    defaultOpt.value = '0';
                    defaultOpt.textContent = imn_w2_settings.i18n.select;
                    projectSelect.appendChild(defaultOpt);

                    // Add project options.
                    projects.forEach(function (p) {
                        var opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.name + ' (' + (p.total_urls || 0) + ' URLs)';
                        if (String(p.id) === currentVal) {
                            opt.selected = true;
                        }
                        projectSelect.appendChild(opt);
                    });
                })
                .catch(function () {
                    keySpinner.classList.remove('is-active');
                    keyStatus.style.display = 'inline-block';
                    keyStatus.className = 'imn-w2-push-status--error';
                    keyStatus.textContent = imn_w2_settings.i18n.error;
                    verifyBtn.disabled = false;
                });
        });
    }

    // Init state on load.
    updateProjectSelectState();

    // ---------- Refresh Credits ----------

    var refreshCreditsBtn = document.getElementById('imn-w2-refresh-credits');
    var creditsSpinner = document.querySelector('.imn-w2-credits-spinner');

    if (refreshCreditsBtn) {
        refreshCreditsBtn.addEventListener('click', function () {
            refreshCreditsBtn.disabled = true;
            if (creditsSpinner) creditsSpinner.classList.add('is-active');

            var data = new FormData();
            data.append('action', 'imn_w2_refresh_credits');
            data.append('nonce', imn_w2_settings.credits_nonce);

            fetch(imn_w2_settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (creditsSpinner) creditsSpinner.classList.remove('is-active');
                    refreshCreditsBtn.disabled = false;

                    if (res.success && creditsDisplay) {
                        creditsDisplay.textContent = res.data.credits;
                    }
                })
                .catch(function () {
                    if (creditsSpinner) creditsSpinner.classList.remove('is-active');
                    refreshCreditsBtn.disabled = false;
                });
        });
    }

    // ---------- Push History ----------

    var historyBody = document.getElementById('imn-w2-history-body');
    var historyPagination = document.getElementById('imn-w2-history-pagination');

    function loadHistory(page) {
        if (!historyBody) return;

        historyBody.innerHTML = '<tr><td colspan="6">' + escHtml(imn_w2_settings.i18n.loading_history || 'Loading...') + '</td></tr>';

        var data = new FormData();
        data.append('action', 'imn_w2_push_history');
        data.append('nonce', imn_w2_settings.history_nonce);
        data.append('page', page || 1);

        fetch(imn_w2_settings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (!res.success || !res.data.entries || res.data.entries.length === 0) {
                    historyBody.innerHTML = '<tr><td colspan="6">' + escHtml(imn_w2_settings.i18n.no_history || 'No push history yet.') + '</td></tr>';
                    if (historyPagination) historyPagination.innerHTML = '';
                    return;
                }

                var html = '';
                res.data.entries.forEach(function (e) {
                    var statusClass = 'imn-w2-history-badge imn-w2-history-badge--' + escHtml(e.status);
                    var postLink = e.post_id > 0
                        ? '<a href="post.php?post=' + escHtml(String(e.post_id)) + '&action=edit">#' + escHtml(String(e.post_id)) + '</a>'
                        : '—';
                    html += '<tr>';
                    html += '<td>' + escHtml(e.pushed_at) + '</td>';
                    html += '<td class="imn-w2-history-url-cell">' + escHtml(e.url) + '</td>';
                    html += '<td>' + postLink + '</td>';
                    html += '<td><span class="' + statusClass + '">' + escHtml(e.status) + '</span></td>';
                    html += '<td>' + escHtml(e.push_trigger) + '</td>';
                    html += '<td>' + escHtml(e.message) + '</td>';
                    html += '</tr>';
                });
                historyBody.innerHTML = html;

                // Pagination.
                if (historyPagination && res.data.total_pages > 1) {
                    var pHtml = '';
                    for (var i = 1; i <= res.data.total_pages; i++) {
                        if (i === res.data.page) {
                            pHtml += '<span class="imn-w2-history-page imn-w2-history-page--current">' + i + '</span> ';
                        } else {
                            pHtml += '<a href="#" class="imn-w2-history-page" data-page="' + i + '">' + i + '</a> ';
                        }
                    }
                    historyPagination.innerHTML = pHtml;
                } else if (historyPagination) {
                    historyPagination.innerHTML = '';
                }
            })
            .catch(function () {
                historyBody.innerHTML = '<tr><td colspan="6">' + escHtml(imn_w2_settings.i18n.error_history || 'Error loading history.') + '</td></tr>';
            });
    }

    // Handle pagination clicks.
    if (historyPagination) {
        historyPagination.addEventListener('click', function (e) {
            var link = e.target.closest('.imn-w2-history-page[data-page]');
            if (!link) return;
            e.preventDefault();
            loadHistory(parseInt(link.dataset.page, 10));
        });
    }

    // Load history on page load.
    if (historyBody) {
        loadHistory(1);
    }

    // ---------- Purge History ----------

    var purgeBtn = document.getElementById('imn-w2-purge-history');
    var purgeDaysSelect = document.getElementById('imn-w2-purge-days');
    var purgeSpinner = document.querySelector('.imn-w2-purge-spinner');
    var purgeStatus = document.getElementById('imn-w2-purge-status');

    if (purgeBtn && purgeDaysSelect) {
        purgeBtn.addEventListener('click', function () {
            if (!confirm(imn_w2_settings.i18n.confirm_purge)) {
                return;
            }

            var days = purgeDaysSelect.value;
            purgeBtn.disabled = true;
            if (purgeSpinner) purgeSpinner.classList.add('is-active');
            if (purgeStatus) purgeStatus.style.display = 'none';

            var data = new FormData();
            data.append('action', 'imn_w2_purge_history');
            data.append('nonce', imn_w2_settings.purge_nonce);
            data.append('days', days);

            fetch(imn_w2_settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (purgeSpinner) purgeSpinner.classList.remove('is-active');
                    purgeBtn.disabled = false;

                    if (purgeStatus) {
                        purgeStatus.style.display = 'inline';
                        if (res.success) {
                            purgeStatus.className = 'imn-w2-push-status--success';
                            purgeStatus.textContent = res.data.message;
                            // Reload history table.
                            loadHistory(1);
                        } else {
                            purgeStatus.className = 'imn-w2-push-status--error';
                            purgeStatus.textContent = res.data.message || imn_w2_settings.i18n.purge_error;
                        }
                    }
                })
                .catch(function () {
                    if (purgeSpinner) purgeSpinner.classList.remove('is-active');
                    purgeBtn.disabled = false;
                    if (purgeStatus) {
                        purgeStatus.style.display = 'inline';
                        purgeStatus.className = 'imn-w2-push-status--error';
                        purgeStatus.textContent = imn_w2_settings.i18n.purge_error;
                    }
                });
        });
    }

    // ---------- Sitemap Push ----------

    var loadSitemapBtn = document.getElementById('imn-w2-load-sitemap');
    var sitemapSpinner = document.querySelector('.imn-w2-sitemap-spinner');
    var sitemapStatus = document.getElementById('imn-w2-sitemap-status');
    var sitemapPreview = document.getElementById('imn-w2-sitemap-preview');
    var sitemapUrlsContainer = document.getElementById('imn-w2-sitemap-urls');
    var sitemapCount = document.getElementById('imn-w2-sitemap-count');
    var pushSitemapBtn = document.getElementById('imn-w2-push-sitemap');
    var pushSitemapSpinner = document.querySelector('.imn-w2-push-sitemap-spinner');
    var pushSitemapStatus = document.getElementById('imn-w2-push-sitemap-status');
    var selectAllUrlsBtn = document.getElementById('imn-w2-select-all-urls');
    var deselectAllUrlsBtn = document.getElementById('imn-w2-deselect-all-urls');

    var sitemapUrls = [];

    if (loadSitemapBtn) {
        loadSitemapBtn.addEventListener('click', function () {
            loadSitemapBtn.disabled = true;
            if (sitemapSpinner) sitemapSpinner.classList.add('is-active');
            if (sitemapStatus) sitemapStatus.textContent = imn_w2_settings.i18n.loading_sitemap;

            var data = new FormData();
            data.append('action', 'imn_w2_get_sitemap_urls');
            data.append('nonce', imn_w2_settings.sitemap_nonce);

            fetch(imn_w2_settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (sitemapSpinner) sitemapSpinner.classList.remove('is-active');
                    loadSitemapBtn.disabled = false;

                    if (!res.success) {
                        if (sitemapStatus) {
                            sitemapStatus.className = 'imn-w2-push-status--error';
                            sitemapStatus.textContent = res.data.message || imn_w2_settings.i18n.sitemap_error;
                        }
                        return;
                    }

                    sitemapUrls = res.data.urls || [];
                    if (sitemapStatus) sitemapStatus.textContent = '';

                    // Show preview.
                    if (sitemapPreview) sitemapPreview.style.display = 'block';
                    if (sitemapCount) {
                        sitemapCount.textContent = sitemapUrls.length + ' URLs';
                    }

                    // Render URLs as checkboxes.
                    if (sitemapUrlsContainer) {
                        var html = '';
                        sitemapUrls.forEach(function (url, index) {
                            html += '<label style="display: block; margin-bottom: 4px;">';
                            html += '<input type="checkbox" class="imn-w2-sitemap-url-cb" value="' + escHtml(url) + '" checked /> ';
                            html += '<span style="word-break: break-all;">' + escHtml(url) + '</span>';
                            html += '</label>';
                        });
                        sitemapUrlsContainer.innerHTML = html;
                    }
                })
                .catch(function () {
                    if (sitemapSpinner) sitemapSpinner.classList.remove('is-active');
                    loadSitemapBtn.disabled = false;
                    if (sitemapStatus) {
                        sitemapStatus.className = 'imn-w2-push-status--error';
                        sitemapStatus.textContent = imn_w2_settings.i18n.sitemap_error;
                    }
                });
        });
    }

    // Select all URLs.
    if (selectAllUrlsBtn) {
        selectAllUrlsBtn.addEventListener('click', function () {
            var checkboxes = document.querySelectorAll('.imn-w2-sitemap-url-cb');
            checkboxes.forEach(function (cb) { cb.checked = true; });
        });
    }

    // Deselect all URLs.
    if (deselectAllUrlsBtn) {
        deselectAllUrlsBtn.addEventListener('click', function () {
            var checkboxes = document.querySelectorAll('.imn-w2-sitemap-url-cb');
            checkboxes.forEach(function (cb) { cb.checked = false; });
        });
    }

    // Push sitemap URLs.
    if (pushSitemapBtn) {
        pushSitemapBtn.addEventListener('click', function () {
            var checkboxes = document.querySelectorAll('.imn-w2-sitemap-url-cb:checked');
            var selectedUrls = [];
            checkboxes.forEach(function (cb) { selectedUrls.push(cb.value); });

            if (selectedUrls.length === 0) {
                if (pushSitemapStatus) {
                    pushSitemapStatus.className = 'imn-w2-push-status--error';
                    pushSitemapStatus.textContent = imn_w2_settings.i18n.no_urls_selected;
                }
                return;
            }

            // Confirm.
            var confirmMsg = imn_w2_settings.i18n.confirm_sitemap
                .replace('%1$d', selectedUrls.length)
                .replace('%2$d', selectedUrls.length);
            if (!confirm(confirmMsg)) {
                return;
            }

            pushSitemapBtn.disabled = true;
            if (pushSitemapSpinner) pushSitemapSpinner.classList.add('is-active');
            if (pushSitemapStatus) pushSitemapStatus.textContent = imn_w2_settings.i18n.pushing_sitemap;

            var data = new FormData();
            data.append('action', 'imn_w2_push_sitemap');
            data.append('nonce', imn_w2_settings.sitemap_nonce);
            selectedUrls.forEach(function (url) {
                data.append('urls[]', url);
            });

            fetch(imn_w2_settings.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data,
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (pushSitemapSpinner) pushSitemapSpinner.classList.remove('is-active');
                    pushSitemapBtn.disabled = false;

                    if (pushSitemapStatus) {
                        if (res.success) {
                            pushSitemapStatus.className = 'imn-w2-push-status--success';
                            pushSitemapStatus.textContent = res.data.message;
                            // Reload history.
                            loadHistory(1);
                        } else {
                            pushSitemapStatus.className = 'imn-w2-push-status--error';
                            pushSitemapStatus.textContent = res.data.message || imn_w2_settings.i18n.sitemap_error;
                        }
                    }
                })
                .catch(function () {
                    if (pushSitemapSpinner) pushSitemapSpinner.classList.remove('is-active');
                    pushSitemapBtn.disabled = false;
                    if (pushSitemapStatus) {
                        pushSitemapStatus.className = 'imn-w2-push-status--error';
                        pushSitemapStatus.textContent = imn_w2_settings.i18n.sitemap_error;
                    }
                });
        });
    }
})();
