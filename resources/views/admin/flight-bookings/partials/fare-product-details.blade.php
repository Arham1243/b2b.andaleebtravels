@php
    $adminDetails = $adminDetails ?? [];
    $hasProductDetails = ($adminDetails['fare_brand'] ?? '') !== ''
        || ($adminDetails['fare_basis_labels'] ?? []) !== []
        || ($adminDetails['validating_carrier'] ?? '') !== ''
        || ($adminDetails['baggage_summary'] ?? '') !== ''
        || ($adminDetails['last_ticket_date'] ?? '') !== '';
@endphp

@if ($hasProductDetails)
    <div class="bkpd-card mb-3">
        <div class="bkpd-card__section-head bkpd-card__section-head--blue"><i class="bx bx-purchase-tag"></i> Fare &amp; product</div>
        <div class="bkpd-info-rows">
            @if (($adminDetails['trip_type'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Trip type</span>
                    <span class="bkpd-info-row__val">{{ $adminDetails['trip_type'] }}</span>
                </div>
            @endif
            @if (($adminDetails['fare_brand'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Fare brand</span>
                    <span class="bkpd-info-row__val">{{ $adminDetails['fare_brand'] }}</span>
                </div>
            @endif
            @if (!empty($adminDetails['fare_basis_labels']))
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Fare basis</span>
                    <span class="bkpd-info-row__val" style="font-family:monospace;font-weight:700;">
                        {{ implode(', ', $adminDetails['fare_basis_labels']) }}
                    </span>
                </div>
            @endif
            @if (!empty($adminDetails['fare_type_tags']))
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Fare type</span>
                    <span class="bkpd-info-row__val">{{ implode(' · ', $adminDetails['fare_type_tags']) }}</span>
                </div>
            @endif
            @if (($adminDetails['validating_carrier'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Validating carrier</span>
                    <span class="bkpd-info-row__val">{{ $adminDetails['validating_carrier'] }}</span>
                </div>
            @endif
            @if (($adminDetails['cabin'] ?? '') !== '' || ($adminDetails['booking_code'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Cabin / class</span>
                    <span class="bkpd-info-row__val">
                        {{ trim(($adminDetails['cabin'] ?? '') . (($adminDetails['booking_code'] ?? '') !== '' ? ' · ' . $adminDetails['booking_code'] : '')) }}
                    </span>
                </div>
            @endif
            @if (($adminDetails['baggage_summary'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Baggage</span>
                    <span class="bkpd-info-row__val">{{ $adminDetails['baggage_summary'] }}</span>
                </div>
            @endif
            @if (($adminDetails['last_ticket_date'] ?? '') !== '')
                <div class="bkpd-info-row">
                    <span class="bkpd-info-row__label">Last ticket date</span>
                    <span class="bkpd-info-row__val">{{ $adminDetails['last_ticket_date'] }}</span>
                </div>
            @endif
        </div>
    </div>
@endif
