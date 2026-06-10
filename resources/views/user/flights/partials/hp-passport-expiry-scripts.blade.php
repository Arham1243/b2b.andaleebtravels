<script>
(function () {
    function parseYmd(value) {
        if (!value) return null;
        const parts = String(value).split('-').map(Number);
        if (parts.length !== 3 || parts.some(isNaN)) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatYmd(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function addDays(date, days) {
        const next = new Date(date.getTime());
        next.setDate(next.getDate() + days);
        return next;
    }

    function addMonths(date, months) {
        const next = new Date(date.getTime());
        next.setMonth(next.getMonth() + months);
        return next;
    }

    function startOfDay(date) {
        const next = new Date(date.getTime());
        next.setHours(0, 0, 0, 0);
        return next;
    }

    window.HpPassportExpiry = {
        init: function (config) {
            const form = document.querySelector(config.formSelector);
            const travelDate = parseYmd(config.travelDate);
            if (!form || !travelDate) return;

            const today = startOfDay(new Date());
            const travel = startOfDay(travelDate);
            const minExpiry = today > addDays(travel, 1) ? today : addDays(travel, 1);
            const sixMonthThreshold = addMonths(travel, 6);

            form.querySelectorAll('.js-passport-exp').forEach(function (input) {
                input.min = formatYmd(minExpiry);
                input.addEventListener('change', function () {
                    HpPassportExpiry.validateField(input, travel, today, sixMonthThreshold);
                });
                input.addEventListener('input', function () {
                    HpPassportExpiry.validateField(input, travel, today, sixMonthThreshold);
                });
                if (input.value) {
                    HpPassportExpiry.validateField(input, travel, today, sixMonthThreshold);
                }
            });

            form.addEventListener('submit', function (e) {
                let valid = true;
                form.querySelectorAll('.js-passport-exp').forEach(function (input) {
                    if (!HpPassportExpiry.validateField(input, travel, today, sixMonthThreshold)) {
                        valid = false;
                    }
                });
                if (!valid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const firstInvalid = form.querySelector('.js-passport-exp.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }, true);
        },

        fieldContainer: function (input) {
            return input.closest('.col-md-4') || input.parentElement;
        },

        validateField: function (input, travel, today, sixMonthThreshold) {
            const container = HpPassportExpiry.fieldContainer(input);
            const errorEl = container ? container.querySelector('.hp-passport-exp-error') : null;
            const infoEl = container ? container.querySelector('.hp-passport-exp-info') : null;
            const paxLabel = input.dataset.paxLabel || 'Passenger';
            const value = (input.value || '').trim();

            const displayEl = input.closest('.hp-date-field')?.querySelector('.js-hp-date-display');

            const clearError = function () {
                input.classList.remove('is-invalid');
                if (displayEl) displayEl.classList.remove('is-invalid');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                }
            };

            const setError = function (message) {
                input.classList.add('is-invalid');
                if (displayEl) displayEl.classList.add('is-invalid');
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.hidden = false;
                }
                if (infoEl) infoEl.hidden = true;
            };

            if (!value) {
                clearError();
                if (infoEl) infoEl.hidden = true;
                return true;
            }

            const expiry = parseYmd(value);
            if (!expiry) {
                setError(paxLabel + ': enter a valid passport expiry date.');
                return false;
            }

            const expiryDay = startOfDay(expiry);

            if (expiryDay < today) {
                setError(paxLabel + ': passport expiry cannot be in the past.');
                return false;
            }

            if (expiryDay <= travel) {
                setError(paxLabel + ': passport must be valid after the travel date (' + formatYmd(travel) + ').');
                return false;
            }

            clearError();

            if (infoEl) {
                infoEl.hidden = !(expiryDay < sixMonthThreshold);
            }

            return true;
        },
    };
})();
</script>
