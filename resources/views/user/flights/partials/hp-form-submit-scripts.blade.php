<div id="hp-submit-overlay" class="hp-submit-overlay" hidden role="alert" aria-live="assertive">
    <div class="hp-submit-overlay__panel">
        <i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i>
        <div class="hp-submit-overlay__text">Processing...</div>
    </div>
</div>

<style>
.hp-submit-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(3px);
}
.hp-submit-overlay[hidden] {
    display: none !important;
}
.hp-submit-overlay__panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .85rem;
    min-width: 240px;
    max-width: min(92vw, 360px);
    padding: 1.5rem 2rem;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    text-align: center;
}
.hp-submit-overlay__panel i {
    font-size: 2.5rem;
    color: #cd1b4f;
    line-height: 1;
}
.hp-submit-overlay__text {
    font-size: .98rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.45;
}
.hp-btn-pay.is-processing,
.hp-btn-hold.is-processing {
    cursor: wait;
    pointer-events: none;
}
.hp-btn-pay:disabled,
.hp-btn-hold:disabled {
    opacity: .78;
    cursor: wait;
    transform: none;
}
</style>

<script>
(function () {
    const DEFAULT_LOADING_HTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';

    function overlayEl() {
        return document.getElementById('hp-submit-overlay');
    }

    function showOverlay(text) {
        const overlay = overlayEl();
        if (!overlay) {
            return;
        }
        const textEl = overlay.querySelector('.hp-submit-overlay__text');
        if (textEl && text) {
            textEl.textContent = text;
        }
        overlay.hidden = false;
        void overlay.offsetHeight;
    }

    function hideOverlay() {
        const overlay = overlayEl();
        if (overlay) {
            overlay.hidden = true;
        }
    }

    window.HpFormSubmit = {
        showOverlay: showOverlay,
        hideOverlay: hideOverlay,

        bind: function (config) {
            const form = document.querySelector(config.formSelector);
            const buttons = config.buttonSelector
                ? Array.from(document.querySelectorAll(config.buttonSelector))
                : [];

            if (!form || buttons.length === 0) {
                return;
            }

            if (form.getAttribute('data-hp-submit-bound') === '1') {
                return;
            }
            form.setAttribute('data-hp-submit-bound', '1');

            const loadingHtml = config.loadingHtml || DEFAULT_LOADING_HTML;
            const loadingText = config.loadingText || 'Processing...';
            const useOverlay = config.overlay !== false;
            const originals = new Map();

            buttons.forEach(function (btn) {
                originals.set(btn, btn.innerHTML);
            });

            function resetButtons() {
                buttons.forEach(function (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-processing');
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = originals.get(btn);
                });
            }

            function hideLoading() {
                form.removeAttribute('data-hp-submitting');
                form.removeAttribute('data-hp-allow-native-submit');
                hideOverlay();
                resetButtons();
            }

            function showLoading() {
                form.setAttribute('data-hp-submitting', '1');

                if (useOverlay) {
                    showOverlay(loadingText);
                }

                buttons.forEach(function (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-processing');
                    btn.setAttribute('aria-busy', 'true');
                    btn.innerHTML = loadingHtml;
                });
            }

            function validationBlocksSubmit() {
                if (form.querySelector('.is-invalid')) {
                    return true;
                }
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return true;
                }
                return false;
            }

            function submitFormNatively() {
                form.setAttribute('data-hp-allow-native-submit', '1');
                if (typeof form.requestSubmit === 'function') {
                    const active = document.activeElement;
                    if (active && buttons.indexOf(active) !== -1) {
                        form.requestSubmit(active);
                    } else if (buttons[0]) {
                        form.requestSubmit(buttons[0]);
                    } else {
                        form.requestSubmit();
                    }
                    return;
                }
                HTMLFormElement.prototype.submit.call(form);
            }

            if (config.resetOnErrors) {
                hideLoading();
            }

            function onSubmit(e) {
                if (form.getAttribute('data-hp-allow-native-submit') === '1') {
                    form.removeAttribute('data-hp-allow-native-submit');
                    return;
                }

                if (form.getAttribute('data-hp-submitting') === '1') {
                    e.preventDefault();
                    return;
                }

                if (e.defaultPrevented) {
                    hideLoading();
                    return;
                }

                if (validationBlocksSubmit()) {
                    e.preventDefault();
                    hideLoading();
                    return;
                }

                e.preventDefault();
                showLoading();

                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        submitFormNatively();
                    });
                });
            }

            // Capture — runs after DOB/passport validators registered earlier.
            form.addEventListener('submit', onSubmit, true);

            form.addEventListener('invalid', function () {
                hideLoading();
            }, true);

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    hideLoading();
                }
            });
        },
    };
})();
</script>
