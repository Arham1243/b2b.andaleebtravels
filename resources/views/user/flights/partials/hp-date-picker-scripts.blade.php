<script>
(function () {
    const DISPLAY_FMT = 'MMM D, YYYY';
    const BACKEND_FMT = 'YYYY-MM-DD';

    function parseBackend(value) {
        if (!value) return null;
        const m = moment(String(value).substring(0, 10), BACKEND_FMT, true);
        return m.isValid() ? m : null;
    }

    window.HpDatePicker = {
        syncDisplay: function (hiddenInput) {
            if (!hiddenInput) return;
            const wrap = hiddenInput.closest('.hp-date-field');
            const displayEl = wrap ? wrap.querySelector('.js-hp-date-display') : null;
            if (!displayEl) return;

            const m = parseBackend(hiddenInput.value);
            displayEl.value = m ? m.format(DISPLAY_FMT) : '';
        },

        setValue: function (hiddenInput, ymd) {
            if (!hiddenInput) return;
            const m = parseBackend(ymd);
            hiddenInput.value = m ? m.format(BACKEND_FMT) : '';
            HpDatePicker.syncDisplay(hiddenInput);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        init: function (config) {
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

                const opts = Object.assign({}, defaults, {
                    parentEl: wrap,
                    opens: 'center',
                    drops: 'down',
                });

                if (config && config.maxDate) opts.maxDate = config.maxDate;
                if (config && config.minDate) opts.minDate = config.minDate;

                const fieldMax = display.dataset.maxDate;
                const fieldMin = display.dataset.minDate;
                if (fieldMax) opts.maxDate = moment(fieldMax, BACKEND_FMT, true);
                if (fieldMin) opts.minDate = moment(fieldMin, BACKEND_FMT, true);

                const current = parseBackend(hidden.value);
                if (current) opts.startDate = current.clone();

                $display.daterangepicker(opts);

                HpDatePicker.syncDisplay(hidden);

                $display.on('apply.daterangepicker', function (ev, picker) {
                    hidden.value = picker.startDate.format(BACKEND_FMT);
                    display.value = picker.startDate.format(DISPLAY_FMT);
                    hidden.dispatchEvent(new Event('change', { bubbles: true }));
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                });

                wrap.addEventListener('click', function (e) {
                    if (!$(e.target).is($display)) {
                        $display.data('daterangepicker')?.show();
                    }
                });
            });
        },
    };
})();
</script>
