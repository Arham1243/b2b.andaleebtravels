<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/intlTelInput.min.js"></script>
<script>
(function () {
    function syncTravelportPhoneField(wrapper) {
        const input = wrapper.querySelector('[data-hp-phone-input]');
        if (!input || typeof window.intlTelInput !== 'function') {
            return;
        }

        const dialInput = wrapper.querySelector('[data-hp-phone-dial-code]');
        const localInput = wrapper.querySelector('[data-hp-phone-local]');
        const displayInput = wrapper.querySelector('[data-hp-phone-display]');
        const initialIso = (input.getAttribute('data-initial-iso') || 'ae').toLowerCase();

        const iti = window.intlTelInput(input, {
            initialCountry: initialIso,
            separateDialCode: true,
            preferredCountries: ['ae', 'sa', 'qa', 'om', 'kw', 'bh', 'pk', 'in', 'gb', 'us'],
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js',
        });

        function writeHiddenFields() {
            const countryData = iti.getSelectedCountryData();
            const dialCode = (countryData && countryData.dialCode) ? String(countryData.dialCode) : '';
            const localDigits = String(input.value || '').replace(/\D+/g, '').replace(/^0+/, '');

            if (dialInput) {
                dialInput.value = dialCode;
            }
            if (localInput) {
                localInput.value = localDigits;
            }
            if (displayInput) {
                displayInput.value = localDigits !== '' ? ('+' + dialCode + localDigits) : '';
            }
        }

        input.addEventListener('countrychange', writeHiddenFields);
        input.addEventListener('input', writeHiddenFields);
        input.addEventListener('blur', writeHiddenFields);
        writeHiddenFields();

        const form = wrapper.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                writeHiddenFields();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-hp-travelport-phone]').forEach(syncTravelportPhoneField);
    });
})();
</script>
