<style>
.hp-submit-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.42);
    backdrop-filter: blur(2px);
}
.hp-submit-overlay[hidden] {
    display: none !important;
}
.hp-submit-overlay__panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
    min-width: 220px;
    padding: 1.35rem 2rem;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
}
.hp-submit-overlay__panel i {
    font-size: 2.25rem;
    color: #cd1b4f;
    line-height: 1;
}
.hp-submit-overlay__text {
    font-size: .95rem;
    font-weight: 600;
    color: #0f172a;
}
.hp-btn-pay.is-processing,
.hp-btn-hold.is-processing {
    cursor: wait;
    pointer-events: none;
}
.hp-btn-pay:disabled,
.hp-btn-hold:disabled {
    opacity: .72;
    cursor: not-allowed;
    transform: none;
}
</style>
<script>
(function () {
    const DEFAULT_LOADING_HTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing…';

    function ensureOverlay(loadingText) {
        let overlay = document.getElementById('hp-submit-overlay');
        if (overlay) {
            const textEl = overlay.querySelector('.hp-submit-overlay__text');
            if (textEl) {
                textEl.textContent = loadingText;
            }
            overlay.hidden = false;
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'hp-submit-overlay';
        overlay.className = 'hp-submit-overlay';
        overlay.setAttribute('role', 'alert');
        overlay.setAttribute('aria-live', 'assertive');
        overlay.innerHTML =
            '<div class="hp-submit-overlay__panel">' +
                '<i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i>' +
                '<div class="hp-submit-overlay__text">' + loadingText + '</div>' +
            '</div>';
        document.body.appendChild(overlay);
        return overlay;
    }

    window.HpFormSubmit = {
        bind: function (config) {
            const form = document.querySelector(config.formSelector);
            const buttons = config.buttonSelector
                ? Array.from(document.querySelectorAll(config.buttonSelector))
                : [];

            if (!form || buttons.length === 0) {
                return;
            }

            const loadingHtml = config.loadingHtml || DEFAULT_LOADING_HTML;
            const loadingText = config.loadingText || 'Processing…';
            const useOverlay = config.overlay !== false;
            const originals = new Map();

            buttons.forEach(function (btn) {
                originals.set(btn, btn.innerHTML);
                if (config.resetOnErrors) {
                    btn.disabled = false;
                    btn.classList.remove('is-processing');
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = originals.get(btn);
                }
            });

            if (config.resetOnErrors) {
                form.removeAttribute('data-hp-submitting');
                const overlay = document.getElementById('hp-submit-overlay');
                if (overlay) {
                    overlay.hidden = true;
                }
            }

            function showLoading() {
                form.setAttribute('data-hp-submitting', '1');

                if (useOverlay) {
                    ensureOverlay(loadingText);
                }

                buttons.forEach(function (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-processing');
                    btn.setAttribute('aria-busy', 'true');
                    btn.innerHTML = loadingHtml;
                });
            }

            form.addEventListener('submit', function (e) {
                if (form.getAttribute('data-hp-submitting') === '1') {
                    e.preventDefault();
                    return;
                }

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

                showLoading();
            });

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (form.getAttribute('data-hp-submitting') === '1') {
                        return;
                    }
                });
            });
        },
    };
})();
</script>
