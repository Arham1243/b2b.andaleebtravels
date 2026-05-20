<div class="modal fade" id="adminBookingRefundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm refund status change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 js-refund-modal-intro"></p>
                <div class="js-refund-modal-process d-none">
                    <p class="small text-muted mb-2">
                        Type <strong>REFUND</strong> below to credit the vendor wallet and save the refunded status.
                    </p>
                    <input type="text" class="form-control js-refund-confirm-input" placeholder="Type REFUND" autocomplete="off">
                    <p class="small text-danger mt-2 mb-0 d-none js-refund-confirm-error">
                        Please type REFUND exactly to process the wallet credit.
                    </p>
                </div>
                <div class="alert alert-warning py-2 px-3 small mb-0 js-refund-modal-already d-none">
                    A wallet credit for this booking already exists. Only the status will be updated — no additional refund will be processed.
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary js-refund-status-only d-none">
                    Mark as refunded only
                </button>
                <button type="button" class="btn btn-primary js-refund-process-wallet d-none">
                    Process refund &amp; save
                </button>
                <button type="button" class="btn btn-primary js-refund-save-status-only d-none">
                    Save status only
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let pendingForm = null;
    const modalEl = document.getElementById('adminBookingRefundModal');

    if (!modalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const $modal = $(modalEl);

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

    function setSkipWalletRefund($form, skip) {
        $form.find('.js-skip-wallet-refund').val(skip ? '1' : '0');
    }

    function resetModal() {
        $modal.find('.js-refund-confirm-input').val('');
        $modal.find('.js-refund-confirm-error').addClass('d-none');
        $modal.find('.js-refund-modal-process, .js-refund-modal-already').addClass('d-none');
        $modal.find('.js-refund-process-wallet, .js-refund-status-only, .js-refund-save-status-only').addClass('d-none');
    }

    function openRefundModal($form) {
        resetModal();

        const bookingNumber = $form.data('bookingNumber') || '';
        const amount = $form.data('totalAmount') || '0.00';
        const vendor = $form.data('vendorName') || 'the vendor';
        const type = $form.data('bookingType') === 'hotel' ? 'hotel' : 'flight';
        const walletAlreadyCredited = String($form.data('walletRefunded') || '0') === '1';

        const intro =
            'You selected a Refunded status for ' + type + ' booking <strong>' + bookingNumber + '</strong>.' +
            (walletAlreadyCredited
                ? ''
                : (' Vendor: <strong>' + vendor + '</strong>. Amount: <strong>' + amount + ' AED</strong>.'));

        $modal.find('.js-refund-modal-intro').html(intro);

        if (walletAlreadyCredited) {
            $modal.find('.js-refund-modal-already').removeClass('d-none');
            $modal.find('.js-refund-save-status-only').removeClass('d-none');
        } else {
            $modal.find('.js-refund-modal-process').removeClass('d-none');
            $modal.find('.js-refund-process-wallet, .js-refund-status-only').removeClass('d-none');
        }

        modal.show();
    }

    function submitPendingForm(skipWalletRefund) {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm[0];
        setSkipWalletRefund(pendingForm, skipWalletRefund);
        pendingForm.data('refundConfirmed', true);
        modal.hide();
        pendingForm = null;
        form.submit();
    }

    $(document).on('submit', '.admin-booking-status-form', function(e) {
        const $form = $(this);

        if ($form.data('refundConfirmed')) {
            $form.removeData('refundConfirmed');
            return;
        }

        if (!isNewlyRefunded($form)) {
            setSkipWalletRefund($form, false);
            return;
        }

        e.preventDefault();
        pendingForm = $form;
        openRefundModal($form);
    });

    $modal.on('click', '.js-refund-process-wallet', function() {
        const typed = ($modal.find('.js-refund-confirm-input').val() || '').trim().toUpperCase();

        if (typed !== 'REFUND') {
            $modal.find('.js-refund-confirm-error').removeClass('d-none');
            return;
        }

        submitPendingForm(false);
    });

    $modal.on('click', '.js-refund-status-only', function() {
        submitPendingForm(true);
    });

    $modal.on('click', '.js-refund-save-status-only', function() {
        submitPendingForm(true);
    });

    $modal.on('hidden.bs.modal', function() {
        pendingForm = null;
    });
})();
</script>
