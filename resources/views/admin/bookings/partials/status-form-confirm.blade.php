<script>
(function() {
    function selectedValue($form, name) {
        const $radio = $form.find('input[name="' + name + '"]:checked');
        if ($radio.length) {
            return $radio.val();
        }
        const $hidden = $form.find('input[name="' + name + '"][type="hidden"]');
        return $hidden.length ? $hidden.val() : '';
    }

    function isNewlyRefunded($form) {
        const payment = selectedValue($form, 'payment_status');
        const booking = selectedValue($form, 'booking_status');
        const ticket = selectedValue($form, 'ticket_status');

        const curPayment = $form.data('currentPaymentStatus') || '';
        const curBooking = $form.data('currentBookingStatus') || '';
        const curTicket = $form.data('currentTicketStatus') || '';

        return (payment === 'refunded' && curPayment !== 'refunded')
            || (booking === 'refunded' && curBooking !== 'refunded')
            || (ticket === 'refunded' && curTicket !== 'refunded');
    }

    $(document).on('submit', '.admin-booking-status-form', function(e) {
        const $form = $(this);

        if (!isNewlyRefunded($form)) {
            return;
        }

        const bookingNumber = $form.data('bookingNumber') || '';
        const amount = $form.data('totalAmount') || '0.00';
        const vendor = $form.data('vendorName') || 'the vendor';
        const type = $form.data('bookingType') === 'hotel' ? 'hotel' : 'flight';

        const message =
            'You selected a Refunded status for this ' + type + ' booking.\n\n' +
            'Booking: ' + bookingNumber + '\n' +
            'Vendor: ' + vendor + '\n' +
            'Amount to credit to wallet: ' + amount + ' AED\n\n' +
            'This action will update the booking and credit the vendor wallet (once per booking).\n\n' +
            'Are you sure you want to save?';

        if (!confirm(message)) {
            e.preventDefault();
        }
    });
})();
</script>
