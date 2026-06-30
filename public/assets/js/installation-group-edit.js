(function () {
    'use strict';

    function initInstallationGroupEditor(config) {
        if (!config || !config.searchUrl) {
            return;
        }

        var availableEl = document.getElementById('ig-available');
        var selectedEl = document.getElementById('ig-selected');
        var searchInput = document.getElementById('ig-search');
        var searchBtn = document.getElementById('ig-search-btn');
        var apiToggle = document.getElementById('ig-use-api');
        var hiddenInput = document.getElementById('ig-member-ids');
        var searchStatus = document.getElementById('ig-search-status');

        if (!availableEl || !selectedEl || !hiddenInput) {
            return;
        }

        /** @type {Map<number, {id:number, miscno2:string, name:string, city:string}>} */
        var members = new Map();
        var initial = config.initialMembers || [];
        initial.forEach(function (item) {
            members.set(Number(item.id), item);
        });

        function renderItem(item, side) {
            var li = document.createElement('li');
            li.className = 'abas-dual-list-item';
            li.draggable = true;
            li.dataset.id = String(item.id);
            li.dataset.side = side;
            li.innerHTML =
                '<span class="font-mono text-brand">' + escapeHtml(item.miscno2 || '') + '</span>' +
                '<span class="text-gray-600 truncate">' + escapeHtml(item.name || '') + '</span>' +
                (item.city ? '<span class="text-gray-400 text-xs">' + escapeHtml(item.city) + '</span>' : '');

            li.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', String(item.id));
                e.dataTransfer.setData('application/x-ig-side', side);
                li.classList.add('is-dragging');
            });
            li.addEventListener('dragend', function () {
                li.classList.remove('is-dragging');
            });
            li.addEventListener('dblclick', function () {
                if (side === 'available') {
                    addMember(item);
                } else {
                    removeMember(item.id);
                }
            });
            return li;
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function syncHidden() {
            hiddenInput.value = Array.from(members.keys()).join(',');
        }

        function renderSelected() {
            selectedEl.innerHTML = '';
            var sorted = Array.from(members.values()).sort(function (a, b) {
                return String(a.miscno2 || '').localeCompare(String(b.miscno2 || ''), 'da');
            });
            sorted.forEach(function (item) {
                selectedEl.appendChild(renderItem(item, 'selected'));
            });
            syncHidden();
        }

        function addMember(item) {
            members.set(Number(item.id), item);
            renderSelected();
            renderAvailableLast();
        }

        function removeMember(id) {
            members.delete(Number(id));
            renderSelected();
            renderAvailableLast();
        }

        var lastAvailable = [];

        function renderAvailableLast() {
            renderAvailableList(lastAvailable);
        }

        function renderAvailableList(items) {
            availableEl.innerHTML = '';
            items.forEach(function (item) {
                if (members.has(Number(item.id))) {
                    return;
                }
                availableEl.appendChild(renderItem(item, 'available'));
            });
        }

        function moveSelected(fromSide, ids) {
            ids.forEach(function (idStr) {
                var id = Number(idStr);
                if (fromSide === 'available') {
                    var found = lastAvailable.find(function (row) { return Number(row.id) === id; });
                    if (found) {
                        addMember(found);
                    }
                } else {
                    removeMember(id);
                }
            });
        }

        function selectedIdsInList(listEl) {
            var ids = [];
            listEl.querySelectorAll('.abas-dual-list-item.is-selected').forEach(function (el) {
                ids.push(el.dataset.id);
            });
            if (ids.length === 0) {
                listEl.querySelectorAll('.abas-dual-list-item').forEach(function (el) {
                    ids.push(el.dataset.id);
                });
            }
            return ids;
        }

        function setupDropZone(listEl, targetSide) {
            ['dragenter', 'dragover'].forEach(function (eventName) {
                listEl.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    listEl.classList.add('is-drop-target');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                listEl.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    listEl.classList.remove('is-drop-target');
                });
            });
            listEl.addEventListener('drop', function (e) {
                var id = e.dataTransfer.getData('text/plain');
                var fromSide = e.dataTransfer.getData('application/x-ig-side');
                if (!id) {
                    return;
                }
                if (targetSide === 'selected' && fromSide === 'available') {
                    moveSelected('available', [id]);
                } else if (targetSide === 'available' && fromSide === 'selected') {
                    moveSelected('selected', [id]);
                }
            });
        }

        function setupSelectionClick(listEl) {
            listEl.addEventListener('click', function (e) {
                var item = e.target.closest('.abas-dual-list-item');
                if (!item) {
                    return;
                }
                if (e.metaKey || e.ctrlKey) {
                    item.classList.toggle('is-selected');
                } else {
                    listEl.querySelectorAll('.abas-dual-list-item.is-selected').forEach(function (el) {
                        el.classList.remove('is-selected');
                    });
                    item.classList.add('is-selected');
                }
            });
        }

        setupDropZone(availableEl, 'available');
        setupDropZone(selectedEl, 'selected');
        setupSelectionClick(availableEl);
        setupSelectionClick(selectedEl);

        document.querySelectorAll('[data-ig-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-ig-action');
                if (action === 'add-selected') {
                    moveSelected('available', selectedIdsInList(availableEl));
                } else if (action === 'remove-selected') {
                    moveSelected('selected', selectedIdsInList(selectedEl));
                } else if (action === 'add-all') {
                    lastAvailable.forEach(function (item) {
                        if (!members.has(Number(item.id))) {
                            addMember(item);
                        }
                    });
                } else if (action === 'remove-all') {
                    members.clear();
                    renderSelected();
                    renderAvailableLast();
                }
            });
        });

        function runSearch() {
            var q = (searchInput && searchInput.value || '').trim();
            if (q === '') {
                if (searchStatus) {
                    searchStatus.textContent = 'Angiv søgetekst.';
                }
                lastAvailable = [];
                renderAvailableList([]);
                return;
            }
            if (searchStatus) {
                searchStatus.textContent = 'Søger…';
            }
            var url = config.searchUrl + '?q=' + encodeURIComponent(q);
            if (apiToggle && apiToggle.checked) {
                url += '&api=1';
            }
            fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    lastAvailable = data.installations || [];
                    renderAvailableList(lastAvailable);
                    if (searchStatus) {
                        var suffix = apiToggle && apiToggle.checked ? ' (inkl. API)' : ' (cache)';
                        searchStatus.textContent = lastAvailable.length + ' træffere' + suffix;
                    }
                })
                .catch(function () {
                    if (searchStatus) {
                        searchStatus.textContent = 'Søgning fejlede.';
                    }
                });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', runSearch);
        }
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
        }

        renderSelected();
    }

    window.abasInitInstallationGroupEditor = initInstallationGroupEditor;
})();
