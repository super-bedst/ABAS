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
        var busy = false;

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
