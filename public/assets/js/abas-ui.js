(function () {
    'use strict';

    var loaderEl = null;
    var loaderText = null;

    function ensurePageLoader() {
        if (loaderEl) {
            return;
        }
        loaderEl = document.createElement('div');
        loaderEl.id = 'abas-page-loader';
        loaderEl.className = 'abas-page-loader hidden';
        loaderEl.setAttribute('role', 'status');
        loaderEl.setAttribute('aria-live', 'polite');
        loaderEl.setAttribute('aria-busy', 'true');
        loaderEl.innerHTML =
            '<div class="abas-page-loader__panel">' +
            '<span class="abas-spinner" aria-hidden="true"></span>' +
            '<span class="abas-page-loader__text">Arbejder…</span>' +
            '</div>';
        document.body.appendChild(loaderEl);
        loaderText = loaderEl.querySelector('.abas-page-loader__text');
    }

    function showPageLoading(message) {
        ensurePageLoader();
        if (loaderText) {
            loaderText.textContent = message || 'Arbejder…';
        }
        loaderEl.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function hidePageLoading() {
        if (!loaderEl) {
            return;
        }
        loaderEl.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    window.abasShowPageLoading = showPageLoading;
    window.abasHidePageLoading = hidePageLoading;

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            hidePageLoading();
        }
    });

    function loadingMessageForNav(el) {
        var msg = el.getAttribute('data-abas-loading');
        if (msg) {
            return msg;
        }
        if (el.classList.contains('abas-table-row-link') || el.classList.contains('abas-mobile-card')) {
            return 'Åbner anlæg…';
        }
        return null;
    }

    function shouldShowNavLoading(event, el, href) {
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return false;
        }
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return false;
        }
        if (el.target === '_blank' || el.hasAttribute('download')) {
            return false;
        }
        return loadingMessageForNav(el) !== null;
    }

    function bindLoadingForms() {
        document.querySelectorAll('form[data-abas-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var msg = form.getAttribute('data-abas-loading') || 'Arbejder…';
                showPageLoading(msg);

                var btn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('abas-btn-loading');
                    if (!btn.dataset.originalText) {
                        btn.dataset.originalText = btn.textContent;
                    }
                    btn.textContent = msg;
                }

                var overlay = form.closest('.abas-card, .abas-portal-form');
                if (overlay) {
                    overlay.classList.add('abas-loading-overlay');
                }
            });
        });
    }

    function bindLoadingNavigation() {
        document.addEventListener('click', function (event) {
            var row = event.target.closest('.abas-table-row-link');
            if (row && row.dataset.href) {
                if (shouldShowNavLoading(event, row, row.dataset.href)) {
                    showPageLoading(loadingMessageForNav(row));
                }
                window.location.href = row.dataset.href;
                return;
            }

            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            var msg = loadingMessageForNav(link);
            if (!msg || !shouldShowNavLoading(event, link, link.getAttribute('href'))) {
                return;
            }
            showPageLoading(msg);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindLoadingNavigation();

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            var row = event.target.closest('.abas-table-row-link');
            if (!row || !row.dataset.href) {
                return;
            }
            event.preventDefault();
            if (shouldShowNavLoading(event, row, row.dataset.href)) {
                showPageLoading(loadingMessageForNav(row));
            }
            window.location.href = row.dataset.href;
        });

        document.querySelectorAll('.abas-ack-checkbox').forEach(function (ack) {
            var form = ack.closest('form');
            if (!form) {
                return;
            }
            var btn = form.querySelector('.abas-ack-submit');
            if (!btn) {
                return;
            }
            function syncAckButton() {
                btn.disabled = !ack.checked;
            }
            ack.addEventListener('change', syncAckButton);
            syncAckButton();
        });

        bindLoadingForms();

        document.querySelectorAll('.abas-table[data-abas-client-sort]').forEach(function (table) {
            abasInitClientTableSort(table);
        });
    });
})();

function abasSetFormLoading(form, message) {
    if (!form) {
        return;
    }
    form.setAttribute('data-abas-loading', message || 'Arbejder…');
    form.dispatchEvent(new Event('submit', { cancelable: true }));
}

function abasInitModal(openButtonId, modalId) {
    var openBtn = document.getElementById(openButtonId);
    var modal = document.getElementById(modalId);
    if (!openBtn || !modal) {
        return;
    }

    var panel = modal.querySelector('.abas-modal-panel');
    var lastFocus = null;

    function syncExpanded(isOpen) {
        openBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        modal.classList.toggle('hidden', !isOpen);
        document.body.classList.toggle('overflow-hidden', isOpen);
    }

    function openModal() {
        lastFocus = document.activeElement;
        syncExpanded(true);
        var firstField = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstField) {
            firstField.focus();
        }
    }

    function closeModal() {
        syncExpanded(false);
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    openBtn.addEventListener('click', openModal);
    modal.querySelectorAll('[data-abas-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
    if (panel) {
        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }
}

window.abasInitModal = abasInitModal;

function abasInitClientTableSort(table) {
    if (!table) {
        return;
    }

    var thead = table.querySelector('thead');
    var tbody = table.querySelector('tbody');
    if (!thead || !tbody) {
        return;
    }

    var sortState = { column: -1, dir: 'asc' };

    thead.querySelectorAll('th[data-sort-col]').forEach(function (th) {
        var button = th.querySelector('.abas-table-sort');
        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            var col = parseInt(th.getAttribute('data-sort-col') || '-1', 10);
            if (col < 0) {
                return;
            }

            if (sortState.column === col) {
                sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.column = col;
                sortState.dir = 'asc';
            }

            thead.querySelectorAll('th[data-sort-col]').forEach(function (header) {
                var btn = header.querySelector('.abas-table-sort');
                if (!btn) {
                    return;
                }
                var headerCol = parseInt(header.getAttribute('data-sort-col') || '-1', 10);
                var active = headerCol === sortState.column;
                btn.classList.toggle('abas-table-sort--active', active);
                var indicator = btn.querySelector('.abas-table-sort-indicator');
                if (indicator) {
                    indicator.textContent = active ? (sortState.dir === 'asc' ? '↑' : '↓') : '';
                } else if (active) {
                    var span = document.createElement('span');
                    span.className = 'abas-table-sort-indicator';
                    span.setAttribute('aria-hidden', 'true');
                    span.textContent = sortState.dir === 'asc' ? '↑' : '↓';
                    btn.appendChild(span);
                }
            });

            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            var sortType = th.getAttribute('data-sort-type') || 'text';
            var mult = sortState.dir === 'asc' ? 1 : -1;

            rows.sort(function (a, b) {
                var leftCell = a.children[col];
                var rightCell = b.children[col];
                var left = leftCell ? leftCell.textContent.trim().toLowerCase() : '';
                var right = rightCell ? rightCell.textContent.trim().toLowerCase() : '';
                if (sortType === 'number') {
                    var leftNum = parseFloat(left.replace(/[^\d.-]/g, '')) || 0;
                    var rightNum = parseFloat(right.replace(/[^\d.-]/g, '')) || 0;
                    return mult * (leftNum - rightNum);
                }
                return mult * left.localeCompare(right, 'da');
            });

            rows.forEach(function (row) {
                tbody.appendChild(row);
            });
        });
    });
}

window.abasInitClientTableSort = abasInitClientTableSort;
