(function () {
    'use strict';

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (document.querySelector('script[src="' + src + '"]')) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.crossOrigin = 'anonymous';
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error('script load failed')); };
            document.head.appendChild(script);
        });
    }

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
        var logLoading = document.getElementById('inst-log-loading');
        var serviceStatus = document.getElementById('inst-service-status');
        var zonesContent = document.getElementById('inst-zones-content');
        var contactsContent = document.getElementById('inst-contacts-content');
        var mapWrap = document.getElementById('inst-map-wrap');
        var mapEl = document.getElementById('inst-map');
        var alidWrap = document.getElementById('inst-alid-wrap');
        var alidEl = document.getElementById('inst-alid');
        var spinner = document.getElementById('inst-log-spinner');
        var logHeightKey = 'abas_inst_log_height';
        var busy = false;
        var mapInstance = null;
        var detailsLoaded = false;

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

        function showInitialLoading() {
            if (typeof window.abasShowPageLoading === 'function' && config.deferInitial) {
                window.abasShowPageLoading('Henter anlægsdata…');
            }
        }

        function hideInitialLoading() {
            if (typeof window.abasHidePageLoading === 'function' && config.deferInitial) {
                window.abasHidePageLoading();
            }
        }

        function initMap(lat, lon) {
            if (!mapEl || mapInstance !== null) {
                return Promise.resolve();
            }
            var leafletSrc = config.leafletScript || 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            return loadScript(leafletSrc).then(function () {
                if (typeof window.L === 'undefined') {
                    return;
                }
                if (mapWrap) {
                    mapWrap.classList.add('hidden');
                }
                mapEl.classList.remove('hidden');
                mapInstance = window.L.map(mapEl, { scrollWheelZoom: false }).setView([lat, lon], 16);
                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(mapInstance);
                window.L.marker([lat, lon]).addTo(mapInstance);
                setTimeout(function () { mapInstance.invalidateSize(); }, 100);
            }).catch(function () {
                if (mapWrap) {
                    mapWrap.textContent = 'Kort kunne ikke indlæses.';
                }
            });
        }

        function applyDetails(data) {
            if (contactsContent && data.contactsHtml !== undefined) {
                contactsContent.innerHTML = data.contactsHtml;
            }

            if (data.alid && alidWrap && alidEl) {
                alidEl.textContent = data.alid;
                alidWrap.classList.remove('hidden');
            }

            if (data.mapLat !== null && data.mapLat !== undefined && data.mapLon !== null && data.mapLon !== undefined) {
                initMap(Number(data.mapLat), Number(data.mapLon));
            } else if (mapWrap) {
                mapWrap.innerHTML = '<p class="text-gray-500 text-xs text-center px-3">GPS-koordinater ikke tilgængelige for dette anlæg.</p>';
                mapWrap.classList.remove('hidden');
                if (mapEl) {
                    mapEl.classList.add('hidden');
                }
            }

            detailsLoaded = true;
        }

        function applyLog(data) {
            if (logLoading) {
                logLoading.classList.add('hidden');
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
        }

        function refreshInstallationView() {
            if (busy || document.hidden) {
                return;
            }
            busy = true;
            setSpinner(true);
            if (!detailsLoaded && config.deferInitial) {
                showInitialLoading();
            }

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
                    if (!detailsLoaded) {
                        applyDetails(data);
                        detailsLoaded = true;
                    }
                    if (config.deferInitial) {
                        applyLog(data);
                    }
                    if (zonesContent && data.zonesHtml) {
                        zonesContent.innerHTML = data.zonesHtml;
                    }
                    hideInitialLoading();
                })
                .catch(function () {
                    hideInitialLoading();
                })
                .finally(function () {
                    busy = false;
                    setSpinner(false);
                });
        }

        refreshInstallationView();
        setInterval(refreshInstallationView, intervalMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshInstallationView();
            }
        });
    }

    window.abasInitInstallationAutoRefresh = initInstallationAutoRefresh;
})();
