(function () {
    'use strict';

    var cfg = window.abasInstallationLinks || {};
    var searchUrl = cfg.searchUrl || '';
    var installationId = Number(cfg.installationId || 0);
    var linkedIds = new Set((cfg.linkedIds || []).map(function (id) { return Number(id); }));

    function debounce(fn, ms) {
        var timer;
        return function () {
            var args = arguments;
            var self = this;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(self, args);
            }, ms);
        };
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function isExcluded(id) {
        id = Number(id);
        return id <= 0 || id === installationId || linkedIds.has(id);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('inst-link-search');
        var list = document.getElementById('inst-link-results');
        var pendingWrap = document.getElementById('inst-link-pending-wrap');
        var pendingList = document.getElementById('inst-link-pending-list');
        var hiddenInputs = document.getElementById('inst-link-hidden-inputs');
        var combobox = document.getElementById('inst-link-combobox');

        if (!input || !list || !pendingWrap || !pendingList || !hiddenInputs || !searchUrl) {
            return;
        }

        /** @type {Map<number, {id:number, miscno2:string, name:string, city:string}>} */
        var pending = new Map();
        var items = [];
        var activeIndex = -1;

        function openList() {
            list.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        }

        function closeList() {
            list.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function renderPending() {
            pendingList.innerHTML = '';
            hiddenInputs.innerHTML = '';

            if (pending.size === 0) {
                pendingWrap.classList.add('hidden');
                return;
            }

            pendingWrap.classList.remove('hidden');
            Array.from(pending.values())
                .sort(function (a, b) {
                    return String(a.miscno2 || '').localeCompare(String(b.miscno2 || ''), 'da');
                })
                .forEach(function (item) {
                    var li = document.createElement('li');
                    li.className = 'flex flex-wrap items-center justify-between gap-2 border border-brand-gold/30 bg-white rounded-xl px-3 py-2 text-sm';
                    li.innerHTML =
                        '<div><span class="font-mono font-medium text-brand">' + escapeHtml(item.miscno2 || '') + '</span>' +
                        '<span class="text-gray-600 ml-2">' + escapeHtml(item.name || '') + '</span></div>' +
                        '<button type="button" class="abas-btn-secondary !py-1 !px-2 text-xs" data-remove-pending="' + item.id + '">Fjern</button>';

                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'link_installation_ids[]';
                    hidden.value = String(item.id);
                    hiddenInputs.appendChild(hidden);

                    pendingList.appendChild(li);
                });
        }

        function addPending(item) {
            var id = Number(item.id);
            if (isExcluded(id) || pending.has(id)) {
                return;
            }
            pending.set(id, item);
            input.value = '';
            closeList();
            renderPending();
        }

        function renderResults() {
            list.innerHTML = '';
            activeIndex = -1;
            var visible = items.filter(function (item) {
                return !isExcluded(item.id) && !pending.has(Number(item.id));
            });

            if (visible.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'abas-combobox-empty';
                empty.textContent = input.value.trim().length < 2 ? 'Skriv mindst 2 tegn' : 'Ingen anlæg fundet';
                list.appendChild(empty);
                openList();
                return;
            }

            visible.forEach(function (item, index) {
                var li = document.createElement('li');
                li.className = 'abas-combobox-item';
                li.setAttribute('role', 'option');
                li.innerHTML =
                    '<span class="font-mono font-medium text-brand">' + escapeHtml(item.miscno2 || '') + '</span>' +
                    '<span class="text-sm text-gray-600">' + escapeHtml(item.name || '—') +
                    (item.city ? ' · ' + escapeHtml(item.city) : '') + '</span>';
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    addPending(item);
                });
                li.dataset.index = String(index);
                list.appendChild(li);
            });
            openList();
        }

        function search(q) {
            q = q.trim();
            if (q.length < 2) {
                closeList();
                return;
            }

            list.innerHTML =
                '<li class="abas-combobox-empty"><span class="abas-loading-panel justify-center">' +
                '<span class="abas-spinner" aria-hidden="true"></span><span>Søger…</span></span></li>';
            openList();

            fetch(searchUrl + '?type=installations&q=' + encodeURIComponent(q), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    items = data.items || [];
                    renderResults();
                })
                .catch(function () {
                    items = [];
                    renderResults();
                });
        }

        input.addEventListener('input', debounce(function () {
            search(input.value);
        }, 250));

        input.addEventListener('keydown', function (e) {
            var nodes = list.querySelectorAll('.abas-combobox-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (list.classList.contains('hidden')) {
                    search(input.value);
                    return;
                }
                activeIndex = Math.min(nodes.length - 1, activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(0, activeIndex - 1);
            } else if (e.key === 'Enter' && activeIndex >= 0 && nodes[activeIndex]) {
                e.preventDefault();
                nodes[activeIndex].dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                return;
            } else if (e.key === 'Escape') {
                closeList();
                return;
            } else {
                return;
            }

            nodes.forEach(function (node, i) {
                node.classList.toggle('is-active', i === activeIndex);
            });
        });

        document.addEventListener('click', function (e) {
            if (combobox && !combobox.contains(e.target)) {
                closeList();
            }
        });

        pendingList.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-pending]');
            if (!btn) {
                return;
            }
            pending.delete(Number(btn.getAttribute('data-remove-pending')));
            renderPending();
        });
    });
})();
