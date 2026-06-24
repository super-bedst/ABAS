(function () {
    var cfg = window.abasVcService || {};
    var searchUrl = cfg.searchUrl || 'vc-service-search.php';

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
        this.activeIndex = -1;
        this.items = [];
        this.bind();
    }

    Combobox.prototype.bind = function () {
        var self = this;
        this.input.addEventListener('input', debounce(function () {
            self.search(self.input.value.trim());
        }, 250));
        this.input.addEventListener('focus', function () {
            if (self.type === 'montors' && self.input.value.trim() === '' && !self.hidden.value) {
                self.search('');
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

    Combobox.prototype.search = function (q) {
        var self = this;
        if (this.type === 'installations' && q.length < this.minChars) {
            this.close();
            return;
        }

        var url = searchUrl + '?type=' + encodeURIComponent(this.type) + '&q=' + encodeURIComponent(q);
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
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
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    self.pick(item);
                });
            } else {
                li.innerHTML =
                    '<span class="font-medium">' + escapeHtml(item.username) + '</span>' +
                    '<span class="text-sm text-gray-600">' +
                    (item.company_name ? escapeHtml(item.company_name) + ' · ' : '') +
                    escapeHtml(item.phone || '—') + '</span>';
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    self.pick(item);
                });
            }
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
            this.hidden.value = String(item.id);
            this.selected.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-2">' +
                '<div class="text-sm space-y-0.5">' +
                '<div><span class="text-gray-500">Navn:</span> <span class="font-medium">' + escapeHtml(item.username) + '</span></div>' +
                '<div><span class="text-gray-500">Firma:</span> ' + escapeHtml(item.company_name || '—') + '</div>' +
                '<div><span class="text-gray-500">Telefon:</span> ' + escapeHtml(item.phone || '—') + '</div>' +
                '</div>' +
                '<button type="button" class="text-sm abas-link vc-clear-pick">Skift montør</button></div>';
            this.input.value = item.username;
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

    document.addEventListener('DOMContentLoaded', function () {
        var manualFields = document.getElementById('manual-montor-fields');
        var manualName = document.getElementById('manual_montor_name');
        var manualPhone = document.getElementById('manual_montor_phone');
        var form = document.getElementById('vc-service-form');

        function syncManualFields() {
            var hasMontor = document.getElementById('montor_id').value !== '0' &&
                document.getElementById('montor_id').value !== '';
            if (hasMontor) {
                manualFields.classList.add('hidden');
                manualName.value = '';
                manualPhone.value = '';
            } else {
                manualFields.classList.remove('hidden');
            }
        }

        var instBox = new Combobox({
            input: document.getElementById('inst-search'),
            hidden: document.getElementById('miscno2'),
            list: document.getElementById('inst-results'),
            selected: document.getElementById('inst-selected'),
            type: 'installations',
            minChars: 2
        });

        var montorBox = new Combobox({
            input: document.getElementById('montor-search'),
            hidden: document.getElementById('montor_id'),
            list: document.getElementById('montor-results'),
            selected: document.getElementById('montor-selected'),
            type: 'montors',
            minChars: 0,
            onSelect: syncManualFields,
            onClear: syncManualFields
        });

        document.getElementById('inst-combobox').addEventListener('click', function (e) {
            if (e.target.classList.contains('vc-clear-pick')) {
                instBox.clear();
            }
        });
        document.getElementById('montor-combobox').addEventListener('click', function (e) {
            if (e.target.classList.contains('vc-clear-pick')) {
                montorBox.clear();
            }
        });

        form.addEventListener('submit', function (e) {
            if (!document.getElementById('miscno2').value) {
                e.preventDefault();
                alert('Vælg et anlæg fra listen.');
                return;
            }
        });

        syncManualFields();
    });
})();
