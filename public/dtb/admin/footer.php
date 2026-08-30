<?php
/** Closes the document for the demo-tape-board admin pages and inlines the shared JS. */
?>
<footer></footer>

<script>
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        /* Toast: the server adds .show when there is a message to flash. */
        var toast = document.getElementById('toast');
        if (toast && toast.classList.contains('show')) {
            setTimeout(function () { toast.classList.remove('show'); }, 4000);
        }

        /* Save button follows the owner's admin: disabled until something changes. */
        var mainForm = document.getElementById('main-form');
        var saveBtn = document.getElementById('save-btn');
        if (mainForm && saveBtn) {
            var enable = function () { saveBtn.disabled = false; };
            mainForm.addEventListener('change', enable);
            mainForm.addEventListener('input', enable);
        }
    });

    /* Copy-link buttons. navigator.clipboard is undefined on insecure origins, so
       fall back to selecting the adjacent input and, failing that, telling the user. */
    document.addEventListener('click', function (event) {
        var btn = event.target.closest ? event.target.closest('[data-copy]') : null;
        if (!btn || btn.dataset.copyBusy === '1') {
            return;
        }
        event.preventDefault();

        var text = btn.getAttribute('data-copy') || '';
        var field = btn.parentElement ? btn.parentElement.querySelector('input[type="text"]') : null;

        var finish = function (ok) {
            btn.dataset.copyBusy = '1';
            var original = btn.dataset.copyLabel || btn.textContent;
            btn.dataset.copyLabel = original;
            btn.textContent = ok ? 'Copied!' : 'Press Ctrl+C';
            setTimeout(function () {
                btn.textContent = original;
                btn.dataset.copyBusy = '0';
            }, 1800);
        };

        var fallback = function () {
            if (!field) { return finish(false); }
            field.focus();
            field.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            finish(ok);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { finish(true); }, fallback);
        } else {
            fallback();
        }
    });
}());
</script>
</body>
</html>
