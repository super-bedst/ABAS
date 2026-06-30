(function () {
    'use strict';

    function initRegistrationRequestCard(card) {
        var approveForm = card.querySelector('.abas-reg-request-approve');
        if (!approveForm) {
            return;
        }

        var smsToggle = approveForm.querySelector('[data-reg-sms-toggle]');
        var smsPanel = approveForm.querySelector('[data-reg-sms-panel]');
        var smsInput = approveForm.querySelector('[data-reg-sms-input]');
        var roleToggle = approveForm.querySelector('[data-reg-role-virksomhedsadmin]');
        var smsModule = approveForm.querySelector('[data-reg-module="sms"]');
        var scopeDetails = approveForm.querySelector('[data-reg-scope-details]');
        var groupFilter = approveForm.querySelector('[data-reg-group-filter]');
        var groupList = approveForm.querySelector('[data-reg-group-list]');

        function syncSmsPanel() {
            if (!smsPanel) {
                return;
            }
            var showSmsModule = !roleToggle || !roleToggle.checked;
            if (smsModule) {
                smsModule.hidden = !showSmsModule;
            }
            if (!showSmsModule) {
                smsPanel.hidden = true;
                if (smsToggle) {
                    smsToggle.checked = false;
                }
                if (smsInput) {
                    smsInput.required = false;
                    smsInput.value = '';
                }
                return;
            }
            var enabled = smsToggle && smsToggle.checked;
            smsPanel.hidden = !enabled;
            if (smsInput) {
                smsInput.required = enabled;
                if (!enabled) {
                    smsInput.value = '';
                }
            }
        }

        if (smsToggle) {
            smsToggle.addEventListener('change', syncSmsPanel);
        }
        if (roleToggle) {
            roleToggle.addEventListener('change', function () {
                syncSmsPanel();
                if (scopeDetails) {
                    scopeDetails.hidden = roleToggle.checked;
                    if (roleToggle.checked) {
                        var scoped = scopeDetails.querySelector('[data-reg-scoped-toggle]');
                        if (scoped) {
                            scoped.checked = false;
                        }
                    }
                }
            });
        }

        syncSmsPanel();

        if (groupFilter && groupList) {
            groupFilter.addEventListener('input', function () {
                var query = groupFilter.value.trim().toLowerCase();
                groupList.querySelectorAll('[data-reg-group-item]').forEach(function (item) {
                    var label = item.getAttribute('data-reg-group-label') || '';
                    item.hidden = query !== '' && label.indexOf(query) === -1;
                });
            });
        }

        approveForm.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            if (!submitter || submitter.value !== 'approve') {
                return;
            }
            syncSmsPanel();
            if (smsToggle && smsToggle.checked && smsInput && smsInput.value.trim().length < 6) {
                event.preventDefault();
                smsInput.focus();
                window.alert('Angiv SMS-kode (min. 6 tegn) når SMS-betjening er aktiveret.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-reg-request-card]').forEach(initRegistrationRequestCard);
    });
})();
