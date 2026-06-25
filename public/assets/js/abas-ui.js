document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        var row = event.target.closest('.abas-table-row-link');
        if (!row || !row.dataset.href) {
            return;
        }
        window.location.href = row.dataset.href;
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        var row = event.target.closest('.abas-table-row-link');
        if (!row || !row.dataset.href) {
            return;
        }
        event.preventDefault();
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

    document.querySelectorAll('form[data-abas-loading]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var msg = form.getAttribute('data-abas-loading') || 'Arbejder…';
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

    var logSpinner = document.getElementById('inst-log-spinner');
    if (logSpinner && typeof window.instLogRefreshFetch === 'function') {
        var orig = window.instLogRefreshFetch;
        window.instLogRefreshFetch = function () {
            logSpinner.classList.remove('hidden');
            return orig().finally(function () {
                logSpinner.classList.add('hidden');
            });
        };
    }
});

function abasSetFormLoading(form, message) {
    if (!form) return;
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
