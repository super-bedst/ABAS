(function () {
    'use strict';

    function initDashboardAutoRefresh(config) {
        var externalWrap = document.getElementById('abas-dashboard-external-wrap');
        var mainWrap = document.getElementById('abas-dashboard-main-wrap');
        var statusEl = document.getElementById('abas-dashboard-refresh-status');
        if (!mainWrap || !config || !config.url) {
            return;
        }

        var intervalMs = config.intervalMs || 5000;
        var busy = false;

        function setStatus(text) {
            if (statusEl) {
                statusEl.textContent = text;
            }
        }

        function refresh() {
            if (busy || document.hidden) {
                return;
            }
            busy = true;
            fetch(config.url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (data.skip) {
                        return;
                    }
                    if (externalWrap) {
                        externalWrap.innerHTML = data.externalHtml || '';
                    }
                    mainWrap.innerHTML = data.mainHtml || '';
                    if (data.updatedAt && statusEl) {
                        var dt = new Date(data.updatedAt);
                        if (!isNaN(dt.getTime())) {
                            setStatus('Opdateret ' + dt.toLocaleTimeString('da-DK', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
                        }
                    }
                })
                .catch(function () {
                    setStatus('Kunne ikke opdatere — prøver igen…');
                })
                .finally(function () {
                    busy = false;
                });
        }

        setInterval(refresh, intervalMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refresh();
            }
        });
        setStatus('Opdateres automatisk');
        refresh();
    }

    window.abasInitDashboardAutoRefresh = initDashboardAutoRefresh;
})();
