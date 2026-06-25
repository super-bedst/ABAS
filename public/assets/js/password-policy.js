(function () {
    'use strict';

    function initPasswordPolicy(formId, opts) {
        var form = document.getElementById(formId);
        if (!form) return;

        var p1 = form.querySelector('input[name="password"]');
        var p2 = form.querySelector('input[name="password2"]');
        var btn = form.querySelector('button[type="submit"]');
        if (!p1 || !p2 || !btn) return;

        var debounceTimer;
        var reqId = 0;
        var lastPwnedCheck = { pwd: '', result: null };
        var hasSubtle = !!(window.crypto && window.crypto.subtle);

        function pwdLen(s) {
            return Array.from(s).length;
        }

        function setRule(rule, state) {
            var el = form.querySelector('[data-rule="' + rule + '"]');
            if (!el) return;
            el.classList.remove('pw-rule-ok', 'pw-rule-bad', 'pw-rule-wait', 'pw-rule-neutral');
            if (state === 'ok') { el.textContent = '✓'; el.classList.add('pw-rule-ok'); }
            else if (state === 'bad') { el.textContent = '✗'; el.classList.add('pw-rule-bad'); }
            else if (state === 'wait') { el.textContent = '…'; el.classList.add('pw-rule-wait'); }
            else { el.textContent = '·'; el.classList.add('pw-rule-neutral'); }
        }

        function charClassRules(s) {
            var lower = false;
            var upper = false;
            try {
                lower = /\p{Ll}/u.test(s);
                upper = /\p{Lu}/u.test(s);
            } catch (e) {
                lower = /[a-zæøåäöü]/.test(s);
                upper = /[A-ZÆØÅÄÖÜ]/.test(s);
            }
            var digit = false;
            try {
                digit = /\p{N}/u.test(s);
            } catch (e2) {
                digit = /[0-9]/.test(s);
            }
            var symbol = false;
            try {
                symbol = /[^\p{L}\p{N}\s]/u.test(s);
            } catch (e3) {
                symbol = /[^a-zA-ZæøåÆØÅ0-9\s]/.test(s);
            }
            function setClassRule(rule, has) {
                setRule(rule, !s.length ? 'neutral' : (has ? 'ok' : 'bad'));
            }
            setClassRule('lower', lower);
            setClassRule('upper', upper);
            setClassRule('digit', digit);
            setClassRule('symbol', symbol);
            return lower && upper && digit && symbol;
        }

        function canSubmit(lenOk, matchOk, pwnedOk, serverFallback, compOk) {
            if (!lenOk || !matchOk || !compOk) return false;
            if (!hasSubtle) return true;
            if (serverFallback) return true;
            return pwnedOk === true;
        }

        async function sha1HexFromString(str) {
            var te = new TextEncoder();
            var buf = await crypto.subtle.digest('SHA-1', te.encode(str));
            return Array.from(new Uint8Array(buf)).map(function (b) {
                return b.toString(16).padStart(2, '0');
            }).join('').toUpperCase();
        }

        async function checkPwned(pwd, myReq) {
            if (pwdLen(pwd) < 12 || pwdLen(pwd) > 128) {
                setRule('pwned', 'neutral');
                return null;
            }
            setRule('pwned', 'wait');
            var hash = await sha1HexFromString(pwd);
            if (myReq !== reqId) return null;
            var prefix = hash.slice(0, 5);
            var suffix = hash.slice(5);
            var res = await fetch('https://api.pwnedpasswords.com/range/' + prefix, {
                headers: { 'Add-Padding': 'true' }
            });
            if (!res.ok) throw new Error('hibp');
            var text = await res.text();
            if (myReq !== reqId) return null;
            var lines = text.split('\n');
            for (var i = 0; i < lines.length; i++) {
                var parts = lines[i].trim().split(':');
                if (parts[0] && parts[0].toUpperCase() === suffix) {
                    setRule('pwned', 'bad');
                    return false;
                }
            }
            setRule('pwned', 'ok');
            return true;
        }

        function updateSync() {
            var a = p1.value;
            var b = p2.value;
            var len = pwdLen(a);
            var lenOk = len >= 12 && len <= 128;
            setRule('len', lenOk ? 'ok' : (a.length ? 'bad' : 'neutral'));
            var compOk = charClassRules(a);

            var matchOk = a.length > 0 && b.length > 0 && a === b;
            setRule('match', b.length === 0 ? 'neutral' : (matchOk ? 'ok' : 'bad'));

            if (!hasSubtle) {
                setRule('pwned', 'neutral');
                btn.disabled = !canSubmit(lenOk, matchOk, null, true, compOk);
                return;
            }

            if (!lenOk || !matchOk || !compOk) {
                setRule('pwned', 'neutral');
                lastPwnedCheck = { pwd: '', result: null };
                btn.disabled = true;
                return;
            }

            clearTimeout(debounceTimer);
            if (a === lastPwnedCheck.pwd && lastPwnedCheck.result !== null) {
                btn.disabled = !canSubmit(lenOk, matchOk, lastPwnedCheck.result, false, compOk);
                return;
            }
            btn.disabled = true;
            debounceTimer = setTimeout(function () {
                var myReq = ++reqId;
                checkPwned(a, myReq).then(function (ok) {
                    if (p1.value !== a) return;
                    if (ok === null) return;
                    lastPwnedCheck = { pwd: a, result: ok };
                    btn.disabled = !canSubmit(lenOk, matchOk, ok, false, compOk);
                }).catch(function () {
                    if (p1.value !== a) return;
                    setRule('pwned', 'neutral');
                    lastPwnedCheck = { pwd: '', result: null };
                    btn.disabled = !canSubmit(lenOk, matchOk, null, true, compOk);
                });
            }, opts && opts.debounceMs ? opts.debounceMs : 450);
        }

        p1.addEventListener('input', updateSync);
        p2.addEventListener('input', updateSync);
        btn.disabled = true;
        updateSync();
    }

    window.abasInitPasswordPolicy = initPasswordPolicy;
})();
