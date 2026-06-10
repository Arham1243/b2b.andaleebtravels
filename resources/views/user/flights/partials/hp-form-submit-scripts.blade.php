<script>
(function () {
    window.HpFormSubmit = {
        bind: function (config) {
            const form = document.querySelector(config.formSelector);
            const buttons = config.buttonSelector
                ? Array.from(document.querySelectorAll(config.buttonSelector))
                : [];

            if (!form || buttons.length === 0) {
                return;
            }

            const originals = new Map();
            buttons.forEach(function (btn) {
                originals.set(btn, btn.innerHTML);
                if (config.resetOnErrors) {
                    btn.disabled = false;
                    btn.innerHTML = originals.get(btn);
                }
            });

            form.addEventListener('submit', function (e) {
                if (e.defaultPrevented) {
                    return;
                }

                if (form.querySelector('.is-invalid')) {
                    e.preventDefault();
                    return;
                }

                if (!form.checkValidity()) {
                    e.preventDefault();
                    form.reportValidity();
                    return;
                }

                buttons.forEach(function (btn) {
                    btn.disabled = true;
                    btn.innerHTML = config.loadingHtml
                        || '<i class="bx bx-loader-alt bx-spin"></i> Processing…';
                });
            });
        },
    };
})();
</script>
