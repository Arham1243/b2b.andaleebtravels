<script>
(function () {
    const ADULT_MIN_AGE = 12;
    const CHILD_MIN_AGE = 2;
    const CHILD_MAX_AGE = 11;
    const INFANT_MIN_DAYS = 7;

    function parseYmd(value) {
        if (!value || typeof moment === 'undefined') return null;
        const m = moment(String(value).substring(0, 10), 'YYYY-MM-DD', true);
        return m.isValid() ? m.clone().startOf('day') : null;
    }

    function ageOnDate(reference, dob) {
        let age = reference.year() - dob.year();
        const refKey = reference.format('MMDD');
        const dobKey = dob.format('MMDD');
        if (refKey < dobKey) {
            age -= 1;
        }
        return age;
    }

    function daysSinceBirth(dob, reference) {
        return reference.diff(dob, 'days');
    }

    function normalizeType(type) {
        const code = String(type || 'ADT').toUpperCase();
        if (code === 'C06' || code === 'CNN' || code === 'CHD') return 'CHILD';
        if (code === 'INF') return 'INFANT';
        return 'ADULT';
    }

    function travelDateLabel(referenceDate) {
        return referenceDate ? ' (' + referenceDate.format('DD MMM YYYY') + ')' : '';
    }

    function earliestDate(a, b) {
        return a.isBefore(b, 'day') ? a.clone() : b.clone();
    }

    function messageForAgeOnTravelDate(type, age, daysOld, referenceDate) {
        const label = travelDateLabel(referenceDate);

        if (type === 'CHILD') {
            if (age >= CHILD_MIN_AGE && age <= CHILD_MAX_AGE) return null;
            return 'Child must be between 2 and 11 years old on the travel date' + label + '.';
        }

        if (type === 'INFANT') {
            if (daysOld < INFANT_MIN_DAYS) {
                return 'Infant must be at least 7 days old (1 week) on the travel date' + label + '.';
            }
            if (age >= CHILD_MIN_AGE) {
                return 'Infant must be under 2 years old on the travel date' + label + '.';
            }
            return null;
        }

        if (age >= ADULT_MIN_AGE) return null;
        return 'Adult must be 12 years or older on the travel date' + label + '.';
    }

    function computeBoundsForType(paxType, referenceDate) {
        const today = moment().startOf('day');
        const ref = (referenceDate || today).clone().startOf('day');
        const type = normalizeType(paxType);
        let minDate = null;
        let maxDate = null;

        if (type === 'INFANT') {
            maxDate = earliestDate(ref.clone().subtract(INFANT_MIN_DAYS, 'days'), today);
            minDate = ref.clone().subtract(2, 'years').add(1, 'day');
        } else if (type === 'CHILD') {
            maxDate = earliestDate(ref.clone().subtract(CHILD_MIN_AGE, 'years'), today);
            minDate = ref.clone().subtract(CHILD_MAX_AGE + 1, 'years').add(1, 'day');
        } else {
            maxDate = earliestDate(ref.clone().subtract(ADULT_MIN_AGE, 'years'), today);
        }

        return {
            minDob: minDate ? minDate.format('YYYY-MM-DD') : '',
            maxDob: maxDate ? maxDate.format('YYYY-MM-DD') : '',
            minYear: minDate ? minDate.year() : null,
            maxYear: maxDate ? maxDate.year() : null,
        };
    }

    function applyBoundsToInput(input, paxType, referenceDate) {
        if (!input) return;

        const bounds = computeBoundsForType(paxType, referenceDate);
        input.dataset.paxType = String(paxType || 'ADT').toUpperCase();

        if (bounds.minDob) {
            input.dataset.minDob = bounds.minDob;
        } else {
            delete input.dataset.minDob;
        }

        if (bounds.maxDob) {
            input.dataset.maxDob = bounds.maxDob;
        } else {
            delete input.dataset.maxDob;
        }

        if (bounds.minYear !== null) {
            input.dataset.minYear = String(bounds.minYear);
        } else {
            delete input.dataset.minYear;
        }

        if (bounds.maxYear !== null) {
            input.dataset.maxYear = String(bounds.maxYear);
        } else {
            delete input.dataset.maxYear;
        }

        applyPickerBounds(input, referenceDate || moment().startOf('day'));
    }

    function applyPickerBounds(input, referenceDate) {
        if (typeof $ === 'undefined' || typeof moment === 'undefined') return;

        const wrap = input.closest('.hp-date-field');
        const display = wrap ? wrap.querySelector('.js-hp-date-display') : null;
        if (!display) return;

        const $display = $(display);
        const picker = $display.data('daterangepicker');
        if (!picker) return;

        const minFromData = input.dataset.minDob;
        const maxFromData = input.dataset.maxDob;

        if (minFromData || maxFromData) {
            if (maxFromData) {
                const maxMoment = parseYmd(maxFromData);
                if (maxMoment) picker.maxDate = maxMoment;
            }
            if (minFromData) {
                const minMoment = parseYmd(minFromData);
                if (minMoment) picker.minDate = minMoment;
            }

            if (window.HpDatePicker && typeof window.HpDatePicker.syncPassengerDobBounds === 'function') {
                window.HpDatePicker.syncPassengerDobBounds(picker, input, null);
            } else {
                if (picker.minDate) picker.minYear = picker.minDate.year();
                if (picker.maxDate) picker.maxYear = picker.maxDate.year();
                if (typeof picker.updateCalendars === 'function') picker.updateCalendars();
            }
        } else {
            const today = moment().startOf('day');
            const type = normalizeType(input.dataset.paxType);

            if (type === 'INFANT') {
                const youngestDob = referenceDate.clone().subtract(INFANT_MIN_DAYS, 'days');
                picker.maxDate = earliestDate(youngestDob, today);
                picker.minDate = referenceDate.clone().subtract(2, 'years').add(1, 'day');
            } else if (type === 'CHILD') {
                const maxDob = referenceDate.clone().subtract(CHILD_MIN_AGE, 'years');
                picker.maxDate = earliestDate(maxDob, today);
                picker.minDate = referenceDate.clone().subtract(CHILD_MAX_AGE + 1, 'years').add(1, 'day');
            } else {
                const maxDob = referenceDate.clone().subtract(ADULT_MIN_AGE, 'years');
                picker.maxDate = earliestDate(maxDob, today);
                picker.minDate = false;
            }

            if (window.HpDatePicker && typeof window.HpDatePicker.syncPassengerDobBounds === 'function') {
                window.HpDatePicker.syncPassengerDobBounds(picker, input, null);
            } else {
                if (picker.minDate) picker.minYear = picker.minDate.year();
                if (picker.maxDate) picker.maxYear = picker.maxDate.year();
                if (typeof picker.updateCalendars === 'function') picker.updateCalendars();
            }
        }

        const current = parseYmd(input.value);
        if (current) {
            if (picker.maxDate && current.isAfter(picker.maxDate, 'day')) {
                input.value = '';
                display.value = '';
            } else if (picker.minDate && current.isBefore(picker.minDate, 'day')) {
                input.value = '';
                display.value = '';
            }
        }
    }

    window.HpPassengerDob = {
        applyBoundsForType: function (input, paxType, referenceDateIso) {
            const referenceDate = parseYmd(referenceDateIso) || moment().startOf('day');
            applyBoundsToInput(input, paxType, referenceDate);
        },

        init: function (config) {
            const form = document.querySelector(config.formSelector);
            if (!form || typeof moment === 'undefined') return;

            const today = moment().startOf('day');
            const travelDate = parseYmd(config.travelDate);
            const referenceDate = travelDate || today;

            const applyAllBounds = function () {
                form.querySelectorAll('.js-hp-date-value[data-pax-type]').forEach(function (input) {
                    applyPickerBounds(input, referenceDate);
                });
            };

            applyAllBounds();

            if (typeof $ !== 'undefined') {
                $(function () {
                    applyAllBounds();
                });
            }

            form.querySelectorAll('.js-hp-date-value[data-pax-type]').forEach(function (input) {
                const validate = function () {
                    HpPassengerDob.validateField(input, today, referenceDate);
                };

                input.addEventListener('change', validate);
                input.addEventListener('input', validate);

                if (input.value) {
                    validate();
                }
            });

            form.addEventListener('submit', function (e) {
                let valid = true;

                form.querySelectorAll('.js-hp-date-value[data-pax-type]').forEach(function (input) {
                    if (input.dataset.required === '1' && !String(input.value || '').trim()) {
                        HpPassengerDob.setError(input, 'Date of birth is required.');
                        valid = false;
                        return;
                    }

                    if (!HpPassengerDob.validateField(input, today, referenceDate)) {
                        valid = false;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const firstInvalid = form.querySelector('.js-hp-date-value.is-invalid');
                    if (firstInvalid) {
                        const wrap = firstInvalid.closest('.hp-date-field');
                        const display = wrap ? wrap.querySelector('.js-hp-date-display') : null;
                        if (display) {
                            display.focus();
                            display.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            }, true);
        },

        fieldContainer: function (input) {
            return input.closest('.col-md-4') || input.parentElement;
        },

        setError: function (input, message) {
            const container = HpPassengerDob.fieldContainer(input);
            input.classList.add('is-invalid');

            let error = container ? container.querySelector('.js-hp-dob-error') : null;
            if (!error && container) {
                error = document.createElement('span');
                error.className = 'hp-field-error js-hp-dob-error';
                container.appendChild(error);
            }

            if (error) {
                error.textContent = message || '';
                error.style.display = message ? '' : 'none';
            }
        },

        clearError: function (input) {
            const container = HpPassengerDob.fieldContainer(input);
            input.classList.remove('is-invalid');

            const error = container ? container.querySelector('.js-hp-dob-error') : null;
            if (error) {
                error.textContent = '';
                error.style.display = 'none';
            }
        },

        validateField: function (input, today, referenceDate) {
            const value = String(input.value || '').trim();
            const type = normalizeType(input.dataset.paxType);
            const travelRef = referenceDate || today;

            if (!value) {
                if (input.dataset.required === '1') {
                    HpPassengerDob.setError(input, 'Date of birth is required.');
                    return false;
                }
                HpPassengerDob.clearError(input);
                return true;
            }

            const dob = parseYmd(value);
            if (!dob) {
                HpPassengerDob.setError(input, 'Enter a valid date of birth.');
                return false;
            }

            if (dob.isAfter(today)) {
                HpPassengerDob.setError(input, 'Date of birth cannot be in the future.');
                return false;
            }

            const age = ageOnDate(travelRef, dob);
            const daysOld = daysSinceBirth(dob, travelRef);
            const message = messageForAgeOnTravelDate(type, age, daysOld, travelRef);

            if (message) {
                HpPassengerDob.setError(input, message);
                return false;
            }

            HpPassengerDob.clearError(input);
            return true;
        },
    };
})();
</script>
