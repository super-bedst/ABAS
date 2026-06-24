document.addEventListener('DOMContentLoaded', function () {
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
