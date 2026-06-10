@if ($booking->hasIssuedTicketNumbers())
    @php
        $modalId = 'eticketExportModal_' . $booking->id;
        $ticketNumbers = $booking->resolvedTicketNumbers();
        $exportRoute = $exportRoute ?? route('user.bookings.flights.eticket-pdf', $booking->id);
        $variant = $variant ?? 'compact';
        $toolbar = $toolbar ?? false;
        $isCompact = $variant === 'compact';
        $exportWrapperClass = 'eticket-export';
        if ($toolbar) {
            $exportWrapperClass .= ' eticket-export--toolbar';
        } elseif ($isCompact) {
            $exportWrapperClass .= ' eticket-export--compact mb-3';
        } else {
            $exportWrapperClass .= ' mb-3';
        }
    @endphp

    <div class="{{ $exportWrapperClass }}">
        <div class="{{ $isCompact ? 'eticket-export__actions' : 'd-flex flex-wrap gap-2' }}">
            <button type="button"
                class="{{ $isCompact ? 'eticket-action-btn eticket-action-btn--primary' : 'themeBtn eticket-export__btn' }}"
                data-eticket-action="download"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
                title="Download E-Ticket">
                <i class="bx bx-download"></i>
                <span>Download E-Ticket</span>
            </button>
            <button type="button"
                class="{{ $isCompact ? 'eticket-action-btn eticket-action-btn--secondary' : 'themeBtn eticket-export__btn' }}"
                data-eticket-action="print"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
                title="Print E-Ticket">
                <i class="bx bx-printer"></i>
                <span>Print E-Ticket</span>
            </button>
        </div>
    </div>

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
        data-eticket-export-modal data-export-route="{{ $exportRoute }}"
        data-ticket-numbers='@json($ticketNumbers)'>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">E-Ticket export options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" data-eticket-action-input value="download">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Passengers</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eticket_scope_{{ $booking->id }}"
                                id="eticket_scope_combined_{{ $booking->id }}" value="combined" checked
                                data-eticket-scope>
                            <label class="form-check-label" for="eticket_scope_combined_{{ $booking->id }}">
                                Combined (all passengers in one PDF)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eticket_scope_{{ $booking->id }}"
                                id="eticket_scope_separate_{{ $booking->id }}" value="separate" data-eticket-scope>
                            <label class="form-check-label" for="eticket_scope_separate_{{ $booking->id }}">
                                Separate (one PDF per ticket{{ count($ticketNumbers) > 1 ? ', downloaded as ZIP' : '' }})
                            </label>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Fare details</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eticket_fare_{{ $booking->id }}"
                                id="eticket_fare_with_{{ $booking->id }}" value="with" checked data-eticket-fare>
                            <label class="form-check-label" for="eticket_fare_with_{{ $booking->id }}">
                                With fare
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eticket_fare_{{ $booking->id }}"
                                id="eticket_fare_without_{{ $booking->id }}" value="without" data-eticket-fare>
                            <label class="form-check-label" for="eticket_fare_without_{{ $booking->id }}">
                                Without fare
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="themeBtn" data-eticket-confirm>
                        <span data-eticket-confirm-label>Download PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('js')
            <script>
                (function () {
                    function selectedValue(modal, selector) {
                        var input = modal.querySelector(selector + ':checked');
                        return input ? input.value : '';
                    }

                    function buildUrl(base, params) {
                        var url = new URL(base, window.location.origin);
                        Object.keys(params).forEach(function (key) {
                            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                                url.searchParams.set(key, params[key]);
                            }
                        });
                        return url.toString();
                    }

                    document.querySelectorAll('[data-eticket-export-modal]').forEach(function (modal) {
                        var baseRoute = modal.getAttribute('data-export-route') || '';
                        var ticketNumbers = [];
                        try {
                            ticketNumbers = JSON.parse(modal.getAttribute('data-ticket-numbers') || '[]');
                        } catch (e) {
                            ticketNumbers = [];
                        }

                        var actionInput = modal.querySelector('[data-eticket-action-input]');
                        var confirmBtn = modal.querySelector('[data-eticket-confirm]');
                        var confirmLabel = modal.querySelector('[data-eticket-confirm-label]');

                        document.querySelectorAll('[data-bs-target="#' + modal.id + '"]').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                if (actionInput) {
                                    actionInput.value = btn.getAttribute('data-eticket-action') || 'download';
                                }
                                if (confirmLabel) {
                                    confirmLabel.textContent = actionInput && actionInput.value === 'print'
                                        ? 'Open for print'
                                        : 'Download PDF';
                                }
                            });
                        });

                        if (!confirmBtn) {
                            return;
                        }

                        confirmBtn.addEventListener('click', function () {
                            var action = actionInput ? actionInput.value : 'download';
                            var scope = selectedValue(modal, '[data-eticket-scope]') || 'combined';
                            var fare = selectedValue(modal, '[data-eticket-fare]') || 'with';
                            var disposition = action === 'print' ? 'inline' : 'download';

                            if (action === 'print' && scope === 'separate' && ticketNumbers.length > 1) {
                                ticketNumbers.forEach(function (number, index) {
                                    setTimeout(function () {
                                        window.open(buildUrl(baseRoute, {
                                            scope: 'separate',
                                            fare: fare,
                                            disposition: 'inline',
                                            ticket: number
                                        }), '_blank');
                                    }, index * 350);
                                });
                            } else {
                                var targetUrl = buildUrl(baseRoute, {
                                    scope: scope,
                                    fare: fare,
                                    disposition: disposition
                                });

                                if (action === 'print') {
                                    window.open(targetUrl, '_blank');
                                } else {
                                    window.location.href = targetUrl;
                                }
                            }

                            var dismiss = modal.querySelector('[data-bs-dismiss="modal"]');
                            if (dismiss) {
                                dismiss.click();
                            }
                        });
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
