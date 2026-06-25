(function () {
    'use strict';

    function initInstallationAutoRefresh(config) {
        if (!config || !config.url) {
            return;
        }

        var intervalMs = config.intervalMs || 5000;
        var initialSessionActive = !!config.sessionActive;
        var initialExternalActive = !!config.externalActive;
        var logRows = document.getElementById('inst-log-rows');
        var logBody = document.getElementById('inst-log-body');
        var logEmpty = document.getElementById('inst-log-empty');
        var logError = document.getElementById('inst-log-error');
        var serviceStatus = document.getElementById('inst-service-status');
        var zonesContent = document.getElementById('inst-zones-content');
        var spinner = document.getElementById('inst-log-spinner');
        var logHeightKey = 'abas_inst_log_height';
        var busy = false;

        function applySavedLogHeight() {
            if (!logBody) {
                return;
            }
            try {
                var saved = localStorage.getItem(logHeightKey);
                if (saved) {
                    var px = parseInt(saved, 10);
                    if (px >= 80 && px <= Math.min(window.innerHeight * 0.9, 1200)) {
                        logBody.style.height = px + 'px';
                    }
                }
            } catch (e) {}
        }

        function saveLogHeight() {
            if (!logBody || !logBody.style.height) {
                return;
            }
            try {
                localStorage.setItem(logHeightKey, String(Math.round(logBody.offsetHeight)));
            } catch (e) {}
        }

        function syncLogBodyHeight() {
            if (!logBody || logBody.classList.contains('hidden')) {
                return;
            }
            if (logBody.style.height) {
                return;
            }
            logBody.style.height = 'auto';
            var contentHeight = logBody.scrollHeight;
            var min = 80;
            var max = Math.min(window.innerHeight * 0.7, 672);
            logBody.style.height = Math.max(min, Math.min(contentHeight, max)) + 'px';
        }

        applySavedLogHeight();
        syncLogBodyHeight();
        if (logBody && typeof ResizeObserver !== 'undefined') {
            var resizeObserver = new ResizeObserver(function () {
                saveLogHeight();
            });
            resizeObserver.observe(logBody);
        }

        function setSpinner(visible) {
            if (!spinner) {
                return;
            }
            spinner.classList.toggle('hidden', !visible);
        }

        function refreshInstallationView() {
            if (busy || document.hidden) {
                return;
            }
            busy = true;
            setSpinner(true);

            fetch(config.url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.error) {
                        return;
                    }
                    if (data.sessionActive !== initialSessionActive || data.externalActive !== initialExternalActive) {
                        window.location.reload();
                        return;
                    }
                    if (serviceStatus && data.sessionLabel) {
                        serviceStatus.textContent = data.sessionLabel;
                    }
                    if (data.logCode !== 0) {
                        if (logError) {
                            logError.textContent = 'Log kunne ikke hentes (kode ' + data.logCode + ').';
                            logError.classList.remove('hidden');
                        }
                        if (logBody) {
                            logBody.classList.add('hidden');
                        }
                        if (logEmpty) {
                            logEmpty.classList.add('hidden');
                        }
                        return;
                    }
                    if (logError) {
                        logError.classList.add('hidden');
                    }
                    if (data.logEmpty) {
                        if (logBody) {
                            logBody.classList.add('hidden');
                        }
                        if (logEmpty) {
                            logEmpty.classList.remove('hidden');
                        }
                        if (logRows) {
                            logRows.innerHTML = '';
                        }
                        return;
                    }
                    if (logEmpty) {
                        logEmpty.classList.add('hidden');
                    }
                    if (logBody) {
                        logBody.classList.remove('hidden');
                    }
                    if (logRows && data.logHtml) {
                        logRows.innerHTML = data.logHtml;
                        if (!logBody.style.height) {
                            syncLogBodyHeight();
                        }
                    }
                    if (zonesContent && data.zonesHtml) {
                        zonesContent.innerHTML = data.zonesHtml;
                    }
                })
                .catch(function () {})
                .finally(function () {
                    busy = false;
                    setSpinner(false);
                });
        }

        setInterval(refreshInstallationView, intervalMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshInstallationView();
            }
        });
        refreshInstallationView();
    }

    window.abasInitInstallationAutoRefresh = initInstallationAutoRefresh;
})();
