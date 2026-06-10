<script>
(function () {
    const DISPLAY_FMT = 'MMM D, YYYY';
    const BACKEND_FMT = 'YYYY-MM-DD';

    function parseBackend(value) {
        if (!value) return null;
        const m = moment(String(value).substring(0, 10), BACKEND_FMT, true);
        return m.isValid() ? m : null;
    }

    function initHpDatePickers(config) {
        if (typeof $ === 'undefined' || typeof moment === 'undefined' || !$.fn.daterangepicker) {
            return;
        }

        const defaults = {
            singleDatePicker: true,
            autoApply: true,
            showDropdowns: true,
            autoUpdateInput: false,
            linkedCalendars: false,
            locale: { format: DISPLAY_FMT },
        };

        document.querySelectorAll((config && config.rootSelector) || '.hp-date-field').forEach(function (wrap) {
            const display = wrap.querySelector('.js-hp-date-display');
            const hidden = wrap.querySelector('.js-hp-date-value');
            if (!display || !hidden) return;

            const $display = $(display);
            if ($display.data('daterangepicker')) {
                $display.data('daterangepicker').remove();
            }

            const role = display.dataset.dateRole || 'dob';
            const opts = Object.assign({}, defaults, {
                parentEl: wrap,
                opens: 'center',
                drops: 'down',
                maxDate: false,
                minDate: false,
            });

            if (role === 'dob') {
                if (config && config.maxDate) opts.maxDate = config.maxDate;
                if (config && config.minDate) opts.minDate = config.minDate;
            } else if (role === 'passport-exp') {
                const fieldMin = display.dataset.minDate;
                if (fieldMin) {
                    const minMoment = moment(fieldMin, BACKEND_FMT, true);
                    if (minMoment.isValid()) {
                        opts.minDate = minMoment.clone().startOf('day');
                        opts.startDate = minMoment.clone().startOf('day');
                    }
                }
            }

            const current = parseBackend(hidden.value);
            if (current) {
                opts.startDate = current.clone().startOf('day');
            }

            $display.daterangepicker(opts);

            HpDatePicker.syncDisplay(hidden);

            $display.off('apply.daterangepicker.hp').on('apply.daterangepicker.hp', function () {
                const picker = $display.data('daterangepicker');
                if (!picker) return;
                hidden.value = picker.startDate.format(BACKEND_FMT);
                display.value = picker.startDate.format(DISPLAY_FMT);
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            });

            if (!wrap.dataset.hpDateBound) {
                wrap.dataset.hpDateBound = '1';
                wrap.addEventListener('click', function (e) {
                    if (!$(e.target).is($display)) {
                        $display.data('daterangepicker')?.show();
                    }
                });
            }
        });
    }

    window.HpDatePicker = {
        syncDisplay: function (hiddenInput) {
            if (!hiddenInput) return;
            const wrap = hiddenInput.closest('.hp-date-field');
            const displayEl = wrap ? wrap.querySelector('.js-hp-date-display') : null;
            if (!displayEl) return;

            const m = parseBackend(hiddenInput.value);
            displayEl.value = m ? m.format(DISPLAY_FMT) : '';
            displayEl.classList.toggle('is-invalid', hiddenInput.classList.contains('is-invalid'));
        },

        setValue: function (hiddenInput, ymd) {
            if (!hiddenInput) return;
            const m = parseBackend(ymd);
            hiddenInput.value = m ? m.format(BACKEND_FMT) : '';
            HpDatePicker.syncDisplay(hiddenInput);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        init: function (config) {
            initHpDatePickers(config);

            // Re-init after global app.js date-picker hook so passport expiry keeps correct bounds.
            if (typeof $ !== 'undefined') {
                $(function () {
                    initHpDatePickers(config);
                });
            }
        },
    };
})();
</script>
