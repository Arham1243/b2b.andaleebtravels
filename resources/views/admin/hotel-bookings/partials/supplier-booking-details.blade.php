@if (!empty($supplierBookingDetails))
    <div class="bkpd-card mb-3">
        <div class="bkpd-card__head" style="padding:16px 16px 0;">
            <div>
                <div class="bkpd-card__title">Supplier Booking Details</div>
                <div class="bkpd-card__sub">
                    Live data from {{ $supplierBookingDetails['supplier_label'] ?? 'supplier' }}
                    @if (($supplierBookingDetails['source'] ?? '') === 'live')
                        · synced now
                    @elseif (($supplierBookingDetails['source'] ?? '') === 'saved')
                        · saved confirmation
                    @endif
                </div>
            </div>
            @if (!empty($supplierBookingDetails['status']))
                <span class="bkp-badge bkp-badge--{{ $supplierBookingDetails['status']['tone'] }}">
                    {{ $supplierBookingDetails['status']['label'] }}
                </span>
            @endif
        </div>

        @if (!empty($supplierBookingDetails['error']))
            <div style="padding:0 16px 12px;">
                <p class="small mb-0" style="color:#b35900;">
                    <i class="bx bx-info-circle"></i>
                    Live supplier lookup unavailable. Showing the best available confirmation data.
                    {{ $supplierBookingDetails['error'] }}
                </p>
            </div>
        @endif

        @if (!empty($supplierBookingDetails['sections']))
            @foreach ($supplierBookingDetails['sections'] as $section)
                <div class="bkpd-card__section-head bkpd-card__section-head--{{ $section['tone'] ?? 'slate' }}">
                    <i class="bx {{ $section['icon'] ?? 'bx-info-circle' }}"></i> {{ $section['title'] }}
                </div>
                <div class="bkpd-info-rows">
                    @foreach ($section['rows'] as $row)
                        <div class="bkpd-info-row" @if (!empty($row['multiline'])) style="align-items:flex-start;" @endif>
                            <span class="bkpd-info-row__label">{{ $row['label'] }}</span>
                            <span class="bkpd-info-row__val"
                                @if (!empty($row['mono'])) style="font-family:monospace;font-size:.78rem;" @endif
                                @if (!empty($row['multiline'])) style="text-align:right;max-width:68%;white-space:pre-line;" @endif>
                                @if (!empty($row['badge']))
                                    <span class="badge rounded-pill bg-secondary">{{ $row['value'] }}</span>
                                @else
                                    {{ $row['value'] }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="bkpd-info-rows">
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__val" style="color:#64748b;font-weight:500;">
                        No supplier booking details are available for this reservation yet.
                    </span>
                </div>
            </div>
        @endif
    </div>
@endif
