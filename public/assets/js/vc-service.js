(function () {
    var cfg = window.abasVcService || {};
    var searchUrl = cfg.searchUrl || 'vc-service-search.php';
    var pollMs = cfg.pollMs || 3000;

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

    function Combobox(options) {
        this.input = options.input;
        this.hidden = options.hidden;
        this.list = options.list;
        this.selected = options.selected;
        this.type = options.type;
        this.onSelect = options.onSelect || function () {};
        this.onClear = options.onClear || function () {};
        this.minChars = options.minChars || 2;
        this.callerUserId = 0;
        this.activeIndex = -1;
        this.items = [];
        this.bind();
    }

    Combobox.prototype.setCallerUserId = function (userId) {
        this.callerUserId = userId > 0 ? userId : 0;
        if (this.type === 'installations') {
            this.minChars = this.callerUserId > 0 ? 0 : 2;
        }
    };

    Combobox.prototype.clearCallerUserId = function () {
        this.setCallerUserId(0);
    };

    Combobox.prototype.bind = function () {
        var self = this;
        this.input.addEventListener('input', debounce(function () {
            self.search(self.input.value.trim());
        }, 250));
        this.input.addEventListener('focus', function () {
            if (self.type === 'montors' && self.input.value.trim() === '' && !self.hidden.value) {
                self.search('');
            } else if (self.type === 'installations' && self.callerUserId > 0) {
                self.search(self.input.value.trim());
            } else if (self.input.value.trim().length >= self.minChars) {
                self.search(self.input.value.trim());
            }
        });
        this.input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                self.move(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                self.move(-1);
            } else if (e.key === 'Enter') {
                if (self.activeIndex >= 0 && self.items[self.activeIndex]) {
                    e.preventDefault();
                    self.pick(self.items[self.activeIndex]);
                }
            } else if (e.key === 'Escape') {
                self.close();
            }
        });
        document.addEventListener('click', function (e) {
            if (!self.input.contains(e.target) && !self.list.contains(e.target)) {
                self.close();
            }
        });
    };

    Combobox.prototype.buildSearchUrl = function (q) {
        var url = searchUrl + '?type=' + encodeURIComponent(this.type) + '&q=' + encodeURIComponent(q);
        if (this.type === 'installations' && this.callerUserId > 0) {
            url += '&caller_user_id=' + encodeURIComponent(String(this.callerUserId));
        }
        return url;
    };

    Combobox.prototype.search = function (q) {
        var self = this;
        if (this.type === 'installations' && q.length < this.minChars) {
            if (this.callerUserId <= 0) {
                this.close();
                return;
            }
        }

        this.list.innerHTML =
            '<li class="abas-combobox-empty"><span class="abas-loading-panel justify-center">' +
            '<span class="abas-spinner" aria-hidden="true"></span><span>Søger…</span></span></li>';
        this.open();

        fetch(this.buildSearchUrl(q), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                self.items = data.items || [];
                self.render();
            })
            .catch(function () {
                self.items = [];
                self.render();
            });
    };

    Combobox.prototype.render = function () {
        var self = this;
        this.list.innerHTML = '';
        this.activeIndex = -1;

        if (this.items.length === 0) {
            var empty = document.createElement('li');
            empty.className = 'abas-combobox-empty';
            empty.textContent = this.type === 'installations' ? 'Ingen anlæg fundet' : 'Ingen montører fundet';
            this.list.appendChild(empty);
            this.open();
            return;
        }

        this.items.forEach(function (item, index) {
            var li = document.createElement('li');
            li.className = 'abas-combobox-item';
            li.setAttribute('role', 'option');
            li.tabIndex = -1;
            if (self.type === 'installations') {
                li.innerHTML =
                    '<span class="font-mono font-medium text-brand">' + escapeHtml(item.miscno2) + '</span>' +
                    '<span class="text-sm text-gray-600">' + escapeHtml(item.name || '—') +
                    (item.city ? ' · ' + escapeHtml(item.city) : '') + '</span>';
            } else {
                var label = montorDisplayName(item);
                li.innerHTML =
                    '<span class="font-medium">' + escapeHtml(label) + '</span>' +
                    '<span class="text-sm text-gray-600">' +
                    (item.company_name ? escapeHtml(item.company_name) + ' · ' : '') +
                    escapeHtml(item.phone || '—') + '</span>';
            }
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                self.pick(item);
            });
            li.dataset.index = String(index);
            self.list.appendChild(li);
        });
        this.open();
    };

    Combobox.prototype.move = function (delta) {
        if (!this.list.classList.contains('hidden') && this.items.length === 0) {
            return;
        }
        if (this.list.classList.contains('hidden')) {
            this.search(this.input.value.trim());
            return;
        }
        var max = this.items.length - 1;
        this.activeIndex = Math.max(0, Math.min(max, this.activeIndex + delta));
        var nodes = this.list.querySelectorAll('.abas-combobox-item');
        nodes.forEach(function (node, i) {
            node.classList.toggle('is-active', i === this.activeIndex);
        }, this);
    };

    Combobox.prototype.pick = function (item) {
        if (this.type === 'installations') {
            this.hidden.value = (item.miscno2 || '').toLowerCase();
            this.selected.innerHTML =
                '<div class="flex flex-wrap items-center justify-between gap-2">' +
                '<div><span class="font-mono font-semibold text-brand">' + escapeHtml(item.miscno2) + '</span>' +
                '<span class="text-sm text-gray-600 ml-2">' + escapeHtml(item.name || '') + '</span></div>' +
                '<button type="button" class="text-sm abas-link vc-clear-pick">Skift anlæg</button></div>';
            this.input.value = item.miscno2;
        } else {
            var name = montorDisplayName(item);
            this.hidden.value = String(item.id);
            this.selected.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-2">' +
                '<div class="text-sm space-y-0.5">' +
                '<div><span class="text-gray-500">Navn:</span> <span class="font-medium">' + escapeHtml(name) + '</span></div>' +
                '<div><span class="text-gray-500">Firma:</span> ' + escapeHtml(item.company_name || '—') + '</div>' +
                '<div><span class="text-gray-500">Telefon:</span> ' + escapeHtml(montorPickPhone(item)) + '</div>' +
                '</div>' +
                '<button type="button" class="text-sm abas-link vc-clear-pick">Skift montør</button></div>';
            this.input.value = name;
        }

        this.selected.classList.remove('hidden');
        this.input.classList.add('hidden');
        this.close();
        this.onSelect(item);
    };

    Combobox.prototype.clear = function () {
        this.hidden.value = this.type === 'installations' ? '' : '0';
        this.input.value = '';
        this.input.classList.remove('hidden');
        this.selected.classList.add('hidden');
        this.selected.innerHTML = '';
        this.items = [];
        this.close();
        this.onClear();
    };

    Combobox.prototype.open = function () {
        this.list.classList.remove('hidden');
        this.input.setAttribute('aria-expanded', 'true');
    };

    Combobox.prototype.close = function () {
        this.list.classList.add('hidden');
        this.input.setAttribute('aria-expanded', 'false');
        this.activeIndex = -1;
    };

    function montorDisplayName(item) {
        return String((item && (item.display_name || item.username)) || '').trim() || '—';
    }

    function montorPickPhone(item, fallback) {
        return String((item && item.phone) || fallback || '').trim();
    }

        return String(value || '').replace(/\D/g, '').replace(/^0+/, '');
    }

    function callerNameLooksLikeNumber(name, number) {
        name = String(name || '').trim();
        if (!name) {
            return true;
        }
        var nameDigits = phoneDigits(name);
        var phone = phoneDigits(number);
        if (nameDigits && phone && (nameDigits === phone || phone.endsWith(nameDigits) || nameDigits.endsWith(phone))) {
            return true;
        }
        return name.replace(/\s/g, '') === String(number || '').replace(/\s/g, '');
    }

    function callHeading(call) {
        if (call.display_name) {
            return call.display_name;
        }
        if (call.caller_name_usable && call.caller_name) {
            return call.caller_name;
        }
        return call.caller_number || 'Ukendt';
    }

    function renderServiceSessionLinks(sessions) {
        if (!sessions || !sessions.length) {
            return '';
        }
        var html = '<div class="abas-vc-call-sessions">';
        sessions.forEach(function (session) {
            html += '<a href="' + escapeHtml(session.url || '#') + '" class="abas-vc-call-session-link" data-abas-loading="Åbner service…">' +
                escapeHtml(session.label || 'Aktiv service') + '</a>';
        });
        html += '</div>';
        return html;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var manualFields = document.getElementById('manual-montor-fields');
        var manualName = document.getElementById('manual_montor_name');
        var manualPhone = document.getElementById('manual_montor_phone');
        var form = document.getElementById('vc-service-form');
        var behalfInput = document.getElementById('behalf_user_id');
        var callList = document.getElementById('vc-call-list');
        var callEmpty = document.getElementById('vc-call-empty');
        var dropHint = document.getElementById('vc-drop-hint');

        function syncManualFields() {
            var hasMontor = document.getElementById('montor_id').value !== '0' &&
                document.getElementById('montor_id').value !== '';
            var hasBehalf = behalfInput && behalfInput.value !== '0' && behalfInput.value !== '';
            if (hasMontor || hasBehalf) {
                manualFields.classList.add('hidden');
                manualName.value = '';
                manualPhone.value = '';
            } else {
                manualFields.classList.remove('hidden');
            }
        }

        function clearBehalf() {
            if (behalfInput) {
                behalfInput.value = '0';
            }
            instBox.clearCallerUserId();
        }

        function clearLinkedInstallations() {
            var linkedPanel = document.getElementById('vc-linked-installations');
            if (!linkedPanel) {
                return;
            }
            linkedPanel.innerHTML = '';
            linkedPanel.classList.add('hidden');
        }

        function renderLinkedInstallations(items) {
            var linkedPanel = document.getElementById('vc-linked-installations');
            if (!linkedPanel) {
                return;
            }
            if (!items || items.length === 0) {
                clearLinkedInstallations();
                return;
            }

            var html =
                '<p class="text-sm font-medium text-gray-800 mb-1">Koblede anlæg (valgfrit)</p>' +
                '<p class="abas-hint mb-3">Vælg om tilknyttede anlæg også skal i service samtidig.</p>' +
                '<ul class="abas-vc-linked-list">';
            items.forEach(function (item) {
                var disabled = !item.allows_service || item.in_service;
                var statusHint = item.in_service
                    ? 'Allerede i service'
                    : (!item.allows_service ? (item.mon_stat_label || 'Kan ikke sættes i service') : '');
                html +=
                    '<li><label class="abas-vc-linked-option' + (disabled ? ' abas-vc-linked-option--disabled' : '') + '">' +
                    '<input type="checkbox" name="linked_miscno2[]" value="' + escapeHtml(item.miscno2) + '" class="abas-checkbox mt-0.5"' +
                    (disabled ? ' disabled' : '') + '>' +
                    '<span><span class="font-mono font-medium text-brand">' + escapeHtml(item.miscno2) + '</span>' +
                    '<span class="text-gray-600 ml-1">' + escapeHtml(item.name || '') + '</span>' +
                    (statusHint ? '<span class="text-xs text-gray-500 block">' + escapeHtml(statusHint) + '</span>' : '') +
                    '</span></label></li>';
            });
            html += '</ul>';
            linkedPanel.innerHTML = html;
            linkedPanel.classList.remove('hidden');
        }

        function loadLinkedInstallations(installationId) {
            if (!installationId) {
                clearLinkedInstallations();
                return;
            }
            fetch(
                searchUrl + '?type=linked&installation_id=' + encodeURIComponent(String(installationId)),
                { credentials: 'same-origin', headers: { Accept: 'application/json' } }
            )
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    renderLinkedInstallations(data.items || []);
                })
                .catch(function () {
                    clearLinkedInstallations();
                });
        }

        var instBox = new Combobox({
            input: document.getElementById('inst-search'),
            hidden: document.getElementById('miscno2'),
            list: document.getElementById('inst-results'),
            selected: document.getElementById('inst-selected'),
            type: 'installations',
            minChars: 2,
            onSelect: function (item) {
                loadLinkedInstallations(item.id);
            },
            onClear: function () {
                clearLinkedInstallations();
            }
        });

        var montorBox = new Combobox({
            input: document.getElementById('montor-search'),
            hidden: document.getElementById('montor_id'),
            list: document.getElementById('montor-results'),
            selected: document.getElementById('montor-selected'),
            type: 'montors',
            minChars: 0,
            onSelect: function (item) {
                if (behalfInput && item.role && item.role !== 'montor') {
                    behalfInput.value = String(item.id);
                    document.getElementById('montor_id').value = '0';
                } else if (behalfInput) {
                    behalfInput.value = String(item.id);
                }
                syncManualFields();
            },
            onClear: function () {
                clearBehalf();
                syncManualFields();
            }
        });

        document.getElementById('inst-combobox').addEventListener('click', function (e) {
            if (e.target.classList.contains('vc-clear-pick')) {
                instBox.clear();
                clearLinkedInstallations();
            }
        });
        document.getElementById('montor-combobox').addEventListener('click', function (e) {
            if (e.target.classList.contains('vc-clear-pick')) {
                montorBox.clear();
                clearBehalf();
                syncManualFields();
            }
        });

        form.addEventListener('submit', function (e) {
            if (!document.getElementById('miscno2').value) {
                e.preventDefault();
                alert('Vælg et anlæg fra listen.');
            }
        });

        function applyCall(call) {
            var preserveInstallation = !!document.getElementById('miscno2').value;
            if (!preserveInstallation) {
                instBox.clear();
            }
            montorBox.clear();
            if (behalfInput) {
                behalfInput.value = '0';
            }
            instBox.clearCallerUserId();

            var phone = call.caller_number || '';
            var personName = call.display_name || '';

            if (call.matched_user_id && call.matched_role === 'montor') {
                montorBox.pick({
                    id: call.matched_user_id,
                    display_name: personName,
                    username: personName || 'Montør',
                    phone: call.matched_phone || phone,
                    company_name: call.matched_company_name || ''
                });
                if (behalfInput) {
                    behalfInput.value = String(call.matched_user_id);
                }
            } else if (call.matched_user_id && call.filters_installations) {
                if (behalfInput) {
                    behalfInput.value = String(call.matched_user_id);
                }
                montorBox.selected.innerHTML =
                    '<div class="flex flex-wrap items-start justify-between gap-2">' +
                    '<div class="text-sm space-y-0.5">' +
                    '<div><span class="text-gray-500">Navn:</span> <span class="font-medium">' + escapeHtml(personName) + '</span></div>' +
                    '<div><span class="text-gray-500">Rolle:</span> ' + escapeHtml(call.matched_role_label || '') + '</div>' +
                    '<div><span class="text-gray-500">Telefon:</span> ' + escapeHtml(phone) + '</div>' +
                    '</div>' +
                    '<button type="button" class="text-sm abas-link vc-clear-pick">Skift person</button></div>';
                montorBox.selected.classList.remove('hidden');
                montorBox.input.classList.add('hidden');
                montorBox.input.value = personName;
                document.getElementById('montor_id').value = '0';
                if (!preserveInstallation) {
                    instBox.setCallerUserId(call.matched_user_id);
                    instBox.search('');
                }
                syncManualFields();
            } else {
                manualName.value = personName;
                manualPhone.value = phone;
                syncManualFields();
            }

            form.classList.add('is-drop-target');
            window.setTimeout(function () {
                form.classList.remove('is-drop-target');
            }, 1200);
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function renderCalls(items) {
            if (!callList) {
                return;
            }
            callList.innerHTML = '';
            if (!items || items.length === 0) {
                if (callEmpty) {
                    callEmpty.classList.remove('hidden');
                }
                return;
            }
            if (callEmpty) {
                callEmpty.classList.add('hidden');
            }

            items.forEach(function (call) {
                var li = document.createElement('li');
                li.className = 'abas-vc-call-item';
                li.draggable = true;
                li.title = 'Træk eller klik for at udfylde formularen';
                li.setAttribute('role', 'button');
                li.setAttribute('tabindex', '0');

                var statusLabel = 'I samtale';
                var meta = call.matched_role_label
                    ? '<span class="abas-vc-call-match">' + escapeHtml(call.matched_role_label) + '</span>'
                    : '';
                var sessionsHtml = renderServiceSessionLinks(call.active_service_sessions);

                li.innerHTML =
                    '<div class="abas-vc-call-item__head">' +
                    '<span class="font-medium">' + escapeHtml(callHeading(call)) + '</span>' +
                    '<span class="abas-vc-call-status abas-vc-call-status--' + escapeHtml(call.status) + '">' + statusLabel + '</span>' +
                    '</div>' +
                    '<div class="text-sm text-gray-600 font-mono">' + escapeHtml(call.caller_number || '') + '</div>' +
                    (call.queue_name ? '<div class="text-xs text-gray-500">' + escapeHtml(call.queue_name) + '</div>' : '') +
                    meta +
                    sessionsHtml;

                li.querySelectorAll('.abas-vc-call-session-link').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                });

                li.addEventListener('dragstart', function (e) {
                    li.dataset.suppressClick = '1';
                    e.dataTransfer.setData('application/x-abas-call', JSON.stringify(call));
                    e.dataTransfer.effectAllowed = 'copy';
                    li.classList.add('is-dragging');
                });
                li.addEventListener('dragend', function () {
                    li.classList.remove('is-dragging');
                    window.setTimeout(function () {
                        delete li.dataset.suppressClick;
                    }, 0);
                });
                li.addEventListener('click', function () {
                    if (li.dataset.suppressClick === '1') {
                        return;
                    }
                    applyCall(call);
                });
                li.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        applyCall(call);
                    }
                });

                callList.appendChild(li);
            });
        }

        function pollCalls() {
            if (!callList) {
                return;
            }
            fetch(searchUrl + '?type=calls', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    renderCalls(data.items || []);
                })
                .catch(function () {});
        }

        if (form) {
            ['dragenter', 'dragover'].forEach(function (eventName) {
                form.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    form.classList.add('is-drop-hover');
                    if (dropHint) {
                        dropHint.classList.add('is-visible');
                    }
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                form.addEventListener(eventName, function (e) {
                    if (eventName === 'drop') {
                        e.preventDefault();
                        var raw = e.dataTransfer.getData('application/x-abas-call');
                        if (raw) {
                            try {
                                applyCall(JSON.parse(raw));
                            } catch (err) {}
                        }
                    }
                    form.classList.remove('is-drop-hover');
                    if (dropHint) {
                        dropHint.classList.remove('is-visible');
                    }
                });
            });
        }

        syncManualFields();
        pollCalls();
        window.setInterval(pollCalls, pollMs);
    });
})();
